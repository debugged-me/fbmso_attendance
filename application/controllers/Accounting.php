<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accounting extends CI_Controller
{
	private $allowedLevels = ['Admin', 'Cashier', 'Auditor'];
	private $receiptSettingsCache = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper(['url', 'form']);
		$this->load->library(['session', 'form_validation', 'term']);
		$this->load->library('securityaudit');
		$this->load->model('SettingsModel');
		$this->load->model('AuditLogModel');
		$this->config->load('mass_announcement_email', true);

		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('login');
		}
	}

	private function ensureAccess()
	{
		$level = (string)$this->session->userdata('level');
		if (!in_array($level, $this->allowedLevels, true)) {
			show_error('Access Denied', 403);
			exit;
		}
	}

	private function isAuditor()
	{
		return (string)$this->session->userdata('level') === 'Auditor';
	}

	/**
	 * Auditor can inspect accounting data but can never change it. This is a
	 * server-side check so direct requests and loading-time UI races are denied.
	 */
	private function ensureWriteAccess()
	{
		$this->ensureAccess();
		if ($this->isAuditor()) {
			$this->securityaudit->event('ACCESS_DENIED', [
				'status'      => 'denied',
				'module'      => 'Accounting',
				'target'      => (string)$this->session->userdata('username'),
				'description' => 'Auditor attempted a write operation in Accounting',
				'extra'       => ['route' => uri_string()],
			]);
			show_error('Auditor accounts have read-only access to accounting records.', 403);
			exit;
		}
	}

	/** Expenses and expense categories are managed by the Cashier only. */
	private function canManageExpenses()
	{
		return (string)$this->session->userdata('level') === 'Cashier';
	}

	/**
	 * Admin and Auditor can view expenses but not change them. A change they
	 * attempt (an old Edit link, a bookmark, a direct request) is logged and
	 * sent back to the list with a notice, rather than ending on an error page.
	 */
	private function ensureExpenseWriteAccess($backTo)
	{
		$this->ensureAccess();
		if ($this->canManageExpenses()) {
			return;
		}

		$level = (string)$this->session->userdata('level');
		$this->securityaudit->event('ACCESS_DENIED', [
			'status'      => 'denied',
			'module'      => 'Accounting',
			'target'      => (string)$this->session->userdata('username'),
			'description' => $level . ' attempted to change expenses (view-only access)',
			'extra'       => ['route' => uri_string()],
		]);
		$this->session->set_flashdata('expenses_notice', 'Expenses are managed by the Cashier. Your account can view them only.');
		redirect($backTo);
		exit;
	}

	public function index()
	{
		$this->ensureAccess();
		if (in_array((string)$this->session->userdata('level'), ['Cashier', 'Auditor'], true)) {
			redirect('Page/accounting');
			return;
		}
		redirect('Accounting/Payment');
	}

	private function currentSemSy()
	{
		return $this->term->current();
	}

	private function isValidDate($date)
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
			return false;
		}

		[$y, $m, $d] = array_map('intval', explode('-', $date));
		return checkdate($m, $d, $y);
	}

	private function tableExists($table)
	{
		return $this->db->table_exists($table);
	}

	// Records who created, edited or deleted a payment, and what it looked
	// like before/after — so a cashier's changes are visible to Admin, not silent.
	// $reason is the cashier's explanation, required when deleting.
	private function logPaymentAudit($action, $payment, $newValues = null, $reason = null)
	{
		$changedBy = trim((string)$this->session->userdata('username'));
		if ($changedBy === '') {
			$changedBy = trim((string)$this->session->userdata('IDNumber'));
		}

		$snapshot = [
			'StudentNumber' => (string)($payment->StudentNumber ?? ''),
			'ORNumber'      => (string)($payment->ORNumber ?? ''),
			'PDate'         => (string)($payment->PDate ?? ''),
			'Amount'        => (string)($payment->Amount ?? ''),
			'description'   => (string)($payment->description ?? ''),
		];

		// A new payment has no "before": its snapshot is the "after".
		$isCreate = strtolower((string)$action) === 'create';
		$oldValues = $isCreate ? null : $snapshot;
		if ($isCreate && $newValues === null) {
			$newValues = $snapshot;
		}

		$entry = [
			'payment_id'     => (int)($payment->ID ?? 0),
			'action'         => $action,
			'or_number'      => (string)($payment->ORNumber ?? ''),
			'student_number' => (string)($payment->StudentNumber ?? ''),
			'description'    => (string)($payment->description ?? ''),
			'amount'         => (float)($payment->Amount ?? 0),
			'old_values'     => $oldValues !== null ? json_encode($oldValues) : null,
			'new_values'     => $newValues !== null ? json_encode($newValues) : null,
			'reason'         => $reason !== null && trim((string)$reason) !== '' ? trim((string)$reason) : null,
			'changed_by'     => $changedBy,
			'actor_level'    => (string)$this->session->userdata('level') ?: null,
			'changed_at'     => (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s'),
		];
		// Schema_migrator adds `reason`; until it has run, log without it
		// rather than lose the entry.
		if (!$this->db->field_exists('reason', 'payment_audit_log')) {
			unset($entry['reason']);
		}
		$this->db->insert('payment_audit_log', $entry);
	}

	// Expenses, expense categories and fees feed the ledger and the payment
	// screen, so every change to them goes to audit_logs (Super Admin's Audit
	// Trail) with the record before and after, the same way payments do.
	private function logAccountingChange($action, $table, $recordPk, $old, $new, $ok, $label, $name)
	{
		$this->AuditLogModel->write(
			$action,
			'Accounting',
			$table,
			$recordPk !== null ? (string)$recordPk : null,
			$old,
			$new,
			$ok ? 1 : 0,
			$label . ($ok ? '' : ' (failed)') . ': ' . mb_substr(trim((string)$name), 0, 100)
		);
	}

	private function nextTableId($table, $idColumn)
	{
		$row = $this->db->select_max($idColumn, 'max_id')->get($table)->row();
		return (int)($row->max_id ?? 0) + 1;
	}

	// O.R. numbers are date-scoped: YYMMDD-0001, resetting to 0001 on the
	// next calendar day. This makes "today's remove tomorrow" behavior on
	// the recent-payments list line up with what the receipt number itself
	// implies — the prefix alone tells you which day it was issued.
	private function resolveOrDatePrefix($source = '')
	{
		$source = trim((string)$source);

		if ($this->isValidDate($source)) {
			return date('ymd', strtotime($source));
		}

		$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
		return $now->format('ymd');
	}

	private function formatOrNumber($datePrefix, $sequence)
	{
		return sprintf('%s-%04d', $datePrefix, (int)$sequence);
	}

	// Sequence is scoped to the payment DATE column (not a regex over the OR
	// text), so it stays correct even for legacy rows saved under the old
	// YYYY-0001 format.
	/**
	 * Take the next O.R. number from the shared counter.
	 *
	 * A MAX() scan over paymentsaccounts cannot see numbers an offline mobile
	 * cashier has already reserved, so the two would hand out the same
	 * receipt. Both paths now draw from Or_sequence instead.
	 */
	private function generateNextOrNumber($source = '')
	{
		$this->load->library('or_sequence');
		return $this->or_sequence->next($this->orNumberDate($source));
	}

	/** The upcoming O.R. number for display, without consuming it. */
	private function peekNextOrNumber($source = '')
	{
		$this->load->library('or_sequence');
		return $this->or_sequence->peek($this->orNumberDate($source));
	}

	private function orNumberDate($source = '')
	{
		return $this->isValidDate(trim((string)$source))
			? trim((string)$source)
			: (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
	}

	private function paymentFormStateFromPost(array $overrides = [])
	{
		$state = [
			'StudentNumber' => trim((string)$this->input->post('StudentNumber', true)),
			'ORNumber'      => trim((string)$this->input->post('ORNumber', true)),
			'PDate'         => trim((string)$this->input->post('PDate', true)),
			'items'         => $this->paymentItemsFromPost(),
		];

		return array_merge($state, $overrides);
	}

	// The fees being paid in one transaction, as posted by the payment form's
	// item list. Rows with neither a description nor an amount are dropped.
	private function paymentItemsFromPost()
	{
		$posted = $this->input->post('items', true);
		if (!is_array($posted)) {
			return [];
		}

		$items = [];
		foreach ($posted as $row) {
			if (!is_array($row)) {
				continue;
			}

			$description = trim((string)($row['description'] ?? ''));
			$amount      = trim((string)($row['amount'] ?? ''));
			if ($description === '' && $amount === '') {
				continue;
			}

			$items[] = [
				'description' => $description,
				'amount'      => $amount,
				'partial'     => trim((string)($row['partial'] ?? '')) !== '',
			];
		}

		return $items;
	}

	// Checks every fee in a transaction against what the student still owes
	// on it. Returns an error message, or null and fills in each item's
	// float amount and the fee price to snapshot onto its payment row.
	//
	// The amount is only checkable when the description is a configured fee
	// with a price; free-text descriptions stay unconstrained. The UI enforces
	// the same rules, but it enforces them in the browser — this is the copy
	// that actually decides.
	private function validatePaymentItems(array &$items, $studentNumber, $sem, $sy)
	{
		if (empty($items)) {
			return 'Select at least one fee description.';
		}

		$seen = [];
		foreach ($items as &$item) {
			$description = $item['description'];
			if ($description === '') {
				return 'Every item needs a description.';
			}

			$key = strtolower($description);
			if (isset($seen[$key])) {
				return $description . ' is listed more than once. Pay it as a single item.';
			}
			$seen[$key] = true;

			if (!is_numeric($item['amount']) || (float)$item['amount'] <= 0) {
				return 'Enter an amount greater than zero for ' . $description . '.';
			}
			$amount = round((float)$item['amount'], 2);

			$balance = $this->feeBalanceFor($studentNumber, $description, $sem, $sy);
			if ($balance['full'] > 0) {
				if ($balance['remaining'] <= 0.004) {
					return $description . ' is already fully paid for this term (₱' . number_format($balance['paid'], 2) . ' of ₱' . number_format($balance['full'], 2) . ').';
				}

				if ($amount > $balance['remaining'] + 0.004) {
					return 'Amount exceeds the ₱' . number_format($balance['remaining'], 2) . ' still owed on ' . $description . ' for this term.';
				}

				if ($amount + 0.004 < $balance['remaining'] && !$item['partial']) {
					return $description . ': ₱' . number_format($amount, 2) . ' is less than the ₱' . number_format($balance['remaining'], 2) . ' still owed. Tick "Partial" to record it as an instalment.';
				}
			}

			$item['amount']        = $amount;
			$item['feeFullAmount'] = $balance['full'];
		}
		unset($item);

		return null;
	}

	private function generatePaymentSubmitToken()
	{
		try {
			$token = bin2hex(random_bytes(16));
		} catch (Exception $e) {
			$token = sha1(uniqid('payment', true) . mt_rand());
		}

		$tokens = (array)$this->session->userdata('payment_submit_tokens');
		$tokens[$token] = time();

		if (count($tokens) > 20) {
			asort($tokens);
			$tokens = array_slice($tokens, -20, null, true);
		}

		$this->session->set_userdata('payment_submit_tokens', $tokens);
		return $token;
	}

	private function consumePaymentSubmitToken($token)
	{
		$token = trim((string)$token);
		if ($token === '') {
			return false;
		}

		$tokens = (array)$this->session->userdata('payment_submit_tokens');
		if (!isset($tokens[$token])) {
			return false;
		}

		unset($tokens[$token]);
		$this->session->set_userdata('payment_submit_tokens', $tokens);
		return true;
	}

	public function expenses()
	{
		$this->ensureAccess();
		if (strtoupper((string)$this->input->method()) === 'POST') {
			$this->ensureExpenseWriteAccess('Accounting/expenses');
		}

		$data['data'] = $this->SettingsModel->expenses();
		$data['data1'] = $this->SettingsModel->get_expensesCategory();

		$this->load->view('expenses', $data);

		if ($this->input->post('save')) {
			$data = array(
				'Description' => $this->input->post('Description'),
				'Amount' => $this->input->post('Amount'),
				'Responsible' => $this->input->post('Responsible'),
				'ExpenseDate' => $this->input->post('ExpenseDate'),
				'Category' => $this->input->post('Category')
			);
			$ok = $this->SettingsModel->insertexpenses($data);
			$id = $ok ? (int)$this->db->insert_id() : 0;
			$this->logAccountingChange(
				'create',
				'expenses',
				$id ?: null,
				null,
				($id ? ['expensesid' => $id] : []) + $data,
				$ok,
				'Added expense',
				$data['Description']
			);

			// Redirect back to the expenses page after saving
			redirect('Accounting/expenses');
		}
	}


	public function updateexpenses()
	{
		$this->ensureExpenseWriteAccess('Accounting/expenses');
		$expensesid = $this->input->get('expensesid');
		$result['data'] = $this->SettingsModel->getexpensesbyId($expensesid);
		$data['data1'] = $this->SettingsModel->get_expensesCategory();

		// Merge both result and data1 arrays and pass them to the view
		$this->load->view('updateexpenses', array_merge($result, $data));

		if ($this->input->post('update')) {

			$Description = $this->input->post('Description');
			$Amount = $this->input->post('Amount');
			$Responsible = $this->input->post('Responsible');
			$ExpenseDate = $this->input->post('ExpenseDate');
			$Category = $this->input->post('Category');

			$ok = $this->SettingsModel->updateexpenses($expensesid, $Description, $Amount, $Responsible, $ExpenseDate, $Category);

			// $result['data'] was read before the update: it is the "before".
			$before = $result['data'][0] ?? null;
			if ($before) {
				$this->logAccountingChange('update', 'expenses', $expensesid, (array)$before, [
					'Description' => $Description,
					'Amount'      => $Amount,
					'Responsible' => $Responsible,
					'ExpenseDate' => $ExpenseDate,
					'Category'    => $Category,
				], $ok, 'Updated expense', $Description);
			}
			$this->session->set_flashdata('expenses', 'Record updated successfully');
			redirect("Accounting/expenses");
		}
	}



	public function Deleteexpenses()
	{
		$this->ensureExpenseWriteAccess('Accounting/expenses');

		// POST only: a plain link must not be able to delete a record.
		if (strtoupper((string)$this->input->method()) !== 'POST') {
			redirect('Accounting/expenses');
			return;
		}

		$expensesid = (int)$this->input->post('expensesid');
		if ($expensesid > 0) {
			$before = $this->SettingsModel->getexpensesbyId($expensesid)[0] ?? null;
			$ok = $this->SettingsModel->Delete_expenses($expensesid);
			if ($before) {
				$this->logAccountingChange(
					'delete',
					'expenses',
					$expensesid,
					(array)$before,
					null,
					$ok,
					'Deleted expense',
					$before->Description ?? ''
				);
			}
			$this->session->set_flashdata('expenses', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('expenses', 'Error deleting record');
		}

		redirect("Accounting/expenses");
	}


	public function expensescategory()
	{
		$this->ensureAccess();
		if (strtoupper((string)$this->input->method()) === 'POST') {
			$this->ensureExpenseWriteAccess('Accounting/expensescategory');
		}
		$data['data'] = $this->SettingsModel->get_expensesCategory();
		$this->load->view('expensescategory', $data);

		if ($this->input->post('save')) {
			$data = array(
				'Category' => $this->input->post('Category'),
			);
			$ok = $this->SettingsModel->insertexpensesCategory($data);
			$id = $ok ? (int)$this->db->insert_id() : 0;
			$this->logAccountingChange(
				'create',
				'expensescategory',
				$id ?: null,
				null,
				($id ? ['categoryID' => $id] : []) + $data,
				$ok,
				'Added expense category',
				$data['Category']
			);

			// Redirect back to the expenses category page after saving
			redirect('Accounting/expensescategory');
		}
	}

	public function updateexpensescategory()
	{
		$this->ensureExpenseWriteAccess('Accounting/expensescategory');
		$categoryID = $this->input->get('categoryID');
		$result['data'] = $this->SettingsModel->getexpensescategorybyId($categoryID);
		$this->load->view('updateexpensescategory', $result);

		if ($this->input->post('update')) {

			$Category = $this->input->post('Category');


			$ok = $this->SettingsModel->updateexpensescategory($categoryID, $Category);

			$before = $result['data'][0] ?? null;
			if ($before) {
				$this->logAccountingChange(
					'update',
					'expensescategory',
					$categoryID,
					(array)$before,
					['Category' => $Category],
					$ok,
					'Updated expense category',
					$Category
				);
			}
			$this->session->set_flashdata('expenses', 'Record updated successfully');
			redirect("Accounting/expensescategory");
		}
	}


	public function Deleteexpensescategory()
	{
		$this->ensureExpenseWriteAccess('Accounting/expensescategory');

		// POST only: a plain link must not be able to delete a record.
		if (strtoupper((string)$this->input->method()) !== 'POST') {
			redirect('Accounting/expensescategory');
			return;
		}

		$categoryID = (int)$this->input->post('categoryID');
		if ($categoryID > 0) {
			$before = $this->SettingsModel->getexpensescategorybyId($categoryID)[0] ?? null;
			$ok = $this->SettingsModel->Delete_expensescategory($categoryID);
			if ($before) {
				$this->logAccountingChange(
					'delete',
					'expensescategory',
					$categoryID,
					(array)$before,
					null,
					$ok,
					'Deleted expense category',
					$before->Category ?? ''
				);
			}
			$this->session->set_flashdata('expensescategory', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('expensescategory', 'Error deleting record');
		}

		redirect("Accounting/expensescategory");
	}


	public function expensesReport()
	{
		$this->ensureAccess();
		$this->load->model('SettingsModel');

		$data['data'] = $this->SettingsModel->get_expenses();
		$data['categories'] = $this->SettingsModel->get_categories(); // Fetch categories

		// Convert categories array to a simpler format if needed
		$data['categories'] = array_column($data['categories'], 'Category');

		$this->load->view('expensesReport', $data);
	}


	public function expenseSGenerate()
	{
		$this->ensureAccess();
		// Get parameters from the URL
		$category = $this->input->get('category');
		$fromDate = $this->input->get('from');
		$toDate = $this->input->get('to');

		// Load the database library if it's not already loaded
		$this->load->database();

		// Fetch data from the database based on the passed parameters
		$this->db->select('*');
		$this->db->from('expenses');
		if ($category) {
			$this->db->where('Category', $category);
		}
		if ($fromDate && $toDate) {
			$this->db->where('ExpenseDate >=', $fromDate);
			$this->db->where('ExpenseDate <=', $toDate);
		}
		$query = $this->db->get();
		$result = $query->result();

		if ($this->input->get('print', true) === '1') {
			$printRows = [];
			$total = 0.0;
			foreach ($result as $row) {
				$total += (float)$row->Amount;
				$printRows[] = [
					(string)$row->Description,
					(string)$row->Responsible,
					(string)$row->ExpenseDate,
					(string)$row->Category,
					'₱ ' . number_format((float)$row->Amount, 2),
				];
			}

			$meta = [
				['label' => 'Category', 'value' => $category ?: 'All Categories'],
			];
			if ($fromDate || $toDate) {
				$meta[] = ['label' => 'Date Range', 'value' => ($fromDate ?: '...') . ' to ' . ($toDate ?: '...')];
			}
			$meta[] = ['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')];
			$meta[] = ['label' => 'Total Amount', 'value' => '₱ ' . number_format($total, 2)];

			$this->renderReportPrint(
				'Expenses Report',
				$meta,
				['Description', 'Responsible', 'Expense Date', 'Category', 'Amount'],
				$printRows,
				['left', 'left', 'left', 'left', 'right'],
				null,
				base_url('Accounting/expensesReport'),
				'landscape',
				'No expenses matched the selected filters.'
			);
			return;
		}

		// Pass the data to the view
		$data['category'] = $category;
		$data['fromDate'] = $fromDate;
		$data['toDate'] = $toDate;
		$data['result'] = $result;

		// Load the view and pass the data
		$this->load->view('filtered_expenses', $data);
	}


	private function get_expenses()
	{
		$query = $this->db->get('expenses');
		return $query->result();
	}

	private function insertexpenses($data)
	{
		return $this->db->insert('expenses', $data);
	}

	private function getexpensesbyId($expensesid)
	{
		$query = $this->db->query("SELECT * FROM expenses WHERE expensesid = ?", [$expensesid]);
		return $query->result();
	}

	private function Delete_expenses($expensesid)
	{
		$this->db->where('expensesid', $expensesid);
		$this->db->delete('expenses');
	}


	private function get_expensesCategory()
	{
		$query = $this->db->get('expensescategory');
		return $query->result();
	}

	private function insertexpensesCategory($data)
	{
		return $this->db->insert('expensescategory', $data);
	}

	private function getexpensescategorybyId($categoryID)
	{
		$query = $this->db->query("SELECT * FROM expensescategory WHERE categoryID = ?", [$categoryID]);
		return $query->result();
	}


	private function Delete_expensescategory($categoryID)
	{
		$this->db->where('categoryID', $categoryID);
		$this->db->delete('expensescategory');
	}


	private function get_categories()
	{
		$this->db->distinct();
		$this->db->select('Category');
		$this->db->from('expenses');
		$query = $this->db->get();
		return $query->result_array(); // Fetches categories as an array
	}

	private function getStudentsForPayment($sem, $sy)
	{
		// Every student must be payable, not only those enrolled in the active
		// term — a cashier has to be able to collect from students enrolled in
		// other semesters too (e.g. early enrollees, prior-term balances).
		// Names come from studeprofile first, then studentsignup. Course/major/
		// year and the Sem/SY the payment is tagged with come from the student's
		// enrolment row for the active term when present, else their latest one.
		$this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS FallbackCourse,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS FallbackMajor,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS FallbackYearLevel,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
		$this->db->from('studentsignup su');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = su.StudentNumber', 'left');
		$this->db->where("su.StudentNumber <> ''", null, false);
		$this->db->group_by('su.StudentNumber');
		$this->db->order_by('COALESCE(NULLIF(sp.LastName,\'\'), su.LastName, \'\')', 'ASC', false);
		$this->db->order_by('COALESCE(NULLIF(sp.FirstName,\'\'), su.FirstName, \'\')', 'ASC', false);
		$students = $this->db->get()->result();

		if (empty($students)) {
			return [];
		}

		$enrolment = [];
		$enrolRows = $this->db->select('semstudentid, StudentNumber, Course, Major, YearLevel, Semester, SY')
			->from('semesterstude')
			->get()
			->result();

		foreach ($enrolRows as $e) {
			$sn = trim((string)$e->StudentNumber);
			if ($sn === '') {
				continue;
			}

			$current = $enrolment[$sn] ?? null;
			if ($current === null) {
				$enrolment[$sn] = $e;
				continue;
			}

			$currentIsActive = ($current->Semester === $sem && $current->SY === $sy);
			$newIsActive = ($e->Semester === $sem && $e->SY === $sy);

			if ($newIsActive && !$currentIsActive) {
				$enrolment[$sn] = $e;
			} elseif (!$newIsActive && !$currentIsActive && (int)$e->semstudentid > (int)$current->semstudentid) {
				$enrolment[$sn] = $e;
			}
		}

		foreach ($students as $s) {
			$e = $enrolment[trim((string)$s->StudentNumber)] ?? null;
			if ($e !== null) {
				$s->Course    = trim((string)$e->Course) !== '' ? $e->Course : $s->FallbackCourse;
				$s->Major     = trim((string)$e->Major) !== '' ? $e->Major : $s->FallbackMajor;
				$s->YearLevel = trim((string)$e->YearLevel) !== '' ? $e->YearLevel : $s->FallbackYearLevel;
				$s->Semester  = (string)$e->Semester;
				$s->SY        = (string)$e->SY;
			} else {
				$s->Course    = $s->FallbackCourse;
				$s->Major     = $s->FallbackMajor;
				$s->YearLevel = $s->FallbackYearLevel;
				$s->Semester  = '';
				$s->SY        = '';
			}
			unset($s->FallbackCourse, $s->FallbackMajor, $s->FallbackYearLevel);
		}

		// Students who have an enrolment row but no signup row still need to be
		// payable — merge them in from studeprofile.
		$listed = [];
		foreach ($students as $s) {
			$listed[trim((string)$s->StudentNumber)] = true;
		}
		$missing = array_diff_key($enrolment, $listed);
		if (!empty($missing)) {
			$extra = $this->db->select("
				sp.StudentNumber,
				COALESCE(sp.course, '') AS Course,
				COALESCE(sp.major, '') AS Major,
				COALESCE(sp.yearLevel, '') AS YearLevel,
				COALESCE(sp.FirstName, '') AS FirstName,
				COALESCE(sp.MiddleName, '') AS MiddleName,
				COALESCE(sp.LastName, '') AS LastName
			", false)
				->from('studeprofile sp')
				->where_in('sp.StudentNumber', array_keys($missing))
				->get()
				->result();

			foreach ($extra as $s) {
				$e = $enrolment[trim((string)$s->StudentNumber)];
				$s->Course    = trim((string)$e->Course) !== '' ? $e->Course : $s->Course;
				$s->Major     = trim((string)$e->Major) !== '' ? $e->Major : $s->Major;
				$s->YearLevel = trim((string)$e->YearLevel) !== '' ? $e->YearLevel : $s->YearLevel;
				$s->Semester  = (string)$e->Semester;
				$s->SY        = (string)$e->SY;
				$students[]   = $s;
				unset($missing[trim((string)$s->StudentNumber)]);
			}

			// No signup or profile row at all — list the number itself so the
			// cashier can still collect the payment.
			foreach ($missing as $sn => $e) {
				$students[] = (object)[
					'StudentNumber' => $sn,
					'Course'        => (string)$e->Course,
					'Major'         => (string)$e->Major,
					'YearLevel'     => (string)$e->YearLevel,
					'Semester'      => (string)$e->Semester,
					'SY'            => (string)$e->SY,
					'FirstName'     => '',
					'MiddleName'    => '',
					'LastName'      => '',
				];
			}
		}

		// Only students with an Active account (Registered Students list)
		// can be paid for — see Payable_students.
		$this->load->library('payable_students');
		return $this->payable_students->filter($students);
	}

	private function getStudentContext($studentNumber, $sem, $sy)
	{
		$this->db->select("
			ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
		$this->db->from('semesterstude ss');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = ss.StudentNumber', 'left');
		$this->db->where('ss.StudentNumber', $studentNumber);
		if ($sem !== '') {
			$this->db->where('ss.Semester', $sem);
		}
		if ($sy !== '') {
			$this->db->where('ss.SY', $sy);
		}
		$this->db->order_by('ss.semstudentid', 'DESC');
		$this->db->limit(1);
		$row = $this->db->get()->row();

		if (!$row) {
			// Not enrolled in the payment's term — fall back to their most
			// recent enrolment for course/year context.
			$row = $this->db->select("
				ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
				COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
				COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
				COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
				COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
			", false)
				->from('semesterstude ss')
				->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left')
				->join('studentsignup su', 'su.StudentNumber = ss.StudentNumber', 'left')
				->where('ss.StudentNumber', $studentNumber)
				->order_by('ss.semstudentid', 'DESC')
				->limit(1)
				->get()
				->row();
		}

		if ($row) {
			return $row;
		}

		$this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS Course,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS Major,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS YearLevel,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
		$this->db->from('studentsignup su');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = su.StudentNumber', 'left');
		$this->db->where('su.StudentNumber', $studentNumber);
		return $this->db->get()->row();
	}

	private function getFeeTemplates($course = '', $major = '', $yearLevel = '', $semester = '')
	{
		if (!$this->tableExists('fees')) {
			return [];
		}

		$this->db->select('feesid, Description, Amount, feesType');
		$this->db->from('fees');
		$this->db->order_by('Description', 'ASC');
		return $this->db->get()->result();
	}

	// A fee's currently configured price. 0 means the description isn't a
	// configured fee at all — free-text descriptions are allowed, and those
	// have no price to check an amount against.
	private function feeFullAmountFor($description)
	{
		$description = trim((string)$description);
		if ($description === '' || !$this->tableExists('fees')) {
			return 0.0;
		}

		$row = $this->db->select('MAX(Amount) AS FullAmount', false)
			->from('fees')
			->where('Description', $description)
			->get()
			->row();

		return (float)($row->FullAmount ?? 0);
	}

	// What a student still owes on one fee this term. The full amount comes
	// from the price frozen onto their own earlier payments when they have
	// any, so changing a fee's price midway through an instalment plan can't
	// move the goalposts on a balance the student already started paying.
	private function feeBalanceFor($studentNumber, $description, $sem, $sy)
	{
		$studentNumber = trim((string)$studentNumber);
		$description   = trim((string)$description);
		$full = 0.0;
		$paid = 0.0;

		if ($studentNumber !== '' && $description !== '') {
			$this->db->select('COALESCE(SUM(Amount),0) AS Paid, COALESCE(MAX(FeeFullAmount),0) AS Snapshot', false)
				->from('paymentsaccounts')
				->where('StudentNumber', $studentNumber)
				->where('description', $description)
				->where('ORStatus', 'Valid')
				->where('CollectionSource', "Student's Account");
			if ($sem !== '') {
				$this->db->where('Sem', $sem);
			}
			if ($sy !== '') {
				$this->db->where('SY', $sy);
			}

			$row  = $this->db->get()->row();
			$paid = (float)($row->Paid ?? 0);
			$full = (float)($row->Snapshot ?? 0);
		}

		if ($full <= 0) {
			$full = $this->feeFullAmountFor($description);
		}

		return [
			'full'      => $full,
			'paid'      => $paid,
			'remaining' => max($full - $paid, 0.0),
		];
	}

	// Valid payments already taken against a fee description in a term. A fee
	// with collections behind it is price-locked for that term.
	private function feePaymentCount($description, $sem, $sy)
	{
		$description = trim((string)$description);
		if ($description === '') {
			return 0;
		}

		$this->db->from('paymentsaccounts')
			->where('description', $description)
			->where('ORStatus', 'Valid')
			->where('CollectionSource', "Student's Account");
		if ($sem !== '') {
			$this->db->where('Sem', $sem);
		}
		if ($sy !== '') {
			$this->db->where('SY', $sy);
		}

		return (int)$this->db->count_all_results();
	}

	private function getRecentPayments($date = null, $limit = 0)
	{
		// Payments are tagged to the student's enrolment term, not always the
		// active one — list the latest across all terms, otherwise a payment
		// for another semester would look like it was never recorded.
		// Status is per fee, not per receipt: two instalments that together
		// settle a fee must both read "Fully Paid", not "Partial" twice. The
		// price compared against is the one frozen onto the payment itself.
		//
		// The list must never silently drop rows: the table's search runs in
		// the browser over what is rendered here, so a capped list made a paid
		// student's receipt unfindable on busy days. $limit = 0 means no cap;
		// a positive cap is only a page-weight guard for the "all dates" view.
		$this->db->select("p.ID, p.PDate, p.pTime, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier, p.Sem, p.SY,
			p.FeeFullAmount AS FullAmount, agg.TotalPaid,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->join(
			"(SELECT StudentNumber, description, Sem, SY, SUM(Amount) AS TotalPaid
			    FROM paymentsaccounts
			   WHERE ORStatus = 'Valid' AND CollectionSource = \"Student's Account\"
			   GROUP BY StudentNumber, description, Sem, SY) agg",
			'agg.StudentNumber = p.StudentNumber AND agg.description = p.description AND agg.Sem = p.Sem AND agg.SY = p.SY',
			'left'
		);
		$this->db->where('p.CollectionSource', "Student's Account");
		$this->db->where('p.ORStatus', 'Valid');
		if ($date !== null && $date !== '') {
			$this->db->where('p.PDate', $date);
		}
		$this->db->order_by('p.PDate', 'DESC');
		$this->db->order_by('p.pTime', 'DESC');
		$this->db->order_by('p.ID', 'DESC');
		if ((int)$limit > 0) {
			$this->db->limit((int)$limit);
		}
		return $this->db->get()->result();
	}

	// Stat cards describe every payment matching the filter, not just the
	// rows rendered into the table — summing only the visible window made the
	// Collected card disagree with the dashboard on busy days. Status counts
	// use the same rule as the rows: the fee's frozen price against its
	// per-(student, fee, term) total, counted per receipt line.
	private function paymentStats($date = null)
	{
		$this->db->from('paymentsaccounts p')
			->join(
				"(SELECT StudentNumber, description, Sem, SY, SUM(Amount) AS TotalPaid
				    FROM paymentsaccounts
				   WHERE ORStatus = 'Valid' AND CollectionSource = \"Student's Account\"
				   GROUP BY StudentNumber, description, Sem, SY) agg",
				'agg.StudentNumber = p.StudentNumber AND agg.description = p.description AND agg.Sem = p.Sem AND agg.SY = p.SY',
				'left'
			)
			->where('p.CollectionSource', "Student's Account")
			->where('p.ORStatus', 'Valid');
		if ($date !== null && $date !== '') {
			$this->db->where('p.PDate', $date);
		}
		$row = $this->db->select("COUNT(*) AS Payments,
				COALESCE(SUM(p.Amount),0) AS Collected,
				COALESCE(SUM(CASE WHEN p.FeeFullAmount > 0 AND COALESCE(agg.TotalPaid,0) + 0.004 >= p.FeeFullAmount THEN 1 ELSE 0 END),0) AS FullyPaid,
				COALESCE(SUM(CASE WHEN p.FeeFullAmount > 0 AND COALESCE(agg.TotalPaid,0) + 0.004 <  p.FeeFullAmount THEN 1 ELSE 0 END),0) AS Partial", false)
			->get()
			->row();

		return [
			'count'      => (int)($row->Payments ?? 0),
			'collected'  => (float)($row->Collected ?? 0),
			'fully_paid' => (int)($row->FullyPaid ?? 0),
			'partial'    => (int)($row->Partial ?? 0),
		];
	}

	// Distinct dates that have at least one payment — powers the "jump to a
	// date with activity" filter so the cashier isn't guessing at dates.
	private function distinctPaymentDates($limit = 60)
	{
		return $this->db->distinct()
			->select('PDate')
			->from('paymentsaccounts')
			->where('CollectionSource', "Student's Account")
			->where('ORStatus', 'Valid')
			->order_by('PDate', 'DESC')
			->limit((int)$limit)
			->get()
			->result();
	}

	private function getPaymentById($id)
	{
		$this->db->select("p.*,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('p.ID', (int)$id);
		$this->db->limit(1);
		return $this->db->get()->row();
	}

	/**
	 * Make sure the student has a studeaccount row for the term so payment
	 * ledger updates have somewhere to land — e.g. a student paying while
	 * not enrolled in the active term. Rows created here are zero-amount
	 * shells, same shape as Term::provisionAccounts().
	 */
	private function ensureStudeAccount($studentNumber, $sem, $sy)
	{
		$exists = $this->db->select('StudentNumber')
			->from('studeaccount')
			->where('StudentNumber', $studentNumber)
			->where('Sem', $sem)
			->where('SY', $sy)
			->limit(1)
			->get()
			->row();

		if ($exists) {
			return;
		}

		// Prefer the enrolment row for this term; fall back to the latest.
		$enrol = $this->db->select('Course, Major, YearLevel, Section')
			->from('semesterstude')
			->where('StudentNumber', $studentNumber)
			->where('Semester', $sem)
			->where('SY', $sy)
			->limit(1)
			->get()
			->row();

		if (!$enrol) {
			$enrol = $this->db->select('Course, Major, YearLevel, Section')
				->from('semesterstude')
				->where('StudentNumber', $studentNumber)
				->order_by('semstudentid', 'DESC')
				->limit(1)
				->get()
				->row();
		}

		$settingsId = (int)($this->db->select('settingsID')
			->from('o_srms_settings')->limit(1)->get()->row()->settingsID ?? 0);

		$this->db->insert('studeaccount', [
			'StudentNumber' => $studentNumber,
			'Course'        => (string)($enrol->Course ?? ''),
			'Major'         => (string)($enrol->Major ?? ''),
			'YearLevel'     => (string)($enrol->YearLevel ?? ''),
			'Section'       => (string)($enrol->Section ?? ''),
			'FeesDesc'      => '',
			'feesType'      => 'Shell',
			'TotalPayments' => 0,
			'Sem'           => $sem,
			'SY'            => $sy,
			'settingsID'    => $settingsId,
		]);
	}

	private function recomputeStudeAccount($studentNumber, $sem, $sy)
	{
		$studentNumber = trim((string)$studentNumber);
		$sem = trim((string)$sem);
		$sy  = trim((string)$sy);
		if ($studentNumber === '' || $sem === '' || $sy === '') {
			return;
		}

		$this->ensureStudeAccount($studentNumber, $sem, $sy);

		$sumRow = $this->db->select('COALESCE(SUM(Amount),0) AS total', false)
			->from('paymentsaccounts')
			->where('StudentNumber', $studentNumber)
			->where('Sem', $sem)
			->where('SY', $sy)
			->where('ORStatus', 'Valid')
			->where('CollectionSource', "Student's Account")
			->get()
			->row();

		$newTotal = (float)($sumRow->total ?? 0);
		$newTotalSql = $this->db->escape($newTotal);

		$this->db->set('TotalPayments', $newTotalSql, false);
		$this->db->set('CurrentBalance', "GREATEST(COALESCE(AcctTotal,0) - COALESCE(Discount,0) - {$newTotalSql}, 0)", false);
		$this->db->where('StudentNumber', $studentNumber)
			->where('Sem', $sem)
			->where('SY', $sy)
			->update('studeaccount');
	}

	private function getReceiptSettings()
	{
		if ($this->receiptSettingsCache !== null) {
			return $this->receiptSettingsCache;
		}

		$this->receiptSettingsCache = $this->db->select('SchoolName, SchoolAddress, telNo, cashier, cashierPosition, letterhead_web')
			->from('o_srms_settings')
			->limit(1)
			->get()
			->row();

		return $this->receiptSettingsCache;
	}

	// Shared letterhead-style print document (logo, title, meta line, table)
	// used by every accounting report's "Print" button — same look as the
	// Attendance Logs report so printed paperwork is consistent app-wide.
	private function renderReportPrint($title, array $meta, array $columns, array $rows, array $aligns = [], $totals = null, $backUrl = '', $orientation = 'landscape', $emptyMessage = 'No records matched.', array $sections = [])
	{
		$settings = $this->getReceiptSettings();

		$this->load->view('accounting_report_print', [
			'report_title'  => $title,
			'school_name'   => trim((string)($settings->SchoolName ?? 'FBMSO')),
			'meta'          => $meta,
			'columns'       => $columns,
			'rows'          => $rows,
			'aligns'        => $aligns,
			'totals'        => $totals,
			'back_url'      => $backUrl,
			'orientation'   => $orientation,
			'empty_message' => $emptyMessage,
			'sections'      => $sections,
		]);
	}

	private function buildReceiptEmailPayment(array $paymentData, $student)
	{
		$studentData = is_object($student) ? get_object_vars($student) : (array)$student;

		return (object)array_merge($paymentData, [
			'Email' => trim((string)($studentData['Email'] ?? '')),
			'FirstName' => trim((string)($studentData['FirstName'] ?? '')),
			'MiddleName' => trim((string)($studentData['MiddleName'] ?? '')),
			'LastName' => trim((string)($studentData['LastName'] ?? '')),
		]);
	}

	// Every valid fee paid under the same O.R. as this payment. One counter
	// transaction can cover several fees, each stored as its own row, and the
	// receipt has to show all of them — not just the row that was clicked.
	private function getReceiptLines($payment)
	{
		$orNumber = trim((string)($payment->ORNumber ?? ''));
		if ($orNumber === '') {
			return [(object)[
				'description' => (string)($payment->description ?? ''),
				'Amount'      => (float)($payment->Amount ?? 0),
			]];
		}

		$lines = $this->db->select('ID, description, Amount')
			->from('paymentsaccounts')
			->where('ORNumber', $orNumber)
			->where('StudentNumber', (string)($payment->StudentNumber ?? ''))
			->where('ORStatus', 'Valid')
			->order_by('ID', 'ASC')
			->get()
			->result();

		return !empty($lines) ? $lines : [(object)[
			'description' => (string)($payment->description ?? ''),
			'Amount'      => (float)($payment->Amount ?? 0),
		]];
	}

	private function buildReceiptEmailHtml($payment, $settings)
	{
		return $this->load->view('accounting_receipt_email', [
			'payment' => $payment,
			'settings' => $settings,
			'lines' => $this->getReceiptLines($payment),
		], true);
	}

	private function sendReceiptEmailForPayment($payment, $settings = null)
	{
		$recipientEmail = trim((string)($payment->Email ?? ''));
		if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
			return [
				'attempted' => false,
				'sent' => false,
				'email' => '',
				'message' => 'No valid student email address was found for this receipt.',
			];
		}

		if ($settings === null) {
			$settings = $this->getReceiptSettings();
		}

		$schoolName = trim((string)($settings->SchoolName ?? 'School Records Management System'));
		$subject = 'Official Receipt #' . trim((string)($payment->ORNumber ?? '')) . ' - ' . $schoolName;
		$message = $this->buildReceiptEmailHtml($payment, $settings);

		// Queued, not sent inline: the EmailQueue cron sender owns SMTP, the
		// Brevo relay fallback and the retries, so saving a payment never waits
		// on the mail host.
		if (!fbmso_mailqueue_push($this, $recipientEmail, $subject, $message, $schoolName)) {
			log_message('error', 'Could not queue receipt email for payment ID ' . (int)($payment->ID ?? 0) . ' <' . $recipientEmail . '>');

			return [
				'attempted' => true,
				'sent' => false,
				'email' => $recipientEmail,
				'transport' => 'queue',
				'fallback_used' => false,
				'message' => 'Receipt email could not be queued. Please try resending it from the payment list.',
			];
		}

		return [
			'attempted' => true,
			'sent' => true,
			'email' => $recipientEmail,
			'transport' => 'queue',
			'fallback_used' => false,
			'message' => 'Receipt queued for delivery to ' . $recipientEmail . '. It usually arrives within a couple of minutes.',
		];
	}

	private function collectionRows($from, $to, $sem = '', $sy = '')
	{
		$this->db->select("p.ID, p.PDate, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier,
			p.CollectionSource, p.Sem, p.SY,
			CONCAT(
				COALESCE(NULLIF(sp.LastName,''), su.LastName, ''),
				', ',
				COALESCE(NULLIF(sp.FirstName,''), su.FirstName, ''),
				' ',
				COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '')
			) AS StudentName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('p.ORStatus', 'Valid');
		$this->db->where('p.PDate >=', $from);
		$this->db->where('p.PDate <=', $to);
		if ($sem !== '') {
			$this->db->where('p.Sem', $sem);
		}
		if ($sy !== '') {
			$this->db->where('p.SY', $sy);
		}
		$this->db->order_by('p.PDate', 'DESC');
		$this->db->order_by('p.ID', 'DESC');
		return $this->db->get()->result();
	}

	// School ledger: valid collections (credit) and recorded expenses (debit)
	// merged into one chronological running balance for a date range.
	private function ledgerRows($from, $to)
	{
		$income = $this->db->select("PDate AS EntryDate, pTime AS EntryTime, description AS EntryDesc, ORNumber AS EntryRef, Amount AS EntryAmount", false)
			->from('paymentsaccounts')
			->where('CollectionSource', "Student's Account")
			->where('ORStatus', 'Valid')
			->where('PDate >=', $from)
			->where('PDate <=', $to)
			->get()
			->result();

		$expenses = $this->db->select("ExpenseDate AS EntryDate, Description AS EntryDesc, Category AS EntryRef, Amount AS EntryAmount", false)
			->from('expenses')
			->where('ExpenseDate >=', $from)
			->where('ExpenseDate <=', $to)
			->get()
			->result();

		$rows = [];
		foreach ($income as $r) {
			$rows[] = [
				'date'        => (string)$r->EntryDate,
				'time'        => trim((string)($r->EntryTime ?? '')) !== '' ? (string)$r->EntryTime : '00:00:00',
				'type'        => 'income',
				'description' => (string)$r->EntryDesc,
				'ref'         => (string)$r->EntryRef,
				'amount'      => (float)$r->EntryAmount,
			];
		}
		foreach ($expenses as $r) {
			$rows[] = [
				'date'        => (string)$r->EntryDate,
				'time'        => '00:00:00',
				'type'        => 'expense',
				'description' => (string)$r->EntryDesc,
				'ref'         => (string)$r->EntryRef,
				'amount'      => (float)$r->EntryAmount,
			];
		}

		// Running balance only makes sense oldest-first; sort ascending to
		// compute it, then flip to newest-first for display.
		usort($rows, function ($a, $b) {
			$cmp = strcmp($a['date'], $b['date']);
			return $cmp !== 0 ? $cmp : strcmp($a['time'], $b['time']);
		});

		$balance = 0.0;
		foreach ($rows as &$row) {
			$balance += $row['type'] === 'income' ? $row['amount'] : -$row['amount'];
			$row['balance'] = $balance;
		}
		unset($row);

		return array_reverse($rows);
	}

	// Students who paid less than a fee's price on at least one description —
	// grouped per (student, fee) since a student can be fully paid on one item
	// and partial on another in the same term.
	//
	// The price compared against is the one frozen onto the student's own
	// payments (FeeFullAmount), never the live fees table. Re-pricing a fee
	// must not reach back and re-open balances that were already settled at
	// the old price.
	private function partialPaymentRows($sem, $sy)
	{
		$this->db->select("p.StudentNumber, p.description AS Description,
			MAX(p.FeeFullAmount) AS FullAmount, SUM(p.Amount) AS PaidAmount, MAX(p.PDate) AS LastPaymentDate,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('p.ORStatus', 'Valid');
		$this->db->where('p.CollectionSource', "Student's Account");
		$this->db->where('p.FeeFullAmount >', 0);
		if ($sem !== '') {
			$this->db->where('p.Sem', $sem);
		}
		if ($sy !== '') {
			$this->db->where('p.SY', $sy);
		}
		$this->db->group_by('p.StudentNumber, p.description');
		$this->db->having('SUM(p.Amount) < MAX(p.FeeFullAmount)', null, false);
		$this->db->order_by('LastName', 'ASC');
		$this->db->order_by('FirstName', 'ASC');
		$this->db->order_by('p.description', 'ASC');

		return $this->db->get()->result();
	}

	private function courseList()
	{
		$rows = $this->db->select('CourseDescription')
			->distinct()
			->from('course_table')
			->order_by('CourseDescription', 'ASC')
			->get()
			->result();

		$courses = [];
		foreach ($rows as $row) {
			$course = trim((string)$row->CourseDescription);
			if ($course !== '') {
				$courses[] = $course;
			}
		}

		return array_values(array_unique($courses));
	}

	private function majorsByCourse($course)
	{
		if ($course === '') {
			return [];
		}

		$rows = $this->db->select('Major')
			->distinct()
			->from('course_table')
			->where('CourseDescription', $course)
			->where("TRIM(Major) <> ''", null, false)
			->order_by('Major', 'ASC')
			->get()
			->result();

		$majors = [];
		foreach ($rows as $row) {
			$major = trim((string)$row->Major);
			if ($major !== '') {
				$majors[] = $major;
			}
		}

		return array_values(array_unique($majors));
	}

	public function Payment()
	{
		$this->ensureAccess();
		[$sem, $sy] = $this->currentSemSy();

		if (strtoupper((string)$this->input->method()) === 'POST') {
			$this->ensureWriteAccess();
			$submitToken = trim((string)$this->input->post('payment_submit_token', true));
			if (!$this->consumePaymentSubmitToken($submitToken)) {
				$this->session->set_flashdata('danger', 'This payment form was already submitted or expired. Please try again.');
				redirect('Accounting/Payment');
				return;
			}

			$this->form_validation->set_rules('StudentNumber', 'Student', 'required|trim');
			$this->form_validation->set_rules('PDate', 'Payment Date', 'required|trim');

			if ($this->form_validation->run() === false) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', strip_tags(validation_errors(' ', ' ')));
				redirect('Accounting/Payment');
				return;
			}

			$studentNumber = trim((string)$this->input->post('StudentNumber', true));
			$items         = $this->paymentItemsFromPost();
			$pDateInput    = trim((string)$this->input->post('PDate', true));
			$paymentType   = trim((string)$this->input->post('PaymentType', true));
			$checkNumber   = trim((string)$this->input->post('CheckNumber', true));
			$bank          = trim((string)$this->input->post('Bank', true));
			$refNo         = trim((string)$this->input->post('refNo', true));

			// Payments are always booked in the active term — the term the
			// money was received in — not the student's enrolment term. The
			// collection report's Sem/SY filter then answers "cash collected
			// during term X".

			if (!$this->isValidDate($pDateInput)) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', 'Invalid payment date.');
				redirect('Accounting/Payment');
				return;
			}

			if ($paymentType === '') {
				$paymentType = 'Cash';
			}

			if (strcasecmp($paymentType, 'Check') !== 0) {
				$checkNumber = '';
				$bank = '';
			}

			$this->load->library('payable_students');
			if (!$this->payable_students->isActive($studentNumber)) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', Payable_students::NOT_ACTIVE_MESSAGE);
				redirect('Accounting/Payment');
				return;
			}

			$student = $this->getStudentContext($studentNumber, $sem, $sy);
			$course = trim((string)($student->Course ?? ''));
			if ($course === '') {
				$course = trim((string)$this->input->post('Course', true));
			}

			$cashier = trim((string)$this->session->userdata('username'));
			if ($cashier === '') {
				$cashier = trim((string)$this->session->userdata('IDNumber'));
			}

			$dtNow = new DateTime('now', new DateTimeZone('Asia/Manila'));

			// Two cashiers (or a cashier and the mobile app) can be taking
			// money from the same student at the same moment. The balance
			// check, the insert and the ledger recompute run under one lock
			// per student, so the second save waits and then sees the first
			// one's payment instead of collecting the same fee again.
			$this->load->library('payment_lock');
			if (!$this->payment_lock->acquire($studentNumber)) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', 'Another payment for this student is being saved right now. Please try again in a moment.');
				redirect('Accounting/Payment');
				return;
			}

			try {
				$itemError = $this->validatePaymentItems($items, $studentNumber, $sem, $sy);
				if ($itemError !== null) {
					$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
					$this->session->set_flashdata('danger', $itemError);
					redirect('Accounting/Payment');
					return;
				}

				// The O.R. number is read-only in the UI and always drawn from
				// the shared counter, which hands each save its own number no
				// matter how many cashiers save at once.
				//
				// Every fee paid in this transaction gets its own row — balances,
				// partial status and the price snapshot are all per fee — but they
				// share one O.R. number, so it is one receipt at the counter.
				// Row IDs come from AUTO_INCREMENT, never MAX(ID)+1.
				$orNumber = $this->generateNextOrNumber($pDateInput);
				$paymentRows = [];
				$insertOk = true;

				$this->db->trans_begin();
				foreach ($items as $item) {
					$row = [
						'StudentNumber'    => $studentNumber,
						'Course'           => $course,
						'PDate'            => $pDateInput,
						'ORNumber'         => $orNumber,
						'Amount'           => $item['amount'],
						'FeeFullAmount'    => $item['feeFullAmount'],
						'description'      => $item['description'],
						'PaymentType'      => $paymentType,
						'CheckNumber'      => $checkNumber,
						'Sem'              => $sem,
						'SY'               => $sy,
						'CollectionSource' => "Student's Account",
						'Bank'             => $bank,
						'ORStatus'         => 'Valid',
						'Cashier'          => $cashier,
						'pTime'            => $dtNow->format('H:i:s'),
						'refNo'            => $refNo
					];

					if (!$this->db->insert('paymentsaccounts', $row)) {
						$insertOk = false;
						break;
					}
					$row['ID'] = (int)$this->db->insert_id();
					$paymentRows[] = $row;
				}

				if ($insertOk && $sem !== '' && $sy !== '') {
					$this->recomputeStudeAccount($studentNumber, $sem, $sy);
				}

				if ($insertOk && $this->db->trans_status() !== false) {
					$this->db->trans_commit();
				} else {
					$this->db->trans_rollback();
					$insertOk = false;
				}
			} finally {
				$this->payment_lock->release();
			}

			if (!$insertOk) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', 'Unable to save payment. Please try again.');
				redirect('Accounting/Payment');
				return;
			}

			// Payment entry is the cashier's main write, so it belongs in the
			// same trail as edits and deletions (and in Super Admin's Audit Trail).
			$receiptTotal = 0.0;
			foreach ($paymentRows as $row) {
				$receiptTotal += (float)$row['Amount'];
				$this->logPaymentAudit('create', (object)$row, [
					'StudentNumber' => $studentNumber,
					'ORNumber'      => $orNumber,
					'PDate'         => $pDateInput,
					'Amount'        => $row['Amount'],
					'description'   => $row['description'],
					'PaymentType'   => $paymentType,
					'Sem'           => $sem,
					'SY'            => $sy,
				]);
			}

			$receiptSettings = $this->getReceiptSettings();
			$receiptPayment = $this->buildReceiptEmailPayment($paymentRows[0], $student);
			$emailResult = $this->sendReceiptEmailForPayment($receiptPayment, $receiptSettings);

			$successMessage = count($paymentRows) > 1
				? 'Payment saved successfully. O.R. #' . $orNumber . ' covers ' . count($paymentRows) . ' fees totalling ₱' . number_format($receiptTotal, 2) . '.'
				: 'Payment saved successfully. O.R. #' . $orNumber . '.';

			// The form shows the next O.R. as a preview when it opens. If
			// another cashier saved first, this payment got a later number —
			// say so, so the receipt handed over matches the system.
			$previewOr = trim((string)$this->input->post('ORNumber', true));
			if ($previewOr !== '' && $previewOr !== $orNumber) {
				$successMessage .= ' Note: O.R. #' . $previewOr . ' was issued to another payment while this one was being entered, so this payment is O.R. #' . $orNumber . '.';
			}
			if (!empty($emailResult['attempted']) && !empty($emailResult['sent'])) {
				$successMessage .= ' ' . trim((string)($emailResult['message'] ?? ''));
				$this->session->set_flashdata('success', $successMessage);
			} else {
				$this->session->set_flashdata('success', $successMessage);
				$this->session->set_flashdata('warning', (string)$emailResult['message']);
			}

			redirect('Accounting/Payment');
			return;
		}

		$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
		$today = $now->format('Y-m-d');
		$oldPaymentForm = $this->session->flashdata('payment_form_old');
		if (!is_array($oldPaymentForm)) {
			$oldPaymentForm = [];
		}

		$settings = $this->getReceiptSettings();

		// Default to today's payments only — yesterday's list clears itself
		// out each morning. A cashier who needs an older date picks it from
		// the "Payments on" filter, which only lists dates that have entries.
		$dateFilter = trim((string)$this->input->get('date', true));
		if ($dateFilter !== 'all' && !$this->isValidDate($dateFilter)) {
			$dateFilter = $today;
		}

		// A chosen date renders every payment that day — no cap: a hard limit
		// once made real receipts vanish from the list and from the in-table
		// search while the fees correctly read "already paid". Only the
		// "all dates" view keeps a cap, purely as page-weight protection; the
		// server-side search still reaches everything beyond it.
		$statDate = $dateFilter === 'all' ? null : $dateFilter;
		$stats = $this->paymentStats($statDate);
		$recentPayments = $this->getRecentPayments($statDate, $dateFilter === 'all' ? 2000 : 0);

		$data = [
			'default_payment_date' => $today,
			'next_or_number'       => $this->peekNextOrNumber($today),
			'students'             => $this->getStudentsForPayment($sem, $sy),
			'recent_payments'      => $recentPayments,
			'payment_stats'        => $stats,
			'payments_truncated'   => count($recentPayments) < $stats['count'],
			'payment_dates'        => $this->distinctPaymentDates(),
			'date_filter'          => $dateFilter,
			'today'                => $today,
			'settings'             => $settings,
			'payment_form_old'     => $oldPaymentForm,
			'payment_submit_token' => $this->generatePaymentSubmitToken(),
			'open_payment_modal'   => !empty($oldPaymentForm),
		];

		$this->load->view('accounting_payment', $data);
	}

	public function updatePayment()
	{
		$this->ensureWriteAccess();

		if (strtoupper((string)$this->input->method()) !== 'POST') {
			show_error('Invalid request method', 405);
			return;
		}

		$id = (int)$this->input->post('id', true);
		if ($id <= 0) {
			$this->session->set_flashdata('danger', 'Invalid payment ID.');
			redirect('Accounting/Payment');
			return;
		}

		$payment = $this->db->select('ID, StudentNumber, ORNumber, PDate, Amount, description, Sem, SY, ORStatus, CollectionSource')
			->from('paymentsaccounts')
			->where('ID', $id)
			->limit(1)
			->get()
			->row();

		if (!$payment) {
			$this->session->set_flashdata('danger', 'Payment not found.');
			redirect('Accounting/Payment');
			return;
		}

		if ((string)$payment->ORStatus !== 'Valid') {
			$this->session->set_flashdata('danger', 'Only VALID payments can be edited.');
			redirect('Accounting/Payment');
			return;
		}

		if ((string)$payment->CollectionSource !== "Student's Account") {
			$this->session->set_flashdata('danger', "This payment is not under Student's Account.");
			redirect('Accounting/Payment');
			return;
		}

		$this->form_validation->set_rules('StudentNumber', 'Student', 'required|trim');
		$this->form_validation->set_rules('description', 'Description', 'required|trim');
		$this->form_validation->set_rules('Amount', 'Amount', 'required|numeric|greater_than[0]');
		$this->form_validation->set_rules('PDate', 'Payment Date', 'required|trim');

		if ($this->form_validation->run() === false) {
			$this->session->set_flashdata('danger', strip_tags(validation_errors(' ', ' ')));
			redirect('Accounting/Payment');
			return;
		}

		$studentNumber = trim((string)$this->input->post('StudentNumber', true));
		$description   = trim((string)$this->input->post('description', true));
		$amount        = (float)$this->input->post('Amount', true);
		$pDateInput    = trim((string)$this->input->post('PDate', true));

		if (!$this->isValidDate($pDateInput)) {
			$this->session->set_flashdata('danger', 'Invalid payment date.');
			redirect('Accounting/Payment');
			return;
		}

		// The O.R. number is read-only once issued — editing a payment never
		// changes it, so a printed receipt always matches its ledger row.
		$orNumber = (string)$payment->ORNumber;

		$oldStudentNumber = trim((string)$payment->StudentNumber);
		$sem = trim((string)$payment->Sem);
		$sy  = trim((string)$payment->SY);

		// Moving a payment to another student follows the same rule as a new
		// payment. Correcting one that stays on its student is always allowed.
		if ($studentNumber !== $oldStudentNumber) {
			$this->load->library('payable_students');
			if (!$this->payable_students->isActive($studentNumber)) {
				$this->session->set_flashdata('danger', Payable_students::NOT_ACTIVE_MESSAGE);
				redirect('Accounting/Payment');
				return;
			}
		}

		// Same per-student lock as a new payment, on both students when the
		// payment moves between them, so the ledger recompute cannot race a
		// payment being saved for either one.
		$this->load->library('payment_lock');
		if (!$this->payment_lock->acquire([$oldStudentNumber, $studentNumber])) {
			$this->session->set_flashdata('danger', 'Another payment for this student is being saved right now. Please try again in a moment.');
			redirect('Accounting/Payment');
			return;
		}

		try {
			// Sem/SY are intentionally not updated: re-tagging a payment to another
			// term would rewrite history. Delete and re-enter it instead.
			$this->db->trans_begin();
			$this->db->where('ID', $id)->where('ORStatus', 'Valid')->update('paymentsaccounts', [
				'StudentNumber' => $studentNumber,
				'PDate'         => $pDateInput,
				'Amount'        => $amount,
				'description'   => $description,
			]);
			$updated = $this->db->affected_rows();

			$this->recomputeStudeAccount($oldStudentNumber, $sem, $sy);
			if ($studentNumber !== $oldStudentNumber) {
				$this->recomputeStudeAccount($studentNumber, $sem, $sy);
			}

			$ok = $this->db->trans_status() !== false;
			if ($ok) {
				$this->db->trans_commit();
			} else {
				$this->db->trans_rollback();
			}
		} finally {
			$this->payment_lock->release();
		}

		if (!$ok) {
			$this->session->set_flashdata('danger', 'Unable to update payment. Please try again.');
			redirect('Accounting/Payment');
			return;
		}

		if ($updated < 1 && !$this->db->where('ID', $id)->count_all_results('paymentsaccounts')) {
			$this->session->set_flashdata('danger', 'This payment was deleted by another user before your changes were saved.');
			redirect('Accounting/Payment');
			return;
		}

		$this->logPaymentAudit('edit', $payment, [
			'StudentNumber' => $studentNumber,
			'PDate'         => $pDateInput,
			'Amount'        => $amount,
			'description'   => $description,
		]);

		$this->session->set_flashdata('success', 'Payment updated successfully. O.R. #' . $orNumber . '.');
		redirect('Accounting/Payment');
	}

	// The O.R. field is read-only; this only refreshes the preview shown to
	// the cashier when they change the payment date, before they save.
	public function ajaxOrNumberStatus()
	{
		$this->ensureAccess();

		$paymentDate = trim((string)$this->input->get('payment_date', true));
		$suggested = $this->peekNextOrNumber($paymentDate);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['suggested' => $suggested]));
	}

	public function receipt($id = null)
	{
		$this->ensureAccess();
		$paymentId = (int)$id;
		if ($paymentId <= 0) {
			$this->session->set_flashdata('danger', 'Invalid receipt request.');
			redirect('Accounting/Payment');
			return;
		}

		$payment = $this->getPaymentById($paymentId);
		if (!$payment) {
			$this->session->set_flashdata('danger', 'Payment not found.');
			redirect('Accounting/Payment');
			return;
		}

		$settings = $this->getReceiptSettings();

		$data = [
			'payment'    => $payment,
			'lines'      => $this->getReceiptLines($payment),
			'settings'   => $settings,
			'auto_print' => $this->input->get('print', true) === '1'
		];

		$this->load->view('accounting_receipt', $data);
	}

	public function emailReceipt()
	{
		$this->ensureWriteAccess();

		if (strtoupper((string)$this->input->method()) !== 'POST') {
			show_error('Invalid request method', 405);
			return;
		}

		$paymentId = (int)$this->input->post('id', true);
		if ($paymentId <= 0) {
			$this->session->set_flashdata('danger', 'Invalid receipt email request.');
			redirect('Accounting/Payment');
			return;
		}

		$payment = $this->getPaymentById($paymentId);
		if (!$payment) {
			$this->session->set_flashdata('danger', 'Payment not found.');
			redirect('Accounting/Payment');
			return;
		}

		$result = $this->sendReceiptEmailForPayment($payment, $this->getReceiptSettings());
		if (!empty($result['sent'])) {
			$this->session->set_flashdata('success', (string)$result['message']);
		} else {
			$this->session->set_flashdata('danger', (string)$result['message']);
		}
		redirect('Accounting/Payment');
	}

	public function ajaxMajors()
	{
		$this->ensureAccess();
		$course = trim((string)$this->input->get('course', true));
		$majors = $this->majorsByCourse($course);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['majors' => $majors]));
	}

	public function ajaxFees()
	{
		$this->ensureAccess();

		$rows = $this->getFeeTemplates();
		$fees = [];
		foreach ($rows as $row) {
			$fees[] = [
				'feesid'      => (int)$row->feesid,
				'description' => (string)$row->Description,
				'amount'      => (float)$row->Amount,
				'feesType'    => (string)$row->feesType,
			];
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['fees' => $fees]));
	}

	// What a student still owes on one fee this term, so the payment form can
	// show Full / Already Paid / Remaining instead of assuming every payment
	// starts from zero.
	public function ajaxFeeBalance()
	{
		$this->ensureAccess();
		[$sem, $sy] = $this->currentSemSy();

		$studentNumber = trim((string)$this->input->get('student', true));
		$description   = trim((string)$this->input->get('description', true));
		$balance       = $this->feeBalanceFor($studentNumber, $description, $sem, $sy);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'full'      => round($balance['full'], 2),
				'paid'      => round($balance['paid'], 2),
				'remaining' => round($balance['remaining'], 2),
			]));
	}

	/**
	 * Server-side payment search for the Recent Payments table.
	 *
	 * The table's own search only sees the rows rendered into the page. On a
	 * busy day — or an "all dates" view that hit its cap — a real payment can
	 * sit outside that window and the in-table search answers "no matching
	 * records" for money that was actually taken. This endpoint searches the
	 * whole paymentsaccounts table so a cashier can always find the receipt.
	 *
	 * Matching is AND-per-term like DataTables: every word must hit somewhere
	 * in the student number, name, O.R., or description.
	 */
	public function ajaxPaymentSearch()
	{
		$this->ensureAccess();

		$q = trim((string)$this->input->get('q', true));
		$terms = preg_split('/\s+/', $q) ?: [];
		$terms = array_values(array_filter($terms, function ($t) { return $t !== ''; }));

		if (count($terms) < 1 || strlen($q) < 2) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(['ok' => true, 'results' => []]));
			return;
		}

		$this->db->select("p.ID, p.PDate, p.pTime, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier,
			p.FeeFullAmount AS FullAmount, agg.TotalPaid,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->join(
			"(SELECT StudentNumber, description, Sem, SY, SUM(Amount) AS TotalPaid
			    FROM paymentsaccounts
			   WHERE ORStatus = 'Valid' AND CollectionSource = \"Student's Account\"
			   GROUP BY StudentNumber, description, Sem, SY) agg",
			'agg.StudentNumber = p.StudentNumber AND agg.description = p.description AND agg.Sem = p.Sem AND agg.SY = p.SY',
			'left'
		);
		$this->db->where('p.CollectionSource', "Student's Account");
		$this->db->where('p.ORStatus', 'Valid');
		foreach ($terms as $term) {
			$like = '%' . $this->db->escape_like_str($term) . '%';
			$this->db->where(
				"CONCAT_WS(' ', p.StudentNumber, p.ORNumber, p.description,
					sp.LastName, sp.FirstName, sp.MiddleName,
					su.LastName, su.FirstName, su.MiddleName) LIKE " . $this->db->escape($like),
				null, false
			);
		}
		$this->db->order_by('p.PDate', 'DESC');
		$this->db->order_by('p.pTime', 'DESC');
		$this->db->order_by('p.ID', 'DESC');
		$rows = $this->db->limit(50)->get()->result();

		$results = [];
		foreach ($rows as $r) {
			$name = trim((string)($r->LastName ?? ''));
			if ($name !== '') $name .= ', ';
			$name .= trim((string)(($r->FirstName ?? '') . ' ' . ($r->MiddleName ?? '')));
			if (trim($name) === '') $name = (string)$r->StudentNumber;

			$full = (float)($r->FullAmount ?? 0);
			$paid = (float)($r->TotalPaid ?? $r->Amount ?? 0);
			$status = $full <= 0 ? 'N/A' : ($paid + 0.004 < $full ? 'Partial' : 'Fully Paid');

			$results[] = [
				'id'      => (int)$r->ID,
				'date'    => date('M d, Y', strtotime((string)$r->PDate)),
				'time'    => trim((string)$r->pTime) !== '' ? date('h:i A', strtotime((string)$r->pTime)) : '',
				'or'      => (string)$r->ORNumber,
				'studno'  => (string)$r->StudentNumber,
				'name'    => $name,
				'desc'    => (string)$r->description,
				'amount'  => (float)$r->Amount,
				'status'  => $status,
				'cashier' => (string)$r->Cashier,
			];
		}

		$this->output->set_content_type('application/json')
			->set_output(json_encode(['ok' => true, 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function course_setUp()
	{
		$this->ensureAccess();
		[$sem, $sy] = $this->currentSemSy();

		if (strtoupper((string)$this->input->method()) === 'POST') {
			$this->ensureWriteAccess();
			$action = trim((string)$this->input->post('action', true));

			if ($action === 'add') {
				$this->form_validation->set_rules('Description', 'Description', 'required|trim');
				$this->form_validation->set_rules('Amount', 'Amount', 'required|numeric|greater_than_equal_to[0]');

				if ($this->form_validation->run() === false) {
					$this->session->set_flashdata('danger', strip_tags(validation_errors(' ', ' ')));
					redirect('Accounting/course_setUp');
					return;
				}

				$data = [
					'feesid'      => $this->nextTableId('fees', 'feesid'),
					'feesType'    => trim((string)$this->input->post('feesType', true)),
					'Description' => trim((string)$this->input->post('Description', true)),
					'Amount'      => (float)$this->input->post('Amount', true),
				];

				if ($data['feesType'] === '') {
					$data['feesType'] = 'School Fee';
				}

				$ok = $this->db->insert('fees', $data);
				$this->logAccountingChange('create', 'fees', $data['feesid'], null, $data, $ok, 'Added fee', $data['Description']);
				$this->session->set_flashdata('success', 'Fee added successfully.');
				redirect('Accounting/course_setUp');
				return;
			}

			if ($action === 'delete') {
				$feeId = (int)$this->input->post('feesid', true);
				if ($feeId > 0) {
					$before = $this->db->select('feesid, feesType, Description, Amount')
						->from('fees')->where('feesid', $feeId)->limit(1)->get()->row();
					$ok = $this->db->where('feesid', $feeId)->delete('fees');
					if ($before) {
						$this->logAccountingChange('delete', 'fees', $feeId, (array)$before, null, $ok, 'Deleted fee', $before->Description);
					}
					$this->session->set_flashdata('success', 'Fee deleted successfully.');
				} else {
					$this->session->set_flashdata('danger', 'Invalid fee record.');
				}
				redirect('Accounting/course_setUp');
				return;
			}

			if ($action === 'update') {
				$this->form_validation->set_rules('feesid', 'Fee ID', 'required|integer');
				$this->form_validation->set_rules('Description', 'Description', 'required|trim');
				$this->form_validation->set_rules('Amount', 'Amount', 'required|numeric|greater_than_equal_to[0]');

				if ($this->form_validation->run() === false) {
					$this->session->set_flashdata('danger', strip_tags(validation_errors(' ', ' ')));
					redirect('Accounting/course_setUp');
					return;
				}

				$feeId = (int)$this->input->post('feesid', true);
				$updateData = [
					'Description' => trim((string)$this->input->post('Description', true)),
					'Amount'      => (float)$this->input->post('Amount', true),
				];

				// Once money has been collected against a fee this term, its
				// price and name are frozen for that term. Renaming would orphan
				// the balances already recorded under the old name, and the
				// price is what those students were quoted. Next term starts
				// clean, so this is a freeze, not a permanent lock.
				$existing = $this->db->select('feesid, feesType, Description, Amount')
					->from('fees')
					->where('feesid', $feeId)
					->limit(1)
					->get()
					->row();

				if ($existing) {
					$renamed  = trim((string)$existing->Description) !== $updateData['Description'];
					$repriced = abs((float)$existing->Amount - $updateData['Amount']) > 0.004;

					if ($renamed || $repriced) {
						$paidCount = $this->feePaymentCount((string)$existing->Description, $sem, $sy);
						if ($paidCount > 0) {
							$this->session->set_flashdata(
								'danger',
								'"' . $existing->Description . '" already has ' . $paidCount . ' payment' . ($paidCount === 1 ? '' : 's') .
									' recorded this term, so its name and amount are locked until the next term. Add a new fee instead.'
							);
							redirect('Accounting/course_setUp');
							return;
						}
					}
				}

				// The edit form has no feesType field; only update it when one
				// is actually posted so we never blank an existing value.
				$feesType = trim((string)$this->input->post('feesType', true));
				if ($feesType !== '') {
					$updateData['feesType'] = $feesType;
				}

				$ok = $feeId > 0
					&& $this->db->where('feesid', $feeId)->update('fees', $updateData);

				if ($existing) {
					$this->logAccountingChange('update', 'fees', $feeId, (array)$existing, $updateData, $ok, 'Updated fee', $updateData['Description']);
				}

				if ($ok) {
					$this->session->set_flashdata('success', 'Fee updated successfully.');
				} else {
					$this->session->set_flashdata('danger', 'Unable to update fee. Please try again.');
				}

				redirect('Accounting/course_setUp');
				return;
			}
		}

		$this->db->select('feesid, feesType, Description, Amount');
		$this->db->from('fees');
		$this->db->order_by('Description', 'ASC');
		$fees = $this->db->get()->result();

		foreach ($fees as $fee) {
			$fee->PaidCount = $this->feePaymentCount((string)$fee->Description, $sem, $sy);
		}

		$data = [
			'semester'        => $sem,
			'courses'         => $this->courseList(),
			'fees'            => $fees,
		];

		$this->load->view('accounting_fee_setup', $data);
	}

	private function renderCollection($from, $to, $title, $sem = '', $sy = '')
	{
		$rows = $this->collectionRows($from, $to, $sem, $sy);
		$total = 0.0;
		foreach ($rows as $row) {
			$total += (float)$row->Amount;
		}

		$settings = $this->getReceiptSettings();
		$reportPeriod = $from === $to
			? date('F d, Y', strtotime($from))
			: date('F d, Y', strtotime($from)) . ' to ' . date('F d, Y', strtotime($to));
		if ($sem !== '' || $sy !== '') {
			$reportPeriod .= ' — ' . trim($sem . ' ' . $sy);
		}
		$generatedAt = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('F d, Y h:i A');

		if ($this->input->get('print', true) === '1') {
			$printRows = [];
			foreach ($rows as $row) {
				$studentName = trim((string)($row->StudentName ?? ''));
				if (trim($studentName, ', ') === '') {
					$studentName = (string)($row->StudentNumber ?? '');
				}
				$printRows[] = [
					(string)$row->PDate,
					(string)$row->ORNumber,
					(string)$row->StudentNumber,
					$studentName,
					(string)$row->description,
					(string)$row->PaymentType,
					trim((string)$row->Sem . ' ' . (string)$row->SY),
					'₱ ' . number_format((float)$row->Amount, 2),
					(string)$row->Cashier,
				];
			}

			$this->renderReportPrint(
				$title,
				[
					['label' => 'Coverage', 'value' => $reportPeriod],
					['label' => 'Printed', 'value' => $generatedAt],
					['label' => 'Transactions', 'value' => number_format(count($rows))],
					['label' => 'Total Collection', 'value' => '₱ ' . number_format($total, 2)],
				],
				['Date', 'O.R.', 'Student No.', 'Student', 'Description', 'Payment Type', 'Sem/SY', 'Amount', 'Cashier'],
				$printRows,
				['left', 'left', 'left', 'left', 'left', 'left', 'left', 'right', 'left'],
				null,
				base_url('Accounting/collectionReport?from=' . urlencode($from) . '&to=' . urlencode($to)),
				'landscape',
				'No payments in this period.'
			);
			return;
		}

		$data = [
			'report_title'   => $title,
			'report_period'  => $reportPeriod,
			'generated_at'   => $generatedAt,
			'from'           => $from,
			'to'             => $to,
			'filter_sem'     => $sem,
			'filter_sy'      => $sy,
			'term_options'   => $this->term->terms(),
			'rows'           => $rows,
			'total_amount'   => $total,
			'total_count'    => count($rows),
			'settings'       => $settings,
		];

		$this->load->view('accounting_collection_report', $data);
	}

	public function collectionReport()
	{
		$this->ensureAccess();
		$from = trim((string)$this->input->get('from', true));
		$to   = trim((string)$this->input->get('to', true));

		if (!$this->isValidDate($from)) {
			$from = date('Y-m-01');
		}
		if (!$this->isValidDate($to)) {
			$to = date('Y-m-d');
		}

		// Optional term scope — "Semester|SY" pair from the filter select.
		$sem = '';
		$sy  = '';
		$term = trim((string)$this->input->get('term', true));
		if ($term !== '' && strpos($term, '|') !== false) {
			[$sem, $sy] = array_map('trim', explode('|', $term, 2));
		}

		$this->renderCollection($from, $to, 'Collection Report (Date Range)', $sem, $sy);
	}

	public function collectionDateRange()
	{
		$this->ensureAccess();
		$this->collectionReport();
	}

	public function collectionMonthly()
	{
		$this->ensureAccess();

		$year = (int)$this->input->get('year', true);
		$month = (int)$this->input->get('month', true);
		if ($year < 2000 || $year > 2100) {
			$year = (int)date('Y');
		}
		if ($month < 1 || $month > 12) {
			$month = (int)date('m');
		}

		$from = sprintf('%04d-%02d-01', $year, $month);
		$to = date('Y-m-t', strtotime($from));
		$this->renderCollection($from, $to, 'Collection Report (Monthly)');
	}

	public function collectionYear()
	{
		$this->ensureAccess();

		$year = (int)$this->input->get('year', true);
		if ($year < 2000 || $year > 2100) {
			$year = (int)date('Y');
		}

		$from = sprintf('%04d-01-01', $year);
		$to = sprintf('%04d-12-31', $year);
		$this->renderCollection($from, $to, 'Collection Report (Yearly)');
	}
	// Fees on the same receipt as payment $id — one O.R. can cover several
	// fees, each its own row — so the delete dialog can offer all of them.
	public function ajaxReceiptItems()
	{
		$this->ensureWriteAccess();

		$id = (int)$this->input->get('id', true);
		$payment = $id > 0
			? $this->db->select('ID, StudentNumber, ORNumber, PDate, description, Amount, ORStatus, CollectionSource')
				->from('paymentsaccounts')->where('ID', $id)->limit(1)->get()->row()
			: null;

		if (!$payment) {
			$this->output->set_status_header(404)->set_content_type('application/json')
				->set_output(json_encode(['ok' => false, 'message' => 'Payment not found. It may have just been deleted by another user.']));
			return;
		}

		$rows = [$payment];
		if (trim((string)$payment->ORNumber) !== '') {
			$rows = $this->db->select('ID, StudentNumber, ORNumber, description, Amount, ORStatus, CollectionSource')
				->from('paymentsaccounts')
				->where('ORNumber', (string)$payment->ORNumber)
				->where('StudentNumber', (string)$payment->StudentNumber)
				->order_by('ID', 'ASC')
				->get()
				->result();
		}

		$items = [];
		foreach ($rows as $r) {
			$items[] = [
				'id'          => (int)$r->ID,
				'description' => (string)$r->description,
				'amount'      => (float)$r->Amount,
				// Same guards as deletePayment().
				'deletable'   => (string)$r->ORStatus === 'Valid' && (string)$r->CollectionSource === "Student's Account",
			];
		}

		$studentNumber = (string)$payment->StudentNumber;
		$name = $this->db->query(
			"SELECT TRIM(CONCAT_WS(', ',
					NULLIF(COALESCE(NULLIF(sp.LastName, ''), NULLIF(su.LastName, ''), ''), ''),
					NULLIF(TRIM(CONCAT_WS(' ', COALESCE(NULLIF(sp.FirstName, ''), su.FirstName), COALESCE(NULLIF(sp.MiddleName, ''), su.MiddleName))), '')
				)) AS name
			   FROM (SELECT ? AS sn) x
			   LEFT JOIN studeprofile sp ON sp.StudentNumber = x.sn
			   LEFT JOIN studentsignup su ON su.StudentNumber = x.sn
			  LIMIT 1",
			[$studentNumber]
		)->row();

		$this->output->set_content_type('application/json')->set_output(json_encode([
			'ok'             => true,
			'or_number'      => (string)$payment->ORNumber,
			'student_number' => $studentNumber,
			'student_name'   => trim((string)($name->name ?? '')),
			'payment_date'   => $this->isValidDate((string)$payment->PDate) ? date('M j, Y', strtotime((string)$payment->PDate)) : '',
			'items'          => $items,
		]));
	}

	// Deletes one or more fees from a single receipt (same O.R., same
	// student) — e.g. the whole receipt when it was tagged to the wrong
	// student. Every deleted fee is logged with the cashier's reason.
	public function deletePayment()
	{
		$this->ensureWriteAccess();

		if (strtoupper((string)$this->input->method()) !== 'POST') {
			show_error('Invalid request method', 405);
			return;
		}

		$ids = $this->input->post('ids', true);
		if (!is_array($ids)) {
			$ids = [$this->input->post('id', true)];
		}
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($v) {
			return $v > 0;
		})));
		if (empty($ids) || count($ids) > 50) {
			$this->session->set_flashdata('danger', 'Invalid payment selection.');
			redirect('Accounting/Payment');
			return;
		}

		// A deletion must say why — the reason goes to the Payment Activity
		// Log and Super Admin's Audit Trail with each deleted entry.
		$reason = trim(preg_replace('/\s+/u', ' ', (string)$this->input->post('reason', true)));
		if (mb_strlen($reason) < 5) {
			$this->session->set_flashdata('danger', 'Please give a reason for deleting this payment (at least 5 characters).');
			redirect('Accounting/Payment');
			return;
		}
		$reason = mb_substr($reason, 0, 255);

		// Who the payments belong to decides which lock to take; the rows are
		// then read again under that lock, so two users deleting the same
		// entry at once cannot both "delete" it and log it twice.
		$owners = $this->db->select('StudentNumber, ORNumber')->from('paymentsaccounts')->where_in('ID', $ids)->get()->result();
		if (count($owners) !== count($ids)) {
			$this->session->set_flashdata('danger', 'Payment not found. It may have just been deleted by another user.');
			redirect('Accounting/Payment');
			return;
		}
		$students = array_unique(array_map(function ($o) { return trim((string)$o->StudentNumber); }, $owners));
		$receipts = array_unique(array_map(function ($o) { return trim((string)$o->ORNumber); }, $owners));
		if (count($students) !== 1 || count($receipts) !== 1) {
			$this->session->set_flashdata('danger', 'Only fees on the same receipt (same O.R. and student) can be deleted together.');
			redirect('Accounting/Payment');
			return;
		}
		$studentNumber = reset($students);

		$this->load->library('payment_lock');
		if (!$this->payment_lock->acquire($studentNumber)) {
			$this->session->set_flashdata('danger', 'Another payment for this student is being saved right now. Please try again in a moment.');
			redirect('Accounting/Payment');
			return;
		}

		try {
			$payments = $this->db->select('ID, StudentNumber, ORNumber, PDate, Amount, description, Sem, SY, ORStatus, CollectionSource')
				->from('paymentsaccounts')
				->where_in('ID', $ids)
				->order_by('ID', 'ASC')
				->get()
				->result();

			if (count($payments) !== count($ids)) {
				$this->session->set_flashdata('danger', 'Payment not found. It may have just been deleted by another user.');
				redirect('Accounting/Payment');
				return;
			}

			$terms = [];
			foreach ($payments as $payment) {
				if ((string)$payment->ORStatus !== 'Valid') {
					$this->session->set_flashdata('danger', 'Only VALID payments can be deleted.');
					redirect('Accounting/Payment');
					return;
				}
				if ((string)$payment->CollectionSource !== "Student's Account") {
					$this->session->set_flashdata('danger', "This payment is not under Student's Account.");
					redirect('Accounting/Payment');
					return;
				}
				$terms[trim((string)$payment->Sem) . '|' . trim((string)$payment->SY)] = true;
			}

			$this->db->trans_start();

			$this->db->where_in('ID', $ids)->delete('paymentsaccounts');

			// Recompute totals for each term the deleted fees were booked in.
			foreach (array_keys($terms) as $term) {
				[$sem, $sy] = explode('|', $term, 2);
				$this->recomputeStudeAccount($studentNumber, $sem, $sy);
			}

			$this->db->trans_complete();
		} finally {
			$this->payment_lock->release();
		}

		if ($this->db->trans_status() === false) {
			$this->session->set_flashdata('danger', 'Unable to delete payment. Please try again.');
			redirect('Accounting/Payment');
			return;
		}

		$total = 0.0;
		foreach ($payments as $payment) {
			$total += (float)$payment->Amount;
			$this->logPaymentAudit('delete', $payment, null, $reason);
		}

		$orNumber = trim((string)$payments[0]->ORNumber);
		$this->session->set_flashdata('success', count($payments) > 1
			? 'Deleted ' . count($payments) . ' payments' . ($orNumber !== '' ? ' from O.R. #' . $orNumber : '') . ' totalling ₱' . number_format($total, 2) . '.'
			: 'Payment deleted successfully.');
		redirect('Accounting/Payment');
	}

	// Visible to Cashier, Admin, and read-only Auditor accounts.
	public function paymentAuditLog()
	{
		$this->ensureAccess();

		$this->db->select("l.*,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName", false);
		$this->db->from('payment_audit_log l');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = l.student_number', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = l.student_number', 'left');
		// This screen is for edits and deletions. New entries are logged too,
		// but they show in Super Admin's Audit Trail, not here.
		$this->db->where_in('l.action', ['edit', 'delete']);
		$this->db->order_by('l.changed_at', 'DESC');
		$this->db->limit(300);
		$rows = $this->db->get()->result();

		if ($this->input->get('print', true) === '1') {
			$printRows = [];
			foreach ($rows as $row) {
				$studentName = trim((string)($row->LastName ?? ''));
				if ($studentName !== '') $studentName .= ', ';
				$studentName .= trim((string)($row->FirstName ?? ''));
				if (trim($studentName) === '') $studentName = (string)($row->student_number ?? '');

				$printRows[] = [
					date('M d, Y h:i A', strtotime((string)$row->changed_at)),
					ucfirst((string)$row->action),
					(string)$row->or_number,
					$studentName,
					(string)$row->description,
					'₱ ' . number_format((float)$row->amount, 2),
					(string)$row->changed_by,
					(string)($row->reason ?? ''),
				];
			}

			$this->renderReportPrint(
				'Payment Activity Log',
				[
					['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')],
					['label' => 'Total Entries', 'value' => number_format(count($rows))],
				],
				['Date & Time', 'Action', 'O.R.', 'Student', 'Description', 'Amount', 'Changed By', 'Reason'],
				$printRows,
				['left', 'left', 'left', 'left', 'left', 'right', 'left', 'left'],
				null,
				base_url('Accounting/paymentAuditLog'),
				'landscape',
				'No edits or deletions recorded yet.'
			);
			return;
		}

		$this->load->view('accounting_payment_log', ['rows' => $rows]);
	}

	// Accounting ledger: collections vs. expenses with a running balance, so
	// "what's the gross, what did we spend, what's left" is one screen.
	public function ledger()
	{
		$this->ensureAccess();

		$from = trim((string)$this->input->get('from', true));
		$to   = trim((string)$this->input->get('to', true));
		if (!$this->isValidDate($from)) {
			$from = date('Y-m-01');
		}
		if (!$this->isValidDate($to)) {
			$to = date('Y-m-d');
		}

		$rows = $this->ledgerRows($from, $to);

		$gross = 0.0;
		$spent = 0.0;
		foreach ($rows as $row) {
			if ($row['type'] === 'income') {
				$gross += $row['amount'];
			} else {
				$spent += $row['amount'];
			}
		}

		if ($this->input->get('print', true) === '1') {
			$printRows = [];
			foreach ($rows as $row) {
				$printRows[] = [
					date('M d, Y', strtotime($row['date'])),
					$row['type'] === 'income' ? 'Collection' : 'Expense',
					$row['description'],
					$row['ref'],
					($row['type'] === 'income' ? '+ ' : '- ') . '₱ ' . number_format($row['amount'], 2),
					'₱ ' . number_format($row['balance'], 2),
				];
			}

			$this->renderReportPrint(
				'Ledger Report',
				[
					['label' => 'Period', 'value' => date('M d, Y', strtotime($from)) . ' to ' . date('M d, Y', strtotime($to))],
					['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')],
					['label' => 'Gross Collections', 'value' => '₱ ' . number_format($gross, 2)],
					['label' => 'Total Expenses', 'value' => '₱ ' . number_format($spent, 2)],
					['label' => 'Net', 'value' => '₱ ' . number_format($gross - $spent, 2)],
				],
				['Date', 'Type', 'Description', 'Reference', 'Amount', 'Balance'],
				$printRows,
				['left', 'left', 'left', 'left', 'right', 'right'],
				null,
				base_url('Accounting/ledger?from=' . urlencode($from) . '&to=' . urlencode($to)),
				'landscape',
				'No collections or expenses in this period.'
			);
			return;
		}

		$data = [
			'from'  => $from,
			'to'    => $to,
			'rows'  => $rows,
			'gross' => $gross,
			'spent' => $spent,
			'net'   => $gross - $spent,
		];

		$this->load->view('accounting_ledger', $data);
	}

	// Students paying in installments: per (student, fee) balance remaining
	// after their payments so far this term, for follow-up and printing.
	public function partialPayments()
	{
		$this->ensureAccess();

		[$sem, $sy] = $this->currentSemSy();
		$rows = $this->partialPaymentRows($sem, $sy);

		$totalOutstanding = 0.0;
		$students = [];
		foreach ($rows as $row) {
			$row->FullAmount = (float)$row->FullAmount;
			$row->PaidAmount = (float)$row->PaidAmount;
			$row->Outstanding = $row->FullAmount - $row->PaidAmount;
			$totalOutstanding += $row->Outstanding;
			$students[(string)$row->StudentNumber] = true;
		}

		if ($this->input->get('print', true) === '1') {
			$printRows = [];
			foreach ($rows as $row) {
				$studentName = trim((string)($row->LastName ?? ''));
				if ($studentName !== '') $studentName .= ', ';
				$studentName .= trim((string)(($row->FirstName ?? '') . ' ' . ($row->MiddleName ?? '')));
				if (trim($studentName) === '') $studentName = (string)$row->StudentNumber;

				$printRows[] = [
					(string)$row->StudentNumber,
					$studentName,
					(string)$row->Description,
					'₱ ' . number_format($row->FullAmount, 2),
					'₱ ' . number_format($row->PaidAmount, 2),
					'₱ ' . number_format($row->Outstanding, 2),
					date('M d, Y', strtotime((string)$row->LastPaymentDate)),
				];
			}

			$this->renderReportPrint(
				'Students with Partial Payments',
				[
					['label' => 'Term', 'value' => trim($sem . ' ' . $sy)],
					['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')],
					['label' => 'Students With Balance', 'value' => number_format(count($students))],
					['label' => 'Total Outstanding', 'value' => '₱ ' . number_format($totalOutstanding, 2)],
				],
				['Student No.', 'Student Name', 'Description', 'Full Amount', 'Paid', 'Outstanding', 'Last Payment'],
				$printRows,
				['left', 'left', 'left', 'right', 'right', 'right', 'left'],
				null,
				base_url('Accounting/partialPayments'),
				'landscape',
				'No outstanding partial payments for this term.'
			);
			return;
		}

		$data = [
			'rows'             => $rows,
			'sem'              => $sem,
			'sy'               => $sy,
			'totalOutstanding' => $totalOutstanding,
			'studentCount'     => count($students),
		];

		$this->load->view('accounting_partial_payments', $data);
	}

	// Read-only balance + payment history for one student, shown in the side
	// panel on Payment, Partial Payments, Collection Report and the Log.
	public function studentSummary()
	{
		$this->ensureAccess();
		$this->output->set_content_type('application/json');

		$studentNumber = trim((string)$this->input->get('id', true));
		[$sem, $sy] = $this->currentSemSy();
		$student = $studentNumber === '' ? null : $this->getStudentContext($studentNumber, $sem, $sy);

		$base = function () use ($studentNumber) {
			return $this->db->from('paymentsaccounts')
				->where('StudentNumber', $studentNumber)
				->where('ORStatus', 'Valid')
				->where('CollectionSource', "Student's Account");
		};

		$allTime = $studentNumber === '' ? null : $base()
			->select('COUNT(*) AS n, COALESCE(SUM(Amount),0) AS total', false)
			->get()->row();

		if (!$student && (int)($allTime->n ?? 0) === 0) {
			$this->output->set_status_header(404)->set_output(json_encode(['ok' => false, 'message' => 'Student not found.']));
			return;
		}

		$feeQuery = $base()->select('description, MAX(FeeFullAmount) AS full, SUM(Amount) AS paid, COUNT(*) AS n, MAX(PDate) AS last', false);
		if ($sem !== '') $feeQuery->where('Sem', $sem);
		if ($sy !== '') $feeQuery->where('SY', $sy);
		$fees = [];
		$termPaid = 0.0;
		$termOutstanding = 0.0;
		foreach ($feeQuery->group_by('description')->order_by('description', 'ASC')->get()->result() as $fee) {
			$full = (float)$fee->full;
			$paid = (float)$fee->paid;
			$outstanding = $full > 0 ? max($full - $paid, 0.0) : 0.0;
			$termPaid += $paid;
			$termOutstanding += $outstanding;
			$fees[] = [
				'description' => (string)$fee->description,
				'full'        => $full,
				'paid'        => $paid,
				'outstanding' => $outstanding,
				'status'      => $full <= 0 ? 'n/a' : ($outstanding > 0.004 ? 'partial' : 'paid'),
				'count'       => (int)$fee->n,
				'last'        => $fee->last ? date('M j, Y', strtotime((string)$fee->last)) : '',
			];
		}

		$payments = [];
		$recent = $base()
			->select('PDate, pTime, ORNumber, description, Amount, PaymentType, Cashier, Sem, SY')
			->order_by('PDate', 'DESC')->order_by('pTime', 'DESC')->order_by('ID', 'DESC')
			->limit(30)->get()->result();
		foreach ($recent as $p) {
			$payments[] = [
				'date'        => date('M j, Y', strtotime((string)$p->PDate)),
				'time'        => trim((string)$p->pTime) !== '' ? date('g:i A', strtotime((string)$p->pTime)) : '',
				'or'          => (string)$p->ORNumber,
				'description' => (string)$p->description,
				'amount'      => (float)$p->Amount,
				'type'        => (string)$p->PaymentType,
				'cashier'     => (string)$p->Cashier,
				'term'        => trim($p->Sem . ' ' . $p->SY),
			];
		}

		$name = trim((string)($student->LastName ?? ''));
		$first = trim(preg_replace('/\s+/', ' ', (string)($student->FirstName ?? '') . ' ' . (string)($student->MiddleName ?? '')));
		if ($first !== '') $name .= ($name !== '' ? ', ' : '') . $first;

		$this->output->set_output(json_encode([
			'ok'              => true,
			'studno'          => $studentNumber,
			'name'            => $name !== '' ? $name : $studentNumber,
			'course'          => trim((string)($student->Course ?? '')),
			'major'           => trim((string)($student->Major ?? '')),
			'yearLevel'       => trim((string)($student->YearLevel ?? '')),
			'email'           => trim((string)($student->Email ?? '')),
			'term'            => trim($sem . ' ' . $sy),
			'fees'            => $fees,
			'termPaid'        => $termPaid,
			'termOutstanding' => $termOutstanding,
			'payments'        => $payments,
			'paymentCount'    => (int)($allTime->n ?? 0),
			'allTimePaid'     => (float)($allTime->total ?? 0),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	// ------------------------------------------------------------------
	// Financial statements (Income Statement, Cash Flow, Balance Sheet)
	// ------------------------------------------------------------------
	// This module is cash-basis: collections and expenses are the only cash
	// movements recorded, so all three statements are derived from the same
	// two tables and always reconcile with each other.
	//
	// The term (Sem/SY) scopes collections and receivables, since both are
	// term-stamped. Expenses carry no term field and are matched to the
	// period purely by date.

	// Distinct Sem / SY values seen on payment and account records, merged
	// with the configured semester list and the active term, so the filter
	// offers every term that could have data plus the current one.
	private function statementTermOptions($activeSem, $activeSy)
	{
		$sems = array_fill_keys(Term::SEMESTERS, true);
		$sys  = [];
		foreach (['paymentsaccounts', 'studeaccount'] as $table) {
			foreach ($this->db->distinct()->select('Sem, SY')->from($table)->get()->result() as $row) {
				if (trim((string)$row->Sem) !== '') {
					$sems[(string)$row->Sem] = true;
				}
				if (trim((string)$row->SY) !== '') {
					$sys[(string)$row->SY] = true;
				}
			}
		}
		if ($activeSem !== '') {
			$sems[$activeSem] = true;
		}
		if ($activeSy !== '') {
			$sys[$activeSy] = true;
		}

		$semList = array_keys($sems);
		sort($semList, SORT_STRING);
		$syList = array_keys($sys);
		rsort($syList, SORT_STRING);

		return [$semList, $syList];
	}

	// Default reporting window: from the first collection in scope to today,
	// so an end-of-semester report covers the whole term without the user
	// having to type dates. Falls back to month-to-date when the term has
	// no collections yet.
	private function statementDefaultRange($sem, $sy)
	{
		$q = $this->db->select('MIN(PDate) AS FirstDate', false)
			->from('paymentsaccounts')
			->where('ORStatus', 'Valid');
		if ($sem !== '') {
			$q->where('Sem', $sem);
		}
		if ($sy !== '') {
			$q->where('SY', $sy);
		}
		$first = trim((string)($q->get()->row()->FirstDate ?? ''));

		return [$this->isValidDate($first) ? $first : date('Y-m-01'), date('Y-m-d')];
	}

	// Collections grouped by fee description — the revenue / inflow lines.
	private function statementCollectionRows($from, $to, $sem, $sy)
	{
		$q = $this->db->select("COALESCE(NULLIF(TRIM(description),''),'(No description)') AS Item,
				COUNT(*) AS TxnCount, COALESCE(SUM(Amount),0) AS Total", false)
			->from('paymentsaccounts')
			->where('ORStatus', 'Valid')
			->where('CollectionSource', "Student's Account")
			->where('PDate >=', $from)
			->where('PDate <=', $to);
		if ($sem !== '') {
			$q->where('Sem', $sem);
		}
		if ($sy !== '') {
			$q->where('SY', $sy);
		}

		return $q->group_by('Item')->order_by('Item', 'ASC')->get()->result();
	}

	// Expenses grouped by category — the expense / outflow lines.
	private function statementExpenseRows($from, $to)
	{
		return $this->db->select("COALESCE(NULLIF(TRIM(Category),''),'Uncategorized') AS Item,
				COUNT(*) AS TxnCount, COALESCE(SUM(Amount),0) AS Total", false)
			->from('expenses')
			->where('ExpenseDate >=', $from)
			->where('ExpenseDate <=', $to)
			->group_by('Item')
			->order_by('Item', 'ASC')
			->get()
			->result();
	}

	// Single sum over the scoped collections; null bounds mean "unbounded"
	// in that direction, which is how beginning/as-of cash is computed.
	private function sumCollections($sem, $sy, $from = null, $to = null)
	{
		$q = $this->db->select('COALESCE(SUM(Amount),0) AS Total', false)
			->from('paymentsaccounts')
			->where('ORStatus', 'Valid')
			->where('CollectionSource', "Student's Account");
		if ($from !== null) {
			$q->where('PDate >=', $from);
		}
		if ($to !== null) {
			$q->where('PDate <=', $to);
		}
		if ($sem !== '') {
			$q->where('Sem', $sem);
		}
		if ($sy !== '') {
			$q->where('SY', $sy);
		}

		return (float)($q->get()->row()->Total ?? 0);
	}

	private function sumExpenses($from = null, $to = null)
	{
		$q = $this->db->select('COALESCE(SUM(Amount),0) AS Total', false)->from('expenses');
		if ($from !== null) {
			$q->where('ExpenseDate >=', $from);
		}
		if ($to !== null) {
			$q->where('ExpenseDate <=', $to);
		}

		return (float)($q->get()->row()->Total ?? 0);
	}

	// Receivables for the balance sheet: unpaid fee balances aggregated per
	// fee description, using the same rule as the Partial Payments report
	// (frozen FeeFullAmount minus what each student has actually paid).
	// Name joins are skipped — only counts and amounts are needed here, so
	// this stays fast even with thousands of students on partial terms.
	private function statementReceivableRows($sem, $sy)
	{
		$q = $this->db->select('p.description AS Description, MAX(p.FeeFullAmount) AS FullAmount,
				SUM(p.Amount) AS PaidAmount, p.StudentNumber', false)
			->from('paymentsaccounts p')
			->where('p.ORStatus', 'Valid')
			->where('p.CollectionSource', "Student's Account")
			->where('p.FeeFullAmount >', 0);
		if ($sem !== '') {
			$q->where('p.Sem', $sem);
		}
		if ($sy !== '') {
			$q->where('p.SY', $sy);
		}
		$rows = $q->group_by('p.StudentNumber, p.description')
			->having('SUM(p.Amount) < MAX(p.FeeFullAmount)', null, false)
			->get()
			->result();

		$lines = [];
		$students = [];
		$total = 0.0;
		foreach ($rows as $row) {
			$outstanding = (float)$row->FullAmount - (float)$row->PaidAmount;
			if ($outstanding <= 0.004) {
				continue;
			}
			$item = trim((string)$row->Description);
			if ($item === '') {
				$item = '(No description)';
			}
			if (!isset($lines[$item])) {
				$lines[$item] = ['item' => $item, 'balances' => 0, 'amount' => 0.0];
			}
			$lines[$item]['balances']++;
			$lines[$item]['amount'] += $outstanding;
			$students[(string)$row->StudentNumber] = true;
			$total += $outstanding;
		}
		ksort($lines, SORT_STRING);

		return [array_values($lines), count($students), $total];
	}

	public function financialStatements()
	{
		$this->ensureAccess();

		[$activeSem, $activeSy] = $this->currentSemSy();

		// Missing term params default to the active term; an explicitly blank
		// choice means "all terms".
		$semParam = $this->input->get('sem', true);
		$syParam  = $this->input->get('sy', true);
		$sem = $semParam === null ? $activeSem : trim((string)$semParam);
		$sy  = $syParam === null ? $activeSy : trim((string)$syParam);

		$from = trim((string)$this->input->get('from', true));
		$to   = trim((string)$this->input->get('to', true));
		if (!$this->isValidDate($from) || !$this->isValidDate($to)) {
			[$defFrom, $defTo] = $this->statementDefaultRange($sem, $sy);
			if (!$this->isValidDate($from)) {
				$from = $defFrom;
			}
			if (!$this->isValidDate($to)) {
				$to = $defTo;
			}
		}
		if ($from > $to) {
			[$from, $to] = [$to, $from];
		}

		// Which statement to print — 'all' prints the full set. On screen the
		// same value just decides which tab opens first.
		$stmt = strtolower(trim((string)$this->input->get('stmt', true)));
		if (!in_array($stmt, ['income', 'cashflow', 'balance', 'all'], true)) {
			$stmt = 'all';
		}

		$revenueRows = $this->statementCollectionRows($from, $to, $sem, $sy);
		$expenseRows = $this->statementExpenseRows($from, $to);

		$totalRevenue  = 0.0;
		$revenueCount  = 0;
		foreach ($revenueRows as $row) {
			$row->Total = (float)$row->Total;
			$totalRevenue += $row->Total;
			$revenueCount += (int)$row->TxnCount;
		}
		$totalExpenses = 0.0;
		$expenseCount  = 0;
		foreach ($expenseRows as $row) {
			$row->Total = (float)$row->Total;
			$totalExpenses += $row->Total;
			$expenseCount += (int)$row->TxnCount;
		}
		$netIncome = $totalRevenue - $totalExpenses;

		// Cash positions are always FUND-WIDE. The cash box doesn't know what
		// term a payment was for, and expenses carry no term tag — so scoping
		// collections to a term while counting every expense would understate
		// the fund's real position. The term filter only scopes which
		// collections show as this term's revenue lines; the cash flow adds a
		// reconciliation line for collections stamped to other terms.
		$periodCollectionsAll  = $this->sumCollections('', '', $from, $to);
		$otherTermCollections  = $periodCollectionsAll - $totalRevenue;
		$netCashFlow           = $periodCollectionsAll - $totalExpenses;
		$dayBefore             = date('Y-m-d', strtotime($from . ' -1 day'));
		$beginCash             = $this->sumCollections('', '', null, $dayBefore) - $this->sumExpenses(null, $dayBefore);
		$endCash               = $beginCash + $netCashFlow;

		[$receivableRows, $receivableStudents, $totalReceivables] = $this->statementReceivableRows($sem, $sy);
		[$semOptions, $syOptions] = $this->statementTermOptions($activeSem, $activeSy);

		$termLabel = ($sem === '' && $sy === '') ? 'All terms' : trim($sem . ' ' . $sy);
		$periodLabel = date('M d, Y', strtotime($from)) . ' to ' . date('M d, Y', strtotime($to));

		if ($this->input->get('print', true) === '1') {
			$fmtLine = function ($label, $count, $amount, $indent = true) {
				return [
					($indent ? '      ' : '') . $label,
					$count === null ? '' : number_format((int)$count),
					$amount === null ? '' : '₱ ' . number_format((float)$amount, 2),
				];
			};

			$incomeRows = [$fmtLine('REVENUES', null, null, false)];
			foreach ($revenueRows as $row) {
				$incomeRows[] = $fmtLine($row->Item, $row->TxnCount, $row->Total);
			}
			$incomeRows[] = $fmtLine('Total Revenues', $revenueCount, $totalRevenue, false);
			$incomeRows[] = $fmtLine('', null, null, false);
			$incomeRows[] = $fmtLine('EXPENSES', null, null, false);
			foreach ($expenseRows as $row) {
				$incomeRows[] = $fmtLine($row->Item, $row->TxnCount, $row->Total);
			}
			$incomeRows[] = $fmtLine('Total Expenses', $expenseCount, $totalExpenses, false);
			$netLabel = $netIncome < 0 ? 'NET LOSS' : 'NET INCOME';
			$netDisplay = $netIncome < 0
				? '(₱ ' . number_format(abs($netIncome), 2) . ')'
				: '₱ ' . number_format($netIncome, 2);

			$cfRows = [
				$fmtLine('CASH FLOWS FROM OPERATING ACTIVITIES', null, null, false),
				$fmtLine('Cash inflows — collections', null, null, false),
			];
			foreach ($revenueRows as $row) {
				$cfRows[] = $fmtLine($row->Item, $row->TxnCount, $row->Total);
			}
			if (abs($otherTermCollections) > 0.004) {
				$cfRows[] = $fmtLine('Collections credited to other terms', null, $otherTermCollections);
			}
			$cfRows[] = $fmtLine('Total cash inflows', null, $periodCollectionsAll, false);
			$cfRows[] = $fmtLine('Cash outflows — expenses', null, null, false);
			foreach ($expenseRows as $row) {
				$cfRows[] = $fmtLine($row->Item, $row->TxnCount, $row->Total);
			}
			$cfRows[] = $fmtLine('Total cash outflows', $expenseCount, $totalExpenses, false);
			$cfRows[] = $fmtLine('Net cash flow for the period', null, $netCashFlow, false);
			$cfRows[] = $fmtLine('Cash at beginning of period', null, $beginCash, false);
			$cfRows[] = $fmtLine('CASH AT END OF PERIOD', null, $endCash, false);

			$bsRows = [
				$fmtLine('ASSETS', null, null, false),
				$fmtLine('Cash on hand', null, $endCash),
			];
			foreach ($receivableRows as $row) {
				$bsRows[] = $fmtLine('Accounts receivable — ' . $row['item'], $row['balances'], $row['amount']);
			}
			if (empty($receivableRows)) {
				$bsRows[] = $fmtLine("Accounts receivable — students' unpaid fee balances", 0, 0.0);
			}
			$bsRows[] = $fmtLine('TOTAL ASSETS', null, $endCash + $totalReceivables, false);
			$bsRows[] = $fmtLine('', null, null, false);
			$bsRows[] = $fmtLine('LIABILITIES', null, null, false);
			$bsRows[] = $fmtLine('None recorded', null, 0.0);
			$bsRows[] = $fmtLine('TOTAL LIABILITIES', null, 0.0, false);
			$bsRows[] = $fmtLine('', null, null, false);
			$bsRows[] = $fmtLine('FUND BALANCE', null, null, false);
			$bsRows[] = $fmtLine('Fund balance, end of period', null, $endCash + $totalReceivables);
			$bsRows[] = $fmtLine('TOTAL LIABILITIES AND FUND BALANCE', null, $endCash + $totalReceivables, false);

			$allSections = [
				'income'   => [
					'title'   => 'Income Statement — ' . $periodLabel,
					'columns' => ['Line Item', 'Entries', 'Amount'],
					'rows'    => $incomeRows,
					'aligns'  => ['left', 'center', 'right'],
					'totals'  => [$netLabel, '', $netDisplay],
				],
				'cashflow' => [
					'title'   => 'Cash Flow Statement — ' . $periodLabel,
					'columns' => ['Line Item', 'Entries', 'Amount'],
					'rows'    => $cfRows,
					'aligns'  => ['left', 'center', 'right'],
					'totals'  => null,
				],
				'balance'  => [
					'title'   => 'Balance Sheet — as of ' . date('M d, Y', strtotime($to)) . ' (receivables: ' . $termLabel . ')',
					'columns' => ['Line Item', 'Balances', 'Amount'],
					'rows'    => $bsRows,
					'aligns'  => ['left', 'center', 'right'],
					'totals'  => null,
				],
			];

			$stmtTitles = [
				'all'      => 'Financial Statements',
				'income'   => 'Income Statement',
				'cashflow' => 'Cash Flow Statement',
				'balance'  => 'Balance Sheet',
			];
			$sections = $stmt === 'all'
				? array_values($allSections)
				: [$allSections[$stmt]];

			$this->renderReportPrint(
				$stmtTitles[$stmt],
				[
					['label' => 'Period', 'value' => $periodLabel],
					['label' => 'Term', 'value' => $termLabel],
					['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')],
					['label' => $netIncome < 0 ? 'Net Loss' : 'Net Income', 'value' => $netDisplay],
				],
				[],
				[],
				['left', 'center', 'right'],
				null,
				base_url('Accounting/financialStatements?from=' . urlencode($from) . '&to=' . urlencode($to) . '&sem=' . urlencode($sem) . '&sy=' . urlencode($sy) . '&stmt=' . urlencode($stmt)),
				'portrait',
				'No data for this period.',
				$sections
			);
			return;
		}

		$this->load->view('accounting_financial_statements', [
			'from'                => $from,
			'to'                  => $to,
			'sem'                 => $sem,
			'sy'                  => $sy,
			'termLabel'           => $termLabel,
			'periodLabel'         => $periodLabel,
			'semOptions'          => $semOptions,
			'syOptions'           => $syOptions,
			'revenueRows'         => $revenueRows,
			'expenseRows'         => $expenseRows,
			'totalRevenue'        => $totalRevenue,
			'totalExpenses'       => $totalExpenses,
			'netIncome'           => $netIncome,
			'periodCollectionsAll' => $periodCollectionsAll,
			'otherTermCollections' => $otherTermCollections,
			'netCashFlow'         => $netCashFlow,
			'beginCash'           => $beginCash,
			'endCash'             => $endCash,
			'receivableRows'      => $receivableRows,
			'receivableStudents'  => $receivableStudents,
			'totalReceivables'    => $totalReceivables,
			'activeStmt'          => $stmt,
		]);
	}
}

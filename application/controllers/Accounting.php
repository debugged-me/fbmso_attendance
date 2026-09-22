<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accounting extends CI_Controller
{
	private $allowedLevels = ['Admin', 'Cashier'];
	private $receiptSettingsCache = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper(['url', 'form']);
		$this->load->library(['session', 'form_validation', 'term']);
		$this->load->model('SettingsModel');
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

	public function index()
	{
		$this->ensureAccess();
		if ((string)$this->session->userdata('level') === 'Cashier') {
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

	// Records who edited or deleted a payment, and what it looked like
	// before/after — so a cashier's changes are visible to Admin, not silent.
	private function logPaymentAudit($action, $payment, $newValues = null)
	{
		$changedBy = trim((string)$this->session->userdata('username'));
		if ($changedBy === '') {
			$changedBy = trim((string)$this->session->userdata('IDNumber'));
		}

		$oldValues = [
			'StudentNumber' => (string)($payment->StudentNumber ?? ''),
			'ORNumber'      => (string)($payment->ORNumber ?? ''),
			'PDate'         => (string)($payment->PDate ?? ''),
			'Amount'        => (string)($payment->Amount ?? ''),
			'description'   => (string)($payment->description ?? ''),
		];

		$this->db->insert('payment_audit_log', [
			'payment_id'     => (int)($payment->ID ?? 0),
			'action'         => $action,
			'or_number'      => (string)($payment->ORNumber ?? ''),
			'student_number' => (string)($payment->StudentNumber ?? ''),
			'description'    => (string)($payment->description ?? ''),
			'amount'         => (float)($payment->Amount ?? 0),
			'old_values'     => json_encode($oldValues),
			'new_values'     => $newValues !== null ? json_encode($newValues) : null,
			'changed_by'     => $changedBy,
			'changed_at'     => (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s'),
		]);
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
		$description = trim((string)$this->input->post('description', true));
		if ($description === '') {
			$description = trim((string)$this->input->post('descriptionField', true));
		}

		$state = [
			'StudentNumber' => trim((string)$this->input->post('StudentNumber', true)),
			'ORNumber'      => trim((string)$this->input->post('ORNumber', true)),
			'PDate'         => trim((string)$this->input->post('PDate', true)),
			'description'   => $description,
			'Amount'        => trim((string)$this->input->post('Amount', true)),
		];

		return array_merge($state, $overrides);
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

	private function isDuplicateDbError($dbError)
	{
		$code = (int)($dbError['code'] ?? 0);
		$message = (string)($dbError['message'] ?? '');

		return $code === 1062 || stripos($message, 'Duplicate entry') !== false;
	}



	public function expenses()
	{
		$this->ensureAccess();

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
			$this->SettingsModel->insertexpenses($data);

			// Redirect back to the expenses page after saving
			redirect('Accounting/expenses');
		}
	}


	public function updateexpenses()
	{
		$this->ensureAccess();
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

			$this->SettingsModel->updateexpenses($expensesid, $Description, $Amount, $Responsible, $ExpenseDate, $Category);
			$this->session->set_flashdata('expenses', 'Record updated successfully');
			redirect("Accounting/expenses");
		}
	}



	public function Deleteexpenses()
	{
		$this->ensureAccess();
		$expensesid = $this->input->get('expensesid');
		if ($expensesid) {
			$this->SettingsModel->Delete_expenses($expensesid);
			$this->session->set_flashdata('expenses', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('expenses', 'Error deleting record');
		}

		redirect("Accounting/expenses");
	}


	public function expensescategory()
	{
		$this->ensureAccess();
		$data['data'] = $this->SettingsModel->get_expensesCategory();
		$this->load->view('expensescategory', $data);

		if ($this->input->post('save')) {
			$data = array(
				'Category' => $this->input->post('Category'),
			);
			$this->SettingsModel->insertexpensesCategory($data);

			// Redirect back to the expenses category page after saving
			redirect('Accounting/expensescategory');
		}
	}

	public function updateexpensescategory()
	{
		$this->ensureAccess();
		$categoryID = $this->input->get('categoryID');
		$result['data'] = $this->SettingsModel->getexpensescategorybyId($categoryID);
		$this->load->view('updateexpensescategory', $result);

		if ($this->input->post('update')) {

			$Category = $this->input->post('Category');


			$this->SettingsModel->updateexpensescategory($categoryID, $Category);
			$this->session->set_flashdata('expenses', 'Record updated successfully');
			redirect("Accounting/expensescategory");
		}
	}


	public function Deleteexpensescategory()
	{
		$this->ensureAccess();
		$categoryID = $this->input->get('categoryID');
		if ($categoryID) {
			$this->SettingsModel->Delete_expensescategory($categoryID);
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


	public function get_expenses()
	{
		$query = $this->db->get('expenses');
		return $query->result();
	}

	public function insertexpenses($data)
	{
		return $this->db->insert('expenses', $data);
	}

	public function getexpensesbyId($expensesid)
	{
		$query = $this->db->query("SELECT * FROM expenses WHERE expensesid = '" . $expensesid . "'");
		return $query->result();
	}

	public function Delete_expenses($expensesid)
	{
		$this->db->where('expensesid', $expensesid);
		$this->db->delete('expenses');
	}


	public function get_expensesCategory()
	{
		$query = $this->db->get('expensescategory');
		return $query->result();
	}

	public function insertexpensesCategory($data)
	{
		return $this->db->insert('expensescategory', $data);
	}

	public function getexpensescategorybyId($categoryID)
	{
		$query = $this->db->query("SELECT * FROM expensescategory WHERE categoryID = '" . $categoryID . "'");
		return $query->result();
	}


	public function Delete_expensescategory($categoryID)
	{
		$this->db->where('categoryID', $categoryID);
		$this->db->delete('expensescategory');
	}


	public function get_categories()
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

		return $students;
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

	private function getRecentPayments($date = null, $limit = 200)
	{
		// Payments are tagged to the student's enrolment term, not always the
		// active one — list the latest across all terms, otherwise a payment
		// for another semester would look like it was never recorded.
		// Status is per fee, not per receipt: two instalments that together
		// settle a fee must both read "Fully Paid", not "Partial" twice. The
		// price compared against is the one frozen onto the payment itself.
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
		$this->db->limit((int)$limit);
		return $this->db->get()->result();
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
	private function renderReportPrint($title, array $meta, array $columns, array $rows, array $aligns = [], $totals = null, $backUrl = '', $orientation = 'landscape', $emptyMessage = 'No records matched.')
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

	private function buildReceiptEmailHtml($payment, $settings)
	{
		return $this->load->view('accounting_receipt_email', [
			'payment' => $payment,
			'settings' => $settings,
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
			$submitToken = trim((string)$this->input->post('payment_submit_token', true));
			if (!$this->consumePaymentSubmitToken($submitToken)) {
				$this->session->set_flashdata('danger', 'This payment form was already submitted or expired. Please try again.');
				redirect('Accounting/Payment');
				return;
			}

			$this->form_validation->set_rules('StudentNumber', 'Student', 'required|trim');
			$this->form_validation->set_rules('description', 'Description', 'required|trim');
			$this->form_validation->set_rules('Amount', 'Amount', 'required|numeric|greater_than[0]');
			$this->form_validation->set_rules('PDate', 'Payment Date', 'required|trim');

			if ($this->form_validation->run() === false) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', strip_tags(validation_errors(' ', ' ')));
				redirect('Accounting/Payment');
				return;
			}

			$studentNumber = trim((string)$this->input->post('StudentNumber', true));
			$description   = trim((string)$this->input->post('description', true));
			$amount        = (float)$this->input->post('Amount', true);
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

			// The amount is only checkable when the description is a configured
			// fee with a price; free-text descriptions stay unconstrained. The
			// UI enforces the same rules, but it enforces them in the browser —
			// this is the copy that actually decides.
			$balance      = $this->feeBalanceFor($studentNumber, $description, $sem, $sy);
			$isPartial    = trim((string)$this->input->post('IsPartial', true)) !== '';
			$feeFullAmount = $balance['full'];

			if ($feeFullAmount > 0) {
				if ($balance['remaining'] <= 0.004) {
					$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
					$this->session->set_flashdata('danger', $description . ' is already fully paid for this term (₱' . number_format($balance['paid'], 2) . ' of ₱' . number_format($feeFullAmount, 2) . ').');
					redirect('Accounting/Payment');
					return;
				}

				if ($amount > $balance['remaining'] + 0.004) {
					$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
					$this->session->set_flashdata('danger', 'Amount exceeds the ₱' . number_format($balance['remaining'], 2) . ' still owed on ' . $description . ' for this term.');
					redirect('Accounting/Payment');
					return;
				}

				if ($amount + 0.004 < $balance['remaining'] && !$isPartial) {
					$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
					$this->session->set_flashdata('danger', 'This is less than the ₱' . number_format($balance['remaining'], 2) . ' still owed. Tick "Partial payment" to record it as an instalment.');
					redirect('Accounting/Payment');
					return;
				}
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

			// The O.R. number is read-only in the UI and always server-generated
			// from the payment date. Retry a few times in case two cashiers hit
			// the same date/sequence in the same instant, instead of bouncing
			// the whole form back for something the cashier never typed.
			$orNumber = '';
			$insertOk = false;
			$paymentData = [];

			for ($attempt = 0; $attempt < 5; $attempt++) {
				$orNumber = $this->generateNextOrNumber($pDateInput);
				$paymentData = [
					'ID'               => $this->nextTableId('paymentsaccounts', 'ID'),
					'StudentNumber'    => $studentNumber,
					'Course'           => $course,
					'PDate'            => $pDateInput,
					'ORNumber'         => $orNumber,
					'Amount'           => $amount,
					'FeeFullAmount'    => $feeFullAmount,
					'description'      => $description,
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

				$this->db->trans_begin();
				$insertOk = $this->db->insert('paymentsaccounts', $paymentData);
				$insertError = $this->db->error();

				if ($insertOk && $sem !== '' && $sy !== '') {
					$this->recomputeStudeAccount($studentNumber, $sem, $sy);
				}

				if ($insertOk && $this->db->trans_status() !== false) {
					$this->db->trans_commit();
					break;
				}

				$this->db->trans_rollback();
				$insertOk = false;

				if (!$this->isDuplicateDbError($insertError)) {
					break;
				}
			}

			if (!$insertOk) {
				$this->session->set_flashdata('payment_form_old', $this->paymentFormStateFromPost());
				$this->session->set_flashdata('danger', 'Unable to save payment. Please try again.');
				redirect('Accounting/Payment');
				return;
			}

				$receiptSettings = $this->getReceiptSettings();
				$receiptPayment = $this->buildReceiptEmailPayment($paymentData, $student);
				$emailResult = $this->sendReceiptEmailForPayment($receiptPayment, $receiptSettings);

				$successMessage = 'Payment saved successfully. O.R. #' . $orNumber . '.';
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

		$data = [
			'default_payment_date' => $today,
			'next_or_number'       => $this->peekNextOrNumber($today),
			'students'             => $this->getStudentsForPayment($sem, $sy),
			'recent_payments'      => $this->getRecentPayments($dateFilter === 'all' ? null : $dateFilter),
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
		$this->ensureAccess();

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

		// Sem/SY are intentionally not updated: re-tagging a payment to another
		// term would rewrite history. Delete and re-enter it instead.
		$this->db->trans_begin();
		$this->db->where('ID', $id)->update('paymentsaccounts', [
			'StudentNumber' => $studentNumber,
			'PDate'         => $pDateInput,
			'Amount'        => $amount,
			'description'   => $description,
		]);

		$this->recomputeStudeAccount($oldStudentNumber, $sem, $sy);
		if ($studentNumber !== $oldStudentNumber) {
			$this->recomputeStudeAccount($studentNumber, $sem, $sy);
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$this->session->set_flashdata('danger', 'Unable to update payment. Please try again.');
			redirect('Accounting/Payment');
			return;
		}

		$this->db->trans_commit();

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
			'settings'   => $settings,
			'auto_print' => $this->input->get('print', true) === '1'
		];

		$this->load->view('accounting_receipt', $data);
	}

	public function emailReceipt()
	{
		$this->ensureAccess();

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

	public function course_setUp()
	{
		$this->ensureAccess();
		[$sem, $sy] = $this->currentSemSy();

		if (strtoupper((string)$this->input->method()) === 'POST') {
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

				$this->db->insert('fees', $data);
				$this->session->set_flashdata('success', 'Fee added successfully.');
				redirect('Accounting/course_setUp');
				return;
			}

			if ($action === 'delete') {
				$feeId = (int)$this->input->post('feesid', true);
				if ($feeId > 0) {
					$this->db->where('feesid', $feeId)->delete('fees');
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
				$existing = $this->db->select('Description, Amount')
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
	public function deletePayment()
	{
		$this->ensureAccess();

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

		// Fetch payment first (needed for recompute, and for the audit log)
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

		// Safety guards (optional, but recommended)
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

		$studentNumber = trim((string)$payment->StudentNumber);
		$sem = trim((string)$payment->Sem);
		$sy  = trim((string)$payment->SY);

		$this->db->trans_start();

		// Delete row
		$this->db->where('ID', (int)$id)->delete('paymentsaccounts');

		// Recompute totals (ONLY for same sem/sy)
		$this->recomputeStudeAccount($studentNumber, $sem, $sy);

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->session->set_flashdata('danger', 'Unable to delete payment. Please try again.');
			redirect('Accounting/Payment');
			return;
		}

		$this->logPaymentAudit('delete', $payment);

		$this->session->set_flashdata('success', 'Payment deleted successfully.');
		redirect('Accounting/Payment');
	}

	// Visible to both Cashier (their own activity) and Admin (everyone's) —
	// ensureAccess() already allows both roles into this controller.
	public function paymentAuditLog()
	{
		$this->ensureAccess();

		$this->db->select("l.*,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName", false);
		$this->db->from('payment_audit_log l');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = l.student_number', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = l.student_number', 'left');
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
				];
			}

			$this->renderReportPrint(
				'Payment Activity Log',
				[
					['label' => 'Printed', 'value' => date('F d, Y \a\t g:i A')],
					['label' => 'Total Entries', 'value' => number_format(count($rows))],
				],
				['Date & Time', 'Action', 'O.R.', 'Student', 'Description', 'Amount', 'Changed By'],
				$printRows,
				['left', 'left', 'left', 'left', 'left', 'right', 'left'],
				null,
				base_url('Accounting/paymentAuditLog'),
				'landscape',
				'No edits or deletions recorded yet.'
			);
			return;
		}

		$this->load->view('accounting_payment_log', ['rows' => $rows]);
	}

	// Cashier ledger: collections vs. expenses with a running balance, so
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
}

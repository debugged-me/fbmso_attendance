<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Accounting extends CI_Controller
{
	private $allowedLevels = ['Admin', 'Accounting'];

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper(['url', 'form']);
		$this->load->library(['session', 'form_validation']);
		$this->load->model('SettingsModel');

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
		redirect('Accounting/Payment');
	}

	private function currentSemSy()
	{
		$sem = trim((string)$this->session->userdata('semester'));
		$sy  = trim((string)$this->session->userdata('sy'));

		if ($sem === '' || $sy === '') {
			$row = $this->db->select('active_sem, active_sy')
				->from('o_srms_settings')
				->limit(1)
				->get()
				->row();

			if ($row) {
				if ($sem === '') {
					$sem = trim((string)$row->active_sem);
				}
				if ($sy === '') {
					$sy = trim((string)$row->active_sy);
				}
			}
		}

		return [$sem, $sy];
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

	private function ensureFeesTable()
	{
		if ($this->tableExists('fees')) {
			return;
		}

		$sql = "CREATE TABLE `fees` (
			`feesid` int(10) unsigned NOT NULL,
			`Description` varchar(100) NOT NULL DEFAULT '',
			`Amount` double NOT NULL DEFAULT 0,
			`Course` varchar(200) NOT NULL DEFAULT '',
			`Major` varchar(65) DEFAULT NULL,
			`YearLevel` varchar(45) NOT NULL DEFAULT '',
			`Semester` varchar(45) NOT NULL DEFAULT '',
			`feesType` varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY (`feesid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

		$this->db->query($sql);
	}

	private function nextTableId($table, $idColumn)
	{
		$row = $this->db->select_max($idColumn, 'max_id')->get($table)->row();
		return (int)($row->max_id ?? 0) + 1;
	}

	private function orNumberExists($orNumber)
	{
		return $this->db->where('ORNumber', $orNumber)->count_all_results('paymentsaccounts') > 0;
	}

	private function generateNextOrNumber()
	{
		$rowNumeric = $this->db->select('ORNumber')
			->from('paymentsaccounts')
			->where("ORNumber REGEXP '^[0-9]+$'", null, false)
			->order_by('CAST(ORNumber AS UNSIGNED)', 'DESC', false)
			->limit(1)
			->get()
			->row();

		if ($rowNumeric && $rowNumeric->ORNumber !== '') {
			return str_pad((string)(((int)$rowNumeric->ORNumber) + 1), 5, '0', STR_PAD_LEFT);
		}

		$rowAny = $this->db->select('ORNumber')
			->from('paymentsaccounts')
			->order_by('ID', 'DESC')
			->limit(1)
			->get()
			->row();

		if ($rowAny && preg_match('/(\d+)(?!.*\d)/', (string)$rowAny->ORNumber, $m)) {
			return str_pad((string)(((int)$m[1]) + 1), 5, '0', STR_PAD_LEFT);
		}

		return '00001';
	}



	public function expenses()
	{
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
		$this->load->model('SettingsModel');

		$data['data'] = $this->SettingsModel->get_expenses();
		$data['categories'] = $this->SettingsModel->get_categories(); // Fetch categories

		// Convert categories array to a simpler format if needed
		$data['categories'] = array_column($data['categories'], 'Category');

		$this->load->view('expensesReport', $data);
	}


	public function expenseSGenerate()
	{
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

	private function reserveOrNumber($candidate = '')
	{
		$orNumber = trim((string)$candidate);
		if ($orNumber === '') {
			$orNumber = $this->generateNextOrNumber();
		}

		if (!$this->orNumberExists($orNumber)) {
			return $orNumber;
		}

		if (preg_match('/^\d+$/', $orNumber)) {
			$next = (int)$orNumber;
			do {
				$next++;
				$orNumber = str_pad((string)$next, 5, '0', STR_PAD_LEFT);
			} while ($this->orNumberExists($orNumber));
			return $orNumber;
		}

		return '';
	}

	private function getStudentsForPayment($sem, $sy)
	{
		$this->db->select("
			ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
		$this->db->from('semesterstude ss');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = ss.StudentNumber', 'left');
		if ($sem !== '') {
			$this->db->where('ss.Semester', $sem);
		}
		if ($sy !== '') {
			$this->db->where('ss.SY', $sy);
		}
		$this->db->group_by('ss.StudentNumber');
		$this->db->order_by('COALESCE(NULLIF(sp.LastName,\'\'), su.LastName, \'\')', 'ASC', false);
		$this->db->order_by('COALESCE(NULLIF(sp.FirstName,\'\'), su.FirstName, \'\')', 'ASC', false);
		$rows = $this->db->get()->result();

		if (!empty($rows)) {
			return $rows;
		}

		$this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS Course,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS Major,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS YearLevel,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
		$this->db->from('studentsignup su');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = su.StudentNumber', 'left');
		$this->db->order_by('COALESCE(NULLIF(sp.LastName,\'\'), su.LastName, \'\')', 'ASC', false);
		$this->db->order_by('COALESCE(NULLIF(sp.FirstName,\'\'), su.FirstName, \'\')', 'ASC', false);
		return $this->db->get()->result();
	}

	private function getStudentContext($studentNumber, $sem, $sy)
	{
		$this->db->select("
			ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
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

		if ($row) {
			return $row;
		}

		$this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS Course,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS Major,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS YearLevel,
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

	private function getRecentPayments($sem, $sy, $limit = 80)
	{
		$this->db->select("p.ID, p.PDate, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
		$this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('p.CollectionSource', "Student's Account");
		$this->db->where('p.ORStatus', 'Valid');
		if ($sem !== '') {
			$this->db->where('p.Sem', $sem);
		}
		if ($sy !== '') {
			$this->db->where('p.SY', $sy);
		}
		$this->db->order_by('p.PDate', 'DESC');
		$this->db->order_by('p.ID', 'DESC');
		$this->db->limit((int)$limit);
		return $this->db->get()->result();
	}

	private function getPaymentById($id)
	{
		$this->db->select("p.*, 
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

	private function collectionRows($from, $to)
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
		$this->db->order_by('p.PDate', 'DESC');
		$this->db->order_by('p.ID', 'DESC');
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
		$this->ensureFeesTable();
		[$sem, $sy] = $this->currentSemSy();

		if (strtoupper((string)$this->input->method()) === 'POST') {
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
			$paymentType   = trim((string)$this->input->post('PaymentType', true));
			$checkNumber   = trim((string)$this->input->post('CheckNumber', true));
			$bank          = trim((string)$this->input->post('Bank', true));
			$refNo         = trim((string)$this->input->post('refNo', true));
			$postedSem     = trim((string)$this->input->post('Sem', true));
			$postedSy      = trim((string)$this->input->post('SY', true));

			if ($sem === '') {
				$sem = $postedSem;
			}
			if ($sy === '') {
				$sy = $postedSy;
			}

			if (!$this->isValidDate($pDateInput)) {
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

			$orCandidate = trim((string)$this->input->post('ORNumber', true));
			$orNumber = $this->reserveOrNumber($orCandidate);
			if ($orNumber === '') {
				$this->session->set_flashdata('danger', 'O.R. number already exists. Please retry.');
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
			$paymentData = [
				'ID'               => $this->nextTableId('paymentsaccounts', 'ID'),
				'StudentNumber'    => $studentNumber,
				'Course'           => $course,
				'PDate'            => $pDateInput,
				'ORNumber'         => $orNumber,
				'Amount'           => $amount,
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

			$this->db->trans_start();
			$this->db->insert('paymentsaccounts', $paymentData);

			if ($sem !== '' && $sy !== '') {
				$amountSql = $this->db->escape($amount);
				$this->db->set('TotalPayments', "COALESCE(TotalPayments,0) + {$amountSql}", false);
				$this->db->set('CurrentBalance', "GREATEST(COALESCE(AcctTotal,0) - COALESCE(Discount,0) - (COALESCE(TotalPayments,0) + {$amountSql}), 0)", false);
				$this->db->where('StudentNumber', $studentNumber)
					->where('Sem', $sem)
					->where('SY', $sy)
					->update('studeaccount');
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === false) {
				$this->session->set_flashdata('danger', 'Unable to save payment. Please try again.');
				redirect('Accounting/Payment');
				return;
			}

			$this->session->set_flashdata('success', 'Payment saved successfully. O.R. #' . $orNumber);
			redirect('Accounting/Payment');
			return;
		}

		$now = new DateTime('now', new DateTimeZone('Asia/Manila'));

		$settings = $this->db->select('SchoolName, SchoolAddress, telNo, cashier, cashierPosition, letterhead_web')
			->from('o_srms_settings')
			->limit(1)
			->get()
			->row();

		$data = [
			'semester'             => $sem,
			'sy'                   => $sy,
			'default_payment_date' => $now->format('Y-m-d'),
			'next_or_number'       => $this->generateNextOrNumber(),
			'students'             => $this->getStudentsForPayment($sem, $sy),
			'recent_payments'      => $this->getRecentPayments($sem, $sy),
			'fee_templates'        => $this->getFeeTemplates(),
			'settings'             => $settings
		];

		$this->load->view('accounting_payment', $data);
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

		$settings = $this->db->select('SchoolName, SchoolAddress, telNo, cashier, cashierPosition, letterhead_web')
			->from('o_srms_settings')
			->limit(1)
			->get()
			->row();

		$data = [
			'payment'    => $payment,
			'settings'   => $settings,
			'auto_print' => $this->input->get('print', true) === '1'
		];

		$this->load->view('accounting_receipt', $data);
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
		$this->ensureFeesTable();

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

	public function course_setUp()
	{
		$this->ensureAccess();
		$this->ensureFeesTable();
		[$sem] = $this->currentSemSy();

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
					'feesType'    => trim((string)$this->input->post('feesType', true)),
					'Description' => trim((string)$this->input->post('Description', true)),
					'Amount'      => (float)$this->input->post('Amount', true),
				];

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

		$data = [
			'semester'        => $sem,
			'courses'         => $this->courseList(),
			'fees'            => $fees,
		];

		$this->load->view('accounting_fee_setup', $data);
	}

	private function renderCollection($from, $to, $title)
	{
		$rows = $this->collectionRows($from, $to);
		$total = 0.0;
		foreach ($rows as $row) {
			$total += (float)$row->Amount;
		}

		$data = [
			'report_title' => $title,
			'from'         => $from,
			'to'           => $to,
			'rows'         => $rows,
			'total_amount' => $total,
			'total_count'  => count($rows),
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

		$this->renderCollection($from, $to, 'Collection Report (Date Range)');
	}

	public function collectionDateRange()
	{
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

		// Fetch payment first (needed for recompute)
		$payment = $this->db->select('ID, StudentNumber, Amount, Sem, SY, ORStatus, CollectionSource')
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
		if ($studentNumber !== '' && $sem !== '' && $sy !== '') {
			// total payments = sum of valid Student's Account payments in same sem/sy
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

			// Update studeaccount using your same balance formula style
			$newTotalSql = $this->db->escape($newTotal);

			$this->db->set('TotalPayments', $newTotalSql, false);
			$this->db->set(
				'CurrentBalance',
				"GREATEST(COALESCE(AcctTotal,0) - COALESCE(Discount,0) - {$newTotalSql}, 0)",
				false
			);
			$this->db->where('StudentNumber', $studentNumber)
				->where('Sem', $sem)
				->where('SY', $sy)
				->update('studeaccount');
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->session->set_flashdata('danger', 'Unable to delete payment. Please try again.');
			redirect('Accounting/Payment');
			return;
		}

		$this->session->set_flashdata('success', 'Payment deleted successfully.');
		redirect('Accounting/Payment');
	}
}

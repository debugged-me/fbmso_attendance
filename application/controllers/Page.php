<?php
class Page extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');
		$this->load->helper('url', 'form');
		$this->load->library('form_validation');
		$this->load->model('StudentModel');
		$this->load->model('SettingsModel');
		$this->load->model('PersonnelModel');
		$this->load->model('InstructorModel');
		$this->load->model('LibraryModel');
		$this->load->model('Ren_model');
		$this->load->library('user_agent');
		$this->load->model('OnlineSettingsModel');
		$this->load->vars(['online_settings' => $this->OnlineSettingsModel->get_setting()]);
		$this->load->model('StudentModel');
		$this->load->model('CourseSectionModel');
		$this->load->model('AuditLogModel');
		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('login');
		}
	}

	function profileListEncoder()
	{
		$username = $this->session->userdata('username');
		$result['data'] = $this->StudentModel->getProfileEncoder($username);
		$this->load->view('profile_list_encoder', $result);
	}

	public function addRequirement()
	{
		$name = $this->input->post('name');
		$description = $this->input->post('description');

		$data = [
			'name' => $name,
			'description' => $description,
			'is_active' => '1'
		];

		$this->db->insert('requirements', $data);

		$this->session->set_flashdata('success', 'Requirement added successfully!');
		redirect('Student/req_list'); // or your current page
	}

	public function updateRequirement()
	{
		$id = $this->input->post('id');
		$data = [
			'name' => $this->input->post('name'),
			'description' => $this->input->post('description')
		];

		$this->db->where('id', $id);
		$this->db->update('requirements', $data);

		$this->session->set_flashdata('success', 'Requirement updated successfully.');
		redirect('Student/req_list'); // Replace with your actual view page
	}

	public function deleteRequirement($id)
	{
		// Optional: Check if the record exists
		$requirement = $this->db->get_where('requirements', ['id' => $id])->row();

		if ($requirement) {
			$this->db->delete('requirements', ['id' => $id]);
			$this->session->set_flashdata('success', 'Requirement deleted successfully.');
		} else {
			$this->session->set_flashdata('error', 'Requirement not found.');
		}

		redirect('Student/req_list'); // Replace 'yourViewPage' with the actual method or route
	}

	function index()
	{
		//Allowing access to admin only
		if ($this->session->userdata('level') === 'Admin') {

			$this->load->view('dashboard_admin');
		} else {
			echo "Access Denied";
		}
	}

	// Access for School IT
	function IT()
	{
		//Allowing access to school IT only
		if ($this->session->userdata('level') === 'IT') {
			//date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$this->load->view('dashboard_school_it', $result);
		} else {
			echo "Access Denied";
		}
	}

	public function admin()
	{
		if ($this->session->userdata('level') === 'Admin') {
			$sy  = (string)$this->session->userdata('sy');
			$sem = (string)$this->session->userdata('semester');

			// define optional filters safely (null if absent)
			$course = $this->input->get('course', true);
			$major  = $this->input->get('major',  true);
			$course = ($course === '' ? null : $course);
			// keep $major as:
			//   - null  -> ALL majors
			//   - ''    -> only blank/NULL majors (if you ever want that)
			//   - 'xyz' -> exact major filter

			$this->load->model('AnnouncementModel');
			$data['announcements'] = $this->AnnouncementModel->getAnnouncements();

			$this->load->model('Message_model');
			$result['unreadMessages'] = $this->Message_model->getUnreadMessages($this->session->userdata('IDNumber'));
			$result['users']          = $this->Message_model->get_all_users($this->session->userdata('IDNumber'));

			$result['data']  = $this->StudentModel->enrolledFirst($sy, $sem);
			$result['data1'] = $this->StudentModel->enrolledSecond($sy, $sem);
			$result['data2'] = $this->StudentModel->enrolledThird($sy, $sem);
			$result['data3'] = $this->StudentModel->enrolledFourth($sy, $sem);
			$result['data4'] = $this->StudentModel->forPaymentVerCount($sy, $sem);
			$result['data5'] = $this->StudentModel->teachersCount();
			$result['data6'] = $this->StudentModel->forValidationCounts($sem, $sy);
			$result['data7'] = $this->StudentModel->totalSignups();

			// summaries
			$result['data8']           = $this->StudentModel->CourseCount($sem, $sy);        // By Course
			$result['majorCounts']     = $this->StudentModel->MajorCount($sem, $sy);         // By Major
			$result['yearLevelCounts'] = $this->StudentModel->YearLevelCount($sem, $sy);     // By Year Level
			// FIXED: correct method name + correct argument order
			$result['sectionCounts']   = $this->StudentModel->SectionCounts($sy, $sem, $course, $major); // By Section

			$result['data9']  = $this->StudentModel->SexCount($sem, $sy);
			$result['data10'] = $this->StudentModel->dailyEnrollStat();
			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$result['data19'] = $this->StudentModel->studeRequestList();
			$result['data21'] = $this->StudentModel->newestSignup();

			$result = array_merge($result, $data);
			$this->load->view('dashboard_admin', $result);
		} else {
			echo "Access Denied";
		}
	}



	function school_admin()
	{
		//Allowing access to Admin only
		if ($this->session->userdata('level') === 'School Admin') {

			$sy  = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');
			$Semester = $this->session->userdata('semester');
			$SY = $this->session->userdata('sy');

			$this->load->model('Message_model');
			$this->load->model('AnnouncementModel');

			$result['announcements'] = $this->AnnouncementModel->getAnnouncements();

			$result['unreadMessages'] = $this->Message_model->getUnreadMessages($this->session->userdata('IDNumber'));
			$result['users']          = $this->Message_model->get_all_users($this->session->userdata('IDNumber'));

			$result['data']  = $this->StudentModel->enrolledFirst($sy, $sem);
			$result['data1'] = $this->StudentModel->enrolledSecond($sy, $sem);
			$result['data2'] = $this->StudentModel->enrolledThird($sy, $sem);
			$result['data3'] = $this->StudentModel->enrolledFourth($sy, $sem);
			$result['data4'] = $this->StudentModel->forPaymentVerCount($sy, $sem);
			$result['data5'] = $this->StudentModel->teachersCount();
			$result['data6'] = $this->StudentModel->forValidationCounts($Semester, $SY);
			$result['data7'] = $this->StudentModel->totalProfile();
			$result['data8'] = $this->StudentModel->CourseCount($sem, $sy);
			$result['data9'] = $this->StudentModel->SexCount($sem, $sy);
			$result['data10'] = $this->StudentModel->dailyEnrollStat();

			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$result['data19'] = $this->StudentModel->studeRequestList();
			$result['data21'] = $this->StudentModel->newestSignup();

			$this->load->view('dashboard_school_admin', $result);
		} else {
			echo "Access Denied";
		}
	}



	function guidance()
	{
		//Allowing access to Admin only
		if ($this->session->userdata('level') === 'Guidance') {
			$result['data1'] = $this->StudentModel->incidentsCounts();
			$result['data2'] = $this->StudentModel->counsellingCounts();
			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$this->load->view('dashboard_guidance', $result);
		} else {
			echo "Access Denied";
		}
	}

	function medical()
	{
		//Allowing access to Admin only
		if ($this->session->userdata('level') === 'School Nurse') {
			$result['data1'] = $this->StudentModel->medInfoCounts();
			$result['data2'] = $this->StudentModel->medRecordsCounts();
			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$this->load->view('dashboard_nurse', $result);
		} else {
			echo "Access Denied";
		}
	}

	function deanList()
	{
		$course = $this->input->get('course');
		$yearlevel = $this->input->get('yl');
		$sy = $this->session->userdata('sy');
		$semester = $this->session->userdata('semester');
		$yearLevelStat = 'Regular';

		$result['course'] = $this->StudentModel->getCourse();

		$result['data'] = $this->StudentModel->deanList($course, $yearlevel, $sy, $semester, $yearLevelStat);

		$this->load->view('dean_list', $result);
	}

	function scholarship()
	{
		$result['data'] = $this->StudentModel->scholarshipApplicants();
		$result['data1'] = $this->StudentModel->reservationCounts();
		$result['data3'] = $this->StudentModel->enrolledCounts();
		$this->load->view('dashboard_scholarship_reservations', $result);
	}

	function acceptReservation()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("update reservation set appStatus='Confirmed' where appNo='" . $id . "'");
		redirect('Page/scholarship');
	}

	function deleteReservation()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("update reservation set appStatus='Deleted' where appNo='" . $id . "'");
		redirect('Page/scholarship');
	}

	function hr()
	{
		$user_level = $this->session->userdata('level');

		if ($user_level === 'HR Admin' || $user_level === 'Admin') {

			// Load SY and Semester only if needed later
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');

			$result = [
				'data'  => $this->PersonnelModel->displaypersonnel(),
				'data1' => $this->PersonnelModel->personnelCounts(),
				'data2' => $this->PersonnelModel->departmentcounts(),
				'data5' => $this->StudentModel->teachersCount()
			];

			$this->load->view('dashboard_hr', $result);
		} else {
			echo "Access Denied";
		}
	}


	function superAdmin()
	{
		if ($this->session->userdata('level') === 'Super Admin') {
			$result['data'] = $this->SettingsModel->getSchoolInformation();
			$result['online_settings'] = $this->OnlineSettingsModel->get_setting(); // 👈 pass to view
			$this->load->view('dashboard_SuperAdmin', $result);                     // your view includes the sidebar
		} else {
			echo "Access Denied";
		}
	}



	function school_info()
	{
		if ($this->session->userdata('level') === 'Super Admin') {
			$result['data'] = $this->SettingsModel->getSchoolInformation();
			$result['online_settings'] = $this->OnlineSettingsModel->get_setting(); // 👈 pass as well
			$this->load->view('schoolInfo', $result);
		} else {
			echo "Access Denied";
		}
	}
	public function updateSuperAdmin()
	{
		$settingsID = $this->input->get('settingsID');
		$result['data'] = $this->SettingsModel->getSuperAdminbyId($settingsID);
		$this->load->view('update_Superadmin', $result);

		if ($this->input->post('update')) {
			// File upload for schoolLogo
			$schoolLogo = $this->uploadImage('schoolLogo');

			// File upload for letterHead
			$letterHead = $this->uploadImage('letterHead');

			// Collecting form data
			$data = array(
				'SchoolName' => $this->input->post('SchoolName'),
				'SchoolAddress' => $this->input->post('SchoolAddress'),
				'SchoolHead' => $this->input->post('SchoolHead'),
				'sHeadPosition' => $this->input->post('sHeadPosition'),
				'Registrar' => $this->input->post('Registrar'),
				'registrarPosition' => $this->input->post('registrarPosition'),
				'cashier' => $this->input->post('cashier'),
				'cashierPosition' => $this->input->post('cashierPosition'),
				'clerk' => $this->input->post('clerk'),
				'clerkPosition' => $this->input->post('clerkPosition'),
				'administrative' => $this->input->post('administrative'),
				'administrativePosition' => $this->input->post('administrativePosition'),
				'admissionOfficer' => $this->input->post('admissionOfficer'),
				'studentNoCode' => $this->input->post('studentNoCode'),
				'scholarshipCoordinator' => $this->input->post('scholarshipCoordinator'),
				'accountant' => $this->input->post('accountant'),
				'schoolLogo' => $schoolLogo, // Save filename
				'letterHead' => $letterHead, // Save filename
				'PropertyCustodian' => $this->input->post('PropertyCustodian'),
				'Division' => $this->input->post('Division'),
				'loginFormImage' => $this->input->post('loginFormImage'),
				'slogan' => $this->input->post('slogan'),
				'telNo' => $this->input->post('telNo'),
				'vp' => $this->input->post('vp'),
				'vpPosition' => $this->input->post('vpPosition'),
				'GuidanceCounselor' => $this->input->post('GuidanceCounselor'),
				'GuidancePosition' => $this->input->post('GuidancePosition'),
				'AccountantPosition' => $this->input->post('AccountantPosition'),
				'deanAssist' => $this->input->post('deanAssist'),
				'deanAssistPosition' => $this->input->post('deanAssistPosition'),
				'dbname' => $this->input->post('dbname'),


				'dragonpay_merchantid' => $this->input->post('dragonpay_merchantid'),
				'dragonpay_password' => $this->input->post('dragonpay_password'),
				'dragonpay_url' => $this->input->post('dragonpay_url'),

				'moodle_base' => $this->input->post('moodle_base'),
				'token' => $this->input->post('token'),


			);

			$this->SettingsModel->updateSuperAdmin($settingsID, $data);
			$this->session->set_flashdata('msg', 'Record updated successfully');
			redirect('Page/superAdmin');
		}
	}

	/**
	 * Uploads an image and returns the filename.
	 */
	private function uploadImage($fieldName)
	{
		$config['upload_path'] = './assets/images/';
		$config['allowed_types'] = 'jpg|jpeg|png|gif';
		$config['max_size'] = 2048; // 2MB
		$config['encrypt_name'] = TRUE; // Encrypt filename for uniqueness

		$this->load->library('upload', $config);

		if ($this->upload->do_upload($fieldName)) {
			$uploadData = $this->upload->data();
			return $uploadData['file_name']; // Return the filename
		} else {
			// If no file was uploaded, keep the existing value
			return $this->input->post($fieldName . '_existing');
		}
	}

	function accounting()
	{
		if ($this->session->userdata('level') === 'Accounting') {
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');

			$result['data4'] = $this->StudentModel->forPaymentVerCount($sy, $sem);
			// $data['pendingCount'] = $this->StudentModel->forPaymentVerCount($sy, $sem);
			$result['data7'] = $this->StudentModel->totalStudeAccountProfile($sy, $sem);
			$result['data11'] = $this->StudentModel->paymentSummary($sem, $sy);
			$result['data12'] = $this->StudentModel->collectionToday();
			$result['data13'] = $this->StudentModel->collectionMonth();
			$result['data14'] = $this->StudentModel->YearlyCollections();

			$this->load->view('dashboard_accounting', $result);
		} else {
			echo "Access Denied";
		}
	}
	function registrar()
	{
		$level = $this->session->userdata('level');
		$allowed_levels = ['Registrar', 'Head Registrar', 'Admin'];

		if (in_array($level, $allowed_levels)) {
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');


			$this->load->model('AnnouncementModel');
			$userRole = 'Registrar';
			$data['announcements'] = $this->AnnouncementModel->getActiveAnnouncementsFor($userRole);

			$this->load->model('Message_model');
			$this->load->model('Message_model');
			$result['unreadMessages'] = $this->Message_model->getUnreadMessages($this->session->userdata('IDNumber'));
			$result['users'] = $this->Message_model->get_all_users($this->session->userdata('IDNumber'));
			$result['data'] = $this->StudentModel->enrolledFirst($sy, $sem);
			$result['data1'] = $this->StudentModel->enrolledSecond($sy, $sem);
			$result['data2'] = $this->StudentModel->enrolledThird($sy, $sem);
			$result['data3'] = $this->StudentModel->enrolledFourth($sy, $sem);
			$result['data5'] = $this->StudentModel->teachersCount();
			$result['data6'] = $this->StudentModel->forValidationCounts($sem, $sy);
			$result['data7'] = $this->StudentModel->totalProfile();
			$result['data8'] = $this->StudentModel->CourseCount($sem, $sy);
			$result['data9'] = $this->StudentModel->SexCount($sem, $sy);
			$result['data10'] = $this->StudentModel->dailyEnrollStat();

			if ($level === 'Admin') {
				$result['data15'] = $this->StudentModel->religionCount($sem, $sy);
				$result['data16'] = $this->StudentModel->ethnicityCount($sem, $sy);
				$result['data17'] = $this->StudentModel->cityCount($sem, $sy);
			}
			$result['announcements'] = $data['announcements'];

			$this->load->view('dashboard_registrar', $result);
		} else {
			echo "Access Denied";
		}
	}

	public function instructor()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Access Denied', 403);
			return;
		}

		// ---- Session context ----
		$id  = (string)$this->session->userdata('username');   // IDNumber
		$sy  = (string)$this->session->userdata('sy');
		$sem = (string)$this->session->userdata('semester');

		// ---- Load models ----
		$this->load->model('InstructorModel');
		$this->load->model('StudentModel');
		$this->load->model('AnnouncementModel');
		$this->load->model('Message_model');

		$result = [];

		// ---- Announcements (for instructors) ----
		$result['announcements'] = $this->AnnouncementModel->getActiveAnnouncementsFor('Instructors');

		// ---- Messaging ----
		$result['unreadMessages'] = $this->Message_model->getUnreadMessages($this->session->userdata('IDNumber'));
		$result['users']          = $this->Message_model->get_all_users($this->session->userdata('IDNumber'));

		// ---- Faculty load / counts ----
		$result['subjectCounts'] = $this->StudentModel->facultyLoadCounts($id, $sem, $sy);
		$result['data2']         = $this->InstructorModel->facultyLoad($id, $sy, $sem); // your per-subject load list

		// ---- Grades settings ----
		$result['gs'] = $this->Common->one_cond_row('grades_settings', 'id', 1);

		// ---- Determine instructor's course & major ----
		$courseDesc = '';
		$major      = '';

		$this->db->select('CourseDescription, Major');
		$this->db->from('course_table');
		$this->db->where('IDNumber', $id);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$courseDesc = (string)$query->row()->CourseDescription;
			$major      = (string)$query->row()->Major;
		}

		$result['courseDescription'] = $courseDesc;
		$result['major']             = $major;

		// ---- Fetch students filtered by Course, SY, Sem (+ Major if any) ----
		$students = [];
		if ($courseDesc !== '' && $sy !== '' && $sem !== '') {
			$this->db->select('
            s.semstudentid,
            s.StudentNumber,
            sp.FirstName, sp.MiddleName, sp.LastName,
            s.YearLevel, s.Section,
            s.Course, s.Major,
            s.Status, s.StudeStatus, s.Scholarship
        ');
			$this->db->from('semesterstude s');
			$this->db->join('studeprofile sp', 'sp.StudentNumber = s.StudentNumber', 'left');
			$this->db->where('s.Course', $courseDesc);
			$this->db->where('s.SY', $sy);
			$this->db->where('s.Semester', $sem);

			if (!empty($major)) {
				$this->db->where('s.Major', $major);
			}

			// Optional: if you only want enrolled
			// $this->db->where('s.StudeStatus', 'Enrolled');

			$students = $this->db->get()->result();
		}

		$result['students']     = $students; // in case needed elsewhere
		$result['studentCount'] = is_array($students) ? count($students) : 0;

		// ---- Build summaries (no student rows in view) ----
		$studentsByYear = [];   // [YearLevel] => array of students (for counting/linking)
		$sectionsByYear = [];   // [YearLevel][Section] => count

		if (!empty($students)) {
			foreach ($students as $s) {
				$yl  = (string)($s->YearLevel ?? 'UNSPECIFIED');
				$sec = trim((string)($s->Section ?? ''));
				if ($sec === '') {
					$sec = 'UNASSIGNED';
				}

				// Year totals
				if (!isset($studentsByYear[$yl])) $studentsByYear[$yl] = [];
				$studentsByYear[$yl][] = $s;

				// Section counts per year
				if (!isset($sectionsByYear[$yl])) $sectionsByYear[$yl] = [];
				if (!isset($sectionsByYear[$yl][$sec])) $sectionsByYear[$yl][$sec] = 0;
				$sectionsByYear[$yl][$sec]++;
			}
		}

		// Sort year levels numerically if possible; else alphabetically
		uksort($studentsByYear, function ($a, $b) {
			if (is_numeric($a) && is_numeric($b)) return (int)$a <=> (int)$b;
			return strcasecmp($a, $b);
		});

		// Keep sections ordered per year (natural case-insensitive)
		foreach ($sectionsByYear as $yl => $secCounts) {
			ksort($secCounts, SORT_NATURAL | SORT_FLAG_CASE);
			$sectionsByYear[$yl] = $secCounts;
		}

		$result['studentsByYear'] = $studentsByYear;
		$result['sectionsByYear'] = $sectionsByYear;

		// ---- Context for headers/badges ----
		$result['sy']  = $sy;
		$result['sem'] = $sem;

		// ---- Render ----
		$this->load->view('dashboard_instructor', $result);
	}




	public function get_enrollment_row()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Forbidden', 403);
			return;
		}

		$sid = $this->input->get('sid');
		if (!$sid) {
			show_error('Missing semstudentid', 400);
			return;
		}

		// Current row
		$row = $this->db->from('semesterstude')->where('semstudentid', $sid)->get()->row_array();
		if (!$row) {
			show_error('Not found', 404);
			return;
		}

		// --- Courses list (as before) ---
		$courses = $this->db->select('CourseDescription AS Course')
			->from('course_table')
			->group_by('CourseDescription')
			->order_by('CourseDescription', 'ASC')
			->get()->result_array();

		// --- Year levels (fixed list) ---
		$yearlvls = [
			['YearLevel' => '1st'],
			['YearLevel' => '2nd'],
			['YearLevel' => '3rd'],
			['YearLevel' => '4th'],
		];

		// --- Majors filtered by the current Course ---
		// Use DISTINCT Majors from semesterstude for reliability; ignore empty majors.
		$this->db->select('DISTINCT Major', false)
			->from('semesterstude')
			->where('Course', $row['Course'])
			->where("Major <> ''", null, false)
			->order_by('Major', 'ASC');
		$majors_rows = $this->db->get()->result_array();
		// Convert to simple array of strings
		$majors = array_values(array_map(function ($r) {
			return $r['Major'];
		}, $majors_rows));

		// Ensure current Major is present (even if empty or not in list)
		if (!empty($row['Major']) && !in_array($row['Major'], $majors, true)) {
			array_unshift($majors, $row['Major']);
		}

		// --- Sections filtered by Course, Major (optional), YearLevel, SY & Semester ---
		$this->db->select('DISTINCT Section', false)
			->from('semesterstude')
			->where('Course', $row['Course'])
			->where('SY', $row['SY'])
			->where('Semester', $row['Semester'])
			->where('YearLevel', $row['YearLevel'])
			->where('Status', 'Enrolled');

		if (!empty($row['Major'])) {
			$this->db->where('Major', $row['Major']);
		} else {
			// When no Major for the student, show sections with empty/null Major
			$this->db->group_start()
				->where('Major', '')
				->or_where('Major IS NULL', null, false)
				->group_end();
		}

		$this->db->order_by('Section', 'ASC');
		$sections_rows = $this->db->get()->result_array();
		$sections = array_values(array_map(function ($r) {
			return $r['Section'];
		}, $sections_rows));

		$resp = [
			'row'       => $row,
			'courses'   => $courses,
			'yearlvls'  => $yearlvls,
			'majors'    => $majors,    // <— NEW
			'sections'  => $sections,  // <— already filtered list
		];

		$this->output->set_content_type('application/json')->set_output(json_encode($resp));
	}



	public function get_majors_by_coursePH()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Forbidden', 403);
			return;
		}
		$course = $this->input->get('course', true);
		if (empty($course)) {
			$this->output->set_content_type('application/json')->set_output(json_encode(['majors' => []]));
			return;
		}

		$this->db->select('DISTINCT Major', false)
			->from('semesterstude')
			->where('Course', $course)
			->where("Major <> ''", null, false)
			->order_by('Major', 'ASC');
		$rows = $this->db->get()->result_array();
		$majors = array_values(array_map(function ($r) {
			return $r['Major'];
		}, $rows));

		$this->output->set_content_type('application/json')->set_output(json_encode(['majors' => $majors]));
	}


	public function get_sections_filtered()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Forbidden', 403);
			return;
		}

		$course    = $this->input->get('course', true);
		$major     = trim((string)$this->input->get('major', true));
		$yearLevel = $this->input->get('yearLevel', true);
		$sy        = $this->session->userdata('sy');       // keep it scoped to current term
		$sem       = $this->session->userdata('semester'); // keep it scoped to current term

		if (empty($course) || empty($yearLevel) || empty($sy) || empty($sem)) {
			$this->output->set_content_type('application/json')->set_output(json_encode(['sections' => []]));
			return;
		}

		$this->db->select('DISTINCT Section', false)
			->from('semesterstude')
			->where('Course', $course)
			->where('SY', $sy)
			->where('Semester', $sem)
			->where('YearLevel', $yearLevel)
			->where('Status', 'Enrolled');

		if ($major !== '') {
			$this->db->where('Major', $major);
		} else {
			$this->db->group_start()
				->where('Major', '')
				->or_where('Major IS NULL', null, false)
				->group_end();
		}

		$this->db->order_by('Section', 'ASC');
		$rows = $this->db->get()->result_array();
		$sections = array_values(array_map(function ($r) {
			return $r['Section'];
		}, $rows));

		$this->output->set_content_type('application/json')->set_output(json_encode(['sections' => $sections]));
	}


	public function updateEnrolledStudent()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Forbidden', 403);
			return;
		}

		// Pull POST
		$sid       = $this->input->post('semstudentid', true);
		$course    = trim((string)$this->input->post('Course', true));
		$major     = trim((string)$this->input->post('Major', true));
		$yearLevel = trim((string)$this->input->post('YearLevel', true));
		$section   = trim((string)$this->input->post('Section', true));

		if (empty($sid) || empty($course) || empty($yearLevel) || empty($section)) {
			$this->session->set_flashdata('error', 'Course, Year Level, Section are required.');
			redirect($_SERVER['HTTP_REFERER'] ?? base_url('page/instructor'));
			return;
		}

		// Normalize YearLevel to your DB format (1st, 2nd, 3rd, 4th)
		$map = [
			'first year'  => '1st',
			'1st year'  => '1st',
			'1st' => '1st',
			'second year' => '2nd',
			'2nd year'  => '2nd',
			'2nd' => '2nd',
			'third year'  => '3rd',
			'3rd year'  => '3rd',
			'3rd' => '3rd',
			'fourth year' => '4th',
			'4th year'  => '4th',
			'4th' => '4th',
		];
		$yl = strtolower($yearLevel);
		$yearLevel = $map[$yl] ?? $yearLevel;

		$data = [
			'Course'    => $course,
			'Major'     => $major,           // keep empty string if none
			'YearLevel' => $yearLevel,
			'Section'   => $section,
		];

		$this->db->where('semstudentid', (int)$sid);
		$ok = $this->db->update('semesterstude', $data);

		if ($ok) {
			$this->session->set_flashdata('success', 'Enrollment updated successfully.');
		} else {
			$this->session->set_flashdata('error', 'No changes saved or update failed.');
		}

		// Redirect back to instructor dashboard (or wherever you prefer)
		redirect(base_url('page/instructor'));
	}







	public function students_by_year($yearLevel)
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Access Denied', 403);
			return;
		}

		$id  = (string) $this->session->userdata('username');   // IDNumber
		$sy  = (string) $this->session->userdata('sy');
		$sem = (string) $this->session->userdata('semester');

		$this->load->model('StudentModel');

		// Get Course and (optional) Major assigned to this instructor
		$courseRow = $this->db
			->select('CourseDescription, Major')
			->from('course_table')
			->where('IDNumber', $id)
			->get()
			->row();

		$courseDesc = $courseRow->CourseDescription ?? '';
		// Normalize "Major": treat NULL / "" / whitespace as empty
		$major = isset($courseRow->Major) ? trim((string)$courseRow->Major) : '';

		$data = [
			'courseDescription' => $courseDesc,
			'major'             => $major,
			'yearLevel'         => (string)$yearLevel,
			'sy'                => $sy,
			'sem'               => $sem,
			// main list
			'students'          => $this->StudentModel->get_students_by_year_level(
				$yearLevel,
				$courseDesc,
				$major,
				$sy,
				$sem
			),
			// **FIXED**: pass the string $courseDesc (not the stdClass)
			'data2'             => $this->StudentModel->SectionCounts(
				$courseDesc,
				$major,
				$sy,
				$sem
			),
		];

		$this->load->view('students_by_year_view', $data);
	}




	function p_custodian()
	{
		if ($this->session->userdata('level') === 'Property Custodian') {

			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$result['count'] = $this->StudentModel->countItemsByCategory('Machinery and Equipment'); // Get the count
			$result['count1'] = $this->StudentModel->countItemsByCategory('Transportation Equipment'); // Get the count
			$result['count2'] = $this->StudentModel->countItemsByCategory('Furniture Fixtures and Books'); // Get the count
			$result['count3'] = $this->StudentModel->countItemsByCategory('OTHERS'); // Get the count


			$this->load->view('dashboard_p_custodian', $result);
		} else {
			echo "Access Denied";
		}
	}


	function library()
	{
		//Allowing access to Accounting only
		if ($this->session->userdata('level') === 'Librarian') {
			$data['total_title_count'] = $this->LibraryModel->getTotalTitleCount();
			$data['total_books'] = $this->LibraryModel->getTotalBooks();
			$data['total_cat'] = $this->LibraryModel->getTotalCategories();
			$this->load->view('dashboard_library', $data);
		} elseif ($this->session->userdata('level') === 'Admin') {
			$data['total_title_count'] = $this->LibraryModel->getTotalTitleCount();
			$data['total_books'] = $this->LibraryModel->getTotalBooks();
			$data['total_cat'] = $this->LibraryModel->getTotalCategories();
			$this->load->view('dashboard_library', $data);
		} else {
			echo "Access Denied";
		}
	}

	function proof_payment()
	{
		//Allowing access to Stuent only
		if ($this->session->userdata('level') === 'Student') {
			$id = $this->session->userdata('username');
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');
			$result1['data1'] = $this->StudentModel->UploadedPayments($id, $sem, $sy);
			// $result1['data'] = $this->StudentModel->getSemesterfromOE($id);
			$this->load->view('upload_payments', $result1);

			//$this->load->view('upload_payments');
		} else {
			echo "Access Denied";
		}
	}
	function proof_payment_view()
	{
		$sem = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');
		$result1['data1'] = $this->StudentModel->UploadedPaymentsAdmin($sem, $sy);
		$result1['data4'] = $this->StudentModel->forPaymentVerCount($sy, $sem);
		$this->load->view('proof_payments', $result1);
	}
	public function onlinePaymentsAll()
	{
		$sem = $this->session->userdata('semester');
		$sy  = $this->session->userdata('sy');

		$this->load->model('OPaymentModel'); // or 'StudentModel' if you placed it there
		$data['data1'] = $this->OPaymentModel->getVerifiedOnlinePayments($sem, $sy);

		$this->load->view('onlinePaymentsAll', $data);
	}


	function forValidation()
	{
		$courseVal = $this->input->post('course');
		$yearlevelVal = $this->input->post('yearlevel');
		$Semester = $this->session->userdata('semester');
		$SY = $this->session->userdata('sy');
		$result['data'] = $this->StudentModel->forValidation($Semester, $SY);
		$result['course'] = $this->StudentModel->getCourse();
		$result['courseVal'] = $courseVal;
		$result['yearlevelVal'] = $yearlevelVal;
		$this->load->view('online_enrollees_for_validation', $result);
	}

	function personnel()
	{
		//Allowing access to Personnel only
		if ($this->session->userdata('level') === 'Personnel') {
			$this->load->view('dashboard_view');
		} else {
			echo "Access Denied";
		}
	}
	function student()
	{
		// allow Student AND Stude Applicant to access the dashboard
		$level = (string) $this->session->userdata('level');
		if (!in_array($level, ['Student', 'Stude Applicant'], true)) {
			redirect('Login'); // or show_error('Forbidden', 403);
			return;
		}

		$id  = (string) $this->session->userdata('username');   // StudentNumber/ID
		$sem = (string) $this->session->userdata('semester');
		$sy  = (string) $this->session->userdata('sy');

		// messages
		$this->load->model('Message_model');
		$result['unreadMessages'] = $this->Message_model->getUnreadMessages($this->session->userdata('IDNumber'));
		$result['users']          = $this->Message_model->get_all_users($this->session->userdata('IDNumber'));

		// announcements + student stats
		$this->load->model('AnnouncementModel');
		$result['data']  = $this->AnnouncementModel->getActiveAnnouncementsFor('Students');
		$result['data1'] = $this->StudentModel->studeEnrollStat($id, $sem, $sy);
		$result['data2'] = $this->StudentModel->studeBalance($id);
		$result['data3'] = $this->StudentModel->semStudeCount($id);
		$result['data4'] = $this->StudentModel->studeTotalSubjects($id, $sem, $sy);
		$result['is_flagged']   = $this->StudentModel->isFlagged($id);
		$result['flag_details'] = $this->StudentModel->getFlagDetails($id);

		$this->load->view('dashboard_student', $result);
	}



	function student_registration()
	{
		//Allowing access to Stuent only
		if ($this->session->userdata('level') === 'Stude Applicant') {
			$id = $this->session->userdata('username');
			$sem = $this->session->userdata('semester');
			$sy = $this->session->userdata('sy');

			$this->load->view('dashboard_student_applicant');
		} else {
			echo "Access Denied";
		}
	}


	function studeEnrollHistory()
	{
		$id = $this->session->userdata('username');
		$result['data'] = $this->StudentModel->admissionHistory($id);
		$this->load->view('stude_enroll_history', $result);
	}

	//stude account - Student Access
	function studeaccount()
	{
		if ($this->session->userdata('level') === 'Student') {
			// IMPORTANT: this must be the StudentNumber
			$id = $this->session->userdata('StudentNumber') ?: $this->session->userdata('username');
			$result['data'] = $this->StudentModel->studeaccountById($id);
			$this->load->view('account_tracking', $result);
		} else {
			$id = $this->input->get('id'); // expect StudentNumber here
			$result['data'] = $this->StudentModel->studeaccountById($id);
			$this->load->view('account_tracking', $result);
		}
	}

	public function studentAccountingRecords()
	{
		$level = (string)$this->session->userdata('level');
		if (!in_array($level, ['Student', 'Stude Applicant', 'Student Applicant'], true)) {
			show_error('Access Denied', 403);
			return;
		}

		$studentNumber = trim((string)($this->session->userdata('StudentNumber') ?: $this->session->userdata('username')));
		if ($studentNumber === '') {
			show_error('Missing student number.', 400);
			return;
		}

		$filterSy  = trim((string)$this->input->get('sy', true));
		$filterSem = trim((string)$this->input->get('sem', true));

		$this->db->select('ID, PDate, pTime, ORNumber, Amount, description, PaymentType, CollectionSource, Sem, SY, ORStatus, refNo');
		$this->db->from('paymentsaccounts');
		$this->db->where('StudentNumber', $studentNumber);
		if ($filterSy !== '') {
			$this->db->where('SY', $filterSy);
		}
		if ($filterSem !== '') {
			$this->db->where('Sem', $filterSem);
		}
		$this->db->order_by('PDate', 'DESC');
		$this->db->order_by('pTime', 'DESC');
		$this->db->order_by('ID', 'DESC');
		$payments = $this->db->get()->result();

		$totalValid = 0.0;
		$totalAll   = 0.0;
		foreach ($payments as $row) {
			$amount = (float)($row->Amount ?? 0);
			$totalAll += $amount;
			if (strcasecmp(trim((string)($row->ORStatus ?? '')), 'Valid') === 0) {
				$totalValid += $amount;
			}
		}

		// Term-level account summary (same StudentNumber key used in accounting module).
		$accountTerms = $this->StudentModel->studeaccountById(
			$studentNumber,
			$filterSy !== '' ? $filterSy : null,
			$filterSem !== '' ? $filterSem : null
		);

		$termRows = $this->db->select('SY, Sem')
			->from('paymentsaccounts')
			->where('StudentNumber', $studentNumber)
			->group_by(['SY', 'Sem'])
			->order_by('SY', 'DESC')
			->get()->result();

		$syOptions  = [];
		$semOptions = [];
		foreach ($termRows as $t) {
			$syVal = trim((string)($t->SY ?? ''));
			$semVal = trim((string)($t->Sem ?? ''));
			if ($syVal !== '') {
				$syOptions[$syVal] = $syVal;
			}
			if ($semVal !== '') {
				$semOptions[$semVal] = $semVal;
			}
		}
		foreach ($accountTerms as $t) {
			$syVal = trim((string)($t->SY ?? ''));
			$semVal = trim((string)($t->Sem ?? ''));
			if ($syVal !== '') {
				$syOptions[$syVal] = $syVal;
			}
			if ($semVal !== '') {
				$semOptions[$semVal] = $semVal;
			}
		}
		$syOptions = array_values($syOptions);
		rsort($syOptions, SORT_NATURAL);
		$semOptions = array_values($semOptions);

		$profile = $this->db->select('FirstName, MiddleName, LastName')
			->from('studeprofile')
			->where('StudentNumber', $studentNumber)
			->limit(1)
			->get()
			->row();

		$data = [
			'studentNumber' => $studentNumber,
			'profile'       => $profile,
			'payments'      => $payments,
			'accountTerms'  => $accountTerms,
			'totalValid'    => $totalValid,
			'totalAll'      => $totalAll,
			'filterSy'      => $filterSy,
			'filterSem'     => $filterSem,
			'syOptions'     => $syOptions,
			'semOptions'    => $semOptions
		];

		$this->load->view('student_accounting_records', $data);
	}


	//stude account - Admin Access
	function studeaccountAdmin()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->studeaccountById($id);
		$this->load->view('account_tracking_admin', $result);
	}





	function studepayments()
	{
		$studentno = $this->input->get('studentno');
		$sem = $this->input->get('sem');
		$sy = $this->input->get('sy');
		$result['data'] = $this->StudentModel->studepayments($studentno, $sem, $sy);
		$this->load->view('stude_payments', $result);
	}



	function accountSummary()
	{
		// $studentno = $this->input->get('studentno');
		// $sem = $this->input->get('sem');
		$sy = $this->input->get('sy');
		$result['data'] = $this->StudentModel->studepayments_summary($sy);
		$this->load->view('accountSummary_print', $result);
	}













	function getOR()
	{
		$query = $this->db->query("select * from paymentsaccounts order by ID desc limit 1");
		return $query->result();
	}

	function UploadedPayments($id)
	{
		$query = $this->db->query("select * from online_payments where StudentNumber='" . $id . "'");
		return $query->result();
	}

	function UploadedPaymentsAdmin($id)
	{
		$query = $this->db->query("select * from online_payments op join studeprofile p on op.StudentNumber=p.StudentNumber where p.StudentNumber='" . $id . "' and op.status='PENDING'");
		return $query->result();
	}

	function studegrades()
	{
		$this->load->view('student_grades');
	}

	public function bdayToday()
	{
		date_default_timezone_set('Asia/Manila');

		$this->load->model('SignupModel'); // <— new model below
		$result['students'] = $this->SignupModel->birthdays_today();

		$this->load->view('bday_today', $result);
	}

	//Masterlist by Sex
	function listBySex()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$sex = $this->input->get('sex');
		$result['data'] = $this->StudentModel->sexList($sem, $sy, $sex);
		$this->load->view('masterlist_by_sex', $result);
	}
	public function bdayMonth()
	{
		date_default_timezone_set('Asia/Manila');

		$this->load->model('SignupModel');
		$result['students'] = $this->SignupModel->birthdays_this_month();

		$this->load->view('bday_month', $result);
	}
	//online enrollment
	function enrollment()
	{
		$id  = $this->session->userdata('username');
		$sy  = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		$courseVal    = $this->input->post('course');     // (kept even if not used)
		$yearlevelVal = $this->input->post('yearlevel');  // (kept even if not used)

		// Load data for the view
		$result['data']        = $this->StudentModel->displayrecordsById($id);
		$result['course']      = $this->StudentModel->getCourse();
		$result['courseVal']   = $courseVal;
		$result['yearlevelVal'] = $yearlevelVal;

		// NEW: check if already enrolled in registrar side for current SY/Sem
		$result['enrolled'] = $this->StudentModel->getEnrolledCurrent($id, $sy, $sem);

		// Show the form (it will now know whether to autofill+lock)
		$this->load->view('enrollment_form', $result);

		// Handle submit
		if ($this->input->post('enroll')) {
			// get data from the form
			$StudentNumber = $this->input->post('StudentNumber');
			$FName         = $this->input->post('FName');
			$MName         = $this->input->post('MName');
			$LName         = $this->input->post('LName');
			$Course        = $this->input->post('Course');
			$Major         = $this->input->post('Major');
			$YearLevel     = $this->input->post('YearLevel');
			$Semester      = $this->input->post('Semester');
			$SY            = $this->input->post('SY');
			$requirements  = $this->input->post('requirements');

			$fname = $this->session->userdata('fname');
			$email = $this->session->userdata('email');

			// NEW: block if already in semesterstude (officially enrolled)
			$alreadyInSemStud = $this->db->where([
				'StudentNumber' => $StudentNumber,
				'Semester'      => $Semester,
				'SY'            => $SY
			])->get('semesterstude')->num_rows() > 0;

			if ($alreadyInSemStud) {
				$this->session->set_flashdata(
					'msg',
					'<div class="alert alert-danger text-center"><b>You are currently enrolled for this semester.</b></div>'
				);
				redirect('Page/enrollment');
				return;
			}

			// existing duplicate check in online_enrollment
			$dupOnline = $this->db->where([
				'StudentNumber' => $StudentNumber,
				'Semester'      => $Semester,
				'SY'            => $SY
			])->get('online_enrollment')->num_rows() > 0;

			if ($dupOnline) {
				$this->session->set_flashdata(
					'msg',
					'<div class="alert alert-danger text-center"><b>You are currently enrolled for this semester.</b></div>'
				);
				redirect('Page/enrollment');
			} else {
				$this->db->insert('online_enrollment', [
					// id is auto-increment, so leave it out if your table is set that way
					'StudentNumber' => $StudentNumber,
					'FName'         => $FName,
					'MName'         => $MName,
					'LName'         => $LName,
					'Course'        => $Course,
					'Major'         => $Major,
					'YearLevel'     => $YearLevel,
					'Semester'      => $Semester,
					'SY'            => $SY,
					'requirements'  => $requirements,
					'status'        => 'For Validation',
					'enrolStatus'   => '0',
					'payStatus'     => 'Unpaid'
				]);

				$this->session->set_flashdata(
					'msg',
					'<div class="alert alert-success text-center"><b>Your data has been submitted successfully for validation.</b></div>'
				);

				// Email Notification (unchanged)
				$this->load->config('email');
				$this->load->library('email');

				$mail_message  = 'Dear ' . $fname . ',<br><br>';
				$mail_message .= 'Your enrollment data has been submitted for validation.<br>';
				$mail_message .= 'Course: <b>' . $Course . '</b><br>';
				$mail_message .= 'Major: <b>' . $Major . '</b><br>';
				$mail_message .= 'Year Level: <b>' . $YearLevel . '</b><br>';
				$mail_message .= 'Sem/SY: <b>' . $Semester . ', ' . $SY . '</b><br>';
				$mail_message .= 'Status: <b>For Validation</b><br><br>';
				$mail_message .= 'You will be notified once validated.<br><br>';
				$mail_message .= 'Thanks & Regards,<br>SRMS - Online';

				$this->email->from('no-reply@srmsportal.com', 'SRMS Online Team')
					->to($email)
					->subject('Enrollment')
					->message($mail_message)
					->send();

				redirect('Page/enrollment');
			}
		}
	}


	public function enrollmentAcceptance()
	{
		$courseVal = $this->input->post('course');
		$yearlevelVal = $this->input->post('yearlevel');

		// Load dropdown values for the form
		$result['course'] = $this->StudentModel->getCourse();
		$result['section'] = $this->StudentModel->getSection();
		$data['settings'] = $this->StudentModel->get_srms_settings(); // settings fetched
		$result['courseVal'] = $courseVal;
		$result['yearlevelVal'] = $yearlevelVal;

		// Load the enrollment form view
		$this->load->view('enrollment_form_final', $result);

		if ($this->input->post('submit')) {

			$settings = $this->StudentModel->get_srms_settings(); // Get latest settings before use

			$data = [
				'StudentNumber' => $this->input->post('StudentNumber'),
				'Course'        => $this->input->post('Course'),
				'YearLevel'     => $this->input->post('YearLevel'),
				'Status'        => $this->input->post('Status'),
				'Semester'      => $this->input->post('Semester'),
				'SY'            => $this->input->post('SY'),
				'Section'       => $this->input->post('Section'),
				'StudeStatus'   => $this->input->post('StudeStatus'),
				'PayingStatus'  => $this->input->post('PayingStatus'),
				'Scholarship' => $this->input->post('Scholarship') ?? '',

				'YearLevelStat' => $this->input->post('YearLevelStat'),
				'Major'         => $this->input->post('Major'),
				'EnroledDate'   => date('Y-m-d'),
				'settingsID'    => $settings->settingsID, // ✅ Set from DB
			];

			$email = $this->input->post('email');

			// Check if student is already enrolled
			$exists = $this->db->where([
				'StudentNumber' => $data['StudentNumber'],
				'Course'        => $data['Course'],
				'Major'         => $data['Major'],
				'Semester'      => $data['Semester'],
				'SY'            => $data['SY']
			])->get('semesterstude')->num_rows();

			if ($exists) {
				$this->session->set_flashdata('danger', '<div class="alert alert-danger text-center"><b>The selected student is currently enrolled!</b></div>');
				redirect('Masterlist/enrolledList');
			} else {
				// insert to semesterstude
				$this->db->insert('semesterstude', $data);

				// sync studeprofile
				$this->db->where('StudentNumber', $data['StudentNumber'])
					->update('studeprofile', [
						'course'    => $data['Course'],
						'major'     => $data['Major'],
						'yearLevel' => $data['YearLevel'], // optional
					]);


				$studentNumber = $this->input->post('StudentNumber', true);
				$course        = $this->input->post('Course', true);
				$major         = $this->input->post('Major', true);
				$yearLevel     = $this->input->post('YearLevel', true);

				$this->db->where('StudentNumber', $studentNumber)
					->update('studeprofile', [
						'course'    => $course,
						'major'     => $major,
						'yearLevel' => $yearLevel,
					]);


				// mark online_enrollment as Enrolled
				$this->db->where([
					'StudentNumber' => $data['StudentNumber'],
					'Semester'      => $data['Semester'],
					'SY'            => $data['SY']
				])->update('online_enrollment', ['enrolStatus' => 'Enrolled']);

				$this->load->config('email');
				$this->load->library('email');

				$mail_message = 'Dear ' . $data['StudentNumber'] . ",<br><br>"; // You may replace with actual name if available
				$mail_message .= "You are now officially enrolled.<br>";
				$mail_message .= "Course: <b>{$data['Course']}</b><br>";
				$mail_message .= "Major: <b>{$data['Major']}</b><br>";
				$mail_message .= "Year Level: <b>{$data['YearLevel']}</b><br>";
				$mail_message .= "Section: <b>{$data['Section']}</b><br>";
				$mail_message .= "Sem/SY: <b>{$data['Semester']}, {$data['SY']}</b><br>";
				$mail_message .= "Status: <b>Validated</b><br><br>";
				$mail_message .= "Thanks & Regards,<br>SRMS - Online";

				$this->email->from('no-reply@lxeinfotechsolutions.com', 'SRMS Online Team');
				$this->email->to($email);
				$this->email->subject('Enrollment');
				$this->email->message($mail_message);
				$this->email->send();

				$this->session->set_flashdata('success', '<div class="alert alert-success text-center"><b>Student successfully enrolled.</b></div>');
				redirect('Masterlist/enrolledList');
			}
		}
	}

	public function updateSemesterSy()
	{
		$semester = $this->input->post('semester');
		$sy = $this->input->post('sy');

		$this->session->set_userdata('semester', $semester);
		$this->session->set_userdata('sy', $sy);

		// Optionally add a flash message or log activity

		redirect($this->agent->referrer()); // redirect back to the previous page
	}





	//update online enrollees
	public function update_online_enrollees()
	{
		$id = $this->input->get('id');
		$this->StudentModel->updateEnrollees($id);
		redirect("Page/admin");
	}

	public function studentsprofile()
	{
		$level = $this->session->userdata('level');
		$sy    = $this->session->userdata('sy');
		$sem   = $this->session->userdata('semester');

		// ID to load
		$id = ($level === 'Student')
			? (string)$this->session->userdata('username')
			: (string)$this->input->get('id');
		$this->ensure_student_profile_exists($id);

		// Pull data from model
		$raw0 = $this->StudentModel->displayrecordsById($id);       // main profile row (array of objects)
		$raw1 = $this->StudentModel->profilepic($id);               // profile pic (array of objects; expect ->avatar)
		$raw2 = $this->StudentModel->getStudentRequirements($id);   // requirements (array)
		$raw3 = $this->StudentModel->studeGrades($id, $sem, $sy);   // grades (array)
		$raw4 = $this->StudentModel->studeaccountById($id);         // account history (array)
		$raw5 = $this->StudentModel->admissionHistory($id);         // enrollment history (array)

		// ---- SKELETON: define all properties your view expects so no "Undefined property" warnings ----
		$studentSkeleton = [
			'StudentNumber'     => '',
			'FirstName'         => '',
			'MiddleName'        => '',
			'LastName'          => '',
			'birthDate'         => '',
			'age'               => '',
			'Sex'               => '',
			'CivilStatus'       => '',
			'contactNo'         => '',
			'email'             => '',
			'father'            => '',
			'fOccupation'       => '',
			'mother'            => '',
			'mOccupation'       => '',
			'guardian'          => '',
			'guardianContact'   => '',
			'guardianAddress'   => '',
			'sitioPresent'      => '',
			'brgyPresent'       => '',
			'cityPresent'       => '',
			'provincePresent'   => '',
			// add any other fields your view echoes, if I missed one
		];

		// Build final $data[0]
		$row0 = (is_array($raw0) && isset($raw0[0]) && is_object($raw0[0])) ? $raw0[0] : (object)[];
		// overlay the real row onto the skeleton
		$final0 = (object) array_merge($studentSkeleton, (array)$row0);

		// Profile pic: ensure avatar property exists to avoid warnings in view
		$picRow = (is_array($raw1) && isset($raw1[0]) && is_object($raw1[0])) ? $raw1[0] : (object)[];
		if (!property_exists($picRow, 'avatar')) {
			$picRow->avatar = ''; // view will fall back to default.png if empty
		}

		// Normalize the rest to arrays (no warnings on foreach)
		$result['data']  = [$final0];              // important: index 0 exists with properties
		$result['data1'] = [$picRow];
		$result['data2'] = is_array($raw2) ? $raw2 : [];
		$result['data3'] = is_array($raw3) ? $raw3 : [];
		$result['data4'] = is_array($raw4) ? $raw4 : [];
		$result['data5'] = is_array($raw5) ? $raw5 : [];

		$this->load->view('profile_page', $result);
	}

	public function myProfile()
	{
		$level = (string)$this->session->userdata('level');
		if (!in_array($level, ['Student', 'Stude Applicant'], true)) {
			show_error('Access Denied', 403);
		}

		$studentNumber = trim((string)$this->session->userdata('username'));
		if ($studentNumber === '') {
			redirect('Login');
			return;
		}

		$this->ensure_student_profile_exists($studentNumber);

		$bundle = $this->StudentModel->getMyProfileBundle($studentNumber);

		$courses = $this->StudentModel->get_courseTable();
		$courseLookup = [];
		foreach ($courses as $course) {
			$courseLookup[(int)$course->courseid] = $course;
		}

		$currentCourseDesc = trim((string)($bundle->enrollment->Course ?? $bundle->profile->course ?? ''));
		$currentYear       = trim((string)($bundle->enrollment->YearLevel ?? $bundle->profile->yearLevel ?? ''));
		$currentSection    = trim((string)($bundle->enrollment->Section ?? $bundle->profile->section ?? ''));
		$yearLevels        = ['1st', '2nd', '3rd', '4th'];

		$civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
		$currentCivilStatus = trim((string)($bundle->profile->CivilStatus ?? ''));
		if ($currentCivilStatus !== '') {
			$already = false;
			foreach ($civilStatusOptions as $opt) {
				if (strcasecmp($opt, $currentCivilStatus) === 0) {
					$already = true;
					break;
				}
			}
			if (!$already) {
				$civilStatusOptions[] = $currentCivilStatus;
			}
		}

		if ($this->input->method() === 'post') {
			$form = $this->input->post(null, true);

			$firstName  = strtoupper(trim((string)($form['FirstName'] ?? '')));
			$middleName = strtoupper(trim((string)($form['MiddleName'] ?? '')));
			$lastName   = strtoupper(trim((string)($form['LastName'] ?? '')));
			$nameExtn   = strtoupper(trim((string)($form['nameExtn'] ?? '')));
			$email      = trim((string)($form['email'] ?? ''));
			$contactNo  = trim((string)($form['contactNo'] ?? ''));
			$birthDate  = trim((string)($form['birthDate'] ?? ''));
			$age        = trim((string)($form['age'] ?? ''));

			$accountData = [
				'fName' => $firstName,
				'mName' => $middleName,
				'lName' => $lastName,
				'email' => $email
			];

			$profileData = [
				'FirstName'   => $firstName,
				'MiddleName'  => $middleName,
				'LastName'    => $lastName,
				'nameExtn'    => $nameExtn,
				'Sex'         => trim((string)($form['Sex'] ?? '')),
				'birthDate'   => $birthDate,
				'age'         => $age,
				'contactNo'   => $contactNo,
				'email'       => $email,
				'CivilStatus' => trim((string)($form['CivilStatus'] ?? '')),
				'BirthPlace'  => trim((string)($form['BirthPlace'] ?? '')),
				'nationality' => trim((string)($form['nationality'] ?? 'Filipino')),
				'working'     => trim((string)($form['working'] ?? 'No')),
				'VaccStat'    => trim((string)($form['VaccStat'] ?? '')),
				'sitio'       => trim((string)($form['sitio'] ?? $form['Sitio'] ?? '')),
				'brgy'        => trim((string)($form['brgy'] ?? $form['Brgy'] ?? '')),
				'city'        => trim((string)($form['city'] ?? $form['City'] ?? '')),
				'province'    => trim((string)($form['province'] ?? $form['Province'] ?? ''))
			];

			$courseDesc = '';
			if (!empty($form['Course1'])) {
				$courseDesc = trim((string)$form['Course1']);
			} elseif (!empty($form['course_id'])) {
				$courseId = (int)$form['course_id'];
				if (isset($courseLookup[$courseId])) {
					$courseDesc = trim((string)$courseLookup[$courseId]->CourseDescription);
				}
			} elseif ($currentCourseDesc !== '') {
				$courseDesc = $currentCourseDesc;
			}

			$yearLevel = trim((string)($form['yearLevel'] ?? $form['YearLevel'] ?? ''));
			$section   = trim((string)($form['section'] ?? $form['Section'] ?? ''));

			$enrollmentData = [];
			if ($courseDesc !== '') {
				$enrollmentData['Course'] = $courseDesc;
				$profileData['course']    = $courseDesc;
			}
			if ($yearLevel !== '') {
				$enrollmentData['YearLevel'] = $yearLevel;
				$profileData['yearLevel']    = $yearLevel;
			}
			if ($section !== '') {
				$enrollmentData['Section'] = $section;
			}

			$save = $this->StudentModel->updateMyProfileBundle(
				$studentNumber,
				$accountData,
				$profileData,
				$enrollmentData,
				[
					'semstudentid' => $bundle->enrollment->semstudentid ?? null,
					'sy'           => $this->session->userdata('sy'),
					'semester'     => $this->session->userdata('semester')
				]
			);

			if (!empty($save['success'])) {
				$this->session->set_userdata('fName', $firstName);
				$this->session->set_userdata('mName', $middleName);
				$this->session->set_userdata('lName', $lastName);
				if ($email !== '') {
					$this->session->set_userdata('email', $email);
				}
				$this->session->set_flashdata('success', 'Your profile was updated.');
				redirect('Page/student');
				return;
			} else {
				$this->session->set_flashdata('danger', 'We could not save your changes. Please try again.');
				redirect('Page/myProfile');
				return;
			}
		}

		$currentProvince = trim((string)($bundle->profile->province ?? ''));
		$currentCity     = trim((string)($bundle->profile->city ?? ''));

		$data = [
			'bundle'             => $bundle,
			'courses'            => $courses,
			'yearLevels'         => $yearLevels,
			'currentCourseDesc'  => $currentCourseDesc,
			'currentYear'        => $currentYear,
			'currentSection'     => $currentSection,
			'sexOptions'         => ['Female', 'Male'],
			'civilStatusOptions' => $civilStatusOptions,
			'currentProvince'    => $currentProvince,
			'currentCity'        => $currentCity,
			'currentBrgy'        => trim((string)($bundle->profile->brgy ?? '')),
			'provincesList'      => $this->StudentModel->get_provinces(),
			'citiesList'         => ($currentProvince !== '') ? $this->StudentModel->get_cities($currentProvince) : [],
			'barangaysList'      => ($currentCity !== '') ? $this->StudentModel->get_barangays($currentCity) : [],
			'backUrl'            => base_url('Page/studentProfile'),
			'backLabel'          => 'Back to Profile'
		];

		$this->load->view('student_my_profile', $data);
	}

	public function studentProfile()
	{
		$level = (string)$this->session->userdata('level');
		if (!in_array($level, ['Student', 'Stude Applicant'], true)) {
			show_error('Access Denied', 403);
		}

		$studentNumber = trim((string)$this->session->userdata('username'));
		if ($studentNumber === '') {
			redirect('Login');
			return;
		}

		$this->ensure_student_profile_exists($studentNumber);

		$bundle = $this->StudentModel->getMyProfileBundle($studentNumber);

		$currentCourseDesc = trim((string)($bundle->enrollment->Course ?? $bundle->profile->course ?? ''));
		$currentYear       = trim((string)($bundle->enrollment->YearLevel ?? $bundle->profile->yearLevel ?? ''));
		$currentSection    = trim((string)($bundle->enrollment->Section ?? $bundle->profile->section ?? ''));

		$profileRow = (object)[
			'StudentNumber' => trim((string)($bundle->profile->StudentNumber ?? $bundle->account->username ?? $studentNumber)),
			'FirstName'     => trim((string)($bundle->profile->FirstName ?? $bundle->account->fName ?? '')),
			'MiddleName'    => trim((string)($bundle->profile->MiddleName ?? $bundle->account->mName ?? '')),
			'LastName'      => trim((string)($bundle->profile->LastName ?? $bundle->account->lName ?? '')),
			'nameExtn'      => trim((string)($bundle->profile->nameExtn ?? '')),
			'Sex'           => trim((string)($bundle->profile->Sex ?? '')),
			'CivilStatus'   => trim((string)($bundle->profile->CivilStatus ?? '')),
			'contactNo'     => trim((string)($bundle->profile->contactNo ?? '')),
			'birthDate'     => trim((string)($bundle->profile->birthDate ?? '')),
			'age'           => trim((string)($bundle->profile->age ?? '')),
			'BirthPlace'    => trim((string)($bundle->profile->BirthPlace ?? '')),
			'email'         => trim((string)($bundle->profile->email ?? $bundle->account->email ?? '')),
			'sitio'         => trim((string)($bundle->profile->sitio ?? '')),
			'brgy'          => trim((string)($bundle->profile->brgy ?? '')),
			'city'          => trim((string)($bundle->profile->city ?? '')),
			'province'      => trim((string)($bundle->profile->province ?? ''))
		];

		$avatarPath = trim((string)($bundle->account->avatar ?? $bundle->profile->imagePath ?? ''));
		if ($avatarPath === '') {
			$avatarPath = 'default.png';
		}

		$data = [
			'data'              => [$profileRow],
			'data1'             => [(object)['avatar' => $avatarPath]],
			'currentCourseDesc' => $currentCourseDesc,
			'currentYear'       => $currentYear,
			'currentSection'    => $currentSection,
			'backUrl'           => base_url('Page/student'),
			'editUrl'           => base_url('Page/myProfile')
		];

		$this->load->view('student_profile_view', $data);
	}
	public function studentsprofile2()
	{
		$userLevel = $this->session->userdata('level');
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		if ($userLevel === 'Student') {
			$id = $this->session->userdata('username');
		} else {
			$id = $this->input->get('id');
			$this->ensure_student_profile_exists($id);
		}

		$studeno = $id;
		$studentNumber = $id;

		// Load common data
		$result['data'] = $this->StudentModel->displayrecordsById($id);
		$result['data1'] = $this->StudentModel->profilepic($id);


		$result['data2'] = $this->StudentModel->getStudentRequirements($studentNumber);

		// Check for grade filter
		$grade = $this->input->post('grade');
		if (empty($grade)) {
			$result['data3'] = $this->StudentModel->studeGrades($studeno, $sy);
		} else {
			$result['data3'] = $this->StudentModel->studeGrades($studeno, $grade);
		}

		$result['data4'] = $this->StudentModel->studeaccountById($id);
		$result['data5'] = $this->StudentModel->admissionHistory($id);
		$result['data6'] = $this->StudentModel->studerequest($id);

		if ($userLevel !== 'Student') {
			$result['data7'] = $this->SettingsModel->getSchoolInfo();
		}

		$this->load->view('profile_page', $result);
	}

	function studentSignup()
	{
		$level            = (string)$this->session->userdata('level');
		$isSelfApplicant  = ($level === 'Stude Applicant');
		$isSelfStudent    = ($level === 'Student');
		$studentNumberRaw = $isSelfApplicant || $isSelfStudent
			? $this->session->userdata('username')
			: $this->input->get('id', true);

		$studentNumber = trim((string)$studentNumberRaw);
		if ($studentNumber === '') {
			// If no ID was supplied for staff users, fail fast.
			if ($isSelfApplicant || $isSelfStudent) {
				redirect('Login');
			} else {
				show_error('A Student Number is required to view this page.', 404);
			}
			return;
		}

		$this->ensure_student_profile_exists($studentNumber);

		$bundle = $this->StudentModel->getMyProfileBundle($studentNumber);
		$courses = $this->StudentModel->get_courseTable();
		$courseLookup = [];
		foreach ($courses as $course) {
			$courseLookup[(int)$course->courseid] = $course;
		}

		$currentCourseDesc = trim((string)($bundle->enrollment->Course ?? $bundle->profile->course ?? ''));
		$currentYear       = trim((string)($bundle->enrollment->YearLevel ?? $bundle->profile->yearLevel ?? ''));
		$currentSection    = trim((string)($bundle->enrollment->Section ?? $bundle->profile->section ?? ''));
		$yearLevels        = ['1st', '2nd', '3rd', '4th'];
		$civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
		$currentCivil = trim((string)($bundle->profile->CivilStatus ?? ''));
		if ($currentCivil !== '') {
			$existsCivil = false;
			foreach ($civilStatusOptions as $opt) {
				if (strcasecmp($opt, $currentCivil) === 0) {
					$existsCivil = true;
					break;
				}
			}
			if (!$existsCivil) {
				$civilStatusOptions[] = $currentCivil;
			}
		}

		if ($this->input->method() === 'post') {
			$form = $this->input->post(null, true);

			$firstName  = strtoupper(trim((string)($form['FirstName'] ?? '')));
			$middleName = strtoupper(trim((string)($form['MiddleName'] ?? '')));
			$lastName   = strtoupper(trim((string)($form['LastName'] ?? '')));
			$nameExtn   = strtoupper(trim((string)($form['nameExtn'] ?? '')));
			$email      = trim((string)($form['email'] ?? ''));
			$contactNo  = trim((string)($form['contactNo'] ?? ''));
			$birthDate  = trim((string)($form['birthDate'] ?? ''));
			$age        = trim((string)($form['age'] ?? ''));

			$accountData = [
				'fName' => $firstName,
				'mName' => $middleName,
				'lName' => $lastName,
				'email' => $email
			];

			$profileData = [
				'FirstName'   => $firstName,
				'MiddleName'  => $middleName,
				'LastName'    => $lastName,
				'nameExtn'    => $nameExtn,
				'Sex'         => trim((string)($form['Sex'] ?? '')),
				'birthDate'   => $birthDate,
				'age'         => $age,
				'contactNo'   => $contactNo,
				'email'       => $email,
				'CivilStatus' => trim((string)($form['CivilStatus'] ?? '')),
				'BirthPlace'  => trim((string)($form['BirthPlace'] ?? '')),
				'nationality' => trim((string)($form['nationality'] ?? 'Filipino')),
				'working'     => trim((string)($form['working'] ?? 'No')),
				'VaccStat'    => trim((string)($form['VaccStat'] ?? '')),
				'sitio'       => trim((string)($form['sitio'] ?? $form['Sitio'] ?? '')),
				'brgy'        => trim((string)($form['brgy'] ?? $form['Brgy'] ?? '')),
				'city'        => trim((string)($form['city'] ?? $form['City'] ?? '')),
				'province'    => trim((string)($form['province'] ?? $form['Province'] ?? ''))
			];

			$courseDesc = '';
			if (!empty($form['Course1'])) {
				$courseDesc = trim((string)$form['Course1']);
			} elseif (!empty($form['course_id'])) {
				$courseId = (int)$form['course_id'];
				if (isset($courseLookup[$courseId])) {
					$courseDesc = trim((string)$courseLookup[$courseId]->CourseDescription);
				}
			} elseif ($currentCourseDesc !== '') {
				$courseDesc = $currentCourseDesc;
			}

			$yearLevel = trim((string)($form['yearLevel'] ?? $form['YearLevel'] ?? ''));
			$section   = trim((string)($form['section'] ?? $form['Section'] ?? ''));

			$enrollmentData = [];
			if ($courseDesc !== '') {
				$enrollmentData['Course'] = $courseDesc;
				$profileData['course']    = $courseDesc;
			}
			if ($yearLevel !== '') {
				$enrollmentData['YearLevel'] = $yearLevel;
				$profileData['yearLevel']    = $yearLevel;
			}
			if ($section !== '') {
				$enrollmentData['Section'] = $section;
			}

			$save = $this->StudentModel->updateMyProfileBundle(
				$studentNumber,
				$accountData,
				$profileData,
				$enrollmentData,
				[
					'semstudentid' => $bundle->enrollment->semstudentid ?? null,
					'sy'           => $this->session->userdata('sy'),
					'semester'     => $this->session->userdata('semester')
				]
			);

			if (!empty($save['success'])) {
				if ($isSelfApplicant || $isSelfStudent) {
					$this->session->set_userdata('fName', $firstName);
					$this->session->set_userdata('mName', $middleName);
					$this->session->set_userdata('lName', $lastName);
					if ($email !== '') {
						$this->session->set_userdata('email', $email);
					}
					$this->session->set_flashdata('success', 'Your profile was updated.');
				} else {
					$this->session->set_flashdata('success', 'Applicant profile updated successfully.');
				}
				$redirectUrl = ($isSelfApplicant || $isSelfStudent)
					? 'Page/student'
					: 'Page/studentSignup?id=' . urlencode($studentNumber);
			} else {
				$this->session->set_flashdata('danger', 'We could not save the changes. Please try again.');
				$redirectUrl = ($isSelfApplicant || $isSelfStudent)
					? 'Page/studentSignup'
					: 'Page/studentSignup?id=' . urlencode($studentNumber);
			}

			redirect($redirectUrl);
			return;
		}

		$viewData = [
			'bundle'            => $bundle,
			'courses'           => $courses,
			'yearLevels'        => $yearLevels,
			'currentCourseDesc' => $currentCourseDesc,
			'currentYear'       => $currentYear,
			'currentSection'    => $currentSection,
			'sexOptions'        => ['Female', 'Male'],
			'pageTitle'         => ($isSelfApplicant || $isSelfStudent) ? 'My Profile' : 'Applicant Profile',
			'pageDescription'   => ($isSelfApplicant || $isSelfStudent)
				? 'Update your personal and academic details.'
				: 'Review and update the applicant\'s personal and academic details.',
			'backUrl'           => ($isSelfApplicant || $isSelfStudent)
				? base_url('Page/studentProfile')
				: base_url('Page/signUpList'),
			'backLabel'         => ($isSelfApplicant || $isSelfStudent)
				? 'Back to Profile'
				: 'Back to Signup List',
			'submitLabel'       => ($isSelfApplicant || $isSelfStudent)
				? 'Save Changes'
				: 'Save Applicant Changes'
		];

		$this->load->view('student_my_profile', $viewData);
	}


	/** Ensure a studeprofile row exists for a given StudentNumber (id). */
	private function ensure_student_profile_exists($id)
	{
		$this->load->database();

		// already exists? nothing to do
		$exists = $this->db->select('StudentNumber')
			->get_where('studeprofile', ['StudentNumber' => $id], 1)
			->row();
		if ($exists) {
			return;
		}

		$settingsRow = $this->db->select('settingsID')->limit(1)->get('o_srms_settings')->row();
		$settingsID  = $settingsRow->settingsID ?? 1;

		// try source 1: studentsignup
		$src = $this->db->get_where('studentsignup', ['StudentNumber' => $id], 1)->row();
		if (!$src) {
			// try source 2: o_users
			$src = $this->db->get_where('o_users', ['username' => $id], 1)->row();
			if ($src) {
				$payload = [
					'StudentNumber' => $src->IDNumber ?: $src->username,
					'FirstName'     => $src->fName ?? '',
					'MiddleName'    => $src->mName ?? '',
					'LastName'      => $src->lName ?? '',
					'email'         => $src->email ?? '',
					'ethnicity'     => '',
					'working'       => 'No',
					'VaccStat'      => '',
					'nationality'   => 'Filipino',
					'course'        => '',
					'Major'         => '',
					'yearLevel'     => '',
					'settingsID'    => $settingsID
				];
				$this->db->insert('studeprofile', $payload);
			}
			return;
		}

		$payload = [
			'StudentNumber'       => $src->StudentNumber,
			'FirstName'           => $src->FirstName ?? '',
			'MiddleName'          => $src->MiddleName ?? '',
			'LastName'            => $src->LastName ?? '',
			'nameExtn'            => $src->nameExtn ?? '',
			'Sex'                 => $src->Sex ?? '',
			'birthDate'           => $src->birthDate ?? '',
			'age'                 => $src->age ?? '',
			'BirthPlace'          => $src->BirthPlace ?? '',
			'contactNo'           => $src->contactNo ?? '',
			'email'               => $src->email ?? '',
			'CivilStatus'         => $src->CivilStatus ?? '',
			'ethnicity'           => $src->ethnicity ?? '',
			'Religion'            => $src->Religion ?? '',
			'working'             => $src->working ?? 'No',
			'VaccStat'            => $src->VaccStat ?? '',
			'province'            => $src->province ?? '',
			'city'                => $src->city ?? '',
			'brgy'                => $src->brgy ?? '',
			'sitio'               => $src->sitio ?? '',
			'course'              => $src->Course1 ?? '',
			'Major'               => $src->Major1 ?? '',
			'occupation'          => $src->occupation ?? '',
			'salary'              => $src->salary ?? '',
			'employer'            => $src->employer ?? '',
			'employerAddress'     => $src->employerAddress ?? '',
			'graduationDate'      => $src->graduationDate ?? '',
			'guardian'            => $src->guardian ?? '',
			'guardianRelationship' => $src->guardianRelationship ?? '',
			'guardianContact'     => $src->guardianContact ?? '',
			'guardianAddress'     => $src->guardianAddress ?? '',
			'spouse'              => $src->spouse ?? '',
			'spouseRelationship'  => $src->spouseRelationship ?? '',
			'spouseContact'       => $src->spouseContact ?? '',
			'children'            => $src->children ?? '',
			'imagePath'           => $src->imagePath ?? '',
			'yearLevel'           => $src->yearLevel ?? '',
			'father'              => $src->father ?? '',
			'fOccupation'         => $src->fOccupation ?? '',
			'fatherAddress'       => $src->fatherAddress ?? '',
			'fatherContact'       => $src->fatherContact ?? '',
			'mother'              => $src->mother ?? '',
			'mOccupation'         => $src->mOccupation ?? '',
			'motherAddress'       => $src->motherAddress ?? '',
			'motherContact'       => $src->motherContact ?? '',
			'disability'          => $src->disability ?? '',
			'parentsMonthly'      => $src->parentsMonthly ?? '0',
			'elementary'          => $src->elementary ?? '',
			'elementaryAddress'   => $src->elementaryAddress ?? '',
			'elemGraduated'       => $src->elemGraduated ?? '',
			'secondary'           => $src->secondary ?? '',
			'secondaryAddress'    => $src->secondaryAddress ?? '',
			'secondaryGraduated'  => $src->secondaryGraduated ?? '',
			'vocational'          => $src->vocational ?? '',
			'vocationalAddress'   => $src->vocationalAddress ?? '',
			'vocationalGraduated' => $src->vocationalGraduated ?? '',
			'vocationalCourse'    => $src->vocationalCourse ?? '',
			'nationality'         => $src->nationality ?? 'Filipino',
			'settingsID'          => $settingsID
		];

		$this->db->insert('studeprofile', $payload);
	}



	public function updatestudentsignup()
	{
		$data = [
			'course' => $this->StudentModel->getCourse(),
			'major' => $this->StudentModel->getCourseMajor(),
			'provinces' => $this->StudentModel->get_provinces(),
			'cities' => $this->StudentModel->get_cities(),
			'brgy' => $this->StudentModel->get_brgy(),
			'ethnicity' => $this->SettingsModel->get_ethnicity(),
			'religion' => $this->SettingsModel->get_religion(),
		];

		$StudentNumber = $this->input->get('StudentNumber');
		$result['data'] = $this->StudentModel->getstudentsignupbyId($StudentNumber);

		$result['course'] = $this->StudentModel->getCourse();
		$result['major'] = $this->StudentModel->getCourseMajor();
		$result['provinces'] = $this->StudentModel->get_provinces();
		$result['cities'] = $this->StudentModel->get_cities();
		$result['brgy'] = $this->StudentModel->get_brgy();
		$result['ethnicity'] = $this->SettingsModel->get_ethnicity();
		$result['religion'] = $this->SettingsModel->get_religion();
		$this->load->view('profile_page_update', $result);
		if ($this->input->post('update')) {
			$data = array(
				'StudentNumber' => $this->input->post('StudentNumber'),
				'FirstName' => $this->input->post('FirstName'),
				'MiddleName' => $this->input->post('MiddleName'),
				'LastName' => $this->input->post('LastName'),
				'nameExtn' => $this->input->post('nameExtn'),
				'Sex' => $this->input->post('Sex'),
				'birthDate' => $this->input->post('bdate'),
				'age' => $this->input->post('age'),
				'BirthPlace' => $this->input->post('BirthPlace'),
				'contactNo' => $this->input->post('contactNo'),
				'email' => $this->input->post('email'),
				'CivilStatus' => $this->input->post('CivilStatus'),
				'ethnicity' => $this->input->post('Ethnicity'),
				'Religion' => $this->input->post('Religion'),
				'working' => $this->input->post('working'),
				'VaccStat' => $this->input->post('VaccStat'),
				'province' => $this->input->post('Province'),
				'city' => $this->input->post('City'),
				'brgy' => $this->input->post('Brgy'),
				'sitio' => $this->input->post('Sitio'),
				'nationality' => $this->input->post('nationality'),
				'yearLevel' => $this->input->post('yearLevel'),
				'Course3' => $this->input->post('Course3'),
				'Major3' => $this->input->post('Major3'),
				'Course1' => $this->input->post('Course1'),
				'Major1' => $this->input->post('Major1'),
				'Course2' => $this->input->post('Course2'),
				'Major2' => $this->input->post('Major2'),

				'Father' => $this->input->post('Father'),
				'FOccupation' => $this->input->post('FOccupation'),
				'fatherContact' => $this->input->post('fatherContact'),
				'Mother' => $this->input->post('Mother'),
				'MOccupation' => $this->input->post('MOccupation'),
				'motherContact' => $this->input->post('motherContact'),
				'Guardian' => $this->input->post('Guardian'),
				'GuardianRelationship' => $this->input->post('GuardianRelationship'),

				'spouse' => $this->input->post('spouse'),
				'spouseRelationship' => $this->input->post('spouseRelationship'),
				'spouseContact' => $this->input->post('spouseContact'),
				'children1' => $this->input->post('children1'),



				'GuardianAddress' => $this->input->post('GuardianAddress'),
				'GuardianContact' => $this->input->post('GuardianContact'),
				'elementary' => $this->input->post('elementary'),
				'elementaryAddress' => $this->input->post('elementaryAddress'),
				'elemGraduated' => $this->input->post('elemGraduated'),
				'secondary' => $this->input->post('secondary'),
				'secondaryAddress' => $this->input->post('secondaryAddress'),
				'secondaryGraduated' => $this->input->post('secondaryGraduated'),

				'vocational' => $this->input->post('vocational'),
				'vocationalAddress' => $this->input->post('vocationalAddress'),
				'vocationalCourse' => $this->input->post('vocationalCourse'),
				'vocationalGraduated' => $this->input->post('vocationalGraduated'),
				'disability' => $this->input->post('disability'),
				'typedisability' => $this->input->post('typedisability'),
				'singleParent' => $this->input->post('singleParent'),
				'children' => $this->input->post('children'),



			);

			$this->StudentModel->updatestudentsignup($StudentNumber, $data);
			$this->session->set_flashdata('message', 'Record updated successfully');
			redirect("Page/studentSignup");
		}
	}







	public function printstudentsignup()
	{
		$StudentNumber = $this->input->get('StudentNumber');
		$result['data'] = $this->StudentModel->getstudentsignupbyId($StudentNumber);
		$result['data1'] = $this->StudentModel->o_srms_settings();

		// Retrieve sy and sem from the session
		$result['sy'] = $this->session->userdata('sy');
		$result['sem'] = $this->session->userdata('semester');

		// Load the view with the additional data
		$this->load->view('profile_page_print', $result);
	}



	public function printstudeProfile()
	{
		$StudentNumber = $this->input->get('StudentNumber');
		$result['data'] = $this->StudentModel->getstudentbyId($StudentNumber);
		$result['data1'] = $this->StudentModel->o_srms_settings();

		$result['sy'] = $this->session->userdata('sy');
		$result['sem'] = $this->session->userdata('semester');

		$this->load->view('stude_profile_print', $result);
	}

	public function copyData()
	{
		$this->load->database();
		$this->load->library('session');

		$id = $this->input->get('id');

		if (!$id) {
			$this->session->set_flashdata('error', 'No StudentNumber provided!');
			redirect(base_url('page/studentSignup'));
			return;
		}

		$signupData = $this->db->select('*')->where('StudentNumber', $id)->get('studentsignup')->row_array();

		if ($signupData) {
			$this->db->where('StudentNumber', $id);
			$existing = $this->db->get('studeprofile')->row_array();

			if (!$existing) {
				// Get settingsID from srms_settings table (default to 1 if not found)
				$settingsRow = $this->db->select('settingsID')->get('o_srms_settings')->row_array();
				$settingsID = $settingsRow['settingsID'] ?? 1;

				$profileData = [
					'StudentNumber' => $signupData['StudentNumber'],
					'FirstName' => $signupData['FirstName'],
					'MiddleName' => $signupData['MiddleName'],
					'LastName' => $signupData['LastName'],
					'nameExtn' => $signupData['nameExtn'],
					'Sex' => $signupData['Sex'],
					'birthDate' => $signupData['birthDate'],
					'age' => $signupData['age'],
					'BirthPlace' => $signupData['BirthPlace'],
					'contactNo' => $signupData['contactNo'],
					'email' => $signupData['email'],
					'CivilStatus' => $signupData['CivilStatus'],
					'ethnicity' => $signupData['ethnicity'],
					'Religion' => $signupData['Religion'],
					'working' => $signupData['working'],
					'VaccStat' => $signupData['VaccStat'],
					'province' => $signupData['province'],
					'city' => $signupData['city'],
					'brgy' => $signupData['brgy'],
					'sitio' => $signupData['sitio'],
					'course' => $signupData['Course1'],
					'Major' => $signupData['Major1'],
					'occupation' => $signupData['occupation'],
					'salary' => $signupData['salary'],
					'employer' => $signupData['employer'],
					'employerAddress' => $signupData['employerAddress'],
					'graduationDate' => $signupData['graduationDate'],
					'guardian' => $signupData['guardian'],
					'guardianRelationship' => $signupData['guardianRelationship'],
					'guardianContact' => $signupData['guardianContact'],
					'guardianAddress' => $signupData['guardianAddress'],
					'spouse' => $signupData['spouse'],
					'spouseRelationship' => $signupData['spouseRelationship'],
					'spouseContact' => $signupData['spouseContact'],
					'children' => $signupData['children'],
					'imagePath' => $signupData['imagePath'],
					'yearLevel' => $signupData['yearLevel'],
					'father' => $signupData['father'],
					'fOccupation' => $signupData['fOccupation'],
					'fatherAddress' => $signupData['fatherAddress'],
					'fatherContact' => $signupData['fatherContact'],
					'mother' => $signupData['mother'],
					'mOccupation' => $signupData['mOccupation'],
					'motherAddress' => $signupData['motherAddress'],
					'motherContact' => $signupData['motherContact'],
					'disability' => $signupData['disability'],
					'parentsMonthly' => $signupData['parentsMonthly'],
					'elementary' => $signupData['elementary'],
					'elementaryAddress' => $signupData['elementaryAddress'],
					'elemGraduated' => $signupData['elemGraduated'],
					'secondary' => $signupData['secondary'],
					'secondaryAddress' => $signupData['secondaryAddress'],
					'secondaryGraduated' => $signupData['secondaryGraduated'],
					'vocational' => $signupData['vocational'],
					'vocationalAddress' => $signupData['vocationalAddress'],
					'vocationalGraduated' => $signupData['vocationalGraduated'],
					'vocationalCourse' => $signupData['vocationalCourse'],
					'nationality' => $signupData['nationality'],
					'settingsID' => $settingsID // Include settingsID
				];

				$this->db->insert('studeprofile', $profileData);
				$this->session->set_flashdata('success', "Data for StudentNumber {$id} inserted successfully.");

				$this->db->where('StudentNumber', $id);
				$this->db->update('studentsignup', ['Status' => 'Confirmed']);

				$this->db->where('username', $id);
				$this->db->update('o_users', ['position' => 'Student']);
			} else {
				$this->session->set_flashdata('info', "StudentNumber {$id} already exists in studeprofile. Skipping insertion.");
			}
		} else {
			$this->session->set_flashdata('error', "No data found for StudentNumber {$id} in studentsignup!");
		}

		redirect(base_url("page/studentSignup?id={$id}"));
	}






	//Staff Profile
	function staffprofile()
	{
		if ($this->session->userdata('level') === 'Admin') {
			$id = $this->input->get('id');
		} elseif ($this->session->userdata('level') === 'HR Admin') {
			$id = $this->input->get('id');
		} else {
			$id = $this->session->userdata('username');
		}

		$result['data'] = $this->StudentModel->staffProfile($id);
		$result['data1'] = $this->StudentModel->profilepic($id);
		$result['data2'] = $this->PersonnelModel->family($id);
		$result['data3'] = $this->PersonnelModel->education($id);
		$result['data4'] = $this->PersonnelModel->cs($id);
		$result['data5'] = $this->PersonnelModel->trainings($id);
		$result['data6'] = $this->PersonnelModel->viewfiles($id);
		$this->load->view('profile_page_staff', $result);
	}

	function notification_error()
	{
		$this->load->view('notification_error');
	}

	function uploadrequirements()
	{
		if ($this->session->userdata('level') === 'Admin') {
			$id = $this->input->get('view');
		} else {
			$id = $this->session->userdata('username');
		}
		$result['data'] = $this->StudentModel->requirements($id);
		$this->load->view('upload_requirements', $result);
	}

	public function upload()
	{
		$config['upload_path'] = './upload/requirements/';
		// $config['allowed_types'] = '*';
		$config['allowed_types'] = 'pdf|jpeg|jpg|png';
		$config['max_size'] = 5120;
		//$config['max_width'] = 1500;
		//$config['max_height'] = 1500;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$msg = array('error' => $this->upload->display_errors());

			$this->load->view('uploadrequirements', $msg);
		} else {
			$data = array('image_metadata' => $this->upload->data());
			//get data from the form

			$StudentNumber = $this->input->post('StudentNumber');
			$email = $this->session->userdata('email');
			$FName = $this->session->userdata('fname');

			$filename = $this->upload->data('file_name');
			$docName = $this->input->post('docName');
			$date = date("Y-m-d");
			$que = $this->db->query("insert into online_requirements values('','$StudentNumber','$filename','$date','$docName')");

			if ($this->session->userdata('level') === 'Admin') {
				redirect('Page/profileList');
			} else {
				$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Uploaded Succesfully!</b></div>');
				//Email Notification
				$this->load->config('email');
				$this->load->library('email');
				$mail_message = 'Dear ' . $FName . ',' . "\r\n";
				$mail_message .= '<br><br>Thank you for submitting/uploading your requirements.' . "\r\n";

				$mail_message .= '<br><br>Thanks & Regards,';
				$mail_message .= '<br>SRMS - Online';

				$this->email->from('no-reply@lxeinfotechsolutions.com', 'SRMS Online Team')
					->to($email)
					->subject('Enrollment')
					->message($mail_message);
				$this->email->send();
				redirect('Page/studentsprofile');
			}
		}
	}



	function inventoryList()
	{
		// Load necessary data
		$data = [
			'data' => $this->StudentModel->getInventory(),
			'data1' => $this->StudentModel->inventorySummary(),
			'data2' => $this->StudentModel->getInventoryCategory(),
			'data3' => $this->StudentModel->getOffice(),
			'data4' => $this->StudentModel->getStaff(),
		];

		// Load the view with data
		$this->load->view('inventory_list1', $data);
	}


	function inventoryList1()
	{
		// Load necessary data
		$data = [
			'data' => $this->StudentModel->getInventory(),
			'data1' => $this->StudentModel->inventorySummary(),
			'data2' => $this->StudentModel->getInventoryCategory(),
			'data3' => $this->StudentModel->getOffice(),
			'data4' => $this->StudentModel->getStaff(),
			'data5' => $this->StudentModel->getBrand(),
		];

		// Load the view with data
		$this->load->view('inventory_Form', $data);

		// Check if form is submitted
		if ($this->input->post('submit')) {
			// Collect form data
			$formData = [
				'ctrlNo' => $this->input->post('ctrlNo'),
				'itemName' => $this->input->post('itemName'),
				'description' => $this->input->post('description'),
				'qty' => $this->input->post('qty'),
				'unit' => $this->input->post('unit'),
				'brand' => $this->input->post('brand') ?: '',
				'serialNo' => $this->input->post('serialNo'),
				'itemCondition' => $this->input->post('itemCondition'),
				'accountable' => $this->input->post('accountable'),
				'IDNumber' => $this->input->post('accountable'),
				'acquiredDate' => $this->input->post('acquiredDate'),
				'itemCategory' => $this->input->post('itemCategory'),
				'itemSubCategory' => $this->input->post('itemSubCategory'),
				'model' => $this->input->post('model'),
				'office' => $this->input->post('office'),

			];

			// Insert data into the database
			$this->db->insert('ls_items', array_merge($formData, ['settingsID' => 1]));

			// Set flash message and redirect
			$this->session->set_flashdata('success', 'Item successfully added!');
			redirect('Page/inventoryList');
		}
	}

	public function updateInventory()
	{
		// Get the itemID from the URL (use get if it's a query string)
		$itemID = $this->input->get('itemID');

		// Fetch the item details using the itemID
		$data['item'] = $this->StudentModel->display_itemsById($itemID);
		$data['data1'] = $this->StudentModel->inventorySummary();
		$data['data2'] = $this->StudentModel->getInventoryCategory();
		$data['data3'] = $this->StudentModel->getOffice();
		$data['data4'] = $this->StudentModel->getStaff();
		$data['data5'] = $this->StudentModel->getBrand();

		// Load the view with the fetched data
		$this->load->view('inventory_update_form', $data);

		// Check if the form is submitted (using 'update' as the submit button name)
		if ($this->input->post('update')) {
			// Get POST data from the form
			$ctrlNo = $this->input->post('ctrlNo');
			$itemName = $this->input->post('itemName');
			$description = $this->input->post('description');
			$qty = $this->input->post('qty');
			$unit = $this->input->post('unit');
			$brand = $this->input->post('brand');
			$serialNo = $this->input->post('serialNo');
			$itemCondition = $this->input->post('itemCondition');
			$accountable = $this->input->post('accountable');
			$acquiredDate = $this->input->post('acquiredDate');
			$itemCategory = $this->input->post('itemCategory');
			$itemSubCategory = $this->input->post('itemSubCategory');
			$model = $this->input->post('model');
			$office = $this->input->post('office');

			// Prepare the data array for updating
			$updatedData = array(
				'ctrlNo' => $ctrlNo,
				'itemName' => $itemName,
				'description' => $description,
				'qty' => $qty,
				'unit' => $unit,
				'brand' => $brand,
				'serialNo' => $serialNo,
				'itemCondition' => $itemCondition,
				'accountable' => $accountable,
				'acquiredDate' => $acquiredDate,
				'itemCategory' => $itemCategory,
				'itemSubCategory' => $itemSubCategory,
				'model' => $model,
				'office' => $office
			);

			// Assuming $itemID is passed to this controller method (you might need to get this from the URL or form)
			$itemID = $this->input->post('itemID'); // or from the URL if you are using URI segments

			// Call the model function to update the item
			$this->StudentModel->updateItem($itemID, $updatedData);

			// Set a flash message to notify the user of the successful update
			$this->session->set_flashdata('inventory', 'Record updated successfully');

			// Redirect to the inventory list page
			redirect("Page/inventoryList");
		}
	}














	function deleteInventoryItem($itemID)
	{
		date_default_timezone_set('Asia/Manila'); // This sets the timezone to Manila

		// Check if item ID is provided
		if (!$itemID) {
			$this->session->set_flashdata('error', 'No item selected for deletion.');
			redirect('Page/inventoryList');
			return;
		}

		// Fetch the item details to get the necessary data for the audit trail
		$this->db->where('itemID', $itemID);
		$item = $this->db->get('ls_items')->row();

		if ($item) {
			// Get the logged-in username from the session
			$username = $this->session->userdata('username'); // Adjust if the session key is different

			// Prepare data for the audit trail
			$auditData = [
				'atDesc' => 'Deleted item: ' . $item->itemName, // Adjust to include relevant item information
				'atDate' => date('Y-m-d'),  // Current date
				'atTime' => date('H:i:s'),  // Current time
				'atRes' => $username,       // Logged-in username as the result (who performed the deletion)
				'atSNo' => $item->ctrlNo,  // Serial number of the deleted item (if applicable)
			];

			// Insert the audit trail record
			$this->db->insert('atrail', $auditData);

			// Delete the item from the 'ls_items' table based on item ID
			$this->db->where('itemID', $itemID);
			$deleted = $this->db->delete('ls_items');

			// Check if the deletion was successful
			if ($deleted) {
				$this->session->set_flashdata('success', 'Item successfully deleted!');
			} else {
				$this->session->set_flashdata('error', 'Failed to delete item. Please try again.');
			}
		} else {
			// Handle the case where the item is not found
			$this->session->set_flashdata('error', 'Item not found.');
		}

		// Redirect back to the inventory list
		redirect('Page/inventoryList');
	}


	public function getSubcategories()
	{
		$category = $this->input->post('category');

		// Query to get subcategories for the selected category
		$this->db->select('Sub_category');
		$this->db->from('ls_categories');
		$this->db->where('Category', $category);
		$this->db->order_by('Sub_category', 'ASC');

		$query = $this->db->get();
		$subcategories = $query->result_array();

		// Return the subcategories as a JSON response
		if ($subcategories) {
			$result = array_column($subcategories, 'Sub_category');
			echo json_encode(['subcategories' => $result]);
		} else {
			echo json_encode(['subcategories' => null]);
		}
	}


	function DashboardinventoryList()
	{
		// Load all inventory data
		$allData = $this->StudentModel->getInventory();

		// Filter the data to show only items with itemCategory "Machinery and Equipment"
		$filteredData = array_filter($allData, function ($item) {
			return $item->itemCategory === "Machinery and Equipment";
		});

		// Prepare the filtered data to pass to the view
		$data = [
			'data' => $filteredData,
		];

		// Load the view with the filtered data
		$this->load->view('dashboard_p_custodianForm', $data);
	}

	function DashboardinventoryList1()
	{
		// Load all inventory data
		$allData = $this->StudentModel->getInventory();

		// Filter the data to show only items with itemCategory "Machinery and Equipment"
		$filteredData = array_filter($allData, function ($item) {
			return $item->itemCategory === "Transportation Equipment";
		});

		// Prepare the filtered data to pass to the view
		$data = [
			'data' => $filteredData,
		];

		// Load the view with the filtered data
		$this->load->view('dashboard_p_custodianForm1', $data);
	}

	function DashboardinventoryList2()
	{
		// Load all inventory data
		$allData = $this->StudentModel->getInventory();

		// Filter the data to show only items with itemCategory "Machinery and Equipment"
		$filteredData = array_filter($allData, function ($item) {
			return $item->itemCategory === "Furniture Fixtures and Books";
		});

		// Prepare the filtered data to pass to the view
		$data = [
			'data' => $filteredData,
		];

		// Load the view with the filtered data
		$this->load->view('dashboard_p_custodianForm2', $data);
	}

	function DashboardinventoryList3()
	{
		// Load all inventory data
		$allData = $this->StudentModel->getInventory();

		// Filter the data to show only items with itemCategory "Machinery and Equipment"
		$filteredData = array_filter($allData, function ($item) {
			return $item->itemCategory === "OTHERS";
		});

		// Prepare the filtered data to pass to the view
		$data = [
			'data' => $filteredData,
		];

		// Load the view with the filtered data
		$this->load->view('dashboard_p_custodianForm3', $data);
	}


	function inventoryAccountable()
	{
		$accountable = $this->input->get('accountable');
		$result['data'] = $this->StudentModel->getInventoryAccountable($accountable);
		$result['data1'] = $this->StudentModel->inventorySummaryAccountable($accountable);
		$this->load->view('inventory_list_accountable', $result);
	}

	// Profile List
	public function profileList()
	{
		// ✅ Use studentsignup instead of studeprofile
		$result['data'] = $this->StudentModel->getsignProfile();  // <— swap this line
		// (Optional) if your view's transfer modal needs a list:
		$result['prof'] = $result['data'];

		if ($this->input->post('submit')) {
			$StudentNumber  = $this->input->post('dataid', true);
			$Company        = $this->input->post('Company', true);
			$CompAddress    = $this->input->post('CompAddress', true);
			$Position       = $this->input->post('Position', true);
			$dateEmployed   = $this->input->post('dateEmployed', true);
			$classification = $this->input->post('classification', true);
			$income         = $this->input->post('income', true);

			$this->db->insert('employment', [
				'StudentNumber'  => $StudentNumber,
				'Company'        => $Company,
				'CompAddress'    => $CompAddress,
				'Position'       => $Position,
				'dateEmployed'   => $dateEmployed,
				'classification' => $classification,
				'income'         => $income
			]);

			// ⚠️ This updates studeprofile; keep it only if that row exists.
			// Otherwise, wrap it in a conditional or remove it if you're fully moving to signups.
			$this->db->where('StudentNumber', $StudentNumber)
				->update('studeprofile', ['empStat' => 'Employed']);

			redirect('Page/profileList');
		} else {
			$this->load->view('profile_list', $result);
		}
	}

	public function duplicateStudentsByName()
	{
		$result['data'] = $this->StudentModel->getDuplicateStudentsByName();
		$this->load->view('profile_duplicates', $result);
	}

	public function deleteDuplicateStudent()
	{
		$studno = trim((string)($this->input->post('student_number', true) ?: $this->input->post('id', true)));
		$username = trim((string)$this->input->post('username', true));
		$ref = $this->input->server('HTTP_REFERER') ?: 'Page/duplicateStudentsByName';

		if ($studno === '' && $username === '') {
			$this->session->set_flashdata('danger', 'No StudentNumber/username provided.');
			redirect($ref);
			return;
		}

		// Fallback key: if only one identity value is provided, use it for both.
		if ($studno === '') {
			$studno = $username;
		}
		if ($username === '') {
			$username = $studno;
		}

		$keys = array_values(array_unique(array_filter([
			trim($studno),
			trim($username)
		], static function ($v) {
			return $v !== '';
		})));

		$this->db->trans_start();

		// (1) Delete from studentsignup by StudentNumber (exact trimmed match).
		$aff_studentsignup = 0;
		foreach ($keys as $k) {
			$this->db->query(
				"DELETE FROM studentsignup
                 WHERE BINARY TRIM(StudentNumber) = BINARY TRIM(?)",
				[$k]
			);
			$aff_studentsignup += (int)$this->db->affected_rows();
		}

		// (2) Delete from studeprofile by StudentNumber (exact trimmed match).
		$aff_studeprofile = 0;
		foreach ($keys as $k) {
			$this->db->query(
				"DELETE FROM studeprofile
                 WHERE BINARY TRIM(StudentNumber) = BINARY TRIM(?)",
				[$k]
			);
			$aff_studeprofile += (int)$this->db->affected_rows();
		}

		// (3) Delete from o_users by username OR IDNumber (student accounts only).
		$aff_ousers = 0;
		foreach ($keys as $k) {
			$this->db->query(
				"DELETE FROM o_users
                 WHERE (
                        BINARY TRIM(username) = BINARY TRIM(?)
                        OR BINARY TRIM(IDNumber) = BINARY TRIM(?)
                       )
                   AND position IN ('Student', 'Stude Applicant')",
				[$k, $k]
			);
			$aff_ousers += (int)$this->db->affected_rows();
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->session->set_flashdata('danger', "Failed to delete StudentNumber {$studno}.");
			redirect($ref);
			return;
		}

		$total = $aff_studentsignup + $aff_studeprofile + $aff_ousers;
		if ($total === 0) {
			$this->session->set_flashdata(
				'danger',
				"No matching record found for StudentNumber/username {$studno}."
			);
			redirect($ref);
			return;
		}

		$this->session->set_flashdata(
			'success',
			"Deleted {$studno} (studentsignup: {$aff_studentsignup}, studeprofile: {$aff_studeprofile}, o_users: {$aff_ousers})."
		);
		redirect($ref);
	}



	function signUpList()
	{
		$result['data'] = $this->StudentModel->signUpList();
		$this->load->view('student_signup', $result);
	}

	function signUpListUpdate()
	{
		$result['data'] = $this->StudentModel->signUpList();
		$this->load->view('student_signup_update', $result);
	}


	public function deleteSignup()
	{
		// Require login
		if (!$this->session->userdata('username')) {
			$this->session->set_flashdata('message', 'Please log in.');
			redirect('Login');
			return;
		}

		// Role check (case-insensitive; supports multiple session keys)
		$role = strtolower(trim((string)(
			$this->session->userdata('level')
			?? $this->session->userdata('position')
			?? $this->session->userdata('role')
			?? ''
		)));
		$allowed = ['head registrar', 'registrar', 'assistant registrar', 'admin', 'administrator'];
		if (!in_array($role, $allowed, true)) {

			// AUDIT: unauthorized delete attempt
			$this->AuditLogModel->write(
				'delete',
				'Signup',
				'studentsignup',
				(string)$this->input->post('id', true) ?: null, // may be empty
				null,
				null,
				0,
				'Unauthorized attempt to delete signup',
				['by_role' => $role]
			);

			$this->session->set_flashdata('danger', 'Unauthorized: Registrar/Admin role required.');
			redirect($this->input->server('HTTP_REFERER') ?: 'Page/profileList');
			return;
		}


		// Inputs (POST)
		$studno       = trim((string)$this->input->post('id', true));   // StudentNumber
		$email        = trim((string)$this->input->post('email', true)); // optional legacy
		$return_level = trim((string)$this->input->post('return_level', true));

		if ($studno === '') {
			$this->session->set_flashdata('danger', 'No StudentNumber provided.');
			redirect($this->input->server('HTTP_REFERER') ?: 'Page/profileList');
			return;
		}

		// Start atomic delete across all related tables
		$this->db->trans_start();

		// (1) semesterstude
		$this->db->delete('semesterstude', ['StudentNumber' => $studno]);
		$aff_semesterstude = $this->db->affected_rows();

		// (2) studentsignup
		$this->db->delete('studentsignup', ['StudentNumber' => $studno]);
		$aff_studentsignup = $this->db->affected_rows();

		// (2b) studeprofile (if present)
		$this->db->delete('studeprofile', ['StudentNumber' => $studno]);
		$aff_studeprofile = $this->db->affected_rows();

		// (3) o_users (limit to student-type accounts so you don't remove staff by accident)
		$this->db->where('username', $studno)
			->group_start()
			->where('position', 'Student')
			->or_where('position', 'Stude Applicant')
			->group_end()
			->delete('o_users');
		$aff_users_by_username = $this->db->affected_rows();

		// Optional legacy cleanup by email
		$aff_users_by_email = 0;
		if ($email !== '') {
			$this->db->delete('o_users', ['email' => $email]);
			$aff_users_by_email = $this->db->affected_rows();
		}

		// (4) ✅ NEW: student_qr — remove any QR tokens for this student
		$this->db->delete('student_qr', ['student_number' => $studno]);
		$aff_student_qr = $this->db->affected_rows();

		// (4b) (Optional) remove QR image file if you store one like /upload/qr/{StudentNumber}.png
		// $qrPng = FCPATH . 'upload/qr/' . $studno . '.png';
		// if (is_file($qrPng)) { @unlink($qrPng); }

		$this->db->trans_complete();
		$ok = $this->db->trans_status();

		if (!$ok) {
			// AUDIT: delete failed
			$this->AuditLogModel->write(
				'delete',
				'Signup',
				'studentsignup',
				$studno,
				null,
				null,
				0,
				'Failed to delete signup record',
				[
					'email'                 => $email,
					'aff_semesterstude'     => $aff_semesterstude,
					'aff_studentsignup'     => $aff_studentsignup,
					'aff_studeprofile'      => $aff_studeprofile,
					'aff_users_by_username' => $aff_users_by_username,
					'aff_users_by_email'    => $aff_users_by_email,
					'aff_student_qr'        => $aff_student_qr
				]
			);

			$this->session->set_flashdata('danger', 'Delete failed. Please try again or check logs.');
		} else {
			// AUDIT: delete success
			$this->AuditLogModel->write(
				'delete',
				'Signup',
				'studentsignup',
				$studno,
				null,
				null,
				1,
				'Deleted signup record',
				[
					'email'                 => $email,
					'aff_semesterstude'     => $aff_semesterstude,
					'aff_studentsignup'     => $aff_studentsignup,
					'aff_studeprofile'      => $aff_studeprofile,
					'aff_users_by_username' => $aff_users_by_username,
					'aff_users_by_email'    => $aff_users_by_email,
					'aff_student_qr'        => $aff_student_qr
				]
			);

			$msg = sprintf(
				'Deleted %s — semesterstude:%d, studentsignup:%d, studeprofile:%d, o_users(user:%d,email:%d), student_qr:%d',
				htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'),
				$aff_semesterstude,
				$aff_studentsignup,
				$aff_studeprofile,
				$aff_users_by_username,
				$aff_users_by_email,
				$aff_student_qr
			);
			$this->session->set_flashdata('success', $msg);
		}


		// Redirect back
		if ($return_level) {
			redirect('Masterlist/byGradeYL?yearlevel=' . rawurlencode($return_level));
		} else {
			$ref = $this->input->server('HTTP_REFERER');
			redirect($ref ?: 'Page/profileList');
		}
	}


	//Profile List for Enrollment
	function profileForEnrollment()
	{
		$result['data'] = $this->StudentModel->getProfile();
		$this->load->view('profile_list_for_enrollment', $result);
	}
	//Contact Directory
	function studeDirectory()
	{
		$result['data'] = $this->StudentModel->getProfile();
		$this->load->view('contact_directory', $result);
	}

	function fetch_major()
	{

		if ($this->input->post('course')) {
			$output = '<option value=""></option>';
			$yearlevel = $this->StudentModel->getMajor($this->input->post('course'));
			foreach ($yearlevel as $row) {
				$output .= '<option value ="' . $row->Major . '">' . $row->Major . '</option>';
			}
			echo $output;
		}
	}
	function changepassword()
	{
		$this->load->view('change_pass');
	}

	function update_password()
	{

		$this->form_validation->set_rules('currentpassword', 'Current Password', 'required|trim|callback__validate_currentpassword');
		$this->form_validation->set_rules('newpassword', 'New Password', 'required|trim|min_length[8]|alpha_numeric');
		$this->form_validation->set_rules('cnewpassword', 'Confirm New Password', 'required|trim|matches[newpassword]');

		$this->form_validation->set_message('required', "Please fill-up the form completely!");
		if ($this->form_validation->run()) {

			$username = $this->session->userdata('username');
			$newpass = sha1($this->input->post('newpassword'));
			if ($this->StudentModel->reset_userpassword($username, $newpass)) {
				$this->session->set_flashdata('msg', '<div class="alert alert-success text-center">Succesfully changed password</div>');
				$this->load->view('change_pass');
			} else {
				echo "Error";
			}
		} else {
			$this->session->set_flashdata('msg', '');
			$this->load->view('change_pass');
		}
	}

	function _validate_currentpassword()
	{
		$username = $this->session->userdata('username');
		$currentpass = sha1($this->input->post('currentpassword'));
		if ($this->StudentModel->is_current_password($username, $currentpass)) {
			return TRUE;
		} else {
			$this->form_validation->set_message('_validate_currentpassword', 'Wrong Current Password');
			return FALSE;
		}
	}

	public function acceeptPayment()
	{
		$sem = $this->session->userdata('semester');
		$sy  = $this->session->userdata('sy');

		// view needs these
		$result['course'] = $this->StudentModel->getCourse();
		$result['data4']  = $this->StudentModel->forPaymentVerCount($sy, $sem);

		// show the form
		$this->load->view('payment_form', $result);

		// handle submit
		if (!$this->input->post('submit')) {
			return;
		}

		// ---------- collect form data ----------
		$email         = $this->input->post('email', true);   // student's email (hidden)
		$id            = $this->input->post('opID', true);    // online_payments.id (proof upload id)
		$StudentNumber = $this->input->post('StudentNumber', true);
		$FirstName     = $this->input->post('FirstName', true);
		$MiddleName    = $this->input->post('MiddleName', true);
		$LastName      = $this->input->post('LastName', true);
		$Course        = $this->input->post('Course', true);
		$YearLevel     = $this->input->post('YearLevel', true);

		$Sem           = $this->input->post('Sem', true);
		$SY            = $this->input->post('SY', true);

		$ORNumber      = $this->input->post('ORNumber', true);
		$Amount        = (float)$this->input->post('Amount', true);
		$description   = $this->input->post('description', true);
		$PaymentType   = $this->input->post('PaymentType', true);
		$CheckNumber   = $this->input->post('CheckNumber', true);
		$Bank          = $this->input->post('Bank', true);
		$refNo         = $this->input->post('refNo', true);   // separate reference number field

		// If refNo wasn't posted for some reason, try to get it from online_payments
		if (empty($refNo) && !empty($id)) {
			$row = $this->db->select('refNo')->from('online_payments')->where('id', $id)->get()->row();
			if ($row && !empty($row->refNo)) {
				$refNo = $row->refNo;
			}
		}

		$Cashier          = $this->session->userdata('username');
		$CollectionSource = "Student's Account";

		// Manila time
		$dtNow = new DateTime('now', new DateTimeZone('Asia/Manila'));
		$PDate = $dtNow->format('Y-m-d');
		$pTime = $dtNow->format('H:i:s');

		// ---------- duplicate OR check ----------
		$dup = $this->db->where('ORNumber', $ORNumber)
			->count_all_results('paymentsaccounts');
		if ($dup > 0) {
			$this->session->set_flashdata(
				'msg',
				'<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                <b>Duplicate O.R. Number.</b> Please enter a unique O.R. number.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>'
			);
			redirect('Page/acceeptPayment');
			return;
		}

		// ---------- TRANSACTION ----------
		$this->db->trans_start();

		// 1) Insert to paymentsaccounts (keep description clean; store refNo separately)
		$this->db->insert('paymentsaccounts', [
			'StudentNumber'     => $StudentNumber,
			'Course'            => $Course,
			'PDate'             => $PDate,
			'ORNumber'          => $ORNumber,
			'Amount'            => $Amount,
			'description'       => $description,
			'PaymentType'       => $PaymentType,
			'CheckNumber'       => $CheckNumber,
			'Sem'               => $Sem,
			'SY'                => $SY,
			'CollectionSource'  => $CollectionSource,
			'Bank'              => $Bank,
			'ORStatus'          => 'Valid',
			'Cashier'           => $Cashier,
			'pTime'             => $pTime,
			'refNo'             => $refNo
		]);

		// 2) Legacy flag
		$this->db->set('downPaymentStat', 'Paid')
			->set('downPayment', $Amount)
			->where('StudentNumber', $StudentNumber)
			->where('Semester', $Sem)
			->where('SY', $SY)
			->update('online_enrollment');

		// 3) Mark proof as Verified
		$this->db->set('status', 'Verified')
			->where('id', $id)
			->update('online_payments');

		// 4) Roll-up studeaccount
		$exists = $this->db->select('AccountID')
			->from('studeaccount')
			->where('StudentNumber', $StudentNumber)
			->where('Sem', $Sem)
			->where('SY', $SY)
			->limit(1)
			->get()->num_rows();

		if ($exists) {
			$amtSql = $this->db->escape($Amount);
			$this->db->set('TotalPayments', "TotalPayments + {$amtSql}", false);
			$this->db->set(
				'CurrentBalance',
				"GREATEST(AcctTotal - Discount - (TotalPayments + {$amtSql}), 0)",
				false
			);
			$this->db->where('StudentNumber', $StudentNumber)
				->where('Sem', $Sem)
				->where('SY', $SY)
				->update('studeaccount');
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === false) {
			$this->session->set_flashdata(
				'msg',
				'<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                <b>Something went wrong while saving the payment.</b> Please try again.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>'
			);
			redirect('Page/acceeptPayment');
			return;
		}

		// ===================== EMAIL NOTIFICATION (Payment Verified) =====================
		if (!empty($email)) {
			// Get school settings & letterhead
			$settings   = $this->db->get('o_srms_settings')->row();
			$schoolName = $settings && !empty($settings->SchoolName) ? $settings->SchoolName : 'School Records Management System';
			$letterhead = $settings && !empty($settings->letterhead_web)
				? base_url('upload/banners/' . $settings->letterhead_web)
				: null;

			// Student full name (from submitted form; fallback to DB)
			$studentFullName = trim(preg_replace('/\s+/', ' ', $FirstName . ' ' . ($MiddleName ?: '') . ' ' . $LastName));
			if ($studentFullName === '') {
				$sp = $this->db->select('FirstName, MiddleName, LastName')
					->from('studeprofile')
					->where('StudentNumber', $StudentNumber)
					->get()->row();
				$studentFullName = $sp
					? trim(preg_replace('/\s+/', ' ', $sp->FirstName . ' ' . ($sp->MiddleName ?? '') . ' ' . $sp->LastName))
					: $StudentNumber;
			}

			$createdDisp = $dtNow->format('M d, Y h:i A');
			$amountDisp  = '₱' . number_format((float)$Amount, 2);

			$subject = 'Payment Verified (OR ' . htmlspecialchars($ORNumber, ENT_QUOTES, 'UTF-8') . ')';

			$mail_message = '
<div style="font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;padding:24px;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #eceff4;">
    <tr>
      <td style="padding:10px 0;text-align:center;background:#ffffff;">' .
				($letterhead
					? '<img src="' . $letterhead . '" alt="School Letterhead" style="max-width:100%;height:auto;">'
					: '<h2 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Payment Verified</h2>'
				) .
				'</td>
    </tr>

    <tr>
      <td style="padding:24px;">
        <p style="margin:0 0 12px 0;font-size:14px;color:#111827;">
          Hello <strong>' . htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8') . '</strong>,
        </p>
        <p style="margin:0 0 16px 0;font-size:14px;color:#111827;">
          Your payment has been <strong style="color:#16a34a;">VERIFIED</strong>. Below are the details for your reference.
        </p>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;">
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Official Receipt No.</td>
            <td style="font-size:14px;color:#111827;"><strong>' . htmlspecialchars($ORNumber, ENT_QUOTES, 'UTF-8') . '</strong></td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Reference No.</td>
            <td style="font-size:14px;color:#111827;">' . htmlspecialchars($refNo, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Student Number</td>
            <td style="font-size:14px;color:#111827;">' . htmlspecialchars($StudentNumber, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Description</td>
            <td style="font-size:14px;color:#111827;">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Amount</td>
            <td style="font-size:14px;color:#111827;"><strong>' . $amountDisp . '</strong></td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">School Year / Term</td>
            <td style="font-size:14px;color:#111827;">' . htmlspecialchars($SY, ENT_QUOTES, 'UTF-8') . ' — ' . htmlspecialchars($Sem, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Payment Date</td>
            <td style="font-size:14px;color:#111827;">' . $createdDisp . ' (Asia/Manila)</td>
          </tr>
          <tr>
            <td style="width:40%;font-size:13px;color:#6b7280;">Status</td>
            <td style="font-size:14px;color:#16a34a;"><strong>VERIFIED</strong></td>
          </tr>
        </table>

        <p style="margin:20px 0 0 0;font-size:12px;color:#6b7280;">
          If you believe there is an error in these details, please contact the school cashier/finance office.
        </p>
      </td>
    </tr>

    <tr>
      <td style="padding:16px 24px;background:#f9fafb;border-top:1px solid #eceff4;color:#6b7280;font-size:12px;">
        ' . htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') . ' • This is an automated message, please do not reply.
      </td>
    </tr>
  </table>
</div>';

			// Send (robust)
			$this->load->config('email');
			$this->load->library('email');

			$emailConfig = $this->config->item('email'); // if SMTP config is an array
			if (is_array($emailConfig)) {
				$this->email->initialize($emailConfig);
			}
			if (method_exists($this->email, 'set_mailtype')) $this->email->set_mailtype('html');
			if (method_exists($this->email, 'set_newline'))  $this->email->set_newline("\r\n");
			if (method_exists($this->email, 'set_crlf'))     $this->email->set_crlf("\r\n");

			$this->email->clear(true);
			$this->email->from('no-reply@srmsportal.com', $schoolName);
			$this->email->to($email);
			$this->email->subject($subject);
			$this->email->message($mail_message);

			$sent = $this->email->send(false);
			if (!$sent) {
				$debug = $this->email->print_debugger(['headers', 'subject', 'body']);
				log_message('error', '[EmailFailure] acceeptPayment(): ' . $debug);

				// Plain text fallback
				$fallback = "Hello {$studentFullName},\n\n"
					. "Your payment has been VERIFIED. Details:\n\n"
					. "Official Receipt No.: {$ORNumber}\n"
					. "Reference No.: {$refNo}\n"
					. "Student Number: {$StudentNumber}\n"
					. "Description: {$description}\n"
					. "Amount: {$amountDisp}\n"
					. "School Year / Term: {$SY} — {$Sem}\n"
					. "Payment Date: {$createdDisp} (Asia/Manila)\n"
					. "Status: VERIFIED\n\n"
					. "{$schoolName} • This is an automated message, please do not reply.";
				$this->email->clear(true);
				if (is_array($emailConfig)) $this->email->initialize($emailConfig);
				if (method_exists($this->email, 'set_newline')) $this->email->set_newline("\r\n");
				if (method_exists($this->email, 'set_crlf'))    $this->email->set_crlf("\r\n");
				$this->email->from('no-reply@srmsportal.com', $schoolName);
				$this->email->to($email);
				$this->email->subject($subject);
				$this->email->message($fallback);
				$this->email->send();
			}
		}
		// ================== END EMAIL NOTIFICATION ==================

		// ===== SUCCESS FLASH MESSAGE =====
		$this->session->set_flashdata(
			'msg',
			'<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <b>The payment details have been processed successfully.</b>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>'
		);

		redirect('Page/proof_payment_view');
	}









	function profileEntry()
	{
		$data['ethnicity'] = $this->SettingsModel->get_ethnicity();
		$data['religion'] = $this->SettingsModel->get_religion();
		$data['prevschool'] = $this->SettingsModel->get_prevschool();

		$this->load->view('profile_form', $data);  // Pass the data to the view
		if ($this->input->post('submit')) {
			//get data from the form
			$StudentNumber = $this->input->post('StudentNumber');
			$FirstName = strtoupper($this->input->post('FirstName'));
			$MiddleName = strtoupper($this->input->post('MiddleName'));
			$LastName = strtoupper($this->input->post('LastName'));
			$nameExtn = $this->input->post('nameExtn');
			$completeName = $FirstName . ' ' . $LastName;
			$Religion = $this->input->post('Religion');
			$Sex = $this->input->post('Sex');
			$CivilStatus = $this->input->post('CivilStatus');
			$MobileNumber = $this->input->post('MobileNumber');
			$ethnicity = $this->input->post('ethnicity');
			$BirthDate = $this->input->post('BirthDate');
			$BirthPlace = $this->input->post('BirthPlace');
			//New
			$age = $this->input->post('age');

			//Parents
			$Father = $this->input->post('Father');
			$FOccupation = $this->input->post('FOccupation');
			$Mother = $this->input->post('Mother');
			$MOccupation = $this->input->post('MOccupation');
			//Guardian Info
			$guardian = $this->input->post('guardian');
			$guardianContact = $this->input->post('guardianContact');
			$guardianRelationship = $this->input->post('guardianRelationship');
			$guardianAddress = $this->input->post('guardianAddress');
			//Address
			$sitio = $this->input->post('sitio');
			$brgy = $this->input->post('brgy');
			$city = $this->input->post('city');
			$province = $this->input->post('province');
			$email = $this->input->post('email');
			$working = $this->input->post('working');
			$nationality = $this->input->post('nationality');

			$Encoder = $this->session->userdata('username');
			$updatedDate = date("Y-m-d");

			date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
			$now = date('H:i:s A');

			$AdmissionDate = date("Y-m-d");
			$GraduationDate = date("Y-m-d");
			$Password = sha1($this->input->post('BirthDate'));
			$Encoder = $this->session->userdata('username');

			//check if record exist
			$que = $this->db->query("select * from studeprofile where StudentNumber='" . $StudentNumber . "'");
			$row = $que->num_rows();
			if ($row) {
				//redirect('Page/notification_error');
				$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Student Number is in use.</b></div>');
				redirect('Page/profileList');
			} else {
				//save profile
				$que = $this->db->query("insert into studeprofile values('$StudentNumber','$FirstName','$MiddleName','$LastName','$Sex','$CivilStatus','$BirthPlace','$Religion','$email','$MobileNumber','$working','','','','','$BirthDate','$AdmissionDate','$GraduationDate','$guardian','$guardianRelationship','$guardianContact','$guardianAddress','','','','','','','','','$father','$fOccupation','','$mother','$mOccupation','','','','$age','','','','','','$ethnicity','$fourPs','','','','','$province','$city','$brgy','$province','$city','$brgy','$sitio','','','','','','','','','','','','','','','','','','','','1','','','$AdmissionDate','$Encoder','','','','','','','','','','','$nameExtn','','','$nationality')");
				$que = $this->db->query("insert into o_users values('$StudentNumber','$Password','Student','$FirstName','$MiddleName','$LastName','email','avatar.png','Active','$AdmissionDate','$completeName','$StudentNumber')");
				$que = $this->db->query("insert into profimage values('','','$StudentNumber')");
				$que = $this->db->query("insert into atrail values('','Created Student''s Profile and User Account','$AdmissionDate','$now','$Encoder','$StudentNumber')");
				$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Profile has been saved successfully.</b></div>');

				//Email Notification
				$this->load->config('email');
				$this->load->library('email');
				$mail_message = 'Dear ' . $FirstName . ',' . "\r\n";
				$mail_message .= '<br><br>Your profile is now encoded to SRMS. Please take note of the following:' . "\r\n";
				$mail_message .= '<br>Username: <b>' . $StudentNumber . '</b>' . "\r\n";
				$mail_message .= '<br>Password: <b>' . $BirthDate . '</b>' . "\r\n";

				$mail_message .= '<br><br>Thanks & Regards,';
				$mail_message .= '<br>SRMS - Online';

				$this->email->from('no-reply@srmsportal.com', 'SRMS Online Team')
					->to($email)
					->subject('Account Created')
					->message($mail_message);
				$this->email->send();

				redirect('Page/profileList');
			}
		}
	}
	public function personnelEntry()
	{
		$schoolName = $this->SettingsModel->getSchoolName();

		if ($this->input->post('submit')) {
			// Sanitize and format input
			$IDNumber    = $this->input->post('IDNumber', true);
			$FirstName   = strtoupper($this->input->post('FirstName', true));
			$MiddleName  = strtoupper($this->input->post('MiddleName', true));
			$LastName    = strtoupper($this->input->post('LastName', true));
			$NameExtn    = $this->input->post('NameExtn', true);
			$prefix      = $this->input->post('prefix', true);
			$Sex         = $this->input->post('Sex', true);
			$BirthDate   = $this->input->post('BirthDate', true);
			$age          = $this->input->post('age', true);
			$BirthPlace  = $this->input->post('BirthPlace', true);
			$MaritalStatus = $this->input->post('MaritalStatus', true);
			$height      = $this->input->post('height', true);
			$weight      = $this->input->post('weight', true);
			$bloodType   = $this->input->post('bloodType', true);
			$empTelNo    = $this->input->post('empTelNo', true);
			$empMobile   = $this->input->post('empMobile', true);
			$empEmail    = $this->input->post('empEmail', true);

			$empPosition = $this->input->post('empPosition', true);
			$Department  = $this->input->post('Department', true);
			$empStatus   = $this->input->post('empStatus', true);
			$dateHired   = $this->input->post('dateHired', true);
			$gsis        = $this->input->post('gsis', true);
			$pagibig     = $this->input->post('pagibig', true);
			$philHealth  = $this->input->post('philHealth', true);
			$sssNo       = $this->input->post('sssNo', true);
			$tinNo       = $this->input->post('tinNo', true);
			$resHouseNo  = $this->input->post('resHouseNo', true);
			$resStreet   = $this->input->post('resStreet', true);
			$resVillage  = $this->input->post('resVillage', true);
			$resBarangay = $this->input->post('resBarangay', true);
			$resCity     = $this->input->post('resCity', true);
			$resProvince = $this->input->post('resProvince', true);
			$resZipCode  = $this->input->post('resZipCode', true);

			// Check duplicate IDNumber
			$exists = $this->db->get_where('staff', ['IDNumber' => $IDNumber])->num_rows();
			if ($exists) {
				$this->session->set_flashdata('danger', 'A duplicate employee number was found.');
				redirect('Page/employeeList');
				return;
			}

			// Insert data
			$data = [
				'IDNumber'      => $IDNumber,
				'FirstName'     => $FirstName,
				'MiddleName'    => $MiddleName,
				'LastName'      => $LastName,
				'NameExtn'      => $NameExtn,
				'prefix'        => $prefix,
				'empPosition'   => $empPosition,
				'Department'    => $Department,
				'MaritalStatus' => $MaritalStatus,
				'empStatus'     => $empStatus,
				'BirthDate'     => $BirthDate,
				'age'     		=> $age,
				'BirthPlace'    => $BirthPlace,
				'Sex'           => $Sex,
				'height'        => $height,
				'weight'        => $weight,
				'bloodType'     => $bloodType,
				'gsis'          => $gsis,
				'pagibig'       => $pagibig,
				'philHealth'    => $philHealth,
				'sssNo'         => $sssNo,
				'tinNo'         => $tinNo,
				'dateHired'     => $dateHired,
				'resHouseNo'    => $resHouseNo,
				'resStreet'     => $resStreet,
				'resVillage'    => $resVillage,
				'resBarangay'   => $resBarangay,
				'resCity'       => $resCity,
				'resProvince'   => $resProvince,
				'resZipCode'    => $resZipCode,
				'perHouseNo'    => $resHouseNo,
				'perStreet'     => $resStreet,
				'perVillage'    => $resVillage,
				'perBarangay'   => $resBarangay,
				'perCity'       => $resCity,
				'perProvince'   => $resProvince,
				'perZipCode'    => $resZipCode,
				'empTelNo'      => $empTelNo,
				'empMobile'     => $empMobile,
				'empEmail'      => $empEmail,
				'settingsID'    => 1
			];

			$this->db->insert('staff', $data);
			$this->session->set_flashdata('success', 'One record added successfully.');

			// Save to audit trail
			$this->db->insert('atrail', [
				'atDesc' => 'Added new personnel record',
				'atDate' => date('Y-m-d'),
				'atTime' => date('H:i:s'),
				'atRes'  => $this->session->userdata('username'),
				'atSNo'  => $IDNumber
			]);

			// Insert to o_users
			$this->db->insert('o_users', [
				'username'   => $IDNumber,
				'password'   => sha1($BirthDate),
				'position'   => 'Instructor',
				'fName'      => $FirstName,
				'mName'      => $MiddleName,
				'lName'      => $LastName,
				'email'      => $empEmail,
				'avatar'     => 'avatar.png',
				'acctStat'   => 'active',
				'dateCreated' => date('Y-m-d'),
				'IDNumber'   => $IDNumber
			]);


			// Load email settings
			$this->load->config('email');
			$this->load->library('email');
			$this->email->set_mailtype("html"); // Ensure HTML format

			$mail_message = '
			<div style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; color: #333;">
				<div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.05); padding: 30px;">
					<h2 style="color: #2b6cb0;">Welcome to ' . htmlspecialchars($schoolName) . '</h2>
					<p>Dear <strong>' . htmlspecialchars($FirstName) . '</strong>,</p>
			
					<p>Your account has been created in the system. Here are your login credentials:</p>
			
					<table style="width: 100%; max-width: 400px; border-collapse: collapse; margin-bottom: 20px;">
						<tr>
							<td style="padding: 8px; background: #f1f1f1; border: 1px solid #ccc;"><strong>Username</strong></td>
							<td style="padding: 8px; border: 1px solid #ccc;">' . htmlspecialchars($IDNumber) . '</td>
						</tr>
						<tr>
							<td style="padding: 8px; background: #f1f1f1; border: 1px solid #ccc;"><strong>Password</strong></td>
							<td style="padding: 8px; border: 1px solid #ccc;">' . htmlspecialchars($BirthDate) . '</td>
						</tr>
					</table>
			
					<p>You may log in using the button below:</p>
					<p>
						<a href="' . base_url('login') . '" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;">
							Go to Login Page
						</a>
					</p>
			
					<p style="margin-top: 30px;">Best regards,<br>
					<strong>' . htmlspecialchars($schoolName) . ' Online Team</strong></p>
			
					<hr style="margin-top: 40px;">
					<p style="font-size: 12px; color: #999;">This is an automated message. Please do not reply to this email.</p>
				</div>
			</div>';


			$this->email->from('no-reply@srmsportal.com', $schoolName . ' Online Team');
			$this->email->to($empEmail);
			$this->email->subject('Your ' . $schoolName . ' Account Has Been Created');
			$this->email->message($mail_message);
			@$this->email->send();


			redirect('Page/employeeList');
		}

		// Load view for data entry if no submission yet
		$this->load->view('hr_personnel_profile_form');
	}


	//Update Personnel Profile
	public function updatePersonnelProfile()
	{
		if ($this->session->userdata('level') === 'Admin' || $this->session->userdata('level') === 'HR Admin') {
			$id = $this->input->get('id');
		} else {
			$id = $this->session->userdata('IDNumber');
		}

		// Load existing data
		$result['data'] = $this->StudentModel->staffProfile($id);
		$this->load->view('hr_personnel_profile_update_form', $result);

		// On form submit
		if ($this->input->post('submit')) {
			// Collect inputs
			$OldIDNumber = $this->input->post('OldIDNumber', true);
			$IDNumber    = $this->input->post('IDNumber', true);
			$FirstName   = strtoupper($this->input->post('FirstName', true));
			$MiddleName  = strtoupper($this->input->post('MiddleName', true));
			$LastName    = strtoupper($this->input->post('LastName', true));
			$NameExtn    = $this->input->post('NameExtn', true);
			$prefix      = $this->input->post('prefix', true);
			$Sex         = $this->input->post('Sex', true);
			$BirthDate   = $this->input->post('BirthDate', true);
			$BirthPlace  = $this->input->post('BirthPlace', true);
			$MaritalStatus = $this->input->post('MaritalStatus', true);
			$height      = $this->input->post('height', true);
			$weight      = $this->input->post('weight', true);
			$bloodType   = $this->input->post('bloodType', true);
			$empTelNo    = $this->input->post('empTelNo', true);
			$empMobile   = $this->input->post('empMobile', true);
			$empEmail    = $this->input->post('empEmail', true);
			// $fb          = $this->input->post('fb', true);
			// $skype       = $this->input->post('skype', true);
			// $citizenship = $this->input->post('citizenship', true);
			// $dualCitizenship = $this->input->post('dualCitizenship', true);
			// $citizenshipType = $this->input->post('citizenshipType', true);
			// $citizenshipCountry = $this->input->post('citizenshipCountry', true);
			$empPosition = $this->input->post('empPosition', true);
			$Department  = $this->input->post('Department', true);
			$empStatus   = $this->input->post('empStatus', true);
			// $agencyCode  = $this->input->post('agencyCode', true);
			$dateHired   = $this->input->post('dateHired', true);
			// $retYear     = $this->input->post('retYear', true);
			$gsis        = $this->input->post('gsis', true);
			$pagibig     = $this->input->post('pagibig', true);
			$philHealth  = $this->input->post('philHealth', true);
			$sssNo       = $this->input->post('sssNo', true);
			$tinNo       = $this->input->post('tinNo', true);
			// $contactName = $this->input->post('contactName', true);
			// $contactRel  = $this->input->post('contactRel', true);
			// $contactEmail = $this->input->post('contactEmail', true);
			// $contactNo    = $this->input->post('contactNo', true);
			// $contactAddress = $this->input->post('contactAddress', true);
			$resHouseNo  = $this->input->post('resHouseNo', true);
			$resStreet   = $this->input->post('resStreet', true);
			$resVillage  = $this->input->post('resVillage', true);
			$resBarangay = $this->input->post('resBarangay', true);
			$resCity     = $this->input->post('resCity', true);
			$resProvince = $this->input->post('resProvince', true);
			$resZipCode  = $this->input->post('resZipCode', true);
			$age         = $this->input->post('age', true);

			$Encoder = $this->session->userdata('username');
			date_default_timezone_set('Asia/Manila');
			$now  = date('H:i:s');
			$date = date('Y-m-d');

			// Update staff table
			$staffData = [
				'IDNumber'         => $IDNumber,
				'FirstName'        => $FirstName,
				'MiddleName'       => $MiddleName,
				'LastName'         => $LastName,
				'NameExtn'         => $NameExtn,
				'prefix'           => $prefix,
				'empPosition'      => $empPosition,
				'Department'       => $Department,
				'MaritalStatus'    => $MaritalStatus,
				'empStatus'        => $empStatus,
				'BirthDate'        => $BirthDate,
				'BirthPlace'       => $BirthPlace,
				'Sex'              => $Sex,
				'height'           => $height,
				'weight'           => $weight,
				'bloodType'        => $bloodType,
				'gsis'             => $gsis,
				'pagibig'          => $pagibig,
				'philHealth'       => $philHealth,
				'sssNo'            => $sssNo,
				'tinNo'            => $tinNo,
				'resHouseNo'       => $resHouseNo,
				'resStreet'        => $resStreet,
				'resVillage'       => $resVillage,
				'resBarangay'      => $resBarangay,
				'resCity'          => $resCity,
				'resProvince'      => $resProvince,
				'resZipCode'       => $resZipCode,
				'perHouseNo'       => $resHouseNo,
				'perStreet'        => $resStreet,
				'perVillage'       => $resVillage,
				'perBarangay'      => $resBarangay,
				'perCity'          => $resCity,
				'perProvince'      => $resProvince,
				'perZipCode'       => $resZipCode,
				'empTelNo'         => $empTelNo,
				'empMobile'        => $empMobile,
				'empEmail'         => $empEmail,
				'age'              => $age,
				'dateHired'        => $dateHired,
				// 'retYear'          => $retYear,
				// 'agencyCode'       => $agencyCode,
				// 'citizenship'      => $citizenship,
				// 'dualCitizenship'  => $dualCitizenship,
				// 'citizenshipType'  => $citizenshipType,
				// 'citizenshipCountry' => $citizenshipCountry,
				// 'contactName'      => $contactName,
				// 'contactRel'       => $contactRel,
				// 'contactEmail'     => $contactEmail,
				// 'contactNo'        => $contactNo,
				// 'contactAddress'   => $contactAddress,
				// 'fb'               => $fb,
				// 'skype'            => $skype
			];

			$this->db->where('IDNumber', $OldIDNumber);
			$this->db->update('staff', $staffData);

			// Update user table (optional)
			// Update user table (update both username and IDNumber)
			$this->db->where('username', $OldIDNumber);
			$this->db->update('o_users', [
				'username' => $IDNumber,   // ✅ also update the username
				'fName'    => $FirstName,
				'mName'    => $MiddleName,
				'lName'    => $LastName,
				'email'    => $empEmail,
				'IDNumber' => $IDNumber
			]);

			$this->db->where('IDNumber', $OldIDNumber);
			$this->db->update('registration', [
				'IDNumber' => $IDNumber
			]);

			// Save audit trail
			$this->db->insert('atrail', [
				'atDesc' => 'Updated Personnel Profile',
				'atDate' => $date,
				'atTime' => $now,
				'atRes'  => $Encoder,
				'atSNo'  => $OldIDNumber
			]);

			$this->session->set_flashdata('success', '<div id="sa-success" class="alert alert-success text-center"><b>Updated successfully.</b></div>');

			$userPosition = $this->session->userdata('level');

			if (in_array($userPosition, ['Admin', 'HR Admin'])) {
				redirect('Page/employeeList');
			} else {
				redirect($this->agent->referrer());
			}
		}
	}

	// Page.php (controller)

	public function changeDP()
	{
		$this->load->view('upload_profile_pic');
	}

	public function uploadProfPic()
	{
		$username = (string) $this->session->userdata('username');
		if ($username === '') {
			redirect('login');
			return;
		}

		$config = [
			'upload_path'      => FCPATH . 'upload/profile/',
			'allowed_types'    => 'jpg|jpeg|png|gif',
			'max_size'         => 2048,
			'file_ext_tolower' => TRUE,
			'encrypt_name'     => TRUE,
			'remove_spaces'    => TRUE,
		];
		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$this->session->set_flashdata(
				'msg',
				'<div class="alert alert-danger text-center">' . $this->upload->display_errors('', '') . '</div>'
			);
			redirect('Page/changeDP');
			return;
		}

		$filename = $this->upload->data('file_name');

		// get current avatar (from users first; if not there try o_users)
		$row = $this->db->select('avatar')->from('users')->where('username', $username)->get()->row();
		if (!$row) {
			$row = $this->db->select('avatar')->from('o_users')->where('username', $username)->get()->row();
		}
		if ($row && $row->avatar && strtolower($row->avatar) !== 'avatar.png') {
			$old = FCPATH . 'upload/profile/' . $row->avatar;
			if (is_file($old)) @unlink($old);
		}

		// update whichever table the user exists in
		$this->db->where('username', $username)->update('users', ['avatar' => $filename]);
		if ($this->db->affected_rows() === 0) {
			$this->db->where('username', $username)->update('o_users', ['avatar' => $filename]);
		}

		// refresh session so UI shows the new image immediately
		$this->session->set_userdata('avatar', $filename);

		$this->session->set_flashdata(
			'msg',
			'<div class="alert alert-success text-center"><b>Uploaded successfully.</b></div>'
		);
		redirect('Page/changeDP');
	}



	public function addNewStudent()
	{
		$data['provinces'] = $this->StudentModel->get_provinces();
		$data['scholar'] = $this->StudentModel->get_Scholar();
		$data['prevschool'] = $this->StudentModel->get_prevSchool();
		$data['ethnicity'] = $this->SettingsModel->get_ethnicity();
		$data['religion'] = $this->SettingsModel->get_religion();
		$settingsID = $this->SettingsModel->get_active_settings_id();

		if ($this->session->userdata('level') === 'Student') {
			$id = $this->session->userdata('username');
		} else {
			$id = $this->input->get('id');
		}

		if ($this->input->post('submit')) {
			// ✅ Generate Student Number only on submit
			$studentNumber = $this->StudentModel->generate_student_number();

			// Handle checkbox copying
			$fatherAddress = $this->input->post('fatherAddress');
			$motherAddress = $this->input->post('motherAddress');
			if (empty($motherAddress) && $this->input->post('copyAddressCheckbox') === 'on') {
				$motherAddress = $fatherAddress;
			}

			// Prepare student profile data
			$profileData = [
				'StudentNumber' => $studentNumber,
				'FirstName'     => strtoupper(trim($this->input->post('FirstName'))),
				'MiddleName'    => strtoupper(trim($this->input->post('MiddleName'))),
				'LastName'      => strtoupper(trim($this->input->post('LastName'))),
				'nameExtn'      => strtoupper(trim($this->input->post('nameExtn'))),
				'Sex'           => $this->input->post('Sex'),
				'CivilStatus'   => $this->input->post('CivilStatus'),
				'Religion'      => $this->input->post('Religion'),
				'Ethnicity'     => $this->input->post('Ethnicity'),
				'contactNo'     => $this->input->post('contactNo'),
				'birthDate'     => $this->input->post('birthDate'),
				'BirthPlace'    => $this->input->post('BirthPlace'),
				'Age'           => $this->input->post('Age'),
				'Father'        => $this->input->post('Father'),
				'FOccupation'   => $this->input->post('FOccupation'),
				'Mother'        => $this->input->post('Mother'),
				'MOccupation'   => $this->input->post('MOccupation'),
				'Guardian'      => $this->input->post('Guardian'),
				'GuardianContact' => $this->input->post('GuardianContact'),
				'GuardianRelationship' => $this->input->post('GuardianRelationship'),
				'GuardianAddress' => $this->input->post('GuardianAddress'),
				'Sitio'         => $this->input->post('Sitio'),
				'Brgy'          => $this->input->post('Brgy'),
				'City'          => $this->input->post('City'),
				'Province'      => $this->input->post('Province'),
				'sitioPresent'  => $this->input->post('Sitio'),
				'brgyPresent'   => $this->input->post('Brgy'),
				'cityPresent'   => $this->input->post('City'),
				'provincePresent' => $this->input->post('Province'),
				'email'         => $this->input->post('email'),
				'working'       => $this->input->post('working'),
				'nationality'   => $this->input->post('nationality'),
				'settingsID'    => $settingsID,
				'encoder'       => $this->session->userdata('username')
			];

			// Create user account
			$userData = [
				'username'    => $studentNumber,
				'password'    => sha1($profileData['birthDate']),
				'fname'       => $profileData['FirstName'],
				'mname'       => $profileData['MiddleName'],
				'lname'       => $profileData['LastName'],
				'position'    => 'Student',
				'email'       => $profileData['email'],
				'avatar'      => 'avatar.png',
				'acctStat'    => 'active',
				'dateCreated' => date('Y-m-d'),
				'name'        => $profileData['FirstName'] . ' ' . $profileData['MiddleName'] . ' ' . $profileData['LastName'],
				'IDNumber'    => $studentNumber
			];

			$this->StudentModel->insert_user_account($userData);
			$this->StudentModel->insert_profile($profileData);

			// ✅ Flash success message
			$this->session->set_flashdata('success', 'Student profile created successfully. <br>Student Number: <strong>' . $studentNumber . '</strong>');

			// Redirect to list
			if ($this->session->userdata('level') === 'Encoder') {
				redirect('Page/profileListEncoder');
			} else {
				redirect('Page/profileList');
			}
		}

		// Load form view
		$this->load->view('profile_form_new', $data);
	}





	// Fetch all provinces
	public function get_provinces()
	{
		$provinces = $this->StudentModel->get_provinces();
		echo json_encode($provinces);
	}

	// Fetch cities based on selected province
	public function get_cities()
	{
		$province = $this->input->post('province'); // Ensure province is received
		if (!$province) {
			echo json_encode(['error' => 'Invalid province selected']);
			return;
		}

		$cities = $this->StudentModel->get_cities($province);
		echo json_encode($cities);
	}

	// Fetch barangays based on selected city
	public function get_barangays()
	{
		$city = $this->input->post('city'); // Ensure city is received
		if (!$city) {
			echo json_encode(['error' => 'Invalid city selected']);
			return;
		}

		$barangays = $this->StudentModel->get_barangays($city);
		echo json_encode($barangays);
	}


	public function encoder()
	{
		if ($this->session->userdata('level') === 'Encoder') {
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');
			$username = $this->session->userdata('username'); // Get current encoder

			$result['data18'] = $this->SettingsModel->getSchoolInfo();
			$result['profileCount'] = $this->StudentModel->countProfilesByEncoder($username);

			$this->load->view('dashboard_encoder', $result);
		} else {
			echo "Access Denied";
		}
	}

	public function updateStudeProfile()
	{
		// Get the dropdown data
		$result['provinces'] = $this->StudentModel->get_provinces();
		$result['scholar'] = $this->StudentModel->get_Scholar();
		$result['prevschool'] = $this->StudentModel->get_prevSchool();

		// Get the student ID
		if ($this->session->userdata('level') === 'Student') {
			$id = $this->session->userdata('username');
		} else {
			$id = $this->input->get('id');
		}

		// Display the student record by ID
		$result['data'] = $this->StudentModel->displayrecordsById($id);
		$this->load->view('profile_form_update', $result);

		if ($this->input->post('submit')) {
			// Get form data
			$data = array(
				'StudentNumber'         => $this->input->post('StudentNumber'),
				'FirstName'             => $this->input->post('FirstName'),
				'MiddleName'            => $this->input->post('MiddleName'),
				'LastName'              => $this->input->post('LastName'),
				'nameExtn'              => $this->input->post('nameExtn'),
				'Religion'              => $this->input->post('Religion'),
				'Sex'                   => $this->input->post('Sex'),
				'CivilStatus'           => $this->input->post('CivilStatus'),
				'contactNo'             => $this->input->post('contactNo'),
				'Ethnicity'             => $this->input->post('Ethnicity'),
				'birthDate'             => $this->input->post('birthDate'),
				'Age'                   => $this->input->post('Age'),
				'BirthPlace'            => $this->input->post('BirthPlace'),
				'email'                 => $this->input->post('email'),
				'working'               => $this->input->post('working'),
				'nationality'           => $this->input->post('nationality'),
				'Province'              => $this->input->post('Province'),
				'City'                  => $this->input->post('City'),
				'Brgy'                  => $this->input->post('Brgy'),
				'Sitio'                 => $this->input->post('Sitio'),
				'Father'                => $this->input->post('Father'),
				'FOccupation'           => $this->input->post('FOccupation'),
				'fatherContact'         => $this->input->post('fatherContact'),
				'fatherBDate'           => $this->input->post('fatherBDate'),
				'fatherAge'             => $this->input->post('fatherAge'),
				'fatherAddress'         => $this->input->post('fatherAddress'),
				'Mother'                => $this->input->post('Mother'),
				'MOccupation'           => $this->input->post('MOccupation'),
				'motherContact'         => $this->input->post('motherContact'),
				'motherBDate'           => $this->input->post('motherBDate'),
				'motherAge'             => $this->input->post('motherAge'),
				'motherAddress'         => $this->input->post('motherAddress'),
				'parentsMonthly'        => $this->input->post('parentsMonthly'),
				'Guardian'              => $this->input->post('Guardian'),
				'GuardianRelationship'  => $this->input->post('GuardianRelationship'),
				'GuardianAddress'       => $this->input->post('GuardianAddress'),
				'GuardianContact'       => $this->input->post('GuardianContact'),
				'disability'            => $this->input->post('disability'),
				'occupation'            => $this->input->post('occupation'),
				'salary'                => $this->input->post('salary'),
				'employer'              => $this->input->post('employer'),
				'employerAddress'       => $this->input->post('employerAddress'),
				'scholarship'           => $this->input->post('scholarship'),
				'VaccStat'              => $this->input->post('VaccStat'),
				'fourPs'                => $this->input->post('fourPs'),
				'4psNo'                 => $this->input->post('4psNo'),
				'seniorCitizen'         => $this->input->post('seniorCitizen'),
				'als'                   => $this->input->post('als'),
				'elementary'            => $this->input->post('elementary'),
				'elementaryAddress'     => $this->input->post('elementaryAddress'),
				'elemGraduated'         => $this->input->post('elemGraduated'),
				'elemMerits'            => $this->input->post('elemMerits'),
				'secondary'             => $this->input->post('secondary'),
				'secondaryAddress'      => $this->input->post('secondaryAddress'),
				'secondaryGraduated'    => $this->input->post('secondaryGraduated'),
				'secondaryMerits'       => $this->input->post('secondaryMerits'),
				'SHS'                   => $this->input->post('SHS'),
				'SHSaddress'            => $this->input->post('SHSaddress'),
				'SHSgraduated'          => $this->input->post('SHSgraduated'),
				'SHSstrand'             => $this->input->post('SHSstrand'),
				'SHSmerits'             => $this->input->post('SHSmerits'),
				'vocational'            => $this->input->post('vocational'),
				'vocationaladdress'     => $this->input->post('vocationaladdress'),
				'vocationalGraduated'   => $this->input->post('vocationalGraduated'),
				'vocationalCourse'      => $this->input->post('vocationalCourse'),
				'ncLevel'               => $this->input->post('ncLevel'),
				'lastAttended'          => $this->input->post('lastAttended'),
				'lastSchoolDate'        => $this->input->post('lastSchoolDate'),
				'transfereeSchool'      => $this->input->post('transfereeSchool'),
				'transfereeAddress'     => $this->input->post('transfereeAddress'),
				'transfereeGraduated'   => $this->input->post('transfereeGraduated'),
				'transfereeCourse'      => $this->input->post('transfereeCourse'),
				'skills'                => $this->input->post('skills'),
				'honors'                => $this->input->post('honors'),
				'rotcSerial'            => $this->input->post('rotcSerial'),
				'cwtsSerial'            => $this->input->post('cwtsSerial'),
				'admissionBasis'        => $this->input->post('admissionBasis'),
				'admissionSem'          => $this->input->post('admissionSem'),
				'admissionSY'           => $this->input->post('admissionSY'),
				'Encoder'               => $this->session->userdata('username')
			);

			// Get old StudentNumber for updating
			$oldStudentNo = $this->input->post('oldStudentNo');

			// Save the profile update
			$this->db->where('StudentNumber', $oldStudentNo);
			$this->db->update('studeprofile', $data);

			// Update other tables similarly
			$this->db->where('StudentNumber', $oldStudentNo);
			$this->db->update('semesterstude', array('StudentNumber' => $this->input->post('StudentNumber')));

			$this->db->where('StudentNumber', $oldStudentNo);
			$this->db->update('paymentsaccounts', array('StudentNumber' => $this->input->post('StudentNumber')));

			$this->db->where('StudentNumber', $oldStudentNo);
			$this->db->update('studeaccount', array('StudentNumber' => $this->input->post('StudentNumber')));

			$this->db->where('username', $oldStudentNo);
			$this->db->update('o_users', array('username' => $this->input->post('StudentNumber'), 'fName' => $this->input->post('FirstName'), 'mName' => $this->input->post('MiddleName'), 'lName' => $this->input->post('LastName'), 'email' => $this->input->post('email')));

			// // Log update in audit trail
			// $updatedDate = date("Y-m-d");
			// $updatedTime = date("h:i:s A");
			// $this->db->insert('atrail', array('action' => 'Updated Profile', 'date' => $updatedDate, 'time' => $updatedTime, 'encoder' => $this->session->userdata('username'), 'StudentNumber' => $this->input->post('StudentNumber')));

			// Set flash message and redirect
			$this->session->set_flashdata('success', 'Record updated successfully.');
			redirect($_SERVER['HTTP_REFERER']);
		}
	}




	// public function updateStudeProfile()
	// {

	// 	if ($this->session->userdata('level') === 'Student') {
	// 		$id = $this->session->userdata('username');
	// 	} else {
	// 		$id = $this->input->get('id');
	// 	}
	// 		$result['provinces'] = $this->StudentModel->get_provinces();
	// 	$result['scholar'] = $this->StudentModel->get_Scholar();
	// 	$result['prevschool'] = $this->StudentModel->get_prevSchool();
	// 	$result['data'] = $this->StudentModel->displayrecordsById($id);
	// 	$this->load->view('profile_form_update', $result);

	// 	if ($this->input->post('submit')) {
	// 		$StudentNumber = $this->input->post('StudentNumber');

	// 		// Copy father address to mother if checkbox is checked
	// 		$fatherAddress = $this->input->post('fatherAddress');
	// 		$motherAddress = $this->input->post('motherAddress');
	// 		if ($this->input->post('copyAddressCheckbox') === 'on') {
	// 			$motherAddress = $fatherAddress;
	// 		}

	// 		$profileData = [
	// 			'StudentNumber'    => $this->input->post('StudentNumber'),
	// 			'FirstName'        => $this->input->post('FirstName'),
	// 			'MiddleName'       => $this->input->post('MiddleName'),
	// 			'LastName'         => $this->input->post('LastName'),
	// 			'nameExtn'         => $this->input->post('nameExtn'),
	// 			'Religion'         => $this->input->post('Religion'),
	// 			'Sex'              => $this->input->post('Sex'),
	// 			'CivilStatus'      => $this->input->post('CivilStatus'),
	// 			'contactNo'        => $this->input->post('contactNo'),
	// 			'Ethnicity'        => $this->input->post('Ethnicity'),
	// 			'birthDate'        => $this->input->post('birthDate'),
	// 			'Age'              => $this->input->post('Age'),
	// 			'BirthPlace'       => $this->input->post('BirthPlace'),
	// 			'email'            => $this->input->post('email'),
	// 			'working'          => $this->input->post('working'),
	// 			'nationality'      => $this->input->post('nationality'),
	// 			'Province'         => $this->input->post('Province'),
	// 			'City'             => $this->input->post('City'),
	// 			'Brgy'             => $this->input->post('Brgy'),
	// 			'Sitio'            => $this->input->post('Sitio'),

	// 			'Father'           => $this->input->post('Father'),
	// 			'FOccupation'      => $this->input->post('FOccupation'),
	// 			'fatherContact'    => $this->input->post('fatherContact'),
	// 			'fatherBDate'      => $this->input->post('fatherBDate'),
	// 			'fatherAge'        => $this->input->post('fatherAge'),
	// 			'fatherAddress'    => $fatherAddress,

	// 			'Mother'           => $this->input->post('Mother'),
	// 			'MOccupation'      => $this->input->post('MOccupation'),
	// 			'motherContact'    => $this->input->post('motherContact'),
	// 			'motherBDate'      => $this->input->post('motherBDate'),
	// 			'motherAge'        => $this->input->post('motherAge'),
	// 			'motherAddress'    => $motherAddress,

	// 			'parentsMonthly'   => $this->input->post('parentsMonthly'),

	// 			'Guardian'              => $this->input->post('Guardian'),
	// 			'GuardianRelationship'  => $this->input->post('GuardianRelationship'),
	// 			'GuardianAddress'       => $this->input->post('GuardianAddress'),
	// 			'GuardianContact'       => $this->input->post('GuardianContact'),

	// 			'disability'        => $this->input->post('disability'),
	// 			'occupation'        => $this->input->post('occupation'),
	// 			'salary'            => $this->input->post('salary'),
	// 			'employer'          => $this->input->post('employer'),
	// 			'employerAddress'   => $this->input->post('employerAddress'),
	// 			'scholarship'       => $this->input->post('scholarship'),
	// 			'VaccStat'          => $this->input->post('VaccStat'),
	// 			'fourPs'            => $this->input->post('fourPs'),
	// 			'4psNo'             => $this->input->post('4psNo'),
	// 			'seniorCitizen'     => $this->input->post('seniorCitizen'),
	// 			'als'               => $this->input->post('als'),

	// 			'elementary'        => $this->input->post('elementary'),
	// 			'elementaryAddress' => $this->input->post('elementaryAddress'),
	// 			'elemGraduated'     => $this->input->post('elemGraduated'),
	// 			'elemMerits'        => $this->input->post('elemMerits'),

	// 			'secondary'             => $this->input->post('secondary'),
	// 			'secondaryAddress'      => $this->input->post('secondaryAddress'),
	// 			'secondaryGraduated'    => $this->input->post('secondaryGraduated'),
	// 			'secondaryMerits'       => $this->input->post('secondaryMerits'),

	// 			'SHS'               => $this->input->post('SHS'),
	// 			'SHSaddress'        => $this->input->post('SHSaddress'),
	// 			'SHSgraduated'      => $this->input->post('SHSgraduated'),
	// 			'SHSstrand'         => $this->input->post('SHSstrand'),
	// 			'SHSmerits'         => $this->input->post('SHSmerits'),

	// 			'vocational'            => $this->input->post('vocational'),
	// 			'vocationaladdress'     => $this->input->post('vocationaladdress'),
	// 			'vocationalGraduated'   => $this->input->post('vocationalGraduated'),
	// 			'vocationalCourse'      => $this->input->post('vocationalCourse'),
	// 			'ncLevel'               => $this->input->post('ncLevel'),

	// 			'lastAttended'      => $this->input->post('lastAttended'),
	// 			'lastSchoolDate'    => $this->input->post('lastSchoolDate'),
	// 			'transfereeSchool'  => $this->input->post('transfereeSchool'),
	// 			'transfereeAddress' => $this->input->post('transfereeAddress'),
	// 			'transfereeGraduated'=> $this->input->post('transfereeGraduated'),
	// 			'transfereeCourse'  => $this->input->post('transfereeCourse'),

	// 			'skills'            => $this->input->post('skills'),
	// 			'honors'            => $this->input->post('honors'),
	// 			'rotcSerial'        => $this->input->post('rotcSerial'),
	// 			'cwtsSerial'        => $this->input->post('cwtsSerial'),
	// 			'admissionBasis'    => $this->input->post('admissionBasis'),
	// 			'admissionSem'      => $this->input->post('admissionSem'),
	// 			'admissionSY'       => $this->input->post('admissionSY')
	// 		];

	// 		$Encoder     = $this->session->userdata('username');
	// 		$updatedDate = date("Y-m-d");
	// 		$updatedTime = date("h:i:s A");

	// 		// Update main profile
	// 		$this->StudentModel->update_profile($profileData, $StudentNumber);

	// 		// Update related tables
	// 		$this->db->update('semesterstude', [
	// 			'StudentNumber' => $profileData['StudentNumber'],
	// 			'FName'         => $profileData['FirstName'],
	// 			'MName'         => $profileData['MiddleName'],
	// 			'LName'         => $profileData['LastName']
	// 		], ['StudentNumber' => $StudentNumber]);

	// 		$this->db->update('paymentsaccounts', [
	// 			'StudentNumber' => $profileData['StudentNumber'],
	// 			'FirstName'     => $profileData['FirstName'],
	// 			'MiddleName'    => $profileData['MiddleName'],
	// 			'LastName'      => $profileData['LastName']
	// 		], ['StudentNumber' => $StudentNumber]);

	// 		$this->db->update('studeaccount', [
	// 			'StudentNumber' => $profileData['StudentNumber'],
	// 			'FirstName'     => $profileData['FirstName'],
	// 			'MiddleName'    => $profileData['MiddleName'],
	// 			'LastName'      => $profileData['LastName']
	// 		], ['StudentNumber' => $StudentNumber]);

	// 		// Insert audit trail
	// 		$this->db->insert('atrail', [
	// 			'atDesc'      => 'Updated Profile',
	// 			'atDate'          => $updatedDate,
	// 			'atTime'          => $updatedTime,
	// 			'atRes'          => $Encoder,
	// 			'atSNo' => $profileData['StudentNumber']
	// 		]);

	// 		echo '<script>alert("Updated successfully."); window.location.href="' . base_url('Page/profileList') . '";</script>';
	// 		exit;
	// 	}
	// }

	function masterlistByCourse1()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->Courses($sem, $sy);
		$this->load->view('masterlist_by_course', $result);
	}


	public function studentListByCourseMajor()
	{
		$course = $this->input->get('course');
		$major  = $this->input->get('major');

		$students = $this->StudentModel->getStudentsByCourseMajor($course, $major);

		// Group by YearLevel and then by Sex
		$grouped = [];
		foreach ($students as $student) {
			$year = $student->YearLevel;
			$sex  = strtoupper($student->Sex) == 'MALE' ? 'Male' : 'Female';
			$grouped[$year][$sex][] = $student;
		}

		$data['grouped'] = $grouped;
		$data['course']  = $course;
		$data['major']   = $major;

		$this->load->view('student_list_by_course_major', $data);
	}


	function masterlistByCourse()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$course = $this->input->get('course');
		$result['data'] = $this->StudentModel->byCourse($course, $sy, $sem);
		$result['data1'] = $this->StudentModel->CourseYLCounts($course, $sy, $sem);
		$result['data2'] = $this->StudentModel->SectionCounts($course, $sy, $sem);
		$this->load->view('masterlist_by_course', $result);
	}


	// Controller/Page.php
	function masterlistByCourseFiltered()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$course = $this->input->get('course');
		$major = $this->input->get('major');

		// DO NOT overwrite $result['course']
		$result['selectedCourse'] = $course;
		$result['selectedMajor'] = $major;

		$result['courseList'] = $this->StudentModel->getCourse(); // rename to avoid conflict
		$result['data'] = $this->StudentModel->byCourse($course, $major, $sy, $sem);
		$result['data1'] = $this->StudentModel->CourseYLCounts($course, $major, $sy, $sem);
		$result['data2'] = $this->StudentModel->SectionCounts($course, $major, $sy, $sem);

		$this->load->view('masterlist_by_course_filtered', $result);
	}


	function masterlistBySectionFiltered()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$section = $this->input->get('section');
		$course = $this->input->get('course');
		$major = $this->input->get('major');

		$result['section'] = $this->StudentModel->getSection();
		$result['data']  = $this->StudentModel->bySection1($section, $course, $major, $sem, $sy);
		$result['data1'] = $this->StudentModel->CourseYLCounts($course, $major, $sy, $sem);
		$result['data2'] = $this->StudentModel->SectionCounts($course, $major, $sy, $sem);


		// 👇 Pass these to the view
		$result['course_description'] = $course;
		$result['major'] = $major;
		$result['Section'] = $section;

		$this->load->view('masterlist_by_section_filtered', $result);
	}




	public function yearLevelDetails()
	{
		// Get the parameters from the URL
		$yearLevel = $this->input->get('yearLevel');
		$course = $this->input->get('course');
		$sy = $this->input->get('sy');
		$semester = $this->input->get('semester');

		// Get the data based on Year Level and Course
		$result['data'] = $this->StudentModel->byYearLevelAndCourse($yearLevel, $course, $sy, $semester);

		// Load the view with the data
		$this->load->view('masterlist_by_course_filtered_preview', $result);
	}

	// tyrone
	function masterlistByCoursenames()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$course = $this->input->get('course');

		// Fetch the names using your StudentModel function
		$result['YearLevel'] = $this->StudentModel->getNamesFromQuery($course, $sy, $sem);

		// Load the view to display the names
		$this->load->view('masterlist_by_course_filtered_preview', $result);
	}

	public function announcement()
	{
		$data = [];
		$data['announcement'] = $this->StudentModel->announcement();
		$this->load->view('announcement', $data);
	}
	public function updateAnnouncement()
	{
		// Load deps here so it works even if your Page constructor doesn't
		$this->load->model('AnnouncementModel');
		$this->load->library(['session', 'upload']);
		$this->load->helper(['url']);

		// Optional: gate only logged-in users (match your policy)
		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('login');
			return;
		}

		$aID         = (int)$this->input->post('aID');
		$title       = trim($this->input->post('title', TRUE));
		$message     = trim($this->input->post('message', TRUE));
		$audience    = $this->input->post('audience', TRUE);
		$date_expire = $this->input->post('date_expire', TRUE);
		$old_image   = $this->input->post('old_image', TRUE);
		$remove_img  = $this->input->post('remove_image');

		if (!$aID) {
			$this->session->set_flashdata('error', 'Invalid announcement ID.');
			return redirect('Announcement'); // your list page
		}
		if ($title === '' || $message === '' || $audience === '') {
			$this->session->set_flashdata('error', 'Please complete Title, Message, and Audience.');
			return redirect('Announcement');
		}

		// Ensure record exists
		$rec = $this->AnnouncementModel->getAnnouncementByID($aID);
		if (!$rec) {
			$this->session->set_flashdata('error', 'Announcement not found.');
			return redirect('Announcement');
		}

		$expire_val = (!empty($date_expire)) ? date('Y-m-d', strtotime($date_expire)) : null;

		// Image handling
		$final_image = $old_image ?: null;

		// Remove image if requested
		if ($remove_img) {
			if (!empty($old_image)) {
				$old_path = FCPATH . 'upload/announcements/' . $old_image;
				if (file_exists($old_path)) @unlink($old_path);
			}
			$final_image = null;
		}

		// New upload replaces old
		if (!empty($_FILES['nonoy']['name'])) {
			$config = [
				'upload_path'   => './upload/announcements/',
				'allowed_types' => 'jpg|jpeg|png|gif',
				'max_size'      => 5120,
				'encrypt_name'  => TRUE,
			];
			$this->upload->initialize($config);

			if (!$this->upload->do_upload('nonoy')) {
				$this->session->set_flashdata('error', 'Image upload failed: ' . $this->upload->display_errors('', ''));
				return redirect('Announcement');
			}

			$file_data   = $this->upload->data();
			$new_file    = $file_data['file_name'];
			$final_image = $new_file;

			if (!empty($old_image)) {
				$old_path = FCPATH . 'upload/announcements/' . $old_image;
				if (file_exists($old_path)) @unlink($old_path);
			}
		}

		$data = [
			'title'       => $title,
			'message'     => $message,
			'audience'    => $audience,
			'date_expire' => $expire_val,
			'image'       => $final_image
		];

		if ($this->AnnouncementModel->updateAnnouncement($aID, $data)) {
			$this->session->set_flashdata('success', 'Announcement updated.');
		} else {
			// affected_rows can be 0 when nothing changed; treat as OK for UX
			$this->session->set_flashdata('success', 'Announcement updated.');
		}

		return redirect('Announcement'); // back to list
	}
	public function userAccounts()
	{
		// 1) Handle "Add New User" POST FIRST (modal posts here)
		if ($this->input->post('submit')) {
			// DO NOT log raw password anywhere
			$username  = trim((string)$this->input->post('username', true)); // XSS filter ok for username
			$IDNumber  = trim((string)$this->input->post('IDNumber', true));
			$rawPass   = (string)$this->input->post('password');             // raw, don't log
			$password  = sha1($rawPass); // consider stronger hashing but keeping your code
			$acctLevel = trim((string)$this->input->post('acctLevel', true));
			$fName     = trim((string)$this->input->post('fName', true));
			$mName     = trim((string)$this->input->post('mName', true));
			$lName     = trim((string)$this->input->post('lName', true));
			$email     = trim((string)$this->input->post('email', true));
			$dateCreated = date('Y-m-d');

			// required-field guard (optional)
			if ($username === '' || $rawPass === '' || $acctLevel === '' || $fName === '' || $lName === '' || $email === '' || $IDNumber === '') {
				$this->AuditLogModel->write(
					'create',
					'User Accounts',
					'o_users',
					null,
					null,
					['username' => $username, 'position' => $acctLevel, 'email' => $email, 'IDNumber' => $IDNumber, 'name' => $fName . ' ' . $lName],
					0,
					'Failed to create user (missing required fields)'
				);
				$this->session->set_flashdata('danger', '<div class="alert alert-danger text-center"><b>Missing required fields.</b></div>');
				return redirect('Page/userAccounts');
			}

			// Duplicate username?
			$exists = $this->db->where('username', $username)->get('o_users')->num_rows() > 0;
			if ($exists) {
				// AUDIT: duplicate prevented
				$this->AuditLogModel->write(
					'create',
					'User Accounts',
					'o_users',
					null,
					null,
					['username' => $username, 'position' => $acctLevel, 'email' => $email, 'IDNumber' => $IDNumber, 'name' => $fName . ' ' . $lName],
					0,
					'Duplicate username prevented'
				);
				$this->session->set_flashdata('danger', '<div class="alert alert-danger text-center"><b>The username is already taken. Please choose a different one.</b></div>');
				return redirect('Page/userAccounts');
			}

			// Prepare row
			$data = [
				'username'    => $username,
				'password'    => $password,      // do not log
				'position'    => $acctLevel,
				'fName'       => $fName,
				'mName'       => $mName,
				'lName'       => $lName,
				'email'       => $email,
				'avatar'      => 'avatar.png',
				'acctStat'    => 'active',
				'dateCreated' => $dateCreated,
				'IDNumber'    => $IDNumber
			];

			$ok = $this->db->insert('o_users', $data);
			$pk = $ok ? (string)$this->db->insert_id() : null;

			// AUDIT: create user (no passwords in audit)
			$this->AuditLogModel->write(
				'create',
				'User Accounts',
				'o_users',
				$pk ?: $username, // fallback to username if no auto ID
				null,
				[
					'username' => $username,
					'position' => $acctLevel,
					'email' => $email,
					'IDNumber' => $IDNumber,
					'fName' => $fName,
					'mName' => $mName,
					'lName' => $lName
				],
				$ok ? 1 : 0,
				$ok ? 'Created user account' : 'Failed to create user account'
			);

			$this->session->set_flashdata(
				$ok ? 'success' : 'danger',
				$ok
					? '<div class="alert alert-success text-center"><b>New account has been created successfully.</b></div>'
					: '<div class="alert alert-danger text-center"><b>Failed to create account.</b></div>'
			);
			return redirect('Page/userAccounts');
		}

		// 2) Render the page
		$result['data'] = $this->StudentModel->userAccounts();
		$this->load->view('user_accounts', $result);
	}


	public function copy_users_to_o_users()
	{
		$this->load->database();

		// Step 1: Get all usernames already in o_users
		$existingUsernames = $this->db->select('username')->get('o_users')->result_array();
		$existingUsernames = array_column($existingUsernames, 'username');

		// Step 2: Get users not in o_users
		$this->db->select('username, password, position, fName, mName, lName, email, avatar, acctStat, dateCreated, name');
		if (!empty($existingUsernames)) {
			$this->db->where_not_in('username', $existingUsernames);
		}
		$newUsersQuery = $this->db->get('users');
		$newUsers = $newUsersQuery->result_array();

		// Step 3: Insert each non-duplicate user into o_users
		$insertedCount = 0;
		foreach ($newUsers as $user) {
			$this->db->insert('o_users', $user);
			$insertedCount++;
		}

		// Step 4: Set flash message
		if ($insertedCount > 0) {
			$msg = "<div class='alert alert-success'>{$insertedCount} new user(s) copied to <strong>o_users</strong>.</div>";
		} else {
			$msg = "<div class='alert alert-info'>No new users to copy. All usernames already exist in <strong>o_users</strong>.</div>";
		}

		$this->session->set_flashdata('success', $msg);
		redirect($this->agent->referrer());
	}


	public function updateNames()
	{
		$this->db->query("
        UPDATE o_users u
        JOIN studeprofile sp ON u.username = sp.StudentNumber
        SET u.fName = sp.FirstName,
            u.mName = sp.MiddleName,
            u.lName = sp.LastName
    ");

		$aff = $this->db->affected_rows();

		// AUDIT: bulk sync names (no old snapshot; too large)
		$this->AuditLogModel->write(
			'update',
			'User Accounts',
			'o_users',
			null,
			null,
			['bulk_sync' => 'names', 'affected' => $aff],
			1,
			'Bulk-synced user names from studeprofile'
		);

		if ($aff > 0) {
			$this->session->set_flashdata('success', 'Names have been successfully updated.');
		} else {
			$this->session->set_flashdata('success', 'No records were updated.');
		}
		return redirect('Page/userAccounts');
	}


	public function changeUserStat()
	{
		$u = $this->input->get('u'); // Username of the account to be updated
		$t = $this->input->get('t'); // Action type (Activate or Deactivate)
		$id = $this->session->userdata('username'); // Current user's username
		date_default_timezone_set('Asia/Manila');
		$now = date('H:i:s A'); // Current time
		$date = date("Y-m-d"); // Current date

		// Determine the new status based on the value of $t
		if ($t == 'Activate') {
			$newStatus = 'active';
		} else {
			$newStatus = 'inactive';
		}

		// Update the user account status
		$this->db->query("UPDATE o_users SET acctStat = '$newStatus' WHERE username = ?", array($u));

		// Insert a trail record
		$this->db->query(
			"INSERT INTO atrail (atrailID, atDesc, atDate, atTime, atRes, atSNo) VALUES (?, ?, ?, ?, ?, ?)",
			array(
				'',
				($newStatus == 'Active' ? 'Activated user account' : 'Deactivated user account'),
				$date,
				$now,
				$id,
				$u
			)
		);

		// Set a success flash message
		$this->session->set_flashdata('success', '<div class="alert alert-success text-center"><b>The selected account has been ' . strtolower($newStatus) . 'd successfully.</b></div>');

		// Redirect to the user accounts page
		redirect('Page/userAccounts');
	}
	public function resetPass()
	{
		$u  = trim((string)$this->input->get('u', true));    // Username/StudentNumber to reset
		$id = (string)$this->session->userdata('username');  // Resetter username
		$returnTo = trim((string)$this->input->get('return_to', true));

		$redirectTo = 'Page/userAccounts';
		if ($returnTo === 'profileList') {
			$redirectTo = 'Page/profileList';
		}

		// Config + login URL
		$schoolName = $this->SettingsModel->getSchoolName();
		$loginURL   = base_url('login');

		if ($u === '') {
			$this->session->set_flashdata('danger', 'No username/StudentNumber provided.');
			return redirect($redirectTo);
		}

		// Generate new password (12 chars hex) + hash
		$password       = bin2hex(random_bytes(6));
		$hashedPassword = sha1($password); // keep your hashing scheme

		date_default_timezone_set('Asia/Manila');

		// Fetch account by username OR IDNumber.
		// From profileList, restrict to student-type accounts only.
		$studentOnly = ($redirectTo === 'Page/profileList');
		$sql = "
			SELECT *
			FROM o_users
			WHERE (
				BINARY TRIM(username) = BINARY TRIM(?)
				OR BINARY TRIM(IDNumber) = BINARY TRIM(?)
			)
		";
		if ($studentOnly) {
			$sql .= " AND position IN ('Student', 'Stude Applicant', 'Student Applicant')";
		}
		$sql .= " LIMIT 1";
		$user = $this->db->query($sql, [$u, $u])->row();

		if (!$user || empty($user->email)) {
			$this->AuditLogModel->write(
				'update',
				'User Accounts',
				'o_users',
				$u,
				null,
				null,
				0,
				'Password reset failed (no email on file)',
				['reset_by' => $id, 'scope' => $studentOnly ? 'student' : 'user']
			);
			$this->session->set_flashdata('danger', 'No account/email found for the selected record.');
			return redirect($redirectTo);
		}

		$targetUsername = (string)$user->username;

		// Update password (always update by actual username from fetched row).
		$ok = $this->db->where('username', $targetUsername)->update('o_users', ['password' => $hashedPassword]);

		// AUDIT: unified audit (no password in logs)
		$this->AuditLogModel->write(
			'update',
			'User Accounts',
			'o_users',
			$targetUsername,
			null,
			[
				'password_reset' => true,
				'email_to' => $user->email,
				'reset_by' => $id,
				'scope' => $studentOnly ? 'student' : 'user'
			],
			$ok ? 1 : 0,
			$ok ? 'Reset user password' : 'Failed to reset user password'
		);

		// Send email (your existing email content)
		$this->load->config('email');
		$this->load->library('email');
		$this->email->set_mailtype("html");

		$mail_message = '
<div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: white; border-radius: 6px; padding: 30px;">
    <h2 style="color: #2b6cb0;">Password Reset Notification</h2>
    <p>Dear <strong>' . htmlspecialchars($user->fName) . '</strong>,</p>
    <p>Your password has been successfully reset.</p>
    <p><strong>Here are your new login credentials:</strong></p>
    <table style="width: 100%; max-width: 400px; border-collapse: collapse; margin-bottom: 20px;">
      <tr><td style="padding:8px;background:#f1f1f1;border:1px solid #ccc;"><strong>Username</strong></td>
          <td style="padding:8px;border:1px solid #ccc;">' . htmlspecialchars($targetUsername) . '</td></tr>
      <tr><td style="padding:8px;background:#f1f1f1;border:1px solid #ccc;"><strong>New Password</strong></td>
          <td style="padding:8px;border:1px solid #ccc;">' . $password . '</td></tr>
    </table>
    <p><a href="' . htmlspecialchars($loginURL) . '" style="display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;">Login Now</a></p>
    <p style="margin-top:30px;">Best regards,<br><strong>' . htmlspecialchars($schoolName) . ' FBMSO Team</strong></p>
    <hr style="margin-top:40px;">
    <p style="font-size:12px;color:#999;">This is an automated message from Faculty of Business and Management Student Organization. Please do not reply.</p>
  </div>
</div>';

		$this->email->from('no-reply@srmsportal.com', $schoolName);
		$this->email->to($user->email);
		$this->email->subject('Your Password Has Been Reset');
		$this->email->message($mail_message);
		$sent = @$this->email->send();

		if (!$ok) {
			$this->session->set_flashdata('danger', 'Password reset failed. Please try again.');
			return redirect($redirectTo);
		}

		if ($sent) {
			$this->session->set_flashdata('success', "Password reset for {$targetUsername}. Temporary password was sent to {$user->email}.");
		} else {
			$this->session->set_flashdata('danger', "Password reset for {$targetUsername}, but email sending failed. Check mail settings/logs.");
		}

		return redirect($redirectTo);
	}
	public function updateUserInfo()
	{
		if ($this->input->post('submitEdit')) {
			$username  = trim((string)$this->input->post('username'));
			$acctLevel = trim((string)$this->input->post('acctLevel'));
			$email     = trim((string)$this->input->post('email'));

			if ($username && $acctLevel && $email) {
				// OLD snapshot
				$old = $this->db->get_where('o_users', ['username' => $username])->row_array();

				$data = ['position' => $acctLevel, 'email' => $email];
				$ok = $this->db->where('username', $username)->update('o_users', $data);

				// AUDIT: update user info
				$this->AuditLogModel->write(
					'update',
					'User Accounts',
					'o_users',
					$username,
					$old ? ['position' => $old['position'] ?? null, 'email' => $old['email'] ?? null] : null,
					$data,
					$ok ? 1 : 0,
					$ok ? 'Updated user info' : 'Failed to update user info'
				);

				if ($ok && $this->db->affected_rows() > 0) {
					$this->session->set_flashdata('success', 'Account info updated successfully.');
				} else {
					$this->session->set_flashdata('danger', 'Update failed. No changes or user not found.');
				}
			} else {
				$this->session->set_flashdata('danger', 'Invalid input values.');
			}
		}

		return redirect('Page/userAccounts');
	}



	function medRecords()
	{
		$result['data'] = $this->StudentModel->medRecords();
		$result['data1'] = $this->StudentModel->searchStudents();
		$this->load->view('medical_records', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$caseNo = $this->input->post('caseNo');
			$incidentDate = $this->input->post('incidentDate');
			$temperature = $this->input->post('temperature');
			$bp = $this->input->post('bp');
			$complaint = $this->input->post('complaint');
			$painTolerance = $this->input->post('painTolerance');
			$medication = $this->input->post('medication');
			$otherDetails = $this->input->post('otherDetails');
			$otherNotes = $this->input->post('otherNotes');

			$que = $this->db->query("insert into medical_records (StudentNumber, caseNo, incidentDate, temperature, bp, complaint, painTolerance, medication, otherDetails, otherNotes) values('$StudentNumber','$caseNo','$incidentDate','$temperature','$bp','$complaint','$painTolerance','$medication','$otherDetails','$otherNotes')");
			$this->session->set_flashdata('success', 'Added successfully.');
			redirect('Page/medRecords');
		}
	}

	public function deleteMedRec()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("delete from medical_records where mrID='" . $id . "'");
		$this->session->set_flashdata('success', 'Deleted successfully.');
		redirect("Page/medRecords");
	}

	public function updateMedRecords()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->medRecordsInd($id);
		$this->load->view('medical_records_update', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$caseNo = $this->input->post('caseNo');
			$incidentDate = $this->input->post('incidentDate');
			$temperature = $this->input->post('temperature');
			$bp = $this->input->post('bp');
			$complaint = $this->input->post('complaint');
			$painTolerance = $this->input->post('painTolerance');
			$medication = $this->input->post('medication');
			$otherDetails = $this->input->post('otherDetails');
			$otherNotes = $this->input->post('otherNotes');

			$que = $this->db->query("update medical_records set StudentNumber='$StudentNumber',caseNo='$caseNo',incidentDate='$incidentDate',temperature='$temperature',bp='$bp',complaint='$complaint',painTolerance='$painTolerance',medication='$medication',otherDetails='$otherDetails',otherNotes='$otherNotes' where mrID='$id'");
			$this->session->set_flashdata('success', 'Updated successfully.');
			redirect(base_url() . 'Page/updateMedRecords?id=' . $id);
		}
	}

	function medInfo()
	{
		$result['data'] = $this->StudentModel->medInfo();
		$result['data1'] = $this->StudentModel->searchStudents();
		$this->load->view('medical_info', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$height = $this->input->post('height');
			$weight = $this->input->post('weight');
			$bloodType = $this->input->post('bloodType');
			$vision = $this->input->post('vision');
			$allergiesDrugs = $this->input->post('allergiesDrugs');
			$allergiesFood = $this->input->post('allergiesFood');
			$eyeColor = $this->input->post('eyeColor');
			$hairColor = $this->input->post('hairColor');
			$specialPhyNeeds = $this->input->post('specialPhyNeeds');
			$specialDieNeeds = $this->input->post('specialDieNeeds');
			$respiratoryProblems = $this->input->post('respiratoryProblems');

			$que = $this->db->query("insert into medical_info (StudentNumber, height, weight, bloodType, vision, allergiesDrugs, allergiesFood, eyeColor, hairColor, specialPhyNeeds, specialDieNeeds, respiratoryProblems) values('$StudentNumber','$height','$weight','$bloodType','$vision','$allergiesDrugs','$allergiesFood','$eyeColor','$hairColor','$specialPhyNeeds','$specialDieNeeds','$respiratoryProblems')");
			$this->session->set_flashdata('success', ' Added successfully.');
			redirect('Page/medInfo');
		}
	}

	public function deleteMedInfo()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("delete from medical_info where medID='" . $id . "'");
		$this->session->set_flashdata('success', ' Deleted successfully.');
		redirect("Page/medInfo");
	}

	public function updateMedInfo()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->medInfoInd($id);
		$this->load->view('medical_info_update', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$height = $this->input->post('height');
			$weight = $this->input->post('weight');
			$bloodType = $this->input->post('bloodType');
			$vision = $this->input->post('vision');
			$allergiesDrugs = $this->input->post('allergiesDrugs');
			$allergiesFood = $this->input->post('allergiesFood');
			$eyeColor = $this->input->post('eyeColor');
			$hairColor = $this->input->post('hairColor');
			$specialPhyNeeds = $this->input->post('specialPhyNeeds');
			$specialDieNeeds = $this->input->post('specialDieNeeds');
			$respiratoryProblems = $this->input->post('respiratoryProblems');

			$que = $this->db->query("update medical_info set StudentNumber='$StudentNumber',height='$height',weight='$weight',bloodType='$bloodType',vision='$vision',allergiesDrugs='$allergiesDrugs',allergiesFood='$allergiesFood',eyeColor='$eyeColor',hairColor='$hairColor',specialPhyNeeds='$specialPhyNeeds',specialDieNeeds='$specialDieNeeds',respiratoryProblems='$respiratoryProblems' where medID='$id'");
			$this->session->set_flashdata('success', 'Updated successfully.');
			// redirect('Page/medInfo');
			redirect(base_url() . 'Page/updateMedInfo?id=' . $id);
		}
	}

	function incidents()
	{
		$result['data'] = $this->StudentModel->incidents();
		$result['data1'] = $this->StudentModel->searchStudents();
		$this->load->view('guidance_incidents', $result);

		if ($this->input->post('submit')) {
			$sem = $this->session->userdata('semester');
			$sy = $this->session->userdata('sy');
			$StudentNumber = $this->input->post('StudentNumber');
			$caseNo = $this->input->post('caseNo');
			$incidentDate = $this->input->post('incidentDate');
			$incPlace = $this->input->post('incPlace');
			$offenseLevel = $this->input->post('offenseLevel');
			$offense = $this->input->post('offense');
			$sanction = $this->input->post('sanction');
			$actionTaken = $this->input->post('actionTaken');

			$que = $this->db->query("insert into guidance_incidents (StudentNumber, caseNo, incidentDate, incPlace, offenseLevel, offense, sanction, actionTaken, sem, sy) values('$StudentNumber','$caseNo','$incidentDate','$incPlace','$offenseLevel','$offense','$sanction','$actionTaken','$sem','$sy')");
			$this->session->set_flashdata('success', 'Added successfully.');
			redirect('Page/incidents');
		}
	}

	public function updateIncidents()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->incidentsInd($id);
		$this->load->view('guidance_incidents_update', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$caseNo = $this->input->post('caseNo');
			$incidentDate = $this->input->post('incidentDate');
			$incPlace = $this->input->post('incPlace');
			$offenseLevel = $this->input->post('offenseLevel');
			$offense = $this->input->post('offense');
			$sanction = $this->input->post('sanction');
			$actionTaken = $this->input->post('actionTaken');

			$que = $this->db->query("update guidance_incidents  set StudentNumber='$StudentNumber',caseNo='$caseNo',incidentDate='$incidentDate',incPlace='$incPlace',offenseLevel='$offenseLevel',offense='$offense',sanction='$sanction',actionTaken='$actionTaken' where incID='$id'");
			$this->session->set_flashdata('success', 'Updated successfully.');
			// redirect('Page/medInfo');
			redirect(base_url() . 'Page/updateIncidents?id=' . $id);
		}
	}

	public function deleteIncident()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("delete from guidance_incidents where incID='" . $id . "'");
		$this->session->set_flashdata('success', ' Deleted successfully.');
		redirect("Page/incidents");
	}

	function counselling()
	{
		$result['data'] = $this->StudentModel->counselling();
		$result['data1'] = $this->StudentModel->searchStudents();
		$this->load->view('guidance_counselling', $result);
		if ($this->input->post('submit')) {
			$sem = $this->session->userdata('semester');
			$sy = $this->session->userdata('sy');
			$StudentNumber = $this->input->post('StudentNumber');
			$recordNo = $this->input->post('recordNo');
			$recordDate = $this->input->post('recordDate');
			$details = $this->input->post('details');
			$actionPlan = $this->input->post('actionPlan');
			$otherNotes = $this->input->post('otherNotes');

			$que = $this->db->query("insert into guidance_counselling (StudentNumber, recordNo, recordDate, details, actionPlan, sem, sy) values('$StudentNumber','$recordNo','$recordDate','$details','$actionPlan','$sem','$sy')");
			$this->session->set_flashdata('success', ' Added successfully.');
			redirect('Page/counselling');
		}
	}

	public function updateCounselling()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->counsellingInd($id);
		$this->load->view('guidance_counselling_update', $result);
		if ($this->input->post('submit')) {
			$StudentNumber = $this->input->post('StudentNumber');
			$recordNo = $this->input->post('recordNo');
			$recordDate = $this->input->post('recordDate');
			$details = $this->input->post('details');
			$actionPlan = $this->input->post('actionPlan');
			$otherNotes = $this->input->post('otherNotes');

			$que = $this->db->query("update guidance_counselling set StudentNumber='$StudentNumber',recordNo='$recordNo',recordDate='$recordDate',details='$details',actionPlan='$actionPlan' where id='$id'");
			$this->session->set_flashdata('success', 'Updated successfully.');
			// redirect('Page/medInfo');
			redirect(base_url() . 'Page/updateCounselling?id=' . $id);
		}
	}

	public function deleteCounselling()
	{
		$id = $this->input->get('id');
		$que = $this->db->query("delete from guidance_counselling where id='" . $id . "'");
		$this->session->set_flashdata('success', ' Deleted successfully.');
		redirect("Page/counselling");
	}


	public function uploadAnnouncement()
	{
		$config['upload_path'] = './upload/announcements/';  // Ensure this path exists and is writable
		$config['allowed_types'] = 'jpg|png|gif';
		$config['max_size'] = 5120;  // Max size in KB (5 MB)

		// Load upload library with the configuration
		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			// If upload fails, set error flash message
			$this->session->set_flashdata('error', 'Data cannot be saved');
			$data['data'] = $this->StudentModel->announcement();
			$data['error'] = $this->upload->display_errors();
			$this->load->view('announcement', $data);
		} else {
			// File successfully uploaded
			$file_data = $this->upload->data();
			$filename = $file_data['file_name'];

			// Retrieve other form data
			$title = $this->input->post('title');
			$encoder = $this->session->userdata('username');
			$datePosted = date("Y-m-d");

			// Prepare data for insertion
			$announcement_data = [
				'datePosted' => $datePosted,
				'title' => $title,
				'announcement' => $filename,
				'author' => $encoder
			];

			// Insert data into the 'announcement' table
			if ($this->db->insert('announcement', $announcement_data)) {
				// Set a success flash message
				$this->session->set_flashdata('success', 'Data have saved successfully');
				redirect(base_url() . 'Page/announcement');
			} else {
				// Set error flash message
				$this->session->set_flashdata('error', 'Data cannot be saved');
				$data['data'] = $this->StudentModel->announcement();
				$this->load->view('announcement', $data);
			}
		}
	}

	//Requirements
	function uploadedRequirements()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->StudentModel->requirements($id);
		$this->load->view('uploaded_requirements', $result);
	}

	//Announcements
	function viewAnnouncement()
	{
		$result['data'] = $this->StudentModel->announcement();
		$this->load->view('announcement_view', $result);
	}
	//Delete Announcement
	public function deleteAnnouncement($id = null)
	{
		if ($id) {
			$this->StudentModel->deleteAnnouncement($id);
		}
		redirect("Page/announcement");
	}



	//Request
	public function submitRequest()
	{
		if ($this->session->userdata('level') === 'Student') {
			$id = $this->session->userdata('username');
		} else {
			$id = $this->input->get('id');
		};

		$result['data'] = $this->StudentModel->studerequest($id);
		$result['data2'] = $this->StudentModel->getTrackingNo();
		$this->load->view('request_submit', $result);

		if ($this->input->post('submit')) {
			if ($this->session->userdata('level') === 'Student') {
				$fname = $this->session->userdata('fname');
			} else {
				$fname = $this->input->post('fname');
			}

			$config['upload_path'] = './upload/reqDocs/';
			$config['allowed_types'] = '*';
			$config['max_size'] = 5120;
			//$config['max_width'] = 1500;
			//$config['max_height'] = 1500;

			$this->load->library('upload', $config);
			$this->upload->do_upload('nonoy');
			$data = array('image_metadata' => $this->upload->data());
			$filename = $this->upload->data('file_name');

			$email = $this->input->post('email');
			$StudentNumber = $this->input->post('StudentNumber');
			$docName = $this->input->post('docName');
			$purpose = $this->input->post('purpose');
			$trackingNo = $this->input->post('trackingNo');
			$pReference = $this->input->post('pReference');
			$trackingNo = $this->input->post('trackingNo');

			$dateReq = date("Y-m-d");
			date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
			$now = date('H:i:s A');

			//check if record exist
			$que = $this->db->query("select * from stude_request where trackingNo='" . $trackingNo . "'");
			$row = $que->num_rows();
			if ($row) {
				//redirect('Page/notification_error');
				$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Tracking No. is in use.</b></div>');
				redirect('Page/profileList');
			} else {

				$que = $this->db->query("insert into stude_request values('$trackingNo','$docName','$purpose','$dateReq','$now','$StudentNumber','Open','$pReference','$filename')");
				$que = $this->db->query("insert into stude_request_stat values('','$StudentNumber','request submitted','$StudentNumber','$dateReq','$now','$trackingNo','Open','$filename','On Process')");
				$que = $this->db->query("insert into atrail values('','Requested a Document','$dateReq','$now','$id','$id')");
				$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Your request has been submitted.</b></div>');

				//Email Notification
				$this->load->config('email');
				$this->load->library('email');
				$mail_message = 'Dear ' . $fname . ',' . "\r\n";
				$mail_message .= '<br><br>Your request with tracking number <b>' . $trackingNo . ' </b>has been submitted.' . "\r\n";
				$mail_message .= '<br><br>Login to your portal to check the status of your request.' . "\r\n";

				$mail_message .= '<br><br>Thanks & Regards,';
				$mail_message .= '<br>SRMS - Online';

				$this->email->from('no-reply@lxeinfotechsolutions.com', 'SRMS Online Team')
					->to($email)
					->subject('Online Request')
					->message($mail_message);
				$this->email->send();
				if ($this->session->userdata('level') === 'Student') {
					redirect('Page/student');
				} else {
					redirect('Page/allRequest');
				}
			}
		}
	}

	function studentRequestStat()
	{
		$id = $this->input->get('trackingNo');
		$result['data'] = $this->StudentModel->studerequestTracking($id);
		$this->load->view('stude_request_status', $result);

		if ($this->input->post('submit')) {
			$config['upload_path'] = './upload/reqDocs/';
			$config['allowed_types'] = '*';
			$config['max_size'] = 5120;
			//$config['max_width'] = 1500;
			//$config['max_height'] = 1500;

			$this->load->library('upload', $config);


			$this->upload->do_upload('nonoy');
			$data = array('image_metadata' => $this->upload->data());
			$filename = $this->upload->data('file_name');

			$StudentNumber = $this->input->post('StudentNumber');
			$trackingNo = $this->input->post('trackingNo');
			$reqStatus = $this->input->post('reqStatus');
			$reqStat = $this->input->post('reqStat');

			$dateReq = date("Y-m-d");
			date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
			$now = date('H:i:s A');

			$que = $this->db->query("update stude_request set reqStat='$reqStat' where trackingNo='$trackingNo'");
			$que = $this->db->query("insert into stude_request_stat values('','$StudentNumber','$reqStatus','$id','$dateReq','$now','$trackingNo','$reqStat','$filename','$reqStat')");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>New request status has been posted.</b></div>');
		}
	}

	public function lockScreen()
	{
		$this->load->view('lock-screen');
	}

	//delete student's profile
	public function deleteProfile()
	{
		$id = $this->input->get('id'); // StudentNumber
		$username = $this->session->userdata('username');
		date_default_timezone_set('Asia/Manila');
		$now = date('h:i:s A');
		$date = date("Y-m-d");

		// Get current Semester and SY from session
		$sem = $this->session->userdata('sem');
		$sy = $this->session->userdata('sy');

		// Delete from studeprofile
		$this->db->where('StudentNumber', $id);
		$this->db->delete('studeprofile');

		// Delete from o_users
		$this->db->where('username', $id);
		$this->db->delete('o_users');

		// Delete from semesterstude
		$this->db->where([
			'StudentNumber' => $id,
			'Semester' => $sem,
			'SY' => $sy
		]);
		$this->db->delete('semesterstude');

		// Insert to atrail
		$this->db->insert('atrail', [
			'atDesc' => "Deleted Student's Profile",
			'atDate' => $date,
			'atTime' => $now,
			'atRes' => $username,
			'atSNo' => $id
		]);

		// Set flash message
		$this->session->set_flashdata('success', "Student profile (StudentNumber: <strong>$id</strong>) has been successfully deleted.");

		redirect('Page/profileList');
	}



	//delete personnel's profile
	public function deletePersonnel()
	{
		$id = $this->input->get('id', true); // IDNumber of the personnel
		$username = $this->session->userdata('username');

		date_default_timezone_set('Asia/Manila');
		$now  = date('H:i:s');
		$date = date('Y-m-d');

		// Delete from staff
		$this->db->where('IDNumber', $id);
		$this->db->delete('staff');

		// Delete from o_users
		$this->db->where('IDNumber', $id);
		$this->db->delete('o_users');

		// Log audit trail
		$this->db->insert('atrail', [
			'atDesc' => 'Deleted Personnel Profile',
			'atDate' => $date,
			'atTime' => $now,
			'atRes'  => $username,
			'atSNo'  => $id
		]);
		$this->session->set_flashdata('success', '<div id="sa-success" class="alert alert-success text-center"><b>Deleted successfully.</b></div>');
		redirect('Page/employeeList');
	}


	//delete student's enrollment
	public function deleteEnrollment()
	{
		$id = $this->input->get('id');
		$query = $this->db->query("delete from semesterstude where semstudentid='" . $id . "'");
		$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Deleted successfully.</b></div>');
		redirect('Masterlist/enrolledList');
	}

	public function deleteEnrollmentPH()
	{
		$id = $this->input->get('id');
		$query = $this->db->query("delete from semesterstude where semstudentid='" . $id . "'");
		$this->session->set_flashdata('msg', 'Deleted successfully.');
		redirect('Masterlist/enrolledListPH');
	}



	public function updateEnrollment()
	{
		$id = $this->input->get('id');
		$semester = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');

		// Load dropdown values and student data
		$result['course'] = $this->StudentModel->getCourse();
		$result['section'] = $this->StudentModel->getSection();
		$result['scholarships'] = $this->StudentModel->getscholarships();

		$result['courseVal'] = $this->input->post('course');
		$result['yearlevelVal'] = $this->input->post('yearlevel');
		$result['data'] = $this->StudentModel->masterlistAll2($id, $semester, $sy);

		// Load the update form view
		$this->load->view('enrollment_form_update', $result);

		if ($this->input->post('submit')) {
			// Collect form data
			$updateData = [
				'StudentNumber' => $this->input->post('StudentNumber'),
				'Course'        => $this->input->post('Course'),
				'YearLevel'     => $this->input->post('YearLevel'),
				'Status'     => $this->input->post('Status'),
				'Semester'      => $this->input->post('Semester'),
				'SY'            => $this->input->post('SY'),
				'Section'       => $this->input->post('Section'),
				'StudeStatus'   => $this->input->post('StudeStatus'),
				'PayingStatus'   => $this->input->post('PayingStatus'),
				'Scholarship'     => $this->input->post('Scholarship') ?? '',
				'YearLevelStat' => $this->input->post('YearLevelStat'),
				'Major'         => $this->input->post('Major'),
				'PrevGPA'         => $this->input->post('PrevGPA'),
				'crossEnrollee'         => $this->input->post('crossEnrollee'),
			];

			// Update semesterstude record
			$this->db->where('semstudentid', $id)->update('semesterstude', $updateData);



			$studentNumber = $this->input->post('StudentNumber', true);
			$course        = $this->input->post('Course', true);
			$major         = $this->input->post('Major', true);
			$yearLevel     = $this->input->post('YearLevel', true);

			$this->db->where('StudentNumber', $studentNumber)
				->update('studeprofile', [
					'course'    => $course,
					'major'     => $major,
					'yearLevel' => $yearLevel,
				]);


			// Prepare email notification details
			$email = $this->input->post('email');
			$FName = $this->input->post('FName');
			$Semester = $this->input->post('Semester');
			$SY = $this->input->post('SY');

			// Email notification
			$this->load->config('email');
			$this->load->library('email');

			$mail_message = 'Dear ' . $FName . ",<br><br>";
			$mail_message .= "Your enrollment details have been updated.<br>";
			$mail_message .= "Course: <b>{$updateData['Course']}</b><br>";
			$mail_message .= "Major: <b>{$updateData['Major']}</b><br>";
			$mail_message .= "Year Level: <b>{$updateData['YearLevel']}</b><br>";
			$mail_message .= "Section: <b>{$updateData['Section']}</b><br>";
			$mail_message .= "Sem/SY: <b>{$Semester}, {$SY}</b><br>";
			$mail_message .= "Status: <b>Validated</b><br><br>";
			$mail_message .= "Thanks & Regards,<br>SRMS - Online";

			$this->email->from('no-reply@lxeinfotechsolutions.com', 'SRMS Online Team');
			$this->email->to($email);
			$this->email->subject('Enrollment Update');
			$this->email->message($mail_message);
			$this->email->send();

			// Set flash message and redirect
			$this->session->set_flashdata('success', '<div class="alert alert-success text-center"><b>Enrollment details have been updated successfully.</b></div>');
			redirect('Masterlist/enrolledList');
		}
	}




	public function updateEnrollmentPH()
	{
		$id = $this->input->get('id');
		$semester = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');

		// Load dropdown values and student data
		$result['course'] = $this->StudentModel->getCourse();
		$result['section'] = $this->StudentModel->getSection();
		$result['scholarships'] = $this->StudentModel->getscholarships();

		$result['courseVal'] = $this->input->post('course');
		$result['yearlevelVal'] = $this->input->post('yearlevel');
		$result['data'] = $this->StudentModel->masterlistAll2($id, $semester, $sy);

		// Load the update form view
		$this->load->view('enrollment_form_update', $result);

		if ($this->input->post('submit')) {
			// Collect form data
			$updateData = [
				'StudentNumber' => $this->input->post('StudentNumber'),
				'Course'        => $this->input->post('Course'),
				'YearLevel'     => $this->input->post('YearLevel'),
				'Status'     => $this->input->post('Status'),
				'Semester'      => $this->input->post('Semester'),
				'SY'            => $this->input->post('SY'),
				'Section'       => $this->input->post('Section'),
				'StudeStatus'   => $this->input->post('StudeStatus'),
				'PayingStatus'   => $this->input->post('PayingStatus'),
				'Scholarship'     => $this->input->post('Scholarship') ?? '',
				'YearLevelStat' => $this->input->post('YearLevelStat'),
				'Major'         => $this->input->post('Major'),
				'PrevGPA'         => $this->input->post('PrevGPA'),
				'crossEnrollee'         => $this->input->post('crossEnrollee'),
			];

			// Update semesterstude record
			$this->db->where('semstudentid', $id)->update('semesterstude', $updateData);



			$studentNumber = $this->input->post('StudentNumber', true);
			$course        = $this->input->post('Course', true);
			$major         = $this->input->post('Major', true);
			$yearLevel     = $this->input->post('YearLevel', true);

			$this->db->where('StudentNumber', $studentNumber)
				->update('studeprofile', [
					'course'    => $course,
					'major'     => $major,
					'yearLevel' => $yearLevel,
				]);



			// Prepare email notification details
			$email = $this->input->post('email');
			$FName = $this->input->post('FName');
			$Semester = $this->input->post('Semester');
			$SY = $this->input->post('SY');

			// Email notification
			$this->load->config('email');
			$this->load->library('email');

			$mail_message = 'Dear ' . $FName . ",<br><br>";
			$mail_message .= "Your enrollment details have been updated.<br>";
			$mail_message .= "Course: <b>{$updateData['Course']}</b><br>";
			$mail_message .= "Major: <b>{$updateData['Major']}</b><br>";
			$mail_message .= "Year Level: <b>{$updateData['YearLevel']}</b><br>";
			$mail_message .= "Section: <b>{$updateData['Section']}</b><br>";
			$mail_message .= "Sem/SY: <b>{$Semester}, {$SY}</b><br>";
			$mail_message .= "Status: <b>Validated</b><br><br>";
			$mail_message .= "Thanks & Regards,<br>SRMS - Online";

			$this->email->from('no-reply@lxeinfotechsolutions.com', 'SRMS Online Team');
			$this->email->to($email);
			$this->email->subject('Enrollment Update');
			$this->email->message($mail_message);
			$this->email->send();

			// Set flash message and redirect
			$this->session->set_flashdata('success', 'Enrollment details have been updated successfully.');
			redirect('Masterlist/enrolledListPH');
		}
	}



	//Employee List (All)  
	function employeelist()
	{
		$result['data'] = $this->PersonnelModel->displaypersonnel();
		$result['data1'] = $this->PersonnelModel->personnelCounts();
		$result['data2'] = $this->PersonnelModel->departmentcounts();
		$this->load->view('hr_personnel_list', $result);
	}


	//Employee List By Department
	function employeelistDepartment()
	{
		$department = $this->input->get('department');
		$result['data'] = $this->PersonnelModel->employeelistDepartment($department);
		$this->load->view('hr_personnel_list_department', $result);
	}

	//Employee List By Position
	function employeelistPosition()
	{
		$position = $this->input->get('position');
		$result['data'] = $this->PersonnelModel->employeelistPosition($position);
		$this->load->view('hr_personnel_list_position', $result);
	}

	public function upload201files()
	{
		$this->load->view('hr_201files_upload');
	}
	public function process201Upload()
	{
		$config['upload_path'] = './upload/201files/';
		$config['allowed_types'] = '*';
		$config['max_size'] = 5120;
		//$config['max_width'] = 1500;
		//$config['max_height'] = 1500;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$msg = array('error' => $this->upload->display_errors());

			$this->load->view('upload201Files', $msg);
		} else {
			$data = array('image_metadata' => $this->upload->data());
			//get data from the form
			$IDNumber = $this->input->post('IDNumber');
			//$filename=$this->input->post('nonoy');
			$filename = $this->upload->data('file_name');
			$docName = $this->input->post('docName');
			$date = date("Y-m-d");
			$que = $this->db->query("insert into hris_files values('','$IDNumber','$docName','$filename','$date')");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Uploaded Succesfully!</b></div>');
			redirect('Page/viewfilesAll');
		}
	}

	function viewfilesAll()
	{
		$result['data'] = $this->PersonnelModel->viewfilesAll();
		$this->load->view('hr_201files', $result);
	}

	function hr_files_individual()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->PersonnelModel->viewfiles($id);
		$this->load->view('hr_files_individual', $result);
	}

	function closedDocRequest()
	{
		$result['data'] = $this->StudentModel->closedDocRequest();
		$this->load->view('request_closed', $result);
	}

	function openDocRequest()
	{
		$result['data'] = $this->StudentModel->openDocRequest();
		$this->load->view('request_open', $result);
	}

	function erform()
	{
		$result['data'] = 'title';

		$result['course'] = $this->Ren_model->no_cond_select_gb('course_table', 'CourseDescription', 'CourseDescription');
		$this->load->view('er_form', $result);
	}

	function prform()
	{
		$result['data'] = 'title';

		$result['course'] = $this->Ren_model->no_cond_select_gb('course_table', 'CourseDescription', 'CourseDescription');
		$this->load->view('pr_form', $result);
	}


	function allRequest()
	{
		$result['data'] = $this->StudentModel->allDocRequest();
		$result['data1'] = $this->StudentModel->getProfile();
		$result['data2'] = $this->StudentModel->getTrackingNo();
		$this->load->view('request_all', $result);
	}

	function newRequest()
	{
		$result['data'] = $this->StudentModel->getProfile();
		$this->load->view('request_search', $result);
	}

	function requestSummary()
	{
		$result['data'] = $this->StudentModel->totalStudeRequest();
		$result['data1'] = $this->StudentModel->openRequest();
		$result['data2'] = $this->StudentModel->closedRequest();
		$result['data3'] = $this->StudentModel->docReqCountsV2();   // ok to keep even if unused
		$result['data4'] = $this->StudentModel->totalReleased();

		$result['data19'] = $this->StudentModel->studeRequestListV2();

		$result['tx'] = $this->StudentModel->recentDocRequestTx(25, 30);

		// $result['byRequester'] = $this->StudentModel->docsByRequester();

		$this->load->view('dashboard_request', $result);
	}


	function releasedRequest()
	{
		$result['data'] = $this->StudentModel->releasedRequest();
		$this->load->view('request_released', $result);
	}

	//delete student's profile
	public function deleteRequest()
	{
		$id = $this->input->get('id');
		$username = $this->session->userdata('username');
		date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
		$now = date('H:i:s A');
		$date = date("Y-m-d");
		$query = $this->db->query("delete from stude_request where trackingNo='" . $id . "'");
		$query = $this->db->query("insert into atrail values('','Deleted Student''s Request','$date','$now','$username','$id')");
		redirect('Page/profileList');
	}

	//deny enrollment
	function denyEnrollment()
	{
		$this->load->view('enrollment_form_deny');
		if ($this->input->post('submit')) {
			//get data from the form
			$email = $this->input->get('email');
			$StudentNumber = $this->input->post('StudentNumber');
			$FName = $this->input->post('FName');
			$MName = $this->input->post('MName');
			$LName = $this->input->post('LName');
			$reason = $this->input->post('reason');

			date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
			$timeDenied = date('H:i:s A');
			$dateDenied = date("Y-m-d");
			$processor = $this->session->userdata('username');
			$sem = $this->session->userdata('semester');
			$sy = $this->session->userdata('sy');

			//save denial
			//$que=$this->db->query("insert into semesterstude values('','$StudentNumber','$FName','$MName','$LName','$Course','$YearLevel','Enrolled','$Semester','$SY','Term','$Section','$StudeStatus','','','','','','','0','$YearLevelStat','$Major','1','$EnrolledDate','','','','','','','')");
			$que = $this->db->query("insert into online_enrollment_deny values('','$StudentNumber','$FName','$MName','$LName','$reason','$dateDenied','$timeDenied','$processor','$sem','$sy')");
			$que1 = $this->db->query("update online_enrollment set enrolStatus='Denied' where StudentNumber='$StudentNumber' and Semester='$sem' and SY='$sy'");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Denied successfully! </b></div>');

			redirect('Page/forValidation');
		}
	}


	function deniedPayments()
	{
		$result['data'] = $this->StudentModel->deniedPayments();
		$this->load->view('denied_payments', $result);
	}


	function voidORs()
	{
		$result['data'] = $this->StudentModel->voidORs();
		$this->load->view('voidORs', $result);
	}


	function report_rog()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->report_rog($StudeNo, $sy, $sem);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_rog', $result);
	}





	function report_cogmc()
	{
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->report_cogmc($StudeNo);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_cogmc', $result);
	}

	function honor_dis()
	{
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->honor_dis($StudeNo);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_honor_dis', $result);
	}


	function report_coe()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->report_coe($StudeNo, $sy, $sem);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_coe', $result);
	}




	function studProf()
	{
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->studProf($StudeNo);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_studentsProfile', $result);
	}

	function coR()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$StudeNo = $this->input->get('StudeNo');
		$result['data'] = $this->StudentModel->report_coR($StudeNo, $sy, $sem);
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$this->load->view('report_coR', $result);
	}

	// this code by renren please note for all changes

	function curriculum()
	{
		$result['course'] = $this->input->post('course');
		$result['major'] = $this->input->post('major');
		$result['sy'] = $this->input->post('sy');

		$stud = $this->input->post('stud');
		$course = $this->input->post('course');
		$sy = $this->input->post('sy');
		$major = $this->input->post('major');

		$result['stud'] = $this->Ren_model->one_cond_row('studeprofile', 'StudentNumber', $stud);

		$result['sub_sem1_yl1'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'First Semester', 'YearLevel', '1st', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sub_sem2_yl1'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Second Semester', 'YearLevel', '1st', 'Effectivity', $sy, 'SubjectCode,description');
		$result['summer_yl1'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '1st', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sc_yl1'] = $this->Ren_model->three_cond_count('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '1st');


		$result['sub_sem1_yl2'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'First Semester', 'YearLevel', '2nd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sub_sem2_yl2'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Second Semester', 'YearLevel', '2nd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['summer_yl2'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '2nd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sc_yl2'] = $this->Ren_model->three_cond_count('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '2nd');

		$result['sub_sem1_yl3'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'First Semester', 'YearLevel', '3rd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sub_sem2_yl3'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Second Semester', 'YearLevel', '3rd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['summer_yl3'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '3rd', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sc_yl3'] = $this->Ren_model->three_cond_count('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '3rd');

		$result['sub_sem1_yl4'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'First Semester', 'YearLevel', '4th', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sub_sem2_yl4'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Second Semester', 'YearLevel', '4th', 'Effectivity', $sy, 'SubjectCode,description');
		$result['summer_yl4'] = $this->Ren_model->four_cond_group('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '4th', 'Effectivity', $sy, 'SubjectCode,description');
		$result['sc_yl4'] = $this->Ren_model->three_cond_count('subjects', 'Course', $course, 'Semester', 'Summer', 'YearLevel', '4th');


		$this->load->view('cur.php', $result);
	}

	function evaluation()
	{

		$result['data'] = $this->Ren_model->no_cond_loop_order_group('studeprofile', 'LastName', 'ASC', 'StudentNumber');
		$result['course'] = $this->Ren_model->no_cond_group('course_table', 'CourseDescription');
		$result['major'] = $this->Ren_model->no_cond('course_table');
		$result['sy'] = $this->Ren_model->no_cond_group('subjects', 'SYEffective');

		$this->load->view('cur_evaluation', $result);
	}

	function document_verifier()
	{
		$data['title'] = 'Document Verifier';

		$data['data'] = $this->Ren_model->no_cond('document_verifier');

		$this->load->view('odv', $data);

		if ($this->input->post('submit')) {
			$this->Ren_model->new_document();
			$this->session->set_flashdata('success', ' New Document Entry.');
			//redirect(base_url() . 'document_verifier');
		}
	}

	function document_verifier_add()
	{
		$this->Ren_model->new_document();
		$this->session->set_flashdata('success', ' New Document Entry.');
		redirect(base_url() . 'Page/document_verifier');
	}

	function document_verifier_qr()
	{
		$data['data'] = $this->Ren_model->one_cond_row('document_verifier', 'id', $this->uri->segment(3));

		$this->load->view('qr', $data);
	}
	function verify()
	{
		$data['data'] = $this->Ren_model->one_cond_row('document_verifier', 'id', $this->uri->segment(3));

		$this->load->view('qr_verify', $data);
	}

	function grades()
	{

		$result['title'] = "STUDENTS' GRADES";
		$sem = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');

		$result['data'] = $this->StudentModel->getGrades($sem, $sy);
		$this->load->view('grades', $result);
	}

	public function grades_for_uploading()
	{
		$result['title'] = "SELECT SUBJECTs FOR GRADES UPLOADING";

		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->slotsMonitoring($sem, $sy);
		$this->load->view('grades_for_uploading', $result);
	}

	public function grades_upload()
	{
		$result['title'] = "GRADES UPLOADING";

		if ($this->input->post('upload') !== NULL) {
			$data = array();

			if (!empty($_FILES['file']['name'])) {
				// Set upload preferences
				$config = array(
					'upload_path'   => 'upload/grades/',
					'allowed_types' => 'csv',
					'max_size'      => '10000', // in kb
					'file_name'     => $_FILES['file']['name']
				);

				// Load upload library
				$this->load->library('upload', $config);

				// Perform file upload
				if ($this->upload->do_upload('file')) {
					// Get file data
					$uploadData = $this->upload->data();
					$filename = $uploadData['file_name'];
					$filePath = $config['upload_path'] . $filename;

					// Read file
					$importDataArr = array();
					if (($file = fopen($filePath, "r")) !== FALSE) {
						while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
							$importDataArr[] = $filedata;
						}
						fclose($file);
					}

					// Insert import data
					foreach ($importDataArr as $index => $userdata) {
						if ($index > 0) { // Skip header row
							$this->StudentModel->gradesUploading($userdata);
						}
					}
					// $data['response'] = '<div class="alert alert-info text-center">Uploaded Successfully.</div>';
					$this->session->set_flashdata('success', ' Uploaded Successfully.');
				} else {
					$data['response'] = '<div class="alert alert-warning text-center">Upload Failed: ' . $this->upload->display_errors() . '</div>';
				}
			} else {
				$data['response'] = '<div class="alert alert-warning text-center">No file selected.</div>';
			}

			// Load view with data
			$this->load->view('grades_upload', $result);
		} else {
			// Load view without data
			$this->load->view('grades_upload', $result);
		}
	}




	function pr()
	{
		$sy = $this->input->post('sy');
		$sem = $this->input->post('sem');

		$course = $this->input->post('course');
		$yearLevel = $this->input->post('yl');

		$result['sy'] = $this->input->post('sy');
		$result['sem'] = $this->input->post('sem');

		$result['data'] = $this->Ren_model->get_students($sy, $sem, $course, $yearLevel);
		//$result['data'] = $this->Ren_model->get_students_with_grades_join($sy, $sem,$course,$yearLevel);
		$this->load->view('pr', $result);
	}

	function er()
	{
		$sy = $this->input->post('sy');
		$sem = $this->input->post('sem');
		$result['sy'] = $this->input->post('sy');
		$result['sem'] = $this->input->post('sem');

		$result['data'] = $this->Ren_model->get_registration_data($sy, $sem);
		$this->load->view('er', $result);
	}


	public function get_majors_by_course()
	{
		$course = $this->input->post('course');
		$majors = $this->StudentModel->getMajorsByCourse($course);
		echo json_encode($majors);
	}


	public function get_sections_by_course_yearlevel()
	{
		$course = $this->input->post('course');
		$yearLevel = $this->input->post('yearLevel');
		$major = $this->input->post('major'); // ✅ get major

		$sections = $this->StudentModel->getSectionsByCourseYearLevel($course, $yearLevel, $major);
		echo json_encode($sections);
	}




	// Fetch signup students not yet in studeprofile
	public function getSignupStudents()
	{
		$query = $this->db->query("
			SELECT StudentNumber, FirstName, LastName FROM studentsignup s
			WHERE NOT EXISTS (
				SELECT 1 FROM studeprofile p WHERE p.StudentNumber = s.StudentNumber
			)
		");
		$students = $query->result_array();
		$result = [];

		foreach ($students as $student) {
			$result[] = [
				'id' => $student['StudentNumber'],
				'text' => $student['StudentNumber'] . ' - ' . $student['FirstName'] . ' ' . $student['LastName']
			];
		}

		echo json_encode($result);
	}
	public function transferSelectedStudent()
	{
		if ($this->input->post('submit')) {
			$studentNumber = $this->input->post('studentNumber');

			$student = $this->StudentModel->getSignupStudentByNumber($studentNumber);

			if ($student) {
				// Prepare data for insertion into studeprofile table
				$data = array(
					'StudentNumber' => $student->StudentNumber,
					'FirstName' => $student->FirstName,
					'MiddleName' => $student->MiddleName,
					'LastName' => $student->LastName,
					'nameExtn' => $student->nameExtn ?: '',
					'Sex' => $student->Sex ?: '',
					'birthDate' => $student->birthDate ?: '0000-00-00',
					'age' => $student->age ?: '',
					'BirthPlace' => $student->BirthPlace ?: '',
					'contactNo' => $student->contactNo ?: '',
					'email' => $student->email ?: '',
					'CivilStatus' => $student->CivilStatus ?: '',
					'ethnicity' => $student->ethnicity ?: '',
					'Religion' => $student->Religion ?: '',
					'working' => $student->working ?: '',
					'VaccStat' => $student->VaccStat ?: '',
					'province' => $student->province ?: '',
					'city' => $student->city ?: '',
					'brgy' => $student->brgy ?: '',
					'sitio' => $student->sitio ?: '',
					'occupation' => $student->occupation ?: '',
					'salary' => $student->salary ?: '',
					'employer' => $student->employer ?: '',
					'employerAddress' => $student->employerAddress ?: '',
					'guardian' => $student->guardian ?: '',
					'guardianRelationship' => $student->guardianRelationship ?: '',
					'guardianContact' => $student->guardianContact ?: '',
					'guardianAddress' => $student->guardianAddress ?: '',
					'spouse' => $student->spouse ?: '',
					'spouseRelationship' => $student->spouseRelationship ?: '',
					'spouseContact' => $student->spouseContact ?: '',
					'children' => $student->children ?: '',
					'spouseIncome' => $student->spouseIncome ?: '',
					'imagePath' => $student->imagePath ?: '',
					'yearLevel' => $student->yearLevel ?: '',
					'father' => $student->father ?: '',
					'fOccupation' => $student->fOccupation ?: '',
					'fatherAddress' => $student->fatherAddress ?: '',
					'fatherContact' => $student->fatherContact ?: '',
					'fatherBDate' => $student->fatherBDate ?: '0000-00-00',
					'fatherAge' => $student->fatherAge ?: '',
					'mother' => $student->mother ?: '',
					'mOccupation' => $student->mOccupation ?: '',
					'motherAddress' => $student->motherAddress ?: '',
					'motherContact' => $student->motherContact ?: '',
					'motherBDate' => $student->motherBDate ?: '0000-00-00',
					'motherAge' => $student->motherAge ?: '',
					'siblings' => $student->siblings ?: '',
					'birthOrder' => $student->birthOrder ?: '',
					'title' => $student->title ?: '',
					'pronoun' => $student->pronoun ?: '',
					'pronoun2' => $student->pronoun2 ?: '',
					'pronoun3' => $student->pronoun3 ?: '',
					'scholarship' => $student->scholarship ?: '',
					'fourPs' => $student->fourPs ?: '',
					'seniorCitizen' => $student->seniorCitizen ?: '',
					'als' => $student->als ?: '',
					'disability' => $student->disability ?: '',
					'parentsMonthly' => $student->parentsMonthly ?: '',
					'elementary' => $student->elementary ?: '',
					'elementaryAddress' => $student->elementaryAddress ?: '',
					'elemGraduated' => $student->elemGraduated ?: '',
					'elemMerits' => $student->elemMerits ?: '',
					'secondary' => $student->secondary ?: '',
					'secondaryAddress' => $student->secondaryAddress ?: '',
					'secondaryGraduated' => $student->secondaryGraduated ?: '',
					'secondaryMerits' => $student->secondaryMerits ?: '',
					'vocational' => $student->vocational ?: '',
					'vocationalAddress' => $student->vocationalAddress ?: '',
					'vocationalGraduated' => $student->vocationalGraduated ?: '',
					'vocationalCourse' => $student->vocationalCourse ?: '',
					'nationality' => $student->nationality ?: '',
					'settingsID' => '1'
				);

				// Insert into studeprofile table
				$this->StudentModel->insertStudeProfile($data);

				// ✅ Update Status in studentsignup to "Verified"
				$this->StudentModel->updateSignupStatus($studentNumber, 'Verified');

				$auditData = [
					'atDesc' => 'Transferred student from signup to profile',
					'atDate' => date('Y-m-d'),
					'atTime' => date('H:i:s'),
					'atRes' => $this->session->userdata('username'), // or 'username'
					'atSNo' => $studentNumber
				];
				$this->db->insert('atrail', $auditData);

				// Redirect with success message
				$this->session->set_flashdata('success', 'Student successfully transferred.');
				redirect('Page/profileList');
			} else {
				$this->session->set_flashdata('error', 'Student not found.');
				redirect('Page/profileList');
			}
		} else {
			show_404();
		}
	}




	public function transferSignupToProfile()
	{
		// Get records from studentsignup that don't exist yet in studeprofile
		$query = $this->db->query("
			SELECT * FROM studentsignup s
			WHERE NOT EXISTS (
				SELECT 1 FROM studeprofile p WHERE p.StudentNumber = s.StudentNumber
			)
		");

		$students = $query->result_array();

		$transferred = 0;

		foreach ($students as $student) {
			// Define the column names you want to copy
			$columns = array(
				'StudentNumber',
				'FirstName',
				'MiddleName',
				'LastName',
				'nameExtn',
				'Sex',
				'birthDate',
				'age',
				'BirthPlace',
				'contactNo',
				'email',
				'CivilStatus',
				'ethnicity',
				'Religion',
				'working',
				'VaccStat',
				'province',
				'city',
				'brgy',
				'sitio',
				'occupation',
				'salary',
				'employer',
				'employerAddress',
				'guardian',
				'guardianRelationship',
				'guardianContact',
				'guardianAddress',
				'spouse',
				'spouseRelationship',
				'spouseContact',
				'children',
				'spouseIncome',
				'imagePath',
				'yearLevel',
				'father',
				'fOccupation',
				'fatherAddress',
				'fatherContact',
				'fatherBDate',
				'fatherAge',
				'mother',
				'mOccupation',
				'motherAddress',
				'motherContact',
				'motherBDate',
				'motherAge',
				'siblings',
				'birthOrder',
				'title',
				'pronoun',
				'pronoun2',
				'pronoun3',
				'scholarship',
				'fourPs',
				'seniorCitizen',
				'als',
				'disability',
				'parentsMonthly',
				'elementary',
				'elementaryAddress',
				'elemGraduated',
				'elemMerits',
				'secondary',
				'secondaryAddress',
				'secondaryGraduated',
				'secondaryMerits',
				'vocational',
				'vocationalAddress',
				'vocationalGraduated',
				'vocationalCourse',
				'nationality'
			);

			// Initialize data array
			$data = array();

			// Copy each column's value or save as empty string if none
			foreach ($columns as $column) {
				$data[$column] = isset($student[$column]) ? $student[$column] : '';
			}

			// Insert into studeprofile
			$this->db->insert('studeprofile', $data);
			$transferred++;
		}

		// Set flash message
		if ($transferred > 0) {
			$this->session->set_flashdata('message', "$transferred signup record(s) successfully transferred to student profiles.");
		} else {
			$this->session->set_flashdata('message', "No new signup records to transfer.");
		}

		// Redirect after transfer
		redirect('Page/profileList');
	}
	public function soa_college()
	{
		if (!$this->session->userdata('username')) {
			redirect('Login');
			return;
		}

		$userLevel = $this->session->userdata('level');
		$sy        = $this->session->userdata('sy');
		$semester  = $this->session->userdata('semester');

		if ($userLevel === 'Student') {
			$studentNumber = $this->session->userdata('username');
		} else {
			$studentNumber = $this->input->get('id');
			if (empty($studentNumber)) {
				show_error('Missing student number.', 400);
				return;
			}
		}

		$this->load->model('CollegeStudentModel');
		$this->load->model('Login_model');

		$result                     = [];
		$result['selectedSY']       = $sy;
		$result['school']           = $this->Login_model->getSchoolInformation();
		$result['school']           = !empty($result['school']) ? $result['school'][0] : null;

		$result['data']             = $this->CollegeStudentModel->getStudentAccount($studentNumber, $sy);
		$result['data1']            = $this->CollegeStudentModel->getAdditionalFees($studentNumber, $sy);
		$result['data2']            = $this->CollegeStudentModel->getDiscounts($studentNumber, $sy);
		$result['data3']            = $this->CollegeStudentModel->getPaymentHistory($studentNumber, $sy);
		$result['student']          = $this->CollegeStudentModel->getStudentProfile($studentNumber);

		$result['yearLevel']        = isset($result['data'][0]) ? ($result['data'][0]->YearLevel ?? 'Not Set') : 'Not Set';
		$result['isStudent']        = ($userLevel === 'Student');

		$this->load->view('account_statement_college', $result);
	}
	// application/controllers/Page.php

	public function verifyOnlinePayment($id)
	{
		// Gate if you have role checks
		if ($this->session->userdata('position') !== 'Accounting') {
			show_error('Access denied.', 403);
		}

		$op = $this->db->get_where('online_payments', ['id' => $id])->row();
		if (!$op) {
			$this->session->set_flashdata('failed', 'Payment not found.');
			return redirect('Page/proof_payment_view');
		}
		if ($op->status !== 'PENDING') {
			$this->session->set_flashdata('failed', 'Only PENDING payments can be verified.');
			return redirect('Page/proof_payment_view');
		}

		$this->db->trans_start();

		// A) Flip student-side status to VERIFIED
		$this->db->where('id', $op->id)->update('online_payments', [
			'status'     => 'VERIFIED',
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		// (Optional) pull name if you actually store names in paymentsaccounts
		$sp = $this->db->select('FirstName, MiddleName, LastName')
			->from('studeprofile')
			->where("CONVERT(StudentNumber USING utf8mb4) = '" . $this->db->escape_str($op->StudentNumber) . "'", null, false)
			->get()->row();

		// B) Post to paymentsaccounts (OR = refNo; PaymentType = ONLINE)
		$this->db->insert('paymentsaccounts', [
			'StudentNumber'    => $op->StudentNumber,
			'FirstName'        => $sp->FirstName  ?? '',
			'MiddleName'       => $sp->MiddleName ?? '',
			'LastName'         => $sp->LastName   ?? '',
			'PDate'            => date('Y-m-d'),
			'ORNumber'         => $op->refNo,
			'Amount'           => (float)$op->amount,
			'description'      => $op->description,
			'PaymentType'      => 'ONLINE',
			'CheckNumber'      => '',
			'Sem'              => $op->sem ?: '',
			'SY'               => $op->sy  ?: '',
			'CollectionSource' => 'Online Portal',
			'Bank'             => '',
			'ORStatus'         => 'Valid',
			'Cashier'          => $this->session->userdata('user') ?: 'System',
			'pTime'            => date('H:i:s'),
		]);

		// C) (Optional) call your recompute service for balances here

		$this->db->trans_complete();

		$flash = $this->db->trans_status() ? 'Payment verified and posted.' : 'Verification failed.';
		$key   = $this->db->trans_status() ? 'success' : 'failed';
		$this->session->set_flashdata($key, $flash);
		return redirect('Page/proof_payment_view');
	}

















	public function create_teacher_accts()
	{
		$result = $this->StudentModel->insert_teachers(); // returns ['ok'=>bool,'inserted'=>int]

		if (!$result['ok']) {
			$this->session->set_flashdata('danger', 'There was an error inserting record. Please try again.');
		} elseif ($result['inserted'] > 0) {
			$this->session->set_flashdata('success', $result['inserted'] . ' personnel account(s) have been inserted successfully!');
		} else {
			$this->session->set_flashdata('warning', 'No new personnel to insert. Everyone already has an account.');
		}

		redirect('Page/userAccounts');
	}


	public function create_stude_accts()
	{
		$result = $this->StudentModel->insert_students(); // ['ok'=>bool,'inserted'=>int]

		if (!$result['ok']) {
			$this->session->set_flashdata('danger', 'There was an error inserting students. Please try again.');
		} elseif ($result['inserted'] > 0) {
			$this->session->set_flashdata('success', $result['inserted'] . ' student account(s) inserted successfully!');
		} else {
			$this->session->set_flashdata('warning', 'No new students to insert. Everyone already has an account.');
		}

		redirect('Page/userAccounts');
	}




	// Page.php
	public function facultyLoadPage()
	{
		if ($this->session->userdata('level') !== 'Instructor') {
			show_error('Access Denied', 403);
			return;
		}

		$id  = (string)$this->session->userdata('username');   // IDNumber
		$sy  = (string)$this->session->userdata('sy');
		$sem = (string)$this->session->userdata('semester');

		$this->load->model('InstructorModel');

		$data = [];

		$data['data2'] = $this->InstructorModel->facultyLoad($id, $sy, $sem);
		$data['gs']    = $this->Common->one_cond_row('grades_settings', 'id', 1);
		$data['sy']    = $sy;
		$data['sem']   = $sem;

		if (empty($data['data2'])) {
			$this->db->from('semsubjects');
			$this->db->where('IDNumber', $id);
			$this->db->where('SY', $sy);
			$this->db->where('Semester', $sem);
			$this->db->order_by('Section, Description');
			$data['data2'] = $this->db->get()->result();
		}

		// NEW: fetch with_classrecord
		$settingsRow = $this->db->get_where('o_srms_settings', ['settingsID' => 1])->row();
		if (!$settingsRow) {
			$settingsRow = $this->db->get_where('srms_settings_o', ['settingsID' => 1])->row();
		}
		$data['with_classrecord'] = (int)($settingsRow->with_classrecord ?? 0);

		$this->load->view('faculty_load_view', $data);
	}






	public function enrolledStudentsPage()
	{
		// --- Session basics
		$sy   = (string) $this->session->userdata('sy');
		$sem  = (string) $this->session->userdata('semester');
		$phID = (string) $this->session->userdata('username');

		// ---- SAFE DEFAULTS
		$courseVal      = null;
		$majorVal       = null;
		$students       = [];
		$studentsByYear = [];
		$sectionsByYear = [];
		$flagCounts     = [];   // << NEW: total/unsettled per student

		// --- Resolve PH program
		$prog = $this->db->where('IDNumber', $phID)->get('course_table')->row();
		if (!$prog) {
			$data = [
				'courseDescription' => $courseVal,
				'major'             => $majorVal,
				'students'          => $students,
				'studentsByYear'    => $studentsByYear,
				'sectionsByYear'    => $sectionsByYear,
				'studentCount'      => 0,
				'sy'                => $sy,
				'sem'               => $sem,
				'flagCounts'        => $flagCounts, // << NEW
			];
			return $this->load->view('enrolled_students_view1', $data);
		}

		$courseVal = (string) $prog->CourseDescription;
		$majorVal  = isset($prog->Major) ? (string) $prog->Major : '';

		// --- Fetch students
		$this->load->model('StudentModel');
		if (method_exists($this->StudentModel, 'bySYCourseMajor')) {
			$students = $this->StudentModel->bySYCourseMajor($sy, $sem, $courseVal, $majorVal);
			if (!is_array($students)) $students = (array)$students;
		} else {
			$this->db->where('SY', $sy)
				->where('Semester', $sem)
				->where('Status', 'Enrolled')
				->where('Course', $courseVal);
			if ($majorVal !== '') $this->db->where('Major', $majorVal);
			$students = $this->db->get('semesterstude')->result();
			if (!is_array($students)) $students = (array)$students;
		}

		// --- Group for summaries
		foreach ($students as $s) {
			$yl  = isset($s->YearLevel) ? (string)$s->YearLevel : 'N/A';
			$sec = isset($s->Section)   ? (string)$s->Section   : 'N/A';

			if (!isset($studentsByYear[$yl]))           $studentsByYear[$yl] = [];
			if (!isset($sectionsByYear[$yl]))           $sectionsByYear[$yl] = [];
			if (!isset($sectionsByYear[$yl][$sec]))     $sectionsByYear[$yl][$sec] = 0;

			$studentsByYear[$yl][] = $s;
			$sectionsByYear[$yl][$sec]++;
		}

		// --- Flags (one query for all students)
		$sns = [];
		foreach ($students as $s) {
			if (!empty($s->StudentNumber)) $sns[] = (string)$s->StudentNumber;
		}
		$sns = array_values(array_unique($sns));

		if (!empty($sns)) {
			$this->load->model('FlagModel'); // expects counts_for_students()
			if (method_exists($this->FlagModel, 'counts_for_students')) {
				// expected: [ "2025xxxx" => ["total"=>N, "unsettled"=>M], ... ]
				$flagCounts = $this->FlagModel->counts_for_students($sns) ?: [];
			} else {
				// fallback if you don't have a helper; compute counts from table
				$rows = $this->db->select('StudentNumber, status')
					->from('student_flags')
					->where_in('StudentNumber', $sns)
					->where('status <>', 'Deleted') // optional
					->get()->result();

				// summarize
				foreach ($rows as $r) {
					$sn = (string)$r->StudentNumber;
					if (!isset($flagCounts[$sn])) $flagCounts[$sn] = ['total' => 0, 'unsettled' => 0];
					$flagCounts[$sn]['total']++;
					if (strcasecmp((string)$r->status, 'Active') === 0) {
						$flagCounts[$sn]['unsettled']++;
					}
				}
			}
		}

		// --- View
		$data = [
			'courseDescription' => $courseVal,
			'major'             => $majorVal,
			'students'          => $students,
			'studentsByYear'    => $studentsByYear,
			'sectionsByYear'    => $sectionsByYear,
			'studentCount'      => count($students),
			'sy'                => $sy,
			'sem'               => $sem,
			'flagCounts'        => $flagCounts, // << NEW
		];

		$this->load->view('enrolled_students_view1', $data);
	}

































	public function save_grades()
	{
		$this->form_validation->set_error_delimiters(
			'<div class="alert alert-danger alert-dismissible fade show" role="alert">
         <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
         </button>',
			'</div>'
		);
		$this->form_validation->set_rules('sn', 'Student', 'required');

		$page = "mg_save";
		if (!file_exists(APPPATH . 'views/' . $page . '.php')) show_404();

		$data['title'] = "Solo Save Grades (College)";

		// Active SY from session
		$sy        = $this->get_active_sy();
		$userLevel = (string)($this->session->userdata('level') ?? '');
		$instrID   = (string)($this->session->userdata('IDNumber') ?? '');

		$this->load->model('Ren_model');
		$this->load->model('Common');

		// Determine if current user is an Instructor/Teacher
		$isInstr = ((strcasecmp($userLevel, 'Instructor') === 0 || strcasecmp($userLevel, 'Teacher') === 0) && !empty($instrID));

		// Populate student dropdown
		if ($isInstr) {
			$data['studs'] = $this->Ren_model->get_students_with_registration_for_teacher_subjects($sy, $instrID);
		} else {
			$data['studs'] = $this->Ren_model->get_students_with_registration($sy);
		}

		// College period labels
		$data['grading_labels'] = [
			'first'  => 'Prelim',
			'second' => 'Midterm',
			'third'  => 'Pre-Final',
			'fourth' => 'Final',
			'all'    => 'All Periods'
		];

		// First render (no submission yet) or validation failed
		if ($this->form_validation->run() === FALSE) {
			$this->load->view($page, $data);
			return;
		}

		// POSTed values
		$sn      = $this->input->post('sn', TRUE);
		$grading = $this->input->post('grading', TRUE) ?: 'all';

		// Security: Instructors can only access their own students
		if ($isInstr) {
			$owns = $this->Ren_model->instructor_owns_student($sy, $instrID, $sn);
			if (!$owns) {
				$this->session->set_flashdata('danger', 'Unauthorized: you can only access students under your load.');
				redirect('Page/save_grades');
				return;
			}
		}

		// Load student
		$data['stud']       = $this->Common->one_cond_row('studeprofile', 'StudentNumber', $sn);
		$data['grading']    = $grading;
		$data['active_sy']  = $sy;

		// Fetch rows (includes Course, Major, LecUnit, LabUnit via JOIN)
		$data['reg_rows']   = $this->Ren_model->get_registration_rows_pending_for_grading(
			$sy,
			$sn,
			$grading,
			$isInstr ? $instrID : null
		);

		$this->load->view($page, $data);
	}

	/**
	 * Insert/update grades into grades_o (Semester-aware).
	 * Saves Course, Major, LecUnit, LabUnit as requested.
	 */
	public function save_batch_studgrade()
	{
		$this->load->model('Ren_model');

		$sn          = $this->input->post('id', TRUE);
		$grading     = $this->input->post('grading', TRUE) ?: 'all';

		// Arrays (some indexed, some keyed by regID)
		$regIDs      = $this->input->post('regID');
		$SubjectCode = $this->input->post('SubjectCode');
		$Description = $this->input->post('Description');
		$Instructor  = $this->input->post('Instructor');

		$Course      = $this->input->post('Course');   // NEW
		$Major       = $this->input->post('Major');    // NEW
		$LecUnit     = $this->input->post('LecUnit');  // NEW
		$LabUnit     = $this->input->post('LabUnit');  // NEW

		$SY          = $this->input->post('SY');
		$YearLevel   = $this->input->post('YearLevel');
		$Section     = $this->input->post('Section');
		$Semester    = $this->input->post('Semester'); // REQUIRED

		$PGrade      = $this->input->post('PGrade');       // keyed by regID
		$MGrade      = $this->input->post('MGrade');       // keyed by regID
		$PFinalGrade = $this->input->post('PFinalGrade');  // keyed by regID
		$FGrade      = $this->input->post('FGrade');       // keyed by regID

		if (empty($regIDs) || !is_array($regIDs)) {
			$this->session->set_flashdata('danger', 'Nothing to save.');
			redirect('Page/save_grades');
			return;
		}

		// Role context
		$sy       = $this->get_active_sy();
		$userLvl  = (string)($this->session->userdata('level') ?? '');
		$instrID2 = (string)($this->session->userdata('IDNumber') ?? '');
		$isInstr2 = ((strcasecmp($userLvl, 'Instructor') === 0 || strcasecmp($userLvl, 'Teacher') === 0) && !empty($instrID2));

		// If Instructor, fetch allowed regnumbers once
		$allowedIds = null;
		if ($isInstr2) {
			$allowedIds = $this->Ren_model->get_allowed_reg_ids($sy, $sn, $instrID2); // array<int>
		}

		$rows = [];
		$encoderID = $this->session->userdata('IDNumber') ?? ''; // stored to grades_o.IDNumber
		$saved_any = false;

		foreach ($regIDs as $i => $rid) {
			$ridInt = (int)$rid;

			// Security: Instructors can only save rows they teach
			if ($isInstr2 && (!is_array($allowedIds) || !in_array($ridInt, $allowedIds, true))) {
				// silently skip unauthorized row
				continue;
			}

			// Normalize grades depending on selected period
			$p = 0;
			$m = 0;
			$pf = 0;
			$f = 0;
			if ($grading === 'first')      $p  = $this->norm_grade_val($PGrade[$ridInt]      ?? null);
			elseif ($grading === 'second') $m  = $this->norm_grade_val($MGrade[$ridInt]      ?? null);
			elseif ($grading === 'third')  $pf = $this->norm_grade_val($PFinalGrade[$ridInt] ?? null);
			elseif ($grading === 'fourth') $f  = $this->norm_grade_val($FGrade[$ridInt]      ?? null);
			else {
				$p  = $this->norm_grade_val($PGrade[$ridInt]      ?? null);
				$m  = $this->norm_grade_val($MGrade[$ridInt]      ?? null);
				$pf = $this->norm_grade_val($PFinalGrade[$ridInt] ?? null);
				$f  = $this->norm_grade_val($FGrade[$ridInt]      ?? null);
			}

			$avg = round(($p + $m + $pf + $f) / 4, 2);

			$rows[] = [
				'StudentNumber' => $sn,
				'Course'        => $Course[$i]      ?? '',
				'Major'         => $Major[$i]       ?? '',
				'YearLevel'     => $YearLevel[$i]   ?? '',
				'Section'       => $Section[$i]     ?? '',
				'SubjectCode'   => $SubjectCode[$i] ?? '',
				'Description'   => $Description[$i] ?? '',
				'LecUnit'       => $LecUnit[$i]     ?? '',
				'LabUnit'       => $LabUnit[$i]     ?? '',
				'IDNumber'      => $encoderID,      // encoder
				'Prelim'        => $p,
				'Midterm'       => $m,
				'PreFinal'      => $pf,
				'Final'         => $f,
				'Complied'      => '',
				'SY'            => $SY[$i]          ?? $sy,
				'Semester'      => $Semester[$i]    ?? '',
				'timeEncoded'   => date('H:i:s'),
				'dateEncoded'   => date('Y-m-d'),
				'TakenAt'       => '',
				'settingsID'    => 1,
				'Average'       => $avg,
			];
			$saved_any = true;
		}

		if (!$saved_any) {
			$this->session->set_flashdata('danger', 'No authorized rows to save or nothing to encode.');
			redirect('Page/save_grades');
			return;
		}

		// Upsert into grades_o (Semester-aware)
		$result = $this->Ren_model->upsert_grades_o_for_pending($rows);

		// Audit trail
		$this->Ren_model->atrail_insert_grades(
			"Solo save to grades_o ({$grading}): {$result['inserted']} inserted, {$result['updated']} updated, {$result['skipped']} skipped",
			'grades_o',
			$sn
		);

		// Feedback
		$msg = ($result['updated'] > 0 && $result['inserted'] == 0)
			? 'Successfully updated.'
			: 'Successfully saved.';
		$this->session->set_flashdata('success', $msg);
		return redirect('Page/save_grades');
	}

	/* ----------------------------- Helpers ------------------------------ */

	private function get_active_sy()
	{
		return $this->session->sy ?: $this->session->userdata('sy') ?: $this->session->userdata('SY');
	}

	// Treat empty / non-numeric as 0; keep numbers as float
	private function norm_grade_val($v)
	{
		return is_numeric($v) ? (float)$v : 0;
	}

	public function editSignup($id = null)
	{
		$id = $id ?: $this->input->get('id') ?: $this->uri->segment(3);

		if (empty($id)) {
			show_404(); // If no ID is found, show 404
			return;
		}

		// Fetch the student data based on the ID
		$student = $this->StudentModel->getstudentsignupbyId($id);  // Get student data

		// Check if data is found
		if (empty($student)) {
			show_error('Student not found');
			return;
		}

		if ($this->input->method(true) === 'POST') {

			// AUDIT: blocked update attempt (read-only)
			$this->AuditLogModel->write(
				'update',
				'Signup',
				'studentsignup',
				(string)$id,
				null,
				null,
				0,
				'Blocked edit attempt on read-only signup view'
			);

			$this->session->set_flashdata('danger', 'Editing student profiles is disabled for administrators.');
			redirect('Page/profileList');
			return;
		}


		// Fetch options for dropdowns (Course, Major, Year Level, Section)
		$result['courses'] = $this->StudentModel->get_courseTable();  // Fetch courses
		$result['majors'] = $this->StudentModel->get_majors($student->Course1);  // Get majors based on Course1
		$result['sections'] = $this->StudentModel->get_sections($student->Course1, $student->yearLevel);  // Get sections based on Course1 and YearLevel
		$result['yearLevels'] = $this->StudentModel->get_year_levels();  // Fetch year levels

		// Location lists for read-only display
		$province = trim((string)($student->Province ?? $student->province ?? ''));
		$city     = trim((string)($student->City ?? $student->city ?? ''));

		$result['provinces'] = $this->StudentModel->get_provinces();
		$result['cities']    = $this->StudentModel->get_cities($province);
		$result['barangays'] = $city !== '' ? $this->StudentModel->get_barangays($city) : [];

		// Pass the student data for the view
		$result['data']      = $student;  // Pass the student object to the view
		$result['readOnly']  = true;
		$this->load->view('profile_form_update', $result);
	}



	public function manageSections()
	{
		// Fetch all sections and available courses for the modal dropdown
		$data['sections']   = $this->CourseSectionModel->getAllSections();
		$data['courses']    = $this->CourseSectionModel->getCourses();
		$data['yearLevels'] = $this->CourseSectionModel->getYearLevels();

		if (empty($data['yearLevels'])) {
			$defaults = ['1st', '2nd', '3rd', '4th'];
			$data['yearLevels'] = array_map(static function ($lvl) {
				return (object)['year_level' => $lvl];
			}, $defaults);
		}

		$this->load->view('manage_sections', $data);
	}
	public function addSection()
	{
		if ($this->input->post()) {
			// Get data from the form
			$sectionData = [
				'courseid'   => $this->input->post('courseid'),
				'year_level' => $this->input->post('year_level'),
				'section'    => $this->input->post('section'),
				'is_active'  => 1
			];

			// Insert data into the database (your model)
			$inserted = $this->CourseSectionModel->addSection($sectionData);

			// AUDIT: create section
			$this->AuditLogModel->write(
				'create',
				'Sections',
				'course_sections',     // adjust if your actual table name differs
				null,                  // record_pk unknown (model hides it); okay to leave null
				null,
				$sectionData,
				$inserted ? 1 : 0,
				$inserted ? 'Created section' : 'Failed to create section'
			);

			if ($inserted) {
				$this->session->set_flashdata('success', 'Section added successfully.');
			} else {
				$this->session->set_flashdata('error', 'Failed to add the section. Please try again.');
			}
			redirect('Page/manageSections');
		} else {
			// Rare direct GET access to addSection: reload manage sections with data
			$data['sections']   = $this->CourseSectionModel->getAllSections();
			$data['courses']    = $this->CourseSectionModel->getCourses();
			$data['yearLevels'] = $this->CourseSectionModel->getYearLevels();

			if (empty($data['yearLevels'])) {
				$defaults = ['1st', '2nd', '3rd', '4th'];
				$data['yearLevels'] = array_map(static function ($lvl) {
					return (object)['year_level' => $lvl];
				}, $defaults);
			}

			$this->load->view('manage_sections', $data);
		}
	}
	public function editSection($id)
	{
		if ($this->input->post()) {
			// Snapshot old row BEFORE update
			$oldRow = $this->CourseSectionModel->getSectionById($id);
			$old = $oldRow ? [
				'courseid'   => $oldRow->courseid ?? null,
				'year_level' => $oldRow->year_level ?? null,
				'section'    => $oldRow->section ?? null,
				'is_active'  => $oldRow->is_active ?? null,
			] : null;

			// Get updated section data from the form
			$sectionData = [
				'courseid'   => $this->input->post('courseid'),
				'year_level' => $this->input->post('year_level'),
				'section'    => $this->input->post('section')
			];

			// Update the section in the database
			$updated = $this->CourseSectionModel->updateSection($id, $sectionData);

			// AUDIT: update section
			$this->AuditLogModel->write(
				'update',
				'Sections',
				'course_sections',   // adjust if needed
				(string)$id,
				$old,
				$sectionData,
				$updated ? 1 : 0,
				$updated ? 'Updated section' : 'Failed to update section'
			);

			if ($updated) {
				$this->session->set_flashdata('success', 'Section updated successfully.');
			} else {
				$this->session->set_flashdata('error', 'Failed to update the section. Please try again.');
			}
			redirect('Page/manageSections');
		} else {
			// Fetch section by ID for pre-filled form
			$data['section']    = $this->CourseSectionModel->getSectionById($id);
			$data['courses']    = $this->CourseSectionModel->getCourses();
			$data['yearLevels'] = $this->CourseSectionModel->getYearLevels();

			if (empty($data['yearLevels'])) {
				$defaults = ['1st', '2nd', '3rd', '4th'];
				$data['yearLevels'] = array_map(static function ($lvl) {
					return (object)['year_level' => $lvl];
				}, $defaults);
			}

			$this->load->view('edit_section', $data);
		}
	}

	public function deleteSection($id)
	{
		// Snapshot old row BEFORE delete
		$oldRow = $this->CourseSectionModel->getSectionById($id);
		$old = $oldRow ? [
			'courseid'   => $oldRow->courseid ?? null,
			'year_level' => $oldRow->year_level ?? null,
			'section'    => $oldRow->section ?? null,
			'is_active'  => $oldRow->is_active ?? null,
		] : null;

		$deleted = $this->CourseSectionModel->deleteSection($id);

		// AUDIT: delete section
		$this->AuditLogModel->write(
			'delete',
			'Sections',
			'course_sections',    // adjust if needed
			(string)$id,
			$old,
			null,
			$deleted ? 1 : 0,
			$deleted ? 'Deleted section' : 'Failed to delete section'
		);

		if ($deleted) {
			$this->session->set_flashdata('success', 'Section deleted successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to delete the section. Please try again.');
		}
		redirect('Page/manageSections');
	}
}

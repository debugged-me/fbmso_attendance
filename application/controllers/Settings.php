<?php
class Settings extends CI_Controller
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
		$this->load->model('Login_model');
		$this->load->library('user_agent');
		$this->load->model('AuditLogModel');

		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('login');
		}
	}

	public function view_logs()
	{
		$this->load->model('Login_model');
		$data['logs'] = $this->db->order_by('login_time', 'DESC')->get('login_logs')->result();

		// Passwords are never recoverable from login_logs. password_attempt now
		// holds a non-reversible peppered HMAC fingerprint, useful only for
		// spotting the same credential reused across accounts.
		foreach ($data['logs'] as $log) {
			$log->decrypted_password = '-';
		}

		$this->load->view('login_logs_view', $data); // make sure this line exists
	}

	public function uploadletterhead()
	{
		$config['upload_path'] = './upload/banners/';
		$config['allowed_types'] = 'jpg|gif|png';
		$config['max_size'] = 15000;
		//$config['max_width'] = 1500;
		//$config['max_height'] = 1500;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$msg = array('error' => $this->upload->display_errors());

			$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Error uploading the file.</b></div>');
		} else {
			$data = array('image_metadata' => $this->upload->data());
			//get data from the form
			$username = $this->session->userdata('username');
			//$filename=$this->input->post('nonoy');
			$filename = $this->upload->data('file_name');

			$que = $this->db->query("update o_srms_settings set letterhead_web='$filename'");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Uploaded Succesfully!</b></div>');
			//$this->load->view('loginFormImage');
			redirect('Settings/loginFormBanner');
		}
	}

	public function Sections()
	{
		$this->load->model('SettingsModel');

		// Get unique year levels and courses for dropdowns
		$result['yearLevels'] = $this->SettingsModel->get_year_levels1(); // Fetch distinct year levels
		$result['courses'] = $this->SettingsModel->get_courseTable1(); // Fetch distinct courses
		$result['desc'] = $this->SettingsModel->get_course(); // Fetch distinct courses

		// Get filter values from URL
		$selectedYearLevel = $this->input->get('yearLevel');
		$selectedCourse = $this->input->get('course');

		// Query the course_sections table with filters
		$this->db->select('*');
		$this->db->from('course_sections');  // Change to 'course_sections'

		if ($selectedYearLevel) {
			$this->db->where('YearLevel', $selectedYearLevel);
		}
		if ($selectedCourse) {
			$this->db->where('Course', $selectedCourse);
		}

		$this->db->order_by('Section', 'ASC');
		$query = $this->db->get();
		$result['data'] = $query->result(); // Store filtered sections

		// Load the view with filtered data
		$this->load->view('settings_sections', $result);
	}

	//delete Section
	public function deleteSection()
	{
		$id = $this->input->get('id');
		$username = $this->session->userdata('username');
		date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
		$now = date('H:i:s A');
		$date = date("Y-m-d");
		$query = $this->db->query("delete from sections where sectionID='" . $id . "'");
		$query = $this->db->query("insert into atrail values('','Deleted a Section','$date','$now','$username','$id')");
		$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Section deleted successfully.</b></div>');
		redirect('Settings/Sections');
	}
	public function deleteCourse()
	{
		// Get the course ID from GET parameters
		$id = $this->input->get('id');
		$username = $this->session->userdata('username');

		// Snapshot BEFORE delete
		$old = $this->db->get_where('course_table', ['courseid' => $id])->row_array();

		// Set timezone and get current date and time
		date_default_timezone_set('Asia/Manila'); // Adjust to your timezone
		$now = date('H:i:s A');
		$date = date("Y-m-d");

		// Delete the course record (use query builder to avoid injections)
		$ok = $this->db->delete('course_table', ['courseid' => $id]);

		// Keep your legacy trail (unchanged)
		$this->db->query("INSERT INTO atrail VALUES('', 'Deleted a Course', '$date', '$now', '$username', '$id')");

		// AUDIT: delete course (unified audit_logs)
		$this->AuditLogModel->write(
			'delete',
			'Courses',
			'course_table',
			(string)$id,
			$old ? [
				'CourseCode'        => $old['CourseCode']        ?? null,
				'CourseDescription' => $old['CourseDescription'] ?? null,
				'Major'             => $old['Major']             ?? null,
				'Duration'          => $old['Duration']          ?? null,
				'recogNo'           => $old['recogNo']           ?? null,
				'SeriesYear'        => $old['SeriesYear']        ?? null,
				'IDNumber'          => $old['IDNumber']          ?? null,
			] : null,
			null,
			$ok ? 1 : 0,
			$ok ? 'Deleted course' : 'Failed to delete course'
		);

		// Set a success message and redirect (your original UX)
		$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Course deleted successfully.</b></div>');
		redirect('Settings/Department');
	}

	// In the Controller
	public function SectionsList()
	{
		$course = $this->input->get('Course');
		$major = $this->input->get('Major');

		// Fetch sections based on course and major
		$data['sections'] = $this->SettingsModel->getSectionsByCourseAndMajor($course, $major);
		$data['course'] = $course;
		$data['major'] = $major;

		// Pass the data to the view
		$this->load->view('sections_view', $data); // Ensure this view matches the data structure
	}

	public function addSection()
	{
		// Get the Course and Major values from the input
		$course = $this->input->post('Course');
		$major = $this->input->post('Major');

		// Call getSectionsByCourseAndMajor to check if the section already exists
		$sections = $this->SettingsModel->getSectionsByCourseAndMajor($course, $major);

		// If no sections are returned, proceed with adding the new section
		if (empty($sections)) {
			$data = [
				'courseid' => $course,  // Correct column name in course_sections
				'year_level' => $this->input->post('YearLevel'),
				'section' => $this->input->post('Section'),
				'is_active' => 1,       // Assuming active by default
				'created_at' => date('Y-m-d H:i:s')  // Correct timestamp
			];

			// Insert the new section into the course_sections table
			$this->SettingsModel->insertSection($data);
			$this->session->set_flashdata('msg', '<div class="alert alert-success">Section added successfully.</div>');
		} else {
			// If the section already exists, display a message
			$this->session->set_flashdata('msg', '<div class="alert alert-warning">Section already exists for this course and major.</div>');
		}

		// Redirect to the previous page
		redirect($this->agent->referrer());
	}

	public function insertSection($data)
	{
		// Make sure you are inserting into the correct table
		$this->db->insert('course_sections', $data);  // This is the correct table: course_sections

	}




	function Department()
	{
		$result['data'] = $this->SettingsModel->getDepartmentList();
		$result['yearLevels'] = $this->SettingsModel->get_year_levels();
		$result['course'] = $this->SettingsModel->course();
		$result['staff'] = $this->StudentModel->getStaff();

		$this->load->view('settings_department', $result);

		if ($this->input->post('submit')) {
			// Collect form data
			$CourseCode        = trim((string)$this->input->post('CourseCode'));
			$CourseDescription = trim((string)$this->input->post('CourseDescription'));
			$Major             = trim((string)$this->input->post('Major'));
			$Duration          = trim((string)$this->input->post('Duration'));
			$recogNo           = trim((string)$this->input->post('recogNo'));
			$SeriesYear        = trim((string)$this->input->post('SeriesYear'));
			$ProgramHead       = trim((string)$this->input->post('ProgramHead'));
			$IDNumber          = trim((string)$this->input->post('IDNumber'));

			date_default_timezone_set('Asia/Manila');
			$now     = date('H:i:s A');
			$date    = date('Y-m-d');
			$Encoder = (string)$this->session->userdata('username');

			$new = [
				'CourseCode'        => $CourseCode,
				'CourseDescription' => $CourseDescription,
				'Major'             => $Major,
				'Duration'          => $Duration,
				'recogNo'           => $recogNo,
				'SeriesYear'        => $SeriesYear,
				'ProgramHead'       => $ProgramHead,
				'IDNumber'          => $IDNumber,
			];

			// Basic required fields (optional but useful)
			if ($CourseCode === '' || $CourseDescription === '') {
				$this->AuditLogModel->write(
					'create',
					'Courses',
					'course_table',
					null,
					null,
					$new,
					0,
					'Failed to create course (missing required fields)',
					['encoder' => $Encoder]
				);
				$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Course Code and Course Description are required.</b></div>');
				redirect('Settings/Department');
				return;
			}

			// Duplicate check (CourseDescription + Major)
			$dup = $this->db->where('CourseDescription', $CourseDescription)
				->where('Major', $Major)
				->limit(1)
				->get('course_table')
				->num_rows() > 0;

			if ($dup) {
				// AUDIT: duplicate create attempt
				$this->AuditLogModel->write(
					'create',
					'Courses',
					'course_table',
					null,
					null,
					$new,
					0,
					'Duplicate course prevented',
					['encoder' => $Encoder]
				);

				$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Duplicate entry. The record already exists.</b></div>');
				redirect('Settings/Department');
				return;
			}

			// Insert using Query Builder (safer) to get insert_id
			$ok = $this->db->insert('course_table', $new);
			$insertId = $ok ? (string)$this->db->insert_id() : null;

			// Keep your legacy trail row
			$description = 'Encoded a Course ' . $CourseDescription;
			$this->db->query("INSERT INTO atrail VALUES('', ?, ?, ?, ?, '')", [$description, $date, $now, $Encoder]);

			// AUDIT: create course (success/fail)
			$this->AuditLogModel->write(
				'create',
				'Courses',
				'course_table',
				$insertId,
				null,
				$new,
				$ok ? 1 : 0,
				$ok ? 'Created course' : 'Failed to create course',
				['encoder' => $Encoder]
			);

			// Flash + redirect
			$this->session->set_flashdata(
				'msg',
				$ok
					? '<div class="alert alert-success text-center"><b>One record added successfully.</b></div>'
					: '<div class="alert alert-danger text-center"><b>Failed to add record.</b></div>'
			);
			redirect('Settings/Department');
		}
	}



	public function displaysubByCourse()
	{

		$result['data'] = $this->SettingsModel->display_course();
		$result['yearLevels'] = $this->SettingsModel->get_year_levels();
		$result['course'] = $this->SettingsModel->course();
		$this->load->view('settings_department_Subject', $result);
	}



	public function schoolInfo()
	{
		$this->load->model('SettingsModel');

		if ($this->input->post('submit')) {
			$data = array(
				'SchoolName'            => $this->input->post('SchoolName'),
				'SchoolAddress'         => $this->input->post('SchoolAddress'),
				'SchoolHead'            => $this->input->post('SchoolHead'),
				'sHeadPosition'         => $this->input->post('sHeadPosition'),
				'Registrar'             => $this->input->post('Registrar'),
				'registrarPosition'     => $this->input->post('registrarPosition'),
				'clerk'                 => $this->input->post('clerk'),
				'clerkPosition'         => $this->input->post('clerkPosition'),
				'administrative'        => $this->input->post('administrative'),
				'administrativePosition' => $this->input->post('administrativePosition'),
				'admissionOfficer'      => $this->input->post('admissionOfficer'),
				'accountant'            => $this->input->post('accountant'),
				'cashier'               => $this->input->post('cashier'),
				'cashierPosition'       => $this->input->post('cashierPosition'),
				'PropertyCustodian'     => $this->input->post('PropertyCustodian'),
				'slogan'                => $this->input->post('slogan'),
				'active_sem'                => $this->input->post('active_sem'),
				'active_sy'                => $this->input->post('active_sy'),
				'allow_signup'                => $this->input->post('allow_signup'),

				'dragonpay_merchantid'                => $this->input->post('dragonpay_merchantid'),
				'dragonpay_password'                => $this->input->post('dragonpay_password'),
				'dragonpay_url'                => $this->input->post('dragonpay_url'),
				'show_online_payments'                => $this->input->post('show_online_payments')
			);

			$this->db->update('o_srms_settings', $data);

			// Log the update in atrail
			$trail = array(
				'atDesc'    => 'Updated the School Info',
				'atDate'      => date('Y-m-d'),
				'atTime'      => date('h:i:s A'),
				'atRes'  => $this->session->userdata('username'),
				'atSNo'    => ''
			);
			$this->db->insert('atrail', $trail);

			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Updated successfully.</b></div>');
			redirect('Settings/schoolInfo');
		} else {
			$massEmailSettings = $this->SettingsModel->getMassAnnouncementEmailSettings();
			$massEmailSettings = $massEmailSettings ? (array) $massEmailSettings : [];
			$oldMassEmailSettings = $this->session->flashdata('mass_email_settings_old');

			if (is_array($oldMassEmailSettings) && !empty($oldMassEmailSettings)) {
				$massEmailSettings = array_merge($massEmailSettings, $oldMassEmailSettings);
			}

			$openPanel = trim((string) $this->input->get('panel', true));
			if ($openPanel === '') {
				$openPanel = trim((string) $this->session->flashdata('open_panel'));
			}

			$canManageMassEmail = ($this->session->userdata('level') === 'Super Admin');
			if ($canManageMassEmail && $openPanel === '') {
				$openPanel = 'mass_email';
			}

			$result['data'] = $this->SettingsModel->getSchoolInfo();
			$result['mass_email_settings'] = $massEmailSettings;
			$result['open_panel'] = $openPanel;
			$result['can_manage_mass_email'] = $canManageMassEmail;
			$this->load->view('settings_school_info', $result);
		}
	}



	public function loginFormBanner()
	{
		$this->load->view('settings_login_image');
	}
	public function uploadloginFormImage()
	{
		$config['upload_path'] = './upload/banners/';
		$config['allowed_types'] = 'jpg|gif|png';
		$config['max_size'] = 15000;
		//$config['max_width'] = 1500;
		//$config['max_height'] = 1500;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$msg = array('error' => $this->upload->display_errors());

			$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Error uploading the file.</b></div>');
		} else {
			$data = array('image_metadata' => $this->upload->data());
			//get data from the form
			$username = $this->session->userdata('username');
			//$filename=$this->input->post('nonoy');
			$filename = $this->upload->data('file_name');

			$que = $this->db->query("update o_srms_settings set loginFormImage='$filename'");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Uploaded Succesfully!</b></div>');
			//$this->load->view('loginFormImage');
			redirect('Settings/loginFormBanner');
		}
	}


	public function uploadloginLogo()
	{
		$config['upload_path'] = './upload/banners/';
		$config['allowed_types'] = 'jpg|gif|png';
		$config['max_size'] = 15000;
		//$config['max_width'] = 1500;
		//$config['max_height'] = 1500;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('nonoy')) {
			$msg = array('error' => $this->upload->display_errors());

			$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center"><b>Error uploading the file.</b></div>');
		} else {
			$data = array('image_metadata' => $this->upload->data());
			//get data from the form
			$username = $this->session->userdata('username');
			//$filename=$this->input->post('nonoy');
			$filename = $this->upload->data('file_name');

			$que = $this->db->query("update o_srms_settings set login_form_image='$filename'");
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Uploaded Succesfully!</b></div>');
			//$this->load->view('loginFormImage');
			redirect('Settings/loginFormBanner');
		}
	}





	function ethnicity()
	{
		$result['data'] = $this->SettingsModel->get_ethnicity();
		$this->load->view('settings_ethnicity', $result);
	}




	public function Addethnicity()
	{
		if ($this->input->post('save')) {
			$data = array(
				'ethnicity' => $this->input->post('ethnicity')
			);
			$this->load->model('SettingsModel');
			$this->SettingsModel->insertethnicity($data);


			redirect('Settings/ethnicity');
		}
		$this->load->view('settings_Addethnicity');
	}








	function rooms()
	{
		$result['data'] = $this->SettingsModel->get_rooms();
		$this->load->view('settings_rooms', $result);
	}




	public function AddRooms()
	{
		if ($this->input->post('save')) {
			$data = array(
				'Room' => $this->input->post('Room')
			);
			$this->load->model('SettingsModel');
			$this->SettingsModel->insertRoom($data);


			redirect('Settings/rooms');
		}
		$this->load->view('settings_AddRoom');
	}



	public function updateRoom()
	{
		$roomID = $this->input->get('roomID');
		$result['data'] = $this->SettingsModel->getroombyId($roomID);
		$this->load->view('updateRoom', $result);

		if ($this->input->post('update')) {

			$Room = $this->input->post('Room');

			$this->SettingsModel->updateRoom($roomID, $Room);
			$this->session->set_flashdata('author', 'Record updated successfully');
			redirect('Settings/rooms');
		}
	}


	public function DeleteRoom()
	{
		$roomID = $this->input->get('roomID');
		if ($roomID) {
			$this->SettingsModel->Delete_Room($roomID);
			$this->session->set_flashdata('ethnicity', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('ethnicity', 'Error deleting record');
		}

		redirect('Settings/rooms');
	}














	public function updateCourse()
	{
		// Get the course ID from GET parameters
		$courseid = $this->input->get('courseid');

		// Fetch course data using the provided course ID
		$result['data'] = $this->SettingsModel->getcoursebyId($courseid);
		$result['staff'] = $this->StudentModel->getStaff();


		// Load the view with the course data
		$this->load->view('updatecourse', $result);

		// Check if the form has been submitted
		if ($this->input->post('update')) {

			// Build a lightweight "old" snapshot from the row you already fetched
			$oldRow = is_array($result['data']) ? (object)$result['data'] : $result['data']; // handle either object/array
			$old = [
				'CourseCode'        => $oldRow->CourseCode        ?? null,
				'CourseDescription' => $oldRow->CourseDescription ?? null,
				'Major'             => $oldRow->Major             ?? null,
				'Duration'          => $oldRow->Duration          ?? null,
				'recogNo'           => $oldRow->recogNo           ?? null,
				'SeriesYear'        => $oldRow->SeriesYear        ?? null,
				'IDNumber'          => $oldRow->IDNumber          ?? null,
			];

			// Get the updated course data from POST request
			$CourseCode        = $this->input->post('CourseCode');
			$CourseDescription = $this->input->post('CourseDescription');
			$Major             = $this->input->post('Major');
			$Duration          = $this->input->post('Duration');
			$recogNo           = $this->input->post('recogNo');
			$SeriesYear        = $this->input->post('SeriesYear');
			$IDNumber          = $this->input->post('IDNumber');

			// Prepare the data array for updating
			$data = [
				'CourseCode'        => $CourseCode,
				'CourseDescription' => $CourseDescription,
				'Major'             => $Major,
				'Duration'          => $Duration,
				'recogNo'           => $recogNo,
				'SeriesYear'        => $SeriesYear,
				'IDNumber'          => $IDNumber
			];

			// Update the course in the database
			$this->SettingsModel->updateCourse($courseid, $data);

			// AUDIT: course update
			$this->AuditLogModel->write(
				'update',
				'Courses',
				'course_table',
				(string)$courseid,
				$old,       // old values
				$data,      // new values
				1,          // assume success (your code doesn’t branch on failure)
				'Updated course'
			);

			// Set a success message and redirect
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Course updated successfully.</b></div>');
			redirect('Settings/Department');
		}
	}


	public function updateSection()
	{
		// Get the section ID from GET parameters
		$sectionID = $this->input->get('sectionID');

		// Fetch section and department data
		$result['data'] = $this->SettingsModel->getsectionbyId($sectionID);
		$result['data1'] = $this->SettingsModel->getDepartmentList();

		// Check if the form has been submitted
		if ($this->input->post('update')) {
			// Get the updated section data from POST request
			$section = $this->input->post('Section');

			// Prepare the data array for updating
			$data = ['Section' => $section];

			// Update the section in the database
			$this->SettingsModel->updateSection($sectionID, $data);

			// Set a success message and redirect
			$this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Section updated successfully.</b></div>');
			// redirect('Settings/Sections');
			redirect($this->agent->referrer());
		} else {
			// Load the view with the section data
			$this->load->view('updateSection', $result);
		}
	}














	public function updateethnicity()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->SettingsModel->getethnicitybyId($id);
		$this->load->view('updateethnicity', $result);

		if ($this->input->post('update')) {

			$ethnicity = $this->input->post('ethnicity');

			$this->SettingsModel->updateethnicity($id, $ethnicity);
			$this->session->set_flashdata('author', 'Record updated successfully');
			redirect('Settings/ethnicity');
		}
	}


	public function Deleteethnicity()
	{
		$id = $this->input->get('id');
		if ($id) {
			$this->SettingsModel->Delete_ethnicity($id);
			$this->session->set_flashdata('ethnicity', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('ethnicity', 'Error deleting record');
		}

		redirect('Settings/ethnicity');
	}





	function religion()
	{
		$result['data'] = $this->SettingsModel->get_religion();
		$this->load->view('settings_religion', $result);
	}


	public function Addreligion()
	{
		if ($this->input->post('save')) {
			$data = array(
				'religion' => $this->input->post('religion')
			);
			$this->load->model('SettingsModel');
			$this->SettingsModel->insertreligion($data);


			redirect('Settings/religion');
		}
		$this->load->view('settings_Addreligion');
	}


	public function updatereligion()
	{
		$id = $this->input->get('id');
		$result['data'] = $this->SettingsModel->getreligionbyId($id);
		$this->load->view('updateReligion', $result);

		if ($this->input->post('update')) {

			$religion = $this->input->post('religion');

			$this->SettingsModel->updatereligion($id, $religion);
			$this->session->set_flashdata('author', 'Record updated successfully');
			redirect('Settings/religion');
		}
	}


	public function Deletereligion()
	{
		$id = $this->input->get('id');
		if ($id) {
			$this->SettingsModel->Delete_religion($id);
			$this->session->set_flashdata('religion', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('religion', 'Error deleting record');
		}

		redirect('Settings/religion');
	}

	// for prevschool

	function prevschool()
	{
		$result['data'] = $this->SettingsModel->get_prevschool();
		$this->load->view('settings_prevschool', $result);
	}




	public function Addprevschool()
	{
		if ($this->input->post('save')) {
			$data = array(
				'School' => $this->input->post('School'),
				'Address' => $this->input->post('Address')
			);

			$this->load->model('SettingsModel');
			$this->SettingsModel->insertprevschool($data);

			$this->session->set_flashdata('success', 'Record added successfully');
			redirect('Settings/prevschool');
		}

		$this->load->view('settings_Addprevschool');
	}


	public function updateprevschool()
	{
		$schoolID = $this->input->get('schoolID');
		$result['data'] = $this->SettingsModel->getprevschoolbyId($schoolID);
		$this->load->view('updateprevschool', $result);

		if ($this->input->post('update')) {

			$School = $this->input->post('School');
			$Address = $this->input->post('Address');


			$this->SettingsModel->updateprevschool($schoolID, $School, $Address);
			$this->session->set_flashdata('success', 'Record updated successfully');
			redirect('Settings/prevschool');
		}
	}


	public function Deleteprevschool()
	{
		$schoolID = $this->input->get('schoolID');

		if ($schoolID) {
			$this->db->where('schoolID', $schoolID);
			$this->db->delete('prevschool');

			if ($this->db->affected_rows() > 0) {
				$this->session->set_flashdata('success', 'Record deleted successfully');
			} else {
				$this->session->set_flashdata('danger', 'No matching record found to delete');
			}
		} else {
			$this->session->set_flashdata('danger', 'Invalid school ID');
		}

		redirect('Settings/prevschool');
	}

	public function brand()
	{
		$result['data1'] = $this->SettingsModel->getSectionList();
		$result['staff'] = $this->SettingsModel->get_staff();
		$result['data'] = $this->SettingsModel->get_brand();


		if ($this->input->post('save')) {
			$data = array(
				'brand' => $this->input->post('brand')
			);
			$this->load->model('SettingsModel');
			$this->SettingsModel->insertBrand($data);


			redirect('Settings/brand');
		}
		$this->load->view('ls_brand', $result);
	}



	public function updateBrand()
	{
		$brandID = $this->input->post('brandID');
		if ($this->input->post('update')) {
			$brand = $this->input->post('brand');

			// Update track and strand in the database
			$this->SettingsModel->update_brand($brandID, $brand);

			$this->session->set_flashdata('success', 'Record updated successfully');
			redirect('Settings/brand');
		} else {
			$result['data'] = $this->SettingsModel->get_brandbyID($brandID);
			$this->load->view('ls_brand', $result);
		}
	}



	public function DeleteBrand()
	{
		$brandID = $this->input->get('brandID');
		if ($brandID) {
			$this->SettingsModel->Delete_brand($brandID);
			$this->session->set_flashdata('brand', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('brand', 'Error deleting record');
		}

		redirect('Settings/brand');
	}




	public function category()
	{
		$result['data'] = $this->SettingsModel->get_category();


		if ($this->input->post('save')) {
			$data = array(
				'Category' => $this->input->post('Category'),
				'Sub_category' => $this->input->post('Sub_category')
			);
			$this->load->model('SettingsModel');
			$this->SettingsModel->insertCategory($data);


			redirect('Settings/category');
		}
		$this->load->view('ls_category', $result);
	}



	public function updateCategory()
	{
		$CatNo = $this->input->post('CatNo');
		if ($this->input->post('update')) {
			$Category = $this->input->post('Category');
			$Sub_category = $this->input->post('Sub_category');


			// Update track and strand in the database
			$this->SettingsModel->update_category($CatNo, $Category, $Sub_category);

			$this->session->set_flashdata('success', 'Record updated successfully');
			redirect('Settings/category');
		} else {
			$result['data'] = $this->SettingsModel->get_categorybyID($CatNo);
			$this->load->view('ls_brand', $result);
		}
	}



	public function DeleteCategory()
	{
		$CatNo = $this->input->get('CatNo');
		if ($CatNo) {
			$this->SettingsModel->Delete_category($CatNo);
			$this->session->set_flashdata('category', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('category', 'Error deleting record');
		}

		redirect('Settings/category');
	}

	public function office()
	{
		$result['data'] = $this->SettingsModel->get_office();


		if ($this->input->post('save')) {
			$data = array(
				'office' => $this->input->post('office')
			);
			$this->SettingsModel->insertOffice($data);


			redirect('Settings/office');
		}
		$this->load->view('ls_office', $result);
	}



	public function updateOffice()
	{
		$officeID = $this->input->post('officeID');
		if ($this->input->post('update')) {
			$office = $this->input->post('office');

			// Update track and strand in the database
			$this->SettingsModel->update_office($officeID, $office);

			$this->session->set_flashdata('success', 'Record updated successfully');
			redirect('Settings/office');
		} else {
			$result['data'] = $this->SettingsModel->get_officebyID($officeID);
			$this->load->view('ls_office', $result);
		}
	}



	public function DeleteOffice()
	{
		$officeID = $this->input->get('officeID');
		if ($officeID) {
			$this->SettingsModel->Delete_office($officeID);
			$this->session->set_flashdata('office', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('office', 'Error deleting record');
		}

		redirect('Settings/office');
	}































	public function Subjects()
	{
		$this->load->model('SettingsModel');
		$this->load->library('session');

		// Get filter values from URL
		$selectedYearLevel = $this->input->get('yearLevel');
		$selectedCourse = $this->input->get('Course');
		$selectedMajor = $this->input->get('Major');
		$selectedSemester = $this->input->get('Semester');

		// Get unique year levels, courses, and semesters for filters
		$result['yearLevels'] = $this->SettingsModel->get_year_levels();
		$result['course'] = $this->SettingsModel->course();
		$result['semesters'] = $this->SettingsModel->get_semesters();

		// Filter subjects based on selected values
		$this->db->select('*');
		$this->db->from('subjects');

		if ($selectedYearLevel) {
			$this->db->where('yearLevel', $selectedYearLevel);
		}
		if ($selectedCourse) {
			$this->db->where('course', $selectedCourse);
		}
		if ($selectedMajor) {
			$this->db->where('major', $selectedMajor);
		}
		if ($selectedSemester) {
			$this->db->where('semester', $selectedSemester);
		}

		$query = $this->db->get();
		$result['data'] = $query->result();

		// Retain selected course/major for form rendering
		$result['selectedCourse'] = $selectedCourse;
		$result['selectedMajor'] = $selectedMajor;

		// Handle form submission
		if ($this->input->post('save')) {
			$lecunit = (int) $this->input->post('lecunit') ?: 0;
			$labunit = (int) $this->input->post('labunit') ?: 0;

			$data = array(
				'Course'       => $this->input->post('Course'),
				'Major'        => $this->input->post('Major'),
				'SubjectCode'  => $this->input->post('SubjectCode'),
				'description'  => $this->input->post('description'),
				'lecunit'      => $lecunit,
				'labunit'      => $labunit,
				'prereq'       => $this->input->post('prereq'),
				'totalUnits'   => $lecunit + $labunit,
				'YearLevel'    => $this->input->post('YearLevel'),
				'Semester'     => $this->input->post('Semester') ?? '',
				'SemEffective' => $this->input->post('Semester') ?? '',
				'SYEffective'  => $this->input->post('SYEffective') ?? '',
				'Effectivity'  => $this->input->post('Effectivity')
			);

			$this->SettingsModel->insertsubjects($data);
			$this->session->set_flashdata('success', 'Subject added successfully!');
			// redirect('Settings/Subjects?Course=' . urlencode($selectedCourse) . '&Major=' . urlencode($selectedMajor));
			redirect($this->agent->referrer());
		}

		$this->load->view('subjects', $result);
	}


	public function updatesubjects()
	{
		$subjectid = $this->input->post('subjectid');
		if ($this->input->post('update')) {
			// Collecting the form data
			// $Course = $this->input->post('Course');
			//$Major = $this->input->post('Major');
			$SubjectCode = $this->input->post('SubjectCode');
			$description = $this->input->post('description');
			$lecunit = $this->input->post('lecunit');
			$labunit = $this->input->post('labunit');
			$prereq = $this->input->post('prereq');
			$totalUnits = $this->input->post('totalUnits');
			$YearLevel = $this->input->post('YearLevel');
			$Semester = $this->input->post('Semester');
			$SYEffective = $this->input->post('SYEffective');
			$Effectivity = $this->input->post('Effectivity');

			// Updating subject data
			$this->SettingsModel->update_subject(
				$subjectid,
				$SubjectCode,
				$description,
				$YearLevel,
				// $Course,
				$Semester,
				// $Major,
				$lecunit,
				$labunit,
				$prereq,
				$totalUnits,
				$Semester,
				$SYEffective,
				$Effectivity
			);

			// Set flash message and redirect
			$this->session->set_flashdata('success', 'Record updated successfully');

			redirect($this->agent->referrer());
		} else {
			// Retrieve the existing subject data to display in the form
			$result['data'] = $this->SettingsModel->get_subjectbyId($subjectid);
			$this->load->view('subjects', $result);
		}
	}

	public function Deletesubject()
	{
		$id = $this->input->get('id');
		if ($id) {
			$this->SettingsModel->Delete_subjects($id);
			$this->session->set_flashdata('success', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('success', 'Error deleting subject.');
		}

		// redirect('Settings/Subjects');
		redirect($this->agent->referrer());
	}


	public function ClassProgram()
	{
		$course = $this->input->get('course');
		$major  = $this->input->get('major');
		$section = $this->input->get('section'); // 🆕

		$sy     = $this->session->userdata('sy');
		$sem    = $this->session->userdata('semester');

		$result['yearLevels'] = $this->SettingsModel->get_Yearlevels();
		$result['data']       = $this->SettingsModel->get_classProgram($sy, $sem, $course);
		$result['staff']      = $this->SettingsModel->get_staff();
		$result['room']      = $this->SettingsModel->get_rooms();
		$result['sub3']       = $this->SettingsModel->GetSection();
		$result['sub5']       = $this->SettingsModel->GetSection1();
		$result['courses']    = $this->SettingsModel->get_courseTable();
		$result['sec']        = $this->SettingsModel->GetSection();
		$result['sub4']       = $this->SettingsModel->GetSub4();

		$result['selectedCourse'] = $course;
		$result['selectedMajor']  = $major;
		$result['selectedSection'] = $section; // 🆕

		$this->load->view('ClassProgram', $result);
	}



	public function getSubjectsByYearLevel()
	{
		$yearLevel = $this->input->post('yearLevel');
		$subjects = $this->SettingsModel->getSubjectsByYearLevel($yearLevel);
		echo json_encode($subjects);
	}


	public function classSched()
	{
		$this->load->model('SettingsModel');
		$result['courses'] = $this->SettingsModel->get_courses();

		$this->load->view('ClassSched', $result);
	}


	public function classprogramform()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->input->post('SubjectCode')) {
			$subjectCodes = $this->input->post('SubjectCode');
			$descriptions = $this->input->post('Description');
			$lecUnits     = $this->input->post('LecUnit');
			$labUnits     = $this->input->post('LabUnit');
			$instructors  = $this->input->post('IDNumber');
			$schedTimes   = $this->input->post('SchedTime');

			$course     = $this->input->post('CourseDescription');
			$major      = $this->input->post('Major');
			$yearLevel  = $this->input->post('YearLevel');
			$section    = $this->input->post('Section');
			$semester   = $this->input->post('Semester');
			$sy         = $this->input->post('SY');
			$rooms 		= $this->input->post('Room');

			$inserted = 0;
			$skipped  = 0;

			// Step 1: Get all existing subject entries to check for full duplication
			$this->db->select('SubjectCode, Course, cMajor, YearLevel, Section, Semester, SY');
			$existingSubjects = $this->db->get('semsubjects')->result();

			// Build array of unique keys
			$existingKeys = [];
			foreach ($existingSubjects as $row) {
				$key = implode('|', [
					trim($row->SubjectCode),
					trim($row->Course),
					trim($row->cMajor),
					trim($row->YearLevel),
					trim($row->Section),
					trim($row->Semester),
					trim($row->SY)
				]);
				$existingKeys[] = $key;
			}

			// Step 2: Loop through subjects to insert
			for ($i = 0; $i < count($subjectCodes); $i++) {
				$subjectCode = trim($subjectCodes[$i]);
				$description = trim($descriptions[$i]);
				$lecUnit     = trim($lecUnits[$i]);
				$labUnit     = trim($labUnits[$i]);
				$instructor  = trim($instructors[$i]);
				$schedTime   = trim($schedTimes[$i]);
				$cMajorValue = $major === '' ? '' : trim($major); // avoid NULL

				// Create a unique key for this subject offering
				$entryKey = implode('|', [
					$subjectCode,
					trim($course),
					$cMajorValue,
					trim($yearLevel),
					trim($section),
					trim($semester),
					trim($sy)
				]);

				if (in_array($entryKey, $existingKeys)) {
					$skipped++;
					continue;
				}

				$data = [
					'SubjectCode' => $subjectCode,
					'Description' => $description,
					'LecUnit'     => $lecUnit,
					'LabUnit'     => $labUnit,
					'Section'     => $section,
					'SchedTime'   => $schedTime,
					'LabTime'     => '',
					'Slot'        => '',
					'IDNumber'    => $instructor,
					'Room'        => !empty($rooms[$i]) ? trim($rooms[$i]) : '', // <-- save room name
					'Course'      => $course,
					'cMajor'      => $cMajorValue,
					'YearLevel'   => $yearLevel,
					'Semester'    => $semester,
					'SY'          => $sy
				];

				$this->db->insert('semsubjects', $data);
				$inserted++;

				// Track this key to avoid duplicate insert in same POST
				$existingKeys[] = $entryKey;
			}

			$msg = "{$inserted} subject(s) saved. {$skipped} duplicate(s) skipped.";
			$this->session->set_flashdata('success', $msg);
			redirect('Settings/classprogramform');
			return;
		}

		// Page Load - fetch dropdown and view data
		$result['courses']     = $this->SettingsModel->get_courseTable();
		$result['staff']       = $this->SettingsModel->get_staff();
		$result['sub3']        = $this->SettingsModel->GetSub3(); // year levels
		$result['sec']         = $this->SettingsModel->GetSection();
		$result['letterhead']  = $this->Login_model->loginImage();
		$result['rooms']       = $this->SettingsModel->get_rooms();

		$this->load->view('classprogramForm', $result);
	}



	public function getMajorsByCourse()
	{
		$CourseDescription = $this->input->post('CourseDescription');
		if (!$CourseDescription) {
			echo json_encode([]); // Return empty array if no input
			return;
		}

		$this->load->model('SettingsModel');
		$majors = $this->SettingsModel->getMajorsByCourse($CourseDescription);
		echo json_encode($majors);
	}

	public function getSubjectsByFilters()
	{
		$course = $this->input->post('CourseDescription');
		$major = $this->input->post('Major'); // Can be empty
		$yearLevel = $this->input->post('YearLevel');
		$semester = $this->session->userdata('semester');

		if (!$course || !$yearLevel || !$semester) {
			echo json_encode([]);
			return;
		}

		$this->load->model('SettingsModel');
		$subjects = $this->SettingsModel->getSubjectsFiltered($course, $major, $yearLevel, $semester);
		echo json_encode($subjects);
	}


	public function saveSemSubjects()
	{
		$subjects = $this->input->post('subjects');

		if (!$subjects || !is_array($subjects)) {
			echo json_encode(['success' => false, 'message' => 'Invalid data']);
			return;
		}

		$this->load->model('SettingsModel');

		foreach ($subjects as $subj) {
			$this->SettingsModel->insertSemSubject($subj);
		}

		echo json_encode(['success' => true]);
	}




	public function insertClassform()
	{
		// Get all POST inputs
		$formData = $this->input->post();

		// Get actual column names from semsubjects table
		$fields = $this->db->list_fields('semsubjects');

		// Keep only keys in $formData that exist in semsubjects table
		$dataToInsert = array_intersect_key($formData, array_flip($fields));

		if ($this->SettingsModel->checkClassExists(
			$dataToInsert['YearLevel'],
			$dataToInsert['SubjectCode'],
			$dataToInsert['Section'],
			$dataToInsert['SY']
		)) {
			$this->session->set_flashdata('error', "Class Program already exists for Subject: {$dataToInsert['SubjectCode']}, Section: {$dataToInsert['Section']}.");
			redirect($this->agent->referrer());
			return;
		}

		// Insert data
		$this->db->insert('semsubjects', $dataToInsert);

		$this->session->set_flashdata('success', 'Class Program successfully saved.');
		redirect($this->agent->referrer());
	}



	// public function updateClassform()
	// {
	// 	// Get all POST inputs
	// 	$formData = $this->input->post();

	// 	// Get the subject ID from the form
	// 	$subjectId = $formData['subjectid'];
	// 	unset($formData['subjectid']); // Remove from data to be updated

	// 	// Get actual column names from semsubjects table
	// 	$fields = $this->db->list_fields('semsubjects');

	// 	// Keep only keys in $formData that exist in semsubjects table
	// 	$dataToUpdate = array_intersect_key($formData, array_flip($fields));

	// 	// Perform the update in semsubjects
	// 	$this->db->where('subjectid', $subjectId);
	// 	$this->db->update('semsubjects', $dataToUpdate);

	// 	// Fetch the updated subject details from semsubjects to locate corresponding registration records
	// 	$subject = $this->db->get_where('semsubjects', ['subjectid' => $subjectId])->row();

	// 	if ($subject) {
	// 		$registrationUpdate = [
	// 			'SchedTime' => $subject->SchedTime,
	// 			'IDNumber'  => $subject->IDNumber,
	// 			'Room'  => $subject->Room
	// 		];

	// 		// Update registration table where the subject matches
	// 		$this->db->where([
	// 			'SubjectCode'  => $subject->SubjectCode,
	// 			'Description'  => $subject->Description,
	// 			'Section'      => $subject->Section,
	// 			'Room'      => $subject->Room,
	// 			'Sem'          => $this->session->userdata('semester'),
	// 			'SY'           => $this->session->userdata('sy')
	// 		]);
	// 		$this->db->update('registration', $registrationUpdate);
	// 	}

	// 	$this->session->set_flashdata('success', 'Class Program successfully updated.');
	// 	redirect($this->agent->referrer());
	// }

	// Controller: Settings.php
	public function getRooms()
	{
		// adjust table/filters as needed (e.g., by settingsID)
		$rows = $this->db->select('Room')
			->from('rooms')
			->order_by('Room', 'ASC')
			->get()->result();
		$this->output->set_content_type('application/json')
			->set_output(json_encode($rows));
	}



	public function updateClassform()
	{
		// Get all POST inputs
		$formData = $this->input->post();
		if (empty($formData['subjectid'])) {
			$this->session->set_flashdata('error', 'Missing subject ID.');
			return redirect($this->agent->referrer());
		}

		$subjectId = (int)$formData['subjectid'];
		unset($formData['subjectid']);

		// Whitelist fields based on semsubjects columns
		$fields = $this->db->list_fields('semsubjects');
		$dataToUpdate = array_intersect_key($formData, array_flip($fields));

		// Begin transaction
		$this->db->trans_start();

		// Update semsubjects
		$this->db->where('subjectid', $subjectId)->update('semsubjects', $dataToUpdate);

		// Fetch the updated subject to drive the registration update
		$subject = $this->db->get_where('semsubjects', ['subjectid' => $subjectId])->row();

		if ($subject) {
			// Fields to push down to registration
			$registrationUpdate = [
				'SchedTime' => $subject->SchedTime,
				'IDNumber'  => $subject->IDNumber,
				'Room'      => $subject->Room,
			];

			// Match your SELECT filter exactly (no Room here)
			$this->db->where('SubjectCode', $subject->SubjectCode)
				->where('Section', $subject->Section)
				->where('SY', $this->session->userdata('sy'))
				->where('Sem', $this->session->userdata('semester'))
				->where('Description', $subject->Description)
				->update('registration', $registrationUpdate);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$this->session->set_flashdata('error', 'Update failed. Please try again.');
		} else {
			$this->session->set_flashdata('success', 'Class Program successfully updated.');
		}

		redirect($this->agent->referrer());
	}



	public function getClassProgramById()
	{
		$subjectid = $this->input->post('subjectid');
		$data = $this->SettingsModel->getClassProgramById($subjectid);

		echo json_encode($data);
	}


	public function getDescriptionBySubjectCode()
	{
		$subjectCode = $this->input->post('subjectCode');
		$description = $this->SettingsModel->getDescriptionBySubjectCode($subjectCode);
		echo json_encode(['description' => $description]);
	}



	public function insertClass()
	{
		$subjects = $this->input->post('subjects');
		$response = ['success' => true, 'messages' => []];

		foreach ($subjects as $subject) {
			$yearLevel = $subject['yearLevel'];
			$course = $subject['course']; // Ensure course is being passed
			$subjectCode = $subject['subjectCode'];
			$section = $subject['section'];
			$schoolYear = $subject['sy']; // Make sure 'sy' is being passed from the AJAX request

			if ($this->SettingsModel->checkClassExists($yearLevel, $subjectCode, $section, $schoolYear)) {
				$response['success'] = false;
				$response['messages'][] = "Record already exists for Subject: $subjectCode, Section: $section.";
				continue;
			}

			// Prepare the data array to insert
			$data = [
				'YearLevel'   => $yearLevel,
				'Course'      => $course, // Include course
				'SubjectCode' => $subjectCode,
				'Description' => $subject['description'],
				'IDNumber'    => $subject['adviser'],
				'Section'     => $section,
				'SchedTime'   => $subject['daysOfClass'],
				'SY'          => $schoolYear  // Ensure SY is saved
			];

			// Insert the data into the database
			$this->SettingsModel->insertclass($data);
		}

		if ($response['success']) {
			$this->session->set_flashdata('success', 'Class program created successfully.');
		} else {
			$this->session->set_flashdata('error', implode('<br>', $response['messages']));
		}

		// Send JSON response to client
		echo json_encode($response);
		exit();
	}


	public function get_subjects_by_yearlevel1()
	{
		$this->load->model('SettingsModel');

		$selectedYearLevel = $this->input->post('year_level');

		// Get subjects for the selected Year Level
		$subjects = $this->SettingsModel->get_subjects_by_yearlevel1($selectedYearLevel);

		// Return the data as JSON
		echo json_encode($subjects);
	}


	public function getSectionsByCourseAndYearLevel()
	{
		$yearLevel = $this->input->post('yearLevel');
		$course = $this->input->post('course');
		$major = $this->input->post('major'); // ✅ Added

		$sections = $this->SettingsModel->get_sections_by_course_and_yearlevel($yearLevel, $course, $major);

		echo json_encode($sections);
	}



	public function get_subjects_by_course_and_yearlevel()
	{
		$this->load->model('SettingsModel');

		$yearLevel = $this->input->post('year_level');
		$course = $this->input->post('course');

		// Get subjects for the selected course and year level
		$subjects = $this->SettingsModel->get_subjects_by_course_and_yearlevel($yearLevel, $course);

		// Return the data as JSON
		echo json_encode($subjects);
	}


	public function updateClassProgram()
	{
		// Check if the form is submitted
		if ($this->input->post('update')) {
			$subjectid = $this->input->post('subjectid');
			$SubjectCode = $this->input->post('SubjectCode');
			$Description = $this->input->post('Description');
			$Section = $this->input->post('Section');
			$YearLevel = $this->input->post('YearLevel');
			$Course = $this->input->post('Course');
			$SchedTime = $this->input->post('SchedTime');
			$SY = $this->input->post('SY');
			$SubjectStatus = $this->input->post('SubjectStatus');
			$IDNumber = $this->input->post('IDNumber');

			// Update track and strand in the database
			$this->SettingsModel->update_class($subjectid, $SubjectCode, $Description, $Section, $SchedTime, $IDNumber, $SY, $Course, $YearLevel, $SubjectStatus);

			$this->session->set_flashdata('success', 'Record updated successfully');
			redirect('Settings/ClassProgram');
		} else {
			// Fetch data if the form is not submitted yet
			$subjectid = $this->input->get('subjectid');
			$result['data'] = $this->SettingsModel->get_classbyId($subjectid);
			$result['staff'] = $this->SettingsModel->get_staff();
			$result['sub3'] = $this->SettingsModel->GetSection();
			$result['courses'] = $this->SettingsModel->get_courseTable(); // Added courses
			$this->load->view('ClassProgramUpdate', $result);
		}
	}


	public function DeleteClass()
	{
		$subjectid = $this->input->get('subjectid');
		if ($subjectid) {
			$this->SettingsModel->Delete_class($subjectid);
			$this->session->set_flashdata('success', 'Record deleted successfully');
		} else {
			$this->session->set_flashdata('error', 'Error deleting record');
		}

		redirect($this->agent->referrer());
	}


	public function getSectionsByYearLevel()
	{
		$yearLevel = $this->input->post('yearLevel');
		$sections = $this->SettingsModel->get_sections_by_yearlevel($yearLevel);

		echo json_encode($sections); // Return JSON response
	}







	public function classprogramform_head()
	{
		$id = $this->session->userdata('username'); // IDNumber of Program Head
		$sy = $this->session->userdata('sy');
		$semester = $this->session->userdata('semester');

		// Get Course & Major assigned to Program Head
		$this->db->select('CourseDescription, Major');
		$this->db->from('course_table');
		$this->db->where('IDNumber', $id);
		$courseQuery = $this->db->get();

		if ($courseQuery->num_rows() === 0) {
			$this->session->set_flashdata('error', 'You are not assigned as a Program Head.');
			redirect('dashboard_instructor'); // or any fallback
			return;
		}

		$courseRow = $courseQuery->row();
		$courseDescription = $courseRow->CourseDescription;
		$major = $courseRow->Major;

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->input->post('SubjectCode')) {
			$subjectCodes = $this->input->post('SubjectCode');
			$descriptions = $this->input->post('Description');
			$lecUnits     = $this->input->post('LecUnit');
			$labUnits     = $this->input->post('LabUnit');
			$instructors  = $this->input->post('IDNumber');
			$rooms        = $this->input->post('Room');        // NEW
			$schedTimes   = $this->input->post('SchedTime');

			$yearLevel  = $this->input->post('YearLevel');
			$section    = $this->input->post('Section');

			$inserted = 0;
			$skipped  = 0;

			$this->db->select('SubjectCode, Course, cMajor, YearLevel, Section, Semester, SY');
			$existingSubjects = $this->db->get('semsubjects')->result();

			$existingKeys = [];
			foreach ($existingSubjects as $row) {
				$key = implode('|', [
					trim($row->SubjectCode),
					trim($row->Course),
					trim($row->cMajor),
					trim($row->YearLevel),
					trim($row->Section),
					trim($row->Semester),
					trim($row->SY)
				]);
				$existingKeys[] = $key;
			}

			for ($i = 0; $i < count($subjectCodes); $i++) {
				$subjectCode = trim($subjectCodes[$i]);
				$description = trim($descriptions[$i]);
				$lecUnit     = trim($lecUnits[$i]);
				$labUnit     = trim($labUnits[$i]);
				$instructor  = trim($instructors[$i]);
				$room        = isset($rooms[$i]) ? trim($rooms[$i]) : '';   // NEW
				$schedTime   = trim($schedTimes[$i]);

				$entryKey = implode('|', [
					$subjectCode,
					trim($courseDescription),
					trim($major),
					trim($yearLevel),
					trim($section),
					trim($semester),
					trim($sy)
				]);

				if (in_array($entryKey, $existingKeys)) {
					$skipped++;
					continue;
				}

				$data = [
					'SubjectCode' => $subjectCode,
					'Description' => $description,
					'LecUnit'     => $lecUnit,
					'LabUnit'     => $labUnit,
					'Section'     => $section,
					'SchedTime'   => $schedTime,
					'LabTime'     => '',
					'Slot'        => '',
					'IDNumber'    => $instructor,
					'Course'      => $courseDescription,
					'cMajor'      => $major,
					'YearLevel'   => $yearLevel,
					'Semester'    => $semester,
					'SY'          => $sy,
					'Room'        => $room, // NEW
				];

				$this->db->insert('semsubjects', $data);
				$inserted++;
				$existingKeys[] = $entryKey;
			}

			$msg = "{$inserted} subject(s) saved. {$skipped} duplicate(s) skipped.";
			$this->session->set_flashdata('success', $msg);
			redirect('Settings/classprogramform_head');
			return;
		}

		// Page Load
		$result['sub3']        = $this->SettingsModel->GetSub3(); // year levels
		$result['sec']         = $this->SettingsModel->GetSection();
		$result['staff']       = $this->SettingsModel->get_staff();
		$result['rooms']       = $this->SettingsModel->get_rooms();
		$result['letterhead']  = $this->Login_model->loginImage();
		$result['courseDescription'] = $courseDescription;
		$result['major'] = $major;

		$this->load->view('classprogramForm_head', $result);
	}

	public function classprogram_list()
	{
		$this->load->model('SettingsModel');

		$id = $this->session->userdata('username'); // Program Head's IDNumber
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		$data['yearLevels'] = $this->SettingsModel->get_Yearlevels();
		$data['staff']      = $this->SettingsModel->get_staff();
		$data['sub3']       = $this->SettingsModel->GetSection();
		$data['sub5']       = $this->SettingsModel->GetSection1();
		$data['courses']    = $this->SettingsModel->get_courseTable();
		$data['sec']        = $this->SettingsModel->GetSection();
		// Get Course and Major from course_table based on IDNumber
		$this->db->select('CourseDescription, Major');
		$this->db->from('course_table');
		$this->db->where('IDNumber', $id);
		$query = $this->db->get();

		if ($query->num_rows() === 0) {
			$this->session->set_flashdata('error', 'No course data found for this Program Head.');
			redirect('dashboard_instructor'); // fallback
			return;
		}

		$courseRow = $query->row();
		$courseDescription = $courseRow->CourseDescription;
		$major = $courseRow->Major;

		// Fetch matching semsubjects
		$this->db->select('semsubjects.*, CONCAT(staff.FirstName, " ", staff.MiddleName, " ", staff.LastName) AS Fullname');
		$this->db->from('semsubjects');
		$this->db->join('staff', 'staff.IDNumber = semsubjects.IDNumber', 'left');
		$this->db->where('semsubjects.Course', $courseDescription);
		$this->db->where('semsubjects.SY', $sy);
		$this->db->where('semsubjects.Semester', $sem);

		if (!empty($major)) {
			$this->db->where('semsubjects.cMajor', $major);
		}

		$this->db->order_by('YearLevel, Section, SubjectCode');
		$data['programs'] = $this->db->get()->result();

		$data['courseDescription'] = $courseDescription;
		$data['major'] = $major;

		$this->load->view('classprogram_list', $data);
	}







	public function grades_status_list()
	{
		$this->load->model('SettingsModel');

		$id  = (string)$this->session->userdata('username'); // Program Head's IDNumber
		$sy  = (string)$this->session->userdata('sy');
		$sem = (string)$this->session->userdata('semester');

		// Resolve Program Head's course/major
		$courseRow = $this->db->select('CourseDescription, Major')
			->from('course_table')
			->where('IDNumber', $id)
			->get()->row();
		if (!$courseRow) {
			$this->session->set_flashdata('error', 'No course data found for this Program Head.');
			return redirect('dashboard_instructor');
		}

		$courseDescription = (string)$courseRow->CourseDescription;
		$major             = (string)$courseRow->Major; // may be empty

		// Base: subjects for this program (by SY/Sem/Course[/Major])
		// Note: Encoded = at least one non-zero grade in grades_o for that period
		//       Submitted = any matching row in grade_receipts for that period
		$this->db->from('semsubjects s');
		$this->db->select([
			's.subjectid',
			's.SubjectCode',
			's.Description',
			's.Section',
			's.Course',
			's.cMajor as Major',
			's.YearLevel',
			's.SY',
			's.Semester',
			"CONCAT(st.FirstName,' ',st.MiddleName,' ',st.LastName) AS Fullname",

			// ===== Encoded flags (non-zero) =====
			// Prelim
			"(SELECT COUNT(1) FROM grades_o go
           WHERE go.SY=s.SY AND go.Semester=s.Semester
             AND go.Course=s.Course AND (go.Major=s.cMajor OR s.cMajor='')
             AND go.Section=s.Section AND go.SubjectCode=s.SubjectCode
             AND go.Prelim IS NOT NULL AND COALESCE(go.Prelim,0) <> 0
         ) AS prelim_encoded_cnt",

			// Midterm
			"(SELECT COUNT(1) FROM grades_o go
           WHERE go.SY=s.SY AND go.Semester=s.Semester
             AND go.Course=s.Course AND (go.Major=s.cMajor OR s.cMajor='')
             AND go.Section=s.Section AND go.SubjectCode=s.SubjectCode
             AND go.Midterm IS NOT NULL AND COALESCE(go.Midterm,0) <> 0
         ) AS midterm_encoded_cnt",

			// PreFinal
			"(SELECT COUNT(1) FROM grades_o go
           WHERE go.SY=s.SY AND go.Semester=s.Semester
             AND go.Course=s.Course AND (go.Major=s.cMajor OR s.cMajor='')
             AND go.Section=s.Section AND go.SubjectCode=s.SubjectCode
             AND go.PreFinal IS NOT NULL AND COALESCE(go.PreFinal,0) <> 0
         ) AS prefinal_encoded_cnt",

			// Final
			"(SELECT COUNT(1) FROM grades_o go
           WHERE go.SY=s.SY AND go.Semester=s.Semester
             AND go.Course=s.Course AND (go.Major=s.cMajor OR s.cMajor='')
             AND go.Section=s.Section AND go.SubjectCode=s.SubjectCode
             AND go.Final IS NOT NULL AND COALESCE(go.Final,0) <> 0
         ) AS final_encoded_cnt",

			// ===== Submitted flags (grade_receipts) =====
			"(SELECT COUNT(1) FROM grade_receipts r
           WHERE r.SY=s.SY AND r.Semester=s.Semester
             AND r.Course=s.Course AND (r.Major=s.cMajor OR s.cMajor='')
             AND r.Section=s.Section AND r.SubjectCode=s.SubjectCode
             AND r.period='prelim'
         ) AS prelim_submitted_cnt",

			"(SELECT COUNT(1) FROM grade_receipts r
           WHERE r.SY=s.SY AND r.Semester=s.Semester
             AND r.Course=s.Course AND (r.Major=s.cMajor OR s.cMajor='')
             AND r.Section=s.Section AND r.SubjectCode=s.SubjectCode
             AND r.period='midterm'
         ) AS midterm_submitted_cnt",

			"(SELECT COUNT(1) FROM grade_receipts r
           WHERE r.SY=s.SY AND r.Semester=s.Semester
             AND r.Course=s.Course AND (r.Major=s.cMajor OR s.cMajor='')
             AND r.Section=s.Section AND r.SubjectCode=s.SubjectCode
             AND r.period='prefinal'
         ) AS prefinal_submitted_cnt",

			"(SELECT COUNT(1) FROM grade_receipts r
           WHERE r.SY=s.SY AND r.Semester=s.Semester
             AND r.Course=s.Course AND (r.Major=s.cMajor OR s.cMajor='')
             AND r.Section=s.Section AND r.SubjectCode=s.SubjectCode
             AND r.period='final'
         ) AS final_submitted_cnt",
		]);
		$this->db->join('staff st', 'st.IDNumber = s.IDNumber', 'left');
		$this->db->where('s.Course', $courseDescription);
		$this->db->where('s.SY', $sy);
		$this->db->where('s.Semester', $sem);
		if ($major !== '') {
			$this->db->where('s.cMajor', $major);
		}
		$this->db->order_by('s.YearLevel, s.Section, s.SubjectCode');

		$rows = $this->db->get()->result();

		// Group for view: YearLevel → Section
		$grouped = [];
		foreach ($rows as $r) {
			$yl = $r->YearLevel ?: '—';
			$grouped[$yl][$r->Section][] = $r;
		}

		$data = [
			'courseDescription' => $courseDescription,
			'major'             => $major,
			'sy'                => $sy,
			'sem'               => $sem,
			'grouped'           => $grouped,
		];

		$this->load->view('grades_status_list', $data);
	}
}

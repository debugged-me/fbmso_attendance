<?php
class Masterlist extends CI_Controller
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
		$this->load->model('Common');
		$this->load->model('Login_model');
		$this->load->library('session');


		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('login');
		}
	}
	//Masterlist by Grade Level
	function masterlistAll()
	{
		$semester = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');
		$result['data'] = $this->StudentModel->masterlistAll($semester, $sy);
		$this->load->view('masterlist_all', $result);
	}

	//Grades Summary
	function gradesSummary()
	{
		$sy = $this->input->get('sy');
		$sem = $this->input->get('sem');
		$result['data'] = $this->StudentModel->gradesSummary($sy, $sem);
		$this->load->view('registrar_grades_summary', $result);

		if ($this->input->post('submit')) {
			$sy = $this->input->get('sy');
			$sem = $this->input->get('sem');
			$result['data'] = $this->StudentModel->gradesSummary($sy, $sem);
			$this->load->view('registrar_grades_summary', $result);
		}
	}

// Masterlist by Grade Level — SIGNUP SOURCE
function byGradeYL()
{
    $yearlevel = $this->input->get('yearlevel');

    $result['data']  = $this->StudentModel->signupByGradeLevel($yearlevel);
    $result['data1'] = $this->StudentModel->signupByGradeLevelCount($yearlevel);

    $this->load->view('masterlist_by_gradelevel', $result);

    if ($this->input->post('submit')) {
        $result['data']  = $this->StudentModel->signupByGradeLevel($yearlevel);
        $result['data1'] = $this->StudentModel->signupByGradeLevelCount($yearlevel);
        $this->load->view('masterlist_by_gradelevel', $result);
    }
}


/**
 * Masterlist by Section.
 *
 * This used to call StudentModel::signupBySection() and load a view named
 * 'masterlist_by_section' — neither of which exists, so the link from
 * masterlist_by_course_filtered.php was a guaranteed 500. It now reuses the
 * same query and view as the dashboard's section drill-down, so one section
 * list looks and behaves the same wherever you reach it from.
 */
public function bySection()
{
    $sy      = (string)$this->session->userdata('sy');
    $sem     = (string)$this->session->userdata('semester');
    $section = (string)$this->input->get('section', true);

    $course = $this->input->get('course', true);
    $course = ($course === '' ? null : $course);
    $major  = $this->input->get('major', true);

    $result['data']         = $this->StudentModel->enrolledStudentsBy($sy, $sem, 'section', $section, $course, $major);
    $result['groupLabel']   = 'Section';
    $result['groupValue']   = ($section === '' ? 'Not Set' : $section);
    $result['filterCourse'] = $course;
    $result['filterMajor']  = $major;
    $result['data18']       = $this->SettingsModel->getSchoolInfo();

    $this->load->view('enrollment_list', $result);
}

// Enrolled list — use studentsignup
public function enrolledList()
{
    // If your view expects the same extra arrays, keep filling them,
    // but source the main list from studentsignup:
    $result['data'] = $this->StudentModel->signupBySYAll();

    // If your dropdowns (course/section) should now come from signups,
    // add helper methods; otherwise keep existing if they’re independent:
    // $result['course']   = $this->StudentModel->getCourseFromSignup();
    // $result['section']  = $this->StudentModel->getSectionFromSignup();

    $result['data1']      = []; // or remove if not used by the view anymore
    $result['scholarships']= $this->StudentModel->getscholarships();
    $result['strand']      = $this->SettingsModel->getTrack();

    $this->load->view('enrolled_list', $result);
}

// Masterlist by School Year — studentsignup has no SY/semester columns
public function bySY()
{
    $result['data'] = $this->StudentModel->signupBySYAll();
    $this->load->view('masterlist_by_sy', $result);

    if ($this->input->post('submit')) {
        $result['data'] = $this->StudentModel->signupBySYAll();
        $this->load->view('masterlist_by_sy', $result);
    }
}


	function bySYLMS()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->bySY($sy, $sem);
		$this->load->view('masterlist_by_sy_lms', $result);
	}

	//Masterlist By Qualification
	function byQualifiation()
	{
		$qual = $this->input->get('qual');
		$result['data'] = $this->StudentModel->byQualification($qual);
		$result['data2'] = $this->StudentModel->byQualificationSectionCounts($qual);
		$this->load->view('masterlist_by_qualification', $result);
	}

	//Masterlist By Qualification
	function byQualifiationSection()
	{
		$qual = $this->input->get('qual');
		$section = $this->input->get('section');
		$result['data'] = $this->StudentModel->byQualificationSection($qual, $section);
		$this->load->view('masterlist_by_qualification_section', $result);
	}

	function byQualifiationEmployement()
	{
		$qual = $this->input->get('qual');
		$section = $this->input->get('section');
		$result['data'] = $this->StudentModel->byQualificationEmployment($qual, $section);
		$this->load->view('masterlist_by_qualification_employment', $result);
	}

	//Masterlist Daily Enrollment
	function dailyEnrollees()
	{
		$date = $this->input->get('date');
		$result['data'] = $this->StudentModel->byDate($date);
		$result['data1'] = $this->StudentModel->byDateCourseSum($date);
		$this->load->view('masterlist_daily_enrollees', $result);

		if ($this->input->post('submit')) {
			$date = $this->input->get('date');
			$result['data'] = $this->StudentModel->byDate($date);
			$result['data1'] = $this->StudentModel->byDateCourseSum($date);
			$this->load->view('masterlist_daily_enrollees', $result);
		}
	}

	//Masterlist by Course
	function byCourse()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$course = $this->input->get('course');
		// byCourse is ($course, $major, $sy, $sem) — null major means "all majors".
		$result['data'] = $this->StudentModel->byCourse($course, null, $sy, $sem);
		$this->load->view('masterlist_by_course', $result);

		if ($this->input->post('submit')) {
			$sy = $this->session->userdata('sy');
			$department = $this->input->get('department');
			$result['data'] = $this->StudentModel->byDepartment($department, $sy);
			$this->load->view('masterlist_by_department', $result);
		}
	}

	//Masterlist by Enrolled Online
	function byEnrolledOnline()
	{
		$sy = $this->session->userdata('sy');
		$department = $this->input->get('department');
		$result['data'] = $this->StudentModel->byEnrolledOnline($department, $sy);
		$this->load->view('masterlist_by_oe', $result);

		if ($this->input->post('submit')) {
			$sy = $this->session->userdata('sy');
			$department = $this->input->get('department');
			$result['data'] = $this->StudentModel->byEnrolledOnline($department, $sy);
			$this->load->view('masterlist_by_oe', $result);
		}
	}

	//Masterlist for Payment Acceptance
	function forPaymentAcceptance()
	{
		$result['data'] = $this->StudentModel->byEnrolledOnline();
		$this->load->view('masterlist_by_op_verification', $result);

		if ($this->input->post('submit')) {
			$result['data'] = $this->StudentModel->byEnrolledOnline();
			$this->load->view('masterlist_by_op_verification', $result);
		}
	}

	//Masterlist by Religion
	function studeReligion()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$religion = $this->input->get('religion');
		$result['data'] = $this->StudentModel->religionList($sem, $sy, $religion);
		$this->load->view('masterlist_by_religion', $result);
	}
	//Masterlist by City
	function cityList()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$city = $this->input->get('city');
		$result['data'] = $this->StudentModel->cityList($sem, $sy, $city);
		$this->load->view('masterlist_by_city', $result);
	}
	//Masterlist by Ethnicity
	function ethnicityList()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$ethnicity = $this->input->get('ethnicity');
		$result['data'] = $this->StudentModel->ethnicityList($sem, $sy, $ethnicity);
		$this->load->view('masterlist_by_ethnicity', $result);
	}
	//Masterlist of Teachers
	function teachers()
	{
		$result['data'] = $this->StudentModel->teachers();
		$this->load->view('masterlist_teachers', $result);
	}

	//Masterlist by Enrolled Online
	function byEnrolledOnlineSem()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->byEnrolledOnlineSem($sem, $sy);
		$this->load->view('masterlist_by_oe', $result);
	}
	function byEnrolledOnlineAll()
	{
		$result['data'] = $this->StudentModel->byEnrolledOnlineAll();
		$this->load->view('masterlist_by_oe_all', $result);
	}

	function scholarshipReservation()
	{
		$program = $this->input->get('program');
		$result['data'] = $this->StudentModel->scholarshipReservation($program);
		$this->load->view('masterlist_scholarship_reservations', $result);
	}

	function studeGrades()
	{
		$studeno = $this->input->get('studeno');
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->studeGrades($studeno, $sem, $sy);
		$this->load->view('stude_grades', $result);
	}

	function deniedEnrollees()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->deniedEnrollees($sem, $sy);
		$this->load->view('denied_enrollees', $result);
	}

	function studeGradesView()
	{
		if ($this->session->userdata('level') === 'Student') {


			if ($this->input->post('submit')) {
				$studeno = $this->session->userdata('username');
				$sy = $this->input->post('sy');
				$sem = $this->input->post('sem');
				$result['data'] = $this->StudentModel->studeGrades($studeno, $sem, $sy);
				$result['data1'] = $this->StudentModel->studeGradesGroup($studeno);
				$this->load->view('stude_grades_view', $result);
			} else {

				$studeno = $this->session->userdata('username');
				$sy = $this->session->userdata('sy');
				$sem = $this->session->userdata('semester');
				$result['data'] = $this->StudentModel->studeGrades($studeno, $sem, $sy);
				$result['data1'] = $this->StudentModel->studeGradesGroup($studeno);
				$this->load->view('stude_grades_view', $result);
			}
		} else {
			$studeno = $this->input->get('studeno');
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');
			$result['data'] = $this->StudentModel->studeGrades($studeno, $sem, $sy);
			$this->load->view('stude_grades_view', $result);
		}
	}

	function COR()
	{
		if ($this->session->userdata('level') === 'Student') {

			if ($this->input->post('submit')) {
				$studeno = $this->session->userdata('username');
				$sy = $this->input->post('sy');
				$sem = $this->input->post('sem');
				$result['data'] = $this->StudentModel->studeCOR($studeno, $sem, $sy);
				$this->load->view('stude_cor', $result);
			} else {

				$studeno = $this->session->userdata('username');
				$sy = $this->session->userdata('sy');
				$sem = $this->session->userdata('semester');
				$result['data'] = $this->StudentModel->studeCOR($studeno, $sem, $sy);
				$this->load->view('stude_cor', $result);
			}
		} else {
			$studeno = $this->input->get('studeno');
			$sy = $this->session->userdata('sy');
			$sem = $this->session->userdata('semester');
			$result['data'] = $this->StudentModel->studeCOR($studeno, $sem, $sy);
			$this->load->view('stude_cor', $result);
		}
	}

	public function slotsMonitoring()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->slotsMonitoring($sem, $sy);
		$this->load->view('registrar_slots_monitoring', $result);
	}

	public function enrolledStudents()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		// Get SubjectCode, Section, Instructor ID, and Schedule from query string
		$SubjectCode  = $this->input->get('SubjectCode');
		$Section      = $this->input->get('Section');
		$InstructorID = $this->input->get('IDNumber'); // This should be the IDNumber
		$SchedTime    = $this->input->get('SchedTime');

		// Check if required parameters are provided
		if ($SubjectCode && $Section) {
			$result['data'] = $this->StudentModel->getEnrolledStudents($SubjectCode, $Section, $InstructorID, $SchedTime, $sy, $sem);
			$this->load->view('enrolled_students_view', $result);
		} else {
			show_error('Subject Code or Section not provided.');
		}
	}


	public function subjectMasterlist()
	{
		$subjectcode = $this->input->get('subjectcode');
		$section = $this->input->get('section');
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->subjectMasterlist($sem, $sy, $subjectcode, $section);
		$this->load->view('registrar_subject_masterlist', $result);
	}

	public function crossEnrollees()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->crossEnrollees($sem, $sy);
		$this->load->view('registrar_cross_enrollees', $result);
	}

	public function fteRecords()
	{
		$course = $this->input->get('course');
		$major = $this->input->get('major');
		$yearlevel = $this->input->get('yearlevel');
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		$result['course'] = $this->StudentModel->getCourse();
		$result['data'] = $this->StudentModel->fteRecords($sem, $sy, $course, $major, $yearlevel);

		$this->load->view('registrar_fte_records', $result);
	}


	public function getMajors()
	{
		$courseDescription = $this->input->post('course');
		$majors = $this->StudentModel->getMajorsByCoursesum($courseDescription);

		$options = "<option value=''>Select Major</option>";
		foreach ($majors as $row) {
			$options .= "<option value='" . $row->Major . "'>" . $row->Major . "</option>";
		}

		echo $options;
	}

	public function subregistration()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->slotsMonitoring($sem, $sy);
		$this->load->view('registrar_subjects', $result);
	}

	public function grades()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data'] = $this->StudentModel->grades($sem, $sy);
		$this->load->view('registrar_grades', $result);
	}

	// public function gradeSheets()
	// {
	// 	// GET params (URL) with sane fallbacks
	// 	$subjectcode = $this->input->get('SubjectCode', TRUE) ?? '';
	// 	$description = $this->input->get('Description', TRUE) ?? '';
	// 	$section     = $this->input->get('Section', TRUE) ?? '';

	// 	// Prefer URL-provided SY/Sem if present; otherwise use session
	// 	$sy  = $this->input->get('SY', TRUE)  ?: $this->session->userdata('sy');
	// 	$sem = $this->input->get('Semester', TRUE) ?: $this->session->userdata('semester');

	// 	// Query (Registrar scope: no IDNumber filter)
	// 	$result['data'] = $this->StudentModel->gradeSheets($sy, $sem, $subjectcode, $section);

	// 	// Pass everything the view needs
	// 	$result['subjectcode'] = $subjectcode;
	// 	$result['description'] = $description;
	// 	$result['section']     = $section;

	// 	// Letterhead / school info (your view uses these)
	// 	$result['letterhead']  = $this->Login_model->getSchoolInformation();
	// 	$result['school']      = !empty($result['letterhead']) ? $result['letterhead'][0] : null;

	// 	$this->load->view('registrar_grades_sheets', $result);
	// }



	public function gradeSheets()
	{
		// GET params (URL) with sane fallbacks
		$subjectcode = $this->input->get('SubjectCode', TRUE) ?? '';
		$description = $this->input->get('Description', TRUE) ?? '';
		$section     = $this->input->get('Section', TRUE) ?? '';

		// Prefer URL-provided SY/Sem if present; otherwise use session
		$sy  = $this->input->get('SY', TRUE)       ?: $this->session->userdata('sy');
		$sem = $this->input->get('Semester', TRUE) ?: $this->session->userdata('semester');

		// Query (Registrar scope: no IDNumber filter) — NOTE: your StudentModel
		$result['data'] = $this->StudentModel->gradeSheets($sy, $sem, $subjectcode, $section);

		// Pass everything the view needs
		$result['subjectcode'] = $subjectcode;
		$result['description'] = $description;
		$result['section']     = $section;

		// Letterhead / school info (your view uses these)
		$result['letterhead']  = $this->Login_model->getSchoolInformation();
		$result['school']      = !empty($result['letterhead']) ? $result['letterhead'][0] : null;

		// 🔒 Locks (college / semester-aware)
		$this->load->model('GradesLockModel');

		$firstRow  = !empty($result['data']) ? $result['data'][0] : null;
		$yearLevel = $firstRow->YearLevel ?? ($this->input->get('YearLevel', TRUE) ?: '');

		$result['locks'] = $this->GradesLockModel->get_or_create_college(
			$sy,
			$sem,
			$subjectcode,
			$description,
			$section,
			$yearLevel,
			$this->session->userdata('username')
		);

		// also pass sy/sem to the view for AJAX context
		$result['sy']  = $sy;
		$result['sem'] = $sem;

		$this->load->view('registrar_grades_sheets', $result);
	}



	public function toggleCollegeLock()
	{
		if (!$this->input->is_ajax_request()) show_404();

		$SY          = $this->input->post('SY', TRUE);
		$Semester    = $this->input->post('Semester', TRUE);
		$SubjectCode = $this->input->post('SubjectCode', TRUE);
		$Description = $this->input->post('Description', TRUE);
		$Section     = $this->input->post('Section', TRUE);
		$YearLevel   = $this->input->post('YearLevel', TRUE);
		$period      = strtolower($this->input->post('period', TRUE));
		$action      = strtolower($this->input->post('action', TRUE));

		$this->load->model('GradesLockModel');

		// ensure row exists
		$this->GradesLockModel->get_or_create_college(
			$SY,
			$Semester,
			$SubjectCode,
			$Description,
			$Section,
			$YearLevel,
			$this->session->userdata('username')
		);

		$map = [
			'prelim'   => 'lock_prelim',
			'midterm'  => 'lock_midterm',
			'prefinal' => 'lock_prefinal',
			'final'    => 'lock_final',
			'all'      => 'all'
		];

		if (!isset($map[$period])) {
			return $this->output->set_content_type('application/json')
				->set_output(json_encode(['ok' => false, 'err' => 'Invalid period']));
		}

		$setVal = ($action === 'lock') ? 1 : 0;
		$data   = [];

		if ($map[$period] === 'all') {
			$data = [
				'lock_prelim'  => $setVal,
				'lock_midterm' => $setVal,
				'lock_prefinal' => $setVal,
				'lock_final'   => $setVal,
			];
		} else {
			$data[$map[$period]] = $setVal;
		}

		$locks = $this->GradesLockModel->update_locks_sem(
			$SY,
			$Semester,
			$SubjectCode,
			$Description,
			$Section,
			$data,
			$this->session->userdata('username')
		);

		return $this->output->set_content_type('application/json')
			->set_output(json_encode(['ok' => true, 'locks' => $locks]));
	}


	public function gradeSheets_print()
	{
		$SubjectCode = $this->input->get('SubjectCode');
		$Description = $this->input->get('Description');
		//$Instructor = $this->input->get('Instructor'); 
		$Section = $this->input->get('Section');
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');
		$result['data1'] = $this->StudentModel->o_srms_settings();
		$result['data'] = $this->StudentModel->gradeSheets($sem, $sy, $SubjectCode, $Description, $Section);
		$this->load->view('registrar_grades_sheets2', $result);
	}



	public function viewing_grades()
	{
		$sy = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		$result['data'] = $this->SettingsModel->grade_view($sy, $sem);
		$result['gs'] = $this->Common->one_cond_row('grades_settings', 'id', 1);

		$this->load->view('viewing_grades', $result);
	}


	// public function encodeGrades()
	// {
	//     $filters = [
	//         'SubjectCode' => $this->input->get('SubjectCode'),
	//         'Description' => urldecode($this->input->get('Description')),
	//         'Section'     => $this->input->get('Section'),
	//         'SY'          => $this->session->userdata('sy'),
	//         'semester'    => $this->session->userdata('semester'),
	//         'IDNumber'    => $this->input->get('IDNumber'),
	//         'ren'         => $this->input->get('ren') ?? 4
	//     ];

	//     $this->load->model('StudentModel');

	//     $data['students'] = $this->StudentModel->get_students_by_registration($filters);
	//     $data['grades']   = $this->StudentModel->get_existing_grades($filters);

	//     $details = $this->StudentModel->get_registration_details($filters);
	//     if ($details) {
	//         $filters['Sem']        = $details->Sem;
	//         $filters['LecUnit']    = $details->LecUnit;
	//         $filters['LabUnit']    = $details->LabUnit;
	//         $filters['settingsID'] = $details->settingsID;
	//         $filters['Course']     = $details->Course;
	//         $filters['Major']      = $details->Major;
	//         $filters['IDNumber']   = $details->IDNumber;
	//     }

	//     $data['filters'] = $filters;
	//     $this->load->view('registrar_grades_add', $data);
	// }





	// 	public function save_grades()
	// {
	//     // Enable error reporting for debugging (you can remove this in production)
	//     ini_set('display_errors', 1);
	//     ini_set('display_startup_errors', 1);
	//     error_reporting(E_ALL);

	//     $json = file_get_contents('php://input');
	//     $data = json_decode($json, true);

	//     if (!isset($data['grades'])) {
	//         echo json_encode(['status' => 'error', 'message' => 'No grades data received']);
	//         return;
	//     }

	//     $grades = $data['grades'];

	//     foreach ($grades as $g) {
	//         // Detect which grading period was submitted
	//         $gradeField = '';
	//         if (isset($g['Prelim'])) {
	//             $gradeField = 'Prelim';
	//         } elseif (isset($g['Midterm'])) {
	//             $gradeField = 'Midterm';
	//         } elseif (isset($g['PreFinal'])) {
	//             $gradeField = 'PreFinal';
	//         } elseif (isset($g['Final'])) {
	//             $gradeField = 'Final';
	//         }

	//         if ($gradeField === '') {
	//             continue;
	//         }

	//         $gradeValue = $g[$gradeField];

	//         // WHERE clause to check existing grade
	//         $where = [
	//             'StudentNumber' => $g['StudentNumber'],
	//             'SubjectCode'   => $g['SubjectCode'],
	//             'Section'       => $g['Section'],
	//             'SY'            => $g['SY'],
	//             'IDNumber'      => $g['IDNumber']
	//         ];

	//         // Grade data
	//         $gradeData = [
	//             $gradeField     => $gradeValue,
	//             'SubjectCode'   => $g['SubjectCode'],
	//             'Description'   => $g['Description'],
	//             'Instructor'    => $g['Instructor'] ?? '', // Optional fallback
	//             'Section'       => $g['Section'],
	//             'SY'            => $g['SY'],
	//             'Semester'      => $g['Semester'],
	//             'Term'          => $g['Term'] ?? '',
	//             'LecUnit'       => $g['LecUnit'],
	//             'LabUnit'       => $g['LabUnit'],
	//             'settingsID'    => $g['settingsID'],
	//             'Course'        => $g['Course'],
	//             'Major'         => $g['Major'],
	//             'IDNumber'      => $g['IDNumber'],
	//             'StudentNumber' => $g['StudentNumber'],
	//             'dateEncoded'   => $g['dateEncoded'],
	//             'timeEncoded'   => $g['timeEncoded']
	//         ];

	//         // Save (insert or update)
	//         $existing = $this->db->get_where('grades', $where)->row();
	//         if ($existing) {
	//             $this->db->where($where);
	//             $success = $this->db->update('grades', $gradeData);
	//         } else {
	//             $success = $this->db->insert('grades', $gradeData);
	//         }

	//         if (!$success) {
	//             log_message('error', 'Failed DB operation: ' . json_encode($this->db->error()));
	//         }
	//     }

	//     echo json_encode(['status' => 'success']);
	// }

	public function encodeGrades()
	{
		// Normalize GET keys (support both camel/lowercase)
		$SubjectCode = $this->input->get('SubjectCode') ?: $this->input->get('subjectcode');
		$descParam   = $this->input->get('Description') ?: $this->input->get('description');
		$Section     = $this->input->get('Section')     ?: $this->input->get('section');
		$ren         = $this->input->get('ren') ?? 4;

		$filters = [
			'SubjectCode' => $SubjectCode,
			'Description' => urldecode((string)($descParam ?? '')), // no null to urldecode
			'Section'     => $Section,
			'SY'          => $this->session->userdata('sy'),
			'semester'    => $this->session->userdata('semester'),
			'Semester'    => $this->session->userdata('semester'),
			'IDNumber'    => $this->input->get('IDNumber'), // optional
			'ren'         => $ren,
		];

		$this->load->model('StudentModel');

		// Students list (OK to use full filters)
		$data['students'] = $this->StudentModel->get_students_by_registration($filters);

		// Registration details (for Course/Major/Units/settingsID)
		$details = $this->StudentModel->get_registration_details($filters);
		if ($details) {
			$filters['Sem']        = $details->Sem ?? $filters['Semester'];
			$filters['LecUnit']    = $details->LecUnit ?? 0;
			$filters['LabUnit']    = $details->LabUnit ?? 0;
			$filters['settingsID'] = $details->settingsID ?? 1;
			$filters['Course']     = $details->Course ?? '';
			$filters['Major']      = $details->Major ?? '';
			$filters['InstructorID'] = $details->IDNumber ?? null; // informational only
			// DO NOT overwrite filters['IDNumber']
		} else {
			$filters['settingsID'] = 1;
			$filters['LecUnit']    = 0;
			$filters['LabUnit']    = 0;
		}

		// Grades list: DO NOT filter by IDNumber so rows are visible no matter who encoded
		$gradesFilters = $filters;
		unset($gradesFilters['IDNumber'], $gradesFilters['InstructorID']);
		$data['grades'] = $this->StudentModel->get_existing_grades($gradesFilters);

		$data['filters'] = $filters;
		$this->load->view('registrar_grades_add', $data);
	}




	public function save_grades()
	{
		$students      = (array)$this->input->post('StudentNumber');
		$ren           = (int)$this->input->post('ren');
		$Course        = (array)$this->input->post('Course');
		$Major         = (array)$this->input->post('Major');
		$YearLevel     = (array)$this->input->post('YearLevel');
		$LabUnit       = (array)$this->input->post('LabUnit');
		$LecUnit       = (array)$this->input->post('LecUnit');
		$Section       = (array)$this->input->post('Section');

		$SubjectCode   = (string)$this->input->post('SubjectCode');
		$Description   = (string)$this->input->post('Description');
		$sec           = (string)$this->input->post('sec');
		// $idNumber           = (string)$this->input->post('IDNumber');

		// carry settingsID if present (we add it in the view below)
		$settingsID    = (int)($this->input->post('settingsID') ?? 1);

		$sy            = (string)$this->session->userdata('sy');
		$semester      = (string)$this->session->userdata('semester');
		// $idNumber      = (string)$this->session->userdata('username'); // Registrar ID

		date_default_timezone_set('Asia/Manila');
		$now  = date('H:i:s A');
		$date = date('Y-m-d');

		$grading_column = ['Prelim', 'Midterm', 'PreFinal', 'Final'];
		$col            = $grading_column[$ren - 1] ?? 'Final';
		$grading_input  = (array)$this->input->post($col);

		// Build rows
		$data = [];
		$count = count($students);
		for ($i = 0; $i < $count; $i++) {
			$row = [
				'StudentNumber'  => $students[$i] ?? '',
				'SY'             => $sy,
				'Semester'       => $semester,
				'Course'         => $Course[$i]    ?? '',
				'Major'          => $Major[$i]     ?? '',
				'YearLevel'      => $YearLevel[$i] ?? '',
				'LabUnit'        => $LabUnit[$i]   ?? 0,
				'LecUnit'        => $LecUnit[$i]   ?? 0,
				'Section'        => $Section[$i]   ?? $sec,
				'SubjectCode'    => $SubjectCode,
				'Description'    => $Description,
				'TakenAt'        => 1,
				// 'IDNumber'       => $idNumber,
				'dateEncoded'    => $date,      // matches column name
				'timeEncoded'    => $now,
				'Prelim'         => 0,
				'Midterm'        => 0,
				'PreFinal'       => 0,
				'Final'          => 0,
				'PrelimStat'     => 0,
				'MidtermStat'    => 0,
				'PreFinalStat'   => 0,
				'FinalStat'      => 0,
				'Complied'       => '',
				'settingsID'     => $settingsID,
				'Average'        => 0,          // REQUIRED: NOT NULL with no default
			];

			// set the active grading column value
			// set the active grading column value
			$row[$col] = isset($grading_input[$i]) && $grading_input[$i] !== ''
				? $grading_input[$i]
				: 0;


			$data[] = $row;
		}

		// Transaction for safety
		$this->db->trans_start();
		$this->db->insert_batch('grades_o', $data);
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$this->session->set_flashdata('danger', 'Failed to save grades. Please try again.');
		} else {
			$this->session->set_flashdata('success', 'Grades saved successfully.');
		}

		redirect("Masterlist/encodeGrades?SubjectCode={$SubjectCode}&Description={$Description}&Section={$sec}&IDNumber={$idNumber}&ren={$ren}");
	}

	public function update_grades()
	{
		$students     = (array)$this->input->post('StudentNumber');
		$ids          = (array)$this->input->post('id');
		$ren          = (int)$this->input->post('ren');

		$Course       = (array)$this->input->post('Course');
		$Major        = (array)$this->input->post('Major');
		$Section      = (array)$this->input->post('Section');
		$YearLevel    = (array)$this->input->post('YearLevel');

		$SubjectCode  = (string)$this->input->post('SubjectCode');
		$Description  = (string)$this->input->post('Description');
		$sec          = (string)$this->input->post('sec');

		$settingsID   = (int)($this->input->post('settingsID') ?? 1);

		$sy           = (string)$this->session->userdata('sy');
		$semester     = (string)$this->session->userdata('semester');
		// $idNumber     = (string)$this->session->userdata('username');
		// $idNumber           = (string)$this->input->post('IDNumber');

		date_default_timezone_set('Asia/Manila');
		$date = date('Y-m-d');

		$grading_column = ['Prelim', 'Midterm', 'PreFinal', 'Final'];
		$col            = $grading_column[$ren - 1] ?? 'Final';
		$grading_input  = (array)$this->input->post($col);

		$update_data = [];
		$count = count($students);
		for ($i = 0; $i < $count; $i++) {
			// guard against missing ids
			if (!isset($ids[$i]) || $ids[$i] === '') {
				// optional: skip rows without an existing id
				continue;
			}

			$row = [
				'gradesid'      => (int)$ids[$i],
				'StudentNumber' => $students[$i]   ?? '',
				'SubjectCode'   => $SubjectCode,
				'Description'   => $Description,
				'Section'       => $Section[$i]    ?? $sec,
				'SY'            => $sy,
				'Semester'      => $semester,
				'Course'        => $Course[$i]     ?? '',
				'Major'         => $Major[$i]      ?? '',
				'YearLevel'     => $YearLevel[$i]  ?? '',
				'dateEncoded'   => $date,
				// 'IDNumber'      => $idNumber,
				'PrelimStat'    => 0,
				'MidtermStat'   => 0,
				'PreFinalStat'  => 0,
				'FinalStat'     => 0,
				'settingsID'    => $settingsID,
				// keep Average as-is unless you recalc; if you want to touch it:
				// 'Average'        => 0,
			];

			$row[$col] = isset($grading_input[$i]) && $grading_input[$i] !== ''
				? $grading_input[$i]
				: 0;


			$update_data[] = $row;
		}

		if (!empty($update_data)) {
			$this->db->trans_start();
			$this->db->update_batch('grades_o', $update_data, 'gradesid');
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->session->set_flashdata('danger', 'Failed to update grades. Please try again.');
			} else {
				$this->session->set_flashdata('success', 'Grades updated successfully.');
			}
		} else {
			$this->session->set_flashdata('danger', 'No rows to update.');
		}

		redirect("Masterlist/encodeGrades?SubjectCode={$SubjectCode}&Description={$Description}&Section={$sec}&IDNumber={$idNumber}&ren={$ren}");
	}









	public function enrolledListPH()
	{
		$sy   = $this->session->userdata('sy');
		$sem  = $this->session->userdata('semester');
		$phID = $this->session->userdata('username');

		// Resolve PH program
		$prog = $this->db->where('IDNumber', $phID)->get('course_table')->row();

		if (!$prog) {
			$this->session->set_flashdata('danger', 'Your account is not mapped to any program in course_table.');
			redirect('Dashboard'); // or wherever makes sense
			return;
		}

		$courseVal = (string)$prog->CourseDescription;
		$majorVal  = trim((string)($prog->Major ?? ''));

		// Only show students enrolled in this Course & Major for current Sem/SY
		$result['data']         = $this->StudentModel->bySYCourseMajor($sy, $sem, $courseVal, $majorVal);

		// Ancillary lists (keep as your current behavior)
		$result['students']     = $this->StudentModel->getProfile();
		$result['sections']     = $this->StudentModel->getSection();
		$result['scholarships'] = $this->StudentModel->getscholarships();
		$result['strand']       = $this->SettingsModel->getTrack();
		$result['sy']           = $sy;
		$result['sem']          = $sem;
		$result['courseVal']    = $courseVal;
		$result['majorVal']     = $majorVal;

		$this->load->view('enrolled_list_ph', $result);
	}

	// SAVE (Program Head enrollment)
	public function enrollmentAcceptancePH()
	{
		// Only accept POST
		if (!$this->input->post('submit')) {
			redirect('Masterlist/enrolledListPH');
			return;
		}

		$sy   = $this->session->userdata('sy');
		$sem  = $this->session->userdata('semester');
		$phID = $this->session->userdata('username');

		// Resolve PH program
		$prog = $this->db->where('IDNumber', $phID)->get('course_table')->row();
		if (!$prog) {
			$this->session->set_flashdata('danger', 'Your account is not mapped to any program in course_table.');
			redirect('Masterlist/enrolledListPH');
			return;
		}

		$courseVal = (string)$prog->CourseDescription;
		$majorVal  = trim((string)($prog->Major ?? ''));

		// Basic validation (server-side)
		$this->form_validation->set_rules('StudentNumber', 'Student', 'required|trim');
		$this->form_validation->set_rules('YearLevel', 'Year Level', 'required|trim');
		$this->form_validation->set_rules('Section', 'Section', 'required|trim');
		$this->form_validation->set_rules('StudeStatus', 'Student Status', 'required|trim');
		$this->form_validation->set_rules('PayingStatus', 'Account Status', 'required|trim');
		$this->form_validation->set_rules('Status', 'Enrollment Status', 'required|trim');

		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('danger', strip_tags(validation_errors()));
			redirect('Masterlist/enrolledListPH');
			return;
		}

		// Settings (for settingsID)
		$settings = $this->StudentModel->get_srms_settings();
		$settingsID = $settings ? (int)$settings->settingsID : 0;

		// Build clean data (force PH course/major)
		$studentNumber = $this->input->post('StudentNumber', TRUE);
		$email         = $this->input->post('email', TRUE);

		$data = [
			'StudentNumber' => $studentNumber,
			'Course'        => $courseVal,
			'Major'         => $majorVal, // blank string if none
			'YearLevel'     => $this->input->post('YearLevel', TRUE),
			'Section'       => $this->input->post('Section', TRUE),
			'Semester'      => $sem,
			'SY'            => $sy,
			'StudeStatus'   => $this->input->post('StudeStatus', TRUE),
			'YearLevelStat' => $this->input->post('YearLevelStat', TRUE),
			'PayingStatus'  => $this->input->post('PayingStatus', TRUE),
			'Scholarship'   => $this->input->post('Scholarship', TRUE) ?: '',
			'Status'        => $this->input->post('Status', TRUE), // Enrolled / Assessed
			'crossEnrollee' => $this->input->post('crossEnrollee', TRUE),
			'EnroledDate'   => date('Y-m-d'),
			'settingsID'    => $settingsID,
		];

		// Start transaction for safety
		$this->db->trans_start();

		// 🔒 HARD BLOCK: any existing enrollment with Status in ['Enrolled','Assessed'] for this student/term
		$blockStatuses = ['Enrolled', 'Assessed'];
		$existing = $this->StudentModel->getAnyEnrollmentThisTerm($studentNumber, $sy, $sem, $blockStatuses);

		if ($existing) {
			// Normalize Major comparison (NULL vs '')
			$norm = function ($v) {
				return trim((string)$v);
			};
			$sameProgram = ($existing->Course === $data['Course']) && ($norm($existing->Major) === $norm($data['Major']));

			if ($sameProgram) {
				$msg = "This student is already {$existing->Status} in this program — {$existing->Course}"
					. ($norm($existing->Major) !== '' ? " ({$norm($existing->Major)})" : '')
					. ", Year Level: {$existing->YearLevel}, Section: {$existing->Section} — SY {$existing->SY} {$existing->Semester}.";
			} else {
				$msg = "This student is already {$existing->Status} in another course this term — {$existing->Course}"
					. ($norm($existing->Major) !== '' ? " ({$norm($existing->Major)})" : '')
					. ", Year Level: {$existing->YearLevel}, Section: {$existing->Section} — SY {$existing->SY} {$existing->Semester}.";
			}

			$this->db->trans_complete(); // nothing to commit
			$this->session->set_flashdata('danger', $msg);
			redirect('Masterlist/enrolledListPH', 'refresh');
			return;
		}

		// Softer duplicate check (ANY record this term, regardless of Status) to avoid silent duplicates
		$dupe = $this->StudentModel->getAnyEnrollmentThisTerm($studentNumber, $sy, $sem, []); // [] = no status filter
		if ($dupe) {
			// Let the PH know there's already a row this term; we won’t insert another
			$msg = "An enrollment record for this student already exists this term (Status: {$dupe->Status}) — "
				. "{$dupe->Course}" . (trim((string)$dupe->Major) !== '' ? " ({$dupe->Major})" : '')
				. ", Year Level: {$dupe->YearLevel}, Section: {$dupe->Section}.";
			$this->db->trans_complete();
			$this->session->set_flashdata('danger', $msg);
			redirect('Masterlist/enrolledListPH', 'refresh');
			return;
		}

		// Insert to semesterstude
		$this->db->insert('semesterstude', $data);

		// Sync minimal profile fields
		$this->db->where('StudentNumber', $data['StudentNumber'])
			->update('studeprofile', [
				'course'    => $data['Course'],
				'major'     => $data['Major'],
				'yearLevel' => $data['YearLevel'],
			]);

		// Mark online_enrollment, if exists
		$this->db->where([
			'StudentNumber' => $data['StudentNumber'],
			'Semester'      => $data['Semester'],
			'SY'            => $data['SY'],
		])->update('online_enrollment', ['enrolStatus' => 'Enrolled']);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			// DB error
			$this->session->set_flashdata('danger', 'Failed to save enrollment. Please try again.');
			redirect('Masterlist/enrolledListPH');
			return;
		}


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



		// Optional email (non-fatal)
		if (!empty($email)) {
			$msg  = 'Dear Student,<br><br>';
			$msg .= 'You are now officially enrolled.<br>';
			$msg .= "Course: <b>{$data['Course']}</b><br>";
			$msg .= "Major: <b>" . html_escape($data['Major']) . "</b><br>";
			$msg .= "Year Level: <b>{$data['YearLevel']}</b><br>";
			$msg .= "Section: <b>{$data['Section']}</b><br>";
			$msg .= "Sem/SY: <b>{$data['Semester']}, {$data['SY']}</b><br>";
			$msg .= "Status: <b>{$data['Status']}</b><br><br>SRMS Online";

			// Hand off to the queue; delivery never blocks the workflow.
			fbmso_mailqueue_push($this, $email, 'Enrollment Confirmation', $msg);
		}

		$this->session->set_flashdata('success', 'Student successfully enrolled under your program.');
		redirect('Masterlist/enrolledListPH');
	}










	public function receiveGrades()
	{
		if (!$this->input->is_ajax_request()) {
			return $this->output->set_status_header(405)->set_output('Method Not Allowed');
		}

		// Inputs
		$sy        = trim($this->input->post('SY', TRUE) ?: '');
		$sem       = trim($this->input->post('Semester', TRUE) ?: '');
		$code      = trim($this->input->post('SubjectCode', TRUE) ?: '');
		$desc      = trim($this->input->post('Description', TRUE) ?: '');
		$section   = trim($this->input->post('Section', TRUE) ?: '');
		$course    = trim($this->input->post('Course', TRUE) ?: '');
		$major     = trim($this->input->post('Major', TRUE) ?: '');
		$yearLevel = trim($this->input->post('YearLevel', TRUE) ?: '');
		$idNumber  = trim($this->input->post('IDNumber', TRUE) ?: '');
		$period    = strtolower(trim($this->input->post('period', TRUE) ?: '')); // prelim|midterm|prefinal|final

		if (!in_array($period, ['prelim', 'midterm', 'prefinal', 'final'], true)) {
			return $this->output->set_content_type('application/json')
				->set_status_header(422)
				->set_output(json_encode(['ok' => false, 'msg' => 'Invalid period']));
		}

		foreach (
			[
				'SY' => $sy,
				'Semester' => $sem,
				'SubjectCode' => $code,
				'Description' => $desc,
				'Section' => $section,
				'Course' => $course,
				'YearLevel' => $yearLevel,
				'IDNumber' => $idNumber
			] as $k => $v
		) {
			if ($v === '') {
				return $this->output->set_content_type('application/json')
					->set_status_header(422)
					->set_output(json_encode(['ok' => false, 'msg' => "Missing: {$k}"]));
			}
		}

		date_default_timezone_set('Asia/Manila');
		$actor = (string)$this->session->userdata('username');
		if ($actor === '') $actor = (string)$this->session->userdata('id') ?: 'system';
		$now = date('Y-m-d H:i:s');

		// Lock column map for college_grade_locks
		$lockMap = [
			'prelim'   => 'lock_prelim',
			'midterm'  => 'lock_midterm',
			'prefinal' => 'lock_prefinal',
			'final'    => 'lock_final'
		];
		$lockCol = $lockMap[$period];

		// Grade column maps (strict whitelist)
		$periodCol = [
			'prelim'   => ['grade' => 'Prelim',   'stat' => 'PrelimStat'],
			'midterm'  => ['grade' => 'Midterm',  'stat' => 'MidtermStat'],
			'prefinal' => ['grade' => 'PreFinal', 'stat' => 'PreFinalStat'],
			'final'    => ['grade' => 'Final',    'stat' => 'FinalStat'],
		];
		$G  = $periodCol[$period]['grade'];  // e.g. "Midterm"
		$Gs = $periodCol[$period]['stat'];   // e.g. "MidtermStat"

		$this->db->trans_start();

		// (1) Upsert receipt (unchanged)
		$sqlReceipt = "INSERT INTO grade_receipts
        (SY,Semester,SubjectCode,Description,Section,Course,Major,YearLevel,IDNumber,period,received_by,received_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE received_by=VALUES(received_by), received_at=VALUES(received_at)";
		$okReceipt = $this->db->query($sqlReceipt, [
			$sy,
			$sem,
			$code,
			$desc,
			$section,
			$course,
			$major,
			$yearLevel,
			$idNumber,
			$period,
			$actor,
			$now
		]);

		// (2a) Ensure a row exists in `grades` per student in the class (do not touch period columns yet)
		// Uses INSERT ... ON DUPLICATE KEY to NO-OP when row already exists
		$sqlEnsure = "
        INSERT INTO grades (
            StudentNumber, Course, Major, YearLevel, Section,
            SubjectCode, Description, LecUnit, LabUnit, IDNumber,
            SY, Semester, timeEncoded, dateEncoded, TakenAt, settingsID, Average
        )
        SELECT
            TRIM(r.StudentNumber),
            TRIM(COALESCE(r.Course, '')),
            TRIM(COALESCE(r.Major, '')),
            TRIM(COALESCE(r.YearLevel, '')),
            TRIM(r.Section),
            TRIM(r.SubjectCode),
            TRIM(COALESCE(r.Description, '')),
            TRIM(COALESCE(r.LecUnit, '')),
            TRIM(COALESCE(r.LabUnit, '')),
            TRIM(COALESCE(r.IDNumber, '')),
            r.SY, r.Semester,
            TRIM(COALESCE(r.timeEncoded, '')),
            COALESCE(r.dateEncoded, '2024-10-10'),
            TRIM(COALESCE(r.TakenAt, '')),
            COALESCE(r.settingsID, 1),
            COALESCE(r.Average, 0)
        FROM grades_o r
        WHERE r.SY=? AND r.Semester=? AND r.SubjectCode=? AND r.Section=?
        ON DUPLICATE KEY UPDATE StudentNumber = VALUES(StudentNumber)"; // no-op
		$okEnsure = $this->db->query($sqlEnsure, [$sy, $sem, $code, $section]);

		// (2b) Update ONLY the selected period columns + refresh shared fields from grades_o
		// Build the SQL with whitelisted column names
		$sqlUpdate = "
        UPDATE grades g
        JOIN grades_o r
          ON r.StudentNumber = g.StudentNumber
         AND r.SubjectCode  = g.SubjectCode
         AND r.Section      = g.Section
         AND r.SY           = g.SY
         AND r.Semester     = g.Semester
        SET
            -- sync shared (non-period) fields from source
            g.Course      = COALESCE(r.Course, g.Course),
            g.Major       = COALESCE(r.Major, g.Major),
            g.YearLevel   = COALESCE(r.YearLevel, g.YearLevel),
            g.Description = COALESCE(r.Description, g.Description),
            g.LecUnit     = COALESCE(r.LecUnit, g.LecUnit),
            g.LabUnit     = COALESCE(r.LabUnit, g.LabUnit),
            g.IDNumber    = COALESCE(r.IDNumber, g.IDNumber),
            g.TakenAt     = COALESCE(r.TakenAt, g.TakenAt),
            g.settingsID  = COALESCE(r.settingsID, g.settingsID),
            g.timeEncoded = COALESCE(r.timeEncoded, g.timeEncoded),
            g.dateEncoded = COALESCE(r.dateEncoded, g.dateEncoded),
            g.Average     = COALESCE(r.Average, g.Average),

            -- update ONLY the chosen period:
            g.{$G}  = r.{$G},
            g.{$Gs} = r.{$Gs}

        WHERE r.SY=? AND r.Semester=? AND r.SubjectCode=? AND r.Section=?
    ";
		$okMirror = $this->db->query($sqlUpdate, [$sy, $sem, $code, $section]);

		// (3) Flip the corresponding lock (insert if missing)
		$sqlLock = "
        INSERT INTO college_grade_locks
            (sy, semester, subjectcode, description, section, yearlevel, {$lockCol}, updated_by, updated_at)
        VALUES (?,?,?,?,?,?,1,?,?)
        ON DUPLICATE KEY UPDATE
            {$lockCol}=1,
            updated_by=VALUES(updated_by),
            updated_at=VALUES(updated_at)";
		$okLock = $this->db->query($sqlLock, [
			$sy,
			$sem,
			$code,
			$desc,
			$section,
			$yearLevel,
			$actor,
			$now
		]);

		$this->db->trans_complete();

		$ok = $this->db->trans_status() && $okReceipt && $okEnsure && $okMirror && $okLock;

		return $this->output->set_content_type('application/json')->set_output(json_encode([
			'ok'          => $ok ? true : false,
			'msg'         => $ok ? "Received and mirrored {$period} to grades, then locked {$period}." : 'Save failed.',
			'receipt_ok'  => (bool)$okReceipt,
			'ensure_ok'   => (bool)$okEnsure,
			'mirror_ok'   => (bool)$okMirror,
			'lock_ok'     => (bool)$okLock,
			'context'     => [
				'SY' => $sy,
				'Semester' => $sem,
				'SubjectCode' => $code,
				'Section' => $section,
				'Period' => $period,
				'UpdatedCols' => [$G, $Gs],
				'LockColumn' => $lockCol
			]
		]));
	}
}

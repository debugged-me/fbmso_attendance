<?php
class Student extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->helper('url');
    $this->load->model('StudentModel');
    $this->load->model('StudentEnrollment');
    $this->load->model('StudentAccounts');
    $this->load->model('SettingsModel');
    $this->load->library('session');
    $this->load->library('user_agent');

    if ($this->session->userdata('logged_in') !== TRUE) {
      redirect('login');
    }
  }

  public function search_select2()
  {
    $q = $this->input->get('q', true);

    $this->db->select('StudentNumber, LastName, FirstName, MiddleName');
    if (!empty($q)) {
      $this->db->group_start()
        ->like('StudentNumber', $q)
        ->or_like('LastName', $q)
        ->or_like('FirstName', $q)
        ->group_end();
    }
    $this->db->limit(20);
    $rows = $this->db->get('studeprofile')->result();

    $out = [];
    foreach ($rows as $r) {
      $full = trim($r->LastName . ', ' . $r->FirstName . ' ' . $r->MiddleName);
      $out[] = ['id' => $r->StudentNumber, 'text' => $r->StudentNumber . ' — ' . $full];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
  }

  public function view_account()
  {
    $sy = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    // Pass sy and sem to the model function
    $data['students'] = $this->StudentAccounts->get_all_students($sy, $sem);

    $this->load->view('account_view', $data);
  }

  public function get_student_data()
  {
    $student_number = $this->input->post('student_number');
    $sy = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    $student = $this->StudentAccounts->get_student_by_number($student_number, $sy, $sem);

    if (!$student) {
      echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
      return;
    }

    $fees = $this->StudentAccounts->get_applicable_fees_by_student($student);
    $rates = $this->StudentAccounts->get_course_rate($student->Course, $student->Major, $student->YearLevel);

    $totalPayments = $this->StudentAccounts->get_total_payments($student->StudentNumber, $student->Semester, $student->SY);
    $unpaidBalances = $this->StudentAccounts->get_unpaid_balances_other_sy($student->StudentNumber, $sy);
    $units = $this->StudentAccounts->get_total_units($student->StudentNumber, $sy, $sem); // <- added

    echo json_encode([
      'status' => 'success',
      'student' => $student,
      'fees' => $fees,
      'totalPayments' => $totalPayments,
      'unpaidBalances' => $unpaidBalances,
      'totalLecUnit' => $units->totalLecUnit ?? 0,
      'totalLabUnit' => $units->totalLabUnit ?? 0,
      'lecRate' => $rates->LecRate ?? 0,
      'labRate' => $rates->LabRate ?? 0
    ]);
  }


  /**
   * Save student account with computed fees and payment summary
   */

  public function save_studeaccount()
  {
    $studentNumber = trim($this->input->post('studentNumber'));
    $feesJson      = $this->input->post('fees');
    $totalFees     = floatval($this->input->post('totalFees'));
    $major         = $this->input->post('Major');
    $fees          = json_decode($feesJson);

    $lecUnitsInput = $this->input->post('lecUnits');
    $lecRateInput  = $this->input->post('lecRate');
    $labUnitsInput = $this->input->post('labUnits');
    $labRateInput  = $this->input->post('labRate');
    $tuitionFee  = $this->input->post('tuitionFee');
    $labFee  = $this->input->post('labFee');

    $sy  = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    if (!$studentNumber || !$fees || $totalFees <= 0) {
      $this->session->set_flashdata('msg', '<div class="alert alert-danger">Invalid input data.</div>');
      redirect('Student/view_account');
      return;
    }

    $student = $this->db->get_where('registration', [
      'StudentNumber' => $studentNumber,
      'SY' => $sy,
      'Sem' => $sem
    ])->row();

    if (!$student) {
      $this->session->set_flashdata('msg', '<div class="alert alert-danger">Student not found.</div>');
      redirect('Student/view_account');
      return;
    }

    $existing = $this->db->get_where('studeaccount', [
      'StudentNumber' => $studentNumber,
      'SY' => $sy,
      'Sem' => $sem
    ])->row();

    if ($existing) {
      $this->session->set_flashdata('msg', "<div class='alert alert-warning'>Account already exists for this SY ($sy) and Semester ($sem).</div>");
      redirect('Student/view_account');
      return;
    }

    // Count Lec and Lab units from registration
    $this->db->select_sum('LecUnit', 'totalLecUnits');
    $this->db->select_sum('LabUnit', 'totalLabUnits');
    $this->db->where(['StudentNumber' => $studentNumber, 'SY' => $sy, 'Sem' => $sem]);
    $units = $this->db->get('registration')->row();
    $defaultLecUnits = floatval($units->totalLecUnits);
    $defaultLabUnits = floatval($units->totalLabUnits);

    // Get Lec and Lab Rate based on coursefees
    $this->db->where([
      'Course'     => $student->Course,
      'Major'      => $major,
      'YearLevel'  => $student->YearLevel,
      'Sem'        => $sem,
      'SY'         => $sy
    ]);
    $courseFees = $this->db->get('coursefees')->row();

    $defaultLecRate = floatval($courseFees->LecRate ?? 0);
    $defaultLabRate = floatval($courseFees->LabRate ?? 0);

    $lecUnits = is_numeric($lecUnitsInput) ? floatval($lecUnitsInput) : $defaultLecUnits;
    $lecRate  = is_numeric($lecRateInput)  ? floatval($lecRateInput)  : $defaultLecRate;
    $labUnits = is_numeric($labUnitsInput) ? floatval($labUnitsInput) : $defaultLabUnits;
    $labRate  = is_numeric($labRateInput)  ? floatval($labRateInput)  : $defaultLabRate;

    $totalLec = $lecUnits * $lecRate;
    $totalLab = $labUnits * $labRate;

    // Unpaid balance check
    $this->db->where('StudentNumber', $studentNumber);
    $this->db->where('SY !=', $sy);
    $this->db->where('CurrentBalance >', 0);
    $old_bal = $this->db->get('studeaccount')->result();

    if (!empty($old_bal)) {
      $msg = "<div class='alert alert-danger'><strong>Unpaid balances found:</strong><ul>";
      foreach ($old_bal as $b) {
        $msg .= "<li>SY {$b->SY} - {$b->Sem}: ₱" . number_format($b->CurrentBalance, 2) . "</li>";
      }
      $msg .= "</ul></div>";
      $this->session->set_flashdata('msg', $msg);
      redirect('Student/view_account');
      return;
    }

    $this->db->select_sum('Amount');
    $this->db->where(['StudentNumber' => $studentNumber, 'Sem' => $sem, 'SY' => $sy]);
    $paymentsResult = $this->db->get('paymentsaccounts')->row();
    $totalPayments = (float)($paymentsResult->Amount ?? 0);
    $currentBalance = $totalFees - $totalPayments;

    // INSERT: Tuition Fee (Lecture)
    $this->db->insert('studeaccount', [
      'StudentNumber'  => $student->StudentNumber,
      'Course'         => $student->Course,
      'Major'          => $major,
      'YearLevel'      => $student->YearLevel,
      'FeesDesc'       => 'Tuition Fee (Lecture):',
      'FeesAmount'     => $tuitionFee,
      'feesType'       => 'Tuition',
      'TotalFees'      => $totalFees,
      'AcctTotal'      => $totalFees,
      'TotalPayments'  => $totalPayments,
      'CurrentBalance' => $currentBalance,
      'Sem'            => $sem,
      'SY'             => $sy,
      'Section'        => $student->Section,
      'settingsID'     => $student->settingsID,
      'LecUnits'       => $lecUnits,
      'LecRate'        => $lecRate,
      'TotalLec'       => $totalLec,
      'LabUnits'       => $labUnits,
      'LabRate'        => $labRate,
      'TotalLab'       => $totalLab
    ]);

    // INSERT: Laboratory Fee
    $this->db->insert('studeaccount', [
      'StudentNumber'  => $student->StudentNumber,
      'Course'         => $student->Course,
      'Major'          => $major,
      'YearLevel'      => $student->YearLevel,
      'FeesDesc'       => 'Laboratory Fee:',
      'FeesAmount'     => $labFee,
      'feesType'       => 'Laboratory',
      'TotalFees'      => $totalFees,
      'AcctTotal'      => $totalFees,
      'TotalPayments'  => $totalPayments,
      'CurrentBalance' => $currentBalance,
      'Sem'            => $sem,
      'SY'             => $sy,
      'Section'        => $student->Section,
      'settingsID'     => $student->settingsID,
      'LecUnits'       => $lecUnits,
      'LecRate'        => $lecRate,
      'TotalLec'       => $totalLec,
      'LabUnits'       => $labUnits,
      'LabRate'        => $labRate,
      'TotalLab'       => $totalLab
    ]);

    // INSERT: Other non-rate fees
    foreach ($fees as $fee) {
      if ($fee->feesType === 'Rate') continue;

      $data = [
        'StudentNumber'  => $student->StudentNumber,
        'Course'         => $student->Course,
        'Major'          => $major,
        'YearLevel'      => $student->YearLevel,
        'FeesDesc'       => $fee->Description ?? '',
        'FeesAmount'     => $fee->Amount ?? 0,
        'feesType'       => $fee->feesType ?? 'N/A',
        'TotalFees'      => $totalFees,
        'AcctTotal'      => $totalFees,
        'TotalPayments'  => $totalPayments,
        'CurrentBalance' => $currentBalance,
        'Sem'            => $sem,
        'SY'             => $sy,
        'Section'        => $student->Section,
        'settingsID'     => $student->settingsID,
        'LecUnits'       => $lecUnits,
        'LecRate'        => $lecRate,
        'TotalLec'       => $totalLec,
        'LabUnits'       => $labUnits,
        'LabRate'        => $labRate,
        'TotalLab'       => $totalLab
      ];

      $this->db->insert('studeaccount', $data);
    }

    // Audit Trail
    $this->db->insert('atrail', [
      'atDesc' => "Created student account for: $studentNumber (SY: $sy, Sem: $sem)",
      'atDate' => date('Y-m-d'),
      'atTime' => date('H:i:s'),
      'atRes'  => $this->session->userdata('username') ?? 'Unknown',
      'atSNo'  => $studentNumber
    ]);

    $this->session->set_flashdata('msg', "<div class='alert alert-success'>Student account saved successfully for SY $sy and Semester $sem.</div>");
    redirect('Student/view_account');
  }

  public function get_fees()
  {
    $course = $this->input->post('course');
    $major = $this->input->post('major');
    $year_level = $this->input->post('year_level');
    $semester = $this->input->post('semester');

    log_message('debug', print_r($_POST, true)); // Debug incoming POST data

    $fees = $this->StudentAccounts->get_applicable_fees($course, $major, $year_level, $semester);
    echo json_encode($fees);
  }

  public function save_account()
  {
    $data = $this->input->post();
    $this->StudentAccounts->insert_student_account($data);
    echo json_encode(['status' => 'success']);
  }

  public function enlistment()
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    $this->db->select('semesterstude.StudentNumber, semesterstude.Course, semesterstude.Major, semesterstude.YearLevel, semesterstude.Section, semesterstude.Semester, semesterstude.SY, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
    $this->db->from('semesterstude');
    $this->db->join('studeprofile', 'semesterstude.StudentNumber = studeprofile.StudentNumber');
    $this->db->where('semesterstude.Semester', $semester);
    $this->db->where('semesterstude.SY', $sy);
    $this->db->order_by('studeprofile.LastName', 'ASC');

    $data['students'] = $this->db->get()->result();
    $this->load->view('enlistment_view', $data);
  }

  public function fetchSubjects()
  {
    $studentNumber = $this->input->post('studentNumber');
    $this->load->model('StudentEnrollment');
    $student = $this->StudentEnrollment->getStudentDetails($studentNumber);

    if (!$student) {
      echo json_encode([]);
      return;
    }

    $subjects = $this->StudentEnrollment->getAvailableSubjects($student);
    echo json_encode($subjects);
  }


  public function registerSubject()
  {
    $data = [
      'SubjectCode'   => $this->input->post('SubjectCode'),
      'Description'   => $this->input->post('Description'),
      'LecUnit'       => $this->input->post('LecUnit'),
      'LabUnit'       => $this->input->post('LabUnit'),
      'Section'       => $this->input->post('Section'),
      'SchedTime'     => $this->input->post('SchedTime'),
      'Room'          => $this->input->post('Room'),
      'IDNumber'      => $this->input->post('IDNumber'), // instructor (nullable if you allow)
      'Sem'           => $this->session->userdata('semester'),
      'SY'            => $this->session->userdata('sy'),
      'Course'        => $this->input->post('Course'),
      'YearLevel'     => $this->input->post('YearLevel'),
      'StudentNumber' => $this->input->post('StudentNumber'),
      'enrolledBy'    => $this->session->userdata('username')
    ];

    $major = $this->input->post('Major');
    if (!empty($major)) {
      $data['Major'] = $major;
    }

    // Basic presence checks (optional — comment out if you prefer DB to enforce)
    if (empty($data['SubjectCode']) || empty($data['StudentNumber']) || empty($data['Sem']) || empty($data['SY'])) {
      return $this->output->set_content_type('application/json')
        ->set_output(json_encode(['status' => 'error', 'msg' => 'Missing required fields.']));
    }

    // Avoid duplicates
    $exists = $this->db->get_where('registration', [
      'StudentNumber' => $data['StudentNumber'],
      'SubjectCode'   => $data['SubjectCode'],
      'Sem'           => $data['Sem'],
      'SY'            => $data['SY']
    ])->num_rows();

    if ($exists > 0) {
      return $this->output->set_content_type('application/json')
        ->set_output(json_encode(['status' => 'exists']));
    }

    // Insert registration row
    $ok = $this->db->insert('registration', $data);
    if (!$ok) {
      return $this->output->set_content_type('application/json')
        ->set_output(json_encode(['status' => 'error', 'msg' => 'Insert failed.']));
    }

    // Audit trail
    $this->db->insert('atrail', [
      'atDesc' => "Enlisted subject {$data['SubjectCode']} for {$data['StudentNumber']}",
      'atDate' => date('Y-m-d'),
      'atTime' => date('H:i:s'),
      'atRes'  => $data['enrolledBy'],
      'atSNo'  => $data['StudentNumber']
    ]);

    return $this->output->set_content_type('application/json')
      ->set_output(json_encode(['status' => 'success']));
  }



  public function checkEnrollmentStatus()
  {
    $studentNumber = $this->input->post('studentNumber');
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    $this->db->where([
      'StudentNumber' => $studentNumber,
      'Sem'           => $semester,
      'SY'            => $sy
    ]);
    $query = $this->db->get('registration');

    echo json_encode(['enrolled' => $query->num_rows() > 0]);
  }

  public function viewEnrolledSubjects($studentNumber)
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    // . Fetch enrolled subjects with instructor name from staff
    $this->db->select('r.*, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS Instructor');
    $this->db->from('registration r');
    $this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
    $this->db->where('r.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $enrolled = $this->db->get()->result();

    // . Fetch student details
    $this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, r.Course, r.YearLevel');
    $this->db->from('studeprofile sp');
    $this->db->join('registration r', 'sp.StudentNumber = r.StudentNumber');
    $this->db->where('sp.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $this->db->limit(1);
    $student = $this->db->get()->row();

    $data['student'] = $student;
    $data['enrolled'] = $enrolled;

    $this->load->view('enrolled_subjects_view', $data);
  }



  public function viewEnrolledSubjectsStude($studentNumber)
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    // . Fetch enrolled subjects with instructor name from staff
    $this->db->select('r.*, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS Instructor');
    $this->db->from('registration r');
    $this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
    $this->db->where('r.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $enrolled = $this->db->get()->result();

    // . Fetch student details
    $this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, r.Course, r.YearLevel');
    $this->db->from('studeprofile sp');
    $this->db->join('registration r', 'sp.StudentNumber = r.StudentNumber');
    $this->db->where('sp.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $this->db->limit(1);
    $student = $this->db->get()->row();

    $data['student'] = $student;
    $data['enrolled'] = $enrolled;

    $this->load->view('enrolled_subjects_viewv2', $data);
  }




  public function print_enlistment_report($studentNumber)
  {
    $this->load->model('Login_model');
    $this->load->model('StudentEnrollment');

    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');
    $programHeadID = $this->session->userdata('username');

    // Get enrolled subjects
    $this->db->select('r.*, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS Instructor');
    $this->db->from('registration r');
    $this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
    $this->db->where('r.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $enrolled = $this->db->get()->result();

    // . Use the model method to get student info safely
    $student = $this->StudentEnrollment->getStudentInfoWithRegistration($studentNumber, $sy, $semester);

    // Letterhead
    $data['letterhead'] = $this->Login_model->getSchoolInformation();
    $data['school'] = !empty($data['letterhead']) ? $data['letterhead'][0] : null;

    // Program Head Info
    $programHead = $this->db
      ->select('fname, mname, lname')
      ->get_where('o_users', ['username' => $programHeadID])
      ->row();

    // Load view
    $this->load->view('enlistment_report_view', [
      'student' => $student,
      'enrolled' => $enrolled,
      'school' => $data['school'],
      'sy' => $sy,
      'sem' => $semester,
      'programHead' => $programHead
    ]);
  }






  public function sendEmailCor()
  {
    $studentNumber = $this->input->post('studentNumber') ?? $this->input->get('studentNumber');
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    // Get student info
    $this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, sp.email, r.Course, r.YearLevel');
    $this->db->from('studeprofile sp');
    $this->db->join('registration r', 'sp.StudentNumber = r.StudentNumber');
    $this->db->where('sp.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $this->db->limit(1);
    $student = $this->db->get()->row();

    if (!$student || empty($student->email)) {
      $this->session->set_flashdata('danger', 'Student email not found.');
      redirect($_SERVER['HTTP_REFERER']);
      return;
    }

    // Get enrolled subjects
    $this->db->select('r.SubjectCode, r.Description, r.Section, r.SchedTime, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS Instructor');
    $this->db->from('registration r');
    $this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
    $this->db->where('r.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $enrolled = $this->db->get()->result();

    if (empty($enrolled)) {
      $this->session->set_flashdata('danger', 'No enrolled subjects found.');
      redirect($_SERVER['HTTP_REFERER']);
      return;
    }

    // Get school name and banner image
    $schoolName = $this->SettingsModel->getSchoolName();
    $letterheadImg = $this->SettingsModel->getLetterheadImage(); // Returns base_url('upload/banners/' . image)

    $loginURL = base_url('login');

    // Build HTML email
    $mail_message = '
      <div style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
          <div style="max-width: 650px; margin: auto; background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">';

    if (!empty($letterheadImg)) {
      $mail_message .= '
              <div style="text-align: center; margin-bottom: 20px;">
                  <img src="' . htmlspecialchars($letterheadImg) . '" alt="School Banner" style="max-width: 100%; max-height: 120px;">
              </div>';
    }

    $mail_message .= '
          <h2 style="color: #2b6cb0; text-align: center;">Certificate of Registration (CoR)</h2>

              <p>Dear <strong>' . htmlspecialchars($student->FirstName) . '</strong>,</p>
              <p>This is your enrolled subject list for the <strong>' . $semester . '</strong>, School Year <strong>' . $sy . '</strong>.</p>
  
              <p>
                  <strong>Student Number:</strong> ' . $student->StudentNumber . '<br>
                  <strong>Name:</strong> ' . $student->LastName . ', ' . $student->FirstName . ' ' . $student->MiddleName . '<br>
                  <strong>Course:</strong> ' . $student->Course . ' | <strong>Year Level:</strong> ' . $student->YearLevel . '<br>
              </p>
  
              <table style="width:100%; border-collapse: collapse; margin-top: 15px;">
                  <thead>
                      <tr>
                          <th style="border: 1px solid #ddd; padding: 8px;">Subject Code</th>
                          <th style="border: 1px solid #ddd; padding: 8px;">Description</th>
                          <th style="border: 1px solid #ddd; padding: 8px;">Section</th>
                          <th style="border: 1px solid #ddd; padding: 8px;">Schedule</th>
                          <th style="border: 1px solid #ddd; padding: 8px;">Instructor</th>
                      </tr>
                  </thead>
                  <tbody>';

    foreach ($enrolled as $subj) {
      $mail_message .= '<tr>
              <td style="border: 1px solid #ddd; padding: 8px;">' . $subj->SubjectCode . '</td>
              <td style="border: 1px solid #ddd; padding: 8px;">' . $subj->Description . '</td>
              <td style="border: 1px solid #ddd; padding: 8px;">' . $subj->Section . '</td>
              <td style="border: 1px solid #ddd; padding: 8px;">' . $subj->SchedTime . '</td>
              <td style="border: 1px solid #ddd; padding: 8px;">' . $subj->Instructor . '</td>
          </tr>';
    }

    $mail_message .= '</tbody>
              </table>
  
              <p style="margin-top: 30px;">You can log in anytime to SRMS at <a href="' . $loginURL . '">' . $loginURL . '</a></p>
              <p>Best regards,<br><strong>' . htmlspecialchars($schoolName) . ' SRMS Team</strong></p>
          </div>
      </div>';

    // Queue it; the EmailQueue cron sender delivers it.
    if (fbmso_mailqueue_push($this, $student->email, 'Your Certificate of Registration', $mail_message, $schoolName)) {
      $this->session->set_flashdata('success', 'CoR queued for delivery to ' . $student->email . '. It usually arrives within a couple of minutes.');
    } else {
      $this->session->set_flashdata('danger', 'Failed to queue the CoR email. Please check the student\'s email address.');
    }

    redirect($_SERVER['HTTP_REFERER']);
  }


  public function removeSubject()
  {
    $studentNumber = $this->input->post('StudentNumber');
    $subjectCode   = $this->input->post('SubjectCode');
    $semester      = $this->input->post('Sem');
    $sy            = $this->input->post('SY');

    // Delete from registration
    $this->db->where([
      'StudentNumber' => $studentNumber,
      'SubjectCode'   => $subjectCode,
      'Sem'           => $semester,
      'SY'            => $sy
    ]);
    $deleted = $this->db->delete('registration');

    // Save audit trail if deleted
    if ($deleted) {
      $logData = [
        'atDesc' => "Removed subject $subjectCode for SY $sy, Sem $semester",
        'atDate' => date('Y-m-d'),
        'atTime' => date('H:i:s'),
        'atRes'  => $this->session->userdata('username'), // or user_id if stored differently
        'atSNo'  => $studentNumber
      ];
      $this->db->insert('atrail', $logData);

      $this->session->set_flashdata('success', 'Subject successfully removed.');
    } else {
      $this->session->set_flashdata('danger', 'Failed to remove subject.');
    }

    redirect($this->agent->referrer());
  }

  public function getAvailableSubjectsGrouped()
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    $this->db->select('Course, SubjectCode, Description');
    $this->db->from('semsubjects');
    $this->db->where('Semester', $semester);
    $this->db->where('SY', $sy);
    $this->db->order_by('Course');
    $this->db->order_by('Description');

    $results = $this->db->get()->result();

    $grouped = [];
    foreach ($results as $row) {
      $grouped[$row->Course][] = [
        'id' => $row->SubjectCode,
        'text' => $row->SubjectCode . ' - ' . $row->Description
      ];
    }

    $output = [];
    foreach ($grouped as $course => $subjects) {
      $output[] = [
        'text' => $course,
        'children' => $subjects
      ];
    }

    echo json_encode($output);
  }

  public function fetchSubjectsForModal()
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    $this->db->where('Semester', $semester);
    $this->db->where('SY', $sy);
    $this->db->order_by('Course');
    $this->db->order_by('YearLevel');
    $this->db->order_by('Description');
    $subjects = $this->db->get('semsubjects')->result();

    $grouped = [];
    foreach ($subjects as $subj) {
      $course = $subj->Course;
      $year = $subj->YearLevel;
      $grouped[$course][$year][] = $subj;
    }

    echo json_encode($grouped);
  }




  function index()
  {

    if ($this->session->userdata('level') === 'Student') {
      $this->load->view('account_tracking');
    } else {
      echo "Access Denied";
    }
  }


  public function print_grades($student_number)
  {
    $data['profile'] = $this->StudentModel->get_student_profile($student_number);
    $data['settings'] = $this->StudentModel->get_srms_settings();
    $data['grades'] = $this->StudentModel->get_grades($student_number);

    if (!$data['profile']) {
      show_404(); // student not found
    }

    $this->load->view('print_grades', $data);
  }

  public function email_grades($student_number)
  {
    $this->load->model('StudentModel');

    $profile = $this->StudentModel->get_student_profile($student_number);
    $settings = $this->StudentModel->get_srms_settings();
    $grades = $this->StudentModel->get_grades($student_number);

    if (!$profile || empty($profile->email)) {
      $this->session->set_flashdata('danger', 'Student email not found.');
      redirect($_SERVER['HTTP_REFERER']);
      return;
    }

    // Compose the HTML message
    $letterheadImg = !empty($settings->letterhead_web)
      ? base_url('upload/banners/' . $settings->letterhead_web)
      : base_url('assets/images/default_letterhead.jpg');

    $mail_message = '
    <div style="font-family: Arial, sans-serif; background: #fff; padding: 20px; max-width: 700px; margin: auto;">
        <div style="text-align: center;">
            <img src="' . htmlspecialchars($letterheadImg) . '" alt="Letterhead" style="max-width: 100%; height: auto; margin-bottom: 20px;">
            <h2 style="color: #2b6cb0; text-align: center;">Report of Grades</h2>
        </div>
        <p><strong>Student Name:</strong> ' . $profile->FirstName . ' ' . $profile->MiddleName . ' ' . $profile->LastName . '</p>
        <p><strong>Course:</strong> ' . $profile->course . '</p>';

    if (!empty($grades)) {
      $mail_message .= '
        <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 13px;">
            <thead style="background: #f1f1f1;">
                <tr>
                    <th>Sem./SY</th>
                    <th>Subject Code</th>
                    <th>Description</th>
                    <th>Finals</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>';

      $sem_sy = '';
      foreach ($grades as $grade) {
        $showSem = ($sem_sy != $grade->Semester . ', ' . $grade->SY) ? htmlentities($grade->Semester . ', ' . $grade->SY) : '';
        $mail_message .= '
                <tr>
                    <td>' . $showSem . '</td>
                    <td>' . htmlentities($grade->SubjectCode) . '</td>
                    <td>' . htmlentities($grade->Description) . '</td>
                    <td align="center">' . $grade->Final . '</td>
                    <td align="center">' . ($grade->Final <= 3.5 ? 'Passed' : 'Failed') . '</td>
                </tr>';
        $sem_sy = $grade->Semester . ', ' . $grade->SY;
      }

      $mail_message .= '</tbody></table>';
    } else {
      $mail_message .= '<p style="color: red; font-weight: bold;">No grade records found.</p>';
    }

    $schoolName = $settings->SchoolName ?? 'SRMS';

    $mail_message .= '
        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>' . htmlspecialchars($schoolName) . '</strong> Team
        </p>
        <p style="font-size: 12px; color: #888888; margin-top: 20px;">
            <em>This is a system-generated report. Please do not reply to this email.</em>
        </p>
    </div>';

    if (fbmso_mailqueue_push($this, $profile->email, 'Report of Grades – ' . $schoolName, $mail_message, $schoolName)) {
      $this->session->set_flashdata('success', 'Report of Grades queued for delivery to <strong>' . $profile->email . '</strong>.');
    } else {
      $this->session->set_flashdata('danger', 'Failed to queue the Report of Grades email.');
    }

    redirect($_SERVER['HTTP_REFERER']);
  }


  public function upload_requirement()
  {
    $studentNumber = $this->input->post('StudentNumber');
    $requirementId = $this->input->post('requirement_id');

    if (!empty($_FILES['requirement_file']['name'])) {
      $config['upload_path'] = './upload/requirements/';
      $config['allowed_types'] = 'pdf|doc|docx|jpg|jpeg|png';
      $config['max_size'] = 2048;
      $config['encrypt_name'] = TRUE;
      $config['remove_spaces'] = TRUE;

      $this->load->library('upload', $config);

      if ($this->upload->do_upload('requirement_file')) {
        $uploadData = $this->upload->data();
        $filePath = 'upload/requirements/' . $uploadData['file_name'];

        // Build data array
        $data = [
          'StudentNumber'  => $studentNumber,
          'requirement_id' => $requirementId,
          'date_submitted' => date('Y-m-d'),
          'file_path'      => $filePath,
          'is_verified'    => 1,
          'verified_by'    => $this->session->userdata('username'),
          'verified_at'    => date('Y-m-d H:i:s'),
          // 'comment'        => 'Uploaded via portal and auto-verified'
          'comment' => 'Uploaded by ' . $this->session->userdata('username')

        ];

        // Check if already exists
        $existing = $this->db->get_where('student_requirements', [
          'StudentNumber' => $studentNumber,
          'requirement_id' => $requirementId
        ])->row();

        if ($existing) {
          $this->db->where('id', $existing->id);
          $this->db->update('student_requirements', $data);
        } else {
          $this->db->insert('student_requirements', $data);
        }

        $this->session->set_flashdata('success', 'Requirement uploaded successfully.');
      } else {
        $this->session->set_flashdata('danger', $this->upload->display_errors());
      }
    } else {
      $this->session->set_flashdata('danger', 'No file selected.');
    }

    redirect($this->agent->referrer());
    // 🔄 Use the working method for viewing student profile
    // redirect('Student/studentsprofile?id=' . $studentNumber);
  }

  function downloads()
  {
    $this->load->view('download_resources');
  }

  public function student_requirements()
  {
    $studentNumber = $this->session->userdata('username');

    if (!$studentNumber) {
      show_error('You must be logged in as a student to view this page.', 403);
    }

    $this->load->model('StudentModel');
    $data['student'] = $this->StudentModel->get_student_by_number($studentNumber);
    $data['requirements'] = $this->StudentModel->getStudentRequirements($studentNumber);

    $this->load->view('requirements_view', $data);
  }


  public function student_requirements_app()
  {
    $studentNumber = $this->session->userdata('username');

    if (!$studentNumber) {
      show_error('You must be logged in as a student to view this page.', 403);
    }

    $this->load->model('StudentModel');
    $data['student'] = $this->StudentModel->get_student_by_number_app($studentNumber);
    $data['requirements'] = $this->StudentModel->getStudentRequirements($studentNumber);

    $this->load->view('requirements_view', $data);
  }


  public function evaluation()
  {
    $data['students']      = $this->StudentModel->getAllStudents();           // existing
    $data['courses']       = $this->StudentModel->getCourseOptions();         // NEW
    $data['majors']        = [];                                              // filled via AJAX
    $data['effectivities'] = [];                                              // filled via AJAX
    $this->load->view('evaluation_form_select', $data);
  }

  // ---------------------------
  // AJAX: course/major by student
  // ---------------------------
  public function ajaxCourseMajorByStudent()
  {
    $studentNumber = $this->input->get('studentNumber', true);
    $s = $this->StudentModel->getStudentByNumberPH($studentNumber);
    if (!$s) {
      return $this->output->set_status_header(404)
        ->set_content_type('application/json')
        ->set_output(json_encode(['error' => 'Student not found']));
    }
    return $this->output->set_content_type('application/json')
      ->set_output(json_encode([
        'course' => (string)$s->Course,
        'major'  => (string)($s->Major ?? '')
      ]));
  }

  // ---------------------------
  // AJAX: majors by course
  // ---------------------------
  public function ajaxMajorsByCourse()
  {
    $course = $this->input->get('course', true);
    $rows   = $this->StudentModel->getMajorsByCourse($course);
    $majors = array_map(function ($r) {
      return $r->Major;
    }, $rows);
    return $this->output->set_content_type('application/json')
      ->set_output(json_encode($majors));
  }

  // ---------------------------
  // AJAX: effectivities by course/major
  // ---------------------------
  public function ajaxEffectivities()
  {
    $course = $this->input->get('course', true);
    $major  = $this->input->get('major', true);
    $rows   = $this->StudentModel->getEffectivityOptionsByCourseMajor($course, $major);
    $effs   = array_map(function ($r) {
      return $r->Effectivity;
    }, $rows);
    return $this->output->set_content_type('application/json')
      ->set_output(json_encode($effs));
  }



  public function showEvaluation()
  {
    $studentNumber = $this->input->get('studentNumber');
    $effectivity   = $this->input->get('effectivity');
    $selCourse     = $this->input->get('course');   // from select_PH form
    $selMajor      = $this->input->get('major');    // from select_PH form (can be empty)

    $student = $this->StudentModel->getStudentByNumberPH($studentNumber);
    if (!$student) {
      show_404();
    }

    // Use selected course/major if provided, else default to student's current
    $course = !empty($selCourse) ? $selCourse : $student->Course;
    // Normalize empty strings to NULL to trigger "no-major" branch in queries
    $major  = strlen((string)$selMajor) ? $selMajor : null;

    // Filter effectivities and subjects using the SELECTED course/major
    $effectivities = $this->StudentModel->getEffectivityOptionsByCourseMajor($course, $major);
    $subjects      = $this->StudentModel->getSubjectsWithGrades($course, $major, $student->StudentNumber, $effectivity);

    $data = [
      'student'              => $student,         // has StudentNumber, FirstName, LastName, Course, Major (current)
      'subjects'             => $subjects,
      'effectivities'        => $effectivities,
      'selectedEffectivity'  => $effectivity,
      'selectedCourse'       => $course,          // for header + hidden field persistence
      'selectedMajor'        => $major,           // "
    ];

    $this->load->view('evaluation_form_result', $data);
  }

  public function evaluationPH()
  {
    $username = $this->session->userdata('username');
    $idnumber = $this->session->userdata('IDNumber');

    $data['allCourses']   = $this->StudentModel->getAllCourses();
    $data['phCourses']    = $this->StudentModel->getCoursesByProgramHead($username, $idnumber);
    $data['defaultCourse'] = !empty($data['phCourses']) ? $data['phCourses'][0]->CourseDescription : '';

    // . filtered by Program Head
    $data['allStudents']  = $this->StudentModel->getAllStudentsBasic($username, $idnumber);

    $data['majors'] = $data['defaultCourse'] ? $this->StudentModel->getMajorsByCourse($data['defaultCourse']) : [];

    $data['effectivities'] = $this->StudentModel->getEffectivityOptions();
    $this->load->view('evaluation_form_select_ph', $data);
  }


  public function getMajorsByCourse()
  {
    $course = $this->input->post('course');
    $majors = $this->StudentModel->getMajorsByCoursePH($course);
    echo json_encode($majors);
  }


  public function getStudentsByCourseMajor()
  {
    $course = $this->input->post('course');
    $major  = $this->input->post('major') ?: null;
    $students = $this->StudentModel->getStudentsByCourseMajorPH($course, $major);
    echo json_encode($students);
  }

  
  public function manual_verify()
  {
    // Load the necessary model
    $this->load->model('StudentModel');

    // Get POST data
    $requirement_id = $this->input->post('requirement_id', TRUE); // Get the requirement id
    $comment = $this->input->post('comment', TRUE); // Get the comment
    $student_number = $this->input->post('StudentNumber', TRUE); // Get the selected student's number

    // Check if the required data is provided
    if ($requirement_id && $comment !== null && $student_number) {
      // Prepare insert data
      $data = [
        'StudentNumber'  => $student_number,                    // Use the selected student's StudentNumber
        'requirement_id' => $requirement_id,                     // The id of the requirement being verified
        'date_submitted' => date('Y-m-d H:i:s'),                 // Current timestamp for when it's submitted
        'is_verified'    => 1,                                   // Mark as verified
        'comment'        => $comment,                            // Save the comment
        'verified_by'    => $this->session->userdata('username'), // Store the username of who verified it
        'verified_at'    => date('Y-m-d H:i:s')                  // Current timestamp for when it's verified
      ];

      // Insert the data into the student_requirements table
      $inserted = $this->db->insert('student_requirements', $data);

      // Check if the insert was successful and set flash message accordingly
      if ($inserted) {
        $this->session->set_flashdata('success', 'Requirement successfully verified.');
      } else {
        $this->session->set_flashdata('error', 'Failed to insert verification data.');
      }
    } else {
      // Flash message if the required data is missing
      $this->session->set_flashdata('error', 'Missing required data.');
    }

    // Redirect back to the previous page (student profile or the previous page)
    redirect($_SERVER['HTTP_REFERER']);
  }



  public function submit_requirement()
  {
    $this->load->model('StudentModel');

    $studentNumber = $this->input->post('student_number');
    $requirementId = $this->input->post('requirement_id');
    $file = $_FILES['document'];

    $config['upload_path'] = './upload/requirements/';
    $config['allowed_types'] = 'pdf';
    // $config['allowed_types'] = 'pdf|jpg|png';
    $config['max_size'] = 2048;
    $this->load->library('upload', $config);

    if (!$this->upload->do_upload('document')) {
      $error = $this->upload->display_errors();
      echo "Upload error: " . $error;
    } else {
      $uploadData = $this->upload->data();

      $data = [
        'StudentNumber' => $studentNumber,
        'requirement_id' => $requirementId,
        'date_submitted' => date('Y-m-d'),
        'file_path' => 'upload/requirements/' . $uploadData['file_name'],
        'is_verified' => 0
      ];

      $this->StudentModel->submitRequirement($data);
      // redirect('Student/student_requirements/' . $studentNumber);
      redirect($this->agent->referrer());
    }
  }

  public function update_student_ages()
  {
    $students = $this->db->get('studeprofile')->result();
    $today = new DateTime('today');

    $profile_updates = [];
    $signup_updates = [];
    $updated_count = 0;

    foreach ($students as $student) {
      if (!empty($student->birthDate)) {
        $birthDate = new DateTime($student->birthDate);
        $age = $birthDate->diff($today)->y;

        if ((int)$student->age !== $age) {
          $profile_updates[] = [
            'StudentNumber' => $student->StudentNumber,
            'age' => $age
          ];

          $signup_updates[] = [
            'StudentNumber' => $student->StudentNumber,
            'age' => $age
          ];

          $updated_count++;
        }
      }
    }

    // . Update studeprofile in one batch
    if (!empty($profile_updates)) {
      $this->db->update_batch('studeprofile', $profile_updates, 'StudentNumber');
    }

    // . Check existing studentsignup records using chunks to avoid regex error
    if (!empty($signup_updates)) {
      $existing_ids = [];
      $student_numbers = array_column($signup_updates, 'StudentNumber');
      $chunks = array_chunk($student_numbers, 500); // break into 500-row chunks

      foreach ($chunks as $chunk) {
        $results = $this->db->select('StudentNumber')
          ->from('studentsignup')
          ->where_in('StudentNumber', $chunk)
          ->get()
          ->result_array();

        $existing_ids = array_merge($existing_ids, array_column($results, 'StudentNumber'));
      }

      // Filter signup_updates to only those that exist
      $signup_updates_filtered = array_filter($signup_updates, function ($item) use ($existing_ids) {
        return in_array($item['StudentNumber'], $existing_ids);
      });

      // . Batch update studentsignup
      if (!empty($signup_updates_filtered)) {
        $this->db->update_batch('studentsignup', $signup_updates_filtered, 'StudentNumber');
      }
    }

    // . Flash message and redirect
    $this->session->set_flashdata('success', "$updated_count student age(s) updated successfully.");
    $this->load->view('landing_page');
  }


  public function req_list()
  {
    $data['req'] = $this->StudentModel->req_list();
    $this->load->view('req_list', $data);
  }

  public function pending_uploads()
  {
    $data['pending'] = $this->StudentModel->getPendingRequirements();
    $this->load->view('req_pending_uploads_view', $data);
  }

  public function disapprove_upload()
  {
    $id = $this->input->post('id');
    $comment = $this->input->post('comment');

    $this->db->where('id', $id);
    $this->db->update('student_requirements', [
      'is_verified' => 2,
      'comment' => $comment,
      'verified_by' => $this->session->userdata('username'),
      'verified_at' => date('Y-m-d H:i:s')
    ]);

    $this->session->set_flashdata('success', 'Requirement disapproved with reason.');
    redirect('student/pending_uploads');
  }


  public function approved_uploads()
  {
    $this->load->model('StudentModel');
    $data['pending'] = $this->StudentModel->approved_uploads();
    $this->load->view('req_pending_uploads_view_approved', $data);
  }

  public function approve_upload($id)
  {

    $verifier = $this->session->userdata('username') ?? 'Registrar';

    $this->StudentModel->approveRequirement($id, $verifier);

    $this->session->set_flashdata('success', 'Document approved successfully.');
    redirect('student/pending_uploads');
  }

  public function view_list_not_enrolled()
  {
    $enrolledSY = $this->input->post('enrolled_during');
    $notEnrolledSY = $this->input->post('not_during');

    // Use the already-loaded model
    $data['students'] = $this->StudentModel->getPreviouslyButNotCurrentlyEnrolled($enrolledSY, $notEnrolledSY);

    // Load the view
    $this->load->view('view_list_not_enrolled', $data);
  }
  public function compute_general_averages()
  {
    $this->load->database();
    $sy = $this->session->userdata('sy');

    if (!$sy) {
      $this->session->set_flashdata('error', 'School Year not set in session.');
      redirect('dashboard'); // Change to your actual route
      return;
    }

    $students = $this->db
      ->select('StudentNumber, YearLevel')
      ->from('semesterstude')
      ->where('SY', $sy)
      ->where('Status', 'Enrolled')
      ->get()
      ->result();

    $updatedCount = 0;

    foreach ($students as $student) {
      $studentNumber = $student->StudentNumber;
      $yearLevel = $student->YearLevel;

      if (in_array($yearLevel, ['Grade 11', 'Grade 12'])) {
        $query = $this->db
          ->select('AVG((PGrade + MGrade) / 2) AS gen_avg')
          ->from('grades')
          ->where('StudentNumber', $studentNumber)
          ->get();
      } else {
        $query = $this->db
          ->select('AVG((PGrade + MGrade + PFinalGrade + FGrade) / 4) AS gen_avg')
          ->from('grades')
          ->where('StudentNumber', $studentNumber)
          ->get();
      }

      $result = $query->row();

      if ($result && $result->gen_avg !== null) {
        $generalAverage = round($result->gen_avg, 2);

        $this->db->where('StudentNumber', $studentNumber);
        $this->db->where('SY', $sy);
        $this->db->update('semesterstude', ['gen_average' => $generalAverage]);

        $updatedCount++;
      }
    }

    // Set flash message and redirect
    $this->session->set_flashdata('success', $updatedCount . ' general averages updated for SY: ' . $sy);
    redirect('Page/grades_updated');
  }











  public function enlistment_student()
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');
    $selectedStudent = $this->input->get('student'); // Get student number from URL

    $this->db->select('semesterstude.StudentNumber, semesterstude.Course, semesterstude.Major, semesterstude.YearLevel, semesterstude.Section, semesterstude.Semester, semesterstude.SY, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
    $this->db->from('semesterstude');
    $this->db->join('studeprofile', 'semesterstude.StudentNumber = studeprofile.StudentNumber');
    $this->db->where('semesterstude.Semester', $semester);
    $this->db->where('semesterstude.SY', $sy);
    $this->db->order_by('studeprofile.LastName', 'ASC');

    $data['students'] = $this->db->get()->result();
    $data['selectedStudent'] = $selectedStudent;

    $this->load->view('enlistment_view_student', $data);
  }



  public function add_subjects()
  {
    $studentNumber = $this->input->get('student');
    $this->load->model('StudentEnrollment');
    $student = $this->StudentEnrollment->getStudentDetails($studentNumber);

    if (!$student) show_404();

    $sy = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    // Available subjects for adding
    $data['subjects'] = $this->StudentEnrollment->getSubjectsByCourseMajor(
      $student->Course,
      $student->Major,
      $studentNumber,
      $sy,
      $sem
    );

    // Subjects already added for approval
    $data['pendingSubjects'] = $this->StudentEnrollment->getPendingDropAddSubjects(
      $studentNumber,
      $sy,
      $sem
    );

    $data['student'] = $student;

    $this->load->view('add_subjects_view', $data);
  }


  public function save_add_subject()
  {
    $studentNumber = $this->input->post('StudentNumber');
    $subjectCode   = $this->input->post('subjectCode');
    $description   = $this->input->post('Description');
    $lecUnit       = $this->input->post('LecUnit');
    $labUnit       = $this->input->post('LabUnit');
    $refID         = $this->input->post('refID'); // semsubjects.subjectid

    $sy      = $this->session->userdata('sy');
    $sem     = $this->session->userdata('semester');
    $progHead = $this->session->userdata('IDNumber'); // Assuming Program Head is logged in

    $data = array(
      'StudentNumber' => $studentNumber,
      'subjectCode'   => $subjectCode,
      'Description'   => $description,
      'LecUnit'       => $lecUnit,
      'LabUnit'       => $labUnit,
      'SY'            => $sy,
      'Sem'           => $sem,
      'adAction'      => 'Adding',
      'progHead'      => $progHead,
      'adDate'        => date('Y-m-d'),
      'refID'         => $refID
    );

    $this->db->insert('dropadd', $data);

    $this->session->set_flashdata('success', 'Subject successfully added for enlistment.');
    redirect('student/add_subjects?student=' . $studentNumber);
  }


  public function remove_added_subject()
  {
    $adID = $this->input->post('adID');
    $studentNumber = $this->input->post('StudentNumber');

    $this->db->where('adID', $adID);
    $this->db->delete('dropadd');

    $this->session->set_flashdata('success', 'Subject removed successfully.');
    redirect('student/add_subjects?student=' . $studentNumber);
  }


  public function drop_subjects()
  {
    $studentNumber = $this->input->get('student');
    $this->load->model('StudentEnrollment');
    $student = $this->StudentEnrollment->getStudentDetails($studentNumber);

    if (!$student) show_404();

    $sy = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    $data['student'] = $student;
    $data['registeredSubjects'] = $this->StudentEnrollment->getRegisteredSubjects(
      $studentNumber,
      $student->Course,
      $student->Major,
      $sy,
      $sem
    );

    // 👇 fetch dropped subjects from dropadd table
    $data['droppedSubjects'] = $this->StudentEnrollment->getDroppedSubjects(
      $studentNumber,
      $student->Course,
      $student->Major,
      $sy,
      $sem
    );

    $this->load->view('drop_subjects_view', $data);
  }


  public function drop_subject()
  {
    $studentNumber = $this->input->post('StudentNumber');
    $subjectCode   = $this->input->post('subjectCode');
    $description   = $this->input->post('Description');
    $lecUnit       = $this->input->post('LecUnit');
    $labUnit       = $this->input->post('LabUnit');
    $sy            = $this->input->post('SY');
    $sem           = $this->input->post('Sem');
    $refID         = $this->input->post('refID'); // registration.regnumber
    $progHead      = $this->session->userdata('IDNumber');

    $data = array(
      'StudentNumber' => $studentNumber,
      'subjectCode'   => $subjectCode,
      'Description'   => $description,
      'LecUnit'       => $lecUnit,
      'LabUnit'       => $labUnit,
      'SY'            => $sy,
      'Sem'           => $sem,
      'adAction'      => 'Dropping',
      'progHead'      => $progHead,
      'adDate'        => date('Y-m-d'),
      'refID'         => $refID
    );

    $this->db->insert('dropadd', $data);

    $this->session->set_flashdata('success', 'Subject successfully marked for dropping.');
    redirect('student/drop_subjects?student=' . $studentNumber);
  }



  public function print_add_drop_form($studentNumber)
  {
    $this->load->model('StudentEnrollment');
    $this->load->model('Login_model'); // make sure this is loaded

    $data['letterhead'] = $this->Login_model->getSchoolInformation();
    $data['school'] = !empty($data['letterhead']) ? $data['letterhead'][0] : null;
    $student = $this->StudentEnrollment->getStudentDetails1($studentNumber);
    if (!$student) show_404();

    $sy = $this->session->userdata('sy');
    $sem = $this->session->userdata('semester');

    $pendingSubjects = $this->StudentEnrollment->getPendingDropAddSubjects($studentNumber, $sy, $sem);
    $droppedSubjects = $this->StudentEnrollment->getDroppedSubjects($studentNumber, $student->Course, $student->Major, $sy, $sem);

    $programHeadID = $this->session->userdata('username');
    $programHead = $this->db
      ->select('fname, mname, lname')
      ->get_where('o_users', ['username' => $programHeadID])
      ->row();


    $this->load->view('add_drop_report', [
      'student' => $student,
      'sy' => $sy,
      'sem' => $sem,
      'pendingSubjects' => $pendingSubjects,
      'droppedSubjects' => $droppedSubjects,
      'programHead' => $programHead,
      'school' => $data['school'] // explicitly added
    ]);
  }





  public function requestSub()
  {
    $this->load->model('StudentEnrollment');
    $data['requests'] = $this->StudentEnrollment->getRequestedSubjects();
    $this->load->view('subject_requests_view', $data);
  }


  public function approveRequest()
  {
    $studentNumber = $this->input->post('StudentNumber');
    $subjectCode   = $this->input->post('subjectCode');
    $sy            = $this->input->post('SY');
    $sem           = $this->input->post('Sem');

    $this->load->model('StudentEnrollment');

    // Get subject info from semsubjects
    $subject = $this->db->get_where('semsubjects', [
      'SubjectCode' => $subjectCode,
      'SY' => $sy,
      'Semester' => $sem
    ])->row();

    if (!$subject) {
      $this->session->set_flashdata('error', 'Subject not found.');
      redirect('Student/requestSub');
    }

    // Get student data from semesterstude for YearLevel and Major
    $this->db->select('YearLevel, Major, Course');
    $this->db->from('semesterstude');
    $this->db->where([
      'StudentNumber' => $studentNumber,
      'SY' => $sy,
      'Semester' => $sem
    ]);
    $semStudent = $this->db->get()->row();

    if (!$semStudent) {
      $this->session->set_flashdata('error', 'Student semester record not found.');
      redirect('Student/requestSub');
    }

    // Calculate units
    $lec = floatval($subject->LecUnit);
    $lab = floatval($subject->LabUnit);
    $totalUnits = $lec + $lab;

    // Insert into registration
    $data = [
      'SubjectCode' => $subject->SubjectCode,
      'Description' => $subject->Description,
      'LecUnit' => $subject->LecUnit,
      'LabUnit' => $subject->LabUnit,
      'Section' => $subject->Section,
      'LabTime' => $subject->LabTime,
      'SchedTime' => $subject->SchedTime,
      'Room' => '',
      'Instructor' => $subject->IDNumber,
      'Sem' => $sem,
      'SY' => $sy,
      'StudentNumber' => $studentNumber,
      'Course' => $semStudent->Course,
      'YearLevel' => $semStudent->YearLevel,
      'Major' => $semStudent->Major,
      'settingsID' => 1,
      'schedType' => '',
      'totalUnits' => $totalUnits,
      'labFee' => 0,
      'enrolledBy' => $this->session->userdata('IDNumber'),
      'regDate' => date('Y-m-d'),
      'IDNumber' =>  $subject->IDNumber // Assuming this is the instructor's ID
    ];

    $this->db->insert('registration', $data);

    // Remove from dropadd
    $this->db->delete('dropadd', [
      'StudentNumber' => $studentNumber,
      'subjectCode' => $subjectCode,
      'SY' => $sy,
      'Sem' => $sem,
      'adAction' => 'Adding'
    ]);

    $this->session->set_flashdata('success', 'Subject approved and added to registration.');
    redirect('Student/requestSub');
  }

  
public function my_qr()
{
    // allow Student and Stude Applicant (adjust as needed)
    if ( ! in_array($this->session->userdata('level'), ['Student','Stude Applicant'], true)) {
        show_404(); // or redirect('auth/login');
    }

    $student_number = (string) $this->session->userdata('username');

    $this->load->model('Student_qr_model');
    $qr = $this->Student_qr_model->get_or_issue($student_number);

    $profile = $this->db->select('FirstName, MiddleName, LastName')
        ->where('StudentNumber', $student_number)
        ->limit(1)
        ->get('studeprofile')
        ->row();

    $student_name = '';
    $first_name = '';
    $last_name = '';
    if ($profile) {
        $first_name = trim((string)$profile->FirstName);
        $last_name  = trim((string)$profile->LastName);
        $student_name = trim($first_name . ' ' . $last_name);
    }
    if ($student_name === '') {
        $student_name = $student_number;
    }

    $data = [
        'student_number' => $student_number,
        'student_name'   => $student_name,
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'token'          => $qr->token ?? null,
        'status'         => $qr->status ?? null,
    ];

    // match the actual view path
    $this->load->view('student_my_qr', $data);
}

}

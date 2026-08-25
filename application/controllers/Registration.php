<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Registration extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('RegistrationModel');
        $this->load->model('SettingsModel'); // for reCAPTCHA keys + school name
        $this->load->model('StudentModel');  // for dropdowns
        $this->load->library('email');       // CI email (uses application/config/email.php)
        $this->load->helper(['url', 'security']);
        $this->load->database();
    }

    public function index()
    {
        // ----- Build data for the form -----
        $provinces        = $this->StudentModel->get_provinces();
        $default_province = !empty($provinces) ? $provinces[0]->Province : null;

        $data = [
            'course'     => $this->StudentModel->getCourse(),
            'major'      => $this->StudentModel->getCourseMajor(),
            'provinces'  => $provinces,
            'cities'     => $default_province ? $this->StudentModel->get_cities($default_province) : [],
            'brgy'       => [],
            'site_key'   => $this->SettingsModel->getRecaptchaSiteKey(),
        ];
        $source = $this->input->get('source', true) ?: $this->input->post('source', true);
        $isAdminFlow = (strtolower((string)$source) === 'admin');
        $registrationRedirect = $isAdminFlow ? 'Registration/index?source=admin' : 'Registration/index';
        // ----- Handle POST (registration submit) -----
        // Handle any POST submission (button name can be omitted when form submits via Enter key).
        if ($this->input->method(TRUE) === 'POST') {

            // 0) reCAPTCHA verify (robust: use cURL)
            $recaptchaResponse = (string)$this->input->post('g-recaptcha-response', true);
            $secretKey         = $this->SettingsModel->getRecaptchaSecretKey();

            if ($recaptchaResponse === '') {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Please complete the reCAPTCHA.</b></div>');
                redirect($registrationRedirect);
                return;
            }

            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'secret'   => $secretKey,
                    'response' => $recaptchaResponse,
                    'remoteip' => $this->input->ip_address(),
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $verifyResponse = curl_exec($ch);
            curl_close($ch);

            $json = @json_decode($verifyResponse, true);
            if (!is_array($json) || empty($json['success'])) {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>reCAPTCHA verification failed. Please try again.</b></div>');
                redirect($registrationRedirect);
                return;
            }

            // Year Level: normalize/validate (expects "1st/2nd/3rd/4th")
            $yearLevelInput      = trim((string)$this->input->post('yearLevel', true));
            $yearLevelNormalized = preg_replace('/\s*Year$/i', '', $yearLevelInput); // allow "1st Year"
            $validLevels         = ['1st', '2nd', '3rd', '4th'];
            if (!in_array($yearLevelNormalized, $validLevels, true)) {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Please select a valid Year Level.</b></div>');
                redirect($registrationRedirect);
                return;
            }

            // 1) Collect ONLY the fields that exist on the form
            $studentData = [
                'StudentNumber'     => $this->input->post('StudentNumber', true),
                'FirstName'         => strtoupper((string)$this->input->post('FirstName', true)),
                'MiddleName'        => strtoupper((string)$this->input->post('MiddleName', true)),
                'LastName'          => strtoupper((string)$this->input->post('LastName', true)),
                'nameExtn'          => strtoupper((string)$this->input->post('nameExtn', true)),
                'Sex'               => $this->input->post('Sex', true),
                'birthDate'         => $this->input->post('birthDate', true),
                'age'               => $this->input->post('age', true),
                'contactNo'         => $this->input->post('contactNo', true),
                'email'             => $this->input->post('email', true),
                'section' => $this->input->post('section', true),


                // defaults to satisfy NOT NULL columns
                'working'           => $this->input->post('working', true)  ?? 'No',
                'VaccStat'          => $this->input->post('VaccStat', true) ?? '',
                'nationality'       => $this->input->post('nationality', true) ?: 'Filipino',
                'yearLevel'         => $yearLevelNormalized,

                // Preferred courses/majors
                'Course1'           => $this->input->post('Course1', true),
                'Major1'            => $this->input->post('Major1', true),

                // system-controlled
                'EnrollmentDate'   => date('Y-m-d'),
            ];
            // studentsignup has many legacy NOT NULL columns that are not in the simplified public form.
            // Provide safe defaults so account creation can complete without DB constraint errors.
            $studentData = array_merge([
                'BirthPlace'           => '',
                'CivilStatus'          => 'Single',
                'Religion'             => '',
                'province'             => '',
                'city'                 => '',
                'brgy'                 => '',
                'sitio'                => '',
                'Course2'              => '',
                'Course3'              => '',
                'Major2'               => '',
                'Major3'               => '',
                'Status'               => 'Pending',
                'graduationDate'       => '',
                'guardian'             => '',
                'guardianRelationship' => '',
                'guardianContact'      => '',
                'guardianAddress'      => '',
                'father'               => '',
                'fOccupation'          => '',
                'fatherAddress'        => '',
                'fatherContact'        => '',
                'mother'               => '',
                'mOccupation'          => '',
                'motherAddress'        => '',
                'motherContact'        => '',
            ], $studentData);
            // Normalize and validate StudentNumber (username)
            $studentData['StudentNumber'] = strtoupper(trim((string)$studentData['StudentNumber']));
            // Ensure age is always a numeric value even if JS didn't populate the hidden field.
            $age = (int)$studentData['age'];
            if ($age <= 0) {
                $dob = DateTime::createFromFormat('Y-m-d', (string)$studentData['birthDate']);
                if ($dob instanceof DateTime) {
                    $today = new DateTime('today');
                    $age   = (int)$dob->diff($today)->y;
                }
            }
            $studentData['age'] = max(0, $age);

            if ($studentData['StudentNumber'] === '') {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Please enter a Student ID/Number. This will be used as your username.</b></div>');
                redirect($registrationRedirect);
                return;
            }
            if (!preg_match('/^[A-Z0-9\-]+$/', $studentData['StudentNumber'])) {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Student ID may only contain letters, numbers, and hyphen.</b></div>');
                redirect($registrationRedirect);
                return;
            }


            // identifiers
            $studentNumber = $studentData['StudentNumber'] ?: (string) random_int(1000000000, 1999999999);
            $email         = (string)$studentData['email'];
            $firstName     = (string)$studentData['FirstName'];
            $middleName    = (string)$studentData['MiddleName'];
            $lastName      = (string)$studentData['LastName'];
            $fullName      = trim($firstName . ' ' . $middleName . ' ' . $lastName);
            $passwordRaw   = (string)$this->input->post('password');
            $confirmPass   = (string)$this->input->post('confirm_password');

            $passwordRaw = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $passwordRaw);
            $confirmPass = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $confirmPass);
            $passwordRaw = preg_replace('/^\s+|\s+$/u', '', $passwordRaw);
            $confirmPass = preg_replace('/^\s+|\s+$/u', '', $confirmPass);

            if ($passwordRaw === '') {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Please enter a password.</b></div>');
                redirect($registrationRedirect);
                return;
            }
            if (strlen($passwordRaw) < 8) {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Password must be at least 8 characters.</b></div>');
                redirect($registrationRedirect);
                return;
            }
            if ($passwordRaw !== $confirmPass) {
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Passwords do not match.</b></div>');
                redirect($registrationRedirect);
                return;
            }

            // 2) Duplicate checks across both login/accounts and signup staging tables.
            $studentIdExists = (
                $this->db->where('username', $studentNumber)->count_all_results('o_users') > 0
            ) || (
                $this->db->where('StudentNumber', $studentNumber)->count_all_results('studentsignup') > 0
            );
            $emailExists = (
                $this->db->where('email', $email)->count_all_results('o_users') > 0
            ) || (
                $this->db->where('email', $email)->count_all_results('studentsignup') > 0
            );

            if ($studentIdExists || $emailExists) {
                $parts = [];
                if ($studentIdExists) $parts[] = 'Student ID already exists.';
                if ($emailExists) $parts[] = 'Email already exists.';
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>' . implode(' ', $parts) . '</b></div>');
                redirect($registrationRedirect);
                return;
            }

            // 3) Insert into studentsignup + o_users (legacy) in a transaction
            $this->db->trans_start();

            // Ensure StudentNumber in payload is set
            $studentData['StudentNumber'] = $studentNumber;
            $this->db->insert('studentsignup', $studentData);

            // Login flow expects SHA-1 hash in o_users.password.
            $passwordHash = sha1($passwordRaw);

            $this->db->insert('o_users', [
                'username'   => $studentNumber,
                'IDNumber'   => $studentNumber,
                'fName'      => $firstName,
                'mName'      => $middleName,
                'lName'      => $lastName,
                'name'       => $fullName,
                'password'   => $passwordHash,
                'position'   => 'Student',
                'email'      => $email,
                'acctStat'   => 'active',
                'dateCreated' => date('Y-m-d'),
            ]);

            $this->db->trans_complete();
            if (!$this->db->trans_status()) {
                $dbError = $this->db->error();
                log_message('error', 'Registration transaction failed: ' . json_encode($dbError));
                $errorDetail = '';
                if (!empty($dbError['message'])) {
                    $errorDetail = '<br><small>' . htmlspecialchars((string)$dbError['message'], ENT_QUOTES, 'UTF-8') . '</small>';
                }
                $this->flashRegistrationError('<div class="alert alert-danger text-center"><b>Unexpected error. Please try again.</b>' . $errorDetail . '</div>');
                redirect($registrationRedirect);
                return;
            }

            // 3.5) Create/Update Profiling row in `semesterstude`
            $SY       = $this->session->userdata('sy');
            $Semester = $this->session->userdata('semester');

            if (!$SY || !$Semester) {
                $settingsRow = method_exists($this->SettingsModel, 'getSettingsRow')
                    ? $this->SettingsModel->getSettingsRow()
                    : $this->db->limit(1)->get('o_srms_settings')->row();
                if ($settingsRow) {
                    $SY       = $SY       ?: ($settingsRow->SY       ?? null);
                    $Semester = $Semester ?: ($settingsRow->Semester ?? null);
                }
            }
            $SY       = $SY       ?: date('Y') . '-' . (date('Y') + 1);
            $Semester = $Semester ?: 'First Semester';

            $course1 = (string)$this->input->post('Course1', true);
            $major1  = (string)$this->input->post('Major1', true);

            $existingSem = $this->db->get_where('semesterstude', [
                'StudentNumber' => $studentNumber,
                'SY'            => $SY,
                'Semester'      => $Semester
            ])->row();

            $profiling = [
                'StudentNumber'   => $studentNumber,
                'Course'          => $course1 ?: '',
                'YearLevel'       => $yearLevelNormalized,
                'Status'          => 'Enrolled',
                'Semester'        => $Semester,
                'SY'              => $SY,
                'Term'            => null,
                'Section' => $this->input->post('section', true),

                'StudeStatus'     => 'New',
                'Scholarship'     => '',
                'DurationFrom'    => '',
                'DurationTo'      => '',
                'AssessmentDate'  => '',
                'AssessmentResult' => '',
                'PayingStatus'    => 'Paying',
                'GrantAmount'     => 0,
                'YearLevelStat'   => 'Regular',
                'Major'           => $major1,
                'settingsID'      => 1,
                'enroledDate'     => date('Y-m-d'),
                'crossEnrollee'   => '',
                'classSession'    => '',
                'prevGPA'         => '',
                'testType'        => '',
                'testDate'        => '',
                'testResult'      => '',
                'caapPromoted'    => ''
            ];

            if ($existingSem) {
                $this->db->where('semstudentid', $existingSem->semstudentid)
                    ->update('semesterstude', [
                        'Course'    => $profiling['Course'],
                        'Major'     => $profiling['Major'],
                        'YearLevel' => $profiling['YearLevel'],
                        'Status'    => $profiling['Status'],
                        'Section'   => $profiling['Section'],
                    ]);
            } else {
                $this->db->insert('semesterstude', $profiling);
            }

            // (non-fatal) keep profiles.yearLevel in sync if the row exists
            $this->db->where('studentNumber', $studentNumber)
                ->update('profiles', ['yearLevel' => $yearLevelNormalized]);

            // 4) Send email with login details
            $schoolName = $this->SettingsModel->getSchoolName();

            $this->email->set_mailtype('html');
            $this->email->from('no-reply@srmsportal.com', 'Attendance MS');
            $this->email->to($email);
            $this->email->subject('Attendance Monitoring System');

            $htmlMessage = '
            <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                <h2 style="color: #2b6cb0;">Welcome to Faculty of Business and Management <br>
                Student Organization Attendance Portal</h2>
                <p>Dear <strong>' . htmlspecialchars($firstName) . '</strong>,</p>
                <p>Thank you for signing up! Your Attendance Portal account has been created successfully.</p>
                <table style="width: 100%; max-width: 420px; margin: 20px 0; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;"><strong>Username:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($studentNumber) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;"><strong>Password:</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($passwordRaw) . '</td>
                    </tr>
                </table>
                <p>You may now log in to the system using the credentials above.</p>
                <p style="margin-top: 30px;">Best regards,<br><strong>' . htmlspecialchars($schoolName) . '</strong></p>
                <hr style="margin-top: 40px;">
                <p style="font-size: 12px; color: #888;">This is an automated message. Please do not reply to this email.</p>
            </div>';
            $this->email->message($htmlMessage);

            $sent = $this->email->send();
            if (!$sent) {
                log_message('error', 'EMAIL SEND FAILED: ' . $this->email->print_debugger(['headers', 'subject', 'body']));

                if ($isAdminFlow) {
                    // The account WAS created — only the email failed. Go to the
                    // list either way: staying on the form reads as "it didn't
                    // work" and invites a duplicate submission.
                    $this->session->set_flashdata(
                        'warning',
                        'Account created for ' . $studentNumber . ', but the credentials email could not be sent. '
                            . 'Use Reset Password on the list to send it again.'
                    );
                    return redirect('Page/profileList');
                } else {
                    // Public signup → go to login with an INFO SweetAlert
                    $this->session->set_flashdata(
                        'info_message',
                        'Registration saved, but email could not be sent. You can log in using your Student ID and password.'
                    );
                    return redirect('login');
                }
            }
            if ($isAdminFlow) {
                // ✅ Admin created the account → stay in admin area with GREEN SweetAlert
                $this->session->set_flashdata('success', 'Account created. Login credentials were emailed to the user.');
                return redirect('Page/profileList'); // or your admin list page
            } else {
                // 🌐 Public self-signup → go to login with an INFO SweetAlert
                $this->session->set_flashdata(
                    'info_message',
                    'Registration successful. Check your email for login credentials.'
                );
                return redirect('login');
            }
        }

        // ----- Render the form view -----
        $this->load->view('registration_form', $data);
    }


    // ====== AJAX helpers (unchanged) ====== //

    public function getMajorsByCourse()
    {
        $course = $this->input->post('course', true);
        $this->db->select('Major');
        $this->db->where('CourseDescription', $course);
        $this->db->distinct();
        $this->db->order_by('Major', 'ASC');
        $query = $this->db->get('course_table');

        $options = '<option value="">Select Major</option>';
        foreach ($query->result() as $row) {
            $options .= '<option value="' . html_escape($row->Major) . '">' . html_escape($row->Major) . '</option>';
        }
        echo $options;
    }

    public function getCitiesByProvince()
    {
        $province = $this->input->post('province', true);
        $this->db->select('City');
        $this->db->where('Province', $province);
        $this->db->distinct();
        $this->db->order_by('City', 'ASC');
        $query = $this->db->get('settings_address');

        $options = '<option value="">Select City/Municipality</option>';
        foreach ($query->result() as $row) {
            $options .= '<option value="' . html_escape($row->City) . '">' . html_escape($row->City) . '</option>';
        }
        echo $options;
    }

    public function getBarangaysByCity()
    {
        $city = $this->input->post('city', true);
        $this->db->select('Brgy');
        $this->db->where('City', $city);
        $this->db->distinct();
        $this->db->order_by('Brgy', 'ASC');
        $query = $this->db->get('settings_address');

        $options = '<option value="">Select Barangay</option>';
        foreach ($query->result() as $row) {
            $options .= '<option value="' . html_escape($row->Brgy) . '">' . html_escape($row->Brgy) . '</option>';
        }
        echo $options;
    }

    public function checkAvailability()
    {
        $field = strtolower(trim((string)$this->input->post('field', true)));
        $value = trim((string)$this->input->post('value', true));

        $response = [
            'ok'      => true,
            'field'   => $field,
            'exists'  => false,
            'message' => ''
        ];

        if ($field === 'studentnumber' || $field === 'student_id' || $field === 'student') {
            $studentNumber = strtoupper($value);
            if ($studentNumber !== '') {
                $exists = (
                    $this->db->where('username', $studentNumber)->count_all_results('o_users') > 0
                ) || (
                    $this->db->where('StudentNumber', $studentNumber)->count_all_results('studentsignup') > 0
                );
                $response['exists'] = $exists;
                $response['message'] = $exists ? 'Student ID already exists.' : 'Student ID is available.';
            }
        } elseif ($field === 'email') {
            $email = trim($value);
            if ($email !== '') {
                $exists = (
                    $this->db->where('email', $email)->count_all_results('o_users') > 0
                ) || (
                    $this->db->where('email', $email)->count_all_results('studentsignup') > 0
                );
                $response['exists'] = $exists;
                $response['message'] = $exists ? 'Email already exists.' : 'Email is available.';
            }
        } else {
            $response['ok'] = false;
            $response['message'] = 'Unsupported field.';
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    public function create()
    {
        // pull from session as you already do elsewhere
        $sy  = $this->session->userdata('sy');
        $sem = $this->session->userdata('semester');

        // year level options (adjust if you support more)
        $data['yearLevels'] = ['1st', '2nd', '3rd', '4th'];

        // if you already pass $courses / $majors to the view, keep using that.
        // $data['courses'] = ...;
        // $data['majors']  = ...;

        // initial sections (blank until Course+YearLevel picked)
        $data['sections'] = [];

        $this->load->view('registration_form', $data);
    }

    // AJAX endpoint: return sections for a given course + year level
    public function sections()
    {
        $course    = $this->input->post('course');
        $yearLevel = $this->input->post('yearLevel');

        $sy  = $this->session->userdata('sy');
        $sem = $this->session->userdata('semester');

        $rows = $this->RegistrationModel->getSectionsByCourseYear($course, $yearLevel, $sy, $sem);
        $sections = array_map(function ($r) {
            return $r['Section'];
        }, $rows);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'sections' => $sections]));
    }

    // Handle form submit (save with YearLevel & Section)
    public function store()
    {
        $sy  = $this->session->userdata('sy');
        $sem = $this->session->userdata('semester');

        $payload = [
            'StudentNumber' => $this->input->post('StudentNumber'),
            'Course'        => $this->input->post('Course'),
            'Major'         => $this->input->post('Major'),
            'YearLevel'     => $this->input->post('YearLevel'),  // NEW
            'Section'       => $this->input->post('Section'),    // NEW

            'SY'            => $sy,
            'Sem'           => $sem,

            // the rest of the required fields in your schema (safe defaults)
            'SubjectCode'   => '',  // or set later when adding subjects
            'Description'   => '',
            'LecUnit'       => '0',
            'LabUnit'       => '0',
            'LabTime'       => NULL,
            'SchedTime'     => NULL,
            'Room'          => NULL,
            'Instructor'    => NULL,
            'settingsID'    => 1,
            'schedType'     => '',
            'totalUnits'    => 0,
            'labFee'        => 0,
            'enrolledBy'    => $this->session->userdata('username') ?? 'reg',
            'regDate'       => date('Y-m-d'),
            'IDNumber'      => $this->session->userdata('idnumber') ?? '',
        ];

        $id = $this->RegistrationModel->saveRegistration($payload);

        // redirect back with flash message
        $this->session->set_flashdata('ok', 'Student registered with Year Level and Section.');
        redirect('Registration/create');
    }
    public function getSectionsByCourseYear()
    {
        // Accept either: course (CourseDescription string) OR courseid (numeric)
        $courseid     = trim((string)$this->input->post('courseid', true));
        $courseByName = trim((string)$this->input->post('course', true)); // CourseDescription
        $yearLevel    = trim((string)$this->input->post('yearLevel', true));

        // If "courseid" was sent but it's not numeric (because your select holds the description), ignore it
        if ($courseid !== '' && !ctype_digit($courseid)) {
            $courseid = '';
        }

        // Resolve numeric courseid from CourseDescription when needed
        if ($courseid === '' && $courseByName !== '') {
            $row = $this->db->select('courseid')
                ->from('course_table')
                ->where('CourseDescription', $courseByName)
                ->limit(1)->get()->row();
            if ($row) $courseid = (string)$row->courseid;
        }

        $sections = [];

        // If course_sections table exists and we have both filters, fetch real data
        if ($this->db->table_exists('course_sections') && $courseid !== '' && $yearLevel !== '') {
            $q = $this->db->select('section')
                ->from('course_sections')
                ->where('courseid', (int)$courseid)
                ->where('year_level', $yearLevel)
                ->where('is_active', 1)
                ->order_by('section', 'ASC')
                ->get();
            foreach ($q->result() as $r) {
                $sections[] = $r->section;
            }
        }

        // Fallback so the UI always works even if course_sections has no row yet
        if (empty($sections)) {
            $sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        }

        // Return <option> HTML (your view expects this)
        $options = '<option value="">Select Section</option>';
        foreach ($sections as $sec) {
            $secEsc = htmlspecialchars($sec, ENT_QUOTES, 'UTF-8');
            $options .= "<option value=\"{$secEsc}\">{$secEsc}</option>";
        }

        $this->output->set_content_type('text/html')->set_output($options);
    }

    private function flashRegistrationError($messageHtml)
    {
        $oldInput = $this->input->post(NULL, true);
        if (!is_array($oldInput)) {
            $oldInput = [];
        }
        unset($oldInput['g-recaptcha-response'], $oldInput['password'], $oldInput['confirm_password']);

        $this->session->set_flashdata('old_input', $oldInput);
        $this->session->set_flashdata('msg', $messageHtml);
    }
}

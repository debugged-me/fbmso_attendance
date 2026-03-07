<?php
class Login extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Login_model');
        $this->load->model('SettingsModel');
        $this->load->model('StudentModel');
        $this->load->model('AuditLogModel');
    }

    function index()
    {
        $settings = $this->Login_model->loginImage(); // returns an array of result objects
        $result['data'] = $settings;

        // Assuming there's at least one row returned
        if (!empty($settings)) {
            $result['active_sem'] = $settings[0]->active_sem;
            $result['active_sy'] = $settings[0]->active_sy;
            $result['allow_signup'] = $settings[0]->allow_signup; // <- Add this line
        } else {
            $result['active_sem'] = null;
            $result['active_sy'] = null;
            $result['allow_signup'] = 'No'; // default to No
        }

        $this->load->view('home_page', $result);
    }


    function faq()
    {
        $result['data'] = $this->Login_model->loginImage();
        //$this->output->cache(60);
        $this->load->view('web-faq', $result);
    }

    function login()
    {
        $result['data'] = $this->Login_model->loginImage();
        $result['allow_signup'] = 'Yes';
        $this->load->view('home_page', $result);
    }



    function registration()
    {
        $this->load->helper('url');
        redirect('Registration/index');
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


    function reservation()
    {
        $this->load->view('reservation_form');

        if ($this->input->post('reserve')) {
            $appDate = date("Y-m-d");
            $firstName = strtoupper($this->input->post('firstName'));
            $middleName = strtoupper($this->input->post('middleName'));
            $lastName = strtoupper($this->input->post('lastName'));
            $nameExtn = strtoupper($this->input->post('nameExtn'));
            $sex = $this->input->post('sex');
            $bDate = $this->input->post('bDate');
            $age = $this->input->post('age');
            $civilStatus = $this->input->post('civilStatus');
            $empStatus = $this->input->post('empStatus');
            $ad_street = $this->input->post('ad_street');
            $ad_barangay = $this->input->post('ad_barangay');
            $ad_city = $this->input->post('ad_city');
            $ad_province = $this->input->post('ad_province');
            $email = $this->input->post('email');
            $contactNos = $this->input->post('contactNos');
            $course = $this->input->post('course');
            $que = $this->db->query("insert into reservation values(0,'$appDate','$firstName','$middleName','$lastName','$nameExtn','$sex','$bDate','$age','$civilStatus','$empStatus','$ad_street','$ad_barangay','$ad_city','$ad_province','$email','$contactNos','$course','Pending')");
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-center"><b>Reservation details have been processed successfully.  You will be notified via text or phone call for the status of your reservation.  Thank you.</b></div>');
            redirect('Login/reservation');
        }
    }


    function auth()
    {
        $username     = (string)$this->input->post('username', TRUE);

        // Normalize copied values (NBSP/zero-width/line-breaks) and trim edges.
        $username = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $username);
        $username = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $username);
        $username = trim(preg_replace('/\s+/u', ' ', $username));

        // 🔧 Do NOT XSS-filter the password (keeps characters intact)
        $raw_password = (string)$this->input->post('password');   // <-- removed TRUE
        $raw_password = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $raw_password);
        $raw_password = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $raw_password);
        // Trim only leading/trailing whitespace so accidental copy spaces won't break login.
        $raw_password = preg_replace('/^\s+|\s+$/u', '', $raw_password);
        $password     = sha1($raw_password);              // <-- hash the raw input

        $sy       = $this->input->post('sy', TRUE);
        $semester = $this->input->post('semester', TRUE);

        // NEW: capture next from POST first (form), then GET
        $next = $this->input->post('next', TRUE) ?: $this->input->get('next', TRUE);

        $validate = $this->Login_model->validate($username, $password);

        if ($validate->num_rows() > 0) {
            $data     = $validate->row_array();
            $username = $data['username'];
            $fname    = $data['fName'];
            $mname    = $data['mName'];
            $lname    = $data['lName'];
            $avatar   = $data['avatar'];
            $email    = $data['email'];
            $level    = $data['position'];
            $IDNumber = $data['IDNumber'];
            $position = $data['position'];
            $acctStat = $data['acctStat'];

            // 🔧 Be tolerant to case (active/Active/ACTIVE)
            if (strtolower((string)$acctStat) === 'active') {
                $this->Login_model->log_login_attempt($username, $raw_password, 'success');

                $user_data = array(
                    'username'  => $username,
                    'fname'     => $fname,
                    'mname'     => $mname,
                    'lname'     => $lname,
                    'avatar'    => $avatar,
                    'email'     => $email,
                    'level'     => $level,          // <-- Attendance::checkin reads this
                    'IDNumber'  => $IDNumber,
                    'position'  => $position,
                    'sy'        => $sy,
                    'semester'  => $semester,
                    'logged_in' => TRUE
                );
                $this->session->set_userdata($user_data);
                // AUDIT: successful login
                $this->AuditLogModel->write(
                    'login',
                    'Login',
                    null,
                    null,
                    null,
                    null,
                    1,
                    'User logged in successfully',
                    ['posted_sy' => $sy, 'posted_semester' => $semester]
                );

                if ($next) {
                    $host  = parse_url($next, PHP_URL_HOST);
                    $path  = parse_url($next, PHP_URL_PATH) ?: '';
                    $query = parse_url($next, PHP_URL_QUERY);
                    $rel   = ltrim($path . ($query ? ('?' . $query) : ''), '/');

                    // Compute the *current* origin (proxy/CDN aware)
                    $xfProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
                    $xfHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? null;
                    $scheme  = $xfProto ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
                    $hostNow = $xfHost  ?: ($_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST));
                    $origin  = $scheme . '://' . $hostNow;

                    // Relative NEXT → make an absolute URL on the current origin
                    if (!$host && $rel) {
                        redirect(rtrim($origin, '/') . '/' . $rel);
                        return;
                    }

                    // Absolute + same host → allow as-is
                    if ($host && strcasecmp($host, $hostNow) === 0) {
                        redirect($next);
                        return;
                    }

                    // Absolute but different host → sanitize to current origin + relative path
                    if ($rel) {
                        redirect(rtrim($origin, '/') . '/' . $rel);
                        return;
                    }
                }



                // Fallback: your existing role-based redirects
                switch ($level) {
                    case 'Admin':
                        redirect('page/admin');
                        break;
                    case 'School Admin':
                        redirect('page/school_admin');
                        break;
                    case 'Registrar':
                        redirect('page/registrar');
                        break;
                    case 'Head Registrar':
                        redirect('page/registrar');
                        break;
                    case 'Super Admin':
                        redirect('page/superAdmin');
                        break;
                    case 'Property Custodian':
                        redirect('page/p_custodian');
                        break;
                    case 'HR Admin':
                        redirect('page/hr');
                        break;
                    case 'Academic Officer':
                        redirect('page/a_officer');
                        break;
                    case 'Student':
                        redirect('page/student');
                        break;
                    case 'Stude Applicant':
                        redirect('page/student');
                        break;   // <— changed
                    case 'Accounting':
                        redirect('page/accounting');
                        break;
                    case 'Instructor':
                        redirect('page/Instructor');
                        break;
                    case 'Encoder':
                        redirect('page/encoder');
                        break;
                    case 'Human Resource':
                        redirect('page/hr');
                        break;
                    case 'Guidance':
                        redirect('page/guidance');
                        break;
                    case 'School Nurse':
                        redirect('page/medical');
                        break;
                    case 'IT':
                        redirect('page/IT');
                        break;
                    case 'Librarian':
                        redirect('page/library');
                        break;
                    case 'Principal':
                        redirect('page/s_principal');
                        break;
                    default:
                        $this->session->set_flashdata('auth_error', 'Unauthorized access.');
                        redirect('login');
                }
                return;
            } else {
                // Inactive account
                $this->Login_model->log_login_attempt($username, $raw_password, 'failed');
                // AUDIT: login failed (inactive account)
                $this->AuditLogModel->write(
                    'login',
                    'Login',
                    null,
                    null,
                    null,
                    ['reason' => 'inactive account'],
                    0,
                    'Login failed',
                    ['attempted_username' => $username]
                );
                $this->session->set_flashdata('auth_error', 'Your account is not active. Please contact support.');
                redirect('login' . ($next ? ('?next=' . urlencode($next)) : ''));


                return;
            }
        } else {
            // Invalid credentials
            $this->Login_model->log_login_attempt($username, $raw_password, 'failed');
            // AUDIT: login failed (invalid credentials)
            $this->AuditLogModel->write(
                'login',
                'Login',
                null,
                null,
                null,
                ['reason' => 'invalid credentials'],
                0,
                'Login failed',
                ['attempted_username' => $username]
            );
            $this->session->set_flashdata('auth_error', 'The username or password is incorrect!');
            redirect('login' . ($next ? ('?next=' . urlencode($next)) : ''));


            return;
        }
    }

    public function deleteUser($user)
    {
        // Attempt to delete the user
        $deleteSuccess = $this->Login_model->deleteUser($user);

        if ($deleteSuccess) {
            // AUDIT: user delete (success)
            $this->AuditLogModel->write(
                'delete',
                'User Accounts',
                'users',            // adjust to your actual users table if different
                $user,              // target username (record_pk)
                null,
                null,
                1,
                'Deleted user account',
                ['target_username' => $user]
            );

            $this->session->set_flashdata('success', '<div class="alert alert-success">User account deleted successfully.</div>');
        } else {
            // AUDIT: user delete (failed)
            $this->AuditLogModel->write(
                'delete',
                'User Accounts',
                'users',
                $user,
                null,
                null,
                0,
                'Failed to delete user account',
                ['target_username' => $user]
            );

            $this->session->set_flashdata('error', '<div class="alert alert-danger">Error deleting enrollment. Please try again.</div>');
        }

        redirect(base_url('Page/userAccounts'));
    }

    function logout()
    {
        // AUDIT: logout
        $this->AuditLogModel->write(
            'logout',
            'Login',
            null,
            null,
            null,
            null,
            1,
            'User logged out'
        );

        $this->session->sess_destroy();
        redirect('login');
    }

    public function forgot_pass()
    {
        $email = $this->input->post('email');
        $findemail = $this->Login_model->forgotPassword($email);
        if ($findemail) {
            $this->Login_model->sendpassword($findemail);
        } else {
            $this->session->set_flashdata('msg', ' Email not found!');
            redirect(base_url() . 'login', 'refresh');
        }
    }
}

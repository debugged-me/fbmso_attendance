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
        $this->load->library('securityaudit');
        $this->load->library('loginthrottle');
        $this->load->library('sessionregistry');
        $this->load->library('devicetokens');
        $this->load->library('riskengine');
    }

    function index()
    {
        // Prevent browser caching so flashdata messages always show after redirect
        $this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header('Expires: 0');

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
            $que = $this->db->query(
                "INSERT INTO reservation VALUES (0,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'Pending')",
                array($appDate,$firstName,$middleName,$lastName,$nameExtn,$sex,$bDate,$age,$civilStatus,$empStatus,$ad_street,$ad_barangay,$ad_city,$ad_province,$email,$contactNos,$course)
            );
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

        $sy       = $this->input->post('sy', TRUE);
        $semester = $this->input->post('semester', TRUE);

        // NEW: capture next from POST first (form), then GET
        $next = $this->input->post('next', TRUE) ?: $this->input->get('next', TRUE);

        // Throttle BEFORE verifying anything. bcrypt is deliberately slow, so
        // letting a blocked attempt reach it would turn the login form into a
        // CPU amplifier.
        $blocked = $this->loginthrottle->check($username);
        if ($blocked) {
            $this->Login_model->log_login_attempt($username, $raw_password, 'failed');
            $this->securityaudit->event('RATE_LIMIT_TRIGGERED', [
                'module'      => 'Login',
                'status'      => 'blocked',
                'target'      => $username,
                'description' => 'Sign-in refused: too many recent failures',
                'extra'       => ['scope' => $blocked['scope'], 'retry_after' => $blocked['retry_after']],
            ]);
            $this->session->set_flashdata('auth_error', $this->loginthrottle->retryMessage($blocked['retry_after']));
            redirect('login' . ($next ? ('?next=' . urlencode($next)) : ''));
            return;
        }

        // validate() takes the RAW password; it verifies bcrypt (and legacy
        // sha1) in PHP and upgrades old hashes on success.
        $validate = $this->Login_model->validate($username, $raw_password);

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

                $this->loginthrottle->succeed($username);
                $this->loginthrottle->prune();

                // New session ID on privilege change, so a session ID captured
                // before authentication cannot be replayed afterwards.
                $this->session->sess_regenerate(TRUE);

                // After regeneration: the reference must match the session id
                // the user will actually carry, not the pre-login one.
                $this->sessionregistry->open($username);
                $this->sessionregistry->prune();

                // Device recognition. Records the visit and tells us whether
                // this browser has been here before on this account.
                $device = $this->devicetokens->recognise($username);

                // Score the sign-in from everything we now know about it.
                $risk = $this->riskengine->assess($username, $device, (string)$level);

                $this->securityaudit->event(
                    $device['new_device'] ? 'LOGIN_NEW_DEVICE' : 'LOGIN_RECOGNIZED_DEVICE',
                    [
                        'module' => 'Login',
                        'status' => $device['revoked'] ? 'revoked-device' : 'success',
                        'target' => $username,
                        'description' => $device['new_device']
                            ? 'Signed in from a device not seen on this account before'
                            : 'Signed in from a recognised device',
                        'risk_score'  => $risk['score'],
                        'risk_level'  => $risk['level'],
                        'risk_reason' => implode('; ', $risk['reasons']),
                        'extra' => [
                            'trusted'        => $device['trusted'],
                            'revoked'        => $device['revoked'],
                            'login_count'    => $device['device']['login_count'] ?? 1,
                            // Same browser seen on other accounts. Several in a
                            // short window is the credential-spraying shape.
                            'other_accounts' => $device['other_accounts'],
                        ],
                    ]
                );

                // Tell the account holder. They are the only person who knows
                // whether a sign-in was theirs, and on 2026-08-28 this email
                // would have arrived while the attacker was still on the
                // profile page.
                if (!empty($risk['actions']['notify_user'])) {
                    $this->notify_risky_login($username, $email, $risk, $device);
                }

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

                // Force password change if the account is still on legacy
                // SHA1 or was flagged by a password reset. The user lands on
                // the change-password page and cannot navigate away until
                // they set a new password.
                $forceChange = (int)($data['force_change_password'] ?? 0);
                if ($forceChange === 1) {
                    $user_data['force_change_password'] = 1;
                }
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
                $this->securityaudit->event('LOGIN_SUCCESS', [
                    'module'      => 'Login',
                    'status'      => 'success',
                    'target'      => $username,
                    'description' => 'Password accepted',
                    'extra'       => ['level' => $level],
                ]);

                if ($next) {
                    $host  = parse_url($next, PHP_URL_HOST);
                    $path  = parse_url($next, PHP_URL_PATH) ?: '';
                    $query = parse_url($next, PHP_URL_QUERY);
                    $rel   = ltrim($path . ($query ? ('?' . $query) : ''), '/');

                    // Compute the *current* origin — do NOT trust
                    // X-Forwarded-Host as it can be spoofed by the client.
                    // Only use HTTP_HOST which is set by the web server.
                    $scheme  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $hostNow = $_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST);
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



                // Force password change before any dashboard access.
                if ($forceChange === 1) {
                    $this->session->set_flashdata('warning',
                        'For security, you must change your password before continuing.');
                    redirect('page/changepassword');
                    return;
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
                $this->loginthrottle->fail($username);
                $this->securityaudit->event('LOGIN_FAILED', [
                    'module'      => 'Login',
                    'status'      => 'failed',
                    'target'      => $username,
                    'description' => 'Correct password but account not active',
                    'extra'       => ['acctStat' => (string)$acctStat],
                ]);
                $status = strtolower(trim((string)$acctStat));
                $message = $status === 'pending verification'
                    ? 'Verify your email before signing in. Check your inbox or resend the verification email.'
                    : 'Your account is not active. Please contact support.';
                $this->session->set_flashdata('auth_error', $message);
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
            $triggered = $this->loginthrottle->fail($username);
            $this->securityaudit->event('LOGIN_FAILED', [
                'module'      => 'Login',
                'status'      => 'failed',
                'target'      => $username,
                'description' => 'Invalid credentials',
                'extra'       => $triggered ? ['throttle_triggered' => $triggered] : null,
            ]);
            $this->session->set_flashdata('auth_error', 'The username or password is incorrect!');
            redirect('login' . ($next ? ('?next=' . urlencode($next)) : ''));


            return;
        }
    }

    public function deleteUser($user)
    {
        // Defense in depth: the authguard role rule blocks unauthenticated
        // access, but never trust a single layer. Require a logged-in admin.
        if (!$this->session->userdata('logged_in')
            || !in_array($this->session->userdata('level'), array('Super Admin', 'Admin', 'IT'), true)) {
            show_error('You do not have permission to perform this action.', 403);
            return;
        }

        // Require POST: this is a destructive action. A GET link (e.g. from
        // an email or another site) must not be able to delete a user account.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            show_error('This action requires a POST request.', 405);
            return;
        }

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
        $this->securityaudit->event('LOGOUT', [
            'module' => 'Login',
            'status' => 'success',
        ]);
        $this->sessionregistry->close('signed out');

        // Clear the device-recognition cookie so a new browser session
        // is not auto-trusted as a "known device" after explicit logout.
        $this->load->helper('cookie');
        delete_cookie('fbmso_dev');

        $this->session->sess_destroy();
        redirect('login');
    }

    public function forgot_pass()
    {
        $email = $this->normalize_reset_email($this->input->post('email', TRUE));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect_forgot_password('Please enter a valid email address.', $email);
            return;
        }

        // Throttle by email to prevent enumeration/DoS via repeated reset
        // requests (each one rotates the account password).
        $blocked = $this->loginthrottle->check($email);
        if ($blocked) {
            $this->redirect_forgot_password(
                'Too many reset attempts. Please try again in ' . $blocked['retry_after'] . ' seconds.',
                $email
            );
            return;
        }

        $user = $this->Login_model->findUserByEmail($email);
        if (!$user) {
            $this->loginthrottle->fail($email);
            $this->redirect_forgot_password('Email not found!', $email);
            return;
        }

        $status = strtolower(trim((string)($user['acctStat'] ?? '')));
        if ($status !== 'active') {
            $this->loginthrottle->fail($email);
            $message = $status === 'pending verification'
                ? 'Verify your email before resetting your password.'
                : 'Your account is not active. Please contact support.';
            $this->redirect_forgot_password($message, $email);
            return;
        }

        $sendResult = $this->Login_model->sendTemporaryPasswordForUser((string)$user['username']);

        $this->AuditLogModel->write(
            'password_reset',
            'Login',
            'o_users',
            $user['username'],
            null,
            ['password_reset' => !empty($sendResult['ok']), 'mode' => 'temporary_password_email'],
            !empty($sendResult['ok']) ? 1 : 0,
            !empty($sendResult['ok']) ? 'Temporary password queued from forgot-password form' : 'Temporary password could not be queued from forgot-password form',
            ['target_email' => $email]
        );
        $this->securityaudit->event('PASSWORD_RESET', [
            'module'      => 'Login',
            'status'      => !empty($sendResult['ok']) ? 'success' : 'failed',
            'target'      => (string)$user['username'],
            'table'       => 'o_users',
            'record_pk'   => (string)$user['username'],
            'description' => 'Temporary password requested via forgot-password form',
        ]);

        if (empty($sendResult['ok'])) {
            $this->loginthrottle->fail($email);
            $this->redirect_forgot_password(
                (string)($sendResult['message'] ?? 'Unable to send the temporary password email.'),
                $email
            );
            return;
        }

        $this->loginthrottle->succeed($email);
        $this->loginthrottle->prune();
        $this->session->set_flashdata('forgot_info', (string)$sendResult['message']);
        redirect(base_url('login'), 'refresh');
        return;
    }

    /**
     * Email the account holder about a high-risk sign-in.
     *
     * Says what happened and when, and never why it was judged risky. Telling
     * a suspicious visitor "your risk score is 85 because the device is
     * unknown" hands them the list of signals to defeat next time; the plan
     * documents make the same point. The detail belongs in the security
     * report, which only staff receive.
     */
    private function notify_risky_login($username, $email, array $risk, array $device)
    {
        $email = trim((string)$email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Do not bury someone on a flaky connection under forty emails.
        $cooldown = (int)$this->config->item('risk_notify_cooldown') ?: 3600;
        $recent = $this->db->query(
            "SELECT 1 FROM fbmso_email_queue
              WHERE to_email = ? AND subject LIKE ? AND created_at >= ? LIMIT 1",
            array($email, 'New sign-in to your%', date('Y-m-d H:i:s', time() - $cooldown))
        )->row();

        if ($recent) {
            return;
        }

        $d = $device['device'] ?? array();
        $parts = array();
        $name  = trim((string)($d['device_marketing_name'] ?? ''));
        $model = trim((string)($d['device_model_code'] ?? ''));
        if ($name !== '' && $model !== '') { $parts[] = $name . ' (' . $model . ')'; }
        elseif ($name !== '')              { $parts[] = $name; }
        elseif ($model !== '')             { $parts[] = $model; }
        if (!empty($d['operating_system'])) {
            $parts[] = trim($d['operating_system'] . ' ' . (string)($d['os_version'] ?? ''));
        }
        if (!empty($d['browser'])) { $parts[] = (string)$d['browser']; }

        $deviceText = $parts ? implode(' · ', $parts) : 'a device we could not identify';
        $school = fbmso_mailqueue_school_name($this);
        $esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

        $body = '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;max-width:560px;'
              . 'margin:auto;color:#1f2328;line-height:1.6">'
              . '<h2 style="font-size:18px;margin:0 0 12px">A new device signed in to your account</h2>'
              . '<p>Someone signed in to <strong>' . $esc($username) . '</strong> just now using a device '
              . 'that has not been used on this account before.</p>'
              . '<div style="background:#f7f8fa;border-left:3px solid #c9ced6;padding:12px 14px;margin:14px 0;'
              . 'font-family:ui-monospace,Menlo,monospace;font-size:13px">'
              . $esc(date('l, j F Y \a\t H:i')) . '<br>'
              . $esc($deviceText) . '<br>'
              . 'from ' . $esc($this->input->ip_address())
              . '</div>'
              . '<p><strong>If this was you</strong>, nothing to do.</p>'
              . '<p><strong>If it was not</strong>, change your password now. That signs out every other '
              . 'device immediately. If you cannot get in, use Forgot Password on the sign-in page, '
              . 'or contact the FBMSO office.</p>'
              . '<p style="font-size:12px;color:#6b7280;margin-top:22px;border-top:1px solid #eceff2;'
              . 'padding-top:12px">Automatic message from ' . $esc($school) . '. We will never ask you '
              . 'for your password by email.</p></div>';

        fbmso_mailqueue_push($this, $email, 'New sign-in to your ' . $school . ' account', $body, $school);

        $this->securityaudit->event('SECURITY_ALERT_SENT', [
            'module' => 'Login', 'status' => 'success', 'target' => $username,
            'description' => 'Account holder notified of a high-risk sign-in',
            'risk_score' => $risk['score'], 'risk_level' => $risk['level'],
        ]);
    }

    private function normalize_reset_email($email)
    {
        $email = (string)$email;
        $email = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $email);
        $email = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $email);
        $email = preg_replace('/\s+/u', '', $email);

        return strtolower(trim($email));
    }

    private function redirect_forgot_password($message, $email = '')
    {
        $this->session->set_flashdata('forgot_error', $message);
        $this->session->set_flashdata('forgot_modal_open', 1);
        $this->session->set_flashdata('forgot_email', $email);

        redirect(base_url('login'), 'refresh');
    }
}

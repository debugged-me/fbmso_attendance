<?php
defined('BASEPATH') or exit('No direct script access allowed');

// MobileAuth extends the shared MobileApi base controller which lives in
// application/libraries/. CI3 does not auto-load library classes as base
// controllers, so require it explicitly.
require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile authentication API for the FBMSO Attendance native app.
 *
 * Mirrors the srms-college /api/mobile contract but is self-contained for
 * this codebase: it reuses Login_model::validate() (sha1 password path) so
 * that ANY account which logs in on the web can log in on the mobile app
 * with the same credentials. No separate user store.
 *
 * Endpoints (see application/config/routes.php):
 *   GET  api/mobile/config
 *   POST api/mobile/auth/login
 *   GET  api/mobile/auth/me
 *   POST api/mobile/auth/logout
 *   POST api/mobile/auth/change-password
 *   POST api/mobile/auth/change-avatar
 *   GET  api/mobile/auth/avatar
 *   POST api/mobile/auth/forgot-password
 */
class MobileAuth extends MobileApi
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Login_model');
        $this->load->model('EmailVerificationModel');
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Endpoints
    // ──────────────────────────────────────────────────────────────────────

    /** Public bootstrap: school name, active SY/sem, logo URLs, signup flag. */
    public function config()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $settings = $this->current_settings();
        $baseUrl  = $this->runtime_base_url();

        return $this->json([
            'ok'                    => true,
            'school_name'           => (string)($settings->SchoolName ?? 'FBMSO Portal'),
            'active_sy'             => (string)($settings->active_sy ?? ''),
            'active_sem'            => (string)($settings->active_sem ?? ''),
            'allow_signup'          => (string)($settings->allow_signup ?? 'No'),
            'login_logo_url'        => $this->banner_url((string)($settings->login_form_image ?? '')),
            'login_background_url'  => $this->banner_url((string)($settings->loginFormImage ?? '')),
            'base_url'              => $baseUrl,
            'api_base_url'          => rtrim($baseUrl, '/') . '/api/mobile',
        ]);
    }

    /**
     * Registration form options — courses, year levels, and sections.
     * Public (no token needed) so the registration form can populate
     * dropdowns before the user has an account.
     */
    public function registration_options()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $this->load->model('StudentModel');

        $courses = [];
        try {
            $rows = $this->StudentModel->getCourse();
            foreach ($rows as $r) {
                $courses[] = (string)($r->CourseDescription ?? '');
            }
        } catch (\Throwable $e) {
            // ignore — return empty list
        }

        $yearLevels = ['1st', '2nd', '3rd', '4th'];

        // Sections depend on course + year level, so we return them
        // as a flat list of all sections for simplicity. The mobile
        // form can filter client-side or just show all.
        $sections = [];
        try {
            if (method_exists($this->StudentModel, 'get_all_sections')) {
                $secRows = $this->StudentModel->get_all_sections();
                foreach ($secRows as $r) {
                    $sections[] = (string)($r->section ?? $r->Section ?? '');
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // If no sections from model, try a direct query
        if (empty($sections) && $this->db->table_exists('course_sections')) {
            $secRows = $this->db->distinct()->select('section')->get('course_sections')->result();
            foreach ($secRows as $r) {
                $s = trim((string)($r->section ?? ''));
                if ($s !== '') $sections[] = $s;
            }
        }

        return $this->json([
            'ok'         => true,
            'courses'    => array_values(array_filter($courses)),
            'year_levels'=> $yearLevels,
            'sections'   => array_values(array_filter($sections)),
        ]);
    }

    /**
     * Sections for a specific course + year level.
     * Mirrors Registration::getSectionsByCourseYear on the web.
     */
    public function registration_sections()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $courseByName = trim((string)$this->input->get('course', true));
        $courseid     = trim((string)$this->input->get('courseid', true));
        $yearLevel    = trim((string)$this->input->get('year_level', true));

        // If courseid is not numeric, ignore it (the mobile form sends CourseDescription)
        if ($courseid !== '' && !ctype_digit($courseid)) {
            $courseid = '';
        }

        // Resolve numeric courseid from CourseDescription
        if ($courseid === '' && $courseByName !== '') {
            $row = $this->db->select('courseid')
                ->from('course_table')
                ->where('CourseDescription', $courseByName)
                ->limit(1)->get()->row();
            if ($row) $courseid = (string)$row->courseid;
        }

        $sections = [];

        if ($this->db->table_exists('course_sections') && $courseid !== '' && $yearLevel !== '') {
            $q = $this->db->select('section')
                ->from('course_sections')
                ->where('courseid', (int)$courseid)
                ->where('year_level', $yearLevel)
                ->where('is_active', 1)
                ->order_by('section', 'ASC')
                ->get();
            foreach ($q->result() as $r) {
                $sections[] = (string)$r->section;
            }
        }

        // Fallback so the form always works even if no rows match
        if (empty($sections)) {
            $sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        }

        return $this->json([
            'ok'       => true,
            'sections' => array_values(array_filter(array_unique($sections))),
        ]);
    }

    /**
     * Check if a Student ID or email already exists.
     * Mirrors Registration::checkAvailability on the web.
     */
    public function registration_check_availability()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $field = strtolower(trim((string)$this->input->get('field', true)));
        $value = trim((string)$this->input->get('value', true));

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

        return $this->json($response);
    }

    /** Login with the same credentials the web Login::auth() accepts. */
    public function login()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $payload     = $this->read_payload();
        $username    = trim((string)($payload['username'] ?? ''));
        $passwordRaw = (string)($payload['password'] ?? '');
        $settings    = $this->current_settings();
        $sy          = trim((string)($payload['sy'] ?? ($settings->active_sy ?? '')));
        $semester    = trim((string)($payload['semester'] ?? ($settings->active_sem ?? '')));

        if ($username === '' || $passwordRaw === '') {
            return $this->json(['ok' => false, 'message' => 'Username and password are required.'], 422);
        }

        // Same path as Login::auth(): validate() takes the RAW password and
        // verifies bcrypt (or legacy sha1) in PHP.
        $result = $this->Login_model->validate($username, $passwordRaw);

        if (!$result || $result->num_rows() === 0) {
            $this->Login_model->log_login_attempt($username, $passwordRaw, 'failed');
            return $this->json(['ok' => false, 'message' => 'The username or password is incorrect.'], 401);
        }

        $userRow = $result->row_array();
        $username = (string)($userRow['username'] ?? $username);

        if (!$this->user_is_active($userRow)) {
            $this->Login_model->log_login_attempt($username, $passwordRaw, 'failed');
            if ($this->user_needs_email_verification($userRow)) {
                return $this->json(['ok' => false, 'message' => 'Verify your email before signing in. Check your inbox for the verification link.'], 403);
            }
            return $this->json(['ok' => false, 'message' => 'Your account is not active. Please contact support.'], 403);
        }

        $this->Login_model->log_login_attempt($username, $passwordRaw, 'success');

        $token = $this->MobileTokenModel->issue($username, [
            'device_id'   => trim((string)($payload['device_id'] ?? ''))   ?: null,
            'device_name' => trim((string)($payload['device_name'] ?? '')) ?: null,
            'platform'    => trim((string)($payload['platform'] ?? ''))    ?: null,
        ]);

        $schoolName = (string)($settings->SchoolName ?? 'FBMSO Portal');

        return $this->json([
            'ok'         => true,
            'message'    => 'Login successful.',
            'token'      => $token,
            'school_name'=> $schoolName,
            'active_sy'  => $sy,
            'active_sem' => $semester,
            'user'       => $this->build_user_payload($userRow, $schoolName, $sy, $semester),
        ]);
    }

    /** Restore session on cold start using a bearer token. */
    public function me()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $tokenRow = $this->require_token();
        if ($tokenRow === null) {
            return; // response already sent
        }

        $username = (string)$tokenRow['username'];
        $userRow  = $this->db->get_where('o_users', ['username' => $username], 1)->row_array();
        if (!$userRow) {
            return $this->json(['ok' => false, 'message' => 'User not found.'], 404);
        }
        if (!$this->user_is_active($userRow)) {
            return $this->json(['ok' => false, 'message' => 'Your account is not active. Please contact support.'], 403);
        }

        $settings = $this->current_settings();
        $schoolName = (string)($settings->SchoolName ?? 'FBMSO Portal');
        $sy       = trim((string)($this->input->get('sy', true) ?: ($settings->active_sy ?? '')));
        $semester = trim((string)($this->input->get('semester', true) ?: ($settings->active_sem ?? '')));

        return $this->json([
            'ok'         => true,
            'school_name'=> $schoolName,
            'active_sy'  => $sy,
            'active_sem' => $semester,
            'user'       => $this->build_user_payload($userRow, $schoolName, $sy, $semester),
        ]);
    }

    /** Revoke the calling token. */
    public function logout()
    {
        $raw = $this->bearer_token();
        if ($raw !== '') {
            $this->MobileTokenModel->revoke($raw);
        }
        return $this->json(['ok' => true, 'message' => 'Logged out.']);
    }

    /** Change password (sha1 re-hash). Revokes all mobile tokens for the user. */
    public function change_password()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $tokenRow = $this->require_token();
        if ($tokenRow === null) {
            return;
        }

        $payload          = $this->read_payload();
        $currentPassword  = (string)($payload['current_password'] ?? '');
        $newPassword      = (string)($payload['new_password'] ?? '');
        $confirmPassword  = (string)($payload['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return $this->json(['ok' => false, 'message' => 'All password fields are required.'], 422);
        }
        if (strlen($newPassword) < 8) {
            return $this->json(['ok' => false, 'message' => 'New password must be at least 8 characters long.'], 422);
        }
        if ($newPassword !== $confirmPassword) {
            return $this->json(['ok' => false, 'message' => 'New password and confirmation do not match.'], 422);
        }

        $username = (string)$tokenRow['username'];
        $userRow  = $this->db->get_where('o_users', ['username' => $username], 1)->row_array();
        if (!$userRow) {
            return $this->json(['ok' => false, 'message' => 'User not found.'], 404);
        }

        if (!fbmso_password_verify($currentPassword, (string)($userRow['password'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Current password is incorrect.'], 401);
        }

        $newHash = fbmso_password_hash($newPassword);
        if ($newHash === '') {
            return $this->json(['ok' => false, 'message' => 'That password cannot be used. Please choose a different one.'], 422);
        }

        $this->db->where('username', $username)->update('o_users', ['password' => $newHash]);
        $this->MobileTokenModel->revokeAllForUser($username);

        return $this->json(['ok' => true, 'message' => 'Password changed successfully. Please log in again.']);
    }

    /** Change display picture (multipart upload, field name `photo`). */
    public function change_avatar()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $tokenRow = $this->require_token();
        if ($tokenRow === null) {
            return;
        }
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];

        if (empty($_FILES['photo']['name'])) {
            $body = json_encode(['ok' => false, 'message' => 'A photo file (field "photo") is required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
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

        if (!$this->upload->do_upload('photo')) {
            $body = json_encode(['ok' => false, 'message' => $this->upload->display_errors('', '')]);
            $this->record_idempotent_response(400, $body);
            return $this->json(json_decode($body, true), 400);
        }

        $filename = $this->upload->data('file_name');

        // Remove the previous avatar (users first, then o_users) unless it is the default.
        $row = $this->db->select('avatar')->from('users')->where('username', $username)->get()->row();
        if (!$row) {
            $row = $this->db->select('avatar')->from('o_users')->where('username', $username)->get()->row();
        }
        if ($row && $row->avatar && strtolower($row->avatar) !== 'avatar.png') {
            $old = FCPATH . 'upload/profile/' . $row->avatar;
            if (is_file($old)) @unlink($old);
        }

        // Update whichever table the user exists in (mirrors Page/uploadProfPic).
        $this->db->where('username', $username)->update('users', ['avatar' => $filename]);
        if ($this->db->affected_rows() === 0) {
            $this->db->where('username', $username)->update('o_users', ['avatar' => $filename]);
        }

        $avatarUrl = $this->avatar_url($filename);
        $body = json_encode([
            'ok'         => true,
            'avatar_url' => $avatarUrl,
            'message'    => 'Avatar updated successfully.',
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Return the current user's avatar URL (o_users.avatar, falling back to users.avatar). */
    public function avatar()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $tokenRow = $this->require_token();
        if ($tokenRow === null) {
            return;
        }

        $username = (string)$tokenRow['username'];
        $row = $this->db->select('avatar')->from('o_users')->where('username', $username)->limit(1)->get()->row();
        if (!$row) {
            $row = $this->db->select('avatar')->from('users')->where('username', $username)->limit(1)->get()->row();
        }
        $avatar = trim((string)($row->avatar ?? ''));

        return $this->json(['ok' => true, 'avatar_url' => $this->avatar_url($avatar)]);
    }

    /** Email-based password reset (delegates to Login_model::forgotPassword). */
    public function forgot_password()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $payload = $this->read_payload();
        $email   = trim((string)($payload['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['ok' => false, 'message' => 'A valid email is required.'], 422);
        }

        $account = $this->Login_model->findUserByEmail($email);
        if (!$account) {
            // Do not leak whether the email exists.
            return $this->json(['ok' => true, 'message' => 'If that email exists, a temporary password has been sent.']);
        }

        if (!$this->user_is_active($account)) {
            $message = $this->user_needs_email_verification($account)
                ? 'Verify your email before resetting your password.'
                : 'Your account is not active. Please contact support.';
            return $this->json(['ok' => false, 'message' => $message], 403);
        }

        // Returns ['ok' => bool, 'message' => string] — a bare truthiness check
        // on the array would always pass and report success on a failed reset.
        $sent = $this->Login_model->sendTemporaryPasswordForUser((string)($account['username'] ?? ''));
        if (empty($sent['ok'])) {
            return $this->json(['ok' => false, 'message' => 'Unable to send a reset email right now. Please try again later.'], 500);
        }

        return $this->json(['ok' => true, 'message' => 'If that email exists, a temporary password has been sent.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Registration — mirrors Registration::index() public signup flow
    // ──────────────────────────────────────────────────────────────────────

    public function register()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }

        $p = $this->read_payload();

        $studentNumber = strtoupper(trim((string)($p['StudentNumber'] ?? '')));
        $firstName     = strtoupper(trim((string)($p['FirstName'] ?? '')));
        $middleName    = strtoupper(trim((string)($p['MiddleName'] ?? '')));
        $lastName      = strtoupper(trim((string)($p['LastName'] ?? '')));
        $nameExtn      = strtoupper(trim((string)($p['nameExtn'] ?? '')));
        $sex           = trim((string)($p['Sex'] ?? ''));
        $birthDate     = trim((string)($p['birthDate'] ?? ''));
        $email         = strtolower(trim((string)($p['email'] ?? '')));
        $contactNo     = trim((string)($p['contactNo'] ?? ''));
        $course1       = trim((string)($p['Course1'] ?? ''));
        $major1        = trim((string)($p['Major1'] ?? ''));
        $yearLevel     = trim((string)($p['yearLevel'] ?? ''));
        $section       = trim((string)($p['section'] ?? ''));
        $passwordRaw   = (string)($p['password'] ?? '');
        $confirmPass   = (string)($p['confirm_password'] ?? '');

        // Validate required fields
        $errors = [];
        if ($studentNumber === '') $errors[] = 'Student ID/Number is required.';
        if (!preg_match('/^[A-Z0-9\-]+$/', $studentNumber)) $errors[] = 'Student ID may only contain letters, numbers, and hyphen.';
        if ($firstName === '') $errors[] = 'First name is required.';
        if ($lastName === '') $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if ($passwordRaw === '') $errors[] = 'Password is required.';
        if (strlen($passwordRaw) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($passwordRaw !== $confirmPass) $errors[] = 'Passwords do not match.';

        $validLevels = ['1st', '2nd', '3rd', '4th'];
        $yearLevelNormalized = preg_replace('/\s*Year$/i', '', $yearLevel);
        if (!in_array($yearLevelNormalized, $validLevels, true)) {
            $errors[] = 'Please select a valid Year Level.';
        }

        if (!empty($errors)) {
            return $this->json(['ok' => false, 'message' => implode(' ', $errors)], 422);
        }

        // Duplicate checks
        $studentIdExists = ($this->db->where('username', $studentNumber)->count_all_results('o_users') > 0)
            || ($this->db->where('StudentNumber', $studentNumber)->count_all_results('studentsignup') > 0);
        $emailExists = ($this->db->where('email', $email)->count_all_results('o_users') > 0)
            || ($this->db->where('email', $email)->count_all_results('studentsignup') > 0);

        if ($studentIdExists || $emailExists) {
            $parts = [];
            if ($studentIdExists) $parts[] = 'Student ID already exists.';
            if ($emailExists) $parts[] = 'Email already exists.';
            return $this->json(['ok' => false, 'message' => implode(' ', $parts)], 409);
        }

        // Compute age
        $age = 0;
        if ($birthDate !== '') {
            $dob = DateTime::createFromFormat('Y-m-d', $birthDate);
            if ($dob instanceof DateTime) {
                $today = new DateTime('today');
                $age = (int)$dob->diff($today)->y;
            }
        }

        $studentData = [
            'StudentNumber'   => $studentNumber,
            'FirstName'       => $firstName,
            'MiddleName'      => $middleName,
            'LastName'        => $lastName,
            'nameExtn'        => $nameExtn,
            'Sex'             => $sex,
            'birthDate'       => $birthDate,
            'age'             => $age,
            'contactNo'       => $contactNo,
            'email'           => $email,
            'section'         => $section,
            'working'         => 'No',
            'VaccStat'        => '',
            'nationality'     => 'Filipino',
            'yearLevel'       => $yearLevelNormalized,
            'Course1'         => $course1,
            'Major1'          => $major1,
            'EnrollmentDate'  => date('Y-m-d'),
            'BirthPlace'      => '',
            'CivilStatus'     => 'Single',
            'Religion'        => '',
            'province'        => '',
            'city'            => '',
            'brgy'            => '',
            'sitio'           => '',
            'Course2'         => '',
            'Course3'         => '',
            'Major2'          => '',
            'Major3'          => '',
            'Status'          => 'Pending',
            'graduationDate'  => '',
            'guardian'        => '',
            'guardianRelationship' => '',
            'guardianContact' => '',
            'guardianAddress' => '',
            'father'          => '',
            'fOccupation'     => '',
            'fatherAddress'   => '',
            'fatherContact'   => '',
            'mother'          => '',
            'mOccupation'     => '',
            'motherAddress'   => '',
            'motherContact'   => '',
        ];

        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        $passwordHash = fbmso_password_hash($passwordRaw);
        if ($passwordHash === '') {
            return $this->json(['ok' => false, 'message' => 'That password cannot be used. Please choose a different one.'], 422);
        }

        $this->db->trans_start();
        $this->db->insert('studentsignup', $studentData);
        $this->db->insert('o_users', [
            'username'    => $studentNumber,
            'IDNumber'    => $studentNumber,
            'fName'       => $firstName,
            'mName'       => $middleName,
            'lName'       => $lastName,
            'name'        => $fullName,
            'password'    => $passwordHash,
            'position'    => 'Student',
            'email'       => $email,
            'acctStat'    => EmailVerificationModel::ACCOUNT_STATUS,
            'dateCreated' => date('Y-m-d'),
        ]);
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return $this->json(['ok' => false, 'message' => 'Registration failed. Please try again.'], 500);
        }

        // Create semesterstude profiling row
        $settings = $this->current_settings();
        $sy = $settings->SY ?? (date('Y') . '-' . (date('Y') + 1));
        $semester = $settings->Semester ?? 'First Semester';

        $existingSem = $this->db->get_where('semesterstude', [
            'StudentNumber' => $studentNumber,
            'SY' => $sy,
            'Semester' => $semester,
        ])->row();

        $profiling = [
            'StudentNumber' => $studentNumber,
            'Course' => $course1,
            'YearLevel' => $yearLevelNormalized,
            'Status' => 'Enrolled',
            'Semester' => $semester,
            'SY' => $sy,
            'Section' => $section,
            'StudeStatus' => 'New',
            'Major' => $major1,
            'settingsID' => 1,
            'enroledDate' => date('Y-m-d'),
        ];

        if (!$existingSem) {
            $this->db->insert('semesterstude', $profiling);
        }

        $verification = $this->EmailVerificationModel->queueForUser($studentNumber);
        $message = !empty($verification['ok'])
            ? 'Registration successful. Check your email and tap Verify Email & Login before signing in.'
            : 'Registration successful, but the verification email could not be sent. Use Resend verification email on the web login page.';

        return $this->json([
            'ok' => true,
            'email_queued' => !empty($verification['ok']),
            'message' => $message,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers (auth-specific; shared helpers inherited from MobileApi)
    // ──────────────────────────────────────────────────────────────────────

    private function user_is_active(array $userRow): bool
    {
        return strtolower(trim((string)($userRow['acctStat'] ?? ''))) === 'active';
    }

    private function user_needs_email_verification(array $userRow): bool
    {
        return strtolower(trim((string)($userRow['acctStat'] ?? ''))) === strtolower(EmailVerificationModel::ACCOUNT_STATUS);
    }

    private function current_settings()
    {
        return $this->db->query('SELECT * FROM o_srms_settings LIMIT 1')->row();
    }

    /** Resolve the base URL the client should use, proxy/CDN aware. */
    private function runtime_base_url(): string
    {
        $xfProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $xfHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? null;
        $scheme  = $xfProto ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host    = $xfHost  ?: ($_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST) ?? '');
        return rtrim($scheme . '://' . $host, '/');
    }

    /** Turn a stored banner filename into an absolute URL. */
    private function banner_url(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return '';
        }
        return rtrim($this->runtime_base_url(), '/') . '/upload/banners/' . rawurlencode($filename);
    }

    /** Build the user payload returned by login/me. */
    private function build_user_payload(array $userRow, string $schoolName, string $sy, string $semester): array
    {
        $position = (string)($userRow['position'] ?? '');

        return [
            'username'   => (string)($userRow['username']  ?? ''),
            'id_number'  => (string)($userRow['IDNumber']  ?? ''),
            'first_name' => (string)($userRow['fName']     ?? ''),
            'middle_name'=> (string)($userRow['mName']     ?? ''),
            'last_name'  => (string)($userRow['lName']     ?? ''),
            'email'      => (string)($userRow['email']     ?? ''),
            'avatar'     => $this->avatar_url((string)($userRow['avatar'] ?? '')),
            'position'   => $position,
            'role'       => $position, // role == position in this codebase
            'school_name'=> $schoolName,
            'active_sy'  => $sy,
            'active_sem' => $semester,
        ];
    }

    private function avatar_url(string $avatar): string
    {
        $avatar = trim($avatar);
        if ($avatar === '') {
            $avatar = 'avatar.png';
        }
        if (preg_match('#^https?://#i', $avatar)) {
            return $avatar;
        }
        return rtrim($this->runtime_base_url(), '/') . '/upload/profile/' . rawurlencode(ltrim($avatar, '/'));
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

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
class MobileAuth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->model('Login_model');
        $this->load->model('MobileTokenModel');
        $this->send_cors_headers();
    }

    /**
     * CORS support so the Flutter web build (dev preview on another port)
     * can call the API. Native mobile apps don't need CORS. OPTIONS
     * preflights are answered immediately with 204.
     */
    private function send_cors_headers(): void
    {
        $origin = $this->input->get_request_header('Origin');
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Idempotency-Key');
        header('Access-Control-Max-Age: 86400');

        if ($this->input->method(true) === 'OPTIONS') {
            $this->output->set_status_header(204);
            $this->output->_display();
            exit;
        }
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

        // Same hashing path as Login::auth(): web hashes raw input with sha1
        // before comparing against o_users.password.
        $passwordHash = sha1($passwordRaw);
        $result = $this->Login_model->validate($username, $passwordHash);

        if (!$result || $result->num_rows() === 0) {
            $this->Login_model->log_login_attempt($username, $passwordRaw, 'failed');
            return $this->json(['ok' => false, 'message' => 'The username or password is incorrect.'], 401);
        }

        $userRow = $result->row_array();
        $username = (string)($userRow['username'] ?? $username);

        if (!$this->user_is_active($userRow)) {
            $this->Login_model->log_login_attempt($username, $passwordRaw, 'failed');
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

        if (sha1($currentPassword) !== (string)($userRow['password'] ?? '')) {
            return $this->json(['ok' => false, 'message' => 'Current password is incorrect.'], 401);
        }

        $this->db->where('username', $username)->update('o_users', ['password' => sha1($newPassword)]);
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

        $sent = $this->Login_model->sendTemporaryPasswordForUser((string)($account['username'] ?? ''));
        if (!$sent) {
            return $this->json(['ok' => false, 'message' => 'Unable to send a reset email right now. Please try again later.'], 500);
        }

        return $this->json(['ok' => true, 'message' => 'If that email exists, a temporary password has been sent.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Idempotency (mirrors MobileApi's o_mobile_outbox dedup)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * For a mutating request, replay the stored response if the
     * X-Idempotency-Key has been seen before. Returns true when replayed
     * (caller must stop); false to proceed.
     */
    private function replay_if_duplicate(): bool
    {
        $method = strtoupper((string)$this->input->method(true));
        if ($method === 'GET') {
            return false;
        }

        $key = trim((string)$this->input->get_request_header('X-Idempotency-Key'));
        if ($key === '') {
            return false;
        }

        $now = time();
        try {
            $this->db->where('expires_at <', $now)->delete('o_mobile_outbox');
        } catch (Throwable $e) {
            // ignore
        }

        $row = $this->db->get_where('o_mobile_outbox', ['idem_key' => $key], 1)->row_array();
        if (!$row) {
            return false;
        }

        $this->output
            ->set_status_header((int)$row['status_code'])
            ->set_content_type('application/json', 'utf-8')
            ->set_output((string)$row['response_body']);
        return true;
    }

    /** Record the response for the current idempotency key (no-op if no key). */
    private function record_idempotent_response(int $statusCode, string $responseBody): void
    {
        $key = trim((string)$this->input->get_request_header('X-Idempotency-Key'));
        if ($key === '') {
            return;
        }

        $now = time();
        $username = '';
        $raw = $this->bearer_token();
        if ($raw !== '') {
            $t = $this->MobileTokenModel->lookup($raw);
            if ($t) {
                $username = (string)$t['username'];
            }
        }
        $endpoint = 'mobileauth/' . strtolower((string)$this->router->fetch_method());

        try {
            $this->db->insert('o_mobile_outbox', [
                'idem_key'      => $key,
                'username'      => $username,
                'endpoint'      => $endpoint,
                'status_code'   => $statusCode,
                'response_body' => $responseBody,
                'created_at'    => $now,
                'expires_at'    => $now + 86400,
            ]);
        } catch (Throwable $e) {
            // A duplicate insert (race) is fine — the first one wins.
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────

    /** Emit JSON and stop. */
    private function json(array $data, int $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
        return null;
    }

    /** Read + decode a JSON request body (falls back to form input). */
    private function read_payload(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== '' && $raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $this->input->post() ?: [];
    }

    /** Extract the bearer token from the Authorization header. */
    private function bearer_token(): string
    {
        $header = $this->input->get_request_header('Authorization');
        if (!$header) {
            return '';
        }
        $header = trim($header);
        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return trim($header);
    }

    /**
     * Validate the bearer token; on failure send 401 and return null.
     * On success return the o_mobile_tokens row.
     */
    private function require_token(): ?array
    {
        $raw = $this->bearer_token();
        if ($raw === '') {
            $this->json(['ok' => false, 'message' => 'Missing authorization token.'], 401);
            return null;
        }
        $row = $this->MobileTokenModel->lookup($raw);
        if (!$row) {
            $this->json(['ok' => false, 'message' => 'Invalid or expired token. Please log in again.'], 401);
            return null;
        }
        return $row;
    }

    private function user_is_active(array $userRow): bool
    {
        return strtolower(trim((string)($userRow['acctStat'] ?? ''))) === 'active';
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
            return '';
        }
        if (preg_match('#^https?://#i', $avatar)) {
            return $avatar;
        }
        return rtrim($this->runtime_base_url(), '/') . '/upload/profile/' . rawurlencode(ltrim($avatar, '/'));
    }
}

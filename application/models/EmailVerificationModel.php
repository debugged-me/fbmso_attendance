<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EmailVerificationModel extends CI_Model
{
    const TABLE = 'o_email_verifications';
    const ACCOUNT_STATUS = 'Pending Verification';
    const TOKEN_TTL_HOURS = 24;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensureTable();
    }

  
    public function ensureTable()
    {
        if ($this->db->table_exists(self::TABLE)) {
            return true;
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(45) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_email_verification_token` (`token_hash`),
                KEY `idx_email_verification_user` (`username`, `used_at`),
                KEY `idx_email_verification_expiry` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

      
        $this->db->data_cache = [];

        return $this->db->table_exists(self::TABLE);
    }

    public function queueForUser($username)
    {
        $username = trim((string)$username);
    
        if ($username === '' || !$this->ensureTable()) {
            return $this->failure(
                'Unable to prepare the verification email. Please try again.'
            );
        }
    
        // Get user account
        $user = $this->db
            ->where('username', $username)
            ->limit(1)
            ->get('o_users')
            ->row_array();
    
        if (!$user) {
            return $this->failure('Account not found.');
        }
    
        // Check account status
        $status = strtolower(trim((string)($user['acctStat'] ?? '')));
    
        if ($status === 'active') {
            return [
                'ok' => true,
                'already_verified' => true,
                'message' => 'This email address is already verified. You can sign in now.',
            ];
        }
    
        if ($status !== strtolower(self::ACCOUNT_STATUS)) {
            return $this->failure(
                'This account cannot be verified by email. Please contact support.'
            );
        }
    
        // Prevent rapid resend requests
        $recentCutoff = date('Y-m-d H:i:s', time() - 60);
    
        $recent = $this->db
            ->where('username', $username)
            ->where('used_at IS NULL', null, false)
            ->where('created_at >=', $recentCutoff)
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();
    
        if ($recent) {
            return $this->failure(
                'A verification email was just sent. Please wait one minute before requesting another.'
            );
        }
    
        // Validate registered email
        $email = strtolower(trim((string)($user['email'] ?? '')));
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failure(
                'The account does not have a valid email address.'
            );
        }
    
        // Generate secure verification token
        try {
            $token = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
    
            log_message(
                'error',
                'Email verification token generation failed: ' . $e->getMessage()
            );
    
            return $this->failure(
                'Unable to prepare the verification email. Please try again.'
            );
        }
    
        $createdAt = date('Y-m-d H:i:s');
    
        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + (self::TOKEN_TTL_HOURS * 3600)
        );
    
        // Store verification token
        $inserted = $this->db->insert(self::TABLE, [
            'username'   => $username,
            'email'      => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'used_at'    => null,
            'created_at' => $createdAt,
        ]);
    
        if (!$inserted) {
            return $this->failure(
                'Unable to prepare the verification email. Please try again.'
            );
        }
    
        $verificationId = (int)$this->db->insert_id();
    
        // School information
        $settings = $this->db
            ->limit(1)
            ->get('o_srms_settings')
            ->row();
    
        $schoolName = trim(
            (string)($settings->SchoolName ?? 'FBMSO Attendance Portal')
        );
    
        $firstName = trim(
            (string)($user['fName'] ?? 'Student')
        ) ?: 'Student';
    
        // Build verification URL
        $verificationUrl =
            site_url('verify-email') .
            '?token=' .
            rawurlencode($token);
    
        $escapedUrl = htmlspecialchars(
            $verificationUrl,
            ENT_QUOTES,
            'UTF-8'
        );
    
        // Email content
        $htmlMessage = '
            <div style="
                margin:0;
                padding:32px 16px;
                background:#f0f4ff;
                font-family:Arial,sans-serif;
                color:#26324a;
            ">
    
                <div style="
                    max-width:600px;
                    margin:0 auto;
                    background:#ffffff;
                    border-radius:18px;
                    overflow:hidden;
                    box-shadow:0 12px 35px rgba(42,64,144,.12);
                    border:1px solid #e4ebff;
                ">
    
                    <div style="
                        padding:28px 32px;
                        background:linear-gradient(135deg,#1a2a6c,#2a4090,#3b5fd4);
                        color:#ffffff;
                    ">
    
                        <div style="
                            font-size:13px;
                            opacity:.75;
                            margin-bottom:6px;
                        ">
                            Attendance Portal
                        </div>
    
                        <div style="
                            font-size:24px;
                            font-weight:bold;
                        ">
                            Verify your email
                        </div>
    
                    </div>
    
                    <div style="padding:32px;">
    
                        <p style="
                            margin-top:0;
                            line-height:1.7;
                            color:#4a5a7a;
                        ">
                            Dear <strong style="color:#0d1b4b;">' .
                            htmlspecialchars(
                                $firstName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) .
                            '</strong>,
                        </p>
    
                        <p style="
                            line-height:1.7;
                            color:#4a5a7a;
                        ">
                            Your account for
                            <strong style="color:#0d1b4b;">' .
                            htmlspecialchars(
                                $schoolName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) .
                            '</strong>
                            has been created successfully.
                        </p>
    
                        <p style="
                            line-height:1.7;
                            color:#4a5a7a;
                        ">
                            Please verify your email address before signing in.
                            Click the button below to activate your account.
                        </p>
    
                        <div style="
                            text-align:center;
                            margin:32px 0;
                        ">
    
                            <a
                                href="' . $escapedUrl . '"
                                style="
                                    display:inline-block;
                                    padding:14px 30px;
                                    background:linear-gradient(
                                        135deg,
                                        #2a4090,
                                        #4266d4,
                                        #3b5fd4
                                    );
                                    color:#ffffff;
                                    text-decoration:none;
                                    border-radius:12px;
                                    font-size:14px;
                                    font-weight:bold;
                                    box-shadow:
                                        0 10px 24px rgba(42,64,144,.25);
                                "
                            >
                                Verify Email
                            </a>
    
                        </div>
    
                        <div style="
                            padding:14px 16px;
                            background:#f6f9ff;
                            border:1px solid #e2e9ff;
                            border-radius:12px;
                            font-size:13px;
                            color:#6b7fa8;
                            line-height:1.6;
                        ">
                            This verification link expires in
                            <strong>' .
                            self::TOKEN_TTL_HOURS .
                            ' hours</strong>.
                            If you did not create this account,
                            you can safely ignore this email.
                        </div>
    
                        <p style="
                            margin-top:24px;
                            margin-bottom:5px;
                            font-size:12px;
                            color:#98a2b3;
                        ">
                            If the button does not work, copy and open this link:
                        </p>
    
                        <p style="
                            font-size:12px;
                            word-break:break-all;
                            line-height:1.5;
                        ">
                            <a
                                href="' . $escapedUrl . '"
                                style="color:#3b5fd4;"
                            >' .
                            $escapedUrl .
                            '</a>
                        </p>
    
                    </div>
    
                    <div style="
                        padding:18px 32px;
                        background:#f8faff;
                        border-top:1px solid #e9eeff;
                        text-align:center;
                        color:#98a2b3;
                        font-size:11px;
                    ">
                        ' .
                        htmlspecialchars(
                            $schoolName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '<br>
                        This is an automated message. Please do not reply.
                    </div>
    
                </div>
    
            </div>
        ';
    
        $queued = fbmso_mailqueue_push(
            $this,
            $email,
            'Verify your email - ' . $schoolName,
            $htmlMessage,
            $schoolName
        );
        
        if (!$queued) {
            $this->db
                ->where('id', $verificationId)
                ->delete(self::TABLE);
        
            return $this->failure(
                'The verification email could not be queued. Please try again.'
            );
        }
        
        return [
            'ok' => true,
            'already_verified' => false,
            'message' => 'A verification email has been queued. Please check your inbox shortly and verify your account before signing in.',
        ];
        }

    
    public function queueForEmail($email)
    {
        $email = strtolower(trim((string)$email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failure('Please enter a valid email address.');
        }

        $user = $this->db
            ->where('LOWER(TRIM(email)) = ' . $this->db->escape($email), null, false)
            ->order_by('dateCreated', 'DESC')
            ->limit(1)
            ->get('o_users')
            ->row_array();

        if (!$user) {
            return $this->failure('No account was found for that email address.');
        }

        return $this->queueForUser((string)$user['username']);
    }

    public function verifyToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{64}$/i', $token) || !$this->ensureTable()) {
            return $this->failure('This verification link is invalid. Please request a new one.');
        }

        $verification = $this->db
            ->where('token_hash', hash('sha256', $token))
            ->limit(1)
            ->get(self::TABLE)
            ->row_array();

        if (!$verification) {
            return $this->failure('This verification link is invalid. Please request a new one.');
        }

        $user = $this->db
            ->where('username', $verification['username'])
            ->limit(1)
            ->get('o_users')
            ->row_array();

        if (!$user) {
            return $this->failure('The account for this verification link no longer exists.');
        }

        if (strtolower(trim((string)($user['acctStat'] ?? ''))) === 'active') {
            $this->markUserTokensUsed((string)$verification['username']);
            return [
                'ok' => true,
                'already_verified' => true,
                'message' => 'Your email is already verified. You can sign in now.',
            ];
        }

        if (!empty($verification['used_at'])) {
            return $this->failure('This verification link has already been used. Please request a new one.');
        }

        if (strtotime((string)$verification['expires_at']) < time()) {
            return $this->failure('This verification link has expired. Please request a new one.');
        }

        if (strtolower(trim((string)($user['acctStat'] ?? ''))) !== strtolower(self::ACCOUNT_STATUS)) {
            return $this->failure('This account cannot be verified by email. Please contact support.');
        }

        if (strcasecmp(trim((string)$user['email']), trim((string)$verification['email'])) !== 0) {
            return $this->failure('The email address on this account has changed. Please request a new verification link.');
        }

        $this->db->trans_begin();
        $this->db
            ->where('username', $verification['username'])
            ->where('acctStat', self::ACCOUNT_STATUS)
            ->update('o_users', ['acctStat' => 'active']);

        $currentUser = $this->db
            ->select('acctStat')
            ->where('username', $verification['username'])
            ->limit(1)
            ->get('o_users')
            ->row_array();
        $activated = $currentUser
            && strtolower(trim((string)($currentUser['acctStat'] ?? ''))) === 'active';

        if ($activated) {
            $this->markUserTokensUsed((string)$verification['username']);
        }

        if (!$activated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->failure('Email verification could not be completed. Please try again.');
        }
        $this->db->trans_commit();

        return [
            'ok' => true,
            'already_verified' => false,
            'message' => 'Email verified successfully. You can now sign in.',
        ];
    }

    private function markUserTokensUsed($username)
    {
        return $this->db
            ->where('username', $username)
            ->where('used_at IS NULL', null, false)
            ->update(self::TABLE, ['used_at' => date('Y-m-d H:i:s')]);
    }

    private function failure($message)
    {
        return [
            'ok' => false,
            'already_verified' => false,
            'message' => (string)$message,
        ];
    }
}

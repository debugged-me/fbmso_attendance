<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| FBMSO Mail Queue
|--------------------------------------------------------------------------
| Web requests never talk to SMTP directly: they INSERT into `fbmso_email_queue`
| (auto-created on first use) and redirect instantly. The EmailQueue
| controller, run by cron every 2 minutes, delivers a small throttled batch
| so the host's outbound rate limit is never hammered and a tarpitted SMTP
| session can never hang a PHP worker. Rate-limited emails stay pending and
| are retried on a later run.
|
| Delivery order per message:
|   1. primary  - application/config/email.php SMTP account
|   2. fallback - Brevo relay from mass_announcement_email settings (DB row
|                 first, then application/config/mass_announcement_email.php)
|
| The sender address always comes from the resolved profile, so the various
| hardcoded no-reply@ addresses that used to fail SPF are no longer used.
*/

if (!function_exists('fbmso_mailqueue_ensure_table'))
{
    function fbmso_mailqueue_ensure_table($ci = null)
    {
        if ($ci === null) {
            $ci =& get_instance();
        }
        // NOTE: isset() on $ci->db is unreliable — CI_Model implements __get()
        // but no __isset(), so isset() is always FALSE when a model calls in.
        if (!is_object($ci->db)) {
            return false;
        }
        if ($ci->db->table_exists('fbmso_email_queue')) {
            return true;
        }

        $ci->db->query("
            CREATE TABLE IF NOT EXISTS `fbmso_email_queue` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `to_email` VARCHAR(255) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `body` MEDIUMTEXT NOT NULL,
                `school_name` VARCHAR(255) NOT NULL DEFAULT '',
                `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `last_error` VARCHAR(500) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                `sent_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status_created` (`status`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // table_exists() caches SHOW TABLES; reset so the fresh table is seen.
        $ci->db->data_cache = [];

        return $ci->db->table_exists('fbmso_email_queue');
    }
}

if (!function_exists('fbmso_mailqueue_push'))
{
    /**
     * Queue one HTML email. Returns TRUE once the row is committed — that is
     * the caller's signal that the message is durably owned by the queue.
     */
    function fbmso_mailqueue_push($ci, $toEmail, $subject, $htmlBody, $schoolName = '')
    {
        if ($ci === null) {
            $ci =& get_instance();
        }

        $toEmail = trim((string) $toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!fbmso_mailqueue_ensure_table($ci)) {
            return false;
        }

        return (bool) $ci->db->insert('fbmso_email_queue', [
            'to_email'    => $toEmail,
            'subject'     => mb_substr((string) $subject, 0, 255),
            'body'        => (string) $htmlBody,
            'school_name' => mb_substr(trim((string) $schoolName), 0, 255),
            'status'      => 'pending',
            'attempts'    => 0,
            'last_error'  => '',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}

if (!function_exists('fbmso_mailqueue_token'))
{
    function fbmso_mailqueue_token($ci = null)
    {
        if ($ci === null) {
            $ci =& get_instance();
        }
        $dbName = is_object($ci->db) ? (string) $ci->db->database : '';
        return substr(hash('sha256', 'fbmso-email-queue|' . (string) config_item('encryption_key') . '|' . $dbName), 0, 40);
    }
}

if (!function_exists('fbmso_mailqueue_suspend_file'))
{
    function fbmso_mailqueue_suspend_file()
    {
        return rtrim(sys_get_temp_dir(), '/\\') . '/fbmso_mail_suspend_' . md5(APPPATH) . '.flag';
    }
}

if (!function_exists('fbmso_mailqueue_suspended'))
{
    function fbmso_mailqueue_suspended()
    {
        $file = fbmso_mailqueue_suspend_file();
        if (!is_file($file)) {
            return false;
        }
        $until = (int) @file_get_contents($file);
        if ($until > time()) {
            return true;
        }
        @unlink($file);
        return false;
    }
}

if (!function_exists('fbmso_mailqueue_suspend'))
{
    function fbmso_mailqueue_suspend($minutes = 15)
    {
        @file_put_contents(fbmso_mailqueue_suspend_file(), (string) (time() + ($minutes * 60)));
    }
}

if (!function_exists('fbmso_mailqueue_is_rate_limited'))
{
    // Transient provider-side failures (rate limit / tarpit / timeout):
    // these must NOT count against the email's retry attempts.
    function fbmso_mailqueue_is_rate_limited($result)
    {
        $r = strtolower((string) $result);

        // 421 / 451 must look like an SMTP reply code, not a fragment of a
        // version string or a size advertised in the EHLO banner.
        if (preg_match('/(?<![\d.-])4(?:21|51)(?![\d.])/', $r)) {
            return true;
        }

        return strpos($r, 'ratelimit') !== false
            || strpos($r, 'rate limit') !== false
            || strpos($r, 'too many') !== false
            || strpos($r, 'try again later') !== false
            || strpos($r, 'timed out') !== false
            || strpos($r, 'timeout') !== false;
    }
}

if (!function_exists('fbmso_mailqueue_school_name'))
{
    function fbmso_mailqueue_school_name($ci = null)
    {
        if ($ci === null) {
            $ci =& get_instance();
        }
        if (!is_object($ci->db)) {
            return 'School Records Management System';
        }

        $row = $ci->db->select('SchoolName')->limit(1)->get('o_srms_settings')->row();

        return !empty($row->SchoolName) ? (string) $row->SchoolName : 'School Records Management System';
    }
}

if (!function_exists('fbmso_mailqueue_primary_profile'))
{
    function fbmso_mailqueue_primary_profile($ci, $schoolName = '')
    {
        $schoolName = trim((string) $schoolName);
        if ($schoolName === '') {
            $schoolName = fbmso_mailqueue_school_name($ci);
        }

        $ci->load->config('email');

        $mailConfig = [
            'protocol'     => (string) ($ci->config->item('protocol') ?: 'smtp'),
            'smtp_host'    => (string) $ci->config->item('smtp_host'),
            'smtp_user'    => (string) $ci->config->item('smtp_user'),
            'smtp_pass'    => (string) $ci->config->item('smtp_pass'),
            'smtp_port'    => (int) ($ci->config->item('smtp_port') ?: 587),
            'smtp_crypto'  => (string) $ci->config->item('smtp_crypto'),
            'smtp_timeout' => (int) ($ci->config->item('smtp_timeout') ?: 10),
            'mailtype'     => 'html',
            'charset'      => (string) ($ci->config->item('charset') ?: 'utf-8'),
            'newline'      => "\r\n",
            'crlf'         => "\r\n",
            'wordwrap'     => $ci->config->item('wordwrap') === null ? true : (bool) $ci->config->item('wordwrap'),
        ];

        return [
            'source'      => 'system_email',
            'mail_config' => $mailConfig,
            'from_email'  => trim((string) $mailConfig['smtp_user']),
            'from_name'   => $schoolName,
        ];
    }
}

if (!function_exists('fbmso_mailqueue_fallback_profile'))
{
    /**
     * Brevo relay used by the mass-announcement feature, reused here as the
     * second delivery attempt. Returns NULL when it is unconfigured or is the
     * same account as the primary profile.
     */
    function fbmso_mailqueue_fallback_profile($ci, $schoolName = '')
    {
        $primaryProfile = fbmso_mailqueue_primary_profile($ci, $schoolName);
        $defaultTimeout = (int) ($primaryProfile['mail_config']['smtp_timeout'] ?? 10);
        if ($defaultTimeout <= 0) {
            $defaultTimeout = 10;
        }

        $section = 'mass_announcement_email';
        $ci->load->config('mass_announcement_email', true);
        $configDefaults = (array) $ci->config->item('mass_announcement_email', $section);

        $ci->load->model('SettingsModel');
        $dbSettings = $ci->SettingsModel->getMassAnnouncementEmailSettings();
        $dbSettings = $dbSettings ? (array) $dbSettings : [];

        $smtpHost = trim((string) ($dbSettings['smtp_host'] ?? ($configDefaults['smtp_host'] ?? '')));
        $smtpUser = trim((string) ($dbSettings['smtp_user'] ?? ($configDefaults['smtp_user'] ?? '')));
        $smtpPass = trim((string) ($dbSettings['smtp_pass'] ?? ($configDefaults['smtp_pass'] ?? '')));

        if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
            return null;
        }

        $mailConfig = [
            'protocol'     => 'smtp',
            'smtp_host'    => $smtpHost,
            'smtp_user'    => $smtpUser,
            'smtp_pass'    => $smtpPass,
            'smtp_port'    => (int) ($dbSettings['smtp_port'] ?? ($configDefaults['smtp_port'] ?? 587)) ?: 587,
            'smtp_crypto'  => trim((string) ($dbSettings['smtp_crypto'] ?? ($configDefaults['smtp_crypto'] ?? 'tls'))),
            'smtp_timeout' => $defaultTimeout,
            'mailtype'     => 'html',
            'charset'      => 'utf-8',
            'newline'      => "\r\n",
            'crlf'         => "\r\n",
            'wordwrap'     => true,
        ];

        $senderEmail = trim((string) ($dbSettings['sender_email'] ?? $ci->config->item('mass_announcement_sender_email', $section) ?? ''));
        $senderName  = trim((string) ($dbSettings['sender_name'] ?? $ci->config->item('mass_announcement_sender_name', $section) ?? ''));

        $primaryConfig = (array) $primaryProfile['mail_config'];
        $isSameConfig = (
            strcasecmp(trim((string) ($primaryConfig['smtp_host'] ?? '')), $smtpHost) === 0
            && strcasecmp(trim((string) ($primaryConfig['smtp_user'] ?? '')), $smtpUser) === 0
            && (int) ($primaryConfig['smtp_port'] ?? 0) === (int) $mailConfig['smtp_port']
            && strtolower(trim((string) ($primaryConfig['smtp_crypto'] ?? ''))) === strtolower(trim((string) $mailConfig['smtp_crypto']))
        );

        if ($isSameConfig) {
            return null;
        }

        return [
            'source'      => 'brevo_relay_fallback',
            'mail_config' => $mailConfig,
            'from_email'  => $senderEmail !== '' ? $senderEmail : $smtpUser,
            'from_name'   => $senderName !== '' ? $senderName : (string) $primaryProfile['from_name'],
        ];
    }
}

if (!function_exists('fbmso_mailqueue_deliver'))
{
    function fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, array $mailProfile, $schoolName = '')
    {
        $mailConfig = (array) ($mailProfile['mail_config'] ?? []);
        $fromEmail  = trim((string) ($mailProfile['from_email'] ?? ''));
        $fromName   = trim((string) ($mailProfile['from_name'] ?? ''));
        $source     = trim((string) ($mailProfile['source'] ?? 'mail'));

        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return [false, $source . ': missing_sender_email'];
        }
        if (empty($mailConfig)) {
            return [false, $source . ': missing_mail_config'];
        }

        // Load MY_Email (subclass) so we can force-close any stale SMTP socket
        // before re-initializing with a different profile. Without this, a
        // failed AUTH on profile A leaves its socket open and profile B's
        // credentials get sent to profile A's server -> "535 5.7.8".
        $ci->load->library('email');
        if (method_exists($ci->email, 'disconnect')) {
            $ci->email->disconnect();
        }
        $ci->email->clear(true);
        $ci->email->initialize($mailConfig);

        if (method_exists($ci->email, 'set_mailtype')) {
            $ci->email->set_mailtype('html');
        }
        if (method_exists($ci->email, 'set_newline')) {
            $ci->email->set_newline("\r\n");
        }
        if (method_exists($ci->email, 'set_crlf')) {
            $ci->email->set_crlf("\r\n");
        }

        $ci->email->from($fromEmail, $fromName !== '' ? $fromName : $schoolName);
        if (method_exists($ci->email, 'reply_to')) {
            $ci->email->reply_to($fromEmail, $fromName !== '' ? $fromName : $schoolName);
        }
        $ci->email->to($toEmail);
        $ci->email->subject((string) $subject);
        $ci->email->message((string) $htmlBody);

        if ((bool) $ci->email->send(false)) {
            return [true, $source];
        }

        $debug = '';
        if (method_exists($ci->email, 'print_debugger')) {
            // Empty $include => the SMTP conversation only, no header/body dump.
            $debug = trim(strip_tags((string) $ci->email->print_debugger([])));
            // Keep the tail: the greeting banner is noise, the rejection is last.
            $debug = preg_replace('/\s+/', ' ', $debug);
            if (mb_strlen($debug) > 200) {
                $debug = '...' . mb_substr($debug, -200);
            }
        }

        return [false, $source . ($debug !== '' ? ': ' . $debug : '')];
    }
}

if (!function_exists('fbmso_mailqueue_send_now'))
{
    // Primary sender, then Brevo relay fallback. Returns [sent, resultText, isRateLimited].
    function fbmso_mailqueue_send_now($ci, $toEmail, $subject, $htmlBody, $schoolName = '')
    {
        $primaryProfile = fbmso_mailqueue_primary_profile($ci, $schoolName);
        list($sent, $result) = fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, $primaryProfile, $schoolName);
        if ($sent) {
            return [true, $result, false];
        }

        $fallbackProfile = fbmso_mailqueue_fallback_profile($ci, $schoolName);
        if ($fallbackProfile) {
            list($fbSent, $fbResult) = fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, $fallbackProfile, $schoolName);
            if ($fbSent) {
                return [true, $fbResult, false];
            }
            $result .= ' | fallback: ' . $fbResult;
        }

        return [false, $result, fbmso_mailqueue_is_rate_limited($result)];
    }
}

if (!function_exists('fbmso_mailqueue_process'))
{
    function fbmso_mailqueue_process($ci = null, $batchSize = 5, $spacingSeconds = 2, $maxAttempts = 10)
    {
        if ($ci === null) {
            $ci =& get_instance();
        }

        if (!fbmso_mailqueue_ensure_table($ci)) {
            return ['status' => 'error', 'message' => 'email_queue table unavailable'];
        }

        if (fbmso_mailqueue_suspended()) {
            return ['status' => 'cooldown', 'message' => 'mail suspended, retrying on a later run'];
        }

        // One runner at a time; the lock auto-releases if PHP dies mid-run.
        $lockName = 'fbmsomailq_' . md5((string) $ci->db->database);
        $lockRes  = $ci->db->query('SELECT GET_LOCK(' . $ci->db->escape($lockName) . ', 0) AS l');
        $lockRow  = $lockRes ? $lockRes->row() : null;
        if (!$lockRow || (int) $lockRow->l !== 1) {
            return ['status' => 'locked', 'message' => 'another run in progress'];
        }

        // Housekeeping: drop delivered rows after 30 days.
        $ci->db->where('status', 'sent')
            ->where('sent_at <', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->delete('fbmso_email_queue');

        $rows = $ci->db->from('fbmso_email_queue')
            ->where('status', 'pending')
            ->where('attempts <', (int) $maxAttempts)
            ->order_by('id', 'ASC')
            ->limit(max(1, (int) $batchSize))
            ->get()->result();

        $summary = ['status' => 'ok', 'picked' => count($rows), 'sent' => 0, 'failed' => 0, 'deferred' => 0];

        foreach ($rows as $i => $row) {
            if ($i > 0 && $spacingSeconds > 0) {
                sleep((int) $spacingSeconds);
            }

            list($sent, $result, $rateLimited) = fbmso_mailqueue_send_now(
                $ci,
                (string) $row->to_email,
                (string) $row->subject,
                (string) $row->body,
                (string) $row->school_name
            );

            if ($sent) {
                $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                    'status'     => 'sent',
                    'sent_at'    => date('Y-m-d H:i:s'),
                    'last_error' => '',
                ]);
                $summary['sent']++;
                continue;
            }

            if ($rateLimited) {
                // Provider throttling: keep pending (no attempts bump), stop the
                // batch, and pause all senders for a cooldown window.
                $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                    'last_error' => mb_substr($result, 0, 500),
                ]);
                fbmso_mailqueue_suspend();
                log_message('error', 'Mail queue: provider rate-limit detected, cooling down. ' . mb_substr($result, 0, 200));
                $summary['deferred'] = count($rows) - $i;
                break;
            }

            $attempts = (int) $row->attempts + 1;
            $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                'attempts'   => $attempts,
                'status'     => ($attempts >= (int) $maxAttempts) ? 'failed' : 'pending',
                'last_error' => mb_substr($result, 0, 500),
            ]);
            $summary['failed']++;
            log_message('error', 'Mail queue: send failed id=' . (int) $row->id . ' to=' . $row->to_email . ' attempt=' . $attempts . ' reason=' . mb_substr($result, 0, 200));
        }

        $ci->db->query('SELECT RELEASE_LOCK(' . $ci->db->escape($lockName) . ')');

        return $summary;
    }
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');


if (!function_exists('fbmso_mailqueue_ensure_table'))
{
    function fbmso_mailqueue_ensure_table($ci = null)
    {
        if ($ci === null) {
            $ci =& get_instance();
        }
       
        if (!is_object($ci->db)) {
            return false;
        }
        if ($ci->db->table_exists('fbmso_email_queue')) {
            // Add the attachment column to pre-existing tables (used to
            // deliver an inline image as a real attachment).
            if (!$ci->db->field_exists('attachment_path', 'fbmso_email_queue')) {
                $ci->db->query("ALTER TABLE `fbmso_email_queue` ADD COLUMN `attachment_path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `school_name`");
                $ci->db->data_cache = [];
            }
            return true;
        }

        $ci->db->query("
            CREATE TABLE IF NOT EXISTS `fbmso_email_queue` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `to_email` VARCHAR(255) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `body` MEDIUMTEXT NOT NULL,
                `school_name` VARCHAR(255) NOT NULL DEFAULT '',
                `attachment_path` VARCHAR(255) NOT NULL DEFAULT '',
                `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `last_error` VARCHAR(500) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                `sent_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status_created` (`status`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $ci->db->data_cache = [];

        return $ci->db->table_exists('fbmso_email_queue');
    }
}

if (!function_exists('fbmso_mailqueue_push'))
    {
        function fbmso_mailqueue_push(
            $ci,
            $toEmail,
            $subject,
            $htmlBody,
            $schoolName = '',
            $attachmentPath = ''
        )
        {
            if ($ci === null) {
                $ci =& get_instance();
            }

            $toEmail = trim((string)$toEmail);

            // Don't queue addresses that can never receive mail (typos,
            // .local, ...): each bounce counts against the sending domain's
            // hourly failure limit and blocks mail for everyone else.
            $invalidReason = fbmso_mailqueue_validate_recipient($toEmail, false);
            if ($invalidReason !== null) {
                if ($toEmail !== '') {
                    log_message('error', 'Mail queue: not queued to=' . $toEmail . ' reason=' . $invalidReason . ' subject=' . mb_substr((string)$subject, 0, 80));
                }
                return false;
            }
    
            if (!fbmso_mailqueue_ensure_table($ci)) {
                return false;
            }
    
            return (bool)$ci->db->insert(
                'fbmso_email_queue',
                [
                    'to_email'    => $toEmail,
                    'subject'     => mb_substr((string)$subject, 0, 255),
                    'body'        => (string)$htmlBody,
                    'school_name' => mb_substr(
                        trim((string)$schoolName),
                        0,
                        255
                    ),
                    'attachment_path' => mb_substr(trim((string)$attachmentPath), 0, 255),
                    'status'      => 'pending',
                    'attempts'    => 0,
                    'last_error'  => '',
                    'created_at'  => date('Y-m-d H:i:s'),
                ]
            );
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
    // File holds "untilTimestamp|reason"; readers (int)-cast the timestamp.
    function fbmso_mailqueue_suspend($minutes = 15, $reason = '')
    {
        @file_put_contents(fbmso_mailqueue_suspend_file(), (time() + ($minutes * 60)) . '|' . $reason);
    }
}

if (!function_exists('fbmso_mailqueue_is_rate_limited'))
{
    
    function fbmso_mailqueue_is_rate_limited($result)
    {
        $r = strtolower((string) $result);

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
    function fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, array $mailProfile, $schoolName = '', $attachmentPath = '')
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

        // Inline image attachment: attach the file and swap the CID
        // placeholder in the body for the real Content-ID so it renders
        // inline (data: URIs are stripped by Gmail and most webmail).
        $body = (string) $htmlBody;
        $attachmentPath = trim((string) $attachmentPath);
        if ($attachmentPath !== '' && is_file($attachmentPath) && method_exists($ci->email, 'attach')) {
            if (strpos($body, '__INLINE_IMAGE_CID__') !== false) {
                // Legacy inline-image use: CID-embed so the image renders in place.
                $ci->email->attach($attachmentPath, 'inline');
                $cid = method_exists($ci->email, 'attachment_cid') ? $ci->email->attachment_cid($attachmentPath) : '';
                if ($cid) {
                    $body = str_replace('__INLINE_IMAGE_CID__', $cid, $body);
                }
            } else {
                // Ordinary file attachment (e.g. a .sql.gz backup): proper
                // attachment disposition so mail clients show it as a file.
                $ci->email->attach($attachmentPath);
            }
        }
        // Clean up any placeholder that wasn't replaced (missing file).
        $body = str_replace('cid:__INLINE_IMAGE_CID__', '', $body);
        $ci->email->message($body);

        if ((bool) $ci->email->send(false)) {
            return [true, $source, ''];
        }

        $debug = '';
        $kind  = '';
        if (method_exists($ci->email, 'print_debugger')) {
            // Empty $include => the SMTP conversation only, no header/body dump.
            $debug = trim(strip_tags((string) $ci->email->print_debugger([])));
            // Classify on the full text; the stored tail below is truncated.
            $kind  = fbmso_mailqueue_classify_failure($debug);
            // Keep the tail: the greeting banner is noise, the rejection is last.
            $debug = preg_replace('/\s+/', ' ', $debug);
            // Drop the QUIT reply and CI's generic footer so the tail keeps
            // the server's actual rejection instead of boilerplate.
            $debug = preg_replace('/quit: 221 .*?closing connection/i', '', $debug);
            $debug = trim(str_replace('Unable to send email using PHP SMTP. Your server might not be configured to send mail using this method.', '', $debug));
            if (mb_strlen($debug) > 200) {
                $debug = '...' . mb_substr($debug, -200);
            }
        }

        return [false, $source . ($debug !== '' ? ': ' . $debug : ''), $kind];
    }
}

if (!function_exists('fbmso_mailqueue_typo_domain'))
{
    /**
     * Returns the provider a misspelled free-mail domain was meant to be
     * (gamail.com, gmail.con, gakil.com -> gmail.com), or null.
     *
     * A fixed typo list can't keep up, and DNS doesn't help: typo-squatters
     * register gamil.com, gmai.com, gnail.com... with working MX records, so
     * mail is "accepted" and then bounces, counting against the sending
     * domain's hourly failure limit on the host.
     */
    function fbmso_mailqueue_typo_domain($domain)
    {
        $domain = strtolower(trim((string) $domain));
        $dot = strpos($domain, '.');
        if ($dot === false) {
            return null;
        }
        $label  = substr($domain, 0, $dot);
        $suffix = substr($domain, $dot + 1);

        $providers = ['gmail', 'yahoo', 'hotmail', 'outlook', 'icloud'];
        // Only "<name>.com"-shaped domains are compared, so subdomains and
        // country domains (yahoo.com.ph, yahoo.ca, mail.dorsu.edu.ph) pass.
        $comLike = levenshtein($suffix, 'com') <= 1 || in_array($suffix, ['ocm', 'cmo', 'coom'], true);

        if (in_array($label, $providers, true)) {
            if ($suffix === 'com') {
                return null;
            }
            // Gmail has no country domains, so anything but gmail.com is a typo.
            return ($label === 'gmail' || $comLike) ? $label . '.com' : null;
        }

        // Real providers that happen to sit one letter away from the ones above.
        $realLookalikes = ['mail', 'email', 'ymail', 'cloud'];
        if (!$comLike || in_array($label, $realLookalikes, true)) {
            return null;
        }

        foreach ($providers as $provider) {
            if (levenshtein($label, $provider) <= 2) {
                return $provider . '.com';
            }
        }

        return null;
    }
}

if (!function_exists('fbmso_mailqueue_domain_has_mx'))
{
    /**
     * true when the domain publishes a usable MX host, false when it does not
     * (none, null MX ".", or "localhost" as parked typo domains use), null when
     * DNS itself is down — so a resolver hiccup can't fail the whole queue.
     */
    function fbmso_mailqueue_domain_has_mx($domain)
    {
        static $cache = [];
        static $dnsWorks = null;

        if (!function_exists('getmxrr')) {
            return null;
        }
        if (array_key_exists($domain, $cache)) {
            return $cache[$domain];
        }

        $hosts = [];
        @getmxrr($domain, $hosts);
        foreach ($hosts as $host) {
            $host = strtolower(rtrim(trim((string) $host), '.'));
            if ($host !== '' && $host !== 'localhost' && strpos($host, '127.') !== 0 && $host !== '0.0.0.0') {
                return $cache[$domain] = true;
            }
        }

        if ($dnsWorks === null) {
            $probe = [];
            $dnsWorks = @getmxrr('gmail.com', $probe) && !empty($probe);
        }

        return $cache[$domain] = ($dnsWorks ? false : null);
    }
}

if (!function_exists('fbmso_mailqueue_validate_recipient'))
{
    /**
     * Validate a recipient email address before attempting SMTP.
     * Returns null if valid, or a string explaining why it was rejected.
     *
     * Catches:
     * - Missing @ or domain
     * - Fake/local domains (.local, .localhost, .test, .example, .invalid)
     * - Misspelled free-mail domains (gamail.com, gmail.con, gakil.com, ...)
     * - Domains with no usable MX record (only when $checkDns)
     *
     * $checkDns is off at enqueue time so saving a payment or a login never
     * waits on DNS; the cron re-validates with DNS before sending.
     */
    function fbmso_mailqueue_validate_recipient($email, $checkDns = true)
    {
        $email = trim(strtolower((string) $email));

        if ($email === '') {
            return 'empty address';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'malformed address';
        }

        $domain = substr($email, strrpos($email, '@') + 1);
        if ($domain === '' || $domain === false || strpos($domain, '.') === false) {
            return 'missing domain';
        }

        // Reserved/internal names that can never receive mail
        $fakeTlds = array('.local', '.localhost', '.localdomain', '.test', '.example', '.invalid', '.internal', '.lan', '.home', '.corp');
        foreach ($fakeTlds as $tld) {
            if (substr($domain, -strlen($tld)) === $tld) {
                return 'fake/local domain (' . $domain . ')';
            }
        }
        if (in_array($domain, array('example.com', 'example.net', 'example.org'), true)) {
            return 'fake/local domain (' . $domain . ')';
        }

        $meant = fbmso_mailqueue_typo_domain($domain);
        if ($meant !== null) {
            return 'likely typo (' . $domain . ' → ' . $meant . ')';
        }

        if ($checkDns && fbmso_mailqueue_domain_has_mx($domain) === false) {
            return 'no mail server for domain (' . $domain . ')';
        }

        return null;
    }
}

if (!function_exists('fbmso_mailqueue_classify_failure'))
{
    /**
     * Classify an SMTP failure from the full debugger text:
     *  - 'domain_limit'  cPanel's per-hour defer/failure cap for the whole
     *                    sending domain; everything is discarded until it clears
     *  - 'throttled'     provider rate limit / transient error
     *  - 'bad_recipient' RCPT TO refused with 5xx — the address can't receive
     *  - ''              anything else
     */
    function fbmso_mailqueue_classify_failure($text)
    {
        $t = strtolower((string) $text);

        if (strpos($t, 'max defers') !== false || strpos($t, 'defers and failures') !== false) {
            return 'domain_limit';
        }
        if (fbmso_mailqueue_is_rate_limited($t)) {
            return 'throttled';
        }
        if (preg_match('/(?:^|[\s>])to:\s*5\d\d/', $t)
            && !preg_match('/relay|authenticat|sender verify|sender address/', $t)) {
            return 'bad_recipient';
        }

        return '';
    }
}

if (!function_exists('fbmso_mailqueue_send_now'))
{
    // Primary sender, then Brevo relay fallback.
    // Returns [sent, resultText, failureKind] — see fbmso_mailqueue_classify_failure().
    function fbmso_mailqueue_send_now($ci, $toEmail, $subject, $htmlBody, $schoolName = '', $attachmentPath = '')
    {
        $primaryProfile = fbmso_mailqueue_primary_profile($ci, $schoolName);
        list($sent, $result, $kind) = fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, $primaryProfile, $schoolName, $attachmentPath);
        if ($sent) {
            return [true, $result, ''];
        }

        // The recipient was refused outright; the relay would only bounce it too.
        if ($kind === 'bad_recipient') {
            return [false, $result, $kind];
        }

        $fallbackProfile = fbmso_mailqueue_fallback_profile($ci, $schoolName);
        if ($fallbackProfile) {
            list($fbSent, $fbResult, $fbKind) = fbmso_mailqueue_deliver($ci, $toEmail, $subject, $htmlBody, $fallbackProfile, $schoolName, $attachmentPath);
            if ($fbSent) {
                return [true, $fbResult, ''];
            }
            $result .= ' | fallback: ' . $fbResult;

            foreach (['domain_limit', 'throttled', 'bad_recipient'] as $severity) {
                if ($kind === $severity || $fbKind === $severity) {
                    $kind = $severity;
                    break;
                }
            }
        }

        return [false, $result, $kind];
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

        // Housekeeping: drop delivered rows after 7 days to keep the
        // queue table small. Sent emails are only useful for debugging
        // delivery issues — keeping them for a month wastes space.
        $ci->db->where('status', 'sent')
            ->where('sent_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->delete('fbmso_email_queue');

        // Also clean up failed emails older than 7 days — they've been
        // retried already and just take up space.
        $ci->db->where('status', 'failed')
            ->where('created_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->delete('fbmso_email_queue');

        $rows = $ci->db->from('fbmso_email_queue')
            ->where('status', 'pending')
            ->where('attempts <', (int) $maxAttempts)
            ->order_by('id', 'ASC')
            ->limit(max(1, (int) $batchSize))
            ->get()->result();

        $summary = ['status' => 'ok', 'picked' => count($rows), 'sent' => 0, 'failed' => 0, 'deferred' => 0, 'skipped' => 0];

        foreach ($rows as $i => $row) {
            // Skip invalid email addresses immediately — every bounce counts
            // against the sending domain's hourly failure limit on the host.
            // attempts stays as-is: nothing was sent, and status alone keeps
            // the row out of the pending pick.
            $invalidReason = fbmso_mailqueue_validate_recipient((string) $row->to_email);
            if ($invalidReason !== null) {
                $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                    'status'     => 'failed',
                    'last_error' => 'Skipped: ' . $invalidReason,
                ]);
                $summary['skipped']++;
                log_message('debug', 'Mail queue: skipped invalid recipient id=' . (int) $row->id . ' to=' . $row->to_email . ' reason=' . $invalidReason);
                continue;
            }
            if ($i > 0 && $spacingSeconds > 0) {
                sleep((int) $spacingSeconds);
            }

            list($sent, $result, $failureKind) = fbmso_mailqueue_send_now(
                $ci,
                (string) $row->to_email,
                (string) $row->subject,
                (string) $row->body,
                (string) $row->school_name,
                (string) ($row->attachment_path ?? '')
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

            if ($failureKind === 'throttled' || $failureKind === 'domain_limit') {
                // Provider throttling: keep pending (no attempts bump), stop the
                // batch, and pause all senders for a cooldown window. The host's
                // defer/failure cap is counted per hour, so sending again any
                // sooner only adds failures and keeps the domain blocked.
                $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                    'last_error' => mb_substr($result, 0, 500),
                ]);
                if ($failureKind === 'domain_limit') {
                    fbmso_mailqueue_suspend(60, 'The mail host hit its hourly limit of failed deliveries for the sending domain.');
                } else {
                    fbmso_mailqueue_suspend(15, 'A send failed with a transient/rate-limit error.');
                }
                log_message('error', 'Mail queue: provider rate-limit detected (' . $failureKind . '), cooling down. ' . mb_substr($result, 0, 200));
                $summary['deferred'] = count($rows) - $i;
                break;
            }

            if ($failureKind === 'bad_recipient') {
                // The server refused this address; retrying can't succeed and
                // each try is another failure on the domain's hourly limit.
                $ci->db->where('id', (int) $row->id)->update('fbmso_email_queue', [
                    'attempts'   => (int) $row->attempts + 1,
                    'status'     => 'failed',
                    'last_error' => mb_substr('Rejected: ' . $result, 0, 500),
                ]);
                $summary['failed']++;
                log_message('error', 'Mail queue: recipient rejected id=' . (int) $row->id . ' to=' . $row->to_email . ' reason=' . mb_substr($result, 0, 200));
                continue;
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

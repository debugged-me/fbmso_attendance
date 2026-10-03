<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Backup — download a full database dump straight from the app.
 *
 * index()    — backup page: database info + download button.
 * download() — generates a phpMyAdmin-compatible .sql file and sends it
 *              as an attachment. The dump is written to a temp file first
 *              (streams, flat memory), then readfile()'d, so the download
 *              only starts once the file is complete and its size is known.
 *
 * Super Admin only. The dump contains every record including password
 * hashes — the AuthGuard rule and this controller check are deliberate
 * duplication, matching how security/* is protected.
 */
class Backup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->model('AuditLogModel');
        $this->load->model('SettingsModel'); // used by top-nav-bar
        $this->load->library('DbBackup');
    }

    /**
     * Second layer behind AuthGuard's 'backup/*' role rule — both are
     * deliberate, same convention as FbmsoPersonnels and Security. Kept in
     * the methods (not the constructor) so the guard gets first shot at
     * redirecting guests and logging denied-role attempts.
     */
    private function requireSuperAdmin()
    {
        if (is_cli()) {
            return;
        }
        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login');
        }
        if ((string)$this->session->userdata('level') !== 'Super Admin') {
            show_error('Access Denied — Super Admin only.', 403);
        }
    }

    /**
     * Mutating endpoints must be POST. CI3 CSRF only guards POST requests —
     * without this, a GET (e.g. an <img> tag on another page the admin opens)
     * would run these without any token check.
     */
    private function requirePost()
    {
        if (strtolower((string)$this->input->method()) !== 'post') {
            show_error('Method not allowed', 405);
        }
    }

    /**
     * Shared token for the cron URL — same construction as
     * Securitycheck::token(), different namespace so the tokens can never be
     * replayed against each other. Set 'backup_cron_token' in config.php to
     * pin a fixed value across environments.
     */
    public static function token($ci = null)
    {
        $configured = trim((string)config_item('backup_cron_token'));
        if ($configured !== '') {
            return $configured;
        }
        if ($ci === null) {
            $ci = &get_instance();
        }
        $dbName = is_object($ci->db) ? (string)$ci->db->database : '';
        return substr(hash('sha256', 'fbmso-backup-cron|' . (string)config_item('encryption_key') . '|' . $dbName), 0, 40);
    }

    public function index()
    {
        $this->requireSuperAdmin();
        $this->ensureTables();

        $data['stats']     = $this->dbbackup->stats();
        $data['settings']  = $this->getSettings();
        $data['runs']      = $this->db->order_by('id', 'DESC')->limit(10)->get('backup_runs')->result_array();
        foreach ($data['runs'] as &$r) {
            $r['local_exists'] = $r['filename'] !== ''
                && is_file($this->backupDir() . DIRECTORY_SEPARATOR . basename($r['filename']));
        }
        unset($r);
        $data['cron_url']  = site_url('backup/cron') . '?key=' . self::token($this);
        $data['cron_line'] = '* * * * * curl -s "' . $data['cron_url'] . '" > /dev/null 2>&1';
        $this->load->view('backup_index', $data);
    }

    public function download()
    {
        $this->requireSuperAdmin();

        @set_time_limit(0);

        // Hold the session lock for as short a time as possible — a large
        // dump can take a while and the admin's other tabs shouldn't stall.
        $username = (string)$this->session->userdata('username');
        $db = (string)$this->db->database;
        session_write_close();

        $tmp = tempnam(sys_get_temp_dir(), 'fbmso_dump_');
        if ($tmp === false) {
            show_error('Could not create a temporary file for the backup.', 500);
            return;
        }
        // Always clean up the temp file, even if the connection dies
        // mid-download or PHP fatals partway through.
        register_shutdown_function(static function () use ($tmp) {
            @unlink($tmp);
        });

        try {
            $stats = $this->dbbackup->dumpTo($tmp);
        } catch (Exception $e) {
            try {
                $this->AuditLogModel->write(
                    'export', 'Database Backup', null, null, null,
                    array('database' => $db, 'error' => $e->getMessage()),
                    0, 'Database backup failed',
                    array('_actor_username' => $username)
                );
            } catch (Throwable $ignored) {
            }
            show_error('Database backup failed: ' . $e->getMessage(), 500);
            return;
        }

        $filename = $db . '_' . date('Y-m-d_H-i-s') . '.sql';

        try {
            $this->AuditLogModel->write(
                'export', 'Database Backup', null, null, null,
                array(
                    'database' => $db,
                    'file'     => $filename,
                    'tables'   => $stats['tables'],
                    'rows'     => $stats['rows'],
                    'bytes'    => $stats['bytes'],
                    'seconds'  => $stats['seconds'],
                ),
                1, 'Downloaded database backup',
                array('_actor_username' => $username)
            );
        } catch (Throwable $ignored) {
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($tmp);
        exit;
    }

    // ------------------------------------------------------------------
    // Automation
    // ------------------------------------------------------------------

    /**
     * Where scheduled backups live. The .htaccess + index.html keep the
     * dumps out of the web root's reach — the files hold password hashes
     * and session data, so a guessable URL must not serve them.
     */
    private function backupDir()
    {
        $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        // The dir can be created by CLI (local user) and later written by
        // Apache (daemon), or vice versa — keep it world-writable like the
        // parent upload/ dir; the .htaccess below guards web access.
        if (is_dir($dir) && !is_writable($dir)) {
            @chmod($dir, 0777);
        }
        $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\nDeny from all\n");
        }
        $index = $dir . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($index)) {
            @file_put_contents($index, '');
        }
        return $dir;
    }

    /** Idempotent DDL so the CLI cron works even before the migrator runs. */
    private function ensureTables()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `backup_settings` (
                `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `auto_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `backup_time` VARCHAR(5) NOT NULL DEFAULT '22:30',
                `email_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `email_time` VARCHAR(5) NOT NULL DEFAULT '23:00',
                `email_recipients` TEXT NULL,
                `attach_max_mb` SMALLINT UNSIGNED NOT NULL DEFAULT 15,
                `drive_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `drive_folder_id` VARCHAR(128) NOT NULL DEFAULT '',
                `drive_sa_json` MEDIUMTEXT NULL,
                `drive_client_id` VARCHAR(255) NOT NULL DEFAULT '',
                `drive_client_secret` VARCHAR(255) NOT NULL DEFAULT '',
                `drive_refresh_token` VARCHAR(512) NOT NULL DEFAULT '',
                `keep_days` SMALLINT UNSIGNED NOT NULL DEFAULT 14,
                `keep_local` TINYINT(1) NOT NULL DEFAULT 0,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // Older installs of the table predate the OAuth columns.
        foreach (array(
            'drive_client_id'     => "VARCHAR(255) NOT NULL DEFAULT ''",
            'drive_client_secret' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'drive_refresh_token' => "VARCHAR(512) NOT NULL DEFAULT ''",
            'keep_local'          => "TINYINT(1) NOT NULL DEFAULT 0",
        ) as $col => $type) {
            $exists = $this->db->query(
                "SHOW COLUMNS FROM `backup_settings` LIKE " . $this->db->escape($col)
            )->row();
            if (!$exists) {
                $this->db->query("ALTER TABLE `backup_settings` ADD COLUMN `{$col}` {$type}");
            }
        }
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `backup_runs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `filename` VARCHAR(255) NOT NULL DEFAULT '',
                `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `tables_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `rows_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `duration_sec` DECIMAL(8,2) NOT NULL DEFAULT 0,
                `status` ENUM('ok','failed') NOT NULL DEFAULT 'ok',
                `triggered_by` ENUM('cron','run_now') NOT NULL DEFAULT 'cron',
                `emailed_at` DATETIME NULL,
                `drive_file_id` VARCHAR(128) NOT NULL DEFAULT '',
                `drive_link` VARCHAR(512) NOT NULL DEFAULT '',
                `error` VARCHAR(500) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_created` (`created_at`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function getSettings()
    {
        $defaults = array(
            'auto_enabled'     => 0,
            'backup_time'      => '22:30',
            'email_enabled'    => 0,
            'email_time'       => '23:00',
            'email_recipients' => '',
            'attach_max_mb'    => 15,
            'drive_enabled'    => 0,
            'drive_folder_id'  => '',
            'drive_sa_json'    => '',
            'drive_client_id'     => '',
            'drive_client_secret' => '',
            'drive_refresh_token' => '',
            'keep_days'        => 14,
            'keep_local'       => 0,
        );
        $row = $this->db->limit(1)->get('backup_settings')->row_array();
        return $row ? array_merge($defaults, $row) : $defaults;
    }

    /** POST — save automation settings. */
    public function settings()
    {
        $this->requireSuperAdmin();
        $this->requirePost();
        $this->ensureTables();

        $time = static function ($v, $fallback) {
            $v = trim((string)$v);
            return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : $fallback;
        };

        // Normalize recipients: accept commas, semicolons, newlines.
        $recipients = preg_split('/[\s,;]+/', (string)$this->input->post('email_recipients', true), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = array_values(array_filter(array_map('trim', $recipients), function ($e) {
            return filter_var($e, FILTER_VALIDATE_EMAIL);
        }));

        $current = $this->getSettings();

        $saJson = trim((string)$this->input->post('drive_sa_json', false));
        if ($saJson === '') {
            $saJson = (string)$current['drive_sa_json']; // blank = keep existing
        } else {
            $decoded = json_decode($saJson, true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                $this->session->set_flashdata('danger', 'The service-account JSON is invalid — it needs client_email and private_key.');
                redirect('backup');
                return;
            }
        }

        $data = array(
            'id'               => 1,
            'auto_enabled'     => $this->input->post('auto_enabled') ? 1 : 0,
            'backup_time'      => $time($this->input->post('backup_time', true), '22:30'),
            'email_enabled'    => $this->input->post('email_enabled') ? 1 : 0,
            'email_time'       => $time($this->input->post('email_time', true), '23:00'),
            'email_recipients' => implode(',', $recipients),
            'attach_max_mb'    => max(1, min(50, (int)$this->input->post('attach_max_mb'))),
            'drive_enabled'    => $this->input->post('drive_enabled') ? 1 : 0,
            'drive_folder_id'  => trim((string)$this->input->post('drive_folder_id', true)),
            'drive_sa_json'    => $saJson !== '' ? $saJson : null,
            'drive_client_id'  => trim((string)$this->input->post('drive_client_id', true)),
            'drive_client_secret' => trim((string)$this->input->post('drive_client_secret', true)) !== ''
                ? trim((string)$this->input->post('drive_client_secret', true))
                : (string)$current['drive_client_secret'],
            'keep_days'        => max(1, min(90, (int)$this->input->post('keep_days'))),
            'keep_local'       => $this->input->post('keep_local') ? 1 : 0,
            'updated_at'       => date('Y-m-d H:i:s'),
        );

        if ($this->db->count_all_results('backup_settings') > 0) {
            $this->db->where('id', 1)->update('backup_settings', $data);
        } else {
            $this->db->insert('backup_settings', $data);
        }

        if ($data['email_enabled'] && $data['email_recipients'] === '') {
            $this->session->set_flashdata('warning', 'Settings saved, but email delivery is on with no recipients — add at least one address.');
        } else {
            $this->session->set_flashdata('success', 'Backup automation settings saved.');
        }
        redirect('backup');
    }

    /**
     * POST — run the full cycle right now: generate, email, Drive.
     * Ignores the schedule but honours the same settings.
     */
    public function run_now()
    {
        $this->requireSuperAdmin();
        $this->requirePost();
        $this->ensureTables();

        @set_time_limit(0);
        ignore_user_abort(true);

        $messages = $this->cycle(true, 'run_now');
        $this->session->set_flashdata('success', implode(' ', $messages));
        redirect('backup');
    }

    /**
     * GET backup/file/{id} — download a stored .sql.gz.
     */
    public function file($id = 0)
    {
        $this->requireSuperAdmin();
        $this->ensureTables();

        $run = $this->db->where('id', (int)$id)->get('backup_runs')->row_array();
        $path = $run ? $this->backupDir() . DIRECTORY_SEPARATOR . basename($run['filename']) : null;

        if (!$run || !$path || !is_file($path)) {
            show_error('That backup file no longer exists.', 404);
            return;
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($run['filename']) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    /** POST — delete one stored backup. */
    public function remove($id = 0)
    {
        $this->requireSuperAdmin();
        $this->requirePost();
        $this->ensureTables();

        $run = $this->db->where('id', (int)$id)->get('backup_runs')->row_array();
        if ($run) {
            $path = $this->backupDir() . DIRECTORY_SEPARATOR . basename($run['filename']);
            @unlink($path);
            $this->db->where('id', (int)$run['id'])->delete('backup_runs');
            $this->session->set_flashdata('success', 'Stored backup deleted.');
        }
        redirect('backup');
    }

    /**
     * GET backup/key — show the cron secret + ready-made crontab line.
     * Super Admin only (the authguard 'backup/*' rule covers this). Same
     * idea as EmailQueue/key?show_cron=1, but the whole page exists only
     * for admins so the key is shown directly.
     */
    public function key()
    {
        $this->requireSuperAdmin();

        $token    = self::token($this);
        $cronUrl  = site_url('backup/cron') . '?key=' . $token;
        $cronLine = '* * * * * curl -s "' . $cronUrl . '" > /dev/null 2>&1';
        $cliLine  = '* * * * * cd ' . rtrim(FCPATH, '/\\') . ' && php index.php Backup cron > /dev/null 2>&1';

        $e = static function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };

        $this->output->set_content_type('text/html')->set_output(
            '<!doctype html><html><head><meta charset="utf-8"><title>Backup cron key</title>
            <style>body{font-family:-apple-system,Arial,sans-serif;max-width:640px;margin:40px auto;padding:0 16px;color:#1a202c}
            code,pre{background:#f1f3f8;border:1px solid #dfe3ee;border-radius:8px;padding:10px 12px;display:block;word-break:break-all;font-size:13px}
            h3{margin:24px 0 8px;font-size:14px;color:#4a5568;text-transform:uppercase;letter-spacing:.04em}
            .warn{background:#fff6e6;border:1px solid #f0d9a8;border-radius:8px;padding:10px 14px;font-size:13px}</style></head><body>
            <h2>Database backup — cron setup</h2>
            <p class="warn">Keep this URL secret. Anyone holding it can trigger a backup run.</p>
            <h3>Cron URL</h3><code>' . $e($cronUrl) . '</code>
            <h3>Crontab line (cPanel / Linux — every minute)</h3><pre>' . $e($cronLine) . '</pre>
            <h3>CLI alternative (no key needed)</h3><pre>' . $e($cliLine) . '</pre>
            <h3>Token only</h3><code>' . $e($token) . '</code>
            <p style="font-size:13px;color:#6b7280">Pinned value: set <code style="display:inline;padding:2px 6px">$config[\'backup_cron_token\']</code> in config.php.</p>
            </body></html>'
        );
    }

    /**
     * GET backup/google-connect — kick off the one-time Google consent.
     * We ask for drive.file (the least-privileged Drive scope): the app can
     * only see files/folders it created itself.
     */
    public function google_connect()
    {
        $this->requireSuperAdmin();
        $this->ensureTables();

        $s = $this->getSettings();
        if (empty($s['drive_client_id']) || empty($s['drive_client_secret'])) {
            $this->session->set_flashdata('warning', 'Save your OAuth Client ID and Client Secret first, then connect.');
            redirect('backup');
            return;
        }

        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('gdrive_oauth_state', $state);

        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(array(
            'client_id'     => $s['drive_client_id'],
            'redirect_uri'  => site_url('backup/google-callback'),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive.file',
            'access_type'   => 'offline',
            'prompt'        => 'consent', // ensures a refresh_token comes back every time
            'state'         => $state,
        )));
    }

    /**
     * GET backup/google-callback?code=…&state=… — Google's return leg.
     * Exchanges the code for a refresh token and stores it.
     */
    public function google_callback()
    {
        $this->requireSuperAdmin();
        $this->ensureTables();

        $state    = (string)$this->input->get('state', true);
        $expected = (string)$this->session->userdata('gdrive_oauth_state');
        $this->session->unset_userdata('gdrive_oauth_state');

        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            $this->session->set_flashdata('danger', 'Drive connection rejected — bad state. Try connecting again.');
            redirect('backup');
            return;
        }

        if ((string)$this->input->get('error', true) !== '') {
            $this->session->set_flashdata('danger', 'Drive connection cancelled: ' . (string)$this->input->get('error', true));
            redirect('backup');
            return;
        }

        $code = trim((string)$this->input->get('code', true));
        if ($code === '') {
            $this->session->set_flashdata('danger', 'Drive connection failed — Google returned no code.');
            redirect('backup');
            return;
        }

        $s = $this->getSettings();
        require_once APPPATH . 'libraries/GDriveUpload.php';
        $gd = new GDriveUpload(array(
            'client_id'     => 'x',
            'client_secret' => 'x',
            'refresh_token' => 'x',
        ));
        $refresh = $gd->exchangeCode(
            $s['drive_client_id'],
            $s['drive_client_secret'],
            $code,
            site_url('backup/google-callback')
        );

        if ($refresh !== false) {
            $this->db->where('id', 1)->update('backup_settings', array(
                'drive_refresh_token' => $refresh,
                'drive_enabled'       => 1,
                'updated_at'          => date('Y-m-d H:i:s'),
            ));
            $this->session->set_flashdata('success', 'Google Drive connected — backups will upload to a "FBMSO Backups" folder in your Drive.');
        } else {
            $this->session->set_flashdata('danger', 'Drive connection failed: ' . $gd->error());
        }
        redirect('backup');
    }

    /** POST backup/google-disconnect — drop the stored refresh token. */
    public function google_disconnect()
    {
        $this->requireSuperAdmin();
        $this->requirePost();
        $this->ensureTables();
        $this->db->where('id', 1)->update('backup_settings', array(
            'drive_refresh_token' => '',
            'updated_at'          => date('Y-m-d H:i:s'),
        ));
        $this->session->set_flashdata('success', 'Google Drive disconnected.');
        redirect('backup');
    }

    /**
     * Cron endpoint — CLI or token-gated HTTP, same shape as
     * Securitycheck::daily_report.
     *
     *   php index.php Backup cron
     *   curl "…/backup/cron?key=TOKEN"
     *
     * Schedule is read from backup_settings (Manila time — the app config
     * sets that timezone). Backup fires once a day at/after backup_time;
     * the notification email goes out at/after email_time via the existing
     * mail queue; the Drive upload happens as soon as the file exists.
     */
    public function cron()
    {
        if (!is_cli() && !$this->input->is_cli_request()) {
            $key = (string)$this->input->get('key', true);
            if ($key === '' || !hash_equals(self::token($this), $key)) {
                show_error('Forbidden', 403);
                return;
            }
        }

        @set_time_limit(0);
        ignore_user_abort(true);
        $this->ensureTables();

        $settings = $this->getSettings();
        if (empty($settings['auto_enabled']) && !$this->forceFlag()) {
            return $this->cronOut('skipped (automation disabled)');
        }

        try {
            $messages = $this->cycle($this->forceFlag(), 'cron');
        } catch (Throwable $e) {
            log_message('error', 'Backup cron: ' . $e->getMessage());
            $messages = array('cron FAILED: ' . $e->getMessage());
        }
        $this->cronOut(implode('; ', $messages));
    }

    private function forceFlag()
    {
        foreach ((array)$this->uri->rsegments as $seg) {
            if ($seg === 'force') return true;
        }
        return ((string)$this->input->get('force') === '1')
            || in_array('force', (array)($_SERVER['argv'] ?? array()), true);
    }

    private function cronOut($text)
    {
        if (!is_cli() && !$this->input->is_cli_request()) {
            $this->output->set_content_type('text/plain')->set_output($text === '' ? "done\n" : $text . "\n");
            return;
        }
        echo $text === '' ? "done\n" : $text . "\n";
    }

    /**
     * One scheduler pass. Returns a list of human-readable outcome strings.
     * $force ignores the "already done today" guard but still honours
     * enabled/disabled switches per channel.
     */
    private function cycle($force, $triggeredBy)
    {
        $lockName = 'fbmsobkp_' . md5((string)$this->db->database);
        $lock = $this->db->query('SELECT GET_LOCK(' . $this->db->escape($lockName) . ', 0) AS l')->row();
        if (!$lock || (int)$lock->l !== 1) {
            return array('skipped: another run is in progress');
        }

        $messages = array();
        try {
            $settings = $this->getSettings();
            $now = time();

            // Most recent completed run — pending email/Drive attach to it.
            $latest = $this->db->where('status', 'ok')->order_by('id', 'DESC')
                ->limit(1)->get('backup_runs')->row_array();

            // --- Generate -------------------------------------------------
            $backupDue = $force || (
                !$latest || strtotime((string)$latest['created_at']) < strtotime(date('Y-m-d 00:00:00'))
            );
            $backupDue = $backupDue && ($force || $now >= strtotime(date('Y-m-d') . ' ' . $settings['backup_time'] . ':00'));

            if ($backupDue) {
                $latest = $this->generateStored($triggeredBy, $messages);
            }

            // --- Google Drive ---------------------------------------------
            // Each delivery channel is independently try/caught: a Drive or
            // mail outage must never block the backup (which already ran) or
            // the other channel.
            if (!empty($settings['drive_enabled']) && $latest && (string)$latest['drive_file_id'] === '') {
                try {
                    $this->uploadDrive($latest, $settings, $messages);
                    // Re-fetch so the email below can include the Drive link.
                    $latest = $this->db->where('id', (int)$latest['id'])->get('backup_runs')->row_array();
                } catch (Throwable $e) {
                    $this->recordChannelError((int)$latest['id'], 'drive: ' . $e->getMessage());
                    $messages[] = 'Drive upload FAILED: ' . $e->getMessage();
                }
            }

            // --- Email ------------------------------------------------------
            if (
                !empty($settings['email_enabled']) && $latest
                && empty($latest['emailed_at'])
                && ($force || $now >= strtotime(date('Y-m-d') . ' ' . $settings['email_time'] . ':00'))
            ) {
                try {
                    $this->queueEmails($latest, $settings, $messages);
                } catch (Throwable $e) {
                    $this->recordChannelError((int)$latest['id'], 'email: ' . $e->getMessage());
                    $messages[] = 'email FAILED: ' . $e->getMessage();
                }
            }

            // --- Local copy cleanup -----------------------------------------
            // Drop the server-side .sql.gz once the backup exists elsewhere
            // (Drive upload done, or email attachment actually sent). If no
            // channel delivered, the file stays — it's the only copy.
            if ($latest) {
                try {
                    $this->maybeRemoveLocal($latest, $settings, $messages);
                } catch (Throwable $e) {
                    $messages[] = 'local cleanup failed: ' . $e->getMessage();
                }
            }

            // --- Retention --------------------------------------------------
            try {
                $this->prune((int)$settings['keep_days'], $messages);
            } catch (Throwable $e) {
                $messages[] = 'prune failed: ' . $e->getMessage();
            }
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(' . $this->db->escape($lockName) . ')');
        }

        return $messages ?: array('nothing due');
    }

    /**
     * Dump to upload/backups/<db>_<ts>_<rand>.sql.gz and log the run.
     * Returns the fresh backup_runs row.
     */
    private function generateStored($triggeredBy, array &$messages)
    {
        $dir = $this->backupDir();
        $db  = (string)$this->db->database;
        // e.g. softtech_fbmso_2026-10-03.sql.gz — a same-day second run
        // gets a time suffix so it never clobbers the first.
        $filename = $db . '_' . date('Y-m-d') . '.sql.gz';
        if (is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $db . '_' . date('Y-m-d_H-i-s') . '.sql.gz';
        }
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $stats = $this->dbbackup->dumpTo('compress.zlib://' . $path);
            $run = array(
                'filename'     => $filename,
                'file_size'    => (int)@filesize($path),
                'tables_count' => (int)$stats['tables'],
                'rows_count'   => (int)$stats['rows'],
                'duration_sec' => $stats['seconds'],
                'status'       => 'ok',
                'triggered_by' => $triggeredBy,
                'created_at'   => date('Y-m-d H:i:s'),
            );
        } catch (Exception $e) {
            $run = array(
                'filename'     => '',
                'status'       => 'failed',
                'triggered_by' => $triggeredBy,
                'error'        => substr($e->getMessage(), 0, 500),
                'created_at'   => date('Y-m-d H:i:s'),
            );
            $messages[] = 'backup FAILED: ' . $e->getMessage();
        }

        $this->db->insert('backup_runs', $run);
        $runId = (int)$this->db->insert_id();

        if ($run['status'] === 'ok') {
            $messages[] = 'backup written: ' . $filename
                . ' (' . number_format($run['file_size'] / 1048576, 1) . ' MB, '
                . number_format($run['tables_count']) . ' tables)';
            try {
                $this->AuditLogModel->write(
                    'export', 'Database Backup', null, null, null,
                    array(
                        'database' => $db,
                        'file'     => $filename,
                        'tables'   => $run['tables_count'],
                        'rows'     => $run['rows_count'],
                        'bytes'    => $run['file_size'],
                        'via'      => $triggeredBy,
                    ),
                    1, 'Scheduled database backup generated',
                    array('_actor_username' => 'cron')
                );
            } catch (Throwable $ignored) {
            }
            // Re-fetch so emailed_at / drive_file_id keys exist for the
            // pending-channel checks in cycle().
            return $this->db->where('id', $runId)->get('backup_runs')->row_array();
        }

        return null; // failed runs must not be picked up for email/Drive
    }

    /** Merge a channel failure into the run row without clobbering others. */
    private function recordChannelError($runId, $msg)
    {
        $row = $this->db->where('id', $runId)->get('backup_runs')->row_array();
        $existing = $row ? (string)$row['error'] : '';
        $new = $existing === '' ? $msg : $existing . ' | ' . $msg;
        $this->db->where('id', $runId)->update('backup_runs', array('error' => substr($new, 0, 500)));
    }

    /**
     * Queue the notification email through the existing mail queue — the
     * EmailQueue cron delivers it with retries. The .sql.gz rides along as
     * a real attachment when it fits under attach_max_mb.
     */
    private function queueEmails(array $run, array $settings, array &$messages)
    {
        $recipients = preg_split('/[\s,;]+/', (string)$settings['email_recipients'], -1, PREG_SPLIT_NO_EMPTY);
        if (!$recipients) {
            $messages[] = 'email skipped: no recipients configured';
            return;
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . basename($run['filename']);
        $attach = '';
        if (is_file($path) && filesize($path) <= ((int)$settings['attach_max_mb'] * 1048576)) {
            $attach = $path;
        }

        $this->load->helper('fbmso_email');
        $school  = fbmso_mailqueue_school_name($this);
        $subject = '[FBMSO Backup] Database backup - ' . date('Y-m-d', strtotime($run['created_at']));
        $body    = $this->emailBody($run, $settings, $attach !== '');

        $queued = 0;
        foreach ($recipients as $to) {
            if (fbmso_mailqueue_push($this, $to, $subject, $body, $school, $attach)) {
                $queued++;
            }
        }

        if ($queued > 0) {
            $this->db->where('id', (int)$run['id'])->update('backup_runs', array('emailed_at' => date('Y-m-d H:i:s')));
            $messages[] = 'email queued to ' . $queued . ' recipient(s)'
                . ($attach !== '' ? ' with the .sql.gz attached' : ' (file too large to attach — link included instead)');
        } else {
            $messages[] = 'email FAILED: could not queue any recipient';
        }
    }

    private function uploadDrive(array $run, array $settings, array &$messages)
    {
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . basename($run['filename']);
        if (!is_file($path)) {
            $messages[] = 'Drive skipped: backup file missing';
            return;
        }

        // OAuth refresh token (uploads as the connected user — the only
        // mode that works on a regular account) wins over a service-account
        // JSON (which only works with Shared Drives).
        if (trim((string)$settings['drive_refresh_token']) !== '') {
            $creds = array(
                'client_id'     => $settings['drive_client_id'],
                'client_secret' => $settings['drive_client_secret'],
                'refresh_token' => $settings['drive_refresh_token'],
            );
        } else {
            $creds = trim((string)$settings['drive_sa_json']);
            if ($creds === '') {
                $messages[] = 'Drive skipped: no Google account connected and no service-account JSON saved';
                return;
            }
        }

        // Plain class, no CI loader needed — the loader can't pass the
        // service-account JSON to the constructor anyway.
        require_once APPPATH . 'libraries/GDriveUpload.php';
        $uploader = new GDriveUpload($creds);
        $result = $uploader->upload($path, $run['filename'], (string)$settings['drive_folder_id']);

        if ($result === false) {
            $this->db->where('id', (int)$run['id'])
                ->update('backup_runs', array('error' => substr('drive: ' . $uploader->error(), 0, 500)));
            $messages[] = 'Drive upload FAILED: ' . $uploader->error();
            return;
        }

        $this->db->where('id', (int)$run['id'])->update('backup_runs', array(
            'drive_file_id' => $result['id'],
            'drive_link'    => $result['link'],
            'error'         => '',
        ));
        $messages[] = 'uploaded to Google Drive';
    }

    /**
     * Delete the server-side dump once it has been delivered elsewhere —
     * keeps the disk clean and shrinks the window a stolen file would be
     * useful. Deferred while a queued email still references the file as an
     * attachment. keep_local = 1 opts back into keeping every copy.
     */
    private function maybeRemoveLocal(array $run, array $settings, array &$messages)
    {
        if (!empty($settings['keep_local']) || (string)$run['filename'] === '') {
            return;
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . basename($run['filename']);
        if (!is_file($path)) {
            return;
        }

        // Re-fetch: emailed_at / drive_file_id may have been set moments ago.
        $run = $this->db->where('id', (int)$run['id'])->get('backup_runs')->row_array();
        if (!$run) {
            return;
        }

        $delivered = ((string)$run['drive_file_id'] !== '')
            || (!empty($settings['email_enabled']) && !empty($run['emailed_at']));
        if (!$delivered) {
            return; // nothing delivered it — this file is the only copy
        }

        // Deletion waits until every queue row referencing this file has
        // actually sent — 'pending' still needs it, 'failed' means the email
        // never delivered it, so the file is still the only delivered copy.
        $undelivered = $this->db->where('attachment_path', $path)
            ->where('status !=', 'sent')
            ->count_all_results('fbmso_email_queue');
        if ($undelivered > 0) {
            $messages[] = 'local copy kept for now: the email attachment has not been sent yet';
            return;
        }

        if (@unlink($path)) {
            $messages[] = 'local copy removed (delivered elsewhere)';
        } else {
            $messages[] = 'could not remove the local copy';
        }
    }

    /** Drop run rows + files older than the retention window. */
    private function prune($keepDays, array &$messages)
    {
        $keepDays = max(1, $keepDays);
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $keepDays . ' days'));
        $old = $this->db->where('created_at <', $cutoff)->get('backup_runs')->result_array();

        $removed = 0;
        foreach ($old as $r) {
            if ($r['filename'] !== '') {
                @unlink($this->backupDir() . DIRECTORY_SEPARATOR . basename($r['filename']));
            }
            $this->db->where('id', (int)$r['id'])->delete('backup_runs');
            $removed++;
        }
        if ($removed) {
            $messages[] = 'pruned ' . $removed . ' backup(s) older than ' . $keepDays . ' day(s)';
        }
    }

    private function emailBody(array $run, array $settings, $hasAttachment)
    {
        $e = static function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };

        $fileUrl = site_url('backup/file/' . (int)$run['id']);
        $sizeMb  = number_format((int)$run['file_size'] / 1048576, 1);
        $driveHtml = '';
        if (!empty($run['drive_link'])) {
            $driveHtml = '<p style="margin:14px 0">Google Drive copy: '
                . '<a href="' . $e($run['drive_link']) . '">' . $e($run['drive_link']) . '</a></p>';
        }
        $attachHtml = $hasAttachment
            ? '<p style="margin:14px 0">The <code>.sql.gz</code> dump is <b>attached</b> to this email.</p>'
            : '<p style="margin:14px 0">The dump was too large to attach — download it with the button below (Super Admin login required).</p>';

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1a202c;max-width:560px">'
            . '<h2 style="font-size:18px;margin:0 0 4px">Database backup ready</h2>'
            . '<p style="color:#6b7280;margin:0 0 14px">Scheduled backup of <b>' . $e($this->db->database) . '</b></p>'
            . '<table style="border-collapse:collapse;font-size:13px;margin-bottom:6px">'
            . '<tr><td style="padding:3px 14px 3px 0;color:#6b7280">File</td><td><code>' . $e($run['filename']) . '</code></td></tr>'
            . '<tr><td style="padding:3px 14px 3px 0;color:#6b7280">Size</td><td>' . $sizeMb . ' MB (gzip)</td></tr>'
            . '<tr><td style="padding:3px 14px 3px 0;color:#6b7280">Tables</td><td>' . number_format((int)$run['tables_count']) . '</td></tr>'
            . '<tr><td style="padding:3px 14px 3px 0;color:#6b7280">Rows</td><td>' . number_format((int)$run['rows_count']) . '</td></tr>'
            . '<tr><td style="padding:3px 14px 3px 0;color:#6b7280">Generated</td><td>' . $e($run['created_at']) . '</td></tr>'
            . '</table>'
            . $attachHtml
            . $driveHtml
            . '<p style="margin:14px 0"><a href="' . $e($fileUrl) . '" style="display:inline-block;background:#2a4090;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600">Download backup</a></p>'
            . '<p style="font-size:12px;color:#9aa4b2;margin-top:18px">Import via phpMyAdmin (it accepts .sql.gz directly) or '
            . '<code>gunzip &lt; file.sql.gz | mysql -u user -p dbname</code>. The file contains DROP TABLE statements — '
            . 'importing replaces existing data.</p>'
            . '</div>';
    }
}

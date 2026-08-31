<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security maintenance tools.
 *
 *   php index.php securitycheck daily_report     (also callable over HTTP with a token)
 *   php index.php securitycheck verify_chain     (CLI only)
 *   php index.php securitycheck weak_passwords   (CLI only)
 *   php index.php securitycheck key              (CLI only - prints the cron URL)
 *
 * Only daily_report is reachable over HTTP, and only with the shared token,
 * because shared hosting often makes a cron URL easier to set up than a CLI
 * path. Its HTTP response is deliberately bare -- every finding goes to email,
 * never into the response body, so the endpoint cannot be used to probe the
 * system's security state.
 *
 * verify_chain and weak_passwords stay CLI-only: their output answers
 * questions an attacker would also like answered.
 */
class Securitycheck extends CI_Controller
{
    /** The only method reachable over HTTP, and only with a valid token. */
    const HTTP_METHOD = 'daily_report';

    public function __construct()
    {
        parent::__construct();

        // Deliberately in the constructor: this runs BEFORE AuthGuard's
        // post_controller_constructor hook, so a CLI-only method answers a
        // plain 404 whether or not the visitor is signed in.
        //
        // Letting AuthGuard handle it instead sent anonymous visitors to
        // /login?next=securitycheck%2Fkey, which echoed the path back into
        // the address bar, pushed it into browser history, and confirmed to
        // anyone probing that the route exists and is merely gated. A route
        // that does not serve browsers should look like it isn't there.
        if (!is_cli() && !$this->input->is_cli_request()) {
            if (strtolower((string)$this->router->fetch_method()) !== self::HTTP_METHOD) {
                show_404();
            }
        }
    }

    /**
     * Shared token for the cron URL. Derived from the encryption key and the
     * database name, so it is stable across deploys and never stored anywhere
     * extra. Same construction as the mail-queue token, different namespace,
     * so one token can never be replayed against the other endpoint.
     */
    public static function token($ci = null)
    {
        // Prefer the explicit config value: it is the same in every
        // environment, and it is readable without shell access.
        $configured = trim((string)config_item('security_report_token'));
        if ($configured !== '') {
            return $configured;
        }

        // Fallback for installs that have not set one yet. Folds in the
        // database name, so it differs between local and production.
        if ($ci === null) {
            $ci = &get_instance();
        }
        $dbName = is_object($ci->db) ? (string)$ci->db->database : '';

        return substr(hash('sha256', 'fbmso-security-report|' . (string)config_item('encryption_key') . '|' . $dbName), 0, 40);
    }

    /** Allow HTTP only for the named method, and only with a valid token. */
    private function gate($httpAllowed = false)
    {
        if (is_cli() || $this->input->is_cli_request()) {
            return;
        }

        if (!$httpAllowed) {
            show_404();
            return;
        }

        $key = (string)$this->input->get('key', true);
        if ($key === '' || !hash_equals(self::token($this), $key)) {
            show_error('Forbidden', 403);
        }
    }

    /**
     * Print the ready-to-paste cron line. CLI only.
     *
     * This was briefly browsable by Super Admin/Admin/IT for convenience while
     * setting the cron up. That page is gone: a URL that displays a working
     * security token is worth more to an attacker than it is to us, and the
     * token only needs reading once. Retrieve it over SSH instead:
     *
     *   php index.php securitycheck key
     */
    public function key()
    {
        $this->gate(false);

        $cron = '0 6 * * * curl -s "' . site_url('securitycheck/daily_report')
              . '?key=' . self::token($this) . '" > /dev/null 2>&1';

        echo "Security report cron line:\n\n  {$cron}\n\n";
        echo "Keep it secret; the token authenticates the request.\n";
    }

    /** Verify the security_audit_logs hash chain end to end. */
    public function verify_chain()
    {
        $this->gate(false);

        $this->load->library('securityaudit');
        $result = $this->securityaudit->verify();

        if ($result['ok']) {
            echo "OK: {$result['checked']} record(s) verified, chain intact.\n";
            return;
        }

        echo "TAMPERING DETECTED\n";
        echo "  records verified before the break: {$result['checked']}\n";
        echo "  chain breaks at record id: {$result['broken_at']}\n";
        echo "  This record was modified or a preceding record was removed.\n";
    }

    /**
     * Daily security digest: verify the chain, compare against the last
     * checkpoint, summarise activity, then queue an email.
     *
     * The email is the point. A hash chain cannot detect its own tail being
     * cut off, and an attacker with database access can delete the checkpoint
     * table too. What they cannot reach is a message already sitting in your
     * inbox, so each digest carries the record count and last hash. If
     * tomorrow's figures go backwards against yesterday's email, records were
     * destroyed -- even if every table on the server agrees they were not.
     *
     * Delivery rides the existing mail queue, so the EmailQueue cron sends it.
     *
     *   php index.php securitycheck daily_report
     */
    public function daily_report($hours = 24)
    {
        $this->gate(true);

        // Refuse to run more often than MIN_REPORT_INTERVAL.
        //
        // A cron line with '*' in the minute field fires 1440 times a day, and
        // this endpoint queues an email every time. That floods the mailbox,
        // backs up the mail queue behind it so real messages (verification,
        // password resets) stop going out, and buries the one report that
        // actually said something. The schedule should be fixed, but the
        // endpoint should not depend on the schedule being right.
        //
        // Pass 'force' as the second segment to override:
        //   php index.php securitycheck daily_report 24 force
        if (!$this->force_requested() && ($wait = $this->too_soon()) !== null) {
            $mins = (int)ceil($wait / 60);
            if (!is_cli() && !$this->input->is_cli_request()) {
                $this->output->set_content_type('text/plain')->set_output("skipped\n");
                return;
            }
            echo "Skipped: a report was generated less than " . (self::MIN_REPORT_INTERVAL / 3600)
               . "h ago. Next due in about {$mins} minute(s).\n";
            echo "If your cron has '*' in the minute field, change it to '0 6 * * *'.\n";
            return;
        }

        $hours = max(1, (int)$hours);
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));

        $this->load->library('securityaudit');

        $chain = $this->securityaudit->verify();
        $state = $this->chain_state();
        $prev  = $this->db->order_by('id', 'DESC')->limit(1)
            ->get('security_audit_anchors')->row_array();

        $alerts = $this->anchor_alerts($prev, $state, $chain);
        $ok     = $chain['ok'] && empty($alerts);

        // Persist the new checkpoint before mailing, so the figures in the
        // email and the figures on disk always describe the same moment.
        $this->db->insert('security_audit_anchors', array(
            'checked_at'       => date('Y-m-d H:i:s'),
            'last_record_id'   => $state['last_id'],
            'last_record_hash' => $state['last_hash'],
            'total_records'    => $state['total'],
            'chain_ok'         => $ok ? 1 : 0,
            'notes'            => $alerts ? implode('; ', $alerts) : null,
        ));

        // Second anchor, on disk. The emailed checkpoint is the one that
        // matters -- it lives off the server -- but email is exactly what
        // breaks first, and a checkpoint that only ever existed in the
        // database is no anchor at all once someone wipes the database.
        $this->write_file_anchor($state, $ok, $alerts);

        $mail = $this->queue_health();
        $activity = $this->activity_summary($since);
        $subject  = ($ok ? '[FBMSO Security] Daily report - OK' : '[FBMSO Security] ALERT - audit trail integrity')
                  . ' - ' . date('Y-m-d');

        $body = $this->render_report($ok, $chain, $state, $prev, $alerts, $activity, $since, $hours, $mail);

        $sent = 0;
        foreach ($this->recipients() as $to) {
            if (fbmso_mailqueue_push($this, $to, $subject, $body, fbmso_mailqueue_school_name($this))) {
                $sent++;
            }
        }

        // Over HTTP say nothing useful: the findings are in the email, and a
        // detailed response would let anyone holding the URL probe the system.
        if (!is_cli() && !$this->input->is_cli_request()) {
            $this->output->set_content_type('text/plain')->set_output("done\n");
            return;
        }

        echo ($ok ? "OK" : "ALERT") . ": chain checked ({$chain['checked']} records), queued to {$sent} recipient(s).\n";
        foreach ($alerts as $a) {
            echo "  ! {$a}\n";
        }
        if ($sent === 0) {
            echo "  ! No email queued. Check security_report_recipients in config.php.\n";
        }

        // Queueing is not delivering. Say so loudly: a report nobody receives
        // looks exactly like a report with nothing to say.
        foreach ($mail['warnings'] as $w) {
            echo "  ! MAIL: {$w}\n";
        }
        echo "  checkpoint also written to: " . $this->anchor_path() . "\n";
    }

    /** Minimum gap between reports, whatever the cron says. */
    const MIN_REPORT_INTERVAL = 21600; // 6 hours

    /**
     * Seconds still to wait, or NULL when a report is due.
     * Uses the anchor table, which is written on every real run.
     */
    private function too_soon()
    {
        if (!$this->db->table_exists('security_audit_anchors')) {
            return null;
        }

        $last = $this->db->select('checked_at')->order_by('id', 'DESC')
            ->limit(1)->get('security_audit_anchors')->row();

        if (!$last || empty($last->checked_at)) {
            return null;
        }

        $elapsed = time() - strtotime($last->checked_at);

        return $elapsed < self::MIN_REPORT_INTERVAL ? (self::MIN_REPORT_INTERVAL - $elapsed) : null;
    }

    private function force_requested()
    {
        foreach ((array)$this->uri->rsegments as $seg) {
            if ($seg === 'force') return true;
        }
        if ((string)$this->input->get('force') === '1') return true;

        return in_array('force', (array)($_SERVER['argv'] ?? array()), true);
    }

    /** Current end-of-chain figures. */
    private function chain_state()
    {
        $last = $this->db->select('id, record_hash')->order_by('id', 'DESC')
            ->limit(1)->get('security_audit_logs')->row();

        return array(
            'last_id'   => $last ? (int)$last->id : null,
            'last_hash' => $last ? (string)$last->record_hash : null,
            'total'     => (int)$this->db->count_all('security_audit_logs'),
        );
    }

    /**
     * Compare against the previous checkpoint. This is what catches the
     * deletions the hash chain alone cannot see.
     */
    private function anchor_alerts($prev, array $state, array $chain)
    {
        $alerts = array();

        if (!$chain['ok']) {
            $alerts[] = 'Hash chain broken at record id ' . $chain['broken_at']
                      . ' - a record was modified or removed.';
        }

        if (!$prev) {
            return $alerts;
        }

        if ($state['total'] < (int)$prev['total_records']) {
            $alerts[] = 'Record count fell from ' . (int)$prev['total_records']
                      . ' to ' . $state['total'] . ' - records were deleted.';
        }

        if ($prev['last_record_id'] !== null && $state['last_id'] !== null
            && $state['last_id'] < (int)$prev['last_record_id']) {
            $alerts[] = 'Last record id went backwards, from ' . (int)$prev['last_record_id']
                      . ' to ' . $state['last_id'] . ' - the end of the trail was truncated.';
        }

        // The record the last checkpoint pointed at must still be there,
        // unchanged. This catches a tail deletion followed by fresh writes.
        if ($prev['last_record_id'] !== null) {
            $row = $this->db->select('record_hash')
                ->where('id', (int)$prev['last_record_id'])
                ->limit(1)->get('security_audit_logs')->row();

            if (!$row) {
                $alerts[] = 'Previously checkpointed record id ' . (int)$prev['last_record_id']
                          . ' no longer exists.';
            } elseif ((string)$row->record_hash !== (string)$prev['last_record_hash']) {
                $alerts[] = 'Previously checkpointed record id ' . (int)$prev['last_record_id']
                          . ' now has a different hash - it was rewritten.';
            }
        }

        return $alerts;
    }

    /** Counts worth eyeballing each morning. */
    private function activity_summary($since)
    {
        $byType = $this->db->query(
            "SELECT event_type, COUNT(*) c
               FROM security_audit_logs WHERE event_time >= ?
              GROUP BY event_type ORDER BY c DESC",
            array($since)
        )->result_array();

        $failedIps = $this->db->query(
            "SELECT ip_address, COUNT(*) c, COUNT(DISTINCT target_username) accounts
               FROM security_audit_logs
              WHERE event_time >= ? AND event_type = 'LOGIN_FAILED'
              GROUP BY ip_address HAVING c >= 5 ORDER BY c DESC LIMIT 10",
            array($since)
        )->result_array();

        $changes = $this->db->query(
            "SELECT event_time, actor_username, target_username, changed_field,
                    old_value, new_value, ip_address, device_marketing_name, device_model_code
               FROM security_audit_logs
              WHERE event_time >= ?
                AND event_type IN ('PROFILE_CHANGED','PASSWORD_CHANGED','PASSWORD_RESET')
              ORDER BY event_time DESC LIMIT 25",
            array($since)
        )->result_array();

        return compact('byType', 'failedIps', 'changes');
    }

    /**
     * Is the mail queue actually delivering?
     *
     * Yesterday's report sitting in the queue unsent means the checkpoint
     * never left the building, and every later report is equally invisible.
     */
    private function queue_health()
    {
        $out = array('pending' => 0, 'failed' => 0, 'oldest' => null, 'stuck_reports' => 0, 'warnings' => array());

        if (!$this->db->table_exists('fbmso_email_queue')) {
            return $out;
        }

        $row = $this->db->query(
            "SELECT SUM(status='pending') pending, SUM(status='failed') failed,
                    MIN(CASE WHEN status='pending' THEN created_at END) oldest
               FROM fbmso_email_queue"
        )->row();

        $out['pending'] = (int)($row->pending ?? 0);
        $out['failed']  = (int)($row->failed ?? 0);
        $out['oldest']  = $row->oldest ?? null;

        $out['stuck_reports'] = (int)$this->db->query(
            "SELECT COUNT(*) c FROM fbmso_email_queue
              WHERE status IN ('pending','failed') AND subject LIKE '[FBMSO Security]%'"
        )->row()->c;

        if ($out['stuck_reports'] > 0) {
            $out['warnings'][] = $out['stuck_reports'] . ' earlier security report(s) never sent. '
                               . 'The emailed checkpoint is not reaching you.';
        }
        if ($out['failed'] > 0) {
            $out['warnings'][] = $out['failed'] . ' message(s) marked failed in the mail queue.';
        }
        if ($out['pending'] > 50) {
            $out['warnings'][] = $out['pending'] . ' messages pending -- the queue is backing up.';
        }
        if ($out['oldest'] !== null && strtotime($out['oldest']) < strtotime('-1 day')) {
            $out['warnings'][] = 'Oldest pending message is from ' . $out['oldest'] . ' -- delivery is stalled.';
        }

        return $out;
    }

    /** Where the on-disk checkpoint lives. */
    private function anchor_path()
    {
        $dir = rtrim(APPPATH, '/') . '/cache';

        return $dir . '/security_anchor.log';
    }

    /**
     * Append the checkpoint to a local file.
     *
     * Append-only text, one line per run. It does not survive an attacker with
     * filesystem access -- nothing on the server does -- but it does survive
     * the far more common case of the database being wiped while the files
     * are left alone, and it works when email does not.
     */
    private function write_file_anchor(array $state, $ok, array $alerts)
    {
        $line = sprintf(
            "%s  total=%d  last_id=%s  hash=%s  status=%s%s\n",
            date('c'),
            $state['total'],
            $state['last_id'] === null ? '-' : $state['last_id'],
            $state['last_hash'] === null ? '-' : $state['last_hash'],
            $ok ? 'OK' : 'ALERT',
            $alerts ? '  (' . implode('; ', $alerts) . ')' : ''
        );

        @file_put_contents($this->anchor_path(), $line, FILE_APPEND | LOCK_EX);
    }

    private function recipients()
    {
        $raw = (string)$this->config->item('security_report_recipients');
        $out = array();

        foreach (explode(',', $raw) as $addr) {
            $addr = trim($addr);
            if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $out[] = $addr;
            }
        }

        return $out;
    }

    private function render_report($ok, array $chain, array $state, $prev, array $alerts, array $activity, $since, $hours, array $mail = array())
    {
        $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        $banner = $ok ? '#1a7f37' : '#b42318';

        $h  = '<div style="font-family:Arial,Helvetica,sans-serif;color:#222;max-width:760px;margin:auto">';
        $h .= '<div style="background:' . $banner . ';color:#fff;padding:16px 20px;border-radius:6px 6px 0 0">';
        $h .= '<h2 style="margin:0;font-size:18px">' . ($ok ? 'Audit trail intact' : 'AUDIT TRAIL ALERT') . '</h2>';
        $h .= '<div style="opacity:.9;font-size:13px;margin-top:4px">' . $e(date('D, d M Y H:i')) . ' &middot; last ' . (int)$hours . 'h</div>';
        $h .= '</div><div style="border:1px solid #ddd;border-top:none;padding:20px;border-radius:0 0 6px 6px">';

        if (!$ok) {
            $h .= '<div style="background:#fff4f4;border-left:4px solid #b42318;padding:12px 14px;margin-bottom:18px">';
            $h .= '<strong>Someone may have altered the security log.</strong><ul style="margin:8px 0 0 18px;padding:0">';
            foreach ($alerts as $a) { $h .= '<li>' . $e($a) . '</li>'; }
            $h .= '</ul></div>';
        }

        // Checkpoint block -- the part worth keeping.
        $h .= '<h3 style="font-size:15px;margin:0 0 8px">Checkpoint</h3>';
        $h .= '<table style="border-collapse:collapse;font-size:13px;width:100%">';
        $rows = array(
            'Records verified' => $chain['checked'],
            'Total records'    => $state['total'],
            'Last record id'   => $state['last_id'] === null ? '(none)' : $state['last_id'],
            'Last record hash' => $state['last_hash'] === null ? '(none)' : $state['last_hash'],
        );
        if ($prev) {
            $rows['Previous check']     = $prev['checked_at'];
            $rows['Previous total']     = $prev['total_records'];
            $rows['Previous last id']   = $prev['last_record_id'];
        }
        foreach ($rows as $k => $v) {
            $h .= '<tr><td style="padding:5px 10px 5px 0;color:#666;white-space:nowrap">' . $e($k)
                . '</td><td style="padding:5px 0;font-family:monospace;word-break:break-all">' . $e($v) . '</td></tr>';
        }
        $h .= '</table>';
        $h .= '<p style="font-size:12px;color:#666;margin:10px 0 20px">Keep this email. If a later report shows fewer records '
            . 'or a lower last id than this one, the trail was cut -- regardless of what the server reports.</p>';

        // Mail health -- if this report reached you, the queue is at least
        // partly working, but a backlog still means earlier ones did not.
        if (!empty($mail['warnings'])) {
            $h .= '<div style="background:#fffbe6;border-left:4px solid #d4a017;padding:12px 14px;margin-bottom:18px">';
            $h .= '<strong>Mail delivery problems</strong><ul style="margin:8px 0 0 18px;padding:0">';
            foreach ($mail['warnings'] as $w) { $h .= '<li>' . $e($w) . '</li>'; }
            $h .= '</ul></div>';
        }

        // Events
        $h .= '<h3 style="font-size:15px;margin:0 0 8px">Events</h3>';
        if (empty($activity['byType'])) {
            $h .= '<p style="font-size:13px;color:#666">No security events recorded.</p>';
        } else {
            $h .= '<table style="border-collapse:collapse;font-size:13px">';
            foreach ($activity['byType'] as $r) {
                $h .= '<tr><td style="padding:4px 16px 4px 0">' . $e($r['event_type'])
                    . '</td><td style="padding:4px 0;text-align:right"><strong>' . $e($r['c']) . '</strong></td></tr>';
            }
            $h .= '</table>';
        }

        // Repeated failures
        if (!empty($activity['failedIps'])) {
            $h .= '<h3 style="font-size:15px;margin:20px 0 8px">Repeated failed logins</h3>';
            $h .= '<table style="border-collapse:collapse;font-size:13px;width:100%">';
            $h .= '<tr style="background:#f5f5f5"><th align="left" style="padding:6px">IP</th>'
                . '<th align="right" style="padding:6px">Failures</th><th align="right" style="padding:6px">Accounts</th></tr>';
            foreach ($activity['failedIps'] as $r) {
                $flag = ((int)$r['accounts'] > 1) ? ' style="background:#fff4f4"' : '';
                $h .= '<tr' . $flag . '><td style="padding:6px;font-family:monospace">' . $e($r['ip_address'])
                    . '</td><td align="right" style="padding:6px">' . $e($r['c'])
                    . '</td><td align="right" style="padding:6px">' . $e($r['accounts']) . '</td></tr>';
            }
            $h .= '</table>';
            $h .= '<p style="font-size:12px;color:#666;margin-top:6px">One IP failing against several accounts is credential spraying '
                . '-- the pattern behind the 28 Aug 2026 incident.</p>';
        }

        // Account changes
        $h .= '<h3 style="font-size:15px;margin:20px 0 8px">Account changes</h3>';
        if (empty($activity['changes'])) {
            $h .= '<p style="font-size:13px;color:#666">No account or password changes.</p>';
        } else {
            $h .= '<table style="border-collapse:collapse;font-size:12px;width:100%">';
            $h .= '<tr style="background:#f5f5f5"><th align="left" style="padding:6px">When</th>'
                . '<th align="left" style="padding:6px">Actor &rarr; Target</th>'
                . '<th align="left" style="padding:6px">Field</th>'
                . '<th align="left" style="padding:6px">Old &rarr; New</th>'
                . '<th align="left" style="padding:6px">Device</th></tr>';
            foreach ($activity['changes'] as $r) {
                $who = $e($r['actor_username']);
                if ((string)$r['actor_username'] !== (string)$r['target_username']) {
                    $who .= ' &rarr; <strong>' . $e($r['target_username']) . '</strong>';
                }
                $dev = trim((string)($r['device_marketing_name'] ?: $r['device_model_code']));
                $val = ($r['changed_field'] === null)
                    ? '<em style="color:#666">-</em>'
                    : $e($r['old_value']) . ' &rarr; <strong>' . $e($r['new_value']) . '</strong>';

                $h .= '<tr><td style="padding:6px;white-space:nowrap">' . $e($r['event_time'])
                    . '</td><td style="padding:6px">' . $who
                    . '</td><td style="padding:6px;font-family:monospace">' . $e($r['changed_field'])
                    . '</td><td style="padding:6px">' . $val
                    . '</td><td style="padding:6px">' . $e($dev ?: '-') . '<br><span style="color:#888">' . $e($r['ip_address']) . '</span>'
                    . '</td></tr>';
            }
            $h .= '</table>';
        }

        $h .= '<p style="font-size:11px;color:#888;margin-top:24px;border-top:1px solid #eee;padding-top:12px">'
            . 'Automated report from FBMSO. No passwords, hashes or session tokens are included.</p>';
        $h .= '</div></div>';

        return $h;
    }

    /**
     * Report accounts still on guessable credentials.
     * Counts only; never prints a hash or a password.
     */
    public function weak_passwords()
    {
        $this->gate(false);

        $compromised = $this->db->query(
            "SELECT COUNT(*) c FROM o_users WHERE password = ?",
            array('2fbd3e72682117dfad3ce0089afa803b021bf80b')
        )->row()->c;

        $birthdate = $this->db->query(
            "SELECT COUNT(*) c
               FROM o_users u
               JOIN studeprofile p
                 ON CONVERT(TRIM(p.StudentNumber) USING utf8mb4) = CONVERT(TRIM(u.username) USING utf8mb4)
              WHERE CONVERT(u.password USING utf8mb4)
                    = CONVERT(SHA1(DATE_FORMAT(p.birthDate,'%Y-%m-%d')) USING utf8mb4)
                AND p.birthDate <> '0000-00-00'"
        )->row()->c;

        $legacy = $this->db->query(
            "SELECT SUM(LENGTH(password)=40) sha1, SUM(password LIKE '\$2y\$%') bcrypt, COUNT(*) total FROM o_users"
        )->row();

        echo "Accounts on the known-compromised password : {$compromised}\n";
        echo "Accounts whose password is their birth date: {$birthdate}\n";
        echo "Still on legacy sha1                       : {$legacy->sha1}\n";
        echo "Converted to bcrypt                        : {$legacy->bcrypt}\n";
        echo "Total accounts                             : {$legacy->total}\n";
        echo "\nNote: hash-based detection only finds accounts still on sha1.\n";
        echo "Once an account converts to bcrypt it can no longer be matched this way.\n";
    }
}

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

        // Written as prose rather than tables. A wall of counters gets skimmed
        // and then ignored; the point of a daily report is that somebody
        // actually reads it and notices when a number is wrong.
        $mono = 'font-family:ui-monospace,SFMono-Regular,Menlo,monospace';
        $flow = function (array $steps) use ($e, $mono) {
            $h = '<div style="' . $mono . ';font-size:13px;line-height:1.7;background:#f7f8fa;'
               . 'border-left:3px solid #c9ced6;padding:14px 16px;margin:12px 0">';
            $last = count($steps) - 1;
            foreach ($steps as $i => $step) {
                $h .= '<div>' . $e($step) . '</div>';
                if ($i !== $last) {
                    $h .= '<div style="color:#9aa3ad;padding-left:2px">&darr;</div>';
                }
            }
            return $h . '</div>';
        };

        $banner = $ok ? '#1a7f37' : '#b42318';
        $h  = '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1f2328;'
            . 'max-width:680px;margin:auto;line-height:1.6">';
        $h .= '<div style="background:' . $banner . ';color:#fff;padding:18px 22px;border-radius:6px 6px 0 0">';
        $h .= '<div style="font-size:19px;font-weight:600">'
            . ($ok ? 'Nothing looks wrong' : 'Something looks wrong with the security log')
            . '</div>';
        $h .= '<div style="opacity:.9;font-size:13px;margin-top:3px">'
            . $e(date('l, j F Y \a\t H:i')) . ' &middot; covering the last ' . (int)$hours . ' hours</div>';
        $h .= '</div><div style="border:1px solid #d8dde3;border-top:none;padding:22px;border-radius:0 0 6px 6px">';

        // ---- the alarming part, told as a sequence -----------------------
        if (!$ok) {
            $h .= '<p style="margin-top:0"><strong>The security log is smaller than it was.</strong> '
                . 'Nothing in the application ever deletes from it, so this should not happen on its own.</p>';

            if ($prev) {
                $h .= $flow(array(
                    'At ' . substr((string)$prev['checked_at'], 0, 16) . '  the log held '
                        . (int)$prev['total_records'] . ' records, ending at #' . (int)$prev['last_record_id'],
                    'Between then and now, records went missing',
                    'At ' . date('Y-m-d H:i') . '  it holds ' . $state['total']
                        . ' records, ending at #' . ($state['last_id'] === null ? '-' : $state['last_id']),
                ));
            }

            $h .= '<p>Specifically:</p><ul style="margin:6px 0 14px 18px;padding:0">';
            foreach ($alerts as $a) { $h .= '<li>' . $e($a) . '</li>'; }
            $h .= '</ul>';

            $h .= '<p style="background:#fff8f0;border-left:3px solid #d4a017;padding:12px 14px;margin:14px 0">'
                . '<strong>Before assuming the worst.</strong> Restoring a database backup, re-importing a copy, '
                . 'or clearing test data all look exactly like this. Check whether anyone did one of those first. '
                . 'On the server, <span style="' . $mono . '">SELECT AUTO_INCREMENT FROM information_schema.TABLES '
                . 'WHERE TABLE_NAME=\'security_audit_logs\'</span> tells you which: a low number means the table was '
                . 'emptied and started again; a number above the old maximum means individual rows were removed, '
                . 'which is far harder to explain innocently.</p>';
        } else {
            $h .= '<p style="margin-top:0">The audit trail is intact. Every record still matches its hash, '
                . 'and the log has only grown since the last check.</p>';
        }

        // ---- mail health -------------------------------------------------
        if (!empty($mail['warnings'])) {
            $h .= '<p style="background:#fffbe6;border-left:3px solid #d4a017;padding:12px 14px">'
                . '<strong>Email is not getting through properly.</strong></p><ul style="margin:6px 0 14px 18px">';
            foreach ($mail['warnings'] as $w) { $h .= '<li>' . $e($w) . '</li>'; }
            $h .= '</ul>';
        }

        // ---- what happened, in words -------------------------------------
        $c = array();
        foreach ($activity['byType'] as $r) { $c[$r['event_type']] = (int)$r['c']; }
        $n = function ($k) use ($c) { return isset($c[$k]) ? $c[$k] : 0; };

        $h .= '<h3 style="font-size:15px;margin:22px 0 6px">What happened</h3>';

        if (!array_sum($c)) {
            $h .= '<p>Nobody signed in and nothing changed. Quiet day.</p>';
        } else {
            $parts = array();
            if ($n('LOGIN_SUCCESS')) {
                $parts[] = '<strong>' . $n('LOGIN_SUCCESS') . '</strong> successful sign-in'
                         . ($n('LOGIN_SUCCESS') === 1 ? '' : 's');
            }
            if ($n('LOGIN_FAILED')) {
                $parts[] = '<strong>' . $n('LOGIN_FAILED') . '</strong> failed attempt'
                         . ($n('LOGIN_FAILED') === 1 ? '' : 's');
            }
            if ($n('LOGIN_NEW_DEVICE')) {
                $parts[] = '<strong>' . $n('LOGIN_NEW_DEVICE') . '</strong> sign-in'
                         . ($n('LOGIN_NEW_DEVICE') === 1 ? '' : 's') . ' from a device not seen before';
            }
            // "a, b and c" rather than "a, b, c", and the verb agrees with
            // the FIRST item, not the number of items -- otherwise one
            // sign-in and five failures reads "There were 1 successful
            // sign-in".
            $join = function (array $items) {
                if (count($items) === 1) return $items[0];
                $last = array_pop($items);
                return implode(', ', $items) . ' and ' . $last;
            };
            $firstCount = $n('LOGIN_SUCCESS') ?: ($n('LOGIN_FAILED') ?: $n('LOGIN_NEW_DEVICE'));

            $h .= '<p>There ' . ($firstCount === 1 ? 'was ' : 'were ') . $join($parts) . '.';

            $extra = array();
            if ($n('PASSWORD_CHANGED')) $extra[] = $n('PASSWORD_CHANGED') . ' password change' . ($n('PASSWORD_CHANGED') === 1 ? '' : 's');
            if ($n('PASSWORD_RESET'))   $extra[] = $n('PASSWORD_RESET') . ' password reset' . ($n('PASSWORD_RESET') === 1 ? '' : 's');
            if ($n('RATE_LIMIT_TRIGGERED')) $extra[] = $n('RATE_LIMIT_TRIGGERED') . ' sign-in' . ($n('RATE_LIMIT_TRIGGERED') === 1 ? '' : 's') . ' blocked for too many attempts';
            if ($extra) $h .= ' Also ' . $join($extra) . '.';
            $h .= '</p>';
        }

        // ---- suspicious IPs, explained ------------------------------------
        if (!empty($activity['failedIps'])) {
            $h .= '<h3 style="font-size:15px;margin:22px 0 6px">Worth a look</h3>';
            foreach ($activity['failedIps'] as $r) {
                $many = (int)$r['accounts'] > 1;
                $h .= '<p style="margin:8px 0">'
                    . '<span style="' . $mono . '">' . $e($r['ip_address']) . '</span> failed '
                    . '<strong>' . $e($r['c']) . '</strong> times';
                if ($many) {
                    $h .= ' against <strong>' . $e($r['accounts']) . ' different accounts</strong>. '
                        . 'One address trying several accounts is what credential spraying looks like &mdash; '
                        . 'the same shape as 28 August. A shared campus or household connection can look '
                        . 'identical, so check whether those accounts have anything to do with each other '
                        . 'before treating it as an attack.';
                } else {
                    $h .= ' against one account. Most likely somebody who has forgotten their password.';
                }
                $h .= '</p>';
            }
        }

        // ---- account changes, as small sequences --------------------------
        $h .= '<h3 style="font-size:15px;margin:22px 0 6px">Account changes</h3>';
        if (empty($activity['changes'])) {
            $h .= '<p>No profile fields, passwords or names were changed.</p>';
        } else {
            foreach ($activity['changes'] as $r) {
                $actor  = (string)$r['actor_username'];
                $target = (string)$r['target_username'];
                $device = trim((string)($r['device_marketing_name'] ?: $r['device_model_code'] ?: 'an unrecognised device'));

                $who = ($actor !== '' && $target !== '' && $actor !== $target)
                    ? $actor . ' changed ' . $target . "'s record"
                    : (($actor ?: $target ?: 'someone') . ' changed their own record');

                $steps = array(
                    substr((string)$r['event_time'], 0, 19),
                    $who,
                    'using ' . $device . ' at ' . (string)$r['ip_address'],
                );

                if ($r['changed_field'] !== null && $r['changed_field'] !== '') {
                    $steps[] = $r['changed_field'] . ':  '
                             . ($r['old_value'] === null || $r['old_value'] === '' ? '(empty)' : $r['old_value'])
                             . '   ->   '
                             . ($r['new_value'] === null || $r['new_value'] === '' ? '(empty)' : $r['new_value']);
                } elseif ($r['description']) {
                    $steps[] = (string)$r['description'];
                }

                $h .= $flow($steps);
            }
        }

        // ---- checkpoint, with the reason it matters -----------------------
        $h .= '<h3 style="font-size:15px;margin:24px 0 6px">Keep this email</h3>';
        $h .= '<p>These numbers are the only copy of the audit trail\'s size that lives outside the server. '
            . 'If a future report shows <em>fewer</em> records or a <em>lower</em> last id than this one, '
            . 'the log was cut &mdash; no matter what the server claims.</p>';
        $h .= '<div style="' . $mono . ';font-size:13px;background:#f7f8fa;border:1px solid #e3e7ec;'
            . 'padding:12px 14px;border-radius:4px;word-break:break-all">'
            . 'records&nbsp;&nbsp;&nbsp;' . $e($state['total']) . '<br>'
            . 'last&nbsp;id&nbsp;&nbsp;&nbsp;#' . $e($state['last_id'] === null ? '-' : $state['last_id']) . '<br>'
            . 'hash&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $e($state['last_hash'] === null ? '-' : $state['last_hash'])
            . '</div>';

        $h .= '<p style="font-size:12px;color:#6b7280;margin-top:22px;border-top:1px solid #eceff2;padding-top:12px">'
            . 'Sent automatically by FBMSO. No passwords, hashes or session tokens appear in this email.</p>';
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

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tamper-evident security event trail.
 *
 * Writes to security_audit_logs. Two entry points:
 *
 *   event()   - a single security event (login, logout, access denied, ...)
 *   changes() - diffs a before/after record and writes one row PER CHANGED
 *               FIELD, so "who renamed this account, from what, to what"
 *               is a single indexed query instead of JSON archaeology.
 *
 * Design notes:
 *
 *  - Actor and target are separate columns. A student editing themselves and
 *    an admin editing that student are different events and must not collapse.
 *  - Interpreted device labels live beside the raw user-agent, never instead
 *    of it. Unknown stays NULL; nothing is guessed.
 *  - Rows are hash-chained: record_hash = SHA256(payload + prev_hash). Editing
 *    or deleting a historic row breaks every hash after it, which verify()
 *    detects.
 *  - Never records passwords, hashes, tokens or session ids. Sensitive field
 *    names are redacted to a marker before they reach the database.
 */
class Securityaudit
{
    /** @var CI_Controller */
    protected $CI;

    /** Field names whose VALUES must never be stored, matched case-insensitively. */
    protected $redact = array(
        'password', 'passwd', 'pwd', 'password_attempt', 'password_hash',
        'newpassword', 'cnewpassword', 'currentpassword', 'confirm_password',
        'otp', 'mfa_secret', 'token', 'api_key', 'secret', 'session_id',
        'remember_token', 'reset_token',
    );

    const REDACTED = '[redacted]';

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Record one security event.
     *
     * @param string $type e.g. LOGIN_SUCCESS, LOGIN_FAILED, PASSWORD_CHANGED
     * @param array  $opts status, module, target, description, extra,
     *                     risk_score, risk_level, risk_reason,
     *                     table, record_pk, field, old, new
     */
    public function event($type, array $opts = array())
    {
        try {
            return $this->write($type, $opts);
        } catch (Throwable $e) {
            // Auditing must never break the request it is auditing.
            log_message('error', 'Securityaudit::event ' . $type . ' - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Diff two versions of a record and log every field that actually changed.
     *
     * @param string $type   event type for the rows, e.g. PROFILE_CHANGED
     * @param array  $before field => value before
     * @param array  $after  field => value after
     * @param array  $opts   as event(); 'only' limits which fields are compared
     * @return int number of changed fields recorded
     */
    public function changes($type, array $before, array $after, array $opts = array())
    {
        try {
            $only = isset($opts['only']) ? (array)$opts['only'] : null;
            $fields = $only !== null ? $only : array_keys($after);

            $written = 0;
            foreach ($fields as $field) {
                $old = array_key_exists($field, $before) ? $before[$field] : null;
                $new = array_key_exists($field, $after) ? $after[$field] : null;

                if (!$this->differs($old, $new)) {
                    continue;
                }

                $rowOpts = $opts;
                $rowOpts['field'] = $field;
                $rowOpts['old']   = $old;
                $rowOpts['new']   = $new;
                unset($rowOpts['only']);

                $this->write($type, $rowOpts);
                $written++;
            }

            return $written;
        } catch (Throwable $e) {
            log_message('error', 'Securityaudit::changes ' . $type . ' - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verify the hash chain.
     *
     * @param int $fromId start at this id (0 = beginning)
     * @return array ['ok' => bool, 'checked' => int, 'broken_at' => ?int]
     */
    public function verify($fromId = 0)
    {
        $rows = $this->CI->db
            ->where('id >=', (int)$fromId)
            ->order_by('id', 'ASC')
            ->get('security_audit_logs')
            ->result_array();

        $prev = null;
        $checked = 0;

        foreach ($rows as $row) {
            // The first row examined inherits whatever chain preceded it.
            if ($prev !== null && (string)$row['prev_hash'] !== (string)$prev) {
                return array('ok' => false, 'checked' => $checked, 'broken_at' => (int)$row['id']);
            }

            $expected = $this->hashRow($row, (string)$row['prev_hash']);
            if (!hash_equals((string)$row['record_hash'], $expected)) {
                return array('ok' => false, 'checked' => $checked, 'broken_at' => (int)$row['id']);
            }

            $prev = $row['record_hash'];
            $checked++;
        }

        return array('ok' => true, 'checked' => $checked, 'broken_at' => null);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function write($type, array $opts)
    {
        $session = $this->CI->session;

        $actor      = (string)$session->userdata('username');
        $actorFirst = (string)$session->userdata('fname');
        $actorLast  = (string)$session->userdata('lname');
        $actorName  = trim($actorLast . ($actorFirst ? ', ' . $actorFirst : ''));

        $device = $this->device();

        $field = isset($opts['field']) ? (string)$opts['field'] : null;
        $old   = array_key_exists('old', $opts) ? $opts['old'] : null;
        $new   = array_key_exists('new', $opts) ? $opts['new'] : null;

        if ($field !== null && $this->isSensitive($field)) {
            $old = $old === null ? null : self::REDACTED;
            $new = $new === null ? null : self::REDACTED;
        }

        $row = array(
            'event_time'   => date('Y-m-d H:i:s'),
            'event_type'   => strtoupper((string)$type),
            'event_status' => isset($opts['status']) ? (string)$opts['status'] : null,
            'module'       => isset($opts['module']) ? (string)$opts['module'] : null,

            'actor_username'  => $actor !== '' ? $actor : (isset($opts['actor']) ? (string)$opts['actor'] : null),
            'actor_full_name' => ($actorName !== '' && $actorName !== ',') ? $actorName : null,
            'actor_level'     => (string)$session->userdata('level') ?: null,
            'target_username' => isset($opts['target']) ? (string)$opts['target'] : ($actor !== '' ? $actor : null),

            'table_name'    => isset($opts['table']) ? (string)$opts['table'] : null,
            'record_pk'     => isset($opts['record_pk']) ? (string)$opts['record_pk'] : null,
            'changed_field' => $field,
            'old_value'     => $this->stringify($old),
            'new_value'     => $this->stringify($new),

            'ip_address'        => $this->CI->input->ip_address(),
            'request_uri'       => substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 500) ?: null,
            'request_method'    => (string)($_SERVER['REQUEST_METHOD'] ?? '') ?: null,
            'session_reference' => $this->sessionReference(),

            'device_type'           => $device['device_type'],
            'device_brand'          => $device['device_brand'],
            'device_model_code'     => $device['device_model_code'],
            'device_marketing_name' => $device['device_marketing_name'],
            'operating_system'      => $device['operating_system'],
            'os_version'            => $device['os_version'],
            'browser'               => $device['browser'],
            'browser_version'       => $device['browser_version'],
            'raw_user_agent'        => $device['raw_user_agent'],

            'risk_score'  => isset($opts['risk_score']) ? (int)$opts['risk_score'] : 0,
            'risk_level'  => isset($opts['risk_level']) ? (string)$opts['risk_level'] : null,
            'risk_reason' => isset($opts['risk_reason']) ? (string)$opts['risk_reason'] : null,

            'description' => isset($opts['description']) ? (string)$opts['description'] : null,
            'extra'       => isset($opts['extra']) ? json_encode($opts['extra'], JSON_UNESCAPED_UNICODE) : null,
        );

        $prev = $this->lastHash();
        $row['prev_hash']   = $prev;
        $row['record_hash'] = $this->hashRow($row, (string)$prev);

        return $this->CI->db->insert('security_audit_logs', $row);
    }

    /** Hash of the meaningful payload, chained to the previous record. */
    protected function hashRow(array $row, $prevHash)
    {
        $payload = array();
        foreach (array(
            'event_time', 'event_type', 'event_status', 'module',
            'actor_username', 'target_username',
            'table_name', 'record_pk', 'changed_field', 'old_value', 'new_value',
            'ip_address', 'request_uri', 'request_method',
            'device_model_code', 'raw_user_agent',
            'risk_score', 'description',
        ) as $k) {
            $payload[$k] = array_key_exists($k, $row) ? (string)$row[$k] : '';
        }

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE) . '|' . $prevHash);
    }

    protected function lastHash()
    {
        $row = $this->CI->db
            ->select('record_hash')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('security_audit_logs')
            ->row();

        return $row ? (string)$row->record_hash : null;
    }

    /**
     * Non-reversible reference to the current session, so events from one
     * sign-in can be grouped without ever storing the session id itself.
     */
    protected function sessionReference()
    {
        // Shared with the session registry so an audit row can be tied to the
        // session it came from.
        return fbmso_session_reference();
    }

    protected function isSensitive($field)
    {
        $f = strtolower((string)$field);
        foreach ($this->redact as $needle) {
            if (strpos($f, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function differs($a, $b)
    {
        if (is_scalar($a) || $a === null) {
            $a = trim((string)$a);
        } else {
            $a = json_encode($a);
        }
        if (is_scalar($b) || $b === null) {
            $b = trim((string)$b);
        } else {
            $b = json_encode($b);
        }

        return $a !== $b;
    }

    protected function stringify($v)
    {
        if ($v === null) {
            return null;
        }
        if (is_scalar($v)) {
            return substr((string)$v, 0, 4000);
        }
        return substr((string)json_encode($v, JSON_UNESCAPED_UNICODE), 0, 4000);
    }

    // ------------------------------------------------------------------
    // Device interpretation
    // ------------------------------------------------------------------

    /**
     * Best-effort labels from the user-agent, plus Client Hints when the
     * browser sends them. Anything not clearly stated stays NULL -- a wrong
     * device label is worse than an unknown one.
     */
    public function device()
    {
        $ua = (string)$this->CI->input->user_agent();

        $out = array(
            'device_type' => null, 'device_brand' => null,
            'device_model_code' => null, 'device_marketing_name' => null,
            'operating_system' => null, 'os_version' => null,
            'browser' => null, 'browser_version' => null,
            'raw_user_agent' => $ua !== '' ? substr($ua, 0, 1000) : null,
        );

        if ($ua === '') {
            return $out;
        }

        // --- OS -------------------------------------------------------
        if (preg_match('/Android\s+([0-9.]+)/i', $ua, $m)) {
            $out['operating_system'] = 'Android';
            $out['os_version'] = $m[1];
            $out['device_type'] = stripos($ua, 'Mobile') !== false ? 'Mobile' : 'Tablet';
        } elseif (preg_match('/(iPhone|iPad) OS ([0-9_]+)/i', $ua, $m)) {
            $out['operating_system'] = 'iOS';
            $out['os_version'] = str_replace('_', '.', $m[2]);
            $out['device_type'] = stripos($m[1], 'iPad') !== false ? 'Tablet' : 'Mobile';
            $out['device_brand'] = 'Apple';
        } elseif (preg_match('/Windows NT ([0-9.]+)/i', $ua, $m)) {
            $out['operating_system'] = 'Windows';
            $out['os_version'] = $m[1];
            $out['device_type'] = 'Desktop';
        } elseif (preg_match('/Mac OS X ([0-9_.]+)/i', $ua, $m)) {
            $out['operating_system'] = 'macOS';
            $out['os_version'] = str_replace('_', '.', $m[1]);
            $out['device_type'] = 'Desktop';
            $out['device_brand'] = 'Apple';
        } elseif (stripos($ua, 'Linux') !== false) {
            $out['operating_system'] = 'Linux';
            $out['device_type'] = 'Desktop';
        }

        // --- Android model code --------------------------------------
        // Android UAs carry "; <MODEL> Build/" or "; <MODEL>)".
        if ($out['operating_system'] === 'Android') {
            if (preg_match('/;\s*([^;)]+?)\s+Build\//i', $ua, $m)) {
                $out['device_model_code'] = trim($m[1]);
            } elseif (preg_match('/Android\s+[0-9.]+;\s*([^;)]+?)\s*[;)]/i', $ua, $m)) {
                $candidate = trim($m[1]);
                if ($candidate !== '' && stripos($candidate, 'wv') !== 0) {
                    $out['device_model_code'] = $candidate;
                }
            }
        }

        // --- Browser --------------------------------------------------
        // Order matters: Edge/OPR/Samsung all also contain "Chrome".
        $browsers = array(
            'Edg'      => 'Edge',
            'OPR'      => 'Opera',
            'SamsungBrowser' => 'Samsung Internet',
            'Chrome'   => 'Chrome',
            'Firefox'  => 'Firefox',
            'Safari'   => 'Safari',
        );
        foreach ($browsers as $token => $label) {
            if (preg_match('#' . preg_quote($token, '#') . '/([0-9.]+)#i', $ua, $m)) {
                $out['browser'] = $label;
                $out['browser_version'] = $m[1];
                break;
            }
        }

        // In-app webviews identify themselves and matter for investigations.
        if (preg_match('/\b(FBAN|FBAV|FB_IAB|Instagram|Line|Messenger)\b/i', $ua, $m)) {
            $out['browser'] = 'In-app browser (' . $m[1] . ')';
        }

        // --- Client Hints override the UA when present ----------------
        $chModel    = $this->clientHint('HTTP_SEC_CH_UA_MODEL');
        $chPlatform = $this->clientHint('HTTP_SEC_CH_UA_PLATFORM');
        $chVersion  = $this->clientHint('HTTP_SEC_CH_UA_PLATFORM_VERSION');

        if ($chModel !== null && $chModel !== '') {
            $out['device_model_code'] = $chModel;
        }
        if ($chPlatform !== null && $chPlatform !== '') {
            $out['operating_system'] = $chPlatform;
        }
        if ($chVersion !== null && $chVersion !== '') {
            $out['os_version'] = $chVersion;
        }

        // --- Resolve model code to a marketing name -------------------
        if ($out['device_model_code'] !== null) {
            $resolved = $this->resolveModel($out['device_model_code']);
            if ($resolved) {
                $out['device_marketing_name'] = $resolved['marketing_name'];
                if ($out['device_brand'] === null) {
                    $out['device_brand'] = $resolved['manufacturer'];
                }
            }
        }

        return $out;
    }

    /** Client Hint header value, stripped of the quotes browsers send. */
    protected function clientHint($key)
    {
        if (!isset($_SERVER[$key])) {
            return null;
        }
        return trim((string)$_SERVER[$key], " \"'");
    }

    /** Catalog lookup. Returns NULL rather than guessing. */
    protected function resolveModel($code)
    {
        $row = $this->CI->db
            ->where('model_code', $code)
            ->limit(1)
            ->get('device_model_catalog')
            ->row_array();

        return $row ?: null;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Failed-login throttling.
 *
 * Counts failures against three independent scopes, because each catches a
 * different attack shape:
 *
 *   account_ip  one person guessing one account      -> classic brute force
 *   account     many sources against one account     -> distributed guessing
 *   ip          one source against many accounts     -> credential spraying,
 *                                                       the 2026-08-28 shape
 *
 * Blocks are always temporary. A permanent lockout on failed attempts hands
 * an attacker a denial-of-service: spray wrong passwords at every account and
 * the whole school is locked out. Windows expire on their own instead.
 *
 * No sleep() anywhere. Delaying the response holds a PHP worker open, so under
 * a real flood the throttle would exhaust the pool and take the site down --
 * the attack would succeed by a different route. Blocked requests are refused
 * immediately.
 */
class Loginthrottle
{
    /** @var CI_Controller */
    protected $CI;

    /** Failures counted within this many seconds of each other. */
    const WINDOW = 900; // 15 minutes

    /**
     * scope => [failure threshold, block seconds]
     * Escalates: each further block for the same scope doubles, to MAX_BLOCK.
     */
    protected $rules = array(
        'account_ip' => array(5,  300),   // 5 tries at one account from one IP
        'account'    => array(12, 600),   // one account attacked from anywhere
        'ip'         => array(20, 900),   // one IP attacking anything
    );

    const MAX_BLOCK = 3600;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Is this attempt currently blocked?
     *
     * @return array|null ['scope','retry_after'] when blocked, else NULL
     */
    public function check($username, $ip = null)
    {
        $ip = $ip !== null ? $ip : $this->CI->input->ip_address();

        foreach ($this->scopeKeys($username, $ip) as $scope => $key) {
            $row = $this->row($scope, $key);
            if (!$row || empty($row['blocked_until'])) {
                continue;
            }

            $remaining = strtotime($row['blocked_until']) - time();
            if ($remaining > 0) {
                return array('scope' => $scope, 'retry_after' => $remaining);
            }
        }

        return null;
    }

    /** Record a failed attempt against every scope; block when over threshold. */
    public function fail($username, $ip = null)
    {
        $ip  = $ip !== null ? $ip : $this->CI->input->ip_address();
        $now = date('Y-m-d H:i:s');
        $triggered = array();

        foreach ($this->scopeKeys($username, $ip) as $scope => $key) {
            $row = $this->row($scope, $key);

            // A gap longer than the window starts a fresh count, so occasional
            // typos over weeks never accumulate into a block.
            $stale = $row && (strtotime($now) - strtotime($row['last_failure_at'])) > self::WINDOW;

            if (!$row) {
                $this->CI->db->insert('login_throttle', array(
                    'scope' => $scope, 'scope_key' => $key, 'failures' => 1,
                    'first_failure_at' => $now, 'last_failure_at' => $now,
                ));
                continue;
            }

            $failures = $stale ? 1 : ((int)$row['failures'] + 1);
            list($threshold, $baseBlock) = $this->rules[$scope];

            $update = array(
                'failures'         => $failures,
                'last_failure_at'  => $now,
                'first_failure_at' => $stale ? $now : $row['first_failure_at'],
                'blocked_until'    => $stale ? null : $row['blocked_until'],
            );

            if ($failures >= $threshold) {
                // Each additional block for the same scope doubles.
                $over     = $failures - $threshold;
                $seconds  = min(self::MAX_BLOCK, $baseBlock * pow(2, min($over, 4)));
                $update['blocked_until'] = date('Y-m-d H:i:s', time() + $seconds);
                $triggered[$scope] = (int)$seconds;
            }

            $this->CI->db->where('id', $row['id'])->update('login_throttle', $update);
        }

        return $triggered;
    }

    /**
     * Clear counters after a genuine sign-in.
     *
     * The account scopes are cleared; the IP scope is NOT. Otherwise anyone
     * spraying could reset their own IP counter simply by signing into an
     * account they legitimately control, which is exactly what happened on
     * 2026-08-28: the attacker registered an account, signed into it, then
     * moved on to someone else's.
     */
    public function succeed($username, $ip = null)
    {
        $ip = $ip !== null ? $ip : $this->CI->input->ip_address();

        $this->CI->db
            ->where('scope', 'account')->where('scope_key', $this->key($username))
            ->delete('login_throttle');

        $this->CI->db
            ->where('scope', 'account_ip')->where('scope_key', $this->key($username) . '|' . $ip)
            ->delete('login_throttle');
    }

    /** Drop expired rows. Called opportunistically; cheap and indexed. */
    public function prune()
    {
        $this->CI->db
            ->where('last_failure_at <', date('Y-m-d H:i:s', time() - (self::WINDOW * 4)))
            ->where('(blocked_until IS NULL OR blocked_until < NOW())', null, false)
            ->delete('login_throttle');
    }

    /** Human phrasing for a retry delay, without leaking the rule that fired. */
    public function retryMessage($seconds)
    {
        $minutes = (int)ceil($seconds / 60);

        return $minutes <= 1
            ? 'Too many sign-in attempts. Please wait a minute and try again.'
            : 'Too many sign-in attempts. Please try again in about ' . $minutes . ' minutes.';
    }

    /**
     * Generic counter for non-login endpoints that leak information when
     * called in bulk -- account-existence checks, chiefly.
     *
     * Returns seconds remaining when the caller is blocked, else NULL.
     * Call once per request; it counts and checks in one go.
     */
    public function probe($scope, $threshold, $blockSeconds, $key = null)
    {
        $key = $key !== null ? $key : $this->CI->input->ip_address();
        $now = date('Y-m-d H:i:s');
        $row = $this->row($scope, $key);

        if ($row && !empty($row['blocked_until'])) {
            $remaining = strtotime($row['blocked_until']) - time();
            if ($remaining > 0) {
                return $remaining;
            }
        }

        if (!$row) {
            $this->CI->db->insert('login_throttle', array(
                'scope' => $scope, 'scope_key' => $key, 'failures' => 1,
                'first_failure_at' => $now, 'last_failure_at' => $now,
            ));
            return null;
        }

        $stale    = (strtotime($now) - strtotime($row['last_failure_at'])) > self::WINDOW;
        $failures = $stale ? 1 : ((int)$row['failures'] + 1);

        $update = array(
            'failures'         => $failures,
            'last_failure_at'  => $now,
            'first_failure_at' => $stale ? $now : $row['first_failure_at'],
            'blocked_until'    => null,
        );

        $blocked = null;
        if ($failures >= $threshold) {
            $update['blocked_until'] = date('Y-m-d H:i:s', time() + $blockSeconds);
            $blocked = $blockSeconds;
        }

        $this->CI->db->where('id', $row['id'])->update('login_throttle', $update);

        return $blocked;
    }

    // ------------------------------------------------------------------

    protected function scopeKeys($username, $ip)
    {
        $u = $this->key($username);

        return array(
            'account_ip' => $u . '|' . $ip,
            'account'    => $u,
            'ip'         => $ip,
        );
    }

    /** Normalised account key: throttling must not be dodged by casing
     *  or email formatting differences. */
    protected function key($username)
    {
        $k = strtolower(trim((string)$username));
        // Canonicalize emails so John@Example.com and john@example.com
        // share the same throttle bucket.
        if (filter_var($k, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $k, 2);
            if (count($parts) === 2) {
                $k = $parts[0] . '@' . $parts[1];
            }
        }
        return substr($k, 0, 100);
    }

    protected function row($scope, $key)
    {
        return $this->CI->db
            ->where('scope', $scope)->where('scope_key', $key)
            ->limit(1)->get('login_throttle')->row_array();
    }
}

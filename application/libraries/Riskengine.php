<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Scores a sign-in from the signals already collected elsewhere.
 *
 * Deliberately does not decide anything on one signal. An unknown device is
 * ordinary -- new phone, cleared cookies, a different browser. What is not
 * ordinary is an unknown device that has also touched five other accounts,
 * from an IP this account has never used, after a run of failures. Each of
 * those alone is noise; together they are the 2026-08-28 incident.
 *
 * Rules and weights live in config/risk.php so they can be tuned against real
 * traffic without touching this file.
 */
class Riskengine
{
    /** @var CI_Controller */
    protected $CI;

    protected $rules  = array();
    protected $levels = array();

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('risk', false, true);

        $this->rules  = (array)$this->CI->config->item('risk_rules');
        $this->levels = (array)$this->CI->config->item('risk_levels');
    }

    /**
     * Score a successful authentication.
     *
     * @param string $username
     * @param array  $device  the array Devicetokens::recognise() returned
     * @param string $level   the account's role
     * @return array score, level, reasons[], actions
     */
    public function assess($username, array $device, $level = '')
    {
        $score   = 0;
        $reasons = array();

        $add = function ($rule, $why) use (&$score, &$reasons) {
            $points = (int)($this->rules[$rule] ?? 0);
            if ($points > 0) {
                $score += $points;
                $reasons[] = $why . ' (+' . $points . ')';
            }
        };

        if (!empty($device['revoked'])) {
            $add('revoked_device', 'Device was previously revoked and has returned');
        } elseif (!empty($device['new_device'])) {
            $add('unknown_device', 'Device not seen on this account before');
        }

        $others = (int)($device['other_accounts'] ?? 0);
        if ($others >= 5) {
            $add('device_other_accounts_5', 'This device has signed into ' . $others . ' other accounts');
        } elseif ($others >= 2) {
            $add('device_other_accounts_2', 'This device has signed into ' . $others . ' other accounts');
        }

        if ($this->recentFailures($username) >= 3) {
            $add('failures_before_success', 'Several failed attempts shortly before this success');
        }

        if ($this->wasThrottled($username)) {
            $add('throttled_recently', 'This account or address was rate limited recently');
        }

        if ($this->isNewIp($username)) {
            $add('new_ip_for_account', 'Network address not previously used by this account');
        }

        $privileged = (array)$this->CI->config->item('risk_privileged_levels');
        if ($level !== '' && in_array($level, $privileged, true)) {
            $add('privileged_account', 'Privileged account (' . $level . ')');
        }

        $band = $this->band($score);

        return array(
            'score'   => $score,
            'level'   => $band,
            'reasons' => $reasons,
            'actions' => (array)($this->CI->config->item('risk_actions')[$band] ?? array()),
        );
    }

    /** Which band a score falls into. */
    public function band($score)
    {
        $band = 'LOW';
        foreach ($this->levels as $name => $floor) {
            if ($score >= (int)$floor) {
                $band = $name;
            }
        }

        return $band;
    }

    // ------------------------------------------------------------------

    /** Failed sign-ins for this account in the last 30 minutes. */
    protected function recentFailures($username)
    {
        return (int)$this->CI->db
            ->where('username', $username)
            ->where('status', 'failed')
            ->where('login_time >=', date('Y-m-d H:i:s', time() - 1800))
            ->count_all_results('login_logs');
    }

    protected function wasThrottled($username)
    {
        $ip = $this->CI->input->ip_address();

        return (bool)$this->CI->db->query(
            "SELECT 1 FROM login_throttle
              WHERE (scope_key = ? OR scope_key = ? OR scope_key = ?)
                AND last_failure_at >= ?
              LIMIT 1",
            array(
                strtolower($username),
                strtolower($username) . '|' . $ip,
                $ip,
                date('Y-m-d H:i:s', time() - 3600),
            )
        )->row();
    }

    /**
     * Has this account signed in from this address before?
     *
     * Mobile networks reassign addresses constantly, so on its own this means
     * very little -- which is why it carries the smallest weight in the table.
     */
    protected function isNewIp($username)
    {
        $ip = $this->CI->input->ip_address();
        if ($ip === '') {
            return false;
        }

        $seen = (int)$this->CI->db
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('status', 'success')
            ->where('login_time <', date('Y-m-d H:i:s', time() - 5))
            ->count_all_results('login_logs');

        return $seen === 0;
    }
}

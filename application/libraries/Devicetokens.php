<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Trusted-device recognition.
 *
 * Answers "has this browser signed into this account before?" -- the question
 * IMEI was supposed to answer and cannot, because no browser exposes hardware
 * identifiers and Android has restricted IMEI to carrier apps since Android 10.
 *
 * A random 256-bit token is issued to the browser in a Secure, HttpOnly cookie.
 * The server keeps only its SHA-256, so the table cannot be used to impersonate
 * a device. Unlike a hardware id this is revocable, which is what you actually
 * need when a phone is lost.
 *
 * Honest limits: clearing cookies looks like a new device, and a different
 * browser on the same phone is a different device. Both fail toward asking for
 * MORE verification, never less, so the failure mode is safe.
 */
class Devicetokens
{
    /** @var CI_Controller */
    protected $CI;

    const COOKIE = 'fbmso_dev';
    const LIFETIME = 63072000; // 2 years

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Recognise (or register) the device signing in, and record the visit.
     *
     * @return array {
     *   known:        bool  seen on THIS account before
     *   new_device:   bool  inverse of known
     *   revoked:      bool  device was explicitly revoked -- treat as hostile
     *   trusted:      bool  marked trusted by user or admin
     *   device:       array|null the stored row
     *   other_accounts:int   how many OTHER accounts this same browser has used
     * }
     */
    public function recognise($username)
    {
        $raw = $this->cookieToken();
        $issued = false;

        if ($raw === null) {
            $raw = bin2hex(random_bytes(32)); // 256 bits
            $issued = true;
        }

        $hash = hash('sha256', $raw);
        $now  = date('Y-m-d H:i:s');
        $ip   = $this->CI->input->ip_address();

        $this->CI->load->library('securityaudit');
        $d = $this->CI->securityaudit->device();

        $row = $this->CI->db
            ->where('username', $username)->where('device_token_hash', $hash)
            ->limit(1)->get('user_devices')->row_array();

        if ($row) {
            $this->CI->db->where('id', $row['id'])->update('user_devices', array(
                'last_seen_at' => $now,
                'last_ip'      => $ip,
                'login_count'  => (int)$row['login_count'] + 1,
                // Refresh interpreted labels: a browser upgrade changes them,
                // and stale labels make an investigation harder to read.
                'browser'         => $d['browser'],
                'browser_version' => $d['browser_version'],
                'os_version'      => $d['os_version'],
                'raw_user_agent'  => $d['raw_user_agent'],
            ));
        } else {
            $this->CI->db->insert('user_devices', array(
                'username'          => $username,
                'device_token_hash' => $hash,
                'device_brand'          => $d['device_brand'],
                'device_marketing_name' => $d['device_marketing_name'],
                'device_model_code'     => $d['device_model_code'],
                'device_type'           => $d['device_type'],
                'operating_system'      => $d['operating_system'],
                'os_version'            => $d['os_version'],
                'browser'               => $d['browser'],
                'browser_version'       => $d['browser_version'],
                'raw_user_agent'        => $d['raw_user_agent'],
                'first_ip'      => $ip,
                'last_ip'       => $ip,
                'login_count'   => 1,
                'first_seen_at' => $now,
                'last_seen_at'  => $now,
            ));
        }

        // Re-issue the cookie every login so its expiry rolls forward.
        $this->setCookie($raw);

        $stored = $this->CI->db
            ->where('username', $username)->where('device_token_hash', $hash)
            ->limit(1)->get('user_devices')->row_array();

        // Same browser, other accounts. One device across many accounts in a
        // short window is credential spraying, not a shared family tablet --
        // the risk engine will want this number.
        $others = (int)$this->CI->db
            ->where('device_token_hash', $hash)
            ->where('username !=', $username)
            ->count_all_results('user_devices');

        return array(
            'known'          => !$issued && $row !== null,
            'new_device'     => $issued || $row === null,
            'revoked'        => $stored ? (bool)$stored['is_revoked'] : false,
            'trusted'        => $stored ? (bool)$stored['is_trusted'] : false,
            'device'         => $stored,
            'other_accounts' => $others,
        );
    }

    /** Devices that have signed into an account. */
    public function forUser($username)
    {
        return $this->CI->db->where('username', $username)
            ->order_by('last_seen_at', 'DESC')->get('user_devices')->result_array();
    }

    /** Every account a given device has been used on. */
    public function accountsForDevice($tokenHash)
    {
        return $this->CI->db->select('username, first_seen_at, last_seen_at, login_count')
            ->where('device_token_hash', $tokenHash)
            ->order_by('last_seen_at', 'DESC')->get('user_devices')->result_array();
    }

    /** Devices seen on more accounts than a normal person would use. */
    public function sharedDevices($minAccounts = 3)
    {
        return $this->CI->db->query(
            "SELECT device_token_hash, COUNT(*) AS accounts,
                    MAX(device_marketing_name) AS device, MAX(device_model_code) AS model,
                    MIN(first_seen_at) AS first_seen, MAX(last_seen_at) AS last_seen
               FROM user_devices
              GROUP BY device_token_hash
             HAVING accounts >= ?
             ORDER BY accounts DESC",
            array((int)$minAccounts)
        )->result_array();
    }

    public function trust($username, $tokenHash)
    {
        $this->CI->db->where('username', $username)->where('device_token_hash', $tokenHash)
            ->update('user_devices', array('is_trusted' => 1, 'trusted_at' => date('Y-m-d H:i:s'), 'is_revoked' => 0, 'revoked_at' => null));

        return $this->CI->db->affected_rows();
    }

    /** Revoke a device. Its next sign-in is treated as hostile, not merely new. */
    public function revoke($username, $tokenHash, $by = null)
    {
        $this->CI->db->where('username', $username)->where('device_token_hash', $tokenHash)
            ->update('user_devices', array(
                'is_revoked' => 1, 'is_trusted' => 0,
                'revoked_at' => date('Y-m-d H:i:s'),
                'revoked_by' => $by ?: ((string)$this->CI->session->userdata('username') ?: 'system'),
            ));

        return $this->CI->db->affected_rows();
    }

    /** Hash of the calling browser's token, or NULL if it has none. */
    public function currentHash()
    {
        $raw = $this->cookieToken();

        return $raw === null ? null : hash('sha256', $raw);
    }

    // ------------------------------------------------------------------

    protected function cookieToken()
    {
        $v = isset($_COOKIE[self::COOKIE]) ? (string)$_COOKIE[self::COOKIE] : '';

        // Must look like what we issue; anything else is treated as absent
        // rather than trusted, so a crafted cookie cannot probe the table.
        return preg_match('/^[a-f0-9]{64}$/', $v) ? $v : null;
    }

    protected function setCookie($raw)
    {
        $secure = (strpos((string)config_item('base_url'), 'https://') === 0);

        // SameSite=Lax: the cookie should ride along with a normal navigation
        // to the site but not with a cross-site POST.
        $params = array(
            'expires'  => time() + self::LIFETIME,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        );

        if (PHP_VERSION_ID >= 70300) {
            setcookie(self::COOKIE, $raw, $params);
            return;
        }

        setcookie(self::COOKIE, $raw, $params['expires'], $params['path'] . '; SameSite=Lax', '', $secure, true);
    }
}

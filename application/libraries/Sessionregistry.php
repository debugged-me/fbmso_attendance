<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Inventory of active sign-ins, and the ability to end them.
 *
 * Sessions are stored as files on disk, so there is no row to delete to kick
 * someone out. Revocation is therefore enforced in the application: every
 * request looks up its own session and, if it has been revoked, the session is
 * destroyed before the controller runs.
 *
 * That costs one indexed lookup per authenticated request. It is deliberate:
 * a "revoke" that only takes effect when the intruder's session happens to
 * expire is not containment.
 *
 * Only the HMAC of the session id is stored. A stored session id is a stored
 * credential -- anyone reading the table could impersonate every live user.
 */
class Sessionregistry
{
    /** @var CI_Controller */
    protected $CI;

    /** Skip the activity write if we already touched it this recently. */
    const TOUCH_INTERVAL = 60;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /** Record a new sign-in. Called once, right after authentication. */
    public function open($username)
    {
        $ref = fbmso_session_reference();
        if ($ref === null) {
            return false;
        }

        $this->CI->load->library('securityaudit');
        $d = $this->CI->securityaudit->device();
        $now = date('Y-m-d H:i:s');

        // Session ids are regenerated on login, so a collision means a reused
        // id rather than a duplicate sign-in: overwrite it.
        $this->CI->db->query(
            "INSERT INTO user_security_sessions
               (username, session_reference, ip_address, device_type, device_marketing_name,
                device_model_code, operating_system, browser, raw_user_agent,
                created_at, last_activity_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               username = VALUES(username), ip_address = VALUES(ip_address),
               created_at = VALUES(created_at), last_activity_at = VALUES(last_activity_at),
               revoked_at = NULL, revoke_reason = NULL, revoked_by = NULL",
            array(
                $username, $ref, $this->CI->input->ip_address(),
                $d['device_type'], $d['device_marketing_name'], $d['device_model_code'],
                $d['operating_system'], $d['browser'], $d['raw_user_agent'],
                $now, $now,
            )
        );

        return true;
    }

    /**
     * Has the current session been revoked?
     * Also refreshes last_activity_at, at most once a minute.
     */
    public function isCurrentRevoked()
    {
        $ref = fbmso_session_reference();
        if ($ref === null) {
            return false;
        }

        $row = $this->CI->db
            ->select('id, revoked_at, last_activity_at')
            ->where('session_reference', $ref)
            ->limit(1)->get('user_security_sessions')->row();

        if (!$row) {
            // Sessions that predate this feature are not treated as revoked;
            // that would sign everybody out on deploy.
            return false;
        }

        if (!empty($row->revoked_at)) {
            return true;
        }

        $last = $row->last_activity_at ? strtotime($row->last_activity_at) : 0;
        if ((time() - $last) >= self::TOUCH_INTERVAL) {
            $this->CI->db->where('id', $row->id)
                ->update('user_security_sessions', array('last_activity_at' => date('Y-m-d H:i:s')));
        }

        return false;
    }

    /** Mark the current session closed after a normal sign-out. */
    public function close($reason = 'signed out')
    {
        $ref = fbmso_session_reference();
        if ($ref === null) {
            return;
        }

        $this->CI->db->where('session_reference', $ref)->where('revoked_at IS NULL', null, false)
            ->update('user_security_sessions', array(
                'revoked_at' => date('Y-m-d H:i:s'),
                'revoke_reason' => $reason,
            ));
    }

    /**
     * End every session for an account.
     *
     * @param string      $username
     * @param string      $reason
     * @param bool        $keepCurrent Leave the caller signed in -- what you
     *                                 want for "log out my other devices".
     * @return int sessions ended
     */
    public function revokeAllForUser($username, $reason = 'all sessions revoked', $keepCurrent = false)
    {
        $this->CI->db->where('username', $username)->where('revoked_at IS NULL', null, false);

        if ($keepCurrent) {
            $ref = fbmso_session_reference();
            if ($ref !== null) {
                $this->CI->db->where('session_reference !=', $ref);
            }
        }

        $this->CI->db->update('user_security_sessions', array(
            'revoked_at'    => date('Y-m-d H:i:s'),
            'revoke_reason' => $reason,
            'revoked_by'    => (string)$this->CI->session->userdata('username') ?: 'system',
        ));

        $n = $this->CI->db->affected_rows();

        if ($n > 0) {
            $this->CI->load->library('securityaudit');
            $this->CI->securityaudit->event('ALL_SESSIONS_REVOKED', array(
                'module' => 'Session', 'status' => 'success', 'target' => $username,
                'description' => $reason,
                'extra' => array('sessions_ended' => $n, 'kept_current' => (bool)$keepCurrent),
            ));
        }

        return $n;
    }

    /** End one session by its reference. */
    public function revoke($reference, $reason = 'revoked')
    {
        $this->CI->db->where('session_reference', $reference)->where('revoked_at IS NULL', null, false)
            ->update('user_security_sessions', array(
                'revoked_at'    => date('Y-m-d H:i:s'),
                'revoke_reason' => $reason,
                'revoked_by'    => (string)$this->CI->session->userdata('username') ?: 'system',
            ));

        return $this->CI->db->affected_rows();
    }

    /** Active sessions for an account, most recently used first. */
    public function activeFor($username)
    {
        return $this->CI->db
            ->where('username', $username)
            ->where('revoked_at IS NULL', null, false)
            ->where('last_activity_at >', date('Y-m-d H:i:s', time() - (int)config_item('sess_expiration')))
            ->order_by('last_activity_at', 'DESC')
            ->get('user_security_sessions')->result_array();
    }

    /** Is a row the session making this request? */
    public function isCurrent(array $row)
    {
        $ref = fbmso_session_reference();

        return $ref !== null && isset($row['session_reference']) && hash_equals($row['session_reference'], $ref);
    }

    /** Drop rows well past expiry so the table does not grow forever. */
    public function prune()
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(86400, (int)config_item('sess_expiration') * 4));

        $this->CI->db->where('last_activity_at <', $cutoff)->delete('user_security_sessions');
    }
}

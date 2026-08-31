<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security dashboard. Super Admin only.
 *
 * Everything the security work collects -- sessions, devices, risk scores,
 * account changes -- has until now been reachable only over SSH or by email.
 * This puts it on a screen.
 *
 * Deliberately NOT open to Admin. This page shows which accounts are on weak
 * credentials, which devices have touched several accounts, and can end
 * anyone's session. That is the security owner's job, not general admin.
 * AuthGuard enforces the same rule; both layers are intentional.
 */
class Security extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('securityaudit');
        $this->load->library('sessionregistry');
        $this->load->library('devicetokens');
        $this->load->library('riskengine');

        if (strcasecmp((string)$this->session->userdata('level'), 'Super Admin') !== 0) {
            show_error('Access Denied', 403);
        }
    }

    /** Overview: what needs attention today. */
    public function index()
    {
        $since = date('Y-m-d H:i:s', time() - 86400);

        $d['counts'] = array(
            'logins_today'   => $this->countEvents('LOGIN_SUCCESS', $since),
            'failed_today'   => $this->countEvents('LOGIN_FAILED', $since),
            'new_devices'    => $this->countEvents('LOGIN_NEW_DEVICE', $since),
            'blocked'        => $this->countEvents('RATE_LIMIT_TRIGGERED', $since),
            'active_sessions' => (int)$this->db
                ->where('revoked_at IS NULL', null, false)
                ->where('last_activity_at >', date('Y-m-d H:i:s', time() - (int)config_item('sess_expiration')))
                ->count_all_results('user_security_sessions'),
            'locked_accounts' => (int)$this->db->like('password', '!locked:', 'after')->count_all_results('o_users'),
        );

        // Anything the risk engine flagged above LOW, most recent first.
        $d['risky'] = $this->db
            ->select('event_time, target_username, ip_address, risk_score, risk_level, risk_reason,
                      device_marketing_name, device_model_code, operating_system, os_version, browser')
            ->where('event_time >=', date('Y-m-d H:i:s', time() - (86400 * 7)))
            ->where('risk_score >', 0)
            ->order_by('risk_score', 'DESC')->order_by('event_time', 'DESC')
            ->limit(25)->get('security_audit_logs')->result_array();

        $d['shared_devices'] = $this->devicetokens->sharedDevices(3);

        $d['chain'] = $this->securityaudit->verify();

        $d['changes'] = $this->db
            ->select('event_time, actor_username, target_username, changed_field, old_value, new_value,
                      ip_address, device_marketing_name, device_model_code, operating_system, os_version, browser')
            ->where('event_time >=', date('Y-m-d H:i:s', time() - (86400 * 7)))
            ->where_in('event_type', array('PROFILE_CHANGED', 'PASSWORD_CHANGED', 'PASSWORD_RESET'))
            ->order_by('event_time', 'DESC')->limit(30)->get('security_audit_logs')->result_array();

        $this->load->view('security_dashboard', $d);
    }

    /** Active sessions, newest activity first. */
    public function sessions()
    {
        $d['sessions'] = $this->db
            ->where('revoked_at IS NULL', null, false)
            ->where('last_activity_at >', date('Y-m-d H:i:s', time() - (int)config_item('sess_expiration')))
            ->order_by('last_activity_at', 'DESC')->limit(200)
            ->get('user_security_sessions')->result_array();

        $d['registry'] = $this->sessionregistry;

        $this->load->view('security_sessions', $d);
    }

    /** Devices, grouped by account. */
    public function devices()
    {
        $q = trim((string)$this->input->get('u', true));

        $this->db->order_by('last_seen_at', 'DESC')->limit(200);
        if ($q !== '') {
            $this->db->like('username', $q);
        }
        $d['devices'] = $this->db->get('user_devices')->result_array();
        $d['q'] = $q;

        $this->load->view('security_devices', $d);
    }

    /** End one session. POST only -- it changes state. */
    public function revoke_session()
    {
        $this->require_post();

        $ref = (string)$this->input->post('reference', true);
        $n = $ref !== '' ? $this->sessionregistry->revoke($ref, 'revoked from security dashboard') : 0;

        $this->session->set_flashdata(
            $n ? 'success' : 'danger',
            $n ? 'Session ended. Their next request signs them out.' : 'That session was not found or had already ended.'
        );

        redirect('Security/sessions');
    }

    /** End every session for one account. */
    public function revoke_user()
    {
        $this->require_post();

        $username = (string)$this->input->post('username', true);
        $n = $username !== '' ? $this->sessionregistry->revokeAllForUser($username, 'revoked from security dashboard') : 0;

        $this->session->set_flashdata(
            'success',
            'Ended ' . $n . ' session(s) for ' . $username . '. Change their password too, or whoever it was can sign straight back in.'
        );

        redirect('Security/sessions');
    }

    /** Revoke a device for an account. */
    public function revoke_device()
    {
        $this->require_post();

        $username = (string)$this->input->post('username', true);
        $hash     = (string)$this->input->post('token_hash', true);
        $n = ($username !== '' && $hash !== '') ? $this->devicetokens->revoke($username, $hash) : 0;

        if ($n) {
            $this->securityaudit->event('SECURITY_SETTING_CHANGED', array(
                'module' => 'Security Dashboard', 'status' => 'success', 'target' => $username,
                'description' => 'Device revoked from the security dashboard',
            ));
        }

        $this->session->set_flashdata($n ? 'success' : 'danger',
            $n ? 'Device revoked. Its next sign-in scores as high risk.' : 'Device not found.');

        redirect('Security/devices' . ($username !== '' ? '?u=' . urlencode($username) : ''));
    }

    // ------------------------------------------------------------------

    /**
     * State changes must not be reachable by GET: a link or an <img> tag
     * would otherwise be enough to end somebody's session.
     */
    private function require_post()
    {
        if (strtoupper((string)$this->input->method()) !== 'POST') {
            show_error('Method Not Allowed', 405);
        }
    }

    private function countEvents($type, $since)
    {
        return (int)$this->db->where('event_type', $type)->where('event_time >=', $since)
            ->count_all_results('security_audit_logs');
    }
}

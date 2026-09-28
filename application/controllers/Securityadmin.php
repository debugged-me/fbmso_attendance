<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security Admin Dashboard
 *
 * Provides admin staff with:
 *   - Attacker forensic report (IP, device, accounts touched, timeline)
 *   - IP blacklist management (add, remove, view)
 *   - Login activity monitor (recent logins, suspicious patterns)
 *   - Blocked attempt log
 *
 * All methods require staff authentication via AuthGuard.
 */
class Securityadmin extends CI_Controller
{
    /** Records per page for paginated tables */
    const PER_PAGE = 25;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'device']);
        $this->load->database();
        $this->load->library('securityaudit');
        $this->load->library('pagination');
        $this->load->model('AuditLogModel');
    }

    /** Build pagination config for a given URL + total count */
    protected function paginateConfig($baseUrl, $total, $queryString = '')
    {
        $config['base_url'] = base_url($baseUrl);
        $config['total_rows'] = $total;
        $config['per_page'] = self::PER_PAGE;
        $config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = TRUE;
        $config['num_links'] = 5;
        $config['full_tag_open'] = '<nav style="margin-top:1rem"><ul class="pagination">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = '&laquo;';
        $config['last_link'] = '&raquo;';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = ['class' => 'page-link'];
        return $config;
    }

    /**
     * Main dashboard — shows summary of security events.
     */
    public function index()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Registrar', 'Super Admin'], true)) {
            show_error('Access Denied — administrators only.', 403);
        }

        // Summary counts
        $data['blocked_today'] = $this->db
            ->where('event_type', 'IP_BLOCKED')
            ->where('event_time >=', date('Y-m-d 00:00:00'))
            ->count_all_results('security_audit_logs');

        $data['profile_blocked_today'] = $this->db
            ->where('event_type', 'PROFILE_BLOCKED')
            ->where('event_time >=', date('Y-m-d 00:00:00'))
            ->count_all_results('security_audit_logs');

        $data['failed_logins_today'] = $this->db
            ->where('status', 'failed')
            ->where('login_time >=', date('Y-m-d 00:00:00'))
            ->count_all_results('login_logs');

        $data['successful_logins_today'] = $this->db
            ->where('status', 'success')
            ->where('login_time >=', date('Y-m-d 00:00:00'))
            ->count_all_results('login_logs');

        $data['blacklisted_ips'] = $this->db
            ->order_by('blocked_at', 'DESC')
            ->get('ip_blacklist')
            ->result_array();

        // Recent security events
        $data['recent_events'] = $this->db
            ->order_by('event_time', 'DESC')
            ->limit(20)
            ->get('security_audit_logs')
            ->result_array();

        $this->load->view('security_admin_dashboard', $data);
    }

    /**
     * Unified, read-only activity trail for the five operational roles.
     * This deliberately has no purge/delete action: evidence must not be
     * removable from the same screen used to investigate it.
     */
    public function audit_trail()
    {
        if ((string)$this->session->userdata('level') !== 'Super Admin') {
            show_error('Access Denied — Super Admin only.', 403);
        }

        $filters = array(
            'role'   => trim((string)$this->input->get('role')),
            'source' => trim((string)$this->input->get('source')),
            'action' => trim((string)$this->input->get('action')),
            'status' => trim((string)$this->input->get('status')),
            'from'   => trim((string)$this->input->get('from')),
            'to'     => trim((string)$this->input->get('to')),
            'q'      => mb_substr(trim((string)$this->input->get('q')), 0, 100),
        );

        $page = max(1, (int)$this->input->get('page'));
        $offset = ($page - 1) * self::PER_PAGE;
        $total = $this->AuditLogModel->countUnified($filters);

        $data = array(
            'events'     => $this->AuditLogModel->getUnified($filters, self::PER_PAGE, $offset),
            'filters'    => $filters,
            'roles'      => $this->AuditLogModel->trackedRoles(),
            'summary'    => $this->AuditLogModel->auditSummary(30),
            'total'      => $total,
            'page'       => $page,
            'per_page'   => self::PER_PAGE,
        );

        $this->pagination->initialize($this->paginateConfig('Securityadmin/audit_trail', $total));
        $data['pagination'] = $this->pagination->create_links();

        $this->load->view('security_audit_trail', $data);
    }

    /**
     * Forensic report for a specific IP address.
     * Shows all accounts touched, login attempts, device info, and timeline.
     */
    public function investigate()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Registrar', 'Super Admin'], true)) {
            show_error('Access Denied — administrators only.', 403);
        }

        $ip = trim((string)$this->input->get('ip'));
        if ($ip === '') {
            show_error('IP address required. Usage: /Securityadmin/investigate?ip=1.2.3.4', 400);
        }

        // Login attempts from this IP
        $data['login_attempts'] = $this->db
            ->where('ip_address', $ip)
            ->order_by('login_time', 'ASC')
            ->get('login_logs')
            ->result_array();

        // Audit log entries from this IP
        $data['audit_entries'] = $this->db
            ->where('ip_address', $ip)
            ->order_by('event_time', 'ASC')
            ->get('audit_logs')
            ->result_array();

        // Security audit entries
        $data['security_events'] = $this->db
            ->where('ip_address', $ip)
            ->order_by('event_time', 'ASC')
            ->get('security_audit_logs')
            ->result_array();

        // All accounts this IP touched
        $data['accounts'] = $this->db
            ->distinct()
            ->select('username')
            ->where('ip_address', $ip)
            ->order_by('username', 'ASC')
            ->get('login_logs')
            ->result_array();

        // Device fingerprints from this IP
        $data['devices'] = $this->db
            ->distinct()
            ->select('user_agent, device_fingerprint')
            ->where('ip_address', $ip)
            ->get('login_logs')
            ->result_array();

        // Check if IP is blacklisted
        $data['blacklisted'] = $this->db
            ->where('ip_address', $ip)
            ->get('ip_blacklist')
            ->row_array();

        $data['ip'] = $ip;

        $this->load->view('security_investigate', $data);
    }

    /**
     * JSON summary of one IP for the side panel on the security screens.
     * Read-only; the full forensic report is still investigate().
     */
    public function ip_summary()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Registrar', 'Super Admin'], true)) {
            show_error('Access Denied — administrators only.', 403);
        }

        $this->output->set_content_type('application/json');
        $ip = trim((string)$this->input->get('ip'));
        if ($ip === '') {
            $this->output->set_status_header(400)->set_output(json_encode(['ok' => false, 'message' => 'IP address required.']));
            return;
        }

        $counts = $this->db
            ->select("COUNT(*) AS total, SUM(status = 'success') AS success, SUM(status = 'failed') AS failed,
                      COUNT(DISTINCT username) AS accounts, MIN(login_time) AS first_seen, MAX(login_time) AS last_seen", false)
            ->where('ip_address', $ip)
            ->get('login_logs')->row();

        $accounts = $this->db
            ->select("username, COUNT(*) AS attempts, SUM(status = 'failed') AS failed, MAX(login_time) AS last", false)
            ->where('ip_address', $ip)
            ->group_by('username')
            ->order_by('attempts', 'DESC')
            ->limit(8)
            ->get('login_logs')->result_array();

        $recent = $this->db
            ->select('login_time, username, status')
            ->where('ip_address', $ip)
            ->order_by('login_time', 'DESC')
            ->limit(6)
            ->get('login_logs')->result_array();

        $devices = [];
        $agents = $this->db
            ->select('user_agent, COUNT(*) AS n', false)
            ->where('ip_address', $ip)
            ->group_by('user_agent')
            ->order_by('n', 'DESC')
            ->limit(4)
            ->get('login_logs')->result_array();
        foreach ($agents as $agent) {
            $label = device_label($agent['user_agent']);
            if ($label !== '') $devices[] = ['label' => $label, 'count' => (int)$agent['n']];
        }

        $block = $this->db
            ->select('reason, blocked_by, blocked_at, is_permanent, expires_at')
            ->where('ip_address', $ip)
            ->get('ip_blacklist')->row_array();

        $events = $this->db->where('ip_address', $ip)->count_all_results('security_audit_logs');

        $when = function ($value) {
            $ts = $value ? strtotime((string)$value) : false;
            return $ts ? date('M j, Y · g:i A', $ts) : '';
        };

        $this->output->set_output(json_encode([
            'ok'        => true,
            'ip'        => $ip,
            'total'     => (int)($counts->total ?? 0),
            'success'   => (int)($counts->success ?? 0),
            'failed'    => (int)($counts->failed ?? 0),
            'accounts'  => (int)($counts->accounts ?? 0),
            'firstSeen' => $when($counts->first_seen ?? null),
            'lastSeen'  => $when($counts->last_seen ?? null),
            'events'    => (int)$events,
            'blocked'   => $block ? [
                'reason'    => (string)$block['reason'],
                'by'        => (string)$block['blocked_by'],
                'at'        => $when($block['blocked_at']),
                'permanent' => (bool)$block['is_permanent'],
                'expires'   => $when($block['expires_at']),
            ] : null,
            'topAccounts' => array_map(function ($a) use ($when) {
                return ['username' => (string)$a['username'], 'attempts' => (int)$a['attempts'], 'failed' => (int)$a['failed'], 'last' => $when($a['last'])];
            }, $accounts),
            'recent' => array_map(function ($r) use ($when) {
                return ['time' => $when($r['login_time']), 'username' => (string)$r['username'], 'status' => (string)$r['status']];
            }, $recent),
            'devices' => $devices,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Add an IP to the blacklist.
     */
    public function block_ip()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Super Admin'], true)) {
            show_error('Access Denied.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Securityadmin');
            return;
        }

        $ip = trim((string)$this->input->post('ip_address'));
        $reason = trim((string)$this->input->post('reason'));
        $permanent = (int)$this->input->post('is_permanent');

        if ($ip === '' || $reason === '') {
            $this->session->set_flashdata('danger', 'IP address and reason are required.');
            redirect('Securityadmin');
            return;
        }

        $this->db->replace('ip_blacklist', [
            'ip_address'      => $ip,
            'reason'          => $reason,
            'blocked_by'      => $this->session->userdata('username'),
            'is_permanent'    => $permanent,
            'incident_reference' => trim((string)$this->input->post('incident_reference')),
        ]);

        $this->securityaudit->event('IP_BLOCKED', [
            'status'      => 'success',
            'description' => 'Admin manually blocked IP: ' . $reason,
            'target'      => $ip,
        ]);

        $this->session->set_flashdata('success', 'IP ' . htmlspecialchars($ip) . ' has been blocked.');
        redirect('Securityadmin');
    }

    /**
     * Remove an IP from the blacklist.
     */
    public function unblock_ip()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Super Admin'], true)) {
            show_error('Access Denied.', 403);
        }

        $ip = trim((string)$this->input->post('ip_address'));
        if ($ip === '') {
            redirect('Securityadmin');
            return;
        }

        $this->db->where('ip_address', $ip)->delete('ip_blacklist');

        $this->securityaudit->event('IP_UNBLOCKED', [
            'status'      => 'success',
            'description' => 'Admin unblocked IP: ' . $ip,
            'target'      => $ip,
        ]);

        $this->session->set_flashdata('success', 'IP ' . htmlspecialchars($ip) . ' has been unblocked.');
        redirect('Securityadmin');
    }

    /**
     * View all login activity with filtering.
     */
    public function login_activity()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Admin', 'HR Admin', 'Registrar', 'Super Admin'], true)) {
            show_error('Access Denied — administrators only.', 403);
        }

        // Get filter parameters
        $ip_filter = trim((string)$this->input->get('ip'));
        $user_filter = trim((string)$this->input->get('username'));
        $status_filter = trim((string)$this->input->get('status'));
        $page = max(1, (int)$this->input->get('page'));
        $offset = ($page - 1) * self::PER_PAGE;

        // Build query with filters
        $this->db->order_by('login_time', 'DESC');
        if ($ip_filter !== '') $this->db->where('ip_address', $ip_filter);
        if ($user_filter !== '') $this->db->where('username', $user_filter);
        if ($status_filter !== '') $this->db->where('status', $status_filter);

        // Count total for pagination
        $total = $this->db->count_all_results('login_logs', false);

        // Get the page
        $this->db->limit(self::PER_PAGE, $offset);
        $data['logins'] = $this->db->get()->result_array();
        $data['ip_filter'] = $ip_filter;
        $data['user_filter'] = $user_filter;
        $data['status_filter'] = $status_filter;
        $data['total'] = $total;
        $data['page'] = $page;
        $data['per_page'] = self::PER_PAGE;

        // Pagination links
        $this->pagination->initialize($this->paginateConfig('Securityadmin/login_activity', $total));
        $data['pagination'] = $this->pagination->create_links();

        $this->load->view('security_login_activity', $data);
    }

    /**
     * Clear old login logs (frees DB storage).
     */
    public function purge_login_logs()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Securityadmin/login_activity');
            return;
        }

        $days = (int)$this->input->post('days');
        $deleteAll = ($days === 0);

        if ($deleteAll) {
            $this->db->empty_table('login_logs');
            $this->securityaudit->event('LOGIN_LOGS_PURGED', [
                'status'      => 'success',
                'description' => 'Purged ALL login logs',
            ]);
            $this->session->set_flashdata('success', 'Deleted ALL login logs.');
        } else {
            if ($days < 1) $days = 90;
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $this->db->where('login_time <', $cutoff)->delete('login_logs');
            $this->securityaudit->event('LOGIN_LOGS_PURGED', [
                'status'      => 'success',
                'description' => 'Purged login logs older than ' . $days . ' days',
            ]);
            $this->session->set_flashdata('success', 'Deleted login logs older than ' . $days . ' days.');
        }
        redirect('Securityadmin/login_activity');
    }

    /**
     * Purge security audit logs (Recent Security Events).
     * Super Admin only.
     */
    public function purge_security_events()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Securityadmin');
            return;
        }

        $days = (int)$this->input->post('days');
        $deleteAll = ($days === 0);

        if ($deleteAll) {
            $count = $this->db->count_all('security_audit_logs');
            $this->db->empty_table('security_audit_logs');
            // The purge itself becomes the first record of the new chain, so
            // the next daily report can explain the reset instead of only
            // alarming about it.
            $this->securityaudit->event('AUDIT_LOG_PURGED', array(
                'module'      => 'security',
                'description' => 'Audit log emptied by ' . (string)$this->session->userdata('username')
                               . ' (' . $count . ' records deleted)',
            ));
            $this->session->set_flashdata('success', 'Deleted ALL ' . $count . ' security events. The purge is recorded as the first entry of the new log.');
        } else {
            if ($days < 1) $days = 30;
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $count = $this->db->where('event_time <', $cutoff)->count_all_results('security_audit_logs');
            $this->db->where('event_time <', $cutoff)->delete('security_audit_logs');
            $this->securityaudit->event('AUDIT_LOG_PURGED', array(
                'module'      => 'security',
                'description' => 'Purged ' . $count . ' audit records older than ' . $days . ' days',
            ));
            $this->session->set_flashdata('success', 'Deleted ' . $count . ' security events older than ' . $days . ' days.');
        }
        redirect('Securityadmin');
    }

    /**
     * Purge device records (Security/devices page).
     * Super Admin only.
     */
    public function purge_devices()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Security/devices');
            return;
        }

        $days = (int)$this->input->post('days');
        $deleteAll = ($days === 0);

        if ($deleteAll) {
            $count = $this->db->count_all('user_devices');
            $this->db->empty_table('user_devices');
            $this->session->set_flashdata('success', 'Deleted ALL ' . $count . ' device records.');
        } else {
            if ($days < 1) $days = 30;
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $count = $this->db->where('last_seen_at <', $cutoff)->count_all_results('user_devices');
            $this->db->where('last_seen_at <', $cutoff)->delete('user_devices');
            $this->session->set_flashdata('success', 'Deleted ' . $count . ' devices not seen in ' . $days . ' days.');
        }
        redirect('Security/devices');
    }

    /**
     * Purge session records (Security/sessions page).
     * Super Admin only. Does NOT kick out currently-logged-in users —
     * it only clears the session tracking table.
     */
    public function purge_sessions()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Security/sessions');
            return;
        }

        $days = (int)$this->input->post('days');
        $deleteAll = ($days === 0);

        if ($deleteAll) {
            $count = $this->db->count_all('user_security_sessions');
            $this->db->empty_table('user_security_sessions');
            $this->session->set_flashdata('success', 'Deleted ALL ' . $count . ' session records. (Active users are NOT kicked out — only tracking data cleared.)');
        } else {
            if ($days < 1) $days = 7;
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $count = $this->db->where('last_activity_at <', $cutoff)->count_all_results('user_security_sessions');
            $this->db->where('last_activity_at <', $cutoff)->delete('user_security_sessions');
            $this->session->set_flashdata('success', 'Deleted ' . $count . ' sessions older than ' . $days . ' days.');
        }
        redirect('Security/sessions');
    }
}

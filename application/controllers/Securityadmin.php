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
        $this->load->helper('url');
        $this->load->database();
        $this->load->library('securityaudit');
        $this->load->library('pagination');
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
     * View forensic captures (photos, GPS, device fingerprints).
     */
    public function forensic_captures()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        $ip_filter = trim((string)$this->input->get('ip'));
        $user_filter = trim((string)$this->input->get('username'));
        $page = max(1, (int)$this->input->get('page'));
        $offset = ($page - 1) * self::PER_PAGE;

        $this->db->order_by('captured_at', 'DESC');
        if ($ip_filter !== '') $this->db->where('ip_address', $ip_filter);
        if ($user_filter !== '') $this->db->where('username', $user_filter);

        $total = $this->db->count_all_results('login_forensic_captures', false);

        $this->db->limit(self::PER_PAGE, $offset);
        $data['captures'] = $this->db->get()->result_array();
        $data['ip_filter'] = $ip_filter;
        $data['user_filter'] = $user_filter;
        $data['total'] = $total;
        $data['page'] = $page;
        $data['per_page'] = self::PER_PAGE;

        $this->pagination->initialize($this->paginateConfig('Securityadmin/forensic_captures', $total));
        $data['pagination'] = $this->pagination->create_links();

        $this->load->view('security_forensic_captures', $data);
    }

    /**
     * View a single forensic capture with full details (photo, GPS map).
     */
    public function forensic_detail()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        $id = (int)$this->input->get('id');
        if ($id <= 0) {
            show_error('Capture ID required.', 400);
        }

        $data['capture'] = $this->db->where('id', $id)->get('login_forensic_captures')->row_array();
        if (!$data['capture']) {
            show_error('Capture not found.', 404);
        }

        $this->load->view('security_forensic_detail', $data);
    }

    /**
     * Delete a single forensic capture (photo file + DB row).
     */
    public function delete_capture()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Securityadmin/forensic_captures');
            return;
        }

        $id = (int)$this->input->post('id');
        if ($id <= 0) {
            redirect('Securityadmin/forensic_captures');
            return;
        }

        $row = $this->db->where('id', $id)->get('login_forensic_captures')->row_array();
        if (!$row) {
            $this->session->set_flashdata('danger', 'Capture not found.');
            redirect('Securityadmin/forensic_captures');
            return;
        }

        // Delete the photo file from disk
        if (!empty($row['photo_path'])) {
            $fullPath = FCPATH . $row['photo_path'];
            if (is_file($fullPath)) @unlink($fullPath);
        }

        $this->db->where('id', $id)->delete('login_forensic_captures');

        $this->securityaudit->event('FORENSIC_DELETED', [
            'status'      => 'success',
            'description' => 'Deleted forensic capture #' . $id . ' for ' . ($row['username'] ?? 'unknown'),
            'target'      => $row['username'] ?? '',
        ]);

        $this->session->set_flashdata('success', 'Capture deleted.');
        redirect('Securityadmin/forensic_captures');
    }

    /**
     * Delete all forensic captures older than a given number of days.
     * Frees disk space and DB storage in one action.
     */
    public function purge_old_captures()
    {
        $level = (string)$this->session->userdata('level');
        if (!in_array($level, ['Super Admin'], true)) {
            show_error('Access Denied — Super Admin only.', 403);
        }

        if ($this->input->method() !== 'post') {
            redirect('Securityadmin/forensic_captures');
            return;
        }

        $days = (int)$this->input->post('days');
        $deleteAll = ($days === 0);

        if ($deleteAll) {
            $rows = $this->db->get('login_forensic_captures')->result_array();
            $deleted = 0;
            foreach ($rows as $r) {
                if (!empty($r['photo_path'])) {
                    $fullPath = FCPATH . $r['photo_path'];
                    if (is_file($fullPath)) @unlink($fullPath);
                }
                $deleted++;
            }
            $this->db->empty_table('login_forensic_captures');
            $this->securityaudit->event('FORENSIC_PURGED', [
                'status'      => 'success',
                'description' => 'Purged ALL ' . $deleted . ' forensic captures',
            ]);
            $this->session->set_flashdata('success', 'Deleted ALL ' . $deleted . ' captures.');
        } else {
            if ($days < 1) $days = 30;
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $rows = $this->db->where('captured_at <', $cutoff)->get('login_forensic_captures')->result_array();
            $deleted = 0;
            foreach ($rows as $r) {
                if (!empty($r['photo_path'])) {
                    $fullPath = FCPATH . $r['photo_path'];
                    if (is_file($fullPath)) @unlink($fullPath);
                }
                $deleted++;
            }
            $this->db->where('captured_at <', $cutoff)->delete('login_forensic_captures');
            $this->securityaudit->event('FORENSIC_PURGED', [
                'status'      => 'success',
                'description' => 'Purged ' . $deleted . ' forensic captures older than ' . $days . ' days',
            ]);
            $this->session->set_flashdata('success', 'Deleted ' . $deleted . ' captures older than ' . $days . ' days.');
        }
        redirect('Securityadmin/forensic_captures');
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
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Datahealth — read-only integrity report over the student records.
 *
 * The registrar edits and renames student numbers here daily; over the
 * years that leaves residue — attendance/payment rows keyed to deleted
 * students, near-duplicate IDs that differ only by the dash, mistyped
 * emails, accounts with no profile row. This page surfaces all of it so
 * staff can clean it up instead of discovering it at report time.
 *
 * Everything is read-only: the page reports, it never repairs. Any fix
 * action links out to the normal edit screens (e.g. Page/editSignup) so
 * changes still go through the usual validation, cascade, and audit log.
 *
 * Admin + Super Admin — the report exposes student PII (names, emails).
 * The AuthGuard 'datahealth/*' rule and this controller check are
 * deliberate duplication, same convention as Errorlogs and Backup.
 */
class Datahealth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('StudentModel');
        $this->load->model('SettingsModel'); // used by top-nav-bar
        $this->load->model('AuditLogModel');
    }

    /** Second layer behind AuthGuard's 'datahealth/*' role rule. */
    private function requireDataHealthRole()
    {
        if (is_cli()) {
            return;
        }
        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login');
        }
        if (!in_array((string)$this->session->userdata('level'), ['Admin', 'Super Admin'], true)) {
            show_error('Access Denied', 403);
        }
    }

    public function index()
    {
        $this->requireDataHealthRole();
        $data['report'] = $this->StudentModel->dataHealthReport();
        $this->load->view('datahealth_index', $data);
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Backup — download a full database dump straight from the app.
 *
 * index()    — backup page: database info + download button.
 * download() — generates a phpMyAdmin-compatible .sql file and sends it
 *              as an attachment. The dump is written to a temp file first
 *              (streams, flat memory), then readfile()'d, so the download
 *              only starts once the file is complete and its size is known.
 *
 * Super Admin only. The dump contains every record including password
 * hashes — the AuthGuard rule and this controller check are deliberate
 * duplication, matching how security/* is protected.
 */
class Backup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->model('AuditLogModel');
        $this->load->library('DbBackup');
    }

    /**
     * Second layer behind AuthGuard's 'backup/*' role rule — both are
     * deliberate, same convention as FbmsoPersonnels and Security. Kept in
     * the methods (not the constructor) so the guard gets first shot at
     * redirecting guests and logging denied-role attempts.
     */
    private function requireSuperAdmin()
    {
        if (is_cli()) {
            return;
        }
        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login');
        }
        if ((string)$this->session->userdata('level') !== 'Super Admin') {
            show_error('Access Denied — Super Admin only.', 403);
        }
    }

    public function index()
    {
        $this->requireSuperAdmin();

        $data['stats'] = $this->dbbackup->stats();
        $this->load->view('backup_index', $data);
    }

    public function download()
    {
        $this->requireSuperAdmin();

        @set_time_limit(0);

        // Hold the session lock for as short a time as possible — a large
        // dump can take a while and the admin's other tabs shouldn't stall.
        $username = (string)$this->session->userdata('username');
        $db = (string)$this->db->database;
        session_write_close();

        $tmp = tempnam(sys_get_temp_dir(), 'fbmso_dump_');
        if ($tmp === false) {
            show_error('Could not create a temporary file for the backup.', 500);
            return;
        }
        // Always clean up the temp file, even if the connection dies
        // mid-download or PHP fatals partway through.
        register_shutdown_function(static function () use ($tmp) {
            @unlink($tmp);
        });

        try {
            $stats = $this->dbbackup->dumpTo($tmp);
        } catch (Exception $e) {
            try {
                $this->AuditLogModel->write(
                    'export', 'Database Backup', null, null, null,
                    array('database' => $db, 'error' => $e->getMessage()),
                    0, 'Database backup failed',
                    array('_actor_username' => $username)
                );
            } catch (Throwable $ignored) {
            }
            show_error('Database backup failed: ' . $e->getMessage(), 500);
            return;
        }

        $filename = $db . '_' . date('Y-m-d_H-i-s') . '.sql';

        try {
            $this->AuditLogModel->write(
                'export', 'Database Backup', null, null, null,
                array(
                    'database' => $db,
                    'file'     => $filename,
                    'tables'   => $stats['tables'],
                    'rows'     => $stats['rows'],
                    'bytes'    => $stats['bytes'],
                    'seconds'  => $stats['seconds'],
                ),
                1, 'Downloaded database backup',
                array('_actor_username' => $username)
            );
        } catch (Throwable $ignored) {
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($tmp);
        exit;
    }
}

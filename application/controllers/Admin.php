<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->dbutil(); // Load Database Utility
        $this->load->helper(array('file', 'download'));
        $this->load->library('session');
    }

    public function backup_database()
    {
        // Database backups contain every user, password hash, and token —
        // restrict to Super Admin only.
        $level = (string)$this->session->userdata('level');
        if (strcasecmp($level, 'Super Admin') !== 0) {
            show_error('Forbidden — Super Admin only.', 403);
            return;
        }

        $this->load->dbutil();
        $this->load->helper('download');

        $prefs = array(
            'format'      => 'zip',
            'filename'    => 'srms_backup.sql',
            'add_drop'    => TRUE,
            'add_insert'  => TRUE,
            'newline'     => "\n"
        );

        $backup = $this->dbutil->backup($prefs);

        $db_name = 'backup-on-' . date("Y-m-d-H-i-s") . '.zip';

        // Direct download to browser
        force_download($db_name, $backup);
    }
}

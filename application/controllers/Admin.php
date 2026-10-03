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

        // The real implementation lives in Backup::download — a streaming
        // phpMyAdmin-compatible dump. (The old dbutil->backup() buffered the
        // whole database in memory and could not finish on a large DB.)
        redirect('Backup/download');
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Keeps every logged-in session's semester/sy aligned with the active term
 * in o_srms_settings. Without this, the term is only stamped at login, so
 * flipping the active term mid-day would leave existing sessions stuck on
 * the old term until each user signs out and back in.
 */
class TermSyncHook
{
    public function sync()
    {
        $CI = &get_instance();

        $CI->load->library('session');
        if ($CI->session->userdata('logged_in') !== TRUE) {
            return;
        }

        $CI->load->library('term');
        $CI->term->syncSession();
    }
}

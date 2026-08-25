<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Applies any missing database indexes, once, then gets out of the way.
 *
 * Named *Hook so it cannot shadow the Dbtuner library — PHP class names are
 * case-insensitive.
 */
class DbTunerHook
{
    public function tune()
    {
        $CI = &get_instance();

        $CI->load->library('dbtuner');
        $CI->dbtuner->ensure();
    }
}

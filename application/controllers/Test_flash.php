<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Test_flash extends CI_Controller
{
    public function index()
    {
        $this->session->set_flashdata('info_message', 'Registration successful. Check your email for login credentials.');
        redirect('login');
    }
}

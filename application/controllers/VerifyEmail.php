<?php
defined('BASEPATH') or exit('No direct script access allowed');

class VerifyEmail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('EmailVerificationModel');
        $this->load->helper('url');
    }

    public function index()
    {
        $this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header('Expires: 0');

        $token = trim((string)$this->input->get('token', true));
        if ($token !== '') {
            $result = $this->EmailVerificationModel->verifyToken($token);
            if (!empty($result['ok'])) {
                $this->session->set_flashdata('info_message', (string)$result['message']);
            } else {
                $this->session->set_flashdata('auth_error', (string)$result['message']);
            }
            redirect('login');
            return;
        }

        if ($this->input->method(true) === 'POST') {

        $email = strtolower(trim((string)$this->input->post('email', true)));
    
        $result = $this->EmailVerificationModel->queueForEmail($email);
    
        // SUCCESS: redirect to home/login and show success message there
        if (!empty($result['ok'])) {
    
            $this->session->set_flashdata(
                'info_message',
                (string)$result['message']
            );
    
            redirect('login');
            return;
        }
    
        // ERROR: stay on verification page
        $this->session->set_flashdata(
            'verification_error',
            (string)$result['message']
        );
    
        $this->session->set_flashdata(
            'verification_email',
            $email
        );
    
        redirect('verify-email');
        return;
    }
    
    $this->load->view('verify_email');
    }
}

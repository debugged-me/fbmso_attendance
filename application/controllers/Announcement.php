<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Announcement extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('AnnouncementModel');
        $this->load->library('session');
        $this->load->helper(array('form', 'url'));

        if ($this->session->userdata('logged_in') !== TRUE) {
            redirect('login'); 
        }
    }

    public function index() {
        $data['announcement'] = $this->AnnouncementModel->getAnnouncements();
        $this->load->view('announcement', $data);
    }

    public function uploadAnnouncement()
{
    $title       = trim($this->input->post('title', TRUE));
    $message     = trim($this->input->post('message', TRUE));
    $date_expire = $this->input->post('date_expire', TRUE);

    if ($title === '' || $message === '') {
        $this->session->set_flashdata('error', 'Please complete Title and Message.');
        return redirect('Announcement');
    }

    $filename = null;
    if (!empty($_FILES['nonoy']['name'])) {
        $config = [
            'upload_path'   => './upload/announcements/',
            'allowed_types' => 'jpg|jpeg|png|gif',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('nonoy')) {
            $this->session->set_flashdata('error', 'Image upload failed: ' . $this->upload->display_errors('', ''));
            return redirect('Announcement');
        }

        $file_data = $this->upload->data();
        $filename  = $file_data['file_name'];
    }

    $expire_val = (!empty($date_expire)) ? date('Y-m-d', strtotime($date_expire)) : null;

   $data = [
    'title'        => $title,
    'message'      => $message, 
    'image'        => $filename, 
    'author'       => $this->session->userdata('username'),
    'datePosted'   => date('Y-m-d'),
    'audience'     => 'Students',
    'date_expire'  => $expire_val
];


    if ($this->AnnouncementModel->insertAnnouncement($data)) {
        $this->session->set_flashdata('success', 'Announcement posted successfully.');
    } else {
        $this->session->set_flashdata('error', 'Database insertion failed.');
    }

    return redirect('Announcement');
}


    public function delete($id = null) {
        if ($id) {
            $announcement = $this->AnnouncementModel->getAnnouncementByID($id);
            if ($announcement) {
$file_path = './upload/announcements/' . $announcement->image;
                if (!empty($announcement->image) && file_exists($file_path)) {
    unlink($file_path);
}

                $this->AnnouncementModel->deleteAnnouncement($id);
                $this->session->set_flashdata('success', 'Announcement deleted.');
            } else {
                $this->session->set_flashdata('error', 'Announcement not found.');
            }
        }
        redirect('Announcement');
    }
    public function getActiveForStudent() {
    $data['data'] = $this->AnnouncementModel->getActiveAnnouncementsFor('Students');
    $this->load->view('dashboard_student', $data);
    
}

private function audienceForCurrentUser() {
    $level = (string) $this->session->userdata('level');
    $pos   = (string) $this->session->userdata('position');

    $val = strtolower(trim($level ?: $pos));

    if ($val === 'student' || $val === 'students') return 'Students';

    if ($val === 'registrar' || $val === 'head registrar') return 'Registrar';

    if (strpos($val, 'instructor') !== false || strpos($val, 'teacher') !== false) return 'Instructors';

    if ($val === 'admin') return 'All';

    return 'Students';
}



public function active() {
    $aud  = $this->audienceForCurrentUser();
    $list = ($aud === 'Students')
        ? $this->AnnouncementModel->getActiveAnnouncementsForMany(['All', 'Students'])
        : $this->AnnouncementModel->getAllActiveAnnouncements();

    $out = [];
    foreach (array_slice($list, 0, 10) as $a) {
        $out[] = [
            'aID'        => (int)$a->aID,
            'title'      => (string)$a->title,
            'message'    => (string)$a->message,
            'image'      => (string)($a->image ?? ''),
            'datePosted' => (string)$a->datePosted,
            'audience'   => (string)$a->audience,
            'date_expire'=> (string)($a->date_expire ?? '')
        ];
    }

    $this->output->set_content_type('application/json')->set_output(json_encode($out));
}

public function activeCount() {
    $aud = $this->audienceForCurrentUser();
    $cnt = ($aud === 'Students')
        ? $this->AnnouncementModel->countActiveAnnouncementsForMany(['All', 'Students'])
        : $this->AnnouncementModel->countAllActiveAnnouncements();

    $this->output->set_content_type('application/json')->set_output(json_encode(['count' => (int)$cnt]));
}


}
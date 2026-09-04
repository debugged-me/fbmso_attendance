<?php defined('BASEPATH') or exit('No direct script access allowed');

class FbmsoPersonnels extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('FbmsoPersonnelsModel', 'Team');
        $this->load->model('SettingsModel'); // <-- add
        $this->load->helper(['form', 'url']);
        $this->load->library('upload');
    }

    /**
     * Roles allowed to administer personnel records.
     * AuthGuard enforces this too; keeping it here as well means neither
     * layer silently becomes the only one.
     */
    private function require_manager()
    {
        $level = trim((string)$this->session->userdata('level'));

        if (!in_array($level, array('Super Admin', 'Admin', 'IT', 'HR Admin', 'Human Resource'), true)) {
            show_error('Access Denied', 403);
        }
    }

    /**
     * Destructive actions must not be reachable by GET: a link or image tag
     * would otherwise be enough to fire them from any page a signed-in user
     * happens to visit.
     */
    private function require_post()
    {
        if (strtoupper((string)$this->input->method()) !== 'POST') {
            show_error('Method Not Allowed', 405);
        }
    }

    // DELETE this if you added it earlier:
    // $this->load->model('SettingsModel');

    private function school_info_block()
    {
        // Pull SchoolName / SchoolAddress / letterhead_web from o_srms_settings
        if ($this->db->table_exists('o_srms_settings')) {
            $q = $this->db->select('SchoolName, SchoolAddress, letterhead_web')
                ->from('o_srms_settings')
                ->limit(1)
                ->get();
            if ($q && $q->num_rows() > 0) {
                // Your template expects $data18[0]->SchoolName and ->SchoolAddress
                return ['data18' => $q->result()];
            }
        }

        // Fallback if table is missing/empty
        return ['data18' => [(object)[
            'SchoolName'     => 'Faculty of Business & Management Student Org.',
            'SchoolAddress'  => '',
            'letterhead_web' => null
        ]]];
    }


    /** Public landing page */
    public function index()
    {
        $data = $this->school_info_block();
        $data['people'] = $this->Team->all_active();
        $this->load->view('fbmso_team_public', $data);
    }

    /** Admin manage page */
    public function manage()
    {
        $this->require_manager();

        $data = $this->school_info_block();
        $data['people'] = $this->Team->all();
        $this->load->view('fbmso_team_manage', $data);
    }

    public function save()
    {
        $this->require_manager();
        $this->require_post();

        $id = $this->input->post('id');
        $payload = [
            'full_name'  => $this->input->post('full_name', true),
            'title'      => $this->input->post('title', true),
            'bio'        => $this->input->post('bio', false),
            'sort_order' => (int)$this->input->post('sort_order'),
            'is_active'  => (int)$this->input->post('is_active', 1),
        ];

        if (!empty($_FILES['photo']['name'])) {
            $path = FCPATH . 'upload/banners/';
            if (!is_dir($path)) @mkdir($path, 0755, true);
            $config = [
                'upload_path'   => $path,
                'allowed_types' => 'jpg|jpeg|png|webp',
                'max_size'      => 4096,
                'file_name'     => 'fbmso_' . time() . '_' . mt_rand(1000, 9999),
                'overwrite'     => false,
            ];
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('photo')) {
                $this->session->set_flashdata('danger', $this->upload->display_errors('', ''));
                redirect('FbmsoPersonnels/manage');
                return;
            }
            $payload['photo'] = $this->upload->data('file_name');
        }

        $this->Team->upsert($payload, $id ?: null);
        $this->session->set_flashdata('success', 'Saved successfully.');
        redirect('FbmsoPersonnels/manage');
    }

    public function delete($id)
    {
        $this->require_manager();
        $this->require_post();

        $this->Team->delete((int)$id);
        $this->session->set_flashdata('success', 'Removed.');
        redirect('FbmsoPersonnels/manage');
    }

    public function toggle($id)
    {
        $this->require_manager();
        $this->require_post();

        $active = (int)$this->input->get('v', 1);
        $this->Team->toggle((int)$id, $active);
        redirect('FbmsoPersonnels/manage');
    }
}

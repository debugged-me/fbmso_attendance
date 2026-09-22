<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reports extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ReportsModel');
        $this->load->model('SettingsModel');
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    public function index()
    {
        // Defaults from session (badges use these)
        $sy  = $this->input->get('sy', true)  ?: $this->session->userdata('sy');
        $sem = $this->input->get('sem', true) ?: $this->session->userdata('semester');

        // Optional filters for “by section” (hidden in UI, but kept)
        $course    = $this->input->get('course', true);
        $yearLevel = $this->input->get('yearLevel', true);

        $data['sy']  = $sy;
        $data['sem'] = $sem;
        $data['school'] = $this->SettingsModel->getSchoolInfo();

        // Enrollment aggregates (semesterstude)
        $data['by_yearlevel']   = $this->ReportsModel->students_by_yearlevel($sy, $sem);
        $data['by_course']      = $this->ReportsModel->students_by_course($sy, $sem);
        $data['by_section']     = $this->ReportsModel->students_by_section($sy, $sem, $course, $yearLevel);

        // Sections per course (course_table + course_sections)
        $data['sections_count'] = $this->ReportsModel->sections_count_by_course();

        // Events / Attendance (activities + activity_attendance + studentsignup)
        $data['events_summary']    = $this->ReportsModel->events_summary($sy, $sem);
        $data['events_total']      = $this->ReportsModel->events_total($sy, $sem);
        $data['event_scans']       = $this->ReportsModel->event_scans_total($sy, $sem);
        $data['recent_attendance'] = $this->ReportsModel->attendance_recent($sy, $sem, 100);

        // Lists (useful if you re-enable filters later)
        $data['courses']    = $this->ReportsModel->courses_list();
        $data['yearlevels'] = $this->ReportsModel->yearlevels_list();

        $this->load->view('reports_index', $data);
    }
}

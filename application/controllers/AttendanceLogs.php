<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AttendanceLogs extends CI_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url','form']);
        $this->load->library(['session']);
		$this->load->library('term');
        $this->load->model('Activities_model','ActivitiesModel');
        $this->load->model('Activity_attendance_model','ActAttModel');
    }
public function index()
{
    $activity_id = (int)$this->input->get('activity_id');
    $section     = trim((string)$this->input->get('section'));
    $yearLevel   = trim((string)$this->input->get('year_level'));
    $date        = trim((string)$this->input->get('date'));      // YYYY-MM-DD (optional)
    $session     = trim((string)$this->input->get('session'));   // am|pm|eve (optional)

	list($use_sem, $use_sy) = $this->term->current();

    // Build course lookup (CourseCode -> variants) from course_table
    $courseCatalog = $this->db->select('courseid, CourseCode, CourseDescription, Major')
                              ->from('course_table')
                              ->get()->result();
    $normalizeCourseKey = static function ($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/\s+/', ' ', $value);
        return strtoupper($value);
    };
    $courseLookup = [];
    foreach ($courseCatalog as $course) {
        $code  = strtoupper(trim((string)$course->CourseCode));
        if ($code === '') {
            continue;
        }
        $description = trim((string)$course->CourseDescription);
        $major       = trim((string)$course->Major);
        $candidates  = [$code, $description, $major];
        if ($description !== '' && $major !== '') {
            $candidates[] = $description . ' ' . $major;
            $candidates[] = $description . ' - ' . $major;
            $candidates[] = $description . ' Major in ' . $major;
            $candidates[] = $description . ' major in ' . $major;
        }
        foreach ($candidates as $candidate) {
            $key = $normalizeCourseKey($candidate);
            if ($key === '') {
                continue;
            }
            $courseLookup[$key] = $code;
        }
    }

    $data = [
        'activities' => $this->db->select('activity_id, title')
                                  ->from('activities')->order_by('start_at','DESC')->get()->result(),
        'activity_id'=> $activity_id ?: null,
        'section'    => $section ?: '',
        'year_level' => $yearLevel ?: '',
        'date'       => $date ?: '',
        'session'    => $session ?: '',
        'rows'       => [],
        'course_lookup' => $courseLookup,
    ];

    if ($activity_id > 0) {
        // 1st pass: with current filters
        $rows = $this->ActAttModel->report_by_activity_section(
            $activity_id,
            $section ?: null,
            $date ?: null,
            $session ?: null,
            $yearLevel ?: null,
            $use_sy,
            $use_sem
        );

        if (empty($rows)) {
            // Fallback: show ALL logs for the activity so you SEE data
            $rowsAll = $this->ActAttModel->report_by_activity_section(
                $activity_id, null, null, null, null,
                $use_sy, $use_sem
            );
            if (!empty($rowsAll)) {
                $data['rows'] = $rowsAll;
                $data['filter_note'] = 'No logs matched your filters - showing all logs for this activity.';
            } else {
                $data['rows'] = [];
            }
        } else {
            $data['rows'] = $rows;
        }
    }

    // Section / Year Level dropdowns from course sections master list (ensures manual selection)
    $data['sections'] = $this->db
        ->select('DISTINCT cs.section, cs.year_level, COALESCE(ct.CourseCode, ct.CourseDescription) AS course_code', false)
        ->from('course_sections cs')
        ->where('cs.is_active', 1)
        ->join('course_table ct', 'ct.courseid = cs.courseid', 'left')
        ->order_by('ct.CourseCode', 'ASC')
        ->where('cs.section IS NOT NULL', null, false)
        ->where("TRIM(cs.section) <> ''", null, false)
        ->order_by('cs.year_level', 'ASC')
        ->order_by('cs.section', 'ASC')
        ->get()
        ->result();

    $data['year_levels'] = $this->db
        ->select('DISTINCT cs.year_level', false)
        ->from('course_sections cs')
        ->where('cs.is_active', 1)
        ->where('cs.year_level IS NOT NULL', null, false)
        ->where("TRIM(cs.year_level) <> ''", null, false)
        ->order_by('cs.year_level', 'ASC')
        ->get()
        ->result();

    $this->load->view('attendance_logs_index', $data);
}
public function activity($activity_id)
{
    $activity_id = (int)$activity_id;
    $section     = trim((string)$this->input->get('section'));
    $date        = trim((string)$this->input->get('date'));
    $session     = trim((string)$this->input->get('session'));
    $yearLevel   = trim((string)$this->input->get('year_level'));

    $activity = $this->ActivitiesModel->find($activity_id);
    if (!$activity) show_404();

	list($use_sem, $use_sy) = $this->term->current();

    $rows = $this->ActAttModel->report_by_activity_section(
        $activity_id,
        $section ?: null,
        $date ?: null,
        $session ?: null,
        $yearLevel ?: null,
        $use_sy,
        $use_sem
    );

    $this->load->view('attendance_logs_report', [
        'activity' => $activity,
        'rows'     => $rows,
        'filters'  => [
            'section'    => $section,
            'date'       => $date,
            'session'    => $session,
            'year_level' => $yearLevel,
        ],
    ]);
}
public function export_csv($activity_id)
{
    $activity_id = (int)$activity_id;
    $section     = trim((string)$this->input->get('section'));
    $date        = trim((string)$this->input->get('date'));
    $session     = trim((string)$this->input->get('session'));
    $yearLevel   = trim((string)$this->input->get('year_level'));

    $activity = $this->ActivitiesModel->find($activity_id);
    if (!$activity) show_404();

	list($use_sem, $use_sy) = $this->term->current();

    $rows = $this->ActAttModel->report_by_activity_section(
        $activity_id,
        $section ?: null,
        $date ?: null,
        $session ?: null,
        $yearLevel ?: null,
        $use_sy,
        $use_sem
    );

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="attendance_'.$activity_id.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['StudentNumber','StudentName','Course','YearLevel','Section','Session','Check-In','Check-Out','Duration(min)','Remarks','Checked-In By']);
    foreach ($rows as $r) {
        $mins = ($r->checked_out_at && $r->checked_in_at)
            ? round((strtotime($r->checked_out_at) - strtotime($r->checked_in_at)) / 60)
            : null;
        fputcsv($out, [
            $r->student_number,
            $r->student_name,
            $r->course, $r->YearLevel, $r->section,
            strtoupper($r->session ?: ''),
            $r->checked_in_at, $r->checked_out_at, $mins,
            $r->remarks, $r->checked_in_by
        ]);
    }
    fclose($out);
    exit;
}

}

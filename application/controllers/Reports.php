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

        if ($this->input->get('print', true) === '1') {
            $this->renderPrintDocument($data);
            return;
        }

        $this->load->view('reports_index', $data);
    }

    // Dedicated print document (same letterhead format as the accounting
    // reports) rendered in a new tab via ?print=1&sections=a,b,c
    private function renderPrintDocument(array $d)
    {
        $allowed = ['by_yearlevel', 'by_course', 'sections_per_course', 'by_section', 'events', 'attendance'];
        $picked  = array_values(array_intersect($allowed, array_filter(array_map('trim', explode(',', (string)$this->input->get('sections', true))))));
        if (empty($picked)) {
            $picked = $allowed;
        }

        $schoolName = trim((string)($d['school'][0]->SchoolName ?? 'FBMSO'));
        $sections   = [];

        if (in_array('by_yearlevel', $picked, true)) {
            $rows = [];
            $sum  = 0;
            foreach ((array)$d['by_yearlevel'] as $r) {
                $sum += (int)$r->total;
                $rows[] = [($r->YearLevel !== '' ? $r->YearLevel : '—'), number_format((int)$r->total)];
            }
            $sections[] = [
                'title'   => 'Students by Year Level',
                'columns' => ['Year Level', 'Students'],
                'rows'    => $rows,
                'aligns'  => ['left', 'right'],
                'totals'  => ['Total', number_format($sum)],
            ];
        }

        if (in_array('by_course', $picked, true)) {
            $rows = [];
            $sum  = 0;
            foreach ((array)$d['by_course'] as $r) {
                $sum += (int)$r->total;
                $rows[] = [($r->Course !== '' ? $r->Course : '—'), number_format((int)$r->total)];
            }
            $sections[] = [
                'title'   => 'Students by Course',
                'columns' => ['Course', 'Students'],
                'rows'    => $rows,
                'aligns'  => ['left', 'right'],
                'totals'  => ['Total', number_format($sum)],
            ];
        }

        if (in_array('sections_per_course', $picked, true)) {
            $rows = [];
            $sum  = 0;
            foreach ((array)$d['sections_count'] as $r) {
                $sum += (int)$r->sections;
                $rows[] = [($r->Course !== '' ? $r->Course : '—'), number_format((int)$r->sections)];
            }
            $sections[] = [
                'title'   => 'Number of Sections per Course',
                'columns' => ['Course', 'Sections'],
                'rows'    => $rows,
                'aligns'  => ['left', 'right'],
                'totals'  => ['Total', number_format($sum)],
            ];
        }

        if (in_array('by_section', $picked, true)) {
            $rows = [];
            $sum  = 0;
            foreach ((array)$d['by_section'] as $r) {
                $sum += (int)$r->total;
                $rows[] = [
                    ($r->Course !== '' ? $r->Course : '—'),
                    ($r->YearLevel !== '' ? $r->YearLevel : '—'),
                    ($r->Section !== '' ? $r->Section : '—'),
                    number_format((int)$r->total),
                ];
            }
            $sections[] = [
                'title'   => 'Students by Section',
                'columns' => ['Course', 'Year Level', 'Section', 'Students'],
                'rows'    => $rows,
                'aligns'  => ['left', 'left', 'left', 'right'],
                'totals'  => ['Total', '', '', number_format($sum)],
            ];
        }

        if (in_array('events', $picked, true)) {
            $fmtTime = function ($v) {
                if (empty($v)) return '—';
                $ts = strtotime($v);
                return $ts ? date('h:i A', $ts) : '—';
            };
            $rows    = [];
            $scansum = 0;
            foreach ((array)$d['events_summary'] as $ev) {
                $dateText = (!empty($ev->activity_date) && $ev->activity_date !== '0000-00-00')
                    ? date('M d, Y', strtotime($ev->activity_date))
                    : (!empty($ev->start_at) ? date('M d, Y', strtotime($ev->start_at)) : '—');
                $scansum += (int)$ev->scans;
                $rows[] = [
                    (string)$ev->title,
                    $dateText,
                    $fmtTime($ev->meta_start_time ?: ($ev->meta_start ?: $ev->start_at)),
                    $fmtTime($ev->meta_end_time ?: ($ev->meta_end ?: $ev->end_at)),
                    (isset($ev->program) && $ev->program !== '' ? $ev->program : '—'),
                    number_format((int)$ev->scans),
                ];
            }
            $sections[] = [
                'title'   => 'Events & Attendance (latest)',
                'columns' => ['Title', 'Date', 'Start', 'End', 'Program', 'Scan Count'],
                'rows'    => $rows,
                'aligns'  => ['left', 'left', 'left', 'left', 'left', 'right'],
                'totals'  => ['Total Scans', '', '', '', '', number_format($scansum)],
            ];
        }

        if (in_array('attendance', $picked, true)) {
            $sessionMap = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
            $rows = [];
            $i    = 1;
            foreach ((array)$d['recent_attendance'] as $row) {
                $fullName = trim(implode(' ', array_filter([$row->FirstName ?? '', $row->MiddleName ?? '', $row->LastName ?? ''])));
                $rows[] = [
                    $i++,
                    ($fullName !== '' ? $fullName : ($row->student_number ?: '—')),
                    ($row->CourseName ?: '—'),
                    ($row->yearLevel ?: '—') . ' / ' . ($row->section ?: '—'),
                    ($row->activity_title ?: '—'),
                    ($sessionMap[$row->session ?? ''] ?? ($row->session ?: '—')),
                    (!empty($row->checked_in_at)  ? date('M d, Y h:i:s A', strtotime($row->checked_in_at))  : '—'),
                    (!empty($row->checked_out_at) ? date('M d, Y h:i:s A', strtotime($row->checked_out_at)) : '—'),
                    strtoupper($row->source ?: '—'),
                    ($row->remarks ?: ''),
                ];
            }
            $sections[] = [
                'title'   => 'Recent Attendance (latest 100)',
                'columns' => ['#', 'Student', 'Course', 'Year / Section', 'Activity', 'Session', 'IN', 'OUT', 'Source', 'Remarks'],
                'rows'    => $rows,
                'aligns'  => ['center', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left'],
            ];
        }

        $totalStudents = array_sum(array_map(function ($r) {
            return (int)$r->total;
        }, (array)$d['by_course']));

        $this->load->view('accounting_report_print', [
            'report_title' => 'Enrollment & Attendance Report',
            'school_name'  => ($schoolName !== '' ? $schoolName : 'FBMSO'),
            'meta'         => [
                ['label' => 'School Year', 'value' => ($d['sy']  !== '' ? $d['sy']  : '—')],
                ['label' => 'Semester',   'value' => ($d['sem'] !== '' ? $d['sem'] : '—')],
                ['label' => 'Students',   'value' => number_format($totalStudents)],
                ['label' => 'Events',     'value' => number_format((int)$d['events_total'])],
                ['label' => 'Scans',      'value' => number_format((int)$d['event_scans'])],
                ['label' => 'Printed',    'value' => date('F d, Y \a\t g:i A')],
            ],
            'sections'    => $sections,
            'back_url'    => base_url('reports'),
            'orientation' => 'landscape',
        ]);
    }
}

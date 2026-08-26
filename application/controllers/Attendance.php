<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url']);
        $this->load->library(['session']);
        $this->load->model('Student_qr_model');
        $this->load->model('Activity_attendance_model');
        $this->load->model('Activities_model', 'ActivitiesModel');
        $this->load->model('AuditLogModel');
    }

    public function scan($activity_id)
    {
        $activity = $this->ActivitiesModel->find((int)$activity_id);
        if (!$activity) show_404();
        $data['activity']       = $activity;
        $data['activity_state'] = activity_state($activity);

        // (optional) AUDIT: scan page opened
        $this->AuditLogModel->write(
            'update',
            'Attendance',
            'scan_ui',
            (string)$activity_id,
            null,
            ['opened_by' => ($this->session->userdata('username') ?: null)],
            1,
            'Opened activity scan page'
        );

        $this->load->view('scan_page', $data);
    }

    public function consume()
    {
        if ($this->input->method() !== 'post') {
            return $this->output->set_status_header(405)->set_output('Method Not Allowed');
        }

        $activity_id = (int)$this->input->post('activity_id');
        $raw         = (string)$this->input->post('token');
        $direction   = strtolower((string)$this->input->post('direction'));

        if (!in_array($direction, ['in', 'out', 'auto'], true)) {
            $direction = 'auto';
        }

        if (!$activity_id || $raw === '') {
            return $this->output->set_status_header(400)->set_output('Missing activity/token');
        }

        // --- Normalize $raw into a bare 32-hex token
        $token = trim($raw);

        // Case A: "ACTIVITY|TOKEN"
        if (strpos($token, '|') !== false) {
            $parts = explode('|', $token);
            if (count($parts) >= 2) $token = trim($parts[1]);
        }

        // Case B: Full URL "...?token=xxxx"
        if (stripos($token, 'http://') === 0 || stripos($token, 'https://') === 0) {
            $q = parse_url($token, PHP_URL_QUERY);
            if ($q) {
                parse_str($q, $qs);
                if (!empty($qs['token'])) {
                    $token = trim($qs['token']);
                }
            }
        }

        // Case C: URL path containing ".../checkin/<id>?token=xxxx"
        if (!preg_match('/^[A-Fa-f0-9]{32}$/', $token)) {
            // last-resort: pull last 32-hex from the string
            if (preg_match('/([A-Fa-f0-9]{32})/', $raw, $m)) {
                $token = $m[1];
            }
        }

        $old_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $op = $this->Activity_attendance_model->consume_token($activity_id, $token, $direction);

        // annotate rows
        $postedRemarks = trim((string)$this->input->post('remarks', true)); // from scan_page.js
        if (!empty($op['ok']) && $op['ok'] && isset($op['mode']) && !empty($op['id'])) {
            $rowId = (int)$op['id'];
            $mode  = (string)$op['mode'];

            $checkedBy = (string)$this->session->userdata('name')
                ?: (string)$this->session->userdata('username')
                ?: (string)$this->session->userdata('IDNumber')
                ?: 'Scanner';

            if ($mode === 'checked_in') {
                // set checked_in_by if empty
                $this->db->where('id', $rowId)
                    ->group_start()->where('checked_in_by IS NULL', null, false)->or_where('checked_in_by', '')->group_end()
                    ->set('checked_in_by', $checkedBy)->update('activity_attendance');

                // remarks: prefer manual; else default only if still empty/null
                if ($postedRemarks !== '') {
                    if (mb_strlen($postedRemarks) > 120) {
                        $postedRemarks = mb_substr($postedRemarks, 0, 120);
                    }
                    $this->db->where('id', $rowId)
                        ->set('remarks', $postedRemarks)
                        ->update('activity_attendance');
                } else {
                    $this->db->where('id', $rowId)
                        ->group_start()->where('remarks IS NULL', null, false)->or_where('remarks', '')->group_end()
                        ->set('remarks', 'Scanned via QR')
                        ->update('activity_attendance');
                }

                // source default if empty
                $this->db->where('id', $rowId)
                    ->group_start()->where('source IS NULL', null, false)->or_where('source', '')->group_end()
                    ->set('source', 'qr')->update('activity_attendance');
            } elseif ($mode === 'checked_out') {
                // treat literal fallback as empty
                $isFallback = (strcasecmp($postedRemarks, 'Scanned via QR') === 0);

                if ($postedRemarks !== '' && !$isFallback) {
                    // only overwrite if a *real* manual remark was provided
                    if (mb_strlen($postedRemarks) > 120) $postedRemarks = mb_substr($postedRemarks, 0, 120);
                    $this->db->where('id', $rowId)->set('remarks', $postedRemarks)->update('activity_attendance');
                } else {
                    // only set default if still empty
                    $this->db->where('id', $rowId)
                        ->group_start()->where('remarks IS NULL', null, false)->or_where('remarks', '')->group_end()
                        ->set('remarks', 'Scanned via QR')
                        ->update('activity_attendance');
                }
            }
        }
        $this->db->db_debug = $old_debug;

        /* ================== AUDIT: QR consume (scan) ================== */
        $actor =
            (string)$this->session->userdata('name')
            ?: (string)$this->session->userdata('username')
            ?: (string)$this->session->userdata('IDNumber')
            ?: null;

        $recordPk = isset($op['id']) ? (string)$op['id'] : null;
        $mode     = isset($op['mode']) ? (string)$op['mode'] : null;
        $okFlag   = !empty($op['ok']) && $op['ok'] ? 1 : 0;

        $this->AuditLogModel->write(
            'update',
            'Attendance',
            'activity_attendance',
            $recordPk,           // row id affected (if any)
            null,                // old snapshot not needed here
            [
                'activity_id' => $activity_id,
                'mode'        => $mode,           // 'checked_in' | 'checked_out' | 'err'
                'direction'   => $direction,      // 'in' | 'out' | 'auto'
                'remarks'     => ($postedRemarks !== '' ? $postedRemarks : 'Scanned via QR'),
                'actor'       => $actor
            ],
            $okFlag,
            $okFlag ? 'QR consume success' : 'QR consume failed'
        );
        /* =============================================================== */

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode($op));
    }
    public function checkin($activity_id)
    {
        $activity_id = (int)$activity_id;

        // pass-through ?direction=in|out|auto (default auto)
        $direction = strtolower((string)$this->input->get('direction', true));
        if (!in_array($direction, ['in', 'out', 'auto'], true)) {
            $direction = 'auto';
        }

        // auth + role
        $rawLevel = (string)$this->session->userdata('level');
        $loggedIn = (bool)$this->session->userdata('logged_in');
        $isStudent = (bool) preg_match('/^student(?:\s+applicant)?$/i', trim($rawLevel))
            || (bool) preg_match('/^stude(?:\s+applicant)?$/i', trim($rawLevel));
        $nextPath = 'attendance/checkin/' . $activity_id . ($direction ? ('?direction=' . $direction) : '');

        if (!$loggedIn) {
            redirect(site_url('login') . '?next=' . urlencode($nextPath));
            return;
        }
        if (!$isStudent) {
            // Render friendly message instead of show_error
            $data = [
                'activity_id'    => $activity_id,
                'student_number' => null,
                'result'         => [
                    'ok'      => false,
                    'mode'    => 'err',
                    'message' => 'This check-in link is for student accounts only. You are currently logged in as: ' . ($rawLevel ?: 'Unknown') . '.'
                ],
            ];
            $this->output->set_status_header(200);
            $this->load->view('activity_join_result', $data);
            return;
        }

        // Resolve student number
        $student_number =
            (string)$this->session->userdata('username')
            ?: (string)$this->session->userdata('student_number')
            ?: (string)$this->session->userdata('studentnumber');

        if ($student_number === '') {
            redirect(site_url('login') . '?next=' . urlencode($nextPath));
            return;
        }

        // Validate activity exists and is open — render message instead of show_error
        $this->load->model('Activities_model', 'Activities');
        $activity = $this->Activities->find($activity_id);
        if (!$activity) {
            $data = [
                'activity_id'    => $activity_id,
                'student_number' => $student_number,
                'result'         => [
                    'ok'      => false,
                    'mode'    => 'err',
                    'message' => 'This activity does not exist (it may have been deleted).',
                ],
            ];
            $this->output->set_status_header(200);
            $this->load->view('activity_join_result', $data);
            return;
        }
        $state = activity_state($activity);
        if (!$state['is_open']) {
            $data = [
                'activity_id'    => $activity_id,
                'student_number' => $student_number,
                'result'         => [
                    'ok'      => false,
                    'mode'    => 'err',
                    'message' => $state['reason'] ?: 'This activity is closed for check-ins.',
                    'state'   => $state['state'],
                ],
            ];
            $this->output->set_status_header(200);
            $this->load->view('activity_join_result', $data);
            return;
        }

        // Proceed with check-in/out
        $this->load->model('Student_qr_model', 'StudentQR');
        $this->load->model('Activity_attendance_model', 'AttendanceModel');

        $qr  = $this->StudentQR->get_active($student_number);
        if (!$qr || empty($qr->token)) {
            $data = [
                'activity_id'    => $activity_id,
                'student_number' => $student_number,
                'result'         => [
                    'ok'      => false,
                    'mode'    => 'err',
                    'message' => 'No active QR found for your account. Please generate/activate your permanent QR first (My QR), then try again.',
                ],
            ];
            $this->output->set_status_header(200);
            $this->load->view('activity_join_result', $data);
            return;
        }

        $res = $this->AttendanceModel->consume_token($activity_id, $qr->token, $direction);

        /* ================== AUDIT: Self check-in/out ================== */
        $recordPk = isset($res['id']) ? (string)$res['id'] : null;
        $mode     = isset($res['mode']) ? (string)$res['mode'] : null;
        $okFlag   = !empty($res['ok']) && $res['ok'] ? 1 : 0;

        $this->AuditLogModel->write(
            'update',
            'Attendance',
            'activity_attendance',
            $recordPk,
            null,
            [
                'activity_id'    => $activity_id,
                'student_number' => $student_number,
                'mode'           => $mode,        // 'checked_in' | 'checked_out' | 'err'
                'direction'      => $direction    // 'in' | 'out' | 'auto'
            ],
            $okFlag,
            $okFlag ? 'Self check event' : 'Self check failed'
        );
        /* =============================================================== */

        $data = [
            'activity_id'    => $activity_id,
            'student_number' => $student_number,
            'result'         => $res,
        ];
        $this->output->set_status_header(200);
        $this->load->view('activity_join_result', $data);
    }



    public function logs($activity_id = 0)
    {
        $activity_id = (int)$activity_id;
        if ($activity_id <= 0) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'rows' => [], 'message' => 'Invalid activity id']));
        }

        $this->db->from('activity_attendance aa');
        $this->db->select('aa.id, aa.activity_id, aa.student_number, aa.checked_in_at, aa.checked_out_at, aa.checked_in_by, aa.source, aa.remarks, aa.session');

        // Try to attach name components and a display name in Last First Middle order
        $studentNameExpr = null;
        if ($this->db->table_exists('studentsignup')) {
            // Prefer: Last First [Middle]
            $studentNameExpr = "CONCAT(TRIM(ss.LastName),' ',TRIM(ss.FirstName), IF(ss.MiddleName IS NULL OR ss.MiddleName='', '', CONCAT(' ', TRIM(ss.MiddleName))))";
            $this->db->join('studentsignup ss', 'ss.StudentNumber = aa.student_number', 'left');
            $this->db->select("TRIM(ss.LastName) AS LastName, TRIM(ss.FirstName) AS FirstName", false);
        } elseif ($this->db->table_exists('studeprofile')) {
            $studentNameExpr = "CONCAT(TRIM(sp.LastName),' ',TRIM(sp.FirstName), IF(sp.MiddleName IS NULL OR sp.MiddleName='', '', CONCAT(' ', TRIM(sp.MiddleName))))";
            $this->db->join('studeprofile sp', 'sp.StudentNumber = aa.student_number', 'left');
            $this->db->select("TRIM(sp.LastName) AS LastName, TRIM(sp.FirstName) AS FirstName", false);
        } elseif ($this->db->table_exists('users')) {
            $studentNameExpr = "CONCAT(TRIM(u.lName),' ',TRIM(u.fName), IF(u.mName IS NULL OR u.mName='', '', CONCAT(' ', TRIM(u.mName))))";
            $this->db->join('users u', 'u.username = aa.student_number', 'left');
            $this->db->select("TRIM(u.lName) AS LastName, TRIM(u.fName) AS FirstName", false);
        }

        if ($studentNameExpr) {
            $this->db->select("$studentNameExpr AS student_name", false);
        } else {
            $this->db->select("NULL AS student_name", false);
        }

        $this->db->where('aa.activity_id', $activity_id);
        $this->db->order_by('aa.checked_in_at', 'DESC');

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$r) {
            $map = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
            $r['session_label'] = $map[$r['session'] ?? ''] ?? '—';
            $r['student_name'] = trim((string)($r['student_name'] ?? ''));
            $r['full_name']    = $r['student_name'];
            if ($r['remarks'] === null || $r['remarks'] === '') {
                $r['remarks'] = (strtolower((string)$r['source']) === 'qr') ? 'Scanned via QR' : '—';
            }
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'rows' => $rows]));
    }

    public function my_logs()
    {
        $student_number = (string)$this->session->userdata('username')
            ?: (string)$this->session->userdata('student_number')
            ?: (string)$this->session->userdata('studentnumber');
        if (!$student_number) {
            return $this->output->set_status_header(401)->set_output('Unauthorized');
        }
        $rows = $this->Activity_attendance_model->list_student_attendance($student_number, 500, 0);
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'rows' => $rows]));
    }

    public function profile()
    {
        $sn = trim((string)$this->input->get('sn', TRUE));
        if ($sn === '') {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Missing student number']));
        }

        $sn_norm = preg_replace('/[\s-]+/', '', $sn);

        // 1) PRIMARY: studentsignup
        $sp = null;
        if ($this->db->table_exists('studentsignup')) {
            $fields = array_flip($this->db->list_fields('studentsignup'));
            $courseCols = ['Course1', 'Course2', 'Course3', 'Course'];
            $majorCols  = ['Major1', 'Major2', 'Major3', 'Major'];
            $courseCol = null;
            foreach ($courseCols as $c) {
                if (isset($fields[$c])) {
                    $courseCol = $c;
                    break;
                }
            }
            $majorCol  = null;
            foreach ($majorCols  as $m) {
                if (isset($fields[$m])) {
                    $majorCol  = $m;
                    break;
                }
            }
            $selCourse = $courseCol ? $courseCol : "''";
            $selMajor  = $majorCol  ? $majorCol  : "''";

            $sp = $this->db->select("
                StudentNumber,
                TRIM(FirstName)  AS FirstName,
                TRIM(MiddleName) AS MiddleName,
                TRIM(LastName)   AS LastName,
                CONCAT(
                  TRIM(LastName), ', ', TRIM(FirstName),
                  IF(MiddleName IS NULL OR MiddleName='', '', CONCAT(' ', LEFT(TRIM(MiddleName),1), '.'))
                ) AS student_name,
                {$selCourse} AS course,
                {$selMajor}  AS major
            ", false)
                ->from('studentsignup')
                ->where("REPLACE(REPLACE(StudentNumber,'-',''),' ','') =", $sn_norm)
                ->limit(1)->get()->row();
        }

        // 2) FALLBACKS: studeprofile → o_users → users.username
        if (!$sp && $this->db->table_exists('studeprofile')) {
            $sp = $this->db->select("
                sp.StudentNumber,
                sp.FirstName, sp.MiddleName, sp.LastName,
                CONCAT(
                  sp.LastName, ', ', sp.FirstName,
                  IF(sp.MiddleName IS NULL OR sp.MiddleName='', '', CONCAT(' ', LEFT(sp.MiddleName,1), '.'))
                ) AS student_name,
                sp.Course AS course,
                sp.Major  AS major
            ", false)
                ->from('studeprofile sp')
                ->where("REPLACE(REPLACE(sp.StudentNumber,'-',''),' ','') =", $sn_norm)
                ->limit(1)->get()->row();
        }

        if (!$sp && $this->db->table_exists('o_users')) {
            $sp = $this->db->select("
                username AS StudentNumber,
                fName AS FirstName, mName AS MiddleName, lName AS LastName,
                CONCAT(lName, ', ', fName, IF(mName IS NULL OR mName='', '', CONCAT(' ', LEFT(mName,1), '.'))) AS student_name
            ", false)
                ->from('o_users')
                ->group_start()
                ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)
                ->or_where("REPLACE(REPLACE(IDNumber,'-',''),' ','') =", $sn_norm)
                ->group_end()
                ->limit(1)->get()->row();
        }

        // LAST RESORT: users.username (no name columns there)
        if (!$sp && $this->db->table_exists('users')) {
            $uu = $this->db->select('username')->from('users')
                ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)
                ->limit(1)->get()->row();
            if ($uu) {
                $sp = (object)[
                    'StudentNumber' => $uu->username,
                    'student_name'  => $uu->username, // show username as name
                    'course'        => null,
                    'major'         => null,
                ];
            }
        }

        // Avatar (users/o_users)
        $photo_url = null;
        if ($this->db->table_exists('users')) {
            $row = $this->db->select('avatar')->from('users')
                ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)->get()->row();
            if ($row && !empty($row->avatar)) $photo_url = base_url('upload/profile/' . $row->avatar);
        }
        if (!$photo_url && $this->db->table_exists('o_users')) {
            $row = $this->db->select('avatar')->from('o_users')
                ->group_start()
                ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)
                ->or_where("REPLACE(REPLACE(IDNumber,'-',''),' ','') =", $sn_norm)
                ->group_end()
                ->get()->row();
            if ($row && !empty($row->avatar)) $photo_url = base_url('upload/profile/' . $row->avatar);
        }

        // Response (fallback name to SN instead of null)
        if (!$sp) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => true,
                    'student_number' => $sn,
                    'student_name'   => $sn,   // nicer than “Unknown Student”
                    'course'         => null,
                    'major'          => null,
                    'photo_url'      => $photo_url
                ]));
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode([
                'ok' => true,
                'student_number' => $sp->StudentNumber ?: $sn,
                'student_name'   => $sp->student_name ?: $sn,
                'course'         => isset($sp->course) ? $sp->course : null,
                'major'          => isset($sp->major)  ? $sp->major  : null,
                'photo_url'      => $photo_url
            ]));
    }

    private function lookup_avatar($studentNumber)
    {
        $row = $this->db->select('avatar')->from('users')
            ->where('username', $studentNumber)->limit(1)->get()->row();
        if ($row && !empty($row->avatar)) {
            return base_url('upload/profile/' . $row->avatar);
        }

        $row = $this->db->select('avatar')->from('o_users')
            ->where('username', $studentNumber)->limit(1)->get()->row();
        if ($row && !empty($row->avatar)) {
            return base_url('upload/profile/' . $row->avatar);
        }

        // NEW: the column your DB actually uses for the SN
        $row = $this->db->select('avatar')->from('o_users')
            ->where('IDNumber', $studentNumber)->limit(1)->get()->row();
        if ($row && !empty($row->avatar)) {
            return base_url('upload/profile/' . $row->avatar);
        }
        return null;
    }
}

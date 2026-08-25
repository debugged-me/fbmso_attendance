<?php
defined('BASEPATH') or exit('No direct script access allowed');

// MobileAttendance extends the shared MobileApi base controller which lives
// in application/libraries/. CI3 does not auto-load library classes as base
// controllers, so require it explicitly here.
require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile attendance API.
 *
 * Mirrors the web Attendance controller but authenticates via bearer token
 * (MobileApi base) instead of CI sessions. Reuses the same models so the
 * check-in/out semantics are identical to the web scanner:
 *   - Activity_attendance_model::consume_token() for scan/consume + self check-in
 *   - Activities_model for the activity list
 *   - Student_qr_model for the student's active QR token
 *
 * Endpoints (see application/config/routes.php):
 *   GET  api/mobile/activities                  list open/recent activities
 *   GET  api/mobile/activities/(:num)           one activity
 *   POST api/mobile/attendance/consume          scanner consumes a student QR token
 *   POST api/mobile/attendance/checkin/(:num)   student self check-in/out
 *   GET  api/mobile/attendance/my_logs          student's own attendance log
 *   GET  api/mobile/attendance/logs/(:num)      per-attendance log (staff)
 */
class MobileAttendance extends MobileApi
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Activities_model', 'ActivitiesModel');
        $this->load->model('Activity_attendance_model', 'AttendanceModel');
        $this->load->model('Student_qr_model', 'StudentQR');
        $this->load->model('AuditLogModel');
    }

    // ─── Activities ────────────────────────────────────────────────────────

    /** List activities (open ones first, then recent). */
    public function activities()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_token() === null) return;

        $rows = $this->ActivitiesModel->list_all();
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->activity_shape($r);
        }

        // Open activities first, then by start_at desc.
        usort($out, function ($a, $b) {
            if ($a['is_open'] !== $b['is_open']) {
                return $a['is_open'] ? -1 : 1;
            }
            return strcmp($b['start_at'] ?? '', $a['start_at'] ?? '');
        });

        return $this->json(['ok' => true, 'activities' => $out]);
    }

    /** One activity. */
    public function activity($id)
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_token() === null) return;

        $activity = $this->ActivitiesModel->find((int)$id);
        if (!$activity) {
            return $this->json(['ok' => false, 'message' => 'Activity not found.'], 404);
        }
        return $this->json(['ok' => true, 'activity' => $this->activity_shape($activity)]);
    }

    // ─── Scanner: consume a student QR token ───────────────────────────────

    /**
     * Scanner flow. The authenticated user (instructor/personnel) scans a
     * student's QR and POSTs {activity_id, token, direction, remarks}.
     * Reuses Activity_attendance_model::consume_token() — same lock + dedup
     * + session classification as the web scanner.
     */
    public function consume()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        if ($this->replay_if_duplicate()) return; // idempotency replay

        $payload   = $this->read_payload();
        $activityId = (int)($payload['activity_id'] ?? 0);
        $raw        = (string)($payload['token'] ?? '');
        $direction  = strtolower((string)($payload['direction'] ?? 'auto'));
        $remarks    = trim((string)($payload['remarks'] ?? ''));

        if (!in_array($direction, ['in', 'out', 'auto'], true)) {
            $direction = 'auto';
        }
        if (!$activityId || $raw === '') {
            $body = json_encode(['ok' => false, 'message' => 'Missing activity_id or token.']);
            $this->record_idempotent_response(400, $body);
            return $this->json(['ok' => false, 'message' => 'Missing activity_id or token.'], 400);
        }

        // Normalize the token the same way the web consume() does.
        $token = $raw;
        if (strpos($token, '|') !== false) {
            $parts = explode('|', $token);
            if (count($parts) >= 2) $token = trim($parts[1]);
        }
        if (stripos($token, 'http://') === 0 || stripos($token, 'https://') === 0) {
            $q = parse_url($token, PHP_URL_QUERY);
            if ($q) {
                parse_str($q, $qs);
                if (!empty($qs['token'])) $token = trim($qs['token']);
            }
        }
        if (!preg_match('/^[A-Fa-f0-9]{32}$/', $token)) {
            if (preg_match('/([A-Fa-f0-9]{32})/', $raw, $m)) {
                $token = $m[1];
            }
        }

        $oldDebug = $this->db->db_debug;
        $this->db->db_debug = false;
        $op = $this->AttendanceModel->consume_token($activityId, $token, $direction);
        $this->db->db_debug = $oldDebug;

        // Annotate the row exactly like the web flow.
        if (!empty($op['ok']) && !empty($op['id'])) {
            $rowId = (int)$op['id'];
            $mode  = (string)$op['mode'];
            $checkedBy = (string)$tokenRow['username'];

            if ($mode === 'checked_in') {
                $this->db->where('id', $rowId)
                    ->group_start()->where('checked_in_by IS NULL', null, false)->or_where('checked_in_by', '')->group_end()
                    ->set('checked_in_by', $checkedBy)->update('activity_attendance');

                if ($remarks !== '') {
                    if (mb_strlen($remarks) > 120) $remarks = mb_substr($remarks, 0, 120);
                    $this->db->where('id', $rowId)->set('remarks', $remarks)->update('activity_attendance');
                } else {
                    $this->db->where('id', $rowId)
                        ->group_start()->where('remarks IS NULL', null, false)->or_where('remarks', '')->group_end()
                        ->set('remarks', 'Scanned via QR')->update('activity_attendance');
                }
                $this->db->where('id', $rowId)
                    ->group_start()->where('source IS NULL', null, false)->or_where('source', '')->group_end()
                    ->set('source', 'qr')->update('activity_attendance');
            } elseif ($mode === 'checked_out') {
                $isFallback = strcasecmp($remarks, 'Scanned via QR') === 0;
                if ($remarks !== '' && !$isFallback) {
                    if (mb_strlen($remarks) > 120) $remarks = mb_substr($remarks, 0, 120);
                    $this->db->where('id', $rowId)->set('remarks', $remarks)->update('activity_attendance');
                } else {
                    $this->db->where('id', $rowId)
                        ->group_start()->where('remarks IS NULL', null, false)->or_where('remarks', '')->group_end()
                        ->set('remarks', 'Scanned via QR')->update('activity_attendance');
                }
            }
        }

        // Audit
        $this->AuditLogModel->write(
            'update',
            'Attendance',
            'activity_attendance',
            isset($op['id']) ? (string)$op['id'] : null,
            null,
            [
                'activity_id' => $activityId,
                'mode'        => $op['mode'] ?? null,
                'direction'   => $direction,
                'remarks'     => $remarks !== '' ? $remarks : 'Scanned via QR',
                'actor'       => $tokenRow['username'],
                'source'      => 'mobile',
            ],
            !empty($op['ok']) ? 1 : 0,
            !empty($op['ok']) ? 'Mobile QR consume success' : 'Mobile QR consume failed'
        );

        $status = !empty($op['ok']) ? 200 : 200; // keep 200 even on "already_in" etc.
        $body = json_encode($op);
        $this->record_idempotent_response($status, $body);
        return $this->json($op, $status);
    }

    // ─── Student self check-in/out ─────────────────────────────────────────

    /**
     * Student self check-in. Uses the student's own active QR token (looked
     * up by username) — the mobile equivalent of the web checkin($id) link.
     * Direction comes from the request body (default auto).
     */
    public function checkin($activity_id)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        if ($this->replay_if_duplicate()) return;

        $activityId = (int)$activity_id;
        $payload    = $this->read_payload();
        $direction  = strtolower((string)($payload['direction'] ?? 'auto'));
        if (!in_array($direction, ['in', 'out', 'auto'], true)) {
            $direction = 'auto';
        }

        $username = (string)$tokenRow['username'];

        // Only student/applicant accounts may self check-in.
        $position = $this->db->select('position')->from('o_users')
            ->where('username', $username)->limit(1)->get()->row();
        $level = strtolower(trim((string)($position->position ?? '')));
        $isStudent = (bool)preg_match('/^student(?:\s+applicant)?$/i', $level)
            || (bool)preg_match('/^stude(?:\s+applicant)?$/i', $level);
        if (!$isStudent) {
            $body = json_encode([
                'ok' => false,
                'mode' => 'err',
                'message' => 'Self check-in is for student accounts only.',
            ]);
            $this->record_idempotent_response(403, $body);
            return $this->json(json_decode($body, true), 403);
        }

        // Activity exists + open?
        $activity = $this->ActivitiesModel->find($activityId);
        if (!$activity) {
            $body = json_encode([
                'ok' => false,
                'mode' => 'err',
                'message' => 'This activity does not exist (it may have been deleted).',
            ]);
            $this->record_idempotent_response(404, $body);
            return $this->json(json_decode($body, true), 404);
        }
        if (isset($activity->is_open) && (int)$activity->is_open !== 1) {
            $body = json_encode([
                'ok' => false,
                'mode' => 'err',
                'message' => 'This activity is closed for check-ins.',
            ]);
            $this->record_idempotent_response(409, $body);
            return $this->json(json_decode($body, true), 409);
        }

        // Student's active QR.
        $qr = $this->StudentQR->get_active($username);
        if (!$qr || empty($qr->token)) {
            $body = json_encode([
                'ok' => false,
                'mode' => 'err',
                'message' => 'No active QR found for your account. Generate/activate your permanent QR first (My QR), then try again.',
            ]);
            $this->record_idempotent_response(409, $body);
            return $this->json(json_decode($body, true), 409);
        }

        $oldDebug = $this->db->db_debug;
        $this->db->db_debug = false;
        $res = $this->AttendanceModel->consume_token($activityId, $qr->token, $direction);
        $this->db->db_debug = $oldDebug;

        $this->AuditLogModel->write(
            'update',
            'Attendance',
            'activity_attendance',
            isset($res['id']) ? (string)$res['id'] : null,
            null,
            [
                'activity_id'    => $activityId,
                'student_number' => $username,
                'mode'           => $res['mode'] ?? null,
                'direction'      => $direction,
                'source'         => 'mobile',
            ],
            !empty($res['ok']) ? 1 : 0,
            !empty($res['ok']) ? 'Mobile self check event' : 'Mobile self check failed'
        );

        $body = json_encode($res);
        $this->record_idempotent_response(200, $body);
        return $this->json($res, 200);
    }

    // ─── Logs ──────────────────────────────────────────────────────────────

    /** Student's own attendance log. */
    public function my_logs()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $rows = $this->AttendanceModel->list_student_attendance($username, 500, 0);

        $out = array_map([$this, 'log_shape'], $rows);
        return $this->json(['ok' => true, 'rows' => $out]);
    }

    /** Per-activity attendance log (staff). */
    public function logs($activity_id = 0)
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $activityId = (int)$activity_id;
        if ($activityId <= 0) {
            return $this->json(['ok' => false, 'rows' => [], 'message' => 'Invalid activity id.']);
        }

        // Reuse the same join logic as the web Attendance::logs().
        $this->db->from('activity_attendance aa');
        $this->db->select('aa.id, aa.activity_id, aa.student_number, aa.checked_in_at, aa.checked_out_at, aa.checked_in_by, aa.source, aa.remarks, aa.session');

        $studentNameExpr = null;
        if ($this->db->table_exists('studentsignup')) {
            $studentNameExpr = "CONCAT(TRIM(ss.LastName),' ',TRIM(ss.FirstName), IF(ss.MiddleName IS NULL OR ss.MiddleName='', '', CONCAT(' ', TRIM(ss.MiddleName))))";
            $this->db->join('studentsignup ss', 'ss.StudentNumber = aa.student_number', 'left');
        } elseif ($this->db->table_exists('studeprofile')) {
            $studentNameExpr = "CONCAT(TRIM(sp.LastName),' ',TRIM(sp.FirstName), IF(sp.MiddleName IS NULL OR sp.MiddleName='', '', CONCAT(' ', TRIM(sp.MiddleName))))";
            $this->db->join('studeprofile sp', 'sp.StudentNumber = aa.student_number', 'left');
        } elseif ($this->db->table_exists('users')) {
            $studentNameExpr = "CONCAT(TRIM(u.lName),' ',TRIM(u.fName), IF(u.mName IS NULL OR u.mName='', '', CONCAT(' ', TRIM(u.mName))))";
            $this->db->join('users u', 'u.username = aa.student_number', 'left');
        }

        if ($studentNameExpr) {
            $this->db->select("$studentNameExpr AS student_name", false);
        } else {
            $this->db->select("NULL AS student_name", false);
        }

        $this->db->where('aa.activity_id', $activityId);
        $this->db->order_by('aa.checked_in_at', 'DESC');
        $rows = $this->db->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $map = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
            $r['session_label'] = $map[$r['session'] ?? ''] ?? '—';
            $r['student_name'] = trim((string)($r['student_name'] ?? ''));
            if ($r['remarks'] === null || $r['remarks'] === '') {
                $r['remarks'] = strtolower((string)$r['source']) === 'qr' ? 'Scanned via QR' : '—';
            }
            $out[] = $r;
        }

        return $this->json(['ok' => true, 'rows' => $out]);
    }

    // ─── Shaping helpers ───────────────────────────────────────────────────

    private function activity_shape($r): array
    {
        $r = is_array($r) ? (object)$r : $r;
        return [
            'activity_id'   => (int)($r->activity_id ?? 0),
            'title'         => (string)($r->title ?? ''),
            'code'          => (string)($r->code ?? ''),
            'activity_date' => (string)($r->activity_date ?? ''),
            'start_at'      => (string)($r->start_at ?? ''),
            'end_at'        => (string)($r->end_at ?? ''),
            'start_time'    => (string)($r->start_time ?? ''),
            'end_time'      => (string)($r->end_time ?? ''),
            'location'      => (string)($r->location ?? ''),
            'description'   => (string)($r->description ?? ''),
            'program'       => (string)($r->program_effective ?? $r->program ?? ''),
            'sy'            => (string)($r->sy ?? ''),
            'semester'      => (string)($r->semester ?? ''),
            'status'        => (string)($r->status ?? ''),
            'is_open'       => (int)($r->is_open ?? 0) === 1,
        ];
    }

    private function log_shape(array $r): array
    {
        $map = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
        return [
            'id'             => (int)($r['id'] ?? 0),
            'activity_id'    => (int)($r['activity_id'] ?? 0),
            'title'          => (string)($r['title'] ?? ''),
            'activity_date'  => (string)($r['activity_date'] ?? ''),
            'checked_in_at'  => (string)($r['checked_in_at'] ?? ''),
            'checked_out_at' => (string)($r['checked_out_at'] ?? ''),
            'source'         => (string)($r['source'] ?? ''),
            'remarks'        => (string)($r['remarks'] ?? ''),
            'session'        => (string)($r['session'] ?? ''),
            'session_label'  => $map[$r['session'] ?? ''] ?? '—',
        ];
    }
}

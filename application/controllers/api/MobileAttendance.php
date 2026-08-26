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

        return $this->json(['ok' => true, 'activities' => $out, 'poster_mode' => $this->_get_poster_mode()]);
    }

    /** Get current poster mode state. */
    public function poster_mode()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        return $this->json(['ok' => true, 'poster_mode' => $this->_get_poster_mode()]);
    }

    /** Toggle poster mode on/off. Admin only. */
    public function set_poster_mode()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }
        $p = $this->read_payload();
        $mode = strtolower((string)($p['mode'] ?? '')) === 'on' ? 'on' : 'off';
        $this->_write_poster_mode($mode);
        // Read back to verify — if the file write failed, return the actual state.
        $actual = $this->_get_poster_mode();
        return $this->json(['ok' => true, 'poster_mode' => $actual]);
    }

    private function _poster_flag_path(): string
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'qr_poster_mode.flag';
    }

    private function _get_poster_mode(): string
    {
        $path = $this->_poster_flag_path();
        if (is_file($path)) {
            $v = strtolower(trim(@file_get_contents($path)));
            return ($v === 'on') ? 'on' : 'off';
        }
        return 'off';
    }

    private function _write_poster_mode(string $mode): void
    {
        @file_put_contents($this->_poster_flag_path(), ($mode === 'on' ? 'on' : 'off'), LOCK_EX);
    }

    /** Get the poster QR data (check-in URL) for an activity. */
    public function poster_qr($id)
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $activityId = (int)$id;
        $activity = $this->ActivitiesModel->find($activityId);
        if (!$activity) {
            return $this->json(['ok' => false, 'message' => 'Activity not found.'], 404);
        }

        // Build the check-in URL (same logic as web Activities::poster)
        $path = 'attendance/checkin/' . $activityId;
        // Use base_url() which includes the subdirectory (e.g. fbmso_attendance/)
        $checkinUrl = rtrim(base_url(), '/') . '/' . ltrim($path, '/');

        return $this->json([
            'ok' => true,
            'activity_id'   => $activityId,
            'title'         => (string)($activity->title ?? ''),
            'activity_date' => (string)($activity->activity_date ?? ''),
            'location'      => (string)($activity->location ?? ''),
            'program'       => (string)($activity->program ?? ''),
            'checkin_url'   => $checkinUrl,
            'checkin_path'  => $path,
            'poster_mode'   => $this->_get_poster_mode(),
        ]);
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
        $op = $this->AttendanceModel->consume_token(
            $activityId, $token, $direction, $payload['client_submitted_at'] ?? null
        );
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
        $state = activity_state($activity, activity_resolve_scan_time($payload['client_submitted_at'] ?? null));
        if (!$state['is_open']) {
            $body = json_encode([
                'ok' => false,
                'mode' => 'err',
                'message' => $state['reason'] ?: 'This activity is closed for check-ins.',
                'state' => $state['state'],
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
        $res = $this->AttendanceModel->consume_token(
            $activityId, $qr->token, $direction, $payload['client_submitted_at'] ?? null
        );
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

        // Filters (matching web AttendanceLogs/index)
        $limit     = (int)$this->input->get('limit', true) ?: 50;
        $offset    = (int)$this->input->get('offset', true) ?: 0;
        $search    = trim((string)$this->input->get('search', true));
        $section   = trim((string)$this->input->get('section', true));
        $yearLevel = trim((string)$this->input->get('year_level', true));
        $date      = trim((string)$this->input->get('date', true));
        $session   = trim((string)$this->input->get('session', true));

        // Active SY/Sem from settings (same as web)
        $active = $this->db->select('active_sy, active_sem')
            ->order_by('settingsID', 'DESC')->limit(1)
            ->get('settings')->row();
        $use_sy  = (string)($active->active_sy ?? '');
        $use_sem = (string)($active->active_sem ?? '');

        // Use the web's Activity_attendance_model::report_by_activity_section
        $this->load->model('Activity_attendance_model', 'ActAttModel');
        $allRows = $this->ActAttModel->report_by_activity_section(
            $activityId,
            $section ?: null,
            $date ?: null,
            $session ?: null,
            $yearLevel ?: null,
            $use_sy,
            $use_sem
        );

        // Apply search filter (the web model doesn't do text search)
        if ($search !== '') {
            $q = strtolower($search);
            $allRows = array_filter($allRows, function ($r) use ($q) {
                return stripos((string)($r->student_number ?? ''), $q) !== false
                    || stripos((string)($r->student_name ?? ''), $q) !== false;
            });
            $allRows = array_values($allRows);
        }

        $total = count($allRows);

        // Apply pagination
        if ($limit > 0) {
            $pageRows = array_slice($allRows, $offset, $limit);
        } else {
            $pageRows = $allRows;
        }

        $out = [];
        foreach ($pageRows as $r) {
            $sessionMap = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
            $sess = strtolower((string)($r->session ?? ''));
            $out[] = [
                'id'             => (int)($r->id ?? 0),
                'activity_id'    => (int)($r->activity_id ?? $activityId),
                'student_number' => (string)($r->student_number ?? ''),
                'student_name'   => trim((string)($r->student_name ?? '')),
                'course'         => (string)($r->course ?? ''),
                'year_level'     => (string)($r->YearLevel ?? ''),
                'section'        => (string)($r->section ?? ''),
                'session'        => $sess,
                'session_label'  => $sessionMap[$sess] ?? '—',
                'checked_in_at'  => (string)($r->checked_in_at ?? ''),
                'checked_out_at' => (string)($r->checked_out_at ?? ''),
                'checked_in_by'  => (string)($r->checked_in_by ?? ''),
                'source'         => (string)($r->source ?? ''),
                'remarks'        => (string)($r->remarks ?? ''),
            ];
        }

        return $this->json([
            'ok' => true,
            'rows' => $out,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'section' => $section,
                'year_level' => $yearLevel,
                'date' => $date,
                'session' => $session,
            ],
        ]);
    }

    /** Export attendance logs as CSV (matching web AttendanceLogs/export_csv). */
    public function export_csv($activity_id = 0)
    {
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $activityId = (int)$activity_id;
        if ($activityId <= 0) {
            return $this->json(['ok' => false, 'message' => 'Invalid activity id.']);
        }

        $section   = trim((string)$this->input->get('section', true));
        $yearLevel = trim((string)$this->input->get('year_level', true));
        $date      = trim((string)$this->input->get('date', true));
        $session   = trim((string)$this->input->get('session', true));

        $active = $this->db->select('active_sy, active_sem')
            ->order_by('settingsID', 'DESC')->limit(1)
            ->get('settings')->row();
        $use_sy  = (string)($active->active_sy ?? '');
        $use_sem = (string)($active->active_sem ?? '');

        $this->load->model('Activity_attendance_model', 'ActAttModel');
        $rows = $this->ActAttModel->report_by_activity_section(
            $activityId,
            $section ?: null,
            $date ?: null,
            $session ?: null,
            $yearLevel ?: null,
            $use_sy,
            $use_sem
        );

        // Output CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="attendance_' . $activityId . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['StudentNumber', 'StudentName', 'Course', 'YearLevel', 'Section', 'Session', 'Check-In', 'Check-Out', 'Duration(min)', 'Remarks', 'Checked-In By']);
        foreach ($rows as $r) {
            $mins = ($r->checked_out_at && $r->checked_in_at)
                ? round((strtotime($r->checked_out_at) - strtotime($r->checked_in_at)) / 60)
                : null;
            fputcsv($out, [
                $r->student_number,
                $r->student_name,
                $r->course,
                $r->YearLevel,
                $r->section,
                strtoupper($r->session ?: ''),
                $r->checked_in_at,
                $r->checked_out_at,
                $mins,
                $r->remarks,
                $r->checked_in_by
            ]);
        }
        fclose($out);
        exit;
    }

    // ─── Activity management (staff only) ──────────────────────────────────

    /** Create a new activity. Staff only. */
    public function create_activity()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }
        if ($this->replay_if_duplicate()) return;

        $payload = $this->read_payload();

        $title         = trim((string)($payload['title'] ?? ''));
        $description   = trim((string)($payload['description'] ?? ''));
        $location      = trim((string)($payload['location'] ?? ''));
        $activityDate  = trim((string)($payload['activity_date'] ?? ''));
        $startTime     = trim((string)($payload['start_time'] ?? ''));
        $endTime       = trim((string)($payload['end_time'] ?? ''));
        $program       = trim((string)($payload['program'] ?? ''));

        // Manual state: prefer explicit `status`, else fall back to the legacy
        // `is_open` boolean older builds of the app still send.
        if (array_key_exists('status', $payload)) {
            $status = activity_normalize_status($payload['status'], 'open');
        } elseif (array_key_exists('is_open', $payload)) {
            $status = ((int)$payload['is_open'] === 1) ? 'open' : 'closed';
        } else {
            $status = 'open';
        }

        $autoClose = array_key_exists('auto_close', $payload)
            ? filter_var($payload['auto_close'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $grace = array_key_exists('grace_minutes', $payload)
            ? activity_normalize_grace($payload['grace_minutes'])
            : ACTIVITY_DEFAULT_GRACE_MINUTES;

        if ($title === '' || $activityDate === '') {
            $body = json_encode(['ok' => false, 'message' => 'Title and date are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $startAt = $activityDate . ' ' . ($startTime !== '' ? $startTime . ':00' : '00:00:00');
        $endAt   = ($endTime !== '') ? ($activityDate . ' ' . $endTime . ':00') : null;

        // Pull active SY/semester from settings if not provided.
        $sy  = trim((string)($payload['sy'] ?? ''));
        $sem = trim((string)($payload['semester'] ?? ''));
        if ($sy === '' || $sem === '') {
            $settings = $this->db->select('active_sy, active_sem')->from('settings')->limit(1)->get()->row();
            if ($settings) {
                if ($sy === '')  $sy  = (string)$settings->active_sy;
                if ($sem === '') $sem = (string)$settings->active_sem;
            }
        }

        $username = (string)$tokenRow['username'];

        $data = [
            'settingsID'    => 1,
            'title'         => $title,
            'description'   => $description ?: null,
            'location'      => $location ?: null,
            'program'       => $program,
            'activity_date' => $activityDate,
            'start_time'    => $startTime !== '' ? $startTime . ':00' : null,
            'end_time'      => $endTime !== '' ? $endTime . ':00' : null,
            'start_at'      => $startAt,
            'end_at'        => $endAt,
            'status'        => $status,
            'is_open'       => $status === 'open' ? 1 : 0,   // mirrored — see activity_state_helper
            'meta'          => activity_meta_merge_autoclose($payload['meta'] ?? '', $autoClose, $grace),
            'sy'            => $sy,
            'semester'      => $sem,
            'created_by_str'=> $username,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $ok = $this->db->insert('activities', $data);
        if (!$ok) {
            $err = $this->db->error();
            $body = json_encode(['ok' => false, 'message' => 'Failed: ' . $err['message']]);
            $this->record_idempotent_response(500, $body);
            return $this->json(json_decode($body, true), 500);
        }

        $newId = (int)$this->db->insert_id();
        $row = $this->ActivitiesModel->find($newId);
        $body = json_encode([
            'ok' => true,
            'message' => 'Activity created.',
            'activity' => $this->activity_shape($row),
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Update an existing activity. Staff only. */
    public function update_activity($id = 0)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }
        if ($this->replay_if_duplicate()) return;

        $activityId = (int)$id;
        if ($activityId <= 0) {
            return $this->json(['ok' => false, 'message' => 'Invalid activity id.'], 422);
        }

        $existing = $this->ActivitiesModel->find($activityId);
        if (!$existing) {
            return $this->json(['ok' => false, 'message' => 'Activity not found.'], 404);
        }

        $payload = $this->read_payload();

        $data = [];
        foreach (['title', 'description', 'location', 'program'] as $f) {
            if (array_key_exists($f, $payload)) {
                $data[$f] = trim((string)$payload[$f]);
            }
        }
        if (isset($payload['activity_date'])) {
            $data['activity_date'] = trim((string)$payload['activity_date']);
        }
        if (isset($payload['start_time'])) {
            $t = trim((string)$payload['start_time']);
            $data['start_time'] = $t !== '' ? $t . ':00' : null;
        }
        if (isset($payload['end_time'])) {
            $t = trim((string)$payload['end_time']);
            $data['end_time'] = $t !== '' ? $t . ':00' : null;
        }
        if (isset($payload['activity_date'])) {
            $date = trim((string)$payload['activity_date']);
            $st = trim((string)($payload['start_time'] ?? ''));
            $et = trim((string)($payload['end_time'] ?? ''));
            $data['start_at'] = $date . ' ' . ($st !== '' ? $st . ':00' : '00:00:00');
            $data['end_at']   = ($et !== '') ? ($date . ' ' . $et . ':00') : null;
        }
        // Manual state — `status` wins, `is_open` is the legacy fallback. Whichever
        // arrives, both columns are written so they can never disagree.
        if (array_key_exists('status', $payload)) {
            $newStatus = activity_normalize_status($payload['status'], activity_normalize_status($existing->status ?? 'open'));
            $data['status']  = $newStatus;
            $data['is_open'] = $newStatus === 'open' ? 1 : 0;
        } elseif (array_key_exists('is_open', $payload)) {
            $newStatus = ((int)$payload['is_open'] === 1) ? 'open' : 'closed';
            $data['status']  = $newStatus;
            $data['is_open'] = $newStatus === 'open' ? 1 : 0;
        }

        // Auto-close knobs live in meta; merge so `sessions` survives.
        if (array_key_exists('auto_close', $payload) || array_key_exists('grace_minutes', $payload)) {
            $cur = activity_auto_close_settings($existing->meta ?? '');
            $data['meta'] = activity_meta_merge_autoclose(
                $existing->meta ?? '',
                array_key_exists('auto_close', $payload)
                    ? filter_var($payload['auto_close'], FILTER_VALIDATE_BOOLEAN)
                    : $cur['auto_close'],
                array_key_exists('grace_minutes', $payload)
                    ? $payload['grace_minutes']
                    : $cur['grace_minutes']
            );
        }

        if (empty($data)) {
            return $this->json(['ok' => false, 'message' => 'No fields to update.'], 422);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('activity_id', $activityId)->update('activities', $data);

        $row = $this->ActivitiesModel->find($activityId);
        $body = json_encode([
            'ok' => true,
            'message' => 'Activity updated.',
            'activity' => $this->activity_shape($row),
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Delete an activity. Staff only. */
    public function delete_activity($id = 0)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }
        if ($this->replay_if_duplicate()) return;

        $activityId = (int)$id;
        if ($activityId <= 0) {
            return $this->json(['ok' => false, 'message' => 'Invalid activity id.'], 422);
        }

        $existing = $this->ActivitiesModel->find($activityId);
        if (!$existing) {
            return $this->json(['ok' => false, 'message' => 'Activity not found.'], 404);
        }

        $ok = $this->ActivitiesModel->delete($activityId);
        $body = json_encode([
            'ok' => $ok,
            'message' => $ok ? 'Activity deleted.' : 'Failed to delete activity.',
        ]);
        $this->record_idempotent_response($ok ? 200 : 500, $body);
        return $this->json(json_decode($body, true), $ok ? 200 : 500);
    }

    // ─── Staff helpers ─────────────────────────────────────────────────────

    private function is_staff(array $tokenRow): bool
    {
        $pos = strtolower(trim($this->position_of((string)$tokenRow['username'])));
        if (in_array($pos, ['admin', 'super admin', 'school admin', 'registrar', 'head registrar', 'accounting', 'hr admin', 'human resource', 'academic officer', 'encoder', 'it', 'instructor', 'teacher', 'personnel'], true)) {
            return true;
        }
        return false;
    }

    private function position_of(string $username): string
    {
        $row = $this->db->select('position')->from('o_users')
            ->where('username', $username)->limit(1)->get()->row();
        return (string)($row->position ?? '');
    }

    // ─── Shaping helpers ───────────────────────────────────────────────────

    private function activity_shape($r): array
    {
        $r  = is_array($r) ? (object)$r : $r;
        $st = activity_state($r);

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

            // `status` stays the raw manual enum so existing clients keep working;
            // `is_open` is now the EFFECTIVE answer (manual AND time window).
            'status'        => $st['manual_status'],
            'is_open'       => $st['is_open'],

            // Detail for clients that can render more than an open/closed pill.
            'state'         => $st['state'],          // open|scheduled|ended|closed|draft|archived
            'state_label'   => $st['label'],
            'closed_reason' => $st['reason'],         // null when open
            'manual_status' => $st['manual_status'],
            'manual_open'   => $st['manual_open'],
            'auto_close'    => $st['auto_close'],
            'grace_minutes' => $st['grace_minutes'],
            'window_start'  => $st['window_start'],
            'window_end'    => $st['window_end'],
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

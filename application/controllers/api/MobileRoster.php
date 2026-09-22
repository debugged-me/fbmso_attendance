<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Roster snapshot for offline scanning.
 *
 * A student QR carries an opaque random token that only the server can map to
 * a student, so an offline scanner can otherwise do nothing but blind-queue a
 * scan — no name, no duplicate detection, no way to reject a foreign QR. This
 * ships the scanner enough of the roster to answer those questions locally.
 *
 * Tokens are never sent to the device. Each row carries
 * SHA256(salt + token) instead: the device can verify a QR it physically
 * scans, but cannot mint one for a student it has not seen. The salt is
 * derived from the server key and the roster version, so a snapshot is also
 * useless once the roster changes.
 *
 * Endpoints (see application/config/routes.php):
 *   GET api/mobile/roster/manifest?activity_id=N
 *   GET api/mobile/roster/chunk?activity_id=N&version=V&seq=K
 */
class MobileRoster extends MobileApi
{
    /** Rows per chunk. ~500 keeps a response near 60KB gzipped. */
    private const CHUNK_SIZE = 500;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->helper('activity_state');
    }

    // ─── Manifest ──────────────────────────────────────────────────────────

    public function manifest()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->can_scan($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }

        $activityId = (int)$this->input->get('activity_id');
        $activity   = $this->find_activity($activityId);
        if (!$activity) {
            return $this->json(['ok' => false, 'message' => 'Activity not found.'], 404);
        }

        $version = $this->roster_version();
        $total   = $this->roster_total();
        $chunks  = (int)ceil($total / self::CHUNK_SIZE);

        $meta     = activity_meta_decode($activity->meta);
        $sessions = (isset($meta['sessions']) && is_array($meta['sessions']))
            ? $meta['sessions']
            : ['am' => ['in' => '08:00', 'out' => '12:00'],
               'pm' => ['in' => '13:00', 'out' => '18:00']];

        $window = activity_checkin_window($activity);

        return $this->json([
            'ok'             => true,
            'activity_id'    => $activityId,
            'activity_title' => (string)($activity->title ?? ''),
            'activity_date'  => (string)($activity->activity_date ?? ''),
            'roster_version' => $version,
            'salt'           => $this->salt_for($version),
            'total'          => $total,
            'chunk_size'     => self::CHUNK_SIZE,
            'chunk_count'    => $chunks,
            'sessions'       => $sessions,
            'window_start'   => $window['start'] !== null ? date('c', $window['start']) : null,
            'window_end'     => $window['end']   !== null ? date('c', $window['end'])   : null,
            'server_time'    => date('c'),
            'server_tz'      => date_default_timezone_get(),
        ]);
    }

    // ─── Chunk ─────────────────────────────────────────────────────────────

    public function chunk()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if (!$this->can_scan($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }

        $requested = trim((string)$this->input->get('version'));
        $version   = $this->roster_version();

        // The salt is tied to the version, so a chunk fetched against a stale
        // manifest would hash under the wrong salt and match nothing.
        if ($requested !== '' && $requested !== $version) {
            return $this->json([
                'ok'             => false,
                'mode'           => 'version_changed',
                'roster_version' => $version,
                'message'        => 'The roster changed. Fetch the manifest again.',
            ], 409);
        }

        $seq  = max(0, (int)$this->input->get('seq'));
        $salt = $this->salt_for($version);
        $rows = $this->roster_rows(self::CHUNK_SIZE, $seq * self::CHUNK_SIZE);

        $students = [];
        foreach ($rows as $r) {
            $token = attendance_normalize_student_qr((string)$r->qr_token);
            if ($token === '') continue;

            $students[] = [
                'qr_hash'        => hash('sha256', $salt . $token),
                'student_number' => (string)$r->student_number,
                'name'           => $this->format_name($r),
                'program'        => (string)($r->course ?? ''),
                'section'        => (string)($r->section ?? ''),
                'photo_url'      => !empty($r->avatar)
                    ? base_url('upload/profile/' . $r->avatar)
                    : null,
            ];
        }

        return $this->json([
            'ok'             => true,
            'roster_version' => $version,
            'seq'            => $seq,
            'students'       => $students,
        ]);
    }

    // ─── Roster source ─────────────────────────────────────────────────────

    /**
     * Only students holding an active QR can be scanned, so `student_qr` is
     * the roster. Identity is filled in from whichever table this install
     * actually carries it in, mirroring
     * Activity_attendance_model::resolve_student_min().
     */
    private function roster_rows(int $limit, int $offset): array
    {
        [$select, $joins] = $this->roster_select();

        // No GROUP BY: it would need ONLY_FULL_GROUP_BY disabled, which is not
        // guaranteed on every deployment. A student matched twice by the
        // o_users join simply upserts the same qr_hash on the device.
        $sql = 'SELECT ' . implode(', ', $select) . '
                FROM student_qr q' . $joins . "
                WHERE q.status = 'active'
                ORDER BY q.student_number ASC, q.qr_token ASC
                LIMIT ? OFFSET ?";

        $query = $this->db->query($sql, [$limit, $offset]);
        return $query === false ? [] : $query->result();
    }

    /**
     * The select list and joins, shared so the row count and the paged reads
     * can never disagree about how many rows there are.
     *
     * @return array{0: string[], 1: string}
     */
    private function roster_select(): array
    {
        $select = ['q.student_number', 'q.qr_token'];
        $joins  = '';

        if ($this->db->table_exists('studentsignup')) {
            $fields    = array_flip($this->db->list_fields('studentsignup'));
            $courseCol = $this->first_present($fields, ['Course1', 'Course2', 'Course3', 'Course']);
            $sectionOk = isset($fields['section']);

            $select[] = 'TRIM(s.FirstName)  AS FirstName';
            $select[] = 'TRIM(s.MiddleName) AS MiddleName';
            $select[] = 'TRIM(s.LastName)   AS LastName';
            $select[] = ($courseCol !== '' ? "s.`$courseCol`" : "''") . ' AS course';
            $select[] = ($sectionOk ? 's.`section`' : "''") . ' AS section';

            $joins .= " LEFT JOIN studentsignup s
                          ON REPLACE(REPLACE(s.StudentNumber,'-',''),' ','')
                           = REPLACE(REPLACE(q.student_number,'-',''),' ','')";
        } else {
            $select[] = "'' AS FirstName";
            $select[] = "'' AS MiddleName";
            $select[] = "'' AS LastName";
            $select[] = "'' AS course";
            $select[] = "'' AS section";
        }

        if ($this->db->table_exists('o_users')) {
            $select[] = 'u.avatar AS avatar';
            $select[] = 'u.fName  AS u_fName';
            $select[] = 'u.mName  AS u_mName';
            $select[] = 'u.lName  AS u_lName';

            $joins .= " LEFT JOIN o_users u
                          ON REPLACE(REPLACE(u.username,'-',''),' ','')
                           = REPLACE(REPLACE(q.student_number,'-',''),' ','')
                          OR REPLACE(REPLACE(u.IDNumber,'-',''),' ','')
                           = REPLACE(REPLACE(q.student_number,'-',''),' ','')";
        } else {
            $select[] = 'NULL AS avatar';
            $select[] = "'' AS u_fName";
            $select[] = "'' AS u_mName";
            $select[] = "'' AS u_lName";
        }

        return [$select, $joins];
    }

    private function roster_total(): int
    {
        [, $joins] = $this->roster_select();

        $query = $this->db->query(
            'SELECT COUNT(*) AS c FROM student_qr q' . $joins . "
             WHERE q.status = 'active'"
        );
        if ($query === false) return 0;

        $row = $query->row();
        return (int)($row->c ?? 0);
    }

    /**
     * Changes whenever a QR is issued or revoked, which is exactly when a
     * cached snapshot stops being trustworthy.
     */
    private function roster_version(): string
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS c, COALESCE(MAX(issued_at), '') AS latest
               FROM student_qr WHERE status = 'active'"
        )->row();

        return substr(sha1(($row->c ?? 0) . '|' . ($row->latest ?? '')), 0, 16);
    }

    /**
     * Deterministic per version so a chunk hashes the same way the manifest
     * promised, and unguessable without the server key so an exfiltrated
     * snapshot cannot be correlated against another one.
     */
    private function salt_for(string $version): string
    {
        $secret = (string)$this->config->item('encryption_key');
        return substr(hash_hmac('sha256', 'roster:' . $version, $secret), 0, 32);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function find_activity(int $activityId)
    {
        if ($activityId <= 0) return null;
        return $this->db
            ->select('activity_id, title, status, is_open, start_at, end_at, activity_date, meta')
            ->from('activities')
            ->where('activity_id', $activityId)
            ->limit(1)->get()->row();
    }

    private function format_name($r): string
    {
        $last  = trim((string)($r->LastName  ?? ''));
        $first = trim((string)($r->FirstName ?? ''));
        $mid   = trim((string)($r->MiddleName ?? ''));

        if ($last === '' && $first === '') {
            $last  = trim((string)($r->u_lName ?? ''));
            $first = trim((string)($r->u_fName ?? ''));
            $mid   = trim((string)($r->u_mName ?? ''));
        }
        if ($last === '' && $first === '') {
            return (string)$r->student_number;
        }

        $mi = $mid === '' ? '' : (' ' . strtoupper(substr($mid, 0, 1)) . '.');
        return trim($last . ', ' . $first . $mi);
    }

    private function first_present(array $fields, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (isset($fields[$c])) return $c;
        }
        return '';
    }

    /** Same rule as MobileAttendance::consume() — if you can scan, you can pull the roster. */
    private function can_scan(array $tokenRow): bool
    {
        $row = $this->db->select('position')->from('o_users')
            ->where('username', (string)$tokenRow['username'])->limit(1)->get()->row();
        $pos = strtolower(trim((string)($row->position ?? '')));

        return $pos !== ''
            && $pos !== 'cashier'
            && !in_array($pos, ['student', 'student applicant', 'stude', 'stude applicant'], true);
    }
}

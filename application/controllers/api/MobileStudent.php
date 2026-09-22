<?php
defined('BASEPATH') or exit('No direct script access allowed');

// MobileStudent extends the shared MobileApi base controller.
require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile student API: profile, my QR. All endpoints are bearer-token
 * authenticated and reuse the same models as the web Student controller.
 *
 * Endpoints (see application/config/routes.php):
 *   GET  api/mobile/student/profile
 *   GET  api/mobile/student/status   (flagged-account state — mirrors Page::student)
 *   GET  api/mobile/student/my_qr
 *   POST api/mobile/student/my_qr/issue
 *   POST api/mobile/student/my_qr/revoke
 */
class MobileStudent extends MobileApi
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Student_qr_model', 'StudentQR');
        $this->load->model('StudentModel');
    }

    // ─── Profile ───────────────────────────────────────────────────────────

    /** The authenticated student's profile (studentsignup → studeprofile → o_users). */
    public function profile()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $profile  = $this->resolve_profile($username);

        return $this->json([
            'ok' => true,
            'profile' => $profile,
        ]);
    }

    /**
     * Flagged-account state — same queries the web student dashboard runs
     * (Page::student → StudentModel::isFlagged / getFlagDetails).
     */
    public function status()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $flag = $this->StudentModel->getFlagDetails($username);

        return $this->json([
            'ok' => true,
            'is_flagged' => $flag !== null,
            'flag' => $flag === null ? null : [
                'reason'     => (string)($flag->flaggedReason ?? ''),
                'flagged_by' => (string)($flag->flaggedBy ?? ''),
                'office'     => (string)($flag->Office ?? ''),
                'sy'         => (string)($flag->SY ?? ''),
                'semester'   => (string)($flag->Semester ?? ''),
            ],
        ]);
    }

    // ─── My QR ─────────────────────────────────────────────────────────────

    /** Return the student's active QR token (issue one if none exists). */
    public function my_qr()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $qr = $this->StudentQR->get_or_issue($username);

        return $this->json([
            'ok' => true,
            'student_number' => $username,
            'token'          => (string)($qr->qr_token ?? $qr->token ?? ''),
            'status'         => (string)($qr->status ?? 'active'),
            'issued_at'      => (string)($qr->issued_at ?? ''),
        ]);
    }

    /** Issue a fresh QR token (revokes the previous one). */
    public function issue_qr()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];

        // Revoke any existing active token.
        $this->db->where('student_number', $username)
            ->where('status', 'active')
            ->update('student_qr', [
                'status' => 'revoked',
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

        // Issue a new one.
        $newToken = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');
        $this->db->insert('student_qr', [
            'student_number' => $username,
            'qr_token'       => $newToken,
            'status'         => 'active',
            'issued_at'      => $now,
        ]);

        $body = json_encode([
            'ok' => true,
            'student_number' => $username,
            'token' => $newToken,
            'status' => 'active',
            'issued_at' => $now,
            'message' => 'New QR token issued.',
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Revoke the active QR token. */
    public function revoke_qr()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];
        $this->db->where('student_number', $username)
            ->where('status', 'active')
            ->update('student_qr', [
                'status' => 'revoked',
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

        $body = json_encode(['ok' => true, 'message' => 'QR token revoked.']);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /** Resolve a student profile across the three possible tables. */
    private function resolve_profile(string $username): array
    {
        $snNorm = preg_replace('/[\s-]+/', '', $username);

        // 1) studentsignup (applicants / online enrollees)
        if ($this->db->table_exists('studentsignup')) {
            $fields = array_flip($this->db->list_fields('studentsignup'));
            $courseCol = $this->first_col($fields, ['Course3', 'Course1', 'Course2', 'Course']);
            $majorCol  = $this->first_col($fields, ['Major3', 'Major1', 'Major2', 'Major']);
            $selCourse = $courseCol ?: "''";
            $selMajor  = $majorCol  ?: "''";

            $row = $this->db->select("
                StudentNumber,
                TRIM(FirstName) AS first_name,
                TRIM(MiddleName) AS middle_name,
                TRIM(LastName) AS last_name,
                TRIM(nameExtn) AS name_extn,
                Sex AS sex,
                birthDate AS birth_date,
                email,
                contactNo AS contact_no,
                CivilStatus AS civil_status,
                ethnicity,
                Religion AS religion,
                province,
                city,
                brgy AS barangay,
                sitio,
                {$selCourse} AS course,
                {$selMajor} AS major,
                Status AS status,
                EnrollmentDate AS enrollment_date
            ", false)->from('studentsignup')
                ->group_start()
                ->where('StudentNumber', $username)
                ->or_where("REPLACE(REPLACE(StudentNumber,'-',''),' ','') =", $snNorm)
                ->group_end()
                ->limit(1)->get()->row();

            if ($row) return $this->profile_array($row, $username);
        }

        // 2) studeprofile
        if ($this->db->table_exists('studeprofile')) {
            $row = $this->db->select("
                StudentNumber,
                TRIM(FirstName) AS first_name,
                TRIM(MiddleName) AS middle_name,
                TRIM(LastName) AS last_name,
                Course AS course,
                Major AS major
            ", false)->from('studeprofile')
                ->group_start()
                ->where('StudentNumber', $username)
                ->or_where("REPLACE(REPLACE(StudentNumber,'-',''),' ','') =", $snNorm)
                ->group_end()
                ->limit(1)->get()->row();
            if ($row) return $this->profile_array($row, $username);
        }

        // 3) o_users fallback
        $row = $this->db->select("
            username AS StudentNumber,
            fName AS first_name,
            mName AS middle_name,
            lName AS last_name,
            email,
            '' AS course,
            '' AS major
        ", false)->from('o_users')
            ->group_start()
            ->where('username', $username)
            ->or_where("REPLACE(REPLACE(IDNumber,'-',''),' ','') =", $snNorm)
            ->group_end()
            ->limit(1)->get()->row();

        if ($row) return $this->profile_array($row, $username);

        // Last resort
        return [
            'student_number' => $username,
            'first_name' => '',
            'last_name' => '',
            'full_name' => $username,
            'course' => null,
            'major' => null,
        ];
    }

    private function profile_array($row, string $fallback): array
    {
        $first = trim((string)($row->first_name ?? ''));
        $middle = trim((string)($row->middle_name ?? ''));
        $last = trim((string)($row->last_name ?? ''));
        $full = trim("$last, $first" . ($middle !== '' ? " {$middle[0]}." : ''));

        return [
            'student_number'   => (string)($row->StudentNumber ?? $fallback),
            'first_name'       => $first,
            'middle_name'      => $middle,
            'last_name'        => $last,
            'full_name'        => $full !== '' ? $full : $fallback,
            'name_extn'        => (string)($row->name_extn ?? ''),
            'sex'              => (string)($row->sex ?? ''),
            'birth_date'       => (string)($row->birth_date ?? ''),
            'email'            => (string)($row->email ?? ''),
            'contact_no'       => (string)($row->contact_no ?? ''),
            'civil_status'     => (string)($row->civil_status ?? ''),
            'ethnicity'        => (string)($row->ethnicity ?? ''),
            'religion'         => (string)($row->religion ?? ''),
            'province'         => (string)($row->province ?? ''),
            'city'             => (string)($row->city ?? ''),
            'barangay'         => (string)($row->barangay ?? ''),
            'sitio'            => (string)($row->sitio ?? ''),
            'course'           => (string)($row->course ?? ''),
            'major'            => (string)($row->major ?? ''),
            'status'           => (string)($row->status ?? ''),
            'enrollment_date'  => (string)($row->enrollment_date ?? ''),
        ];
    }

    private function first_col(array $fields, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (isset($fields[$c])) return $c;
        }
        return '';
    }

    private function file_url(string $path): string
    {
        $path = ltrim($path, '/');
        if ($path === '') return '';
        return rtrim($this->runtime_base_url(), '/') . '/' . $path;
    }

    private function runtime_base_url(): string
    {
        $xfProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $xfHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? null;
        $scheme  = $xfProto ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host    = $xfHost  ?: ($_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST) ?? '');
        return rtrim($scheme . '://' . $host, '/');
    }
}

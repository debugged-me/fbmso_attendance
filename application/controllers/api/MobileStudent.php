<?php
defined('BASEPATH') or exit('No direct script access allowed');

// MobileStudent extends the shared MobileApi base controller.
require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile student API: profile, my QR, requirements, grades, enrolled
 * subjects (COR). All endpoints are bearer-token authenticated and reuse
 * the same models as the web Student controller.
 *
 * Endpoints (see application/config/routes.php):
 *   GET  api/mobile/student/profile
 *   GET  api/mobile/student/my_qr
 *   POST api/mobile/student/my_qr/issue
 *   POST api/mobile/student/my_qr/revoke
 *   GET  api/mobile/student/requirements
 *   POST api/mobile/student/requirements/upload
 *   GET  api/mobile/student/grades
 *   GET  api/mobile/student/enrolled_subjects
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

    // ─── Requirements ──────────────────────────────────────────────────────

    /** List requirement types + the student's submission status for each. */
    public function requirements()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $rows = $this->StudentModel->getStudentRequirements($username);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'req_id'        => (int)$r->req_id,
                'name'          => (string)$r->name,
                'description'   => (string)($r->description ?? ''),
                'date_submitted'=> (string)($r->date_submitted ?? ''),
                'file_path'     => (string)($r->file_path ?? ''),
                'file_url'      => $this->file_url((string)($r->file_path ?? '')),
                'is_verified'   => (int)($r->is_verified ?? 0) === 1,
                'comment'       => (string)($r->comment ?? ''),
            ];
        }

        return $this->json(['ok' => true, 'requirements' => $out]);
    }

    /** Upload a requirement file (multipart form-data). */
    public function upload_requirement()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];
        $requirementId = (int)$this->input->post('requirement_id');

        if ($requirementId <= 0 || empty($_FILES['requirement_file']['name'])) {
            $body = json_encode(['ok' => false, 'message' => 'Requirement ID and file are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $config['upload_path']   = './upload/requirements/';
        $config['allowed_types'] = 'pdf|doc|docx|jpg|jpeg|png';
        $config['max_size']      = 2048;
        $config['file_name']     = time() . '_' . $_FILES['requirement_file']['name'];

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('requirement_file')) {
            $body = json_encode(['ok' => false, 'message' => $this->upload->display_errors('', '')]);
            $this->record_idempotent_response(400, $body);
            return $this->json(json_decode($body, true), 400);
        }

        $uploadData = $this->upload->data();
        $filePath = 'upload/requirements/' . $uploadData['file_name'];

        $data = [
            'StudentNumber'  => $username,
            'requirement_id' => $requirementId,
            'date_submitted' => date('Y-m-d'),
            'file_path'      => $filePath,
            'is_verified'    => 1,
            'verified_by'    => $username,
            'verified_at'    => date('Y-m-d H:i:s'),
            'comment'        => 'Uploaded by ' . $username . ' (mobile)',
        ];

        $existing = $this->db->get_where('student_requirements', [
            'StudentNumber' => $username,
            'requirement_id' => $requirementId,
        ])->row();

        if ($existing) {
            $this->db->where('id', $existing->id)->update('student_requirements', $data);
        } else {
            $this->db->insert('student_requirements', $data);
        }

        $body = json_encode([
            'ok' => true,
            'message' => 'Requirement uploaded successfully.',
            'file_url' => $this->file_url($filePath),
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    // ─── Grades ────────────────────────────────────────────────────────────

    /** All grades for the student, newest SY/Sem first. */
    public function grades()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $rows = $this->StudentModel->get_grades($username);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'subject_code' => (string)($r->SubjectCode ?? ''),
                'description'  => (string)($r->Description ?? ''),
                'course'       => (string)($r->Course ?? ''),
                'major'        => (string)($r->Major ?? ''),
                'year_level'   => (string)($r->YearLevel ?? ''),
                'section'      => (string)($r->Section ?? ''),
                'lec_unit'     => (string)($r->LecUnit ?? ''),
                'lab_unit'     => (string)($r->LabUnit ?? ''),
                'prelim'       => $this->num($r->Prelim ?? null),
                'midterm'      => $this->num($r->Midterm ?? null),
                'pre_final'    => $this->num($r->PreFinal ?? null),
                'final'        => $this->num($r->Final ?? null),
                'average'      => $this->num($r->Average ?? null),
                'sy'           => (string)($r->SY ?? ''),
                'semester'     => (string)($r->Semester ?? ''),
            ];
        }

        return $this->json(['ok' => true, 'grades' => $out]);
    }

    // ─── Enrolled subjects (COR) ───────────────────────────────────────────

    /** Currently enrolled subjects for the active SY/Sem (the COR data). */
    public function enrolled_subjects()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $sy = trim((string)$this->input->get('sy', true));
        $sem = trim((string)$this->input->get('sem', true));

        // Fall back to the active settings if not provided.
        if ($sy === '' || $sem === '') {
            $settings = $this->db->query('SELECT active_sy, active_sem FROM o_srms_settings LIMIT 1')->row();
            if ($sy === '')  $sy  = (string)($settings->active_sy ?? '');
            if ($sem === '') $sem = (string)($settings->active_sem ?? '');
        }

        $this->db->select('r.SubjectCode, r.Description, r.LecUnit, r.LabUnit, r.Section, r.SchedTime, r.Room, r.Instructor, r.Course, r.YearLevel, r.Major, r.Sem, r.SY, r.totalUnits, r.schedType');
        $this->db->from('registration r');
        $this->db->where('r.StudentNumber', $username);
        if ($sy !== '')  $this->db->where('r.SY', $sy);
        if ($sem !== '') $this->db->where('r.Sem', $sem);
        $rows = $this->db->get()->result();

        $out = [];
        $totalUnits = 0.0;
        foreach ($rows as $r) {
            $lec = (float)($r->LecUnit ?? 0);
            $lab = (float)($r->LabUnit ?? 0);
            $units = $lec + $lab;
            $totalUnits += $units;
            $out[] = [
                'subject_code' => (string)($r->SubjectCode ?? ''),
                'description'  => (string)($r->Description ?? ''),
                'lec_unit'     => (string)($r->LecUnit ?? ''),
                'lab_unit'     => (string)($r->LabUnit ?? ''),
                'units'        => $units,
                'section'      => (string)($r->Section ?? ''),
                'schedule'     => (string)($r->SchedTime ?? ''),
                'room'         => (string)($r->Room ?? ''),
                'instructor'   => (string)($r->Instructor ?? ''),
                'course'       => (string)($r->Course ?? ''),
                'year_level'   => (string)($r->YearLevel ?? ''),
                'major'        => (string)($r->Major ?? ''),
                'sem'          => (string)($r->Sem ?? ''),
                'sy'           => (string)($r->SY ?? ''),
                'sched_type'   => (string)($r->schedType ?? ''),
            ];
        }

        return $this->json([
            'ok' => true,
            'sy' => $sy,
            'sem' => $sem,
            'total_units' => $totalUnits,
            'subjects' => $out,
        ]);
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

    private function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        return (float)$v;
    }
}

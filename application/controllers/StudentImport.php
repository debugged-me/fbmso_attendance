<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * StudentImport — bulk creation of student accounts from a spreadsheet.
 *
 * template()  GET  → downloads a ready-made .xlsx with dropdown validations
 *                    and an Instructions sheet.
 * upload()    POST → parses the filled-in .xlsx (or a .csv saved from it),
 *                    validates every row against the same rules the
 *                    registration form enforces, and creates the same set of
 *                    records: studentsignup + o_users + semesterstude +
 *                    studeaccount ledger shell, then queues a credential
 *                    email per account.
 */
class StudentImport extends CI_Controller
{
    const MAX_ROWS  = 5000;
    const MAX_BYTES = 8388608; // 8 MB

    /** Canonical field → normalized header aliases. */
    const HEADER_MAP = [
        'studentnumber' => ['studentnumber', 'studentno', 'studentnum', 'studentid', 'idnumber', 'studno', 'studentnoid'],
        'firstname'     => ['firstname', 'fname', 'givenname', 'first'],
        'middlename'    => ['middlename', 'mname', 'middle', 'mi'],
        'lastname'      => ['lastname', 'lname', 'surname', 'familyname', 'last'],
        'nameextn'      => ['nameextn', 'ext', 'extension', 'suffix', 'namesuffix', 'nameextension'],
        'sex'           => ['sex', 'gender'],
        'birthdate'     => ['birthdate', 'birthday', 'dob', 'dateofbirth', 'bday'],
        'contactno'     => ['contactno', 'contact', 'mobileno', 'mobile', 'phoneno', 'phone', 'cellno', 'cellphone', 'contactnumber'],
        'email'         => ['email', 'emailaddress', 'mail'],
        'course'        => ['course', 'course1', 'program', 'courseprogram', 'coursedescription', 'degree'],
        'major'         => ['major', 'major1', 'specialization'],
        'yearlevel'     => ['yearlevel', 'year', 'level', 'ylevel', 'yrlevel', 'gradelevel'],
        'section'       => ['section', 'sect'],
    ];

    const REQUIRED = ['studentnumber', 'firstname', 'lastname', 'sex', 'birthdate', 'contactno', 'email', 'course', 'yearlevel', 'section'];

    /** Columns shown on the Students sheet, in order. */
    const TEMPLATE_HEADERS = [
        'Student Number *', 'First Name *', 'Middle Name', 'Last Name *', 'Ext',
        'Sex *', 'Birth Date (YYYY-MM-DD) *', 'Mobile No *', 'Email *',
        'Course *', 'Major', 'Year Level *', 'Section *',
    ];

    // Mirrors the 'studentimport/*' rule in config/authguard.php — the
    // controller checks too so the two layers stay independent.
    private $allowedLevels = ['admin', 'super admin', 'it'];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->model('StudentModel');
        $this->load->model('SettingsModel');
        $this->load->model('AuditLogModel');
        $this->load->library('term');

        if ($this->session->userdata('logged_in') !== true) {
            redirect('login');
        }
        if (!in_array(strtolower(trim((string)$this->session->userdata('level'))), $this->allowedLevels, true)) {
            $this->session->set_flashdata('danger', 'You do not have permission to bulk import student accounts.');
            redirect('Page/index');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Template download                                                  */
    /* ------------------------------------------------------------------ */

    public function template()
    {
        $courses  = $this->importCourses();
        $sections = $this->importSections();

        $allMajors   = [];
        $allSections = [];
        foreach ($courses as $c) {
            foreach ($c['majors'] as $m) {
                $allMajors[$m] = true;
            }
        }
        foreach ($sections as $yearSections) {
            foreach ($yearSections as $sectionMap) {
                foreach ($sectionMap as $canon) {
                    $allSections[$canon] = true;
                }
            }
        }
        $allMajors   = array_keys($allMajors);
        $allSections = array_keys($allSections);
        sort($allMajors);
        sort($allSections);

        $xlsx = $this->buildTemplateXlsx(
            array_values(array_column($courses, 'desc')),
            $allMajors,
            $allSections,
            $courses,
            $sections
        );

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->set_header('Content-Disposition: attachment; filename="student_import_template.xlsx"')
            ->set_header('Content-Length: ' . strlen($xlsx))
            ->set_header('Cache-Control: no-store')
            ->set_output($xlsx);
    }

    /* ------------------------------------------------------------------ */
    /*  Upload + import                                                    */
    /* ------------------------------------------------------------------ */

    public function upload()
    {
        if (strtoupper((string)$this->input->method(true)) !== 'POST') {
            redirect('Page/profileList');
            return;
        }
        set_time_limit(300);

        $back = 'Page/profileList';
        $fail = function ($msg) use ($back) {
            $this->session->set_flashdata('danger', $msg);
            redirect($back);
        };

        if (empty($_FILES['file']) || !is_uploaded_file((string)$_FILES['file']['tmp_name'])) {
            $fail('Please choose the filled-in template file to upload.');
            return;
        }
        if ((int)$_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $fail('The file upload failed (error ' . (int)$_FILES['file']['error'] . '). Please try again.');
            return;
        }
        if ((int)$_FILES['file']['size'] > self::MAX_BYTES) {
            $fail('The file is too large. Maximum size is 8 MB.');
            return;
        }
        $ext = strtolower(pathinfo((string)$_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            $fail('Unsupported file type. Upload the .xlsx template, or a .csv saved from it.');
            return;
        }

        $rows = $this->readImportRows($_FILES['file']['tmp_name'], $ext);
        if ($rows === false) {
            $fail('The file could not be read. Use the provided Excel template (.xlsx) or save it as .csv.');
            return;
        }
        if (count($rows) < 2) {
            $fail('The file has no student rows. Fill in the Students sheet starting at row 2.');
            return;
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            $fail('The file has more than ' . self::MAX_ROWS . ' rows. Split it into smaller files.');
            return;
        }

        // Map header row to canonical fields.
        $colMap = $this->mapHeaders($rows[0]);
        $missing = [];
        foreach (self::REQUIRED as $key) {
            if (!isset($colMap[$key])) {
                $missing[] = $key;
            }
        }
        if ($missing) {
            $fail('The file is missing required column(s): ' . implode(', ', $missing)
                . '. Download the Excel template and keep the header row unchanged.');
            return;
        }

        // Resolve the active term the same way the registration form does.
        list($Semester, $SY) = $this->term->current();
        if (!$this->term->isValidSem($Semester) || !$this->term->isValidSy($SY)) {
            $fail('Bulk import is unavailable until an academic term is activated in Settings.');
            return;
        }

        // Preload lookup tables once.
        $courses       = $this->importCourses();          // keyed by lowercase trimmed desc
        $sections      = $this->importSections();          // [courseid][yearlevel] => [section...]
        $existingIds   = $this->existingStudentIds();      // uppercase set
        $existingEmail = $this->existingEmails();          // lowercase set

        $hasForceChange = $this->db->field_exists('force_change_password', 'o_users');
        $created = 0;
        $skipped = 0;
        $failed  = 0;
        $results = [];
        $seenIds    = [];
        $seenEmails = [];

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $excelRow = $i + 1;
            $row = $rows[$i];

            $get = function ($key) use ($row, $colMap) {
                if (!isset($colMap[$key])) {
                    return '';
                }
                $v = isset($row[$colMap[$key]]) ? (string)$row[$colMap[$key]] : '';
                return trim(str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', $v));
            };

            // Skip completely empty rows.
            $hasAny = false;
            foreach (self::HEADER_MAP as $key => $_) {
                if ($get($key) !== '') {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                continue;
            }

            $errors = [];
            $rec = $this->validateRow($get, $courses, $sections, $existingIds, $existingEmail, $seenIds, $seenEmails, $errors);

            if ($errors) {
                // Rows that fail ONLY on duplicate checks are skips, not
                // failures — re-uploading the same file (e.g. an export)
                // must be safe and quiet.
                $allDup = count(array_filter($errors, static function ($e) {
                    return strpos($e, 'already exists') === false && strpos($e, 'already in use') === false;
                })) === 0;
                $rowName = trim($get('firstname') . ' ' . $get('lastname'));
                if ($allDup) {
                    $skipped++;
                    $results[] = ['row' => $excelRow, 'id' => $get('studentnumber'), 'name' => $rowName, 'status' => 'skipped', 'message' => implode(' ', $errors)];
                } else {
                    $failed++;
                    $results[] = ['row' => $excelRow, 'id' => $get('studentnumber'), 'name' => $rowName, 'status' => 'error', 'message' => implode(' ', $errors)];
                }
                continue;
            }

            $sn = $rec['StudentNumber'];
            $seenIds[$sn]      = true;
            $seenEmails[$rec['email']] = true;

            $inserted = $this->createStudentAccount($rec, $Semester, $SY, $hasForceChange);
            if (!$inserted) {
                $failed++;
                $dbErr = $this->db->error();
                $detail = !empty($dbErr['message']) ? ' (' . $dbErr['message'] . ')' : '';
                $results[] = ['row' => $excelRow, 'id' => $sn, 'name' => trim($rec['FirstName'] . ' ' . $rec['LastName']), 'status' => 'error', 'message' => 'Database error while creating the account.' . $detail];
                continue;
            }

            // Non-fatal follow-ups, same as the single-record registration flow.
            $this->term->provisionStudentAccount($sn, $Semester, $SY);
            $this->db->where('studentNumber', $sn)->update('profiles', ['yearLevel' => $rec['yearLevel']]);

            $mailQueued = $this->queueCredentialEmail($rec);
            $created++;
            $results[] = [
                'row'     => $excelRow,
                'id'      => $sn,
                'name'    => trim($rec['FirstName'] . ' ' . $rec['LastName']),
                'status'  => 'created',
                'message' => $mailQueued ? 'Account created; credential email queued.' : 'Account created; credential email could not be queued — use Reset on the list if needed.',
            ];
        }

        $this->AuditLogModel->write(
            'create',
            'Bulk Student Import',
            'studentsignup',
            null,
            null,
            ['created' => $created, 'skipped' => $skipped, 'failed' => $failed, 'term' => $Semester . ' ' . $SY],
            $failed === 0 ? 1 : 0,
            'Bulk student import: ' . $created . ' created, ' . $skipped . ' skipped, ' . $failed . ' failed'
        );

        $this->session->set_flashdata('bulk_import', [
            'created' => $created,
            'skipped' => $skipped,
            'failed'  => $failed,
            'rows'    => array_slice($results, 0, 400),
            'truncated' => max(0, count($results) - 400),
        ]);

        $parts = $created . ' created, ' . $skipped . ' skipped, ' . $failed . ' failed';
        if ($failed === 0 && $created > 0) {
            $this->session->set_flashdata('success', 'Bulk import complete: ' . $parts . ' — for ' . $Semester . ' ' . $SY . '.');
        } elseif ($created > 0) {
            $this->session->set_flashdata('success', 'Bulk import finished: ' . $parts . ' — see details below.');
        } else {
            $this->session->set_flashdata('danger', 'Bulk import created no accounts (' . $parts . ') — see details below.');
        }

        redirect($back);
    }

    /* ------------------------------------------------------------------ */
    /*  Export                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Download all registered students (studentsignup) as an .xlsx using the
     * exact import-template columns, so the file can be edited and uploaded
     * straight back through Bulk Upload for testing or bulk edits.
     */
    public function export()
    {
        set_time_limit(300);

        $rows = $this->db
            ->select('StudentNumber, FirstName, MiddleName, LastName, nameExtn, Sex, birthDate, contactNo, email, Course1, Major1, yearLevel, section')
            ->order_by('LastName', 'ASC')
            ->order_by('FirstName', 'ASC')
            ->get('studentsignup')
            ->result_array();

        $sheetRows = [self::TEMPLATE_HEADERS];
        $ylMap = ['1st' => '1st Year', '2nd' => '2nd Year', '3rd' => '3rd Year', '4th' => '4th Year'];

        foreach ($rows as $r) {
            $birth = trim((string)$r['birthDate']);
            if ($birth === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
                $birth = '';
            }
            $yl = strtolower(trim((string)$r['yearLevel']));

            $sheetRows[] = [
                trim((string)$r['StudentNumber']),
                trim((string)$r['FirstName']),
                trim((string)$r['MiddleName']),
                trim((string)$r['LastName']),
                trim((string)$r['nameExtn']),
                trim((string)$r['Sex']),
                $birth,
                trim((string)$r['contactNo']),
                trim((string)$r['email']),
                trim((string)$r['Course1']),
                trim((string)$r['Major1']),
                $ylMap[$yl] ?? trim((string)$r['yearLevel']),
                trim((string)$r['section']),
            ];
        }

        $widths = [16, 22, 22, 22, 8, 12, 22, 16, 30, 52, 40, 14, 14];
        $xlsx = $this->zipXlsx(
            [['name' => 'Students', 'xml' => $this->sheetGeneric($sheetRows, $widths, 1)]],
            ''
        );

        $filename = 'registered_students_' . date('Y-m-d') . '.xlsx';
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Content-Length: ' . strlen($xlsx))
            ->set_header('Cache-Control: no-store')
            ->set_output($xlsx);
    }

    /* ------------------------------------------------------------------ */
    /*  Lookups                                                            */
    /* ------------------------------------------------------------------ */

    /** course_table → [lower(trim(desc)) => ['courseid'=>, 'desc'=>, 'majors'=>[lower=>canon]]] */
    private function importCourses()
    {
        $map = [];
        $rows = $this->db->select('courseid, CourseDescription, Major')->get('course_table')->result();
        foreach ($rows as $r) {
            $desc = trim((string)$r->CourseDescription);
            if ($desc === '') {
                continue;
            }
            $key = strtolower($desc);
            if (!isset($map[$key])) {
                $map[$key] = ['courseid' => (int)$r->courseid, 'desc' => $desc, 'majors' => []];
            }
            $major = trim((string)$r->Major);
            if ($major !== '') {
                $map[$key]['majors'][strtolower($major)] = $major;
            }
        }
        return $map;
    }

    /** course_sections → [courseid][yearlevel] => [lower(section)=>canon] */
    private function importSections()
    {
        $map = [];
        if (!$this->db->table_exists('course_sections')) {
            return $map;
        }
        $rows = $this->db->select('courseid, year_level, section')
            ->where('is_active', 1)
            ->order_by('section', 'ASC')
            ->get('course_sections')->result();
        foreach ($rows as $r) {
            $cid = (int)$r->courseid;
            $yl  = trim((string)$r->year_level);
            $sec = trim((string)$r->section);
            if ($sec === '') {
                continue;
            }
            $map[$cid][$yl][strtolower($sec)] = $sec;
        }
        return $map;
    }

    private function existingStudentIds()
    {
        $set = [];
        foreach (['o_users' => 'username', 'studentsignup' => 'StudentNumber', 'studeprofile' => 'StudentNumber'] as $table => $col) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists($col, $table)) {
                continue;
            }
            foreach ($this->db->select($col)->get($table)->result_array() as $r) {
                $v = strtoupper(trim((string)$r[$col]));
                if ($v !== '') {
                    $set[$v] = true;
                }
            }
        }
        return $set;
    }

    private function existingEmails()
    {
        $set = [];
        foreach (['o_users' => 'email', 'studentsignup' => 'email'] as $table => $col) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists($col, $table)) {
                continue;
            }
            foreach ($this->db->select($col)->get($table)->result_array() as $r) {
                $v = strtolower(trim((string)$r[$col]));
                if ($v !== '') {
                    $set[$v] = true;
                }
            }
        }
        return $set;
    }

    /* ------------------------------------------------------------------ */
    /*  Parsing                                                            */
    /* ------------------------------------------------------------------ */

    private function readImportRows($path, $ext)
    {
        if ($ext === 'csv') {
            $fh = @fopen($path, 'r');
            if (!$fh) {
                return false;
            }
            $rows = [];
            while (($r = fgetcsv($fh, 0, ',')) !== false) {
                $rows[] = $r;
            }
            fclose($fh);
            if ($rows && isset($rows[0][0])) {
                // Strip UTF-8 BOM from the first header cell.
                $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$rows[0][0]);
            }
            return $rows;
        }

        require_once APPPATH . 'libraries/SimpleXLSX.php';
        $xlsx = \Shuchkin\SimpleXLSX::parseFile($path);
        if (!$xlsx) {
            return false;
        }
        $xlsx->setDateTimeFormat('Y-m-d');

        // Prefer the "Students" sheet; fall back to the first sheet.
        $sheetIdx = 0;
        foreach ((array)$xlsx->sheetNames() as $idx => $name) {
            if (strtolower(trim((string)$name)) === 'students') {
                $sheetIdx = (int)$idx;
                break;
            }
        }
        return $xlsx->rows($sheetIdx);
    }

    /** Header row → [canonicalKey => columnIndex]. */
    private function mapHeaders(array $headerRow)
    {
        $alias = [];
        foreach (self::HEADER_MAP as $key => $names) {
            foreach ($names as $n) {
                $alias[$n] = $key;
            }
        }
        $map = [];
        foreach ($headerRow as $idx => $h) {
            $norm = strtolower(preg_replace('/\([^)]*\)/', '', (string)$h));
            $norm = preg_replace('/[^a-z0-9]+/', '', $norm);
            if ($norm !== '' && isset($alias[$norm]) && !isset($map[$alias[$norm]])) {
                $map[$alias[$norm]] = (int)$idx;
            }
        }
        return $map;
    }

    /* ------------------------------------------------------------------ */
    /*  Row validation                                                     */
    /* ------------------------------------------------------------------ */

    private function validateRow(callable $get, array $courses, array $sections, array $existingIds, array $existingEmails, array $seenIds, array $seenEmails, array &$errors)
    {
        $rec = [];

        // Student number
        $sn = strtoupper($get('studentnumber'));
        if ($sn === '') {
            $errors[] = 'Student Number is required.';
        } elseif (!preg_match('/^[0-9]{4}-[0-9]{4,}$/', $sn)) {
            $errors[] = 'Student Number must be YYYY-NNNN, e.g. 2026-0001 (4 digits, a dash, then 4 or more digits).';
        } elseif (isset($existingIds[$sn]) || isset($seenIds[$sn])) {
            $errors[] = 'Student Number ' . $sn . ' already exists.';
        }
        $rec['StudentNumber'] = $sn;

        // Names
        $first = $get('firstname');
        $middle = $get('middlename');
        $last = $get('lastname');
        $ext = $get('nameextn');
        if ($first === '') {
            $errors[] = 'First Name is required.';
        } elseif (strlen($first) > 45) {
            $errors[] = 'First Name is too long (max 45 characters).';
        }
        if ($last === '') {
            $errors[] = 'Last Name is required.';
        } elseif (strlen($last) > 45) {
            $errors[] = 'Last Name is too long (max 45 characters).';
        }
        if (strlen($middle) > 45 || strlen($ext) > 45) {
            $errors[] = 'Middle Name / Ext is too long (max 45 characters).';
        }
        $rec['FirstName']   = strtoupper($first);
        $rec['MiddleName']  = strtoupper($middle);
        $rec['LastName']    = strtoupper($last);
        $rec['nameExtn']    = strtoupper($ext);

        // Sex
        $sexRaw = strtolower($get('sex'));
        $sexMap = ['m' => 'Male', 'male' => 'Male', 'f' => 'Female', 'female' => 'Female', 'o' => 'Others', 'other' => 'Others', 'others' => 'Others'];
        if (!isset($sexMap[$sexRaw])) {
            $errors[] = 'Sex must be Male, Female, or Others.';
            $rec['Sex'] = '';
        } else {
            $rec['Sex'] = $sexMap[$sexRaw];
        }

        // Birth date → Y-m-d, and age
        $birthRaw = $get('birthdate');
        $birth = $this->parseBirthDate($birthRaw);
        if ($birth === null) {
            $errors[] = 'Birth Date must be a valid date (YYYY-MM-DD).';
            $rec['birthDate'] = '';
            $rec['age'] = 0;
        } else {
            $rec['birthDate'] = $birth;
            $dob = DateTime::createFromFormat('Y-m-d', $birth);
            $rec['age'] = $dob ? (int)$dob->diff(new DateTime('today'))->y : 0;
        }

        // Mobile
        $contact = preg_replace('/\D+/', '', $get('contactno'));
        if (!preg_match('/^09[0-9]{9}$/', $contact)) {
            $errors[] = 'Mobile No must be 11 digits starting with 09.';
        }
        $rec['contactNo'] = $contact;

        // Email
        $email = strtolower($get('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid Email is required.';
        } elseif (strlen($email) > 45) {
            $errors[] = 'Email is too long (max 45 characters).';
        } elseif (isset($existingEmails[$email]) || isset($seenEmails[$email])) {
            $errors[] = 'Email ' . $email . ' is already in use.';
        }
        $rec['email'] = $email;

        // Course
        $courseRaw = $get('course');
        $courseKey = strtolower(trim($courseRaw));
        if ($courseKey === '' || !isset($courses[$courseKey])) {
            $errors[] = 'Course is required and must match one of the dropdown values (see the Lists sheet).';
            $rec['Course1'] = '';
            $rec['_courseid'] = 0;
            $rec['Major1'] = '';
        } else {
            $course = $courses[$courseKey];
            $rec['Course1']   = $course['desc'];
            $rec['_courseid'] = $course['courseid'];

            // Major: optional — auto-resolve when the course has exactly one.
            $majorRaw = strtolower(trim($get('major')));
            if ($majorRaw === '') {
                $rec['Major1'] = count($course['majors']) === 1 ? reset($course['majors']) : '';
            } elseif (isset($course['majors'][$majorRaw])) {
                $rec['Major1'] = $course['majors'][$majorRaw];
            } else {
                $errors[] = 'Major "' . $get('major') . '" does not belong to ' . $course['desc'] . '.';
                $rec['Major1'] = '';
            }
        }

        // Year level
        $ylRaw = strtolower($get('yearlevel'));
        $rec['yearLevel'] = '';
        if (preg_match('/([1-4])/', $ylRaw, $m)) {
            $rec['yearLevel'] = ['1st', '2nd', '3rd', '4th'][(int)$m[1] - 1];
        } else {
            $errors[] = 'Year Level must be 1st, 2nd, 3rd, or 4th.';
        }

        // Section
        $section = trim($get('section'));
        if ($section === '') {
            $errors[] = 'Section is required.';
            $rec['section'] = '';
        } elseif (strlen($section) > 50) {
            $errors[] = 'Section is too long (max 50 characters).';
            $rec['section'] = '';
        } else {
            $cid = (int)($rec['_courseid'] ?? 0);
            $yl  = (string)$rec['yearLevel'];
            $valid = $cid && $yl !== '' && isset($sections[$cid][$yl]) ? $sections[$cid][$yl] : null;
            if ($valid !== null) {
                $lk = strtolower($section);
                if (isset($valid[$lk])) {
                    $rec['section'] = $valid[$lk];
                } else {
                    $errors[] = 'Section "' . $section . '" is not a valid section for that Course + Year Level (see the Lists sheet).';
                    $rec['section'] = '';
                }
            } else {
                $rec['section'] = $section;
            }
        }
        unset($rec['_courseid']);

        // Password: always the student's birth date (YYYY-MM-DD) — the same
        // convention the single-record admin form uses.
        $rec['_password'] = $rec['birthDate'];

        return $rec;
    }

    private function parseBirthDate($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return null;
        }
        // Excel serial number (e.g. a numeric cell that lost its date format)
        if (is_numeric($raw) && (float)$raw > 20000 && (float)$raw < 80000) {
            $ts = (int)(((float)$raw - 25569) * 86400);
            return gmdate('Y-m-d', $ts);
        }
        $raw = preg_replace('/\s+.*$/', '', $raw); // drop any trailing time part
        foreach (['Y-m-d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/y'] as $fmt) {
            $d = DateTime::createFromFormat($fmt, $raw);
            if ($d instanceof DateTime && $d->format($fmt) === $raw) {
                $out = $d->format('Y-m-d');
                return $out <= date('Y-m-d') ? $out : null;
            }
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }
        $out = date('Y-m-d', $ts);
        return $out <= date('Y-m-d') ? $out : null;
    }

    /* ------------------------------------------------------------------ */
    /*  Account creation                                                   */
    /* ------------------------------------------------------------------ */

    private function createStudentAccount(array $rec, $Semester, $SY, $hasForceChange)
    {
        $studentNumber = $rec['StudentNumber'];
        $firstName = $rec['FirstName'];
        $middleName = $rec['MiddleName'];
        $lastName = $rec['LastName'];
        $fullName = substr(trim($firstName . ' ' . $middleName . ' ' . $lastName), 0, 65);
        $passwordHash = fbmso_password_hash($rec['_password']);
        if ($passwordHash === '') {
            return false;
        }

        // studentsignup payload — identical shape to Registration/index.
        $studentData = array_merge([
            'BirthPlace'           => '',
            'CivilStatus'          => 'Single',
            'Religion'             => '',
            'province'             => '',
            'city'                 => '',
            'brgy'                 => '',
            'sitio'                => '',
            'Course2'              => '',
            'Course3'              => '',
            'Major2'               => '',
            'Major3'               => '',
            'Status'               => 'Pending',
            'graduationDate'       => '',
            'guardian'             => '',
            'guardianRelationship' => '',
            'guardianContact'      => '',
            'guardianAddress'      => '',
            'father'               => '',
            'fOccupation'          => '',
            'fatherAddress'        => '',
            'fatherContact'        => '',
            'mother'               => '',
            'mOccupation'          => '',
            'motherAddress'        => '',
            'motherContact'        => '',
        ], [
            'StudentNumber'  => $studentNumber,
            'FirstName'      => $firstName,
            'MiddleName'     => $middleName,
            'LastName'       => $lastName,
            'nameExtn'       => $rec['nameExtn'],
            'Sex'            => $rec['Sex'],
            'birthDate'      => $rec['birthDate'],
            'age'            => $rec['age'],
            'contactNo'      => $rec['contactNo'],
            'email'          => $rec['email'],
            'section'        => $rec['section'],
            'working'        => 'No',
            'VaccStat'       => '',
            'nationality'    => 'Filipino',
            'yearLevel'      => $rec['yearLevel'],
            'Course1'        => $rec['Course1'],
            'Major1'         => $rec['Major1'],
            'EnrollmentDate' => date('Y-m-d'),
        ]);

        $userData = [
            'username'    => $studentNumber,
            'IDNumber'    => $studentNumber,
            'fName'       => $firstName,
            'mName'       => $middleName,
            'lName'       => $lastName,
            'name'        => $fullName,
            'password'    => $passwordHash,
            'position'    => 'Student',
            'email'       => $rec['email'],
            'avatar'      => 'avatar.png',
            // Admin-created accounts are usable immediately and must set a
            // new password at first sign-in — same as Page/userAccounts.
            'acctStat'    => 'active',
            'dateCreated' => date('Y-m-d'),
        ];
        if ($hasForceChange) {
            $userData['force_change_password'] = 1;
        }

        $this->db->trans_begin();
        $ok1 = $this->db->insert('studentsignup', $studentData);
        $ok2 = $ok1 && $this->db->insert('o_users', $userData);

        // semesterstude — same upsert as Registration/index.
        $ok3 = $ok2;
        if ($ok2) {
            $existing = $this->db->get_where('semesterstude', [
                'StudentNumber' => $studentNumber,
                'SY'            => $SY,
                'Semester'      => $Semester,
            ])->row();

            $profiling = [
                'StudentNumber'    => $studentNumber,
                'Course'           => $rec['Course1'],
                'YearLevel'        => $rec['yearLevel'],
                'Status'           => 'Enrolled',
                'Semester'         => $Semester,
                'SY'               => $SY,
                'Term'             => null,
                'Section'          => $rec['section'],
                'StudeStatus'      => 'New',
                'Scholarship'      => '',
                'DurationFrom'     => '',
                'DurationTo'       => '',
                'AssessmentDate'   => '',
                'AssessmentResult' => '',
                'PayingStatus'     => 'Paying',
                'GrantAmount'      => 0,
                'YearLevelStat'    => 'Regular',
                'Major'            => $rec['Major1'],
                'settingsID'       => 1,
                'enroledDate'      => date('Y-m-d'),
                'crossEnrollee'    => '',
                'classSession'     => '',
                'prevGPA'          => '',
                'testType'         => '',
                'testDate'         => '',
                'testResult'       => '',
                'caapPromoted'     => '',
            ];

            if ($existing) {
                $ok3 = $this->db->where('semstudentid', $existing->semstudentid)->update('semesterstude', [
                    'Course'    => $profiling['Course'],
                    'Major'     => $profiling['Major'],
                    'YearLevel' => $profiling['YearLevel'],
                    'Status'    => $profiling['Status'],
                    'Section'   => $profiling['Section'],
                ]);
            } else {
                $ok3 = $this->db->insert('semesterstude', $profiling);
            }
        }

        if ($ok1 && $ok2 && $ok3 && $this->db->trans_status() !== false) {
            $this->db->trans_commit();
            return true;
        }
        $this->db->trans_rollback();
        return false;
    }

    private function queueCredentialEmail(array $rec)
    {
        $email = trim((string)$rec['email']);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $schoolName = trim((string)$this->SettingsModel->getSchoolName());
        if ($schoolName === '') {
            $schoolName = 'School Records Management System';
        }
        $loginUrl = base_url('login');
        $displayName = trim($rec['FirstName'] . ' ' . $rec['LastName']);
        $esc = static function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };

        $message = '<div style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;color:#1f2937">'
            . '<div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:30px">'
            . '<h2 style="color:#2a4090;margin-top:0">Your FBMSO student account is ready</h2>'
            . '<p>Dear <strong>' . $esc($displayName !== '' ? $displayName : $rec['StudentNumber']) . '</strong>,</p>'
            . '<p>A student account has been created for you.</p>'
            . '<table style="width:100%;max-width:440px;border-collapse:collapse;margin:20px 0">'
            . '<tr><td style="padding:10px;background:#f1f5f9;border:1px solid #dbe2ea"><strong>Username</strong></td>'
            . '<td style="padding:10px;border:1px solid #dbe2ea">' . $esc($rec['StudentNumber']) . '</td></tr>'
            . '<tr><td style="padding:10px;background:#f1f5f9;border:1px solid #dbe2ea"><strong>Temporary Password</strong></td>'
            . '<td style="padding:10px;border:1px solid #dbe2ea;font-family:monospace">' . $esc($rec['_password']) . '</td></tr>'
            . '</table>'
            . '<p>Your temporary password is your <strong>birth date in YYYY-MM-DD format</strong> (for example, 2005-03-11).</p>'
            . '<p>Sign in with this temporary password — you will be asked to create a new password immediately.</p>'
            . '<p><a href="' . $esc($loginUrl) . '" style="display:inline-block;padding:10px 20px;background:#2a4090;color:#fff;text-decoration:none;border-radius:5px">Sign in</a></p>'
            . '<p style="margin-top:30px">Best regards,<br><strong>' . $esc($schoolName) . '</strong></p>'
            . '<hr style="margin-top:35px;border:0;border-top:1px solid #e5e7eb">'
            . '<p style="font-size:12px;color:#6b7280">This is an automated message. Please do not reply or share your temporary password.</p>'
            . '</div></div>';

        return fbmso_mailqueue_push(
            $this,
            $email,
            'Your FBMSO Student Account - ' . $schoolName,
            $message,
            $schoolName
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Minimal XLSX writer (ZipArchive)                                   */
    /* ------------------------------------------------------------------ */

    private function buildTemplateXlsx(array $courseList, array $majorList, array $sectionList, array $courses, array $sections)
    {
        $sexList  = ['Male', 'Female', 'Others'];
        $yearList = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

        // ---- Lists sheet columns (A..E) ----
        $listRows = [['Sex', 'Year Level', 'Course', 'Major', 'Section']];
        $max = max(count($sexList), count($yearList), count($courseList), count($majorList), count($sectionList));
        for ($i = 0; $i < $max; $i++) {
            $listRows[] = [
                $sexList[$i]     ?? '',
                $yearList[$i]    ?? '',
                $courseList[$i]  ?? '',
                $majorList[$i]   ?? '',
                $sectionList[$i] ?? '',
            ];
        }

        // ---- Instructions sheet ----
        $ins = [];
        $ins[] = ['Student Bulk Import — Instructions'];
        $ins[] = [''];
        $ins[] = ['1. Fill in the "Students" sheet — one student per row, starting at row 2. Do not change the header row.'];
        $ins[] = ['2. Columns marked * are required. Middle Name, Ext, and Major are optional.'];
        $ins[] = ['3. Save the file (keep .xlsx, or use Save As → CSV), then upload it on the Registered Students page via Bulk Upload.'];
        $ins[] = [''];
        $ins[] = ['Field notes'];
        $ins[] = ['Student Number — format YYYY-NNNN, e.g. 2026-0001 (4 digits, a dash, then 4 or more digits). Must be unique.'];
        $ins[] = ['Sex — pick Male, Female, or Others from the dropdown.'];
        $ins[] = ['Birth Date — format YYYY-MM-DD, e.g. 2005-03-11.'];
        $ins[] = ['Mobile No — 11 digits starting with 09, e.g. 09123456789.'];
        $ins[] = ['Email — must be valid and unique; the student\'s login credentials are sent here.'];
        $ins[] = ['Course / Major / Year Level / Section — use the dropdowns. If Major is left blank it is filled automatically when the course has one major.'];
        $ins[] = ['Password — the student\'s Birth Date (YYYY-MM-DD) is automatically their temporary password. They will be asked to set a new one at first sign-in.'];
        $ins[] = [''];
        $ins[] = ['Example row (do not copy the Student Number — make your own)'];
        $ins[] = self::TEMPLATE_HEADERS;
        $ins[] = ['2026-0001', 'JUAN', 'SANTOS', 'DELA CRUZ', '', 'Male', '2005-03-11', '09123456789', 'juan.delacruz@email.com', $courseList[0] ?? 'Course', '', '1st Year', ''];
        $ins[] = [''];
        $ins[] = ['Valid Sections by Course and Year Level'];
        foreach ($courses as $c) {
            $cid = $c['courseid'];
            foreach (['1st', '2nd', '3rd', '4th'] as $yl) {
                $secs = isset($sections[$cid][$yl]) ? array_values($sections[$cid][$yl]) : [];
                if (!$secs) {
                    continue;
                }
                $ins[] = [$c['desc'], $yl . ' Year', implode(', ', $secs)];
            }
        }

        // ---- Assemble sheets ----
        $sheets = [
            ['name' => 'Students',     'xml' => $this->sheetStudents()],
            ['name' => 'Instructions', 'xml' => $this->sheetGeneric($ins, [42, 14, 60, 30, 40], 1)],
            ['name' => 'Lists',        'xml' => $this->sheetGeneric($listRows, [10, 12, 60, 45, 12], 1)],
        ];

        $nSex  = count($sexList);
        $nYl   = count($yearList);
        $nCrs  = max(1, count($courseList));
        $nMaj  = max(1, count($majorList));
        $nSec  = max(1, count($sectionList));

        $definedNames = implode('', [
            '<definedName name="SexList">Lists!$A$2:$A$' . ($nSex + 1) . '</definedName>',
            '<definedName name="YearLevelList">Lists!$B$2:$B$' . ($nYl + 1) . '</definedName>',
            '<definedName name="CourseList">Lists!$C$2:$C$' . ($nCrs + 1) . '</definedName>',
            '<definedName name="MajorList">Lists!$D$2:$D$' . ($nMaj + 1) . '</definedName>',
            '<definedName name="SectionList">Lists!$E$2:$E$' . ($nSec + 1) . '</definedName>',
        ]);

        return $this->zipXlsx($sheets, $definedNames);
    }

    /** Students sheet: frozen bold header + validations. */
    private function sheetStudents()
    {
        $widths = [16, 22, 22, 22, 8, 12, 22, 16, 30, 52, 40, 14, 14];
        $headerCells = '';
        foreach (self::TEMPLATE_HEADERS as $i => $h) {
            $headerCells .= $this->xlCell($i, 1, $h, 1);
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }

        $dv = '<dataValidations count="8">'
            // list validations
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid Sex" error="Choose Male, Female, or Others from the dropdown." sqref="F2:F2000"><formula1>SexList</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid Course" error="Choose a course from the dropdown (see the Lists sheet)." sqref="J2:J2000"><formula1>CourseList</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid Major" error="Choose a major from the dropdown (see the Lists sheet)." sqref="K2:K2000"><formula1>MajorList</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid Year Level" error="Choose a year level from the dropdown." sqref="L2:L2000"><formula1>YearLevelList</formula1></dataValidation>'
            . '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Invalid Section" error="Choose a section from the dropdown. It must match the Course + Year Level (see Instructions)." sqref="M2:M2000"><formula1>SectionList</formula1></dataValidation>'
            // input prompts
            . '<dataValidation allowBlank="1" showInputMessage="1" promptTitle="Student Number" prompt="Format: YYYY-NNNN, e.g. 2026-0001 (4 digits, a dash, then 4 or more digits). Must be unique." sqref="A2:A2000"/>'
            . '<dataValidation allowBlank="1" showInputMessage="1" promptTitle="Birth Date" prompt="Format: YYYY-MM-DD, e.g. 2005-03-11." sqref="G2:G2000"/>'
            . '<dataValidation allowBlank="1" showInputMessage="1" promptTitle="Mobile No" prompt="11 digits starting with 09, e.g. 09123456789." sqref="H2:H2000"/>'
            . '</dataValidations>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData><row r="1">' . $headerCells . '</row></sheetData>'
            . $dv
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    /** Generic sheet: rows of plain strings; first row styled as header. */
    private function sheetGeneric(array $rows, array $widths, $headerRow = 0)
    {
        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }

        $xml = '';
        foreach ($rows as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach ($row as $c => $v) {
                if ($v === '' || $v === null) {
                    continue;
                }
                $style = ($r === $headerRow - 1) ? 1 : 0;
                // Instructions: style section titles
                $xml .= $this->xlCell($c, $r + 1, (string)$v, $style);
            }
            $xml .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $xml . '</sheetData>'
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private function xlCell($col, $row, $value, $style = 0)
    {
        $ref = $this->xlCol($col) . $row;
        $s = $style ? ' s="' . $style . '"' : '';
        return '<c r="' . $ref . '" t="inlineStr"' . $s . '><is><t xml:space="preserve">'
            . htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8')
            . '</t></is></c>';
    }

    private function xlCol($i)
    {
        $name = '';
        $i = (int)$i + 1;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $name = chr(65 + $m) . $name;
            $i = intdiv($i - 1 - $m, 26);
        }
        return $name;
    }

    private function zipXlsx(array $sheets, $definedNames)
    {
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        foreach ($sheets as $i => $_) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheets as $i => $_) {
            $wbRels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $wbRels .= '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $sheetTags = '';
        foreach ($sheets as $i => $s) {
            $sheetTags .= '<sheet name="' . htmlspecialchars($s['name'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<workbookPr/>'
            . '<bookViews><workbookView/></bookViews>'
            . '<sheets>' . $sheetTags . '</sheets>'
            . ($definedNames !== '' ? '<definedNames>' . $definedNames . '</definedNames>' : '')
            . '</workbook>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2A4090"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '<dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium9" defaultPivotStyle="PivotStyleLight16"/>'
            . '</styleSheet>';

        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Student Import Template</dc:title>'
            . '<dc:creator>FBMSO</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified>'
            . '</cp:coreProperties>';

        $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>FBMSO</Application></Properties>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            return '';
        }
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('docProps/core.xml', $core);
        $zip->addFromString('docProps/app.xml', $app);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/styles.xml', $styles);
        foreach ($sheets as $i => $s) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $s['xml']);
        }
        $zip->close();

        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data === false ? '' : $data;
    }
}

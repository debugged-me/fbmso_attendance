<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile accounting API — mirrors the web Accounting controller so the
 * cashier role can do on mobile everything its web sidebar offers:
 *
 *   GET  api/mobile/accounting/dashboard            — Page::accounting stats
 *   GET  api/mobile/accounting/payment/context      — Payment() form data
 *   POST api/mobile/accounting/payment              — Payment() POST
 *   POST api/mobile/accounting/payment/delete       — deletePayment()
 *   GET  api/mobile/accounting/payment/audit-log    — paymentAuditLog()
 *   GET  api/mobile/accounting/partial-payments     — partialPayments()
 *   GET  api/mobile/accounting/collection-report    — collectionReport()
 *   GET  api/mobile/accounting/ledger               — ledger()
 *   GET  api/mobile/accounting/expenses-report      — expenseSGenerate()
 *   GET  api/mobile/accounting/terms                — term filter options
 *   GET  api/mobile/accounting/fees                 — course_setUp() list
 *   POST api/mobile/accounting/fees/create|update|delete
 *
 * Gate: the web controller's $allowedLevels = ['Admin', 'Cashier'].
 */
class MobileAccounting extends MobileApi
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'fbmso_email']);
        $this->load->library('term');
        $this->load->model('StudentModel');
        $this->load->model('SettingsModel');
    }

    // ─── Gate ──────────────────────────────────────────────────────────────

    /** Admin or Cashier only — same as Accounting::$allowedLevels. */
    private function require_accounting(): ?array
    {
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return null;

        $pos = strtolower(trim($this->position_of((string)$tokenRow['username'])));
        if (!in_array($pos, ['admin', 'cashier'], true)) {
            $this->json(['ok' => false, 'message' => 'Accounting access only.'], 403);
            return null;
        }
        return $tokenRow;
    }

    private function position_of(string $username): string
    {
        $row = $this->db->select('position')->from('o_users')
            ->where('username', $username)->limit(1)->get()->row();
        return (string)($row->position ?? '');
    }

    // ─── Shared helpers (ported from Accounting.php) ───────────────────────

    private function currentSemSy()
    {
        return $this->term->current();
    }

    private function isValidDate($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }

    private function tableExists($table)
    {
        return $this->db->table_exists($table);
    }

    private function logPaymentAudit($action, $payment, $changedBy, $newValues = null)
    {
        $oldValues = [
            'StudentNumber' => (string)($payment->StudentNumber ?? ''),
            'ORNumber'      => (string)($payment->ORNumber ?? ''),
            'PDate'         => (string)($payment->PDate ?? ''),
            'Amount'        => (string)($payment->Amount ?? ''),
            'description'   => (string)($payment->description ?? ''),
        ];

        $this->db->insert('payment_audit_log', [
            'payment_id'     => (int)($payment->ID ?? 0),
            'action'         => $action,
            'or_number'      => (string)($payment->ORNumber ?? ''),
            'student_number' => (string)($payment->StudentNumber ?? ''),
            'description'    => (string)($payment->description ?? ''),
            'amount'         => (float)($payment->Amount ?? 0),
            'old_values'     => json_encode($oldValues),
            'new_values'     => $newValues !== null ? json_encode($newValues) : null,
            'changed_by'     => $changedBy,
            'changed_at'     => (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s'),
        ]);
    }

    private function nextTableId($table, $idColumn)
    {
        $row = $this->db->select_max($idColumn, 'max_id')->get($table)->row();
        return (int)($row->max_id ?? 0) + 1;
    }

    private function resolveOrDatePrefix($source = '')
    {
        $source = trim((string)$source);
        if ($this->isValidDate($source)) {
            return date('ymd', strtotime($source));
        }
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        return $now->format('ymd');
    }

    private function formatOrNumber($datePrefix, $sequence)
    {
        return sprintf('%s-%04d', $datePrefix, (int)$sequence);
    }

    private function generateNextOrNumber($source = '')
    {
        $date = $this->isValidDate(trim((string)$source))
            ? trim((string)$source)
            : (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        $prefix = $this->resolveOrDatePrefix($date);

        $row = $this->db->select("MAX(CAST(SUBSTRING_INDEX(ORNumber, '-', -1) AS UNSIGNED)) AS max_sequence", false)
            ->from('paymentsaccounts')
            ->where('PDate', $date)
            ->get()
            ->row();

        $nextSequence = (int)($row->max_sequence ?? 0) + 1;
        return $this->formatOrNumber($prefix, $nextSequence);
    }

    private function isDuplicateDbError($dbError)
    {
        $code = (int)($dbError['code'] ?? 0);
        $message = (string)($dbError['message'] ?? '');
        return $code === 1062 || stripos($message, 'Duplicate entry') !== false;
    }

    private function getStudentsForPayment($sem, $sy)
    {
        $this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS FallbackCourse,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS FallbackMajor,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS FallbackYearLevel,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
        $this->db->from('studentsignup su');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = su.StudentNumber', 'left');
        $this->db->where("su.StudentNumber <> ''", null, false);
        $this->db->group_by('su.StudentNumber');
        $this->db->order_by('COALESCE(NULLIF(sp.LastName,\'\'), su.LastName, \'\')', 'ASC', false);
        $this->db->order_by('COALESCE(NULLIF(sp.FirstName,\'\'), su.FirstName, \'\')', 'ASC', false);
        $students = $this->db->get()->result();

        if (empty($students)) {
            return [];
        }

        $enrolment = [];
        $enrolRows = $this->db->select('semstudentid, StudentNumber, Course, Major, YearLevel, Semester, SY')
            ->from('semesterstude')
            ->get()
            ->result();

        foreach ($enrolRows as $e) {
            $sn = trim((string)$e->StudentNumber);
            if ($sn === '') {
                continue;
            }
            $current = $enrolment[$sn] ?? null;
            if ($current === null) {
                $enrolment[$sn] = $e;
                continue;
            }
            $currentIsActive = ($current->Semester === $sem && $current->SY === $sy);
            $newIsActive = ($e->Semester === $sem && $e->SY === $sy);
            if ($newIsActive && !$currentIsActive) {
                $enrolment[$sn] = $e;
            } elseif (!$newIsActive && !$currentIsActive && (int)$e->semstudentid > (int)$current->semstudentid) {
                $enrolment[$sn] = $e;
            }
        }

        foreach ($students as $s) {
            $e = $enrolment[trim((string)$s->StudentNumber)] ?? null;
            if ($e !== null) {
                $s->Course    = trim((string)$e->Course) !== '' ? $e->Course : $s->FallbackCourse;
                $s->Major     = trim((string)$e->Major) !== '' ? $e->Major : $s->FallbackMajor;
                $s->YearLevel = trim((string)$e->YearLevel) !== '' ? $e->YearLevel : $s->FallbackYearLevel;
                $s->Semester  = (string)$e->Semester;
                $s->SY        = (string)$e->SY;
            } else {
                $s->Course    = $s->FallbackCourse;
                $s->Major     = $s->FallbackMajor;
                $s->YearLevel = $s->FallbackYearLevel;
                $s->Semester  = '';
                $s->SY        = '';
            }
            unset($s->FallbackCourse, $s->FallbackMajor, $s->FallbackYearLevel);
        }

        $listed = [];
        foreach ($students as $s) {
            $listed[trim((string)$s->StudentNumber)] = true;
        }
        $missing = array_diff_key($enrolment, $listed);
        if (!empty($missing)) {
            $extra = $this->db->select("
				sp.StudentNumber,
				COALESCE(sp.course, '') AS Course,
				COALESCE(sp.major, '') AS Major,
				COALESCE(sp.yearLevel, '') AS YearLevel,
				COALESCE(sp.FirstName, '') AS FirstName,
				COALESCE(sp.MiddleName, '') AS MiddleName,
				COALESCE(sp.LastName, '') AS LastName
			", false)
                ->from('studeprofile sp')
                ->where_in('sp.StudentNumber', array_keys($missing))
                ->get()
                ->result();

            foreach ($extra as $s) {
                $e = $enrolment[trim((string)$s->StudentNumber)];
                $s->Course    = trim((string)$e->Course) !== '' ? $e->Course : $s->Course;
                $s->Major     = trim((string)$e->Major) !== '' ? $e->Major : $s->Major;
                $s->YearLevel = trim((string)$e->YearLevel) !== '' ? $e->YearLevel : $s->YearLevel;
                $s->Semester  = (string)$e->Semester;
                $s->SY        = (string)$e->SY;
                $students[]   = $s;
                unset($missing[trim((string)$s->StudentNumber)]);
            }

            foreach ($missing as $sn => $e) {
                $students[] = (object)[
                    'StudentNumber' => $sn,
                    'Course'        => (string)$e->Course,
                    'Major'         => (string)$e->Major,
                    'YearLevel'     => (string)$e->YearLevel,
                    'Semester'      => (string)$e->Semester,
                    'SY'            => (string)$e->SY,
                    'FirstName'     => '',
                    'MiddleName'    => '',
                    'LastName'      => '',
                ];
            }
        }

        return $students;
    }

    private function getStudentContext($studentNumber, $sem, $sy)
    {
        $this->db->select("
			ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
        $this->db->from('semesterstude ss');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left');
        $this->db->join('studentsignup su', 'su.StudentNumber = ss.StudentNumber', 'left');
        $this->db->where('ss.StudentNumber', $studentNumber);
        if ($sem !== '') {
            $this->db->where('ss.Semester', $sem);
        }
        if ($sy !== '') {
            $this->db->where('ss.SY', $sy);
        }
        $this->db->order_by('ss.semstudentid', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        if (!$row) {
            $row = $this->db->select("
				ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Semester, ss.SY,
				COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
				COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
				COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
				COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
			", false)
                ->from('semesterstude ss')
                ->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left')
                ->join('studentsignup su', 'su.StudentNumber = ss.StudentNumber', 'left')
                ->where('ss.StudentNumber', $studentNumber)
                ->order_by('ss.semstudentid', 'DESC')
                ->limit(1)
                ->get()
                ->row();
        }

        if ($row) {
            return $row;
        }

        $this->db->select("
			su.StudentNumber,
			COALESCE(NULLIF(sp.course,''), su.Course1, '') AS Course,
			COALESCE(NULLIF(sp.major,''), su.Major1, '') AS Major,
			COALESCE(NULLIF(sp.yearLevel,''), su.yearLevel, '') AS YearLevel,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName
		", false);
        $this->db->from('studentsignup su');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = su.StudentNumber', 'left');
        $this->db->where('su.StudentNumber', $studentNumber);
        return $this->db->get()->row();
    }

    private function getFeeTemplates()
    {
        if (!$this->tableExists('fees')) {
            return [];
        }
        $this->db->select('feesid, feesType, Description, Amount');
        $this->db->from('fees');
        $this->db->order_by('Description', 'ASC');
        return $this->db->get()->result();
    }

    private function getRecentPayments($date = null, $limit = 200)
    {
        $this->db->select("p.ID, p.PDate, p.pTime, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier, p.Sem, p.SY,
			f.FullAmount,
			COALESCE(NULLIF(TRIM(sp.email),''), NULLIF(TRIM(su.email),'')) AS Email,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
        $this->db->from('paymentsaccounts p');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
        $this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
        $this->db->join('(SELECT Description, MAX(Amount) AS FullAmount FROM fees GROUP BY Description) f', 'f.Description = p.description', 'left');
        $this->db->where('p.CollectionSource', "Student's Account");
        $this->db->where('p.ORStatus', 'Valid');
        if ($date !== null && $date !== '') {
            $this->db->where('p.PDate', $date);
        }
        $this->db->order_by('p.PDate', 'DESC');
        $this->db->order_by('p.pTime', 'DESC');
        $this->db->order_by('p.ID', 'DESC');
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    private function distinctPaymentDates($limit = 60)
    {
        return $this->db->distinct()
            ->select('PDate')
            ->from('paymentsaccounts')
            ->where('CollectionSource', "Student's Account")
            ->where('ORStatus', 'Valid')
            ->order_by('PDate', 'DESC')
            ->limit((int)$limit)
            ->get()
            ->result();
    }

    private function ensureStudeAccount($studentNumber, $sem, $sy)
    {
        $exists = $this->db->select('StudentNumber')
            ->from('studeaccount')
            ->where('StudentNumber', $studentNumber)
            ->where('Sem', $sem)
            ->where('SY', $sy)
            ->limit(1)
            ->get()
            ->row();

        if ($exists) {
            return;
        }

        $enrol = $this->db->select('Course, Major, YearLevel, Section')
            ->from('semesterstude')
            ->where('StudentNumber', $studentNumber)
            ->where('Semester', $sem)
            ->where('SY', $sy)
            ->limit(1)
            ->get()
            ->row();

        if (!$enrol) {
            $enrol = $this->db->select('Course, Major, YearLevel, Section')
                ->from('semesterstude')
                ->where('StudentNumber', $studentNumber)
                ->order_by('semstudentid', 'DESC')
                ->limit(1)
                ->get()
                ->row();
        }

        $settingsId = (int)($this->db->select('settingsID')
            ->from('o_srms_settings')->limit(1)->get()->row()->settingsID ?? 0);

        $this->db->insert('studeaccount', [
            'StudentNumber' => $studentNumber,
            'Course'        => (string)($enrol->Course ?? ''),
            'Major'         => (string)($enrol->Major ?? ''),
            'YearLevel'     => (string)($enrol->YearLevel ?? ''),
            'Section'       => (string)($enrol->Section ?? ''),
            'FeesDesc'      => '',
            'feesType'      => 'Shell',
            'TotalPayments' => 0,
            'Sem'           => $sem,
            'SY'            => $sy,
            'settingsID'    => $settingsId,
        ]);
    }

    private function recomputeStudeAccount($studentNumber, $sem, $sy)
    {
        $studentNumber = trim((string)$studentNumber);
        $sem = trim((string)$sem);
        $sy  = trim((string)$sy);
        if ($studentNumber === '' || $sem === '' || $sy === '') {
            return;
        }

        $this->ensureStudeAccount($studentNumber, $sem, $sy);

        $sumRow = $this->db->select('COALESCE(SUM(Amount),0) AS total', false)
            ->from('paymentsaccounts')
            ->where('StudentNumber', $studentNumber)
            ->where('Sem', $sem)
            ->where('SY', $sy)
            ->where('ORStatus', 'Valid')
            ->where('CollectionSource', "Student's Account")
            ->get()
            ->row();

        $newTotal = (float)($sumRow->total ?? 0);
        $newTotalSql = $this->db->escape($newTotal);

        $this->db->set('TotalPayments', $newTotalSql, false);
        $this->db->set('CurrentBalance', "GREATEST(COALESCE(AcctTotal,0) - COALESCE(Discount,0) - {$newTotalSql}, 0)", false);
        $this->db->where('StudentNumber', $studentNumber)
            ->where('Sem', $sem)
            ->where('SY', $sy)
            ->update('studeaccount');
    }

    private function getReceiptSettings()
    {
        return $this->db->select('SchoolName, SchoolAddress, telNo, cashier, cashierPosition, letterhead_web')
            ->from('o_srms_settings')
            ->limit(1)
            ->get()
            ->row();
    }

    private function sendReceiptEmailForPayment($payment, $settings = null)
    {
        $recipientEmail = trim((string)($payment->Email ?? ''));
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['attempted' => false, 'sent' => false, 'email' => ''];
        }

        if ($settings === null) {
            $settings = $this->getReceiptSettings();
        }

        $schoolName = trim((string)($settings->SchoolName ?? 'School Records Management System'));
        $subject = 'Official Receipt #' . trim((string)($payment->ORNumber ?? '')) . ' - ' . $schoolName;
        $message = $this->load->view('accounting_receipt_email', [
            'payment'  => $payment,
            'settings' => $settings,
        ], true);

        if (!fbmso_mailqueue_push($this, $recipientEmail, $subject, $message, $schoolName)) {
            log_message('error', 'Could not queue receipt email for payment ID ' . (int)($payment->ID ?? 0) . ' <' . $recipientEmail . '>');
            return ['attempted' => true, 'sent' => false, 'email' => $recipientEmail];
        }

        return ['attempted' => true, 'sent' => true, 'email' => $recipientEmail];
    }

    private function collectionRows($from, $to, $sem = '', $sy = '')
    {
        $this->db->select("p.ID, p.PDate, p.ORNumber, p.StudentNumber, p.Amount, p.description, p.PaymentType, p.Cashier,
			p.CollectionSource, p.Sem, p.SY,
			CONCAT(
				COALESCE(NULLIF(sp.LastName,''), su.LastName, ''),
				', ',
				COALESCE(NULLIF(sp.FirstName,''), su.FirstName, ''),
				' ',
				COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '')
			) AS StudentName", false);
        $this->db->from('paymentsaccounts p');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
        $this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
        $this->db->where('p.ORStatus', 'Valid');
        $this->db->where('p.PDate >=', $from);
        $this->db->where('p.PDate <=', $to);
        if ($sem !== '') {
            $this->db->where('p.Sem', $sem);
        }
        if ($sy !== '') {
            $this->db->where('p.SY', $sy);
        }
        $this->db->order_by('p.PDate', 'DESC');
        $this->db->order_by('p.ID', 'DESC');
        return $this->db->get()->result();
    }

    private function ledgerRows($from, $to)
    {
        $income = $this->db->select("PDate AS EntryDate, pTime AS EntryTime, description AS EntryDesc, ORNumber AS EntryRef, Amount AS EntryAmount", false)
            ->from('paymentsaccounts')
            ->where('CollectionSource', "Student's Account")
            ->where('ORStatus', 'Valid')
            ->where('PDate >=', $from)
            ->where('PDate <=', $to)
            ->get()
            ->result();

        $expenses = $this->db->select("ExpenseDate AS EntryDate, Description AS EntryDesc, Category AS EntryRef, Amount AS EntryAmount", false)
            ->from('expenses')
            ->where('ExpenseDate >=', $from)
            ->where('ExpenseDate <=', $to)
            ->get()
            ->result();

        $rows = [];
        foreach ($income as $r) {
            $rows[] = [
                'date'        => (string)$r->EntryDate,
                'time'        => trim((string)($r->EntryTime ?? '')) !== '' ? (string)$r->EntryTime : '00:00:00',
                'type'        => 'income',
                'description' => (string)$r->EntryDesc,
                'ref'         => (string)$r->EntryRef,
                'amount'      => (float)$r->EntryAmount,
            ];
        }
        foreach ($expenses as $r) {
            $rows[] = [
                'date'        => (string)$r->EntryDate,
                'time'        => '00:00:00',
                'type'        => 'expense',
                'description' => (string)$r->EntryDesc,
                'ref'         => (string)$r->EntryRef,
                'amount'      => (float)$r->EntryAmount,
            ];
        }

        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['date'], $b['date']);
            return $cmp !== 0 ? $cmp : strcmp($a['time'], $b['time']);
        });

        $balance = 0.0;
        foreach ($rows as &$row) {
            $balance += $row['type'] === 'income' ? $row['amount'] : -$row['amount'];
            $row['balance'] = $balance;
        }
        unset($row);

        return array_reverse($rows);
    }

    private function partialPaymentRows($sem, $sy)
    {
        if (!$this->tableExists('fees')) {
            return [];
        }

        $this->db->select("p.StudentNumber, p.description AS Description,
			f.FullAmount, SUM(p.Amount) AS PaidAmount, MAX(p.PDate) AS LastPaymentDate,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName,
			COALESCE(NULLIF(sp.MiddleName,''), su.MiddleName, '') AS MiddleName", false);
        $this->db->from('paymentsaccounts p');
        $this->db->join('(SELECT Description, MAX(Amount) AS FullAmount FROM fees GROUP BY Description) f', 'f.Description = p.description', 'inner');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = p.StudentNumber', 'left');
        $this->db->join('studentsignup su', 'su.StudentNumber = p.StudentNumber', 'left');
        $this->db->where('p.ORStatus', 'Valid');
        $this->db->where('p.CollectionSource', "Student's Account");
        if ($sem !== '') {
            $this->db->where('p.Sem', $sem);
        }
        if ($sy !== '') {
            $this->db->where('p.SY', $sy);
        }
        $this->db->group_by('p.StudentNumber, p.description, f.FullAmount');
        $this->db->having('SUM(p.Amount) < f.FullAmount', null, false);
        $this->db->order_by('LastName', 'ASC');
        $this->db->order_by('FirstName', 'ASC');
        $this->db->order_by('p.description', 'ASC');

        return $this->db->get()->result();
    }

    // ─── Dashboard (Page::accounting) ──────────────────────────────────────

    /**
     * Cashier dashboard — same numbers Page/accounting renders on the web:
     * accounts-with-balance count, today/month/year collections, 14-day
     * collection trend, and the 8 most recent payments.
     */
    public function dashboard()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        [$sem, $sy] = $this->currentSemSy();

        // The StudentModel methods return result() rows ([{Amount: …}]) for the
        // web views; unwrap them into scalars so mobile clients get numbers.
        $scalar = function ($rows, $key) {
            $row = is_array($rows) && isset($rows[0]) ? $rows[0] : null;
            $val = is_object($row) ? ($row->$key ?? 0) : (is_array($row) ? ($row[$key] ?? 0) : 0);
            return (float)$val;
        };

        $trend = [];
        foreach ($this->StudentModel->collectionTrend(14) as $t) {
            $trend[] = [
                'date'  => (string)($t->CDate ?? ''),
                'total' => (float)($t->Amount ?? 0),
            ];
        }

        $recent = [];
        foreach ($this->StudentModel->recentPayments(8) as $r) {
            $recent[] = [
                'id'             => (int)($r->ID ?? 0),
                'date'           => (string)($r->PDate ?? ''),
                'or_number'      => (string)($r->ORNumber ?? ''),
                'student_number' => (string)($r->StudentNumber ?? ''),
                'student_name'   => trim((string)($r->LastName ?? '') . ', ' . (string)($r->FirstName ?? '')),
                'amount'         => (float)($r->Amount ?? 0),
                'description'    => (string)($r->description ?? ''),
                'cashier'        => (string)($r->Cashier ?? ''),
            ];
        }

        return $this->json([
            'ok'                => true,
            'sem'               => $sem,
            'sy'                => $sy,
            'accounts_balance'  => (int)$scalar($this->StudentModel->totalStudeAccountProfile($sy, $sem), 'StudeCount'),
            'collection_today'  => $scalar($this->StudentModel->collectionToday(), 'Amount'),
            'collection_month'  => $scalar($this->StudentModel->collectionMonth(), 'Amount'),
            'collection_year'   => $scalar($this->StudentModel->YearlyCollections(), 'Amount'),
            'trend'             => $trend,
            'recent_payments'   => $recent,
        ]);
    }

    // ─── Payment Entry (Accounting::Payment) ───────────────────────────────

    /**
     * Everything the payment form needs in one call: payable students, fee
     * templates, today's recent payments, dates that have payments, and the
     * next O.R. number for today.
     */
    public function payment_context()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        [$sem, $sy] = $this->currentSemSy();
        $today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');

        $students = [];
        foreach ($this->getStudentsForPayment($sem, $sy) as $s) {
            $students[] = [
                'student_number' => (string)$s->StudentNumber,
                'first_name'     => (string)$s->FirstName,
                'middle_name'    => (string)$s->MiddleName,
                'last_name'      => (string)$s->LastName,
                'course'         => (string)($s->Course ?? ''),
                'major'          => (string)($s->Major ?? ''),
                'year_level'     => (string)($s->YearLevel ?? ''),
                'semester'       => (string)($s->Semester ?? ''),
                'sy'             => (string)($s->SY ?? ''),
            ];
        }

        $fees = [];
        foreach ($this->getFeeTemplates() as $f) {
            $fees[] = [
                'id'          => (int)$f->feesid,
                'type'        => (string)($f->feesType ?? ''),
                'description' => (string)$f->Description,
                'amount'      => (float)$f->Amount,
            ];
        }

        $dates = [];
        foreach ($this->distinctPaymentDates() as $d) {
            $dates[] = (string)$d->PDate;
        }

        return $this->json([
            'ok'                => true,
            'today'             => $today,
            'sem'               => $sem,
            'sy'                => $sy,
            'next_or_number'    => $this->generateNextOrNumber($today),
            'students'          => $students,
            'fees'              => $fees,
            'payment_dates'     => $dates,
            'recent_payments'   => $this->shapePayments($this->getRecentPayments($today)),
        ]);
    }

    /** Recent valid payments for a date (or all when date=all). */
    public function payments()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $date = trim((string)$this->input->get('date', true));
        $filter = ($date === 'all' || !$this->isValidDate($date)) ? null : $date;

        return $this->json([
            'ok'       => true,
            'payments' => $this->shapePayments($this->getRecentPayments($filter)),
        ]);
    }

    private function shapePayments($rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $name = trim((string)($r->LastName ?? '') . ', ' . (string)($r->FirstName ?? '') . ' ' . (string)($r->MiddleName ?? ''));
            if (trim($name, ', ') === '') {
                $name = (string)$r->StudentNumber;
            }
            $out[] = [
                'id'             => (int)$r->ID,
                'date'           => (string)$r->PDate,
                'time'           => (string)($r->pTime ?? ''),
                'or_number'      => (string)$r->ORNumber,
                'student_number' => (string)$r->StudentNumber,
                'student_name'   => $name,
                'amount'         => (float)$r->Amount,
                'full_amount'    => isset($r->FullAmount) ? (float)$r->FullAmount : null,
                'description'    => (string)$r->description,
                'payment_type'   => (string)($r->PaymentType ?? ''),
                'cashier'        => (string)($r->Cashier ?? ''),
                'sem'            => (string)($r->Sem ?? ''),
                'sy'             => (string)($r->SY ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Record a payment — mirrors Accounting::Payment POST: server-generated
     * date-scoped O.R., paymentsaccounts insert inside a transaction,
     * studeaccount recompute, queued receipt email. X-Idempotency-Key plays
     * the role the web's payment_submit_token plays (no double submit).
     */
    public function payment_create()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_accounting();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $p = $this->read_payload();

        $studentNumber = trim((string)($p['StudentNumber'] ?? $p['student_number'] ?? ''));
        $description   = trim((string)($p['description'] ?? ''));
        $amount        = (float)($p['Amount'] ?? $p['amount'] ?? 0);
        $pDateInput    = trim((string)($p['PDate'] ?? $p['date'] ?? ''));
        $paymentType   = trim((string)($p['PaymentType'] ?? $p['payment_type'] ?? ''));
        $checkNumber   = trim((string)($p['CheckNumber'] ?? $p['check_number'] ?? ''));
        $bank          = trim((string)($p['Bank'] ?? $p['bank'] ?? ''));
        $refNo         = trim((string)($p['refNo'] ?? $p['ref_no'] ?? ''));

        $errors = [];
        if ($studentNumber === '') $errors[] = 'Student is required.';
        if ($description === '') $errors[] = 'Description is required.';
        if (!is_numeric($p['Amount'] ?? $p['amount'] ?? null) || $amount <= 0) $errors[] = 'Amount must be greater than 0.';
        if (!$this->isValidDate($pDateInput)) $errors[] = 'Invalid payment date.';
        if ($errors) {
            $body = json_encode(['ok' => false, 'message' => implode(' ', $errors)]);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        if ($paymentType === '') {
            $paymentType = 'Cash';
        }
        if (strcasecmp($paymentType, 'Check') !== 0) {
            $checkNumber = '';
            $bank = '';
        }

        [$sem, $sy] = $this->currentSemSy();

        $student = $this->getStudentContext($studentNumber, $sem, $sy);
        $course = trim((string)($student->Course ?? ''));
        if ($course === '') {
            $course = trim((string)($p['Course'] ?? ''));
        }

        $cashier = (string)$tokenRow['username'];
        $dtNow = new DateTime('now', new DateTimeZone('Asia/Manila'));

        $orNumber = '';
        $insertOk = false;
        $paymentData = [];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $orNumber = $this->generateNextOrNumber($pDateInput);
            $paymentData = [
                'ID'               => $this->nextTableId('paymentsaccounts', 'ID'),
                'StudentNumber'    => $studentNumber,
                'Course'           => $course,
                'PDate'            => $pDateInput,
                'ORNumber'         => $orNumber,
                'Amount'           => $amount,
                'description'      => $description,
                'PaymentType'      => $paymentType,
                'CheckNumber'      => $checkNumber,
                'Sem'              => $sem,
                'SY'               => $sy,
                'CollectionSource' => "Student's Account",
                'Bank'             => $bank,
                'ORStatus'         => 'Valid',
                'Cashier'          => $cashier,
                'pTime'            => $dtNow->format('H:i:s'),
                'refNo'            => $refNo,
            ];

            $this->db->trans_begin();
            $insertOk = $this->db->insert('paymentsaccounts', $paymentData);
            $insertError = $this->db->error();

            if ($insertOk && $sem !== '' && $sy !== '') {
                $this->recomputeStudeAccount($studentNumber, $sem, $sy);
            }

            if ($insertOk && $this->db->trans_status() !== false) {
                $this->db->trans_commit();
                break;
            }

            $this->db->trans_rollback();
            $insertOk = false;

            if (!$this->isDuplicateDbError($insertError)) {
                break;
            }
        }

        if (!$insertOk) {
            $body = json_encode(['ok' => false, 'message' => 'Unable to save payment. Please try again.']);
            $this->record_idempotent_response(500, $body);
            return $this->json(json_decode($body, true), 500);
        }

        // Queue the receipt email exactly like the web flow — non-fatal.
        $emailResult = ['attempted' => false, 'sent' => false];
        try {
            $studentData = is_object($student) ? get_object_vars($student) : (array)$student;
            $receiptPayment = (object)array_merge($paymentData, [
                'Email'      => trim((string)($studentData['Email'] ?? '')),
                'FirstName'  => trim((string)($studentData['FirstName'] ?? '')),
                'MiddleName' => trim((string)($studentData['MiddleName'] ?? '')),
                'LastName'   => trim((string)($studentData['LastName'] ?? '')),
            ]);
            $emailResult = $this->sendReceiptEmailForPayment($receiptPayment, $this->getReceiptSettings());
        } catch (Throwable $e) {
            log_message('error', 'Receipt email failed for OR ' . $orNumber . ': ' . $e->getMessage());
        }

        $result = [
            'ok'             => true,
            'message'        => 'Payment saved successfully.',
            'or_number'      => $orNumber,
            'payment_id'     => (int)$paymentData['ID'],
            'email_sent'     => !empty($emailResult['sent']),
            'student_name'   => trim((string)(($studentData['LastName'] ?? '') . ', ' . ($studentData['FirstName'] ?? ''))),
        ];
        $body = json_encode($result);
        $this->record_idempotent_response(200, $body);
        return $this->json($result);
    }

    /**
     * Delete (void) a payment — mirrors Accounting::deletePayment: only VALID
     * "Student's Account" payments, recompute the ledger, write the audit row.
     */
    public function payment_delete()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_accounting();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $p = $this->read_payload();
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) {
            $body = json_encode(['ok' => false, 'message' => 'Invalid payment ID.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $payment = $this->db->select('ID, StudentNumber, ORNumber, PDate, Amount, description, Sem, SY, ORStatus, CollectionSource')
            ->from('paymentsaccounts')
            ->where('ID', $id)
            ->limit(1)
            ->get()
            ->row();

        if (!$payment) {
            $body = json_encode(['ok' => false, 'message' => 'Payment not found.']);
            $this->record_idempotent_response(404, $body);
            return $this->json(json_decode($body, true), 404);
        }
        if ((string)$payment->ORStatus !== 'Valid') {
            $body = json_encode(['ok' => false, 'message' => 'Only VALID payments can be deleted.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }
        if ((string)$payment->CollectionSource !== "Student's Account") {
            $body = json_encode(['ok' => false, 'message' => "This payment is not under Student's Account."]);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $studentNumber = trim((string)$payment->StudentNumber);
        $sem = trim((string)$payment->Sem);
        $sy  = trim((string)$payment->SY);

        $this->db->trans_start();
        $this->db->where('ID', (int)$id)->delete('paymentsaccounts');
        $this->recomputeStudeAccount($studentNumber, $sem, $sy);
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $body = json_encode(['ok' => false, 'message' => 'Unable to delete payment. Please try again.']);
            $this->record_idempotent_response(500, $body);
            return $this->json(json_decode($body, true), 500);
        }

        $this->logPaymentAudit('delete', $payment, (string)$tokenRow['username']);

        $body = json_encode(['ok' => true, 'message' => 'Payment deleted successfully.']);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true));
    }

    // ─── Payment Activity Log (Accounting::paymentAuditLog) ────────────────

    public function payment_audit_log()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $this->db->select("l.*,
			COALESCE(NULLIF(sp.LastName,''), su.LastName, '') AS LastName,
			COALESCE(NULLIF(sp.FirstName,''), su.FirstName, '') AS FirstName", false);
        $this->db->from('payment_audit_log l');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = l.student_number', 'left');
        $this->db->join('studentsignup su', 'su.StudentNumber = l.student_number', 'left');
        $this->db->order_by('l.changed_at', 'DESC');
        $this->db->limit(300);
        $rows = $this->db->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $name = trim((string)($r->LastName ?? ''));
            if ($name !== '') $name .= ', ';
            $name .= trim((string)($r->FirstName ?? ''));
            if (trim($name) === '') $name = (string)$r->student_number;

            $out[] = [
                'id'             => (int)($r->id ?? 0),
                'changed_at'     => (string)$r->changed_at,
                'action'         => (string)$r->action,
                'or_number'      => (string)$r->or_number,
                'student_number' => (string)$r->student_number,
                'student_name'   => $name,
                'description'    => (string)$r->description,
                'amount'         => (float)$r->amount,
                'changed_by'     => (string)$r->changed_by,
            ];
        }

        return $this->json(['ok' => true, 'entries' => $out]);
    }

    // ─── Partial Payments (Accounting::partialPayments) ────────────────────

    public function partial_payments()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        [$sem, $sy] = $this->currentSemSy();
        $rows = $this->partialPaymentRows($sem, $sy);

        $out = [];
        $totalOutstanding = 0.0;
        $students = [];
        foreach ($rows as $row) {
            $full = (float)$row->FullAmount;
            $paid = (float)$row->PaidAmount;
            $outstanding = $full - $paid;
            $totalOutstanding += $outstanding;
            $students[(string)$row->StudentNumber] = true;

            $name = trim((string)($row->LastName ?? ''));
            if ($name !== '') $name .= ', ';
            $name .= trim((string)(($row->FirstName ?? '') . ' ' . ($row->MiddleName ?? '')));
            if (trim($name) === '') $name = (string)$row->StudentNumber;

            $out[] = [
                'student_number'    => (string)$row->StudentNumber,
                'student_name'      => $name,
                'description'       => (string)$row->Description,
                'full_amount'       => $full,
                'paid_amount'       => $paid,
                'outstanding'       => $outstanding,
                'last_payment_date' => (string)($row->LastPaymentDate ?? ''),
            ];
        }

        return $this->json([
            'ok'                => true,
            'sem'               => $sem,
            'sy'                => $sy,
            'rows'              => $out,
            'total_outstanding' => $totalOutstanding,
            'student_count'     => count($students),
        ]);
    }

    // ─── Collection Report (Accounting::collectionReport) ──────────────────

    /** ?from=YYYY-MM-DD&to=YYYY-MM-DD&term=Sem|SY — same defaults as web. */
    public function collection_report()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $from = trim((string)$this->input->get('from', true));
        $to   = trim((string)$this->input->get('to', true));
        if (!$this->isValidDate($from)) {
            $from = date('Y-m-01');
        }
        if (!$this->isValidDate($to)) {
            $to = date('Y-m-d');
        }

        $sem = '';
        $sy  = '';
        $term = trim((string)$this->input->get('term', true));
        if ($term !== '' && strpos($term, '|') !== false) {
            [$sem, $sy] = array_map('trim', explode('|', $term, 2));
        }

        $rows = $this->collectionRows($from, $to, $sem, $sy);
        $total = 0.0;
        $out = [];
        foreach ($rows as $r) {
            $total += (float)$r->Amount;
            $studentName = trim((string)($r->StudentName ?? ''));
            if (trim($studentName, ', ') === '') {
                $studentName = (string)$r->StudentNumber;
            }
            $out[] = [
                'id'             => (int)$r->ID,
                'date'           => (string)$r->PDate,
                'or_number'      => (string)$r->ORNumber,
                'student_number' => (string)$r->StudentNumber,
                'student_name'   => $studentName,
                'description'    => (string)$r->description,
                'payment_type'   => (string)$r->PaymentType,
                'sem'            => (string)$r->Sem,
                'sy'             => (string)$r->SY,
                'amount'         => (float)$r->Amount,
                'cashier'        => (string)$r->Cashier,
            ];
        }

        return $this->json([
            'ok'          => true,
            'from'        => $from,
            'to'          => $to,
            'sem'         => $sem,
            'sy'          => $sy,
            'rows'        => $out,
            'total'       => $total,
            'count'       => count($out),
        ]);
    }

    /** Term options for the collection-report filter (web: term->terms()). */
    public function terms()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $terms = method_exists($this->term, 'terms') ? $this->term->terms() : [];
        $out = [];
        foreach ((array)$terms as $t) {
            $sem = trim((string)(is_object($t) ? ($t->Semester ?? $t->sem ?? '') : ($t['Semester'] ?? $t['sem'] ?? '')));
            $sy  = trim((string)(is_object($t) ? ($t->SY ?? $t->sy ?? '') : ($t['SY'] ?? $t['sy'] ?? '')));
            if ($sem !== '' || $sy !== '') {
                $out[] = ['sem' => $sem, 'sy' => $sy, 'label' => trim($sem . ' ' . $sy)];
            }
        }
        if (empty($out)) {
            // Fallback: distinct terms seen on payments.
            $rows = $this->db->select('Sem, SY')->distinct()
                ->from('paymentsaccounts')
                ->where("Sem <> ''", null, false)
                ->order_by('SY', 'DESC')->order_by('Sem', 'ASC')
                ->get()->result();
            foreach ($rows as $r) {
                $out[] = ['sem' => (string)$r->Sem, 'sy' => (string)$r->SY, 'label' => trim($r->Sem . ' ' . $r->SY)];
            }
        }

        return $this->json(['ok' => true, 'terms' => $out]);
    }

    // ─── Ledger (Accounting::ledger) ───────────────────────────────────────

    public function ledger()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $from = trim((string)$this->input->get('from', true));
        $to   = trim((string)$this->input->get('to', true));
        if (!$this->isValidDate($from)) {
            $from = date('Y-m-01');
        }
        if (!$this->isValidDate($to)) {
            $to = date('Y-m-d');
        }

        $rows = $this->ledgerRows($from, $to);
        $gross = 0.0;
        $spent = 0.0;
        foreach ($rows as $row) {
            if ($row['type'] === 'income') {
                $gross += $row['amount'];
            } else {
                $spent += $row['amount'];
            }
        }

        return $this->json([
            'ok'    => true,
            'from'  => $from,
            'to'    => $to,
            'rows'  => $rows,
            'gross' => $gross,
            'spent' => $spent,
            'net'   => $gross - $spent,
        ]);
    }

    // ─── Expenses Report (Accounting::expenseSGenerate) ────────────────────

    /** ?category=&from=&to= — filtered expense list + total. */
    public function expenses_report()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $category = trim((string)$this->input->get('category', true));
        $fromDate = trim((string)$this->input->get('from', true));
        $toDate   = trim((string)$this->input->get('to', true));

        $this->db->select('*')->from('expenses');
        if ($category !== '') {
            $this->db->where('Category', $category);
        }
        if ($this->isValidDate($fromDate) && $this->isValidDate($toDate)) {
            $this->db->where('ExpenseDate >=', $fromDate);
            $this->db->where('ExpenseDate <=', $toDate);
        }
        $this->db->order_by('ExpenseDate', 'DESC');
        $rows = $this->db->get()->result();

        $out = [];
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float)$r->Amount;
            $out[] = [
                'id'          => (int)($r->expensesid ?? $r->id ?? 0),
                'description' => (string)$r->Description,
                'responsible' => (string)($r->Responsible ?? ''),
                'date'        => (string)$r->ExpenseDate,
                'category'    => (string)$r->Category,
                'amount'      => (float)$r->Amount,
            ];
        }

        return $this->json([
            'ok'       => true,
            'rows'     => $out,
            'total'    => $total,
            'count'    => count($out),
            'category' => $category,
            'from'     => $fromDate,
            'to'       => $toDate,
        ]);
    }

    // ─── Fees Setup (Accounting::course_setUp) ─────────────────────────────

    public function fees()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;

        $out = [];
        foreach ($this->getFeeTemplates() as $f) {
            $out[] = [
                'id'          => (int)$f->feesid,
                'type'        => (string)($f->feesType ?? ''),
                'description' => (string)$f->Description,
                'amount'      => (float)$f->Amount,
            ];
        }
        return $this->json(['ok' => true, 'fees' => $out]);
    }

    public function fees_create()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;
        if ($this->replay_if_duplicate()) return;

        $p = $this->read_payload();
        $description = trim((string)($p['description'] ?? ''));
        $amount      = (float)($p['amount'] ?? -1);
        $feesType    = trim((string)($p['fees_type'] ?? ''));

        if ($description === '' || $amount < 0 || !is_numeric($p['amount'] ?? null)) {
            $body = json_encode(['ok' => false, 'message' => 'Description and a non-negative amount are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }
        if ($feesType === '') {
            $feesType = 'School Fee';
        }

        $this->db->insert('fees', [
            'feesid'      => $this->nextTableId('fees', 'feesid'),
            'feesType'    => $feesType,
            'Description' => $description,
            'Amount'      => $amount,
        ]);

        $body = json_encode(['ok' => true, 'message' => 'Fee added successfully.']);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true));
    }

    public function fees_update()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;
        if ($this->replay_if_duplicate()) return;

        $p = $this->read_payload();
        $feeId       = (int)($p['id'] ?? 0);
        $description = trim((string)($p['description'] ?? ''));
        $amount      = (float)($p['amount'] ?? -1);
        $feesType    = trim((string)($p['fees_type'] ?? ''));

        if ($feeId <= 0 || $description === '' || !is_numeric($p['amount'] ?? null) || $amount < 0) {
            $body = json_encode(['ok' => false, 'message' => 'Fee ID, description and a non-negative amount are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $updateData = ['Description' => $description, 'Amount' => $amount];
        // Match the web edit form: only touch feesType when one is posted.
        if ($feesType !== '') {
            $updateData['feesType'] = $feesType;
        }

        $ok = $this->db->where('feesid', $feeId)->update('fees', $updateData);
        $result = $ok
            ? ['ok' => true, 'message' => 'Fee updated successfully.']
            : ['ok' => false, 'message' => 'Unable to update fee. Please try again.'];
        $body = json_encode($result);
        $this->record_idempotent_response($ok ? 200 : 500, $body);
        return $this->json($result, $ok ? 200 : 500);
    }

    public function fees_delete()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        if ($this->require_accounting() === null) return;
        if ($this->replay_if_duplicate()) return;

        $p = $this->read_payload();
        $feeId = (int)($p['id'] ?? 0);
        if ($feeId <= 0) {
            $body = json_encode(['ok' => false, 'message' => 'Invalid fee record.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $this->db->where('feesid', $feeId)->delete('fees');
        $body = json_encode(['ok' => true, 'message' => 'Fee deleted successfully.']);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true));
    }
}

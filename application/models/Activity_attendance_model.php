<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Activity_attendance_model extends CI_Model
{
    /* ---- read AM/PM/Eve windows from activities.meta and classify current time ---- */
    private function get_activity_meta($activity_id)
    {
        return $this->db->select('meta')
                        ->from('activities')
                        ->where('activity_id', (int)$activity_id)
                        ->get()->row();
    }

    private function classify_session($activity_id, $now = null)
{
    // keep signature compatible with callers
    $now = $now ?: date('H:i'); // 'HH:MM' 24h

    // 1) Load activity meta
    $act = $this->get_activity_meta($activity_id); // <-- this exists in your model
    $defined = [];

    if ($act && !empty($act->meta)) {
        $meta = json_decode((string)$act->meta, true);
        if (is_array($meta) && !empty($meta['sessions']) && is_array($meta['sessions'])) {
            foreach ($meta['sessions'] as $key => $win) {
                // accept only sessions that actually exist in meta
                if (!isset($win['in']) && !isset($win['out'])) continue;
                $in  = isset($win['in'])  ? trim((string)$win['in'])  : null; // 'HH:MM' or null
                $out = isset($win['out']) ? trim((string)$win['out']) : null; // 'HH:MM' or null
                $defined[$key] = ['in' => $in ?: null, 'out' => $out ?: null];
            }
        }
    }

    // Fallback if nothing configured: only AM/PM (like before) — no default 'eve'
    if (!$defined) {
        $defined = [
            'am' => ['in' => '08:00', 'out' => '12:00'],
            'pm' => ['in' => '13:00', 'out' => '18:00'],
        ];
    }

    // helpers to compare times as minutes since midnight
    $toMin = function($t) {
        if (!$t) return null;
        $h = (int)substr($t,0,2);
        $m = (int)substr($t,3,2);
        return $h*60 + $m;
    };
    $nowMin = $toMin($now);

    // make a sortable list of the defined windows
    $slots = [];
    foreach ($defined as $key => $win) {
        $slots[] = [
            'key' => $key,
            'in'  => $toMin($win['in']),
            'out' => $toMin($win['out']),
        ];
    }

    // 2) If 'now' is inside any defined window, return that session
    foreach ($slots as $s) {
        $inOK  = is_null($s['in'])  ? true : ($nowMin >= $s['in']);
        $outOK = is_null($s['out']) ? true : ($nowMin <  $s['out']);
        if ($inOK && $outOK) return $s['key'];
    }

    // 3) Clamp to nearest defined session when outside all ranges
    usort($slots, function($a,$b){
        $ai = is_null($a['in']) ? -1 : $a['in'];
        $bi = is_null($b['in']) ? -1 : $b['in'];
        return $ai <=> $bi;
    });

    // before first 'in' -> first session
    foreach ($slots as $s) {
        if (!is_null($s['in']) && $nowMin < $s['in']) {
            return $s['key'];
        }
    }
    // after last -> last session
    return $slots ? $slots[count($slots)-1]['key'] : 'am';
}

/**
 * @param mixed $occurred_at Client scan time for offline queued scans; the
 *                           auto-close window is judged against it when plausible.
 */
public function consume_token($activity_id, $token, $direction = 'auto', $occurred_at = null)
{
    $activity_id = (int)$activity_id;

    // 0) Validate 32-hex token
    $token = trim((string)$token);
    if (!preg_match('/^[A-Fa-f0-9]{32}$/', $token)) {
        return ['ok' => false, 'mode' => 'err', 'message' => 'Invalid token format'];
    }

    // 1) Activity open? Manual status AND the auto-close time window must both pass.
    $act = $this->db->select('activity_id, status, is_open, start_at, end_at, activity_date, meta')
            ->from('activities')
            ->where('activity_id', $activity_id)->limit(1)->get()->row();
    if (!$act) return ['ok'=>false,'mode'=>'err','message'=>'Activity not found'];
    $state = activity_state($act, activity_resolve_scan_time($occurred_at));
    if (!$state['is_open']) {
        return [
            'ok'      => false,
            'mode'    => 'err',
            'message' => $state['reason'] ?: 'Activity is closed',
            'state'   => $state['state'],
        ];
    }

    // 2) QR must be active
    $qr = $this->db->select('student_number')
                   ->from('student_qr')
                   ->where('qr_token', $token)
                   ->where('status', 'active')
                   ->limit(1)->get()->row();
    if (!$qr) {
        return ['ok'=>false,'mode'=>'err','message'=>'Invalid or inactive student QR'];
    }

    $student_number   = (string)$qr->student_number;

    // 2.1) Block if owner account doesn’t exist or isn’t active
    if (!$this->student_exists_and_active_strict($student_number)) {
        return [
            'ok'=>false,'mode'=>'err',
            'message'=>'Account not found or inactive for this QR',
            'student' => $this->resolve_student_min($student_number)
        ];
    }

    $nowTs  = date('Y-m-d H:i:s');
    $today  = date('Y-m-d');
    $sess   = $this->classify_session($activity_id);

    // 3) Normalize direction
    $direction = strtolower((string)$direction);
    if (!in_array($direction, ['in','out','auto'], true)) $direction = 'auto';

    // 4) Serialize per activity+student+date
    $lockKey = sprintf('att:%d:%s:%s', $activity_id, $student_number, $today);
    $gotLock = $this->db->query("SELECT GET_LOCK(?, 3) AS ok", [$lockKey])->row();
    if (!$gotLock || (int)$gotLock->ok !== 1) {
        return ['ok' => false, 'mode' => 'err', 'message' => 'Please try again',
            'student' => $this->resolve_student_min($student_number)];
    }

    try {
        $this->db->trans_begin();

        // Open row?
        $openRow = $this->db->query("
            SELECT id, session, checked_in_at
            FROM activity_attendance
            WHERE activity_id = ? AND student_number = ? AND scan_date = ? AND checked_out_at IS NULL
            ORDER BY checked_in_at DESC
            LIMIT 1
            FOR UPDATE
        ", [$activity_id, $student_number, $today])->row();

        // Recent IN on this session (debounce)
        $recentIn = $this->db->query("
            SELECT id, checked_in_at
            FROM activity_attendance
            WHERE activity_id = ? AND student_number = ? AND scan_date = ? AND session = ?
            ORDER BY checked_in_at DESC
            LIMIT 1
            FOR UPDATE
        ", [$activity_id, $student_number, $today, $sess])->row();

        $tooSoon = false;
        if ($recentIn && !$openRow) {
            $last = strtotime((string)$recentIn->checked_in_at);
            if ($last && (time() - $last) <= 5) $tooSoon = true;
        }

        $student_payload = $this->resolve_student_min($student_number);

        /* FORCE IN */
        if ($direction === 'in') {
            if ($openRow) {
                $this->db->trans_rollback();
                return [
                    'ok'=>false,'mode'=>'already_in',
                    'message'=>'Already checked in. Please check out first.',
                    'student_number'=>$student_number,
                    'session'=>$openRow->session ?: $sess,
                    'student'=>$student_payload
                ];
            }
            if ($tooSoon) {
                $this->db->trans_rollback();
                return ['ok'=>true,'mode'=>'duplicate',
                    'student_number'=>$student_number,'session'=>$sess,
                    'student'=>$student_payload];
            }

            $ok = $this->db->insert('activity_attendance', [
                'activity_id'    => $activity_id,
                'student_number' => $student_number,
                'checked_in_at'  => $nowTs,
                'checked_out_at' => null,
                'scan_date'      => $today,
                'source'         => 'qr',
                'remarks'        => 'Scanned via QR',
                'session'        => $sess,
            ]);
            if (!$ok) {
                $err = $this->db->error();
                $this->db->trans_rollback();
                return ['ok'=>false,'mode'=>'err','message'=>'DB error (insert): '.$err['code'],
                    'student'=>$student_payload];
            }
            $newId = (int)$this->db->insert_id();
            $this->db->trans_commit();
            return ['ok'=>true,'mode'=>'checked_in','id'=>$newId,'student_number'=>$student_number,'session'=>$sess,
                'student'=>$student_payload];
        }

        /* FORCE OUT */
        if ($direction === 'out') {
            if (!$openRow) {
                $this->db->trans_rollback();
                return [
                    'ok'=>false,'mode'=>'no_open',
                    'message'=>'No open check-in to check out.',
                    'student_number'=>$student_number,'session'=>$sess,
                    'student'=>$student_payload
                ];
            }
            $this->db->where('id', (int)$openRow->id)
                     ->where('checked_out_at IS NULL', null, false)
                     ->set('checked_out_at', $nowTs)
                     ->update('activity_attendance');
            $this->db->trans_commit();
            return [
                'ok'=>true,'mode'=>'checked_out','id'=>(int)$openRow->id,
                'student_number'=>$student_number,'session'=>$openRow->session ?: $sess,
                'student'=>$student_payload
            ];
        }

        /* AUTO TOGGLE */
        if ($openRow) {
            // Anti-accidental-double-scan: if the check-in happened very
            // recently, don't immediately check them out.  This prevents
            // the camera reading the same QR twice in quick succession
            // from creating a bogus 2-second IN→OUT record.
            $AUTO_OUT_DEBOUNCE_SEC = 10; // configurable
            $checkedInTs = strtotime((string)$openRow->checked_in_at);
            if ($checkedInTs && (time() - $checkedInTs) <= $AUTO_OUT_DEBOUNCE_SEC) {
                $this->db->trans_rollback();
                $elapsed = time() - $checkedInTs;
                return [
                    'ok'             => false,
                    'mode'           => 'too_soon_after_in',
                    'message'        => 'Checked in ' . $elapsed . 's ago — scan again to check out.',
                    'student_number' => $student_number,
                    'session'        => $openRow->session ?: $sess,
                    'student'        => $student_payload,
                ];
            }

            $this->db->where('id', (int)$openRow->id)
                     ->where('checked_out_at IS NULL', null, false)
                     ->set('checked_out_at', $nowTs)
                     ->update('activity_attendance');
            $this->db->trans_commit();
            return [
                'ok'=>true,'mode'=>'checked_out','id'=>(int)$openRow->id,
                'student_number'=>$student_number,'session'=>$openRow->session ?: $sess,
                'student'=>$student_payload
            ];
        }

        // Duplicate (already completed this session)
        $doneThisSession = $this->db->query("
            SELECT id
            FROM activity_attendance
            WHERE activity_id = ? AND student_number = ? AND scan_date = ? AND session = ? AND checked_out_at IS NOT NULL
            LIMIT 1
            FOR UPDATE
        ", [$activity_id, $student_number, $today, $sess])->row();

        if ($doneThisSession || $tooSoon) {
            $this->db->trans_rollback();
            return ['ok'=>true,'mode'=>'duplicate','student_number'=>$student_number,'session'=>$sess,
                'student'=>$student_payload];
        }

        // New IN
        $ok = $this->db->insert('activity_attendance', [
            'activity_id'    => $activity_id,
            'student_number' => $student_number,
            'checked_in_at'  => $nowTs,
            'checked_out_at' => null,
            'scan_date'      => $today,
            'source'         => 'qr',
            'remarks'        => 'Scanned via QR',
            'session'        => $sess,
        ]);
        if (!$ok) {
            $err = $this->db->error();
            $this->db->trans_rollback();
            return ['ok'=>false,'mode'=>'err','message'=>'DB error (insert): '.$err['code'],
                'student'=>$student_payload];
        }

        $newId = (int)$this->db->insert_id();
        $this->db->trans_commit();
        return ['ok'=>true,'mode'=>'checked_in','id'=>$newId,'student_number'=>$student_number,'session'=>$sess,
            'student'=>$student_payload];

    } finally {
        $this->db->query("DO RELEASE_LOCK(?)", [$lockKey]);
    }
}

private function student_exists_and_active_strict(string $sn): bool
{
    $sn = trim($sn);
    if ($sn === '') return false;
    $sn_norm = preg_replace('/[\s-]+/', '', $sn);

    // 1) o_users first (username or IDNumber), and honor acctStat if present
    if ($this->db->table_exists('o_users')) {
        $fields = array_flip($this->db->list_fields('o_users'));
        $hasAcct = isset($fields['acctStat']);
        $acctWhere = $hasAcct ? " AND (acctStat IS NULL OR LOWER(acctStat) IN ('active','1','yes','enabled'))" : "";

        $q1 = $this->db->query("
            SELECT 1 FROM o_users
            WHERE (REPLACE(REPLACE(username,'-',''),' ','') = ? OR REPLACE(REPLACE(IDNumber,'-',''),' ','') = ?)
            $acctWhere
            LIMIT 1
        ", [$sn_norm, $sn_norm]);

        if ($q1 && $q1->num_rows() > 0) return true;
    }

    // 2) studentsignup (no strict status requirement here, just presence)
    if ($this->db->table_exists('studentsignup')) {
        $q2 = $this->db->query("
            SELECT 1 FROM studentsignup
            WHERE REPLACE(REPLACE(StudentNumber,'-',''),' ','') = ?
            LIMIT 1
        ", [$sn_norm]);
        if ($q2 && $q2->num_rows() > 0) return true;
    }

    // 3) studeprofile
    if ($this->db->table_exists('studeprofile')) {
        $q3 = $this->db->query("
            SELECT 1 FROM studeprofile
            WHERE REPLACE(REPLACE(StudentNumber,'-',''),' ','') = ?
            LIMIT 1
        ", [$sn_norm]);
        if ($q3 && $q3->num_rows() > 0) return true;
    }

    // 4) users (respect isActive if present)
    if ($this->db->table_exists('users')) {
        $uf = array_flip($this->db->list_fields('users'));
        $hasIsActive = isset($uf['isActive']);
        $activeWhere = $hasIsActive ? " AND (isActive IS NULL OR isActive=1)" : "";
        $q4 = $this->db->query("
            SELECT 1 FROM users
            WHERE REPLACE(REPLACE(username,'-',''),' ','') = ?
            $activeWhere
            LIMIT 1
        ", [$sn_norm]);
        if ($q4 && $q4->num_rows() > 0) return true;
    }

    return false;
}

// -----------------------------------------------
// Resolve minimal identity for display on scanner
// Returns: ['number'=>..., 'name'=>..., 'course'=>..., 'section'=>..., 'photo_url'=>...]
private function resolve_student_min(string $student_number): array
{
    $sn_norm = preg_replace('/[\s-]+/','', $student_number);
    $photo_url = null;

    // o_users (username or IDNumber)
    if ($this->db->table_exists('o_users')) {
        $ou = $this->db->select("username, IDNumber, fName, mName, lName, avatar")
            ->from('o_users')
            ->group_start()
              ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)
              ->or_where("REPLACE(REPLACE(IDNumber,'-',''),' ','') =", $sn_norm)
            ->group_end()
            ->limit(1)->get()->row();
        if ($ou) {
            if (!empty($ou->avatar)) $photo_url = base_url('upload/profile/'.$ou->avatar);
            $mid = trim((string)$ou->mName);
            $mi  = $mid === '' ? '' : (' '.strtoupper(substr($mid,0,1)).'.');
            return [
                'number'    => $student_number,
                'name'      => trim($ou->lName).', '.trim($ou->fName).$mi,
                'course'    => null,
                'section'   => null,
                'photo_url' => $photo_url,
            ];
        }
    }

    // studentsignup
    if ($this->db->table_exists('studentsignup')) {
        $fields = array_flip($this->db->list_fields('studentsignup'));
        $courseCols = ['Course1','Course2','Course3','Course'];
        $majorCols  = ['Major1','Major2','Major3','Major'];
        $courseCol = ''; foreach ($courseCols as $c) if (isset($fields[$c])) { $courseCol = $c; break; }
        $majorCol  = ''; foreach ($majorCols  as $m) if (isset($fields[$m])) { $majorCol  = $m; break; }
        $selCourse = $courseCol ?: "''";
        $selMajor  = $majorCol  ?: "''";

        $ssu = $this->db->select("
                StudentNumber,
                TRIM(FirstName)  AS FirstName,
                TRIM(MiddleName) AS MiddleName,
                TRIM(LastName)   AS LastName,
                {$selCourse} AS course,
                {$selMajor}  AS major,
                section
            ", false)
            ->from('studentsignup')
            ->where("REPLACE(REPLACE(StudentNumber,'-',''),' ','') =", $sn_norm)
            ->limit(1)->get()->row();
        if ($ssu) {
            $mid = trim((string)$ssu->MiddleName);
            $mi  = $mid === '' ? '' : (' '.strtoupper(substr($mid,0,1)).'.');
            return [
                'number'    => $student_number,
                'name'      => trim($ssu->LastName).', '.trim($ssu->FirstName).$mi,
                'course'    => (string)$ssu->course ?: null,
                'section'   => isset($ssu->section) ? (string)$ssu->section : null,
                'photo_url' => $photo_url,
            ];
        }
    }

    // studeprofile
    if ($this->db->table_exists('studeprofile')) {
        $sp = $this->db->select('FirstName, MiddleName, LastName, Course')
            ->from('studeprofile')
            ->where("REPLACE(REPLACE(StudentNumber,'-',''),' ','') =", $sn_norm)
            ->limit(1)->get()->row();
        if ($sp) {
            $mid = trim((string)$sp->MiddleName);
            $mi  = $mid === '' ? '' : (' '.strtoupper(substr($mid,0,1)).'.');
            return [
                'number'    => $student_number,
                'name'      => trim($sp->LastName).', '.trim($sp->FirstName).$mi,
                'course'    => (string)$sp->Course ?: null,
                'section'   => null,
                'photo_url' => $photo_url,
            ];
        }
    }

    // users
    if ($this->db->table_exists('users')) {
        $u = $this->db->select('fName, mName, lName, avatar')
            ->from('users')
            ->where("REPLACE(REPLACE(username,'-',''),' ','') =", $sn_norm)
            ->limit(1)->get()->row();
        if ($u) {
            if (!empty($u->avatar)) $photo_url = base_url('upload/profile/'.$u->avatar);
            $mid = trim((string)$u->mName);
            $mi  = $mid === '' ? '' : (' '.strtoupper(substr($mid,0,1)).'.');
            return [
                'number'    => $student_number,
                'name'      => trim($u->lName).', '.trim($u->fName).$mi,
                'course'    => null,
                'section'   => null,
                'photo_url' => $photo_url,
            ];
        }
    }

    // fallback — never empty
    return ['number'=>$student_number, 'name'=>$student_number, 'course'=>null, 'section'=>null, 'photo_url'=>null];
}


public function list_student_attendance($student_number, $limit=100, $offset=0)
{
    // Join activities to bring back the title and date shown on the student page
    return $this->db->select("
            aa.id,
            aa.activity_id,
            aa.checked_in_at,
            aa.checked_out_at,
            aa.source,
            aa.remarks,
            aa.session,
            a.title,
            DATE(a.start_at) AS activity_date
        ", false)
        ->from('activity_attendance aa')
        ->join('activities a', 'a.activity_id = aa.activity_id', 'left')
        ->where('aa.student_number', (string)$student_number)
        ->order_by('aa.checked_in_at','DESC')
        ->limit($limit,$offset)
        ->get()->result_array();
}
 public function report_by_activity_section($activity_id, $section = null, $date = null, $session = null, $yearLevel = null, $sy = null, $sem = null)
    {
        $this->db->select("
            aa.id,
            aa.activity_id,
            aa.student_number,
            COALESCE(
              CONCAT(TRIM(ssu.LastName), ', ', TRIM(ssu.FirstName),
                     IF(ssu.MiddleName IS NULL OR ssu.MiddleName='', '', CONCAT(' ', LEFT(TRIM(ssu.MiddleName),1), '.'))),
              CONCAT(TRIM(sp.LastName),  ', ', TRIM(sp.FirstName),
                     IF(sp.MiddleName IS NULL OR sp.MiddleName='',  '', CONCAT(' ', LEFT(TRIM(sp.MiddleName),1),  '.'))),
              CONCAT(TRIM(ou.lName),     ', ', TRIM(ou.fName),
                     IF(ou.mName       IS NULL OR ou.mName='',       '', CONCAT(' ', LEFT(TRIM(ou.mName),1),       '.'))),
              u.username
            ) AS student_name,
            COALESCE(sst.Course,
                     NULLIF(ssu.Course1,''),
                     NULLIF(ssu.Course2,''),
                     NULLIF(ssu.Course3,''),
                     sp.Course) AS course,
            COALESCE(sst.YearLevel, ssu.yearLevel, '') AS YearLevel,
            COALESCE(sst.Section,   ssu.section,   '') AS section,
            aa.session, aa.checked_in_at, aa.checked_out_at, aa.remarks, aa.checked_in_by
        ", false);

        $this->db->from('activity_attendance aa');
        $this->db->join('activities a', 'a.activity_id = aa.activity_id', 'left');
        $this->db->join('studeprofile sp', 'sp.StudentNumber = aa.student_number', 'left');
        $this->db->join('studentsignup ssu', 'ssu.StudentNumber = aa.student_number', 'left');

        if (!empty($sy) && !empty($sem)) {
            $this->db->join(
                'semesterstude sst',
                "sst.StudentNumber = aa.student_number
                 AND sst.SY = ".$this->db->escape($sy)."
                 AND sst.Semester = ".$this->db->escape($sem),
                'left',
                false
            );
        } else {
            $latestSst = "
                (SELECT s1.*
                 FROM semesterstude s1
                 JOIN (
                     SELECT StudentNumber, MAX(semstudentid) AS mx
                     FROM semesterstude
                     GROUP BY StudentNumber
                 ) sx ON sx.StudentNumber = s1.StudentNumber AND sx.mx = s1.semstudentid
                ) sst
            ";
            $this->db->join($latestSst, 'sst.StudentNumber = aa.student_number', 'left', false);
        }

        if ($this->db->table_exists('users')) {
            $this->db->join('users u', 'u.username = aa.student_number', 'left');
        }
        if ($this->db->table_exists('o_users')) {
            $this->db->join('o_users ou', 'ou.username = aa.student_number OR ou.IDNumber = aa.student_number', 'left');
        }

        $this->db->where('aa.activity_id', (int)$activity_id);

        if (!empty($date)) {
            $this->db->group_start()
                     ->where('aa.scan_date', $date)
                     ->or_where('DATE(aa.checked_in_at) =', $date)
                     ->group_end();
        }

        if (!empty($session)) {
            $this->db->where('aa.session', strtolower($session));
        }

        if (!empty($section)) {
            $sectionTok = trim($section);
            $this->db->group_start()
                     ->where('sst.Section', $sectionTok)
                     ->or_like('sst.Section', $sectionTok, 'both', true)
                     ->or_where('ssu.section', $sectionTok)
                     ->group_end();
        }

        if (!empty($yearLevel)) {
            $yearTok = trim($yearLevel);
            $this->db->group_start()
                     ->where('sst.YearLevel', $yearTok)
                     ->or_where('ssu.yearLevel', $yearTok)
                     ->group_end();
        }

        $this->db->order_by("(CASE WHEN COALESCE(sst.Section, ssu.section, '') = '' THEN 1 ELSE 0 END)", "ASC", false);
        $this->db->order_by("COALESCE(sst.Section, ssu.section, '')", "ASC", false);
        $this->db->order_by("student_name", "ASC");

        return $this->db->get()->result();
    }


}

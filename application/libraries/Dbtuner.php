<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dbtuner
 *
 * Creates the indexes this app needs, by itself, on whatever database it finds
 * itself pointed at. Deploying to production is just deploying the files — no
 * SQL to remember to run, and no "it's fast on my machine" surprises.
 *
 * How it stays out of the way:
 *  - After a successful pass it stores a hash of the index list in a one-row
 *    table. Every later request is a single primary-key lookup (~0.1ms) and
 *    returns. Add an index to $indexes below and the hash changes, so the next
 *    request applies it and re-stores. Nothing else to do.
 *  - The marker lives in the database rather than a file on purpose: the web
 *    server often cannot write into application/cache (here it runs as
 *    `daemon` while the tree is owned by the developer), and a database marker
 *    is also shared correctly when the app runs on more than one server.
 *  - It only ever CREATEs indexes. It never drops, alters or rewrites data.
 *  - Everything is wrapped: a missing table, a renamed column, or a database
 *    user without ALTER rights all end in "skip and carry on", never a broken
 *    page. A failed pass backs off instead of retrying on every request.
 *
 * @see application/config/hooks.php  (post_controller_constructor)
 */
class Dbtuner
{
    /** How long to wait before retrying after a failed pass. */
    const RETRY_AFTER = 3600;

    /** One-row bookkeeping table this library owns. */
    const STATE_TABLE = 'app_schema_state';

    /** Set once the check has run in this PHP process. */
    protected static $checked = false;

    /**
     * Indexes to guarantee. Each was picked from a measured query, not a guess
     * — the comment says which page pays for it.
     *
     * 'unique' is intentionally absent: this only creates plain indexes, so it
     * can never fail on existing duplicate data.
     */
    protected $indexes = array(

        // profileList + the duplicate-students screen join these three tables
        // on student number. Without indexes the joins were block nested-loop
        // scans and the page took ~6.5s.
        array('studentsignup', 'idx_ss_studno',      'StudentNumber'),
        array('studeprofile',  'idx_sp_studno',      'StudentNumber'),
        array('o_users',       'idx_ou_position',    'position'),

        // Dashboard summaries, masterlists and the enrollment drill-downs all
        // filter on the term and group/join by student.
        array('semesterstude', 'idx_sem_term',       'SY, Semester, Status'),
        array('semesterstude', 'idx_sem_studno',     'StudentNumber'),
        array('semesterstude', 'idx_sem_section',    'Section'),
        array('semesterstude', 'idx_sem_course',     'Course'),

        // Address dropdowns on every profile and registration form, over a
        // 42,000-row table that had no index at all.
        array('settings_address', 'idx_addr_prov',   'Province'),
        array('settings_address', 'idx_addr_city',   'City'),

        // Activity QR scanning: every scan looks a token up by hand today.
        array('student_qr',    'idx_qr_token',       'qr_token'),
        array('student_qr',    'idx_qr_student',     'student_number, status'),
        array('activity_attendance', 'idx_aa_act',   'activity_id'),
        array('activity_attendance', 'idx_aa_stud',  'student_number'),
        array('activity_attendance', 'idx_aa_date',  'scan_date'),

        // Login screen writes here on every attempt; the logs view reads it.
        array('login_logs',    'idx_ll_user',        'username'),
        array('login_logs',    'idx_ll_time',        'login_time'),

        // Student document requests and ledger lookups.
        array('stude_request', 'idx_sr_studno',      'StudentNumber'),
        array('studeaccount',  'idx_sa_studno',      'StudentNumber'),
    );

    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    // ------------------------------------------------------------------

    /**
     * The whole public surface. Cheap to call on every request.
     */
    public function ensure()
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        $wanted = $this->fingerprint();
        $state  = $this->read_state();

        // The common case: already applied, one primary-key lookup and out.
        if ($state !== null && isset($state['hash']) && $state['hash'] === $wanted) {
            return;
        }

        // A previous pass failed (no ALTER privilege, table locked, ...).
        // Back off rather than retrying on every page view.
        if ($state !== null && !empty($state['failed_at'])
            && (time() - (int)$state['failed_at']) < self::RETRY_AFTER
        ) {
            return;
        }

        try {
            $applied = $this->apply();
            $this->write_state(array(
                'hash'       => $wanted,
                'applied_at' => time(),
                'log'        => $applied,
            ));
        } catch (Exception $e) {
            $this->write_state(array(
                'hash'      => null,
                'failed_at' => time(),
                'error'     => $e->getMessage(),
            ));
            log_message('error', 'Dbtuner: ' . $e->getMessage());
        }
    }

    /**
     * Run a pass now and return what it did. Used by ensure(), and handy to
     * call from a controller when you want to see the result.
     *
     * @return array human-readable lines, one per index considered
     */
    public function apply()
    {
        if (!isset($this->CI->db)) {
            $this->CI->load->database();
        }
        $db  = $this->CI->db;
        $log = array();

        $tables  = array_flip($db->list_tables());
        $existing = $this->existing_indexes();

        foreach ($this->indexes as $spec) {
            list($table, $name, $columns) = $spec;

            if (!isset($tables[$table])) {
                $log[] = "skip  $table.$name (no such table)";
                continue;
            }

            $cols = array_map('trim', explode(',', $columns));
            $have = $db->list_fields($table);
            $missing = array_diff($cols, $have);
            if ($missing) {
                $log[] = "skip  $table.$name (no column " . implode(', ', $missing) . ')';
                continue;
            }

            $key      = strtolower($table);
            $names    = isset($existing[$key]['names']) ? $existing[$key]['names'] : array();
            $leading  = isset($existing[$key]['leading']) ? $existing[$key]['leading'] : array();

            // Skip when the name is taken, or when some other index already
            // leads with this column — a production database may well have its
            // own index here under a different name.
            if (in_array(strtolower($name), $names, true)
                || in_array(strtolower($cols[0]), $leading, true)
            ) {
                $log[] = "ok    $table.$name";
                continue;
            }

            $quoted = array();
            foreach ($cols as $c) {
                $quoted[] = '`' . str_replace('`', '', $c) . '`';
            }

            $sql = 'ALTER TABLE `' . str_replace('`', '', $table) . '`'
                . ' ADD INDEX `' . str_replace('`', '', $name) . '`'
                . ' (' . implode(', ', $quoted) . ')';

            // db_debug off for this call: a failure here is informational, not
            // something that should render CI's database error page.
            $prev = $db->db_debug;
            $db->db_debug = false;
            $ok = $db->simple_query($sql);
            $db->db_debug = $prev;

            $log[] = ($ok ? 'ADDED ' : 'fail  ') . "$table.$name ($columns)";
        }

        return $log;
    }

    // ------------------------------------------------------------------

    /**
     * Every index in this schema, in one query.
     *
     * This used to be a SHOW INDEX per table. That is fine on the happy path —
     * it runs once ever — but if the database user cannot create the state
     * table, the marker never persists and the pass repeats on every request.
     * One information_schema read keeps even that degraded case cheap.
     *
     * @return array table => ['names' => [...], 'leading' => [...]]
     */
    protected function existing_indexes()
    {
        $db   = $this->CI->db;
        $prev = $db->db_debug;
        $db->db_debug = false;
        $q = $db->query(
            'SELECT LOWER(TABLE_NAME) AS t, LOWER(INDEX_NAME) AS i,'
                . ' LOWER(COLUMN_NAME) AS c, SEQ_IN_INDEX AS s'
                . ' FROM information_schema.STATISTICS'
                . ' WHERE TABLE_SCHEMA = DATABASE()'
        );
        $db->db_debug = $prev;

        $out = array();
        if (!$q) {
            return $out;
        }

        foreach ($q->result_array() as $row) {
            $t = $row['t'];
            if (!isset($out[$t])) {
                $out[$t] = array('names' => array(), 'leading' => array());
            }
            if (!in_array($row['i'], $out[$t]['names'], true)) {
                $out[$t]['names'][] = $row['i'];
            }
            if ((int)$row['s'] === 1 && !in_array($row['c'], $out[$t]['leading'], true)) {
                $out[$t]['leading'][] = $row['c'];
            }
        }

        return $out;
    }

    /** Editing $indexes changes this, which is what triggers another pass. */
    protected function fingerprint()
    {
        return substr(md5(serialize($this->indexes)), 0, 16);
    }

    /** @return array|null decoded state row, or null if it cannot be read */
    protected function read_state()
    {
        $db = $this->CI->db;
        if (!isset($db)) {
            return null;
        }

        $prev = $db->db_debug;
        $db->db_debug = false;
        $q = $db->query(
            'SELECT v FROM `' . self::STATE_TABLE . '` WHERE k = ? LIMIT 1',
            array('db_tuned')
        );
        $db->db_debug = $prev;

        if (!$q || $q->num_rows() === 0) {
            return null;  // table not created yet, or nothing stored
        }

        $decoded = json_decode($q->row()->v, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function write_state(array $state)
    {
        $db   = $this->CI->db;
        $prev = $db->db_debug;
        $db->db_debug = false;

        $db->simple_query(
            'CREATE TABLE IF NOT EXISTS `' . self::STATE_TABLE . '` ('
            . ' `k` VARCHAR(64) NOT NULL,'
            . ' `v` TEXT NULL,'
            . ' `updated_at` DATETIME NULL,'
            . ' PRIMARY KEY (`k`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $db->query(
            'REPLACE INTO `' . self::STATE_TABLE . '` (k, v, updated_at) VALUES (?, ?, NOW())',
            array('db_tuned', json_encode($state))
        );

        $db->db_debug = $prev;
    }
}

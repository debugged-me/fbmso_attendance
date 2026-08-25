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

        $tables = array_flip($db->list_tables());

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

            if ($this->index_exists($table, $name, $cols[0])) {
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
     * True when the index name is taken, or some other index already leads
     * with this column — in which case adding ours would just duplicate work.
     */
    protected function index_exists($table, $name, $first_column)
    {
        $db  = $this->CI->db;
        $sql = 'SHOW INDEX FROM `' . str_replace('`', '', $table) . '`';

        $prev = $db->db_debug;
        $db->db_debug = false;
        $query = $db->query($sql);
        $db->db_debug = $prev;

        if (!$query) {
            return true; // cannot inspect it; leave the table alone
        }

        foreach ($query->result_array() as $row) {
            if (isset($row['Key_name']) && strcasecmp($row['Key_name'], $name) === 0) {
                return true;
            }
            if (isset($row['Seq_in_index'], $row['Column_name'])
                && (int)$row['Seq_in_index'] === 1
                && strcasecmp($row['Column_name'], $first_column) === 0
            ) {
                return true;
            }
        }
        return false;
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

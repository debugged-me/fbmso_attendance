<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Applies pending schema changes automatically, so a deploy needs no manual
 * phpMyAdmin step.
 *
 * Design constraints, because this runs on live web requests:
 *
 *  - Cheap when there is nothing to do. After a successful run a marker file
 *    is written and every later request costs one is_file() call, no queries.
 *  - Safe under concurrency. Two requests arriving together would otherwise
 *    both try the same DDL, so the runner takes a MySQL advisory lock first.
 *  - Never fatal. A migration failure is logged and the request continues;
 *    a schema tweak must not be able to take the site down.
 *  - Additive and idempotent ONLY. Column widening, new tables, new indexes.
 *    Nothing here may delete, lock, or rewrite user data -- that stays a
 *    deliberate manual decision (see application/migrations_sql/).
 */
class Schema_migrator
{
    /** @var CI_Controller */
    protected $CI;

    /** Bumped whenever a migration is added below. */
    const MARKER = 'schema_migrations_v5.done';

    /** Advisory lock name + seconds to wait for it. */
    const LOCK_NAME    = 'fbmso_schema_migrator';
    const LOCK_TIMEOUT = 5;

    /** Set once per PHP process so repeated calls are free. */
    protected static $ranThisProcess = false;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Migration list. Key = permanent id (never rename an applied one).
     * Each 'check' returns TRUE when the migration still needs to run, so a
     * migration is skipped when the schema already matches.
     */
    protected function migrations()
    {
        return array(

            // The password column was sized for sha1 (40 chars). bcrypt needs
            // 60 -- which already fit -- but the '!locked:<sha256>' marker used
            // to retire a compromised password is 72, and with stricton=FALSE
            // MySQL would silently truncate it. Widen once, with headroom for
            // a future move to argon2id.
            '2026_08_31_widen_o_users_password' => array(
                'check' => function () {
                    $len = $this->columnLength('o_users', 'password');
                    return $len !== null && $len < 255;
                },
                'run' => function () {
                    $this->CI->db->query("ALTER TABLE `o_users` MODIFY `password` VARCHAR(255) NOT NULL");
                },
            ),

            // audit_logs.action is an ENUM that never included the actions the
            // code actually writes. Every forgot-password event since
            // 2026-03-23 landed as '' (985 rows) because stricton is off, so
            // password resets are invisible to any action-based query.
            // Widening an ENUM preserves existing values.
            '2026_08_31_extend_audit_action_enum' => array(
                'check' => function () {
                    $type = $this->columnType('audit_logs', 'action');
                    return $type !== null && strpos($type, 'password_reset') === false;
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `audit_logs` MODIFY `action` ENUM(
                            'login','logout','create','update','delete',
                            'password_reset','password_change','profile_change',
                            'access_denied','security'
                         ) NOT NULL"
                    );
                },
            ),

            // Tamper-evident security event trail. Separate from audit_logs so
            // security events are not diluted by 37k routine login rows, and so
            // actor/target and device context have real columns.
            '2026_08_31_create_security_audit_logs' => array(
                'check' => function () {
                    return !$this->tableExists('security_audit_logs');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `security_audit_logs` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `event_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                          `event_type` VARCHAR(80) NOT NULL,
                          `event_status` VARCHAR(30) DEFAULT NULL,
                          `module` VARCHAR(100) DEFAULT NULL,

                          -- Who did it vs whose record it was. Kept separate so
                          -- 'user edited themselves' is distinguishable from
                          -- 'an admin edited someone else'.
                          `actor_username` VARCHAR(100) DEFAULT NULL,
                          `actor_full_name` VARCHAR(200) DEFAULT NULL,
                          `actor_level` VARCHAR(60) DEFAULT NULL,
                          `target_username` VARCHAR(100) DEFAULT NULL,

                          `table_name` VARCHAR(100) DEFAULT NULL,
                          `record_pk` VARCHAR(100) DEFAULT NULL,
                          `changed_field` VARCHAR(150) DEFAULT NULL,
                          `old_value` TEXT DEFAULT NULL,
                          `new_value` TEXT DEFAULT NULL,

                          `ip_address` VARCHAR(45) DEFAULT NULL,
                          `request_uri` VARCHAR(500) DEFAULT NULL,
                          `request_method` VARCHAR(10) DEFAULT NULL,
                          `session_reference` CHAR(64) DEFAULT NULL,

                          -- Interpreted device labels, kept separate from the
                          -- raw user-agent so a parser change never destroys
                          -- the original forensic value.
                          `device_type` VARCHAR(50) DEFAULT NULL,
                          `device_brand` VARCHAR(100) DEFAULT NULL,
                          `device_model_code` VARCHAR(100) DEFAULT NULL,
                          `device_marketing_name` VARCHAR(150) DEFAULT NULL,
                          `operating_system` VARCHAR(100) DEFAULT NULL,
                          `os_version` VARCHAR(50) DEFAULT NULL,
                          `browser` VARCHAR(100) DEFAULT NULL,
                          `browser_version` VARCHAR(50) DEFAULT NULL,
                          `raw_user_agent` TEXT DEFAULT NULL,

                          `risk_score` INT NOT NULL DEFAULT 0,
                          `risk_level` VARCHAR(20) DEFAULT NULL,
                          `risk_reason` TEXT DEFAULT NULL,

                          `description` TEXT DEFAULT NULL,
                          `extra` LONGTEXT DEFAULT NULL,

                          -- Hash chain: record_hash = SHA256(payload + prev_hash).
                          -- Editing or removing a historic row breaks the chain.
                          `prev_hash` CHAR(64) DEFAULT NULL,
                          `record_hash` CHAR(64) DEFAULT NULL,

                          PRIMARY KEY (`id`),
                          KEY `idx_event_type_time` (`event_type`,`event_time`),
                          KEY `idx_actor_time` (`actor_username`,`event_time`),
                          KEY `idx_target_time` (`target_username`,`event_time`),
                          KEY `idx_ip_time` (`ip_address`,`event_time`),
                          KEY `idx_model_code` (`device_model_code`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Model code -> marketing name. Codes are ambiguous across markets,
            // so this is a display aid only; the raw code is always preserved.
            '2026_08_31_create_device_model_catalog' => array(
                'check' => function () {
                    return !$this->tableExists('device_model_catalog');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `device_model_catalog` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `manufacturer` VARCHAR(100) DEFAULT NULL,
                          `model_code` VARCHAR(100) NOT NULL,
                          `marketing_name` VARCHAR(150) DEFAULT NULL,
                          `source_reference` VARCHAR(255) DEFAULT NULL,
                          `updated_at` DATETIME DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_model_code` (`model_code`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );

                    $seed = array(
                        array('realme', 'RMX3834', 'realme Note 50'),
                        array('realme', 'RMX3630', 'realme C53'),
                        array('Samsung', 'SM-A556E', 'Galaxy A55 5G'),
                        array('Samsung', 'SM-A146P', 'Galaxy A14 5G'),
                        array('OPPO',    'CPH2603', 'OPPO A38'),
                        array('Xiaomi',  '23028RN4DG', 'Redmi 12C'),
                        array('vivo',    'V2247', 'vivo Y17s'),
                    );
                    foreach ($seed as $row) {
                        $this->CI->db->query(
                            "INSERT IGNORE INTO `device_model_catalog`
                               (manufacturer, model_code, marketing_name, source_reference, updated_at)
                             VALUES (?, ?, ?, 'initial seed', NOW())",
                            $row
                        );
                    }
                },
            ),

            // Checkpoints for the audit chain. A hash chain cannot detect its
            // own tail being truncated, so each run records where the chain
            // ended. The same figures go out by email, which is the copy that
            // actually matters: it lives off the server, where someone with
            // database access cannot reach it.
            '2026_08_31_create_security_audit_anchors' => array(
                'check' => function () {
                    return !$this->tableExists('security_audit_anchors');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `security_audit_anchors` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `checked_at` DATETIME NOT NULL,
                          `last_record_id` BIGINT UNSIGNED DEFAULT NULL,
                          `last_record_hash` CHAR(64) DEFAULT NULL,
                          `total_records` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                          `chain_ok` TINYINT(1) NOT NULL DEFAULT 1,
                          `notes` TEXT DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          KEY `idx_checked_at` (`checked_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),
        );
    }

    /** Full COLUMN_TYPE for a column, or NULL if it does not exist. */
    protected function columnType($table, $column)
    {
        $row = $this->CI->db->query(
            "SELECT COLUMN_TYPE AS t
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?",
            array($table, $column)
        )->row();

        return $row ? (string)$row->t : null;
    }

    protected function tableExists($table)
    {
        $row = $this->CI->db->query(
            "SELECT 1 AS ok
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
              LIMIT 1",
            array($table)
        )->row();

        return (bool)$row;
    }

    // ------------------------------------------------------------------

    /**
     * Force the next run() to re-check the schema, ignoring the marker.
     *
     * Needed after restoring an older database backup: the marker says the
     * work is done, but the restored schema may predate it. Call from a CLI
     * bootstrap, or just delete the marker file named by markerPath().
     */
    public function forget()
    {
        self::$ranThisProcess = false;

        $marker = $this->markerPath();
        if ($marker !== null && is_file($marker)) {
            @unlink($marker);
        }
    }

    /** Entry point, called by the hook on every request. */
    public function run()
    {
        if (self::$ranThisProcess) {
            return;
        }

        $marker = $this->markerPath();
        if ($marker !== null && is_file($marker)) {
            self::$ranThisProcess = true;
            return;
        }

        try {
            $this->applyPending();
            self::$ranThisProcess = true;

            if ($marker !== null) {
                @file_put_contents($marker, date('c') . " applied\n", LOCK_EX);
            }
        } catch (Throwable $e) {
            // Log and carry on. A schema problem must never 500 the site.
            log_message('error', 'Schema_migrator: ' . $e->getMessage());
            self::$ranThisProcess = true;
        }
    }

    protected function applyPending()
    {
        $pending = array();
        foreach ($this->migrations() as $id => $m) {
            $check = $m['check'];
            if ($check()) {
                $pending[$id] = $m;
            }
        }

        if (empty($pending)) {
            return;
        }

        // Serialise across web workers so concurrent requests do not both
        // fire the same ALTER.
        if (!$this->acquireLock()) {
            return; // another worker is doing it; try again next request
        }

        try {
            $this->ensureLedger();

            foreach ($pending as $id => $m) {
                if ($this->alreadyApplied($id)) {
                    continue;
                }

                // Re-check under the lock: the other worker may have just
                // finished this exact migration.
                $check = $m['check'];
                if (!$check()) {
                    $this->recordApplied($id);
                    continue;
                }

                $run = $m['run'];
                $run();
                $this->recordApplied($id);

                log_message('info', 'Schema_migrator: applied ' . $id);
            }
        } finally {
            $this->releaseLock();
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** CHARACTER_MAXIMUM_LENGTH for a column, or NULL if it does not exist. */
    protected function columnLength($table, $column)
    {
        $row = $this->CI->db->query(
            "SELECT CHARACTER_MAXIMUM_LENGTH AS len
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?",
            array($table, $column)
        )->row();

        return $row ? (int)$row->len : null;
    }

    protected function ensureLedger()
    {
        $this->CI->db->query(
            "CREATE TABLE IF NOT EXISTS `fbmso_schema_migrations` (
               `id` VARCHAR(191) NOT NULL,
               `applied_at` DATETIME NOT NULL,
               PRIMARY KEY (`id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    protected function alreadyApplied($id)
    {
        $row = $this->CI->db->query(
            "SELECT 1 AS ok FROM `fbmso_schema_migrations` WHERE id = ? LIMIT 1",
            array($id)
        )->row();

        return (bool)$row;
    }

    protected function recordApplied($id)
    {
        $this->CI->db->query(
            "INSERT IGNORE INTO `fbmso_schema_migrations` (id, applied_at) VALUES (?, NOW())",
            array($id)
        );
    }

    protected function acquireLock()
    {
        $row = $this->CI->db->query(
            "SELECT GET_LOCK(?, ?) AS got",
            array(self::LOCK_NAME, self::LOCK_TIMEOUT)
        )->row();

        return $row && (int)$row->got === 1;
    }

    protected function releaseLock()
    {
        $this->CI->db->query("SELECT RELEASE_LOCK(?)", array(self::LOCK_NAME));
    }

    /**
     * Marker file path, or NULL if nowhere is writable.
     *
     * The cache dir is preferred but is often owned by the deploying user
     * rather than the web-server user, so fall back to the system temp dir.
     * Losing the marker is harmless: the runner just re-checks the schema,
     * finds nothing to do, and rewrites it.
     *
     * The database name is folded into the filename so two sites sharing a
     * temp dir cannot read each other's marker.
     */
    protected function markerPath()
    {
        $suffix = substr(sha1((string)$this->CI->db->database), 0, 12) . '.' . self::MARKER;

        $candidates = array();

        $configured = rtrim((string)$this->CI->config->item('cache_path'), '/');
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        $candidates[] = rtrim(APPPATH, '/') . '/cache';
        $candidates[] = rtrim((string)sys_get_temp_dir(), '/');

        foreach ($candidates as $dir) {
            if ($dir !== '' && is_dir($dir) && is_writable($dir)) {
                return $dir . '/' . $suffix;
            }
        }

        return null;
    }
}

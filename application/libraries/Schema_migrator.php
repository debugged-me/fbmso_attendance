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
    const MARKER = 'schema_migrations_v20.done';

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

            // A deleted payment must say why. The cashier's reason is kept
            // with the delete entry so the Payment Activity Log and Super
            // Admin's Audit Trail both show it.
            '2026_09_30_payment_audit_reason' => array(
                'check' => function () {
                    return $this->tableExists('payment_audit_log')
                        && !$this->columnExists('payment_audit_log', 'reason');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `payment_audit_log`
                         ADD COLUMN `reason` VARCHAR(255) DEFAULT NULL AFTER `new_values`"
                    );
                },
            ),

            // Payment IDs were handed out as MAX(ID)+1 on a column with no
            // key, so two cashiers saving at the same moment could both write
            // the same ID -- silently -- and Edit/Delete/Print Receipt would
            // then act on several payments at once. The database assigns them
            // now. Any duplicates (and legacy ID 0 rows) already present are
            // split first: one copy keeps the ID, each extra copy gets a fresh
            // one, so the key can be added.
            '2026_09_29_payment_id_auto_increment' => array(
                'check' => function () {
                    return $this->tableExists('paymentsaccounts')
                        && stripos((string)$this->columnExtra('paymentsaccounts', 'ID'), 'auto_increment') === false;
                },
                'run' => function () {
                    $db = $this->CI->db;

                    $dups = $db->query(
                        "SELECT ID, COUNT(*) AS n FROM `paymentsaccounts`
                          GROUP BY ID HAVING COUNT(*) > 1 OR ID = 0"
                    )->result();
                    foreach ($dups as $d) {
                        $extra = (int)$d->ID === 0 ? (int)$d->n : (int)$d->n - 1;
                        for ($i = 0; $i < $extra; $i++) {
                            $next = (int)$db->query("SELECT MAX(ID) AS m FROM `paymentsaccounts`")->row()->m + 1;
                            $db->query("UPDATE `paymentsaccounts` SET ID = ? WHERE ID = ? LIMIT 1", array($next, (int)$d->ID));
                            log_message('error', 'Schema_migrator: duplicate payment ID ' . $d->ID . ' split off as ' . $next);
                        }
                    }

                    $hasPrimary = (bool)$db->query(
                        "SELECT 1 AS ok FROM information_schema.STATISTICS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paymentsaccounts'
                            AND INDEX_NAME = 'PRIMARY' LIMIT 1"
                    )->row();

                    $db->query(
                        "ALTER TABLE `paymentsaccounts`
                         MODIFY `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, "
                        . ($hasPrimary ? "ADD UNIQUE KEY `uq_payment_id` (`ID`)" : "ADD PRIMARY KEY (`ID`)")
                    );
                },
            ),

            // Payment entries are now logged alongside edits and deletions.
            // The ENUM only allowed 'edit'/'delete', and with stricton off a
            // 'create' row would silently land as ''. Widening an ENUM keeps
            // existing values.
            '2026_09_28_payment_audit_create_action' => array(
                'check' => function () {
                    $type = $this->columnType('payment_audit_log', 'action');
                    return $type !== null && strpos($type, "'create'") === false;
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `payment_audit_log`
                         MODIFY `action` ENUM('create','edit','delete') NOT NULL"
                    );
                },
            ),

            // Preserve the actor's role at the moment an event is written.
            // Joining o_users at read time is not enough: an account can be
            // deleted or its role can change, which would erase or rewrite
            // the historical context Super Admin needs during an inquiry.
            '2026_09_28_add_audit_actor_levels' => array(
                'check' => function () {
                    return ($this->tableExists('audit_logs')
                            && !$this->columnExists('audit_logs', 'actor_level'))
                        || ($this->tableExists('login_logs')
                            && !$this->columnExists('login_logs', 'actor_level'))
                        || ($this->tableExists('payment_audit_log')
                            && !$this->columnExists('payment_audit_log', 'actor_level'));
                },
                'run' => function () {
                    if ($this->tableExists('audit_logs')
                        && !$this->columnExists('audit_logs', 'actor_level')) {
                        $this->CI->db->query(
                            "ALTER TABLE `audit_logs`
                             ADD COLUMN `actor_level` VARCHAR(60) DEFAULT NULL AFTER `user_id`"
                        );
                    }
                    if ($this->tableExists('login_logs')
                        && !$this->columnExists('login_logs', 'actor_level')) {
                        $this->CI->db->query(
                            "ALTER TABLE `login_logs`
                             ADD COLUMN `actor_level` VARCHAR(60) DEFAULT NULL AFTER `username`"
                        );
                    }
                    if ($this->tableExists('payment_audit_log')
                        && !$this->columnExists('payment_audit_log', 'actor_level')) {
                        $this->CI->db->query(
                            "ALTER TABLE `payment_audit_log`
                             ADD COLUMN `actor_level` VARCHAR(60) DEFAULT NULL AFTER `changed_by`"
                        );
                    }
                },
            ),

            // Temporary passwords issued for new accounts and password
            // resets must be replaced on first sign-in. Some installations
            // already use this flag in code but predate the column itself.
            '2026_09_22_add_force_change_password' => array(
                'check' => function () {
                    return $this->tableExists('o_users')
                        && !$this->columnExists('o_users', 'force_change_password');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `o_users`
                         ADD COLUMN `force_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `acctStat`"
                    );
                },
            ),

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

            // Failed-login counters. A dedicated table rather than querying
            // login_logs: ip_address there is unindexed, so counting recent
            // failures would full-scan a table that grows forever.
            '2026_08_31_create_login_throttle' => array(
                'check' => function () {
                    return !$this->tableExists('login_throttle');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `login_throttle` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `scope` VARCHAR(20) NOT NULL,
                          `scope_key` VARCHAR(160) NOT NULL,
                          `failures` INT UNSIGNED NOT NULL DEFAULT 0,
                          `first_failure_at` DATETIME NOT NULL,
                          `last_failure_at` DATETIME NOT NULL,
                          `blocked_until` DATETIME DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_scope` (`scope`,`scope_key`),
                          KEY `idx_blocked_until` (`blocked_until`),
                          KEY `idx_last_failure` (`last_failure_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Pre-containment snapshot of password hashes. Two reasons:
            // the change is reversible if something goes wrong, and the old
            // hashes are evidence -- they are how we identified which
            // accounts shared the compromised credential. Once every account
            // is bcrypt that comparison becomes impossible, so capture it
            // before rotating anything.
            '2026_08_31_create_password_rotation_backup' => array(
                'check' => function () {
                    return !$this->tableExists('password_rotation_backup');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `password_rotation_backup` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `username` VARCHAR(100) NOT NULL,
                          `old_password` VARCHAR(255) NOT NULL,
                          `position` VARCHAR(60) DEFAULT NULL,
                          `reason` VARCHAR(120) NOT NULL,
                          `batch` VARCHAR(64) NOT NULL,
                          `rotated_at` DATETIME NOT NULL,
                          `restored_at` DATETIME DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          KEY `idx_username` (`username`),
                          KEY `idx_batch` (`batch`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Active session inventory. Sessions are stored as files, so there
            // is no table to delete a row from to end one. Revocation is
            // enforced in the application instead: every request checks
            // whether its own session has been revoked.
            '2026_08_31_create_user_security_sessions' => array(
                'check' => function () {
                    return !$this->tableExists('user_security_sessions');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `user_security_sessions` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `username` VARCHAR(100) NOT NULL,
                          `session_reference` CHAR(64) NOT NULL,

                          `ip_address` VARCHAR(45) DEFAULT NULL,
                          `device_type` VARCHAR(50) DEFAULT NULL,
                          `device_marketing_name` VARCHAR(150) DEFAULT NULL,
                          `device_model_code` VARCHAR(100) DEFAULT NULL,
                          `operating_system` VARCHAR(100) DEFAULT NULL,
                          `browser` VARCHAR(100) DEFAULT NULL,
                          `raw_user_agent` TEXT DEFAULT NULL,

                          `created_at` DATETIME NOT NULL,
                          `last_activity_at` DATETIME DEFAULT NULL,
                          `revoked_at` DATETIME DEFAULT NULL,
                          `revoke_reason` VARCHAR(255) DEFAULT NULL,
                          `revoked_by` VARCHAR(100) DEFAULT NULL,

                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_session_reference` (`session_reference`),
                          KEY `idx_username_activity` (`username`,`last_activity_at`),
                          KEY `idx_revoked` (`revoked_at`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Trusted-device recognition.
            //
            // Keyed on (username, device_token_hash) rather than the token
            // alone. One browser holds one token; if it signs into several
            // accounts it produces several rows sharing that token, which
            // makes "this device has been used on 17 accounts" a single
            // query. That is the 2026-08-28 pattern, and a schema keyed only
            // on the token would have hidden it.
            //
            // Only the SHA-256 of the token is stored. The raw value lives in
            // the browser cookie and nowhere else, so the table cannot be
            // used to impersonate a device.
            '2026_08_31_create_user_devices' => array(
                'check' => function () {
                    return !$this->tableExists('user_devices');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `user_devices` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `username` VARCHAR(100) NOT NULL,
                          `device_token_hash` CHAR(64) NOT NULL,

                          `device_brand` VARCHAR(100) DEFAULT NULL,
                          `device_marketing_name` VARCHAR(150) DEFAULT NULL,
                          `device_model_code` VARCHAR(100) DEFAULT NULL,
                          `device_type` VARCHAR(50) DEFAULT NULL,
                          `operating_system` VARCHAR(100) DEFAULT NULL,
                          `os_version` VARCHAR(50) DEFAULT NULL,
                          `browser` VARCHAR(100) DEFAULT NULL,
                          `browser_version` VARCHAR(50) DEFAULT NULL,
                          `raw_user_agent` TEXT DEFAULT NULL,

                          `first_ip` VARCHAR(45) DEFAULT NULL,
                          `last_ip` VARCHAR(45) DEFAULT NULL,
                          `login_count` INT UNSIGNED NOT NULL DEFAULT 0,

                          `first_seen_at` DATETIME NOT NULL,
                          `last_seen_at` DATETIME NOT NULL,

                          `is_trusted` TINYINT(1) NOT NULL DEFAULT 0,
                          `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
                          `trusted_at` DATETIME DEFAULT NULL,
                          `revoked_at` DATETIME DEFAULT NULL,
                          `revoked_by` VARCHAR(100) DEFAULT NULL,

                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uq_user_device` (`username`,`device_token_hash`),
                          KEY `idx_token` (`device_token_hash`),
                          KEY `idx_user_seen` (`username`,`last_seen_at`),
                          KEY `idx_model` (`device_model_code`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Forensic columns on login_logs: user_agent, referrer, device
            // fingerprint, session_id. Additive only — existing rows keep
            // their NULL values, new rows get the extra context.
            '2026_09_04_add_login_logs_forensic_columns' => array(
                'check' => function () {
                    return !$this->columnExists('login_logs', 'user_agent');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `login_logs`
                          ADD COLUMN `user_agent` VARCHAR(500) NULL AFTER `ip_address`,
                          ADD COLUMN `referrer` VARCHAR(500) NULL AFTER `user_agent`,
                          ADD COLUMN `device_fingerprint` VARCHAR(255) NULL AFTER `referrer`,
                          ADD COLUMN `session_id` VARCHAR(64) NULL AFTER `device_fingerprint`"
                    );
                },
            ),

            // IP blacklist — blocks known attacker IPs at the AuthGuard
            // level, before they can even load the login page.
            '2026_09_04_create_ip_blacklist' => array(
                'check' => function () {
                    return !$this->tableExists('ip_blacklist');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `ip_blacklist` (
                          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `ip_address` VARCHAR(45) NOT NULL,
                          `reason` VARCHAR(255) NOT NULL,
                          `blocked_by` VARCHAR(100) NOT NULL DEFAULT 'system',
                          `blocked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `expires_at` DATETIME NULL,
                          `is_permanent` TINYINT(1) NOT NULL DEFAULT 0,
                          `incident_reference` VARCHAR(100) NULL,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `uniq_ip` (`ip_address`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );

                    // Seed the known attacker IP from the Aug 28 incident.
                    $this->CI->db->query(
                        "INSERT IGNORE INTO `ip_blacklist`
                           (ip_address, reason, blocked_by, is_permanent, incident_reference)
                         VALUES
                           ('138.84.127.148',
                            'Unauthorized account access — changed student name and profile picture on Aug 28, 2026',
                            'admin', 1, 'INC-2026-08-28-001')"
                    );
                },
            ),

            // Student QR lifecycle fields were introduced by the QR model but
            // older installations only have token/status/issued_at. Without
            // this additive migration, selecting expires_at makes every scan
            // fail with HTTP 500 on those installations.
            '2026_09_16_add_student_qr_lifecycle_columns' => array(
                'check' => function () {
                    return $this->tableExists('student_qr')
                        && (!$this->columnExists('student_qr', 'expires_at')
                            || !$this->columnExists('student_qr', 'revoked_at'));
                },
                'run' => function () {
                    if (!$this->columnExists('student_qr', 'expires_at')) {
                        $this->CI->db->query(
                            "ALTER TABLE `student_qr` ADD COLUMN `expires_at` DATETIME NULL AFTER `issued_at`"
                        );
                    }
                    if (!$this->columnExists('student_qr', 'revoked_at')) {
                        $this->CI->db->query(
                            "ALTER TABLE `student_qr` ADD COLUMN `revoked_at` DATETIME NULL AFTER `expires_at`"
                        );
                    }
                },
            ),

            // Fee schedule the Payment and Fee Setup screens read from.
            // feesid has no AUTO_INCREMENT because the controller allocates
            // ids itself (nextTableId) -- kept as-is so an existing prod
            // table and a freshly created one behave identically.
            '2026_09_22_create_fees' => array(
                'check' => function () {
                    return !$this->tableExists('fees');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `fees` (
                          `feesid` INT(10) UNSIGNED NOT NULL,
                          `Description` VARCHAR(100) NOT NULL DEFAULT '',
                          `Amount` DOUBLE NOT NULL DEFAULT 0,
                          `Course` VARCHAR(200) NOT NULL DEFAULT '',
                          `Major` VARCHAR(65) DEFAULT NULL,
                          `YearLevel` VARCHAR(45) NOT NULL DEFAULT '',
                          `Semester` VARCHAR(45) NOT NULL DEFAULT '',
                          `feesType` VARCHAR(45) NOT NULL DEFAULT '',
                          PRIMARY KEY (`feesid`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Who edited or deleted a payment, and what it looked like before
            // and after. Must exist before the first edit, not be created by
            // it -- an install that has never edited a payment would otherwise
            // 500 on the Payment Activity Log page.
            '2026_09_22_create_payment_audit_log' => array(
                'check' => function () {
                    return !$this->tableExists('payment_audit_log');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "CREATE TABLE `payment_audit_log` (
                          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                          `payment_id` INT(10) UNSIGNED NOT NULL,
                          `action` ENUM('create','edit','delete') NOT NULL,
                          `or_number` VARCHAR(20) NOT NULL DEFAULT '',
                          `student_number` VARCHAR(45) NOT NULL DEFAULT '',
                          `description` VARCHAR(150) NOT NULL DEFAULT '',
                          `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                          `old_values` TEXT DEFAULT NULL,
                          `new_values` TEXT DEFAULT NULL,
                          `changed_by` VARCHAR(45) NOT NULL DEFAULT '',
                          `actor_level` VARCHAR(60) DEFAULT NULL,
                          `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          KEY `payment_id` (`payment_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ),

            // Offline scans are recorded at the time they were taken, not the
            // time they synced. recorded_at keeps the server's receipt time so
            // the gap between the two stays auditable, and time_source says
            // which clock the stored checked_in_at came from.
            '2026_09_22_add_attendance_scan_time_source' => array(
                'check' => function () {
                    return $this->tableExists('activity_attendance')
                        && !$this->columnExists('activity_attendance', 'time_source');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `activity_attendance`
                         ADD COLUMN `recorded_at` DATETIME NULL DEFAULT NULL,
                         ADD COLUMN `time_source` ENUM('server','client') NOT NULL DEFAULT 'server'"
                    );
                },
            ),

            // Natural-key dedup for queued scans. The X-Idempotency-Key replay
            // log expires, so a scan delivered but never acknowledged could be
            // re-executed on a later retry. A unique client id makes the
            // duplicate impossible at the table level rather than by TTL.
            '2026_09_22_add_attendance_client_scan_id' => array(
                'check' => function () {
                    return $this->tableExists('activity_attendance')
                        && !$this->columnExists('activity_attendance', 'client_scan_id');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `activity_attendance`
                         ADD COLUMN `client_scan_id` CHAR(36) NULL DEFAULT NULL,
                         ADD UNIQUE KEY `uq_client_scan` (`client_scan_id`)"
                    );
                },
            ),

            // Natural-key dedup for payments queued offline. Payment IDs and
            // O.R. numbers are assigned server-side, so a payment that was
            // recorded but whose response never reached the device would
            // otherwise be charged to the student twice on retry.
            '2026_09_22_add_payment_client_id' => array(
                'check' => function () {
                    return $this->tableExists('paymentsaccounts')
                        && !$this->columnExists('paymentsaccounts', 'client_payment_id');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `paymentsaccounts`
                         ADD COLUMN `client_payment_id` CHAR(36) NULL DEFAULT NULL,
                         ADD UNIQUE KEY `uq_client_payment` (`client_payment_id`)"
                    );
                },
            ),

            // The fee price a payment was taken against, frozen at the moment
            // it was received. Without it, "is this student fully paid?" was
            // answered against the LIVE fees table, so raising a fee's price
            // retroactively re-opened balances that were already settled.
            // Existing rows are backfilled with today's price -- the best
            // estimate available -- so the report reads the same before and
            // after this migration, and stops drifting from here on.
            '2026_09_23_add_payment_fee_snapshot' => array(
                'check' => function () {
                    return $this->tableExists('paymentsaccounts')
                        && !$this->columnExists('paymentsaccounts', 'FeeFullAmount');
                },
                'run' => function () {
                    $this->CI->db->query(
                        "ALTER TABLE `paymentsaccounts`
                         ADD COLUMN `FeeFullAmount` DECIMAL(12,2) NOT NULL DEFAULT 0.00"
                    );

                    if ($this->tableExists('fees')) {
                        $this->CI->db->query(
                            "UPDATE `paymentsaccounts` p
                               JOIN (SELECT Description, MAX(Amount) AS FullAmount
                                       FROM `fees` GROUP BY Description) f
                                 ON f.Description = p.description
                                SET p.FeeFullAmount = f.FullAmount
                              WHERE p.FeeFullAmount = 0"
                        );
                    }
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

    /** EXTRA for a column (e.g. 'auto_increment'), or NULL if it does not exist. */
    protected function columnExtra($table, $column)
    {
        $row = $this->CI->db->query(
            "SELECT EXTRA AS e
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?",
            array($table, $column)
        )->row();

        return $row ? (string)$row->e : null;
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

    /** TRUE if a column exists on a table. */
    protected function columnExists($table, $column)
    {
        $row = $this->CI->db->query(
            "SELECT 1 AS ok
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
              LIMIT 1",
            array($table, $column)
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

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
    const MARKER = 'schema_migrations_v3.done';

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
        );
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

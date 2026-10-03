<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Db_backup — pure-PHP MySQL/MariaDB dump generator.
 *
 * Produces a .sql file in the phpMyAdmin/mysqldump style: session SET
 * statements up front, CREATE TABLE + chunked INSERT statements per table,
 * then views/triggers/routines/events, then COMMIT and charset restore.
 * The output imports cleanly through phpMyAdmin or the mysql CLI.
 *
 * Everything is streamed to a file handle and rows are read with an
 * unbuffered query, so memory stays flat regardless of database size.
 * A REPEATABLE READ snapshot keeps InnoDB data consistent without locking.
 *
 * Used by the Backup controller; also safe to call from CLI for testing.
 */
class DbBackup
{
    /** Max bytes per INSERT statement (keeps every query under any sane
     *  max_allowed_packet on the importing server). */
    const INSERT_CHUNK = 262144;

    /** @var CI_Controller */
    private $CI;

    /** @var mysqli raw connection handle (CI keeps it on db->conn_id) */
    private $conn;

    /** @var resource output stream */
    private $fh;

    /** @var int bytes written so far */
    private $written = 0;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->conn = $this->CI->db->conn_id;
    }

    /**
     * Information shown on the backup page (also handy for the audit log).
     */
    public function stats()
    {
        $db = $this->CI->db->database;

        $row = $this->CI->db->query(
            "SELECT COUNT(*) AS objects,
                    SUM(TABLE_TYPE = 'BASE TABLE') AS tables,
                    SUM(TABLE_TYPE = 'VIEW') AS views,
                    COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) AS bytes,
                    COALESCE(SUM(TABLE_ROWS), 0) AS rows_estimate
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ?",
            array($db)
        )->row_array();

        return array(
            'database'       => $db,
            'hostname'       => (string)$this->CI->db->hostname,
            'server_version' => mysqli_get_server_info($this->conn),
            'php_version'    => PHP_VERSION,
            'tables'         => (int)($row['tables'] ?? 0),
            'views'          => (int)($row['views'] ?? 0),
            'objects'        => (int)($row['objects'] ?? 0),
            'bytes'          => (float)($row['bytes'] ?? 0),
            'rows_estimate'  => (int)($row['rows_estimate'] ?? 0),
        );
    }

    /**
     * Write a complete dump of the current database to $path.
     *
     * @return array stats: bytes, tables, views, rows, seconds
     * @throws Exception on any failure — the partial file is unlinked.
     */
    public function dumpTo($path)
    {
        $started = microtime(true);
        $stats = array('bytes' => 0, 'tables' => 0, 'views' => 0, 'rows' => 0);

        $this->fh = fopen($path, 'wb');
        if (!$this->fh) {
            throw new Exception('DbBackup: cannot write to ' . $path);
        }

        try {
            // Consistent snapshot for InnoDB — same guarantee mysqldump's
            // --single-transaction gives, with no table locking.
            mysqli_query($this->conn, 'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            mysqli_query($this->conn, 'START TRANSACTION WITH CONSISTENT SNAPSHOT');
            mysqli_query($this->conn, "SET NAMES utf8mb4");
            // TIMESTAMP columns must be exported as UTC: the dump file's
            // SET time_zone="+00:00" makes the importer interpret the
            // literals as UTC, so values written here must be UTC too.
            // Without this a server on e.g. Asia/Manila shifts every
            // timestamp by +8h on restore. (mysqldump does the same.)
            mysqli_query($this->conn, "SET SESSION time_zone = '+00:00'");

            $this->writeHeader();

            $objects = $this->listObjects();
            $views   = array();

            foreach ($objects as $obj) {
                if ($obj['TABLE_TYPE'] === 'VIEW') {
                    $views[] = $obj['TABLE_NAME'];
                    continue;
                }
                $stats['rows'] += $this->dumpTable($obj['TABLE_NAME']);
                $stats['tables']++;
            }

            foreach ($views as $view) {
                $this->dumpView($view);
                $stats['views']++;
            }

            $this->dumpTriggers();
            $this->dumpRoutines();
            $this->dumpEvents();

            mysqli_query($this->conn, 'COMMIT');
            $this->writeFooter();
        } catch (Throwable $e) {
            fclose($this->fh);
            $this->fh = null;
            @unlink($path);
            throw new Exception($e->getMessage());
        }

        fclose($this->fh);
        $this->fh = null;

        $stats['bytes']   = $this->written;
        $stats['seconds'] = round(microtime(true) - $started, 2);
        return $stats;
    }

    // -------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------

    private function out($sql)
    {
        $n = fwrite($this->fh, $sql);
        if ($n === false) {
            throw new Exception('DbBackup: write failed');
        }
        $this->written += $n;
    }

    private function q($name) // backtick-quote an identifier
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function listObjects()
    {
        return $this->CI->db->query(
            'SELECT TABLE_NAME, TABLE_TYPE
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ?
              ORDER BY TABLE_NAME',
            array($this->CI->db->database)
        )->result_array();
    }

    private function writeHeader()
    {
        $db = $this->CI->db->database;

        $this->out(
            "-- FBM SO Attendance — database backup\n" .
            "-- phpMyAdmin-compatible SQL dump\n" .
            "--\n" .
            '-- Host: ' . $this->CI->db->hostname . "\n" .
            '-- Generation Time: ' . date('M d, Y \a\t h:i A') . "\n" .
            '-- Server version: ' . mysqli_get_server_info($this->conn) . "\n" .
            '-- PHP Version: ' . PHP_VERSION . "\n\n" .
            "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
            "START TRANSACTION;\n" .
            "SET time_zone = \"+00:00\";\n\n\n" .
            "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n" .
            "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n" .
            "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n" .
            "/*!40101 SET NAMES utf8mb4 */;\n" .
            "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n" .
            "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n\n" .
            "--\n" .
            '-- Database: ' . $this->q($db) . "\n" .
            "--\n\n"
        );
    }

    private function writeFooter()
    {
        $this->out(
            "COMMIT;\n\n" .
            "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n" .
            "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n" .
            "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n" .
            "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n" .
            "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n"
        );
    }

    /** Dump one table's structure + data. Returns the row count. */
    private function dumpTable($table)
    {
        $res = mysqli_query($this->conn, 'SHOW CREATE TABLE ' . $this->q($table));
        if (!$res) {
            throw new Exception('SHOW CREATE TABLE failed for ' . $table);
        }
        $row = mysqli_fetch_assoc($res);
        $create = $row['Create Table'];
        $res->free();

        $this->out(
            "-- --------------------------------------------------------\n\n" .
            "--\n" .
            '-- Table structure for table ' . $this->q($table) . "\n" .
            "--\n\n" .
            'DROP TABLE IF EXISTS ' . $this->q($table) . ";\n" .
            $create . ";\n\n"
        );

        return $this->dumpRows($table);
    }

    /** Stream all rows of $table as chunked INSERT statements. */
    private function dumpRows($table)
    {
        $res = mysqli_query($this->conn, 'SELECT * FROM ' . $this->q($table), MYSQLI_USE_RESULT);
        if (!$res) {
            throw new Exception('SELECT failed for table ' . $table);
        }

        $fields = $res->fetch_fields();
        $cols = array();
        foreach ($fields as $f) {
            $cols[] = $this->q($f->name);
        }

        $this->out(
            "--\n" .
            '-- Dumping data for table ' . $this->q($table) . "\n" .
            "--\n\n"
        );

        $prefix = 'INSERT INTO ' . $this->q($table) . ' (' . implode(', ', $cols) . ") VALUES\n";
        $chunk = '';
        $count = 0;

        while ($row = $res->fetch_row()) {
            $tuple = '(' . $this->rowValues($row, $fields) . ')';
            if ($chunk === '') {
                $chunk = $prefix . $tuple;
            } elseif (strlen($chunk) + strlen($tuple) + 2 > self::INSERT_CHUNK) {
                $this->out($chunk . ";\n");
                $chunk = $prefix . $tuple;
            } else {
                $chunk .= ",\n" . $tuple;
            }
            $count++;
        }
        $res->free();

        if ($chunk !== '') {
            $this->out($chunk . ";\n");
        }
        $this->out("\n");

        return $count;
    }

    /**
     * Render one row's VALUES tuple.
     * NULL unquoted; numbers unquoted; binary data as 0x hex; everything
     * else single-quoted via mysqli_real_escape_string (same escaping
     * phpMyAdmin produces: \' \\ \n \r \0 \Z).
     */
    private function rowValues($row, $fields)
    {
        static $numeric = array(
            MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT,
            MYSQLI_TYPE_LONG, MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE,
            MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24, MYSQLI_TYPE_YEAR,
            MYSQLI_TYPE_NEWDECIMAL,
        );

        // Temporal types report charsetnr 63 (binary) in mysqli metadata
        // on both MySQL and MariaDB — they are NOT binary data and must be
        // handled before the charset test. YEAR stays in $numeric; mysqldump
        // emits it unquoted too.
        static $temporal = array(
            MYSQLI_TYPE_TIMESTAMP, MYSQLI_TYPE_DATE, MYSQLI_TYPE_TIME,
            MYSQLI_TYPE_DATETIME, MYSQLI_TYPE_NEWDATE,
        );

        $out = array();
        foreach ($row as $i => $val) {
            if ($val === null) {
                $out[] = 'NULL';
                continue;
            }

            $f = $fields[$i];

            if ($f->type === MYSQLI_TYPE_BIT) {
                // BIT comes back as a raw byte string; b'...' is the only
                // literal form that round-trips it.
                $bits = '';
                foreach (str_split($val) as $c) {
                    $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
                }
                $bits = ltrim($bits, '0');
                $out[] = ($bits === '') ? "b'0'" : "b'" . $bits . "'";
                continue;
            }

            if (in_array($f->type, $numeric, true)) {
                $out[] = (string)$val;
                continue;
            }

            if (in_array($f->type, $temporal, true)) {
                $out[] = "'" . mysqli_real_escape_string($this->conn, $val) . "'";
                continue;
            }

            if ($f->charsetnr === 63 || $f->type === MYSQLI_TYPE_GEOMETRY) {
                // binary/varbinary/blob/geometry — hex literal needs no
                // escaping and can't corrupt on re-import.
                $out[] = ($val === '') ? "''" : '0x' . bin2hex($val);
                continue;
            }

            $out[] = "'" . mysqli_real_escape_string($this->conn, $val) . "'";
        }

        return implode(', ', $out);
    }

    private function dumpView($view)
    {
        $res = mysqli_query($this->conn, 'SHOW CREATE VIEW ' . $this->q($view));
        if (!$res) {
            throw new Exception('SHOW CREATE VIEW failed for ' . $view);
        }
        $row = mysqli_fetch_assoc($res);
        $res->free();

        $create = $this->stripDefiner($row['Create View']);

        $this->out(
            "-- --------------------------------------------------------\n\n" .
            "--\n" .
            '-- Structure for view ' . $this->q($view) . "\n" .
            "--\n\n" .
            'DROP TABLE IF EXISTS ' . $this->q($view) . ";\n" .
            'DROP VIEW IF EXISTS ' . $this->q($view) . ";\n" .
            $create . ";\n\n"
        );
    }

    private function dumpTriggers()
    {
        $res = mysqli_query(
            $this->conn,
            'SHOW TRIGGERS FROM ' . $this->q($this->CI->db->database)
        );
        if (!$res) {
            return; // privilege lacking — don't fail the whole dump
        }
        $names = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $names[] = $row['Trigger'];
        }
        $res->free();

        foreach ($names as $name) {
            $r = mysqli_query($this->conn, 'SHOW CREATE TRIGGER ' . $this->q($name));
            if (!$r) {
                continue;
            }
            $row = mysqli_fetch_assoc($r);
            $r->free();
            $sql = $this->stripDefiner($row['SQL Original Statement']);

            $this->out(
                "--\n-- Trigger " . $this->q($name) . "\n--\n" .
                "DELIMITER ;;\n" .
                'DROP TRIGGER IF EXISTS ' . $this->q($name) . ";;\n" .
                $sql . ";;\n" .
                "DELIMITER ;\n\n"
            );
        }
    }

    private function dumpRoutines()
    {
        $rows = $this->CI->db->query(
            "SELECT ROUTINE_NAME, ROUTINE_TYPE
               FROM information_schema.ROUTINES
              WHERE ROUTINE_SCHEMA = ?
              ORDER BY ROUTINE_NAME",
            array($this->CI->db->database)
        )->result_array();

        foreach ($rows as $rt) {
            $type = strtoupper($rt['ROUTINE_TYPE']); // PROCEDURE | FUNCTION
            $r = mysqli_query($this->conn, 'SHOW CREATE ' . $type . ' ' . $this->q($rt['ROUTINE_NAME']));
            if (!$r) {
                continue;
            }
            $row = mysqli_fetch_assoc($r);
            $r->free();
            $sql = $this->stripDefiner($row['Create ' . ucfirst(strtolower($type))] ?? $row[array_keys($row)[2]]);

            $this->out(
                "--\n-- $type " . $this->q($rt['ROUTINE_NAME']) . "\n--\n" .
                "DELIMITER ;;\n" .
                'DROP ' . $type . ' IF EXISTS ' . $this->q($rt['ROUTINE_NAME']) . ";;\n" .
                $sql . ";;\n" .
                "DELIMITER ;\n\n"
            );
        }
    }

    private function dumpEvents()
    {
        $res = mysqli_query($this->conn, 'SHOW EVENTS FROM ' . $this->q($this->CI->db->database));
        if (!$res) {
            return;
        }
        $events = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $events[] = array($row['Name'], $row['Status']);
        }
        $res->free();

        foreach ($events as $ev) {
            $r = mysqli_query($this->conn, 'SHOW CREATE EVENT ' . $this->q($ev[0]));
            if (!$r) {
                continue;
            }
            $row = mysqli_fetch_assoc($r);
            $r->free();
            $sql = $this->stripDefiner($row['Create Event']);

            $this->out(
                "--\n-- Event " . $this->q($ev[0]) . "\n--\n" .
                "DELIMITER ;;\n" .
                'DROP EVENT IF EXISTS ' . $this->q($ev[0]) . ";;\n" .
                $sql . ";;\n" .
                "DELIMITER ;\n"
            );
            if (stripos($ev[1], 'DISABLE') === 0) {
                $this->out('ALTER EVENT ' . $this->q($ev[0]) . " DISABLE;\n");
            }
            $this->out("\n");
        }
    }

    /**
     * Remove DEFINER=`u`@`h` clauses so the dump imports under a different
     * account (e.g. cPanel user ≠ local root) without needing SUPER.
     */
    private function stripDefiner($sql)
    {
        return preg_replace('/DEFINER\s*=\s*(?:`[^`]*`|[^\s@]+)@(?:`[^`]*`|[^\s]+)\s*/', '', $sql);
    }
}

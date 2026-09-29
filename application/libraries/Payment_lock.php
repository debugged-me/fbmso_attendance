<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Serialises payment writes per student, across web and mobile cashiers.
 *
 * Saving a payment is read-then-write: check what the student still owes,
 * insert, then recompute their ledger total. Two cashiers doing that for the
 * same student at the same moment both see the old balance, so a fee could be
 * collected twice, and each recompute could miss the other's row. Holding a
 * named lock per student for the whole sequence makes the second cashier wait
 * a moment and then see the first one's payment. Other students are not
 * blocked.
 *
 * MariaDB named locks belong to the connection, not the transaction, and are
 * dropped if the connection closes -- a crashed request cannot leave one held.
 * That also covers redirect(): it exits without running `finally`, and the
 * lock goes when the request's connection closes a moment later.
 */
class Payment_lock
{
    /** Seconds a cashier waits for another save on the same student. */
    const TIMEOUT = 10;

    /** @var CI_Controller */
    protected $CI;

    /** Lock names this request currently holds. */
    protected $held = [];

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Take the lock for each student. An edit that moves a payment between
     * students needs both; names are taken in sorted order so two such edits
     * can never wait on each other.
     *
     * @param string|string[] $studentNumbers
     * @return bool FALSE if a lock could not be had within TIMEOUT.
     */
    public function acquire($studentNumbers)
    {
        $names = [];
        foreach ((array)$studentNumbers as $sn) {
            $sn = trim((string)$sn);
            if ($sn !== '') {
                $names[$this->nameFor($sn)] = true;
            }
        }
        $names = array_keys($names);
        sort($names);

        foreach ($names as $name) {
            $row = $this->CI->db->query('SELECT GET_LOCK(?, ?) AS got', [$name, self::TIMEOUT])->row();
            if (!$row || (int)$row->got !== 1) {
                $this->release();
                return false;
            }
            $this->held[] = $name;
        }

        return true;
    }

    public function release()
    {
        foreach (array_reverse($this->held) as $name) {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$name]);
        }
        $this->held = [];
    }

    // Named locks are server-wide, so the database name is folded in to keep
    // two sites on one server apart. Names are capped at 64 characters.
    protected function nameFor($studentNumber)
    {
        return 'fbmso_pay_' . sha1($this->CI->db->database . '|' . $studentNumber);
    }
}

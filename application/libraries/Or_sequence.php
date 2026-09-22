<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single allocator for O.R. numbers, shared by the web cashier and the mobile
 * app.
 *
 * Both used to derive the next number with
 * MAX(SUBSTRING_INDEX(ORNumber,'-',-1)) over paymentsaccounts. That cannot
 * support offline work: a cashier with no signal has to hand a real receipt
 * number across the desk, which means reserving numbers ahead of time, and a
 * reserved block is invisible to a MAX() scan — the web would immediately
 * re-issue the same numbers.
 *
 * Numbers therefore come from a counter table instead. A device reserves a
 * contiguous block while it still has signal and spends it offline; the web
 * takes one at a time from the same counter and can never collide with it.
 *
 * Unused numbers in an expired block leave gaps in the sequence. That is the
 * deliberate trade for offline receipts, and o_or_blocks records who held
 * every range so a gap can always be explained.
 */
class Or_sequence
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->ensure_schema();
    }

    // ─── Allocation ────────────────────────────────────────────────────────

    /**
     * Take the next single number for [$date] (Y-m-d). Used by the online
     * paths on both web and mobile.
     */
    public function next($date = '')
    {
        $block = $this->reserve($date, 1);
        return $this->format($block['prefix'], $block['start']);
    }

    /**
     * The number [next()] would hand out, WITHOUT taking it.
     *
     * Payment forms show the upcoming O.R. on every load; consuming there
     * would burn a number per page view and leave gaps everywhere.
     */
    public function peek($date = '')
    {
        $date   = $this->normalize_date($date);
        $prefix = date('ymd', strtotime($date));

        $row = $this->CI->db->from('o_or_sequence')
            ->where('date_prefix', $prefix)->limit(1)->get()->row();

        $next = $row !== null
            ? (int)$row->next_seq
            : $this->highest_existing($date) + 1;

        return $this->format($prefix, $next);
    }

    /**
     * Reserve [$count] consecutive numbers.
     *
     * @return array{prefix:string, start:int, end:int, date:string}
     */
    public function reserve($date = '', $count = 1, $deviceId = '', $username = '')
    {
        $date   = $this->normalize_date($date);
        $prefix = date('ymd', strtotime($date));
        $count  = max(1, (int)$count);

        $this->CI->db->trans_begin();
        try {
            // Seed from whatever is already in paymentsaccounts so an install
            // with history does not restart the sequence at 1.
            $this->CI->db->query(
                'INSERT IGNORE INTO `o_or_sequence` (`date_prefix`, `next_seq`) VALUES (?, ?)',
                [$prefix, $this->highest_existing($date) + 1]
            );

            $row = $this->CI->db->query(
                'SELECT `next_seq` FROM `o_or_sequence` WHERE `date_prefix` = ? FOR UPDATE',
                [$prefix]
            )->row();

            $start = (int)($row->next_seq ?? 1);
            $end   = $start + $count - 1;

            $this->CI->db->query(
                'UPDATE `o_or_sequence` SET `next_seq` = ? WHERE `date_prefix` = ?',
                [$end + 1, $prefix]
            );

            if ($deviceId !== '') {
                $this->CI->db->insert('o_or_blocks', [
                    'device_id'        => $deviceId,
                    'username'         => $username,
                    'date_prefix'      => $prefix,
                    'payment_date'     => $date,
                    'seq_start'        => $start,
                    'seq_end'          => $end,
                    'consumed_through' => $start - 1,
                    'issued_at'        => date('Y-m-d H:i:s'),
                ]);
            }

            if ($this->CI->db->trans_status() === false) {
                $this->CI->db->trans_rollback();
                throw new RuntimeException('Could not reserve O.R. numbers.');
            }
            $this->CI->db->trans_commit();

            return ['prefix' => $prefix, 'start' => $start, 'end' => $end, 'date' => $date];
        } catch (Throwable $e) {
            $this->CI->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Is [$orNumber] inside a block this device still holds, and not already
     * spent? Stops a device inventing numbers it was never given.
     */
    public function owns($deviceId, $orNumber)
    {
        $deviceId = trim((string)$deviceId);
        $parsed   = $this->parse($orNumber);
        if ($deviceId === '' || $parsed === null) return false;

        $row = $this->CI->db->from('o_or_blocks')
            ->where('device_id', $deviceId)
            ->where('date_prefix', $parsed['prefix'])
            ->where('seq_start <=', $parsed['sequence'])
            ->where('seq_end >=', $parsed['sequence'])
            ->limit(1)->get()->row();

        return $row !== null;
    }

    /** Record how far into its block a device has spent. */
    public function mark_consumed($deviceId, $orNumber)
    {
        $parsed = $this->parse($orNumber);
        if ($parsed === null || trim((string)$deviceId) === '') return;

        $this->CI->db->query(
            'UPDATE `o_or_blocks`
                SET `consumed_through` = GREATEST(`consumed_through`, ?)
              WHERE `device_id` = ? AND `date_prefix` = ?
                AND `seq_start` <= ? AND `seq_end` >= ?',
            [$parsed['sequence'], $deviceId, $parsed['prefix'],
             $parsed['sequence'], $parsed['sequence']]
        );
    }

    // ─── Formatting ────────────────────────────────────────────────────────

    public function format($prefix, $sequence)
    {
        return sprintf('%s-%04d', $prefix, (int)$sequence);
    }

    /** @return array{prefix:string, sequence:int}|null */
    public function parse($orNumber)
    {
        $orNumber = trim((string)$orNumber);
        if (!preg_match('/^(\d{6})-(\d+)$/', $orNumber, $m)) return null;
        return ['prefix' => $m[1], 'sequence' => (int)$m[2]];
    }

    // ─── Internals ─────────────────────────────────────────────────────────

    /**
     * Highest sequence already stored for this date, so the counter starts
     * above existing receipts rather than colliding with them.
     */
    private function highest_existing($date)
    {
        $row = $this->CI->db
            ->select("MAX(CAST(SUBSTRING_INDEX(ORNumber, '-', -1) AS UNSIGNED)) AS max_sequence", false)
            ->from('paymentsaccounts')
            ->where('PDate', $date)
            ->get()->row();

        return (int)($row->max_sequence ?? 0);
    }

    private function normalize_date($date)
    {
        $date = trim((string)$date);
        $ts = $date !== '' ? strtotime($date) : false;
        if ($ts === false) {
            return (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        }
        return date('Y-m-d', $ts);
    }

    private function ensure_schema()
    {
        $this->CI->db->query(
            'CREATE TABLE IF NOT EXISTS `o_or_sequence` (
               `date_prefix` CHAR(6) NOT NULL,
               `next_seq` INT UNSIGNED NOT NULL DEFAULT 1,
               PRIMARY KEY (`date_prefix`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->CI->db->query(
            'CREATE TABLE IF NOT EXISTS `o_or_blocks` (
               `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
               `device_id` VARCHAR(64) NOT NULL,
               `username` VARCHAR(64) NOT NULL DEFAULT \'\',
               `date_prefix` CHAR(6) NOT NULL,
               `payment_date` DATE NOT NULL,
               `seq_start` INT UNSIGNED NOT NULL,
               `seq_end` INT UNSIGNED NOT NULL,
               `consumed_through` INT UNSIGNED NOT NULL DEFAULT 0,
               `issued_at` DATETIME NOT NULL,
               PRIMARY KEY (`id`),
               KEY `idx_device_prefix` (`device_id`, `date_prefix`),
               KEY `idx_range` (`date_prefix`, `seq_start`, `seq_end`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

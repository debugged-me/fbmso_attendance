<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single source of truth for whether an activity accepts check-ins.
 *
 * Two layers combine:
 *   1. MANUAL  — `activities.status` ('draft'|'open'|'closed'|'archived').
 *                `activities.is_open` is kept mirrored (1 only when status='open')
 *                so older clients that only know is_open still behave correctly.
 *   2. AUTO    — when meta.auto_close is on (default), check-ins are only accepted
 *                inside [start_at - grace, end_at + grace]. end_at falls back to
 *                23:59:59 on the activity date when it is NULL (all-day activity).
 *
 * Both layers must pass for the activity to be open. Every gate (web check-in,
 * web scanner, mobile self check-in, mobile scanner) routes through here.
 */

if (!defined('ACTIVITY_DEFAULT_GRACE_MINUTES')) {
    define('ACTIVITY_DEFAULT_GRACE_MINUTES', 15);
}

if (!function_exists('activity_manual_statuses')) {
    /** Allowed values of `activities.status`, in the order they should appear in a picker. */
    function activity_manual_statuses(): array
    {
        return [
            'draft'    => 'Draft',
            'open'     => 'Open',
            'closed'   => 'Closed',
            'archived' => 'Archived',
        ];
    }
}

if (!function_exists('activity_normalize_status')) {
    /** Coerce arbitrary input to a valid enum value. Unknown input falls back to $fallback. */
    function activity_normalize_status($status, string $fallback = 'open'): string
    {
        $status = strtolower(trim((string)$status));
        return array_key_exists($status, activity_manual_statuses()) ? $status : $fallback;
    }
}

if (!function_exists('activity_meta_decode')) {
    /** Decode the `meta` longtext column to an array; always returns an array. */
    function activity_meta_decode($meta): array
    {
        if (is_array($meta))  return $meta;
        if (is_object($meta)) return (array)$meta;
        $meta = trim((string)$meta);
        if ($meta === '') return [];
        $decoded = json_decode($meta, true);
        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
}

if (!function_exists('activity_normalize_grace')) {
    /** Clamp grace minutes to a sane 0..1440 range. */
    function activity_normalize_grace($minutes): int
    {
        $m = (int)$minutes;
        if ($m < 0)    $m = 0;
        if ($m > 1440) $m = 1440;
        return $m;
    }
}

if (!function_exists('activity_auto_close_settings')) {
    /**
     * Read the auto-close knobs out of an activity's meta JSON.
     *
     * @return array{auto_close:bool, grace_minutes:int}
     */
    function activity_auto_close_settings($metaOrRow): array
    {
        $meta = is_object($metaOrRow) && isset($metaOrRow->meta)
            ? activity_meta_decode($metaOrRow->meta)
            : activity_meta_decode($metaOrRow);

        // Absent key = on. Existing rows created before this feature auto-close by default.
        $auto = array_key_exists('auto_close', $meta)
            ? filter_var($meta['auto_close'], FILTER_VALIDATE_BOOLEAN)
            : true;

        $grace = array_key_exists('grace_minutes', $meta)
            ? activity_normalize_grace($meta['grace_minutes'])
            : ACTIVITY_DEFAULT_GRACE_MINUTES;

        return ['auto_close' => (bool)$auto, 'grace_minutes' => $grace];
    }
}

if (!function_exists('activity_checkin_window')) {
    /**
     * Resolve the effective check-in window for a row, grace already applied.
     *
     * @return array{start:?int, end:?int} Unix timestamps; null when unbounded.
     */
    function activity_checkin_window($row): array
    {
        $cfg   = activity_auto_close_settings($row);
        $grace = $cfg['grace_minutes'] * 60;

        $startRaw = trim((string)($row->start_at ?? ''));
        $endRaw   = trim((string)($row->end_at ?? ''));
        $dateRaw  = trim((string)($row->activity_date ?? ''));

        $start = ($startRaw !== '' && $startRaw !== '0000-00-00 00:00:00') ? strtotime($startRaw) : null;

        if ($endRaw !== '' && $endRaw !== '0000-00-00 00:00:00') {
            $end = strtotime($endRaw);
        } elseif ($dateRaw !== '' && $dateRaw !== '0000-00-00') {
            // No end time recorded → the activity runs to the end of its own day.
            $end = strtotime(substr($dateRaw, 0, 10) . ' 23:59:59');
        } elseif ($start !== null) {
            $end = strtotime(date('Y-m-d', $start) . ' 23:59:59');
        } else {
            $end = null;
        }

        // A misconfigured end before start would close the activity forever.
        if ($start !== null && $end !== null && $end < $start) {
            $end = strtotime(date('Y-m-d', $start) . ' 23:59:59');
        }

        return [
            'start' => $start !== null && $start !== false ? $start - $grace : null,
            'end'   => $end   !== null && $end   !== false ? $end   + $grace : null,
        ];
    }
}

if (!function_exists('activity_state')) {
    /**
     * Compute the full open/closed picture for an activity row.
     *
     * @param object|array $row Must carry status, is_open, start_at, end_at, activity_date, meta.
     * @param int|null     $now Unix timestamp to evaluate against (defaults to time()).
     *
     * @return array{
     *   state:string, label:string, is_open:bool, reason:?string,
     *   manual_status:string, manual_open:bool, auto_close:bool, grace_minutes:int,
     *   window_start:?string, window_end:?string
     * }
     */
    function activity_state($row, ?int $now = null): array
    {
        $row = is_array($row) ? (object)$row : $row;
        $now = $now ?? time();

        $manualStatus = activity_normalize_status($row->status ?? 'open');
        // A legacy row (or an older mobile client) may have flipped is_open without
        // touching status — honour that as a manual close.
        $isOpenFlag   = array_key_exists('is_open', (array)$row) ? (int)$row->is_open : 1;
        $manualOpen   = ($manualStatus === 'open' && $isOpenFlag === 1);

        $cfg    = activity_auto_close_settings($row);
        $window = activity_checkin_window($row);

        $out = [
            'state'         => 'open',
            'label'         => 'Open',
            'is_open'       => true,
            'reason'        => null,
            'manual_status' => $manualStatus,
            'manual_open'   => $manualOpen,
            'auto_close'    => $cfg['auto_close'],
            'grace_minutes' => $cfg['grace_minutes'],
            'window_start'  => $window['start'] !== null ? date('Y-m-d H:i:s', $window['start']) : null,
            'window_end'    => $window['end']   !== null ? date('Y-m-d H:i:s', $window['end'])   : null,
        ];

        // ── Layer 1: manual ────────────────────────────────────────────────
        if (!$manualOpen) {
            $labels = activity_manual_statuses();
            // status='open' but is_open=0 reads as a plain manual close.
            $state  = ($manualStatus === 'open') ? 'closed' : $manualStatus;
            $out['state']   = $state;
            $out['label']   = $labels[$state] ?? 'Closed';
            $out['is_open'] = false;
            $out['reason']  = [
                'draft'    => 'This activity is still a draft and is not accepting check-ins yet.',
                'archived' => 'This activity has been archived.',
            ][$state] ?? 'This activity has been closed for check-ins.';
            return $out;
        }

        // ── Layer 2: time window ───────────────────────────────────────────
        if (!$cfg['auto_close']) {
            $out['window_start'] = null;
            $out['window_end']   = null;
            return $out;
        }

        if ($window['start'] !== null && $now < $window['start']) {
            $out['state']   = 'scheduled';
            $out['label']   = 'Scheduled';
            $out['is_open'] = false;
            $out['reason']  = 'Check-in for this activity opens on '
                . date('M j, Y \a\t g:i A', $window['start']) . '.';
            return $out;
        }

        if ($window['end'] !== null && $now > $window['end']) {
            $out['state']   = 'ended';
            $out['label']   = 'Ended';
            $out['is_open'] = false;
            $out['reason']  = 'Check-in for this activity closed on '
                . date('M j, Y \a\t g:i A', $window['end']) . '.';
            return $out;
        }

        return $out;
    }
}

if (!function_exists('activity_is_open')) {
    /** Shorthand: is this activity accepting check-ins right now? */
    function activity_is_open($row, ?int $now = null): bool
    {
        return activity_state($row, $now)['is_open'];
    }
}

if (!function_exists('activity_state_badge_class')) {
    /** Bootstrap 4 badge modifier for a state key (web list view). */
    function activity_state_badge_class(string $state): string
    {
        switch ($state) {
            case 'open':      return 'badge-success';
            case 'scheduled': return 'badge-info';
            case 'ended':     return 'badge-warning';
            case 'closed':    return 'badge-secondary';
            case 'draft':     return 'badge-light';
            case 'archived':  return 'badge-dark';
            default:          return 'badge-light';
        }
    }
}

if (!function_exists('activity_meta_merge_autoclose')) {
    /**
     * Fold the auto-close knobs into an activity's meta JSON without disturbing
     * anything else already in there (notably the `sessions` windows).
     *
     * @param string|array|null $metaJson Existing meta (raw JSON string or array).
     * @return string JSON ready to store in `activities`.`meta`.
     */
    function activity_meta_merge_autoclose($metaJson, bool $autoClose, $graceMinutes): string
    {
        $meta = activity_meta_decode($metaJson);
        $meta['auto_close']    = $autoClose;
        $meta['grace_minutes'] = activity_normalize_grace($graceMinutes);
        return json_encode($meta, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * How far back a client-supplied scan timestamp may reach. Offline scans queued
 * in the mobile outbox can sync long after the fact; without this the auto-close
 * window would reject attendance that was genuinely taken inside it.
 */
if (!defined('ACTIVITY_CLIENT_TIME_MAX_AGE_HOURS')) {
    define('ACTIVITY_CLIENT_TIME_MAX_AGE_HOURS', 48);
}

if (!function_exists('activity_resolve_scan_time')) {
    /**
     * Decide which timestamp the auto-close window should be judged against.
     *
     * Returns the client's own scan time when it is plausible — in the past, and
     * no older than ACTIVITY_CLIENT_TIME_MAX_AGE_HOURS — otherwise server time.
     * NOTE: this trusts the device clock within that bound, which is what makes
     * offline attendance work; it is not a defence against a forged timestamp.
     *
     * @param mixed $raw ISO-8601 string, "Y-m-d H:i:s", or epoch millis/seconds.
     */
    function activity_resolve_scan_time($raw, ?int $now = null): int
    {
        $now = $now ?? time();
        if ($raw === null || $raw === '' || $raw === false) return $now;

        if (is_numeric($raw)) {
            $n = (float)$raw;
            // Heuristic: anything past year ~2286 in seconds is milliseconds.
            $ts = $n > 100000000000 ? (int)round($n / 1000) : (int)$n;
        } else {
            $ts = strtotime((string)$raw);
            if ($ts === false) return $now;
        }

        if ($ts <= 0)   return $now;
        if ($ts > $now) return $now;  // never let a future clock widen the window
        if ($ts < $now - (ACTIVITY_CLIENT_TIME_MAX_AGE_HOURS * 3600)) return $now;

        return $ts;
    }
}

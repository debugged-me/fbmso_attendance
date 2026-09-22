<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single source of truth for whether an activity accepts check-ins.
 *
 * Two layers combine:
 *   1. MANUAL  — `activities.status` ('draft'|'open'|'closed'|'archived').
 *                `activities.is_open` is kept mirrored (1 only when status='open')
 *                so older clients that only know is_open still behave correctly.
 *   2. AUTO    — when meta.auto_close is explicitly on, check-ins are only accepted
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

        // Auto-close is opt-in. Older activities have session metadata but no
        // `auto_close` key; treating those rows as opted in made every legacy
        // activity appear closed in the scanner after its scheduled time.
        $auto = array_key_exists('auto_close', $meta)
            ? filter_var($meta['auto_close'], FILTER_VALIDATE_BOOLEAN)
            : false;

        $grace = array_key_exists('grace_minutes', $meta)
            ? activity_normalize_grace($meta['grace_minutes'])
            : ACTIVITY_DEFAULT_GRACE_MINUTES;

        return ['auto_close' => (bool)$auto, 'grace_minutes' => $grace];
    }
}

if (!function_exists('attendance_normalize_student_qr')) {
    /**
     * Extract a student QR token from the payload formats used by the web app,
     * native app, printed cards, and older scanner builds.
     *
     * Tokens remain high-entropy hexadecimal values. This only makes their
     * transport tolerant of URL encoding, a UTF-8 BOM, JSON wrappers, full
     * URLs, and the historic "activity|token" format.
     */
    function attendance_normalize_student_qr($raw): string
    {
        if (!is_scalar($raw)) return '';

        $original = (string)$raw;
        $value = preg_replace('/^(?:\xEF\xBB\xBF|\x{FEFF})+/u', '', $original);
        $value = trim((string)$value);
        if ($value === '') return '';

        // Some QR tools wrap the value in a tiny JSON object.
        if ($value[0] === '{') {
            $json = json_decode($value, true);
            if (is_array($json)) {
                foreach (['token', 'qr_token', 'student_token'] as $key) {
                    if (!empty($json[$key]) && is_scalar($json[$key])) {
                        $value = trim((string)$json[$key]);
                        break;
                    }
                }
            }
        }

        // Full URLs and query-string-only payloads.
        $query = parse_url($value, PHP_URL_QUERY);
        if ($query === null && strpos($value, '=') !== false) {
            $query = ltrim($value, '?');
        }
        if (is_string($query) && $query !== '') {
            parse_str(html_entity_decode($query, ENT_QUOTES, 'UTF-8'), $params);
            foreach (['token', 'qr_token', 'student_token'] as $key) {
                if (!empty($params[$key]) && is_scalar($params[$key])) {
                    $value = trim((string)$params[$key]);
                    break;
                }
            }
        }

        // Historic payload: "activity-id|student-token".
        if (strpos($value, '|') !== false) {
            foreach (array_reverse(explode('|', $value)) as $part) {
                $part = trim($part);
                if (preg_match('/^[a-f0-9]{32}(?:[a-f0-9]{32})?$/i', $part)) {
                    $value = $part;
                    break;
                }
            }
        }

        $value = rawurldecode(trim($value));
        if (preg_match('/^[a-f0-9]{32}(?:[a-f0-9]{32})?$/i', $value)) {
            return strtolower($value);
        }

        // Last resort for labelled/printed payloads. Hex boundaries prevent
        // silently accepting a fragment of a longer malformed token.
        if (preg_match('/(?<![a-f0-9])([a-f0-9]{64}|[a-f0-9]{32})(?![a-f0-9])/i', $original, $match)) {
            return strtolower($match[1]);
        }

        return '';
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

/** Clock-skew tolerance when judging a client-supplied scan timestamp. */
if (!defined('ACTIVITY_CLIENT_TIME_SKEW_SECONDS')) {
    define('ACTIVITY_CLIENT_TIME_SKEW_SECONDS', 300);
}

if (!function_exists('activity_parse_client_time')) {
    /** Parse an epoch (seconds or milliseconds) or date string. NULL when unusable. */
    function activity_parse_client_time($raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) return null;

        if (is_numeric($raw)) {
            $n = (float)$raw;
            // Heuristic: anything past year ~2286 in seconds is milliseconds.
            $ts = $n > 100000000000 ? (int)round($n / 1000) : (int)$n;
        } else {
            $ts = strtotime((string)$raw);
            if ($ts === false) return null;
        }

        return $ts > 0 ? $ts : null;
    }
}

if (!function_exists('activity_clamp_scan_time')) {
    /**
     * Resolve the timestamp an offline-queued scan should be *recorded* at.
     *
     * Replaces activity_resolve_scan_time's rolling 48h allowance, which
     * silently re-stamped an older scan to server now — writing a morning scan
     * into whatever session happened to be current when the queue flushed.
     *
     * A backdated scan is instead validated against the activity's own window:
     * accepted as-is inside it, rejected outside it. That is also the answer to
     * a tampered device clock, since a forged time can only place a scan inside
     * a window where the scan was already permitted.
     *
     * @return array{ts:int, source:string, ok:bool, reason:string}
     */
    function activity_clamp_scan_time($row, $raw, ?int $now = null): array
    {
        $now  = $now ?? time();
        $skew = ACTIVITY_CLIENT_TIME_SKEW_SECONDS;
        $ts   = activity_parse_client_time($raw);

        if ($ts === null) {
            return ['ts' => $now, 'source' => 'server', 'ok' => true, 'reason' => ''];
        }

        // A future clock means skew or tampering. The scan is reaching the
        // server now, so now is the honest answer.
        if ($ts > $now) {
            return ['ts' => $now, 'source' => 'server', 'ok' => true, 'reason' => ''];
        }

        // Live scan — client and server agree. Leave the window to activity_state.
        if ($ts >= $now - $skew) {
            return ['ts' => $ts, 'source' => 'client', 'ok' => true, 'reason' => ''];
        }

        // Genuinely backdated: this came out of an offline queue.
        $window = activity_checkin_window($row);

        if ($window['start'] !== null && $ts < $window['start'] - $skew) {
            return [
                'ts'     => $ts,
                'source' => 'client',
                'ok'     => false,
                'reason' => 'This queued scan is dated ' . date('M j, Y \a\t g:i A', $ts)
                    . ', before check-in opened for this activity.',
            ];
        }

        if ($window['end'] !== null && $ts > $window['end'] + $skew) {
            return [
                'ts'     => $ts,
                'source' => 'client',
                'ok'     => false,
                'reason' => 'This queued scan is dated ' . date('M j, Y \a\t g:i A', $ts)
                    . ', after check-in closed for this activity.',
            ];
        }

        return ['ts' => $ts, 'source' => 'client', 'ok' => true, 'reason' => ''];
    }
}

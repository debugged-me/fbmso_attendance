<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shared helpers for the security screens.
 * Kept in one place so the device line and risk badge read identically
 * on every page -- an operator should not have to re-learn the format.
 */

if (!function_exists('sec_e')) {
    function sec_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('sec_device')) {
    /** One readable line. Prints only what the browser actually revealed. */
    function sec_device(array $r, $withIp = true)
    {
        $bits  = array();
        $name  = trim((string)($r['device_marketing_name'] ?? ''));
        $model = trim((string)($r['device_model_code'] ?? ''));

        if ($name !== '' && $model !== '')      $bits[] = $name . ' (' . $model . ')';
        elseif ($name !== '')                    $bits[] = $name;
        elseif ($model !== '')                   $bits[] = $model;

        $os = trim((string)($r['operating_system'] ?? ''));
        $ov = trim((string)($r['os_version'] ?? ''));
        if ($os !== '') $bits[] = $ov !== '' ? $os . ' ' . $ov : $os;

        if (!empty($r['browser'])) $bits[] = (string)$r['browser'];

        if (!$bits && !empty($r['device_type'])) $bits[] = $r['device_type'] . ' device';
        if (!$bits) $bits[] = 'Unidentified device';

        $out = implode(' &middot; ', array_map('sec_e', $bits));
        $ip  = trim((string)($r['ip_address'] ?? $r['last_ip'] ?? ''));

        if ($withIp && $ip !== '') {
            $out .= '<br><small class="text-muted">' . sec_e($ip) . '</small>';
        }

        return $out;
    }
}

if (!function_exists('sec_risk_badge')) {
    function sec_risk_badge($level, $score = null)
    {
        $map = array(
            'CRITICAL' => 'danger',
            'HIGH'     => 'warning',
            'MEDIUM'   => 'info',
            'LOW'      => 'secondary',
        );
        $cls = $map[strtoupper((string)$level)] ?? 'secondary';
        $txt = sec_e($level ?: 'LOW') . ($score !== null ? ' ' . (int)$score : '');

        return '<span class="badge badge-' . $cls . '">' . $txt . '</span>';
    }
}

if (!function_exists('sec_ago')) {
    /** "3 minutes ago" reads faster than a timestamp when scanning a list. */
    function sec_ago($datetime)
    {
        $t = strtotime((string)$datetime);
        if (!$t) return '-';

        $d = time() - $t;
        if ($d < 60)    return 'just now';
        if ($d < 3600)  return floor($d / 60) . ' min ago';
        if ($d < 86400) return floor($d / 3600) . ' hr ago';

        return floor($d / 86400) . ' day' . (floor($d / 86400) === 1 ? '' : 's') . ' ago';
    }
}

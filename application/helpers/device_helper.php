<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Turns a raw User-Agent string into something a person can read:
 * device type, operating system and browser. Used by the audit trail and
 * the security screens so a device reads the same everywhere.
 */

if (!function_exists('device_summary')) {
    /** @return array{type:string, os:string, browser:string}|null */
    function device_summary($userAgent)
    {
        $userAgent = trim((string)$userAgent);
        if ($userAgent === '') return null;

        $type = preg_match('/iPad|Tablet/i', $userAgent)
            ? 'Tablet'
            : (preg_match('/Mobile|Android|iPhone/i', $userAgent) ? 'Mobile' : 'Desktop');

        $browser = 'Unknown browser';
        if (preg_match('/Edg\/([\d.]+)/', $userAgent, $match)) {
            $browser = 'Microsoft Edge ' . $match[1];
        } elseif (preg_match('/OPR\/([\d.]+)/', $userAgent, $match)) {
            $browser = 'Opera ' . $match[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/', $userAgent, $match)) {
            $browser = 'Chrome ' . $match[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/', $userAgent, $match)) {
            $browser = 'Firefox ' . $match[1];
        } elseif (preg_match('/Version\/([\d.]+).*Safari\//', $userAgent, $match)) {
            $browser = 'Safari ' . $match[1];
        } elseif (preg_match('/^Dart\//', $userAgent) || stripos($userAgent, 'okhttp') !== false) {
            $browser = 'Mobile app';
        }

        $os = 'Unknown operating system';
        if (preg_match('/Android\s+([\d.]+)/i', $userAgent, $match)) {
            $os = 'Android ' . $match[1];
        } elseif (preg_match('/iPad.*OS\s+([\d_]+)/i', $userAgent, $match)) {
            $os = 'iPadOS ' . str_replace('_', '.', $match[1]);
        } elseif (preg_match('/iPhone.*OS\s+([\d_]+)/i', $userAgent, $match)) {
            $os = 'iOS ' . str_replace('_', '.', $match[1]);
        } elseif (preg_match('/Mac OS X\s+([\d_\.]+)/i', $userAgent, $match)) {
            $os = 'macOS ' . str_replace('_', '.', $match[1]);
        } elseif (preg_match('/Windows NT\s+([\d.]+)/i', $userAgent, $match)) {
            $windowsVersions = array('10.0' => '10 or 11', '6.3' => '8.1', '6.2' => '8', '6.1' => '7');
            $os = 'Windows ' . ($windowsVersions[$match[1]] ?? $match[1]);
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        }

        return array('type' => $type, 'os' => $os, 'browser' => $browser);
    }
}

if (!function_exists('device_label')) {
    /** Short one-liner such as "Chrome 128 · macOS 10.15". */
    function device_label($userAgent)
    {
        $device = device_summary($userAgent);
        if ($device === null) return '';
        $browser = preg_replace('/^(.+?\s\d+)(\.[\d.]+)?$/', '$1', $device['browser']);
        $os = preg_replace('/^(.+?\s\d+(\.\d+)?)(\.[\d.]+)?$/', '$1', $device['os']);
        return $browser . ' · ' . $os;
    }
}

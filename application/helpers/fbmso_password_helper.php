<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Central password hashing/verification for FBMSO.
 *
 * Accounts were historically stored as unsalted sha1($raw) (40 hex chars).
 * Everything now writes bcrypt, and legacy sha1 hashes are transparently
 * upgraded the next time the owner successfully signs in.
 *
 * Never call sha1() on a password outside this helper.
 */

// Work factor. ~110ms locally at 11; raise once the production host is measured.
if (!defined('FBMSO_PWD_COST')) {
    define('FBMSO_PWD_COST', 11);
}

// bcrypt silently truncates past 72 bytes and stops at a NUL byte. Reject
// rather than accept a password whose tail is ignored.
if (!defined('FBMSO_PWD_MAX_BYTES')) {
    define('FBMSO_PWD_MAX_BYTES', 72);
}

if (!function_exists('fbmso_password_hash')) {
    /**
     * Hash a raw password for storage. Returns '' when the input cannot be
     * safely hashed, so callers must check before writing to o_users.
     */
    function fbmso_password_hash($raw)
    {
        $raw = (string)$raw;

        if ($raw === '' || strlen($raw) > FBMSO_PWD_MAX_BYTES || strpos($raw, "\0") !== false) {
            return '';
        }

        $hash = password_hash($raw, PASSWORD_BCRYPT, ['cost' => FBMSO_PWD_COST]);

        return is_string($hash) ? $hash : '';
    }
}

if (!function_exists('fbmso_password_is_legacy')) {
    /** TRUE for the old unsalted sha1 hex digests. */
    function fbmso_password_is_legacy($stored)
    {
        $stored = (string)$stored;

        return strlen($stored) === 40 && ctype_xdigit($stored);
    }
}

if (!function_exists('fbmso_password_verify')) {
    /**
     * Verify a raw password against either a bcrypt hash or a legacy sha1 one.
     * Comparisons are constant-time on both paths.
     */
    function fbmso_password_verify($raw, $stored)
    {
        $raw    = (string)$raw;
        $stored = (string)$stored;

        if ($raw === '' || $stored === '') {
            return false;
        }

        if (fbmso_password_is_legacy($stored)) {
            return hash_equals(strtolower($stored), sha1($raw));
        }

        if (strlen($raw) > FBMSO_PWD_MAX_BYTES || strpos($raw, "\0") !== false) {
            return false;
        }

        return password_verify($raw, $stored);
    }
}

if (!function_exists('fbmso_password_needs_rehash')) {
    /** TRUE when the stored hash is legacy sha1 or below the current cost. */
    function fbmso_password_needs_rehash($stored)
    {
        $stored = (string)$stored;

        if ($stored === '' || fbmso_password_is_legacy($stored)) {
            return true;
        }

        return password_needs_rehash($stored, PASSWORD_BCRYPT, ['cost' => FBMSO_PWD_COST]);
    }
}

if (!function_exists('fbmso_password_upgrade')) {
    /**
     * Re-hash a just-verified password to the current algorithm/cost.
     * Called on the success path of every login; failures are non-fatal
     * because the user is already authenticated by this point.
     *
     * @return bool TRUE when the stored hash was actually rewritten.
     */
    function fbmso_password_upgrade($username, $raw, $stored)
    {
        if (!fbmso_password_needs_rehash($stored)) {
            return false;
        }

        $fresh = fbmso_password_hash($raw);
        if ($fresh === '') {
            return false;
        }

        $CI =& get_instance();
        $CI->db->where('username', $username)->update('o_users', ['password' => $fresh]);

        return $CI->db->affected_rows() > 0;
    }
}

if (!function_exists('fbmso_password_fingerprint')) {
    /**
     * Non-reversible, comparable fingerprint of a login attempt.
     *
     * Replaces the old reversible AES ciphertext in login_logs. Identical
     * passwords still produce identical fingerprints, so an investigator can
     * still spot one credential sprayed across many accounts -- which is how
     * the 2026-08-28 incident was traced -- but the value cannot be turned
     * back into a password, and the pepper puts it out of reach of offline
     * dictionary attacks by anyone who only has the database.
     */
    function fbmso_password_fingerprint($raw)
    {
        $raw = (string)$raw;

        if ($raw === '') {
            return null;
        }

        $pepper = (string)config_item('login_attempt_pepper');
        if ($pepper === '') {
            // No pepper configured: record nothing rather than store a
            // freely crackable digest of a real password.
            return null;
        }

        return hash_hmac('sha256', $raw, $pepper);
    }
}

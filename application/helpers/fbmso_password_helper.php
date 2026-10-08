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

if (!function_exists('fbmso_login_clean')) {
    /**
     * Strip what copy-paste adds around a typed credential: the trailing
     * space a phone's text selection takes along, a non-breaking space or a
     * zero-width character from an email client. Used for sign-in on web and
     * mobile alike, so the same credential works in both.
     */
    function fbmso_login_clean($value)
    {
        $value = str_replace(["\xc2\xa0", "\xe2\x80\x8b"], ' ', (string)$value);
        $value = (string)preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $value);

        return (string)preg_replace('/^\s+|\s+$/u', '', $value);
    }
}

if (!function_exists('fbmso_password_match_typed')) {
    /**
     * Verify a password a person typed or pasted: exactly as given first,
     * then without the whitespace copy-paste adds around it. A temporary
     * password copied out of Gmail on a phone often carries a trailing or
     * non-breaking space. Trying the exact form first keeps a password that
     * really was set with edge spaces working.
     *
     * Every place that checks a typed password (sign-in, "current password"
     * on web and mobile) goes through here, so a password that signs in is
     * never then rejected as "wrong current password".
     *
     * @return string|null The form that matched, or NULL.
     */
    function fbmso_password_match_typed($typed, $stored)
    {
        $typed = (string)$typed;

        foreach (array_unique([$typed, fbmso_login_clean($typed)]) as $attempt) {
            if ($attempt !== '' && fbmso_password_verify($attempt, $stored)) {
                return $attempt;
            }
        }

        return null;
    }
}

if (!function_exists('fbmso_login_clean_username')) {
    /** fbmso_login_clean() plus collapsing inner runs of whitespace. */
    function fbmso_login_clean_username($value)
    {
        return trim((string)preg_replace('/\s+/u', ' ', fbmso_login_clean($value)));
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

if (!function_exists('fbmso_recovery_code')) {
    /**
     * Generate a one-time account recovery code in display form,
     * e.g. 'KQ3M-7T2P-WN9F'. 12 characters from a 32-symbol alphabet with
     * the look-alikes removed (0/O, 1/I/L) — about 60 bits of entropy.
     *
     * Only the bcrypt hash of the NORMALIZED form (fbmso_recovery_normalize)
     * is ever stored; the dashes exist only for humans.
     */
    function fbmso_recovery_code()
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $raw      = '';
        for ($i = 0; $i < 12; $i++) {
            $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
    }
}

if (!function_exists('fbmso_recovery_normalize')) {
    /**
     * Canonical form of a recovery code for hashing/comparison: uppercase,
     * dashes and stray whitespace removed. What the user types ('kq3m-7t2p
     * wn9f') and what the file prints ('KQ3M-7T2P-WN9F') normalize to the
     * same value, so verification is format-tolerant but value-exact.
     */
    function fbmso_recovery_normalize($code)
    {
        return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', (string)$code));
    }
}

if (!function_exists('fbmso_session_reference')) {
    /**
     * Stable, non-reversible reference to a session.
     *
     * Used by both the audit trail and the session registry so events and
     * sessions can be correlated without either ever storing a real session
     * id -- a stored session id is a stored credential.
     *
     * @param string|null $sessionId Defaults to the current session.
     */
    function fbmso_session_reference($sessionId = null)
    {
        $id = $sessionId !== null ? (string)$sessionId : (string)session_id();

        if ($id === '') {
            return null;
        }

        $pepper = (string)config_item('login_attempt_pepper');

        return hash_hmac('sha256', $id, $pepper !== '' ? $pepper : 'fbmso');
    }
}

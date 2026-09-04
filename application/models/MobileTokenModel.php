<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mobile bearer-token store.
 *
 * Tokens are random 64-char hex strings. Only the sha256 hash is persisted
 * (o_mobile_tokens.token_hash), mirroring how a server stores a password
 * hash — a DB leak never yields usable tokens.
 */
class MobileTokenModel extends CI_Model
{
    private const TABLE = 'o_mobile_tokens';
    private const TTL_SECONDS = 604800; // 7 days

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /** Issue a token for a user and return the raw token string. */
    public function issue(string $username, array $device = []): string
    {
        $raw = bin2hex(random_bytes(32)); // 64 hex chars
        $now = time();

        $this->db->insert(self::TABLE, [
            'username'      => $username,
            'token_hash'    => hash('sha256', $raw),
            'device_id'     => $device['device_id']    ?? null,
            'device_name'   => $device['device_name']  ?? null,
            'platform'      => $device['platform']     ?? null,
            'issued_at'     => $now,
            'expires_at'    => $now + self::TTL_SECONDS,
            'revoked'       => 0,
            'last_seen_at'  => $now,
        ]);

        return $raw;
    }

    /** Look up a raw token. Returns the row array (with username) or null. */
    public function lookup(string $rawToken): ?array
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            return null;
        }

        $hash = hash('sha256', $rawToken);
        $row = $this->db->get_where(self::TABLE, ['token_hash' => $hash], 1)->row_array();

        if (!$row) {
            return null;
        }

        // Expired or revoked → treat as invalid.
        if ((int)$row['revoked'] === 1 || (int)$row['expires_at'] < time()) {
            return null;
        }

        // Touch last_seen (best effort, non-fatal).
        try {
            $this->db->where('id', (int)$row['id'])->update(self::TABLE, ['last_seen_at' => time()]);
        } catch (Throwable $e) {
            // ignore
        }

        return $row;
    }

    /** Revoke a raw token (logout). */
    public function revoke(string $rawToken): void
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            return;
        }
        $hash = hash('sha256', $rawToken);
        $this->db->where('token_hash', $hash)->update(self::TABLE, ['revoked' => 1]);
    }

    /** Revoke every token for a user (used on password change). */
    public function revokeAllForUser(string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        $this->db->where('username', $username)->update(self::TABLE, ['revoked' => 1]);
    }

    /**
     * Delete tokens that have been expired or revoked for more than 30 days.
     * Call periodically (e.g. from a cron or on login) to keep the table
     * from growing indefinitely.
     */
    public function pruneExpired(int $olderThanSeconds = 2592000): int
    {
        $cutoff = time() - $olderThanSeconds;
        $this->db->group_start()
                 ->where('revoked', 1)
                 ->or_where('expires_at <', $cutoff)
                 ->group_end()
                 ->delete(self::TABLE);
        return $this->db->affected_rows();
    }
}

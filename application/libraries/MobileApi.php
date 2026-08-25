<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MobileApi
 *
 * Shared base for every /api/mobile/* controller. Provides:
 *   - JSON output helper
 *   - JSON request body parsing
 *   - Bearer-token authentication (_require_token)
 *   - Idempotency-key deduplication for mutating requests
 *
 * The idempotency store (o_mobile_outbox) records the first response for a
 * given X-Idempotency-Key and replays it on any retry, so an offline write
 * that the client re-sends after reconnect never double-executes.
 */
class MobileApi extends CI_Controller
{
    private const OUTBOX_TTL_SECONDS = 86400; // 24h

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('MobileTokenModel');
    }

    // ─── Output ────────────────────────────────────────────────────────────

    protected function json(array $data, int $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
        return null;
    }

    // ─── Request body ──────────────────────────────────────────────────────

    protected function read_payload(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw !== '' && $raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $this->input->post() ?: [];
    }

    // ─── Auth ──────────────────────────────────────────────────────────────

    protected function bearer_token(): string
    {
        $header = $this->input->get_request_header('Authorization');
        if (!$header) {
            return '';
        }
        $header = trim($header);
        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return trim($header);
    }

    /**
     * Require a valid bearer token. On failure sends 401 and returns null.
     * On success returns the o_mobile_tokens row (which carries username).
     */
    protected function require_token(): ?array
    {
        $raw = $this->bearer_token();
        if ($raw === '') {
            $this->json(['ok' => false, 'message' => 'Missing authorization token.'], 401);
            return null;
        }
        $row = $this->MobileTokenModel->lookup($raw);
        if (!$row) {
            $this->json(['ok' => false, 'message' => 'Invalid or expired token. Please log in again.'], 401);
            return null;
        }
        return $row;
    }

    /** Convenience: the authenticated username (empty when not authed). */
    protected function auth_username(): string
    {
        $raw = $this->bearer_token();
        if ($raw === '') {
            return '';
        }
        $row = $this->MobileTokenModel->lookup($raw);
        return $row ? (string)$row['username'] : '';
    }

    // ─── Idempotency ───────────────────────────────────────────────────────

    /**
     * For a mutating request (POST/PUT/DELETE), check the X-Idempotency-Key.
     * If we have already served this key, replay the stored response and
     * return true (caller must stop). Otherwise return false; the caller
     * should proceed and then call [record_idempotent_response] with its
     * response payload so future retries are deduped.
     *
     * GET requests are always passed through (return false).
     */
    protected function replay_if_duplicate(): bool
    {
        $method = strtoupper((string)$this->input->method(true));
        if ($method === 'GET') {
            return false;
        }

        $key = trim((string)$this->input->get_request_header('X-Idempotency-Key'));
        if ($key === '') {
            return false; // no key → no dedup (caller chose not to opt in)
        }

        $now = time();
        // Purge expired rows opportunistically (cheap, bounded by index).
        try {
            $this->db->where('expires_at <', $now)->delete('o_mobile_outbox');
        } catch (Throwable $e) {
            // ignore
        }

        $row = $this->db->get_where('o_mobile_outbox', ['idem_key' => $key], 1)->row_array();
        if (!$row) {
            return false;
        }

        // Replay the stored response verbatim.
        $this->output
            ->set_status_header((int)$row['status_code'])
            ->set_content_type('application/json', 'utf-8')
            ->set_output((string)$row['response_body']);
        return true;
    }

    /**
     * Record the response for the current idempotency key so retries are
     * deduped. Call this right before the controller returns its JSON for a
     * mutating request. Safe to call when no key is present (no-op).
     */
    protected function record_idempotent_response(int $statusCode, string $responseBody): void
    {
        $key = trim((string)$this->input->get_request_header('X-Idempotency-Key'));
        if ($key === '') {
            return;
        }

        $now = time();
        $username = $this->auth_username();
        $endpoint = $this->current_endpoint();

        try {
            $this->db->insert('o_mobile_outbox', [
                'idem_key'       => $key,
                'username'       => $username,
                'endpoint'       => $endpoint,
                'status_code'    => $statusCode,
                'response_body'  => $responseBody,
                'created_at'     => $now,
                'expires_at'     => $now + self::OUTBOX_TTL_SECONDS,
            ]);
        } catch (Throwable $e) {
            // A duplicate insert (race) is fine — the first one wins.
        }
    }

    protected function current_endpoint(): string
    {
        $dir = strtolower(trim((string)$this->router->fetch_directory(), '/'));
        $cls = strtolower((string)$this->router->fetch_class());
        $mtd = strtolower((string)$this->router->fetch_method());
        return ($dir !== '' ? $dir . '/' : '') . $cls . '/' . ($mtd ?: 'index');
    }
}

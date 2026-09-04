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
        $this->send_cors_headers();
        $this->ensure_mobile_schema();
    }

    /**
     * Auto-create the mobile API tables if they don't exist.
     * This runs on every /api/mobile/* request but is a no-op once the
     * tables exist (CREATE TABLE IF NOT EXISTS + cached flag).
     * New clients don't need to manually run mobile_api.sql.
     */
    private function ensure_mobile_schema(): void
    {
        // Static flag — only run the checks once per request lifecycle.
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $db = $this->db;

        // 1. o_mobile_tokens — bearer token store
        if (!$db->table_exists('o_mobile_tokens')) {
            $db->query("CREATE TABLE IF NOT EXISTS `o_mobile_tokens` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `username` varchar(120) NOT NULL,
                `token_hash` char(64) NOT NULL COMMENT 'sha256 of the bearer token',
                `device_id` varchar(160) DEFAULT NULL,
                `device_name` varchar(160) DEFAULT NULL,
                `platform` varchar(20) DEFAULT NULL,
                `issued_at` int unsigned NOT NULL,
                `expires_at` int unsigned NOT NULL,
                `revoked` tinyint(1) NOT NULL DEFAULT 0,
                `last_seen_at` int unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_token_hash` (`token_hash`),
                KEY `idx_username` (`username`),
                KEY `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 2. o_mobile_outbox — idempotency log
        if (!$db->table_exists('o_mobile_outbox')) {
            $db->query("CREATE TABLE IF NOT EXISTS `o_mobile_outbox` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `idem_key` varchar(120) NOT NULL,
                `username` varchar(120) NOT NULL,
                `endpoint` varchar(255) NOT NULL,
                `status_code` smallint unsigned NOT NULL,
                `response_body` longtext NOT NULL,
                `created_at` int unsigned NOT NULL,
                `expires_at` int unsigned NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_idem_key` (`idem_key`),
                KEY `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 3. notes — fix id column if it lacks AUTO_INCREMENT
        if ($db->table_exists('notes')) {
            $fields = $db->field_data('notes');
            $needsFix = true;
            foreach ($fields as $f) {
                if ($f->name === 'id' && $f->primary_key === 1 && stripos((string)$f->type, 'int') !== false) {
                    // Check if it's already auto_increment by looking at the column definition
                    $col = $db->query("SHOW COLUMNS FROM `notes` WHERE Field = 'id'")->row();
                    if ($col && stripos((string)$col->Extra, 'auto_increment') !== false) {
                        $needsFix = false;
                    }
                    break;
                }
            }
            if ($needsFix) {
                try {
                    $db->query("ALTER TABLE `notes` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)");
                } catch (Throwable $e) { /* ignore — may already have a PK */ }
            }
        }

        // 4. todos — fix id column if it lacks AUTO_INCREMENT
        if ($db->table_exists('todos')) {
            $fields = $db->field_data('todos');
            $needsFix = true;
            foreach ($fields as $f) {
                if ($f->name === 'id' && $f->primary_key === 1 && stripos((string)$f->type, 'int') !== false) {
                    $col = $db->query("SHOW COLUMNS FROM `todos` WHERE Field = 'id'")->row();
                    if ($col && stripos((string)$col->Extra, 'auto_increment') !== false) {
                        $needsFix = false;
                    }
                    break;
                }
            }
            if ($needsFix) {
                try {
                    $db->query("ALTER TABLE `todos` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)");
                } catch (Throwable $e) { /* ignore */ }
            }
        }
    }

    /**
     * CORS support. Native mobile apps (Flutter on iOS/Android) do not send
     * an Origin header and are not affected by CORS at all, so this only
     * matters for browser-based callers. We restrict the allowed origins to
     * the school's own domains rather than reflecting any Origin back,
     * which would let attacker-controlled web pages call the API.
     */
    private function send_cors_headers(): void
    {
        $origin = $this->input->get_request_header('Origin');

        // Allowlist of origins that may call the API from a browser.
        $allowed_origins = [
            'https://fbmso.srmsportal.com',
            'https://fbmso.softtechco.biz',
        ];

        if ($origin && in_array($origin, $allowed_origins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
        // If the origin is not on the allowlist, send no ACAO header at all.
        // The browser will block the response — which is the desired behaviour.

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Idempotency-Key');
        header('Access-Control-Max-Age: 86400');

        if ($this->input->method(true) === 'OPTIONS') {
            $this->output->set_status_header(204);
            $this->output->_display();
            exit;
        }
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

        // Verify the account is still active. A disabled/deleted user's
        // token should not work even before it expires.
        $user = $this->db->where('username', $row['username'])
            ->select('acctStat, force_change_password')
            ->get('o_users')->row_array();
        if (!$user) {
            // Account deleted — revoke the token
            $this->MobileTokenModel->revoke($raw);
            $this->json(['ok' => false, 'message' => 'Account no longer exists.'], 401);
            return null;
        }
        if (isset($user['acctStat']) && $user['acctStat'] === 'Inactive') {
            $this->MobileTokenModel->revoke($raw);
            $this->json(['ok' => false, 'message' => 'Account has been deactivated.'], 401);
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

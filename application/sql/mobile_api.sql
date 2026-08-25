-- ============================================================================
-- FBMSO Attendance — Mobile API tables
-- Run once on the production database (srmsportal_fbmso) and on local dev.
-- ============================================================================

-- Bearer tokens issued by /api/mobile/auth/login.
-- The raw token is sent to the client; only its sha256 hash is stored.
CREATE TABLE IF NOT EXISTS `o_mobile_tokens` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server-side idempotency log so retried offline writes do not double-execute.
-- Keyed by (idempotency_key); the first response is replayed on retry.
CREATE TABLE IF NOT EXISTS `o_mobile_outbox` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

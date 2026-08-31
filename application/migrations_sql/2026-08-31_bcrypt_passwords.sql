-- FBMSO: bcrypt password migration
--
-- YOU DO NOT NEED TO RUN THIS BY HAND.
-- application/libraries/Schema_migrator.php applies it automatically on the
-- first request after deploy. This file is kept as documentation, and for
-- anyone who prefers to apply it ahead of the deploy.
--
-- o_users.password was varchar(65), sized for sha1 (40 chars). bcrypt needs 60
-- and future algorithms need more, so widen it. Widening is non-destructive:
-- existing sha1 values are preserved and upgraded on next successful login.

ALTER TABLE `o_users`
  MODIFY `password` VARCHAR(255) NOT NULL;

-- login_logs.password_attempt previously held AES-256-CBC ciphertext with a
-- static IV, i.e. recoverable plaintext passwords. The column is kept so the
-- 2026-08-28 incident evidence survives, but new rows store a non-reversible
-- HMAC fingerprint instead. Rename the old data out of the way once the
-- investigation is closed:
--
--   ALTER TABLE `login_logs` CHANGE `password_attempt` `password_attempt_legacy_ciphertext` TEXT NULL;
--
-- and then, when the evidence is no longer needed:
--
--   UPDATE `login_logs` SET `password_attempt_legacy_ciphertext` = NULL;

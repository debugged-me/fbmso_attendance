-- FBMSO INCIDENT CONTAINMENT -- REVIEW BEFORE RUNNING. NOT RUN AUTOMATICALLY.
--
-- bcrypt fixes how passwords are STORED. It does not fix a password that is
-- already known or guessable. These statements retire the credentials the
-- 2026-08-28 investigation found to be compromised or trivially guessable.
--
-- Locked accounts get a marker that can never match any password, so the
-- holder must go through the forgot-password flow to regain access.
-- Take a database backup first.

-- ---------------------------------------------------------------------------
-- STEP 0: see what would be affected (run these SELECTs first)
-- ---------------------------------------------------------------------------

-- 0a. Accounts still holding the exact password the attacker used on 2025-0116.
--     This same password also works on Admin and Super Admin accounts.
SELECT username, position, acctStat, email
FROM o_users
WHERE password = '2fbd3e72682117dfad3ce0089afa803b021bf80b';

-- 0b. Student accounts whose password is still their own birth date.
SELECT COUNT(*) AS birthdate_password_accounts
FROM o_users u
JOIN studeprofile p
  ON CONVERT(TRIM(p.StudentNumber) USING utf8mb4) = CONVERT(TRIM(u.username) USING utf8mb4)
WHERE CONVERT(u.password USING utf8mb4)
      = CONVERT(SHA1(DATE_FORMAT(p.birthDate, '%Y-%m-%d')) USING utf8mb4)
  AND p.birthDate <> '0000-00-00';

-- 0c. Accounts sharing any password with 2 or more other accounts.
SELECT password, COUNT(*) AS accounts
FROM o_users
GROUP BY password HAVING accounts > 1
ORDER BY accounts DESC;

-- ---------------------------------------------------------------------------
-- STEP 1: privileged accounts on the compromised password -- HIGHEST PRIORITY
-- ---------------------------------------------------------------------------
-- Do this one FIRST and by hand, then set a fresh unique password for each
-- through the application so the owners are not locked out unexpectedly.

UPDATE o_users
SET password = CONCAT('!locked:', SHA2(CONCAT(username, RAND(), NOW(6)), 256))
WHERE password = '2fbd3e72682117dfad3ce0089afa803b021bf80b';

-- ---------------------------------------------------------------------------
-- STEP 2: students whose password is their birth date
-- ---------------------------------------------------------------------------
-- ~524 accounts at time of investigation. Notify them before or alongside this.

UPDATE o_users u
JOIN studeprofile p
  ON CONVERT(TRIM(p.StudentNumber) USING utf8mb4) = CONVERT(TRIM(u.username) USING utf8mb4)
SET u.password = CONCAT('!locked:', SHA2(CONCAT(u.username, RAND(), NOW(6)), 256))
WHERE CONVERT(u.password USING utf8mb4)
      = CONVERT(SHA1(DATE_FORMAT(p.birthDate, '%Y-%m-%d')) USING utf8mb4)
  AND p.birthDate <> '0000-00-00';

-- ---------------------------------------------------------------------------
-- STEP 3: known weak passwords
-- ---------------------------------------------------------------------------
UPDATE o_users
SET password = CONCAT('!locked:', SHA2(CONCAT(username, RAND(), NOW(6)), 256))
WHERE password IN (
  '7c222fb2927d828af22f592134e8932480637c0d', -- 12345678
  '7c4a8d09ca3762af61e59520943dc26494f8941b', -- 123456
  '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', -- password
  'd033e22ae348aeb5660fc2140aec35850c4da997', -- admin
  '7110eda4d09e062aa5e4a390b0a572ac0d2c0220'  -- 1234
);

-- ---------------------------------------------------------------------------
-- STEP 4: verify nothing guessable is left
-- ---------------------------------------------------------------------------
SELECT
  SUM(password LIKE '!locked:%')                        AS locked_pending_reset,
  SUM(LENGTH(password) = 40)                            AS legacy_sha1_remaining,
  SUM(password LIKE '$2y$%')                            AS bcrypt_done,
  COUNT(*)                                              AS total
FROM o_users;

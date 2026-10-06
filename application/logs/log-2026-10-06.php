<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-10-06 19:41:41 --> Query error: COLLATION 'utf8mb4_unicode_ci' is not valid for CHARACTER SET 'latin1' - Invalid query: SELECT COUNT(*) AS n FROM `studeaccount` WHERE `StudentNumber` IS NOT NULL AND `StudentNumber` != '' AND `StudentNumber` COLLATE utf8mb4_unicode_ci NOT IN (SELECT sn FROM _dh_sn)
ERROR - 2026-10-06 19:41:41 --> Severity: error --> Exception: Call to a member function row() on bool /Applications/XAMPP/xamppfiles/htdocs/fbmso_attendance/application/models/StudentModel.php 5325
ERROR - 2026-10-06 19:41:46 --> Query error: COLLATION 'utf8mb4_unicode_ci' is not valid for CHARACTER SET 'latin1' - Invalid query: SELECT COUNT(*) AS n FROM `studeaccount` WHERE `StudentNumber` IS NOT NULL AND `StudentNumber` != '' AND `StudentNumber` COLLATE utf8mb4_unicode_ci NOT IN (SELECT sn FROM _dh_sn)
ERROR - 2026-10-06 19:41:46 --> Severity: error --> Exception: Call to a member function row() on bool /Applications/XAMPP/xamppfiles/htdocs/fbmso_attendance/application/models/StudentModel.php 5325

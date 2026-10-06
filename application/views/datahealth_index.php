<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
// Friendly labels — staff shouldn't see raw table/column names.
$tableLabels = [
    'studentsignup' => 'Signup records', 'studeprofile' => 'Student profiles', 'o_users' => 'Login accounts',
    'paymentsaccounts' => 'Payment records', 'studeaccount' => 'Payment accounts', 'online_payments' => 'Online payments',
    'semesterstude' => 'Enrollment records', 'online_enrollment' => 'Online enrollment', 'online_enrollment_deny' => 'Denied enrollments',
    'online_requirements' => 'Online requirements', 'registration' => 'Registrations', 'grades' => 'Grades',
    'grades_o' => 'Grades (legacy)', 'attendance_daily' => 'Daily attendance', 'attendance_scans' => 'Attendance scans',
    'activity_attendance' => 'Activity attendance', 'cr_attendance' => 'Class-record attendance',
    'student_qr' => 'Student QR codes', 'student_requirements' => 'Student requirements', 'stude_request' => 'Student requests',
    'stude_request_stat' => 'Request statuses', 'document_requests' => 'Document requests',
    'flagged_students' => 'Flagged students', 'student_flags' => 'Student flags', 'email_logs' => 'Email log',
    'profiles' => 'Profiles', 'payment_audit_log' => 'Payment activity log', 'studeadditional' => 'Payment adjustments',
    'studediscount' => 'Student discounts', 'typing_status' => 'Typing status', 'todos' => 'To-do items',
    'notes' => 'Notes', 'o_mobile_tokens' => 'Mobile login tokens', 'o_mobile_outbox' => 'Mobile outbox',
    'o_email_verifications' => 'Email verifications', 'user_devices' => 'Linked devices',
    'user_security_sessions' => 'Login sessions', 'password_rotation_backup' => 'Password backups',
    'course_table' => 'Course records', 'staff' => 'Staff records', 'hris_cs' => 'HR records',
    'hris_educ' => 'HR records', 'hris_family' => 'HR records', 'hris_trainings' => 'HR records',
];
$label = function ($t) use ($tableLabels) {
    $t = (string)$t;
    if (isset($tableLabels[$t])) return $tableLabels[$t];
    $t = preg_replace('/\..*$/', '', $t);
    return isset($tableLabels[$t]) ? $tableLabels[$t] : ucwords(str_replace('_', ' ', $t));
};
$r = is_array($report ?? null) ? $report : [];
$orphans        = $r['orphans']        ?? [];
$missingTables  = $r['missingTables']  ?? [];
$nearDups       = $r['nearDuplicates'] ?? [];
$emails         = $r['emails']         ?? [];
$accountGaps    = $r['accountGaps']    ?? [];
$collationDrift = $r['collationDrift'] ?? [];

$orphanRows    = array_sum(array_map(function ($o) { return (int)$o['rows']; }, $orphans));
$emailIssues   = (int)($emails['malformedTotal'] ?? 0) + (int)($emails['typoTotal'] ?? 0)
               + (int)($emails['duplicatesTotal'] ?? 0) + (int)($emails['emptyAccounts']['count'] ?? 0);
$gapCount      = count($accountGaps['accountsWithoutRecord'] ?? []) + count($accountGaps['signupsWithoutAccount'] ?? []);
$techCount     = count($collationDrift) + count($missingTables);
$editUrl       = function ($sn) { return base_url('Page/editSignup?id=' . urlencode((string)$sn)); };
$totalFindings = $orphanRows + count($nearDups) + $emailIssues + $gapCount + $techCount;
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">
<style>
    .dh-card { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
    .dh-card-head { padding:15px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .dh-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
    .dh-card-head h5 > i { color:#4266d4; font-size:19px; }

    .dh-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(105px,1fr)); gap:10px; }
    .dh-stat { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:14px; padding:12px 14px; }
    .dh-stat b { display:block; font-size:1.35rem; font-weight:800; color:var(--up-ink,#0d1b4b); line-height:1.15; }
    .dh-stat span { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--up-muted,#6b7a99); }
    .dh-stat.bad b { color:#b3261e; }
    .dh-stat.warn b { color:#d9931e; }

    .dh-rows { padding:10px 14px; }
    .dh-row { display:flex; gap:13px; padding:13px 10px; border-radius:14px; border:1px solid transparent; }
    .dh-row:hover { background:var(--up-soft,#f5f7fc); border-color:var(--up-line,#e6ebf5); }
    .dh-ic { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .dh-ic.red { background:#fdeaea; color:#b3261e; }
    .dh-ic.amber { background:#fdf3e2; color:#d9931e; }
    .dh-ic.blue { background:#e8f0fe; color:#2f5fd0; }
    .dh-ic.grey { background:#eef1f7; color:#5b6b8c; }
    .dh-main { flex:1; min-width:0; }
    .dh-title { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:.9rem; font-weight:700; color:var(--up-ink,#0d1b4b); }
    .dh-pill { display:inline-flex; align-items:center; gap:4px; font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .dh-pill-count { background:#e8f0fe; color:#2f5fd0; }
    .dh-pill-lv { background:#f1f3f8; color:#5b6b8c; border:1px solid #e3e8f3; text-transform:uppercase; letter-spacing:.03em; }
    .dh-msg { font-size:.83rem; color:var(--up-ink,#0d1b4b); margin-top:4px; word-break:break-word; }
    .dh-meta { font-size:.75rem; color:var(--up-muted,#6b7a99); margin-top:5px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .dh-meta code { background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); border-radius:6px; padding:1px 6px; font-size:.72rem; }
    .dh-meta a { color:#2f5fd0; font-weight:600; }
    .dh-why { font-size:.8rem; color:var(--up-muted,#6b7a99); margin-top:8px; line-height:1.5; }
    .dh-fix { font-size:.8rem; color:#155724; background:#eaf6ee; border:1px solid #d4ecdc; border-radius:10px; padding:8px 12px; margin-top:6px; line-height:1.5; }
    .dh-fix b { color:#0e5025; }
    .dh-fix a { color:#0e5025; font-weight:700; }
    .dh-act { flex-shrink:0; align-self:flex-start; display:flex; flex-direction:column; gap:5px; }
    a.dh-btn { display:inline-flex; align-items:center; gap:5px; font-size:.74rem; font-weight:700; color:#2f5fd0; background:#e8f0fe; border-radius:9px; padding:6px 12px; text-decoration:none; white-space:nowrap; }
    a.dh-btn:hover { background:#d6e4fd; text-decoration:none; }
    .dh-empty { margin:14px; }
</style>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="page-title-box">
      <h4 class="up-page-title"><i class="mdi mdi-heart-pulse"></i> Student Record Checkup</h4>
      <div class="up-page-sub">A health scan of student records — what needs attention and what to do about it. Read-only; nothing here changes data.</div>
      <hr class="up-divider">
    </div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= html_escape($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="alert alert-danger"><?= html_escape($m); ?></div>
    <?php endif; ?>

    <!-- ==================== Stats ==================== -->
    <div class="dh-stats">
      <div class="dh-stat"><b><?= number_format((int)($r['canonicalKeys'] ?? 0)); ?></b><span>students</span></div>
      <div class="dh-stat <?= $orphanRows ? 'bad' : ''; ?>"><b><?= number_format($orphanRows); ?></b><span>leftover records</span></div>
      <div class="dh-stat <?= count($nearDups) ? 'warn' : ''; ?>"><b><?= count($nearDups); ?></b><span>look-alike IDs</span></div>
      <div class="dh-stat <?= $emailIssues ? 'warn' : ''; ?>"><b><?= number_format($emailIssues); ?></b><span>email issues</span></div>
      <div class="dh-stat <?= $gapCount ? 'warn' : ''; ?>"><b><?= $gapCount; ?></b><span>missing accounts</span></div>
      <div class="dh-stat <?= $techCount ? 'warn' : ''; ?>"><b><?= $techCount; ?></b><span>tech notes</span></div>
    </div>

    <!-- ==================== Leftover records ==================== -->
    <div class="dh-card mt-3">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-file-remove-outline"></i> Leftover Records</h5>
        <span class="up-page-sub" style="margin:0"><?= $orphanRows ? number_format($orphanRows) . ' record(s) left behind' : 'none'; ?></span>
      </div>
      <?php if (empty($orphans)): ?>
        <div class="up-empty dh-empty"><i class="mdi mdi-check-circle-outline"></i><div>Nothing left behind — every record belongs to a real student.</div></div>
      <?php else: ?>
        <div class="dh-rows">
          <?php foreach ($orphans as $o): ?>
            <div class="dh-row">
              <div class="dh-ic red"><i class="mdi mdi-trash-can-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($label($o['table'])); ?> <span class="dh-pill dh-pill-count">&times; <?= number_format((int)$o['rows']); ?></span></div>
                <div class="dh-msg">
                  Records belonging to student number<?= count($o['samples']) === 1 ? '' : 's'; ?> that no longer exist:
                  <b><?= $e(implode(', ', array_map(function ($s) { return $s['k']; }, $o['samples']))); ?><?= (int)$o['rows'] > count($o['samples']) ? '…' : ''; ?></b>
                </div>
                <div class="dh-why">Usually left behind when a student was deleted, or from renames that happened before the automatic cascade existed.</div>
                <div class="dh-fix"><b>What to do:</b> nothing urgent — they don't affect current students. Use Clean Up to remove them, or leave them if you're unsure.</div>
              </div>
              <div class="dh-act">
                <form method="post" action="<?= base_url('datahealth/cleanup'); ?>"
                      onsubmit="return confirm('Delete <?= (int)$o['rows']; ?> leftover record(s) from <?= $e($label($o['table'])); ?>?\n\nOnly rows belonging to student numbers that no longer exist will be removed. This cannot be undone.');">
                  <input type="hidden" name="area" value="<?= $e($o['table']); ?>">
                  <input type="hidden" name="label" value="<?= $e($label($o['table'])); ?>">
                  <button type="submit" class="dh-btn" style="border:0;cursor:pointer;"><i class="mdi mdi-broom"></i> Clean up</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ==================== Look-alike student numbers ==================== -->
    <div class="dh-card mt-3">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-account-multiple-outline"></i> Look-alike Student Numbers</h5>
        <span class="up-page-sub" style="margin:0"><?= count($nearDups); ?> pair<?= count($nearDups) === 1 ? '' : 's'; ?></span>
      </div>
      <?php if (empty($nearDups)): ?>
        <div class="up-empty dh-empty"><i class="mdi mdi-check-circle-outline"></i><div>No look-alike student numbers found.</div></div>
      <?php else: ?>
        <div class="dh-rows">
          <?php foreach ($nearDups as $nd): ?>
            <div class="dh-row">
              <div class="dh-ic amber"><i class="mdi mdi-account-multiple-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title">
                  <?php foreach ($nd['variants'] as $i => $v): ?>
                    <?php if ($i): ?> &amp; <?php endif; ?><?= $e($v); ?>
                  <?php endforeach; ?>
                </div>
                <div class="dh-meta">
                  <?php foreach ($nd['variants'] as $v): ?>
                    <span><?= $e($v); ?>: <?= !empty($nd['names'][$v]) ? $e($nd['names'][$v]) : 'unknown name'; ?></span>
                    <span class="dot">&bull;</span>
                  <?php endforeach; ?>
                </div>
                <div class="dh-why">Same digits, different dash placement — likely the same student registered under two different IDs.</div>
                <div class="dh-fix"><b>What to do:</b> open both records and compare the names and details. If it's the same student, keep one number and remove the duplicate carefully.</div>
              </div>
              <div class="dh-act">
                <?php foreach ($nd['variants'] as $v): ?>
                  <a class="dh-btn" href="<?= $editUrl($v); ?>"><i class="mdi mdi-pencil-outline"></i> <?= $e($v); ?></a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ==================== Email issues ==================== -->
    <div class="dh-card mt-3">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-email-alert-outline"></i> Email Addresses to Fix</h5>
        <span class="up-page-sub" style="margin:0"><?= $emailIssues ? number_format($emailIssues) . ' issue(s)' : 'none'; ?></span>
      </div>
      <?php
        $hasEmailRows = !empty($emails['malformed']) || !empty($emails['typoDomains'])
            || !empty($emails['duplicates'])
            || (!empty($emails['emptyAccounts']) && (int)$emails['emptyAccounts']['count'] > 0);
      ?>
      <?php if (!$hasEmailRows): ?>
        <div class="up-empty dh-empty"><i class="mdi mdi-check-circle-outline"></i><div>All email addresses look good.</div></div>
      <?php else: ?>
        <div class="dh-rows">
          <?php foreach (array_slice($emails['malformed'] ?? [], 0, 8) as $row): ?>
            <div class="dh-row">
              <div class="dh-ic red"><i class="mdi mdi-email-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($row['sn']); ?> <span class="dh-pill dh-pill-lv">invalid</span></div>
                <div class="dh-msg"><code><?= $e($row['email']); ?></code> — this isn't a valid email address.</div>
                <div class="dh-fix"><b>What to do:</b> open the student's profile and correct the email — it updates everywhere at once.</div>
              </div>
              <div class="dh-act"><a class="dh-btn" href="<?= $editUrl($row['sn']); ?>"><i class="mdi mdi-pencil-outline"></i> Fix</a></div>
            </div>
          <?php endforeach; ?>

          <?php foreach (array_slice($emails['typoDomains'] ?? [], 0, 8) as $row): ?>
            <div class="dh-row">
              <div class="dh-ic amber"><i class="mdi mdi-email-alert"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($row['sn']); ?> <span class="dh-pill dh-pill-lv">misspelled</span></div>
                <div class="dh-msg"><code><?= $e($row['email']); ?></code> — the domain looks misspelled (like gmai.com instead of gmail.com).</div>
                <div class="dh-fix"><b>What to do:</b> open the student's profile and correct the email.</div>
              </div>
              <div class="dh-act"><a class="dh-btn" href="<?= $editUrl($row['sn']); ?>"><i class="mdi mdi-pencil-outline"></i> Fix</a></div>
            </div>
          <?php endforeach; ?>

          <?php foreach (array_slice($emails['duplicates'] ?? [], 0, 6) as $d): ?>
            <div class="dh-row">
              <div class="dh-ic amber"><i class="mdi mdi-email-multiple-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($d['email']); ?> <span class="dh-pill dh-pill-count">&times; <?= (int)$d['n']; ?></span></div>
                <div class="dh-msg">Shared by: <b><?= $e($d['users']); ?></b></div>
                <div class="dh-why">Two accounts on one email means a password reset could reach the wrong person.</div>
                <div class="dh-fix"><b>What to do:</b> decide which account should keep this email and update the other.</div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (!empty($emails['emptyAccounts']) && (int)$emails['emptyAccounts']['count'] > 0): ?>
            <?php foreach (array_slice($emails['emptyAccounts']['samples'], 0, 8) as $s): ?>
              <div class="dh-row">
                <div class="dh-ic blue"><i class="mdi mdi-email-outline"></i></div>
                <div class="dh-main">
                  <div class="dh-title"><?= $e(trim($s['fName'] . ' ' . $s['lName']) ?: $s['username']); ?> <span class="dh-pill dh-pill-lv">no email</span></div>
                  <div class="dh-msg">Student number <b><?= $e($s['username']); ?></b> has no email on file — they can't reset their password.</div>
                  <div class="dh-fix"><b>What to do:</b> ask the student for their email and add it on their profile.</div>
                </div>
                <div class="dh-act"><a class="dh-btn" href="<?= $editUrl($s['username']); ?>"><i class="mdi mdi-pencil-outline"></i> Add</a></div>
              </div>
            <?php endforeach; ?>
            <?php if ((int)$emails['emptyAccounts']['count'] > 8): ?>
              <div class="dh-msg" style="padding:4px 10px;">…and <?= number_format((int)$emails['emptyAccounts']['count'] - 8); ?> more accounts without email.</div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ==================== Missing accounts ==================== -->
    <div class="dh-card mt-3">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-account-question-outline"></i> Missing Accounts or Records</h5>
        <span class="up-page-sub" style="margin:0"><?= $gapCount ? $gapCount . ' found' : 'none'; ?></span>
      </div>
      <?php if (empty($accountGaps['accountsWithoutRecord']) && empty($accountGaps['signupsWithoutAccount'])): ?>
        <div class="up-empty dh-empty"><i class="mdi mdi-check-circle-outline"></i><div>Every login has a student record, and every signup has a login.</div></div>
      <?php else: ?>
        <div class="dh-rows">
          <?php foreach (array_slice($accountGaps['accountsWithoutRecord'] ?? [], 0, 8) as $g): ?>
            <div class="dh-row">
              <div class="dh-ic grey"><i class="mdi mdi-account-off-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($g['name'] ?: $g['username']); ?></div>
                <div class="dh-msg">Login <b><?= $e($g['username']); ?></b> exists, but there's no student record behind it.</div>
                <div class="dh-why">Usually a deleted student's leftover login, or a test account.</div>
                <div class="dh-fix"><b>What to do:</b> if it's a leftover, have IT remove the login; if it's a real student, check their record was created.</div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php foreach (array_slice($accountGaps['signupsWithoutAccount'] ?? [], 0, 8) as $g): ?>
            <div class="dh-row">
              <div class="dh-ic amber"><i class="mdi mdi-account-plus-outline"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($g['name'] ?: $g['sn']); ?></div>
                <div class="dh-msg"><b><?= $e($g['sn']); ?></b> registered but has no login — they can't sign in yet.</div>
                <div class="dh-fix"><b>What to do:</b> create their account from the user account tools so they can log in.</div>
              </div>
              <div class="dh-act"><a class="dh-btn" href="<?= $editUrl($g['sn']); ?>"><i class="mdi mdi-eye-outline"></i> View</a></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ==================== Technical notes ==================== -->
    <div class="dh-card mt-3">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-wrench-outline"></i> Technical Notes</h5>
        <span class="up-page-sub" style="margin:0"><?= $techCount ? $techCount . ' note(s) — for IT' : 'none'; ?></span>
      </div>
      <?php if (empty($collationDrift) && empty($missingTables)): ?>
        <div class="up-empty dh-empty"><i class="mdi mdi-check-circle-outline"></i><div>No technical issues found.</div></div>
      <?php else: ?>
        <div class="dh-rows">
          <?php foreach (array_unique(array_map($label, array_column($collationDrift, 'table'))) as $dl): ?>
            <div class="dh-row">
              <div class="dh-ic grey"><i class="mdi mdi-format-font"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($dl); ?></div>
                <div class="dh-msg">Stores text differently from the rest of the system — searches across it can miss matches.</div>
                <div class="dh-fix"><b>What to do:</b> nothing students will notice; flag it for whoever maintains the system.</div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php foreach ($missingTables as $m): ?>
            <div class="dh-row">
              <div class="dh-ic grey"><i class="mdi mdi-table"></i></div>
              <div class="dh-main">
                <div class="dh-title"><?= $e($label($m)); ?></div>
                <div class="dh-msg">A record area the system expects wasn't found here — fine on older installs, worth flagging if production should have it.</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div></div>

  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
</body>
</html>

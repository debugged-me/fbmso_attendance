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
    // Strip a trailing ".column" then humanize whatever is left.
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

$orphanRows   = array_sum(array_map(function ($o) { return (int)$o['rows']; }, $orphans));
$emailIssues  = (int)($emails['malformedTotal'] ?? 0) + (int)($emails['typoTotal'] ?? 0)
              + (int)($emails['duplicatesTotal'] ?? 0) + (int)($emails['emptyAccounts']['count'] ?? 0);
$gapCount     = count($accountGaps['accountsWithoutRecord'] ?? []) + count($accountGaps['signupsWithoutAccount'] ?? []);
$editUrl      = function ($sn) { return base_url('Page/editSignup?id=' . urlencode((string)$sn)); };
$totalIssues  = count($orphans) + count($nearDups) + ($emailIssues > 0 ? 1 : 0) + ($gapCount > 0 ? 1 : 0) + count($collationDrift);
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">
<style>
    .dh-card { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); margin-bottom:18px; }
    .dh-card-head { padding:15px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .dh-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
    .dh-card-head h5 > i { color:#4266d4; font-size:19px; }
    .dh-card-body { padding:16px 22px; }
    .dh-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; margin-bottom:18px; }
    .dh-stat { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:14px; padding:12px 14px; }
    .dh-stat b { display:block; font-size:1.35rem; font-weight:800; color:var(--up-ink,#0d1b4b); line-height:1.15; }
    .dh-stat span { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--up-muted,#6b7a99); }
    .dh-stat.bad b { color:#b3261e; }
    .dh-stat.warn b { color:#d9931e; }
    .dh-stat.ok b { color:#1e8449; }
    .dh-tbl { width:100%; border-collapse:collapse; font-size:.83rem; }
    .dh-tbl th { text-align:left; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--up-muted,#6b7a99); padding:8px 10px; border-bottom:1px solid var(--up-line,#e6ebf5); }
    .dh-tbl td { padding:9px 10px; border-bottom:1px solid var(--up-soft,#f5f7fc); color:var(--up-ink,#0d1b4b); vertical-align:top; }
    .dh-tbl tr:last-child td { border-bottom:0; }
    .dh-chip { display:inline-block; background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); border-radius:8px; padding:3px 8px; font-size:.75rem; font-weight:600; margin:2px 3px 2px 0; }
    .dh-chip b { color:#b3261e; }
    .dh-pill { display:inline-flex; align-items:center; font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .dh-pill-bad { background:#fdeaea; color:#b3261e; }
    .dh-pill-warn { background:#fdf3e2; color:#d9931e; }
    .dh-pill-info { background:#e8f0fe; color:#2f5fd0; }
    .dh-note { font-size:.78rem; color:var(--up-muted,#6b7a99); margin-top:10px; }
    .dh-empty { padding:14px 4px; font-size:.85rem; color:#1e8449; font-weight:600; }
    .dh-sub { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--up-muted,#6b7a99); margin:14px 0 6px; }
    .dh-sub:first-child { margin-top:0; }
    a.dh-link { color:#2f5fd0; font-weight:600; text-decoration:none; }
    a.dh-link:hover { text-decoration:underline; }
</style>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="row"><div class="col-12">
      <div class="page-title-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <h4 class="page-title">Student Data Health</h4>
        <span class="dh-pill <?= $totalIssues > 0 ? 'dh-pill-bad' : 'dh-pill-info'; ?>">
          <?= $totalIssues > 0 ? $totalIssues . ' issue area' . ($totalIssues > 1 ? 's' : '') . ' found' : 'All clear'; ?>
        </span>
      </div>
      <p class="text-muted" style="font-size:.82rem;margin-top:-8px;">
        Read-only integrity scan of student records — nothing here changes data. Fix items link to the normal edit screens.
      </p>
    </div></div>

    <div class="dh-stats">
      <div class="dh-stat"><b><?= number_format((int)($r['canonicalKeys'] ?? 0)); ?></b><span>Student keys</span></div>
      <div class="dh-stat <?= $orphanRows ? 'bad' : 'ok'; ?>"><b><?= number_format($orphanRows); ?></b><span>Orphaned rows</span></div>
      <div class="dh-stat <?= count($nearDups) ? 'warn' : 'ok'; ?>"><b><?= count($nearDups); ?></b><span>Near-dup IDs</span></div>
      <div class="dh-stat <?= $emailIssues ? 'warn' : 'ok'; ?>"><b><?= number_format($emailIssues); ?></b><span>Email issues</span></div>
      <div class="dh-stat <?= $gapCount ? 'warn' : 'ok'; ?>"><b><?= $gapCount; ?></b><span>Account gaps</span></div>
      <div class="dh-stat <?= $collationDrift ? 'warn' : 'ok'; ?>"><b><?= count($collationDrift); ?></b><span>Collation drift</span></div>
    </div>

    <!-- Orphaned records -->
    <div class="dh-card">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-link-variant-off"></i> Orphaned Records</h5>
        <span class="dh-pill <?= $orphans ? 'dh-pill-bad' : 'dh-pill-info'; ?>"><?= $orphans ? count($orphans) . ' table' . (count($orphans) > 1 ? 's' : '') : 'none'; ?></span>
      </div>
      <div class="dh-card-body">
        <?php if (empty($orphans)): ?>
          <div class="dh-empty">No orphaned rows — every student key in related tables resolves to a real student.</div>
        <?php else: ?>
          <table class="dh-tbl">
            <thead><tr><th>Record area</th><th>Orphan rows</th><th>Student numbers found</th></tr></thead>
            <tbody>
            <?php foreach ($orphans as $o): ?>
              <tr>
                <td><?= $e($label($o['table'])); ?></td>
                <td><b><?= number_format((int)$o['rows']); ?></b></td>
                <td>
                  <?php foreach ($o['samples'] as $s): ?>
                    <span class="dh-chip"><?= $e($s['k']); ?> <b>&times;<?= (int)$s['n']; ?></b></span>
                  <?php endforeach; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <div class="dh-note">Rows keyed to student numbers that no longer exist — usually residue from deleted students or renames that predated the cascade. Cross-check before purging; a rename may make some fixable by renumbering.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Near-duplicate IDs -->
    <div class="dh-card">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-content-duplicate"></i> Near-Duplicate Student IDs</h5>
        <span class="dh-pill <?= $nearDups ? 'dh-pill-warn' : 'dh-pill-info'; ?>"><?= count($nearDups); ?> pair<?= count($nearDups) === 1 ? '' : 's'; ?></span>
      </div>
      <div class="dh-card-body">
        <?php if (empty($nearDups)): ?>
          <div class="dh-empty">No dash-variant duplicate IDs.</div>
        <?php else: ?>
          <?php foreach ($nearDups as $nd): ?>
            <div style="padding:8px 0;border-bottom:1px solid var(--up-soft,#f5f7fc);">
              <?php foreach ($nd['variants'] as $v): ?>
                <span class="dh-chip">
                  <a class="dh-link" href="<?= $editUrl($v); ?>"><?= $e($v); ?></a>
                  <?php if (!empty($nd['names'][$v])): ?> — <?= $e($nd['names'][$v]); ?><?php endif; ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <div class="dh-note">Same digits, different dash placement. If the names match it is likely the same student registered twice — verify before merging or deleting either record.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Email problems -->
    <div class="dh-card">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-email-alert-outline"></i> Email Problems</h5>
        <span class="dh-pill <?= $emailIssues ? 'dh-pill-warn' : 'dh-pill-info'; ?>"><?= number_format($emailIssues); ?> issue<?= $emailIssues === 1 ? '' : 's'; ?></span>
      </div>
      <div class="dh-card-body">
        <?php
          $hasAny = false;
          $printRows = function ($rows, $heading) use ($e, $editUrl, $label, &$hasAny) {
              if (empty($rows)) return;
              $hasAny = true;
              echo '<div class="dh-sub">' . $e($heading) . '</div>';
              foreach ($rows as $row) {
                  echo '<div style="padding:4px 0;">';
                  echo '<a class="dh-link" href="' . $editUrl($row['sn']) . '">' . $e($row['sn']) . '</a>';
                  echo ' &rarr; <code>' . $e($row['email']) . '</code>';
                  echo ' <span class="text-muted" style="font-size:.72rem;">(' . $e($label($row['table'])) . ')</span>';
                  echo '</div>';
              }
          };
          $printRows($emails['malformed'] ?? [], 'Malformed — not a valid address (' . (int)($emails['malformedTotal'] ?? 0) . ' total)');
          $printRows($emails['typoDomains'] ?? [], 'Likely typo domain — gmai.com, yahoo.con, gmail.con… (' . (int)($emails['typoTotal'] ?? 0) . ' total)');

          if (!empty($emails['duplicates'])):
              $hasAny = true;
        ?>
          <div class="dh-sub">Same email on multiple accounts (<?= (int)($emails['duplicatesTotal'] ?? count($emails['duplicates'])); ?> total)</div>
          <?php foreach ($emails['duplicates'] as $d): ?>
            <div style="padding:4px 0;"><code><?= $e($d['email']); ?></code> &rarr; <?= $e($d['users']); ?> <span class="dh-pill dh-pill-warn">&times;<?= (int)$d['n']; ?></span></div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($emails['emptyAccounts']) && (int)$emails['emptyAccounts']['count'] > 0): $hasAny = true; ?>
          <div class="dh-sub">Student accounts with no email (<?= (int)$emails['emptyAccounts']['count']; ?>)</div>
          <?php foreach ($emails['emptyAccounts']['samples'] as $s): ?>
            <span class="dh-chip"><a class="dh-link" href="<?= $editUrl($s['username']); ?>"><?= $e($s['username']); ?></a> — <?= $e(trim($s['fName'] . ' ' . $s['lName'])); ?></span>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!$hasAny): ?>
          <div class="dh-empty">No email problems found.</div>
        <?php endif; ?>
        <div class="dh-note">Fix an address via the linked edit page — the update syncs to signup, profile, and login account together.</div>
      </div>
    </div>

    <!-- Account gaps -->
    <div class="dh-card">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-account-question-outline"></i> Account Gaps</h5>
        <span class="dh-pill <?= $gapCount ? 'dh-pill-warn' : 'dh-pill-info'; ?>"><?= $gapCount; ?> found</span>
      </div>
      <div class="dh-card-body">
        <?php if (empty($accountGaps['accountsWithoutRecord']) && empty($accountGaps['signupsWithoutAccount'])): ?>
          <div class="dh-empty">Every student login maps to a record and every signup has a login.</div>
        <?php else: ?>
          <?php if (!empty($accountGaps['accountsWithoutRecord'])): ?>
            <div class="dh-sub">Login accounts with no signup/profile record</div>
            <?php foreach ($accountGaps['accountsWithoutRecord'] as $g): ?>
              <div style="padding:4px 0;"><code><?= $e($g['username']); ?></code> — <?= $e($g['name']); ?></div>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (!empty($accountGaps['signupsWithoutAccount'])): ?>
            <div class="dh-sub">Signups with no login account</div>
            <?php foreach ($accountGaps['signupsWithoutAccount'] as $g): ?>
              <div style="padding:4px 0;"><a class="dh-link" href="<?= $editUrl($g['sn']); ?>"><?= $e($g['sn']); ?></a> — <?= $e($g['name']); ?></div>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Schema drift -->
    <div class="dh-card">
      <div class="dh-card-head">
        <h5><i class="mdi mdi-table-cog"></i> Schema Drift</h5>
        <span class="dh-pill <?= ($collationDrift || $missingTables) ? 'dh-pill-warn' : 'dh-pill-info'; ?>"><?= count($collationDrift) + count($missingTables); ?> finding<?= (count($collationDrift) + count($missingTables)) === 1 ? '' : 's'; ?></span>
      </div>
      <div class="dh-card-body">
        <?php if (empty($collationDrift) && empty($missingTables)): ?>
          <div class="dh-empty">All expected student-key columns present with consistent collation.</div>
        <?php else: ?>
          <?php if (!empty($collationDrift)): ?>
            <div class="dh-sub">Inconsistent text encoding — comparisons against these records may fail</div>
            <?php foreach (array_unique(array_map($label, array_column($collationDrift, 'table'))) as $dl): ?>
              <div style="padding:4px 0;"><?= $e($dl); ?> <span class="text-muted" style="font-size:.72rem;">(encoding mismatch)</span></div>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (!empty($missingTables)): ?>
            <div class="dh-sub">Expected record areas absent on this install</div>
            <?php foreach ($missingTables as $m): ?><span class="dh-chip"><?= $e($label($m)); ?></span><?php endforeach; ?>
            <div class="dh-note">Skipped during the scan — expected if this install predates those features; a flag if production should have them.</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div></div></div>

  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
</body>
</html>

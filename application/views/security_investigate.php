<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'views/security_partials.php';

// Look up real names for all accounts
$accountNames = [];
if (!empty($accounts)) {
    $usernames = [];
    foreach ($accounts as $a) { $usernames[] = $a->username; }
    foreach ($login_attempts as $l) { $usernames[] = $l['username']; }
    foreach ($audit_entries as $a) { $usernames[] = $a['username']; }
    $unique = array_unique(array_filter($usernames));
    if ($unique) {
        $placeholders = implode(',', array_fill(0, count($unique), '?'));
        $rows = $this->db->query(
            "SELECT username, TRIM(CONCAT(fname, ' ', lname)) AS full_name FROM o_users WHERE username IN ($placeholders)",
            $unique
        )->result();
        foreach ($rows as $r) { $accountNames[$r->username] = trim($r->full_name); }
    }
}
$displayName = function ($u) use ($accountNames) {
    $u = (string)$u;
    if ($u === '') return '—';
    $name = isset($accountNames[$u]) ? $accountNames[$u] : '';
    return $name !== '' ? $name . ' (' . $u . ')' : $u;
};

// Merge all events into a single timeline
$timeline = [];
foreach ($login_attempts as $l) {
    $timeline[] = [
        'time' => $l['login_time'],
        'event' => 'LOGIN ' . strtoupper($l['status']),
        'detail' => $displayName($l['username']),
        'type' => $l['status'] === 'success' ? 'success' : 'danger',
    ];
}
foreach ($audit_entries as $a) {
    $timeline[] = [
        'time' => $a['event_time'],
        'event' => strtoupper($a['action']),
        'detail' => $displayName($a['username'] ?? '') . ' — ' . ($a['description'] ?? ''),
        'type' => $a['action'] === 'login' ? 'success' : ($a['action'] === 'logout' ? '' : 'danger'),
    ];
}
foreach ($security_events as $s) {
    $timeline[] = [
        'time' => $s['event_time'],
        'event' => $s['event_type'],
        'detail' => $s['description'] ?? '',
        'type' => strpos($s['event_type'] ?? '', 'BLOCKED') !== false ? 'danger' : '',
    ];
}
usort($timeline, function($a, $b) { return strcmp($a['time'], $b['time']); });
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<style>
/* Print styles — transforms the page into a clean document */
@media print {
  body { background: #fff !important; }
  #wrapper, .content-page, .content, .container-fluid { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
  .topbar, .top-nav-bar, .left-side-menu, .sidebar, #sidebar, .button-menu-mobile,
  .page-title-box .btn, .alert form, .no-print, footer, .footer,
  .right-bar, .rightbar-overlay, .mobile-tabbar, #fbmsoVisionMissionModal { display: none !important; }
  .content-page { margin-left: 0 !important; }
  .page-title-box { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
  .card { border: 1px solid #ccc !important; box-shadow: none !important; page-break-inside: avoid; }
  .card-header { background: #f5f5f5 !important; border-bottom: 1px solid #ddd !important; font-weight: bold; }
  .alert { border: 1px solid #ccc !important; }
  .badge { border: 1px solid #999 !important; color: #333 !important; background: #f0f0f0 !important; }
  .badge-success { background: #d4edda !important; color: #155724 !important; }
  .badge-danger { background: #f8d7da !important; color: #721c24 !important; }
  .btn { display: none !important; }
  a[href]:after { content: ""; }
  .print-header { display: block !important; }
  .print-only { display: block !important; }
  table { font-size: 11px !important; }
  .row { display: block !important; }
  .col-md-6, .col-lg-6, .col-12 { width: 100% !important; max-width: 100% !important; flex: none !important; }
}
.print-header { display: none; }
</style>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <!-- Print-only document header -->
    <div class="print-header" style="margin-bottom:20px;border-bottom:3px double #333;padding-bottom:12px">
      <div style="font-size:10px;color:#888;letter-spacing:2px;text-transform:uppercase">FBMSO Attendance System</div>
      <div style="font-size:20px;font-weight:bold;color:#333;font-family:Georgia,serif">Security Investigation Report</div>
      <div style="font-size:13px;color:#666;font-style:italic">IP Address: <?= sec_e($ip) ?> &middot; Generated <?= date('M j, Y \a\t g:i A') ?></div>
    </div>

    <div class="row"><div class="col-12">
      <div class="page-title-box d-flex justify-content-between align-items-center">
        <h4 class="page-title mb-0"><i class="mdi mdi-magnify-scan"></i> Investigate: <?= sec_e($ip) ?></h4>
        <div class="no-print">
          <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-shield-account"></i> Dashboard</a>
          <a href="<?= base_url('Securityadmin/forensic_captures'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-camera-account"></i> Forensic Captures</a>
        </div>
      </div>
    </div></div>

    <?php if ($blacklisted): ?>
      <div class="alert alert-danger">
        <strong><i class="mdi mdi-block-helper"></i> This IP is BLACKLISTED.</strong><br>
        Reason: <?= sec_e($blacklisted['reason']) ?><br>
        Blocked by: <?= sec_e($blacklisted['blocked_by']) ?> on <?= sec_e($blacklisted['blocked_at']) ?><br>
        Incident: <?= sec_e($blacklisted['incident_reference'] ?? 'N/A') ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info d-flex align-items-center justify-content-between no-print">
        <span>This IP is not currently blacklisted.</span>
        <form method="post" action="<?= base_url('Securityadmin/block_ip') ?>" class="form-inline">
          <input type="hidden" name="ip_address" value="<?= sec_e($ip) ?>">
          <input type="hidden" name="is_permanent" value="1">
          <input type="text" name="reason" class="form-control form-control-sm mr-2" placeholder="Reason" style="width:250px" required>
          <button type="submit" class="btn btn-danger btn-sm"><i class="mdi mdi-block-helper"></i> Block this IP</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Device Info -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-cellphone-information"></i> Device Information</div>
          <div class="card-body">
            <?php if (empty($devices)): ?>
              <p class="text-muted">No device information recorded.</p>
            <?php else: ?>
              <?php foreach ($devices as $d): ?>
                <div style="background:#f8f9fa;padding:.75rem;border-radius:6px;font-family:monospace;font-size:.75rem;word-break:break-all;margin-bottom:.5rem"><?= sec_e($d->user_agent ?? 'Unknown') ?></div>
                <?php if (!empty($d->device_fingerprint)): ?>
                  <small class="text-muted">Fingerprint: <?= sec_e($d->device_fingerprint) ?></small>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Accounts Touched -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-account-multiple"></i> Accounts Accessed (<?= count($accounts) ?>)</div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Account</th><th>Name</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($accounts as $a): ?>
                <tr>
                  <td style="font-family:monospace;font-weight:600"><?= sec_e($a->username) ?></td>
                  <td><?= sec_e($accountNames[$a->username] ?? '—') ?></td>
                  <td class="no-print"><a href="<?= base_url('Securityadmin/login_activity?username=' . urlencode($a->username)) ?>" class="btn btn-outline-primary btn-sm">View Logins</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card">
      <div class="card-header"><i class="mdi mdi-timeline-clock"></i> Activity Timeline</div>
      <div class="card-body">
        <?php if (empty($timeline)): ?>
          <p class="text-muted">No activity recorded for this IP.</p>
        <?php else: ?>
          <div style="position:relative;padding-left:2rem">
            <div style="position:absolute;left:.5rem;top:0;bottom:0;width:2px;background:#ddd"></div>
            <?php foreach ($timeline as $t): ?>
              <div style="position:relative;padding-bottom:1rem">
                <div style="position:absolute;left:-1.5rem;top:.3rem;width:12px;height:12px;border-radius:50%;background:#<?= $t['type']==='danger'?'dc3545':($t['type']==='success'?'28a745':'1a237e') ?>"></div>
                <div style="font-family:monospace;font-size:.78rem;color:#666"><?= sec_e($t['time']) ?></div>
                <div style="font-weight:600;font-size:.85rem"><?= sec_e($t['event']) ?></div>
                <div style="font-size:.8rem;color:#666"><?= sec_e($t['detail']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="row">
      <!-- Login Attempts -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-login-variant"></i> Login Attempts (<?= count($login_attempts) ?>)</div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Time</th><th>Account</th><th>Name</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($login_attempts as $l): ?>
                <tr>
                  <td><small style="font-family:monospace"><?= sec_e(substr($l['login_time'],0,16)) ?></small></td>
                  <td style="font-family:monospace;font-weight:600"><?= sec_e($l['username']) ?></td>
                  <td><small><?= sec_e($accountNames[$l['username']] ?? '—') ?></small></td>
                  <td>
                    <?php if ($l['status'] === 'success'): ?><span class="badge badge-success">SUCCESS</span>
                    <?php elseif ($l['status'] === 'failed'): ?><span class="badge badge-danger">FAILED</span>
                    <?php else: ?><span class="badge badge-secondary"><?= sec_e($l['status']) ?></span><?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Audit Trail -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-file-document-outline"></i> Audit Trail (<?= count($audit_entries) ?>)</div>
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead><tr><th>Time</th><th>Action</th><th>Account</th><th>Description</th></tr></thead>
              <tbody>
                <?php foreach ($audit_entries as $a): ?>
                <tr>
                  <td><small style="font-family:monospace"><?= sec_e(substr($a['event_time'],0,16)) ?></small></td>
                  <td><span class="badge badge-secondary"><?= sec_e($a['action']) ?></span></td>
                  <td><?= sec_e($displayName($a['username'] ?? '')) ?></td>
                  <td><small><?= sec_e($a['description'] ?? '') ?></small></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center py-3 no-print">
      <button onclick="window.print()" class="btn btn-primary"><i class="mdi mdi-printer"></i> Print / Save as PDF</button>
    </div>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

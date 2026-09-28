<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'views/security_partials.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="row"><div class="col-12">
      <div class="page-title-box d-flex justify-content-between align-items-center">
        <h4 class="page-title mb-0"><i class="mdi mdi-login-variant"></i> Login Activity</h4>
        <div>
          <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-shield-account"></i> Dashboard</a>
        </div>
      </div>
    </div></div>

    <!-- Filters -->
    <div class="card">
      <div class="card-body">
        <form method="get" class="form-inline">
          <div class="form-group mr-2 mb-2">
            <input type="text" name="ip" class="form-control form-control-sm" value="<?= sec_e($ip_filter) ?>" placeholder="IP Address" style="width:160px">
          </div>
          <div class="form-group mr-2 mb-2">
            <input type="text" name="username" class="form-control form-control-sm" value="<?= sec_e($user_filter) ?>" placeholder="Username" style="width:160px">
          </div>
          <div class="form-group mr-2 mb-2">
            <select name="status" class="form-control form-control-sm">
              <option value="">All Status</option>
              <option value="success" <?= $status_filter === 'success' ? 'selected' : '' ?>>Success</option>
              <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
              <option value="logout" <?= $status_filter === 'logout' ? 'selected' : '' ?>>Logout</option>
            </select>
          </div>
          <button type="submit" class="btn btn-sm btn-primary mb-2 mr-1"><i class="mdi mdi-magnify"></i> Filter</button>
          <a href="<?= base_url('Securityadmin/login_activity') ?>" class="btn btn-sm btn-outline-secondary mb-2">Clear</a>
        </form>
      </div>
    </div>

    <div class="mb-2 d-flex justify-content-between align-items-center flex-wrap">
      <span class="text-muted">Total: <strong><?= (int)$total ?></strong> records &middot; Page <strong><?= (int)$page ?></strong> of <strong><?= max(1, (int)ceil($total / $per_page)) ?></strong></span>
      <?php if ($total > 0): ?>
      <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#purgeLogsModal"><i class="mdi mdi-delete-sweep"></i> Purge Old Logs</button>
      <?php endif; ?>
    </div>

    <!-- Purge Logs Modal -->
    <div class="modal fade" id="purgeLogsModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post" action="<?= base_url('Securityadmin/purge_login_logs') ?>">
            <div class="modal-header"><h5 class="modal-title">Purge Login Logs</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
              <p class="text-muted">This will permanently delete login log records. This frees database storage but cannot be undone.</p>
              <div class="form-group">
                <label>Delete logs</label>
                <select name="days" class="form-control">
                  <option value="0">ALL logs (everything)</option>
                  <option value="1">Older than 1 day</option>
                  <option value="7">Older than 7 days</option>
                  <option value="30">Older than 30 days</option>
                  <option value="60">Older than 60 days</option>
                  <option value="90" selected>Older than 90 days</option>
                  <option value="180">Older than 180 days</option>
                  <option value="365">Older than 1 year</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This cannot be undone.')"><i class="mdi mdi-delete"></i> Purge</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="mdi mdi-format-list-bulleted"></i> Login Records</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Time</th><th>Username</th><th>Status</th><th>IP Address</th><th>Device</th><th style="width:52px"><span class="sr-only">Details</span></th></tr></thead>
          <tbody>
            <?php $loginRows = []; ?>
            <?php if (empty($logins)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No login records found.</td></tr>
            <?php else: ?>
              <?php foreach ($logins as $i => $l):
                $device = device_summary($l['user_agent'] ?? '');
                // Only what the panel shows — never password_attempt.
                $loginRows[] = [
                  'time'     => date('D, M j, Y · g:i:s A', strtotime((string)$l['login_time'])),
                  'username' => (string)($l['username'] ?? ''),
                  'role'     => (string)($l['actor_level'] ?? ''),
                  'status'   => (string)($l['status'] ?? ''),
                  'ip'       => (string)($l['ip_address'] ?? ''),
                  'device'   => $device,
                  'ua'       => (string)($l['user_agent'] ?? ''),
                  'session'  => (string)($l['session_id'] ?? ''),
                  'referrer' => (string)($l['referrer'] ?? ''),
                ];
              ?>
              <tr class="sd-clickable" data-sd-key="<?= (int)$i ?>" tabindex="0">
                <td><small style="font-family:monospace"><?= sec_e(substr($l['login_time'],0,19)) ?></small></td>
                <td style="font-family:monospace;font-weight:600"><?= sec_e($l['username'] ?? '—') ?></td>
                <td>
                  <?php if ($l['status'] === 'success'): ?>
                    <span class="badge badge-success">SUCCESS</span>
                  <?php elseif ($l['status'] === 'failed'): ?>
                    <span class="badge badge-danger">FAILED</span>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?= sec_e($l['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td style="font-family:monospace;font-weight:600">
                  <?php if (!empty($l['ip_address'])): ?>
                    <a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($l['ip_address'])) ?>" class="text-decoration-none" data-ip-summary data-sd-key="<?= sec_e($l['ip_address']) ?>" title="IP summary">
                      <?= sec_e($l['ip_address']) ?>
                    </a>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><small class="text-muted"><?= sec_e(device_label($l['user_agent'] ?? '') ?: '—') ?></small></td>
                <td class="text-right"><button type="button" class="sd-open-btn" data-sd-open aria-label="View sign-in details" title="Details"><i class="mdi mdi-chevron-right"></i></button></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($pagination)): ?>
      <div class="d-flex justify-content-center"><?= $pagination ?></div>
    <?php endif; ?>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
<script src="<?= base_url('assets/js/security-panels.js?v=2026092801'); ?>"></script>
<script>
(function () {
  if (!window.SideDrawer || !window.SecurityPanels) return;
  var SD = SideDrawer;
  var LOGINS = <?= json_encode($loginRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  var URLS = {
    summary: <?= json_encode(base_url('Securityadmin/ip_summary')) ?>,
    investigate: <?= json_encode(base_url('Securityadmin/investigate')) ?>
  };

  SecurityPanels.ipPanel({ url: URLS.summary, investigateUrl: URLS.investigate });

  SD.create({
    label: 'Sign-in details',
    nav: true,
    rowSelector: '.table tbody tr[data-sd-key]',
    render: function (key, panel) {
      var l = LOGINS[key];
      if (!l) return;
      var html = '<div class="sd-tags">' + SecurityPanels.statusPill(l.status) + (l.role ? '<span class="sd-tag">' + SD.esc(l.role) + '</span>' : '') + '</div>'
        + '<h5 class="sd-title sd-mono">' + SD.esc(l.username || '(blank username)') + '</h5>'
        + '<div class="sd-when"><i class="mdi mdi-clock-outline"></i> ' + SD.esc(l.time) + '</div>';
      html += SD.section('Sign-in', SD.grid([
        SD.field('IP address', l.ip ? '<span class="sd-mono">' + SD.esc(l.ip) + '</span>' : '', false, true),
        SD.field('Device', l.device ? l.device.type : ''),
        SD.field('Operating system', l.device ? l.device.os : ''),
        SD.field('Browser', l.device ? l.device.browser : ''),
        SD.field('Session', l.session ? '<span class="sd-mono">' + SD.esc(l.session.slice(0, 16)) + '\u2026</span>' : '', false, true),
        SD.field('Came from', l.referrer, true)
      ]) + (l.ua ? '<details class="sd-raw"><summary>User agent string</summary><pre>' + SD.esc(l.ua) + '</pre></details>' : ''));

      var ipSection = l.ip ? '<section class="sd-section" id="sdIpSummary"><h6>This IP address</h6>' + SD.skeleton() + '</section>' : '';
      panel.body(html + ipSection);
      panel.footer(l.ip ? SecurityPanels.investigateButton(URLS.investigate, l.ip) : null);

      if (!l.ip) return;
      SecurityPanels.load(URLS.summary, l.ip).then(function (data) {
        var target = panel.current === key && document.getElementById('sdIpSummary');
        if (target) target.innerHTML = '<h6>This IP address</h6>' + SecurityPanels.summaryHtml(data);
      }).catch(function (err) {
        var target = panel.current === key && document.getElementById('sdIpSummary');
        if (target) target.innerHTML = '<h6>This IP address</h6><p class="sd-note sd-error">' + SD.esc(err.message) + '</p>';
      });
    }
  });
})();
</script>
</body>
</html>

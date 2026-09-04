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
      <div class="card-header"><i class="mdi mdi-list"></i> Login Records</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Time</th><th>Username</th><th>Status</th><th>IP Address</th><th>User Agent</th><th>Session</th></tr></thead>
          <tbody>
            <?php if (empty($logins)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No login records found.</td></tr>
            <?php else: ?>
              <?php foreach ($logins as $l): ?>
              <tr>
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
                  <a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($l['ip_address'] ?? '')) ?>" class="text-decoration-none">
                    <?= sec_e($l['ip_address'] ?? '—') ?>
                  </a>
                </td>
                <td><small class="text-muted" style="max-width:250px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sec_e($l['user_agent'] ?? '—') ?></small></td>
                <td><small style="font-family:monospace" class="text-muted"><?= sec_e(substr($l['session_id'] ?? '',0,16)) ?></small></td>
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
</body>
</html>

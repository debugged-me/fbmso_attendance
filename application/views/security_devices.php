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
        <h4 class="page-title mb-0">Devices</h4>
        <div>
          <form method="post" action="<?= base_url('Securityadmin/purge_devices') ?>" style="display:inline"
                onsubmit="return confirm('Delete device records?\n\n0 = delete ALL\n30 = older than 30 days\n\nThis cannot be undone.')">
            <input type="number" name="days" value="0" min="0" max="365"
                   style="width:70px;display:inline-block" class="form-control form-control-sm d-inline-block"
                   title="0 = delete all, or enter days">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i> Purge</button>
          </form>
          <a href="<?= base_url('Security'); ?>" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
      </div>
    </div></div>

    <?php foreach (array('success', 'danger') as $k):
      if ($m = $this->session->flashdata($k)): ?>
        <div class="alert alert-<?= $k; ?>"><?= sec_e($m); ?></div>
    <?php endif; endforeach; ?>

    <div class="card"><div class="card-body">
      <form method="get" class="form-inline mb-3">
        <input type="text" name="u" class="form-control form-control-sm mr-2"
               placeholder="Filter by account, e.g. 2025-0116" value="<?= sec_e($q); ?>">
        <button class="btn btn-sm btn-primary">Search</button>
        <?php if ($q !== ''): ?>
          <a href="<?= base_url('Security/devices'); ?>" class="btn btn-sm btn-link">Clear</a>
        <?php endif; ?>
      </form>

      <p class="text-muted" style="font-size:13px">
        A device is a browser, not a phone. Clearing cookies or using a different browser
        shows up as a new device &mdash; so "new" is normal, and only matters combined with
        something else. Revoking one makes its next sign-in score as high risk.
      </p>

      <?php if (empty($devices)): ?>
        <p class="mb-0 text-muted">No devices recorded yet. They appear from the next sign-in onward.</p>
      <?php else: ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr>
          <th>Account</th><th>Device</th><th>Sign-ins</th><th>Last seen</th><th>Status</th><th class="text-right">Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($devices as $d): ?>
          <tr>
            <td><?= sec_e($d['username']); ?></td>
            <td><small><?= sec_device($d); ?></small></td>
            <td><?= (int)$d['login_count']; ?></td>
            <td><small><?= sec_e(sec_ago($d['last_seen_at'])); ?></small></td>
            <td>
              <?php if ($d['is_revoked']): ?><span class="badge badge-danger">revoked</span>
              <?php elseif ($d['is_trusted']): ?><span class="badge badge-success">trusted</span>
              <?php else: ?><span class="badge badge-light">seen</span><?php endif; ?>
            </td>
            <td class="text-right">
              <?php if (!$d['is_revoked']): ?>
              <form method="post" action="<?= base_url('Security/revoke_device'); ?>"
                    onsubmit="return confirm('Revoke this device for <?= sec_e($d['username']); ?>?');">
                <input type="hidden" name="username" value="<?= sec_e($d['username']); ?>">
                <input type="hidden" name="token_hash" value="<?= sec_e($d['device_token_hash']); ?>">
                <button class="btn btn-sm btn-outline-danger">Revoke</button>
              </form>
              <?php else: ?><small class="text-muted">&mdash;</small><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div></div>

  </div></div>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

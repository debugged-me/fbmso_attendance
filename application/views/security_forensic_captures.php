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
        <h4 class="page-title mb-0"><i class="mdi mdi-camera-account"></i> Forensic Captures</h4>
        <div>
          <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-shield-account"></i> Dashboard</a>
          <a href="<?= base_url('Securityadmin/login_activity'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-login-variant"></i> Login Activity</a>
        </div>
      </div>
    </div></div>

    <!-- Filters -->
    <div class="card">
      <div class="card-body">
        <form method="get" class="form-inline">
          <div class="form-group mr-2 mb-2">
            <label class="sr-only" for="ipFilter">IP</label>
            <input type="text" id="ipFilter" name="ip" class="form-control form-control-sm" value="<?= sec_e($ip_filter) ?>" placeholder="IP Address" style="width:160px">
          </div>
          <div class="form-group mr-2 mb-2">
            <label class="sr-only" for="userFilter">Username</label>
            <input type="text" id="userFilter" name="username" class="form-control form-control-sm" value="<?= sec_e($user_filter) ?>" placeholder="Username" style="width:160px">
          </div>
          <button type="submit" class="btn btn-sm btn-primary mb-2 mr-1"><i class="mdi mdi-magnify"></i> Filter</button>
          <a href="<?= base_url('Securityadmin/forensic_captures') ?>" class="btn btn-sm btn-outline-secondary mb-2">Clear</a>
        </form>
      </div>
    </div>

    <div class="mb-2 d-flex justify-content-between align-items-center flex-wrap">
      <span class="text-muted">Total: <strong><?= (int)$total ?></strong> captures &middot; Page <strong><?= (int)$page ?></strong> of <strong><?= max(1, (int)ceil($total / $per_page)) ?></strong></span>
      <?php if ($total > 0): ?>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#purgeModal"><i class="mdi mdi-delete-sweep"></i> Purge Old Captures</button>
      </div>
      <?php endif; ?>
    </div>

    <!-- Purge Modal -->
    <div class="modal fade" id="purgeModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post" action="<?= base_url('Securityadmin/purge_old_captures') ?>">
            <div class="modal-header"><h5 class="modal-title">Purge Captures</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
              <p class="text-muted">This will permanently delete forensic captures (photos + data). This action cannot be undone.</p>
              <div class="form-group">
                <label>Delete captures</label>
                <select name="days" class="form-control">
                  <option value="0">ALL captures (everything)</option>
                  <option value="1">Older than 1 day</option>
                  <option value="7">Older than 7 days</option>
                  <option value="30" selected>Older than 30 days</option>
                  <option value="60">Older than 60 days</option>
                  <option value="90">Older than 90 days</option>
                  <option value="180">Older than 180 days</option>
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

    <?php if (empty($captures)): ?>
      <div class="card">
        <div class="card-body text-center text-muted py-5">
          <i class="mdi mdi-camera-off" style="font-size:2.5rem"></i>
          <p class="mt-2">No forensic captures found.</p>
        </div>
      </div>
    <?php else: ?>

    <!-- Capture cards grid -->
    <div class="row">
      <?php foreach ($captures as $c): ?>
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
        <div class="card" style="transition:box-shadow .15s">
          <a href="<?= base_url('Securityadmin/forensic_detail?id=' . (int)$c['id']) ?>" class="text-decoration-none" style="color:inherit">
            <div style="width:100%;height:160px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:4px 4px 0 0">
              <?php if (!empty($c['photo_data'])): ?>
                <img src="<?= htmlspecialchars($c['photo_data']) ?>" style="width:100%;height:100%;object-fit:cover" alt="capture">
              <?php else: ?>
                <div class="text-muted text-center">
                  <i class="mdi mdi-camera-off" style="font-size:1.5rem;display:block"></i>
                  <small>No photo</small>
                </div>
              <?php endif; ?>
            </div>
            <div class="p-2">
              <div class="d-flex justify-content-between align-items-center">
                <span class="font-weight-bold" style="font-size:.85rem"><?= sec_e($c['username'] ?: 'Unknown') ?></span>
                <?php if ($c['consent_accepted']): ?>
                  <span class="badge badge-success">Agreed</span>
                <?php else: ?>
                  <span class="badge badge-danger">Declined</span>
                <?php endif; ?>
              </div>
              <div class="text-muted" style="font-size:.72rem;font-family:monospace"><?= sec_e(date('M j, Y g:i A', strtotime($c['captured_at']))) ?></div>
              <div class="mt-1" style="font-size:.72rem">
                <span style="font-family:monospace;font-weight:600"><?= sec_e($c['ip_address'] ?? '—') ?></span>
                <?php if ($c['latitude'] !== null): ?>
                  <span class="text-primary ml-1"><i class="mdi mdi-map-marker"></i> GPS</span>
                <?php endif; ?>
              </div>
              <div class="text-muted mt-1" style="font-size:.68rem"><?= sec_e($c['platform'] ?? '') ?> &middot; <?= sec_e($c['timezone'] ?? '') ?></div>
            </div>
          </a>
          <div class="p-2 pt-0 text-right">
            <form method="post" action="<?= base_url('Securityadmin/delete_capture') ?>" class="d-inline" onsubmit="return confirm('Delete this capture? Photo and data will be permanently removed.')">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm"><i class="mdi mdi-delete"></i> Delete</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($pagination)): ?>
      <div class="d-flex justify-content-center"><?= $pagination ?></div>
    <?php endif; ?>

    <?php endif; ?>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

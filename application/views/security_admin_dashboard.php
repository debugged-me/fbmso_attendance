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
        <h4 class="page-title mb-0"><i class="mdi mdi-shield-account"></i> Security Dashboard</h4>
        <div>
          <a href="<?= base_url('Securityadmin/forensic_captures'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-camera-account"></i> Forensic Captures</a>
          <a href="<?= base_url('Securityadmin/login_activity'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-login-variant"></i> Login Activity</a>
          <a href="<?= base_url('Securityadmin/investigate?ip=138.84.127.148'); ?>" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-magnify-scan"></i> Investigate Attacker</a>
        </div>
      </div>
    </div></div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= sec_e($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="alert alert-danger"><?= sec_e($m); ?></div>
    <?php endif; ?>

    <!-- Counters -->
    <div class="row">
      <?php
      $tiles = array(
        array('IP Blocks Today',         $blocked_today,          'danger',  'mdi-ip-network'),
        array('Profile Blocks Today',    $profile_blocked_today,  'warning', 'mdi-account-lock'),
        array('Failed Logins Today',     $failed_logins_today,    'danger',  'mdi-alert-circle'),
        array('Successful Logins Today', $successful_logins_today,'success', 'mdi-check-circle'),
      );
      foreach ($tiles as $t): ?>
        <div class="col-md-3 col-6">
          <div class="card"><div class="card-body text-center p-3">
            <i class="mdi <?= $t[2]; ?>" style="font-size:1.5rem;color:#<?= $t[2]==='danger'?'dc3545':($t[2]==='warning'?'ffc107':($t[2]==='success'?'28a745':'17a2b8')) ?>"></i>
            <h3 class="mb-0 mt-1"><?= (int)$t[1]; ?></h3>
            <small class="text-muted"><?= sec_e($t[0]); ?></small>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- IP Blacklist -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="mdi mdi-ip-network"></i> IP Blacklist</span>
        <button class="btn btn-sm btn-primary" onclick="document.getElementById('block-form').style.display='block'"><i class="mdi mdi-plus"></i> Block IP</button>
      </div>
      <div class="card-body">
        <div id="block-form" style="display:none;margin-bottom:1rem;padding:1rem;background:#f8f9fa;border-radius:8px;">
          <form method="post" action="<?= base_url('Securityadmin/block_ip') ?>">
            <div class="form-row">
              <div class="col-md-3 mb-2"><input type="text" name="ip_address" class="form-control" placeholder="IP Address" required></div>
              <div class="col-md-5 mb-2"><input type="text" name="reason" class="form-control" placeholder="Reason for blocking" required></div>
              <div class="col-md-2 mb-2"><input type="text" name="incident_reference" class="form-control" placeholder="Incident Ref"></div>
              <div class="col-md-2 mb-2 d-flex align-items-center">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="permBlock" name="is_permanent" value="1" checked>
                  <label class="custom-control-label" for="permBlock">Permanent</label>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-danger btn-sm"><i class="mdi mdi-block-helper"></i> Block IP</button>
          </form>
        </div>

        <?php if (empty($blacklisted_ips)): ?>
          <p class="text-muted text-center py-3">No IPs are currently blacklisted.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead><tr><th>IP Address</th><th>Reason</th><th>Blocked By</th><th>Date</th><th>Type</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($blacklisted_ips as $ip): ?>
                <tr>
                  <td><a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($ip['ip_address'])) ?>" style="font-family:monospace;font-weight:600"><?= sec_e($ip['ip_address']) ?></a></td>
                  <td><?= sec_e($ip['reason']) ?></td>
                  <td><?= sec_e($ip['blocked_by']) ?></td>
                  <td><small class="text-muted"><?= sec_e(substr($ip['blocked_at'],0,16)) ?></small></td>
                  <td><?php if ($ip['is_permanent']): ?><span class="badge badge-danger">Permanent</span><?php else: ?><span class="badge badge-warning">Temporary</span><?php endif; ?></td>
                  <td>
                    <form method="post" action="<?= base_url('Securityadmin/unblock_ip') ?>" class="d-inline" onsubmit="return confirm('Unblock this IP?')">
                      <input type="hidden" name="ip_address" value="<?= sec_e($ip['ip_address']) ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm"><i class="mdi mdi-delete"></i> Unblock</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Security Events -->
    <div class="card">
      <div class="card-header"><i class="mdi mdi-alert"></i> Recent Security Events</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Time</th><th>Event</th><th>Actor</th><th>Target</th><th>IP</th><th>Description</th></tr></thead>
          <tbody>
            <?php foreach ($recent_events as $e): ?>
            <tr>
              <td><small class="text-muted" style="font-family:monospace"><?= sec_e(substr($e['event_time'],0,16)) ?></small></td>
              <td><span class="badge badge-secondary"><?= sec_e($e['event_type']) ?></span></td>
              <td><?= sec_e($e['actor_username'] ?? '—') ?></td>
              <td><?= sec_e($e['target_username'] ?? '—') ?></td>
              <td style="font-family:monospace;font-weight:600">
                <?php if (!empty($e['ip_address'])): ?>
                  <a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($e['ip_address'])) ?>"><?= sec_e($e['ip_address']) ?></a>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><?= sec_e($e['description'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

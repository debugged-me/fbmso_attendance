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
      <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h4 class="up-page-title mb-0"><i class="mdi mdi-shield-account"></i> Security Dashboard</h4>
          <div class="up-page-sub">Forensics, IP blocks, and sign-in activity.</div>
          <hr class="up-divider" style="margin-bottom:0">
        </div>
        <div class="up-header-actions">
          <a href="<?= base_url('Securityadmin/login_activity'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-login-variant"></i> Login Activity</a>
          <a href="<?= base_url('Securityadmin/investigate?ip=138.84.127.148'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-magnify"></i> Investigate Attacker</a>
        </div>
      </div>
    </div></div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="up-flash up-flash-success"><?= sec_e($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="up-flash up-flash-danger"><?= sec_e($m); ?></div>
    <?php endif; ?>

    <!-- Counters -->
    <div class="nx-stats mb-4">
      <?php
      $tiles = array(
        array('IP Blocks Today',         $blocked_today,          'rose',   'mdi-ip-network',           '#ip-blacklist',                                   'Blacklist'),
        array('Profile Blocks Today',    $profile_blocked_today,  'violet', 'mdi-account-lock',         base_url('Securityadmin/audit_trail'),               'Audit trail'),
        array('Failed Logins Today',     $failed_logins_today,    'orange', 'mdi-alert-circle-outline', base_url('Securityadmin/login_activity?status=failed'), 'Failed logins'),
        array('Successful Logins Today', $successful_logins_today,'green',  'mdi-check-circle-outline', base_url('Securityadmin/login_activity?status=success'),'Login activity'),
      );
      foreach ($tiles as $t): ?>
        <a class="nx-stat <?= $t[2]; ?>" href="<?= $t[4]; ?>">
          <div class="nx-stat-main">
            <div>
              <div class="nx-stat-num"><?= number_format((int)$t[1]); ?></div>
              <div class="nx-stat-label"><?= sec_e($t[0]); ?></div>
            </div>
            <div class="nx-stat-icon"><i class="mdi <?= $t[3]; ?>"></i></div>
          </div>
          <div class="nx-stat-foot"><?= sec_e($t[5]); ?> <i class="mdi mdi-arrow-right"></i></div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- IP Blacklist -->
    <div class="up-card mb-4" id="ip-blacklist">
      <div class="up-card-head">
        <h5><i class="mdi mdi-ip-network"></i> IP Blacklist</h5>
        <button class="up-btn up-btn-primary up-btn-sm" onclick="var f=document.getElementById('block-form');f.style.display=(f.style.display==='none'?'block':'none')"><i class="mdi mdi-plus"></i> Block IP</button>
      </div>
      <div class="up-card-body" style="padding:0">
        <div id="block-form" style="display:none">
          <div class="up-panel" style="margin:18px 22px">
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
            <button type="submit" class="up-btn up-btn-danger up-btn-sm"><i class="mdi mdi-block-helper"></i> Block IP</button>
          </form>
          </div>
        </div>

        <?php if (empty($blacklisted_ips)): ?>
          <div class="up-empty"><i class="mdi mdi-ip-network-outline"></i>No IPs are currently blacklisted.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead><tr><th>IP Address</th><th>Reason</th><th>Blocked By</th><th>Date</th><th>Type</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($blacklisted_ips as $ip): ?>
                <tr>
                  <td><a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($ip['ip_address'])) ?>" data-ip-summary data-sd-key="<?= sec_e($ip['ip_address']) ?>" title="IP summary" style="font-family:monospace;font-weight:600"><?= sec_e($ip['ip_address']) ?></a></td>
                  <td><?= sec_e($ip['reason']) ?></td>
                  <td><?= sec_e($ip['blocked_by']) ?></td>
                  <td><small class="text-muted"><?= sec_e(substr($ip['blocked_at'],0,16)) ?></small></td>
                  <td><?php if ($ip['is_permanent']): ?><span class="badge badge-danger">Permanent</span><?php else: ?><span class="badge badge-warning">Temporary</span><?php endif; ?></td>
                  <td>
                    <form method="post" action="<?= base_url('Securityadmin/unblock_ip') ?>" class="d-inline" onsubmit="return confirm('Unblock this IP?')">
                      <input type="hidden" name="ip_address" value="<?= sec_e($ip['ip_address']) ?>">
                      <button type="submit" class="up-btn up-btn-danger up-btn-sm"><i class="mdi mdi-delete"></i> Unblock</button>
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
    <div class="up-card mb-4">
      <div class="up-card-head">
        <h5><i class="mdi mdi-alert-outline"></i> Recent Security Events</h5>
        <form method="post" action="<?= base_url('Securityadmin/purge_security_events') ?>" style="display:inline"
              onsubmit="return confirm('Delete security events?\n\n0 = delete ALL\n30 = delete older than 30 days\n\nThis cannot be undone.')">
          <input type="number" name="days" value="0" min="0" max="365"
                 style="width:70px;display:inline-block" class="form-control form-control-sm d-inline-block"
                 title="0 = delete all, or enter days">
          <button type="submit" class="up-btn up-btn-danger up-btn-sm"><i class="mdi mdi-delete"></i> Purge</button>
        </form>
      </div>
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
                  <a href="<?= base_url('Securityadmin/investigate?ip=' . urlencode($e['ip_address'])) ?>" data-ip-summary data-sd-key="<?= sec_e($e['ip_address']) ?>" title="IP summary"><?= sec_e($e['ip_address']) ?></a>
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
<?php include('includes/side_drawer.php'); ?>
<script src="<?= base_url('assets/js/security-panels.js?v=2026092801'); ?>"></script>
<script>
  if (window.SecurityPanels) {
    SecurityPanels.ipPanel({
      url: <?= json_encode(base_url('Securityadmin/ip_summary')) ?>,
      investigateUrl: <?= json_encode(base_url('Securityadmin/investigate')) ?>
    });
  }
</script>
</body>
</html>

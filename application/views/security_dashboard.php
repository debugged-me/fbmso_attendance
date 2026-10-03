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
          <h4 class="up-page-title mb-0"><i class="mdi mdi-shield-lock-outline"></i> Security</h4>
          <div class="up-page-sub">Sessions, devices, and account protections.</div>
          <hr class="up-divider" style="margin-bottom:0">
        </div>
        <div class="up-header-actions">
          <a href="<?= base_url('Security/sessions'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-account-clock-outline"></i> Active sessions</a>
          <a href="<?= base_url('Security/devices'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-cellphone-link"></i> Devices</a>
        </div>
      </div>
    </div></div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="up-flash up-flash-success"><?= sec_e($m); ?></div>
    <?php endif; ?>

    <!-- Audit trail integrity -->
    <?php if (!empty($chain['ok'])): ?>
      <div class="up-flash up-flash-success">
        <i class="mdi mdi-shield-check-outline"></i>
        Audit trail intact &mdash; <?= number_format((int)$chain['checked']); ?> records verified.
      </div>
    <?php endif; ?>

    <!-- Counters -->
    <div class="nx-stats mb-4">
      <?php
      $tiles = array(
        array('Sign-ins today',     $counts['logins_today'],    'blue',   'mdi-login-variant',          base_url('Securityadmin/login_activity'),                 'Login activity'),
        array('Failed attempts',    $counts['failed_today'],    'rose',   'mdi-alert-circle-outline',   base_url('Securityadmin/login_activity?status=failed'),   'Review failures'),
        array('New devices',        $counts['new_devices'],     'cyan',   'mdi-cellphone-link',         base_url('Security/devices'),                             'Known devices'),
        array('Blocked for retries',$counts['blocked'],         'orange', 'mdi-timer-lock-outline',     base_url('Securityadmin'),                                'IP blocks'),
        array('Active sessions',    $counts['active_sessions'], 'violet', 'mdi-account-clock-outline',  base_url('Security/sessions'),                            'Who is signed in'),
        array('Locked accounts',    $counts['locked_accounts'], 'green',  'mdi-lock-outline',           base_url('Securityadmin/login_activity?status=failed'),   'See attempts'),
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

    <!-- Devices seen on several accounts -->
    <?php if (!empty($shared_devices)): ?>
    <div class="up-card mb-4">
      <div class="up-card-head"><h5><i class="mdi mdi-devices"></i> One device, several accounts</h5></div>
      <div class="up-card-body" style="padding:0">
      <div class="up-note" style="margin:16px 22px">
        <i class="mdi mdi-information-outline"></i>
        <span>A browser signing into many accounts is what credential spraying looks like.
        A shared family phone looks the same &mdash; judge it on the timing:
        many accounts within minutes is an attack, spread over months is a shared device.</span>
      </div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Device</th><th>Accounts</th><th>First seen</th><th>Last seen</th></tr></thead>
        <tbody>
        <?php foreach ($shared_devices as $r): ?>
          <tr>
            <td><?= sec_e($r['device'] ?: ($r['model'] ?: 'Unidentified')); ?></td>
            <td><span class="badge badge-<?= (int)$r['accounts'] >= 5 ? 'danger' : 'warning'; ?>"><?= (int)$r['accounts']; ?></span></td>
            <td><small><?= sec_e(substr((string)$r['first_seen'], 0, 16)); ?></small></td>
            <td><small><?= sec_e(substr((string)$r['last_seen'], 0, 16)); ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Risky sign-ins -->
    <div class="up-card mb-4">
      <div class="up-card-head"><h5><i class="mdi mdi-shield-search"></i> Sign-ins worth a look</h5></div>
      <div class="up-card-body" style="padding:0">
      <div class="up-note" style="margin:16px 22px">
        <i class="mdi mdi-information-outline"></i>
        <span>Last 7 days, highest risk first. A score is a prompt to check, not a verdict.</span>
      </div>
      <?php if (empty($risky)): ?>
        <div class="up-empty"><i class="mdi mdi-shield-check-outline"></i>Nothing scored above zero. Quiet week.</div>
      <?php else: ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>When</th><th>Account</th><th>Risk</th><th>Device</th><th>Why</th></tr></thead>
        <tbody>
        <?php foreach ($risky as $r): ?>
          <tr>
            <td><small><?= sec_e(sec_ago($r['event_time'])); ?></small></td>
            <td><?= sec_e($r['target_username']); ?></td>
            <td><?= sec_risk_badge($r['risk_level'], $r['risk_score']); ?></td>
            <td><small><?= sec_device($r); ?></small></td>
            <td><small class="text-muted"><?= sec_e($r['risk_reason']); ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
      </div>
    </div>

    <!-- Account changes -->
    <div class="up-card mb-4">
      <div class="up-card-head"><h5><i class="mdi mdi-account-edit-outline"></i> Recent account changes</h5></div>
      <div class="up-card-body" style="padding:0">
      <div class="up-note" style="margin:16px 22px">
        <i class="mdi mdi-information-outline"></i>
        <span>Who changed what, from where. Last 7 days.</span>
      </div>
      <?php if (empty($changes)): ?>
        <div class="up-empty"><i class="mdi mdi-account-check-outline"></i>No profile or password changes.</div>
      <?php else: ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>When</th><th>Who</th><th>Field</th><th>Change</th><th>Device</th></tr></thead>
        <tbody>
        <?php foreach ($changes as $r):
          $actor = (string)$r['actor_username']; $target = (string)$r['target_username'];
          $byOther = ($actor !== '' && $target !== '' && $actor !== $target); ?>
          <tr>
            <td><small><?= sec_e(sec_ago($r['event_time'])); ?></small></td>
            <td>
              <?php if ($byOther): ?>
                <?= sec_e($actor); ?> <span class="text-muted">&rarr;</span> <strong><?= sec_e($target); ?></strong>
              <?php else: ?>
                <?= sec_e($actor ?: $target); ?> <small class="text-muted">(own)</small>
              <?php endif; ?>
            </td>
            <td><small><?= sec_e($r['changed_field'] ?: '-'); ?></small></td>
            <td><small>
              <?php if ($r['changed_field']): ?>
                <span class="text-muted"><?= sec_e($r['old_value'] !== '' ? $r['old_value'] : '(empty)'); ?></span>
                &rarr; <strong><?= sec_e($r['new_value'] !== '' ? $r['new_value'] : '(empty)'); ?></strong>
              <?php else: ?>
                <span class="text-muted">password changed</span>
              <?php endif; ?>
            </small></td>
            <td><small><?= sec_device($r); ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
      </div>
    </div>

  </div></div>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

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
        <h4 class="page-title mb-0">Security</h4>
        <div>
          <a href="<?= base_url('Security/sessions'); ?>" class="btn btn-sm btn-outline-primary">Active sessions</a>
          <a href="<?= base_url('Security/devices'); ?>" class="btn btn-sm btn-outline-primary">Devices</a>
        </div>
      </div>
    </div></div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= sec_e($m); ?></div>
    <?php endif; ?>

    <!-- Audit trail integrity -->
    <?php if (!empty($chain['ok'])): ?>
      <div class="alert alert-success py-2">
        Audit trail intact &mdash; <?= (int)$chain['checked']; ?> records verified.
      </div>
    <?php endif; ?>

    <!-- Counters -->
    <div class="row">
      <?php
      $tiles = array(
        array('Sign-ins today',   $counts['logins_today'],    ''),
        array('Failed attempts',  $counts['failed_today'],    $counts['failed_today'] > 20 ? 'text-danger' : ''),
        array('New devices',      $counts['new_devices'],     ''),
        array('Blocked for retries', $counts['blocked'],      ''),
        array('Active sessions',  $counts['active_sessions'], ''),
        array('Locked accounts',  $counts['locked_accounts'], ''),
      );
      foreach ($tiles as $t): ?>
        <div class="col-md-2 col-6">
          <div class="card"><div class="card-body text-center p-3">
            <h3 class="mb-0 <?= $t[2]; ?>"><?= (int)$t[1]; ?></h3>
            <small class="text-muted"><?= sec_e($t[0]); ?></small>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Devices seen on several accounts -->
    <?php if (!empty($shared_devices)): ?>
    <div class="card"><div class="card-body">
      <h5 class="mb-1">One device, several accounts</h5>
      <p class="text-muted mb-3" style="font-size:13px">
        A browser signing into many accounts is what credential spraying looks like.
        A shared family phone looks the same &mdash; judge it on the timing:
        many accounts within minutes is an attack, spread over months is a shared device.
      </p>
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
    </div></div>
    <?php endif; ?>

    <!-- Risky sign-ins -->
    <div class="card"><div class="card-body">
      <h5 class="mb-1">Sign-ins worth a look</h5>
      <p class="text-muted mb-3" style="font-size:13px">Last 7 days, highest risk first. A score is a prompt to check, not a verdict.</p>
      <?php if (empty($risky)): ?>
        <p class="mb-0 text-muted">Nothing scored above zero. Quiet week.</p>
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
    </div></div>

    <!-- Account changes -->
    <div class="card"><div class="card-body">
      <h5 class="mb-1">Recent account changes</h5>
      <p class="text-muted mb-3" style="font-size:13px">Who changed what, from where. Last 7 days.</p>
      <?php if (empty($changes)): ?>
        <p class="mb-0 text-muted">No profile or password changes.</p>
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
    </div></div>

  </div></div>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

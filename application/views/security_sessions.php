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
        <h4 class="page-title mb-0">Who is signed in right now</h4>
        <a href="<?= base_url('Security'); ?>" class="btn btn-sm btn-outline-secondary">Back to Security</a>
      </div>
    </div></div>

    <?php foreach (array('success' => 'success', 'danger' => 'danger') as $k => $cls):
      if ($m = $this->session->flashdata($k)): ?>
        <div class="alert alert-<?= $cls; ?>"><?= sec_e($m); ?></div>
    <?php endif; endforeach; ?>

    <div class="card"><div class="card-body">
      <p class="text-muted" style="font-size:13px">
        Ending a session signs that person out on their next click. It does <strong>not</strong> change
        their password &mdash; if someone else knows it, they can sign straight back in. Reset the
        password as well.
      </p>

      <?php if (empty($sessions)): ?>
        <p class="mb-0 text-muted">Nobody is signed in.</p>
      <?php else: ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr>
          <th>Account</th><th>Device</th><th>Last active</th><th>Signed in</th><th class="text-right">Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($sessions as $s):
          $isMe = $registry->isCurrent($s); ?>
          <tr<?= $isMe ? ' class="table-active"' : ''; ?>>
            <td>
              <?= sec_e($s['username']); ?>
              <?php if ($isMe): ?><span class="badge badge-secondary">this is you</span><?php endif; ?>
            </td>
            <td><small><?= sec_device($s); ?></small></td>
            <td><small><?= sec_e(sec_ago($s['last_activity_at'])); ?></small></td>
            <td><small class="text-muted"><?= sec_e(substr((string)$s['created_at'], 0, 16)); ?></small></td>
            <td class="text-right">
              <?php if (!$isMe): ?>
              <form method="post" action="<?= base_url('Security/revoke_session'); ?>" style="display:inline"
                    onsubmit="return confirm('Sign out <?= sec_e($s['username']); ?> on this device?');">
                <input type="hidden" name="reference" value="<?= sec_e($s['session_reference']); ?>">
                <button class="btn btn-sm btn-outline-danger">Sign out</button>
              </form>
              <form method="post" action="<?= base_url('Security/revoke_user'); ?>" style="display:inline"
                    onsubmit="return confirm('Sign out <?= sec_e($s['username']); ?> everywhere?');">
                <input type="hidden" name="username" value="<?= sec_e($s['username']); ?>">
                <button class="btn btn-sm btn-outline-danger">All devices</button>
              </form>
              <?php else: ?>
                <small class="text-muted">&mdash;</small>
              <?php endif; ?>
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

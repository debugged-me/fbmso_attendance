<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- Title -->
          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4 class="up-page-title">Change Password</h4>
                <div class="up-page-sub">Keep your account secure with a strong password.</div>
                <hr class="up-divider" />
              </div>
            </div>
          </div>

          <!-- Flash messages -->
          <?php $flashMsg = $this->session->flashdata('msg'); ?>
          <?php if ($flashMsg): ?>
            <div class="up-flash up-flash-success"><?= $flashMsg; ?></div>
          <?php endif; ?>
          <?php if (validation_errors() != NULL): ?>
            <div class="up-flash up-flash-danger"><?= validation_errors(); ?></div>
          <?php endif; ?>

          <!-- Identity strip -->
          <div class="up-id-strip">
            <div class="up-id-icon"><i class="mdi mdi-lock-reset"></i></div>
            <div>
              <div class="up-id-name"><?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="up-id-meta">Password security</div>
            </div>
          </div>

          <!-- Form card -->
          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-form-textbox-password"></i> Update Password</h4>
                </div>
                <div class="up-card-body">
                  <form method="POST" action="<?= base_url(); ?>page/update_password" enctype="multipart/form-data">
                    <input type="hidden" name="txt_hidden" value="<?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="up-section-head">
                      <div class="up-section-dot"></div>
                      <div class="up-section-label">Credentials</div>
                      <div class="up-section-line"></div>
                    </div>

                    <div class="form-group">
                      <label for="currentpassword">Current Password</label>
                      <input type="password" class="form-control" id="currentpassword" name="currentpassword" placeholder="Enter your current password" required>
                    </div>
                    <div class="form-group">
                      <label for="newpassword">New Password</label>
                      <input type="password" class="form-control" id="newpassword" name="newpassword" placeholder="Enter your new password" minlength="8" required>
                      <div class="up-hint">Use at least 8 characters with a mix of letters, numbers, and symbols.</div>
                    </div>
                    <div class="form-group">
                      <label for="cnewpassword">Confirm Password</label>
                      <input type="password" class="form-control" id="cnewpassword" name="cnewpassword" placeholder="Repeat your new password" required>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                      <button type="submit" name="updatePwd" class="up-btn up-btn-primary">
                        <i class="mdi mdi-content-save"></i> Update Password
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>

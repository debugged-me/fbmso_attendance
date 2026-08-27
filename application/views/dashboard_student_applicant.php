<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">

    <!-- Topbar -->
    <?php include('includes/top-nav-bar.php'); ?>
    <!-- Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- Flash message (success / error from registration, email send, etc.) -->
          <?php if ($this->session->flashdata('msg')): ?>
            <div class="row mt-2">
              <div class="col-12">
                <?php echo $this->session->flashdata('msg'); ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Hero -->
          <div class="row mt-2">
            <div class="col-12">
              <div class="card border-0 shadow-sm overflow-hidden">
                <div class="row g-0 align-items-center">
                  <div class="col-lg-6 p-4 p-lg-5">
                    <h1 class="mb-2">Welcome to the SRMS Attendance Portal</h1>
                    <p class="text-muted mb-3">
                      Create your account, confirm your email, and access the student portal. If you already registered,
                      use the buttons below to log in or resend your activation email.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                      <a href="<?= base_url('Registration'); ?>" class="btn btn-primary btn-lg">
                        Create my account
                      </a>
                      <a href="<?= base_url('Login'); ?>" class="btn btn-outline-secondary btn-lg">
                        I already have an account
                      </a>
                      <a href="<?= base_url('VerifyEmail'); ?>" class="btn btn-link btn-lg px-0">
                        Resend activation email
                      </a>
                    </div>
                    <div class="mt-3 small text-muted">
                      Having trouble? Contact support at <a href="mailto:no-reply@srmsportal.com">no-reply@srmsportal.com</a>.
                    </div>
                  </div>
                  <div class="col-lg-6 d-none d-lg-block">
                    <img
                      src="<?= base_url(); ?>assets/images/default-applicant.png"
                      alt="SRMS Attendance"
                      class="img-fluid w-100 h-100"
                      style="object-fit: cover; min-height: 360px;">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick cards -->
          <div class="row mt-3">
            <div class="col-md-4">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <h4 class="card-title mb-2">1) Register</h4>
                  <p class="text-muted">
                    Fill out the online registration form to create your portal account.
                  </p>
                  <a href="<?= base_url('Registration'); ?>" class="btn btn-soft-primary">Start registration</a>
                </div>
              </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <h4 class="card-title mb-2">2) Confirm Email</h4>
                  <p class="text-muted">
                    We’ll send an activation link to your email. Didn’t get it?
                  </p>
                  <a href="<?= base_url('VerifyEmail'); ?>" class="btn btn-soft-info">Resend activation</a>
                </div>
              </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <h4 class="card-title mb-2">3) Log In</h4>
                  <p class="text-muted">
                    After activating your account, log in to access the portal.
                  </p>
                  <a href="<?= base_url('Login'); ?>" class="btn btn-soft-success">Go to login</a>
                </div>
              </div>
            </div>
          </div>

          <!-- Help / FAQ (optional) -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card shadow-sm">
                <div class="card-body">
                  <h4 class="mb-2">Need help?</h4>
                  <ul class="mb-0 text-muted">
                    <li>Make sure your email is correct—check Spam/Promotions for the activation mail.</li>
                    <li>To resend the activation link, click <a href="<?= base_url('VerifyEmail'); ?>">Resend activation email</a>.</li>
                    <li>If email still doesn’t arrive, contact <a href="mailto:no-reply@srmsportal.com">no-reply@srmsportal.com</a>.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /.container-fluid -->
      </div><!-- /.content -->

      <?php include('includes/footer.php'); ?>
    </div><!-- /.content-page -->

  </div><!-- /#wrapper -->

  <!-- Right Sidebar (if you use it) -->
  <?php include('includes/themecustomizer.php'); ?>

  <!-- Vendor js -->
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <script defer src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
</body>
</html>

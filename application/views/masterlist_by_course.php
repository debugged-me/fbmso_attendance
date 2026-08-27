<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body class="masterlist-page">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('includes/top-nav-bar.php'); ?>
        <!-- end Topbar --> <!-- ========== Left Sidebar Start ========== -->

        <!-- Lef Side bar -->
        <?php include('includes/sidebar.php'); ?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Masterlist By Course</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <h4 class="m-t-0 header-title mb-4"><b>Masterlist By Course<br />
                                        </b></h4>
                                    <!--<table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                         -->
                                 <table class="table table-bordered">
    <thead>
        <tr>
            <th>Course</th>
            <th>Major</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
   <tbody>
  <?php foreach ($data as $row): ?>
    <?php
      $ln = trim($row->LastName ?? '');
      $fn = trim($row->FirstName ?? '');
      $mn = trim($row->MiddleName ?? '');
      $fullname = trim(($ln ? $ln : '') . ($ln || $fn ? ', ' : '') . ($fn ? $fn : '') . ($mn ? ' ' . $mn : ''));
      if ($fullname === '' && !empty($row->StudentNumber)) $fullname = $row->StudentNumber;

      $studno = $row->StudentNumber ?? '';
      $bdate  = !empty($row->birthDate) ? $row->birthDate : 'â€”';
      $yl     = $row->yearLevel ?? '';
      $sec    = $row->section ?? '';
      $stat   = $row->signupStatus ?? '';
    ?>
    <tr>
      <td>
        <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
        <span class="badge badge-warning ml-2">Signup</span>
        <?php if ($yl || $sec): ?>
          <div class="text-muted small"><?= htmlspecialchars("$yl $sec", ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($stat): ?>
          <div class="text-muted small">Status: <?= htmlspecialchars($stat, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?= htmlspecialchars($bdate, ENT_QUOTES, 'UTF-8'); ?></td>
      <td style="text-align:center">
        <a href="<?= base_url(); ?>Page/studentsprofile?id=<?= urlencode($studno); ?>" class="text-success">
          <i class="mdi mdi-face-profile"></i> Profile
        </a>&nbsp;&nbsp;&nbsp;&nbsp;

        <!-- If you want to delete signup (not profile), route to a signup delete action -->
        <a href="<?= base_url(); ?>Page/deleteSignup?id=<?= urlencode($studno); ?>"
           data-ui-confirm="This removes the student's signup record. This cannot be undone."
           data-ui-confirm-title="Delete this signup record?"
           data-ui-confirm-ok="Delete signup"
           class="text-danger">
          <i class="mdi mdi-delete-empty-outline"></i> Delete
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>

</table>

                                    <hr>
                                  
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                <!-- end container-fluid -->

            </div>
            <!-- end content -->



            <!-- Footer Start -->
            <?php include('includes/footer.php'); ?>
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->


    <!-- Right Sidebar -->
    <?php include('includes/themecustomizer.php'); ?>
    <!-- /Right-bar -->


    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <!-- Chat app -->
    <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>

    <!-- Todo app -->
    <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>

    <!--Morris Chart-->
    <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>

    <!-- Sparkline charts -->
    <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>

    <!-- Dashboard init JS -->
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <!-- Required datatable js -->
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>

    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

</body>

</html>


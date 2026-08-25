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
                                <h4 class="page-title">Masterlist by Section</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <!-- <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li> -->
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <!-- end page title -->
                    <div class="row">
                        <?php if (isset($data) && !empty($data)): ?>
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body table-responsive">
                                        <h4 class="m-t-0 header-title mb-4">
                                            <b>
                                                <?php echo $course_description; ?>
                                                <?php if (!empty($major)) : ?>
                                                    â€” Major in <?php echo $major; ?>
                                                <?php endif; ?>
                                            </b>
                                            <br />
                                            <span class="badge badge-purple mb-2">
                                                <?php echo $this->session->userdata('semester'); ?>, SY <?php echo $this->session->userdata('sy'); ?>
                                            </span><br />
                                            <span class="text-muted">Section: <?php echo $Section; ?></span>
                                        </h4>

                                        <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Last Name</th>
                                                    <th>First Name</th>
                                                    <th>Middle Name</th>
                                                    <th>Student No.</th>
                                                    <th>Year Level</th>
                                                    <th>Section</th>
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
                                    </div>
                                </div>
                            </div>

                            <!-- <div class="row">
								<div class="col-lg-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title mb-4">Section Summary</h4>

                                         <table class="table table-striped table-valign-middle">
										  <thead>
										  <tr>
											<th style="text-align:left">Section</th>
											<th style="text-align:center">Counts</th>
										  </tr>
										  </thead>
										  <tbody>
										  <?php
                                            foreach ($data2 as $row) {
                                            ?>
										  <tr>
											<td><?php echo $row->Section; ?>
											</td>
											<td style="text-align:center"><?php echo $row->Counts; ?></td>
										  </tr>
												<?php } ?>	
										  </tbody>
										</table>
                                    </div>
									</div> -->
                            <!-- end card -->
                            <!-- </div>
								<div class="col-lg-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title mb-4">Year Level Summary</h4>

                                         <table class="table table-striped table-valign-middle">
										  <thead>
										  <tr>
											<th style="text-align:left">Year Level</th>
											<th style="text-align:center">Counts</th>
										  </tr>
										  </thead>
                                          <tbody>
  <?php
                            foreach ($data1 as $row) {
    ?>
  <tr>
    <td><?php echo $row->YearLevel; ?></td>
    <td style="text-align:center"> -->
                            <!-- Make the count value clickable with course and year level parameters -->
                            <!-- <a href="<?= base_url('Page/yearLevelDetails?yearLevel=' . $row->YearLevel . '&course=' . urlencode($this->input->get('course')) . '&sy=' . $this->session->userdata('sy') . '&semester=' . $this->session->userdata('semester')); ?>">
        <?php echo $row->yearLevelCounts; ?>
      </a>
    </td>
  </tr>
  <?php } ?>
</tbody>
										</table>
                                    </div>
									</div> -->
                            <!-- end card -->
                    </div>
                </div>

            </div>
        <?php endif; ?>

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
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <!-- Required datatable js -->
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

</body>

</html>


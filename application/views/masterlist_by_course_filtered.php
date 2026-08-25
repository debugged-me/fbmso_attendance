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
                                <!-- <h4 class="page-title">Masterlist by Course</h4> -->
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

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <h4 class="m-t-0 header-title mb-4">
                                        <b>
                                            <?php echo $selectedCourse; ?>
                                            <?php if (!empty($selectedMajor)) : ?>
                                                â€” Major in <?php echo $selectedMajor; ?>
                                            <?php endif; ?>

                                        </b>
                                        <br />
                                        <span class="badge badge-purple mb-3">
                                            <?php echo $this->session->userdata('semester'); ?>, SY <?php echo $this->session->userdata('sy'); ?>
                                        </span>
                                    </h4>

                                    <?php echo $this->session->flashdata('msg'); ?>
                                    <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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


                                            <?php
                                            $i = 1;
                                            foreach ($data as $row) {
                                                echo "<tr>";
                                                echo "<td>" . $row->LastName . "</td>";
                                                echo "<td>" . $row->FirstName . "</td>";
                                                echo "<td>" . $row->MiddleName . "</td>";
                                            ?>
                                                <td><?php echo $row->StudentNumber; ?></a></td>
                                                <td><?php echo $row->YearLevel; ?></td>
                                                <td><?php echo $row->Section; ?></td>
                                                <!--
                                         <td><a href="<?= base_url(); ?>Masterlist/bySection?section=<?php echo $row->Section; ?>&semester=<?php echo $row->Semester; ?>sy=<?php echo $row->SY; ?>"><?php echo $row->Section; ?></a></td>
                                        -->
                                            <?php
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mb-4">Section Summary</h4>

                                    <table class="table table-striped table-valign-middle">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left">Section</th>
                                                <th style="text-align:center">Counts</th>
                                                <th style="text-align:center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data2 as $row) { ?>
                                                <tr>
                                                    <td><?php echo $row->Section; ?></td>
                                                    <td style="text-align:center"><?php echo $row->Counts; ?></td>
                                                    <td style="text-align:center">
                                                        <a href="<?= base_url(
                                                                        'Page/masterlistBySectionFiltered?section=' . urlencode($row->Section) .
                                                                            '&course=' . urlencode($selectedCourse ?? '') .
                                                                            '&major='  . urlencode($selectedMajor ?? '')
                                                                    ); ?>" class="btn btn-primary btn-sm">
                                                            View List
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- end card -->
                        </div>

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
                                                    <td style="text-align:center">
                                                        <!-- Make the count value clickable with course and year level parameters -->
                                                        <a href="<?= base_url('Page/yearLevelDetails?yearLevel=' . $row->YearLevel . '&course=' . urlencode($this->input->get('course')) . '&sy=' . $this->session->userdata('sy') . '&semester=' . $this->session->userdata('semester')); ?>">
                                                            <?php echo $row->yearLevelCounts; ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- end card -->
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


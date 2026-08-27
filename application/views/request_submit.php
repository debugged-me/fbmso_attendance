<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body>

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
                                <h4 class="page-title">Submit Request</h4>
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
                                    <!--<h4 class="m-t-0 header-title mb-4"><b>REQUEST SUBMISSION</b></h4>-->

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4 class="m-t-0 header-title mb-2"><b>REQUEST FORM</b> <br />
                                                <span class="badge badge-primary mb-3"><?php echo $_GET['fname'] . ' ' . $_GET['mname'] . ' ' . $_GET['lname']; ?></span>
                                            </h4>
                                            <form enctype='multipart/form-data' method="post">
                                                <div class="form-group">
                                                    <label>Student No.</label>
                                                    <input type="text" class="form-control" name="StudentNumber" value="<?php if ($this->session->userdata('level') === 'Student') {
                                                                                                                            echo $this->session->userdata('username');
                                                                                                                        } else {
                                                                                                                            echo $_GET['id'];
                                                                                                                        }; ?>" readonly required>

                                                </div>

                                                <div class="form-group">
                                                    <label>Tracking No.</label>
                                                    <input type="text" class="form-control" name="trackingNo" value="<?php if (!$data2) {
                                                                                                                            //the value is null
                                                                                                                            echo "20200001";
                                                                                                                        } else {
                                                                                                                            echo $data2[0]->trackingNo + 1;
                                                                                                                        } ?>" readonly required>
                                                </div>

                                                <div class="form-group">
                                                    <label>Document to Request</label>
                                                    <select class="form-control" name="docName" required>
                                                        <option></option>
                                                        <option>Certification</option>
                                                        <option>Honorable Dismissal</option>
                                                        <option>TOR</option>
                                                        <option>TOR/Honorable Dismissal</option>

                                                    </select>

                                                </div>
                                                <div class="form-group">
                                                    <label>Purpose</label>
                                                    <input type="text" class="form-control" name="purpose" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Payment Reference <span style="color:red;"><small>(O.R. No./Transaction No., N/A if not applicable)</small></span></label>
                                                    <input type="text" class="form-control" name="pReference" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Attachment </label>
                                                    <input type="file" class="form-control" name="nonoy">
                                                </div>
                                                <input type="hidden" class="form-control" name="email" value="<?php echo $_GET['email']; ?>" required>
                                                <input type="hidden" class="form-control" name="fname" value="<?php echo $_GET['fname']; ?>" required>
                                                <div class="box-footer">
                                                    <input type="submit" name="submit" class="btn btn-info float-md-right" value="Submit Request">
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="m-t-0 header-title mb-2"><b>Requested Documents</b></h4>
                                            <table class="table mb-0">

                                                <tbody>
                                                    <tr>
                                                        <th>Requested Document</td>
                                                        <th>Date Requested</td>
                                                        <th>Tracking</td>
                                                    </tr>
                                                    <?php
                                                    foreach ($data as $row) {
                                                        echo "<tr>";
                                                    ?>
                                                        <td><?php echo $row->docName; ?></td>
                                                        <td><?php echo $row->dateReq . ' ' . $row->timeReq; ?></td>
                                                        <td><a href="<?= base_url(); ?>Page/studentRequestStat?trackingNo=<?php echo $row->trackingNo; ?>"><button type="button" class="btn btn-primary btn-xs"><?php echo $row->trackingNo; ?></button></a></td>
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
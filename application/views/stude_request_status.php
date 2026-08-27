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
                                <!-- <h4 class="page-title">Request Status</h4> -->
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="m-t-0 header-title mb-4"><b>Request Tracking</b></h4>

                                    <table>
                                        <tr>
                                            <td>Document Requested</td>
                                            <td>: <b><?php echo $data[0]->docName; ?></b></td>
                                        </tr>
                                        <tr>
                                            <td>Tracking No.</td>
                                            <td>: <b><?php echo $data[0]->trackingNo; ?></b></td>
                                        </tr>
                                        <tr>
                                            <td>Date Requested</td>
                                            <td>: <b><?php echo $data[0]->dateReq; ?>, <?php echo $data[0]->timeReq; ?></b></td>
                                        </tr>
                                        <tr>
                                            <td>Payment Reference</td>
                                            <td>: <b><?php echo $data[0]->pReference; ?></b></td>
                                        </tr>

                                    </table>
                                    <div class="timeline timeline-left">
                                        <article class="timeline-item alt">
                                            <div class="text-left">
                                                <div class="time-show first">

                                                    <a href="#" data-toggle="modal" data-target="#reqstat" class="btn btn-primary w-lg">Action</a>

                                                </div>
                                            </div>
                                        </article>

                                        <?php
                                        foreach ($data as $row) {
                                        ?>
                                            <article class="timeline-item">
                                                <div class="timeline-desk">
                                                    <div class="panel">
                                                        <div class="timeline-box">
                                                            <span class="arrow"></span>
                                                            <span class="timeline-icon bg-success"><i class="mdi mdi-checkbox-blank-circle-outline"></i></span>
                                                            <h4 class=""><?php echo $row->procDate; ?></h4>
                                                            <p class="timeline-date text-muted"><small><?php echo $row->procTime; ?></small></p>
                                                            <p style="color:#010752"><strong><?php echo $row->reqStatus; ?> </strong></p>
                                                            <p>Processed by: <?php echo $row->procBy; ?> </p>
                                                            <p>Attachment: <a href="<?= base_url(); ?>upload/reqDocs/<?php echo $row->fileAttachment; ?>" target="_blank"><?php echo $row->fileAttachment; ?></a> </p>
                                                            <p>Status: <span style="color:red"><?php echo $row->currentStat; ?></span> </p>



                                                        </div>
                                                    </div>
                                                </div>
                                            </article>

                                        <?php } ?>
                                    </div>
                                    <!-- end timeline -->
                                </div>
                                <!-- end card-body -->
                            </div>
                            <!-- end card -->
                        </div>
                        <!-- end col -->
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

<div class="modal fade" id="reqstat" tabindex="-1" role="dialog" aria-labelledby="forgotModalLabel" style="color:black">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="forgotModalLabel">Add Status</h4>
            </div>
            <div class="modal-body">

                <form id="updateRequest" enctype='multipart/form-data' method="post">
                    <input type="hidden" name="trackingNo" value="<?php echo $data[0]->trackingNo; ?>" class="form-control" placeholder="Add Status" required>
                    <input type="hidden" name="StudentNumber" value="<?php echo $data[0]->StudentNumber; ?>" class="form-control" placeholder="Add Status" required>


                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="text-muted" for="lastName">Notes/Message <span style="color:red">*</span></label>
                                <input type="text" name="reqStatus" id="reqStatus" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="text-muted" for="lastName">Status <span style="color:red">*</span></label>
                                <select class="form-control" name="ongoingStat">
                                    <option value="On Process">On Process</option>
                                    <option value="Released">Released</option>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="text-muted" for="lastName">Attachment </label>
                                <input type="file" class="form-control" name="nonoy">
                            </div>
                        </div>
                    </div>

                    <?php if ($this->session->userdata('level') === 'Student') {
                    ?>
                        <input type="hidden" value="Open" name="reqStat">
                    <?php } else { ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="text-muted" for="lastName">End of the Process? <span style="color:red">*</span></label>
                                    <select class="form-control" name="reqStat">
                                        <option value="Open">No</option>
                                        <option value="Closed">Yes</option>
                                </div>
                            </div>
                        </div>

                    <?php } ?>

                    <div class="row">
                        <div class="col-12">
                            <input type="submit" name="submit" class="btn btn-info float-md-right" value="Post Update">
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

</html>
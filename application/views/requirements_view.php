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
                                <!-- <h4 class="page-title">Submit Request</h4> -->
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
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
                                        <div class="col-md-12">
                                            <h4 class="m-t-0 header-title mb-2">
                                                Requirements for <?php echo $student->FullName ?? $student->StudentNumber; ?>
                                                <span class="badge badge-info ml-2">* Only PDF files, max size 2MB</span>
                                            </h4>
                                            <table class="table mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Requirements</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                        <th>Comment</th>
                                                        <th>Submitted On</th>
                                                        <th>File</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($requirements as $req): ?>
                                                        <tr>
                                                            <td><?php echo $req->name; ?></td>
                                                            <td><?php echo $req->description; ?></td>
                                                            <td>
                                                                <?php
                                                                if (!$req->date_submitted) {
                                                                    echo '<span class="badge bg-secondary">Not Submitted</span>';
                                                                } elseif ($req->is_verified == 1) {
                                                                    echo '<span class="badge bg-success">Verified</span>';
                                                                } elseif ($req->is_verified == 2) {
                                                                    echo '<span class="badge bg-danger">Disapproved</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                                }
                                                                ?>

                                                            </td>
                                                            <td><?php echo $req->comment; ?></td>
                                                            <td><?php echo $req->date_submitted ?? '—'; ?></td>
                                                            <td>
                                                                <?php if ($req->file_path): ?>
                                                                    <button type="button" class="btn btn-info btn-sm viewFileBtn"
                                                                        data-fileurl="<?php echo base_url($req->file_path); ?>"
                                                                        data-filename="<?php echo $req->name; ?>">
                                                                        View File
                                                                    </button>
                                                                <?php else: ?>
                                                                    <form method="post" enctype="multipart/form-data" action="<?php echo site_url('Student/submit_requirement'); ?>">
                                                                        <input type="hidden" name="student_number" value="<?php echo $student->StudentNumber; ?>">
                                                                        <input type="hidden" name="requirement_id" value="<?php echo $req->req_id; ?>">
                                                                        <input type="file" name="document" required>
                                                                        <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
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

    <!-- View File Modal -->
    <div class="modal fade" id="viewFileModal" tabindex="-1" role="dialog" aria-labelledby="viewFileLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submitted Requirement</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <iframe id="fileViewer" src="" width="100%" height="600px" style="border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>


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

    <script>
        $(document).ready(function() {
            $('.viewFileBtn').on('click', function() {
                var fileUrl = $(this).data('fileurl');
                var fileName = $(this).data('filename');

                $('#fileViewer').attr('src', fileUrl);
                $('#viewFileModal .modal-title').text(fileName);
                $('#viewFileModal').modal('show');
            });
        });
    </script>


</body>

</html>
<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

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

                    <!-- Title + actions -->
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Expenses Report</h4>
                            <div class="up-page-sub">All expenses across all categories and dates.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <a href="#custom-modal" class="up-btn up-btn-primary" data-animation="fadein" data-plugin="custommodal" data-overlayspeed="200" data-overlaycolor="#36404a">
                                <i class="mdi mdi-plus-circle"></i> Add New Expense
                            </a>
                        </div>
                    </div>


                    <!-- end page title -->
                    <div class="row">

                        <div class="col-md-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-file-document-multiple-outline"></i> All Expenses</h4>
                                    <span class="badge badge-purple"><?= count($data); ?> entries</span>
                                </div>
                                <div class="up-card-body table-responsive">


                                    <table id="datatable" class="table table-bordered dt-responsive nowrap up-rt" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Responsible</th>
                                                <th>Date</th>
                                                <th>Category</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php
                                            foreach ($data as $row) {
                                                echo "<tr>";
                                                echo "<td data-label=\"Description\" style=\"font-weight:600;color:var(--up-ink);\">" . htmlspecialchars($row->Description, ENT_QUOTES, 'UTF-8') . "</td>";
                                                echo "<td data-label=\"Amount\" style=\"font-weight:700;color:var(--up-blue);\">₱ " . htmlspecialchars(number_format($row->Amount, 2), ENT_QUOTES, 'UTF-8') . "</td>";
                                                echo "<td data-label=\"Responsible\" style=\"color:var(--up-muted);\">" . htmlspecialchars($row->Responsible, ENT_QUOTES, 'UTF-8') . "</td>";
                                                echo "<td data-label=\"Date\" style=\"color:var(--up-muted);\">" . htmlspecialchars($row->ExpenseDate, ENT_QUOTES, 'UTF-8') . "</td>";
                                                echo "<td data-label=\"Category\"><span class=\"badge badge-secondary\" style=\"border-radius:6px;font-size:.72rem;font-weight:700;\">" . htmlspecialchars($row->Category, ENT_QUOTES, 'UTF-8') . "</span></td>";
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



    <!-- Modal -->
    <div id="custom-modal" class="modal-demo">
        <button type="button" class="close" onclick="Custombox.modal.close();">
            <span>&times;</span><span class="sr-only">Close</span>
        </button>
        <h4 class="custom-modal-title">New Expense</h4>
        <div class="custom-modal-text">
            <form class="form-horizontal" method="post">
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Description</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="Description">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label">Amount</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="Amount">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="inputPassword5" class="col-md-3 col-form-label">Responsible</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="Responsible">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="inputPassword5" class="col-md-3 col-form-label">Date</label>
                    <div class="col-md-9">
                        <input type="date" class="form-control" name="ExpenseDate">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="inputPassword5" class="col-md-3 col-form-label">Category</label>
                    <div class="col-md-9">
                        <select class="form-control" name="Category">


                            <?php
                            foreach ($data1 as $row) {
                            ?>

                                <option value="<?php echo $row->Category; ?>"><?php echo $row->Category; ?></option>


                            <?php }



                            ?>

                        </select>
                    </div>
                </div>

                <div class="form-group mb-0 justify-content-end row">
                    <div class="col-md-9">
                        <input type="submit" name="submit" class="up-btn up-btn-primary" value="Submit">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="<?= base_url(); ?>assets/libs/custombox/custombox.min.js"></script>


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
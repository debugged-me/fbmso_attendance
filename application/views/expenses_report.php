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
                            <div class="up-page-sub">Filter expenses by date range and view summaries by category.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Date filter -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-filter-variant"></i> Date Range Filter</h4>
                                </div>
                                <div class="up-card-body">
                                    <form method="GET" class="form-inline" style="gap:10px;flex-wrap:wrap;">
                                        <input type="date" class="form-control" name="from" />
                                        <input type="date" class="form-control" name="to" />
                                        <input type="submit" name="submit" class="up-btn up-btn-primary" value="Submit">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- end page title -->
                    <div class="row">
                        <?php
                        if (isset($_GET["submit"])) {
                        ?>
                            <div class="col-md-12">
                                <div class="up-card">
                                    <div class="up-card-head">
                                        <h4><i class="mdi mdi-file-document-outline"></i> Expenses Report</h4>
                                        <span class="badge badge-purple"><?= count($data); ?> entries</span>
                                    </div>
                                    <div class="up-card-body table-responsive">
                                        <?php echo $this->session->flashdata('msg'); ?>
                                        <table class="mb-3" style="font-size:.9rem;color:var(--up-muted);">
                                            <tr>
                                                <td style="padding:2px 12px 2px 0;">Data Range</td>
                                                <td>: <b style="color:var(--up-ink);"><?php echo htmlspecialchars($_GET['from'], ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars($_GET['to'], ENT_QUOTES, 'UTF-8'); ?></b></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:2px 12px 2px 0;">Total Expenses</td>
                                                <td>: <b style="color:var(--up-ink);">₱ <?php echo number_format($data1[0]->TotalAmount, 2); ?></b></td>
                                            </tr>
                                        </table>

                                        <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap up-rt" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-chart-donut"></i> Collection Type Summary</h4>
                                </div>
                                <div class="up-card-body table-responsive">

                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left">Type</th>
                                                <th style="text-align:right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($data2 as $row) {
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($row->Category, ENT_QUOTES, 'UTF-8'); ?>
                                                    </td>
                                                    <td style="text-align:right">₱ <?php echo htmlspecialchars(number_format($row->TotalAmount, 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php }  ?>
                                            <tr>
                                                <td style="font-weight:700;color:var(--up-ink);">TOTAL EXPENSES</td>
                                                <td style="text-align:right;font-weight:700;color:var(--up-blue);">₱ <?php echo number_format($data1[0]->TotalAmount, 2); ?></td>
                                            </tr>
                                        <?php }   ?>
                                        </tbody>
                                    </table>
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
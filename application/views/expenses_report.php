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
                                <h4 class="page-title">Expenses Report</h4>
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

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group">
                                            <input type="date" class="form-control" name="from" />&nbsp
                                            <input type="date" class="form-control" name="to" />&nbsp

                                        </div>
                                        <input type="submit" name="submit" class="btn btn-info float-right" value="submit">

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
                                <div class="card">
                                    <div class="card-body table-responsive">
                                        <h4 class="m-t-0 header-title mb-4"><b>Expenses Report</b></h4>

                                        <?php echo $this->session->flashdata('msg'); ?>
                                        <table>
                                            <tr>
                                                <td>Data Range</td>
                                                <td>: <b><?php echo $_GET['from'] . ' to ' . $_GET['to']; ?></b></td>
                                            </tr>
                                            <tr>
                                                <td>Total Expenses</td>
                                                <td>: <b><?php echo number_format($data1[0]->TotalAmount, 2); ?></b></td>
                                            </tr>
                                        </table>

                                        <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
                                                    echo "<td>" . $row->Description . "</td>";
                                                    echo "<td>" . $row->Amount . "</td>";
                                                    echo "<td>" . $row->Responsible . "</td>";
                                                    echo "<td>" . $row->ExpenseDate . "</td>";
                                                    echo "<td>" . $row->Category . "</td>";
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
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <h4 class="m-t-0 header-title mb-4"><b>Collection Type Summary</b></h4>

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
                                                        <?php echo $row->Category; ?>
                                                    </td>
                                                    <td style="text-align:right"><?php echo $row->TotalAmount; ?></td>
                                                </tr>
                                            <?php }  ?>
                                            <tr>
                                                <td>TOTAL EXPENSES</td>
                                                <td style="text-align:right;"><b><?php echo number_format($data1[0]->TotalAmount, 2); ?></b></td>
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
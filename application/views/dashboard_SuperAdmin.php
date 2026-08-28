<?php
// dashboard_SuperAdmin.php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body>

    <div id="wrapper">

        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <div>
                                    <?php
                                    $schoolName    = '';
                                    $schoolAddress = '';

                                    if (!empty($data) && isset($data[0])) {
                                        $schoolName    = !empty($data[0]->SchoolName) ? $data[0]->SchoolName : '';
                                        $schoolAddress = !empty($data[0]->SchoolAddress) ? $data[0]->SchoolAddress : '';
                                    }
                                    ?>
                                    <h4 class="page-title mb-0">
                                        <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </h4>
                                </div>

                            </div>
                        </div>
                    </div>

                </div><!-- container-fluid -->
            </div><!-- content -->

            <?php include('includes/footer.php'); ?>
        </div><!-- content-page -->

    </div><!-- wrapper -->

    <?php include('includes/themecustomizer.php'); ?>

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
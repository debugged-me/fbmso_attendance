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

                <div class="page-title-box">
                    <h4 class="page-title">Students in <?= $course; ?> <?= $major ? ' - ' . $major : ''; ?></h4> <br>
                    <hr style="height: 2px; background: linear-gradient(to right, #4285F4, #FBBC05, #34A853);">
                </div>

                <?php if (!empty($grouped)): ?>
                    <?php foreach ($grouped as $yearLevel => $sexGroup): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <strong>Year Level: <?= $yearLevel; ?></strong>
                            </div>
                            <div class="card-body">

                                <?php foreach (['Male', 'Female'] as $gender): ?>
                                    <?php if (!empty($sexGroup[$gender])): ?>
                                        <h5 class="text-<?= $gender == 'Male' ? 'blue' : 'pink'; ?>"><?= strtoupper($gender); ?> STUDENTS</h5>
                                        <table class="table table-bordered table-sm mb-4">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student Number</th>
                                                    <th>Full Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; foreach ($sexGroup[$gender] as $student): ?>
                                                    <tr>
                                                        <td><?= $i++; ?></td>
                                                        <td><?= $student->StudentNumber; ?></td>
                                                        <td><?= strtoupper($student->LastName . ', ' . $student->FirstName . ' ' . $student->MiddleName); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning">No students found for this course and major.</div>
                <?php endif; ?>

                <a href="<?= base_url('Page/masterlistByCourse1'); ?>" class="btn btn-secondary mt-3">Back to Masterlist</a>

            </div>
        </div>
        <?php include('includes/footer.php'); ?>
    </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
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

    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script></body>
</html>

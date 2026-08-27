<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

<body>

<!-- Begin page -->
<div id="wrapper">

    <!-- Topbar Start -->
    <?php include('includes/top-nav-bar.php'); ?>
    <!-- end Topbar -->

    <!-- ========== Left Sidebar Start ========== -->

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
                <?php if ($this->session->flashdata('msg')): ?>
                    <?= $this->session->flashdata('msg'); ?>
                <?php endif; ?>

                <!-- start page title -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Edit Section</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb p-0 m-0">
                                </ol>
                            </div>
                            <div class="clearfix"></div>
                            <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                        </div>
                    </div>
                </div>
                <!-- start row -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="<?= base_url('Page/editSection/' . $section->id); ?>">
                                    <div class="form-group">
                                        <label for="courseid">Course</label>
                                        <select name="courseid" class="form-control" required>
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?= $course->courseid ?>" <?= $course->courseid == $section->courseid ? 'selected' : '' ?>>
                                                    <?= $course->CourseCode . ' - ' . $course->CourseDescription ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="year_level">Year Level</label>
                                        <select name="year_level" class="form-control select2" required>
                                            <option value="">Select Year Level</option>
                                            <?php if (!empty($yearLevels)): ?>
                                                <?php foreach ($yearLevels as $yearLevel): ?>
                                                    <option value="<?= $yearLevel->year_level ?>" <?= ($yearLevel->year_level == $section->year_level) ? 'selected' : '' ?>>
                                                        <?= $yearLevel->year_level ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="section">Section</label>
                                        <input type="text" name="section" class="form-control" value="<?= $section->section ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Update Section</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end container-fluid -->

        </div>
    </div>

    <!-- ============================================================== -->
    <!-- Footer Start -->
    <?php include('includes/footer.php'); ?>
    <!-- end Footer -->

</div>

<!-- ============================================================== -->
<!-- End Page content -->
<!-- ============================================================== -->

</div>
<!-- END wrapper -->

<!-- Vendor js -->
<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>

<script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

<!-- Required datatable js -->
<script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
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

<!-- Datatables init -->
<script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>

</body>
</html>

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
                                $settingsID    = 1;
                                $examCount     = 1;
                                $openSHS       = 0; // default NO (0)

                                if (!empty($data) && isset($data[0])) {
                                    $schoolName    = !empty($data[0]->SchoolName) ? $data[0]->SchoolName : '';
                                    $schoolAddress = !empty($data[0]->SchoolAddress) ? $data[0]->SchoolAddress : '';

                                    if (isset($data[0]->settingsID)) $settingsID = (int)$data[0]->settingsID;
                                    if (isset($data[0]->examCount))  $examCount  = (int)$data[0]->examCount;
                                    if (isset($data[0]->openSHS))    $openSHS    = (int)$data[0]->openSHS;
                                }

                                if ($settingsID <= 0) $settingsID = 1;
                                if ($examCount <= 0)  $examCount  = 1;
                                $openSHS = ($openSHS === 1) ? 1 : 0;
                                ?>
                                <h4 class="page-title mb-0">
                                    <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8'); ?>
                                    </small>
                                </h4>
                            </div>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item">
                                        <span class="badge badge-purple">
                                            Currently login to
                                            <b>
                                                SY <?= htmlspecialchars($this->session->userdata('sy')); ?>
                                                <?= htmlspecialchars($this->session->userdata('semester')); ?>
                                            </b>
                                        </span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Messages -->
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= html_escape($this->session->flashdata('success')); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('danger')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= html_escape($this->session->flashdata('danger')); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">

                    <!-- =======================
                        GRADE PERIOD SETTINGS CARD
                    ======================= -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card">
                            <div class="card-header bg-primary py-3 text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-gear me-1"></i> Grade Period Settings
                                </h5>
                            </div>
                            <div class="card-body">

                                <?php
                                $gs = (isset($grades_settings) && $grades_settings)
                                    ? $grades_settings
                                    : (object)[
                                        'Prelim'       => 1,
                                        'Midterm'      => 1,
                                        'PreFinal'     => 1,
                                        'Final'        => 1,
                                        'Average'      => 0,
                                        'PassingGrade' => 3.50,
                                    ];

                                if (!isset($gs->Prelim))       $gs->Prelim   = 1;
                                if (!isset($gs->Midterm))      $gs->Midterm  = 1;
                                if (!isset($gs->PreFinal))     $gs->PreFinal = 1;
                                if (!isset($gs->Final))        $gs->Final    = 1;
                                if (!isset($gs->Average))      $gs->Average  = 0;
                                if (!isset($gs->PassingGrade) || (float)$gs->PassingGrade <= 0) $gs->PassingGrade = 3.50;
                                ?>

                                <form action="<?= site_url('Page/superAdmin'); ?>" method="post">
                                    <input type="hidden"
                                           name="<?= $this->security->get_csrf_token_name(); ?>"
                                           value="<?= $this->security->get_csrf_hash(); ?>">
                                    <input type="hidden" name="save_grades_settings" value="1">

                                    <small class="text-muted d-block mb-3">
                                        ✅ <strong>Checked</strong> = Enabled (0) &nbsp;|&nbsp;
                                        ⛔ <strong>Unchecked</strong> = Disabled (1)
                                    </small>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="Prelim" name="Prelim" value="1"
                                               <?= ((int)$gs->Prelim === 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="Prelim">Enable Prelim</label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="Midterm" name="Midterm" value="1"
                                               <?= ((int)$gs->Midterm === 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="Midterm">Enable Midterm</label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="PreFinal" name="PreFinal" value="1"
                                               <?= ((int)$gs->PreFinal === 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="PreFinal">Enable Pre-Final</label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="Final" name="Final" value="1"
                                               <?= ((int)$gs->Final === 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="Final">Enable Final</label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="Average" name="Average" value="1"
                                               <?= ((int)$gs->Average === 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="Average">Enable Final Average Column</label>
                                    </div>

                                    <hr class="my-2">

                                    <div class="mb-3">
                                        <label for="PassingGrade" class="form-label mb-1">Passing Grade (Total Average)</label>
                                        <input type="number" step="0.01" min="1" max="5"
                                               class="form-control form-control-sm"
                                               id="PassingGrade" name="PassingGrade"
                                               value="<?= htmlspecialchars(number_format((float)$gs->PassingGrade, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <small class="text-muted">
                                            Example: <strong>3.50</strong> – passing threshold.
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-save me-1"></i> Save Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>


                    <!-- =======================
                        EXAM COUNT SETTINGS CARD
                    ======================= -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card">
                            <div class="card-header bg-success py-3 text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-ui-checks me-1"></i> Exam Count Encoding
                                </h5>
                            </div>
                            <div class="card-body">

                                <small class="text-muted d-block mb-2">
                                    Set how many exam entries can be encoded (e.g., 1, 2, 3...).
                                </small>

                                <form action="<?= site_url('Page/superAdmin'); ?>" method="post">
                                    <input type="hidden"
                                           name="<?= $this->security->get_csrf_token_name(); ?>"
                                           value="<?= $this->security->get_csrf_hash(); ?>">
                                    <input type="hidden" name="save_exam_count" value="1">
                                    <input type="hidden" name="settingsID" value="<?= (int)$settingsID; ?>">

                                    <div class="mb-2">
                                        <label class="form-label mb-1" for="examCount">Exam Count</label>
                                        <input type="number"
                                               class="form-control"
                                               id="examCount"
                                               name="examCount"
                                               min="1"
                                               max="20"
                                               value="<?= (int)$examCount; ?>">
                                        <small class="text-muted">
                                            Recommended: 1–5 (you can increase if your school needs more).
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-save me-1"></i> Save Exam Count
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>


                    <!-- =======================
                        OPEN SHS SETTINGS CARD (NEW)
                    ======================= -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card">
                            <div class="card-header bg-warning py-3 text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-mortarboard me-1"></i> SHS Setting
                                </h5>
                            </div>
                            <div class="card-body">

                                <small class="text-muted d-block mb-2">
                                    Toggle if Senior High School (SHS) features are open/available in the system.
                                </small>

                                <form action="<?= site_url('Page/superAdmin'); ?>" method="post">
                                    <input type="hidden"
                                           name="<?= $this->security->get_csrf_token_name(); ?>"
                                           value="<?= $this->security->get_csrf_hash(); ?>">
                                    <input type="hidden" name="save_open_shs" value="1">
                                    <input type="hidden" name="settingsID" value="<?= (int)$settingsID; ?>">

                                    <div class="mb-3">
                                        <label class="form-label mb-1" for="openSHS">Open SHS?</label>
                                        <select class="form-select" id="openSHS" name="openSHS">
                                            <option value="1" <?= ((int)$openSHS === 1) ? 'selected' : ''; ?>>Yes (1)</option>
                                            <option value="0" <?= ((int)$openSHS === 0) ? 'selected' : ''; ?>>No (0)</option>
                                        </select>
                                        <small class="text-muted">
                                            Yes = 1, No = 0 (saved to <code>o_srms_settings.openSHS</code>).
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-save me-1"></i> Save SHS Setting
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>


                    <!-- =======================
                        OTHER SUPER ADMIN WIDGETS
                    ======================= -->
                    <div class="col-md-12 col-lg-3">
                        <div class="card">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="card-title mb-0">Super Admin Dashboard</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Add your other summary widgets here (enrollment stats, requests, online settings, etc.).
                                </p>

                                <?php if (!empty($online_settings)): ?>
                                    <div class="border rounded p-2 mb-2">
                                        <h6 class="mb-1">Online Settings</h6>
                                        <small class="text-muted">
                                            You can surface information from <code>$online_settings</code> here.
                                        </small>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div><!-- end row -->

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
<script src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
<script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>

<script src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>

<script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

<script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

</body>
</html>

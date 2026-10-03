<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    .si-panel { border:1px solid var(--up-line,#e6ebf5); border-radius:14px; background:#f8faff; padding:20px 22px; margin-bottom:18px; }
    .si-panel-note { font-size:.85rem; color:var(--up-muted,#6b7a99); margin-bottom:16px; }
    .up-card .form-check-input { position:relative; margin-top:.3rem; }
</style>

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
                    <?php
                    $massSettings = (array) ($mass_email_settings ?? []);
                    $openPanel = (string) ($open_panel ?? '');
                    $openMassEmail = ($openPanel === 'mass_email');
                    $canManageMassEmail = !empty($can_manage_mass_email);
                    ?>

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <?php if ($canManageMassEmail): ?>
                                    <h4 class="up-page-title"><i class="mdi mdi-email-cog-outline"></i> Mass Email Setup</h4>
                                    <div class="up-page-sub">Configure the sender and transport used for mass email announcements.</div>
                                <?php else: ?>
                                    <h4 class="up-page-title"><i class="mdi mdi-school-outline"></i> School Information</h4>
                                    <div class="up-page-sub">School profile, officials, and payment gateway credentials.</div>
                                <?php endif; ?>
                                <hr class="up-divider">
                                <?php echo $this->session->flashdata('msg'); ?>
                                <?php if ($this->session->flashdata('success')): ?>
                                    <div class="up-flash up-flash-success" role="alert">
                                        <?= $this->session->flashdata('success'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->session->flashdata('warning')): ?>
                                    <div class="up-flash up-flash-info" role="alert">
                                        <?= $this->session->flashdata('warning'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->session->flashdata('danger')): ?>
                                    <div class="up-flash up-flash-danger" role="alert">
                                        <?= $this->session->flashdata('danger'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- end page title -->

                    <div class="col-xl-12 col-sm-6 ">
                        <!-- Portlet card -->
                        <div class="up-card">
                            <div class="up-card-head">
                                <?php if ($canManageMassEmail): ?>
                                    <h5><i class="mdi mdi-email-cog-outline"></i> Email Delivery Settings</h5>
                                    <a href="<?= base_url('mass-announcement'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-bullhorn-outline"></i> Email Announcement</a>
                                <?php else: ?>
                                    <h5><i class="mdi mdi-school-outline"></i> School Information</h5>
                                <?php endif; ?>
                            </div>
                            <div id="cardCollpase2" class="collapse show">
                                <div class="up-card-body">
                                    <form role="form" method="post" enctype="multipart/form-data">
                                        <!-- general form elements -->
                                        <div class="card-body">
                                            <div class="row">

                                            </div>
                                            <?php if (!$canManageMassEmail): ?>
                                                <div class="row">
                                                    <div class="col-lg-12">

                                                        <div class="form-group">
                                                            <label for="lastName">School Name </label>
                                                            <input type="text" class="form-control" name="SchoolName" value="<?php echo $data[0]->SchoolName; ?>" required>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label>School Address </label>
                                                            <input type="text" class="form-control" name="SchoolAddress" value="<?php echo $data[0]->SchoolAddress; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label>School Slogan </label>
                                                            <input type="text" class="form-control" name="slogan" value="<?php echo $data[0]->slogan; ?>">
                                                        </div>
                                                    </div>
                                                </div> -->
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>School Head</label>
                                                            <input type="text" class="form-control" name="SchoolHead" value="<?php echo $data[0]->SchoolHead; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>School Head Position </label>
                                                            <input type="text" class="form-control" name="sHeadPosition" value="<?php echo $data[0]->sHeadPosition; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Registrar</label>
                                                            <input type="text" class="form-control" name="RegistrarJHS" value="<?php echo $data[0]->RegistrarJHS ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Property Custodian </label>
                                                            <input type="text" class="form-control" name="PropertyCustodian" value="<?php echo $data[0]->PropertyCustodian; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Principal (Pre-School)</label>
                                                            <input type="text" class="form-control" name="principalPre" value="<?php echo isset($data[0]->principalPre) ? $data[0]->principalPre : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Principal (Grade School)</label>
                                                            <input type="text" class="form-control" name="principalGS" value="<?php echo isset($data[0]->principalGS) ? $data[0]->principalGS : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Principal (JHS)</label>
                                                            <input type="text" class="form-control" name="principalJHS" value="<?php echo $data[0]->principalJHS ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Principal (SHS)</label>
                                                            <input type="text" class="form-control" name="principalSHS" value="<?php echo isset($data[0]->principalSHS) ? $data[0]->principalSHS : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Finance Officer </label>
                                                            <input type="text" class="form-control" name="financeOfficer" value="<?php echo $data[0]->financeOfficer ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Allow Viewing of Grades?</label>
                                                            <select class="form-control" name="viewGrades">
                                                                <option><?php echo $data[0]->viewGrades ?? ''; ?></option>
                                                                <option>Yes</option>
                                                                <option>No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <label>Active Term</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim(($data[0]->active_sem ?? '') . ' ' . ($data[0]->active_sy ?? '')), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                                            <small class="text-muted">Changed under <a href="<?= base_url('Settings/academicTerm'); ?>">Academic Term</a>.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Dragonpay Credentials</span><span class="up-section-line"></span></div>
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label>Merchant ID</label>
                                                            <input type="text" class="form-control" name="dragonpay_merchantid" value="<?php echo $data[0]->dragonpay_merchantid; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label>Dragonpay Password </label>
                                                            <input type="text" class="form-control" name="dragonpay_password" value="<?php echo $data[0]->dragonpay_password; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label>Dragonpay URL </label>
                                                            <input type="text" class="form-control" name="dragonpay_url" value="<?php echo $data[0]->dragonpay_url; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="up-flash up-flash-info">
                                                    <i class="mdi mdi-information-outline"></i>
                                                    This page is limited to <strong>Mass Email Setup</strong> for Super Admin. School Information fields are managed by the Admin account.
                                                </div>
                                            <?php endif; ?>



                                            <?php if ($canManageMassEmail): ?>
                                                <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Delivery Configuration</span><span class="up-section-line"></span>
                                                    <button type="button"
                                                        class="up-btn up-btn-ghost"
                                                        data-toggle="collapse"
                                                        data-target="#massEmailSettingsCollapse"
                                                        aria-expanded="<?= $openMassEmail ? 'true' : 'false'; ?>"
                                                        aria-controls="massEmailSettingsCollapse">
                                                        <i class="mdi mdi-tune"></i> Setup Email
                                                    </button>
                                                </div>

                                                <div id="massEmailSettingsCollapse" class="collapse <?= $openMassEmail ? 'show' : ''; ?>">
                                                    <div class="si-panel">
                                                        <p class="si-panel-note">
                                                            <i class="mdi mdi-information-outline"></i> Sender name is automatic and always uses your school name.
                                                        </p>
                                                        <input type="hidden" name="return_to" value="Settings/schoolInfo?panel=mass_email">
                                                        <div class="form-row">
                                                            <div class="form-group col-md-3">
                                                                <label for="mass_email_transport">Transport</label>
                                                                <select name="transport" id="mass_email_transport" class="form-control">
                                                                    <option value="brevo_api" <?= (($massSettings['transport'] ?? '') === 'brevo_api') ? 'selected' : ''; ?>>Brevo API</option>
                                                                    <option value="smtp" <?= (($massSettings['transport'] ?? '') === 'smtp') ? 'selected' : ''; ?>>SMTP</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group col-md-5">
                                                                <label for="mass_email_sender_email">Sender Email (verified in Brevo)</label>
                                                                <input type="email" id="mass_email_sender_email" name="sender_email" class="form-control" value="<?= html_escape((string) ($massSettings['sender_email'] ?? '')); ?>">
                                                            </div>
                                                        </div>

                                                        <div id="mass_email_brevo_fields">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="mass_email_brevo_api_url">Brevo API URL</label>
                                                                    <input type="text" id="mass_email_brevo_api_url" name="brevo_api_url" class="form-control" value="<?= html_escape((string) ($massSettings['brevo_api_url'] ?? 'https://api.brevo.com/v3/smtp/email')); ?>">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="mass_email_brevo_api_key">Brevo API Key</label>
                                                                    <input type="text" id="mass_email_brevo_api_key" name="brevo_api_key" class="form-control" value="<?= html_escape((string) ($massSettings['brevo_api_key'] ?? '')); ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="mass_email_smtp_fields">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-4">
                                                                    <label for="mass_email_smtp_host">SMTP Host</label>
                                                                    <input type="text" id="mass_email_smtp_host" name="smtp_host" class="form-control" value="<?= html_escape((string) ($massSettings['smtp_host'] ?? 'smtp-relay.brevo.com')); ?>">
                                                                </div>
                                                                <div class="form-group col-md-3">
                                                                    <label for="mass_email_smtp_port">SMTP Port</label>
                                                                    <input type="number" id="mass_email_smtp_port" name="smtp_port" class="form-control" value="<?= (int) ($massSettings['smtp_port'] ?? 587); ?>">
                                                                </div>
                                                                <div class="form-group col-md-3">
                                                                    <label for="mass_email_smtp_crypto">SMTP Crypto</label>
                                                                    <?php $massSmtpCrypto = (string) ($massSettings['smtp_crypto'] ?? 'tls'); ?>
                                                                    <select name="smtp_crypto" id="mass_email_smtp_crypto" class="form-control">
                                                                        <option value="tls" <?= $massSmtpCrypto === 'tls' ? 'selected' : ''; ?>>tls</option>
                                                                        <option value="ssl" <?= $massSmtpCrypto === 'ssl' ? 'selected' : ''; ?>>ssl</option>
                                                                        <option value="" <?= $massSmtpCrypto === '' ? 'selected' : ''; ?>>none</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="mass_email_smtp_user">SMTP User</label>
                                                                    <input type="text" id="mass_email_smtp_user" name="smtp_user" class="form-control" value="<?= html_escape((string) ($massSettings['smtp_user'] ?? '')); ?>">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="mass_email_smtp_pass">SMTP Password / Key</label>
                                                                    <input type="text" id="mass_email_smtp_pass" name="smtp_pass" class="form-control" value="<?= html_escape((string) ($massSettings['smtp_pass'] ?? '')); ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <button type="submit"
                                                            class="up-btn up-btn-primary"
                                                            formaction="<?= site_url('mass-announcement/settings'); ?>"
                                                            formmethod="post">
                                                            <i class="mdi mdi-content-save-outline"></i> Save Mass Email Settings
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!$canManageMassEmail): ?>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <input type="submit" name="submit" class="up-btn up-btn-primary" value="Update School Information">
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        </div><!-- /.box -->

                                </div>

                                </form>

                            </div>
                        </div>
                    </div>
                    <!-- end card-->

                </div>
                <!-- end col -->

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

    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <!-- Sweet alert init js-->
    <script src="<?= base_url(); ?>assets/js/pages/sweet-alerts.init.js"></script>
    <script>
        $(function() {
            function toggleMassEmailTransportFields() {
                var transport = ($('#mass_email_transport').val() || '').toLowerCase();
                if (transport === 'smtp') {
                    $('#mass_email_smtp_fields').show();
                    $('#mass_email_brevo_fields').hide();
                    return;
                }
                $('#mass_email_smtp_fields').hide();
                $('#mass_email_brevo_fields').show();
            }

            $('#mass_email_transport').on('change', toggleMassEmailTransportFields);
            toggleMassEmailTransportFields();
        });
    </script>
</body>

</html>

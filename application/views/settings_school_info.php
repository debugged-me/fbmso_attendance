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
                                <!-- <h4 class="page-title">School Information</h4> -->
                                <?php echo $this->session->flashdata('msg'); ?>
                                <?php if ($this->session->flashdata('success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                                        <?= $this->session->flashdata('success'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->session->flashdata('warning')): ?>
                                    <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
                                        <?= $this->session->flashdata('warning'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->session->flashdata('danger')): ?>
                                    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                        <?= $this->session->flashdata('danger'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <!-- <li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li> -->
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>

                    <!-- end page title -->

                    <div class="col-xl-12 col-sm-6 ">
                        <!-- Portlet card -->
                        <div class="card">
                            <div class="card-header bg-primary py-3 text-white">
                                <div class="card-widgets">
                                    <a href="javascript:;" data-toggle="reload"><i class="mdi mdi-refresh"></i></a>
                                    <a data-toggle="collapse" href="#cardCollpase2" role="button" aria-expanded="false" aria-controls="cardCollpase2"><i class="mdi mdi-minus"></i></a>
                                    <a href="#" data-toggle="remove"><i class="mdi mdi-close"></i></a>
                                </div>
                                <h5 class="card-title mb-0 text-white">School Information</h5>
                            </div>
                            <div id="cardCollpase2" class="collapse show">
                                <div class="card-body">
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
                                                            <label>Active SY </label>
                                                            <input type="text" class="form-control" name="active_sy" value="<?php echo $data[0]->active_sy; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <h4>Dragonpay Credentials</h4>
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
                                                <div class="alert alert-info">
                                                    This page is limited to <strong>Mass Email Setup</strong> for Super Admin. School Information fields are managed by Admin account.
                                                </div>
                                            <?php endif; ?>



                                            <?php if ($canManageMassEmail): ?>
                                                <h4 class="mt-3">Mass Email Setup</h4>
                                                <div class="mb-3">
                                                    <button type="button"
                                                        class="btn btn-outline-info btn-sm"
                                                        data-toggle="collapse"
                                                        data-target="#massEmailSettingsCollapse"
                                                        aria-expanded="<?= $openMassEmail ? 'true' : 'false'; ?>"
                                                        aria-controls="massEmailSettingsCollapse">
                                                        Setup Email
                                                    </button>
                                                </div>

                                                <div id="massEmailSettingsCollapse" class="collapse <?= $openMassEmail ? 'show' : ''; ?>">
                                                    <div class="border rounded p-3 mb-3">
                                                        <p class="text-muted mb-3">
                                                            Sender name is automatic and always uses your school name.
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
                                                            class="btn btn-success"
                                                            formaction="<?= site_url('mass-announcement/settings'); ?>"
                                                            formmethod="post">
                                                            Save Mass Email Settings
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!$canManageMassEmail): ?>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <input type="submit" name="submit" class="btn btn-info" value="Update School Information">
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

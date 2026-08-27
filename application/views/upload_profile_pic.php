<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260827'); ?>">

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="up-page-title">Change Profile Picture</h4>
                                <div class="up-page-sub">Upload a clear photo to personalize your account.</div>
                                <hr class="up-divider" />
                            </div>
                        </div>
                    </div>

                    <!-- Identity strip -->
                    <div class="up-id-strip">
                        <div class="up-id-icon"><i class="mdi mdi-account-circle-outline"></i></div>
                        <div>
                            <div class="up-id-name"><?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="up-id-meta">Profile photo upload</div>
                        </div>
                    </div>

                    <!-- Upload card -->
                    <div class="row">
                        <div class="col-12">
                            <?php $flashMsg = $this->session->flashdata('msg'); ?>
                            <?php if ($flashMsg): ?>
                                <div class="up-flash up-flash-info"><?= $flashMsg; ?></div>
                            <?php endif; ?>

                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-upload"></i> Upload Photo</h4>
                                </div>
                                <div class="up-card-body">
                                    <form role="form" action="<?= site_url('Page/uploadProfPic'); ?>" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="StudentNumber" value="<?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>" readonly required>

                                        <div class="up-section-head">
                                            <div class="up-section-dot"></div>
                                            <div class="up-section-label">Select Image</div>
                                            <div class="up-section-line"></div>
                                        </div>

                                        <div class="form-group">
                                            <label>Profile Picture</label>
                                            <input type="file" class="form-control" name="nonoy" accept="image/*" required>
                                            <div class="up-hint">
                                                Limit the size to <span style="color:var(--up-red);font-weight:700">2MB only</span>.
                                                The recommended size is <span style="color:var(--up-red);font-weight:700">215px by 215px</span>.
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button type="submit" name="submit" class="up-btn up-btn-primary">
                                                <i class="mdi mdi-upload"></i> Upload
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>

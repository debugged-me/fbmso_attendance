<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title>SRMS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta content="Responsive bootstrap 4 admin template" name="description" />
        <meta content="Coderthemes" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/Attendance.png">

        <!-- App css -->
        <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
        <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />
        <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=6'); ?>">
        <meta name="theme-color" content="#1a2942">
        <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
        <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
        <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>

    </head>

    <body class="authentication-page">

        <div class="account-pages my-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-header text-center p-4 bg-primary">
                                <h4 class="text-white mb-0 mt-0">School Records Management System</h4>
								<h5 style="color:red"><?php echo $this->session->flashdata('msg'); ?></h5>
                            </div>
                            <div class="card-body">
                               		<form action="<?php echo site_url('Login/auth');?>" method="post" class="p-2"">
                                    <div class="user-thumb text-center mb-4">
                                        <img src="<?= base_url(); ?>upload/profile/<?php echo $this->session->userdata('avatar');?>" class="img-fluid rounded-circle avatar-lg" alt="thumbnail">
                                    </div>

                                    <div class="form-group text-center mb-0">
									<input type="hidden" value="<?php echo $this->session->userdata('username'); ?>" name="username">
									<input type="hidden" name="sy" value="<?php echo $this->session->userdata('sy'); ?>">
									<input type="hidden" name="semester" value="<?php echo $this->session->userdata('semester'); ?>">
									
                                        <h5><?php echo $this->session->userdata('fname').' '.$this->session->userdata('lname');?></h5>
                                        <p>Enter your password to access the system.</p>
                                        <div class="input-group">
                                            <input type="password" name="password" class="form-control" placeholder="Enter Your Password">
                                            <span class="input-group-append"> <button type="submit" class="btn btn-primary">Login</button> </span>
                                        </div>

                                    </div>
                                </form>

                            </div>
                            <!-- end card-body -->
                        </div>
                        <!-- end card -->

                        <!-- end row -->

                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->

            </div>
        </div>

        <!-- Vendor js -->
        <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

        <!-- App js -->
        <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
        <?php include(APPPATH . 'views/includes/mobile-tabbar.php'); ?>
        <script src="<?= base_url('assets/js/mobile-shell.js?v=5'); ?>"></script>

    </body>

</html>

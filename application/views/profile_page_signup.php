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
                        <div class="col-sm-12">
                            <div class="profile-bg-picture" style="background-image:url('<?= base_url(); ?>assets/images/bg-profile.jpg')">
                                <span class="picture-bg-overlay"></span>
                                <!-- overlay -->
                            </div>
                            <!-- meta -->
                            <div class="profile-user-box">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <?php if ($this->session->userdata('level') === 'Student') : ?>
                                            <div class="profile-user-img"><img src="<?= base_url(); ?>upload/profile/<?php echo $this->session->userdata('avatar'); ?>" alt="" class="avatar-lg rounded-circle"></div>

                                        <?php else : ?>
                                            <div class="profile-user-img"><img src="<?= base_url(); ?>upload/profile/<?php echo $data1[0]->avatar; ?>" alt="" class="avatar-lg rounded-circle"></div>
                                        <?php endif; ?>
                                        <div class="">
                                            <h4 class="mt-5 font-18 ellipsis"><?php echo $data[0]->FirstName . ' ' . $data[0]->MiddleName . ' ' . $data[0]->LastName; ?></h4>
                                            <p class="font-13"> <span style="text-transform: uppercase;"><?php echo $data[0]->sitio . ' ' . $data[0]->brgy . ', ' . $data[0]->city . ', ' . $data[0]->province; ?></span></p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="text-right">

                                            <a href="<?= base_url(); ?>page/updatestudentsignup?StudentNumber=<?php echo $data[0]->StudentNumber; ?>">
                                                <button type="button" class="btn btn-warning waves-effect width-md waves-light">Edit Profile</button>
                                            </a>


                                            <!-- <a href="<?= base_url(); ?>page/enrollmentAcceptance?id=<?php echo $data[0]->StudentNumber; ?>&FName=<?php echo $data[0]->FirstName; ?>&MName=<?php echo $data[0]->MiddleName; ?>&LName=<?php echo $data[0]->LastName; ?>&Course=&YearLevel=&sem=&sy=">
                                                <button type="button" class="btn btn-primary waves-effect waves-light">Enroll</button>
                                            </a> -->
                                            <a href="<?= base_url(); ?>page/printstudentsignup?StudentNumber=<?php echo $data[0]->StudentNumber; ?>" target="_blank">
                                                <button type="button" class="btn btn-secondary waves-effect width-md">Print Profile</button>
                                            </a>



                                            <?php

                                            if ($this->session->userdata('level') !== 'Stude Applicant'): ?>
                                                <a href="<?= base_url(); ?>page/copyData?id=<?php echo $data[0]->StudentNumber; ?>">
                                                    <button type="button" class="btn btn-info waves-effect waves-light">Copy to Student's Profile</button>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--/ meta -->
                        </div>
                    </div>
                    <!-- end row -->

                    <div class="row mt-4">
                        <div class="col-sm-12">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success">
                                    <?= $this->session->flashdata('success'); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger">
                                    <?= $this->session->flashdata('error'); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($this->session->flashdata('info')): ?>
                                <div class="alert alert-info">
                                    <?= $this->session->flashdata('info'); ?>
                                </div>
                            <?php endif; ?>


                            <div class="card p-0">
                                <div class="card-body p-0">
                                    <ul class=" nav nav-tabs tabs-bordered nav-justified">
                                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#aboutme">About</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#requirements">Requirements</a></li>

                                    </ul>
                                    <?php echo $this->session->flashdata('msg'); ?>
                                    <div class="tab-content m-0 p-4">

                                        <div id="aboutme" class="tab-pane active">
                                            <div class="profile-desk">
                                                <h4 class="mt-1 font-18 ellipsis">Student's Information</h4>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <!--<h5 class="mt-4">Official Information</h5>-->
                                                        <table class="table table-condensed mb-0">

                                                            <tbody>
                                                                <tr>
                                                                    <th scope="row">Record No.</th>
                                                                    <td>
                                                                        <?php echo $data[0]->StudentNumber; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Birth Date</th>
                                                                    <td>
                                                                        <?php echo $data[0]->birthDate; ?>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Birth Place</th>
                                                                    <td>
                                                                        <?php echo $data[0]->BirthPlace; ?>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Age</th>
                                                                    <td>
                                                                        <?php echo $data[0]->age; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Sex</th>
                                                                    <td>
                                                                        <?php echo $data[0]->Sex; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Civil Status</th>
                                                                    <td>
                                                                        <?php echo $data[0]->CivilStatus; ?>
                                                                    </td>
                                                                </tr>


                                                                <tr>
                                                                    <th scope="row">Ethnicity</th>
                                                                    <td>
                                                                        <?php echo $data[0]->ethnicity; ?>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Religion</th>
                                                                    <td>
                                                                        <?php echo $data[0]->Religion; ?>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Working Student?</th>
                                                                    <td>
                                                                        <?php echo $data[0]->working; ?>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Mobile No.</th>
                                                                    <td>
                                                                        <?php echo $data[0]->contactNo; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Email</th>
                                                                    <td>
                                                                        <?php echo $data[0]->email; ?>
                                                                    </td>
                                                                </tr>
                                                            </tbody>

                                                        </table>
                                                    </div>

                                                    <div class="col-sm-6">
                                                        <!--<h5 class="mt-4">Contact Person</h5>-->
                                                        <table class="table table-condensed mb-0">

                                                            <tbody>
                                                                <tr>
                                                                    <th scope="row">Preferred Courses:</th>

                                                                    <td><?php echo $data[0]->Course1; ?></td>
                                                                    <td><?php echo $data[0]->Course2; ?></td>
                                                                    <td><?php echo $data[0]->Course3; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Major</th>
                                                                    <td><?php echo $data[0]->Major1; ?></td>
                                                                    <td><?php echo $data[0]->Major2; ?></td>
                                                                    <td><?php echo $data[0]->Major3; ?></td>
                                                                </tr>

                                                                <tr>
                                                                    <th scope="row">Status</th>
                                                                    <td>
                                                                        <?php echo $data[0]->Status; ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row">Singup Date</th>
                                                                    <td>
                                                                        <?php echo $data[0]->EnrollmentDate; ?>
                                                                    </td>
                                                                </tr>

                                                            </tbody>

                                                        </table>
                                                    </div>
                                                </div>
                                            </div> <!-- end profile-desk -->
                                        </div> <!-- about-me -->

                                        <div id="requirements" class="tab-pane">
                                            <h4 class="mt-1 font-18 ellipsis">Requirements</h4>

                                            <table class="table mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Requirement</th>
                                                        <th>Status</th>
                                                        <th>Submitted On</th>
                                                        <th>Remarks</th>
                                                        <th>File</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data2 as $index => $req): ?>
                                                        <tr>
                                                            <td><?= $req->name ?></td>
                                                            <td>
                                                                <?php
                                                                if ($req->date_submitted) {
                                                                    if ($req->is_verified == 1) {
                                                                        echo '<span style="color: green;">Verified</span>';
                                                                    } elseif ($req->is_verified == 2) {
                                                                        echo '<span style="color: red;">Disapproved</span>';
                                                                    } else {
                                                                        echo '<span style="color: orange;">Pending</span>';
                                                                    }
                                                                } else {
                                                                    echo '<a href="#" class="text-danger" data-toggle="modal" data-target="#manualVerifyModal' . $index . '">Not Submitted</a>';
                                                                }
                                                                ?>

                                                            </td>
                                                            <td><?= $req->date_submitted ?? '—' ?></td>
                                                            <td><?= $req->comment ?></td>
                                                            <td>
                                                                <?php if ($req->file_path): ?>
                                                                    <a href="<?= base_url($req->file_path) ?>" class="btn btn-primary btn-sm" target="_blank">
                                                                        <i class="fa fa-eye"></i> View
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#uploadModal<?= $index ?>">
                                                                        <i class="fa fa-upload"></i> Upload
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>

                                                        <!-- Upload Modal -->
                                                        <?php if (!$req->file_path): ?>
                                                            <div class="modal fade" id="uploadModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel<?= $index ?>" aria-hidden="true">
                                                                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                                                                    <form action="<?= base_url('Student/upload_requirement') ?>" method="post" enctype="multipart/form-data">
                                                                        <input type="hidden" name="requirement_id" value="<?= $req->req_id ?>">
                                                                        <input type="hidden" name="StudentNumber" value="<?= $this->input->get('id'); ?>">

                                                                        <div class="modal-content">
                                                                            <div class="modal-header p-2">
                                                                                <h6 class="modal-title" id="uploadModalLabel<?= $index ?>">Upload Requirement</h6>
                                                                                <button type="button" class="close m-0" data-dismiss="modal" aria-label="Close" style="font-size: 1rem;">
                                                                                    <span aria-hidden="true">&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body p-2">
                                                                                <p class="mb-1"><strong><?= $req->name ?></strong></p>
                                                                                <div class="form-group mb-2">
                                                                                    <input type="file" name="requirement_file" class="form-control form-control-sm" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer p-2">
                                                                                <button type="submit" class="btn btn-success btn-sm">✔ Upload</button>
                                                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">✖ Cancel</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Manual Verify Modal -->
                                                        <?php if (!$req->date_submitted): ?>
                                                            <div class="modal fade" id="manualVerifyModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="manualVerifyLabel<?= $index ?>" aria-hidden="true">
                                                                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                                                                    <form action="<?= base_url('Student/manual_verify') ?>" method="post">
                                                                        <input type="hidden" name="requirement_id" value="<?= $req->req_id ?>">
                                                                        <input type="hidden" name="StudentNumber" value="<?= $this->input->get('id'); ?>">

                                                                        <div class="modal-content">
                                                                            <div class="modal-header p-2">
                                                                                <h6 class="modal-title" id="manualVerifyLabel<?= $index ?>">Verify Requirement</h6>
                                                                                <button type="button" class="close m-0" data-dismiss="modal" aria-label="Close" style="font-size: 1rem;">
                                                                                    <span aria-hidden="true">&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body p-2">
                                                                                <p class="mb-1"><strong><?= $req->name ?></strong></p>
                                                                                <div class="form-group mb-2">
                                                                                    <input type="text" class="form-control form-control-sm" name="comment" placeholder="Remarks (e.g., Submitted hard copy)" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer p-2">
                                                                                <button type="submit" class="btn btn-success btn-sm">✔ Verify</button>
                                                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">✖ Cancel</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                    </div>
                    <!-- end row -->

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

    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

</body>

</html>
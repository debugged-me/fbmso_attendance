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

                    <!-- Page Title and Profile Picture -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="profile-bg-picture" style="background-image:url('<?= base_url(); ?>assets/images/mis.jpg')">
                                <span class="picture-bg-overlay"></span>
                            </div>
                            <div class="profile-user-box">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="profile-user-img">
                                            <img src="<?= base_url(); ?>upload/profile/<?= !empty($data1) ? htmlspecialchars($data1[0]->avatar, ENT_QUOTES, 'UTF-8') : 'avatar.png'; ?>" alt="" class="avatar-lg rounded-circle">
                                        </div>
                                        <?php if (!empty($data)) { ?>
                                            <div class="">
                                                <h4 class="mt-5 font-18 ellipsis"><?= $data[0]->FirstName . ' ' . $data[0]->MiddleName . ' ' . $data[0]->LastName; ?></h4>
                                                <p class="font-13"><?= $data[0]->empPosition; ?></p>
                                                <p class="text-muted mb-0">
                                                    <small><?= $data[0]->perStreet . ' ' . $data[0]->perVillage . ' ' . $data[0]->perBarangay . ', ' . $data[0]->perCity . ', ' . $data[0]->perProvince; ?></small>
                                                </p>
                                            </div>
                                        <?php } else { ?>
                                            <div class="">
                                                <h4 class="mt-5 font-18 ellipsis text-danger">Profile not found</h4>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <?php if (!empty($data)) { ?>
                                            <a href="<?= base_url(); ?>page/updatePersonnelProfile?id=<?= $data[0]->IDNumber; ?>">
                                                <button type="button" class="btn btn-success">
                                                    <i class="far fa-edit mr-1"></i> Edit Profile
                                                </button>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Tabs -->
                    <div class="row mt-4">
                        <div class="col-sm-12">
                            <div class="card p-0">
                                <div class="card-body p-0">
                                    <ul class="nav nav-tabs tabs-bordered nav-justified">
                                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#aboutme">About</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#family">Family</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#education">Education</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#cs">Civil Service</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#trainings">Trainings</a></li>
                                    </ul>

                                    <div class="tab-content m-0 p-4">
                                        <!-- About Me Tab -->
                                        <div id="aboutme" class="tab-pane active">
                                            <?php if (!empty($data)) { ?>
                                                <h5 class="mt-4">Official Information</h5>
                                                <div class="row">
                                                    <!-- Column 1 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Employee No.</th>
                                                                    <td><?= $data[0]->IDNumber; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Job Title</th>
                                                                    <td><?= $data[0]->jobTitle; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Position</th>
                                                                    <td><?= $data[0]->empPosition; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Emp. Status</th>
                                                                    <td><?= $data[0]->empStatus; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <!-- Column 2 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Department</th>
                                                                    <td><?= $data[0]->Department; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Date Hired</th>
                                                                    <td><?= $data[0]->dateHired; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>TIN</th>
                                                                    <td><?= $data[0]->tinNo; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <!-- Column 3 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>GSIS BP No.</th>
                                                                    <td><?= $data[0]->gsis; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>PAG-IBIG No.</th>
                                                                    <td><?= $data[0]->pagibig; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>SSS</th>
                                                                    <td><?= $data[0]->sssNo; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>PhilHealth No.</th>
                                                                    <td><?= $data[0]->philHealth; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <h5 class="mt-4">Personal Information</h5>
                                                <div class="row">
                                                    <!-- Column 1 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Gender</th>
                                                                    <td><?= $data[0]->Sex; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Birth Date</th>
                                                                    <td><?= $data[0]->BirthDate; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Birth Place</th>
                                                                    <td><?= $data[0]->BirthPlace; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <!-- Column 2 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Blood Type</th>
                                                                    <td><?= $data[0]->bloodType; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Marital Status</th>
                                                                    <td><?= $data[0]->MaritalStatus; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <!-- Column 3 -->
                                                    <div class="col-sm-4">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Height</th>
                                                                    <td><?= $data[0]->height; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Weight</th>
                                                                    <td><?= $data[0]->weight; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <h5 class="mt-4">Contact Information</h5>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Contact No.</th>
                                                                    <td><?= $data[0]->empMobile; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Official Email</th>
                                                                    <td><?= $data[0]->empEmail; ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Address</th>
                                                                    <td><?= $data[0]->perStreet . ' ' . $data[0]->perVillage . ' ' . $data[0]->perBarangay . ', ' . $data[0]->perCity . ', ' . $data[0]->perProvince; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <p class="text-danger">No personal profile data available.</p>
                                            <?php } ?>
                                        </div>

                                        <!-- Family -->
                                        <div id="family" class="tab-pane">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Relationship</th>
                                                        <th>Birth Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data2 as $row): ?>
                                                        <tr>
                                                            <td><?= $row->fullName; ?></td>
                                                            <td><?= $row->relationship; ?></td>
                                                            <td><?= $row->bDate; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <br><button type="button" class="btn btn-primary btn-xs">Add</button>
                                        </div>

                                        <!-- Education -->
                                        <div id="education" class="tab-pane">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Level</th>
                                                        <th>School Name</th>
                                                        <th>Course</th>
                                                        <th>Year Started</th>
                                                        <th>Year Graduated</th>
                                                        <th>Scholarship</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data3 as $row): ?>
                                                        <tr>
                                                            <td><?= $row->level; ?></td>
                                                            <td><?= $row->schoolName; ?></td>
                                                            <td><?= $row->course; ?></td>
                                                            <td><?= $row->yearStarted; ?></td>
                                                            <td><?= $row->yearGraduated; ?></td>
                                                            <td><?= $row->scholarship; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <br><button type="button" class="btn btn-primary btn-xs">Add</button>
                                        </div>

                                        <!-- Civil Service -->
                                        <div id="cs" class="tab-pane">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Rating</th>
                                                        <th>Date of Exam</th>
                                                        <th>Place</th>
                                                        <th>License No.</th>
                                                        <th>Validity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data4 as $row): ?>
                                                        <tr>
                                                            <td><?= $row->licenseTitle; ?></td>
                                                            <td><?= $row->rating; ?></td>
                                                            <td><?= $row->examDate; ?></td>
                                                            <td><?= $row->examPlace; ?></td>
                                                            <td><?= $row->licenseNo; ?></td>
                                                            <td><?= $row->validity; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <br><button type="button" class="btn btn-primary btn-xs">Add</button>
                                        </div>

                                        <!-- Trainings -->
                                        <div id="trainings" class="tab-pane">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Training Title</th>
                                                        <th>Date Started</th>
                                                        <th>Date Finished</th>
                                                        <th>Hours</th>
                                                        <th>Type</th>
                                                        <th>Conducted By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data5 as $row): ?>
                                                        <tr>
                                                            <td><?= $row->trainingTitle; ?></td>
                                                            <td><?= $row->dateStarted; ?></td>
                                                            <td><?= $row->dateFinished; ?></td>
                                                            <td><?= $row->noHours; ?></td>
                                                            <td><?= $row->ldType; ?></td>
                                                            <td><?= $row->sponsor; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <br><button type="button" class="btn btn-primary btn-xs">Add</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- end container-fluid -->
            </div> <!-- end content -->
            <?php include('includes/footer.php'); ?>
        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <?php include('includes/themecustomizer.php'); ?>

    <!-- Scripts -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
</body>

</html>
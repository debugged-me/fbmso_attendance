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
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">

    <!-- Plugins css-->
    <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <!-- third party css -->
    <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=6'); ?>">
    <meta name="theme-color" content="#1a2942">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
    <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>
    <script type="text/javascript">
        function submitBday() {

            var Bdate = document.getElementById('bday').value;
            var Bday = +new Date(Bdate);
            Q4A = ~~((Date.now() - Bday) / (31557600000));
            var theBday = document.getElementById('resultBday');
            theBday.value = Q4A;
        }
    </script>

</head>

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
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Update Profile Form</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>Page/studentsprofile?id=<?php echo $data[0]->StudentNumber; ?>">Profile Page</a></li>
                                        <li class="breadcrumb-item"><a href="#">Profile Update Form</a></li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>

                    <!-- end page title -->


                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">

                                <div class="card-body table-responsive">
                                    <!-- <h4 class="m-t-0 header-title mb-4"><b><?php echo $_GET['yearlevel']; ?> Year, <?php echo $_GET['course']; ?> | <?php echo $this->session->userdata('semester'); ?> SY <?php echo $this->session->userdata('sy'); ?></b></h4>-->

                                    <!-- form start -->
                                    <form role="form" method="post" enctype="multipart/form-data">
                                        <!-- general form elements -->
                                        <div class="card-body">
                                            <?php
                                            foreach ($data as $row) {
                                            ?>

                                                <div class="row">

                                                    <div class="col-lg-3">
                                                        <input type="hidden" class="form-control" value="<?php echo $row->StudentNumber; ?>" name="oldStudentNo" readonly required>
                                                        <div class="form-group">
                                                            <label for="lastName">Student No. <span style="color:red">*</span></label>
                                                            <?php if ($this->session->userdata('level') === 'Student') { ?>
                                                                <input type="text" class="form-control" value="<?php echo $row->StudentNumber; ?>" name="StudentNumber" readonly required>
                                                            <?php } else { ?>
                                                                <input type="text" class="form-control" value="<?php echo $row->StudentNumber; ?>" name="StudentNumber" readonly required>
                                                            <?php } ?>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>First Name <span style="color:red">*</span></label>
                                                            <?php if ($this->session->userdata('level') === 'Student') { ?>
                                                                <input type="text" class="form-control" name="FirstName" value="<?php echo $row->FirstName; ?>" readonly required>
                                                            <?php } else { ?>
                                                                <input type="text" class="form-control" name="FirstName" value="<?php echo $row->FirstName; ?>" required>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Middle Name<span style="color:red">*</span></label>
                                                            <input type="text" class="form-control" name="MiddleName" value="<?php echo $row->MiddleName; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Last Name <span style="color:red">*</span></label>
                                                            <?php if ($this->session->userdata('level') === 'Student') { ?>
                                                                <input type="text" class="form-control" name="LastName" value="<?php echo $row->LastName; ?>" readonly required>
                                                            <?php } else { ?>
                                                                <input type="text" class="form-control" name="LastName" value="<?php echo $row->LastName; ?>" required>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Name Extn</label>
                                                            <input type="text" class="form-control" name="nameExtn" value="<?php echo $row->nameExtn; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-2 form-group">
                                                        <label>Sex<span style="color:red;">*</span></label>
                                                        <select class="form-control" name="Sex" required>
                                                            <option></option>
                                                            <option <?php echo ($data[0]->Sex == "Female") ? 'selected' : ''; ?>>Female</option>
                                                            <option <?php echo ($data[0]->Sex == "Male") ? 'selected' : ''; ?>>Male</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label>Civil Status <span style="color:red;">*</span></label>
                                                        <select name="CivilStatus" class="form-control" required>
                                                            <option value=""></option>
                                                            <option value="Single" <?php echo ($data[0]->CivilStatus == "Single") ? 'selected' : ''; ?>>Single</option>
                                                            <option value="Married" <?php echo ($data[0]->CivilStatus == "Married") ? 'selected' : ''; ?>>Married</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-2 form-group">
                                                        <label>Birth Date<span style="color:red;">*</span></label>
                                                        <input type="date" placeholder="" class="form-control" name="birthDate"
                                                            value="<?php echo $data[0]->birthDate; ?>" required>
                                                    </div>
                                                    <div class="col-sm-1 form-group">
                                                        <label>Age<span style="color:red;">*</span></label>
                                                        <input type="text" placeholder="" class="form-control" name="age"
                                                            value="<?php echo $data[0]->age; ?>" readonly required>
                                                    </div>
                                                    <div class="col-sm-5 form-group">
                                                        <label>Birth Place <span style="color:red;">*</span></label>
                                                        <input type="text" placeholder="" class="form-control" name="BirthPlace"
                                                            value="<?php echo $data[0]->BirthPlace; ?>" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-3 form-group">
                                                        <label>Mobile No.<span style="color:red;">*</span></label>
                                                        <input type="text" placeholder="" class="form-control" name="contactNo"
                                                            value="<?php echo $data[0]->contactNo; ?>" required>
                                                    </div>

                                                    <div class="col-sm-3 form-group">
                                                        <label>E-mail Address<span style="color:red;">*</span></label>
                                                        <input type="email" placeholder="" class="form-control" name="email"
                                                            value="<?php echo $data[0]->email; ?>" required>
                                                    </div>

                                                    <div class="col-sm-3 form-group">
                                                        <label>Vaccination Status</label>
                                                        <select class="form-control" name="VaccStat">
                                                            <option></option>
                                                            <option <?php echo ($data[0]->VaccStat == "Not Yet Vaccinated") ? 'selected' : ''; ?>>Not Yet Vaccinated</option>
                                                            <option <?php echo ($data[0]->VaccStat == "1st Dose Only") ? 'selected' : ''; ?>>1st Dose Only</option>
                                                            <option <?php echo ($data[0]->VaccStat == "Fully Vaccinated") ? 'selected' : ''; ?>>Fully Vaccinated</option>
                                                        </select>
                                                    </div>


                                                    <div class="col-sm-3 form-group">
                                                        <label>Working Student<span style="color:red;">*</span></label>
                                                        <select class="form-control" name="working" required>
                                                            <option></option>
                                                            <option <?php echo ($data[0]->working == "Yes") ? 'selected' : ''; ?>>Yes</option>
                                                            <option <?php echo ($data[0]->working == "No") ? 'selected' : ''; ?>>No</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row">

                                                    <div class="col-sm-3 form-group">
                                                        <label>Ethnicity <span style="color:red;">*</span></label>
                                                        <select class="form-control select2" name="ethnicity" required>
                                                            <option disabled selected></option>
                                                            <?php foreach ($ethnicity as $row) { ?>
                                                                <option value="<?= $row->ethnicity; ?>"
                                                                    <?php echo ($row->ethnicity == $data[0]->ethnicity) ? 'selected' : ''; ?>>
                                                                    <?= ucwords(strtolower($row->ethnicity)); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-3 form-group">
                                                        <label>Religion <span style="color:red;">*</span></label>
                                                        <select class="form-control select2" name="Religion" required>
                                                            <option disabled selected></option>
                                                            <?php foreach ($religion as $row) { ?>
                                                                <option value="<?= $row->religion; ?>"
                                                                    <?php echo ($row->religion == $data[0]->Religion) ? 'selected' : ''; ?>>
                                                                    <?= ucwords(strtolower($row->religion)); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-3 form-group">
                                                        <label>Year Level <span style="color:red;">*</span></label>
                                                        <select name="yearLevel" class="form-control" required>
                                                            <option value="">Select Year Level</option>
                                                            <?php
                                                            $yearLevels = ["1st Year", "2nd Year", "3rd Year", "4th Year", "5th Year"];
                                                            foreach ($yearLevels as $level) { ?>
                                                                <option value="<?= $level; ?>"
                                                                    <?php echo ($level == $data[0]->yearLevel) ? 'selected' : ''; ?>>
                                                                    <?= $level; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-3 form-group">
                                                        <label>Nationality</label>
                                                        <input type="text" class="form-control" name="nationality"
                                                            value="<?php echo $data[0]->nationality ?? 'Filipino'; ?>">
                                                    </div>

                                                </div>

                                                <h5>Preferred Courses:</h5>
                                                <div class="row">

                                                    <div class="col-sm-6 form-group">
                                                        <label>Course/Program <span style="color:red;">*</span></label>
                                                        <select name="Course1" id="course" class="form-control" required>
                                                            <option value="">Select Course</option>
                                                            <?php foreach ($course as $row) { ?>
                                                                <option value="<?= $row->CourseDescription; ?>"
                                                                    <?php echo ($row->CourseDescription == $data[0]->Course1) ? 'selected' : ''; ?>>
                                                                    <?= $row->CourseDescription; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-6 form-group">
                                                        <label>Major</label>
                                                        <select name="Major1" id="major" class="form-control">
                                                            <option value="">Select Major</option>
                                                            <?php foreach ($major as $row) { ?>
                                                                <option value="<?= $row->Major; ?>"
                                                                    <?php echo ($row->Major == $data[0]->Major1) ? 'selected' : ''; ?>>
                                                                    <?= $row->Major; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-6 form-group">
                                                        <!-- <label>Course/Program <span style="color:red;">*</span></label> -->
                                                        <select name="Course1" id="course" class="form-control" required>
                                                            <option value="">Select Course</option>
                                                            <?php foreach ($course as $row) { ?>
                                                                <option value="<?= $row->CourseDescription; ?>"
                                                                    <?php echo ($row->CourseDescription == $data[0]->Course2) ? 'selected' : ''; ?>>
                                                                    <?= $row->CourseDescription; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-6 form-group">
                                                        <!-- <label>Major</label> -->
                                                        <select name="Major1" id="major" class="form-control">
                                                            <option value="">Select Major</option>
                                                            <?php foreach ($major as $row) { ?>
                                                                <option value="<?= $row->Major; ?>"
                                                                    <?php echo ($row->Major == $data[0]->Major2) ? 'selected' : ''; ?>>
                                                                    <?= $row->Major; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-6 form-group">
                                                        <!-- <label>Course/Program <span style="color:red;">*</span></label> -->
                                                        <select name="Course1" id="course" class="form-control" required>
                                                            <option value="">Select Course</option>
                                                            <?php foreach ($course as $row) { ?>
                                                                <option value="<?= $row->CourseDescription; ?>"
                                                                    <?php echo ($row->CourseDescription == $data[0]->Course3) ? 'selected' : ''; ?>>
                                                                    <?= $row->CourseDescription; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-sm-6 form-group">
                                                        <!-- <label>Major</label> -->
                                                        <select name="Major1" id="major" class="form-control">
                                                            <option value="">Select Major</option>
                                                            <?php foreach ($major as $row) { ?>
                                                                <option value="<?= $row->Major; ?>"
                                                                    <?php echo ($row->Major == $data[0]->Major3) ? 'selected' : ''; ?>>
                                                                    <?= $row->Major; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>



                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Spouse (if married)</label>
                                                            <input type="text" class="form-control" name="spouse" value="<?php echo $data[0]->spouse; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Spouse Relationship </label>
                                                            <input type="text" class="form-control" name="spouseRelationship" value="<?php echo $data[0]->spouseRelationship; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Spouse Contact </label>
                                                            <input type="text" class="form-control" name="spouseContact" value="<?php echo $data[0]->spouseContact; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Children</label>
                                                            <input type="text" class="form-control" name="children1" value="<?php echo $data[0]->children1; ?>">
                                                        </div>
                                                    </div>
                                                </div>








                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Father's Name</label>
                                                            <input type="text" class="form-control" name="father" value="<?php echo $data[0]->father; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Father's Occupation</label>
                                                            <input type="text" class="form-control" name="fOccupation" value="<?php echo $data[0]->fOccupation; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Mother</label>
                                                            <input type="text" class="form-control" name="mother" value="<?php echo $data[0]->mother; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Mother's Occupation</label>
                                                            <input type="text" class="form-control" name="mOccupation" value="<?php echo $data[0]->mOccupation; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Guardian</label>
                                                            <input type="text" class="form-control" name="guardian" value="<?php echo $data[0]->guardian; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Relationship to Guardian </label>
                                                            <input type="text" class="form-control" name="guardianRelationship" value="<?php echo $data[0]->guardianRelationship; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Guardian Address </label>
                                                            <input type="text" class="form-control" name="guardianAddress" value="<?php echo $data[0]->guardianAddress; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Guardian Contact No.</label>
                                                            <input type="text" class="form-control" name="guardianContact" value="<?php echo $data[0]->guardianContact; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label>Present Address<span style="color:red">*</span></label>
                                                            <input type="text" class="form-control" name="sitio" placeholder="Sitio" value="<?php echo $data[0]->sitio; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label><span class="text-muted">_</span></label>
                                                            <input type="text" class="form-control" name="brgy" placeholder="Barangay" value="<?php echo $data[0]->brgy; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label><span class="text-muted">_</span></label>
                                                            <input type="text" class="form-control" name="city" placeholder="City/Municipality" value="<?php echo $data[0]->city; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label><span class="text-muted">_</span></label>
                                                            <input type="text" class="form-control" name="province" placeholder="Province" value="<?php echo $data[0]->province; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="province">Elementary <span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="elementary" value="<?php echo $data[0]->elementary; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-5">
                                                        <div class="form-group">
                                                            <label for="province">School Address<span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="elementaryAddress" value="<?php echo $data[0]->elementaryAddress; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">Year Graduated <span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="elemGraduated" value="<?php echo $data[0]->elemGraduated; ?>">
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label for="province">Senior High/Junior High <span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="secondary" value="<?php echo $data[0]->secondary; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-5">
                                                        <div class="form-group">
                                                            <label for="province">School Address<span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="secondaryAddress" value="<?php echo $data[0]->secondaryAddress; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">Year Graduated <span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="secondaryGraduated" value="<?php echo $data[0]->secondaryGraduated; ?>">
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">Vocational School</label>
                                                            <input type="text" class="form-control" name="vocational" value="<?php echo $data[0]->vocational; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">School Address<span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="vocationalAddress" value="<?php echo $data[0]->vocationalAddress; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province"> Course<span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="vocationalCourse" value="<?php echo $data[0]->vocationalCourse; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">Year Graduated <span style="color:red;">*</span></label>
                                                            <input type="text" class="form-control" name="vocationalGraduated" value="<?php echo $data[0]->vocationalGraduated; ?>">
                                                        </div>
                                                    </div>
                                                </div>






                                                <div class="row">
                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">PWD</label>
                                                            <select class="form-control" name="disability">
                                                                <option value=""></option>
                                                                <option value="Yes" <?php echo ($data[0]->disability == "Yes") ? 'selected' : ''; ?>>Yes</option>
                                                                <option value="No" <?php echo ($data[0]->disability == "No") ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">If yes, give details (type of disability):</label>
                                                            <input type="text" class="form-control" name="typedisability"
                                                                value="<?php echo $data[0]->typedisability ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">Single Parent</label>
                                                            <select class="form-control" name="singleParent">
                                                                <option value=""></option>
                                                                <option value="Yes" <?php echo ($data[0]->singleParent == "Yes") ? 'selected' : ''; ?>>Yes</option>
                                                                <option value="No" <?php echo ($data[0]->singleParent == "No") ? 'selected' : ''; ?>>No</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-3">
                                                        <div class="form-group">
                                                            <label for="province">If yes, give details (Number of children):</label>
                                                            <input type="text" class="form-control" name="children"
                                                                value="<?php echo $data[0]->children ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>







                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <input type="submit" name="update" class="btn btn-info" value="Update Profile">
                                                    </div>
                                                </div>

                                        </div><!-- /.box -->

                                </div>
                            <?php } ?>
                            </form>
                            </div><!-- /.box -->

                        </div>

                        </form>
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


    <script>
        $(document).ready(function() {
            $('#course').change(function() {
                var course = $(this).val();
                if (course) {
                    $.ajax({
                        url: '<?= base_url("Registration/getMajorsByCourse") ?>',
                        type: 'POST',
                        data: {
                            course: course
                        },
                        success: function(data) {
                            $('#major').html(data);
                        },
                        error: function() {
                            UI.error('Could not load the major list. Please try again.');
                        }
                    });
                } else {
                    $('#major').html('<option value="">Select Major</option>');
                }
            });
        });
    </script>
</body>

</html>

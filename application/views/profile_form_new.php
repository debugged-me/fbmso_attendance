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
    <lin k href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <!-- third party css -->
    <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= base_url(); ?>assets/libs/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=6'); ?>">
    <meta name="theme-color" content="#1a2942">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
    <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>

    <script type="text/javascript">
        function calculateAge(dateInputId, resultInputId) {
            const dateValue = document.getElementById(dateInputId).value;
            if (dateValue) {
                const birthTime = new Date(dateValue).getTime();
                const now = Date.now();
                const age = Math.floor((now - birthTime) / 31557600000); // Average year
                document.getElementById(resultInputId).value = age;
            } else {
                document.getElementById(resultInputId).value = '';
            }
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
                                <h4 class="page-title">ADD NEW STUDENT</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                                        <li class="breadcrumb-item"><a href="#">Add New Student</a></li>
                                        <li class="breadcrumb-item"><a href="#"></a></li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
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
                                    <form class="parsley-examples" role="form" method="post" enctype="multipart/form-data">
                                        <div class="row mt-4">
                                            <div class="col-sm-12">
                                                <div class="card p-0">
                                                    <div class="card-body p-0">
                                                        <ul class="nav nav-tabs tabs-bordered nav-justified">
                                                            <li class="nav-item">
                                                                <a class="nav-link active" data-toggle="tab" href="#personalData">Personal Data</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="tab" href="#familyBackground">Family Background</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="tab" href="#educationalBackground">Educational Background</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="tab" href="#skillsInterests">Skills & Interests</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="tab" href="#admissiondetails">Admission Details</a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="tab" href="#otherDetails">Other Details</a>
                                                            </li>
                                                        </ul>

                                                        <div class="tab-content m-0 p-4">
                                                            <!-- Personal Data Tab -->
                                                            <div class="tab-pane active" id="personalData">
                                                                <div class="row">
                                                                    <div class="col-lg-12">
                                                                        <div class="form-group">
                                                                            <!-- Student Number is now auto-generated on submission -->
                                                                            <input type="hidden" name="StudentNumber" value="">

                                                                            <div class="alert alert-info">
                                                                                <i class="mdi mdi-information-outline"></i>
                                                                                Student Number will be generated automatically once you submit the form.
                                                                            </div>


                                                                        </div>
                                                                    </div>

                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>First Name <span style="color:red">*</span></label>
                                                                            <input type="text" class="form-control" name="FirstName" required style="text-transform: uppercase;">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Middle Name</label>
                                                                            <input type="text" class="form-control" name="MiddleName" style="text-transform: uppercase;">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Last Name <span style="color:red">*</span></label>
                                                                            <input type="text" class="form-control" name="LastName" required style="text-transform: uppercase;">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Name Extn</label>
                                                                            <input type="text" class="form-control" name="nameExtn" style="text-transform: uppercase;">
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Religion</label>
                                                                            <select class="form-control select2" name="Religion">
                                                                                <option>Select Religion</option>
                                                                                <?php foreach ($religion as $row) { ?>
                                                                                    <option value="<?= $row->religion; ?>"><?= $row->religion; ?></option>
                                                                                <?php } ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Sex <span style="color:red">*</span></label>
                                                                            <select name="Sex" class="form-control" required>
                                                                                <option value=""></option>
                                                                                <option value="Female">Female</option>
                                                                                <option value="Male">Male</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Civil Status<span style="color:red">*</span></label>
                                                                            <select name="CivilStatus" class="form-control" required>
                                                                                <option value=""></option>
                                                                                <option value="Single">Single</option>
                                                                                <option value="Married">Married</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Mobile No.</label>
                                                                            <input type="text" class="form-control" name="contactNo">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Ethnicity</label>
                                                                            <select class="form-control select2" name="Ethnicity">
                                                                                <option>Select Ethnicity</option>
                                                                                <?php foreach ($ethnicity as $row) { ?>
                                                                                    <option value="<?= $row->ethnicity; ?>"><?= $row->ethnicity; ?></option>
                                                                                <?php } ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Birth Date <span style="color:red">*</span></label>
                                                                            <input type="date" name="birthDate" class="form-control" id="bday" onchange="calculateAge('bday', 'resultBday')" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1">
                                                                        <div class="form-group">
                                                                            <label>Age <span style="color:red">*</span></label>
                                                                            <input type="text" name="Age" id="resultBday" class="form-control" readonly required />
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-5">
                                                                        <div class="form-group">
                                                                            <label>Birth Place</label>
                                                                            <input type="text" class="form-control" name="BirthPlace">
                                                                        </div>
                                                                    </div>

                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label>E-mail</label>
                                                                            <input type="text" class="form-control" name="email">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Working Student?</label>
                                                                            <select class="form-control" name="working">
                                                                                <option value="No">No</option>
                                                                                <option value="Yes">Yes</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Nationality</label>
                                                                            <input type="text" value="Filipino" class="form-control" name="nationality">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="province">Province <span style="color:red">*</span></label>
                                                                            <select id="province" name="Province" class="form-control" required>
                                                                                <option value="">Select Province</option>
                                                                                <?php foreach ($provinces as $province): ?>
                                                                                    <option value="<?php echo $province->AddID; ?>"><?php echo $province->Province; ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="city">City/Municipality <span style="color:red">*</span></label>
                                                                            <select id="city" name="City" class="form-control" required disabled>
                                                                                <option value="">Select City/Municipality</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="barangay">Barangay <span style="color:red">*</span></label>
                                                                            <select id="barangay" name="Brgy" class="form-control" required disabled>
                                                                                <option value="">Select Barangay</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Sitio</label>
                                                                            <input type="text" id="sitio" class="form-control" name="Sitio" placeholder="Sitio">
                                                                        </div>
                                                                    </div>
                                                                </div>





                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Person with disability?</label><br>

                                                                            <input type="text" class="form-control" name="disability" id="disability" placeholder="Specify if yes and N/A if no">
                                                                        </div>
                                                                    </div>



                                                                    <div class="col-lg-5">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Occupation</label>
                                                                            <input type="text" id="sitio" class="form-control" name="occupation">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Salary</label>
                                                                            <input type="text" id="sitio" class="form-control" name="salary">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Employer</label>
                                                                            <input type="text" id="employer" class="form-control" name="occupation">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Employer Adrress</label>
                                                                            <input type="text" id="sitio" class="form-control" name="employerAddress">
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Scholarship</label>
                                                                            <select name="scholarship" id="" class="form-control">
                                                                                <option value="">Select Scholarship</option>
                                                                                <?php foreach ($scholar as $province): ?>
                                                                                    <option value="<?php echo $province->Scholarship; ?>"><?php echo $province->Scholarship; ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>


                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Vaccination Status</label>
                                                                            <select name="VaccStat" id="" class="form-control">
                                                                                <option value="">Select Status</option>
                                                                                <option value="First Dose only">First Dose</option>
                                                                                <option value="Second Dose only">Second Dose</option>
                                                                                <option value="Second Dose only">Fully Vaccinated</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="fourPs">4P's Member</label>
                                                                            <select name="fourPs" id="fourPs" class="form-control">
                                                                                <option value=""></option>
                                                                                <option value="Yes">Yes</option>
                                                                                <option value="No">No</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="4psNo">4P's No.</label>
                                                                            <input type="text" name="4psNo" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="seniorCitizen">Senior Citizen</label>
                                                                            <select name="seniorCitizen" id="seniorCitizen" class="form-control">
                                                                                <option value=""></option>
                                                                                <option value="Yes">Yes</option>
                                                                                <option value="No">No</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>


                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label for="als">ALS Graduate</label>
                                                                            <select name="als" id="als" class="form-control">
                                                                                <option value=""></option>
                                                                                <option value="Yes">Yes</option>
                                                                                <option value="No">No</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>


                                                            <div class="tab-pane active" id="familyBackground">
                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Father's Name</label>
                                                                            <input type="text" class="form-control" name="Father">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Father's Occupation</label>
                                                                            <input type="text" class="form-control" name="FOccupation">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Father's Contact No.</label>
                                                                            <input type="text" class="form-control" name="fatherContact">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label>Father's Birth Date</label>
                                                                            <input type="date" name="fatherBDate" class="form-control" id="fbday" onchange="calculateAge('fbday', 'resultfBday')">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1">
                                                                        <div class="form-group">
                                                                            <label>Age</label>
                                                                            <input type="text" name="fatherAge" id="resultfBday" class="form-control" />
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Mother</label>
                                                                            <input type="text" class="form-control" name="Mother">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Mother's Occupation</label>
                                                                            <input type="text" class="form-control" name="MOccupation">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Mother's Contact No.</label>
                                                                            <input type="text" class="form-control" name="motherContact">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-2">
                                                                        <div class="form-group">
                                                                            <label>Mother's Birth Date</label>
                                                                            <input type="date" name="motherBDate" class="form-control" id="mbday" onchange="calculateAge('mbday', 'resultmBday')">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-1">
                                                                        <div class="form-group">
                                                                            <label>Age </label>
                                                                            <input type="text" name="motherAge" id="resultmBday" class="form-control" />
                                                                        </div>
                                                                    </div>
                                                                </div>



                                                                <div class="row align-items-center">

                                                                    <!-- Parent's Monthly Income -->
                                                                    <div class="col-lg-4">
                                                                        <div class="form-group mb-2">
                                                                            <label for="parentsMonthly">Parent's Monthly Income</label>
                                                                            <select name="parentsMonthly" class="form-control" id="parentsMonthly">
                                                                                <option value="">Select</option>
                                                                                <option value="No Income">No Income</option>
                                                                                <option value="0001-5,000">1 - 5,000</option>
                                                                                <option value="5,001-10,000">5,001 - 10,000</option>
                                                                                <option value="10,001-15,000">10,001 - 15,000</option>
                                                                                <option value="15,001-20,000">15,001 - 20,000</option>
                                                                                <option value="20,001-25,000">20,001 - 25,000</option>
                                                                                <option value="25,001-30,000">25,001 - 30,000</option>
                                                                                <option value="30,001-35,000">30,001 - 35,000</option>
                                                                                <option value="35,001-40,000">35,001 - 40,000</option>
                                                                                <option value="40,001-45,000">40,001 - 45,000</option>
                                                                                <option value="45,001-50,000">45,001 - 50,000</option>
                                                                                <option value="50,001-10,0000">50,001 - 100,000</option>
                                                                                <option value="100,001-150,000">100,001 - 150,000</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>







                                                                    <!-- Father's Address -->
                                                                    <div class="col-lg-5">
                                                                        <div class="form-group mb-2">
                                                                            <label for="fatherAddress">Father's Address</label>
                                                                            <input type="text" name="fatherAddress" id="fatherAddress" class="form-control" />
                                                                        </div>
                                                                    </div>


                                                                    <!-- Checkbox to copy address -->
                                                                    <!-- Stylish Checkbox with Custom Design -->
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group form-check mt-4">
                                                                            <input type="checkbox" id="copyAddressCheckbox" class="form-check-input styled-checkbox" />
                                                                            <label class="form-check-label ms-2 fw-semibold text-dark" for="copyAddressCheckbox">
                                                                                <i class="bi bi-house-door-fill me-1 text-primary"></i>
                                                                                Copy to Mother's Address
                                                                            </label>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Optional: Add this to your page or CSS file -->
                                                                    <style>
                                                                        .styled-checkbox {
                                                                            width: 1.3rem;
                                                                            height: 1.3rem;
                                                                            cursor: pointer;
                                                                            accent-color: #0d6efd;
                                                                            /* Bootstrap Primary */
                                                                            transition: transform 0.2s ease-in-out;
                                                                        }

                                                                        .styled-checkbox:hover {
                                                                            transform: scale(1.1);
                                                                        }

                                                                        .form-check-label {
                                                                            font-size: 1rem;
                                                                        }
                                                                    </style>

                                                                </div>

                                                                <!-- Hidden Mother's Address input -->
                                                                <input type="hidden" name="motherAddress" id="motherAddress" class="form-control" />

                                                                <!-- Script for copying address -->
                                                                <script>
                                                                    document.getElementById('copyAddressCheckbox').addEventListener('change', function() {
                                                                        const fatherAddress = document.getElementById('fatherAddress').value;
                                                                        const motherAddressInput = document.getElementById('motherAddress');
                                                                        if (this.checked) {
                                                                            motherAddressInput.value = fatherAddress;
                                                                        } else {
                                                                            motherAddressInput.value = '';
                                                                        }
                                                                    });
                                                                </script>




                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Guardian</label>
                                                                            <input type="text" class="form-control" name="Guardian">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Relationship to Guardian </label>
                                                                            <input type="text" class="form-control" name="GuardianRelationship">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Guardian Address </label>
                                                                            <input type="text" class="form-control" name="GuardianAddress">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label>Guardian Contact No.</label>
                                                                            <input type="text" class="form-control" name="GuardianContact">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </div>



                                                            <!-- Educational Background Tab -->
                                                            <div class="tab-pane" id="educationalBackground">

                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Elementary</label>
                                                                            <select name="elementary" id="elementary" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?>" data-address="<?= $school->Address ?>">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="elementaryAddress">Elementary Address</label>
                                                                            <input type="text" id="elementaryAddress" class="form-control" name="elementaryAddress" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>



                                                                <div class="row">
                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Year Graduated</label>
                                                                            <input type="text" name="elemGraduated" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-8">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Honor / Merits</label>
                                                                            <input type="text" id="elemMerits" class="form-control" name="elemMerits">
                                                                        </div>
                                                                    </div>
                                                                </div>



                                                                <div class="row">
                                                                    <!-- Junior High -->
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="secondary">Junior High</label>
                                                                            <select name="secondary" id="secondary" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?>" data-address="<?= $school->Address ?>">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="secondaryAddress">Junior High Address</label>
                                                                            <input type="text" id="secondaryAddress" class="form-control" name="secondaryAddress" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Year Graduated</label>
                                                                            <input type="text" name="secondaryGraduated" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-8">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Honor / Merits</label>
                                                                            <input type="text" id="secondaryMerits" class="form-control" name="secondaryMerits">
                                                                        </div>
                                                                    </div>
                                                                </div>





                                                                <div class="row">
                                                                    <!-- Senior High -->
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="seniorHigh">Senior High</label>
                                                                            <select name="SHS" id="seniorHigh" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?>" data-address="<?= $school->Address ?>">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="seniorHighAddress">Senior High Address</label>
                                                                            <input type="text" id="seniorHighAddress" class="form-control" name="SHSaddress" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Year Graduated</label>
                                                                            <input type="text" name="SHSgraduated" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-5">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Strand</label>
                                                                            <input type="text" name="SHSstrand" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Honor / Merits</label>
                                                                            <input type="text" id="SHSmerits" class="form-control" name="SHSmerits">
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <!-- Vocational -->
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="vocational">Vocational</label>
                                                                            <select name="vocational" id="vocational" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?>" data-address="<?= $school->Address ?>">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="vocationalAddress">Vocational Address</label>
                                                                            <input type="text" id="vocationalAddress" class="form-control" name="vocationaladdress" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>


                                                                <div class="row">
                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Year Graduated</label>
                                                                            <input type="text" name="vocationalGraduated" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Course</label>
                                                                            <input type="text" id="vocationalCourse" class="form-control" name="vocationalCourse">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">NC Level</label>
                                                                            <input type="text" id="ncLevel" class="form-control" name="ncLevel">
                                                                        </div>
                                                                    </div>
                                                                </div>



                                                                <div class="row">
                                                                    <!-- Vocational -->
                                                                    <div class="col-lg-8">
                                                                        <div class="form-group">
                                                                            <label for="lastAttended">Last School Attended</label>
                                                                            <select name="lastAttended" id="lastAttended" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?> ">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="vocationalAddress">Date</label>
                                                                            <input type="date" id="lastSchoolDate" class="form-control" name="lastSchoolDate">
                                                                        </div>
                                                                    </div>
                                                                </div>




                                                                <div class="row">
                                                                    <!-- transfereeSchool -->
                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="transfereeSchool">Transferee</label>
                                                                            <select name="transfereeSchool" id="transfereeSchool" class="form-control select2">
                                                                                <option value="">Select School</option>
                                                                                <?php foreach ($prevschool as $school): ?>
                                                                                    <option value="<?= $school->School ?>" data-address="<?= $school->Address ?>">
                                                                                        <?= $school->School ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="form-group">
                                                                            <label for="transfereeAddress">School Address</label>
                                                                            <input type="text" id="transfereeAddress" class="form-control" name="transfereeAddress" readonly>
                                                                        </div>
                                                                    </div>
                                                                </div>




                                                                <div class="row">
                                                                    <div class="col-lg-4">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Year Graduated</label>
                                                                            <input type="text" name="transfereeGraduated" id="" class="form-control">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-8">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Course</label>
                                                                            <input type="text" id="transfereeCourse" class="form-control" name="transfereeCourse">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Skills & Interests Tab -->
                                                            <div class="tab-pane" id="skillsInterests">
                                                                <div class="row">
                                                                    <div class="col-lg-12">
                                                                        <label for="">Skills/Interest</label>
                                                                        <textarea class="form-control" name="skills" rows="4"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- admissiondetails Tab -->
                                                            <div class="tab-pane" id="admissiondetails">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <div class="form-group">
                                                                            <label for="">Basis of Admission</label>
                                                                            <textarea name="admissionBasis" id="" class="form-control" rows="4"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>




                                                            <!-- Other Details Tab -->
                                                            <div class="tab-pane" id="otherDetails">


                                                                <div class="row">
                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="sitio">Graduation Date/Last Attended</label>
                                                                            <input type="text" name="lastSchoolDate" id="" class="form-control" placeholder="January 5, 2025">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">Honor</label>
                                                                            <input type="text" id="honors" class="form-control" name="honors">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">ROTC Serial No.</label>
                                                                            <input type="text" id="rotcSerial" class="form-control" name="rotcSerial">
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-3">
                                                                        <div class="form-group">
                                                                            <label for="elemMerits">CWTS Serial No.</label>
                                                                            <input type="text" id="cwtsSerial" class="form-control" name="cwtsSerial">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="admissionSem" value="<?= $this->session->userdata('semester'); ?>">
                                        <input type="hidden" name="admissionSY" value="<?= $this->session->userdata('sy'); ?>">


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <input type="submit" name="submit" class="btn btn-info" value="Submit">
                                            </div>
                                        </div>
                                </div>

                                </form>


                                <!-- general form elements -->









                                <!-- <label for="" style="margin-bottom: 40px; margin-top: 20px;">REQUIREMENTS:</label> -->






                                <script type="text/javascript">
                                    $(document).ready(function() {

                                        // Load provinces on page load
                                        $.ajax({
                                            url: '<?php echo site_url('Page/get_provinces'); ?>',
                                            type: 'GET',
                                            dataType: 'json',
                                            success: function(data) {
                                                $('#province').html('<option value="">Select Province</option>');
                                                $.each(data, function(index, province) {
                                                    $('#province').append('<option value="' + province.Province + '">' + province.Province + '</option>');
                                                });
                                            },
                                            error: function(xhr, status, error) {
                                                UI.error("Could not load the province list. " + error, 'Loading failed');
                                            }
                                        });

                                        // Load cities based on selected province
                                        $('#province').change(function() {
                                            var province = $(this).val();
                                            $('#city').prop('disabled', province == '');
                                            $('#barangay').prop('disabled', true).html('<option value="">Select Barangay</option>');

                                            if (province) {
                                                $.ajax({
                                                    url: '<?php echo site_url('Page/get_cities'); ?>',
                                                    type: 'POST',
                                                    dataType: 'json',
                                                    data: {
                                                        province: province
                                                    },
                                                    success: function(data) {
                                                        $('#city').html('<option value="">Select City/Municipality</option>');
                                                        if (data.error) {
                                                            UI.error(data.error);
                                                            return;
                                                        }
                                                        $.each(data, function(index, city) {
                                                            $('#city').append('<option value="' + city.City + '">' + city.City + '</option>');
                                                        });
                                                    },
                                                    error: function(xhr, status, error) {
                                                        UI.error("Could not load the city list. " + error, 'Loading failed');
                                                    }
                                                });
                                            } else {
                                                $('#city').html('<option value="">Select City/Municipality</option>');
                                            }
                                        });

                                        // Load barangays based on selected city
                                        $('#city').change(function() {
                                            var city = $(this).val();
                                            $('#barangay').prop('disabled', city == '');

                                            if (city) {
                                                $.ajax({
                                                    url: '<?php echo site_url('Page/get_barangays'); ?>',
                                                    type: 'POST',
                                                    dataType: 'json',
                                                    data: {
                                                        city: city
                                                    },
                                                    success: function(data) {
                                                        $('#barangay').html('<option value="">Select Barangay</option>');
                                                        if (data.error) {
                                                            UI.error(data.error);
                                                            return;
                                                        }
                                                        $.each(data, function(index, barangay) {
                                                            $('#barangay').append('<option value="' + barangay.Brgy + '">' + barangay.Brgy + '</option>');
                                                        });
                                                    },
                                                    error: function(xhr, status, error) {
                                                        UI.error("Could not load the barangay list. " + error, 'Loading failed');
                                                    }
                                                });
                                            } else {
                                                $('#barangay').html('<option value="">Select Barangay</option>');
                                            }
                                        });

                                    });
                                </script>



                                <script>
                                    $(document).ready(function() {
                                        // Initialize all select2 dropdowns
                                        $('.select2').select2();

                                        // Function to bind change and autofill address
                                        function bindSelect2Address(selectId, inputId) {
                                            $('#' + selectId).on('change', function() {
                                                var address = $(this).find(':selected').data('address');
                                                $('#' + inputId).val(address || '');
                                            });
                                        }

                                        // Bind all school selectors
                                        bindSelect2Address('elementary', 'elementaryAddress');
                                        bindSelect2Address('secondary', 'secondaryAddress');
                                        bindSelect2Address('seniorHigh', 'seniorHighAddress');
                                        bindSelect2Address('vocational', 'vocationalAddress');
                                        bindSelect2Address('transfereeSchool', 'transfereeAddress');
                                        bindSelect2Address('transfereeSchool', 'transfereeAddress');

                                    });
                                </script>






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
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

    <!-- Plugin js-->
    <script src="<?= base_url(); ?>assets/libs/parsleyjs/parsley.min.js"></script>

    <!-- Validation init js-->
    <script src="<?= base_url(); ?>assets/js/pages/form-validation.init.js"></script>

    <!-- Select2 JS -->
    <script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>






</body>

</html>

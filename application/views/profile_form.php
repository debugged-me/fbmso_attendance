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
	<link rel="shortcut icon" href="<?= base_url(); ?>assets/images/favicon.ico">

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
	<link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=7'); ?>">
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

    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
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
								<h4 class="page-title">Student's Profile Form</h4>
								<div class="page-title-right">
									<ol class="breadcrumb p-0 m-0">
										<li class="breadcrumb-item"><a href="#">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b></a></li>
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
							<?php echo $this->session->flashdata('msg'); ?>
							<div class="card">
								<div class="card-body table-responsive">
									<!-- <h4 class="m-t-0 header-title mb-4"><b>Heading Here</b></h4>-->
									<form role="form" method="post">
										<!-- general form elements -->
										<div class="card-body">

											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label for="lastName">Student No. <span style="color:red">*</span></label>
														<input type="text" class="form-control" name="StudentNumber" required>
													</div>
												</div>
											</div>

											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>First Name <span style="color:red">*</span></label>
														<input type="text" class="form-control" name="FirstName" value="" required>
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Middle Name</label>
														<input type="text" class="form-control" name="MiddleName" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Last Name <span style="color:red">*</span></label>
														<input type="text" class="form-control" name="LastName" value="" required>
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label for="lastName">Name Extn.</label>
														<input type="text" class="form-control" name="nameExt">
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
															<option value="">Select</option>
															<option value="Female">Female</option>
															<option value="Male">Male</option>
														</select>
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Civil Status</label>
														<select name="CivilStatus" class="form-control">
															<option value="Single">Single</option>
															<option value="Married">Married</option>
														</select>
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Mobile No.</label>
														<input type="text" class="form-control" name="MobileNumber" value="">
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
														<input type="date" name="BirthDate" class="form-control" id="bday" onchange="submitBday()" required>
													</div>
												</div>
												<div class="col-lg-1">
													<div class="form-group">
														<label>Age</label>
														<input type="text" name="age" id="resultBday" class="form-control" readonly />
													</div>
												</div>
												<div class="col-lg-5">
													<div class="form-group">
														<label>Birth Place</label>
														<input type="text" class="form-control" name="BirthPlace" value="">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>E-mail <span style="color:red">*</span></label>
														<input type="email" class="form-control" name="email" value="" required>
													</div>
												</div>

												<div class="col-lg-3">
													<div class="form-group">
														<label>Working Student?</label>
														<select class="form-control" name="working">
															<option></option>
															<option>No Data</option>
															<option>No</option>
															<option>Yes</option>
														</select>

													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>4Ps Beneficiary?</label>
														<select class="form-control" name="fourPs">
															<option>Select</option>
															<option>No Data</option>
															<option>No</option>
															<option>Yes</option>
														</select>

													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Nationality</label>
														<input type="text" class="form-control" name="nationality" value="Filipino">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>Father's Name</label>
														<input type="text" class="form-control" name="father" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Father's Occupation</label>
														<input type="text" class="form-control" name="fOccupation" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Father Address</label>
														<input type="text" class="form-control" name="fatherAddress" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Father Contact No.</label>
														<input type="text" class="form-control" name="fatherContact" value="">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>Mother</label>
														<input type="text" class="form-control" name="mother" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Mother's Occupation</label>
														<input type="text" class="form-control" name="mOccupation" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Mother Address</label>
														<input type="text" class="form-control" name="motherAddress" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Mother Contact No.</label>
														<input type="text" class="form-control" name="motherContact" value="">
													</div>
												</div>
											</div>

											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>Guardian</label>
														<input type="text" class="form-control" name="guardian" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Relationship to Guardian </label>
														<input type="text" class="form-control" name="guardianRelationship" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Guardian Address </label>
														<input type="text" class="form-control" name="guardianAddress" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label>Guardian Contact No.</label>
														<input type="text" class="form-control" name="guardianContact" value="">
													</div>
												</div>
											</div>

											<div class="row">
												<div class="col-lg-3">
													<div class="form-group">
														<label>Present Address</label>
														<input type="text" class="form-control" name="Sitio" placeholder="Sitio" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label><span class="text-muted">_</span></label>
														<input type="text" class="form-control" name="Brgy" placeholder="Barangay" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label><span class="text-muted">_</span></label>
														<input type="text" class="form-control" name="City" placeholder="City/Municipality" value="">
													</div>
												</div>
												<div class="col-lg-3">
													<div class="form-group">
														<label><span class="text-muted">_</span></label>
														<input type="text" class="form-control" name="Province" placeholder="Province" value="">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-12">
													<input type="submit" name="submit" class="btn btn-info" value="Save Profile">
												</div>
											</div>

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

</body>

</html>

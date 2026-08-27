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
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <!-- <a href="<?= base_url(); ?>Accounting/Addexpenses">    
                        <button type="button" class="btn btn-info waves-effect waves-light"> <i class="fas fa-stream mr-1"></i> <span>Add New</span> </button>
                        </a> -->
                                    <button type="button" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".bs-example-modal-lg" style="float: right;">Add New</button>

                                </h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item">
                                            <a href="#">
                                                <!-- <span class="badge badge-purple mb-3">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></span></b> -->
                                            </a>
                                        </li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />

                                <?php if ($this->session->flashdata('success')) : ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= $this->session->flashdata('success'); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <!-- Start row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="clearfix">

                                        <div class="float-left">
                                            <h5 style="text-transform:uppercase">
                                                <!-- <strong>SUBJECTS</strong> -->
                                                <br />
                                                <small>
                                                    <!-- <span class="badge badge-purple mb-3">SUBJECT MODULE</span> -->
                                                </small>
                                            </h5>
                                        </div>


                                        <div class="table-responsive">
                                            <?php if (!empty($data)) : ?>
                                                <h4 class="mb-2 text-center">
                                                    <strong><?= $data[0]->Course; ?></strong>
                                                </h4>
                                                <?php if (!empty($data[0]->Major)) : ?>
                                                    <div class="text-center mb-3">
                                                        <span class="badge badge-primary" style="font-size: 1rem;">
                                                            <?= $data[0]->Major; ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>



                                            <?php
                                            // Group data by YearLevel and Semester
                                            $groupedData = [];

                                            foreach ($data as $row) {
                                                $yearLevel = $row->YearLevel;
                                                $semester = $row->Semester;

                                                $groupedData[$yearLevel][$semester][] = $row;
                                            }
                                            ?>

                                            <?php foreach ($groupedData as $yearLevel => $semesters): ?>
                                                <?php foreach ($semesters as $semester => $subjects): ?>
                                                    <h5><strong>Year Level:</strong> <?= $yearLevel ?> | <strong>Semester:</strong> <?= $semester ?></h5>
                                                    <table class="table table-bordered" style="width: 100%; margin-bottom: 40px;">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Sub. Code</th>
                                                                <th>Description</th>
                                                                <th>Lec Units</th>
                                                                <th>Lab Units</th>
                                                                <th>Prerequisite</th>
                                                                <th style="text-align:center">Manage</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($subjects as $row): ?>
                                                                <tr>
                                                                    <td><?= $row->SubjectCode; ?></td>
                                                                    <td><?= $row->description; ?></td>
                                                                    <td><?= $row->lecunit; ?></td>
                                                                    <td><?= $row->labunit; ?></td>
                                                                    <td><?= $row->prereq; ?></td>
                                                                    <td style="text-align: center;">
                                                                        <button type="button" class="btn btn-primary btn-sm"
                                                                            onclick="editProgram(
                                                                                '<?= $row->subjectid; ?>', '<?= $row->SubjectCode; ?>', 
                                                                                '<?= $row->description; ?>', '<?= $row->YearLevel; ?>', 
                                                                                '<?= $row->Course; ?>', '<?= $row->Semester; ?>',
                                                                                '<?= $row->lecunit; ?>', '<?= $row->labunit; ?>', 
                                                                                '<?= $row->prereq; ?>', '<?= $row->totalUnits; ?>', 
                                                                                '<?= $row->SYEffective; ?>', '<?= $row->Effectivity; ?>')"
                                                                            data-toggle="modal" data-target="#editModal">
                                                                            <i class="mdi mdi-pencil"></i> Edit
                                                                        </button>

                                                                        <a href="#" onclick="setDeleteUrl('<?= base_url('Settings/Deletesubject?id=' . $row->subjectid); ?>')"
                                                                            data-toggle="modal" data-target="#confirmationModal"
                                                                            class="btn btn-danger btn-sm">
                                                                            <i class="ion ion-ios-alert"></i> Delete
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Initialize Select2 -->
                    <script>
                        $(document).ready(function() {
                            $('.select2').select2();
                        });
                    </script>


                    <!-- Update Modal -->
                    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Update Subject</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="<?php echo base_url('Settings/updatesubjects'); ?>">

                                        <input type="hidden" id="editid" name="subjectid">

                                        <div class="form-row align-items-center">
                                            <div class="col-md-4 mb-3">
                                                <label for="subjectCode">Subject Code</label>
                                                <input type="text" class="form-control" id="editSubjectCode" name="SubjectCode" required>
                                            </div>
                                            <div class="col-md-8 mb-3">
                                                <label for="description">Description</label>
                                                <input type="text" class="form-control" id="editDescription" name="description" required>
                                            </div>
                                        </div>

                                        <div class="form-row align-items-center">
                                            <div class="form-group col-md-6 mb-3">
                                                <label for="">Units (Lec/Lab)</label>
                                                <div class="d-flex align-items-center mt-2">
                                                    <input type="number" class="form-control mr-2" name="lecunit" id="lecunit" style="flex: 1;">
                                                    <input type="number" class="form-control" name="labunit" id="labunit" style="flex: 1;">
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="prereq">Prerequisites</label>
                                                <input type="text" class="form-control" name="prereq" id="prereq">
                                            </div>
                                        </div>

                                        <div class="form-row align-items-center">
                                            <input type="hidden" class="form-control" id="totalUnits" name="totalUnits" required readonly>
                                        </div>



                                        <div class="form-row align-items-center">
                                            <div class="col-md-4 mb-3">
                                                <label for="YearLevel">Year Level</label>
                                                <select name="YearLevel" id="YearLevel" class="form-control" required>
                                                    <option value="">Select Year level</option>
                                                    <option value="1st">1st Year</option>
                                                    <option value="2nd">2nd Year</option>
                                                    <option value="3rd">3rd Year</option>
                                                    <option value="4th">4th Year</option>
                                                    <option value="5th">5th Year</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="Semester">Semester</label>
                                                <select name="Semester" id="Semesterup" class="form-control" required>
                                                    <option value="">Select Semester</option>
                                                    <option value="First Semester">First Semester</option>
                                                    <option value="Second Semester">Second Semester</option>
                                                    <option value="Summer">Summer</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label>Effectivity</label>
                                                <input type="text" class="form-control" name="Effectivity" id="Effectivity">
                                            </div>


                                        </div>

                                        <div class="modal-footer">
                                            <input type="submit" name="update" value="Update Data" class="btn btn-primary waves-effect waves-light" />
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>





                    <!-- end container-fluid -->



                    <!-- Confirmation Modal -->
                    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmationModalLabel">Delete Confirmation</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <div class="circle-with-stroke d-inline-flex justify-content-center align-items-center">
                                            <span class="h1 text-danger">!</span>
                                        </div>
                                        <p class="mt-3">Are you sure you want to delete this data?</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <a href="#" id="deleteButton" class="btn btn-danger" onclick="deleteData()">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .circle-with-stroke {
                            width: 100px;
                            height: 100px;
                            border: 4px solid #dc3545;
                            border-radius: 50%;
                        }
                    </style>

                    <script>
                        function setDeleteUrl(url) {
                            document.getElementById('deleteButton').href = url;
                        }

                        function deleteData() {
                            // This will now correctly delete the selected item
                        }
                    </script>

                    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="myLargeModalLabel">Add New</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($selectedCourse)) : ?>
                                        <form method="post" action="<?php echo base_url('Settings/Subjects'); ?>">

                                            <div class="form-row align-items-center">

                                                <div class="col-lg-8">
                                                    <div class="form-group">
                                                        <label>Course <span style="color:red">*</span></label>
                                                        <input type="text" name="Course" class="form-control" value="<?= isset($data[0]) ? $data[0]->Course : $selectedCourse ?>" readonly required>
                                                    </div>
                                                </div>

                                                <div class="col-lg-4">
                                                    <div class="form-group">
                                                        <label>Major</label>
                                                        <input type="text" name="Major" class="form-control" value="<?= isset($data[0]) ? $data[0]->Major : $selectedMajor ?>" readonly>
                                                    </div>
                                                </div>

                                            </div>


                                            <div class="form-row align-items-center">
                                                <div class="col-md-4 mb-3">
                                                    <label for="subjectCode">Subject Code</label>
                                                    <input type="text" class="form-control" id="subjectCode" name="SubjectCode" required>
                                                </div>
                                                <div class="col-md-8 mb-3">
                                                    <label for="description">Description</label>
                                                    <input type="text" class="form-control" id="description" name="description" required>
                                                </div>

                                            </div>

                                            <div class="form-row align-items-center">
                                                <div class="col-md-4 mb-3">
                                                    <label for="">Lec Units</label>
                                                    <input type="number" class="form-control mr-2" name="lecunit" id="lecunit" style="flex: 1;">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="">Lab Units</label>
                                                    <input type="number" class="form-control" name="labunit" id="labunit" style="flex: 1;">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="">Prerequisites</label>
                                                    <input type="text" class="form-control" name="prereq">
                                                </div>
                                            </div>

                                            <div class="form-row align-items-center">

                                                <div class="col-md-4 mb-3">
                                                    <label for="description">Year Level</label>
                                                    <select name="YearLevel" id="YearLevel" class="form-control">
                                                        <option value="">Select Year level</option>
                                                        <option value="1st">1st Year</option>
                                                        <option value="2nd">2nd Year</option>
                                                        <option value="3rd">3rd Year</option>
                                                        <option value="4th">4th Year</option>
                                                        <option value="5th">5th Year</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="description">Semester</label>
                                                    <select name="Semester" id="Semester" class="form-control" required>
                                                        <option value="">Select Semester</option>
                                                        <option value="First Semester">First Semester</option>
                                                        <option value="Second Semester">Second Semester</option>
                                                        <option value="Summer">Summer</option>
                                                    </select>
                                                </div>


                                                <div class="col-md-4 mb-3">
                                                    <label for="SYEffective">Effectivity (Semester, SY)</label>
                                                    <input type="text" class="form-control" id="Effectivity" name="Effectivity" placeholder="e.g., First Semester, 2025-2026" required>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <input type="submit" name="save" value="Submit" class="btn btn-primary waves-effect waves-light" />
                                            </div>
                                        </form>
                                    <?php else : ?>
                                        <div class="alert alert-warning">
                                            <strong>Note:</strong> Please select a course and major first before adding a new subject.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                        </div>
                    </div>

                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
    </div>


    </div>
    </div>
    </div>





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
    <script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>
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
    <!-- Select2 JS -->
    <script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>


    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>


    <script>
        function editProgram(id, subjectCode, description, yearLevel, course, semester, lecunit, labunit, prereq, totalUnits, syEffective, effectivity) {
            console.log('Year Level:', yearLevel); // Log to check the value being passed

            $('#editid').val(id);
            $('#editSubjectCode').val(subjectCode);
            $('#editDescription').val(description);
            $('#lecunit').val(lecunit);
            $('#labunit').val(labunit);
            $('#prereq').val(prereq);
            $('#totalUnits').val(totalUnits);
            $('#Semesterup').val(semester);
            $('#YearLevel').val(yearLevel);
            $('#SYEffective').val(syEffective);
            $('#Effectivity').val(effectivity);

            // Set the course and trigger the change event to load the correct year levels
            $('#editCourse').val(course).trigger('change');

            // Load the year levels and pre-select the one provided
            loadGradeLevels(course, yearLevel);
        }

        // Function to load year levels dynamically based on the selected course
        function loadGradeLevels(course, selectedYearLevel = null) {
            $.ajax({
                url: '<?php echo base_url("Accounting/getMajorsByCourse"); ?>',
                type: 'POST',
                data: {
                    CourseDescription: course
                },
                dataType: 'json',
                cache: false, // Disable caching to ensure fresh data
                success: function(data) {
                    console.log("Year Levels:", data); // Debugging

                    // Clear the dropdown and add the default option
                    $('#editYearLevel').empty().append('<option disabled>Select Grade Level</option>');

                    // Populate the dropdown with new options
                    $.each(data, function(index, major) {
                        const isSelected = major.Major === selectedYearLevel ? 'selected' : '';
                        $('#editYearLevel').append(
                            `<option value="${major.Major}" ${isSelected}>${major.Major}</option>`
                        );
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching year levels:", error); // Debugging
                    UI.error('Could not load the year levels. Please try again.');
                }
            });
        }
    </script>

</body>

</html>
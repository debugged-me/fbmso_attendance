<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

<style>
  .pl-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; flex-wrap:wrap; margin-bottom:16px;
  }
  .pl-header .page-title-box { flex:1 1 auto; margin:0; }
  .pl-header .page-title-box .up-divider { margin:10px 0 0; }
  .pl-header .pl-actions { flex:0 0 auto; align-self:center; display:flex; flex-wrap:wrap; gap:10px; }

  .resp-table thead th {
    background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
    padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
  }
  .resp-table tbody td {
    padding:14px 16px; vertical-align:middle; font-size:.86rem; color:#0d1b4b;
    border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
  }
  .resp-table tbody tr:hover { background:#f8faff !important; }
  .resp-table tbody tr:last-child td { border-bottom:none !important; }

  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter input {
    border-radius:10px !important; border:1px solid #e6ebf5 !important;
    padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important;
  }
  .dataTables_wrapper .dataTables_length select {
    border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:6px 10px !important; margin-left:6px !important;
  }
  .dataTables_paginate .paginate_button {
    border-radius:8px !important; min-width:38px; min-height:38px;
    display:inline-flex !important; align-items:center; justify-content:center;
  }
  .dataTables_paginate .paginate_button.current,
  .dataTables_paginate .paginate_button.current:hover {
    background:linear-gradient(135deg,#2a4090,#4266d4) !important; color:#fff !important;
    border-color:#2a4090 !important;
  }

  @media (max-width:767.98px) {
    .pl-header { flex-direction:column; gap:10px; }
    .pl-header .pl-actions { align-self:flex-start; }
  }
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
          $flashMsgRaw = $this->session->flashdata('msg');
          $flashSuccess = $this->session->flashdata('success');
          $flashError = $this->session->flashdata('error');
          $flashInfo = $this->session->flashdata('info');
          $flashMsg = $flashMsgRaw ? strip_tags($flashMsgRaw) : null;
          ?>

          <!-- Title + actions on one row -->
          <div class="pl-header">
            <div class="page-title-box">
              <h4 class="up-page-title">Manage Courses</h4>
              <div class="up-page-sub">Create, edit, and delete course offerings.</div>
              <hr class="up-divider" />
            </div>
            <div class="pl-actions">
              <a href="<?= base_url(); ?>Page/admin" class="up-btn up-btn-ghost">
                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
              </a>
              <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#courseModal">
                <i class="mdi mdi-plus"></i> Add Course
              </button>
            </div>
          </div>
          <!-- start row -->
          <div class="row">
            <div class="col-md-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-book-open-variant"></i> Course List</h4>
                  <span class="badge badge-purple" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;">SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></span>
                </div>
                <div class="up-card-body" style="padding:0 !important;">
                  <div class="table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive nowrap resp-table" style="width:100%;">
                      <thead>
                        <tr>
                          <th>Code</th>
                          <th>Course</th>
                          <th>Major</th>
                          <th>Duration</th>
                          <th>Recognition No.</th>
                          <th>Series Year</th>
                          <th style="text-align:center;">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($data as $row) { ?>
                          <tr>
                            <td data-label="Code"><?= $row->CourseCode; ?></td>
                            <td data-label="Course"><?= $row->CourseDescription; ?></td>
                            <td data-label="Major"><?= $row->Major; ?></td>
                            <td data-label="Duration"><?= $row->Duration; ?></td>
                            <td data-label="Recognition No."><?= $row->recogNo; ?></td>
                            <td data-label="Series Year"><?= $row->SeriesYear; ?></td>
                            <td data-label="Action" style="text-align:center;">
                              <a href="<?= base_url('Settings/updateCourse?courseid=' . $row->courseid); ?>" class="up-btn up-btn-ghost up-btn-sm"><i class="mdi mdi-pencil"></i> Edit</a>
                              <form action="<?= base_url(); ?>Settings/deleteCourse" method="post" style="display:inline">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
                                <input type="hidden" name="id" value="<?= $row->courseid; ?>" />
                                <button type="submit" class="up-btn up-btn-danger up-btn-sm" onclick="return confirm('Delete course <?= htmlspecialchars($row->CourseDescription, ENT_QUOTES, 'UTF-8'); ?>? This cannot be undone.')">
                                  <i class="mdi mdi-delete-forever"></i> Delete
                                </button>
                              </form>
                            </td>
                          <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- end container-fluid -->
    </div>
    <!-- end content -->




    <!-- Modal -->
    <div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="courseModalLabel">Course</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;"><i class="mdi mdi-close"></i></button>
          </div>
          <div class="modal-body">
            <!-- Form -->
            <form class="form-horizontal" method="POST">
              <div class="card-body">
                <div class="form-group row">
                  <label for="CourseCode" class="col-md-4 col-form-label">Course Code</label>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="CourseCode" placeholder="BS IT" required>
                  </div>
                </div>
                <div class="form-group row">
                  <label for="CourseDescription" class="col-md-4 col-form-label">Course</label>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="CourseDescription" placeholder="Bachelor of Science in Information Technology" required>
                  </div>
                </div>
                <div class="form-group row">
                  <label for="Major" class="col-md-4 col-form-label">Major</label>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="Major" placeholder="">
                  </div>
                </div>
                <div class="form-group row">
                  <label for="Duration" class="col-md-4 col-form-label">Duration</label>
                  <div class="col-md-8">
                    <select class="form-control" name="Duration">
                      <option value=""></option>
                      <option value="1 Year">1 Year</option>
                      <option value="2 Years">2 Years</option>
                      <option value="3 Years">3 Years</option>
                      <option value="4 Years">4 Years</option>
                      <option value="5 Years">5 Years</option>
                    </select>
                  </div>
                </div>
                <div class="form-group row">
                  <label for="recogNo" class="col-md-4 col-form-label">Recognition No./Permit No.</label>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="recogNo" placeholder="">
                  </div>
                </div>
                <div class="form-group row">
                  <label for="SeriesYear" class="col-md-4 col-form-label">Series Year</label>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="SeriesYear" placeholder="">
                  </div>
                </div>
                <!-- <div class="form-group row">
  <label for="ProgramHead" class="col-md-4 col-form-label">Program Head</label>
  <div class="col-md-8">
    <select class="form-control select2" name="IDNumber" id="IDNumber">
      <option value="">Select Program Head</option>
      <?php foreach ($staff as $s): ?>
        <option value="<?= $s->IDNumber ?>">
          <?= $s->FirstName . ' ' . $s->MiddleName . ' ' . $s->LastName ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div> -->



              </div>
              <div class="modal-footer">
                <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                <input type="submit" name="submit" class="btn btn-info" value="Save">
              </div>
            </form>
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
  <script src="<?= base_url(); ?>assets/libs/select2/select2.min.js"></script>

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
    (function() {
      // Flash messages are shown by the shared toast bridge (includes/ui_kit.php).

      function closestByClass(element, className) {
        while (element && element !== document) {
          if (element.classList && element.classList.contains(className)) {
            return element;
          }
          element = element.parentNode;
        }
        return null;
      }

      document.addEventListener('click', function(event) {
        var trigger = closestByClass(event.target, 'course-delete-btn');
        if (!trigger) {
          return;
        }
        event.preventDefault();

        var deleteUrl = trigger.getAttribute('data-delete-url') || trigger.getAttribute('href');
        if (!deleteUrl) {
          return;
        }
        var courseName = trigger.getAttribute('data-course-name') || 'this course';
        var message = 'Delete ' + courseName + '? This cannot be undone.';

        if (window.UI && typeof window.UI.fire === 'function') {
          window.UI.fire({
            title: 'Delete course?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f1556c',
            cancelButtonColor: '#6c757d'
          }).then(function(result) {
            var confirmed = false;
            if (result) {
              if (typeof result.isConfirmed !== 'undefined') {
                confirmed = result.isConfirmed;
              } else if (typeof result.value !== 'undefined') {
                confirmed = !!result.value;
              } else if (result === true) {
                confirmed = true;
              }
            }
            if (confirmed) {
              if (window.UI && UI.navBusy) UI.navBusy('Deleting…');
              window.location.href = deleteUrl;
            }
          });
        } else if (window.confirm(message)) {
          window.location.href = deleteUrl;
        }
      });
    })();
  </script>

  <script>
    $(document).ready(function() {
      $('.select2').select2();
    });
  </script>
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body class="masterlist-page">

  <!-- Begin page -->
  <div id="wrapper">

    <!-- Topbar Start -->
    <?php include('includes/top-nav-bar.php'); ?>
    <!-- end Topbar -->

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
                <!-- Back + Print -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <a href="<?= base_url('Page/admin'); ?>" class="btn btn-primary btn-sm"
                      onclick="if (history.length > 1) { history.back(); return false; }">
                      <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                    <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">
                      <i class="mdi mdi-printer"></i> Print
                    </button>
                  </div>
                </div>

                <h4 class="page-title">
                  Masterlist By Year Level - <?= html_escape($_GET['yearlevel'] ?? '') ?> Year,
                  <?= html_escape($this->session->userdata('semester')) ?> SY <?= html_escape($this->session->userdata('sy')) ?>
                </h4>

                <div class="page-title-right">
                  <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                      <a href="#">Currently login to
                        <b>SY <?= html_escape($this->session->userdata('sy')) ?>
                          <?= html_escape($this->session->userdata('semester')) ?></b></a>
                    </li>
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
                <div class="card-body">
                  <?php echo $this->session->flashdata('msg'); ?>

                  <!-- DataTable with Responsive child rows -->
                  <table id="datatable" class="table table-bordered table-striped w-100">
                    <thead>
                      <tr>
                        <!-- control column for plus icon -->
                        <th></th>
                        <th>Student Name</th>
                        <th>Student No.</th>
                        <th>Course</th>
                        <th>Section</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($data as $row): ?>
                        <tr>
                          <td></td> <!-- control cell -->
                          <td><?= html_escape($row->LastName . ', ' . $row->FirstName . ' ' . $row->MiddleName) ?></td>
                          <td><?= html_escape($row->StudentNumber) ?></td>
                          <td><?= html_escape($row->Course ?? '') ?></td>
                          <td><?= html_escape($row->Section ?? '') ?></td>

                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>

                </div>
              </div>
            </div>
          </div>

        </div><!-- end container-fluid -->
      </div><!-- end content -->

      <?php include('includes/footer.php'); ?>

    </div>
    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

  </div><!-- END wrapper -->

  <!-- Right Sidebar -->
  <?php include('includes/themecustomizer.php'); ?>
  <!-- /Right-bar -->

  <!-- Vendor js -->
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

  <!-- App js -->
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <!-- DataTables core -->
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Buttons -->
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>

  <!-- Responsive extension -->
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

  <!-- LOCAL DataTable init: enable child-row details -->
  <script>
    $(function() {
      var $t = $('#datatable');
      if ($.fn.DataTable.isDataTable($t)) {
        $t.DataTable().destroy();
      }

      $t.DataTable({
        responsive: {
          details: {
            type: 'column', // show "+" in the control column
            target: 0
          }
        },
        columnDefs: [{
            targets: 0,
            className: 'control',
            orderable: false,
            searchable: false
          }, // plus icon col
          {
            targets: 1,
            responsivePriority: 1
          }, // Student Name - always try to keep
          {
            targets: 2,
            responsivePriority: 2
          }, // Student No.
          {
            targets: 3,
            responsivePriority: 3
          }, // Course
          {
            targets: 4,
            responsivePriority: 4
          }, // Section
          {
            targets: 5,
            orderable: false,
            searchable: false,
            responsivePriority: 5
          } // Action
        ],
        order: [
          [1, 'asc']
        ], // sort by Student Name
        autoWidth: false,
        dom: "<'row'<'col-12'tr>>" +
          "<'row align-items-center mt-2'<'col-sm-6'i><'col-sm-6'p>>",
        language: {
          paginate: {
            previous: 'Previous',
            next: 'Next'
          }
        }
      });
    });
  </script>

  <!-- PRINT-ONLY CSS -->
  <style>
    @media print {

      /* Hide chrome & non-essential UI */
      #wrapper .topbar,
      #wrapper .left-side-menu,
      #wrapper .sidebar,
      #wrapper .right-bar,
      .page-title-box,
      .themecustomizer,
      .footer,
      .btn {
        display: none !important;
      }

      /* Hide DataTables controls/info/pagination blocks */
      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        display: none !important;
      }

      /* Paper setup */
      @page {
        size: A4 portrait;
        margin: 12mm;
      }

      body {
        margin: 0;
      }

      table {
        font-size: 11pt;
      }

      th,
      td {
        padding: 6px 8px !important;
      }

      a {
        text-decoration: none !important;
        color: #000 !important;
      }
    }
  </style>

  <!-- MOBILE POLISH -->
  <style>
    .table-responsive {
      -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 576px) {

      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_info {
        display: none !important;
      }

      #datatable th,
      #datatable td {
        padding: .6rem .5rem;
        font-size: 13px;
      }
    }
  </style>

</body>

</html>


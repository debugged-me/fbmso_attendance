<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<style>
  #datatable thead th {
    background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
    padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
  }
  #datatable tbody td {
    padding:14px 16px; vertical-align:middle; font-size:.88rem; color:#0d1b4b;
    border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
  }
  #datatable tbody tr:hover { background:#f8faff !important; }
  #datatable tbody tr:last-child td { border-bottom:none !important; }
  .dataTables_filter input {
    border-radius:10px !important; border:1px solid #e6ebf5 !important;
    padding:8px 14px !important; font-size:.86rem !important;
  }
  .dataTables_filter input:focus { border-color:#4266d4 !important; box-shadow:0 0 0 3px rgba(66,102,212,.12) !important; outline:none !important; }
  .dataTables_length select {
    border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:6px 10px !important;
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
  /* Pad the info + pagination + filter row so it doesn't hug the card edge */
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate {
    padding:14px 18px !important;
    margin:0 !important;
  }
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_length {
    padding:16px 18px 12px !important;
    margin:0 !important;
  }
  .dataTables_wrapper .dataTables_filter input { margin-left:6px !important; }
  .dataTables_wrapper .dataTables_length select { margin-left:6px !important; }

  /* Action buttons row */
  .pl-actions { display:flex; flex-wrap:wrap; gap:10px; margin:0; }
  .pl-actions > .up-btn,
  .pl-actions > a.up-btn,
  .pl-actions > button.up-btn { margin-right:10px; margin-bottom:6px; }
  @supports (gap:10px) { .pl-actions > .up-btn { margin-right:0; } }

  /* Title + actions on one row */
  .pl-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; flex-wrap:wrap; margin-bottom:16px;
  }
  .pl-header .page-title-box { flex:1 1 auto; margin:0; }
  .pl-header .page-title-box .up-divider { margin:10px 0 0; }
  .pl-header .pl-actions { flex:0 0 auto; align-self:center; }

  /* ===== Mobile: table becomes cards ===== */
  @media (max-width: 767.98px) {
    .pl-header { flex-direction:column; gap:10px; margin-bottom:14px; }
    .pl-header .pl-actions { align-self:flex-start; }
    .pl-actions .up-btn { font-size:.8rem; padding:8px 14px; }

    /* DataTables wrapper: let it flow as block */
    .dataTables_wrapper { display:block !important; }
    .dataTables_scrollHead { display:none !important; }
    .dataTables_scrollBody {
      overflow:visible !important; height:auto !important;
      border:0 !important;
    }
    .table-responsive { overflow:visible !important; border:0 !important; }

    /* Hide table header — cards use data-label */
    #datatable thead { display:none; }
    #datatable { width:100% !important; border:0 !important; }
    #datatable tbody { display:block; }
    #datatable tbody tr {
      display:block;
      margin:0 0 14px;
      padding:14px 16px;
      border:1px solid #e6ebf5 !important;
      border-radius:14px;
      background:#fff;
      box-shadow:0 6px 18px rgba(13,27,75,.06);
    }
    #datatable tbody tr:last-child { margin-bottom:0; }
    #datatable tbody tr:hover { background:#f8faff !important; }
    #datatable tbody td {
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:.6rem;
      width:100%;
      padding:7px 0 !important;
      border:0 !important;
      border-bottom:1px solid #f0f3f8 !important;
      font-size:.9rem;
      text-align:right;
      white-space:normal;
    }
    #datatable tbody tr:last-child td:last-child { border-bottom:0 !important; }
    #datatable tbody td::before {
      flex:0 0 42%;
      content:attr(data-label);
      color:#6b7a99;
      font-size:.72rem;
      font-weight:700;
      letter-spacing:.04em;
      text-transform:uppercase;
      text-align:left;
    }
    /* Action cell: full-width buttons stacked */
    #datatable tbody td:last-child {
      flex-direction:column;
      align-items:stretch;
      gap:6px;
      border-bottom:0 !important;
    }
    #datatable tbody td:last-child::before { display:none; }
    #datatable tbody td:last-child .btn,
    #datatable tbody td:last-child a,
    #datatable tbody td:last-child button {
      width:100%;
      text-align:center;
      font-size:.82rem;
      padding:8px 12px;
    }

    /* DataTables filter/length/pagination — stacked */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
      float:none !important;
      text-align:left !important;
      padding:10px 0 !important;
    }
    .dataTables_wrapper .dataTables_filter input {
      width:100% !important; margin-left:0 !important;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
      float:none !important;
      text-align:center !important;
      padding:8px 0 !important;
    }
    .dataTables_paginate .paginate_button { min-width:34px; min-height:34px; }
  }</style>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>
    <?php

    function view_signup_url($id)
    {

      return site_url('Page/editSignup') . '?id=' . rawurlencode($id);
    }
    ?>
    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <?php
          $flashSuccess = $this->session->flashdata('success');
          $flashDanger  = $this->session->flashdata('danger');
          $flashMessage = $this->session->flashdata('message');
          ?>

          <?php if ($flashSuccess): ?>
            <div class="up-flash up-flash-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <?php if ($flashDanger): ?>
            <div class="up-flash up-flash-danger"><?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <?php if ($flashMessage): ?>
            <div class="up-flash up-flash-info"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <!-- Title + actions on one row -->
          <div class="pl-header">
            <div class="page-title-box">
              <h4 class="up-page-title">Registered Students</h4>
              <div class="up-page-sub">View, edit, and manage student profiles.</div>
              <hr class="up-divider" />
            </div>

            <div class="pl-actions">
              <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
              </a>
              <a href="<?= site_url('Registration/index') . '?source=admin'; ?>" class="up-btn up-btn-primary">
                <i class="mdi mdi-account-plus"></i> Add Student
              </a>
              <a href="<?= base_url('Page/duplicateStudentsByName'); ?>" class="up-btn up-btn-ghost" style="background:#fef3c7;color:#92400e;border-color:#fcd34d;">
                <i class="mdi mdi-account-multiple"></i> Duplicate Students
              </a>
              <button type="button" class="up-btn up-btn-ghost" onclick="window.print()">
                <i class="mdi mdi-printer"></i> Print
              </button>
            </div>
          </div>

          <!-- Students table card -->
          <div class="row">
            <div class="col-md-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-account-group"></i> Student List</h4>
                  <span class="badge badge-light" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;color:#6b7a99;border:1px solid #e6ebf5;">
                    <?= number_format(count($data)); ?> records
                  </span>
                </div>
                <div class="up-card-body" style="padding:0 !important;">
                  <div class="table-responsive" style="padding:0;">
                    <table id="datatable" class="table table-hover dt-responsive nowrap" style="width:100%;margin:0;">
                      <thead>
                        <tr>
                          <th>Student Name</th>
                          <th>Student No.</th>
                          <th>Email</th>
                          <th style="width:110px">Birth Date</th>
                          <th style="text-align:center;width:320px">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $allowedRoles = ['head registrar', 'registrar', 'assistant registrar', 'admin', 'administrator'];
                        $canDelete = in_array(
                            strtolower(trim((string)($this->session->userdata('level') ?? ''))),
                            $allowedRoles,
                            true
                        );
                        ?>
                        <?php foreach ($data as $row): ?>
                          <?php
                          $ln = trim($row->LastName ?? '');
                          $fn = trim($row->FirstName ?? '');
                          $mn = trim($row->MiddleName ?? '');
                          $fullname = trim(($ln ? $ln : '') . (($ln || $fn) ? ', ' : '') . ($fn ? $fn : '') . ($mn ? ' ' . $mn : ''));
                          if ($fullname === '' && !empty($row->StudentNumber)) $fullname = $row->StudentNumber;

                          $studno = $row->StudentNumber ?? '';
                          $bdate  = !empty($row->birthDate) ? $row->birthDate : 'N/A';
                          $email  = trim((string)($row->email ?? ''));
                          $yl     = $row->yearLevel ?? '';
                          $sec    = $row->section ?? '';
                          ?>
                          <tr>
                            <td data-label="Student Name">
                              <div style="font-weight:700;color:#0d1b4b;"><?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?></div>
                              <?php if ($yl || $sec): ?>
                                <div style="font-size:.76rem;color:#6b7a99;margin-top:2px;"><?= htmlspecialchars("$yl $sec", ENT_QUOTES, 'UTF-8'); ?></div>
                              <?php endif; ?>
                            </td>
                            <td data-label="Student No." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:#2a4090;"><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Email"><?= $email ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '<span style="color:#9aa5b8;">N/A</span>'; ?></td>
                            <td data-label="Birth Date" style="color:#6b7a99;"><?= htmlspecialchars($bdate, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Action" class="text-center">
                              <a href="<?= view_signup_url($studno); ?>" class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;">
                                <i class="mdi mdi-eye-outline"></i> View
                              </a>
                              <?php if ($canDelete): ?>
                                <?php
                                $resetHref = base_url('Page/resetPass?u=' . rawurlencode((string)$studno) . '&return_to=profileList');
                                ?>
                                <a href="<?= $resetHref; ?>"
                                  class="up-btn up-btn-ghost reset-pass-btn"
                                  style="padding:6px 12px;font-size:.78rem;min-height:auto;background:#fef3c7;color:#92400e;border-color:#fcd34d;"
                                  data-href="<?= htmlspecialchars($resetHref, ENT_QUOTES, 'UTF-8'); ?>"
                                  data-studno="<?= htmlspecialchars((string)$studno, ENT_QUOTES, 'UTF-8'); ?>">
                                  <i class="mdi mdi-lock-reset"></i> Reset
                                </a>
                                <form method="post" action="<?= base_url('Page/deleteSignup'); ?>" style="display:inline" class="delete-signup-form">
                                  <input type="hidden" name="id" value="<?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?>">
                                  <button type="button" class="up-btn delete-signup-btn" style="padding:6px 12px;font-size:.78rem;min-height:auto;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;" data-studno="<?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="mdi mdi-delete-forever"></i> Delete
                                  </button>
                                </form>
                              <?php else: ?>
                                <span style="color:#9aa5b8;">&mdash;</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div style="height:40px;"></div>

        </div>
      </div>
      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" />
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script>
    $(function() {
      $('#datatable').DataTable();
    });
  </script>
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

      function handleDeleteClick(event, button) {
        event.preventDefault();
        var form = button.closest('form');
        if (!form) {
          return;
        }
        var studno = button.getAttribute('data-studno') || 'this record';
        var promptText = 'Delete ' + studno + '? This cannot be undone.';

        var confirmed = function(result) {
          var ok = false;
          if (result) {
            if (typeof result.isConfirmed !== 'undefined') {
              ok = result.isConfirmed;
            } else if (typeof result.value !== 'undefined') {
              ok = !!result.value;
            } else if (result === true) {
              ok = true;
            }
          }
          if (ok) {
            if (window.UI && UI.navBusy) UI.navBusy('Deleting ' + studno + '…');
            form.submit();
          }
        };

        if (window.UI && typeof window.UI.fire === 'function') {
          window.UI.fire({
            title: 'Delete record?',
            text: promptText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f1556c',
            cancelButtonColor: '#6c757d'
          }).then(confirmed);
        } else if (window.confirm(promptText)) {
          form.submit();
        }
      }

      function handleResetClick(event, button) {
        event.preventDefault();
        var href = button.getAttribute('data-href') || button.getAttribute('href');
        if (!href) {
          return;
        }
        var studno = button.getAttribute('data-studno') || 'this record';
        var promptText = 'Reset password for ' + studno + '? A temporary password will be emailed.';

        var confirmed = function(result) {
          var ok = false;
          if (result) {
            if (typeof result.isConfirmed !== 'undefined') {
              ok = result.isConfirmed;
            } else if (typeof result.value !== 'undefined') {
              ok = !!result.value;
            } else if (result === true) {
              ok = true;
            }
          }
          if (ok) {
            if (window.UI && UI.navBusy) UI.navBusy('Resetting the password…');
            window.location.href = href;
          }
        };

        if (window.UI && typeof window.UI.fire === 'function') {
          window.UI.fire({
            title: 'Reset password?',
            text: promptText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reset',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f0ad4e',
            cancelButtonColor: '#6c757d'
          }).then(confirmed);
        } else if (window.confirm(promptText)) {
          window.location.href = href;
        }
      }

      document.addEventListener('click', function(event) {
        var button = closestByClass(event.target, 'delete-signup-btn');
        if (button) {
          handleDeleteClick(event, button);
          return;
        }
        var resetButton = closestByClass(event.target, 'reset-pass-btn');
        if (resetButton) {
          handleResetClick(event, resetButton);
        }
      });
    })();
  </script>

  <!-- Print-only document header (hidden on screen) -->
  <div id="printHeader" style="display:none;">
    <div class="ph-school"><?= isset($school[0]->SchoolName) ? htmlspecialchars($school[0]->SchoolName, ENT_QUOTES, 'UTF-8') : 'FBMSO Attendance'; ?></div>
    <div class="ph-address"><?= isset($school[0]->SchoolAddress) ? htmlspecialchars($school[0]->SchoolAddress, ENT_QUOTES, 'UTF-8') : ''; ?></div>
    <div class="ph-title">Registered Students</div>
    <div class="ph-meta">
      <span>Printed: <?= date('F d, Y \a\t h:i A'); ?></span>
      <span>Total Records: <?= number_format(count($data)); ?></span>
    </div>
    <div class="ph-line"></div>
  </div>

  <style>
    @media print {
      /* Hide everything that's not the print document */
      #wrapper .topbar,
      #wrapper .left-side-menu,
      #wrapper .sidebar,
      #wrapper .right-bar,
      .themecustomizer,
      .footer,
      .page-title-box,
      .pl-actions,
      .up-card-head,
      .up-flash,
      .btn,
      .delete-signup-form,
      .delete-signup-btn,
      .reset-pass-btn,
      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        display: none !important;
      }

      /* Show the print header */
      #printHeader { display: block !important; }

      /* Print header styling */
      #printHeader {
        text-align: center;
        margin-bottom: 20px;
      }
      #printHeader .ph-school {
        font-size: 16pt;
        font-weight: 800;
        color: #0d1b4b;
        margin: 0;
      }
      #printHeader .ph-address {
        font-size: 10pt;
        color: #555;
        margin: 2px 0 10px;
      }
      #printHeader .ph-title {
        font-size: 13pt;
        font-weight: 700;
        color: #2a4090;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 8px 0 4px;
      }
      #printHeader .ph-meta {
        font-size: 9pt;
        color: #777;
        display: flex;
        justify-content: center;
        gap: 20px;
      }
      #printHeader .ph-line {
        height: 2px;
        background: linear-gradient(to right, #2a4090, #4266d4, #2a4090);
        margin: 10px 0 16px;
        border-radius: 1px;
      }

      /* Hide Action column (last) and Birth Date column */
      #datatable th:last-child,
      #datatable td:last-child { display: none !important; }

      /* Table: clean document style */
      @page {
        size: A4 portrait;
        margin: 14mm;
      }

      body {
        margin: 0;
        background: #fff !important;
      }

      .content-page {
        margin-left: 0 !important;
        margin-top: 0 !important;
        padding: 0 !important;
      }

      .up-card {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }

      .up-card-body {
        padding: 0 !important;
      }

      #datatable {
        font-size: 9.5pt;
        border-collapse: collapse;
        width: 100% !important;
      }

      #datatable thead th {
        background: #2a4090 !important;
        color: #fff !important;
        font-size: 8pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 8px 10px !important;
        border: 1px solid #2a4090 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      #datatable tbody td {
        padding: 6px 10px !important;
        font-size: 9.5pt;
        color: #1a1a1a !important;
        border: 1px solid #ccc !important;
      }

      #datatable tbody tr:nth-child(even) td {
        background: #f5f7fc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      #datatable tbody tr:hover { background: transparent !important; }

      /* Force DataTables to show ALL rows when printing */
      .dataTables_wrapper { display: block !important; }
      .dataTables_scrollHead { display: none !important; }
      .dataTables_scrollBody { height: auto !important; overflow: visible !important; }
      #datatable { width: 100% !important; }
      #datatable tbody tr { display: table-row !important; }
    }
  </style>

  <script>
    // Before printing: expand DataTable to show all rows, then restore after
    (function() {
      var dtTable = null;
      var savedPageLen = null;

      window.addEventListener('beforeprint', function() {
        if (window.jQuery && $('#datatable').length) {
          try {
            dtTable = $('#datatable').DataTable();
            savedPageLen = dtTable.page.len();
            dtTable.page.len(-1).draw(false);
          } catch(e) {}
        }
      });

      window.addEventListener('afterprint', function() {
        if (dtTable && savedPageLen !== null) {
          try {
            dtTable.page.len(savedPageLen).draw(false);
          } catch(e) {}
        }
      });
    })();
  </script>

</body>

</html>

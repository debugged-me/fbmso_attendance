<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

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
          ?>

          <div class="row">
            <div class="col-md-12">
              <div class="page-title-box">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <a href="<?= base_url('Page/admin'); ?>" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                      </a>
                      <a href="<?= site_url('Registration/index') . '?source=admin'; ?>" class="btn btn-success btn-sm">
                        <i class="mdi mdi-account-plus"></i> Add Student
                      </a>
                      <a href="<?= base_url('Page/duplicateStudentsByName'); ?>" class="btn btn-warning btn-sm">
                        <i class="mdi mdi-account-multiple"></i> Duplicate Students
                      </a>
                      <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">
                        <i class="mdi mdi-printer"></i> Print
                      </button>
                    </div>
                  </div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body table-responsive">
                  <h4 class="m-t-0 header-title mb-4">LIST OF REGISTERED STUDENTS</h4>
                  <table id="datatable" class="table table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                      <tr>
                        <th>Student Name</th>
                        <th>Student No.</th>
                        <th style="width:110px">Birth Date</th>
                        <th style="text-align:center;width:320px">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Hoisted: the same for every row, so compute it once
                      // rather than rebuilding the array 2,400 times.
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
                        $yl     = $row->yearLevel ?? '';
                        $sec    = $row->section ?? '';
                        $stat   = $row->signupStatus ?? '';
                        ?>
                        <tr>
                          <td>
                            <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($yl || $sec): ?>
                              <div class="text-muted small"><?= htmlspecialchars("$yl $sec", ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <?php if ($stat): ?>
                              <div class="text-muted small">Status: <?= htmlspecialchars($stat, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?= htmlspecialchars($bdate, ENT_QUOTES, 'UTF-8'); ?></td>
                          <td class="text-center">
                            <a href="<?= view_signup_url($studno); ?>" class="btn btn-info btn-xs">
                              <i class="mdi mdi-eye-outline"></i> View
                            </a>
                            <?php if ($canDelete): ?>
                              <?php
                              $resetHref = base_url('Page/resetPass?u=' . rawurlencode((string)$studno) . '&return_to=profileList');
                              ?>
                              <a href="<?= $resetHref; ?>"
                                class="btn btn-warning btn-xs reset-pass-btn"
                                data-href="<?= htmlspecialchars($resetHref, ENT_QUOTES, 'UTF-8'); ?>"
                                data-studno="<?= htmlspecialchars((string)$studno, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="mdi mdi-lock-reset"></i> Reset Password
                              </a>
                              <form method="post" action="<?= base_url('Page/deleteSignup'); ?>" style="display:inline" class="delete-signup-form">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="button" class="btn btn-danger btn-xs delete-signup-btn" data-studno="<?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?>">
                                  <i class="mdi mdi-delete-forever"></i> Delete
                                </button>
                              </form>
                            <?php else: ?>
                              <span class="text-muted">&mdash;</span>
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
      </div>
      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
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

  <style>
    @media print {


      #wrapper .topbar,
      #wrapper .left-side-menu,
      #wrapper .sidebar,
      #wrapper .right-bar,
      .page-title-box,
      .themecustomizer,
      .footer,
      .btn,
      .delete-signup-form,
      .delete-signup-btn {
        display: none !important;
      }


      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate {
        display: none !important;
      }


      #datatable th:nth-child(4),
      #datatable td:nth-child(4) {
        display: none !important;
      }


      #datatable td:nth-child(1) .text-muted.small {
        display: none !important;
      }


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
    }
  </style>

</body>

</html>

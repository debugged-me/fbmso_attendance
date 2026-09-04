<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

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

  .table-action-links a { display:inline-flex; align-items:center; gap:4px; margin-right:14px; font-size:.84rem; font-weight:600; }
  .table-action-links a i { font-size:16px; }

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
    .table-action-links { display:block; padding-top:8px; }
    .table-action-links a { display:inline-flex; margin:4px 10px 4px 0; }
  }
</style>

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
              <?php
              $flashSuccessRaw = (string)$this->session->flashdata('success');
              $flashDangerRaw  = (string)$this->session->flashdata('danger');

              $normalizeFlash = static function ($message) {
                if ($message === '') {
                  return '';
                }
                $normalized = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $message);
                $normalized = strip_tags($normalized);
                $normalized = html_entity_decode($normalized, ENT_QUOTES, 'UTF-8');
                $normalized = preg_replace("/\r?\n\s*/", "\n", $normalized);
                return trim($normalized);
              };
              $flashSuccessText = $normalizeFlash($flashSuccessRaw);
              $flashDangerText  = $normalizeFlash($flashDangerRaw);
              ?>
              <input type="hidden" id="flashSuccess" value="<?= htmlspecialchars($flashSuccessText, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" id="flashDanger" value="<?= htmlspecialchars($flashDangerText, ENT_QUOTES, 'UTF-8'); ?>">

              <div class="pl-header">
                <div class="page-title-box">
                  <h4 class="up-page-title">User Accounts</h4>
                  <div class="up-page-sub">Manage admin, staff, and personnel account credentials.</div>
                  <hr class="up-divider" />
                </div>
                <div class="pl-actions">
                  <a href="<?= base_url(); ?>Page/admin" class="up-btn up-btn-ghost">
                    <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                  </a>
                  <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target=".bs-example-modal-lg">
                    <i class="mdi mdi-account-plus"></i> Add New
                  </button>
                </div>
              </div>
            </div>
          </div>


          <div class="row">
            <div class="col-md-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-shield-account-outline"></i> Account List</h4>
                  <span class="badge badge-purple" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;">SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></span>
                </div>
                <div class="up-card-body" style="padding:0 !important;">
                  <div class="table-responsive">
                  <table id="datatable-buttons" class="table table-striped dt-responsive nowrap resp-table" style="width:100%;">
                    <thead>
                      <tr>
                        <th>Account Name</th>
                        <th>Username</th>
                        <th>Account Level</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($data as $row) {
                        // Hide Student accounts entirely
                        if (strcasecmp($row->position, 'Student') === 0) {
                          continue;
                        }

                        echo "<tr>";
                        echo "<td>" . $row->fName . ', ' . $row->mName . ' ' . $row->lName . "</td>";
                      ?>

                        <td><?php echo $row->username; ?></td>
                        <td><?php echo $row->position; ?></td>
                        <td><?php echo $row->email; ?></td>
                        <td><?php echo $row->acctStat; ?></td>
                        <td class="table-action-links">
                          <?php if ($row->position != 'Teacher' && $row->position != 'Student'): ?>
                            <a href="#" class="text-primary edit-user-btn"
                              data-username="<?= htmlspecialchars($row->username, ENT_QUOTES, 'UTF-8'); ?>"
                              data-email="<?= htmlspecialchars($row->email, ENT_QUOTES, 'UTF-8'); ?>"
                              data-position="<?= htmlspecialchars($row->position, ENT_QUOTES, 'UTF-8'); ?>"
                              data-name="<?= htmlspecialchars(trim($row->fName . ' ' . $row->lName), ENT_QUOTES, 'UTF-8'); ?>">
                              <i class="mdi mdi-pencil"></i> Edit
                            </a>
                          <?php endif; ?>

                          <?php
                          $resetHref   = base_url('page/resetPass?u=' . urlencode($row->username));
                          $deleteHref  = base_url('Login/deleteUser/' . urlencode($row->username));
                          $deactHref   = base_url('page/changeUserStat?u=' . urlencode($row->username) . '&t=Deactivate');
                          $activateHref = base_url('page/changeUserStat?u=' . urlencode($row->username) . '&t=Activate');
                          $displayName = trim($row->fName . ' ' . $row->lName);
                          ?>

                          <a href="<?= $resetHref; ?>"
                            class="text-success reset-password-btn"
                            data-href="<?= $resetHref; ?>"
                            data-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="mdi mdi-file-document-box-check-outline"></i>Reset Password
                          </a>

                          <a href="<?= $deleteHref; ?>"
                            class="text-warning delete-account-btn"
                            data-href="<?= $deleteHref; ?>"
                            data-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="mdi mdi-file-document-box-check-outline"></i> Delete Account
                          </a>

                          <?php if ($row->acctStat == 'active'): ?>
                            <a href="<?= $deactHref; ?>"
                              class="text-danger change-status-btn"
                              data-href="<?= $deactHref; ?>"
                              data-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                              data-action="deactivate">
                              <i class="mdi mdi-file-document-box-check-outline"></i>Deactivate
                            </a>
                          <?php else: ?>
                            <a href="<?= $activateHref; ?>"
                              class="text-success change-status-btn"
                              data-href="<?= $activateHref; ?>"
                              data-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                              data-action="activate">
                              <i class="mdi mdi-file-document-box-check-outline"></i>Activate
                            </a>
                          <?php endif; ?>
                        </td>
                      <?php
                        echo "</tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                  </div>
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


  <!--  Modal content for the above example -->
  <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="myLargeModalLabel">Add New User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;"><i class="mdi mdi-close"></i></button>
        </div>
        <div class="modal-body">
          <form class="form-horizontal parsley-examples" method="POST">
            <div class="card-body">
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">First Name</label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" name="fName" placeholder="" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">Middle Name</label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" name="mName" placeholder="">
                </div>
              </div>
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">Last Name</label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" name="lName" placeholder="" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">Employee No./Student No.</label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" name="IDNumber" placeholder="" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">E-mail</label>
                <div class="col-sm-8">
                  <input type="email" class="form-control" name="email" placeholder="" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">Account Level</label>
                <div class="col-sm-8">
                  <select class="form-control" name="acctLevel" required>
                    <!-- <option value=""></option>
                    <option value="Accounting">Accounting</option>
                    <option value="Cashier">Accounting - Cashier</option> -->
                    <option value="Admin">Admin</option>
                    <!-- <option value="HR Admin">HR Admin</option>
                    <option value="Guidance">Guidance</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Registrar">Registrar</option>
                    <option value="School Nurse">School Nurse</option> -->
                    <option value="Student">Student</option>
                    <!-- <option value="Academic Officer">Academic Officer</option>
                    <option value="Principal">Principal</option>
                    <option value="Property Custodian">Property Custodian</option>
                    <option value="Encoder">Encoder</option>
                    <option value="BAC">BAC</option> -->
                  </select>
                </div>
              </div>


              <div class="form-group row">
                <label for="inputEmail3" class="col-sm-4 col-form-label">Username<br /><span style="color:red"><small>Student No. for Students/Employee No. for Teachers</small></span></label>
                <div class="col-sm-8">
                  <input type="text" class="form-control" name="username" placeholder="" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="inputPassword3" class="col-sm-4 col-form-label">Password</label>
                <div class="col-sm-8">
                  <input
                    type="password"
                    class="form-control"
                    name="password"
                    placeholder=""
                    required
                    minlength="8"

                    title="Password must be at least 8 characters long.">

                </div>
              </div>

            </div>
            <!-- /.card-body -->
            <div class="card-footer">
              <input type="submit" name="submit" class="btn btn-info float-right" value="Create Account">
            </div>
            <!-- /.card-footer -->
          </form>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.modal -->

  <script>
    document.querySelector('input[name="password"]').addEventListener('input', function(e) {
      const password = e.target.value;
      const minLength = 8;
      // const hasUpperCase = /[A-Z]/.test(password);
      // const hasLowerCase = /[a-z]/.test(password);
      // const hasDigit = /\d/.test(password);
      // const hasSpecialChar = /[@$!%*?&]/.test(password);

      if (password.length >= minLength) {
        // if (password.length >= minLength && hasUpperCase && hasLowerCase && hasDigit && hasSpecialChar) {
        e.target.setCustomValidity('');
      } else {
        e.target.setCustomValidity('Password must be at least 8 characters long.');
        // e.target.setCustomValidity('Password must be at least 8 characters long and include a mix of uppercase letters, lowercase letters, digits, and special characters.');
      }
    });
  </script>



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

  <!-- Plugin js-->
  <script src="<?= base_url(); ?>assets/libs/parsleyjs/parsley.min.js"></script>

  <!-- Validation init js-->
  <script src="<?= base_url(); ?>assets/js/pages/form-validation.init.js"></script>




  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form method="POST" action="<?= base_url('Page/updateUserInfo'); ?>">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit User Info</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <!-- Hidden input for username -->
            <input type="hidden" name="username" id="modalUsername">

            <!-- Account Level -->
            <div class="form-group">
              <label for="modalAcctLevel"> Account Level </label>
              <select class="form-control" name="acctLevel" id="modalAcctLevel" required>
                <option value="">-- Select Level --</option>
                <!-- <option value="Accounting">Accounting</option>
                <option value="Cashier">Accounting - Cashier</option> -->
                <option value="Admin"> Admin </option>
                <!-- <option value="HR Admin">HR Admin</option>
                <option value="Guidance">Guidance</option>
                <option value="Librarian">Librarian</option>
                <option value="Instructor">Instructor</option>
                <option value="Registrar">Registrar</option>
                <option value="School Nurse">School Nurse</option> -->
                <option value="Student"> Student </option>
                <!-- <option value="Academic Officer">Academic Officer</option>
                <option value="Principal">Principal</option>
                <option value="Property Custodian">Property Custodian</option>
                <option value="Encoder">Encoder</option>
                <option value="BAC">BAC</option> -->
              </select>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="modalEmail">Email</label>
              <input type="email" class="form-control" name="email" id="modalEmail" required>
            </div>
          </div>

          <div class="modal-footer">
            <input type="submit" name="submitEdit" class="btn btn-primary" value="Update">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>


  <script>
    (function($) {
      var flashSuccess = $('#flashSuccess').val();
      var flashDanger = $('#flashDanger').val();

      function confirmAction(opts) {
        var swalType = opts.icon || opts.type || 'warning';
        var config = {
          title: opts.title,
          text: opts.text,
          type: swalType,
          showCancelButton: (typeof opts.showCancel === 'boolean') ? opts.showCancel : true,
          confirmButtonText: opts.confirmText || 'Yes',
          cancelButtonText: opts.cancelText || 'Cancel',
          confirmButtonColor: opts.confirmColor || '#2563eb',
          cancelButtonColor: '#94a3b8',
          reverseButtons: true,
          focusCancel: true
        };

        if (typeof UI.fire === 'function') {
          config.icon = swalType;
          return UI.fire(config).then(function(result) {
            if ((result.value === true || result.isConfirmed === true) && typeof opts.onConfirm === 'function') {
              // Only a real confirmation leads somewhere; a plain notice does not.
              if (config.showCancelButton && window.UI && UI.navBusy) {
                UI.navBusy(opts.busyText || 'Working…');
              }
              opts.onConfirm();
            }
          });
        }

        if (typeof swal === 'function') {
          return swal(config).then(function(isConfirmed) {
            if (isConfirmed && typeof opts.onConfirm === 'function') {
              opts.onConfirm();
            }
          });
        }

        if (window.confirm(opts.text || opts.title)) {
          if (typeof opts.onConfirm === 'function') {
            opts.onConfirm();
          }
        }
      }

      $(function() {
        var $table = $('#datatable-buttons');
        if ($.fn && $.fn.dataTable) {
          $.fn.dataTable.ext.errMode = 'none';
          if ($.fn.dataTable.isDataTable($table)) {
            $table.DataTable().destroy();
          }
          $table.DataTable({
            processing: false,
            serverSide: false,
            deferRender: true,
            autoWidth: false,
            pageLength: 20,
            lengthMenu: [
              [10, 20, 50, -1],
              [10, 20, 50, 'All']
            ],
            pagingType: 'simple',
            order: [
              [0, 'asc']
            ],
            responsive: false,
            columnDefs: [{
                responsivePriority: 1,
                targets: 0
              },
              {
                responsivePriority: 2,
                targets: -1
              },
              {
                responsivePriority: 3,
                targets: 1
              },
              {
                responsivePriority: 4,
                targets: 2
              }
            ],
            dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
              search: '',
              searchPlaceholder: 'Search accounts...',
              lengthMenu: 'Show _MENU_',
              zeroRecords: 'No matching accounts found'
            }
          });
        }

        if (flashSuccess) {
          confirmAction({
            title: 'Success',
            text: flashSuccess,
            icon: 'success',
            confirmText: 'Close',
            showCancel: false,
            confirmColor: '#22c55e'
          });
        } else if (flashDanger) {
          confirmAction({
            title: 'Error',
            text: flashDanger,
            icon: 'error',
            confirmText: 'Close',
            showCancel: false,
            confirmColor: '#ef4444'
          });
        }

        $(document).on('click', '.edit-user-btn', function(e) {
          e.preventDefault();
          var $btn = $(this);
          var username = $btn.data('username');
          var email = $btn.data('email');
          var position = $btn.data('position');
          var name = $btn.data('name') || username;

          confirmAction({
            title: 'Edit Account?',
            text: 'Open edit form for ' + name + '?',
            icon: 'question',
            confirmText: 'Edit',
            confirmColor: '#10b981',
            onConfirm: function() {
              var modal = $('#editUserModal');
              modal.find('#modalUsername').val(username);
              modal.find('#modalEmail').val(email);
              modal.find('#modalAcctLevel').val(position);
              modal.modal('show');
            }
          });
        });

        // Submit a POST form to the given URL, preserving any query params
        // as hidden inputs. Required because destructive endpoints now
        // reject GET (CSRF protection).
        function postTo(url, params) {
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = url.split('?')[0];
          // CSRF token
          var csrfName = $('meta[name="csrf-token-name"]').attr('content') || 'fbmso_csrf_token';
          var csrfHash = $('meta[name="csrf-token"]').attr('content') || '';
          if (csrfName && csrfHash) {
            var csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = csrfName; csrf.value = csrfHash;
            form.appendChild(csrf);
          }
          // Extract params from query string
          var query = url.split('?')[1] || '';
          if (query) {
            query.split('&').forEach(function(pair) {
              var kv = pair.split('=');
              if (kv.length === 2) {
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = decodeURIComponent(kv[0]);
                inp.value = decodeURIComponent(kv[1].replace(/\+/g, ' '));
                form.appendChild(inp);
              }
            });
          }
          // Extra params
          if (params) {
            Object.keys(params).forEach(function(key) {
              var inp = document.createElement('input');
              inp.type = 'hidden'; inp.name = key; inp.value = params[key];
              form.appendChild(inp);
            });
          }
          document.body.appendChild(form);
          form.submit();
        }

        $(document).on('click', '.reset-password-btn', function(e) {
          e.preventDefault();
          var $btn = $(this);
          var href = $btn.data('href');
          var name = $btn.data('name') || '';

          confirmAction({
            title: 'Reset Password?',
            text: 'This will reset the password for ' + name + '. Continue?',
            confirmText: 'Reset',
            confirmColor: '#f59e0b',
            onConfirm: function() {
              postTo(href);
            }
          });
        });

        $(document).on('click', '.delete-account-btn', function(e) {
          e.preventDefault();
          var $btn = $(this);
          var href = $btn.data('href');
          var name = $btn.data('name') || '';

          confirmAction({
            title: 'Delete Account?',
            text: 'This action cannot be undone. Delete account for ' + name + '?',
            icon: 'error',
            confirmText: 'Delete',
            confirmColor: '#ef4444',
            onConfirm: function() {
              postTo(href);
            }
          });
        });

        $(document).on('click', '.change-status-btn', function(e) {
          e.preventDefault();
          var $btn = $(this);
          var href = $btn.data('href');
          var action = ($btn.data('action') || '').toLowerCase();
          var name = $btn.data('name') || '';
          var isDeactivate = action === 'deactivate';

          confirmAction({
            title: (isDeactivate ? 'Deactivate' : 'Activate') + ' Account?',
            text: 'Are you sure you want to ' + action + ' the account of ' + name + '?',
            confirmText: isDeactivate ? 'Deactivate' : 'Activate',
            confirmColor: isDeactivate ? '#ef4444' : '#10b981',
            onConfirm: function() {
              postTo(href);
            }
          });
        });
      });
    })(jQuery);
  </script>

</body>

</html>
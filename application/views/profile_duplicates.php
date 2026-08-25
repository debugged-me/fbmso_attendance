<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>
    <?php
    $flashSuccess = $this->session->flashdata('success');
    $flashDanger  = $this->session->flashdata('danger');
    ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12">
              <div class="page-title-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <a href="<?= base_url('Page/profileList'); ?>" class="btn btn-primary btn-sm">
                      <i class="mdi mdi-arrow-left"></i> Back to Profile List
                    </a>
                    <a href="<?= base_url('Page/admin'); ?>" class="btn btn-secondary btn-sm">
                      <i class="mdi mdi-view-dashboard"></i> Dashboard
                    </a>
                  </div>
                </div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body table-responsive">
                  <h4 class="m-t-0 header-title mb-2">VIEW DUPLICATE STUDENTS </h4>
                  <p class="text-muted mb-3">
                    Comparison is by name only please compare the students <code>ID</code>.
                  </p>

                  <table id="duplicateTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student No.</th>
                        <th>Year / Section</th>
                        <th style="width:170px">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($data)): ?>
                        <?php $n = 1; ?>
                        <?php foreach ($data as $row): ?>
                          <?php
                          $ln = trim((string)($row->LastName ?? ''));
                          $fn = trim((string)($row->FirstName ?? ''));
                          $mn = trim((string)($row->MiddleName ?? ''));
                          $fullName = trim($ln . ($ln !== '' ? ', ' : '') . $fn . ($mn !== '' ? ' ' . $mn : ''));
                          if ($fullName === '') {
                            $fullName = '(No Name)';
                          }

                          $year = trim((string)($row->YearLevel ?? $row->yearLevel ?? ''));
                          $section = trim((string)($row->Section ?? $row->section ?? ''));
                          $yearSection = trim($year . ($section !== '' ? ' / ' . $section : ''));
                          if ($yearSection === '') {
                            $yearSection = 'N/A';
                          }
                          ?>
                          <tr>
                            <td><?= $n++; ?></td>
                            <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($yearSection, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                              <a href="<?= base_url('Page/updateStudeProfile') . '?id=' . rawurlencode((string)($row->StudentNumber ?? '')); ?>"
                                class="btn btn-info btn-xs">
                                <i class="mdi mdi-pencil"></i> Edit
                              </a>
                              <form method="post" action="<?= base_url('Page/deleteDuplicateStudent'); ?>" class="d-inline delete-dup-form">
                                <input type="hidden" name="student_number" value="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit"
                                  class="btn btn-danger btn-xs"
                                  data-ui-confirm="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?> is removed from the records. This cannot be undone."
                                  data-ui-confirm-title="Delete this duplicate?"
                                  data-ui-confirm-ok="Delete record">
                                  <i class="mdi mdi-delete"></i> Delete
                                </button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>

                  <?php if (empty($data)): ?>
                    <div class="alert alert-success mb-0">No duplicate student names found in <code>studeprofile</code>.</div>
                  <?php endif; ?>
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
  <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" />
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

  <script>
    $(function() {
      $('#duplicateTable').DataTable({
        pageLength: 25,
        order: [
          [1, 'asc'],
          [2, 'asc']
        ]
      });

      var successMessage = <?= json_encode($flashSuccess ?? null); ?>;
      var dangerMessage = <?= json_encode($flashDanger ?? null); ?>;
      if (dangerMessage && window.UI && typeof window.UI.fire === 'function') {
        window.UI.fire({
          icon: 'error',
          title: 'Error',
          text: dangerMessage,
          confirmButtonColor: '#348cd4'
        });
      } else if (successMessage && window.UI && typeof window.UI.fire === 'function') {
        window.UI.fire({
          icon: 'success',
          title: 'Success',
          text: successMessage,
          confirmButtonColor: '#348cd4'
        });
      }
    });
  </script>
</body>

</html>

<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<style>
  #duplicateTable thead th {
    background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
    padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
  }
  #duplicateTable tbody td {
    padding:14px 16px; vertical-align:middle; font-size:.88rem; color:#0d1b4b;
    border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
  }
  #duplicateTable tbody tr:hover { background:#f8faff !important; }
  #duplicateTable tbody tr:last-child td { border-bottom:none !important; }
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate {
    padding:14px 18px !important; margin:0 !important;
  }
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_length {
    padding:16px 18px 12px !important; margin:0 !important;
  }
  .dataTables_wrapper .dataTables_filter input {
    border-radius:10px !important; border:1px solid #e6ebf5 !important;
    padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important;
  }
  .dataTables_wrapper .dataTables_filter input:focus { border-color:#4266d4 !important; box-shadow:0 0 0 3px rgba(66,102,212,.12) !important; outline:none !important; }
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
  .pl-actions { display:flex; flex-wrap:wrap; gap:10px; margin:0; }
  .pl-actions > .up-btn,
  .pl-actions > a.up-btn,
  .pl-actions > button.up-btn { margin-right:10px; margin-bottom:6px; }
  @supports (gap:10px) { .pl-actions > .up-btn { margin-right:0; } }
  .dup-empty {
    text-align:center; padding:48px 20px; color:#6b7a99;
  }
  .dup-empty i { font-size:42px; display:block; margin-bottom:10px; color:#10b981; }

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

    .dataTables_wrapper { display:block !important; }
    .dataTables_scrollHead { display:none !important; }
    .dataTables_scrollBody {
      overflow:visible !important; height:auto !important; border:0 !important;
    }
    .table-responsive { overflow:visible !important; border:0 !important; }

    #duplicateTable thead { display:none; }
    #duplicateTable { width:100% !important; border:0 !important; }
    #duplicateTable tbody { display:block; }
    #duplicateTable tbody tr {
      display:block; margin:0 0 14px; padding:14px 16px;
      border:1px solid #e6ebf5 !important; border-radius:14px; background:#fff;
      box-shadow:0 6px 18px rgba(13,27,75,.06);
    }
    #duplicateTable tbody tr:last-child { margin-bottom:0; }
    #duplicateTable tbody tr:hover { background:#f8faff !important; }
    #duplicateTable tbody td {
      display:flex; align-items:flex-start; justify-content:space-between;
      gap:.6rem; width:100%; padding:7px 0 !important;
      border:0 !important; border-bottom:1px solid #f0f3f8 !important;
      font-size:.9rem; text-align:right; white-space:normal;
    }
    #duplicateTable tbody tr:last-child td:last-child { border-bottom:0 !important; }
    #duplicateTable tbody td::before {
      flex:0 0 42%; content:attr(data-label);
      color:#6b7a99; font-size:.72rem; font-weight:700;
      letter-spacing:.04em; text-transform:uppercase; text-align:left;
    }
    #duplicateTable tbody td:last-child {
      flex-direction:column; align-items:stretch; gap:6px; border-bottom:0 !important;
    }
    #duplicateTable tbody td:last-child::before { display:none; }
    #duplicateTable tbody td:last-child .btn,
    #duplicateTable tbody td:last-child a,
    #duplicateTable tbody td:last-child button {
      width:100%; text-align:center; font-size:.82rem; padding:8px 12px;
    }

    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
      float:none !important; text-align:left !important; padding:10px 0 !important;
    }
    .dataTables_wrapper .dataTables_filter input {
      width:100% !important; margin-left:0 !important;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
      float:none !important; text-align:center !important; padding:8px 0 !important;
    }
    .dataTables_paginate .paginate_button { min-width:34px; min-height:34px; }
  }
</style>

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

          <?php if ($flashSuccess): ?>
            <div class="up-flash up-flash-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <?php if ($flashDanger): ?>
            <div class="up-flash up-flash-danger"><?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <!-- Title + actions on one row -->
          <div class="pl-header">
            <div class="page-title-box">
              <h4 class="up-page-title">Duplicate Students</h4>
              <div class="up-page-sub">Students with matching names &mdash; compare their IDs to identify true duplicates.</div>
              <hr class="up-divider" />
            </div>

            <div class="pl-actions">
              <a href="<?= base_url('Page/profileList'); ?>" class="up-btn up-btn-ghost">
                <i class="mdi mdi-arrow-left"></i> Back to Profile List
              </a>
              <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                <i class="mdi mdi-view-dashboard"></i> Dashboard
              </a>
            </div>
          </div>

          <!-- Duplicates table card -->
          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-account-multiple-alert"></i> Duplicate Records</h4>
                  <span class="badge badge-light" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;color:#6b7a99;border:1px solid #e6ebf5;">
                    <?= number_format(count($data)); ?> found
                  </span>
                </div>
                <div class="up-card-body" style="padding:0 !important;">
                  <div class="table-responsive" style="padding:0;">
                    <table id="duplicateTable" class="table table-hover dt-responsive nowrap" style="width:100%;margin:0;">
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
                              <td data-label="#" style="color:#9aa5b8;font-weight:700;"><?= $n++; ?></td>
                              <td data-label="Student Name" style="font-weight:700;color:#0d1b4b;"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Student No." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:#2a4090;"><?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Year / Section" style="color:#6b7a99;"><?= htmlspecialchars($yearSection, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Action" class="text-center">
                                <a href="<?= base_url('Page/updateStudeProfile') . '?id=' . rawurlencode((string)($row->StudentNumber ?? '')); ?>"
                                  class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;">
                                  <i class="mdi mdi-pencil"></i> Edit
                                </a>
                                <form method="post" action="<?= base_url('Page/deleteDuplicateStudent'); ?>" class="d-inline delete-dup-form">
                                  <input type="hidden" name="student_number" value="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                  <input type="hidden" name="username" value="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                  <button type="submit"
                                    class="up-btn" style="padding:6px 12px;font-size:.78rem;min-height:auto;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;"
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
                      <div class="dup-empty">
                        <i class="mdi mdi-check-circle"></i>
                        No duplicate student names found.
                      </div>
                    <?php endif; ?>
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
    });
  </script>
</body>

</html>

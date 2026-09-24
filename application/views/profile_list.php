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

  /* Actions inside the card head */
  .up-card-head .pl-actions { flex:0 0 auto; }
  .up-card-head .pl-actions .up-btn { padding:7px 14px; font-size:.8rem; }

  /* ===== Mobile: table becomes cards ===== */
  @media (max-width: 767.98px) {
    .up-card-head .pl-actions { width:100%; }
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

          <?php
          $bulk = $this->session->flashdata('bulk_import');
          if (is_array($bulk) && !empty($bulk['rows'])):
            $bulkMeta = [
              'created' => ['label' => 'Created', 'bg' => '#dcfce7', 'fg' => '#15803d', 'accent' => '#22c55e', 'icon' => 'mdi-check-circle'],
              'skipped' => ['label' => 'Skipped', 'bg' => '#fef9c3', 'fg' => '#a16207', 'accent' => '#eab308', 'icon' => 'mdi-skip-next-circle'],
              'error'   => ['label' => 'Failed',  'bg' => '#fee2e2', 'fg' => '#b91c1c', 'accent' => '#ef4444', 'icon' => 'mdi-alert-circle'],
            ];
          ?>
            <style>
              .bulk-filter {
                border:1px solid #e6ebf5; background:#f8faff; color:#4a5a7a;
                border-radius:999px; padding:5px 12px; font-size:.76rem; font-weight:700;
                cursor:pointer; display:inline-flex; align-items:center; gap:6px;
              }
              .bulk-filter span { opacity:.65; font-weight:800; }
              .bulk-filter.is-active { border-color:transparent; color:#fff; }
              .bulk-filter.is-active[data-status="all"]     { background:#2a4090; }
              .bulk-filter.is-active[data-status="created"] { background:#16a34a; }
              .bulk-filter.is-active[data-status="skipped"] { background:#ca8a04; }
              .bulk-filter.is-active[data-status="error"]   { background:#dc2626; }
              .bulk-filter.is-active span { opacity:.85; }
              .bulk-search {
                border:1px solid #e6ebf5; border-radius:999px; background:#f8faff;
                padding:5px 12px 5px 30px; font-size:.78rem; width:170px; color:#0d1b4b;
                outline:none;
              }
              .bulk-search:focus { border-color:#c7d2fe; background:#fff; box-shadow:0 0 0 3px rgba(59,95,212,.12); }
              .bulk-results-body { max-height:420px; overflow:auto; }
              .bulk-results-table { width:100%; border-collapse:collapse; font-size:.86rem; }
              .bulk-results-table thead th {
                position:sticky; top:0; z-index:2; background:#f1f5fd; color:#4a5a7a;
                font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; font-weight:800;
                padding:10px 14px; text-align:left; border-bottom:1px solid #e6ebf5;
              }
              .bulk-results-table tbody td { padding:9px 14px; border-bottom:1px solid #f1f4fb; vertical-align:top; }
              .bulk-results-table tbody tr:nth-child(even) { background:#fafbff; }
              .bulk-results-table tbody tr:hover { background:#f1f5fd; }
              .bulk-results-table tr[data-status="created"] td:first-child { box-shadow:inset 4px 0 0 #22c55e; }
              .bulk-results-table tr[data-status="skipped"] td:first-child { box-shadow:inset 4px 0 0 #eab308; }
              .bulk-results-table tr[data-status="error"]   td:first-child { box-shadow:inset 4px 0 0 #ef4444; }
              .bulk-row-num { color:#8a97b8; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.8rem; }
              .bulk-studno { font-family:ui-monospace,Menlo,Consolas,monospace; font-weight:700; color:#0d1b4b; font-size:.86rem; }
              .bulk-name { color:#6b7a99; font-size:.78rem; margin-top:1px; }
              .bulk-badge {
                display:inline-flex; align-items:center; gap:5px; border-radius:999px;
                padding:4px 11px; font-size:.72rem; font-weight:700; white-space:nowrap;
              }
              .bulk-msg { color:#4a5a7a; font-size:.83rem; line-height:1.5; }
              .bulk-empty-row td { padding:26px 14px !important; text-align:center; color:#8a97b8; font-size:.85rem; }
            </style>
            <div class="up-card" style="margin-bottom:18px;" id="bulkResultsCard">
              <div class="up-card-head">
                <h4><i class="mdi mdi-cloud-upload-outline"></i> Bulk Upload Results</h4>
                <div class="pl-actions" style="gap:6px;align-items:center;">
                  <span style="position:relative;display:inline-flex;align-items:center;">
                    <i class="mdi mdi-magnify" style="position:absolute;left:10px;color:#8a97b8;font-size:.95rem;"></i>
                    <input type="text" class="bulk-search" id="bulkSearch" placeholder="Filter student…" autocomplete="off">
                  </span>
                  <button type="button" class="bulk-filter is-active" data-status="all">All <span><?= count($bulk['rows']); ?></span></button>
                  <button type="button" class="bulk-filter" data-status="created">Created <span><?= (int)$bulk['created']; ?></span></button>
                  <button type="button" class="bulk-filter" data-status="skipped">Skipped <span><?= (int)$bulk['skipped']; ?></span></button>
                  <button type="button" class="bulk-filter" data-status="error">Failed <span><?= (int)$bulk['failed']; ?></span></button>
                </div>
              </div>
              <div class="bulk-results-body">
                <table class="bulk-results-table">
                  <thead>
                    <tr>
                      <th style="width:64px;">Row</th>
                      <th style="width:220px;">Student</th>
                      <th style="width:110px;">Status</th>
                      <th>Details</th>
                    </tr>
                  </thead>
                  <tbody id="bulkResultsBody">
                    <?php foreach ($bulk['rows'] as $br):
                      $bm = $bulkMeta[$br['status']] ?? $bulkMeta['error'];
                      $bStatus = in_array($br['status'], ['created','skipped','error'], true) ? $br['status'] : 'error';
                    ?>
                      <tr data-status="<?= $bStatus; ?>">
                        <td class="bulk-row-num"><?= (int)$br['row']; ?></td>
                        <td>
                          <div class="bulk-studno"><?= htmlspecialchars((string)$br['id'], ENT_QUOTES, 'UTF-8'); ?></div>
                          <?php if (!empty($br['name'])): ?>
                            <div class="bulk-name"><?= htmlspecialchars((string)$br['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="bulk-badge" style="background:<?= $bm['bg']; ?>;color:<?= $bm['fg']; ?>;">
                            <i class="mdi <?= $bm['icon']; ?>"></i> <?= $bm['label']; ?>
                          </span>
                        </td>
                        <td class="bulk-msg"><?= htmlspecialchars((string)$br['message'], ENT_QUOTES, 'UTF-8'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <tr class="bulk-empty-row" style="display:none;"><td colspan="4">No rows in this group.</td></tr>
                  </tbody>
                </table>
              </div>
              <?php if (!empty($bulk['truncated'])): ?>
                <div style="padding:10px 18px;font-size:.8rem;color:#6b7a99;border-top:1px solid #f1f4fb;">
                  <i class="mdi mdi-information-outline"></i> …and <?= (int)$bulk['truncated']; ?> more row(s) not shown.
                </div>
              <?php endif; ?>
            </div>
            <script>
              (function () {
                var card = document.getElementById('bulkResultsCard');
                if (!card) return;
                var chips = card.querySelectorAll('.bulk-filter');
                var search = document.getElementById('bulkSearch');
                var rows = card.querySelectorAll('#bulkResultsBody tr:not(.bulk-empty-row)');
                var emptyRow = card.querySelector('.bulk-empty-row');
                var status = 'all';
                function apply() {
                  var q = (search.value || '').toLowerCase();
                  var visible = 0;
                  rows.forEach(function (tr) {
                    var okStatus = status === 'all' || tr.getAttribute('data-status') === status;
                    var okText = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
                    tr.style.display = (okStatus && okText) ? '' : 'none';
                    if (okStatus && okText) visible++;
                  });
                  emptyRow.style.display = visible === 0 ? '' : 'none';
                }
                chips.forEach(function (chip) {
                  chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.classList.remove('is-active'); });
                    chip.classList.add('is-active');
                    status = chip.getAttribute('data-status');
                    apply();
                  });
                });
                if (search) search.addEventListener('input', apply);
              })();
            </script>
          <?php endif; ?>

          <!-- Title source for the top navbar (visually hidden; read by mobile-shell.js) -->
          <div class="page-title-box">
            <h4 class="up-page-title">Registered Students</h4>
          </div>

          <?php
          $nxYearCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
          foreach ((array)($data ?? []) as $row) {
              if (preg_match('/(\d)/', (string)($row->yearLevel ?? ''), $m) && isset($nxYearCounts[(int)$m[1]])) {
                  $nxYearCounts[(int)$m[1]]++;
              }
          }
          $nxYearMeta = [
              1 => ['label' => '1st Year', 'cls' => 'blue',   'icon' => 'mdi-numeric-1-circle-outline'],
              2 => ['label' => '2nd Year', 'cls' => 'cyan',   'icon' => 'mdi-numeric-2-circle-outline'],
              3 => ['label' => '3rd Year', 'cls' => 'violet', 'icon' => 'mdi-numeric-3-circle-outline'],
              4 => ['label' => '4th Year', 'cls' => 'orange', 'icon' => 'mdi-numeric-4-circle-outline'],
          ];
          ?>

          <!-- Stat tiles — same palette as the dashboard; clicking filters the table -->
          <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
            <?php foreach ($nxYearMeta as $yr => $meta): ?>
              <a class="nx-stat <?= $meta['cls']; ?>" href="javascript:void(0)" data-yearfilter="<?= ['1st','2nd','3rd','4th'][$yr - 1]; ?>">
                <div class="nx-stat-main">
                  <div>
                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($nxYearCounts[$yr]); ?></span></div>
                    <div class="nx-stat-label"><?= $meta['label']; ?></div>
                  </div>
                  <div class="nx-stat-icon"><i class="mdi <?= $meta['icon']; ?>"></i></div>
                </div>
                <div class="nx-stat-foot">Filter table <i class="mdi mdi-arrow-right"></i></div>
              </a>
            <?php endforeach; ?>
          </div>

          <!-- Students table card -->
          <div class="row">
            <div class="col-md-12">
              <div class="up-card">
                <div class="up-card-head">
                  <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                    <h4><i class="mdi mdi-account-group"></i> Student List</h4>
                    <span class="badge badge-light" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;color:#6b7a99;border:1px solid #e6ebf5;">
                      <?= number_format(count($data)); ?> records
                    </span>
                  </div>
                  <div class="pl-actions">
                    <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                      <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="<?= site_url('Registration/index') . '?source=admin'; ?>" class="up-btn up-btn-primary">
                      <i class="mdi mdi-account-plus"></i> Add Student
                    </a>
                    <div class="dropdown">
                      <button type="button" class="up-btn up-btn-ghost dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background:#eef2ff;color:#3730a3;border-color:#c7d2fe;">
                        <i class="mdi mdi-dots-horizontal"></i> Tools
                      </button>
                      <div class="dropdown-menu dropdown-menu-right" style="min-width:230px;border:none;border-radius:14px;box-shadow:0 14px 40px rgba(13,27,75,.2);padding:8px;">
                        <a class="dropdown-item" href="<?= site_url('StudentImport/template'); ?>" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-file-excel" style="color:#15803d;font-size:1.05rem;margin-right:8px;"></i> Download Template
                        </a>
                        <a class="dropdown-item" href="<?= site_url('StudentImport/export'); ?>" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-export" style="color:#0369a1;font-size:1.05rem;margin-right:8px;"></i> Export to Excel
                        </a>
                        <a class="dropdown-item" href="javascript:void(0)" data-toggle="modal" data-target="#bulkUploadModal" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-cloud-upload-outline" style="color:#3730a3;font-size:1.05rem;margin-right:8px;"></i> Bulk Upload
                        </a>
                        <div class="dropdown-divider" style="margin:6px 4px;"></div>
                        <a class="dropdown-item" href="<?= base_url('Page/duplicateStudentsByName'); ?>" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-account-multiple" style="color:#92400e;font-size:1.05rem;margin-right:8px;"></i> Duplicate Students
                        </a>
                        <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-printer" style="color:#4a5a7a;font-size:1.05rem;margin-right:8px;"></i> Print
                        </a>
                      </div>
                    </div>
                  </div>
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
                      <?php
                      $allowedRoles = ['head registrar', 'registrar', 'assistant registrar', 'admin', 'administrator'];
                      $canDelete = in_array(
                          strtolower(trim((string)($this->session->userdata('level') ?? ''))),
                          $allowedRoles,
                          true
                      );

                      // Rows are handed to DataTables as JSON and rendered lazily
                      // (deferRender) — writing ~3k <tr>s into the page made the
                      // browser build ~20k DOM nodes before init and was the lag.
                      $dtRows = [];
                      foreach ((array)$data as $row) {
                          $ln = trim($row->LastName ?? '');
                          $fn = trim($row->FirstName ?? '');
                          $mn = trim($row->MiddleName ?? '');
                          $fullname = trim(($ln ? $ln : '') . (($ln || $fn) ? ', ' : '') . ($fn ? $fn : '') . ($mn ? ' ' . $mn : ''));
                          if ($fullname === '' && !empty($row->StudentNumber)) $fullname = $row->StudentNumber;
                          $dtRows[] = [
                              'name'   => $fullname,
                              'sub'    => trim(($row->yearLevel ?? '') . ' ' . ($row->section ?? '')),
                              'studno' => (string)($row->StudentNumber ?? ''),
                              'email'  => trim((string)($row->email ?? '')),
                              'bdate'  => !empty($row->birthDate) ? $row->birthDate : 'N/A',
                          ];
                      }
                      ?>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div style="height:40px;"></div>

          <!-- Bulk Upload modal -->
          <div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(13,27,75,.25);">
                <form method="post" action="<?= site_url('StudentImport/upload'); ?>" enctype="multipart/form-data">
                  <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090,#3b5fd4);color:#fff;border:none;">
                    <h5 class="modal-title" id="bulkUploadModalLabel" style="color:#fff;font-weight:700;">
                      <i class="mdi mdi-cloud-upload-outline"></i> Bulk Upload Students
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9;">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body" style="padding:24px;">
                    <ol style="padding-left:18px;margin:0 0 16px;font-size:.88rem;color:#4a5a7a;line-height:1.8;">
                      <li>Download the
                        <a href="<?= site_url('StudentImport/template'); ?>" style="font-weight:700;color:#2a4090;">Excel template <i class="mdi mdi-file-excel"></i></a>
                        and fill in one student per row starting at row 2.
                      </li>
                      <li>Save the file — keep it as <strong>.xlsx</strong>, or use Save As → <strong>.csv</strong>.</li>
                      <li>Upload it below. Each valid row creates the student account, enrolls the student in the current term, and emails their login credentials.</li>
                    </ol>
                    <div class="form-group" style="margin-bottom:10px;">
                      <label style="font-size:.8rem;font-weight:700;color:#0d1b4b;">Template file (.xlsx or .csv)</label>
                      <input type="file" name="file" accept=".xlsx,.csv" required
                        style="display:block;width:100%;padding:10px;border:1px dashed #c7d2fe;border-radius:10px;background:#f8faff;font-size:.86rem;">
                    </div>
                    <div style="font-size:.76rem;color:#6b7a99;">
                      <i class="mdi mdi-information-outline"></i>
                      Rows with an existing Student Number or Email are skipped. Validation errors are listed per row after the upload.
                    </div>
                  </div>
                  <div class="modal-footer" style="border:none;padding:16px 24px 22px;">
                    <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="up-btn up-btn-primary">
                      <i class="mdi mdi-cloud-upload-outline"></i> Upload &amp; Import
                    </button>
                  </div>
                </form>
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
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" />
  <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" />
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script>
    var PL_DATA = <?= json_encode($dtRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var PL_CAN_DELETE = <?= $canDelete ? 'true' : 'false'; ?>;
    var PL_URL_VIEW = <?= json_encode(site_url('Page/editSignup')); ?>;
    var PL_URL_RESET = <?= json_encode(base_url('Page/resetPass')); ?>;
    var PL_URL_DELETE = <?= json_encode(base_url('Page/deleteSignup')); ?>;

    function plEsc(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    $(function() {
      var csrfNameEl = document.querySelector('meta[name="csrf-token-name"]');
      var csrfHashEl = document.querySelector('meta[name="csrf-token"]');
      var csrfField = (csrfNameEl && csrfHashEl)
        ? '<input type="hidden" name="' + plEsc(csrfNameEl.content) + '" value="' + plEsc(csrfHashEl.content) + '">'
        : '';

      $('#datatable').DataTable({
        data: PL_DATA,
        deferRender: true,
        columns: [
          {
            data: 'name',
            render: function(d, type, row) {
              if (type === 'sort' || type === 'type') return d;
              var html = '<div style="font-weight:700;color:#0d1b4b;">' + plEsc(d) + '</div>';
              if (row.sub) {
                html += '<div style="font-size:.76rem;color:#6b7a99;margin-top:2px;">' + plEsc(row.sub) + '</div>';
              }
              return html;
            },
            createdCell: function(td) { td.setAttribute('data-label', 'Student Name'); }
          },
          {
            data: 'studno',
            createdCell: function(td) {
              td.setAttribute('data-label', 'Student No.');
              td.style.fontFamily = 'ui-monospace,Menlo,Consolas,monospace';
              td.style.fontWeight = '700';
              td.style.color = '#2a4090';
            }
          },
          {
            data: 'email',
            render: function(d, type) {
              if (type !== 'display') return d;
              return d ? plEsc(d) : '<span style="color:#9aa5b8;">N/A</span>';
            },
            createdCell: function(td) { td.setAttribute('data-label', 'Email'); }
          },
          {
            data: 'bdate',
            createdCell: function(td) {
              td.setAttribute('data-label', 'Birth Date');
              td.style.color = '#6b7a99';
            }
          },
          {
            data: null,
            orderable: false,
            searchable: false,
            className: 'text-center',
            render: function(d, type, row) {
              if (type !== 'display') return '';
              var studno = plEsc(row.studno);
              var html = '<a href="' + PL_URL_VIEW + '?id=' + encodeURIComponent(row.studno) + '" class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;">'
                + '<i class="mdi mdi-eye-outline"></i> View</a>';
              if (!PL_CAN_DELETE) {
                return html + ' <span style="color:#9aa5b8;">&mdash;</span>';
              }
              var resetHref = PL_URL_RESET + '?u=' + encodeURIComponent(row.studno) + '&return_to=profileList';
              html += ' <a href="' + plEsc(resetHref) + '" class="up-btn up-btn-ghost reset-pass-btn"'
                + ' style="padding:6px 12px;font-size:.78rem;min-height:auto;background:#fef3c7;color:#92400e;border-color:#fcd34d;"'
                + ' data-href="' + plEsc(resetHref) + '" data-studno="' + studno + '">'
                + '<i class="mdi mdi-lock-reset"></i> Reset</a>'
                + ' <form method="post" action="' + PL_URL_DELETE + '" style="display:inline" class="delete-signup-form">'
                + csrfField
                + '<input type="hidden" name="id" value="' + studno + '">'
                + '<button type="button" class="up-btn delete-signup-btn" style="padding:6px 12px;font-size:.78rem;min-height:auto;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;" data-studno="' + studno + '">'
                + '<i class="mdi mdi-delete-forever"></i> Delete</button></form>';
              return html;
            },
            createdCell: function(td) { td.setAttribute('data-label', 'Action'); }
          }
        ]
      });
    });
    $(document).on('click', '.nx-stat[data-yearfilter]', function(e) {
      e.preventDefault();
      try {
        $('#datatable').DataTable().search(String($(this).data('yearfilter') || '')).draw();
      } catch (err) {}
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
            // Submit as POST form (CSRF-protected) instead of GET redirect
            var url = href.split('?');
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url[0];
            var csrfName = document.querySelector('meta[name="csrf-token-name"]');
            var csrfHash = document.querySelector('meta[name="csrf-token"]');
            if (csrfName && csrfHash) {
              var ci = document.createElement('input');
              ci.type = 'hidden'; ci.name = csrfName.content; ci.value = csrfHash.content;
              form.appendChild(ci);
            }
            if (url[1]) {
              url[1].split('&').forEach(function(p) {
                var kv = p.split('=');
                if (kv.length === 2) {
                  var inp = document.createElement('input');
                  inp.type = 'hidden'; inp.name = decodeURIComponent(kv[0]);
                  inp.value = decodeURIComponent(kv[1].replace(/\+/g, ' '));
                  form.appendChild(inp);
                }
              });
            }
            document.body.appendChild(form);
            form.submit();
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
    <div class="ph-letterhead">
      <img src="<?= base_url('assets/images/srms-logo-1.png') ?>" alt="School Logo">
    </div>
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
      #printHeader .ph-letterhead {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 22mm;
        padding: 3mm 8mm;
        margin-bottom: 8px;
        background: #1a2942;
        border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      #printHeader .ph-letterhead img {
        display: block;
        width: 80mm;
        height: auto;
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

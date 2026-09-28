<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<?php $isAuditor = ((string)$this->session->userdata('level') === 'Auditor'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">
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

  /* Per-row actions: one kebab menu, same look as Admin Accounts */
  .row-actions .dropdown-toggle {
    width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center; gap:6px;
    border:1px solid #e6ebf5; border-radius:10px; background:#fff; color:#6b7a99;
    padding:0; box-shadow:none; font-size:18px;
  }
  .row-actions .dropdown-toggle::after { display:none; }
  .row-actions .dropdown-toggle:hover,
  .row-actions .dropdown-toggle:focus,
  .row-actions.show .dropdown-toggle { color:#4266d4; border-color:#4266d4; box-shadow:0 3px 10px rgba(66,102,212,.12); outline:none; }
  .row-actions .dropdown-toggle .ra-label { display:none; font-size:.82rem; font-weight:700; }
  .row-actions .dropdown-menu {
    min-width:210px; padding:6px; border:1px solid #e6ebf5; border-radius:12px;
    box-shadow:0 12px 36px rgba(15,23,42,.14); z-index:1060;
  }
  .row-actions .dropdown-item {
    display:flex; align-items:center; gap:9px; width:100%; padding:9px 12px; border-radius:8px;
    font-size:.84rem; font-weight:600; margin:0; color:#0d1b4b; background:none; border:0; text-align:left;
  }
  .row-actions .dropdown-item:hover,
  .row-actions .dropdown-item:focus { background:#f4f7ff; }
  .row-actions .dropdown-item i { width:19px; text-align:center; font-size:17px; }
  .row-actions .dropdown-item.ra-warn i    { color:#b45309; }
  .row-actions .dropdown-item.ra-success   { color:#15803d; }
  .row-actions .dropdown-item.ra-danger    { color:#dc2626; }
  .row-actions .dropdown-item.ra-danger:hover { background:#fef2f2; }
  .row-actions .dropdown-divider { margin:5px 4px; }
  .row-actions form { margin:0; }

  /* Account status badge */
  .acct-badge {
    display:inline-flex; align-items:center; gap:5px; border-radius:999px;
    padding:4px 11px; font-size:.72rem; font-weight:700; white-space:nowrap;
  }
  .acct-badge::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; }
  .acct-active   { background:#dcfce7; color:#15803d; }
  .acct-inactive { background:#fee2e2; color:#b91c1c; }
  .acct-pending  { background:#fef3c7; color:#a16207; }
  .acct-none     { background:#f1f5f9; color:#64748b; }

  /* Status filter chips */
  .st-filters {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    padding:14px 18px 0;
  }
  .st-filters-label { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#8a97b8; margin-right:2px; }
  .st-chip {
    border:1px solid #e6ebf5; background:#f8faff; color:#4a5a7a;
    border-radius:999px; padding:6px 13px; font-size:.8rem; font-weight:700;
    cursor:pointer; display:inline-flex; align-items:center; gap:7px; line-height:1.2;
  }
  .st-chip span { opacity:.65; font-weight:800; }
  .st-chip:hover { border-color:#c7d2fe; background:#fff; }
  .st-chip:focus-visible { outline:none; box-shadow:0 0 0 3px rgba(66,102,212,.2); }
  .st-chip.is-active { border-color:transparent; color:#fff; }
  .st-chip.is-active span { opacity:.85; }
  .st-chip.is-active[data-status="all"]      { background:#2a4090; }
  .st-chip.is-active[data-status="active"]   { background:#16a34a; }
  .st-chip.is-active[data-status="inactive"] { background:#dc2626; }
  .st-chip.is-active[data-status="pending"]  { background:#ca8a04; }
  .st-chip.is-active[data-status="none"]     { background:#64748b; }
  @media (max-width: 767.98px) {
    /* One swipeable row instead of wrapping into a tall block */
    .st-filters { flex-wrap:nowrap; overflow-x:auto; padding:12px 14px 4px; scroll-padding:0 14px; -webkit-overflow-scrolling:touch; scrollbar-width:none; }
    .st-filters::-webkit-scrollbar { display:none; }
    .st-filters-label { display:none; }
    .st-chip { flex:0 0 auto; white-space:nowrap; }
  }
  @media print { .st-filters, .bulk-bar, .sel-col, .sel-cell { display:none !important; } }

  /* Bulk-delete selection */
  #datatable th.sel-col, #datatable td.sel-cell { width:36px; padding-right:0 !important; text-align:center; }
  .pl-sel { width:17px; height:17px; cursor:pointer; accent-color:#dc2626; vertical-align:middle; }
  .bulk-bar {
    position:fixed; left:50%; bottom:20px; transform:translateX(-50%); z-index:1050;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap; justify-content:center;
    background:#0d1b4b; color:#fff; border-radius:14px; padding:10px 12px 10px 18px;
    box-shadow:0 14px 40px rgba(13,27,75,.35); font-size:.86rem; max-width:calc(100vw - 32px);
  }
  .bulk-bar[hidden] { display:none; }
  .bulk-bar-count b { font-size:1rem; }
  .bulk-bar-link { background:none; border:0; color:#c7d2fe; font-weight:700; padding:4px 2px; cursor:pointer; font-size:.84rem; }
  .bulk-bar-link:hover { color:#fff; text-decoration:underline; }
  .bulk-bar-link[hidden] { display:none; }
  .bulk-bar-delete {
    background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; border:0; border-radius:10px;
    padding:8px 14px; font-weight:700; font-size:.84rem; cursor:pointer; display:inline-flex; align-items:center; gap:6px;
  }
  @media (max-width: 767.98px) {
    /* Sit above the mobile tab bar */
    .bulk-bar { bottom:calc(76px + env(safe-area-inset-bottom, 0px)); width:calc(100vw - 32px); gap:10px; }
    #datatable tbody td.sel-cell:empty { display:none; }
    #datatable tbody td.sel-cell { width:100% !important; justify-content:space-between; align-items:center; padding-right:0 !important; }
    #datatable tbody td.sel-cell .pl-sel { width:20px; height:20px; }
  }

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
    /* On a card the kebab becomes a full-width "Actions" button */
    #datatable tbody td:last-child .row-actions .dropdown-toggle {
      width:100%; height:auto; padding:9px 12px; font-size:1rem;
    }
    #datatable tbody td:last-child .row-actions .dropdown-toggle .ra-label { display:inline; }

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
                    <a href="<?= base_url($isAuditor ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                      <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
					<?php if (!$isAuditor): ?>
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
                        <a class="dropdown-item" href="<?= site_url('Page/profileListPrint'); ?>" id="plPrintReport" target="_blank" rel="noopener" style="border-radius:9px;padding:9px 12px;font-size:.86rem;font-weight:600;color:#0d1b4b;">
                          <i class="mdi mdi-printer" style="color:#4a5a7a;font-size:1.05rem;margin-right:8px;"></i> Print
                        </a>
                      </div>
                    </div>
					<?php endif; ?>
                  </div>
                </div>
                <?php
                // Reset password, activate/deactivate and bulk delete are guarded
                // to these roles (config/authguard.php), so only they see them.
                $canManage = in_array(
                    strtolower(trim((string)($this->session->userdata('level') ?? ''))),
                    ['super admin', 'admin', 'it'],
                    true
                );
                // Status chips: one click narrows the table to that account status.
                $stCounts = ['all' => 0, 'active' => 0, 'inactive' => 0, 'pending' => 0, 'none' => 0];
                foreach ((array)$data as $row) {
                    $stCounts['all']++;
                    $st = isset($row->acctStat) ? strtolower(trim((string)$row->acctStat)) : null;
                    if ($st === null)                      $stCounts['none']++;
                    elseif ($st === 'active')              $stCounts['active']++;
                    elseif ($st === 'inactive')            $stCounts['inactive']++;
                    elseif ($st === 'pending verification') $stCounts['pending']++;
                }
                $stChips = [
                    'all'      => 'All',
                    'active'   => 'Active',
                    'inactive' => 'Inactive',
                    'pending'  => 'Pending',
                    'none'     => 'No account',
                ];
                ?>
                <div class="st-filters" role="group" aria-label="Filter by account status">
                  <span class="st-filters-label"><i class="mdi mdi-filter-variant"></i> Status</span>
                  <?php foreach ($stChips as $key => $label): ?>
                    <?php if ($key !== 'all' && $key !== 'inactive' && $stCounts[$key] === 0) continue; // Inactive always shows, even at 0 ?>
                    <button type="button" class="st-chip<?= $key === 'all' ? ' is-active' : ''; ?>" data-status="<?= $key; ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false'; ?>">
                      <?= $label; ?> <span><?= number_format($stCounts[$key]); ?></span>
                    </button>
                  <?php endforeach; ?>
                </div>
                <div class="up-card-body" style="padding:0 !important;">
                  <div class="table-responsive" style="padding:0;">
                    <table id="datatable" class="table table-hover dt-responsive nowrap" style="width:100%;margin:0;">
                      <thead>
                        <tr>
                          <?php if ($canManage): ?>
                            <th class="sel-col"><input type="checkbox" id="plSelectAll" class="pl-sel" aria-label="Select all deletable students shown"></th>
                          <?php endif; ?>
                          <th>Student Name</th>
                          <th>Student No.</th>
                          <th>Email</th>
                          <th style="width:110px">Birth Date</th>
                          <th style="width:110px">Status</th>
                          <th style="text-align:center;width:70px">Action</th>
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
                              // null = no login account exists for this signup yet
                              'status' => isset($row->acctStat) ? strtolower(trim((string)$row->acctStat)) : null,
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

          <?php if ($canManage): ?>
            <!-- Bulk delete bar: appears once at least one row is ticked -->
            <div class="bulk-bar" id="plBulkBar" hidden>
              <span class="bulk-bar-count"><b id="plSelCount">0</b> selected</span>
              <button type="button" class="bulk-bar-link" id="plSelAllShown"></button>
              <button type="button" class="bulk-bar-link" id="plSelClear">Clear</button>
              <button type="button" class="bulk-bar-delete" id="plBulkDelete">
                <i class="mdi mdi-delete-forever"></i> Delete permanently
              </button>
            </div>
          <?php endif; ?>

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
    var PL_CAN_MANAGE = <?= $canManage ? 'true' : 'false'; ?>;
    var PL_URL_VIEW = <?= json_encode(site_url('Page/editSignup')); ?>;
    var PL_URL_PREVIEW = <?= json_encode(site_url('Page/signupPreview')); ?>;
    var PL_CAN_EDIT = <?= (string)$this->session->userdata('level') === 'Admin' ? 'true' : 'false'; ?>;
    var PL_URL_RESET = <?= json_encode(base_url('Page/resetPass')); ?>;
    var PL_URL_DELETE = <?= json_encode(base_url('Page/deleteSignup')); ?>;
    var PL_URL_STATUS = <?= json_encode(base_url('Page/setStudentStatus')); ?>;
    var PL_URL_BULK_DELETE = <?= json_encode(base_url('Page/bulkDeleteStudents')); ?>;
    var PL_SELECTED = new Set();   // studnos ticked for bulk delete, across pages

    // Only students who never got going can be bulk-deleted. The server
    // re-checks this; here it just decides which rows get a checkbox.
    function plDeletable(row) {
      return row.status === null || row.status === 'pending verification';
    }

    // acctStat -> badge. null means the signup has no login account yet.
    function plStatusMeta(s) {
      if (s === null || s === undefined) return { cls: 'acct-none', label: 'No account' };
      if (s === 'active') return { cls: 'acct-active', label: 'Active' };
      if (s === 'inactive') return { cls: 'acct-inactive', label: 'Inactive' };
      if (s === 'pending verification') return { cls: 'acct-pending', label: 'Pending' };
      return { cls: 'acct-none', label: s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown' };
    }

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
        order: [[PL_CAN_MANAGE ? 1 : 0, 'asc']],
        createdRow: function(tr, row) { tr.setAttribute('data-studno', row.studno); },
        columns: (PL_CAN_MANAGE ? [{
          data: null,
          orderable: false,
          searchable: false,
          className: 'sel-cell',
          render: function(d, type, row) {
            if (type !== 'display' || !plDeletable(row)) return '';
            return '<input type="checkbox" class="pl-sel pl-row-sel" value="' + plEsc(row.studno) + '"'
              + (PL_SELECTED.has(row.studno) ? ' checked' : '')
              + ' aria-label="Select ' + plEsc(row.studno) + ' for deletion">';
          },
          createdCell: function(td) { td.setAttribute('data-label', 'Select'); }
        }] : []).concat([
          {
            data: 'name',
            render: function(d, type, row) {
              if (type === 'sort' || type === 'type') return d;
              var html = '<a class="pv-open pl-name-link" href="' + PL_URL_VIEW + '?id=' + encodeURIComponent(row.studno) + '"'
                + ' data-studno="' + plEsc(row.studno) + '">' + plEsc(d) + '</a>';
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
            data: 'status',
            name: 'status',
            render: function(d, type) {
              var meta = plStatusMeta(d);
              // Filter/sort on the label so typing "inactive" in search finds them.
              if (type !== 'display') return meta.label;
              return '<span class="acct-badge ' + meta.cls + '">' + plEsc(meta.label) + '</span>';
            },
            createdCell: function(td) { td.setAttribute('data-label', 'Status'); }
          },
          {
            data: null,
            orderable: false,
            searchable: false,
            className: 'text-center',
            render: function(d, type, row) {
              if (type !== 'display') return '';
              var studno = plEsc(row.studno);
              var items = '<a class="dropdown-item pv-open" href="' + PL_URL_VIEW + '?id=' + encodeURIComponent(row.studno) + '" data-studno="' + studno + '">'
                + '<i class="mdi mdi-eye-outline"></i> View Profile</a>';

              // Reset and status need a login account to act on.
              if (PL_CAN_MANAGE && row.status !== null) {
                var resetHref = PL_URL_RESET + '?u=' + encodeURIComponent(row.studno) + '&return_to=profileList';
                items += '<a class="dropdown-item ra-warn reset-pass-btn" href="' + plEsc(resetHref) + '"'
                  + ' data-href="' + plEsc(resetHref) + '" data-studno="' + studno + '">'
                  + '<i class="mdi mdi-lock-reset"></i> Reset Password</a>';

                if (row.status === 'inactive') {
                  items += '<a class="dropdown-item ra-success status-toggle-btn" href="#" data-studno="' + studno + '" data-action="Activate">'
                    + '<i class="mdi mdi-account-check-outline"></i> Activate Account</a>';
                } else {
                  items += '<a class="dropdown-item ra-danger status-toggle-btn" href="#" data-studno="' + studno + '" data-action="Deactivate">'
                    + '<i class="mdi mdi-account-off-outline"></i> Set Inactive</a>';
                }
              }

              if (PL_CAN_DELETE) {
                items += '<div class="dropdown-divider"></div>'
                  + '<form method="post" action="' + PL_URL_DELETE + '" class="delete-signup-form">'
                  + csrfField
                  + '<input type="hidden" name="id" value="' + studno + '">'
                  + '<button type="button" class="dropdown-item ra-danger delete-signup-btn" data-studno="' + studno + '">'
                  + '<i class="mdi mdi-delete-forever"></i> Delete</button></form>';
              }

              return '<div class="dropdown row-actions">'
                + '<button type="button" class="dropdown-toggle ra-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Actions for ' + studno + '">'
                + '<i class="mdi mdi-dots-vertical"></i><span class="ra-label">Actions</span></button>'
                + '<div class="dropdown-menu dropdown-menu-right">' + items + '</div></div>';
            },
            createdCell: function(td) { td.setAttribute('data-label', 'Action'); }
          }
        ])
      });
    });
    // Row menus open and close here rather than through Bootstrap's dropdown
    // plugin. They are pinned to the viewport so .table-responsive's overflow
    // can't clip the last rows, and the theme's bootstrap.css forces
    // `.dropdown-menu.show{top:100%!important}` / `.dropdown-menu-right
    // {right:0!important}`, so coordinates must be !important. Bootstrap's
    // Popper rewrites top/left on every update without the flag, which threw
    // the menu off-screen — hence no Bootstrap here.
    var PL_MENU_PROPS = ['position', 'top', 'left', 'right', 'bottom', 'transform'];
    function plPlaceMenu(wrap) {
      var toggle = wrap.querySelector('.ra-toggle');
      var menu = wrap.querySelector('.dropdown-menu');
      var r = toggle.getBoundingClientRect();
      var mw = menu.offsetWidth, mh = menu.offsetHeight;
      var vw = document.documentElement.clientWidth, vh = window.innerHeight;
      var left = Math.max(8, Math.min(r.right - mw, vw - mw - 8));
      var top = r.bottom + 4;
      if (top + mh > vh - 8 && r.top - mh - 4 >= 8) top = r.top - mh - 4; // open upward
      var set = { position: 'fixed', top: top + 'px', left: left + 'px', right: 'auto', bottom: 'auto', transform: 'none' };
      PL_MENU_PROPS.forEach(function(p) { menu.style.setProperty(p, set[p], 'important'); });
    }
    function plCloseMenus() {
      document.querySelectorAll('#datatable .row-actions.show').forEach(function(wrap) {
        var menu = wrap.querySelector('.dropdown-menu');
        wrap.classList.remove('show');
        menu.classList.remove('show');
        PL_MENU_PROPS.forEach(function(p) { menu.style.removeProperty(p); });
        wrap.querySelector('.ra-toggle').setAttribute('aria-expanded', 'false');
      });
    }
    document.addEventListener('click', function(e) {
      var toggle = e.target.closest && e.target.closest('#datatable .ra-toggle');
      if (toggle) {
        var wrap = toggle.parentNode;
        var wasOpen = wrap.classList.contains('show');
        plCloseMenus();
        if (!wasOpen) {
          wrap.classList.add('show');
          wrap.querySelector('.dropdown-menu').classList.add('show');
          toggle.setAttribute('aria-expanded', 'true');
          plPlaceMenu(wrap);
        }
        return;
      }
      // Any other click (including picking an item) closes the menu.
      plCloseMenus();
    });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') plCloseMenus(); });
    // A pinned menu would drift away from its row on scroll; close it instead.
    document.addEventListener('scroll', function(e) {
      if (!(e.target && e.target.closest && e.target.closest('.dropdown-menu'))) plCloseMenus();
    }, true);
    window.addEventListener('resize', plCloseMenus);

    // Status chips filter the Status column, whose filter value is
    // the badge label from plStatusMeta(). A column filter, so it stacks with
    // the year tiles and the search box. The choice is kept for this tab, so
    // activating someone from the Inactive view reloads onto Inactive again.
    var PL_STATUS_LABEL = { active: 'Active', inactive: 'Inactive', pending: 'Pending', none: 'No account' };
    var PL_STATUS_KEY = 'profileList.status';
    function plApplyStatus(key) {
      if (!PL_STATUS_LABEL[key]) key = 'all';
      $('.st-chip').each(function() {
        var on = this.getAttribute('data-status') === key;
        this.classList.toggle('is-active', on);
        this.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      $('#datatable').DataTable().column('status:name')
        .search(key === 'all' ? '' : '^' + PL_STATUS_LABEL[key] + '$', true, false)
        .draw();
      try { sessionStorage.setItem(PL_STATUS_KEY, key); } catch (err) {}
    }
    $(document).on('click', '.st-chip', function() { plApplyStatus(this.getAttribute('data-status')); });
    $(function() {
      // ?status=inactive in the URL wins, so the filter can be linked to.
      var m = /[?&]status=([a-z]+)/.exec(location.search);
      var saved = null;
      try { saved = sessionStorage.getItem(PL_STATUS_KEY); } catch (err) {}
      var key = m ? m[1] : saved;
      if (key && key !== 'all' && $('.st-chip[data-status="' + key + '"]').length) plApplyStatus(key);
    });

    // ---- Bulk delete (Pending / No account only) ----------------------
    // Rows render lazily, so the selection lives in PL_SELECTED and the
    // checkboxes are only a view of it.
    function plDeletableShown() {
      var out = [];
      $('#datatable').DataTable().rows({ search: 'applied' }).data().each(function(row) {
        if (plDeletable(row)) out.push(row.studno);
      });
      return out;
    }
    function plSyncSelection() {
      if (!PL_CAN_MANAGE) return;
      document.querySelectorAll('#datatable .pl-row-sel').forEach(function(cb) {
        cb.checked = PL_SELECTED.has(cb.value);
      });
      var shown = plDeletableShown();
      var picked = shown.filter(function(sn) { return PL_SELECTED.has(sn); }).length;
      var all = document.getElementById('plSelectAll');
      if (all) {
        all.disabled = shown.length === 0;
        all.checked = shown.length > 0 && picked === shown.length;
        all.indeterminate = picked > 0 && picked < shown.length;
      }
      var bar = document.getElementById('plBulkBar');
      bar.hidden = PL_SELECTED.size === 0;
      document.getElementById('plSelCount').textContent = PL_SELECTED.size;
      var more = document.getElementById('plSelAllShown');
      more.hidden = picked === shown.length;
      more.textContent = 'Select all ' + shown.length + ' shown';
    }
    $(document).on('change', '#datatable .pl-row-sel', function() {
      if (this.checked) PL_SELECTED.add(this.value); else PL_SELECTED.delete(this.value);
      plSyncSelection();
    });
    $(document).on('change', '#plSelectAll', function() {
      var on = this.checked;
      plDeletableShown().forEach(function(sn) { if (on) PL_SELECTED.add(sn); else PL_SELECTED.delete(sn); });
      plSyncSelection();
    });
    $(document).on('click', '#plSelAllShown', function() {
      plDeletableShown().forEach(function(sn) { PL_SELECTED.add(sn); });
      plSyncSelection();
    });
    $(document).on('click', '#plSelClear', function() { PL_SELECTED.clear(); plSyncSelection(); });
    $(function() { if (PL_CAN_MANAGE) $('#datatable').on('draw.dt', plSyncSelection); plSyncSelection(); });

    $(document).on('click', '#plBulkDelete', function() {
      var ids = Array.from(PL_SELECTED);
      if (!ids.length) return;
      if (ids.length > 500) {
        if (window.UI && UI.alert) UI.alert({ title: 'Too many selected', message: 'Delete at most 500 students at a time.' });
        else window.alert('Delete at most 500 students at a time.');
        return;
      }
      var msg = 'This permanently erases ' + ids.length + ' student' + (ids.length === 1 ? '' : 's')
        + ' — signup, profile, enrollment, fee assessments, attendance, QR code, login account and photo. It cannot be undone. Type DELETE to confirm.';

      var submit = function(val) {
        if (val === null || val === undefined) return;          // cancelled
        if (String(val).trim() !== 'DELETE') {
          if (window.UI && UI.toast) UI.toast({ type: 'warning', message: 'Nothing was deleted — type DELETE exactly to confirm.' });
          else window.alert('Nothing was deleted — type DELETE exactly to confirm.');
          return;
        }
        if (window.UI && UI.navBusy) UI.navBusy('Deleting ' + ids.length + ' student(s)…');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = PL_URL_BULK_DELETE;
        var add = function(name, value) {
          var inp = document.createElement('input');
          inp.type = 'hidden'; inp.name = name; inp.value = value;
          form.appendChild(inp);
        };
        var csrfName = document.querySelector('meta[name="csrf-token-name"]');
        var csrfHash = document.querySelector('meta[name="csrf-token"]');
        if (csrfName && csrfHash) add(csrfName.content, csrfHash.content);
        ids.forEach(function(sn) { add('ids[]', sn); });
        document.body.appendChild(form);
        form.submit();
      };

      if (window.UI && typeof UI.prompt === 'function') {
        UI.prompt({
          icon: 'error',
          title: 'Delete ' + ids.length + ' student' + (ids.length === 1 ? '' : 's') + ' permanently?',
          message: msg,
          placeholder: 'DELETE',
          confirmText: 'Delete permanently',
          variant: 'danger'
        }).then(submit);
      } else {
        submit(window.prompt(msg));
      }
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

      function handleStatusClick(event, button) {
        event.preventDefault();
        var studno = button.getAttribute('data-studno') || '';
        var action = button.getAttribute('data-action') === 'Activate' ? 'Activate' : 'Deactivate';
        var activate = action === 'Activate';
        var promptText = activate
          ? studno + ' will be able to sign in again.'
          : studno + ' will be signed out everywhere and will not be able to sign in until the account is activated again.';

        var submit = function() {
          if (window.UI && UI.navBusy) UI.navBusy(activate ? 'Activating…' : 'Deactivating…');
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = PL_URL_STATUS;
          var fields = { u: studno, t: action };
          var csrfName = document.querySelector('meta[name="csrf-token-name"]');
          var csrfHash = document.querySelector('meta[name="csrf-token"]');
          if (csrfName && csrfHash) fields[csrfName.content] = csrfHash.content;
          Object.keys(fields).forEach(function(k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
          });
          document.body.appendChild(form);
          form.submit();
        };

        if (window.UI && typeof window.UI.fire === 'function') {
          window.UI.fire({
            title: activate ? 'Activate account?' : 'Set account inactive?',
            text: promptText,
            icon: activate ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: activate ? 'Activate' : 'Set inactive',
            cancelButtonText: 'Cancel'
          }).then(function(result) {
            if (result && (result.isConfirmed || result.value === true)) submit();
          });
        } else if (window.confirm(promptText)) {
          submit();
        }
      }

      document.addEventListener('click', function(event) {
        var statusButton = closestByClass(event.target, 'status-toggle-btn');
        if (statusButton) {
          handleStatusClick(event, statusButton);
          return;
        }
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

  <!-- Printing: the formatted report is Page/profileListPrint (Tools → Print).
       Printing this screen directly would output the dashboard chrome and,
       at paper width, the phone card layout — so point at the report instead. -->
  <div id="plPrintNotice">
    Use <strong>Tools → Print</strong> on the Registered Students page to print the formatted student list.
  </div>
  <style>
    #plPrintNotice { display:none; }
    @media print {
      body > *:not(#plPrintNotice) { display:none !important; }
      #plPrintNotice { display:block !important; padding:40px; font:14pt/1.5 sans-serif; color:#17213a; text-align:center; }
    }
  </style>
  <script>
    // Hand the report whatever the list is showing: status chip + table search
    // (the year tiles search too, so they come along in q).
    $(document).on('click', '#plPrintReport', function() {
      var params = [];
      var chip = document.querySelector('.st-chip.is-active');
      var status = chip ? chip.getAttribute('data-status') : 'all';
      if (status && status !== 'all') params.push('status=' + encodeURIComponent(status));
      try {
        var q = $('#datatable').DataTable().search();
        if (q) params.push('q=' + encodeURIComponent(q));
      } catch (err) {}
      this.href = <?= json_encode(site_url('Page/profileListPrint')); ?> + (params.length ? '?' + params.join('&') : '');
    });
  </script>

  <!-- Student profile side panel: opened from the student name or Actions → View Profile -->
  <div class="pv-backdrop" data-pv-close></div>
  <aside class="pv-drawer" id="pvDrawer" role="dialog" aria-modal="true" aria-labelledby="pvName" aria-hidden="true">
    <div class="pv-bar">
      <div class="pv-nav">
        <button type="button" class="pv-icon-btn" data-pv-step="-1" aria-label="Previous student" title="Previous (↑)"><i class="mdi mdi-chevron-up"></i></button>
        <button type="button" class="pv-icon-btn" data-pv-step="1" aria-label="Next student" title="Next (↓)"><i class="mdi mdi-chevron-down"></i></button>
        <span class="pv-counter" id="pvCounter"></span>
      </div>
      <button type="button" class="pv-icon-btn" data-pv-close aria-label="Close" title="Close (Esc)"><i class="mdi mdi-close"></i></button>
    </div>
    <div class="pv-body" id="pvBody"></div>
    <div class="pv-foot" id="pvFoot" hidden>
      <a class="pv-btn" id="pvFull" href="#"></a>
    </div>
  </aside>
  <style>
    .pl-name-link { font-weight:700; color:#0d1b4b; }
    .pl-name-link:hover, .pl-name-link:focus { color:#4266d4; text-decoration:none; }
    #datatable tbody tr.pv-active, #datatable tbody tr.pv-active:hover { background:#eef2ff !important; }

    body.pv-lock { overflow:hidden; }
    .pv-backdrop {
      position:fixed; inset:0; z-index:1070; background:rgba(13,27,75,.28);
      opacity:0; visibility:hidden; transition:opacity .2s ease, visibility .2s ease;
    }
    .pv-backdrop.is-open { opacity:1; visibility:visible; }
    .pv-drawer {
      position:fixed; top:0; right:0; bottom:0; z-index:1071;
      display:flex; flex-direction:column; width:440px; max-width:100vw;
      background:#fff; color:#0d1b4b; box-shadow:-12px 0 40px rgba(13,27,75,.14);
      transform:translateX(100%); visibility:hidden;
      transition:transform .24s cubic-bezier(.2,.8,.2,1), visibility .24s;
    }
    .pv-drawer.is-open { transform:none; visibility:visible; }
    .pv-bar { display:flex; align-items:center; justify-content:space-between; padding:.6rem .75rem; border-bottom:1px solid #eef1f5; }
    .pv-nav { display:flex; align-items:center; gap:.25rem; }
    .pv-counter { margin-left:.35rem; color:#8a97b8; font-size:.74rem; }
    .pv-icon-btn {
      display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0;
      border:0; border-radius:8px; background:transparent; color:#6b7a99; font-size:19px; cursor:pointer;
    }
    .pv-icon-btn:hover:not(:disabled) { background:#f2f5fb; color:#0d1b4b; }
    .pv-icon-btn:disabled { opacity:.35; cursor:default; }
    .pv-icon-btn:focus, .pv-btn:focus { outline:none; box-shadow:0 0 0 3px rgba(66,102,212,.2); }
    .pv-body { flex:1; overflow-y:auto; padding:1.3rem 1.35rem 1.5rem; overscroll-behavior:contain; }

    .pv-hero { display:flex; align-items:center; gap:.9rem; }
    .pv-photo {
      display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto;
      width:56px; height:56px; overflow:hidden; border-radius:50%;
      background:#eef2ff; color:#4266d4; font-size:28px;
    }
    .pv-photo img { width:100%; height:100%; object-fit:cover; }
    .pv-hero-text { min-width:0; }
    .pv-name { margin:0; font-size:1.08rem; font-weight:700; line-height:1.3; color:#0d1b4b; overflow-wrap:anywhere; }
    .pv-studno { margin-top:.1rem; color:#2a4090; font:600 .8rem ui-monospace,Menlo,Consolas,monospace; }
    .pv-tags { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; margin-top:.45rem; }
    .pv-tag { padding:3px 9px; border-radius:999px; background:#f1f5f9; color:#4a5a7a; font-size:.7rem; font-weight:700; }

    .pv-quick { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:1rem; }
    .pv-quick a {
      display:inline-flex; align-items:center; gap:.3rem; max-width:100%; padding:.3rem .65rem;
      border:1px solid #e6ebf5; border-radius:8px; color:#4a5a7a; font-size:.76rem; font-weight:600;
      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .pv-quick a:hover { border-color:#c7d2fe; background:#f8faff; color:#2a4090; text-decoration:none; }

    .pv-section { margin-top:1.15rem; padding-top:1.1rem; border-top:1px solid #f0f3f8; }
    .pv-section h6 { margin:0 0 .65rem; color:#8a97b8; font-size:.68rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
    .pv-grid { display:grid; grid-template-columns:1fr 1fr; gap:.8rem 1rem; margin:0; }
    .pv-grid > div.pv-wide { grid-column:1 / -1; }
    .pv-grid dt { margin-bottom:.1rem; color:#8a97b8; font-size:.7rem; font-weight:500; }
    .pv-grid dd { margin:0; font-size:.85rem; font-weight:600; overflow-wrap:anywhere; }

    .pv-skel { height:12px; margin:.55rem 0; border-radius:6px; background:linear-gradient(90deg,#f1f4f9 25%,#e7ecf4 50%,#f1f4f9 75%); background-size:200% 100%; animation:pv-shimmer 1.2s infinite linear; }
    @keyframes pv-shimmer { to { background-position:-200% 0; } }
    .pv-note { margin:1.2rem 0 0; padding:.7rem .8rem; border-radius:10px; background:#f8faff; color:#6b7a99; font-size:.8rem; }
    .pv-note.pv-error { background:#fef2f2; color:#b91c1c; }

    .pv-foot { padding:.8rem 1.35rem calc(.8rem + env(safe-area-inset-bottom, 0px)); border-top:1px solid #eef1f5; }
    .pv-foot[hidden] { display:none; }
    .pv-btn {
      display:flex; align-items:center; justify-content:center; gap:.4rem; width:100%; padding:.6rem 1rem;
      border-radius:10px; background:linear-gradient(135deg,#2a4090,#4266d4); color:#fff !important;
      font-size:.85rem; font-weight:700;
    }
    .pv-btn:hover { filter:brightness(1.07); text-decoration:none; }

    @media (max-width: 575.98px) { .pv-grid { grid-template-columns:1fr; } }
    @media (prefers-reduced-motion: reduce) { .pv-drawer, .pv-backdrop { transition:none; } .pv-skel { animation:none; } }
    @media print { .pv-drawer, .pv-backdrop { display:none !important; } }
  </style>
  <script>
    (function() {
      var drawer = document.getElementById('pvDrawer');
      var body = document.getElementById('pvBody');
      var foot = document.getElementById('pvFoot');
      var fullLink = document.getElementById('pvFull');
      var counter = document.getElementById('pvCounter');
      var backdrop = document.querySelector('.pv-backdrop');
      var closeBtn = drawer.querySelector('.pv-bar > [data-pv-close]');
      var prevBtn = drawer.querySelector('[data-pv-step="-1"]');
      var nextBtn = drawer.querySelector('[data-pv-step="1"]');
      var cache = {};
      var current = null;
      var lastFocus = null;

      function isOpen() { return drawer.classList.contains('is-open'); }
      function table() { return $('#datatable').DataTable(); }
      function visibleRows() { return table().rows({ search: 'applied', order: 'applied' }).data().toArray(); }
      function rowFor(studno) {
        var found = null;
        table().rows().data().each(function(r) { if (!found && r.studno === studno) found = r; });
        return found;
      }

      function field(label, value, wide) {
        if (value === null || value === undefined || String(value).trim() === '') return '';
        return '<div' + (wide ? ' class="pv-wide"' : '') + '><dt>' + plEsc(label) + '</dt><dd>' + plEsc(value) + '</dd></div>';
      }
      function section(title, fields) {
        var html = fields.join('');
        return html ? '<section class="pv-section"><h6>' + plEsc(title) + '</h6><dl class="pv-grid">' + html + '</dl></section>' : '';
      }

      function render(row, info, error) {
        var name = (info && info.name) || (row && row.name) || current;
        var status = info ? info.status : (row ? row.status : null);
        var meta = plStatusMeta(status);
        var place = info
          ? [info.yearLevel, info.section].filter(Boolean).join(' \u00b7 ')
          : (row && row.sub ? row.sub : '');
        var photo = info && info.photoUrl
          ? '<img src="' + plEsc(info.photoUrl) + '" alt="">'
          : '<i class="mdi mdi-account"></i>';

        var html = '<div class="pv-hero"><div class="pv-photo">' + photo + '</div><div class="pv-hero-text">'
          + '<h5 class="pv-name" id="pvName">' + plEsc(name) + '</h5>'
          + '<div class="pv-studno">' + plEsc(current) + '</div>'
          + '<div class="pv-tags"><span class="acct-badge ' + meta.cls + '">' + plEsc(meta.label) + '</span>'
          + (place ? '<span class="pv-tag">' + plEsc(place) + '</span>' : '') + '</div></div></div>';

        if (error) {
          html += '<p class="pv-note pv-error"><i class="mdi mdi-alert-circle-outline"></i> ' + plEsc(error) + '</p>';
        } else if (!info) {
          html += '<section class="pv-section"><div class="pv-skel" style="width:40%"></div><div class="pv-skel"></div><div class="pv-skel" style="width:75%"></div></section>'
            + '<section class="pv-section"><div class="pv-skel" style="width:35%"></div><div class="pv-skel" style="width:85%"></div><div class="pv-skel" style="width:60%"></div></section>';
        } else {
          var quick = '';
          if (info.email) quick += '<a href="mailto:' + plEsc(info.email) + '" title="' + plEsc(info.email) + '"><i class="mdi mdi-email-outline"></i> ' + plEsc(info.email) + '</a>';
          if (info.contactNo) quick += '<a href="tel:' + plEsc(info.contactNo.replace(/[^\d+]/g, '')) + '"><i class="mdi mdi-phone-outline"></i> ' + plEsc(info.contactNo) + '</a>';
          if (quick) html += '<div class="pv-quick">' + quick + '</div>';

          html += section('Academic', [
            field('Course', info.course, true),
            field('Major', info.major, true),
            field('Year level', info.yearLevel),
            field('Section', info.section)
          ]);
          html += section('Personal', [
            field('Sex', info.sex),
            field('Civil status', info.civilStatus),
            field('Birth date', info.birthDate),
            field('Age', info.age)
          ]);
          html += section('Contact', [
            field('Email', info.email, true),
            field('Mobile', info.contactNo),
            field('Address', info.address, true)
          ]);
          html += section('Guardian', [
            field('Name', info.guardian, true),
            field('Relationship', info.guardianRelationship),
            field('Contact', info.guardianContact)
          ]);
          html += section('Account', [
            field('Login', meta.label),
            field('Created', info.accountCreated),
            field('Signup status', info.signupStatus)
          ]);
          if (!info.hasSignup) {
            html += '<p class="pv-note"><i class="mdi mdi-information-outline"></i> This student only has a login account — there is no registration record to open.</p>';
          }
        }
        body.innerHTML = html;

        var canOpen = !error && (!info || info.hasSignup);
        foot.hidden = !canOpen;
        if (canOpen) {
          fullLink.href = PL_URL_VIEW + '?id=' + encodeURIComponent(current);
          fullLink.innerHTML = PL_CAN_EDIT
            ? '<i class="mdi mdi-pencil-outline"></i> Edit profile'
            : '<i class="mdi mdi-open-in-new"></i> Open full profile';
        }
      }

      function highlight() {
        document.querySelectorAll('#datatable tbody tr').forEach(function(tr) {
          tr.classList.toggle('pv-active', isOpen() && tr.getAttribute('data-studno') === current);
        });
      }

      function updateNav() {
        var list = visibleRows();
        var index = -1;
        for (var i = 0; i < list.length; i++) { if (list[i].studno === current) { index = i; break; } }
        counter.textContent = index >= 0 ? (index + 1) + ' of ' + list.length : '';
        prevBtn.disabled = index <= 0;
        nextBtn.disabled = index < 0 || index >= list.length - 1;
      }

      function load(studno) {
        if (cache[studno]) { render(rowFor(studno), cache[studno]); return; }
        render(rowFor(studno), null);
        fetch(PL_URL_PREVIEW + '?id=' + encodeURIComponent(studno), {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(res) {
          return res.json().catch(function() { throw new Error('Your session may have expired. Reload the page and try again.'); });
        }).then(function(data) {
          if (!data || !data.ok) throw new Error((data && data.message) || 'Could not load this profile.');
          cache[studno] = data;
          if (current === studno) render(rowFor(studno), data);
        }).catch(function(err) {
          if (current === studno) render(rowFor(studno), null, err.message || 'Could not load this profile.');
        });
      }

      function open(studno) {
        if (!studno) return;
        current = studno;
        load(studno);
        body.scrollTop = 0;
        if (!isOpen()) {
          lastFocus = document.activeElement;
          drawer.classList.add('is-open');
          backdrop.classList.add('is-open');
          drawer.setAttribute('aria-hidden', 'false');
          document.body.classList.add('pv-lock');
          setTimeout(function() { closeBtn.focus(); }, 50);
        }
        updateNav();
        highlight();
      }

      function close() {
        if (!isOpen()) return;
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pv-lock');
        highlight();
        current = null;
        if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus();
      }

      function step(delta) {
        var list = visibleRows();
        for (var i = 0; i < list.length; i++) {
          if (list[i].studno === current) {
            var next = list[i + delta];
            if (!next) return;
            // Follow along in the table: jump to the page that holds the next student.
            var info = table().page.info();
            var target = Math.floor((i + delta) / info.length);
            if (info.length > 0 && target !== info.page) table().page(target).draw('page');
            open(next.studno);
            return;
          }
        }
      }

      document.addEventListener('click', function(e) {
        var trigger = e.target.closest && e.target.closest('.pv-open');
        if (!trigger || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        open(trigger.getAttribute('data-studno'));
      });
      document.querySelectorAll('[data-pv-close]').forEach(function(el) { el.addEventListener('click', close); });
      prevBtn.addEventListener('click', function() { step(-1); });
      nextBtn.addEventListener('click', function() { step(1); });
      document.addEventListener('keydown', function(e) {
        if (!isOpen()) return;
        if (e.key === 'Escape') { close(); return; }
        if (/INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); step(1); }
        if (e.key === 'ArrowUp') { e.preventDefault(); step(-1); }
      });
      $('#datatable').on('draw.dt', function() { if (isOpen()) { updateNav(); highlight(); } });
    })();
  </script>

</body>

</html>

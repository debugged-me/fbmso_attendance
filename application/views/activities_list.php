<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>
    <style>
      /* ===== Attendance Log modal table ===== */
      #logModal .modal-content {
        background:#fff !important; color:#374151 !important;
      }
      #logModal .modal-body { background:#fff !important; }
      /* Modal title white on dark gradient header */
      #logModal .modal-header .modal-title,
      #logModal .modal-header h5 {
        color:#fff !important;
      }

      #logTable thead th {
        background:#f5f7fc; color:#475569; font-size:.72rem; font-weight:800;
        letter-spacing:.1em; text-transform:uppercase;
        border-bottom:2px solid #e6ebf5 !important;
        padding:12px 14px; white-space:nowrap; border-left:none; border-right:none;
      }
      #logTable tbody td {
        padding:10px 14px; vertical-align:middle; font-size:.84rem; color:#374151;
        border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
      }
      #logTable tbody tr:hover { background:#f8faff !important; }
      #logTable tbody tr:last-child td { border-bottom:none !important; }

      .log-export-bar {
        display:flex; gap:6px; padding:12px 16px;
        border-bottom:1px solid #eef1f5; background:#fafbff;
      }

      /* DataTables controls inside modal */
      #logTable_wrapper .dataTables_filter,
      #logTable_wrapper .dataTables_length {
        padding:12px 16px 6px !important; margin:0 !important;
      }
      #logTable_wrapper .dataTables_filter input {
        border-radius:8px !important; border:1px solid #e6ebf5 !important;
        padding:6px 12px !important; font-size:.82rem !important; margin-left:6px !important;
        color:#374151 !important; background:#fff !important;
      }
      #logTable_wrapper .dataTables_length select {
        border-radius:8px !important; border:1px solid #e6ebf5 !important;
        padding:4px 8px !important; margin-left:6px !important;
        color:#374151 !important; background:#fff !important;
      }
      #logTable_wrapper .dataTables_info,
      #logTable_wrapper .dataTables_paginate {
        padding:10px 16px !important; margin:0 !important; color:#6b7a99 !important;
      }
      #logTable_wrapper .dataTables_paginate .paginate_button {
        border-radius:6px !important; min-width:32px; min-height:32px;
        display:inline-flex !important; align-items:center; justify-content:center;
        color:#374151 !important;
      }
      #logTable_wrapper .dataTables_paginate .paginate_button.current,
      #logTable_wrapper .dataTables_paginate .paginate_button.current:hover {
        background:linear-gradient(135deg,#2a4090,#4266d4) !important; color:#fff !important;
        border-color:#2a4090 !important;
      }

      .page-title-box {
        position: relative;
        z-index: 0;
      }

      /* Title + actions on one row (matches AttendanceLogs pattern) */
      .pl-header {
        display:flex; align-items:flex-start; justify-content:space-between;
        gap:18px; flex-wrap:wrap; margin-bottom:16px;
      }
      .pl-header .page-title-box { flex:1 1 auto; margin:0; }
      .pl-header .page-title-box .up-divider { margin:10px 0 0; }
      .pl-header .pl-actions { flex:0 0 auto; align-self:center; display:flex; flex-wrap:wrap; gap:10px; }
      @media (max-width:767.98px) {
        .pl-header { flex-direction:column; gap:10px; }
        .pl-header .pl-actions { align-self:flex-start; }
      }

      /* Uniform table styling */
      .resp-table thead th {
        background: #f5f7fc;
        color: #6b7a99;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        border-bottom: 1px solid #e6ebf5 !important;
        padding: 14px 16px;
        white-space: nowrap;
        border-left: none;
        border-right: none;
      }
      .resp-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        font-size: .86rem;
        color: #0d1b4b;
        border-bottom: 1px solid #eef1f5 !important;
        border-left: none;
        border-right: none;
      }
      .resp-table tbody tr:hover {
        background: #f8faff !important;
      }
      .resp-table tbody tr:last-child td {
        border-bottom: none !important;
      }
      /* Monospace IDs / numbers in blue */
      .resp-table td[data-label="#"] {
        font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
        color: #2a4090;
        font-weight: 700;
      }

      /* Empty state */
      .al-empty {
        text-align: center;
        padding: 48px 20px;
        color: #6b7a99;
      }
      .al-empty i {
        font-size: 42px;
        display: block;
        margin-bottom: 10px;
        color: #9aa5b8;
      }

      /* Uniform log table (modal) */
      #logTable thead th {
        background: #f5f7fc;
        color: #6b7a99;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        border-bottom: 1px solid #e6ebf5 !important;
        padding: 12px 14px;
        white-space: nowrap;
        border-left: none;
        border-right: none;
      }
      #logTable tbody td {
        padding: 11px 14px;
        vertical-align: middle;
        font-size: .84rem;
        color: #0d1b4b;
        border-bottom: 1px solid #eef1f5 !important;
        border-left: none;
        border-right: none;
      }
      #logTable tbody tr:hover {
        background: #f8faff !important;
      }
      #logTable tbody tr:last-child td {
        border-bottom: none !important;
      }
      #logTable td[data-label="#"] {
        font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
        color: #2a4090;
        font-weight: 700;
      }

      /* DataTables (uniform) — in case it gets enabled */
      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
      .dataTables_wrapper .dataTables_filter,
      .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
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

      /* ===== Actions dropdown (replaces scattered icon buttons) ===== */
      .act-dropdown { position: relative; display: inline-block; }

      .act-trigger {
        width: 38px; height: 38px;
        border: 1px solid #e6ebf5; border-radius: 10px;
        background: #fff; color: #6b7a99;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all .15s ease;
        font-size: 20px;
      }
      .act-trigger:hover, .act-trigger:focus {
        border-color: #4266d4; color: #4266d4;
        box-shadow: 0 3px 10px rgba(66,102,212,.12);
        outline: none;
      }
      .act-trigger i { font-size: 20px; line-height: 1; }

      .act-menu {
        min-width: 200px; padding: 6px;
        border: 1px solid #e6ebf5 !important; border-radius: 14px !important;
        box-shadow: 0 12px 36px rgba(15,23,42,.14) !important;
        margin-top: 6px !important;
      }

      .act-item, .act-item-btn {
        display: flex !important; align-items: center; gap: 10px;
        padding: 9px 14px; border: none; background: none;
        border-radius: 8px; font-size: .84rem; font-weight: 600;
        color: #0d1b4b; text-decoration: none; cursor: pointer;
        width: 100%; text-align: left; transition: background .12s ease;
        white-space: nowrap;
      }
      .act-item:hover, .act-item-btn:hover {
        background: #f4f7ff; color: #0d1b4b; text-decoration: none;
      }
      .act-item-btn { font-family: inherit; }

      .act-item-danger { color: #dc2626 !important; }
      .act-item-danger:hover { background: #fef2f2 !important; color: #dc2626 !important; }

      .act-ic { font-size: 18px; line-height: 1; width: 20px; text-align: center; flex-shrink: 0; }
      .act-ic-scan { color: #6b7a99; }
      .act-ic-log { color: #2563eb; }
      .act-ic-poster { color: #2a4090; }
      .act-ic-edit { color: #16a34a; }
      .act-ic-close { color: #f59e0b; }
      .act-ic-open { color: #0f766e; }
      .act-ic-delete { color: #ef4444; }

      .act-divider {
        height: 1px; background: #eef1f5; margin: 4px 8px;
      }

      .status-sub {
        font-size: .68rem;
        color: #6b7280;
        margin-top: 3px;
        text-transform: none
      }

      .table thead th {
        white-space: nowrap
      }

      .date-icons {
        max-width: 180px;
        white-space: nowrap
      }

      .date-icons .ic {
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        color: #374151;
        margin-right: 6px;
        vertical-align: middle;
      }

      .date-icons .ic i {
        font-size: 13px;
        line-height: 1
      }

      .date-icons .ic.cal {
        width: 26px
      }

      .date-icons .ic.am {
        background: #e8f4ff;
        border-color: #cfe3ff;
        color: #0b5ed7
      }

      .date-icons .ic.pm {
        background: #fff3cd;
        border-color: #ffe69c;
        color: #946200
      }

      .date-icons .ic.eve {
        background: #efe7ff;
        border-color: #d6c9ff;
        color: #5a3bd6
      }

      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch
      }

      @media (min-width:768px) and (max-width:1024px) {

        .table-responsive table th,
        .table-responsive table td {
          white-space: nowrap
        }
      }

      @media (max-width: 767.98px) {
        .table.resp-table {
          display: block;
          width: 100%
        }

        .table.resp-table thead {
          display: none
        }

        .table.resp-table tbody {
          display: block
        }

        .table.resp-table tr {
          display: block;
          border: 1px solid #e9ecef;
          border-radius: 12px;
          margin: .5rem 0;
          box-shadow: 0 2px 10px rgba(15, 23, 42, .05)
        }

        .table.resp-table td {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: .75rem;
          padding: .6rem .75rem;
          border: 0;
          border-bottom: 1px dashed #e9ecef;
          white-space: normal;
          word-break: break-word
        }

        .table.resp-table td:last-child {
          border-bottom: 0
        }

        .table.resp-table td[data-label]::before {
          content: attr(data-label);
          flex: 0 0 44%;
          font-weight: 600;
          color: #6b7280
        }

        .act-trigger {
          width: 44px; height: 44px;
        }

        .date-icons {
          white-space: normal
        }
      }

      @media (max-width: 767.98px) {
        #logTable {
          display: block;
          width: 100%
        }

        #logTable thead {
          display: none
        }

        #logTable tbody {
          display: block
        }

        #logTable tr {
          display: block;
          border: 1px solid #e9ecef;
          border-radius: 12px;
          margin: .5rem 0;
          box-shadow: 0 2px 10px rgba(15, 23, 42, .05)
        }

        #logTable td {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: .7rem;
          padding: .55rem .7rem;
          border: 0;
          border-bottom: 1px dashed #e9ecef;
          white-space: normal;
          word-break: break-word
        }

        #logTable td:last-child {
          border-bottom: 0
        }

        #logTable td[data-label]::before {
          content: attr(data-label);
          flex: 0 0 44%;
          font-weight: 600;
          color: #6b7280
        }
      }

      @media print {
        .table-responsive {
          overflow: visible !important
        }

        table {
          display: table !important;
          width: 100% !important;
          table-layout: auto !important
        }

        thead {
          display: table-header-group !important
        }

        tbody {
          display: table-row-group !important
        }

        tr {
          display: table-row !important;
          box-shadow: none !important;
          border: none !important;
          margin: 0 !important
        }

        td,
        th {
          display: table-cell !important;
          white-space: normal !important;
          word-break: break-word !important;
          border: 1px solid #dee2e6 !important;
          padding: .5rem !important;
          vertical-align: middle !important
        }

        td::before {
          content: none !important
        }
      }

      .row-flash {
        animation: rowFlash 1.2s ease-out 1
      }

      @keyframes rowFlash {
        0% {
          background: #e6fffa
        }

        100% {
          background: transparent
        }
      }

      .src-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-weight: 600;
        font-size: .75rem
      }

      .src-qr {
        background: #e6f2ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe
      }

      .src-man {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a
      }

      @media (max-width: 767.98px) {
        .table.resp-table td {
          min-width: 0;
        }

        .table.resp-table td>* {
          max-width: 100%;
        }

        td[data-label="Program"] .badge {
          display: inline-block;
          white-space: normal !important;
          word-break: break-word;
          line-height: 1.2;
          padding: .3rem .5rem;
          max-width: 100%;
        }

        .table.resp-table td[data-label]::before {
          flex: 0 0 38%;
        }

        td[data-label="Title & Location"] .font-weight-600 {
          word-break: break-word;
        }
      }

      @media (max-width: 767.98px) {
        .table.resp-table td {
          display: grid !important;
          grid-template-columns: 40% 1fr;
          gap: 10px;
          align-items: start;
          padding: .6rem .75rem;
          border: 0;
          border-bottom: 1px dashed #e9ecef;
          white-space: normal;
          word-break: break-word;
          min-width: 0;
        }

        .table.resp-table td:last-child {
          border-bottom: 0;
        }

        .table.resp-table td[data-label]::before {
          content: attr(data-label) ":";
          grid-column: 1;
          font-weight: 600;
          color: #6b7280;
          line-height: 1.3;
        }

        .table.resp-table td>* {
          grid-column: 2;
          min-width: 0;
          max-width: 100%;
        }

        td[data-label="Program"] .badge {
          display: inline-block;
          white-space: normal !important;
          word-break: break-word;
          line-height: 1.2;
          padding: .3rem .5rem;
          max-width: 100%;
        }

        td[data-label="Date"] .date-icons {
          white-space: normal;
        }

        td[data-label="Date"] .date-icons .ic {
          margin: 0 6px 6px 0;
        }

        td[data-label="Actions"] .act-dropdown {
          display: inline-flex;
        }

      }

      @media (max-width: 767.98px) {
        #logTable td {
          display: grid !important;
          grid-template-columns: 40% 1fr;
          gap: 10px;
          align-items: start;
          padding: .55rem .7rem;
          border: 0;
          border-bottom: 1px dashed #e9ecef;
          white-space: normal;
          word-break: break-word;
          min-width: 0;
        }

        #logTable td:last-child {
          border-bottom: 0;
        }

        #logTable td[data-label]::before {
          content: attr(data-label) ":";
          grid-column: 1;
          font-weight: 600;
          color: #6b7280;
          line-height: 1.3;
        }

        #logTable td>* {
          grid-column: 2;
          min-width: 0;
          max-width: 100%;
        }

        #logModal .close {
          pointer-events: auto;
        }

      }
    </style>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-12">
              <div class="pl-header">
                <div class="page-title-box">
                  <h4 class="up-page-title d-flex align-items-center">
                    <i class="ion ion-ios-qr-scanner mr-2"></i> Activities
                  </h4>
                  <div class="up-page-sub">Create activities, open the scanner, or print a poster QR for self check-in.</div>
                  <hr class="up-divider" />
                </div>
                <div class="pl-actions">
                  <a href="<?= base_url(); ?>Page/admin" class="up-btn up-btn-ghost">
                    <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                  </a>
                  <a href="<?= site_url('activities/create'); ?>" class="up-btn up-btn-primary">
                    <i class="ion ion-md-add-circle-outline"></i> Create Activity
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="up-card">
            <div class="up-card-head">
              <h4><i class="ion ion-ios-qr-scanner"></i> List of Activities</h4>
              <span class="badge badge-light" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;color:#6b7a99;border:1px solid #e6ebf5;">QR Attendance</span>
            </div>
            <div class="up-card-body" style="padding:0 !important;">
              <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle resp-table">
                  <thead class="thead-light">
                    <tr>
                      <th style="width:60px">L/R</th>
                      <th>Activity Title</th>
                      <th style="width:180px">Date</th>
                      <th style="width:160px">Program</th>
                      <th style="width:110px">Status</th>
                      <th style="width:80px" class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (!isset($posterMode)) {
                      $flagPath = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'qr_poster_mode.flag';
                      $posterMode = (is_file($flagPath) && strtolower(trim(@file_get_contents($flagPath))) === 'on');
                    }

                    /**
                     * "7:10–7:11 PM" with deduped AM/PM, or single "7:10 PM".
                     * If both sides same hour, end shows minutes only: "7:10–11 PM".
                     */
                    function compact_range($in, $out)
                    {
                      $inTs  = $in  ? strtotime($in)  : null;
                      $outTs = $out ? strtotime($out) : null;

                      if ($inTs && $outTs) {
                        $h1 = date('g', $inTs);
                        $m1 = date('i', $inTs);
                        $h2 = date('g', $outTs);
                        $m2 = date('i', $outTs);
                        $ap1 = date('A', $inTs);
                        $ap2 = date('A', $outTs);

                        $left  = $h1 . ':' . $m1;
                        $right = ($h1 === $h2) ? $m2 : ($h2 . ':' . $m2);
                        $suffix = ($ap1 === $ap2) ? (' ' . $ap1) : (' ' . $ap1 . '–' . $ap2);

                        return $left . '–' . $right . $suffix;
                      }

                      if ($inTs)  return date('g:i A', $inTs);
                      if ($outTs) return date('g:i A', $outTs);
                      return '';
                    }

                    /**
                     * Build icons + tooltips for the Date column.
                     * If start/end exist → single clock icon (time range).
                     * Else use meta sessions am/pm/eve → up to three icons with tooltips.
                     */
                    function build_date_icons($row)
                    {
                      // Single window?
                      $st = (!empty($row->start_time) && $row->start_time !== '00:00:00') ? $row->start_time : null;
                      $et = (!empty($row->end_time)   && $row->end_time   !== '00:00:00') ? $row->end_time   : null;
                      if ($st || $et) {
                        $rng = compact_range($st, $et);
                        if ($rng) {
                          return [[
                            'k'    => 'rng',
                            'icon' => 'ion ion-md-time',
                            'cls'  => '',
                            'tip'  => $rng
                          ]];
                        }
                      }

                      // Otherwise sessions
                      $meta = [];
                      if (!empty($row->meta)) {
                        $meta = json_decode((string)$row->meta, true);
                        if (!is_array($meta)) $meta = [];
                      }
                      $map = [
                        'am'  => ['label' => 'Morning',   'icon' => 'ion ion-md-sunny',        'cls' => 'am'],
                        'pm'  => ['label' => 'Afternoon', 'icon' => 'ion ion-md-partly-sunny', 'cls' => 'pm'],
                        'eve' => ['label' => 'Evening',   'icon' => 'ion ion-md-moon',         'cls' => 'eve'],
                      ];
                      $icons = [];
                      foreach (['am', 'pm', 'eve'] as $k) {
                        if (empty($meta['sessions'][$k])) continue;
                        $win = $meta['sessions'][$k];
                        $in  = !empty($win['in'])  ? $win['in']  : null;
                        $out = !empty($win['out']) ? $win['out'] : null;
                        $rng = compact_range($in, $out);
                        if ($rng) {
                          $icons[] = [
                            'k'    => $k,
                            'icon' => $map[$k]['icon'],
                            'cls'  => $map[$k]['cls'],
                            'tip'  => $map[$k]['label'] . ' ' . $rng
                          ];
                        }
                      }
                      return $icons ?: [];
                    }
                    ?>

                    <?php if (!empty($rows)): foreach ($rows as $idx => $r): ?>
                        <?php
                        $dateStr = '—';
                        if (!empty($r->activity_date)) {
                          $dt = DateTime::createFromFormat('Y-m-d', $r->activity_date);
                          $dateStr = $dt ? $dt->format('D, M j, Y') : htmlspecialchars($r->activity_date, ENT_QUOTES, 'UTF-8');
                        }
                        $icons = build_date_icons($r);
                        ?>
                        <tr>
                          <td data-label="#"><?= $idx + 1 ?></td>

                          <td data-label="Title & Location">
                            <div class="font-weight-600"><?= htmlspecialchars($r->title) ?></div>
                            <?php if (!empty($r->location)): ?>
                              <small class="text-muted d-inline-flex align-items-center">
                                <i class="ion ion-md-pin mr-1"></i> <?= htmlspecialchars($r->location) ?>
                              </small>
                            <?php endif; ?>
                          </td>

                          <td data-label="Date" class="date-icons">
                            <!-- Calendar icon (date on hover) -->
                            <span class="ic cal" data-toggle="tooltip" data-placement="top" title="<?= htmlspecialchars($dateStr) ?>">
                              <i class="ion ion-md-calendar"></i>
                            </span>

                            <!-- Session/Range icons (tooltips show label + time) -->
                            <?php if ($icons): foreach ($icons as $ic): ?>
                                <span class="ic <?= htmlspecialchars($ic['cls'] ?? '') ?>"
                                  data-toggle="tooltip" data-placement="top"
                                  title="<?= htmlspecialchars($ic['tip']) ?>">
                                  <i class="<?= htmlspecialchars($ic['icon']) ?>"></i>
                                </span>
                              <?php endforeach;
                            else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>

                          <td data-label="Program">
                            <?= !empty($r->program_effective)
                              ? '<span class="badge badge-pill" style="background:linear-gradient(135deg,#2a4090,#4266d4);color:#fff;">' . htmlspecialchars($r->program_effective, ENT_QUOTES, 'UTF-8') . '</span>'
                              : '<span class="text-muted">—</span>'; ?>
                          </td>

                          <td data-label="Status">
                            <?php $st = activity_state($r); ?>
                            <span class="badge badge-pill <?= activity_state_badge_class($st['state']) ?> text-uppercase"
                                  <?= $st['reason'] ? 'data-toggle="tooltip" title="' . htmlspecialchars($st['reason'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                              <?= htmlspecialchars($st['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($st['state'] === 'ended' && $st['manual_open']): ?>
                              <div class="status-sub">auto-closed</div>
                            <?php elseif ($st['state'] === 'open' && !$st['auto_close']): ?>
                              <div class="status-sub">no auto-close</div>
                            <?php endif; ?>
                          </td>

                          <td data-label="Actions" class="text-center">
                            <div class="act-dropdown">
                              <button type="button" class="act-trigger" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ion ion-md-more"></i>
                              </button>
                              <div class="act-menu dropdown-menu dropdown-menu-right">

                                <?php if (!$posterMode): ?>
                                  <a class="act-item" href="<?= site_url('activities/' . $r->activity_id . '/scan') ?>">
                                    <i class="ion ion-md-qr-scanner act-ic act-ic-scan"></i> Scan
                                  </a>
                                <?php endif; ?>

                                <a class="act-item" href="javascript:void(0)" onclick="viewLog(<?= (int)$r->activity_id ?>)">
                                  <i class="ion ion-md-list act-ic act-ic-log"></i> View Attendance
                                </a>

                                <?php if ($posterMode): ?>
                                  <a class="act-item" href="<?= site_url('activities/' . $r->activity_id . '/poster') ?>" target="_blank" rel="noopener">
                                    <i class="ion ion-md-easel act-ic act-ic-poster"></i> View Poster
                                  </a>
                                <?php endif; ?>

                                <?php
                                $nextStatus = $st['is_open'] ? 'closed' : 'open';
                                $confirmMsg = $st['is_open']
                                  ? 'Students will no longer be able to check in to this activity.'
                                  : ($st['state'] === 'ended'
                                      ? 'This activity already ended. Reopening it also turns OFF auto-close, so it stays open until you close it again.'
                                      : 'Students will be able to check in to this activity.');
                                ?>
                                <form method="post" action="<?= site_url('activities/' . $r->activity_id . '/status') ?>"
                                  data-ui-confirm="<?= htmlspecialchars($confirmMsg, ENT_QUOTES, 'UTF-8') ?>"
                                  data-ui-confirm-title="<?= $st['is_open'] ? 'Close check-ins?' : 'Open check-ins?' ?>"
                                  data-ui-confirm-ok="<?= $st['is_open'] ? 'Close activity' : 'Open activity' ?>">
                                  <input type="hidden" name="status" value="<?= $nextStatus ?>">
                                  <button type="submit" class="act-item act-item-btn">
                                    <i class="ion <?= $st['is_open'] ? 'ion-md-lock act-ic-close' : 'ion-md-unlock act-ic-open' ?>"></i>
                                    <?= $st['is_open'] ? 'Close check-ins' : 'Open check-ins' ?>
                                  </button>
                                </form>

                                <a class="act-item" href="<?= site_url('activities/' . $r->activity_id . '/edit') ?>">
                                  <i class="ion ion-md-create act-ic act-ic-edit"></i> Edit
                                </a>

                                <div class="act-divider"></div>

                                <form method="post" action="<?= site_url('activities/' . $r->activity_id . '/delete'); ?>"
                                  data-ui-confirm="&ldquo;<?= htmlspecialchars((string)$r->title, ENT_QUOTES, 'UTF-8'); ?>&rdquo; and its QR check-in link stop working. This cannot be undone."
                                  data-ui-confirm-title="Delete this activity?"
                                  data-ui-confirm-ok="Delete activity">
                                  <button type="submit" class="act-item act-item-btn act-item-danger">
                                    <i class="ion ion-md-trash act-ic act-ic-delete"></i> Delete
                                  </button>
                                </form>

                              </div>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach;
                    else: ?>
                      <tr>
                        <td colspan="6" class="al-empty">
                          <i class="ion ion-md-calendar"></i>
                          No activities yet.
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Attendance Log Modal -->
          <div class="modal fade" id="logModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
              <div class="modal-content" style="border-radius:16px;overflow:hidden;">

                <!-- Header -->
                <div class="modal-header align-items-center" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;padding:16px 24px;">
                  <h5 class="modal-title" style="font-weight:800;display:flex;align-items:center;gap:8px;">
                    <i class="mdi mdi-clipboard-list-outline"></i>
                    Attendance Log
                    <span class="badge badge-light ml-1" id="logCount" style="color:#2a4090;font-size:.76rem;">0</span>
                  </h5>
                  <div class="ml-auto d-flex align-items-center gap-2">
                    <div class="custom-control custom-switch d-none d-md-block mr-2">
                      <input type="checkbox" class="custom-control-input" id="autoRefreshSwitch" checked>
                      <label class="custom-control-label text-white-50" for="autoRefreshSwitch" style="font-size:.76rem;">Auto</label>
                    </div>
                    <button id="btnRefreshLog" class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.25);">
                      <i class="mdi mdi-refresh"></i> Refresh
                    </button>
                    <button type="button" class="close ml-1" data-dismiss="modal" aria-label="Close" style="color:#fff;opacity:.9;font-size:1.2rem;line-height:1;">
                      <i class="mdi mdi-close"></i>
                    </button>
                  </div>
                </div>

                <!-- Body -->
                <div class="modal-body position-relative" style="padding:0;">
                  <div id="logSpinner" class="text-center"
                    style="position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,.7);z-index:5;">
                    <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading…</span></div>
                  </div>

                  <!-- Export buttons bar -->
                  <div class="log-export-bar" id="logExportBar" style="display:none;">
                    <button class="up-btn up-btn-ghost up-btn-sm" onclick="logTableExport('csv')"><i class="mdi mdi-file-delimited"></i> CSV</button>
                    <button class="up-btn up-btn-ghost up-btn-sm" onclick="logTableExport('excel')"><i class="mdi mdi-file-excel"></i> Excel</button>
                    <button class="up-btn up-btn-ghost up-btn-sm" onclick="logTableExport('print')"><i class="mdi mdi-printer"></i> Print</button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-sm mb-0 w-100" id="logTable" style="border:none;">
                      <thead>
                        <tr>
                          <th style="width:56px">#</th>
                          <th>Student</th>
                          <th style="width:160px">IN</th>
                          <th style="width:160px">OUT</th>
                          <th style="width:110px">Session</th>
                          <th style="width:90px">Source</th>
                          <th>Remarks</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>

                <!-- Mobile footer -->
                <div class="modal-footer d-flex d-md-none" style="border-top:1px solid #e6ebf5;padding:12px 20px;">
                  <div class="custom-control custom-switch mr-auto">
                    <input type="checkbox" class="custom-control-input" id="autoRefreshSwitchSm" checked>
                    <label class="custom-control-label" for="autoRefreshSwitchSm" style="font-size:.76rem;">Auto</label>
                  </div>
                  <button id="btnRefreshLogSm" class="up-btn up-btn-ghost up-btn-sm">
                    <i class="mdi mdi-refresh"></i> Refresh
                  </button>
                  <button type="button" class="up-btn up-btn-ghost up-btn-sm" data-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <div style="height:40px;"></div>
      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script>
    if (window.$ && $.fn.tooltip) {
      $('[data-toggle="tooltip"]').tooltip();
    }

    (function() {
      let _logActivityId = null,
        _autoTimer = null;
      const spinnerEl = document.getElementById('logSpinner');
      const countEl = document.getElementById('logCount');
      const btnRefresh = document.getElementById('btnRefreshLog');
      const btnRefreshSm = document.getElementById('btnRefreshLogSm');
      const autoSw = document.getElementById('autoRefreshSwitch');
      const autoSwSm = document.getElementById('autoRefreshSwitchSm');
      const exportBar = document.getElementById('logExportBar');
      let _logDt = null; // DataTable instance
      let _logRows = []; // raw rows data for full export/print

      window.viewLog = function(activityId) {
        _logActivityId = activityId;
        $('#logModal').modal('show');
        fetchAndRender(true);
      };

      // Helper: format a single row into <td> cells
      function buildLogRowHtml(row, idx) {
        function sesLbl(s) {
          return ({am:'Morning', pm:'Afternoon', eve:'Evening'})[s || ''] || '—';
        }
        function remarkFor(r) {
          var v = (r.remarks || '').trim();
          if (v) return v;
          return ((r.source || '').toLowerCase() === 'qr') ? 'Scanned via QR' : '—';
        }
        function srcText(s) {
          var v = (s || '').toLowerCase();
          if (v === 'qr') return 'QR';
          if (v === 'manual') return 'Manual';
          if (v === 'import') return 'Import';
          return '—';
        }
        var dash = '—';
        var inAt  = row.checked_in_at  ? fmt(row.checked_in_at)  : dash;
        var outAt = row.checked_out_at ? fmt(row.checked_out_at) : dash;
        var name  = (row.student_name && row.student_name.trim()) ? row.student_name : (row.full_name || '');
        return '<tr>' +
          '<td>' + (idx + 1) + '</td>' +
          '<td>' + name + '</td>' +
          '<td>' + inAt + '</td>' +
          '<td>' + outAt + '</td>' +
          '<td>' + sesLbl(row.session) + '</td>' +
          '<td>' + srcText(row.source) + '</td>' +
          '<td>' + remarkFor(row) + '</td>' +
        '</tr>';
      }

      // Export function for CSV/Excel/Print
      window.logTableExport = function(type) {
        if (!_logDt) return;
        if (type === 'csv') { _logDt.button(0).trigger(); return; }
        if (type === 'excel') { _logDt.button(1).trigger(); return; }
        if (type === 'print') {
          // Custom print: build a fresh table from ALL rows (not paginated DOM)
          var actTitle = document.querySelector('#logModal .modal-title');
          var titleText = actTitle ? actTitle.textContent.trim() : 'Attendance Log';
          var rows = _logRows || [];

          // Build full table HTML from raw data
          var tbodyHtml = '';
          if (!rows.length) {
            tbodyHtml = '<tr><td colspan="7" style="text-align:center;padding:20px;">No records.</td></tr>';
          } else {
            // Sort same way as the on-screen table
            rows.forEach(function(row, idx) {
              tbodyHtml += buildLogRowHtml(row, idx);
            });
          }

          var w = window.open('', '_blank', 'width=900,height=600');
          if (!w) { alert('Please allow popups to print.'); return; }
          w.document.write(
            '<html><head><title>' + titleText + '</title>' +
            '<style>' +
            'body{font-family:Arial,Helvetica,sans-serif;color:#333;margin:24px;}' +
            'h2{font-size:16px;margin:0 0 4px;}' +
            'small{color:#888;font-size:11px;}' +
            'table{width:100%;border-collapse:collapse;margin-top:12px;}' +
            'th{padding:8px 10px;border:1px solid #ddd;background:#f5f7fc;font-size:11px;text-transform:uppercase;letter-spacing:.05em;text-align:left;}' +
            'td{padding:6px 10px;border:1px solid #ddd;font-size:12px;}' +
            'tr:nth-child(even){background:#fafbff;}' +
            '@media print{.no-print{display:none}body{margin:12px;}}' +
            '</style></head><body>' +
            '<h2>' + titleText + '</h2>' +
            '<small>Printed: ' + new Date().toLocaleString() + ' &mdash; ' + rows.length + ' record(s)</small>' +
            '<table><thead><tr>' +
            '<th>#</th><th>Student</th><th>IN</th><th>OUT</th><th>Session</th><th>Source</th><th>Remarks</th>' +
            '</tr></thead><tbody>' + tbodyHtml + '</tbody></table>' +
            '<div class="no-print" style="margin-top:20px;"><button onclick="window.print()" style="padding:8px 20px;font-size:14px;cursor:pointer;">Print</button> <button onclick="window.close()" style="padding:8px 20px;font-size:14px;cursor:pointer;">Close</button></div>' +
            '</body></html>'
          );
          w.document.close();
          w.focus();
          return;
        }
      };

      function showSpinner(s) {
        if (spinnerEl) spinnerEl.style.display = s ? 'flex' : 'none';
      }

      function srcBadge(src) {
        const s = (src || '').toLowerCase();
        if (s === 'qr') return '<span class="src-badge src-qr">QR</span>';
        if (s === 'manual') return '<span class="src-badge src-man">Manual</span>';
        if (s === 'import') return '<span class="src-badge" style="background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca">Import</span>';
        return '<span class="src-badge" style="background:#eee;border:1px solid #ddd;color:#555">—</span>';
      }

      // 12-hour format: h:mm:ss AM/PM
      function fmt(iso) {
        if (!iso) return '';
        try {
          return moment(iso).format('h:mm:ss A');
        } catch (e) {
          return iso;
        }
      }

      function remarkFor(row) {
        const r = (row.remarks || '').trim();
        if (r) return r;
        return ((row.source || '').toLowerCase() === 'qr') ? 'Scanned via QR' : '—';
      }

      function fetchAndRender(first = false) {
        if (!_logActivityId) return;

        const logTableEl = $('#logTable');
        const tbodyEl = logTableEl.find('tbody')[0];

        showSpinner(true);
        fetch('<?= site_url('attendance/logs/') ?>' + _logActivityId)
          .then(r => r.json())
          .then(j => {
            showSpinner(false);
            const rows = (j.ok && Array.isArray(j.rows)) ? j.rows : [];
            _logRows = rows; // store for full export/print
            if (countEl) countEl.textContent = String(rows.length);

            tbodyEl.innerHTML = '';
            if (!rows.length) {
              tbodyEl.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px 16px;color:#9aa5b8;"><i class="mdi mdi-clipboard-outline" style="font-size:36px;display:block;margin-bottom:8px;"></i>No attendance records yet.</td></tr>';
              if (exportBar) exportBar.style.display = 'none';
              if (_logDt) { _logDt.destroy(); _logDt = null; }
              return;
            }
            rows.sort((a, b) => {
              // Extract last/first name from various possible fields
              const extractName = (row) => {
                let last = '',
                  first = '';
                // Prefer explicit fields if present
                if (row.LastName || row.FirstName) {
                  last = (row.LastName || '').toString().trim().toLowerCase();
                  first = (row.FirstName || '').toString().trim().toLowerCase();
                  return {
                    last,
                    first
                  };
                }
                const nm = (row.student_name || row.full_name || '').toString().trim();
                if (!nm) return {
                  last: '',
                  first: ''
                };

                // If "Last, First" format
                if (/,/.test(nm)) {
                  const parts = nm.split(',').map(p => p.trim());
                  last = (parts[0] || '').toLowerCase();
                  first = (parts.slice(1).join(' ') || '').toLowerCase();
                  return {
                    last,
                    first
                  };
                }

                // Otherwise assume "First Middle Last" -> last token is last name
                const parts = nm.split(/\s+/).filter(Boolean);
                if (parts.length === 1) {
                  first = parts[0].toLowerCase();
                  return {
                    last: '',
                    first
                  };
                }
                last = parts[parts.length - 1].toLowerCase();
                first = parts.slice(0, parts.length - 1).join(' ').toLowerCase();
                return {
                  last,
                  first
                };
              };

              const A = extractName(a);
              const B = extractName(b);

              // Compare last name, then first name
              const cmpLast = A.last.localeCompare(B.last);
              if (cmpLast !== 0) return cmpLast;
              const cmpFirst = A.first.localeCompare(B.first);
              if (cmpFirst !== 0) return cmpFirst;

              // Fallback: date -> session rank -> timestamp
              const getDate = iso => moment(iso || '').format('YYYY-MM-DD') || '';
              const dateA = getDate(a.checked_in_at || a.checked_out_at);
              const dateB = getDate(b.checked_in_at || b.checked_out_at);
              if (dateA !== dateB) return dateA.localeCompare(dateB);

              const rank = s => ({
                am: 1,
                morning: 1,
                pm: 2,
                afternoon: 2,
                eve: 3,
                evening: 3
              } [String(s || '').toLowerCase()] || 9);
              const rA = rank(a.session);
              const rB = rank(b.session);
              if (rA !== rB) return rA - rB;

              const tA = new Date(a.checked_in_at || a.checked_out_at || 0).getTime();
              const tB = new Date(b.checked_in_at || b.checked_out_at || 0).getTime();
              return tA - tB;
            });


            rows.forEach((row, idx) => {
              const tr = document.createElement('tr');

              function sesLbl(s) {
                return ({
                  am: 'Morning',
                  pm: 'Afternoon',
                  eve: 'Evening'
                })[s || ''] || '—';
              }
              const dash = '—';
              const inAt = row.checked_in_at ? '<span class="log-time">' + fmt(row.checked_in_at) + '</span>' : dash;
              const outAt = row.checked_out_at ? '<span class="log-time">' + fmt(row.checked_out_at) + '</span>' : '<span class="muted">' + dash + '</span>';

              tr.innerHTML =
                '<td data-label="#">' + (idx + 1) + '</td>' +
                '<td data-label="Student" class="copy-sn td-sn">' +
                (row.student_name && row.student_name.trim() ? row.student_name : (row.full_name || '')) +
                '</td>' +
                '<td data-label="IN">' + inAt + '</td>' +
                '<td data-label="OUT">' + outAt + '</td>' +
                '<td data-label="Session">' + '<span class="pill pill-ses">' + sesLbl(row.session) + '</span>' + '</td>' +
                '<td data-label="Source">' + srcBadge(row.source || '') + '</td>' +
                '<td data-label="Remarks">' + remarkFor(row) + '</td>';

              tbodyEl.appendChild(tr);
            });

            logTableEl
              .off('click.copy')
              .on('click.copy', 'td.copy-sn', function() {
                const name = $(this).text().trim();
                if (name && navigator.clipboard) navigator.clipboard.writeText(name);
                $(this).addClass('row-flash');
                setTimeout(() => $(this).removeClass('row-flash'), 800);
              });

            // Initialize DataTable with export buttons
            if (_logDt) { _logDt.destroy(); _logDt = null; }
            _logDt = logTableEl.DataTable({
              dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rtip',
              pageLength: 25,
              lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
              order: [], // no initial sort — keep our custom name sort
              buttons: [
                {
                  extend: 'csvHtml5',
                  text: 'CSV',
                  titleAttr: 'Export CSV',
                  className: 'd-none',
                  exportOptions: { modifier: { page: 'all' } }
                },
                {
                  extend: 'excelHtml5',
                  text: 'Excel',
                  titleAttr: 'Export Excel',
                  className: 'd-none',
                  exportOptions: { modifier: { page: 'all' } }
                }
              ],
              columnDefs: [
                { targets: [2, 3], type: 'string' }, // IN/OUT as string for sorting
                { targets: [4, 5], orderable: false } // Session/Source not sortable
              ],
              language: {
                search: "",
                searchPlaceholder: "Search student...",
                lengthMenu: "Show _MENU_",
                info: "Showing _START_–_END_ of _TOTAL_",
                infoEmpty: "No records",
                infoFiltered: "(filtered from _MAX_)",
                zeroRecords: "No matching records."
              }
            });
            // Show export bar when data is loaded
            if (exportBar) exportBar.style.display = 'flex';

          })
          .catch(() => {
            showSpinner(false);
            tbodyEl.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px 16px;color:#dc2626;"><i class="mdi mdi-alert-circle-outline" style="font-size:36px;display:block;margin-bottom:8px;"></i>Failed to load attendance log.</td></tr>';
            if (exportBar) exportBar.style.display = 'none';
            if (_logDt) { _logDt.destroy(); _logDt = null; }
          });
      }

      function setAuto(on) {
        if (_autoTimer) {
          clearInterval(_autoTimer);
          _autoTimer = null;
        }
        if (on) _autoTimer = setInterval(() => fetchAndRender(), 4000);
      }

      if (btnRefresh) btnRefresh.addEventListener('click', () => fetchAndRender());
      if (btnRefreshSm) btnRefreshSm.addEventListener('click', () => fetchAndRender());
      if (autoSw) autoSw.addEventListener('change', () => setAuto(autoSw.checked));
      if (autoSwSm) autoSwSm.addEventListener('change', () => setAuto(autoSwSm.checked));

      $('#logModal')
        .on('shown.bs.modal', function() {
          if (autoSw && autoSwSm) autoSwSm.checked = autoSw.checked;
          setAuto((autoSw && autoSw.checked) || (autoSwSm && autoSwSm.checked));
        })
        .on('hidden.bs.modal', function() {
          setAuto(false);
          if (_logDt) { _logDt.destroy(); _logDt = null; }
          if (exportBar) exportBar.style.display = 'none';
        });
    })();
    // Strong close for the Attendance Log modal
    $(document)
      .on('click', '#logModal .close', function() {
        $('#logModal').modal('hide');
      })
      .on('hidden.bs.modal', '#logModal', function() {
        // make sure any stray backdrops from other scripts are removed
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
      });

    // Close action dropdown when a non-form action link is clicked
    $(document).on('click', '.act-menu .act-item:not(.act-item-btn)', function() {
      $(this).closest('.act-dropdown').find('.act-trigger').dropdown('toggle');
    });
  </script>
</body>

</html>
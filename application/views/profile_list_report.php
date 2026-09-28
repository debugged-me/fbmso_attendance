<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registered Students — Report</title>
  <link rel="icon" type="image/png" href="<?= base_url('assets/images/Attendance.png') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/fonts/DM_Sans/dm-sans.css?v=20260922') ?>">
  <!-- Same layout as attendance_logs_report.php so printed reports match. -->
  <style>
    :root { --navy:#1a2942; --blue:#2a4090; --ink:#17213a; --muted:#667085; --line:#d9e0ea; }
    * { box-sizing:border-box; }
    html, body { margin:0; min-height:100%; }
    body { background:#eef2f7; color:var(--ink); font-family:'DM Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }

    .print-toolbar {
      position:sticky; top:0; z-index:10; display:flex; justify-content:space-between; align-items:center;
      gap:16px; padding:12px 24px; background:rgba(255,255,255,.96); border-bottom:1px solid #dfe5ee;
      box-shadow:0 4px 16px rgba(15,23,42,.08); backdrop-filter:blur(10px);
    }
    .toolbar-note { color:var(--muted); font-size:13px; }
    .toolbar-actions { display:flex; gap:10px; }
    .toolbar-button {
      display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:9px 17px;
      border:1px solid #cfd7e4; border-radius:9px; background:#fff; color:var(--ink); font:inherit;
      font-size:14px; font-weight:700; text-decoration:none; cursor:pointer;
    }
    .toolbar-button.primary { background:var(--blue); border-color:var(--blue); color:#fff; }

    .report-sheet {
      width:min(1320px, calc(100% - 40px)); margin:28px auto; padding:28px; background:#fff;
      border-radius:12px; box-shadow:0 18px 50px rgba(15,23,42,.12);
    }
    .letterhead {
      display:flex; align-items:center; justify-content:center; min-height:112px; padding:16px 28px;
      border-radius:10px; background:var(--navy); -webkit-print-color-adjust:exact; print-color-adjust:exact;
    }
    .letterhead img { display:block; width:min(440px, 82%); height:auto; }
    .report-heading { padding:22px 0 16px; text-align:center; border-bottom:2px solid var(--blue); }
    .report-heading h1 { margin:0; color:var(--ink); font-size:23px; line-height:1.2; }
    .report-heading p { margin:6px 0 0; color:var(--blue); font-size:14px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
    .report-meta {
      display:flex; flex-wrap:wrap; justify-content:center; gap:8px 22px; margin:14px 0 18px;
      color:var(--muted); font-size:12px;
    }
    .report-meta strong { color:var(--ink); }

    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; table-layout:auto; }
    thead { display:table-header-group; }
    th {
      padding:8px 7px; border:1px solid var(--blue); background:var(--blue); color:#fff; font-size:10px;
      font-weight:800; letter-spacing:.04em; text-align:left; text-transform:uppercase; white-space:nowrap;
      -webkit-print-color-adjust:exact; print-color-adjust:exact;
    }
    td { padding:7px; border:1px solid var(--line); color:#25324c; font-size:10.5px; line-height:1.3; vertical-align:top; }
    tbody tr:nth-child(even) td { background:#f6f8fc; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .number, .date, .status { white-space:nowrap; }
    .email { word-break:break-all; }
    .empty { padding:44px 20px; color:var(--muted); text-align:center; }

    @page { size:A4 landscape; margin:10mm; }
    @media print {
      body { background:#fff; }
      .no-print { display:none !important; }
      .report-sheet { width:100%; margin:0; padding:0; border-radius:0; box-shadow:none; }
      .letterhead { min-height:25mm; padding:3mm 8mm; border-radius:0; }
      .letterhead img { width:92mm; }
      .report-heading { padding:4mm 0 3mm; }
      .report-heading h1 { font-size:16pt; }
      .report-heading p { font-size:9pt; }
      .report-meta { margin:3mm 0 4mm; font-size:7.5pt; }
      .table-wrap { overflow:visible; }
      tr { break-inside:avoid; page-break-inside:avoid; }
      th { padding:1.8mm 1.5mm; font-size:6.5pt; }
      td { padding:1.5mm; font-size:7pt; }
    }
    @media (max-width:720px) {
      .print-toolbar { align-items:flex-start; padding:12px 14px; }
      .toolbar-note { display:none; }
      .report-sheet { width:calc(100% - 20px); margin:14px auto; padding:14px; }
      .letterhead { min-height:84px; padding:12px; }
      .report-heading h1 { font-size:19px; }
    }
  </style>
</head>
<body>
<?php
if (!function_exists('profile_report_h')) {
  function profile_report_h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
  }
}
?>

  <div class="print-toolbar no-print">
    <div class="toolbar-note">Print preview opened in a new tab. Use landscape orientation for best results.</div>
    <div class="toolbar-actions">
      <a class="toolbar-button" href="<?= site_url('Page/profileList') ?>">Back to list</a>
      <button class="toolbar-button primary" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
  </div>

  <main class="report-sheet">
    <header>
      <div class="letterhead">
        <img src="<?= base_url('assets/images/srms-logo-1.png') ?>" alt="FBMSO">
      </div>
      <div class="report-heading">
        <h1>Registered Students</h1>
        <p><?= $statusLabel !== '' ? profile_report_h($statusLabel) . ' Accounts' : 'Student Masterlist' ?></p>
      </div>
      <div class="report-meta">
        <span><strong>Printed:</strong> <?= date('F d, Y \a\t g:i A') ?></span>
        <span><strong>Total records:</strong> <?= number_format(count($rows)) ?></span>
        <?php if ($statusLabel !== ''): ?><span><strong>Status:</strong> <?= profile_report_h($statusLabel) ?></span><?php endif; ?>
        <?php if ($search !== ''): ?><span><strong>Search:</strong> <?= profile_report_h($search) ?></span><?php endif; ?>
      </div>
    </header>

    <?php if (!empty($rows)): ?>
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>#</th><th>Student No.</th><th>Name</th><th>Year &amp; Section</th>
            <th>Email</th><th>Birth Date</th><th>Status</th>
          </tr></thead>
          <tbody>
            <?php foreach ($rows as $index => $row): ?>
              <tr>
                <td class="number"><?= $index + 1 ?></td>
                <td class="number"><?= profile_report_h($row->studno) ?></td>
                <td><?= profile_report_h($row->name) ?></td>
                <td><?= profile_report_h($row->section !== '' ? $row->section : '—') ?></td>
                <td class="email"><?= profile_report_h($row->email !== '' ? $row->email : '—') ?></td>
                <td class="date"><?= profile_report_h($row->bdate !== '' ? $row->bdate : '—') ?></td>
                <td class="status"><?= profile_report_h($row->status) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">No students matched the selected filters.</div>
    <?php endif; ?>
  </main>
</body>
</html>

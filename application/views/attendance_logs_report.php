<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<?php
// Safe escaping helper for PHP 8.1+
if (!function_exists('h')) {
  function h($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
  }
}
?>

<style>
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e2e8f0;
  }

  @media print { .no-print { display:none!important; } }
  .table td,.table th{ font-size:12px; vertical-align:middle }
  .gradient-hr{
    border:0;height:2px;
    background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);
    border-radius:1px;margin:16px 0;
  }

  /* Tablet & small laptop: keep table, allow horizontal scroll so nothing is hidden */
  .table-responsive{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  @media (min-width:768px) and (max-width:1024px){
    .table-responsive table th,
    .table-responsive table td{ white-space:nowrap; }
  }

  /* Phone: convert each row to a card with labels; still shows ALL fields */
  @media (max-width: 767.98px){
    .table-responsive table{ display:block; width:100%; }
    .table-responsive thead{ display:none; }
    .table-responsive tbody{ display:block; }
    .table-responsive tbody tr{
      display:block;
      margin:10px 0;
      border:1px solid var(--line);
      border-radius:12px;
      background:#fff;
      box-shadow:0 2px 10px rgba(15,23,42,.05);
    }
    .table-responsive tbody td{
      display:flex;
      gap:14px;
      justify-content:space-between;
      align-items:flex-start;
      padding:.75rem .9rem;
      border:0;
      border-bottom:1px dashed var(--line);
      white-space:normal;
      word-break:break-word;
    }
    .table-responsive tbody td:last-child{ border-bottom:0; }
    .table-responsive tbody td::before{
      content:attr(data-label);
      font-weight:700;
      color:var(--muted);
      min-width:40%;
      max-width:46%;
      line-height:1.3;
    }
  }

  /* Print: ensure classic table layout */
  @media print{
    .table-responsive table{ display:table; }
    .table-responsive thead{ display:table-header-group; }
    .table-responsive tbody{ display:table-row-group; }
    .table-responsive tr{ display:table-row; box-shadow:none; border:none; }
    .table-responsive td{ display:table-cell; border:1px solid #dee2e6; }
  }
  /* ---------- PRINT: force portrait + classic table ---------- */
@page {
  size: A4 portrait;        /* force portrait */
  margin: 12mm;             /* adjust as you like */
}

@media print {
  /* hide screen-only controls */
  .no-print { display: none !important; }

  /* kill horizontal scrolling/wrapping rules from screen */
  .table-responsive {
    overflow: visible !important;
  }

  /* revert any mobile "card" transforms to normal table */
  .table-responsive table { 
    display: table !important; 
    width: 100% !important;
    table-layout: auto !important;
  }
  .table-responsive thead { 
    display: table-header-group !important; 
  }
  .table-responsive tbody { 
    display: table-row-group !important; 
  }
  .table-responsive tr { 
    display: table-row !important; 
    box-shadow: none !important; 
    border: none !important; 
    margin: 0 !important; 
  }
  .table-responsive td, 
  .table-responsive th {
    display: table-cell !important;
    white-space: normal !important;   /* allow wrapping so it fits */
    word-break: break-word !important;
    border: 1px solid #dee2e6 !important;
    padding: .5rem !important;
    vertical-align: middle !important;
  }

  /* remove mobile labels (data-label) so we don't duplicate headers */
  .table-responsive tbody td::before {
    content: none !important;
  }

  /* optional: smaller text to fit more columns per page */
  .table td, .table th { 
    font-size: 11px !important; 
    line-height: 1.25 !important; 
  }

  /* optional: keep header visible on each printed page */
  thead { 
    display: table-header-group !important; 
  }

  /* remove colored gradient line if you want cleaner print */
  .gradient-hr { 
    height: 1px !important; 
    background: #999 !important; 
  }
}

</style>


<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-12">
              <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0">
                  Attendance Report — <?= h($activity->title ?? 'Activity') ?>
                </h4>
                <div class="no-print">
                  <button class="btn btn-success btn-sm" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                  </button>
                </div>
              </div>
              <hr class="gradient-hr" />
            </div>
          </div>

          <?php
            $section = $filters['section'] ?? '';
            $date    = $filters['date'] ?? '';
            $session = $filters['session'] ?? '';
          ?>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <div class="mb-3">
                    <?php if ($section): ?>
                      <span class="badge badge-info mr-1">Section: <?= h($section) ?></span>
                    <?php endif; ?>
                    <?php if ($date): ?>
                      <span class="badge badge-info mr-1">Date: <?= h($date) ?></span>
                    <?php endif; ?>
                    <?php if ($session): ?>
                      <span class="badge badge-info">Session: <?= strtoupper(h($session)) ?></span>
                    <?php endif; ?>
                  </div>

           <div class="table-responsive">
  <table class="table table-sm table-bordered" id="reportTable">
    <thead class="thead-light">
      <tr>
        <th>#</th>
        <th>Student #</th>
        <th>Name</th>
        <th>Course</th>
        <th>Year</th>
        <th>Section</th>
        <th>Session</th>
        <th>Check-In</th>
        <th>Check-Out</th>
        <th>Duration (min)</th>
        <th>Remarks</th>
        <th>Checked-In By</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($rows as $r):
        $mins = ($r->checked_out_at && $r->checked_in_at)
          ? round((strtotime($r->checked_out_at) - strtotime($r->checked_in_at)) / 60)
          : null; ?>
        <tr>
          <td data-label="#"><?= $i++ ?></td>
          <td data-label="Student #"><?= h($r->student_number) ?></td>
          <td data-label="Name"><?= h($r->student_name) ?></td>
          <td data-label="Course"><?= h($r->course) ?></td>
          <td data-label="Year"><?= h($r->YearLevel) ?></td>
          <td data-label="Section"><?= h($r->section) ?></td>
          <td data-label="Session"><?= strtoupper(h($r->session)) ?></td>
          <td data-label="Check-In"><?= h($r->checked_in_at) ?></td>
          <td data-label="Check-Out"><?= h($r->checked_out_at) ?></td>
          <td data-label="Duration (min)"><?= $mins ?></td>
          <td data-label="Remarks"><?= h($r->remarks) ?></td>
          <td data-label="Checked-In By"><?= h($r->checked_in_by) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


                </div>
              </div>
            </div>
          </div>

        </div><!-- /container-fluid -->
      </div><!-- /content -->
    </div><!-- /content-page -->
  </div><!-- /wrapper -->

  <?php include('includes/themecustomizer.php'); ?>
  <?php include('includes/footer.php'); ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>

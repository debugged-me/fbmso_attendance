<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
  a.text-decoration-none:hover { text-decoration: none; }

  /* ===== KPI stat cards ===== */
  .kpi {
    border: 1px solid var(--up-line, #e6ebf5);
    border-radius: 18px;
    background: var(--up-card, #fff);
    box-shadow: 0 6px 18px rgba(13,27,75,.05);
    transition: transform .22s ease, box-shadow .22s ease;
    margin-bottom: 0;
  }
  .kpi:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(13,27,75,.09); }
  .kpi:active { transform: translateY(-1px) scale(.97); }
  .kpi .card-body { display:flex; align-items:center; justify-content:space-between; padding:20px 22px; height:100%; gap:12px; }
  .kpi .count { font-size:1.6rem; font-weight:800; color:var(--up-ink,#0d1b4b); margin:0; line-height:1; letter-spacing:-.01em; }
  .kpi .label { margin:6px 0 0; color:var(--up-muted,#6b7a99); font-weight:700; font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; }
  .kpi .icon { width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; flex:0 0 auto; }
  .kpi.blue   .icon { background:#eef2ff; color:#4266d4; }
  .kpi.green  .icon { background:#dcfce7; color:#16a34a; }
  .kpi.purple .icon { background:#f3e8ff; color:#8b5cf6; }

  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:stretch; margin-bottom:0; }
  .kpi-grid>a { display:block; height:100%; text-decoration:none; }
  .kpi-grid>a>.card.kpi { height:100%; }
  .kpi .card-body>div:first-child { min-width:0; }
  @media (max-width:575.98px){ .kpi-grid{gap:12px} .kpi .card-body{padding:16px} .kpi .count{font-size:1.35rem} .kpi .icon{width:40px;height:40px;font-size:20px} }

  /* ===== Panels ===== */
  .acct-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); height:100%; }
  .acct-card-head { padding:18px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:linear-gradient(135deg,#1a2a6c,#2a4090); color:#fff; }
  .acct-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:#fff; display:flex; align-items:center; gap:8px; }
  .acct-card-body { padding:22px; }

  .trend-chart { position:relative; height:280px; }
  .sum-chart.skeleton { background:linear-gradient(90deg, #f0f3f8 25%, #e6ebf5 50%, #f0f3f8 75%); background-size:200% 100%; animation:shimmer 1.4s ease-in-out infinite; border-radius:12px; }
  @keyframes shimmer { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }
  .sum-empty { display:grid; place-items:center; height:100%; font-size:.85rem; color:var(--up-muted,#6b7a99); }

  .sum-empty-row td { padding:32px 16px !important; text-align:center; }
  .sum-empty-row .sum-empty-icon { font-size:32px; color:var(--up-muted,#6b7a99); display:block; margin-bottom:8px; opacity:.5; }
  .sum-empty-row .sum-empty-text { font-size:.84rem; color:var(--up-muted,#6b7a99); font-weight:600; }

  /* ===== Recent Scans table ===== */
  .recent-pay-table { border-color:#eef1f5; margin-bottom:0; }
  .recent-pay-table thead th {
    background:#f8f9fc; border-top:0; border-bottom-width:1px;
    font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
    color:var(--up-muted,#6b7a99); padding:12px 16px; white-space:nowrap;
  }
  .recent-pay-table tbody td { padding:12px 16px; font-size:.86rem; color:var(--up-ink,#0d1b4b); vertical-align:middle; border-color:#eef1f5; }
  .recent-pay-table tbody tr:hover { background:#f8fbff; }

  .page-title-box .page-title { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; word-break:break-word; line-height:1.25; }
  @media (max-width:767.98px){
    .page-title-box{display:block}
    .acct-card-body{padding:16px!important}
  }
</style>

<body>
  <div id="wrapper">

    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <?php
          $schoolName    = isset($data18[0]->SchoolName) ? $data18[0]->SchoolName : 'FBMSO';
          $schoolAddress = isset($data18[0]->SchoolAddress) ? $data18[0]->SchoolAddress : '';

          $trendRows = [];
          foreach ((array)($trend ?? []) as $row) {
            $trendRows[] = ['date' => (string)$row->SDate, 'scans' => (int)($row->Scans ?? 0)];
          }
          ?>

          <div class="row">
            <div class="col-12">
              <div class="pl-header">
                <div class="page-title-box">
                  <h4 class="up-page-title"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></h4>
                  <div class="up-page-sub">Committee Dashboard &mdash; QR scanning activity for <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8'); ?></div>
                  <hr class="up-divider" />
                </div>
                <div class="pl-actions">
                  <a href="<?= base_url('activities'); ?>" class="up-btn up-btn-primary">
                    <i class="mdi mdi-qrcode-scan"></i> Scan Student QR
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="kpi-grid">
            <a href="<?= base_url('activities'); ?>" class="text-decoration-none">
              <div class="card kpi blue">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format((int)$todayScans); ?></span></h2>
                    <p class="label mb-0">Today's Scans</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-qrcode-scan"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url('activities'); ?>" class="text-decoration-none">
              <div class="card kpi green">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format((int)$openCount); ?></span></h2>
                    <p class="label mb-0">Open Activities</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-calendar-check-outline"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url('AttendanceLogs'); ?>" class="text-decoration-none">
              <div class="card kpi purple">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format((int)$totalCount); ?></span></h2>
                    <p class="label mb-0">Total Activities</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-clipboard-list-outline"></i></div>
                </div>
              </div>
            </a>
          </div>

          <div class="row mt-4">
            <div class="col-12">
              <div class="acct-card-wrap">
                <div class="acct-card-head">
                  <h5><i class="mdi mdi-chart-areaspline"></i> Scan Activity (Last 14 Days)</h5>
                </div>
                <div class="acct-card-body">
                  <div class="trend-chart sum-chart skeleton"><canvas id="chartTrend"></canvas></div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-12">
              <div class="acct-card-wrap">
                <div class="acct-card-head">
                  <h5><i class="mdi mdi-qrcode-scan"></i> Recent Scans</h5>
                  <a href="<?= base_url('AttendanceLogs'); ?>" class="up-btn up-btn-ghost" style="padding:6px 14px;font-size:.8rem;">
                    View All <i class="mdi mdi-arrow-right"></i>
                  </a>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover table-sm recent-pay-table mb-0">
                    <thead>
                      <tr>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Activity</th>
                        <th>Session</th>
                        <th>Scanned By</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($recentScans)): ?>
                        <?php foreach ($recentScans as $row): ?>
                          <?php
                          $name = trim(($row->LastName ?? '') . ', ' . ($row->FirstName ?? ''), ', ');
                          if ($name === '') $name = $row->student_number ?? '';
                          ?>
                          <tr>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row->checked_in_at)), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($row->activity_title ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars(strtoupper((string)($row->session ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($row->checked_in_by ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No scans recorded yet</span></td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
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
  <script defer src="<?= base_url(); ?>assets/libs/chart-js/Chart.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof Chart === 'undefined') {
        return;
      }

      var trend = <?= json_encode($trendRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      var trendCanvas = document.getElementById('chartTrend');
      if (trendCanvas) {
        var wrap = trendCanvas.closest('.sum-chart');
        if (wrap) wrap.classList.remove('skeleton');
        if (trend.length) {
          var labels = trend.map(function (r) {
            var d = new Date(r.date + 'T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
          });
          var values = trend.map(function (r) { return r.scans; });
          new Chart(trendCanvas.getContext('2d'), {
            type: 'line',
            data: {
              labels: labels,
              datasets: [{
                label: 'Scans',
                data: values,
                borderColor: '#2a4090',
                backgroundColor: 'rgba(66,102,212,.12)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#2a4090',
                fill: true,
                tension: 0.3
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              legend: { display: false },
              scales: {
                yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
              },
              tooltips: {
                callbacks: {
                  label: function (item) { return ' ' + Number(item.yLabel).toLocaleString() + ' scan(s)'; }
                }
              }
            }
          });
        } else {
          trendCanvas.parentNode.innerHTML = '<div class="sum-empty text-muted">No scans in this period yet.</div>';
        }
      }
    });
  </script>
</body>

</html>

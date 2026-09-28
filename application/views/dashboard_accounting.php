<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

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
  .kpi.green  .icon { background:#dcfce7; color:#16a34a; }
  .kpi.blue   .icon { background:#eef2ff; color:#4266d4; }
  .kpi.purple .icon { background:#f3e8ff; color:#8b5cf6; }
  .kpi.orange .icon { background:#fff7ed; color:#f97316; }

  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:stretch; margin-bottom:0; }
  .kpi-grid>a { display:block; height:100%; text-decoration:none; }
  .kpi-grid>a>.card.kpi { height:100%; }
  .kpi .card-body>div:first-child { min-width:0; }
  @media (max-width:575.98px){ .kpi-grid{gap:12px} .kpi .card-body{padding:16px} .kpi .count{font-size:1.35rem} .kpi .icon{width:40px;height:40px;font-size:20px} }

  /* ===== Panels (match uniform dashboard card language) ===== */
  .acct-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); height:100%; }
  .acct-card-head { padding:18px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:linear-gradient(135deg,#1a2a6c,#2a4090); color:#fff; }
  .acct-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:#fff; display:flex; align-items:center; gap:8px; }
  .acct-card-body { padding:22px; }

  .trend-chart { position:relative; height:280px; }
  .trend-chart.is-empty { height:auto; padding:28px 0; }
  @media (max-width:575.98px){ .trend-chart { height:220px; } }
  .sum-chart.skeleton { background:linear-gradient(90deg, #f0f3f8 25%, #e6ebf5 50%, #f0f3f8 75%); background-size:200% 100%; animation:shimmer 1.4s ease-in-out infinite; border-radius:12px; }
  @keyframes shimmer { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }
  .sum-empty { display:grid; place-items:center; height:100%; font-size:.85rem; color:var(--up-muted,#6b7a99); }

  .sum-empty-row td { padding:32px 16px !important; text-align:center; }
  .sum-empty-row .sum-empty-icon { font-size:32px; color:var(--up-muted,#6b7a99); display:block; margin-bottom:8px; opacity:.5; }
  .sum-empty-row .sum-empty-text { font-size:.84rem; color:var(--up-muted,#6b7a99); font-weight:600; }

  /* ===== Recent Payments table ===== */
  .recent-pay-table { border-color:#eef1f5; margin-bottom:0; }
  .recent-pay-table thead th {
    background:#f8f9fc; border-top:0; border-bottom-width:1px;
    font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
    color:var(--up-muted,#6b7a99); padding:12px 16px; white-space:nowrap;
  }
  .recent-pay-table tbody td { padding:12px 16px; font-size:.86rem; color:var(--up-ink,#0d1b4b); vertical-align:middle; border-color:#eef1f5; }
  .recent-pay-table tbody tr:hover { background:#f8fbff; }
  .cash-flow-badge { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:4px 9px; font-size:.7rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }
  .cash-flow-badge.inflow { background:#dcfce7; color:#15803d; }
  .cash-flow-badge.outflow { background:#ffedd5; color:#c2410c; }
  .cash-amount.inflow { color:#15803d; font-weight:800; }
  .cash-amount.outflow { color:#c2410c; font-weight:800; }

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

          $todayAmt = (float)($data12[0]->Amount ?? 0);
          $monthAmt = (float)($data13[0]->Amount ?? 0);
          $yearAmt  = (float)($data14[0]->Amount ?? 0);
          $studeCount = (int)($data7[0]->StudeCount ?? 0);
		  $isAuditor = ((string)$this->session->userdata('level') === 'Auditor');
		  $expenseMonth = (float)($expenseSummary->MonthAmount ?? 0);
		  $expenseYear = (float)($expenseSummary->YearAmount ?? 0);
		  $monthNet = $monthAmt - $expenseMonth;
		  $yearNet = $yearAmt - $expenseYear;

          $trendRows = [];
          foreach ((array)($trend ?? []) as $row) {
            $trendRows[] = ['date' => (string)$row->CDate, 'amount' => (float)($row->Amount ?? 0)];
          }

		  $expenseTrendRows = [];
		  foreach ((array)($expenseTrend ?? []) as $row) {
			$expenseTrendRows[] = ['date' => (string)$row->CDate, 'amount' => (float)($row->Amount ?? 0)];
		  }

		  $cashActivity = [];
		  if ($isAuditor) {
			foreach ((array)($recentPayments ?? []) as $row) {
			  $name = trim(($row->LastName ?? '') . ', ' . ($row->FirstName ?? ''), ', ');
			  if ($name === '') $name = (string)($row->StudentNumber ?? '');
			  $cashActivity[] = [
				'date' => (string)($row->PDate ?? ''),
				'sort' => (string)($row->PDate ?? '') . ' 2 ' . str_pad((string)($row->ID ?? 0), 10, '0', STR_PAD_LEFT),
				'type' => 'inflow',
				'reference' => 'O.R. ' . (string)($row->ORNumber ?? ''),
				'details' => $name,
				'handled_by' => (string)($row->Cashier ?? ''),
				'amount' => (float)($row->Amount ?? 0),
			  ];
			}
			foreach ((array)($recentExpenses ?? []) as $row) {
			  $cashActivity[] = [
				'date' => (string)($row->ExpenseDate ?? ''),
				'sort' => (string)($row->ExpenseDate ?? '') . ' 1 ' . str_pad((string)($row->expensesid ?? 0), 10, '0', STR_PAD_LEFT),
				'type' => 'outflow',
				'reference' => (string)($row->Category ?? 'Expense'),
				'details' => (string)($row->Description ?? ''),
				'handled_by' => (string)($row->Responsible ?? ''),
				'amount' => (float)($row->Amount ?? 0),
			  ];
			}
			usort($cashActivity, function ($a, $b) { return strcmp($b['sort'], $a['sort']); });
			$cashActivity = array_slice($cashActivity, 0, 10);
		  }
          ?>

          <div class="row">
            <div class="col-12">
              <div class="pl-header">
                <div class="page-title-box">
                  <h4 class="up-page-title"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></h4>
				  <div class="up-page-sub"><?= $isAuditor ? 'Auditor Dashboard — read-only cash inflow and outflow' : 'Cashier Dashboard — collections at a glance'; ?> for <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8'); ?></div>
                  <hr class="up-divider" />
                </div>
				<?php if (!$isAuditor): ?>
				  <div class="pl-actions">
					<a href="<?= base_url('Accounting/Payment'); ?>" class="up-btn up-btn-primary">
					  <i class="mdi mdi-plus-circle"></i> New Payment
					</a>
				  </div>
				<?php endif; ?>
              </div>
            </div>
          </div>

		  <?php include('includes/accounting_readonly_notice.php'); ?>

          <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
			<?php if ($isAuditor): ?>
			<a class="nx-stat green" href="<?= base_url('Accounting/collectionMonthly'); ?>">
			  <div class="nx-stat-main">
				<div>
				  <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<span<?= $monthAmt > 0 ? ' data-plugin="counterup"' : ''; ?>><?= number_format($monthAmt, 2); ?></span></div>
				  <div class="nx-stat-label">Cash Inflow · This Month</div>
				</div>
				<div class="nx-stat-icon"><i class="mdi mdi-arrow-down-bold-circle-outline"></i></div>
			  </div>
			  <div class="nx-stat-foot">View collection records <i class="mdi mdi-arrow-right"></i></div>
			</a>
			<a class="nx-stat orange" href="<?= base_url('Accounting/expensesReport'); ?>">
			  <div class="nx-stat-main">
				<div>
				  <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<span<?= $expenseMonth > 0 ? ' data-plugin="counterup"' : ''; ?>><?= number_format($expenseMonth, 2); ?></span></div>
				  <div class="nx-stat-label">Cash Outflow · This Month</div>
				</div>
				<div class="nx-stat-icon"><i class="mdi mdi-arrow-up-bold-circle-outline"></i></div>
			  </div>
			  <div class="nx-stat-foot">View expense records <i class="mdi mdi-arrow-right"></i></div>
			</a>
			<a class="nx-stat <?= $monthNet >= 0 ? 'blue' : 'orange'; ?>" href="<?= base_url('Accounting/ledger'); ?>">
			  <div class="nx-stat-main">
				<div>
				  <div class="nx-stat-num" style="font-size:1.45rem;"><?= $monthNet < 0 ? '-' : ''; ?>&#8369;<span><?= number_format(abs($monthNet), 2); ?></span></div>
				  <div class="nx-stat-label">Net Cash · This Month</div>
				</div>
				<div class="nx-stat-icon"><i class="mdi mdi-scale-balance"></i></div>
			  </div>
			  <div class="nx-stat-foot">Inflow minus outflow <i class="mdi mdi-arrow-right"></i></div>
			</a>
			<a class="nx-stat violet" href="<?= base_url('Accounting/ledger'); ?>">
			  <div class="nx-stat-main">
				<div>
				  <div class="nx-stat-num" style="font-size:1.45rem;"><?= $yearNet < 0 ? '-' : ''; ?>&#8369;<span><?= number_format(abs($yearNet), 2); ?></span></div>
				  <div class="nx-stat-label">Net Cash · This Year</div>
				</div>
				<div class="nx-stat-icon"><i class="mdi mdi-finance"></i></div>
			  </div>
			  <div class="nx-stat-foot">Year-to-date balance <i class="mdi mdi-arrow-right"></i></div>
			</a>
			<?php else: ?>
            <a class="nx-stat green" href="<?= base_url('Accounting/collectionDateRange'); ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<span<?= $todayAmt > 0 ? ' data-plugin="counterup"' : ''; ?>><?= number_format($todayAmt, 2); ?></span></div>
                  <div class="nx-stat-label">Today's Collection</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-trending-up"></i></div>
              </div>
              <div class="nx-stat-foot">Daily breakdown <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat blue" href="<?= base_url('Accounting/collectionMonthly'); ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<span<?= $monthAmt > 0 ? ' data-plugin="counterup"' : ''; ?>><?= number_format($monthAmt, 2); ?></span></div>
                  <div class="nx-stat-label">This Month</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-calendar-month-outline"></i></div>
              </div>
              <div class="nx-stat-foot">Monthly report <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat violet" href="<?= base_url('Accounting/collectionYear'); ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<span<?= $yearAmt > 0 ? ' data-plugin="counterup"' : ''; ?>><?= number_format($yearAmt, 2); ?></span></div>
                  <div class="nx-stat-label">This Year</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-chart-line"></i></div>
              </div>
              <div class="nx-stat-foot">Yearly report <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <div class="nx-stat orange">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($studeCount); ?></span></div>
                  <div class="nx-stat-label">Students This Term</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-account-group-outline"></i></div>
              </div>
              <div class="nx-stat-foot">Enrolled this term <i class="mdi mdi-arrow-right"></i></div>
            </div>
			<?php endif; ?>
          </div>

          <div class="row mt-4">
            <div class="col-12">
              <div class="acct-card-wrap">
                <div class="acct-card-head">
                  <h5><i class="mdi mdi-chart-areaspline"></i> <?= $isAuditor ? 'Cash Inflow vs Outflow' : 'Collection Trend'; ?> (Last 14 Days)</h5>
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
                  <h5><i class="mdi <?= $isAuditor ? 'mdi-swap-vertical-bold' : 'mdi-receipt'; ?>"></i> <?= $isAuditor ? 'Recent Cash Activity' : 'Recent Payments'; ?></h5>
				  <div style="display:flex;gap:8px;flex-wrap:wrap;">
					<?php if ($isAuditor): ?>
					  <a href="<?= base_url('Accounting/paymentAuditLog'); ?>" class="up-btn up-btn-ghost" style="padding:6px 14px;font-size:.8rem;">
						Payment Audit Log <i class="mdi mdi-history"></i>
					  </a>
					<?php endif; ?>
					<a href="<?= base_url($isAuditor ? 'Accounting/ledger' : 'Accounting/Payment'); ?>" class="up-btn up-btn-ghost" style="padding:6px 14px;font-size:.8rem;">
					  <?= $isAuditor ? 'View Ledger' : 'View All'; ?> <i class="mdi mdi-arrow-right"></i>
					</a>
				  </div>
                </div>
                <div class="table-responsive up-rt-host">
                  <table class="table table-bordered table-hover table-sm recent-pay-table up-rt ms-rt-keep mb-0">
					<?php if ($isAuditor): ?>
					<thead>
					  <tr>
						<th>Date</th>
						<th>Flow</th>
						<th>Reference / Category</th>
						<th>Details</th>
						<th>Handled By</th>
						<th class="text-right">Amount</th>
					  </tr>
					</thead>
					<tbody>
					  <?php if (!empty($cashActivity)): ?>
						<?php foreach ($cashActivity as $row): ?>
						  <tr>
							<td data-label="Date"><?= htmlspecialchars(date('M d, Y', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
							<td data-label="Flow"><span class="cash-flow-badge <?= $row['type']; ?>"><i class="mdi <?= $row['type'] === 'inflow' ? 'mdi-arrow-down' : 'mdi-arrow-up'; ?>"></i><?= $row['type'] === 'inflow' ? 'Inflow' : 'Outflow'; ?></span></td>
							<td data-label="Reference / Category"><strong><?= htmlspecialchars($row['reference'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
							<td data-label="Details"><?= htmlspecialchars($row['details'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td data-label="Handled By"><?= htmlspecialchars($row['handled_by'], ENT_QUOTES, 'UTF-8'); ?></td>
							<td data-label="Amount" class="text-right cash-amount <?= $row['type']; ?>"><?= $row['type'] === 'inflow' ? '+' : '-'; ?>&#8369;<?= number_format($row['amount'], 2); ?></td>
						  </tr>
						<?php endforeach; ?>
					  <?php else: ?>
						<tr>
						  <td colspan="6" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-file-search-outline"></i></span><span class="sum-empty-text">No cash activity recorded yet</span></td>
						</tr>
					  <?php endif; ?>
					</tbody>
					<?php else: ?>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>O.R.</th>
                        <th>Student</th>
                        <th>Cashier</th>
                        <th class="text-right">Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($recentPayments)): ?>
                        <?php foreach ($recentPayments as $row): ?>
                          <?php
                          $name = trim(($row->LastName ?? '') . ', ' . ($row->FirstName ?? ''), ', ');
                          if ($name === '') $name = $row->StudentNumber ?? '';
                          ?>
                          <tr>
                            <td data-label="Date"><?= htmlspecialchars(date('M d, Y', strtotime($row->PDate)), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="O.R."><strong><?= htmlspecialchars($row->ORNumber, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td data-label="Student"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Cashier"><?= htmlspecialchars($row->Cashier ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Amount" class="text-right">&#8369;<?= number_format((float)$row->Amount, 2); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-file-search-outline"></i></span><span class="sum-empty-text">No payments recorded yet</span></td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
					<?php endif; ?>
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

      // ---- Cash flow / collection trend ----
      var trend = <?= json_encode($trendRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	  var expenseTrend = <?= json_encode($expenseTrendRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	  var isAuditor = <?= $isAuditor ? 'true' : 'false'; ?>;
      var trendCanvas = document.getElementById('chartTrend');
      if (trendCanvas) {
        var wrap = trendCanvas.closest('.sum-chart');
        if (wrap) wrap.classList.remove('skeleton');
		var hasFlowData = trend.length || (isAuditor && expenseTrend.length);
        if (hasFlowData) {
		  var dateKeys = {};
		  trend.forEach(function (r) { dateKeys[r.date] = true; });
		  if (isAuditor) expenseTrend.forEach(function (r) { dateKeys[r.date] = true; });
		  var dates = Object.keys(dateKeys).sort();
		  var labels = dates.map(function (date) {
			var d = new Date(date + 'T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
          });
		  var toDateMap = function (rows) {
			return rows.reduce(function (map, row) { map[row.date] = Number(row.amount); return map; }, {});
		  };
		  var inflowByDate = toDateMap(trend);
		  var outflowByDate = toDateMap(expenseTrend);
		  var datasets = [{
			label: isAuditor ? 'Cash Inflow' : 'Collections',
			data: dates.map(function (date) { return inflowByDate[date] || 0; }),
			borderColor: '#20a860',
			backgroundColor: 'rgba(32,168,96,.10)',
			borderWidth: 2,
			pointRadius: 3,
			pointBackgroundColor: '#20a860',
			fill: !isAuditor,
			tension: 0.3
		  }];
		  if (isAuditor) {
			datasets.push({
			  label: 'Cash Outflow',
			  data: dates.map(function (date) { return outflowByDate[date] || 0; }),
			  borderColor: '#e07a10',
			  backgroundColor: 'rgba(224,122,16,.10)',
			  borderWidth: 2,
			  pointRadius: 3,
			  pointBackgroundColor: '#e07a10',
			  fill: false,
			  tension: 0.3
			});
		  }
          new Chart(trendCanvas.getContext('2d'), {
            type: 'line',
            data: {
              labels: labels,
			  datasets: datasets
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
			  legend: { display: isAuditor, position: 'bottom' },
              scales: {
                yAxes: [{ ticks: { beginAtZero: true, callback: function (v) { return '₱' + v.toLocaleString(); } } }]
              },
              tooltips: {
                callbacks: {
                  label: function (item) { return ' ₱' + Number(item.yLabel).toLocaleString(undefined, { minimumFractionDigits: 2 }); }
                }
              }
            }
          });
        } else {
          if (wrap) wrap.classList.add('is-empty');
		  trendCanvas.parentNode.innerHTML = '<div class="sum-empty text-muted">' + (isAuditor ? 'No cash activity in this period yet.' : 'No collections in this period yet.') + '</div>';
        }
      }
    });
  </script>
</body>

</html>

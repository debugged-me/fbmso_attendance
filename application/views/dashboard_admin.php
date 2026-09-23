<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
  a.text-decoration-none:hover { text-decoration: none; }

  /* ===== Enrollment summary (white card head, matches nx shell) ===== */
  .enroll-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
  .enroll-card-head { padding:17px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:#fff; color:var(--up-ink,#0d1b4b); }
  .enroll-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
  .enroll-card-head h5 > i { color:#4266d4; font-size:19px; }
  .enroll-card-head .card-widgets a { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:9px; background:#f1f4fb; color:#66728f; }
  .enroll-card-head .card-widgets a:hover { background:#e5eaf6; color:#1b2340; }
  .enroll-card-head .card-widgets a i { color:inherit; }
  .enroll-card-body { padding:22px; }

  .enroll-split { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:20px; align-items:stretch; }
  @media (max-width:1199.98px){ .enroll-split{grid-template-columns:repeat(2,1fr)} }
  @media (max-width:767.98px){ .enroll-split{grid-template-columns:1fr; gap:16px} }

  .enroll-col { display:flex; flex-direction:column; min-width:0; background:#fff; border:1px solid var(--up-line,#e6ebf5); border-radius:14px; padding:16px; box-shadow:0 2px 8px rgba(13,27,75,.04); }
  .sum-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; }
  .sum-head h6 { margin:0; font-weight:800; font-size:.68rem; letter-spacing:.18em; text-transform:uppercase; color:var(--up-muted,#6b7a99); display:flex; align-items:center; gap:8px; }
  .sum-head h6::before { content:''; width:8px; height:8px; border-radius:50%; background:linear-gradient(135deg,var(--up-blue,#2a4090),var(--up-blue-2,#4266d4)); flex-shrink:0; }
  .sum-head .badge { flex:0 0 auto; border-radius:999px; font-size:.72rem; font-weight:700; padding:4px 12px; }
  .sum-head .badge.badge-primary { background:#2f6be6; }
  .sum-head .badge.badge-info { background:#0d97a8; }
  .sum-head .badge.badge-warning { background:#e07a10; }
  .sum-chart { position:relative; height:240px; margin-bottom:10px; }
  .sum-empty { display:grid; place-items:center; height:100%; font-size:.85rem; color:var(--up-muted,#6b7a99); }

  /* Skeleton shimmer placeholder for charts while loading */
  .sum-chart.skeleton { background:linear-gradient(90deg, #f0f3f8 25%, #e6ebf5 50%, #f0f3f8 75%); background-size:200% 100%; animation:shimmer 1.4s ease-in-out infinite; border-radius:12px; }
  @keyframes shimmer { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }
  .sum-filter { margin-bottom:8px; border-radius:10px !important; border:1px solid var(--up-line,#e6ebf5) !important; font-size:.84rem !important; }
  .sum-filter-count { font-size:.72rem; color:var(--up-muted,#6b7a99); font-weight:600; margin-bottom:8px; }
  .sum-scroll { flex:1 1 auto; max-height:250px; overflow-y:auto; border-top:1px solid #eef1f5; }
  .sum-scroll table { margin-bottom:0; }
  .sum-scroll thead th { position:sticky; top:0; z-index:2; background:#f8f9fc; border-top:0; font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--up-muted,#6b7a99); }
  .sum-scroll td { font-size:.86rem; color:var(--up-ink,#0d1b4b); }
  .sum-scroll td.sum-name { max-width:0; width:99%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .sum-scroll td.sum-count { width:1%; white-space:nowrap; }
  .sum-scroll td.sum-count .btn { min-width:48px; border-radius:10px; font-weight:700; font-size:.78rem; }
  .sum-dot { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:7px; vertical-align:middle; flex:0 0 auto; }
  .sum-scroll::-webkit-scrollbar { width:8px; }
  .sum-scroll::-webkit-scrollbar-thumb { background:#cfd8e3; border-radius:8px; }

  /* Empty state — consistent across all summary tables */
  .sum-empty-row td { padding:32px 16px !important; text-align:center; }
  .sum-empty-row .sum-empty-icon { font-size:32px; color:var(--up-muted,#6b7a99); display:block; margin-bottom:8px; opacity:.5; }
  .sum-empty-row .sum-empty-text { font-size:.84rem; color:var(--up-muted,#6b7a99); font-weight:600; }

  /* ===== Announcements (uniform card) ===== */
  .ann-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
  .ann-card-head { padding:17px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; background:#fff; color:var(--up-ink,#0d1b4b); }
  .ann-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
  .ann-card-head h5 > i { color:#4266d4; font-size:19px; }
  .ann-card-head .card-widgets a { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:9px; background:#f1f4fb; color:#66728f; }
  .ann-card-head .card-widgets a:hover { background:#e5eaf6; color:#1b2340; }
  .ann-card-head .card-widgets a i { color:inherit; }
  .ann-card-body { padding:18px 22px 22px; }

  .ann-row { border:1px solid var(--up-line,#e6ebf5); border-radius:14px; padding:16px 18px; background:#fff; box-shadow:0 2px 8px rgba(13,27,75,.04); transition:transform .25s ease, box-shadow .25s ease; margin-bottom:12px; }
  .ann-row:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(13,27,75,.08); }
  .ann-title { font-size:1rem; font-weight:800; color:var(--up-ink,#0d1b4b); margin-bottom:.25rem; padding-left:.6rem; border-left:4px solid var(--up-blue-2,#4266d4); }
  .ann-meta { font-size:.82rem; color:var(--up-muted,#6b7a99); margin-bottom:.5rem; }
  .ann-actions a { font-weight:700; color:var(--up-blue-2,#4266d4); text-decoration:none; font-size:.84rem; }
  .ann-actions a:hover { text-decoration:underline; }

  /* View Details as a proper button with 44px touch target */
  .ann-view-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:10px 20px; border-radius:12px;
    background:var(--up-blue-2,#4266d4); color:#fff !important;
    font-size:.82rem; font-weight:700; text-decoration:none !important;
    min-height:44px; transition:background .18s ease, transform .18s ease;
  }
  .ann-view-btn:hover { background:var(--up-blue,#2a4090); transform:translateY(-1px); text-decoration:none !important; }
  .ann-view-btn:active { transform:translateY(0) scale(.97); }

  /* Expiry date styling */
  .ann-expires { font-weight:600; color:var(--up-muted,#6b7a99); }
  .ann-expires-expired { color:#ef4444; font-weight:700; }

  .modal-body img { max-width:100%; height:auto; border-radius:8px; }
  #viewAnnouncementModal .modal-body { max-height:75vh; overflow:auto; }
  .ann-flex { display:flex; gap:16px; align-items:flex-start; flex-wrap:nowrap; }
  .ann-text { flex:1; font-size:.92rem; line-height:1.6; max-height:60vh; overflow:auto; color:var(--up-ink,#0d1b4b); }
  .ann-aside { width:38%; min-width:260px; }
  .ann-aside img { width:100%; height:auto; border-radius:10px; object-fit:contain; }
  @media (max-width:768px){ .ann-flex{flex-direction:column} .ann-aside{width:100%;min-width:0} .ann-text{max-height:none} }

  .card .table th { font-weight:700; }
  .card .table td, .card .table th { vertical-align:middle; }

  /* ===== Responsive page title ===== */
  .page-title-box .page-title { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; word-break:break-word; line-height:1.25; }
  @media (max-width:767.98px){
    .page-title-box{display:block}
    .page-title-right{float:none!important;margin-top:.5rem}
    .page-title-right .breadcrumb,.page-title-right .badge{white-space:normal!important}
    .enroll-card-body{padding:16px!important}
    .ann-card-body{padding:16px!important}
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
          $nxTz    = new DateTimeZone('Asia/Manila');
          $nxNow   = new DateTime('now', $nxTz);
          $nxHour  = (int)$nxNow->format('G');
          $nxGreet = $nxHour < 12 ? 'Good morning' : ($nxHour < 18 ? 'Good afternoon' : 'Good evening');
          $nxFname = trim((string)$this->session->userdata('fname'));
          if ($nxFname === '') {
            $nxFname = (string)$this->session->userdata('username');
          }
          $nxSchool = !empty($data18) ? $data18[0] : null;

          $SP_count = (int)($data7[0]->StudeCount ?? 0);

          $yl1 = $data[0]  ?? null;
          $yl2 = $data1[0] ?? null;
          $yl3 = $data2[0] ?? null;
          $yl4 = $data3[0] ?? null;

          $yl1Count = (int)($yl1->StudeCount ?? 0);
          $yl2Count = (int)($yl2->StudeCount ?? 0);
          $yl3Count = (int)($yl3->StudeCount ?? 0);
          $yl4Count = (int)($yl4->StudeCount ?? 0);

          $yl1Level = $yl1->YearLevel ?? '1st Year';
          $yl2Level = $yl2->YearLevel ?? '2nd Year';
          $yl3Level = $yl3->YearLevel ?? '3rd Year';
          $yl4Level = $yl4->YearLevel ?? '4th Year';

          $pendingPayCount = (int)($data4[0]->Studecount ?? 0);

          $sy   = $this->session->userdata('sy');
          $sem  = $this->session->userdata('semester');
          $nxOrgName = $nxSchool && !empty($nxSchool->SchoolName)
            ? (string)$nxSchool->SchoolName
            : 'Faculty of Business and Management Student Organization';
          ?>

          <div class="nx-hero">
            <div>
              <span class="page-title" style="display:none;"><?= htmlspecialchars($nxOrgName, ENT_QUOTES, 'UTF-8'); ?></span>
              <h1 class="nx-hello"><?= $nxGreet; ?>, <?= htmlspecialchars($nxFname, ENT_QUOTES, 'UTF-8'); ?> <span class="nx-wave">👋</span></h1>
              <div class="nx-hero-sub"><?= $nxNow->format('l, F j'); ?> &nbsp;&middot;&nbsp; <?= $nxNow->format('g:i A'); ?> &nbsp;&middot;&nbsp; Here&rsquo;s what&rsquo;s happening today.</div>
            </div>
          </div>

          <div class="nx-stats">
            <a class="nx-stat green span2" href="<?= base_url(); ?>Page/profileList">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($SP_count); ?></span></div>
                  <div class="nx-stat-label">Registered Students</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-account-group-outline"></i></div>
              </div>
              <div class="nx-stat-foot">View students <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat blue" href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl1Level) ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($yl1Count); ?></span></div>
                  <div class="nx-stat-label">1st Year</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-numeric-1-box-outline"></i></div>
              </div>
              <div class="nx-stat-foot">View list <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat blue" href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl2Level) ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($yl2Count); ?></span></div>
                  <div class="nx-stat-label">2nd Year</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-numeric-2-box-outline"></i></div>
              </div>
              <div class="nx-stat-foot">View list <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat cyan" href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl3Level) ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($yl3Count); ?></span></div>
                  <div class="nx-stat-label">3rd Year</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-numeric-3-box-outline"></i></div>
              </div>
              <div class="nx-stat-foot">View list <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat green" href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl4Level) ?>">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($yl4Count); ?></span></div>
                  <div class="nx-stat-label">4th Year</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-numeric-4-box-outline"></i></div>
              </div>
              <div class="nx-stat-foot">View list <i class="mdi mdi-arrow-right"></i></div>
            </a>
            <a class="nx-stat orange" href="<?= base_url(); ?>Accounting/paymentAuditLog">
              <div class="nx-stat-main">
                <div>
                  <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($pendingPayCount); ?></span></div>
                  <div class="nx-stat-label">Payment Logs</div>
                </div>
                <div class="nx-stat-icon"><i class="mdi mdi-history"></i></div>
              </div>
              <div class="nx-stat-foot">View payment activity <i class="mdi mdi-arrow-right"></i></div>
            </a>
          </div>
          <?php
          // ---- Enrollment summary: normalise rows so tables and charts always agree ----
          $sumNormalise = function ($rows, $key) {
            $out = array();
            foreach ((array)$rows as $r) {
              $label = trim((string)(isset($r->$key) ? $r->$key : ''));
              if ($label === '') {
                $label = 'Not Set';
              }
              $out[] = array('label' => $label, 'value' => (int)(isset($r->Counts) ? $r->Counts : 0));
            }
            return $out;
          };
          $sumTotal = function ($rows) {
            $t = 0;
            foreach ($rows as $r) {
              $t += $r['value'];
            }
            return $t;
          };

          $courseData  = $sumNormalise(isset($data8) ? $data8 : array(), 'Course');
          $ylData      = $sumNormalise(isset($yearLevelCounts) ? $yearLevelCounts : array(), 'YearLevel');
          $sectionData = $sumNormalise(isset($sectionCounts) ? $sectionCounts : array(), 'Section');

          // Carry the panel's own course/major scope into the drill-down links.
          $sectionScope = '';
          if ($this->input->get('course')) {
            $sectionScope .= '&course=' . urlencode($this->input->get('course'));
          }
          if ($this->input->get('major') !== null) {
            $sectionScope .= '&major=' . urlencode($this->input->get('major'));
          }

          $courseTotal  = $sumTotal($courseData);
          $ylTotal      = $sumTotal($ylData);
          $sectionTotal = $sumTotal($sectionData);
          ?>
          <div class="row mt-4">
            <div class="col-xl-12">
              <div class="enroll-card-wrap">
                <div class="enroll-card-head">
                  <h5><i class="mdi mdi-chart-donut"></i> Student Summary</h5>
                  <div class="d-flex align-items-center" style="gap:8px;">
                    <div class="card-widgets">
                      <a data-toggle="collapse" href="#enrollSummary" role="button" aria-expanded="true" aria-controls="enrollSummary">
                        <i class="mdi mdi-minus"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div id="enrollSummary" class="collapse show">
                  <div class="enroll-card-body">
                    <div class="enroll-split">
                      <div class="enroll-col">
                        <div class="sum-head">
                          <h6 class="text-uppercase text-muted mb-0">By Course</h6>
                          <span class="badge badge-primary">Total: <?= number_format($courseTotal); ?></span>
                        </div>
                        <div class="sum-chart skeleton"><canvas id="chartByCourse"></canvas></div>
                        <div class="sum-scroll">
                          <table class="table table-sm table-hover mb-0" id="tblByCourse">
                            <thead>
                              <tr>
                                <th style="text-align:left">Course</th>
                                <th style="text-align:center">Counts</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (!empty($courseData)) : ?>
                                <?php foreach ($courseData as $row): ?>
                                  <tr>
                                    <td class="sum-name" title="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>">
                                      <span class="sum-dot" data-label="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>"></span><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="sum-count" style="text-align:center">
                                      <a href="<?= base_url(); ?>Page/enrollmentList?by=course&amp;value=<?= urlencode($row['label']); ?>"
                                        class="btn btn-primary btn-xs waves-effect waves-light"
                                        title="View the <?= number_format($row['value']); ?> enrolled student(s)">
                                        <?= number_format($row['value']); ?>
                                      </a>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <tr>
                                  <td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No data available</span></td>
                                </tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- By Major
                      <div class="enroll-col">
                        <h6 class="mb-3 text-uppercase text-muted">By Major</h6>
                        <div class="table-responsive">
                          <table class="table table-sm mb-0">
                            <thead>
                              <tr>
                                <th style="text-align:left">Major</th>
                                <th style="text-align:center">Counts</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (!empty($majorCounts)) : ?>
                                <?php foreach ($majorCounts as $row): ?>
                                  <tr>
                                    <td style="text-align:left;"><?= htmlspecialchars($row->Major ?: 'Not Set'); ?></td>
                                    <td style="text-align:center">
                                      <button type="button" class="btn btn-success btn-xs waves-effect waves-light">
                                        <?= number_format((int)$row->Counts); ?>
                                      </button>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <tr>
                                  <td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No data available</span></td>
                                </tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                      </div> -->
                      <div class="enroll-col">
                        <div class="sum-head">
                          <h6 class="text-uppercase text-muted mb-0">By Year Level</h6>
                          <span class="badge badge-info">Total: <?= number_format($ylTotal); ?></span>
                        </div>
                        <div class="sum-chart skeleton"><canvas id="chartByYearLevel"></canvas></div>
                        <div class="sum-scroll">
                          <table class="table table-sm table-hover mb-0" id="tblByYearLevel">
                            <thead>
                              <tr>
                                <th style="text-align:left">Year Level</th>
                                <th style="text-align:center">Counts</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (!empty($ylData)) : ?>
                                <?php foreach ($ylData as $row): ?>
                                  <tr>
                                    <td class="sum-name" title="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>">
                                      <span class="sum-dot" data-label="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>"></span><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="sum-count" style="text-align:center">
                                      <a href="<?= base_url(); ?>Page/enrollmentList?by=yearlevel&amp;value=<?= urlencode($row['label']); ?>"
                                        class="btn btn-info btn-xs waves-effect waves-light"
                                        title="View the <?= number_format($row['value']); ?> enrolled student(s)">
                                        <?= number_format($row['value']); ?>
                                      </a>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <tr>
                                  <td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No data available</span></td>
                                </tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <div class="enroll-col">
                        <div class="sum-head">
                          <h6 class="text-uppercase text-muted mb-0">By Section</h6>
                          <span class="badge badge-warning">Total: <?= number_format($sectionTotal); ?></span>
                        </div>
                        <?php if (!empty($this->input->get('course')) || !empty($this->input->get('major'))): ?>
                          <div class="mb-2">
                            <?php if ($this->input->get('course')): ?>
                              <span class="badge badge-info">Course: <?= htmlspecialchars($this->input->get('course')); ?></span>
                            <?php endif; ?>
                            <?php if ($this->input->get('major')): ?>
                              <span class="badge badge-secondary">Major: <?= htmlspecialchars($this->input->get('major')); ?></span>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                        <input type="text" class="form-control form-control-sm sum-filter" data-target="#tblBySection" placeholder="Search section..." autocomplete="off">
                        <div class="sum-filter-count" id="sectionFilterCount">Showing <?= count($sectionData); ?> of <?= count($sectionData); ?></div>
                        <div class="sum-chart skeleton"><canvas id="chartBySection"></canvas></div>
                        <div class="sum-scroll">
                          <table class="table table-sm table-hover mb-0" id="tblBySection">
                            <thead>
                              <tr>
                                <th style="text-align:left">Section</th>
                                <th style="text-align:center">Enrollees</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (!empty($sectionData)) : ?>
                                <?php foreach ($sectionData as $row): ?>
                                  <tr>
                                    <td class="sum-name" title="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>">
                                      <span class="sum-dot" data-label="<?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>"></span><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="sum-count" style="text-align:center">
                                      <a href="<?= base_url(); ?>Page/enrollmentList?by=section&amp;value=<?= urlencode($row['label']); ?><?= $sectionScope; ?>"
                                        class="btn btn-warning btn-xs waves-effect waves-light"
                                        title="View the <?= number_format($row['value']); ?> enrolled student(s)">
                                        <?= number_format($row['value']); ?>
                                      </a>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <tr>
                                  <td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No data available</span></td>
                                </tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- 
            <div class="enroll-col">
              <h6 class="mb-3 text-uppercase text-muted">By Sex</h6>
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th style="text-align:left">Sex</th>
                      <th style="text-align:center">Counts</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($data9)) : ?>
                      <?php foreach ($data9 as $row): ?>
                        <tr>
                          <td style="text-align:left;"><?= htmlspecialchars($row->Sex); ?></td>
                          <td style="text-align:center">
                              <button type="button" class="btn btn-success btn-xs waves-effect waves-light">
                                <?= number_format((int)$row->Counts); ?>
                              </button>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-database-off-outline"></i></span><span class="sum-empty-text">No data available</span></td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div> -->

                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-12">
                  <div class="ann-card-wrap">
                    <div class="ann-card-head">
                      <h5><i class="mdi mdi-bullhorn-outline"></i> Announcements</h5>
                      <div class="card-widgets">
                        <a data-toggle="collapse" href="#adminAnnouncements" role="button" aria-expanded="true" aria-controls="adminAnnouncements">
                          <i class="mdi mdi-minus"></i>
                        </a>
                      </div>
                    </div>

                    <div id="adminAnnouncements" class="collapse show">
                      <div class="ann-card-body">
                        <?php if (empty($announcements)): ?>
                          <div style="text-align:center;padding:40px 20px;color:var(--up-muted,#6b7a99);">
                            <i class="mdi mdi-bullhorn-off-outline" style="font-size:42px;display:block;margin-bottom:10px;"></i>
                            No announcements.
                          </div>
                        <?php else: ?>
                          <?php $i = 0;
                          foreach ($announcements as $row): $i++;
                            $modalID  = 'annView' . $i;
                            $title    = $row->title ?? 'Announcement';
                            $message  = $row->message ?? $row->description ?? '';
                            $posted   = !empty($row->datePosted) ? date('F d, Y', strtotime($row->datePosted)) : '';
                            $audience = $row->audience ?? 'All';
                            $imageURL = !empty($row->image) ? base_url('upload/announcements/' . $row->image) : '';
                            $expires  = !empty($row->date_expire) ? date('F d, Y', strtotime($row->date_expire)) : '';
                            $isExpired = !empty($row->date_expire) && strtotime($row->date_expire) < strtotime(date('Y-m-d'));
                          ?>
                            <div class="ann-row">
                              <?php if ($imageURL): ?>
                                <div class="row mb-2">
                                  <div class="col-md-3 text-center">
                                    <img src="<?= $imageURL; ?>" class="img-fluid rounded" style="max-height:100px;" alt="Preview Image">
                                  </div>
                                  <div class="col-md-9">
                                    <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?><?php if ($expires): ?> • <span class="ann-expires<?= $isExpired ? ' ann-expires-expired' : ''; ?>">Expires: <?= $expires; ?></span><?php endif; ?></div>
                                    <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>" class="ann-view-btn"><i class="mdi mdi-eye-outline"></i> View Details</a></div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?><?php if ($expires): ?> • <span class="ann-expires<?= $isExpired ? ' ann-expires-expired' : ''; ?>">Expires: <?= $expires; ?></span><?php endif; ?></div>
                                <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>" class="ann-view-btn"><i class="mdi mdi-eye-outline"></i> View Details</a></div>
                              <?php endif; ?>
                            </div>

                            <div class="modal fade" id="<?= $modalID; ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $modalID; ?>Label" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                  <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;">
                                    <h5 class="modal-title" id="<?= $modalID; ?>Label" style="font-weight:800;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;">
                                      <i class="mdi mdi-close"></i>
                                    </button>
                                  </div>
                                  <div class="modal-body">
                                    <div class="ann-flex">
                                      <div class="ann-text" style="white-space:pre-wrap;"><?= $message; ?></div>
                                      <?php if ($imageURL): ?>
                                        <aside class="ann-aside">
                                          <img src="<?= $imageURL; ?>" alt="Announcement Image">
                                          <div class="text-right mt-2">
                                            <a href="<?= $imageURL; ?>" class="btn btn-outline-info btn-sm" download>
                                              <i class="mdi mdi-download"></i> Download Image
                                            </a>
                                          </div>
                                        </aside>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
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
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/chart-js/Chart.bundle.min.js"></script>
  <script>
    /* ===== Enrollment summary donut charts =====
       Wrapped in DOMContentLoaded so Chart.js (deferred above) is available. */
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof Chart === 'undefined') {
        return;
      }

      // KPI tile hues — keep the donuts on the same palette as the stat cards
      var PALETTE = [
        '#38c982', '#4e8cf6', '#26c2d4', '#f5a623', '#8a68f0', '#f0608a',
        '#20a860', '#2f6be6', '#0d97a8', '#e07a10', '#6746d7', '#d9395f'
      ];
      var OTHERS_COLOR = '#94a3b8';

      var DATA = {
        course: <?= json_encode($courseData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        year: <?= json_encode($ylData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        section: <?= json_encode($sectionData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
      };

      function shorten(text, max) {
        return text.length > max ? text.substring(0, max - 1) + '…' : text;
      }

      // Long lists (sections especially) become an unreadable pie, so keep the
      // biggest slices and fold the tail into a single "Others" slice.
      function condense(items, max) {
        var rows = [];
        for (var i = 0; i < items.length; i++) {
          if (items[i].value > 0) {
            rows.push(items[i]);
          }
        }
        rows.sort(function (a, b) {
          return b.value - a.value;
        });
        if (rows.length <= max) {
          return rows;
        }
        var head = rows.slice(0, max - 1);
        var tail = rows.slice(max - 1);
        var sum = 0;
        for (var j = 0; j < tail.length; j++) {
          sum += tail[j].value;
        }
        head.push({ label: 'Others (' + tail.length + ')', value: sum, others: true });
        return head;
      }

      // Give each table row the same colour its slice got, panel by panel.
      function paintDots(canvas, colorByLabel) {
        var panel = canvas.closest ? canvas.closest('.enroll-col') : null;
        if (!panel) {
          return;
        }
        var dots = panel.querySelectorAll('.sum-dot');
        for (var i = 0; i < dots.length; i++) {
          var label = dots[i].getAttribute('data-label');
          dots[i].style.background = colorByLabel[label] || OTHERS_COLOR;
        }
      }

      function draw(canvasId, items, maxSlices) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) {
          return;
        }
        var wrap = canvas.parentNode;
        if (wrap) { wrap.classList.remove('skeleton'); }
        var rows = condense(items || [], maxSlices);
        if (!rows.length) {
          canvas.parentNode.innerHTML = '<div class="sum-empty text-muted">No data to chart.</div>';
          return;
        }

        var labels = [];
        var values = [];
        var colors = [];
        var colorByLabel = {};
        for (var i = 0; i < rows.length; i++) {
          var color = rows[i].others ? OTHERS_COLOR : PALETTE[i % PALETTE.length];
          labels.push(shorten(rows[i].label, 22));
          values.push(rows[i].value);
          colors.push(color);
          if (!rows[i].others) {
            colorByLabel[rows[i].label] = color;
          }
        }

        paintDots(canvas, colorByLabel);

        var total = values.reduce(function (t, v) {
          return t + v;
        }, 0);

        new Chart(canvas.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              data: values,
              backgroundColor: colors,
              borderColor: '#fff',
              borderWidth: 2,
              hoverBorderColor: '#fff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutoutPercentage: 58,
            animation: { animateScale: true },
            legend: {
              position: 'bottom',
              labels: { boxWidth: 10, padding: 8, fontSize: 11, usePointStyle: true }
            },
            tooltips: {
              callbacks: {
                label: function (item, data) {
                  var value = data.datasets[0].data[item.index] || 0;
                  var pct = total ? Math.round((value / total) * 1000) / 10 : 0;
                  var full = rows[item.index].label;
                  return ' ' + full + ': ' + value.toLocaleString() + ' (' + pct + '%)';
                }
              }
            }
          }
        });
      }

      draw('chartByCourse', DATA.course, 8);
      draw('chartByYearLevel', DATA.year, 8);
      draw('chartBySection', DATA.section, 8);

      // Live filter for the (potentially very long) section list.
      $('.sum-filter').on('keyup search', function () {
        var needle = $.trim($(this).val()).toLowerCase();
        var $table = $($(this).data('target'));
        var total = $table.find('tbody tr').not('.sum-noresult').length;
        var shown = 0;
        $table.find('tbody tr').not('.sum-noresult').each(function () {
          var $row = $(this);
          if ($row.find('td').length < 2) {
            return;
          }
          var hit = needle === '' || $row.find('td.sum-name').text().toLowerCase().indexOf(needle) > -1;
          $row.toggle(hit);
          if (hit) {
            shown++;
          }
        });
        $table.find('tr.sum-noresult').remove();
        if (!shown) {
          $table.find('tbody').append('<tr class="sum-noresult"><td colspan="2" class="sum-empty-row"><span class="sum-empty-icon"><i class="mdi mdi-magnify-close"></i></span><span class="sum-empty-text">No match</span></td></tr>');
        }
        // Update count indicator
        var $count = $('#sectionFilterCount');
        if ($count.length) {
          $count.text('Showing ' + shown + ' of ' + total);
        }
      });
    });
  </script>
  <script>
    $('#viewAnnouncementModal').on('show.bs.modal', function(e) {
      var t = $(e.relatedTarget);
      var title = t.data('title') || '';
      var message = t.data('message') || '';
      var img = t.data('image') || '';
      var hasImg = t.data('hasimage') === 1 || t.data('hasimage') === '1';

      $('#vamTitle').text(title);
      $('#vamMessage').html(message);

      if (hasImg && img) {
        $('#vamImage').attr('src', img);
        $('#vamImageWrap').show();
      } else {
        $('#vamImage').attr('src', '');
        $('#vamImageWrap').hide();
      }
    });
  </script>
</body>

</html>
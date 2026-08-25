<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<style>
  a.text-decoration-none:hover {
    text-decoration: none;
  }

  .kpi {
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    box-shadow: 0 6px 18px rgba(36, 59, 83, .08);
    transition: transform .22s ease, box-shadow .22s ease
  }

  .kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgba(36, 59, 83, .14)
  }

  .kpi .card-body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.25rem
  }

  .kpi .count {
    font-size: 2.0rem;
    font-weight: 800;
    color: #1f2d3d;
    margin: 0;
    line-height: 1
  }

  .kpi .label {
    margin: .15rem 0 0;
    color: #546e7a;
    font-weight: 700;
    letter-spacing: .2px
  }

  .kpi .icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-size: 28px
  }

  .kpi.blue .icon {
    background: rgba(37, 99, 235, .08);
    color: #2563eb
  }

  .kpi.pink .icon {
    background: rgba(236, 72, 153, .10);
    color: #ec4899
  }

  .kpi.purple .icon {
    background: rgba(139, 92, 246, .10);
    color: #8b5cf6
  }

  .kpi.cyan .icon {
    background: rgba(6, 182, 212, .10);
    color: #06b6d4
  }

  .kpi.primary .icon {
    background: rgba(59, 130, 246, .10);
    color: #3b82f6
  }

  .card.announcement-card {
    transition: transform .3s ease, box-shadow .3s ease, border .3s ease;
    border: 1px solid #dee2e6;
    border-radius: 6px
  }

  .card.announcement-card:hover {
    transform: scale(1.03);
    border: 2px solid #007bff;
    box-shadow: 0 8px 20px rgba(0, 123, 255, .2)
  }

  .card.reg-ann {
    border: 1px solid #dee2e6
  }

  .card.reg-ann .card-header {
    background: #17a2b8;
    color: #fff;
    padding: .9rem 1rem
  }

  .card.reg-ann .card-title {
    margin: 0;
    font-weight: 600
  }

  .ann-row {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.0rem;
    background: #fdfdfd;
    box-shadow: 0 3px 10px rgba(0, 0, 0, .05);
    transition: transform .25s ease, box-shadow .25s ease;
    margin-bottom: 12px
  }

  .ann-row:hover {
    transform: scale(1.01);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .1)
  }

  .ann-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #2c3e50;
    margin-bottom: .25rem;
    border-left: 5px solid #007bff;
    padding-left: .5rem
  }

  .ann-meta {
    font-size: .9rem;
    color: #6c757d;
    margin-bottom: .5rem
  }

  .ann-actions a {
    font-weight: 600;
    color: #007bff;
    text-decoration: none
  }

  .ann-actions a:hover {
    text-decoration: underline
  }

  .modal-body img {
    max-width: 100%;
    height: auto;
    border-radius: 6px
  }

  #viewAnnouncementModal .modal-body {
    max-height: 75vh;
    overflow: auto
  }

  .ann-flex {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    flex-wrap: nowrap
  }

  .ann-text {
    flex: 1;
    font-size: 1rem;
    line-height: 1.6;
    max-height: 60vh;
    overflow: auto
  }

  .ann-aside {
    width: 38%;
    min-width: 260px
  }

  .ann-aside img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: contain
  }

  @media (max-width:768px) {
    .ann-flex {
      flex-direction: column
    }

    .ann-aside {
      width: 100%;
      min-width: 0
    }

    .ann-text {
      max-height: none
    }
  }

  .card .table th {
    font-weight: 700;
  }

  .card .table td,
  .card .table th {
    vertical-align: middle;
  }

  .page-title-box .page-title {
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
    word-break: break-word;
    hyphens: auto;
    line-height: 1.25;
  }

  @media (max-width: 767.98px) {
    .page-title-box {
      display: block;
    }

    .page-title-right {
      float: none !important;
      margin-top: .5rem;
    }

    .page-title-right .breadcrumb,
    .page-title-right .badge {
      white-space: normal !important;
    }
  }

  .card.enroll-card {
    border: 1px solid #dee2e6;
  }

  .card.enroll-card .card-header {
    background: #6f42c1;
    color: #fff;
    padding: .9rem 1rem;
  }

  .card.enroll-card .card-title {
    margin: 0;
    font-weight: 600;
  }

  .card.enroll-card .badge-term {
    background: #5a32a6;
  }

  .card.enroll-card .table thead th {
    background: #f8f9fc;
  }

  .card.enroll-card .card-body {
    background: #f7f9fc;
  }

  /* ===== KPI widgets: identical width + identical height ===== */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
    align-items: stretch;
  }

  .kpi-grid>a {
    display: block;
    height: 100%;
  }

  .kpi-grid>a>.card.kpi {
    height: 100%;
    margin-bottom: 0;
  }

  .kpi .card-body {
    height: 100%;
    gap: 12px;
  }

  .kpi .card-body>div:first-child {
    min-width: 0;
  }

  .kpi .icon {
    flex: 0 0 auto;
  }

  @media (max-width: 1699.98px) {
    .kpi .count {
      font-size: 1.75rem;
    }

    .kpi .icon {
      width: 48px;
      height: 48px;
      font-size: 24px;
      border-radius: 12px;
    }
  }

  @media (max-width: 1499.98px) {
    .kpi-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  @media (max-width: 991.98px) {
    .kpi-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .kpi-grid {
      gap: 12px;
    }

    .kpi .card-body {
      padding: .9rem;
    }

    .kpi .count {
      font-size: 1.55rem;
    }

    .kpi .label {
      font-size: .82rem;
    }

    .kpi .icon {
      width: 44px;
      height: 44px;
      font-size: 22px;
      border-radius: 12px;
    }
  }

  /* ===== Enrollment summary panels: equal columns, capped height ===== */
  .enroll-split {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    align-items: stretch;
  }

  @media (max-width: 1199.98px) {
    .enroll-split {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 767.98px) {
    .enroll-split {
      grid-template-columns: minmax(0, 1fr);
      gap: 16px;
    }
  }

  .enroll-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
    border: 1px solid #e9edf2;
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 2px 8px rgba(36, 59, 83, .05);
  }

  .sum-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
  }

  .sum-head h6 {
    margin: 0;
    font-weight: 700;
    letter-spacing: .3px;
  }

  .sum-head .badge {
    flex: 0 0 auto;
  }

  .sum-chart {
    position: relative;
    height: 240px;
    margin-bottom: 10px;
  }

  .sum-empty {
    display: grid;
    place-items: center;
    height: 100%;
    font-size: .85rem;
  }

  .sum-filter {
    margin-bottom: 8px;
  }

  .sum-scroll {
    flex: 1 1 auto;
    max-height: 250px;
    overflow-y: auto;
    border-top: 1px solid #eef1f5;
  }

  .sum-scroll table {
    margin-bottom: 0;
  }

  .sum-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8f9fc;
    border-top: 0;
  }

  .sum-scroll td.sum-name {
    max-width: 0;
    width: 99%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .sum-scroll td.sum-count {
    width: 1%;
    white-space: nowrap;
  }

  .sum-scroll td.sum-count .btn {
    min-width: 48px;
  }

  .sum-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 7px;
    vertical-align: middle;
    flex: 0 0 auto;
  }

  .sum-scroll::-webkit-scrollbar {
    width: 8px;
  }

  .sum-scroll::-webkit-scrollbar-thumb {
    background: #cfd8e3;
    border-radius: 8px;
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
              <div class="page-title-box">
                <h4 class="page-title">
                  <?php echo $data18[0]->SchoolName; ?><br />
                  <small class="text-muted"><?php echo $data18[0]->SchoolAddress; ?></small>
                </h4>

                <div class="page-title-right">
                  <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                      <span class="badge badge-purple mb-3">
                        Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></b>
                      </span>
                    </li>
                  </ol>
                </div>

                <div class="clearfix"></div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;">
              </div>
            </div>
          </div>

          <?php
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

          $sy   = $this->session->userdata('sy');
          $sem  = $this->session->userdata('semester');
          ?>
          <div class="kpi-grid">
            <a href="<?= base_url(); ?>Page/profileList" class="text-decoration-none kpi-span-2">
              <div class="card kpi blue">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1">
                      <span data-plugin="counterup"><?= number_format($SP_count); ?></span>
                    </h2>
                    <p class="label mb-0">Registered Students</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-layers-plus"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl1Level) ?>" class="text-decoration-none">
              <div class="card kpi pink">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format($yl1Count); ?></span></h2>
                    <p class="label mb-0">1st Year</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-monitor-lock"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl2Level) ?>" class="text-decoration-none">
              <div class="card kpi purple">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format($yl2Count); ?></span></h2>
                    <p class="label mb-0">2nd Year</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-file-eye-outline"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl3Level) ?>" class="text-decoration-none">
              <div class="card kpi cyan">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format($yl3Count); ?></span></h2>
                    <p class="label mb-0">3rd Year</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-pen-lock"></i></div>
                </div>
              </div>
            </a>
            <a href="<?= base_url(); ?>Masterlist/byGradeYL?sy=<?= urlencode($sy) ?>&sem=<?= urlencode($sem) ?>&yearlevel=<?= urlencode($yl4Level) ?>" class="text-decoration-none">
              <div class="card kpi primary">
                <div class="card-body">
                  <div>
                    <h2 class="count mb-1"><span data-plugin="counterup"><?= number_format($yl4Count); ?></span></h2>
                    <p class="label mb-0">4th Year</p>
                  </div>
                  <div class="icon"><i class="mdi mdi-cast-education"></i></div>
                </div>
              </div>
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
              <div class="card enroll-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <div>
                    <h5 class="card-title mb-0" style="color: white;">ENROLLMENT SUMMARY</h5>
                    <small class="badge badge-term badge-light text-white">
                      <?= htmlspecialchars($sem ?? $this->session->userdata('semester')); ?>,
                      SY <?= htmlspecialchars($sy ?? $this->session->userdata('sy')); ?>
                    </small>
                  </div>
                  <div class="card-widgets">
                    <a data-toggle="collapse" href="#enrollSummary" role="button" aria-expanded="true" aria-controls="enrollSummary">
                      <i class="mdi mdi-minus text-white"></i>
                    </a>
                  </div>
                </div>
                <div id="enrollSummary" class="collapse show">
                  <div class="card-body">
                    <div class="enroll-split">
                      <div class="enroll-col">
                        <div class="sum-head">
                          <h6 class="text-uppercase text-muted mb-0">By Course</h6>
                          <span class="badge badge-primary">Total: <?= number_format($courseTotal); ?></span>
                        </div>
                        <div class="sum-chart"><canvas id="chartByCourse"></canvas></div>
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
                                  <td colspan="2" class="text-muted text-center">No data.</td>
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
                                  <td colspan="2" class="text-muted text-center">No data.</td>
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
                        <div class="sum-chart"><canvas id="chartByYearLevel"></canvas></div>
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
                                  <td colspan="2" class="text-muted text-center">No data.</td>
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
                        <div class="sum-chart"><canvas id="chartBySection"></canvas></div>
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
                                  <td colspan="2" class="text-muted text-center">No data.</td>
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
                      <tr><td colspan="2" class="text-muted text-center">No data.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div> -->

                    </div>
                  </div>
                </div>

              </div>


              <div class="row mt-4">
                <div class="col-xl-12">
                  <div class="card reg-ann">
                    <div class="card-header">
                      <div class="card-widgets">
                        <a data-toggle="collapse" href="#adminAnnouncements" role="button" aria-expanded="true" aria-controls="adminAnnouncements">
                          <i class="mdi mdi-minus text-white"></i>
                        </a>
                      </div>
                      <h5 class="card-title mb-0" style="color: white;">ANNOUNCEMENT</h5>
                    </div>

                    <div id="adminAnnouncements" class="collapse show">
                      <div class="card-body">
                        <?php if (empty($announcements)): ?>
                          <div class="text-muted">No announcements.</div>
                        <?php else: ?>
                          <?php $i = 0;
                          foreach ($announcements as $row): $i++;
                            $modalID  = 'annView' . $i;
                            $title    = $row->title ?? 'Announcement';
                            $message  = $row->message ?? $row->description ?? '';
                            $posted   = !empty($row->datePosted) ? date('F d, Y', strtotime($row->datePosted)) : '';
                            $audience = $row->audience ?? 'All';
                            $imageURL = !empty($row->image) ? base_url('upload/announcements/' . $row->image) : '';
                          ?>
                            <div class="ann-row">
                              <?php if ($imageURL): ?>
                                <div class="row mb-2">
                                  <div class="col-md-3 text-center">
                                    <img src="<?= $imageURL; ?>" class="img-fluid rounded" style="max-height:100px;" alt="Preview Image">
                                  </div>
                                  <div class="col-md-9">
                                    <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>">View Details</a></div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="ann-title"><i class="mdi mdi-bullhorn-outline mr-1 text-primary"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="ann-meta">Posted on <?= $posted; ?> • Audience: <?= htmlspecialchars($audience, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="ann-actions"><a href="#" data-toggle="modal" data-target="#<?= $modalID; ?>">View Details</a></div>
                              <?php endif; ?>
                            </div>

                            <div class="modal fade" id="<?= $modalID; ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $modalID; ?>Label" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                  <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="<?= $modalID; ?>Label"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
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
            </div>
          </div>

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
  <script src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
  <script src="<?= base_url(); ?>assets/libs/chart-js/Chart.bundle.min.js"></script>
  <script>
    /* ===== Enrollment summary donut charts ===== */
    (function () {
      if (typeof Chart === 'undefined') {
        return;
      }

      var PALETTE = [
        '#2563eb', '#8b5cf6', '#ec4899', '#06b6d4', '#f59e0b',
        '#10b981', '#ef4444', '#6366f1', '#84cc16', '#f97316',
        '#0ea5e9', '#a855f7'
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
        var shown = 0;
        $table.find('tbody tr').each(function () {
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
          $table.find('tbody').append('<tr class="sum-noresult"><td colspan="2" class="text-muted text-center">No match.</td></tr>');
        }
      });
    })();
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
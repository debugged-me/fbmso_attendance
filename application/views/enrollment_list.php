<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
  .pl-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; flex-wrap:wrap; margin-bottom:16px;
  }
  .pl-header .page-title-box { flex:1 1 auto; margin:0; }
  .pl-header .page-title-box .up-divider { margin:10px 0 0; }
  .pl-header .pl-actions { flex:0 0 auto; align-self:center; display:flex; flex-wrap:wrap; gap:10px; }

  .drill-meta { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:16px; }
  .drill-meta .badge { font-size:.78rem; font-weight:700; }
  .drill-meta .up-btn { margin-left:auto; }

  .resp-table thead th {
    background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
    padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
  }
  .resp-table tbody td {
    padding:14px 16px; vertical-align:middle; font-size:.86rem; color:#0d1b4b;
    border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
  }
  .resp-table tbody tr:hover { background:#f8faff !important; }
  .resp-table tbody tr:last-child td { border-bottom:none !important; }

  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter,
  .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
  .dataTables_wrapper .dataTables_filter input {
    border-radius:10px !important; border:1px solid #e6ebf5 !important;
    padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important;
  }
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

  .al-empty { text-align:center; padding:48px 20px; color:#6b7a99; }
  .al-empty i { font-size:42px; display:block; margin-bottom:10px; color:#9aa5b8; }

  @media (max-width:767.98px) {
    .pl-header { flex-direction:column; gap:10px; }
    .pl-header .pl-actions { align-self:flex-start; }
    .drill-meta .up-btn { margin-left:0; }
  }
</style>

<body class="masterlist-page">
  <div id="wrapper">

    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-12">
              <div class="pl-header">
                <div class="page-title-box">
                  <h4 class="up-page-title">
                    <?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>:
                    <?= htmlspecialchars($groupValue, ENT_QUOTES, 'UTF-8'); ?>
                  </h4>
                  <div class="up-page-sub">Enrolled Students</div>
                  <hr class="up-divider" />
                </div>
                <div class="pl-actions">
                  <a href="<?= base_url(); ?>Page/admin" class="up-btn up-btn-ghost">
                    <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-account-group-outline"></i> Enrolled Students</h4>
                  <div class="drill-meta" style="margin:0;">
                    <span class="badge badge-purple">
                      <?= htmlspecialchars($this->session->userdata('semester'), ENT_QUOTES, 'UTF-8'); ?>,
                      SY <?= htmlspecialchars($this->session->userdata('sy'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="badge badge-success"><?= number_format(count($data)); ?> enrolled</span>
                    <?php if (!empty($filterCourse)): ?>
                      <span class="badge badge-info">Course: <?= htmlspecialchars($filterCourse, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($filterMajor)): ?>
                      <span class="badge badge-secondary">Major: <?= htmlspecialchars($filterMajor, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="up-card-body" style="padding:0 !important;">

                  <?php if (empty($data)): ?>
                    <div class="al-empty">
                      <i class="mdi mdi-account-off-outline"></i>
                      No enrolled students found for this <?= strtolower($groupLabel); ?>.
                    </div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table id="datatable-buttons" class="table table-striped dt-responsive nowrap resp-table" style="width:100%">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th>Sex</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Section</th>
                            <th class="text-center">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $i = 0;
                          foreach ($data as $row): $i++;
                            $ln = trim((string)($row->LastName ?? ''));
                            $fn = trim((string)($row->FirstName ?? ''));
                            $mn = trim((string)($row->MiddleName ?? ''));
                            $name = trim($ln . ($ln && $fn ? ', ' : '') . $fn . ($mn ? ' ' . $mn : ''));
                            $studno = (string)($row->StudentNumber ?? '');
                            if ($name === '') {
                              $name = $studno;
                            }
                            $courseCell = trim((string)($row->Course ?? ''));
                            if (!empty($row->Major)) {
                              $courseCell .= ' — ' . $row->Major;
                            }
                          ?>
                            <tr>
                              <td data-label="#"><?= $i; ?></td>
                              <td data-label="Student No."><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Sex"><?= htmlspecialchars((string)($row->Sex ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Course"><?= htmlspecialchars($courseCell !== '' ? $courseCell : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Year Level"><?= htmlspecialchars(trim((string)($row->YearLevel ?? '')) !== '' ? $row->YearLevel : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Section"><?= htmlspecialchars(trim((string)($row->Section ?? '')) !== '' ? $row->Section : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td data-label="Action" class="text-center">
                                <a href="<?= base_url(); ?>Page/studentsprofile?id=<?= urlencode($studno); ?>" class="up-btn up-btn-ghost up-btn-sm">
                                  <i class="mdi mdi-face-profile"></i> Profile
                                </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>

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
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>

</html>

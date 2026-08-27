<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<style>
  .drill-head .page-title {
    white-space: normal !important;
    word-break: break-word;
    line-height: 1.25;
  }

  .drill-meta .badge {
    font-size: .8rem;
    margin-right: .35rem;
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
              <div class="page-title-box drill-head">
                <h4 class="page-title">
                  <?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>:
                  <?= htmlspecialchars($groupValue, ENT_QUOTES, 'UTF-8'); ?>
                </h4>
                <div class="page-title-right">
                  <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                      <a href="<?= base_url(); ?>Page/admin">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Enrolled Students</li>
                  </ol>
                </div>
                <div class="clearfix"></div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body">

                  <div class="drill-meta mb-3">
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
                    <a href="<?= base_url(); ?>Page/admin" class="btn btn-light btn-sm float-right">
                      <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                    </a>
                  </div>

                  <?php if (empty($data)): ?>
                    <div class="text-muted">No enrolled students found for this <?= strtolower($groupLabel); ?>.</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
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
                              <td><?= $i; ?></td>
                              <td><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?= htmlspecialchars((string)($row->Sex ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?= htmlspecialchars($courseCell !== '' ? $courseCell : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?= htmlspecialchars(trim((string)($row->YearLevel ?? '')) !== '' ? $row->YearLevel : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?= htmlspecialchars(trim((string)($row->Section ?? '')) !== '' ? $row->Section : 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                              <td class="text-center">
                                <a href="<?= base_url(); ?>Page/studentsprofile?id=<?= urlencode($studno); ?>" class="text-success">
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

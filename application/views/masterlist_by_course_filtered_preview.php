<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body class="masterlist-page">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('includes/top-nav-bar.php'); ?>
        <!-- End Topbar -->

        <!-- Left Sidebar -->
        <?php include('includes/sidebar.php'); ?>
        <!-- Left Sidebar End -->

        <!-- Start Page Content -->
        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <?php echo $this->input->get('yearLevel'); ?> Year -
                                    <?php echo $this->input->get('course'); ?>
                                </h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item">SY: <?php echo $this->input->get('sy'); ?>, Semester: <?php echo $this->input->get('semester'); ?></li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Student No.</th>
                                                <th>Student's Name</th>
                                                <th>Section</th>
                                                <th>Course</th>
                                                <th>Year Level</th>
                                            </tr>
                                        </thead>
                                     <tbody>
  <?php foreach ($data as $row): ?>
    <?php
      $ln = trim($row->LastName ?? '');
      $fn = trim($row->FirstName ?? '');
      $mn = trim($row->MiddleName ?? '');
      $fullname = trim(($ln ? $ln : '') . ($ln || $fn ? ', ' : '') . ($fn ? $fn : '') . ($mn ? ' ' . $mn : ''));
      if ($fullname === '' && !empty($row->StudentNumber)) $fullname = $row->StudentNumber;

      $studno = $row->StudentNumber ?? '';
      $bdate  = !empty($row->birthDate) ? $row->birthDate : 'â€”';
      $yl     = $row->yearLevel ?? '';
      $sec    = $row->section ?? '';
      $stat   = $row->signupStatus ?? '';
    ?>
    <tr>
      <td>
        <?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
        <span class="badge badge-warning ml-2">Signup</span>
        <?php if ($yl || $sec): ?>
          <div class="text-muted small"><?= htmlspecialchars("$yl $sec", ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($stat): ?>
          <div class="text-muted small">Status: <?= htmlspecialchars($stat, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($studno, ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?= htmlspecialchars($bdate, ENT_QUOTES, 'UTF-8'); ?></td>
      <td style="text-align:center">
        <a href="<?= base_url(); ?>Page/studentsprofile?id=<?= urlencode($studno); ?>" class="text-success">
          <i class="mdi mdi-face-profile"></i> Profile
        </a>&nbsp;&nbsp;&nbsp;&nbsp;

        <!-- If you want to delete signup (not profile), route to a signup delete action -->
        <a href="<?= base_url(); ?>Page/deleteSignup?id=<?= urlencode($studno); ?>"
           data-ui-confirm="This removes the student's signup record. This cannot be undone."
           data-ui-confirm-title="Delete this signup record?"
           data-ui-confirm-ok="Delete signup"
           class="text-danger">
          <i class="mdi mdi-delete-empty-outline"></i> Delete
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Start -->
        <?php include('includes/footer.php'); ?>
        <!-- Footer End -->

    </div>

</body>

</html>


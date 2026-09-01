<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<?php include('includes/chat_modal.php'); ?>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">
        <div class="page-title-box">
          <h4 class="page-title"><i class="ion ion-ios-qr-scanner"></i> Activity Check-in</h4>
        </div>

<?php
  // ----- Normalize $result (supports object OR array) -----
  $r = $result ?? [];
  if (is_object($r)) {
    $r = [
      'ok'      => isset($r->ok) ? $r->ok : null,
      'mode'    => isset($r->mode) ? $r->mode : null,
      'message' => isset($r->message) ? $r->message : null,
      'session' => isset($r->session) ? $r->session : null,
    ];
  }
  $ok   = !empty($r['ok']);
  $mode = strtolower((string)($r['mode'] ?? ''));
  $msg  = (string)($r['message'] ?? '');

  // treat these modes as success (admin scanner uses them)
  $successModes = ['created','checked_in','checked_out'];
?>

        <div class="card"><div class="card-body">
          <?php if ($ok && in_array($mode, $successModes, true)): ?>
            <div class="alert alert-success">
              . Attendance <?=
                $mode === 'checked_out' ? 'check-out recorded' :
                ($mode === 'checked_in' ? 'recorded' : 'recorded')
              ?> for <strong><?= htmlspecialchars($student_number ?? '') ?></strong>
              <?= !empty($r['session']) ? ' • Session: <b>'.htmlspecialchars($r['session']).'</b>' : '' ?>.
            </div>

          <?php elseif ($ok && $mode === 'duplicate'): ?>
            <div class="alert alert-warning">
              • You are already recorded for this session.
              <?= $msg ? ' <small class="text-muted">'.htmlspecialchars($msg).'</small>' : '' ?>
            </div>

          <?php else: ?>
            <div class="alert alert-danger">
              × Could not record attendance. <?= htmlspecialchars($msg ?: 'Please try again or contact the organizer.') ?>
            </div>
          <?php endif; ?>

          <a href="<?= site_url('student/my_qr'); ?>" class="btn btn-primary">See Attendance</a>
        </div></div>
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
<script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
<script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
<script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>
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
</body>
</html>

<?php
// dashboard_SuperAdmin.php
defined('BASEPATH') or exit('No direct script access allowed');
if (!function_exists('super_audit_e')) {
    function super_audit_e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<body>

    <div id="wrapper">

        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex justify-content-between align-items-center">
                                <div>
                                    <?php
                                    $schoolName    = '';
                                    $schoolAddress = '';

                                    if (!empty($data) && isset($data[0])) {
                                        $schoolName    = !empty($data[0]->SchoolName) ? $data[0]->SchoolName : '';
                                        $schoolAddress = !empty($data[0]->SchoolAddress) ? $data[0]->SchoolAddress : '';
                                    }
                                    ?>
                                    <h4 class="page-title mb-0">
                                        <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </h4>
                                </div>
                                <a href="<?= base_url('Securityadmin/audit_trail'); ?>" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-history"></i> Open full audit trail
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php $audit_summary = isset($audit_summary) ? $audit_summary : array(); ?>
                    <div class="row">
                        <?php
                        $summaryCards = array(
                            array('Events today', (int)($audit_summary['events_today'] ?? 0), 'mdi-pulse', 'primary'),
                            array('Deletes · 30 days', (int)($audit_summary['deletions'] ?? 0), 'mdi-delete-alert-outline', 'danger'),
                            array('Failed / denied · 30 days', (int)($audit_summary['failed_or_denied'] ?? 0), 'mdi-shield-alert-outline', 'warning'),
                            array('Active actors · 30 days', (int)($audit_summary['active_actors'] ?? 0), 'mdi-account-group-outline', 'info'),
                        );
                        foreach ($summaryCards as $card):
                        ?>
                        <div class="col-xl-3 col-sm-6">
                            <div class="card shadow-sm">
                                <div class="card-body d-flex align-items-center">
                                    <div class="rounded-circle bg-<?= super_audit_e($card[3]); ?> text-white d-flex align-items-center justify-content-center mr-3" style="width:46px;height:46px;min-width:46px">
                                        <i class="mdi <?= super_audit_e($card[2]); ?>" style="font-size:23px"></i>
                                    </div>
                                    <div><div class="text-muted small"><?= super_audit_e($card[0]); ?></div><h3 class="mb-0"><?= number_format($card[1]); ?></h3></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row">
                        <div class="col-xl-5">
                            <div class="card shadow-sm h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div><i class="mdi mdi-delete-alert-outline text-danger"></i> <strong>Recent deletions</strong></div>
                                    <a href="<?= base_url('Securityadmin/audit_trail?action=delete'); ?>" class="small">View all</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent_deletions)): ?>
                                        <div class="text-center text-muted py-5">No deletion events have been recorded.</div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                        <?php foreach ($recent_deletions as $event): ?>
                                            <a class="list-group-item list-group-item-action" href="<?= base_url('Securityadmin/audit_trail?action=delete&q=' . urlencode((string)($event['record_pk'] ?: $event['username']))); ?>">
                                                <div class="d-flex justify-content-between">
                                                    <strong class="text-danger"><?= super_audit_e($event['username'] ?: 'Unknown user'); ?></strong>
                                                    <small class="text-muted"><?= super_audit_e(date('M d, h:i A', strtotime($event['event_time']))); ?></small>
                                                </div>
                                                <div class="mt-1"><?= super_audit_e($event['description'] ?: 'Deleted a record'); ?></div>
                                                <small class="text-muted"><?= super_audit_e($event['actor_level']); ?> · <?= super_audit_e($event['table_name'] ?: $event['module']); ?><?= !empty($event['record_pk']) ? ' · ID ' . super_audit_e($event['record_pk']) : ''; ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-7 mt-3 mt-xl-0">
                            <div class="card shadow-sm h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div><i class="mdi mdi-history text-primary"></i> <strong>Latest monitored activity</strong></div>
                                    <a href="<?= base_url('Securityadmin/audit_trail'); ?>" class="small">Open audit trail</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="thead-light"><tr><th>Time</th><th>Actor</th><th>Role</th><th>Action</th><th>Area</th><th>Result</th></tr></thead>
                                        <tbody>
                                        <?php if (empty($recent_audit)): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-5">No monitored activity has been recorded.</td></tr>
                                        <?php else: foreach ($recent_audit as $event):
                                            $failed = (int)$event['succeeded'] !== 1;
                                            $isDelete = strpos(strtolower((string)$event['action']), 'delete') !== false;
                                        ?>
                                            <tr>
                                                <td class="text-nowrap"><small><?= super_audit_e(date('M d, h:i A', strtotime($event['event_time']))); ?></small></td>
                                                <td><strong><?= super_audit_e($event['username'] ?: 'Unknown'); ?></strong></td>
                                                <td><span class="badge badge-dark"><?= super_audit_e($event['actor_level']); ?></span></td>
                                                <td><span class="badge badge-<?= $isDelete ? 'danger' : ($failed ? 'warning' : 'primary'); ?>"><?= super_audit_e(strtoupper(str_replace('_', ' ', $event['action']))); ?></span></td>
                                                <td><?= super_audit_e($event['module']); ?></td>
                                                <td><span class="badge badge-<?= $failed ? 'danger' : 'success'; ?>"><?= $failed ? 'FAILED' : 'OK'; ?></span></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1"><i class="mdi mdi-shield-search-outline"></i> Investigation tools</h5>
                                        <div class="text-muted">Review suspicious devices, IP addresses, and sign-in attempts.</div>
                                    </div>
                                    <div class="mt-2 mt-md-0">
                                        <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-shield-account"></i> Security dashboard</a>
                                        <a href="<?= base_url('Securityadmin/login_activity'); ?>" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-login-variant"></i> Login activity</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- container-fluid -->
            </div><!-- content -->

            <?php include('includes/footer.php'); ?>
        </div><!-- content-page -->

    </div><!-- wrapper -->

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

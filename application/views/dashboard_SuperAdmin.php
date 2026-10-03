<?php
// dashboard_SuperAdmin.php — system overview, nx-shell design language
// (same greeting hero + stat tiles + uniform cards as dashboard_admin.php)
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
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    /* ===== Uniform cards (same language as the admin dashboard) ===== */
    .sa-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
    .sa-card-head { padding:17px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:#fff; color:var(--up-ink,#0d1b4b); }
    .sa-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
    .sa-card-head h5 > i { color:#4266d4; font-size:19px; }
    .sa-card-head .sa-head-link { font-size:.82rem; font-weight:700; color:var(--up-blue-2,#4266d4); text-decoration:none; }
    .sa-card-head .sa-head-link:hover { text-decoration:underline; }
    .sa-card-body { padding:20px 22px; }

    /* Deletion feed */
    .sa-feed { max-height:392px; overflow-y:auto; }
    .sa-feed::-webkit-scrollbar { width:8px; }
    .sa-feed::-webkit-scrollbar-thumb { background:#cfd8e3; border-radius:8px; }
    .sa-feed-item { display:block; padding:14px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); text-decoration:none !important; transition:background .15s ease; }
    .sa-feed-item:last-child { border-bottom:0; }
    .sa-feed-item:hover { background:var(--up-soft,#f5f7fc); }
    .sa-feed-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .sa-feed-who { font-weight:700; font-size:.88rem; color:#d9395f; }
    .sa-feed-time { font-size:.74rem; color:var(--up-muted,#6b7a99); white-space:nowrap; }
    .sa-feed-desc { font-size:.85rem; color:var(--up-ink,#0d1b4b); margin:3px 0 2px; }
    .sa-feed-meta { font-size:.74rem; color:var(--up-muted,#6b7a99); }

    /* Activity table */
    .sa-table-scroll { max-height:392px; overflow-y:auto; }
    .sa-table-scroll::-webkit-scrollbar { width:8px; }
    .sa-table-scroll::-webkit-scrollbar-thumb { background:#cfd8e3; border-radius:8px; }
    .sa-table-scroll thead th { position:sticky; top:0; z-index:2; background:#f8f9fc; border-top:0; font-size:.7rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--up-muted,#6b7a99); }
    .sa-table-scroll td { font-size:.84rem; color:var(--up-ink,#0d1b4b); vertical-align:middle; }
    .sa-badge { display:inline-block; border-radius:999px; font-size:.68rem; font-weight:700; letter-spacing:.05em; padding:4px 11px; white-space:nowrap; }
    .sa-badge.dark { background:#eef1f8; color:#3b4769; }
    .sa-badge.blue { background:#e3ecff; color:#2f6be6; }
    .sa-badge.red { background:#ffe4e9; color:#d9395f; }
    .sa-badge.amber { background:#fdf0dc; color:#e07a10; }
    .sa-badge.green { background:#dcf5e8; color:#15803d; }

    /* Quick tools */
    .sa-tools { display:flex; flex-wrap:wrap; gap:10px; }
    .sa-tool { display:inline-flex; align-items:center; gap:9px; padding:12px 18px; border-radius:12px; background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); color:var(--up-ink,#0d1b4b); font-size:.84rem; font-weight:700; text-decoration:none !important; transition:all .18s ease; }
    .sa-tool i { font-size:17px; color:var(--up-blue-2,#4266d4); }
    .sa-tool:hover { background:#eef2fb; border-color:#c9d6f5; transform:translateY(-1px); box-shadow:0 6px 14px rgba(42,64,144,.12); color:var(--up-blue,#2a4090); }

    .sa-empty { padding:44px 20px; text-align:center; color:var(--up-muted,#6b7a99); }
    .sa-empty i { font-size:32px; display:block; margin-bottom:8px; opacity:.45; }
    .sa-empty span { font-size:.85rem; font-weight:600; }

    @media (max-width:767.98px){
        .sa-card-body { padding:16px; }
        .sa-feed, .sa-table-scroll { max-height:320px; }
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

                    $schoolName    = '';
                    $schoolAddress = '';
                    if (!empty($data) && isset($data[0])) {
                        $schoolName    = !empty($data[0]->SchoolName) ? $data[0]->SchoolName : '';
                        $schoolAddress = !empty($data[0]->SchoolAddress) ? $data[0]->SchoolAddress : '';
                    }

                    $audit_summary = isset($audit_summary) ? $audit_summary : array();
                    $eventsToday   = (int)($audit_summary['events_today'] ?? 0);
                    $deletions30d  = (int)($audit_summary['deletions'] ?? 0);
                    $failedDenied  = (int)($audit_summary['failed_or_denied'] ?? 0);
                    ?>

                    <!-- Hero -->
                    <div class="nx-hero">
                        <div>
                            <span class="page-title" style="display:none;"><?= super_audit_e($schoolName); ?></span>
                            <h1 class="nx-hello"><?= $nxGreet; ?>, <?= super_audit_e($nxFname); ?> <span class="nx-wave">👋</span></h1>
                            <div class="nx-hero-sub"><?= $nxNow->format('l, F j'); ?> &nbsp;&middot;&nbsp; <?= $nxNow->format('g:i A'); ?> &nbsp;&middot;&nbsp; System overview<?= $schoolName ? ' — ' . super_audit_e($schoolName) : ''; ?></div>
                        </div>
                        <div>
                            <a href="<?= base_url('Backup'); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-database-export"></i> Backup</a>
                            <a href="<?= base_url('Securityadmin/audit_trail'); ?>" class="up-btn up-btn-primary"><i class="mdi mdi-history"></i> Audit trail</a>
                        </div>
                    </div>

                    <!-- Stat tiles -->
                    <div class="nx-stats">
                        <a class="nx-stat violet span2" href="<?= base_url('Page/userAccounts'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format((int)($stat_accounts ?? 0)); ?></span></div>
                                    <div class="nx-stat-label">User Accounts</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-group-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Manage accounts <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat blue" href="<?= base_url('Securityadmin/audit_trail'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($eventsToday); ?></span></div>
                                    <div class="nx-stat-label">Events Today</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-pulse"></i></div>
                            </div>
                            <div class="nx-stat-foot">Open audit trail <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat cyan" href="<?= base_url('Security/sessions'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format((int)($stat_sessions ?? 0)); ?></span></div>
                                    <div class="nx-stat-label">Active Sessions</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-clock-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Who is signed in <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat orange" href="<?= base_url('Securityadmin/login_activity'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format((int)($stat_failed ?? 0)); ?></span></div>
                                    <div class="nx-stat-label">Failed Logins Today</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-alert-circle-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Login activity <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat green" href="<?= base_url('Securityadmin'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format((int)($stat_blocked ?? 0)); ?></span></div>
                                    <div class="nx-stat-label">Blocked IPs</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-ip-network-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Security dashboard <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat rose" href="<?= base_url('Securityadmin/audit_trail?action=delete'); ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><span data-plugin="counterup"><?= number_format($deletions30d); ?></span></div>
                                    <div class="nx-stat-label">Deletions · 30 Days</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-delete-alert-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Review deletions <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                    </div>

                    <!-- Deletions + activity -->
                    <div class="row mt-4">
                        <div class="col-xl-5">
                            <div class="sa-card-wrap h-100">
                                <div class="sa-card-head">
                                    <h5><i class="mdi mdi-delete-alert-outline" style="color:#d9395f"></i> Recent deletions</h5>
                                    <a href="<?= base_url('Securityadmin/audit_trail?action=delete'); ?>" class="sa-head-link">View all <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                                <div class="sa-feed">
                                    <?php if (empty($recent_deletions)): ?>
                                        <div class="sa-empty"><i class="mdi mdi-delete-off-outline"></i><span>No deletion events recorded</span></div>
                                    <?php else: ?>
                                        <?php foreach ($recent_deletions as $event): ?>
                                            <a class="sa-feed-item" href="<?= base_url('Securityadmin/audit_trail?action=delete&q=' . urlencode((string)($event['record_pk'] ?: $event['username']))); ?>">
                                                <div class="sa-feed-top">
                                                    <span class="sa-feed-who"><?= super_audit_e($event['username'] ?: 'Unknown user'); ?></span>
                                                    <span class="sa-feed-time"><?= super_audit_e(date('M d, h:i A', strtotime($event['event_time']))); ?></span>
                                                </div>
                                                <div class="sa-feed-desc"><?= super_audit_e($event['description'] ?: 'Deleted a record'); ?></div>
                                                <div class="sa-feed-meta"><?= super_audit_e($event['actor_level']); ?> · <?= super_audit_e($event['table_name'] ?: $event['module']); ?><?= !empty($event['record_pk']) ? ' · ID ' . super_audit_e($event['record_pk']) : ''; ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-7 mt-3 mt-xl-0">
                            <div class="sa-card-wrap h-100">
                                <div class="sa-card-head">
                                    <h5><i class="mdi mdi-history"></i> Latest monitored activity</h5>
                                    <a href="<?= base_url('Securityadmin/audit_trail'); ?>" class="sa-head-link">Open audit trail <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                                <div class="sa-table-scroll">
                                    <table class="table table-hover mb-0">
                                        <thead><tr><th>Time</th><th>Actor</th><th>Role</th><th>Action</th><th>Area</th><th>Result</th></tr></thead>
                                        <tbody>
                                        <?php if (empty($recent_audit)): ?>
                                            <tr><td colspan="6"><div class="sa-empty"><i class="mdi mdi-history"></i><span>No monitored activity recorded</span></div></td></tr>
                                        <?php else: foreach ($recent_audit as $event):
                                            $failed = (int)$event['succeeded'] !== 1;
                                            $isDelete = strpos(strtolower((string)$event['action']), 'delete') !== false;
                                        ?>
                                            <tr>
                                                <td class="text-nowrap"><small><?= super_audit_e(date('M d, h:i A', strtotime($event['event_time']))); ?></small></td>
                                                <td><strong><?= super_audit_e($event['username'] ?: 'Unknown'); ?></strong></td>
                                                <td><span class="sa-badge dark"><?= super_audit_e($event['actor_level']); ?></span></td>
                                                <td><span class="sa-badge <?= $isDelete ? 'red' : ($failed ? 'amber' : 'blue'); ?>"><?= super_audit_e(strtoupper(str_replace('_', ' ', $event['action']))); ?></span></td>
                                                <td><?= super_audit_e($event['module']); ?></td>
                                                <td><span class="sa-badge <?= $failed ? 'red' : 'green'; ?>"><?= $failed ? 'FAILED' : 'OK'; ?></span></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick tools -->
                    <div class="row mt-4 mb-3">
                        <div class="col-12">
                            <div class="sa-card-wrap">
                                <div class="sa-card-head">
                                    <h5><i class="mdi mdi-shield-search"></i> Administration &amp; security tools</h5>
                                </div>
                                <div class="sa-card-body">
                                    <div class="sa-tools">
                                        <a href="<?= base_url('Securityadmin'); ?>" class="sa-tool"><i class="mdi mdi-shield-account"></i> Security dashboard</a>
                                        <a href="<?= base_url('Securityadmin/login_activity'); ?>" class="sa-tool"><i class="mdi mdi-login-variant"></i> Login activity</a>
                                        <a href="<?= base_url('Security/sessions'); ?>" class="sa-tool"><i class="mdi mdi-account-clock-outline"></i> Active sessions</a>
                                        <a href="<?= base_url('Security/devices'); ?>" class="sa-tool"><i class="mdi mdi-cellphone-link"></i> Devices</a>
                                        <a href="<?= base_url('Settings/schoolInfo'); ?>" class="sa-tool"><i class="mdi mdi-cog-outline"></i> School information</a>
                                        <a href="<?= base_url('Backup'); ?>" class="sa-tool"><i class="mdi mdi-database-export"></i> Database backup</a>
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

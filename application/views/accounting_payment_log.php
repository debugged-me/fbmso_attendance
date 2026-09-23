<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Title + actions -->
                    <div class="page-title-box">
                            <h4 class="up-page-title">Payment Activity Log</h4>
                            <div class="up-page-sub">Every edit or deletion made to a student payment, and who made it.</div>
                            <hr class="up-divider" />
                        </div>

                    <?php
                    $plRows = (array)($rows ?? []);
                    $plEdit = $plDel = $plToday = 0; $plD = date('Y-m-d');
                    foreach ($plRows as $row) {
                        if (strtolower((string)($row->action ?? '')) === 'delete') $plDel++; else $plEdit++;
                        if (substr((string)($row->changed_at ?? ''), 0, 10) === $plD) $plToday++;
                    }
                    ?>
                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <a class="nx-stat blue" href="javascript:void(0)" data-dtfilter="">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format(count($plRows)); ?></div>
                                    <div class="nx-stat-label">Log Entries</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-history"></i></div>
                            </div>
                            <div class="nx-stat-foot">Show all <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat orange" href="javascript:void(0)" data-dtfilter="Edited">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plEdit); ?></div>
                                    <div class="nx-stat-label">Edited</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-pencil-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Filter table <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat rose" href="javascript:void(0)" data-dtfilter="Deleted">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plDel); ?></div>
                                    <div class="nx-stat-label">Deleted</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-delete-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Filter table <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <div class="nx-stat cyan">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plToday); ?></div>
                                    <div class="nx-stat-label">Today</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-calendar-today"></i></div>
                            </div>
                            <div class="nx-stat-foot">Changed today <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                    </div>

                    <!-- LOG -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
<div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
<h4><i class="mdi mdi-history"></i> Edited &amp; Deleted Payments</h4>
                                    <span class="badge badge-purple"><?= count($rows); ?> entries</span>
</div>
<div class="pl-actions">
                            <a href="<?= base_url($this->session->userdata('level') === 'Cashier' ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" onclick="window.open('<?= base_url('Accounting/paymentAuditLog'); ?>?print=1', '_blank')">
                                <i class="mdi mdi-printer"></i> Print
                            </button>
                            <?php if ($this->session->userdata('level') === 'Cashier'): ?>
                                <a href="<?= base_url('Accounting/Payment'); ?>" class="up-btn up-btn-ghost">
                                    <i class="mdi mdi-cash-multiple"></i> Payment Entry
                                </a>
                            <?php endif; ?>
                        </div>
</div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="paymentLogTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date &amp; Time</th>
                                                    <th>Action</th>
                                                    <th>O.R.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Changed By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <?php
                                                    $studentName = trim((string)($row->LastName ?? ''));
                                                    if ($studentName !== '') $studentName .= ', ';
                                                    $studentName .= trim((string)($row->FirstName ?? ''));
                                                    if (trim($studentName) === '') $studentName = (string)($row->student_number ?? '');

                                                    $action = (string)($row->action ?? '');
                                                    $badgeClass = $action === 'delete' ? 'badge-danger' : 'badge-warning';
                                                    $badgeLabel = $action === 'delete' ? 'Deleted' : 'Edited';
                                                    ?>
                                                    <tr>
                                                        <td data-label="Date & Time" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string)$row->changed_at)), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Action"><span class="badge <?= $badgeClass; ?>"><?= $badgeLabel; ?></span></td>
                                                        <td data-label="O.R." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:var(--up-blue);"><?= htmlspecialchars((string)($row->or_number ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-ink);white-space:nowrap;">₱ <?= number_format((float)($row->amount ?? 0), 2); ?></td>
                                                        <td data-label="Changed By" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->changed_by ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
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

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
    <script>
        $(function() {
            $('#paymentLogTable').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ]
            });
        });
        $(document).on('click', '.nx-stat[data-dtfilter]', function(e) {
            e.preventDefault();
            try {
                $('#paymentLogTable').DataTable().search(String($(this).data('dtfilter') || '')).draw();
            } catch (err) {}
        });
    </script>
</body>

</html>

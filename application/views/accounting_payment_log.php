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
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Payment Activity Log</h4>
                            <div class="up-page-sub">Every edit or deletion made to a student payment, and who made it.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url($this->session->userdata('level') === 'Cashier' ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <?php if ($this->session->userdata('level') === 'Cashier'): ?>
                                <a href="<?= base_url('Accounting/Payment'); ?>" class="up-btn up-btn-primary">
                                    <i class="mdi mdi-cash-multiple"></i> Payment Entry
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- LOG -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-history"></i> Edited &amp; Deleted Payments</h4>
                                    <span class="badge badge-purple"><?= count($rows); ?> entries</span>
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
    </script>
</body>

</html>

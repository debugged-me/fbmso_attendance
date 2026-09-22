<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
    .kpi {
        border: 1px solid var(--up-line, #e6ebf5);
        border-radius: 18px;
        background: var(--up-card, #fff);
        box-shadow: 0 6px 18px rgba(13, 27, 75, .05);
        margin-bottom: 0;
    }
    .kpi .card-body { display: flex; align-items: center; justify-content: space-between; padding: 20px 22px; gap: 12px; }
    .kpi .count { font-size: 1.5rem; font-weight: 800; color: var(--up-ink, #0d1b4b); margin: 0; line-height: 1; letter-spacing: -.01em; }
    .kpi .label { margin: 6px 0 0; color: var(--up-muted, #6b7a99); font-weight: 700; font-size: .72rem; letter-spacing: .16em; text-transform: uppercase; }
    .kpi .icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex: 0 0 auto; }
    .kpi.orange .icon { background: #fff7ed; color: #f97316; }
    .kpi.red .icon { background: #fef2f2; color: #dc2626; }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }

    #partialTable td, #partialTable th { vertical-align: middle; }
</style>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Title + actions -->
                    <div class="pl-header no-print">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Students with Partial Payments</h4>
                            <div class="up-page-sub">
                                Fees paid for less than their full amount &mdash;
                                <?= htmlspecialchars(trim((string)$sem . ' ' . (string)$sy), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/accounting'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" onclick="window.print()">
                                <i class="mdi mdi-printer"></i> Print
                            </button>
                        </div>
                    </div>

                    <div class="report-sheet-print-title" style="display:none;">
                        Students with Partial Payments &mdash; <?= htmlspecialchars(trim((string)$sem . ' ' . (string)$sy), ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="kpi-grid no-print">
                        <div class="card kpi orange">
                            <div class="card-body">
                                <div>
                                    <h2 class="count mb-1"><?= number_format((int)$studentCount); ?></h2>
                                    <p class="label mb-0">Students With Balance</p>
                                </div>
                                <div class="icon"><i class="mdi mdi-account-clock-outline"></i></div>
                            </div>
                        </div>
                        <div class="card kpi red">
                            <div class="card-body">
                                <div>
                                    <h2 class="count mb-1">&#8369;<?= number_format((float)$totalOutstanding, 2); ?></h2>
                                    <p class="label mb-0">Total Outstanding</p>
                                </div>
                                <div class="icon"><i class="mdi mdi-cash-remove"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- LIST -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head no-print">
                                    <h4><i class="mdi mdi-format-list-checks"></i> Outstanding Balances</h4>
                                    <span class="badge badge-purple"><?= count($rows); ?> entries</span>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="partialTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Full Amount</th>
                                                    <th class="text-right">Paid</th>
                                                    <th class="text-right">Outstanding</th>
                                                    <th>Last Payment</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($rows)): ?>
                                                    <?php foreach ($rows as $row): ?>
                                                        <?php
                                                        $studentName = trim((string)($row->LastName ?? ''));
                                                        if ($studentName !== '') $studentName .= ', ';
                                                        $studentName .= trim((string)(($row->FirstName ?? '') . ' ' . ($row->MiddleName ?? '')));
                                                        if (trim($studentName) === '') $studentName = (string)($row->StudentNumber ?? '');
                                                        ?>
                                                        <tr>
                                                            <td data-label="Student" style="font-weight:600;color:var(--up-ink);">
                                                                <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>
                                                                <div style="font-size:.76rem;color:var(--up-muted);font-family:ui-monospace,Menlo,Consolas,monospace;"><?= htmlspecialchars((string)$row->StudentNumber, ENT_QUOTES, 'UTF-8'); ?></div>
                                                            </td>
                                                            <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)$row->Description, ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td data-label="Full Amount" class="text-right" style="white-space:nowrap;">₱ <?= number_format((float)$row->FullAmount, 2); ?></td>
                                                            <td data-label="Paid" class="text-right" style="color:#16a34a;font-weight:700;white-space:nowrap;">₱ <?= number_format((float)$row->PaidAmount, 2); ?></td>
                                                            <td data-label="Outstanding" class="text-right" style="color:#dc2626;font-weight:800;white-space:nowrap;">₱ <?= number_format((float)$row->Outstanding, 2); ?></td>
                                                            <td data-label="Last Payment" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars(date('M d, Y', strtotime((string)$row->LastPaymentDate)), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" style="text-align:center;padding:32px 16px;color:var(--up-muted);">
                                                            <i class="mdi mdi-check-circle-outline" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5;"></i>
                                                            No outstanding partial payments for this term.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
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
    <script>
        $(function() {
            $('#partialTable').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                autoWidth: false,
                order: [
                    [4, 'desc']
                ]
            });
        });
    </script>

    <style>
        @media print {
            #wrapper .topbar,
            #wrapper .left-side-menu,
            .footer,
            .themecustomizer,
            .no-print,
            .dataTables_length,
            .dataTables_filter,
            .dataTables_info,
            .dataTables_paginate {
                display: none !important;
            }

            body { background: #ffffff !important; }
            .content-page { margin-left: 0 !important; }
            .content { padding-top: 0 !important; }
            .container-fluid, .card { margin: 0 !important; border: 0 !important; box-shadow: none !important; }
            .report-sheet-print-title { display: block !important; margin-bottom: 14px; font-size: 16px; font-weight: 700; color: #1e293b !important; }
            .table-responsive { overflow: visible !important; }
            #partialTable { width: 100% !important; font-size: 11px; }
            #partialTable th, #partialTable td { padding: 6px !important; border-color: #cbd5e1 !important; }
        }
    </style>
</body>

</html>

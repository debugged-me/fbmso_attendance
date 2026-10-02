<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    /* ===== Statement tables ===== */
    .fs-table td, .fs-table th { vertical-align: middle; }
    .fs-section-head td {
        background: #eef2ff; color: var(--up-ink, #0d1b4b); font-weight: 800;
        font-size: .72rem; letter-spacing: .1em; text-transform: uppercase;
    }
    .fs-indent td:first-child { padding-left: 26px; color: #25324c; }
    .fs-subhead td:first-child { padding-left: 16px; font-weight: 700; color: var(--up-ink, #0d1b4b); }
    .fs-total-row td {
        border-top: 2px solid var(--up-line, #e6ebf5); font-weight: 800;
        color: var(--up-ink, #0d1b4b); background: #f8f9fc;
    }
    .fs-grand-row td {
        border-top: 2px solid #2a4090; font-weight: 800; color: var(--up-ink, #0d1b4b);
        background: #eef2ff; font-size: .92rem;
    }
    .fs-amt { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .fs-drill { color: inherit; text-decoration: none; border-bottom: 1px dashed #9aa8d6; }
    .fs-drill:hover { color: #2a4090; border-bottom-color: #2a4090; text-decoration: none; }
    .fs-drill .mdi { font-size: .95em; color: #7c8bc4; margin-left: 4px; }
    .fs-neg { color: #dc2626 !important; }
    .fs-empty td {
        text-align: center; padding: 20px 14px !important; color: var(--up-muted, #6b7a99);
        font-style: italic;
    }

    /* ===== Statement tabs ===== */
    .fs-tabs {
        flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden;
        border-bottom: 1px solid var(--up-line, #e6ebf5);
        padding: 0 14px; scrollbar-width: thin;
    }
    .fs-tabs .nav-item { margin-bottom: 0; flex: 0 0 auto; }
    .fs-tabs .nav-link {
        border: 0; border-bottom: 3px solid transparent; border-radius: 0;
        padding: 13px 16px; color: var(--up-muted, #6b7a99); font-weight: 700;
        font-size: .82rem; letter-spacing: .04em; text-transform: uppercase;
        white-space: nowrap; display: flex; align-items: center; gap: 8px;
        background: transparent;
    }
    .fs-tabs .nav-link i { font-size: 1.05rem; }
    .fs-tabs .nav-link:hover { color: var(--up-ink, #0d1b4b); border-bottom-color: #c7d2f5; }
    .fs-tabs .nav-link.active {
        color: #2a4090; border-bottom-color: #2a4090; background: transparent;
    }
    .fs-tabs .nav-link .fs-tab-amt {
        font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 999px;
        background: #f1f5fb; color: var(--up-muted, #6b7a99); text-transform: none; letter-spacing: 0;
    }
    .fs-tabs .nav-link.active .fs-tab-amt { background: #eef2ff; color: #2a4090; }

    .fs-pane-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 8px; padding: 12px 18px;
        border-bottom: 1px solid var(--up-line, #e6ebf5); background: #fafbfe;
    }
    .fs-pane-toolbar .fs-pane-sub { font-size: .8rem; color: var(--up-muted, #6b7a99); }
    .fs-pane-body { padding: 16px 18px; }
    .fs-pane-body .table { border-radius: 8px; overflow: hidden; }

    .fs-note {
        padding: 10px 18px; font-size: .78rem; color: var(--up-muted, #6b7a99);
        border-top: 1px solid var(--up-line, #e6ebf5);
    }

    @media (max-width: 575.98px) {
        .fs-tabs { padding: 0 6px; }
        .fs-tabs .nav-link { padding: 11px 12px; font-size: .74rem; }
        .fs-tabs .nav-link .fs-tab-amt { display: none; }
        .fs-indent td:first-child { padding-left: 14px; }
        .fs-pane-toolbar { padding: 10px 12px; }
        .fs-pane-body { padding: 10px; }
    }

    /* ===== Guide modal ===== */
    .fs-guide-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
    .fs-guide-modal .modal-header {
        background: linear-gradient(135deg, #1a2a6c, #2a4090); color: #fff;
        border-bottom: 0; padding: 16px 22px;
    }
    .fs-guide-modal .modal-title { font-weight: 800; font-size: 1rem; display: flex; align-items: center; gap: 8px; color: #fff !important; }
    .fs-guide-modal .close { color: #fff; opacity: .8; text-shadow: none; }
    .fs-guide-modal .close:hover { opacity: 1; }
    .fs-guide-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--up-line, #e6ebf5); }
    .fs-guide-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .fs-guide-item .g-icon {
        width: 38px; height: 38px; border-radius: 10px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center; font-size: 19px;
        background: #eef2ff; color: #2a4090;
    }
    .fs-guide-item .g-title { font-weight: 800; color: var(--up-ink, #0d1b4b); font-size: .88rem; margin-bottom: 2px; }
    .fs-guide-item .g-text { font-size: .83rem; color: var(--up-muted, #6b7a99); line-height: 1.5; }
    .fs-guide-tip {
        margin-top: 14px; padding: 10px 14px; border-radius: 10px;
        background: #fffbeb; border: 1px solid #fde68a; color: #92600a;
        font-size: .8rem; line-height: 1.5;
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
                    $stmtParam = function ($key) use ($from, $to, $sem, $sy) {
                        return 'from=' . urlencode((string)$from)
                            . '&to=' . urlencode((string)$to)
                            . '&sem=' . urlencode((string)$sem)
                            . '&sy=' . urlencode((string)$sy)
                            . '&stmt=' . $key;
                    };
                    // Who paid a revenue line: same term and dates, so the names add up to the line.
                    $payersUrl = function ($item) use ($from, $to, $sem, $sy) {
                        return base_url('Accounting/feePayers') . '?from=' . urlencode((string)$from)
                            . '&to=' . urlencode((string)$to)
                            . '&sem=' . urlencode((string)$sem)
                            . '&sy=' . urlencode((string)$sy)
                            . '&fee=' . urlencode((string)$item);
                    };
                    $active = isset($activeStmt) ? (string)$activeStmt : 'all';
                    if ($active === 'all') {
                        $active = 'income';
                    }
                    ?>

                    <!-- Title + actions -->
                    <div class="page-title-box">
                        <h4 class="up-page-title">Financial Statements</h4>
                        <div class="up-page-sub">
                            <?= htmlspecialchars((string)$periodLabel, ENT_QUOTES, 'UTF-8'); ?>
                            &middot; Term: <?= htmlspecialchars((string)$termLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <hr class="up-divider" />
                    </div>


                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <div class="nx-stat green">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$totalRevenue, 2); ?></div>
                                    <div class="nx-stat-label">Total Revenues</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-trending-up"></i></div>
                            </div>
                            <div class="nx-stat-foot">Collections in period <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat rose">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$totalExpenses, 2); ?></div>
                                    <div class="nx-stat-label">Total Expenses</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-trending-down"></i></div>
                            </div>
                            <div class="nx-stat-foot">Expenses in period <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat <?= (float)$netIncome < 0 ? 'rose' : 'blue'; ?>">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$netIncome, 2); ?></div>
                                    <div class="nx-stat-label"><?= (float)$netIncome < 0 ? 'Net Loss' : 'Net Income'; ?></div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-scale-balance"></i></div>
                            </div>
                            <div class="nx-stat-foot">Revenues minus expenses <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat violet">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$endCash, 2); ?></div>
                                    <div class="nx-stat-label">Cash on Hand</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-cash-multiple"></i></div>
                            </div>
                            <div class="nx-stat-foot">As of <?= htmlspecialchars(date('M d, Y', strtotime((string)$to)), ENT_QUOTES, 'UTF-8'); ?> <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat orange">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$totalReceivables, 2); ?></div>
                                    <div class="nx-stat-label">Accounts Receivable</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-clock-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot"><?= number_format((int)$receivableStudents); ?> student(s) with balances <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                    </div>

                    <!-- STATEMENTS -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                                        <h4><i class="mdi mdi-file-document-multiple-outline"></i> Statements</h4>
                                        <span class="badge badge-purple"><?= htmlspecialchars((string)$periodLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="pl-actions">
                                        <a href="<?= base_url('Page/accounting'); ?>" class="up-btn up-btn-ghost d-md-none">
                                            <i class="mdi mdi-arrow-left"></i> Dashboard
                                        </a>
                                        <button type="button" class="up-btn up-btn-ghost" data-toggle="modal" data-target="#fsGuideModal">
                                            <i class="mdi mdi-help-circle-outline"></i> Guide
                                        </button>
                                        <button type="button" class="up-btn up-btn-ghost" data-fs-filter>
                                            <i class="mdi mdi-filter-outline"></i> Filter
                                        </button>
                                        <div class="btn-group">
                                            <button type="button" class="up-btn up-btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-printer"></i> Print
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('all'); ?>&print=1" target="_blank">
                                                    <i class="mdi mdi-file-document-multiple-outline mr-1"></i> All three statements
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('income'); ?>&print=1" target="_blank">
                                                    <i class="mdi mdi-file-chart-outline mr-1"></i> Income Statement only
                                                </a>
                                                <a class="dropdown-item" href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('cashflow'); ?>&print=1" target="_blank">
                                                    <i class="mdi mdi-swap-vertical-bold mr-1"></i> Cash Flow only
                                                </a>
                                                <a class="dropdown-item" href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('balance'); ?>&print=1" target="_blank">
                                                    <i class="mdi mdi-bank-outline mr-1"></i> Balance Sheet only
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="<?= htmlspecialchars($payersUrl('') . '&print=1', ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                                    <i class="mdi mdi-account-cash-outline mr-1"></i> Names of payers, per fee
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <ul class="nav nav-tabs fs-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $active === 'income' ? 'active' : ''; ?>" data-toggle="tab" href="#pane-income" role="tab">
                                            <i class="mdi mdi-file-chart-outline"></i> Income Statement
                                            <span class="fs-tab-amt">&#8369;<?= number_format((float)$netIncome, 2); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $active === 'cashflow' ? 'active' : ''; ?>" data-toggle="tab" href="#pane-cashflow" role="tab">
                                            <i class="mdi mdi-swap-vertical-bold"></i> Cash Flow
                                            <span class="fs-tab-amt">&#8369;<?= number_format((float)$endCash, 2); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $active === 'balance' ? 'active' : ''; ?>" data-toggle="tab" href="#pane-balance" role="tab">
                                            <i class="mdi mdi-bank-outline"></i> Balance Sheet
                                            <span class="fs-tab-amt">&#8369;<?= number_format((float)$endCash + (float)$totalReceivables, 2); ?></span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">

                                    <!-- INCOME STATEMENT -->
                                    <div class="tab-pane <?= $active === 'income' ? 'active' : ''; ?>" id="pane-income" role="tabpanel">
                                        <div class="fs-pane-toolbar">
                                            <span class="fs-pane-sub">Revenues and expenses for <?= htmlspecialchars((string)$periodLabel, ENT_QUOTES, 'UTF-8'); ?> &middot; click a revenue line to see who paid</span>
                                            <div class="d-flex" style="gap:8px;flex-wrap:wrap;">
                                                <a href="<?= htmlspecialchars($payersUrl(''), ENT_QUOTES, 'UTF-8'); ?>" class="up-btn up-btn-ghost" style="padding:5px 12px;font-size:.78rem;">
                                                    <i class="mdi mdi-account-cash-outline"></i> Who paid
                                                </a>
                                                <a href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('income'); ?>&print=1" target="_blank" class="up-btn up-btn-ghost" style="padding:5px 12px;font-size:.78rem;">
                                                    <i class="mdi mdi-printer"></i> Print this statement
                                                </a>
                                            </div>
                                        </div>
                                        <div class="fs-pane-body">
                                        <div class="table-responsive up-rt-host">
                                            <table class="table table-bordered table-sm up-rt ms-rt-keep mb-0 fs-table" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Line Item</th>
                                                        <th class="text-center">Entries</th>
                                                        <th class="text-right">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="fs-section-head"><td colspan="3">Revenues</td></tr>
                                                    <?php if (!empty($revenueRows)): ?>
                                                        <?php foreach ($revenueRows as $row): ?>
                                                            <tr class="fs-indent">
                                                                <td data-label="Line Item">
                                                                    <a class="fs-drill" href="<?= htmlspecialchars($payersUrl($row->Item), ENT_QUOTES, 'UTF-8'); ?>" title="See who paid"><?= htmlspecialchars((string)$row->Item, ENT_QUOTES, 'UTF-8'); ?><i class="mdi mdi-account-multiple-outline"></i></a>
                                                                </td>
                                                                <td data-label="Entries" class="text-center"><?= number_format((int)$row->TxnCount); ?></td>
                                                                <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$row->Total, 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr class="fs-empty"><td colspan="3">No collections recorded in this period.</td></tr>
                                                    <?php endif; ?>
                                                    <tr class="fs-total-row">
                                                        <td>Total Revenues</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$totalRevenue, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-section-head"><td colspan="3">Expenses</td></tr>
                                                    <?php if (!empty($expenseRows)): ?>
                                                        <?php foreach ($expenseRows as $row): ?>
                                                            <tr class="fs-indent">
                                                                <td data-label="Line Item"><?= htmlspecialchars((string)$row->Item, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td data-label="Entries" class="text-center"><?= number_format((int)$row->TxnCount); ?></td>
                                                                <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$row->Total, 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr class="fs-empty"><td colspan="3">No expenses recorded in this period.</td></tr>
                                                    <?php endif; ?>
                                                    <tr class="fs-total-row">
                                                        <td>Total Expenses</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$totalExpenses, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-grand-row">
                                                        <td><?= (float)$netIncome < 0 ? 'NET LOSS' : 'NET INCOME'; ?></td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt <?= (float)$netIncome < 0 ? 'fs-neg' : ''; ?>"><?= (float)$netIncome < 0 ? '(&#8369; ' . number_format(abs((float)$netIncome), 2) . ')' : '&#8369; ' . number_format((float)$netIncome, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- CASH FLOW STATEMENT -->
                                    <div class="tab-pane <?= $active === 'cashflow' ? 'active' : ''; ?>" id="pane-cashflow" role="tabpanel">
                                        <div class="fs-pane-toolbar">
                                            <span class="fs-pane-sub">Cash movement for <?= htmlspecialchars((string)$periodLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <a href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('cashflow'); ?>&print=1" target="_blank" class="up-btn up-btn-ghost" style="padding:5px 12px;font-size:.78rem;">
                                                <i class="mdi mdi-printer"></i> Print this statement
                                            </a>
                                        </div>
                                        <div class="fs-pane-body">
                                        <div class="table-responsive up-rt-host">
                                            <table class="table table-bordered table-sm up-rt ms-rt-keep mb-0 fs-table" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Line Item</th>
                                                        <th class="text-center">Entries</th>
                                                        <th class="text-right">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="fs-section-head"><td colspan="3">Cash Flows from Operating Activities</td></tr>
                                                    <tr class="fs-subhead"><td colspan="3">Cash inflows — collections</td></tr>
                                                    <?php if (!empty($revenueRows)): ?>
                                                        <?php foreach ($revenueRows as $row): ?>
                                                            <tr class="fs-indent">
                                                                <td data-label="Line Item">
                                                                    <a class="fs-drill" href="<?= htmlspecialchars($payersUrl($row->Item), ENT_QUOTES, 'UTF-8'); ?>" title="See who paid"><?= htmlspecialchars((string)$row->Item, ENT_QUOTES, 'UTF-8'); ?><i class="mdi mdi-account-multiple-outline"></i></a>
                                                                </td>
                                                                <td data-label="Entries" class="text-center"><?= number_format((int)$row->TxnCount); ?></td>
                                                                <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$row->Total, 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr class="fs-empty"><td colspan="3">No collections recorded in this period.</td></tr>
                                                    <?php endif; ?>
                                                    <?php if (abs((float)$otherTermCollections) > 0.004): ?>
                                                        <tr class="fs-indent">
                                                            <td data-label="Line Item">Collections credited to other terms</td>
                                                            <td data-label="Entries" class="text-center"></td>
                                                            <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$otherTermCollections, 2); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr class="fs-total-row">
                                                        <td>Total cash inflows</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$periodCollectionsAll, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-subhead"><td colspan="3">Cash outflows — expenses</td></tr>
                                                    <?php if (!empty($expenseRows)): ?>
                                                        <?php foreach ($expenseRows as $row): ?>
                                                            <tr class="fs-indent">
                                                                <td data-label="Line Item"><?= htmlspecialchars((string)$row->Item, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td data-label="Entries" class="text-center"><?= number_format((int)$row->TxnCount); ?></td>
                                                                <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$row->Total, 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr class="fs-empty"><td colspan="3">No expenses recorded in this period.</td></tr>
                                                    <?php endif; ?>
                                                    <tr class="fs-total-row">
                                                        <td>Total cash outflows</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$totalExpenses, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-total-row">
                                                        <td>Net cash flow for the period</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt <?= (float)$netCashFlow < 0 ? 'fs-neg' : ''; ?>">&#8369; <?= number_format((float)$netCashFlow, 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td data-label="Line Item" style="padding-left:16px;">Cash at beginning of period</td>
                                                        <td data-label="Entries" class="text-center"></td>
                                                        <td data-label="Amount" class="fs-amt <?= (float)$beginCash < 0 ? 'fs-neg' : ''; ?>">&#8369; <?= number_format((float)$beginCash, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-grand-row">
                                                        <td>CASH AT END OF PERIOD</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt <?= (float)$endCash < 0 ? 'fs-neg' : ''; ?>">&#8369; <?= number_format((float)$endCash, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- BALANCE SHEET -->
                                    <div class="tab-pane <?= $active === 'balance' ? 'active' : ''; ?>" id="pane-balance" role="tabpanel">
                                        <div class="fs-pane-toolbar">
                                            <span class="fs-pane-sub">Position as of <?= htmlspecialchars(date('M d, Y', strtotime((string)$to)), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <a href="<?= base_url('Accounting/financialStatements'); ?>?<?= $stmtParam('balance'); ?>&print=1" target="_blank" class="up-btn up-btn-ghost" style="padding:5px 12px;font-size:.78rem;">
                                                <i class="mdi mdi-printer"></i> Print this statement
                                            </a>
                                        </div>
                                        <div class="fs-pane-body">
                                        <div class="table-responsive up-rt-host">
                                            <table class="table table-bordered table-sm up-rt ms-rt-keep mb-0 fs-table" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Line Item</th>
                                                        <th class="text-center">Balances</th>
                                                        <th class="text-right">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="fs-section-head"><td colspan="3">Assets</td></tr>
                                                    <tr class="fs-indent">
                                                        <td data-label="Line Item">Cash on hand</td>
                                                        <td data-label="Balances" class="text-center"></td>
                                                        <td data-label="Amount" class="fs-amt <?= (float)$endCash < 0 ? 'fs-neg' : ''; ?>">&#8369; <?= number_format((float)$endCash, 2); ?></td>
                                                    </tr>
                                                    <?php foreach ((array)$receivableRows as $row): ?>
                                                        <tr class="fs-indent">
                                                            <td data-label="Line Item">Accounts receivable — <?= htmlspecialchars((string)$row['item'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td data-label="Balances" class="text-center"><?= number_format((int)$row['balances']); ?></td>
                                                            <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$row['amount'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($receivableRows)): ?>
                                                        <tr class="fs-indent">
                                                            <td data-label="Line Item">Accounts receivable — students' unpaid fee balances</td>
                                                            <td data-label="Balances" class="text-center">0</td>
                                                            <td data-label="Amount" class="fs-amt">&#8369; 0.00</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr class="fs-total-row">
                                                        <td>TOTAL ASSETS</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$endCash + (float)$totalReceivables, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-section-head"><td colspan="3">Liabilities</td></tr>
                                                    <tr class="fs-indent">
                                                        <td data-label="Line Item">None recorded</td>
                                                        <td data-label="Balances" class="text-center"></td>
                                                        <td data-label="Amount" class="fs-amt">&#8369; 0.00</td>
                                                    </tr>
                                                    <tr class="fs-total-row">
                                                        <td>TOTAL LIABILITIES</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; 0.00</td>
                                                    </tr>
                                                    <tr class="fs-section-head"><td colspan="3">Fund Balance</td></tr>
                                                    <tr class="fs-indent">
                                                        <td data-label="Line Item">Fund balance, end of period</td>
                                                        <td data-label="Balances" class="text-center"></td>
                                                        <td data-label="Amount" class="fs-amt">&#8369; <?= number_format((float)$endCash + (float)$totalReceivables, 2); ?></td>
                                                    </tr>
                                                    <tr class="fs-grand-row">
                                                        <td>TOTAL LIABILITIES AND FUND BALANCE</td>
                                                        <td class="text-center"></td>
                                                        <td class="fs-amt">&#8369; <?= number_format((float)$endCash + (float)$totalReceivables, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        </div>
                                        <div class="fs-note">
                                            <i class="mdi mdi-information-outline"></i>
                                            Per-student receivable detail is on the
                                            <a href="<?= base_url('Accounting/partialPayments'); ?>">Partial Payments</a> report.
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height:40px;"></div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <!-- GUIDE MODAL -->
    <div class="modal fade fs-guide-modal" id="fsGuideModal" tabindex="-1" role="dialog" aria-labelledby="fsGuideTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fsGuideTitle"><i class="mdi mdi-help-circle-outline"></i> How to read these statements</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 6px 22px 18px;">
                    <div class="fs-guide-item">
                        <div class="g-icon"><i class="mdi mdi-filter-outline"></i></div>
                        <div>
                            <div class="g-title">Period and term</div>
                            <div class="g-text">Use <b>Filter</b> to pick the reporting dates and the term. Collections are limited to the selected term; expenses carry no semester tag and are matched by date. The Balance Sheet is drawn as of the <b>To</b> date.</div>
                        </div>
                    </div>
                    <div class="fs-guide-item">
                        <div class="g-icon"><i class="mdi mdi-file-chart-outline"></i></div>
                        <div>
                            <div class="g-title">Income Statement</div>
                            <div class="g-text">Collections (revenues) minus expenses within the period. The bottom line is your <b>Net Income</b> — or <b>Net Loss</b>, shown in parentheses, when expenses exceed collections. Click any revenue line (e.g. a membership fee) to see the names of the students who paid it.</div>
                        </div>
                    </div>
                    <div class="fs-guide-item">
                        <div class="g-icon"><i class="mdi mdi-swap-vertical-bold"></i></div>
                        <div>
                            <div class="g-title">Cash Flow Statement</div>
                            <div class="g-text">Money that came in and went out during the period: collections as inflows, expenses as outflows. It starts from the <b>cash at beginning of period</b> and lands on <b>cash at end of period</b> — the same cash on hand the Balance Sheet reports. Cash is fund-wide: money collected for other terms still counts toward it.</div>
                        </div>
                    </div>
                    <div class="fs-guide-item">
                        <div class="g-icon"><i class="mdi mdi-bank-outline"></i></div>
                        <div>
                            <div class="g-title">Balance Sheet</div>
                            <div class="g-text"><b>Cash on hand</b> plus <b>accounts receivable</b> (students' unpaid fee balances for the selected term — per-student detail is on the Partial Payments report) equals total assets. No liabilities are recorded, so the whole amount is the fund balance.</div>
                        </div>
                    </div>
                    <div class="fs-guide-tip">
                        <i class="mdi mdi-lightbulb-on-outline"></i>
                        <b>Tip:</b> widen the date range if an expense was recorded before the first collection of the term — it is matched by date, not by semester. Use the <b>Print</b> button to print all three statements together or just the one you are viewing.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>

    <!-- FILTER PANEL (opens on the right) -->
    <form method="get" action="<?= base_url('Accounting/financialStatements'); ?>" id="fsFilterPanel" hidden>
        <div class="sd-form-fields">
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="sem">Semester</label>
                    <select id="sem" name="sem" class="form-control">
                        <option value="">All terms</option>
                        <?php foreach ((array)$semOptions as $opt): ?>
                            <option value="<?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8'); ?>" <?= (string)$opt === (string)$sem ? 'selected' : ''; ?>><?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sy">School Year</label>
                    <select id="sy" name="sy" class="form-control">
                        <option value="">All</option>
                        <?php foreach ((array)$syOptions as $opt): ?>
                            <option value="<?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8'); ?>" <?= (string)$opt === (string)$sy ? 'selected' : ''; ?>><?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control"
                        value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control"
                        value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <small class="sd-help">The term limits which collections and unpaid balances count; the dates set the reporting period.</small>
        </div>
        <div class="sd-actions">
            <a href="<?= base_url('Accounting/financialStatements'); ?>" class="sd-btn-ghost">This term</a>
            <button type="submit" class="sd-btn"><i class="mdi mdi-filter-outline"></i> Apply filter</button>
        </div>
    </form>

    <?php include('includes/side_drawer.php'); ?>
    <script>
        if (window.SideDrawer) {
            SideDrawer.fromElement(document.getElementById('fsFilterPanel'), {
                title: 'Filter statements', icon: 'mdi-filter-outline', width: 400,
                trigger: '[data-fs-filter]', focus: 'select'
            });
        }
    </script>
</body>

</html>

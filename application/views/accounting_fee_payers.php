<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    /* ===== Fee picker: one button, the full list in a modal ===== */
    .fp-picker {
        display: flex; align-items: center; gap: 14px; width: 100%;
        margin-bottom: 18px; padding: 14px 16px; text-align: left;
        border: 1px solid var(--up-line, #e6ebf5); border-radius: 16px;
        background: var(--up-card, #fff); color: var(--up-ink, #0d1b4b);
        box-shadow: 0 6px 18px rgba(13, 27, 75, .05); cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }
    .fp-picker:hover { border-color: #c7d2f5; box-shadow: 0 8px 22px rgba(13, 27, 75, .08); }
    .fp-picker:focus-visible { outline: 3px solid #c7d2f5; outline-offset: 2px; }
    .fp-picker-icon {
        width: 46px; height: 46px; border-radius: 12px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center;
        background: #eef2ff; color: #2a4090; font-size: 22px;
    }
    .fp-picker-main { flex: 1 1 auto; min-width: 0; }
    .fp-picker-label {
        display: block; font-size: .68rem; font-weight: 800; letter-spacing: .12em;
        text-transform: uppercase; color: var(--up-muted, #6b7a99);
    }
    .fp-picker-name { display: block; font-size: 1.05rem; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
    .fp-picker-share { display: block; margin-top: 2px; font-size: .78rem; color: var(--up-muted, #6b7a99); }
    .fp-picker-cta {
        flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; border-radius: 10px; background: #2a4090; color: #fff;
        font-size: .8rem; font-weight: 700; white-space: nowrap;
    }
    .fp-picker-cta .fp-count {
        padding: 1px 7px; border-radius: 999px; background: rgba(255, 255, 255, .2);
        font-size: .72rem; font-variant-numeric: tabular-nums;
    }

    .fp-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
    .fp-modal .modal-header {
        background: linear-gradient(135deg, #1a2a6c, #2a4090); color: #fff;
        border-bottom: 0; padding: 16px 22px;
    }
    .fp-modal .modal-title { font-weight: 800; font-size: 1rem; display: flex; align-items: center; gap: 8px; color: #fff !important; }
    .fp-modal .fp-modal-sub { font-size: .78rem; opacity: .8; margin-top: 2px; }
    .fp-modal .close { color: #fff; opacity: .8; text-shadow: none; }
    .fp-modal .close:hover { opacity: 1; }
    .fp-modal .modal-body { padding: 12px; background: #f6f8fc; }

    .fp-opt {
        display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px;
        margin-bottom: 8px; border-radius: 12px; border: 1px solid var(--up-line, #e6ebf5);
        background: #fff; color: var(--up-ink, #0d1b4b); text-decoration: none !important;
        transition: border-color .15s, box-shadow .15s;
    }
    .fp-opt:last-child { margin-bottom: 0; }
    .fp-opt:hover { border-color: #c7d2f5; box-shadow: 0 4px 12px rgba(13, 27, 75, .06); color: var(--up-ink, #0d1b4b); }
    .fp-opt:focus-visible { outline: 3px solid #c7d2f5; outline-offset: 1px; }
    .fp-opt.active { border-color: #2a4090; background: #eef2ff; box-shadow: inset 0 0 0 1px #2a4090; }
    .fp-opt-mark { flex: 0 0 auto; font-size: 20px; line-height: 1.2; color: #c3cbe0; }
    .fp-opt.active .fp-opt-mark { color: #2a4090; }
    .fp-opt-body { flex: 1 1 auto; min-width: 0; }
    .fp-opt-top { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
    .fp-opt-name { font-weight: 800; font-size: .86rem; line-height: 1.3; overflow-wrap: anywhere; }
    .fp-opt-amt { font-weight: 800; font-size: .9rem; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .fp-opt-bar { display: block; height: 6px; margin: 8px 0 6px; border-radius: 999px; background: #e9edf6; overflow: hidden; }
    .fp-opt-bar span { display: block; height: 100%; border-radius: 999px; background: #2a4090; }
    .fp-opt-sub {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: .76rem; color: var(--up-muted, #6b7a99); font-variant-numeric: tabular-nums;
    }
    .fp-opt-sub b { color: var(--up-ink, #0d1b4b); }
    .fp-opt-empty { padding: 28px 14px; text-align: center; color: var(--up-muted, #6b7a99); font-style: italic; }

    #payersTable td, #payersTable th { vertical-align: middle; }
    #payersTable thead th { white-space: nowrap; }
    .fp-amt { text-align: right; white-space: nowrap; font-weight: 700; font-variant-numeric: tabular-nums; }
    .fp-mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
    .fp-status { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
    .fp-status.paid { background: #ecfdf5; color: #15803d; }
    .fp-status.partial { background: #fff7ed; color: #c2410c; }
    .fp-status.na { background: #f1f5fb; color: var(--up-muted, #6b7a99); }
    #payersExportButtons .dt-buttons .btn { margin-left: 6px; margin-bottom: 6px; }

    @media (max-width: 575.98px) {
        .fp-picker { flex-wrap: wrap; gap: 12px; padding: 12px; }
        .fp-picker-icon { width: 40px; height: 40px; font-size: 19px; }
        .fp-picker-main { flex-basis: calc(100% - 52px); }
        .fp-picker-cta { width: 100%; justify-content: center; padding: 10px 14px; }
        #payersExportButtons { width: 100%; }
        #payersExportButtons .dt-buttons .btn { margin-left: 0; margin-right: 6px; }
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
                    $h = function ($value) {
                        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                    };
                    $feeUrl = function ($item) use ($query) {
                        return base_url('Accounting/feePayers') . '?' . $query . '&fee=' . urlencode((string)$item);
                    };
                    $allAmount = 0.0;
                    foreach ((array)$fees as $f) {
                        $allAmount += (float)$f['amount'];
                    }
                    $statementsUrl = base_url('Accounting/financialStatements') . '?from=' . urlencode((string)$from)
                        . '&to=' . urlencode((string)$to) . '&sem=' . urlencode((string)$sem) . '&sy=' . urlencode((string)$sy)
                        . '&stmt=income';
                    ?>

                    <!-- Title -->
                    <div class="page-title-box">
                        <h4 class="up-page-title">Paid Students by Fee</h4>
                        <div class="up-page-sub">
                            <?= $h($periodLabel); ?> &middot; Term: <?= $h($termLabel); ?>
                            <?php if ((string)$course !== ''): ?>&middot; <?= $h($course); ?><?php endif; ?>
                        </div>
                        <hr class="up-divider" />
                    </div>

                    <?php
                    $selected = null;
                    foreach ((array)$fees as $f) {
                        if ((string)$f['fee'] === (string)$fee) {
                            $selected = $f;
                        }
                    }
                    $selAmount = (string)$fee === '' ? $allAmount : (float)($selected['amount'] ?? 0);
                    $shareOf = function ($amount) use ($allAmount) {
                        return $allAmount > 0 ? (float)$amount / $allAmount * 100 : 0.0;
                    };
                    ?>

                    <!-- Fee picker: the Income Statement's revenue lines, chosen in a modal -->
                    <button type="button" class="fp-picker" data-toggle="modal" data-target="#fpFeeModal" aria-haspopup="dialog">
                        <span class="fp-picker-icon"><i class="mdi mdi-tag-multiple"></i></span>
                        <span class="fp-picker-main">
                            <span class="fp-picker-label">Showing who paid</span>
                            <span class="fp-picker-name"><?= (string)$fee === '' ? 'All fees' : $h($fee); ?></span>
                            <span class="fp-picker-share">
                                <?php if ((string)$fee === ''): ?>
                                    <?= number_format(count((array)$fees)); ?> fee<?= count((array)$fees) === 1 ? '' : 's'; ?> &middot; &#8369;<?= number_format($allAmount, 2); ?> collected
                                <?php else: ?>
                                    <?= number_format($shareOf($selAmount), 1); ?>% of the &#8369;<?= number_format($allAmount, 2); ?> collected in this period
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="fp-picker-cta">
                            <i class="mdi mdi-swap-horizontal"></i> Change fee
                            <span class="fp-count"><?= number_format(count((array)$fees)); ?></span>
                        </span>
                    </button>

                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <div class="nx-stat violet">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format((int)$studentCount); ?></div>
                                    <div class="nx-stat-label">Students Paid</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-multiple-check"></i></div>
                            </div>
                            <div class="nx-stat-foot"><?= (string)$fee === '' ? 'Any fee' : $h($fee); ?> <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat green">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format((float)$totalAmount, 2); ?></div>
                                    <div class="nx-stat-label">Amount Collected</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-cash-multiple"></i></div>
                            </div>
                            <div class="nx-stat-foot">In this period <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat blue">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format((int)$paidCount); ?></div>
                                    <div class="nx-stat-label">Fully Paid</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-check-decagram"></i></div>
                            </div>
                            <div class="nx-stat-foot">Fee settled for the term <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat orange">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format((int)$partialCount); ?></div>
                                    <div class="nx-stat-label">With Balance</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-clock-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Paid part of the fee <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                    </div>

                    <!-- LIST -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                                        <h4><i class="mdi mdi-account-cash-outline"></i> <?= (string)$fee === '' ? 'All payers' : $h($fee); ?></h4>
                                        <span class="badge badge-purple"><?= number_format(count((array)$rows)); ?> entries</span>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                                        <div id="payersExportButtons"></div>
                                        <div class="pl-actions">
                                            <a href="<?= $h($statementsUrl); ?>" class="up-btn up-btn-ghost">
                                                <i class="mdi mdi-arrow-left"></i> Income Statement
                                            </a>
                                            <button type="button" class="up-btn up-btn-ghost" data-fp-filter>
                                                <i class="mdi mdi-filter-outline"></i> Filter
                                            </button>
                                            <a href="<?= $h($feeUrl($fee) . '&print=1'); ?>" target="_blank" class="up-btn up-btn-primary">
                                                <i class="mdi mdi-printer"></i> Print
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="payersTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Student No.</th>
                                                    <th>Student Name</th>
                                                    <th>Course &amp; Year</th>
                                                    <?php if ((string)$fee === ''): ?><th>Fee</th><?php endif; ?>
                                                    <?php if ($showTerm): ?><th>Term</th><?php endif; ?>
                                                    <th>O.R. No.</th>
                                                    <th>Date Paid</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ((array)$rows as $row): ?>
                                                    <tr>
                                                        <td data-label="Student No." class="fp-mono" style="color:var(--up-muted);"><?= $h($row['studno']); ?></td>
                                                        <td data-label="Student Name" style="font-weight:600;color:var(--up-ink);">
                                                            <a href="#" class="sd-link" data-student-balance data-sd-key="<?= $h($row['studno']); ?>" title="View balance"><?= $h($row['name']); ?></a>
                                                        </td>
                                                        <td data-label="Course &amp; Year" style="color:var(--up-muted);"><?= $h($row['courseYear']); ?></td>
                                                        <?php if ((string)$fee === ''): ?>
                                                            <td data-label="Fee"><?= $h($row['fee']); ?></td>
                                                        <?php endif; ?>
                                                        <?php if ($showTerm): ?>
                                                            <td data-label="Term" style="color:var(--up-muted);"><?= $h($row['term']); ?></td>
                                                        <?php endif; ?>
                                                        <td data-label="O.R. No." class="fp-mono" style="font-weight:700;color:var(--up-blue);"><?= $h($row['orNumbers']); ?></td>
                                                        <td data-label="Date Paid" data-order="<?= $h($row['lastDate']); ?>" style="color:var(--up-muted);white-space:nowrap;">
                                                            <?= $h(date('M d, Y', strtotime((string)$row['lastDate']))); ?>
                                                            <?php if ((int)$row['payments'] > 1): ?>
                                                                <div style="font-size:.74rem;"><?= (int)$row['payments']; ?> payments</div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Amount" class="fp-amt" data-order="<?= $h($row['amount']); ?>">&#8369; <?= number_format((float)$row['amount'], 2); ?></td>
                                                        <td data-label="Status">
                                                            <?php if ($row['status'] === 'paid'): ?>
                                                                <span class="fp-status paid">Fully paid</span>
                                                            <?php elseif ($row['status'] === 'partial'): ?>
                                                                <span class="fp-status partial">Partial — &#8369; <?= number_format((float)$row['balance'], 2); ?> balance</span>
                                                            <?php else: ?>
                                                                <span class="fp-status na">&mdash;</span>
                                                            <?php endif; ?>
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

                    <div style="height:40px;"></div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>

    <!-- FEE MODAL -->
    <div class="modal fade fp-modal" id="fpFeeModal" tabindex="-1" role="dialog" aria-labelledby="fpFeeTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="fpFeeTitle"><i class="mdi mdi-tag-multiple"></i> Choose a fee</h5>
                        <div class="fp-modal-sub"><?= $h($periodLabel); ?> &middot; <?= $h($termLabel); ?><?php if ((string)$course !== ''): ?> &middot; <?= $h($course); ?><?php endif; ?></div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    $options = array_merge(
                        [['fee' => '', 'label' => 'All fees', 'payers' => (int)$allStudents, 'amount' => $allAmount]],
                        array_map(function ($f) {
                            return ['fee' => (string)$f['fee'], 'label' => (string)$f['fee'], 'payers' => (int)$f['payers'], 'amount' => (float)$f['amount']];
                        }, (array)$fees)
                    );
                    ?>
                    <?php foreach ($options as $opt): ?>
                        <?php
                        $isActive = (string)$opt['fee'] === (string)$fee;
                        $share = $shareOf($opt['amount']);
                        ?>
                        <a class="fp-opt <?= $isActive ? 'active' : ''; ?>" href="<?= $h($feeUrl($opt['fee'])); ?>" <?= $isActive ? 'aria-current="true"' : ''; ?>>
                            <span class="fp-opt-mark"><i class="mdi <?= $isActive ? 'mdi-check-circle' : 'mdi-radiobox-blank'; ?>"></i></span>
                            <span class="fp-opt-body">
                                <span class="fp-opt-top">
                                    <span class="fp-opt-name"><?= $h($opt['label']); ?></span>
                                    <span class="fp-opt-amt">&#8369;<?= number_format((float)$opt['amount'], 2); ?></span>
                                </span>
                                <span class="fp-opt-bar" aria-hidden="true"><span style="width:<?= number_format(min(100, max(0, $share)), 1, '.', ''); ?>%"></span></span>
                                <span class="fp-opt-sub">
                                    <span><b><?= number_format((int)$opt['payers']); ?></b> student<?= (int)$opt['payers'] === 1 ? '' : 's'; ?> paid</span>
                                    <?php if ((string)$opt['fee'] === ''): ?>
                                        <span><?= number_format(count((array)$fees)); ?> fee<?= count((array)$fees) === 1 ? '' : 's'; ?></span>
                                    <?php else: ?>
                                        <span><?= number_format($share, 1); ?>% of collections</span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($fees)): ?>
                        <div class="fp-opt-empty">No collections recorded in this period.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER PANEL (opens on the right) -->
    <form method="get" action="<?= base_url('Accounting/feePayers'); ?>" id="fpFilterPanel" hidden>
        <div class="sd-form-fields">
            <div class="form-group">
                <label for="fee">Fee</label>
                <select id="fee" name="fee" class="form-control">
                    <option value="">All fees</option>
                    <?php foreach ((array)$fees as $f): ?>
                        <option value="<?= $h($f['fee']); ?>" <?= (string)$f['fee'] === (string)$fee ? 'selected' : ''; ?>><?= $h($f['fee']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="course">Course</label>
                <select id="course" name="course" class="form-control">
                    <option value="">All courses</option>
                    <?php foreach ((array)$courseList as $opt): ?>
                        <option value="<?= $h($opt); ?>" <?= strcasecmp((string)$opt, (string)$course) === 0 ? 'selected' : ''; ?>><?= $h($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="sem">Semester</label>
                    <select id="sem" name="sem" class="form-control">
                        <option value="">All terms</option>
                        <?php foreach ((array)$semOptions as $opt): ?>
                            <option value="<?= $h($opt); ?>" <?= (string)$opt === (string)$sem ? 'selected' : ''; ?>><?= $h($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sy">School Year</label>
                    <select id="sy" name="sy" class="form-control">
                        <option value="">All</option>
                        <?php foreach ((array)$syOptions as $opt): ?>
                            <option value="<?= $h($opt); ?>" <?= (string)$opt === (string)$sy ? 'selected' : ''; ?>><?= $h($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control" value="<?= $h($from); ?>" required>
                </div>
                <div class="form-group">
                    <label for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control" value="<?= $h($to); ?>" required>
                </div>
            </div>
            <small class="sd-help">Same term and dates as the Income Statement, so each fee's total matches its revenue line. "Fully paid" counts everything the student paid toward the fee that term.</small>
        </div>
        <div class="sd-actions">
            <a href="<?= base_url('Accounting/feePayers'); ?>" class="sd-btn-ghost">This term</a>
            <button type="submit" class="sd-btn"><i class="mdi mdi-filter-outline"></i> Apply filter</button>
        </div>
    </form>

    <?php include('includes/side_drawer.php'); ?>
    <script>
        if (window.SideDrawer) {
            SideDrawer.fromElement(document.getElementById('fpFilterPanel'), {
                title: 'Filter payers', icon: 'mdi-filter-outline', width: 400,
                trigger: '[data-fp-filter]', focus: 'select'
            });
            StudentBalancePanel({ url: <?= json_encode(site_url('Accounting/studentSummary')); ?> });
        }

        $('#fpFeeModal').on('shown.bs.modal', function() {
            var current = this.querySelector('.fp-opt.active') || this.querySelector('.fp-opt');
            if (current) current.focus();
        });

        $(function() {
            var title = <?= json_encode(((string)$fee === '' ? 'Paid Students by Fee' : 'Paid Students - ' . (string)$fee) . ' (' . $periodLabel . ')'); ?>;
            var filename = <?= json_encode('paid_students_' . preg_replace('/[^A-Za-z0-9]+/', '_', strtolower((string)$fee === '' ? 'all_fees' : (string)$fee)) . '_' . $from . '_to_' . $to); ?>;
            var exportOptions = { modifier: { search: 'applied', order: 'applied' } };

            var table = $('#payersTable').DataTable({
                pageLength: 50,
                lengthMenu: [25, 50, 100, 500],
                autoWidth: false,
                order: [],
                language: { emptyTable: 'No payments for this fee in the selected period.' },
                buttons: [
                    { extend: 'copyHtml5', text: 'Copy', className: 'btn btn-secondary btn-sm', title: title, exportOptions: exportOptions },
                    { extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: title, filename: filename, exportOptions: exportOptions }
                ]
            });
            table.buttons().container().appendTo('#payersExportButtons');
        });
    </script>
</body>

</html>

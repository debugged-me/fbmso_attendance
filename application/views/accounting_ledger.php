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
    .kpi.green .icon { background: #dcfce7; color: #16a34a; }
    .kpi.red .icon { background: #fef2f2; color: #dc2626; }
    .kpi.blue .icon { background: #eef2ff; color: #4266d4; }
    .kpi.net-negative .count { color: #dc2626; }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }

    #ledgerTable td, #ledgerTable th { vertical-align: middle; }
    .ledger-type-badge { font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: 3px 9px; border-radius: 999px; }
    .ledger-type-income { background: #dcfce7; color: #16a34a; }
    .ledger-type-expense { background: #fef2f2; color: #dc2626; }
</style>

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
                            <h4 class="up-page-title">Ledger</h4>
                            <div class="up-page-sub">
                                Collections and expenses side by side, with a running balance —
                                <?= htmlspecialchars(date('M d, Y', strtotime((string)$from)), ENT_QUOTES, 'UTF-8'); ?>
                                to
                                <?= htmlspecialchars(date('M d, Y', strtotime((string)$to)), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/accounting'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#filterModal">
                                <i class="mdi mdi-filter-outline"></i> Filter
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.84rem;">
                        <i class="mdi mdi-information-outline"></i>
                        <b>How this works:</b> every valid payment collected from a student adds to the balance;
                        every recorded school expense subtracts from it. The list starts from &#8369;0 at the beginning
                        of the date range you picked and runs newest-first, so the top row's Balance is the net
                        cash position (Gross Collections &minus; Total Expenses) for the whole period.
                    </div>

                    <div class="kpi-grid">
                        <div class="card kpi green">
                            <div class="card-body">
                                <div>
                                    <h2 class="count mb-1">&#8369;<?= number_format((float)$gross, 2); ?></h2>
                                    <p class="label mb-0">Gross Collections</p>
                                </div>
                                <div class="icon"><i class="mdi mdi-cash-plus"></i></div>
                            </div>
                        </div>
                        <div class="card kpi red">
                            <div class="card-body">
                                <div>
                                    <h2 class="count mb-1">&#8369;<?= number_format((float)$spent, 2); ?></h2>
                                    <p class="label mb-0">Total Expenses</p>
                                </div>
                                <div class="icon"><i class="mdi mdi-cash-minus"></i></div>
                            </div>
                        </div>
                        <div class="card kpi blue<?= (float)$net < 0 ? ' net-negative' : ''; ?>">
                            <div class="card-body">
                                <div>
                                    <h2 class="count mb-1">&#8369;<?= number_format((float)$net, 2); ?></h2>
                                    <p class="label mb-0">Net (Gross &minus; Expenses)</p>
                                </div>
                                <div class="icon"><i class="mdi mdi-scale-balance"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- LEDGER -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-book-open-variant"></i> Ledger Entries</h4>
                                    <span class="badge badge-purple"><?= count($rows); ?> entries</span>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="ledgerTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Reference</th>
                                                    <th class="text-right">Amount</th>
                                                    <th class="text-right">Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($rows)): ?>
                                                    <?php foreach ($rows as $row): ?>
                                                        <?php $isIncome = $row['type'] === 'income'; ?>
                                                        <tr>
                                                            <td data-label="Date" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars(date('M d, Y', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td data-label="Type"><span class="ledger-type-badge <?= $isIncome ? 'ledger-type-income' : 'ledger-type-expense'; ?>"><?= $isIncome ? 'Collection' : 'Expense'; ?></span></td>
                                                            <td data-label="Description" style="color:var(--up-ink);font-weight:600;"><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td data-label="Reference" style="color:var(--up-muted);font-family:ui-monospace,Menlo,Consolas,monospace;"><?= htmlspecialchars($row['ref'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td data-label="Amount" class="text-right" style="font-weight:700;white-space:nowrap;color:<?= $isIncome ? '#16a34a' : '#dc2626'; ?>;"><?= $isIncome ? '+' : '&minus;'; ?> ₱ <?= number_format($row['amount'], 2); ?></td>
                                                            <td data-label="Balance" class="text-right" style="font-weight:800;color:var(--up-ink);white-space:nowrap;">₱ <?= number_format($row['balance'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" style="text-align:center;padding:32px 16px;color:var(--up-muted);">
                                                            <i class="mdi mdi-database-off-outline" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5;"></i>
                                                            No collections or expenses in this period.
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

    <!-- FILTER MODAL -->
    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="get" action="<?= base_url('Accounting/ledger'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">
                            <i class="mdi mdi-filter-outline"></i> Filter Ledger
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="from" class="mb-1">From</label>
                            <input type="date" id="from" name="from" class="form-control"
                                value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="to" class="mb-1">To</label>
                            <input type="date" id="to" name="to" class="form-control"
                                value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="up-btn up-btn-primary">
                            <i class="mdi mdi-filter-outline"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('#ledgerTable').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                autoWidth: false
            });
        });
    </script>
</body>

</html>

<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

<style>
    .kpi {
        border: 1px solid var(--up-line, #e6ebf5);
        border-radius: 18px;
        background: var(--up-card, #fff);
        box-shadow: 0 6px 18px rgba(13, 27, 75, .05);
        margin-bottom: 0;
    }
    .kpi .card-body { display: flex; align-items: center; justify-content: space-between; padding: 20px 22px; gap: 12px; }
    .kpi .count { font-size: 1.6rem; font-weight: 800; color: var(--up-ink, #0d1b4b); margin: 0; line-height: 1; letter-spacing: -.01em; }
    .kpi .label { margin: 6px 0 0; color: var(--up-muted, #6b7a99); font-weight: 700; font-size: .72rem; letter-spacing: .16em; text-transform: uppercase; }
    .kpi .icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex: 0 0 auto; }
    .kpi.blue .icon { background: #eef2ff; color: #4266d4; }
    .kpi.green .icon { background: #dcfce7; color: #16a34a; }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }

    #feesTable.dataTable { table-layout: fixed; }
    #feesTable td, #feesTable th { vertical-align: middle; }
    #feesTable td[data-label="Description"] { font-size: .92rem; }
    #feesTable .fee-icon { width: 34px; height: 34px; border-radius: 10px; background: #eef2ff; color: #4266d4; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; flex: 0 0 auto; }
    #feesTable .fee-desc-wrap { display: flex; align-items: center; }

    /* ACTION BUTTONS: keep Edit + Delete on one line — the Delete form is a
       block element by default and would otherwise stack under Edit. */
    .action-wrap { display: inline-flex; align-items: center; gap: 8px; flex-wrap: nowrap; white-space: nowrap; }
    .action-wrap form { margin: 0; }
</style>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <?php
                    $flashSuccess = $this->session->flashdata('success');
                    $flashDanger  = $this->session->flashdata('danger');
                    ?>

                    <!-- Title + actions -->
                    <div class="page-title-box">
                            <h4 class="up-page-title">Fees Setup</h4>
                            <div class="up-page-sub">Configure fee descriptions and amounts used for student payments.</div>
                            <hr class="up-divider" />
                        </div>

                    <?php if (!empty($flashSuccess)): ?>
                        <div class="up-flash up-flash-success">
                            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashDanger)): ?>
                        <div class="up-flash up-flash-danger">
                            <?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php
                    $feeTotal = 0.0;
                    foreach ($fees as $fee) {
                        $feeTotal += (float)$fee->Amount;
                    }
                    ?>
                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <div class="nx-stat blue">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format(count((array)$fees)); ?></div>
                                    <div class="nx-stat-label">Configured Fees</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-format-list-bulleted"></i></div>
                            </div>
                            <div class="nx-stat-foot">Listed below <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat green">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format($feeTotal, 2); ?></div>
                                    <div class="nx-stat-label">Combined Value</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-currency-usd"></i></div>
                            </div>
                            <div class="nx-stat-foot">If all fees are paid once <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <a class="nx-stat violet" href="javascript:void(0)" data-toggle="modal" data-target="#addFeeModal">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><i class="mdi mdi-plus-circle-outline" style="font-size:1.6rem;"></i></div>
                                    <div class="nx-stat-label">Add Fee</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-cash-plus"></i></div>
                            </div>
                            <div class="nx-stat-foot">Configure a new fee <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                    </div>

                    <!-- Configured Fees -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
<div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
<h4><i class="mdi mdi-currency-usd"></i> Configured Fees</h4>
                                    <span class="badge badge-purple"><?= count($fees); ?> items</span>
</div>
<div class="pl-actions">
                            <a href="<?= base_url($this->session->userdata('level') === 'Cashier' ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#addFeeModal">
                                <i class="mdi mdi-plus-circle"></i> Add Fee
                            </button>
                        </div>
</div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="feesTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-right" style="width:180px;">Amount</th>
                                                    <th style="width:220px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($fees as $fee): ?>
                                                    <?php $paidCount = (int)($fee->PaidCount ?? 0); ?>
                                                    <tr>
                                                        <td data-label="Description" style="font-weight:600;color:var(--up-ink);">
                                                            <span class="fee-desc-wrap"><span class="fee-icon"><i class="mdi mdi-cash"></i></span><?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <?php if ($paidCount > 0): ?>
                                                                <div style="font-size:.72rem;color:var(--up-muted);margin-top:4px;">
                                                                    <i class="mdi mdi-lock-outline"></i>
                                                                    Locked this term — <?= $paidCount; ?> payment<?= $paidCount === 1 ? '' : 's'; ?> recorded
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-blue);">₱ <?= number_format((float)$fee->Amount, 2); ?></td>
                                                        <td data-label="Action" class="up-rt-actions">
                                                            <div class="action-wrap">
                                                                <button type="button"
                                                                    class="up-btn up-btn-ghost edit-fee-btn"
                                                                    style="padding:8px 14px;font-size:.8rem;"
                                                                    <?= $paidCount > 0 ? 'disabled title="Students have already paid this fee this term. Its name and amount stay locked until next term."' : ''; ?>
                                                                    data-feesid="<?= (int)$fee->feesid; ?>"
                                                                    data-description="<?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-amount="<?= htmlspecialchars((string)$fee->Amount, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="mdi <?= $paidCount > 0 ? 'mdi-lock-outline' : 'mdi-pencil'; ?>"></i> Edit
                                                                </button>
                                                                <form method="post"
                                                                    action="<?= base_url('Accounting/course_setUp'); ?>"
                                                                    class="delete-fee-form d-inline"
                                                                    data-ui-confirm="Assessments already computed with this fee are not recalculated."
                                                                    data-ui-confirm-title="Delete this fee item?"
                                                                    data-ui-confirm-ok="Delete fee">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="feesid" value="<?= (int)$fee->feesid; ?>">
                                                                    <button type="submit" class="up-btn up-btn-danger" style="padding:8px 14px;font-size:.8rem;">
                                                                        <i class="mdi mdi-delete"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
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

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>

    <!-- ADD FEE MODAL -->
    <div class="modal fade" id="addFeeModal" tabindex="-1" role="dialog" aria-labelledby="addFeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="<?= base_url('Accounting/course_setUp'); ?>" id="addFeeForm">
                    <input type="hidden" name="action" value="add">

                    <div class="modal-header">
                        <h5 class="modal-title" id="addFeeModalLabel">
                            <i class="mdi mdi-plus-circle-outline"></i> Add Fee
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="feeDescription">Description</label>
                            <input type="text" class="form-control" id="feeDescription" name="Description"
                                value="<?= htmlspecialchars(set_value('Description'), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feeAmount">Amount</label>
                            <input type="number" class="form-control" id="feeAmount" name="Amount" min="0" step="0.01"
                                value="<?= htmlspecialchars(set_value('Amount'), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="up-btn up-btn-primary">
                            <i class="mdi mdi-plus-circle-outline"></i> Add Fee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT FEE MODAL -->
    <div class="modal fade" id="editFeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="<?= base_url('Accounting/course_setUp'); ?>" id="editFeeForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="feesid" id="editFeeId" value="">

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="mdi mdi-pencil"></i> Edit Fee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="editFeeDescription">Description</label>
                            <input type="text" class="form-control" id="editFeeDescription" name="Description" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editFeeAmount">Amount</label>
                            <input type="number" class="form-control" id="editFeeAmount" name="Amount" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="up-btn up-btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            $(function() {
                // DataTable
                $('#feesTable').DataTable({
                    pageLength: 10,
                    autoWidth: false,
                    order: [
                        [0, 'asc']
                    ]
                });

                // cleanup add modal on close (prevents double-init)
                $('#addFeeModal').on('hidden.bs.modal', function() {
                    $('#addFeeForm')[0].reset();
                    $('#feeDescription').val('');
                    $('#feeAmount').val('');
                });

                // open edit modal and fill
                $(document).on('click', '.edit-fee-btn', function() {
                    var $btn = $(this);

                    var feeId = String($btn.data('feesid') || '');
                    var description = String($btn.data('description') || '');
                    var amount = String($btn.data('amount') || '');

                    $('#editFeeId').val(feeId);
                    $('#editFeeDescription').val(description);

                    var amountNum = parseFloat(amount);
                    if (isNaN(amountNum)) amountNum = 0;
                    $('#editFeeAmount').val(amountNum.toFixed(2));

                    $('#editFeeModal').modal('show');
                });
            });
        })();
    </script>
</body>

</html>

<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />

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
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Fees Setup</h4>
                            <div class="up-page-sub">Configure fee descriptions and amounts used for student payments.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#addFeeModal">
                                <i class="mdi mdi-plus-circle"></i> Add Fee
                            </button>
                        </div>
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

                    <!-- Configured Fees -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
                                    <h4><i class="mdi mdi-currency-usd"></i> Configured Fees</h4>
                                    <span class="badge badge-purple"><?= count($fees); ?> items</span>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive">
                                        <table id="feesTable" class="table table-bordered table-sm dt-responsive nowrap up-rt" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($fees as $fee): ?>
                                                    <tr>
                                                        <td data-label="Description" style="font-weight:600;color:var(--up-ink);"><?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-blue);">₱ <?= number_format((float)$fee->Amount, 2); ?></td>
                                                        <td data-label="Action" class="up-rt-actions">
                                                            <button type="button"
                                                                class="up-btn up-btn-ghost edit-fee-btn"
                                                                style="padding:8px 14px;font-size:.8rem;"
                                                                data-feesid="<?= (int)$fee->feesid; ?>"
                                                                data-description="<?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-amount="<?= htmlspecialchars((string)$fee->Amount, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <i class="mdi mdi-pencil"></i> Edit
                                                            </button>
                                                            <form method="post"
                                                                action="<?= base_url('Accounting/course_setUp'); ?>"
                                                                class="delete-fee-form mb-0"
                                                                data-ui-confirm="Assessments already computed with this fee are not recalculated."
                                                                data-ui-confirm-title="Delete this fee item?"
                                                                data-ui-confirm-ok="Delete fee">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="feesid" value="<?= (int)$fee->feesid; ?>">
                                                                <button type="submit" class="up-btn up-btn-danger" style="padding:8px 14px;font-size:.8rem;">
                                                                    <i class="mdi mdi-delete"></i> Delete
                                                                </button>
                                                            </form>
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

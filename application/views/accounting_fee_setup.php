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
                    <?php
                    $flashSuccess = $this->session->flashdata('success');
                    $flashDanger  = $this->session->flashdata('danger');
                    ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="page-title mb-0">Fees Setup</h4>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addFeeModal">
                                        <i class="mdi mdi-plus-circle"></i> Add Fee
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($flashSuccess)): ?>
                        <div class="alert alert-success" role="alert"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($flashDanger)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <!-- CONFIGURED FEES AT THE BOTTOM -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h5 class="header-title mb-0">Configured Fees</h5>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="feesTable" class="table table-bordered table-sm dt-responsive nowrap" style="width:100%">
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
                                                        <td><?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td class="text-right"><?= number_format((float)$fee->Amount, 2); ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">

                                                                <!-- EDIT BUTTON -->
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-info btn-sm mr-2 edit-fee-btn"
                                                                    data-feesid="<?= (int)$fee->feesid; ?>"
                                                                    data-description="<?= htmlspecialchars((string)$fee->Description, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-amount="<?= htmlspecialchars((string)$fee->Amount, ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="mdi mdi-pencil"></i>
                                                                </button>

                                                                <!-- DELETE FORM -->
                                                                <form method="post"
                                                                    action="<?= base_url('Accounting/course_setUp'); ?>"
                                                                    class="delete-fee-form mb-0"
                                                                    data-ui-confirm="Assessments already computed with this fee are not recalculated."
                                                                    data-ui-confirm-title="Delete this fee item?"
                                                                    data-ui-confirm-ok="Delete fee">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="feesid" value="<?= (int)$fee->feesid; ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                                        <i class="mdi mdi-delete"></i>
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
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-plus-circle-outline"></i> Add Fee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT FEE MODAL (same as yours) -->
    <div class="modal fade" id="editFeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="<?= base_url('Accounting/course_setUp'); ?>" id="editFeeForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="feesid" id="editFeeId" value="">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Fee</h5>
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
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
                    // optional: reset form
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

                // delete confirm — see data-ui-confirm on .delete-fee-form
            });
        })();
    </script>
</body>

</html>
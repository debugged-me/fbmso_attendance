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
                    $paymentFormOld = isset($payment_form_old) && is_array($payment_form_old) ? $payment_form_old : [];
                    $openPaymentModal = !empty($open_payment_modal);
                    ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="page-title mb-0">Payment Entry</h4>
                                    <?php if (!empty($semester) || !empty($sy)): ?>
                                        <div class="mt-1">
                                            <span class="badge badge-info">
                                                Context: <?= htmlspecialchars(trim(($semester ?: 'N/A') . ' | ' . ($sy ?: 'N/A')), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#paymentModal">
                                        <i class="mdi mdi-plus-circle"></i> Add Payment
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

                    <!-- RECENT PAYMENTS BELOW -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h5 class="header-title mb-0">Recent Student Payments</h5>
                                        <small class="text-muted">Latest entries</small>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="recentPaymentsTable" class="table table-bordered table-sm dt-responsive nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>O.R.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_payments as $row): ?>
                                                    <?php
                                                    $studentName = trim((string)($row->LastName ?? ''));
                                                    if ($studentName !== '') $studentName .= ', ';
                                                    $studentName .= trim((string)(($row->FirstName ?? '') . ' ' . ($row->MiddleName ?? '')));
                                                    if (trim($studentName) === '') $studentName = (string)($row->StudentNumber ?? '');
                                                    $rowId = (int)($row->ID ?? 0);
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td class="text-right"><?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                        <td>
                                                            <!-- ACTIONS: spaced + tooltip labels -->
                                                            <div class="action-wrap">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary action-btn print-receipt-btn"
                                                                    data-toggle="tooltip" data-placement="top" title="Print Receipt"
                                                                    data-id="<?= $rowId; ?>"
                                                                    data-ornumber="<?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-date="<?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-studentname="<?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-studentno="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-description="<?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-amount="<?= htmlspecialchars((string)($row->Amount ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-sem="<?= htmlspecialchars((string)($row->Sem ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-sy="<?= htmlspecialchars((string)($row->SY ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-cashier="<?= htmlspecialchars((string)($row->Cashier ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="mdi mdi-printer"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-info action-btn edit-payment-btn"
                                                                    data-toggle="tooltip" data-placement="top" title="Edit Payment"
                                                                    data-id="<?= $rowId; ?>"
                                                                    data-studentno="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-ornumber="<?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-date="<?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-description="<?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-amount="<?= htmlspecialchars((string)($row->Amount ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="mdi mdi-pencil"></i>
                                                                </button>

                                                                <form method="post" action="<?= base_url('Accounting/deletePayment'); ?>" class="delete-payment-form d-inline">
                                                                    <input type="hidden" name="id" value="<?= $rowId; ?>">
                                                                    <button type="submit"
                                                                        class="btn btn-sm btn-outline-danger action-btn"
                                                                        data-toggle="tooltip" data-placement="top" title="Delete Payment">
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

    <!-- ADD PAYMENT MODAL -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="<?= base_url('Accounting/Payment'); ?>" id="paymentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">
                            <i class="mdi mdi-cash-plus"></i> Add Student Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="Sem" value="<?= htmlspecialchars((string)$semester, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="SY" value="<?= htmlspecialchars((string)$sy, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- keep description for controller, but hidden -->
                        <input type="hidden" name="description" id="descriptionHidden" value="">

                        <div class="form-group">
                            <label for="studentSelect">Student</label>
                            <select class="form-control" id="studentSelect" name="StudentNumber" required>
                                <option value="">Select student...</option>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $studentNo = trim((string)($student->StudentNumber ?? ''));
                                    $ln = trim((string)($student->LastName ?? ($student->LName ?? '')));
                                    $fn = trim((string)($student->FirstName ?? ($student->FName ?? '')));
                                    $mn = trim((string)($student->MiddleName ?? ($student->MName ?? '')));

                                    $name = trim(($ln !== '' ? $ln . ', ' : '') . $fn . ($mn !== '' ? ' ' . $mn : ''));
                                    $optionText = ($name !== '') ? trim($studentNo . ' - ' . $name) : $studentNo;

                                    $course = trim((string)($student->Course ?? ''));
                                    $major = trim((string)($student->Major ?? ''));
                                    $yearLevel = trim((string)($student->YearLevel ?? ''));
                                    ?>
                                    <option
                                        value="<?= htmlspecialchars($studentNo, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-course="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-major="<?= htmlspecialchars($major, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-yearlevel="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="orNumber">O.R. Number</label>
                                <input type="text" class="form-control" id="orNumber" name="ORNumber"
                                    value="<?= htmlspecialchars((string)$next_or_number, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="2026-0001"
                                    pattern="\d{4}-\d{4,}"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    spellcheck="false"
                                    aria-describedby="orNumberStatus">
                                <small id="orNumberStatus" class="form-text text-muted">
                                    Format: YYYY-0001. Default uses the payment date year.
                                </small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="paymentDate">Payment Date</label>
                                <input type="date" class="form-control" id="paymentDate" name="PDate"
                                    value="<?= htmlspecialchars((string)$default_payment_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descriptionField">Description <span class="text-danger">*</span></label>
                            <select class="form-control" id="descriptionField" name="descriptionField" required>
                                <option value="">Select or type description...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" id="amount" name="Amount" min="0" step="0.01" required>
                        </div>

                        <div class="alert alert-warning mt-2 mb-0" id="feeWarning" style="display:none;">
                            Please enter or select a <b>Description</b>.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">
                            <i class="mdi mdi-close"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save"></i> Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT PAYMENT MODAL -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="<?= base_url('Accounting/updatePayment'); ?>" id="editPaymentForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPaymentModalLabel">
                            <i class="mdi mdi-pencil"></i> Edit Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId" value="">
                        <input type="hidden" name="Sem" value="<?= htmlspecialchars((string)$semester, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="SY" value="<?= htmlspecialchars((string)$sy, ENT_QUOTES, 'UTF-8'); ?>">

                        <input type="hidden" name="description" id="editDescriptionHidden" value="">

                        <div class="form-group">
                            <label for="editStudentSelect">Student</label>
                            <select class="form-control" id="editStudentSelect" name="StudentNumber" required>
                                <option value="">Select student...</option>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $studentNo = trim((string)($student->StudentNumber ?? ''));
                                    $ln = trim((string)($student->LastName ?? ($student->LName ?? '')));
                                    $fn = trim((string)($student->FirstName ?? ($student->FName ?? '')));
                                    $mn = trim((string)($student->MiddleName ?? ($student->MName ?? '')));

                                    $name = trim(($ln !== '' ? $ln . ', ' : '') . $fn . ($mn !== '' ? ' ' . $mn : ''));
                                    $optionText = ($name !== '') ? trim($studentNo . ' - ' . $name) : $studentNo;

                                    $course = trim((string)($student->Course ?? ''));
                                    $major = trim((string)($student->Major ?? ''));
                                    $yearLevel = trim((string)($student->YearLevel ?? ''));
                                    ?>
                                    <option
                                        value="<?= htmlspecialchars($studentNo, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-course="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-major="<?= htmlspecialchars($major, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-yearlevel="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="editOrNumber">O.R. Number</label>
                                <input type="text" class="form-control" id="editOrNumber" name="ORNumber" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="editPaymentDate">Payment Date</label>
                                <input type="date" class="form-control" id="editPaymentDate" name="PDate" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="editDescriptionField">Description <span class="text-danger">*</span></label>
                            <select class="form-control" id="editDescriptionField" name="descriptionField" required>
                                <option value="">Select or type description...</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label for="editAmount">Amount</label>
                            <input type="number" class="form-control" id="editAmount" name="Amount" min="0" step="0.01" required>
                        </div>

                        <div class="alert alert-warning mt-2 mb-0" id="editFeeWarning" style="display:none;">
                            Please enter or select a <b>Description</b>.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">
                            <i class="mdi mdi-close"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PRINT RECEIPT MODAL -->
    <div class="modal fade" id="printReceiptModal" tabindex="-1" role="dialog" aria-labelledby="printReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document" style="max-width: 5.5in;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printReceiptModalLabel">Receipt Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="receipt-preview">
                        <div class="receipt-body">
                            <div class="receipt-header">
                                <div class="school-name" id="prevSchoolName"></div>
                                <div class="receipt-title">OFFICIAL RECEIPT</div>
                            </div>

                            <div class="receipt-divider"></div>

                            <div class="receipt-ornumber">
                                <span class="label">OR NO:</span>
                                <span class="value" id="prevORNumber"></span>
                            </div>

                            <div class="receipt-details">
                                <div class="detail-row">
                                    <span class="label">Date:</span>
                                    <span class="value" id="prevDate"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Received From:</span>
                                    <span class="value" id="prevStudentName"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Student No:</span>
                                    <span class="value" id="prevStudentNo"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Description:</span>
                                    <span class="value" id="prevDescription"></span>
                                </div>
                            </div>

                            <div class="receipt-divider"></div>

                            <div class="receipt-amount">
                                <span class="label">AMOUNT:</span>
                                <span class="value" id="prevAmount"></span>
                            </div>

                            <div class="receipt-divider"></div>

                            <div class="receipt-signature">
                                <div class="sig-line"></div>
                                <div class="sig-label" id="prevCashier"></div>
                                <div class="sig-pos">Cashier</div>
                            </div>

                            <div class="receipt-footer" id="prevFooter"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="mdi mdi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var baseUrl = <?= json_encode(base_url()); ?>;
            var schoolName = <?= json_encode((string)($settings->SchoolName ?? 'School')); ?>;
            var defaultOrNumber = <?= json_encode((string)$next_or_number); ?>;
            var defaultPaymentDate = <?= json_encode((string)$default_payment_date); ?>;
            var restoredPaymentForm = <?= json_encode($paymentFormOld); ?>;
            var autoOpenPaymentModal = <?= $openPaymentModal ? 'true' : 'false'; ?>;
            var defaultOrHelp = 'Format: YYYY-0001. Default uses the payment date year.';
            var lastSuggestedOrNumber = defaultOrNumber;
            var useRestoredPaymentState = autoOpenPaymentModal;
            var orNumberEditedManually = false;
            var orCheckTimer = null;
            var orCheckRequest = null;
            var paymentFormSubmitting = false;

            function initTooltips() {
                if ($.fn.tooltip) {
                    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
                        container: 'body',
                        trigger: 'hover'
                    });
                }
            }

            function initDescSelect($el, items, dropdownParent) {
                $el.empty().append($('<option>', {
                    value: '',
                    text: 'Select or type description...'
                }));

                (items || []).forEach(function(item) {
                    var $opt = $('<option>', {
                        value: item.description || '',
                        text: (item.description || '')
                    });
                    $opt.attr('data-feesid', item.feesid || '');
                    $opt.attr('data-amount', item.amount || 0);
                    $el.append($opt);
                });

                if ($el.data('select2')) $el.select2('destroy');

                $el.select2({
                    width: '100%',
                    tags: true,
                    tokenSeparators: [],
                    dropdownParent: dropdownParent
                });
            }

            function loadFeesToBothSelects() {
                return $.getJSON(baseUrl + 'Accounting/ajaxFees')
                    .then(function(resp) {
                        var fees = (resp && resp.fees) ? resp.fees : [];
                        initDescSelect($('#descriptionField'), fees, $('#paymentModal'));
                        initDescSelect($('#editDescriptionField'), fees, $('#editPaymentModal'));
                        return fees;
                    })
                    .catch(function() {
                        initDescSelect($('#descriptionField'), [], $('#paymentModal'));
                        initDescSelect($('#editDescriptionField'), [], $('#editPaymentModal'));
                        return [];
                    });
            }

            function applyDescriptionSelection($select, $hidden, $amount, $warn) {
                var val = ($select.val() || '').trim();
                var $opt = $select.find('option:selected');
                var amt = $opt.attr('data-amount') || '';

                if (val) {
                    $hidden.val(val);
                    $warn.hide();
                } else {
                    $hidden.val('');
                }

                if (amt !== '') {
                    $amount.val(Number(amt).toFixed(2));
                }
            }

            function validateDesc($hidden, $warn) {
                var desc = ($hidden.val() || '').trim();
                if (!desc) {
                    $warn.show();
                    return false;
                }
                $warn.hide();
                return true;
            }

            function setSelectValue($select, value) {
                var normalized = $.trim(value || '');

                if (!normalized) {
                    $select.val('').trigger('change');
                    return;
                }

                var hasOption = false;
                $select.find('option').each(function() {
                    if ($(this).val() === normalized) {
                        hasOption = true;
                        return false;
                    }
                });

                if (!hasOption) {
                    $select.append($('<option>', {
                        value: normalized,
                        text: normalized
                    }));
                }

                $select.val(normalized).trigger('change');
            }

            function setOrNumberStatus(state, message) {
                var $input = $('#orNumber');
                var $status = $('#orNumberStatus');

                $input.removeClass('is-invalid is-valid');
                $status.removeClass('text-danger text-success text-muted');

                if (state === 'error') {
                    $input.addClass('is-invalid');
                    $status.addClass('text-danger').text(message || 'Invalid O.R. number.');
                    return;
                }

                if (state === 'success') {
                    $input.addClass('is-valid');
                    $status.addClass('text-success').text(message || 'O.R. number is available.');
                    return;
                }

                $status.addClass('text-muted').text(message || defaultOrHelp);
            }

            function applySuggestedOrNumber(orNumber) {
                if (!orNumber) {
                    return;
                }

                $('#orNumber').val(orNumber);
                lastSuggestedOrNumber = orNumber;
                orNumberEditedManually = false;
            }

            function shouldCheckOrNumber(orNumber) {
                return orNumber === '' || /^\d+$/.test(orNumber) || /^\d{4}-\d+$/.test(orNumber);
            }

            function fetchOrNumberStatus(orNumber, paymentDate) {
                if (orCheckRequest && orCheckRequest.readyState !== 4) {
                    orCheckRequest.abort();
                }

                orCheckRequest = $.ajax({
                    url: baseUrl + 'Accounting/ajaxOrNumberStatus',
                    dataType: 'json',
                    data: {
                        or_number: orNumber,
                        payment_date: paymentDate
                    }
                });

                return orCheckRequest;
            }

            function validateOrNumber(options) {
                var deferred = $.Deferred();
                var settings = options || {};
                var normalizeField = settings.normalizeField !== false;
                var orNumber = $.trim($('#orNumber').val() || '');
                var paymentDate = $.trim($('#paymentDate').val() || '');

                if (orNumber !== '' && !shouldCheckOrNumber(orNumber)) {
                    setOrNumberStatus('error', 'Use the O.R. number format YYYY-0001.');
                    deferred.resolve(false);
                    return deferred.promise();
                }

                fetchOrNumberStatus(orNumber, paymentDate)
                    .done(function(resp) {
                        resp = resp || {};

                        if (normalizeField && resp.normalized_new && resp.normalized) {
                            $('#orNumber').val(resp.normalized);
                            orNumber = resp.normalized;
                        }

                        if (!orNumber && resp.suggested) {
                            applySuggestedOrNumber(resp.suggested);
                            setOrNumberStatus('neutral', resp.message || defaultOrHelp);
                            deferred.resolve(true);
                            return;
                        }

                        if (resp.valid_format === false) {
                            setOrNumberStatus('error', resp.message || 'Use the O.R. number format YYYY-0001.');
                            deferred.resolve(false);
                            return;
                        }

                        if (resp.available === false) {
                            setOrNumberStatus('error', resp.message || 'O.R. number already exists.');
                            deferred.resolve(false);
                            return;
                        }

                        if (resp.suggested) {
                            lastSuggestedOrNumber = resp.suggested;
                        }

                        var successMessage = resp.message || 'O.R. number is available.';
                        if (resp.normalized_new && resp.normalized) {
                            successMessage = 'Will be saved as ' + resp.normalized + '. ' + successMessage;
                        }

                        setOrNumberStatus('success', successMessage);
                        deferred.resolve(true);
                    })
                    .fail(function(xhr, textStatus) {
                        if (textStatus !== 'abort') {
                            setOrNumberStatus('neutral', defaultOrHelp);
                        }
                        deferred.resolve(false);
                    });

                return deferred.promise();
            }

            function refreshSuggestedOrNumber() {
                var paymentDate = $.trim($('#paymentDate').val() || '');

                fetchOrNumberStatus('', paymentDate)
                    .done(function(resp) {
                        if (resp && resp.suggested) {
                            applySuggestedOrNumber(resp.suggested);
                            setOrNumberStatus('neutral', resp.message || defaultOrHelp);
                        }
                    })
                    .fail(function(xhr, textStatus) {
                        if (textStatus !== 'abort') {
                            setOrNumberStatus('neutral', defaultOrHelp);
                        }
                    });
            }

            function resetPaymentForm() {
                if ($('#paymentForm').length && $('#paymentForm')[0]) {
                    $('#paymentForm')[0].reset();
                }

                $('#orNumber').val(defaultOrNumber);
                $('#paymentDate').val(defaultPaymentDate);
                $('#amount').val('');
                $('#descriptionHidden').val('');
                $('#feeWarning').hide();

                if ($('#studentSelect').data('select2')) {
                    $('#studentSelect').val('').trigger('change');
                }

                if ($('#descriptionField').data('select2')) {
                    setSelectValue($('#descriptionField'), '');
                }

                if (orCheckTimer) {
                    window.clearTimeout(orCheckTimer);
                    orCheckTimer = null;
                }

                if (orCheckRequest && orCheckRequest.readyState !== 4) {
                    orCheckRequest.abort();
                }

                paymentFormSubmitting = false;
                lastSuggestedOrNumber = defaultOrNumber;
                orNumberEditedManually = false;
                setOrNumberStatus('neutral', defaultOrHelp);
            }

            function restorePaymentForm() {
                var state = restoredPaymentForm || {};
                var restoredOrNumber = $.trim(state.ORNumber || '') || defaultOrNumber;
                var restoredPaymentDate = $.trim(state.PDate || '') || defaultPaymentDate;

                $('#paymentDate').val(restoredPaymentDate);
                $('#orNumber').val(restoredOrNumber);
                $('#amount').val($.trim(state.Amount || ''));
                $('#descriptionHidden').val($.trim(state.description || ''));
                $('#feeWarning').hide();

                if ($('#studentSelect').data('select2')) {
                    $('#studentSelect').val($.trim(state.StudentNumber || '')).trigger('change');
                } else {
                    $('#studentSelect').val($.trim(state.StudentNumber || ''));
                }

                setSelectValue($('#descriptionField'), $.trim(state.description || ''));
                $('#descriptionHidden').val($.trim(state.description || ''));
                $('#amount').val($.trim(state.Amount || ''));

                paymentFormSubmitting = false;
                lastSuggestedOrNumber = defaultOrNumber;
                orNumberEditedManually = restoredOrNumber !== '' && restoredOrNumber !== defaultOrNumber;
                setOrNumberStatus('neutral', defaultOrHelp);
                validateOrNumber({
                    normalizeField: true
                });
            }

            $(function() {
                // DataTable
                var dt = $('#recentPaymentsTable').DataTable({
                    pageLength: 10,
                    order: [
                        [0, 'desc']
                    ],
                    drawCallback: function() {
                        initTooltips();
                    }
                });

                // tooltips first run
                initTooltips();

                // DELETE confirm
                $(document).on('submit', '.delete-payment-form', function(e) {
                    if (!window.confirm('Delete this payment entry?')) {
                        e.preventDefault();
                    }
                });

                // ADD modal init
                $('#paymentModal').on('shown.bs.modal', function() {
                    $('#studentSelect').select2({
                        width: '100%',
                        dropdownParent: $('#paymentModal')
                    });
                    loadFeesToBothSelects().then(function() {
                        if (useRestoredPaymentState) {
                            restorePaymentForm();
                            useRestoredPaymentState = false;
                            return;
                        }

                        resetPaymentForm();
                        refreshSuggestedOrNumber();
                    });
                });

                $('#paymentModal').on('hidden.bs.modal', function() {
                    resetPaymentForm();

                    if ($.fn.select2) {
                        try {
                            $('#studentSelect').select2('destroy');
                        } catch (e) {}
                        try {
                            $('#descriptionField').select2('destroy');
                        } catch (e) {}
                    }
                    useRestoredPaymentState = false;
                });

                $(document).on('change', '#descriptionField', function() {
                    applyDescriptionSelection($('#descriptionField'), $('#descriptionHidden'), $('#amount'), $('#feeWarning'));
                });

                $('#orNumber').on('input', function() {
                    var value = $.trim($(this).val() || '');
                    orNumberEditedManually = value !== '' && value !== lastSuggestedOrNumber;

                    if (orCheckTimer) {
                        window.clearTimeout(orCheckTimer);
                        orCheckTimer = null;
                    }

                    if (value === '') {
                        setOrNumberStatus('neutral', defaultOrHelp);
                        return;
                    }

                    if (!shouldCheckOrNumber(value)) {
                        setOrNumberStatus('neutral', 'Use the O.R. number format YYYY-0001.');
                        return;
                    }

                    orCheckTimer = window.setTimeout(function() {
                        validateOrNumber({
                            normalizeField: false
                        });
                    }, 350);
                });

                $('#orNumber').on('blur', function() {
                    validateOrNumber({
                        normalizeField: true
                    });
                });

                $('#paymentDate').on('change', function() {
                    var currentOrNumber = $.trim($('#orNumber').val() || '');

                    if (!currentOrNumber || !orNumberEditedManually || currentOrNumber === lastSuggestedOrNumber) {
                        refreshSuggestedOrNumber();
                        return;
                    }

                    validateOrNumber({
                        normalizeField: true
                    });
                });

                $('#paymentForm').on('submit', function(e) {
                    var $form = $(this);

                    if (paymentFormSubmitting) {
                        return;
                    }

                    if (!validateDesc($('#descriptionHidden'), $('#feeWarning'))) {
                        e.preventDefault();
                        return;
                    }

                    e.preventDefault();
                    validateOrNumber({
                        normalizeField: true
                    }).done(function(isValid) {
                        if (!isValid) {
                            paymentFormSubmitting = false;
                            return;
                        }

                        paymentFormSubmitting = true;
                        $form[0].submit();
                    });
                });

                // EDIT open
                $(document).on('click', '.edit-payment-btn', function() {
                    var $btn = $(this);

                    var id = String($btn.data('id') || '');
                    var studentNo = String($btn.data('studentno') || '');
                    var orNumber = String($btn.data('ornumber') || '');
                    var date = String($btn.data('date') || '');
                    var desc = String($btn.data('description') || '');
                    var amount = String($btn.data('amount') || '');

                    $('#editId').val(id);
                    $('#editOrNumber').val(orNumber);
                    $('#editPaymentDate').val(date);
                    $('#editAmount').val(parseFloat(amount || 0).toFixed(2));

                    $('#editFeeWarning').hide();

                    $('#editPaymentModal').modal('show');

                    $('#editPaymentModal').one('shown.bs.modal', function() {
                        $('#editStudentSelect').select2({
                            width: '100%',
                            dropdownParent: $('#editPaymentModal')
                        });

                        loadFeesToBothSelects().then(function() {
                            $('#editStudentSelect').val(studentNo).trigger('change');
                            $('#editDescriptionField').val(desc).trigger('change');
                            $('#editDescriptionHidden').val(desc);
                        });
                    });
                });

                $('#editPaymentModal').on('hidden.bs.modal', function() {
                    $('#editPaymentForm')[0].reset();
                    if ($.fn.select2) {
                        try {
                            $('#editStudentSelect').select2('destroy');
                        } catch (e) {}
                        try {
                            $('#editDescriptionField').select2('destroy');
                        } catch (e) {}
                    }
                    $('#editDescriptionHidden').val('');
                    $('#editFeeWarning').hide();
                });

                $(document).on('change', '#editDescriptionField', function() {
                    applyDescriptionSelection($('#editDescriptionField'), $('#editDescriptionHidden'), $('#editAmount'), $('#editFeeWarning'));
                });

                $('#editPaymentForm').on('submit', function(e) {
                    if (!validateDesc($('#editDescriptionHidden'), $('#editFeeWarning'))) e.preventDefault();
                });

                // PRINT receipt
                $(document).on('click', '.print-receipt-btn', function() {
                    var data = {
                        ornumber: $(this).data('ornumber'),
                        date: $(this).data('date'),
                        studentname: $(this).data('studentname'),
                        studentno: $(this).data('studentno'),
                        description: $(this).data('description'),
                        amount: $(this).data('amount'),
                        sem: $(this).data('sem'),
                        sy: $(this).data('sy'),
                        cashier: $(this).data('cashier')
                    };

                    if (data.date && data.date !== '0000-00-00') {
                        var dateObj = new Date(data.date);
                        data.date = dateObj.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }

                    $('#prevSchoolName').text(schoolName);
                    $('#prevORNumber').text(data.ornumber);
                    $('#prevDate').text(data.date);
                    $('#prevStudentName').text(data.studentname);
                    $('#prevStudentNo').text(data.studentno);
                    $('#prevDescription').text(data.description);
                    $('#prevAmount').text('PHP ' + Number(data.amount || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#prevCashier').text(data.cashier);
                    $('#prevFooter').text('Sem/SY: ' + data.sem + ' ' + data.sy);

                    $('#printReceiptModal').modal('show');
                });

                if (autoOpenPaymentModal) {
                    $('#paymentModal').modal('show');
                }
            });
        })();

        function printReceipt() {
            var printContent = $('.receipt-preview').html();
            var printWindow = window.open('', '', 'height=500,width=500');
            printWindow.document.write('<html><head><title>Receipt</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
                body { font-family: "Courier New", monospace; margin: 0; padding: 0.2in; }
                .receipt-body { font-size: 11px; line-height: 1.3; }
                .receipt-header { text-align: center; margin-bottom: 0.15in; }
                .school-name { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
                .receipt-title { font-size: 11px; font-weight: bold; letter-spacing: 1px; }
                .receipt-divider { border-top: 1px dashed #333; margin: 0.1in 0; }
                .receipt-ornumber { display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 0.1in; font-size: 12px; }
                .receipt-details { margin-bottom: 0.1in; }
                .detail-row { display: flex; justify-content: space-between; margin-bottom: 3px; padding: 0 2px; }
                .detail-row .label { font-weight: bold; width: 35%; flex-shrink: 0; }
                .detail-row .value { text-align: right; word-wrap: break-word; }
                .receipt-amount { display: flex; justify-content: space-between; font-weight: bold; font-size: 12px; padding: 0.1in 0; margin-bottom: 0.1in; }
                .receipt-signature { text-align: center; margin-top: 0.2in; margin-bottom: 0.1in; }
                .sig-line { border-top: 1px solid #333; width: 60%; margin: 0 auto 2px; height: 20px; }
                .sig-label { font-size: 9px; font-weight: bold; }
                .sig-pos { font-size: 8px; margin-top: 1px; }
                .receipt-footer { text-align: center; font-size: 9px; border-top: 1px dashed #333; padding-top: 3px; margin-top: 0.1in; }
            `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>

    <style>
        /* ACTION BUTTONS: spacing + consistent size */
        .action-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .action-wrap .action-btn {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .action-wrap form {
            margin: 0;
        }

        .receipt-preview {
            max-width: 5.5in;
            margin: 0 auto;
            background: #fff;
        }

        .receipt-body {
            padding: 0.3in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.3;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 0.15in;
        }

        .school-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .receipt-title {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .receipt-divider {
            border-top: 1px dashed #333;
            margin: 0.1in 0;
        }

        .receipt-ornumber {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 0.1in;
            font-size: 12px;
        }

        .receipt-details {
            margin-bottom: 0.1in;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            padding: 0 2px;
        }

        .detail-row .label {
            font-weight: bold;
            width: 35%;
            flex-shrink: 0;
        }

        .detail-row .value {
            text-align: right;
            word-wrap: break-word;
            word-break: break-word;
        }

        .receipt-amount {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 12px;
            padding: 0.1in 0;
            margin-bottom: 0.1in;
        }

        .receipt-signature {
            text-align: center;
            margin-top: 0.2in;
            margin-bottom: 0.1in;
        }

        .sig-line {
            border-top: 1px solid #333;
            width: 60%;
            margin: 0 auto 2px;
            height: 20px;
        }

        .sig-label {
            font-size: 9px;
            font-weight: bold;
        }

        .sig-pos {
            font-size: 8px;
            margin-top: 1px;
        }

        .receipt-footer {
            text-align: center;
            font-size: 9px;
            border-top: 1px dashed #333;
            padding-top: 3px;
            margin-top: 0.1in;
        }
    </style>
</body>

</html>

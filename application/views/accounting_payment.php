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
                    $flashWarning = $this->session->flashdata('warning');
                    $flashDanger  = $this->session->flashdata('danger');
                    $paymentFormOld = isset($payment_form_old) && is_array($payment_form_old) ? $payment_form_old : [];
                    $openPaymentModal = !empty($open_payment_modal);
                    ?>

                    <!-- Title + actions -->
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Payment Entry</h4>
                            <div class="up-page-sub">Record and manage student payments. Print receipts on demand.</div>
                            <hr class="up-divider" />
                        </div>
                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#paymentModal">
                                <i class="mdi mdi-plus-circle"></i> Add Payment
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($flashSuccess)): ?>
                        <div class="up-flash up-flash-success">
                            <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashWarning)): ?>
                        <div class="up-flash up-flash-info">
                            <?= htmlspecialchars($flashWarning, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashDanger)): ?>
                        <div class="up-flash up-flash-danger">
                            <?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <!-- RECENT PAYMENTS -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head" style="flex-wrap:wrap;gap:8px;">
                                    <h4><i class="mdi mdi-cash-multiple"></i> Recent Student Payments</h4>
                                    <div class="d-flex align-items-center" style="gap:8px;">
                                        <select id="termFilter" class="form-control form-control-sm" style="max-width:220px;" title="Filter by term">
                                            <option value="">All terms</option>
                                            <?php foreach (($term_options ?? []) as $t): ?>
                                                <?php $termLabel = trim((string)$t->Semester . ' ' . (string)$t->SY); ?>
                                                <option value="<?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="badge badge-purple"><?= count($recent_payments); ?> entries</span>
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="recentPaymentsTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>O.R.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th>Sem/SY</th>
                                                    <th class="text-right" style="white-space:nowrap;">Amount</th>
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
                                                        <td data-label="Date" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="O.R." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:var(--up-blue);"><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Sem/SY" style="color:var(--up-muted);"><?= htmlspecialchars(trim((string)($row->Sem ?? '') . ' ' . (string)($row->SY ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-ink);white-space:nowrap;">₱ <?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                        <td data-label="Actions" class="up-rt-actions">
                                                            <div class="action-wrap">
                                                                <button type="button"
                                                                    class="up-btn up-btn-ghost print-receipt-btn"
                                                                    style="padding:8px 12px;font-size:.78rem;"
                                                                    data-toggle="tooltip" data-placement="top" title="Print Receipt"
                                                                    data-id="<?= $rowId; ?>">
                                                                    <i class="mdi mdi-printer"></i> Receipt
                                                                </button>

                                                                <button type="button"
                                                                    class="up-btn up-btn-ghost edit-payment-btn"
                                                                    style="padding:8px 12px;font-size:.78rem;"
                                                                    data-toggle="tooltip" data-placement="top" title="Edit Payment"
                                                                    data-id="<?= $rowId; ?>"
                                                                    data-studentno="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-ornumber="<?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-date="<?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-description="<?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-amount="<?= htmlspecialchars((string)($row->Amount ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="mdi mdi-pencil"></i> Edit
                                                                </button>

                                                                <form method="post" action="<?= base_url('Accounting/deletePayment'); ?>" class="delete-payment-form d-inline"
                                                                    data-ui-confirm="The payment is removed from the student's ledger and their balance is recomputed."
                                                                    data-ui-confirm-title="Delete this payment entry?"
                                                                    data-ui-confirm-ok="Delete payment">
                                                                    <input type="hidden" name="id" value="<?= $rowId; ?>">
                                                                    <button type="submit"
                                                                        class="up-btn up-btn-danger"
                                                                        style="padding:8px 12px;font-size:.78rem;"
                                                                        data-toggle="tooltip" data-placement="top" title="Delete Payment">
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
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

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
                        <input type="hidden" name="payment_submit_token" value="<?= htmlspecialchars((string)($payment_submit_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.82rem;">
                            <i class="mdi mdi-calendar-check"></i>
                            Recorded under the active term:
                            <b><?= htmlspecialchars(trim((string)$semester . ' ' . (string)$sy), ENT_QUOTES, 'UTF-8'); ?></b>
                            <span id="studentTermHint" class="d-block mt-1" style="display:none;"></span>
                        </div>

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
                                    $studentSem = trim((string)($student->Semester ?? ''));
                                    $studentSy = trim((string)($student->SY ?? ''));
                                    ?>
                                    <option
                                        value="<?= htmlspecialchars($studentNo, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-course="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-major="<?= htmlspecialchars($major, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-yearlevel="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-sem="<?= htmlspecialchars($studentSem, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-sy="<?= htmlspecialchars($studentSy, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">
                            <i class="mdi mdi-close"></i> Close
                        </button>
                        <button type="submit" class="up-btn up-btn-primary" id="paymentSubmitBtn">
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

                        <input type="hidden" name="description" id="editDescriptionHidden" value="">

                        <div class="form-group">
                            <label for="editStudentSelect">Student</label>
                            <select class="form-control" id="editStudentSelect" name="StudentNumber" required>
                                <option value="">Select student...</option>
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
                        <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">
                            <i class="mdi mdi-close"></i> Close
                        </button>
                        <button type="submit" class="up-btn up-btn-primary">
                            <i class="mdi mdi-content-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var baseUrl = <?= json_encode(base_url()); ?>;
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
            var paymentSubmitDefaultHtml = '';

            // Show the selected student's enrolment term for context — the
            // payment itself always records under the active term.
            function updateStudentTermHint($select) {
                var $opt = $select.find('option:selected');
                var sem = $.trim($opt.attr('data-sem') || '');
                var sy = $.trim($opt.attr('data-sy') || '');
                var $hint = $('#studentTermHint');
                if (sem !== '' || sy !== '') {
                    $hint.text('Student enrolled in: ' + $.trim(sem + ' ' + sy)).show();
                } else {
                    $hint.hide().text('');
                }
            }

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

            function setPaymentSubmitState(isSubmitting) {
                var $submitBtn = $('#paymentSubmitBtn');
                if (!$submitBtn.length) {
                    return;
                }

                if (paymentSubmitDefaultHtml === '') {
                    paymentSubmitDefaultHtml = $submitBtn.html();
                }

                if (isSubmitting) {
                    $submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Saving...');
                    return;
                }

                $submitBtn.prop('disabled', false).html(paymentSubmitDefaultHtml);
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
                setPaymentSubmitState(false);
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
                setPaymentSubmitState(false);
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

                // Term filter — exact-match on the Sem/SY column (index 4)
                $('#termFilter').on('change', function() {
                    var v = this.value;
                    dt.column(4).search(
                        v === '' ? '' : '^' + $.fn.dataTable.util.escapeRegex(v) + '$',
                        true, false
                    ).draw();
                });

                // tooltips first run
                initTooltips();
                setPaymentSubmitState(false);

                // DELETE confirm — see data-ui-confirm on .delete-payment-form

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

                $(document).on('change', '#studentSelect', function() {
                    updateStudentTermHint($(this));
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
                        e.preventDefault();
                        return;
                    }

                    if (!validateDesc($('#descriptionHidden'), $('#feeWarning'))) {
                        e.preventDefault();
                        return;
                    }

                    e.preventDefault();
                    paymentFormSubmitting = true;
                    setPaymentSubmitState(true);
                    validateOrNumber({
                        normalizeField: true
                    }).done(function(isValid) {
                        if (!isValid) {
                            paymentFormSubmitting = false;
                            setPaymentSubmitState(false);
                            return;
                        }

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

                    // The edit select is populated by cloning the add form's
                    // options — rendering ~3000 <option> nodes twice would
                    // double this page's weight for no benefit.
                    var $editSel = $('#editStudentSelect');
                    if ($editSel.find('option').length <= 1) {
                        $('#studentSelect option').each(function() {
                            if (this.value !== '') {
                                $editSel.append($(this).clone());
                            }
                        });
                    }

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

                // PRINT receipt — open the dedicated receipt page in a new tab;
                // it renders the full receipt from the database and auto-prints.
                $(document).on('click', '.print-receipt-btn', function() {
                    var id = parseInt($(this).data('id'), 10);
                    if (!id) {
                        return;
                    }
                    window.open(baseUrl + 'Accounting/receipt/' + id + '?print=1', '_blank');
                });

                if (autoOpenPaymentModal) {
                    $('#paymentModal').modal('show');
                }
            });
        })();
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
    </style>
</body>

</html>

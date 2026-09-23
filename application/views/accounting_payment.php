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
                    <div class="page-title-box">
                        <h4 class="up-page-title">Payment Entry</h4>
                    </div>

                    <?php
                    $apRows = (array)($recent_payments ?? []);
                    $apCollected = 0.0; $apFull = 0; $apPart = 0;
                    foreach ($apRows as $row) {
                        $amt  = (float)($row->Amount ?? 0);
                        $full = (float)($row->FullAmount ?? 0);
                        $paid = (float)($row->TotalPaid ?? $amt);
                        $apCollected += $paid;
                        if ($full > 0) {
                            if ($paid + 0.004 < $full) $apPart++; else $apFull++;
                        }
                    }
                    ?>
                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <div class="nx-stat blue">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format(count($apRows)); ?></div>
                                    <div class="nx-stat-label">Payments Listed</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-format-list-bulleted"></i></div>
                            </div>
                            <div class="nx-stat-foot">Recent receipts below <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat green">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">&#8369;<?= number_format($apCollected, 2); ?></div>
                                    <div class="nx-stat-label">Collected</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-cash-check"></i></div>
                            </div>
                            <div class="nx-stat-foot">Sum of payments shown <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat cyan">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($apFull); ?></div>
                                    <div class="nx-stat-label">Fully Paid</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-check-decagram-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Fees settled in full <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat orange">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($apPart); ?></div>
                                    <div class="nx-stat-label">Partial</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-account-clock-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Still with balance <i class="mdi mdi-arrow-right"></i></div>
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
                                        <select id="dateFilter" class="form-control form-control-sm" style="max-width:220px;" title="Payments on">
                                            <option value="all" <?= ($date_filter ?? '') === 'all' ? 'selected' : ''; ?>>All dates</option>
                                            <?php
                                            $todayVal = (string)($today ?? '');
                                            $selectedDate = (string)($date_filter ?? '');
                                            $hasToday = false;
                                            foreach (($payment_dates ?? []) as $d):
                                                $dateVal = (string)($d->PDate ?? '');
                                                if ($dateVal === $todayVal) $hasToday = true;
                                                $label = date('M d, Y', strtotime($dateVal)) . ($dateVal === $todayVal ? ' (Today)' : '');
                                            ?>
                                                <option value="<?= htmlspecialchars($dateVal, ENT_QUOTES, 'UTF-8'); ?>" <?= $dateVal === $selectedDate ? 'selected' : ''; ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                            <?php if (!$hasToday): ?>
                                                <option value="<?= htmlspecialchars($todayVal, ENT_QUOTES, 'UTF-8'); ?>" <?= $todayVal === $selectedDate ? 'selected' : ''; ?>><?= htmlspecialchars(date('M d, Y', strtotime($todayVal)), ENT_QUOTES, 'UTF-8'); ?> (Today)</option>
                                            <?php endif; ?>
                                        </select>
                                        <span class="badge badge-purple"><?= count($recent_payments); ?> entries</span>
                                        <div class="pl-actions" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                                            <a href="<?= base_url($this->session->userdata('level') === 'Cashier' ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                                            </a>
                                            <a href="<?= base_url('Accounting/partialPayments'); ?>" class="up-btn up-btn-ghost">
                                                <i class="mdi mdi-account-clock-outline"></i> View Partial Payments
                                            </a>
                                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#paymentModal">
                                                <i class="mdi mdi-plus-circle"></i> Add Payment
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="recentPaymentsTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date &amp; Time</th>
                                                    <th>O.R.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th class="text-right" style="white-space:nowrap;">Amount</th>
                                                    <th>Status</th>
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
                                                    $pDate = (string)($row->PDate ?? '');
                                                    $pTime = (string)($row->pTime ?? '');
                                                    $dateTimeLabel = $pDate !== '' ? date('M d, Y', strtotime($pDate)) : '';
                                                    if ($pTime !== '') $dateTimeLabel .= ' ' . date('h:i A', strtotime($pTime));

                                                    $amount = (float)($row->Amount ?? 0);
                                                    $fullAmount = (float)($row->FullAmount ?? 0);
                                                    // Status reflects the fee's whole balance, not this one
                                                    // receipt — instalments that add up to the full price
                                                    // are fully paid, however many receipts it took.
                                                    $totalPaid = (float)($row->TotalPaid ?? $amount);
                                                    if ($fullAmount <= 0) {
                                                        $statusLabel = 'N/A';
                                                        $statusClass = 'badge-secondary';
                                                    } elseif ($totalPaid + 0.004 < $fullAmount) {
                                                        $statusLabel = 'Partial';
                                                        $statusClass = 'badge-warning';
                                                    } else {
                                                        $statusLabel = 'Fully Paid';
                                                        $statusClass = 'badge-success';
                                                    }
                                                    $dropdownId = 'paymentActions' . $rowId;
                                                    ?>
                                                    <tr>
                                                        <td data-label="Date & Time" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars($dateTimeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="O.R." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:var(--up-blue);"><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-ink);white-space:nowrap;">₱ <?= number_format($amount, 2); ?></td>
                                                        <td data-label="Status"><span class="badge <?= $statusClass; ?>" style="border-radius:6px;font-size:.72rem;font-weight:700;"><?= $statusLabel; ?></span></td>
                                                        <td data-label="Actions" class="up-rt-actions">
                                                            <div class="row-actions-menu">
                                                                <button type="button" class="up-btn up-btn-ghost row-actions-toggle" style="padding:8px 12px;font-size:.78rem;"
                                                                    id="<?= $dropdownId; ?>" aria-haspopup="true" aria-expanded="false">
                                                                    <i class="mdi mdi-dots-vertical"></i> Actions
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="<?= $dropdownId; ?>">
                                                                    <a class="dropdown-item print-receipt-btn" href="javascript:void(0);" data-id="<?= $rowId; ?>">
                                                                        <i class="mdi mdi-printer"></i> Print Receipt
                                                                    </a>
                                                                    <a class="dropdown-item edit-payment-btn" href="javascript:void(0);"
                                                                        data-id="<?= $rowId; ?>"
                                                                        data-studentno="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-ornumber="<?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-date="<?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-description="<?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                        data-amount="<?= htmlspecialchars((string)($row->Amount ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                                                        <i class="mdi mdi-pencil"></i> Edit Payment
                                                                    </a>
                                                                    <div class="dropdown-divider"></div>
                                                                    <form method="post" action="<?= base_url('Accounting/deletePayment'); ?>" class="delete-payment-form"
                                                                        data-ui-confirm="The payment is removed from the student's ledger and their balance is recomputed."
                                                                        data-ui-confirm-title="Delete this payment entry?"
                                                                        data-ui-confirm-ok="Delete payment">
                                                                        <input type="hidden" name="id" value="<?= $rowId; ?>">
                                                                        <button type="submit" class="dropdown-item text-danger">
                                                                            <i class="mdi mdi-delete"></i> Delete Payment
                                                                        </button>
                                                                    </form>
                                                                </div>
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
                            <i class="mdi mdi-cash-multiple"></i> Add Student Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="payment_submit_token" value="<?= htmlspecialchars((string)($payment_submit_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

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
                            <span id="studentTermHint" class="form-text text-muted" style="display:none;"></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="orNumber">O.R. Number</label>
                                <input type="text" class="form-control" id="orNumber" name="ORNumber"
                                    value="<?= htmlspecialchars((string)$next_or_number, ENT_QUOTES, 'UTF-8'); ?>"
                                    readonly>
                                <small id="orNumberStatus" class="form-text text-muted">
                                    Auto-generated from the payment date. Not editable.
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

                        <div class="fee-balance" id="feeBalance" style="display:none;">
                            <div class="fee-balance-item">
                                <span class="fee-balance-label">Full amount</span>
                                <span class="fee-balance-value" id="balanceFull">₱ 0.00</span>
                            </div>
                            <div class="fee-balance-item">
                                <span class="fee-balance-label">Already paid</span>
                                <span class="fee-balance-value" id="balancePaid" style="color:#16a34a;">₱ 0.00</span>
                            </div>
                            <div class="fee-balance-item">
                                <span class="fee-balance-label">Remaining</span>
                                <span class="fee-balance-value" id="balanceRemaining" style="color:#dc2626;">₱ 0.00</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" id="amount" name="Amount" min="0" step="0.01" readonly required>
                            <small class="form-text text-muted" id="amountHint">Set by the selected Description's configured fee.</small>
                        </div>

                        <div class="form-group custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="partialPayment" name="IsPartial" value="1">
                            <label class="custom-control-label" for="partialPayment">
                                Partial payment — student is paying less than the amount still owed
                            </label>
                        </div>

                        <div class="alert alert-warning mt-2 mb-0" id="partialNotice" style="display:none;"></div>
                        <div class="alert alert-success mt-2 mb-0" id="settledNotice" style="display:none;"></div>

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
                                <input type="text" class="form-control" id="editOrNumber" name="ORNumber" readonly>
                                <small class="form-text text-muted">Not editable — kept as originally issued.</small>
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

                        <div class="form-group">
                            <label for="editAmount">Amount</label>
                            <input type="number" class="form-control" id="editAmount" name="Amount" min="0" step="0.01" readonly required>
                            <small class="form-text text-muted" id="editAmountHint">Set by the selected Description's configured fee.</small>
                        </div>

                        <div class="form-group mb-0 custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="editPartialPayment" name="IsPartial" value="1">
                            <label class="custom-control-label" for="editPartialPayment">
                                Partial payment — student is paying less than the full fee amount
                            </label>
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
            var useRestoredPaymentState = autoOpenPaymentModal;
            var orRefreshRequest = null;
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

            function peso(value) {
                return '₱ ' + Number(value || 0).toFixed(2);
            }

            // Asks the server what this student still owes on this fee, so the
            // form bills the remaining balance rather than assuming every
            // payment starts from the full price again.
            function refreshFeeBalance() {
                var student = ($('#studentSelect').val() || '').trim();
                var description = ($('#descriptionHidden').val() || '').trim();
                var $amount = $('#amount');
                var $partial = $('#partialPayment');

                if (!student || !description) {
                    $('#feeBalance').hide();
                    $('#settledNotice').hide();
                    $('#partialNotice').hide();
                    $amount.removeData('remaining');
                    return;
                }

                $.getJSON(baseUrl + 'Accounting/ajaxFeeBalance', {
                    student: student,
                    description: description
                }).done(function(res) {
                    var full = parseFloat(res.full) || 0;
                    var paid = parseFloat(res.paid) || 0;
                    var remaining = parseFloat(res.remaining) || 0;

                    $amount.data('full-amount', full).data('remaining', remaining);

                    if (full <= 0) {
                        $('#feeBalance').hide();
                        $('#settledNotice').hide();
                        return;
                    }

                    $('#balanceFull').text(peso(full));
                    $('#balancePaid').text(peso(paid));
                    $('#balanceRemaining').text(peso(remaining));
                    $('#feeBalance').show();

                    if (remaining <= 0.004) {
                        $('#settledNotice')
                            .text(description + ' is already fully paid for this term. Nothing further is owed.')
                            .show();
                        $amount.val('').prop('readonly', true);
                        $partial.prop('checked', false).prop('disabled', true);
                        setSubmitDisabled(true);
                        return;
                    }

                    $('#settledNotice').hide();
                    $partial.prop('disabled', false);
                    setSubmitDisabled(false);

                    // Default to clearing the whole remaining balance; ticking
                    // "Partial" is what unlocks paying less than that.
                    if (!$partial.prop('checked')) {
                        $amount.val(remaining.toFixed(2));
                    }
                    updatePartialNotice();
                });
            }

            function setSubmitDisabled(disabled) {
                $('#paymentSubmitBtn').prop('disabled', !!disabled);
            }

            function updatePartialNotice() {
                var $amount = $('#amount');
                var remaining = parseFloat($amount.data('remaining'));
                var entered = parseFloat($amount.val());

                if (isNaN(remaining) || remaining <= 0 || isNaN(entered)) {
                    $('#partialNotice').hide();
                    return;
                }

                if (entered + 0.004 < remaining) {
                    $('#partialNotice')
                        .html('Short by ' + peso(remaining - entered) + '. Balance after this payment: <b>' +
                            peso(remaining - entered) + '</b>.')
                        .show();
                } else {
                    $('#partialNotice').hide();
                }
            }

            function applyDescriptionSelection($select, $hidden, $amount, $warn, $partialCheckbox) {
                var val = ($select.val() || '').trim();
                var $opt = $select.find('option:selected');
                var amt = $opt.attr('data-amount') || '';

                if (val) {
                    $hidden.val(val);
                    $warn.hide();
                } else {
                    $hidden.val('');
                }

                // Changing the fee resets any partial amount already typed —
                // the old partial amount belonged to a different fee.
                if ($partialCheckbox && $partialCheckbox.prop('checked')) {
                    $partialCheckbox.prop('checked', false).trigger('change');
                }

                if (amt !== '') {
                    $amount.data('full-amount', amt);
                    $amount.val(Number(amt).toFixed(2));
                }

                refreshFeeBalance();
            }

            function bindPartialToggle($checkbox, $amount, $hint) {
                $checkbox.on('change', function() {
                    var remaining = parseFloat($amount.data('remaining'));
                    if (isNaN(remaining)) remaining = parseFloat($amount.data('full-amount'));
                    if (isNaN(remaining)) remaining = 0;

                    if (this.checked) {
                        $amount.prop('readonly', false).attr('max', remaining || null).val('').focus();
                        $hint.text('Enter the amount being paid now (less than ' + peso(remaining) + ').');
                    } else {
                        $amount.prop('readonly', true).removeAttr('max').val(remaining.toFixed(2));
                        $hint.text('Settles the full remaining balance of ' + peso(remaining) + '.');
                    }
                    updatePartialNotice();
                });

                $amount.on('input', updatePartialNotice);
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

            // The O.R. field is read-only — this just refreshes the preview
            // shown to the cashier when they change the payment date.
            function refreshSuggestedOrNumber() {
                var paymentDate = $.trim($('#paymentDate').val() || '');

                if (orRefreshRequest && orRefreshRequest.readyState !== 4) {
                    orRefreshRequest.abort();
                }

                orRefreshRequest = $.getJSON(baseUrl + 'Accounting/ajaxOrNumberStatus', {
                    payment_date: paymentDate
                }).done(function(resp) {
                    if (resp && resp.suggested) {
                        $('#orNumber').val(resp.suggested);
                    }
                });
            }

            function resetPaymentForm() {
                if ($('#paymentForm').length && $('#paymentForm')[0]) {
                    $('#paymentForm')[0].reset();
                }

                $('#orNumber').val(defaultOrNumber);
                $('#paymentDate').val(defaultPaymentDate);
                $('#amount').val('').prop('readonly', true).removeAttr('max').removeData('full-amount').removeData('remaining');
                $('#partialPayment').prop('checked', false).prop('disabled', false);
                $('#amountHint').text("Set by the selected Description's configured fee.");
                $('#descriptionHidden').val('');
                $('#feeWarning').hide();
                $('#feeBalance').hide();
                $('#partialNotice').hide();
                $('#settledNotice').hide();

                if ($('#studentSelect').data('select2')) {
                    $('#studentSelect').val('').trigger('change');
                }

                if ($('#descriptionField').data('select2')) {
                    setSelectValue($('#descriptionField'), '');
                }

                paymentFormSubmitting = false;
                setPaymentSubmitState(false);
            }

            function restorePaymentForm() {
                var state = restoredPaymentForm || {};
                var restoredPaymentDate = $.trim(state.PDate || '') || defaultPaymentDate;

                $('#paymentDate').val(restoredPaymentDate);
                $('#orNumber').val(defaultOrNumber);
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
                refreshSuggestedOrNumber();
            }

            $(function() {
                // DataTable
                $('#recentPaymentsTable').DataTable({
                    pageLength: 10,
                    autoWidth: false,
                    order: [
                        [0, 'desc']
                    ],
                    drawCallback: function() {
                        initTooltips();
                    }
                });

                // Date filter — the list is scoped server-side (default: today),
                // so changing it just reloads with the chosen date.
                $('#dateFilter').on('change', function() {
                    window.location = baseUrl + 'Accounting/Payment?date=' + encodeURIComponent(this.value);
                });

                // Row actions menu — hand-rolled instead of Bootstrap's
                // dropdown/Popper, which mis-positioned it inside this
                // horizontally-scrollable table wrapper. Toggling a `.show`
                // class still uses Bootstrap's own CSS for the menu's look;
                // only the open/close and placement logic is custom.
                function closeRowActionMenus() {
                    $('.row-actions-menu .dropdown-menu.show').removeClass('show');
                }

                $(document).on('click', '.row-actions-toggle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $menu = $(this).siblings('.dropdown-menu');
                    var wasOpen = $menu.hasClass('show');
                    closeRowActionMenus();
                    if (wasOpen) {
                        return;
                    }

                    var rect = this.getBoundingClientRect();
                    var menuWidth = 220;
                    var left = Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8);
                    var el = $menu[0];

                    // Placement goes through custom properties: the theme's
                    // `.dropdown-menu.show { top: 100% !important }` beats a
                    // plain inline top, and on a fixed menu that resolves
                    // against the viewport, parking it off the bottom edge.
                    el.style.setProperty('--ra-left', Math.max(8, left) + 'px');
                    el.style.setProperty('--ra-top', (rect.bottom + 4) + 'px');
                    $menu.addClass('show');

                    // Flip above the button when the row sits near the
                    // viewport bottom and the menu would hang off-screen.
                    var menuHeight = el.offsetHeight;
                    if (rect.bottom + 4 + menuHeight > window.innerHeight - 8 && rect.top - menuHeight - 4 > 8) {
                        el.style.setProperty('--ra-top', (rect.top - menuHeight - 4) + 'px');
                    }
                });

                // Picking an item closes the menu — without Bootstrap's
                // dropdown JS nothing else does, so it would otherwise linger
                // at a stale position behind the modal it just opened.
                $(document).on('click', '.row-actions-menu .dropdown-item', closeRowActionMenus);

                // Close on outside click, and on scroll since a fixed-position
                // menu won't track the button if the page moves.
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.row-actions-menu').length) {
                        closeRowActionMenus();
                    }
                });
                $(document).on('scroll', '.up-rt-host', closeRowActionMenus);
                $(window).on('scroll', closeRowActionMenus);

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
                    refreshFeeBalance();
                });

                $(document).on('change', '#descriptionField', function() {
                    applyDescriptionSelection($('#descriptionField'), $('#descriptionHidden'), $('#amount'), $('#feeWarning'), $('#partialPayment'));
                });

                bindPartialToggle($('#partialPayment'), $('#amount'), $('#amountHint'));

                $('#paymentDate').on('change', function() {
                    refreshSuggestedOrNumber();
                });

                $('#paymentForm').on('submit', function(e) {
                    if (paymentFormSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    if (!validateDesc($('#descriptionHidden'), $('#feeWarning'))) {
                        e.preventDefault();
                        return;
                    }

                    paymentFormSubmitting = true;
                    setPaymentSubmitState(true);
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
                            // Selecting the description resets Amount to the fee's
                            // full price and unchecks Partial — restore what was
                            // actually paid afterwards, and flag it as partial if
                            // it's less than the full fee.
                            $('#editDescriptionField').val(desc).trigger('change');
                            $('#editDescriptionHidden').val(desc);

                            var storedAmount = parseFloat(amount);
                            var fullAmount = parseFloat($('#editAmount').data('full-amount'));
                            if (!isNaN(storedAmount)) {
                                if (!isNaN(fullAmount) && storedAmount < fullAmount - 0.004) {
                                    $('#editPartialPayment').prop('checked', true).trigger('change');
                                }
                                $('#editAmount').val(storedAmount.toFixed(2));
                            }
                        });
                    });
                });

                $('#editPaymentModal').on('hidden.bs.modal', function() {
                    $('#editPaymentForm')[0].reset();
                    $('#editAmount').prop('readonly', true).removeAttr('max').removeData('full-amount');
                    $('#editPartialPayment').prop('checked', false);
                    $('#editAmountHint').text("Set by the selected Description's configured fee.");
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
                    applyDescriptionSelection($('#editDescriptionField'), $('#editDescriptionHidden'), $('#editAmount'), $('#editFeeWarning'), $('#editPartialPayment'));
                });

                bindPartialToggle($('#editPartialPayment'), $('#editAmount'), $('#editAmountHint'));

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
        .fee-balance {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .fee-balance-item {
            flex: 1 1 120px;
            border: 1px solid #e6ebf5;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f8fbff;
        }

        .fee-balance-label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6b7a99;
            margin-bottom: 4px;
        }

        .fee-balance-value {
            display: block;
            font-size: 1rem;
            font-weight: 800;
            color: #0d1b4b;
            white-space: nowrap;
        }

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

        /* ROW ACTIONS DROPDOWN: placement comes from --ra-top/--ra-left, set
           by JS in viewport coords. Needs !important + extra specificity to
           beat the theme's `.dropdown-menu.show { top: 100% !important }`. */
        .row-actions-menu {
            display: inline-block;
            position: relative;
        }

        .row-actions-menu .dropdown-menu {
            min-width: 220px;
        }

        .row-actions-menu .dropdown-menu.show {
            position: fixed !important;
            top: var(--ra-top, 100%) !important;
            left: var(--ra-left, auto) !important;
            right: auto !important;
            margin: 0 !important;
            z-index: 1080;
        }

        .row-actions-menu .dropdown-menu form {
            margin: 0;
        }

        /* The card-mode rules in uniform-page.css centre every control in an
           actions cell; menu items still need to read as a left-aligned list. */
        .row-actions-menu .dropdown-menu .dropdown-item {
            text-align: left;
        }
    </style>
</body>

</html>

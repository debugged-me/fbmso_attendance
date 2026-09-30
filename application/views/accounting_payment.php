<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">
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
					$isAuditor = ((string)$this->session->userdata('level') === 'Auditor');
                    ?>

                    <!-- Title + actions -->
                    <div class="page-title-box">
						<h4 class="up-page-title"><?= $isAuditor ? 'Payment Records' : 'Payment Entry'; ?></h4>
                    </div>

					<?php include('includes/accounting_readonly_notice.php'); ?>

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
                                <div class="nx-stat-icon"><i class="mdi mdi-cash"></i></div>
                            </div>
                            <div class="nx-stat-foot">Sum of payments shown <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat cyan">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($apFull); ?></div>
                                    <div class="nx-stat-label">Fully Paid</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-check-decagram"></i></div>
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
                                    <div class="pl-actions" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
											<a href="<?= base_url(in_array($this->session->userdata('level'), ['Cashier', 'Auditor'], true) ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                                            <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                                        </a>
                                        <a href="<?= base_url('Accounting/partialPayments'); ?>" class="up-btn up-btn-ghost">
                                            <i class="mdi mdi-account-clock-outline"></i> View Partial Payments
                                        </a>
											<?php if (!$isAuditor): ?>
												<button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#paymentModal">
													<i class="mdi mdi-plus-circle"></i> Add Payment
												</button>
											<?php endif; ?>
                                    </div>
                                </div>
                                <div class="pay-toolbar">
                                    <div class="pay-filter">
                                        <label for="dateFilter" class="pay-filter-label"><i class="mdi mdi-calendar-month-outline"></i> Payments on</label>
                                        <select id="dateFilter" class="form-control form-control-sm pay-filter-select">
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
                                        <?php $entryCount = count($recent_payments); ?>
                                        <span class="pay-count"><strong><?= number_format($entryCount); ?></strong> <?= $entryCount === 1 ? 'entry' : 'entries'; ?></span>
                                    </div>
                                    <div class="pay-hint">
                                        <i class="mdi mdi-information-outline"></i> Click a student's name to see their full details and balance.
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
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);">
                                                            <?php if (trim((string)($row->StudentNumber ?? '')) !== ''): ?>
                                                                <a href="#" class="sd-link" data-student-balance data-sd-key="<?= htmlspecialchars((string)$row->StudentNumber, ENT_QUOTES, 'UTF-8'); ?>" title="View balance"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></a>
                                                            <?php else: ?>
                                                                <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>
                                                            <?php endif; ?>
                                                        </td>
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
													<?php if (!$isAuditor): ?>
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
                                                                        data-or="<?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                                        <input type="hidden" name="id" value="<?= $rowId; ?>">
                                                                        <input type="hidden" name="reason" value="">
                                                                        <button type="submit" class="dropdown-item text-danger">
                                                                            <i class="mdi mdi-delete"></i> Delete Payment
                                                                        </button>
                                                                    </form>
													<?php endif; ?>
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
    <?php include('includes/side_drawer.php'); ?>
    <script>
        if (window.StudentBalancePanel) StudentBalancePanel({ url: <?= json_encode(site_url('Accounting/studentSummary')); ?> });
    </script>

	<?php if (!$isAuditor): ?>
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

                        <div class="form-group custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="multiFeeToggle">
                            <label class="custom-control-label" for="multiFeeToggle">
                                Multiple payments — student is paying more than one fee (one O.R.)
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="descriptionField">Description <span class="text-danger">*</span></label>
                            <select class="form-control" id="descriptionField"></select>
                            <small class="form-text text-muted" id="descriptionHint">Select a fee, or type a description that isn't listed.</small>
                        </div>

                        <div class="pay-items" id="payItems" style="display:none;">
                            <div id="payItemsList"></div>
                            <div class="pay-items-total">
                                <span>Total</span>
                                <span id="payItemsTotal">₱ 0.00</span>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-2 mb-0" id="feeWarning" style="display:none;">
                            Please select at least one <b>Description</b> to pay.
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
	<?php endif; ?>

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

            function initDescSelect($el, items, dropdownParent, multiple) {
                $el.empty();
                if (!multiple) {
                    $el.append($('<option>', {
                        value: '',
                        text: 'Select or type description...'
                    }));
                }

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
                $el.prop('multiple', !!multiple);

                $el.select2({
                    width: '100%',
                    tags: true,
                    tokenSeparators: [],
                    dropdownParent: dropdownParent,
                    placeholder: multiple ? 'Select or type one or more descriptions...' : undefined
                });
            }

            var feeList = [];

            function isMultiFee() {
                return $('#multiFeeToggle').prop('checked');
            }

            function loadFeesToBothSelects() {
                return $.getJSON(baseUrl + 'Accounting/ajaxFees')
                    .then(function(resp) {
                        feeList = (resp && resp.fees) ? resp.fees : [];
                        initDescSelect($('#descriptionField'), feeList, $('#paymentModal'), isMultiFee());
                        initDescSelect($('#editDescriptionField'), feeList, $('#editPaymentModal'));
                        return feeList;
                    })
                    .catch(function() {
                        feeList = [];
                        initDescSelect($('#descriptionField'), [], $('#paymentModal'), isMultiFee());
                        initDescSelect($('#editDescriptionField'), [], $('#editPaymentModal'));
                        return [];
                    });
            }

            // The selected descriptions as a list, whichever mode the field is in.
            function selectedDescriptions() {
                var v = $('#descriptionField').val();
                if ($.isArray(v)) return v;
                return v ? [v] : [];
            }

            function setSelectedDescriptions(list) {
                var $desc = $('#descriptionField');
                list.forEach(function(d) {
                    var exists = false;
                    $desc.find('option').each(function() {
                        if (this.value === d) {
                            exists = true;
                            return false;
                        }
                    });
                    if (!exists) $desc.append($('<option>', { value: d, text: d }));
                });
                $desc.val(isMultiFee() ? list : (list[0] || '')).trigger('change');
            }

            // "Multiple payments" swaps Description between a single select and
            // a multi-select. What was already picked carries over; going back
            // to single keeps only the first fee line on screen (select2 reports
            // values in option order, not the order they were picked).
            function applyMultiFeeMode() {
                var multi = isMultiFee();
                var keep = payItemRows().map(function() {
                    return $(this).data('desc');
                }).get();
                if (!keep.length) keep = selectedDescriptions();
                if (!multi) keep = keep.slice(0, 1);

                initDescSelect($('#descriptionField'), feeList, $('#paymentModal'), multi);
                $('#descriptionHint').text(multi ?
                    "Pick every fee the student is paying now — they all go on one O.R. You can also type a description that isn't listed." :
                    "Select a fee, or type a description that isn't listed.");
                setSelectedDescriptions(keep);
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

            // ── Fees being paid (add form) ─────────────────────────────────
            // Each description picked becomes one line with its own balance,
            // amount and Partial tick. They are saved as separate payments
            // (balances stay per fee) under one shared O.R. number.
            var payItemSeq = 0;
            var payItemRestore = {};

            function payItemRows() {
                return $('#payItemsList .pay-item');
            }

            function findPayItem(desc) {
                return payItemRows().filter(function() {
                    return $(this).data('desc') === desc;
                });
            }

            function configuredFeeAmount(desc) {
                var amt = NaN;
                $('#descriptionField option').each(function() {
                    if (this.value === desc) {
                        amt = parseFloat($(this).attr('data-amount'));
                        return false;
                    }
                });
                return amt;
            }

            function buildPayItem(desc) {
                var i = payItemSeq++;
                var partialId = 'payItemPartial' + i;

                var $amount = $('<input>', {
                    type: 'number',
                    name: 'items[' + i + '][amount]',
                    'class': 'form-control pay-item-amount',
                    min: '0.01',
                    step: '0.01',
                    required: true,
                    readonly: true
                });

                var $row = $('<div class="pay-item is-loading">').append(
                    $('<input>', { type: 'hidden', name: 'items[' + i + '][description]', value: desc, 'class': 'pay-item-desc-input' }),
                    $('<div class="pay-item-head">').append(
                        $('<span class="pay-item-name">').text(desc),
                        $('<button type="button" class="pay-item-remove" title="Remove this fee" aria-label="Remove this fee">').html('&times;')
                    ),
                    $('<div class="pay-item-meta">').text('Checking balance...'),
                    $('<div class="pay-item-controls">').append(
                        $('<div class="input-group input-group-sm pay-item-amount-wrap">').append(
                            $('<div class="input-group-prepend">').append($('<span class="input-group-text">').text('₱')),
                            $amount
                        ),
                        $('<div class="custom-control custom-checkbox pay-item-partial-wrap">').append(
                            $('<input>', { type: 'checkbox', id: partialId, name: 'items[' + i + '][partial]', value: '1', 'class': 'custom-control-input pay-item-partial' }),
                            $('<label>', { 'for': partialId, 'class': 'custom-control-label' }).text('Partial')
                        )
                    ),
                    $('<div class="pay-item-note">')
                );

                $row.data('desc', desc);
                if (payItemRestore[desc]) {
                    $row.data('restore', payItemRestore[desc]);
                    delete payItemRestore[desc];
                }
                return $row;
            }

            // Asks the server what this student still owes on the line's fee,
            // so it bills the remaining balance rather than assuming every
            // payment starts from the full price again.
            function loadPayItemBalance($row) {
                var student = ($('#studentSelect').val() || '').trim();
                var desc = $row.data('desc');
                var prior = $row.data('req');
                if (prior && prior.readyState !== 4) prior.abort();

                if (!student) {
                    // No student yet: show the configured price as a preview.
                    var configured = configuredFeeAmount(desc);
                    applyPayItemBalance($row, configured > 0 ? configured : 0, 0, configured > 0 ? configured : 0);
                    $row.find('.pay-item-meta').text(configured > 0 ?
                        'Fee: ' + peso(configured) + ' — select a student to check what is still owed.' :
                        'Not a configured fee — enter the amount received.');
                    return;
                }

                $row.addClass('is-loading');
                $row.find('.pay-item-meta').text('Checking balance...');
                $row.data('req', $.getJSON(baseUrl + 'Accounting/ajaxFeeBalance', {
                    student: student,
                    description: desc
                }).done(function(res) {
                    applyPayItemBalance($row, parseFloat(res.full) || 0, parseFloat(res.paid) || 0, parseFloat(res.remaining) || 0);
                }).fail(function(xhr, status) {
                    if (status === 'abort') return;
                    $row.removeClass('is-loading');
                    $row.find('.pay-item-meta').text('Could not check the balance. Remove and re-add this fee to retry.');
                    updatePayTotal();
                }));
            }

            function applyPayItemBalance($row, full, paid, remaining) {
                var $amount = $row.find('.pay-item-amount');
                var $partial = $row.find('.pay-item-partial');
                var restore = $row.data('restore');
                $row.removeData('restore');

                $row.removeClass('is-loading is-settled is-free');
                $row.data('remaining', full > 0 ? remaining : null);
                $row.find('input').prop('disabled', false);

                if (full <= 0) {
                    // Free-text description: nothing to check the amount against.
                    $row.addClass('is-free');
                    $row.find('.pay-item-meta').text('Not a configured fee — enter the amount received.');
                    $partial.prop('checked', false);
                    $amount.prop('readonly', false).removeAttr('max');
                    if (restore) $amount.val(restore.amount || '');
                    updatePayItemNote($row);
                    return;
                }

                $row.find('.pay-item-meta').text('Full ' + peso(full) + ' · Paid ' + peso(paid) + ' · Remaining ' + peso(remaining));

                if (remaining <= 0.004) {
                    // Settled fees stay visible but are left out of the payment.
                    $row.addClass('is-settled');
                    $amount.val('');
                    $partial.prop('checked', false);
                    $row.find('input').prop('disabled', true);
                    updatePayItemNote($row);
                    return;
                }

                if (restore && restore.partial) {
                    $partial.prop('checked', true);
                }

                if ($partial.prop('checked')) {
                    $amount.prop('readonly', false).attr('max', remaining.toFixed(2));
                    if (restore) $amount.val(restore.amount || '');
                } else {
                    // Default to clearing the whole remaining balance; ticking
                    // "Partial" is what unlocks paying less than that.
                    $amount.prop('readonly', true).removeAttr('max').val(remaining.toFixed(2));
                }
                updatePayItemNote($row);
            }

            function updatePayItemNote($row) {
                var $note = $row.find('.pay-item-note');
                var remaining = $row.data('remaining');
                var entered = parseFloat($row.find('.pay-item-amount').val());

                if ($row.hasClass('is-settled')) {
                    $note.text('Already fully paid this term — not included.').show();
                } else if (remaining !== null && remaining !== undefined && !isNaN(entered) && entered + 0.004 < remaining) {
                    $note.text('Balance after this payment: ' + peso(remaining - entered)).show();
                } else if (remaining !== null && remaining !== undefined && !isNaN(entered) && entered > remaining + 0.004) {
                    $note.text('More than the ' + peso(remaining) + ' still owed.').show();
                } else {
                    $note.text('').hide();
                }
                updatePayTotal();
            }

            function updatePayTotal() {
                var total = 0;
                payItemRows().not('.is-settled').find('.pay-item-amount').each(function() {
                    var v = parseFloat(this.value);
                    if (!isNaN(v)) total += v;
                });
                $('#payItemsTotal').text(peso(total));
            }

            // Mirrors the multi-select into the item list, keeping lines that
            // are still selected (and whatever was typed in them) untouched.
            function syncPayItems() {
                var selected = selectedDescriptions();

                payItemRows().each(function() {
                    if (selected.indexOf($(this).data('desc')) === -1) {
                        var req = $(this).data('req');
                        if (req && req.readyState !== 4) req.abort();
                        $(this).remove();
                    }
                });

                selected.forEach(function(desc) {
                    if (!findPayItem(desc).length) {
                        var $row = buildPayItem(desc);
                        $('#payItemsList').append($row);
                        loadPayItemBalance($row);
                    }
                });

                $('#payItems').toggle(selected.length > 0);
                if (selected.length) $('#feeWarning').hide();
                updatePayTotal();
            }

            function clearPayItems() {
                payItemRestore = {};
                payItemRows().remove();
                $('#payItems').hide();
                updatePayTotal();
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
                });
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
                $('#feeWarning').hide();
                clearPayItems();

                if ($('#studentSelect').data('select2')) {
                    $('#studentSelect').val('').trigger('change');
                }

                // form.reset() already unticked "Multiple payments"; rebuild
                // Description as a single select to match.
                if ($('#descriptionField').data('select2')) {
                    applyMultiFeeMode();
                }

                paymentFormSubmitting = false;
                setPaymentSubmitState(false);
            }

            // Brings the form back after the server rejected it, including
            // every fee line with the amount and Partial tick it was sent with.
            function restorePaymentForm() {
                var state = restoredPaymentForm || {};
                var restoredPaymentDate = $.trim(state.PDate || '') || defaultPaymentDate;
                var items = $.isArray(state.items) ? state.items : [];

                $('#paymentDate').val(restoredPaymentDate);
                $('#orNumber').val(defaultOrNumber);
                $('#feeWarning').hide();
                clearPayItems();

                if ($('#studentSelect').data('select2')) {
                    $('#studentSelect').val($.trim(state.StudentNumber || '')).trigger('change');
                } else {
                    $('#studentSelect').val($.trim(state.StudentNumber || ''));
                }

                var descs = [];
                items.forEach(function(item) {
                    var d = $.trim(item.description || '');
                    if (!d || descs.indexOf(d) !== -1) return;
                    descs.push(d);
                    payItemRestore[d] = item;
                });

                // Several fees came back: reopen in "Multiple payments" mode.
                $('#multiFeeToggle').prop('checked', descs.length > 1);
                applyMultiFeeMode();
                setSelectedDescriptions(descs);

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

                // DELETE — two steps. 1) Choose which fees on the clicked
                // row's receipt to delete (one O.R. can cover several fees;
                // only the clicked one starts selected) and give a reason.
                // 2) Review what is deleted and what stays on the receipt,
                // then confirm. The reason is logged against each fee.
                var RECEIPT_ITEMS_URL = <?= json_encode(site_url('Accounting/ajaxReceiptItems')); ?>;

                function delEsc(v) {
                    return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                    });
                }

                function delMoney(n) {
                    return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                function delSum(list) {
                    return list.reduce(function(t, it) { return t + (Number(it.amount) || 0); }, 0);
                }

                function delFeeList(list, tone) {
                    return '<ul class="del-fee-list del-' + tone + '">' + list.map(function(it) {
                        return '<li><span>' + delEsc(it.description || 'Payment') + '</span><b>' + delMoney(it.amount) + '</b></li>';
                    }).join('') + '</ul>';
                }

                function openDeleteDialog(form, receipt) {
                    var clickedId = parseInt(form.querySelector('input[name="id"]').value, 10);
                    var items = receipt.items || [];
                    var multi = items.length > 1;
                    var selected = {};
                    items.forEach(function(it) {
                        if (it.deletable && (it.id === clickedId || !multi)) selected[it.id] = true;
                    });
                    var reason = '';
                    var step = 1;

                    var orLabel = receipt.or_number ? 'O.R. ' + receipt.or_number : 'Payment';
                    var subtitle = [orLabel, receipt.student_name || receipt.student_number, receipt.payment_date]
                        .filter(Boolean).join(' · ');

                    var handle = UI.modal({
                        title: 'Delete from receipt',
                        subtitle: subtitle,
                        icon: 'warning',
                        size: 'del',
                        body: ' ',
                        html: true,
                        buttons: [
                            {
                                label: 'Cancel',
                                variant: 'ghost',
                                onClick: function() {
                                    if (step === 2) { showSelect(); return false; }
                                }
                            },
                            {
                                label: 'Review deletion',
                                variant: 'danger',
                                onClick: function() {
                                    if (step === 1) { showReview(); return false; }
                                    submitDelete();
                                    return false;
                                }
                            }
                        ]
                    });

                    var box = handle.modal;
                    var titleEl = box.querySelector('.uk-modal-title');
                    var backBtn = box.querySelector('.uk-modal-foot [data-uk-index="0"]');
                    var okBtn = box.querySelector('.uk-modal-foot [data-uk-index="1"]');

                    function chosen() { return items.filter(function(it) { return selected[it.id]; }); }
                    function kept() { return items.filter(function(it) { return !selected[it.id]; }); }

                    function showSelect() {
                        step = 1;
                        titleEl.textContent = 'Delete from receipt';
                        backBtn.textContent = 'Cancel';

                        var rows = items.map(function(it) {
                            var off = !it.deletable;
                            return '<label class="del-row' + (off ? ' is-off' : '') + (selected[it.id] ? ' is-on' : '') + '">'
                                + '<input type="checkbox" class="del-check" value="' + it.id + '"'
                                + (off ? ' disabled' : '') + (selected[it.id] ? ' checked' : '') + '>'
                                + '<span class="del-row-text">'
                                + '<span class="del-row-desc">' + delEsc(it.description || 'Payment') + '</span>'
                                + (it.id === clickedId && multi ? '<span class="del-tag">Row you clicked</span>' : '')
                                + (off ? '<span class="del-tag">Can\'t be deleted</span>' : '')
                                + '</span>'
                                + '<span class="del-row-amt">' + delMoney(it.amount) + '</span>'
                                + '</label>';
                        }).join('');

                        handle.body.innerHTML =
                            '<p class="del-lead">' + (multi
                                ? 'This receipt has <b>' + items.length + ' fees</b>. Tick each fee you want to delete — fees you leave unticked stay on the receipt.'
                                : 'This fee will be removed from the student\'s ledger and their balance recomputed.') + '</p>'
                            + '<div class="del-list-head"><span>Fees on this receipt</span>'
                            + (multi ? '<span class="del-links"><button type="button" class="del-all">Select all</button><button type="button" class="del-none">Clear</button></span>' : '')
                            + '</div>'
                            + '<div class="del-rows">' + rows + '</div>'
                            + '<div class="del-summary"></div>'
                            + '<label class="del-field-label" for="delReason">Reason for deleting <span>(required)</span></label>'
                            + '<textarea id="delReason" class="del-reason" rows="2" maxlength="255" placeholder="e.g. Wrong student tagged, duplicate entry"></textarea>'
                            + '<div class="del-error" hidden></div>';

                        var reasonEl = handle.body.querySelector('#delReason');
                        reasonEl.value = reason;
                        reasonEl.addEventListener('input', function() { reason = reasonEl.value; refreshSelect(); });
                        refreshSelect();
                    }

                    function refreshSelect() {
                        var c = chosen(), k = kept();
                        handle.body.querySelectorAll('.del-check').forEach(function(cb) {
                            cb.closest('.del-row').classList.toggle('is-on', cb.checked);
                        });
                        handle.body.querySelector('.del-summary').innerHTML = c.length
                            ? '<span class="del-sum-del"><b>' + c.length + '</b> to delete · ' + delMoney(delSum(c)) + '</span>'
                              + (multi ? '<span class="del-sum-keep"><b>' + k.length + '</b> stay · ' + delMoney(delSum(k)) + '</span>' : '')
                            : '<span class="del-sum-none">No fee selected yet.</span>';
                        okBtn.textContent = 'Review deletion';
                        okBtn.disabled = !c.length || reason.replace(/\s+/g, ' ').trim().length < 5;
                    }

                    function showReview() {
                        var c = chosen(), k = kept();
                        reason = reason.replace(/\s+/g, ' ').trim();
                        if (!c.length || reason.length < 5) {
                            var err = handle.body.querySelector('.del-error');
                            err.textContent = !c.length ? 'Tick at least one fee to delete.' : 'Please give a reason (at least 5 characters).';
                            err.hidden = false;
                            return;
                        }
                        step = 2;
                        titleEl.textContent = 'Confirm deletion';
                        backBtn.textContent = 'Back';

                        var remain = k.length
                            ? '<div class="del-col del-col-keep"><div class="del-col-head">Will remain <span>' + k.length + '</span></div>'
                              + delFeeList(k, 'keep') + '<div class="del-col-total">Remaining ' + delMoney(delSum(k)) + '</div></div>'
                            : '<div class="del-col del-col-empty"><div class="del-col-head">Will remain <span>0</span></div>'
                              + '<p>Nothing — the whole ' + delEsc(orLabel) + ' is removed.</p></div>';

                        handle.body.innerHTML =
                            '<div class="del-cols">'
                            + '<div class="del-col del-col-del"><div class="del-col-head">Will be deleted <span>' + c.length + '</span></div>'
                            + delFeeList(c, 'del') + '<div class="del-col-total">Deleting ' + delMoney(delSum(c)) + '</div></div>'
                            + remain
                            + '</div>'
                            + '<div class="del-reason-view"><span>Reason</span>' + delEsc(reason) + '</div>'
                            + '<p class="del-warn"><i class="mdi mdi-alert-outline"></i> This can\'t be undone. The student\'s balance is recomputed and the deletion is recorded in the Payment Activity Log.</p>'
                            + '<label class="del-ack"><input type="checkbox" id="delAck"> I\'ve checked what will be deleted and what will remain.</label>';

                        var ack = handle.body.querySelector('#delAck');
                        okBtn.textContent = c.length > 1
                            ? 'Delete ' + c.length + ' payments (' + delMoney(delSum(c)) + ')'
                            : 'Delete payment (' + delMoney(delSum(c)) + ')';
                        okBtn.disabled = true;
                        ack.addEventListener('change', function() { okBtn.disabled = !ack.checked; });
                    }

                    function submitDelete() {
                        var c = chosen();
                        var ack = handle.body.querySelector('#delAck');
                        if (!c.length || !ack || !ack.checked) return;

                        form.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
                        c.forEach(function(it) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = it.id;
                            form.appendChild(input);
                        });
                        form.querySelector('input[name="reason"]').value = reason.slice(0, 255);
                        okBtn.disabled = backBtn.disabled = true;
                        handle.close(true);
                        if (UI.busy) UI.busy(c.length > 1 ? 'Deleting payments...' : 'Deleting payment...');
                        form.submit();
                    }

                    box.addEventListener('change', function(e) {
                        if (step !== 1 || !e.target.classList.contains('del-check')) return;
                        selected[e.target.value] = e.target.checked;
                        if (!e.target.checked) delete selected[e.target.value];
                        refreshSelect();
                    });
                    box.addEventListener('click', function(e) {
                        if (step !== 1) return;
                        var all = e.target.closest('.del-all'), none = e.target.closest('.del-none');
                        if (!all && !none) return;
                        items.forEach(function(it) {
                            if (!it.deletable) return;
                            if (all) selected[it.id] = true; else delete selected[it.id];
                        });
                        handle.body.querySelectorAll('.del-check:not(:disabled)').forEach(function(cb) { cb.checked = !!all; });
                        refreshSelect();
                    });

                    showSelect();
                }

                $(document).on('submit', '.delete-payment-form', function(e) {
                    e.preventDefault();
                    var form = this;
                    if (!window.UI || typeof UI.modal !== 'function') return;

                    var id = form.querySelector('input[name="id"]').value;
                    $.getJSON(RECEIPT_ITEMS_URL, { id: id })
                        .done(function(res) {
                            if (!res || !res.ok) {
                                UI.error((res && res.message) || 'Could not load this receipt.');
                                return;
                            }
                            openDeleteDialog(form, res);
                        })
                        .fail(function(xhr) {
                            var msg = xhr.responseJSON && xhr.responseJSON.message;
                            UI.error(msg || 'Could not load this receipt. Please try again.');
                        });
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

                // select2 locks the scroll of every scrollable container around
                // an open dropdown, but on close it only unlocks containers
                // that are *still* scrollable. The tall student list is often
                // what made the modal scrollable, so the lock outlives the
                // dropdown and the modal snaps back to the top on every
                // scroll. Drop whatever lock is left once a dropdown closes.
                $(document).on('select2:close', '#paymentModal select, #editPaymentModal select', function() {
                    var $parents = $(this).parents();
                    setTimeout(function() {
                        $parents.off('scroll.select2');
                    }, 0);
                });

                // A different student owes different balances — re-check every line.
                $(document).on('change', '#studentSelect', function() {
                    updateStudentTermHint($(this));
                    payItemRows().each(function() {
                        loadPayItemBalance($(this));
                    });
                });

                $(document).on('change', '#descriptionField', syncPayItems);
                $('#multiFeeToggle').on('change', applyMultiFeeMode);

                $(document).on('click', '#payItemsList .pay-item-remove', function() {
                    var desc = $(this).closest('.pay-item').data('desc');
                    setSelectedDescriptions(selectedDescriptions().filter(function(d) {
                        return d !== desc;
                    }));
                });

                $(document).on('change', '#payItemsList .pay-item-partial', function() {
                    var $row = $(this).closest('.pay-item');
                    var $amount = $row.find('.pay-item-amount');
                    var remaining = parseFloat($row.data('remaining')) || 0;

                    if (this.checked) {
                        $amount.prop('readonly', false).attr('max', remaining.toFixed(2)).val('').focus();
                    } else {
                        $amount.prop('readonly', true).removeAttr('max').val(remaining.toFixed(2));
                    }
                    updatePayItemNote($row);
                });

                $(document).on('input', '#payItemsList .pay-item-amount', function() {
                    updatePayItemNote($(this).closest('.pay-item'));
                });

                $('#paymentDate').on('change', function() {
                    refreshSuggestedOrNumber();
                });

                $('#paymentForm').on('submit', function(e) {
                    if (paymentFormSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    var $active = payItemRows().not('.is-settled');
                    if (!$active.length) {
                        $('#feeWarning').show();
                        e.preventDefault();
                        return;
                    }

                    // An amount is filled in only once its balance comes back.
                    if (payItemRows().filter('.is-loading').length) {
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
        /* Date filter + entry count, on their own row under the card title. */
        .pay-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px 16px;
            padding: 12px 22px;
            background: var(--up-soft);
            border-bottom: 1px solid var(--up-line);
        }
        .pay-filter { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .pay-filter-label {
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .8rem;
            font-weight: 700;
            color: var(--up-muted);
        }
        #dateFilter.pay-filter-select {
            width: auto;
            min-width: 190px;
            height: 36px;
            padding: 4px 30px 4px 12px;
            border: 1px solid var(--up-line);
            border-radius: 10px;
            background-color: #fff;
            font-size: .85rem;
            font-weight: 600;
            color: var(--up-ink);
        }
        .pay-count {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 36px;
            padding: 0 12px;
            border: 1px solid var(--up-line);
            border-radius: 10px;
            background: #fff;
            font-size: .8rem;
            color: var(--up-muted);
        }
        .pay-count strong { color: var(--up-ink); }
        .pay-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #c9d6f5;
            border-radius: 10px;
            background: #eaf0fd;
            font-size: .8rem;
            font-weight: 600;
            color: var(--up-blue);
        }
        .pay-hint .mdi { color: var(--up-blue-2); font-size: 1rem; }
        @media (max-width: 575.98px) {
            .pay-toolbar { padding: 12px 16px; }
            .pay-filter { width: 100%; }
            #dateFilter.pay-filter-select { flex: 1 1 auto; min-width: 0; }
        }

        /* Delete dialog (two steps: choose fees, then review). UI-kit
           tokens keep it right in the kit's dark theme too. Labels are
           scoped under .uk-modal-body to beat the kit's label{display:block}. */
        .uk-modal-del { max-width: 600px; }
        .del-lead { margin: 0 0 14px; color: var(--uk-text-soft); font-size: .9rem; line-height: 1.5; }
        .del-lead b { color: var(--uk-text); }
        .del-list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--uk-text-soft);
        }
        .del-links { display: inline-flex; gap: 4px; }
        .del-links button {
            border: 0;
            background: none;
            padding: 2px 6px;
            border-radius: 6px;
            font: 600 .8rem/1.4 var(--uk-font);
            letter-spacing: 0;
            text-transform: none;
            color: var(--uk-info);
            cursor: pointer;
        }
        .del-links button:hover { background: rgba(37, 99, 235, .1); }
        .del-rows { display: flex; flex-direction: column; gap: 8px; max-height: 280px; overflow-y: auto; padding: 1px; }
        .uk-modal-body label.del-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding: 12px 14px;
            border: 1px solid var(--uk-border);
            border-radius: var(--uk-radius-sm);
            background: var(--uk-surface);
            font-weight: 500;
            font-size: .9rem;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }
        .uk-modal-body label.del-row:hover { border-color: rgba(239, 68, 68, .45); }
        .uk-modal-body label.del-row.is-on { border-color: var(--uk-error); background: rgba(239, 68, 68, .07); }
        .uk-modal-body label.del-row.is-off { opacity: .55; cursor: not-allowed; }
        .del-check { flex: 0 0 auto; width: 18px; height: 18px; margin: 0; accent-color: var(--uk-error); cursor: inherit; }
        .del-row-text { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
        .del-row-desc { overflow-wrap: anywhere; color: var(--uk-text); }
        .del-tag {
            align-self: flex-start;
            padding: 1px 8px;
            border-radius: 999px;
            background: var(--uk-surface-2);
            border: 1px solid var(--uk-border);
            font-size: .7rem;
            font-weight: 600;
            color: var(--uk-text-soft);
        }
        .del-row-amt { flex: 0 0 auto; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--uk-text); }
        .del-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 16px;
            margin: 12px 0 16px;
            padding: 10px 14px;
            border-radius: var(--uk-radius-sm);
            background: var(--uk-surface-2);
            font-size: .84rem;
            font-variant-numeric: tabular-nums;
        }
        .del-sum-del { color: var(--uk-error); font-weight: 600; }
        .del-sum-keep { color: var(--uk-success); font-weight: 600; }
        .del-sum-none { color: var(--uk-text-soft); }
        .uk-modal-body label.del-field-label { margin-bottom: 6px; }
        .del-field-label span { font-weight: 400; color: var(--uk-text-soft); }
        .del-reason {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--uk-border);
            border-radius: var(--uk-radius-sm);
            background: var(--uk-surface);
            color: var(--uk-text);
            font: 400 .9rem/1.5 var(--uk-font);
            resize: vertical;
        }
        .del-reason:focus { outline: 0; border-color: var(--uk-info); box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        .del-error { margin-top: 8px; font-size: .8rem; font-weight: 600; color: var(--uk-error); }

        .del-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
        .del-col { border-radius: var(--uk-radius-sm); padding: 12px 14px; border: 1px solid; }
        .del-col-del { border-color: rgba(239, 68, 68, .35); background: rgba(239, 68, 68, .06); }
        .del-col-keep { border-color: rgba(16, 185, 129, .35); background: rgba(16, 185, 129, .06); }
        .del-col-empty { border-color: var(--uk-border); border-style: dashed; background: var(--uk-surface-2); }
        .del-col-empty p { margin: 0; font-size: .85rem; color: var(--uk-text-soft); }
        .del-col-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .del-col-del .del-col-head { color: var(--uk-error); }
        .del-col-keep .del-col-head { color: var(--uk-success); }
        .del-col-empty .del-col-head { color: var(--uk-text-soft); }
        .del-col-head span { padding: 0 8px; border: 1px solid currentColor; border-radius: 999px; }
        .del-fee-list { list-style: none; margin: 0; padding: 0; font-size: .86rem; }
        .del-fee-list li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 5px 0;
            border-bottom: 1px dashed var(--uk-border);
            color: var(--uk-text);
        }
        .del-fee-list li:last-child { border-bottom: 0; }
        .del-fee-list b { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .del-del li span { text-decoration: line-through; text-decoration-color: rgba(239, 68, 68, .6); }
        .del-col-total { margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--uk-border); font-size: .82rem; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
        .del-col-del .del-col-total { color: var(--uk-error); }
        .del-col-keep .del-col-total { color: var(--uk-success); }
        .del-reason-view {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-left: 3px solid var(--uk-border);
            background: var(--uk-surface-2);
            border-radius: 0 var(--uk-radius-sm) var(--uk-radius-sm) 0;
            font-size: .88rem;
            color: var(--uk-text);
            overflow-wrap: anywhere;
        }
        .del-reason-view span { display: block; margin-bottom: 2px; font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--uk-text-soft); }
        .del-warn { display: flex; gap: 8px; margin: 0 0 12px; font-size: .84rem; color: var(--uk-text-soft); line-height: 1.5; }
        .del-warn .mdi { color: var(--uk-warning); font-size: 1.1rem; line-height: 1.3; }
        .uk-modal-body label.del-ack {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0;
            padding: 10px 14px;
            border: 1px solid var(--uk-border);
            border-radius: var(--uk-radius-sm);
            font-weight: 600;
            font-size: .86rem;
            cursor: pointer;
        }
        .del-ack input { width: 18px; height: 18px; margin: 1px 0 0; flex: 0 0 auto; accent-color: var(--uk-error); }
        @media (max-width: 575.98px) {
            .del-cols { grid-template-columns: 1fr; }
        }

        /* Selected-fee chips: the theme's white chip text lands on select2's
           stock grey background here, so give them a readable pairing. */
        #paymentModal .select2-selection--multiple .select2-selection__choice {
            background-color: #eef4ff;
            border: 1px solid #cddbf7;
            color: #0d1b4b;
            font-weight: 600;
        }

        #paymentModal .select2-selection--multiple .select2-selection__choice__remove {
            color: #6b7a99;
        }

        #paymentModal .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc2626;
        }

        /* On phones these modals are bottom sheets capped at 90dvh, and only
           .modal-body scrolls. The <form> wrapping header/body/footer sits
           between .modal-content and .modal-body, so it has to carry the
           flex column down or the body never shrinks and the fee lines and
           Save button are clipped off the bottom of the sheet. */
        @media (max-width: 767.98px) {
            #paymentModal .modal-content > form,
            #editPaymentModal .modal-content > form {
                display: flex;
                flex-direction: column;
                flex: 1 1 auto;
                min-height: 0;
            }

            .pay-item-amount-wrap {
                flex: 1 1 150px;
            }
        }

        /* Fee lines in the Add Payment form — one per selected description. */
        .pay-items {
            border: 1px solid #e6ebf5;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .pay-item {
            padding: 10px 12px;
            border-bottom: 1px solid #e6ebf5;
            background: #fff;
        }

        .pay-item.is-settled {
            background: #f6f8fb;
        }

        .pay-item.is-settled .pay-item-name,
        .pay-item.is-settled .pay-item-controls {
            opacity: .55;
        }

        .pay-item-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .pay-item-name {
            font-weight: 700;
            color: #0d1b4b;
            word-break: break-word;
        }

        .pay-item-remove {
            border: 0;
            background: transparent;
            color: #6b7a99;
            font-size: 1.25rem;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
        }

        .pay-item-remove:hover {
            color: #dc2626;
        }

        .pay-item-meta {
            font-size: .78rem;
            color: #6b7a99;
            margin: 2px 0 8px;
        }

        .pay-item-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
        }

        .pay-item-amount-wrap {
            flex: 0 1 200px;
            min-width: 150px;
        }

        .pay-item-amount[readonly] {
            background: #f8fbff;
        }

        .pay-item.is-free .pay-item-partial-wrap {
            display: none;
        }

        .pay-item-note {
            display: none;
            font-size: .78rem;
            font-weight: 600;
            color: #b45309;
            margin-top: 6px;
        }

        .pay-item.is-settled .pay-item-note {
            color: #16a34a;
        }

        .pay-items-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f8fbff;
            font-weight: 800;
            color: #0d1b4b;
        }

        .pay-items-total #payItemsTotal {
            font-size: 1.1rem;
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

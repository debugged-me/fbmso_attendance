<?php
/**
 * One row of the Payment page's list. Rendered by accounting_payment.php for
 * the page, and by Accounting::paymentSavedJson() for a payment saved
 * without a reload — one template, so a new row matches the rest exactly.
 *
 * Expects $row (a getRecentPayments() row) and $isAuditor.
 */
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
// Same key as Accounting::paymentStatusKey(): a later instalment on this fee
// updates this badge in place.
$statusKey = implode('|', [
    trim((string)($row->StudentNumber ?? '')),
    (string)($row->description ?? ''),
    (string)($row->Sem ?? ''),
    (string)($row->SY ?? ''),
]);
$optionText = trim((string)($row->StudentNumber ?? '')) . (trim($studentName) !== '' ? ' - ' . $studentName : '');
$dropdownId = 'paymentActions' . $rowId;
?>
<tr data-payment-id="<?= $rowId; ?>">
    <td data-label="Date & Time" data-order="<?= htmlspecialchars(trim($pDate . ' ' . $pTime), ENT_QUOTES, 'UTF-8'); ?>" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars($dateTimeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
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
    <td data-label="Status"><span class="badge <?= $statusClass; ?>" style="border-radius:6px;font-size:.72rem;font-weight:700;" data-status-key="<?= htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>" data-full="<?= htmlspecialchars((string)$fullAmount, ENT_QUOTES, 'UTF-8'); ?>"><?= $statusLabel; ?></span></td>
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
                <?php if (empty($isAuditor)): ?>
                    <a class="dropdown-item edit-payment-btn" href="javascript:void(0);"
                        data-id="<?= $rowId; ?>"
                        data-studentno="<?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-studentname="<?= htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8'); ?>"
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

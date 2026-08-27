<?php
$schoolName = trim((string)($settings->SchoolName ?? 'School Records Management System'));
$schoolAddr = trim((string)($settings->SchoolAddress ?? ''));
$telNo = trim((string)($settings->telNo ?? ''));

$lastName = trim((string)($payment->LastName ?? ''));
$firstName = trim((string)($payment->FirstName ?? ''));
$middleName = trim((string)($payment->MiddleName ?? ''));
$studentName = trim(($lastName !== '' ? $lastName . ', ' : '') . $firstName . ($middleName !== '' ? ' ' . $middleName : ''));
if ($studentName === '') {
    $studentName = trim((string)($payment->StudentNumber ?? ''));
}

$paidAt = trim((string)($payment->PDate ?? ''));
if ($paidAt !== '' && $paidAt !== '0000-00-00') {
    $paidAt = date('F d, Y', strtotime($paidAt));
}

$cashierName = trim((string)($payment->Cashier ?? ($settings->cashier ?? '')));
$cashierPos = trim((string)($settings->cashierPosition ?? 'Cashier'));
$amountValue = (float)($payment->Amount ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Acknowledgement Receipt</title>
    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>

<body style="margin:0;padding:28px;background:#eef4ff;font-family:'Segoe UI','Helvetica Neue',Arial,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #cddbf7;border-radius:16px;overflow:hidden;box-shadow:0 12px 30px rgba(37,99,235,0.08);">
        <div style="padding:26px 30px;border-bottom:1px solid #dbe7ff;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 55%,#60a5fa 100%);color:#ffffff;">
            <div style="font-size:24px;font-weight:700;letter-spacing:0.2px;"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ($schoolAddr !== ''): ?>
                <div style="margin-top:6px;font-size:13px;color:rgba(255,255,255,0.88);"><?= htmlspecialchars($schoolAddr, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($telNo !== ''): ?>
                <div style="margin-top:4px;font-size:13px;color:rgba(255,255,255,0.88);"><?= htmlspecialchars($telNo, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div style="margin-top:18px;display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,0.16);font-size:12px;letter-spacing:1.5px;font-weight:700;color:#ffffff;">OFFICIAL RECEIPT</div>
        </div>

        <div style="padding:28px 30px;">
            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                Your payment for <strong><?= htmlspecialchars((string)($payment->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                amounting to <strong style="color:#1d4ed8;">PHP <?= number_format($amountValue, 2); ?></strong> has been received.
                Your official receipt details are below.
            </p>

            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;background:#f8fbff;border:1px solid #dbe7ff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;width:38%;color:#0f172a;">O.R. Number</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#0f172a;"><?= htmlspecialchars((string)($payment->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Date</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;"><?= htmlspecialchars($paidAt, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Received From</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Student Number</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;"><?= htmlspecialchars((string)($payment->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Description</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;"><?= htmlspecialchars((string)($payment->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php if (!empty($payment->refNo)): ?>
                    <tr>
                        <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Reference Number</td>
                        <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;"><?= htmlspecialchars((string)$payment->refNo, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($payment->CheckNumber) || !empty($payment->Bank)): ?>
                    <tr>
                        <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#0f172a;">Check Details</td>
                        <td style="padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:right;color:#334155;">
                            <?php if (!empty($payment->CheckNumber)): ?>#<?= htmlspecialchars((string)$payment->CheckNumber, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            <?php if (!empty($payment->Bank)): ?><?= !empty($payment->CheckNumber) ? ' - ' : ''; ?><?= htmlspecialchars((string)$payment->Bank, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:16px 16px 18px;font-weight:700;font-size:16px;color:#0f172a;background:#eef4ff;">Amount</td>
                    <td style="padding:16px 16px 18px;text-align:right;font-weight:800;font-size:18px;color:#1d4ed8;background:#eef4ff;">PHP <?= number_format($amountValue, 2); ?></td>
                </tr>
            </table>

            <div style="margin-top:24px;padding-top:18px;border-top:1px dashed #cbd5e1;font-size:13px;color:#64748b;">
                <div style="font-weight:700;color:#334155;">Processed by: <?= htmlspecialchars($cashierName, ENT_QUOTES, 'UTF-8'); ?></div>
                <div><?= htmlspecialchars($cashierPos, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>
</body>

</html>

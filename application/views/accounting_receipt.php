<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
    <?php
    $flashSuccess = $this->session->flashdata('success');
    $flashDanger  = $this->session->flashdata('danger');

    $schoolName = trim((string)($settings->SchoolName ?? 'School Records Management System'));
    $schoolAddr = trim((string)($settings->SchoolAddress ?? ''));
    $telNo      = trim((string)($settings->telNo ?? ''));

    $lastName   = trim((string)($payment->LastName ?? ''));
    $firstName  = trim((string)($payment->FirstName ?? ''));
    $middleName = trim((string)($payment->MiddleName ?? ''));
    $studentName = trim(($lastName !== '' ? $lastName . ', ' : '') . $firstName . ($middleName !== '' ? ' ' . $middleName : ''));
    if ($studentName === '') {
        $studentName = trim((string)$payment->StudentNumber);
    }

    $paidAt = trim((string)$payment->PDate);
    if ($paidAt !== '' && $paidAt !== '0000-00-00') {
        $paidAt = date('F d, Y', strtotime($paidAt));
    }

    $createdTime = trim((string)($payment->pTime ?? ''));
    $cashierName = trim((string)($payment->Cashier ?? ''));
    $cashierPos  = trim((string)($settings->cashierPosition ?? 'Cashier'));

    $letterhead = trim((string)($settings->letterhead_web ?? ''));
    // One O.R. can cover several fees; older callers pass only the payment.
    $receiptLines = !empty($lines) ? $lines : [$payment];
    $amountValue = 0.0;
    foreach ($receiptLines as $line) {
        $amountValue += (float)($line->Amount ?? 0);
    }
    ?>

    <div class="container-fluid p-2">
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success no-print" role="alert"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!empty($flashDanger)): ?>
            <div class="alert alert-danger no-print" role="alert"><?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-2 no-print">
            <a href="<?= base_url('Accounting/Payment'); ?>" class="btn btn-secondary btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
            <button class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="mdi mdi-printer"></i> Print
            </button>
        </div>

        <div class="receipt-card mx-auto">
            <div class="receipt-body">
                <!-- Header -->
                <div class="receipt-header">
                    <div class="school-name"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="receipt-title">OFFICIAL RECEIPT</div>
                </div>

                <div class="receipt-divider"></div>

                <!-- OR Number -->
                <div class="receipt-ornumber">
                    <span class="label">OR NO:</span>
                    <span class="value"><?= htmlspecialchars((string)$payment->ORNumber, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <!-- Details -->
                <div class="receipt-details">
                    <div class="detail-row">
                        <span class="label">Date:</span>
                        <span class="value"><?= htmlspecialchars($paidAt, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Received From:</span>
                        <span class="value"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Student No:</span>
                        <span class="value"><?= htmlspecialchars((string)$payment->StudentNumber, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Type:</span>
                        <span class="value"><?= htmlspecialchars((string)$payment->PaymentType, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if (!empty($payment->refNo)): ?>
                        <div class="detail-row">
                            <span class="label">Ref No:</span>
                            <span class="value"><?= htmlspecialchars((string)$payment->refNo, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($payment->CheckNumber) || !empty($payment->Bank)): ?>
                        <div class="detail-row">
                            <span class="label">Check:</span>
                            <span class="value">
                                <?php if (!empty($payment->CheckNumber)): ?>#<?= htmlspecialchars((string)$payment->CheckNumber, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                <?php if (!empty($payment->Bank)): ?><?= !empty($payment->CheckNumber) ? ' - ' : ''; ?><?= htmlspecialchars((string)$payment->Bank, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="receipt-divider"></div>

                <!-- Fees paid -->
                <div class="receipt-lines">
                    <?php foreach ($receiptLines as $line): ?>
                        <div class="detail-row">
                            <span class="value line-desc"><?= htmlspecialchars((string)($line->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="value"><?= number_format((float)($line->Amount ?? 0), 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="receipt-divider"></div>

                <!-- Amount -->
                <div class="receipt-amount">
                    <span class="label"><?= count($receiptLines) > 1 ? 'TOTAL:' : 'AMOUNT:'; ?></span>
                    <span class="value">PHP <?= number_format($amountValue, 2); ?></span>
                </div>

                <div class="receipt-divider"></div>

                <!-- Signature -->
                <div class="receipt-signature">
                    <div class="sig-line"></div>
                    <div class="sig-label"><?= htmlspecialchars($cashierName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sig-pos"><?= htmlspecialchars($cashierPos, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="receipt-footer">
                    Sem/SY: <?= htmlspecialchars(trim((string)$payment->Sem . ' ' . (string)$payment->SY), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>

    <style>
        .receipt-card {
            max-width: 4.5in;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
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

        .receipt-lines .detail-row {
            gap: 12px;
        }

        .receipt-lines .line-desc {
            text-align: left;
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

        @media print {

            .no-print,
            .topbar,
            .left-side-menu,
            .footer,
            .right-bar,
            .page-title-box,
            #wrapper>.content-page>.content>.container-fluid>.alert {
                display: none !important;
            }

            body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .container-fluid {
                padding: 0.2in !important;
                margin: 0 !important;
            }

            .receipt-card {
                border: 0 !important;
                box-shadow: none !important;
                margin: 0 auto !important;
            }

            .receipt-body {
                padding: 0.3in !important;
            }
        }
    </style>

    <?php if (!empty($auto_print)): ?>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 350);
            });
        </script>
    <?php endif; ?>
</body>

</html>
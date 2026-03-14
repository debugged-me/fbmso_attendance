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
                    $schoolName = trim((string)($settings->SchoolName ?? 'School Records Management System'));
                    $schoolAddress = trim((string)($settings->SchoolAddress ?? ''));
                    $schoolTel = trim((string)($settings->telNo ?? ''));
                    $reportPeriod = trim((string)($report_period ?? ''));
                    $generatedAt = trim((string)($generated_at ?? ''));
                    $reportFilename = 'collection_report_' .
                        preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$from) .
                        '_to_' .
                        preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$to);
                    ?>

                    <div class="row no-print">
                        <div class="col-12">
                            <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between">
                                <div>
                                    <h4 class="page-title mb-0"><?= htmlspecialchars((string)$report_title, ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <div class="text-muted mt-1">
                                        <?= htmlspecialchars($reportPeriod, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center justify-content-end">
                                    <div id="collectionExportButtons" class="mr-md-2 mb-2 mb-md-0 text-md-right"></div>
                                    <button type="button" class="btn btn-secondary mr-2 mb-2 mb-md-0" data-toggle="modal" data-target="#monthlyModal">
                                        <i class="mdi mdi-calendar-month-outline"></i> Monthly View
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#yearlyModal">
                                        <i class="mdi mdi-calendar-range-outline"></i> Yearly View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date range + stats -->
                    <div class="row mb-3 no-print">
                        <div class="col-md-7">
                            <div class="card mb-0">
                                <div class="card-body py-3">
                                    <form method="get" action="<?= base_url('Accounting/collectionReport'); ?>" class="form-row align-items-end">
                                        <div class="col-md-4 mb-2">
                                            <label for="from" class="mb-1">From</label>
                                            <input type="date" id="from" name="from" class="form-control"
                                                value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="to" class="mb-1">To</label>
                                            <input type="date" id="to" name="to" class="form-control"
                                                value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-2 text-right">
                                            <button class="btn btn-primary btn-block" type="submit">
                                                <i class="mdi mdi-filter-outline"></i> Apply Date Range
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-6">
                                    <div class="card mb-0">
                                        <div class="card-body py-3">
                                            <h6 class="text-muted mb-1">Transactions</h6>
                                            <h4 class="mb-0"><?= (int)$total_count; ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card mb-0">
                                        <div class="card-body py-3">
                                            <h6 class="text-muted mb-1">Total Collection</h6>
                                            <h4 class="mb-0">₱<?= number_format((float)$total_amount, 2); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3 no-print">
                                        <div>
                                            <h5 class="header-title mb-1">Collection Details</h5>
                                            <small class="text-muted">Filtered transactions included in the current report export.</small>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="collectionTable" class="table table-bordered table-sm dt-responsive nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>O.R.</th>
                                                    <th>Student No.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th>Payment Type</th>
                                                    <th>Sem/SY</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Cashier</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <?php
                                                    $studentName = trim((string)($row->StudentName ?? ''));
                                                    if ($studentName === ',' || $studentName === '') {
                                                        $studentName = (string)($row->StudentNumber ?? '');
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->PaymentType ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?= htmlspecialchars(trim((string)($row->Sem ?? '') . ' ' . (string)($row->SY ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td class="text-right"><?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                        <td><?= htmlspecialchars((string)($row->Cashier ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
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

    <!-- MONTHLY MODAL -->
    <div class="modal fade" id="monthlyModal" tabindex="-1" role="dialog" aria-labelledby="monthlyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="get" action="<?= base_url('Accounting/collectionMonthly'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="monthlyModalLabel">
                            <i class="mdi mdi-calendar-month-outline"></i> Monthly View
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="mb-1">Year</label>
                                <input type="number" class="form-control" name="year" value="<?= date('Y'); ?>" min="2000" max="2100" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="mb-1">Month</label>
                                <input type="number" class="form-control" name="month" value="<?= date('m'); ?>" min="1" max="12" required>
                            </div>
                        </div>
                        <small class="text-muted">Choose a year and month to view summary/collection for that month.</small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-secondary">View Monthly</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- YEARLY MODAL -->
    <div class="modal fade" id="yearlyModal" tabindex="-1" role="dialog" aria-labelledby="yearlyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="get" action="<?= base_url('Accounting/collectionYear'); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="yearlyModalLabel">
                            <i class="mdi mdi-calendar-range-outline"></i> Yearly View
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label class="mb-1">Year</label>
                            <input type="number" class="form-control" name="year" value="<?= date('Y'); ?>" min="2000" max="2100" required>
                        </div>
                        <small class="text-muted">Choose a year to view the yearly collection report.</small>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-secondary">View Yearly</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            var exportColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8];
            var reportMeta = {
                title: <?= json_encode((string)$report_title); ?>,
                schoolName: <?= json_encode($schoolName); ?>,
                schoolAddress: <?= json_encode($schoolAddress); ?>,
                schoolTel: <?= json_encode($schoolTel); ?>,
                period: <?= json_encode($reportPeriod); ?>,
                generatedAt: <?= json_encode($generatedAt); ?>,
                totalCount: <?= json_encode((int)$total_count); ?>,
                totalAmountText: <?= json_encode('PHP ' . number_format((float)$total_amount, 2)); ?>,
                filename: <?= json_encode($reportFilename); ?>
            };

            function escapeHtml(value) {
                return $('<div>').text(String(value || '')).html();
            }

            function buildPrintHeaderHtml() {
                var html = '<div class="print-report-header">';
                html += '<div class="print-school-name">' + escapeHtml(reportMeta.schoolName) + '</div>';
                if (reportMeta.schoolAddress) {
                    html += '<div class="print-school-line">' + escapeHtml(reportMeta.schoolAddress) + '</div>';
                }
                if (reportMeta.schoolTel) {
                    html += '<div class="print-school-line">' + escapeHtml(reportMeta.schoolTel) + '</div>';
                }
                html += '<div class="print-report-title">' + escapeHtml(reportMeta.title) + '</div>';
                html += '<div class="print-report-line">Coverage: ' + escapeHtml(reportMeta.period) + '</div>';
                html += '<div class="print-report-line">Transactions: ' + escapeHtml(reportMeta.totalCount) + ' | Total Collection: ' + escapeHtml(reportMeta.totalAmountText) + ' | Generated: ' + escapeHtml(reportMeta.generatedAt) + '</div>';
                html += '</div>';
                return html;
            }

            var table = $('#collectionTable').DataTable({
                pageLength: 20,
                order: [
                    [0, 'desc'],
                    [1, 'desc']
                ],
                autoWidth: false,
                dom: "<'row no-print align-items-center'<'col-md-5'l><'col-md-7 text-md-right'Bf>>" +
                    "t" +
                    "<'row'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'copyHtml5',
                        text: 'Copy',
                        className: 'btn btn-outline-secondary btn-sm',
                        title: reportMeta.title,
                        exportOptions: {
                            columns: exportColumns,
                            modifier: {
                                search: 'applied',
                                order: 'applied'
                            }
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        className: 'btn btn-success btn-sm',
                        title: reportMeta.title,
                        filename: reportMeta.filename,
                        exportOptions: {
                            columns: exportColumns,
                            modifier: {
                                search: 'applied',
                                order: 'applied'
                            }
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: 'PDF',
                        className: 'btn btn-danger btn-sm',
                        title: reportMeta.title,
                        filename: reportMeta.filename,
                        download: 'open',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: exportColumns,
                            modifier: {
                                search: 'applied',
                                order: 'applied'
                            }
                        },
                        customize: function(doc) {
                            doc.pageMargins = [24, 76, 24, 34];
                            doc.defaultStyle.fontSize = 8.5;
                            doc.styles.title = {
                                fontSize: 15,
                                bold: true,
                                alignment: 'center',
                                color: '#0f172a',
                                margin: [0, 0, 0, 4]
                            };
                            doc.styles.tableHeader = {
                                bold: true,
                                fontSize: 9,
                                color: '#ffffff',
                                fillColor: '#1d4ed8',
                                alignment: 'left'
                            };

                            if (doc.content[0]) {
                                doc.content[0].text = reportMeta.title;
                            }

                            doc.content.splice(1, 0, {
                                text: reportMeta.period,
                                alignment: 'center',
                                color: '#475569',
                                margin: [0, 0, 0, 2]
                            }, {
                                text: 'Transactions: ' + reportMeta.totalCount + ' | Total Collection: ' + reportMeta.totalAmountText + ' | Generated: ' + reportMeta.generatedAt,
                                alignment: 'center',
                                color: '#475569',
                                margin: [0, 0, 0, 10]
                            });

                            var tableNode = null;
                            for (var i = 0; i < doc.content.length; i++) {
                                if (doc.content[i] && doc.content[i].table) {
                                    tableNode = doc.content[i];
                                    break;
                                }
                            }

                            if (tableNode) {
                                tableNode.table.headerRows = 1;
                                tableNode.table.widths = [52, 56, 72, '*', '*', 58, 74, 58, 70];
                                tableNode.layout = {
                                    hLineWidth: function() {
                                        return 0.5;
                                    },
                                    vLineWidth: function() {
                                        return 0.5;
                                    },
                                    hLineColor: function() {
                                        return '#d7deea';
                                    },
                                    vLineColor: function() {
                                        return '#d7deea';
                                    },
                                    paddingLeft: function() {
                                        return 4;
                                    },
                                    paddingRight: function() {
                                        return 4;
                                    },
                                    paddingTop: function() {
                                        return 4;
                                    },
                                    paddingBottom: function() {
                                        return 4;
                                    }
                                };
                                tableNode.margin = [0, 6, 0, 0];
                            }

                            doc.header = function() {
                                var stack = [{
                                    text: reportMeta.schoolName,
                                    alignment: 'center',
                                    bold: true,
                                    fontSize: 14,
                                    color: '#0f172a',
                                    margin: [24, 20, 24, 2]
                                }];

                                if (reportMeta.schoolAddress) {
                                    stack.push({
                                        text: reportMeta.schoolAddress,
                                        alignment: 'center',
                                        fontSize: 9,
                                        color: '#64748b',
                                        margin: [24, 0, 24, 0]
                                    });
                                }

                                if (reportMeta.schoolTel) {
                                    stack.push({
                                        text: reportMeta.schoolTel,
                                        alignment: 'center',
                                        fontSize: 9,
                                        color: '#64748b',
                                        margin: [24, 0, 24, 0]
                                    });
                                }

                                return {
                                    stack: stack
                                };
                            };

                            doc.footer = function(currentPage, pageCount) {
                                return {
                                    columns: [{
                                            text: 'Generated ' + reportMeta.generatedAt,
                                            alignment: 'left',
                                            margin: [24, 0, 0, 0],
                                            fontSize: 8,
                                            color: '#64748b'
                                        },
                                        {
                                            text: 'Page ' + currentPage + ' of ' + pageCount,
                                            alignment: 'right',
                                            margin: [0, 0, 24, 0],
                                            fontSize: 8,
                                            color: '#64748b'
                                        }
                                    ]
                                };
                            };
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        className: 'btn btn-primary btn-sm',
                        title: '',
                        exportOptions: {
                            columns: exportColumns,
                            modifier: {
                                search: 'applied',
                                order: 'applied'
                            }
                        },
                        customize: function(win) {
                            var doc = win.document;
                            var style = doc.createElement('style');
                            style.type = 'text/css';
                            style.appendChild(doc.createTextNode(
                                '@page { size: landscape; margin: 12mm; }' +
                                'body { font-family: "Segoe UI", Arial, sans-serif; color: #0f172a; margin: 0; padding: 0; }' +
                                '.print-report-header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #cbd5e1; padding-bottom: 12px; }' +
                                '.print-school-name { font-size: 18px; font-weight: 700; }' +
                                '.print-school-line { font-size: 11px; color: #475569; margin-top: 2px; }' +
                                '.print-report-title { font-size: 15px; font-weight: 700; margin-top: 12px; }' +
                                '.print-report-line { font-size: 11px; color: #334155; margin-top: 4px; }' +
                                'table { width: 100% !important; border-collapse: collapse !important; font-size: 11px; }' +
                                'table thead th { background: #1d4ed8 !important; color: #ffffff !important; border: 1px solid #cbd5e1 !important; padding: 8px 6px !important; }' +
                                'table tbody td { border: 1px solid #dbe4f0 !important; padding: 6px !important; }' +
                                'table tbody tr:nth-child(even) td { background: #f8fbff !important; }' +
                                '.dt-print-view h1 { display: none !important; }'
                            ));
                            doc.head.appendChild(style);

                            var titleNode = doc.querySelector('h1');
                            if (titleNode && titleNode.parentNode) {
                                titleNode.parentNode.removeChild(titleNode);
                            }

                            var header = doc.createElement('div');
                            header.innerHTML = buildPrintHeaderHtml();
                            doc.body.insertBefore(header, doc.body.firstChild);
                        }
                    }
                ]
            });

            table.buttons().container().appendTo('#collectionExportButtons');
        });
    </script>

    <style>
        .report-sheet {
            border: 1px solid #dbe4f0;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .report-sheet-school {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .report-sheet-line {
            color: #64748b;
            margin-top: 4px;
        }

        .report-sheet-print-title {
            display: none;
        }

        .report-metric {
            height: 100%;
            padding: 14px 16px;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        }

        .report-metric-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 6px;
        }

        .report-metric-value {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        #collectionExportButtons .dt-buttons .btn {
            margin-left: 6px;
            margin-bottom: 6px;
        }

        #collectionTable thead th {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            #collectionExportButtons {
                width: 100%;
            }

            #collectionExportButtons .dt-buttons .btn {
                margin-left: 0;
                margin-right: 6px;
            }
        }

        @media print {

            #wrapper .topbar,
            #wrapper .left-side-menu,
            .page-title-box,
            .footer,
            .themecustomizer,
            .no-print,
            .dataTables_length,
            .dataTables_filter,
            .dataTables_info,
            .dataTables_paginate,
            .dt-buttons {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .content-page {
                margin-left: 0 !important;
            }

            .content {
                padding-top: 0 !important;
            }

            .container-fluid,
            .card {
                margin: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            .report-sheet {
                margin-bottom: 14px !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            .report-sheet-print-title {
                display: block !important;
                margin-top: 12px;
                font-size: 18px;
                font-weight: 700;
                color: #1e293b !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            #collectionTable {
                width: 100% !important;
                font-size: 11px;
            }

            #collectionTable th,
            #collectionTable td {
                padding: 6px !important;
                border-color: #cbd5e1 !important;
            }
        }
    </style>
</body>

</html>
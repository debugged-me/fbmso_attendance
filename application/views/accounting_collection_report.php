<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

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

                    <!-- Title source for the top navbar (visually hidden; read by mobile-shell.js) -->
                    <div class="page-title-box">
                        <h4 class="up-page-title"><?= htmlspecialchars((string)$report_title, ENT_QUOTES, 'UTF-8'); ?></h4>
                    </div>

                    <!-- Stats -->
                    <div class="nx-stats no-print" style="margin-top:2px;margin-bottom:18px;">
                        <div class="nx-stat blue">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format((int)$total_count); ?></div>
                                    <div class="nx-stat-label">Transactions</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-receipt"></i></div>
                            </div>
                            <div class="nx-stat-foot">In this report <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat green">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">₱<?= number_format((float)$total_amount, 2); ?></div>
                                    <div class="nx-stat-label">Total Collection</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-cash-multiple"></i></div>
                            </div>
                            <div class="nx-stat-foot">Sum of listed payments <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat cyan">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.45rem;">₱<?= number_format($total_count ? (float)$total_amount / (int)$total_count : 0, 2); ?></div>
                                    <div class="nx-stat-label">Avg / Transaction</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-calculator-variant"></i></div>
                            </div>
                            <div class="nx-stat-foot">Mean payment size <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                        <div class="nx-stat violet">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num" style="font-size:1.15rem;line-height:1.3;"><?= htmlspecialchars(date('M d', strtotime((string)$from)), ENT_QUOTES, 'UTF-8'); ?> &ndash; <?= htmlspecialchars(date('M d, Y', strtotime((string)$to)), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="nx-stat-label">Report Period</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-calendar-range-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Selected date range <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head no-print">
                                    <h4><i class="mdi mdi-file-document-outline"></i> Collection Details</h4>
                                    <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                                        <div id="collectionExportButtons"></div>
                                        <div class="pl-actions">
                                            <a href="<?= base_url('Page/accounting'); ?>" class="up-btn up-btn-ghost d-md-none">
                                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                                            </a>
                                            <button type="button" class="up-btn up-btn-ghost" data-cr-panel="filter">
                                                <i class="mdi mdi-filter-outline"></i> Filter
                                            </button>
                                            <button type="button" class="up-btn up-btn-ghost" data-cr-panel="monthly">
                                                <i class="mdi mdi-calendar-month-outline"></i> Monthly View
                                            </button>
                                            <button type="button" class="up-btn up-btn-ghost" data-cr-panel="yearly">
                                                <i class="mdi mdi-calendar-range-outline"></i> Yearly View
                                            </button>
                                            <button type="button" class="up-btn up-btn-primary" onclick="window.open('<?= base_url('Accounting/collectionReport'); ?>?from=<?= urlencode((string)$from); ?>&to=<?= urlencode((string)$to); ?>&print=1', '_blank')">
                                                <i class="mdi mdi-printer"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">

                                    <div class="table-responsive up-rt-host">
                                        <table id="collectionTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>O.R.</th>
                                                    <th>Student No.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
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
                                                        <td data-label="Date" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="O.R." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:var(--up-blue);"><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student No." style="font-family:ui-monospace,Menlo,Consolas,monospace;color:var(--up-muted);"><?= htmlspecialchars((string)($row->StudentNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);">
                                                            <?php if (trim((string)($row->StudentNumber ?? '')) !== ''): ?>
                                                                <a href="#" class="sd-link" data-student-balance data-sd-key="<?= htmlspecialchars((string)$row->StudentNumber, ENT_QUOTES, 'UTF-8'); ?>" title="View balance"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></a>
                                                            <?php else: ?>
                                                                <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-ink);">₱ <?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                        <td data-label="Cashier" style="color:var(--up-muted);font-size:.82rem;"><?= htmlspecialchars((string)($row->Cashier ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
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

    <!-- Filter / Monthly / Yearly panels (open on the right; see the SideDrawer setup below) -->
    <form method="get" action="<?= base_url('Accounting/collectionReport'); ?>" id="crFilterPanel" hidden>
        <div class="sd-form-fields">
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control"
                        value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control"
                        value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="term">Term</label>
                <select id="term" name="term" class="form-control">
                    <option value="">All terms</option>
                    <?php
                    $selectedTerm = trim((string)($filter_sem ?? '') . '|' . (string)($filter_sy ?? ''), '|');
                    foreach (($term_options ?? []) as $t):
                        $termVal = (string)$t->Semester . '|' . (string)$t->SY;
                        $termLabel = trim((string)$t->Semester . ' ' . (string)$t->SY);
                    ?>
                        <option value="<?= htmlspecialchars($termVal, ENT_QUOTES, 'UTF-8'); ?>" <?= $termVal === $selectedTerm ? 'selected' : ''; ?>><?= htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="sd-actions">
            <a href="<?= base_url('Accounting/collectionReport'); ?>" class="sd-btn-ghost">Reset</a>
            <button type="submit" class="sd-btn"><i class="mdi mdi-filter-outline"></i> Apply filters</button>
        </div>
    </form>

    <form method="get" action="<?= base_url('Accounting/collectionMonthly'); ?>" id="crMonthlyPanel" hidden>
        <div class="sd-form-fields">
            <div class="sd-form-row">
                <div class="form-group">
                    <label for="crMonthYear">Year</label>
                    <input type="number" id="crMonthYear" class="form-control" name="year" value="<?= date('Y'); ?>" min="2000" max="2100" required>
                </div>
                <div class="form-group">
                    <label for="crMonth">Month</label>
                    <input type="number" id="crMonth" class="form-control" name="month" value="<?= date('m'); ?>" min="1" max="12" required>
                </div>
            </div>
            <small class="sd-help">Summary and collections for the chosen month.</small>
        </div>
        <div class="sd-actions">
            <button type="submit" class="sd-btn">View monthly</button>
        </div>
    </form>

    <form method="get" action="<?= base_url('Accounting/collectionYear'); ?>" id="crYearlyPanel" hidden>
        <div class="sd-form-fields">
            <div class="form-group">
                <label for="crYear">Year</label>
                <input type="number" id="crYear" class="form-control" name="year" value="<?= date('Y'); ?>" min="2000" max="2100" required>
                <small class="sd-help">The yearly collection report for the chosen year.</small>
            </div>
        </div>
        <div class="sd-actions">
            <button type="submit" class="sd-btn">View yearly</button>
        </div>
    </form>

    <?php include('includes/side_drawer.php'); ?>
    <script>
        if (window.SideDrawer) {
            [
                ['filter', 'crFilterPanel', 'Filter collection report', 'mdi-filter-outline'],
                ['monthly', 'crMonthlyPanel', 'Monthly view', 'mdi-calendar-month-outline'],
                ['yearly', 'crYearlyPanel', 'Yearly view', 'mdi-calendar-range-outline']
            ].forEach(function(p) {
                SideDrawer.fromElement(document.getElementById(p[1]), {
                    title: p[2], icon: p[3], width: 400,
                    trigger: '[data-cr-panel="' + p[0] + '"]',
                    focus: 'input'
                });
            });
            StudentBalancePanel({ url: <?= json_encode(site_url('Accounting/studentSummary')); ?> });
        }
    </script>

    <script>
        $(function() {
            var exportColumns = [0, 1, 2, 3, 4, 5, 6];
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
                        className: 'btn btn-secondary btn-sm',
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
                                tableNode.table.widths = [52, 56, 72, '*', '*', 58, 70];
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
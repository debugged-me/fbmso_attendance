<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <style>
            /* ===== Accounting Records — modern redesign ===== */
            .ar-shell { --ar-ink:#0d1b4b; --ar-muted:#6b7a99; --ar-line:#e6ebf5; --ar-card:#ffffff; --ar-soft:#f5f7fc;
                        --ar-blue:#2a4090; --ar-blue-2:#4266d4; --ar-green:#16a34a; --ar-amber:#f59e0b; --ar-red:#ef4444; }

            .ar-page-title { font-weight:800; letter-spacing:.02em; color:var(--ar-ink); margin:0; }
            .ar-page-sub   { color:var(--ar-muted); font-size:.9rem; margin-top:2px; }
            .ar-divider { border:0; height:3px; width:64px; border-radius:3px;
                          background:linear-gradient(90deg,var(--ar-blue),var(--ar-blue-2)); margin:14px 0 22px; }

            /* Student identity strip */
            .ar-id-strip {
                display:flex; align-items:center; gap:16px; flex-wrap:wrap;
                background:linear-gradient(135deg,#2a4090 0%, #3b5fd4 100%);
                color:#fff; border-radius:18px; padding:18px 22px; margin-bottom:22px;
                box-shadow:0 14px 30px rgba(42,64,144,.22);
            }
            .ar-id-strip .ar-id-avatar {
                width:48px; height:48px; border-radius:14px; flex:0 0 auto;
                background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center;
                font-weight:800; font-size:1.1rem; letter-spacing:.04em;
            }
            .ar-id-strip .ar-id-name { font-weight:700; font-size:1.05rem; line-height:1.2; }
            .ar-id-strip .ar-id-meta { font-size:.82rem; opacity:.85; margin-top:2px; }
            .ar-id-strip .ar-id-pill {
                margin-left:auto; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.25);
                padding:6px 14px; border-radius:999px; font-size:.8rem; font-weight:700; letter-spacing:.04em;
            }

            /* Filter card */
            .ar-filter-card {
                background:var(--ar-card); border:1px solid var(--ar-line); border-radius:18px;
                padding:18px 20px; margin-bottom:22px; box-shadow:0 6px 18px rgba(13,27,75,.05);
            }
            .ar-filter-card .ar-filter-title {
                font-size:.72rem; font-weight:800; letter-spacing:.18em; text-transform:uppercase;
                color:var(--ar-muted); margin-bottom:14px;
            }
            .ar-filter-card .form-control {
                border-radius:12px; border:1px solid var(--ar-line); padding:10px 14px;
                font-size:.88rem; color:var(--ar-ink); height:auto;
            }
            .ar-filter-card .form-control:focus { border-color:var(--ar-blue-2); box-shadow:0 0 0 3px rgba(66,102,212,.12); }
            .ar-filter-card label { font-size:.78rem; font-weight:700; color:var(--ar-muted); letter-spacing:.02em; }

            .ar-btn { border-radius:12px; font-weight:700; letter-spacing:.02em; padding:10px 18px; font-size:.86rem; border:none; transition:transform .15s ease, box-shadow .15s ease; }
            .ar-btn-primary { background:linear-gradient(135deg,var(--ar-blue),var(--ar-blue-2)); color:#fff; box-shadow:0 8px 18px rgba(42,64,144,.22); }
            .ar-btn-primary:hover { transform:translateY(-1px); box-shadow:0 12px 24px rgba(42,64,144,.28); color:#fff; }
            .ar-btn-ghost { background:var(--ar-soft); color:var(--ar-ink); border:1px solid var(--ar-line); }
            .ar-btn-ghost:hover { background:#eef2fb; color:var(--ar-blue); }

            /* Stat cards */
            .ar-stat {
                position:relative; overflow:hidden; border-radius:18px; padding:20px 22px;
                background:var(--ar-card); border:1px solid var(--ar-line);
                box-shadow:0 6px 18px rgba(13,27,75,.05); transition:transform .2s ease, box-shadow .2s ease;
            }
            .ar-stat:hover { transform:translateY(-3px); box-shadow:0 14px 28px rgba(13,27,75,.09); }
            .ar-stat .ar-stat-icon {
                position:absolute; top:14px; right:16px; width:42px; height:42px; border-radius:12px;
                display:flex; align-items:center; justify-content:center; font-size:18px;
            }
            .ar-stat .ar-stat-label { font-size:.72rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; color:var(--ar-muted); }
            .ar-stat .ar-stat-value { font-size:1.6rem; font-weight:800; color:var(--ar-ink); margin-top:6px; letter-spacing:-.01em; }
            .ar-stat .ar-stat-foot { font-size:.76rem; color:var(--ar-muted); margin-top:4px; }

            .ar-stat-blue   .ar-stat-icon { background:#eef2ff; color:#4266d4; }
            .ar-stat-green  .ar-stat-icon { background:#dcfce7; color:#16a34a; }
            .ar-stat-amber  .ar-stat-icon { background:#fef3c7; color:#d97706; }

            /* Records card */
            .ar-records-card {
                background:var(--ar-card); border:1px solid var(--ar-line); border-radius:18px;
                overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05);
            }
            .ar-records-head {
                padding:18px 22px; border-bottom:1px solid var(--ar-line);
                display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
            }
            .ar-records-head h4 { margin:0; font-weight:800; color:var(--ar-ink); font-size:1rem; }
            .ar-records-head .ar-records-count {
                font-size:.76rem; font-weight:700; color:var(--ar-muted);
                background:var(--ar-soft); padding:4px 12px; border-radius:999px; border:1px solid var(--ar-line);
            }
            .ar-records-body { padding:6px 18px 18px; }

            /* DataTable restyle */
            #studentPaymentsTable { margin:0 !important; }
            #studentPaymentsTable thead th {
                background:var(--ar-soft); color:var(--ar-muted); font-size:.72rem; font-weight:800;
                letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid var(--ar-line) !important;
                padding:12px 14px; white-space:nowrap;
            }
            #studentPaymentsTable tbody td { padding:12px 14px; vertical-align:middle; font-size:.88rem; color:var(--ar-ink); border-bottom:1px solid var(--ar-line) !important; }
            #studentPaymentsTable tbody tr:hover { background:#f5f7fc !important; }
            #studentPaymentsTable tbody tr:last-child td { border-bottom:none !important; }
            #studentPaymentsTable .ar-or {
                font-family:ui-monospace,Menlo,Consolas,monospace; font-weight:700; color:var(--ar-blue);
                background:#eef2ff; padding:3px 10px; border-radius:8px; font-size:.8rem;
            }
            #studentPaymentsTable .ar-amt { font-weight:800; color:var(--ar-ink); }
            #studentPaymentsTable .ar-type-pill {
                display:inline-block; padding:3px 10px; border-radius:999px; font-size:.74rem; font-weight:700;
                background:#f0f4ff; color:#3b5fd4; border:1px solid #d8e2ff;
            }

            .ar-empty {
                text-align:center; padding:48px 20px; color:var(--ar-muted);
            }
            .ar-empty .ar-empty-icon {
                width:64px; height:64px; border-radius:18px; margin:0 auto 14px;
                background:var(--ar-soft); display:flex; align-items:center; justify-content:center;
                font-size:26px; color:var(--ar-muted);
            }

            /* DataTables control restyle */
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
                font-size:.82rem; color:var(--ar-muted); }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                border-radius:8px !important; border:1px solid var(--ar-line) !important; margin:0 2px !important;
                padding:5px 10px !important; color:var(--ar-ink) !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current,
            .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
                background:linear-gradient(135deg,var(--ar-blue),var(--ar-blue-2)) !important; color:#fff !important;
                border-color:var(--ar-blue) !important; }
            .dataTables_wrapper .dataTables_filter input,
            .dataTables_wrapper .dataTables_length select {
                border-radius:10px; border:1px solid var(--ar-line); padding:6px 10px; font-size:.84rem; }

            @media (max-width: 767.98px) {
                .ar-id-strip .ar-id-pill { margin-left:0; }
                .ar-stat .ar-stat-value { font-size:1.35rem; }
            }
        </style>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid ar-shell">

                    <!-- Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="ar-page-title">My Accounting Records</h4>
                                <div class="ar-page-sub">Track your payments and transactions across school years.</div>
                                <hr class="ar-divider" />
                            </div>
                        </div>
                    </div>

                    <!-- Student identity strip -->
                    <?php
                    $fullName = '';
                    $initials  = 'ST';
                    if (!empty($profile)) {
                        $fullName = trim((string)$profile->LastName . ', ' . (string)$profile->FirstName . ' ' . (string)$profile->MiddleName);
                        $initials = strtoupper(substr((string)$profile->FirstName, 0, 1) . substr((string)$profile->LastName, 0, 1));
                    }
                    if ($fullName === '') {
                        $fullName = (string)$studentNumber;
                    }
                    ?>
                    <div class="ar-id-strip">
                        <div class="ar-id-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div>
                            <div class="ar-id-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="ar-id-meta">Student ledger &middot; all school years</div>
                        </div>
                        <div class="ar-id-pill"><?= htmlspecialchars((string)$studentNumber, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>

                    <!-- Filter -->
                    <div class="row">
                        <div class="col-12">
                            <div class="ar-filter-card">
                                <div class="ar-filter-title"><i class="mdi mdi-filter-variant mr-1"></i> Filter records</div>
                                <form method="get" action="<?= base_url('Page/studentAccountingRecords'); ?>" class="form-row align-items-end">
                                    <div class="form-group col-md-4">
                                        <label class="mb-1">School Year</label>
                                        <select name="sy" class="form-control">
                                            <option value="">All School Years</option>
                                            <?php foreach (($syOptions ?? []) as $sy): ?>
                                                <option value="<?= htmlspecialchars((string)$sy, ENT_QUOTES, 'UTF-8'); ?>"
                                                    <?= ((string)$filterSy === (string)$sy) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars((string)$sy, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="mb-1">Semester</label>
                                        <select name="sem" class="form-control">
                                            <option value="">All Semesters</option>
                                            <?php foreach (($semOptions ?? []) as $sem): ?>
                                                <option value="<?= htmlspecialchars((string)$sem, ENT_QUOTES, 'UTF-8'); ?>"
                                                    <?= ((string)$filterSem === (string)$sem) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars((string)$sem, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 d-flex">
                                        <button type="submit" class="ar-btn ar-btn-primary mr-2"><i class="mdi mdi-magnify mr-1"></i> Apply</button>
                                        <a href="<?= base_url('Page/studentAccountingRecords'); ?>" class="ar-btn ar-btn-ghost"><i class="mdi mdi-refresh mr-1"></i> Clear</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row">
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="ar-stat ar-stat-blue h-100">
                                <div class="ar-stat-icon"><i class="mdi mdi-cash-multiple"></i></div>
                                <div class="ar-stat-label">Valid Payments</div>
                                <div class="ar-stat-value">₱ <?= number_format((float)($totalValid ?? 0), 2); ?></div>
                                <div class="ar-stat-foot">Total of OR-status = Valid</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="ar-stat ar-stat-green h-100">
                                <div class="ar-stat-icon"><i class="mdi mdi-format-list-numbered"></i></div>
                                <div class="ar-stat-label">Transactions</div>
                                <div class="ar-stat-value"><?= (int)count($payments ?? []); ?></div>
                                <div class="ar-stat-foot">All recorded payments</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="ar-stat ar-stat-amber h-100">
                                <div class="ar-stat-icon"><i class="mdi mdi-scale-balance"></i></div>
                                <div class="ar-stat-label">Total Amount</div>
                                <div class="ar-stat-value">₱ <?= number_format((float)($totalAll ?? 0), 2); ?></div>
                                <div class="ar-stat-foot">Including pending ORs</div>
                            </div>
                        </div>
                    </div>

                    <!-- Records table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="ar-records-card">
                                <div class="ar-records-head">
                                    <h4><i class="mdi mdi-receipt-text-outline mr-1"></i> Payment Records</h4>
                                    <span class="ar-records-count"><?= (int)count($payments ?? []); ?> entries</span>
                                </div>
                                <div class="ar-records-body table-responsive">
                                    <table id="studentPaymentsTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>OR No.</th>
                                                <th>Payment Date</th>
                                                <th>Time</th>
                                                <th>Description</th>
                                                <th>Payment Type</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($payments ?? []) as $row): ?>
                                                <tr>
                                                    <td><span class="ar-or"><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->pTime ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="ar-type-pill"><?= htmlspecialchars((string)($row->PaymentType ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="text-right ar-amt">₱ <?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if (empty($payments)): ?>
                                        <div class="ar-empty">
                                            <div class="ar-empty-icon"><i class="mdi mdi-file-document-outline"></i></div>
                                            <div class="font-weight-bold">No accounting records found</div>
                                            <div class="small mt-1">Try adjusting the filters above, or check back after your next payment.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <link href="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.css" rel="stylesheet" />
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
    <script>
        $(function() {
            $('#studentPaymentsTable').DataTable({
                pageLength: 15,
                order: [
                    [0, 'desc'],
                    [1, 'desc']
                ]
            });
        });

    </script>
</body>

</html>

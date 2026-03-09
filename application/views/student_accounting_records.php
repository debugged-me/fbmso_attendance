<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <style>
            @media (max-width: 767.98px) {
                .navbar-custom {
                    z-index: 10050 !important;
                }

                .navbar-custom .button-menu-mobile {
                    position: relative;
                    z-index: 10051 !important;
                    pointer-events: auto !important;
                }

                .content-page {
                    position: relative;
                    z-index: 1;
                }
            }
        </style>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">MY ACCOUNTING RECORDS</h4>
                                <div class="clearfix"></div>
                                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
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
                                            <button type="submit" class="btn btn-info mr-2">Apply</button>
                                            <a href="<?= base_url('Page/studentAccountingRecords'); ?>" class="btn btn-light">Clear</a>
                                        </div>
                                    </form>

                                    <?php
                                    $fullName = '';
                                    if (!empty($profile)) {
                                        $fullName = trim((string)$profile->LastName . ', ' . (string)$profile->FirstName . ' ' . (string)$profile->MiddleName);
                                    }
                                    if ($fullName === '') {
                                        $fullName = (string)$studentNumber;
                                    }
                                    ?>
                                    <div class="mt-2 text-muted">
                                        Student: <strong><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        &nbsp;|&nbsp;
                                        Student No.: <strong><?= htmlspecialchars((string)$studentNumber, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-soft-primary">
                                <div class="card-body">
                                    <h6 class="text-uppercase text-muted mb-2">Payments</h6>
                                    <h4 class="mb-0">₱ <?= number_format((float)($totalValid ?? 0), 2); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-soft-success">
                                <div class="card-body">
                                    <h6 class="text-uppercase text-muted mb-2">All Transactions</h6>
                                    <h4 class="mb-0"><?= (int)count($payments ?? []); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-soft-warning">
                                <div class="card-body">
                                    <h6 class="text-uppercase text-muted mb-2">All Amount</h6>
                                    <h4 class="mb-0">₱ <?= number_format((float)($totalAll ?? 0), 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body table-responsive">
                                    <h4 class="m-t-0 header-title mb-3">Payment Records </h4>
                                    <table id="studentPaymentsTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>OR No.</th>
                                                <th>Payment Date</th>
                                                <th>Time</th>
                                                <th>Description</th>
                                                <th>Payment Type</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($payments ?? []) as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string)($row->ORNumber ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->PDate ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->pTime ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string)($row->PaymentType ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-right"><?= number_format((float)($row->Amount ?? 0), 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if (empty($payments)): ?>
                                        <div class="alert alert-info mb-0">No accounting payment records found for your account.</div>
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

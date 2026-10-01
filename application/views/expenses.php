<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<?php
$level = (string)$this->session->userdata('level');
// Expenses are the Cashier's to manage; Admin and Auditor view them only.
$canManage = ($level === 'Cashier');
?>


<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('includes/top-nav-bar.php'); ?>
        <!-- end Topbar --> <!-- ========== Left Sidebar Start ========== -->

        <!-- Lef Side bar -->
        <?php include('includes/sidebar.php'); ?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- Title + actions -->
                    <div class="page-title-box">
                            <h4 class="up-page-title">Expenses</h4>
                            <div class="up-page-sub">Record and manage school expenses by category.</div>
                            <hr class="up-divider" />
                        </div>

					<?php include('includes/accounting_readonly_notice.php'); ?>

					<?php if ($notice = $this->session->flashdata('expenses_notice')): ?>
						<div class="up-flash up-flash-info"><i class="mdi mdi-information-outline"></i> <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
					<?php endif; ?>

					<?php if ($notice = $this->session->flashdata('expenses')): ?>
						<div class="up-flash up-flash-info"><i class="mdi mdi-information-outline"></i> <?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
					<?php endif; ?>

                    <!-- start row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="up-card">
                                <div class="up-card-head">
<div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
<h4><i class="mdi mdi-cash-register"></i> Expenses</h4>
                                    <span class="badge badge-purple">SY <?= htmlspecialchars($this->session->userdata('sy') ?? '', ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($this->session->userdata('semester') ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<div class="pl-actions">
							<a href="<?= base_url($level === 'Admin' ? 'Page/admin' : 'Page/accounting'); ?>" class="up-btn up-btn-ghost d-md-none">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
							<?php if ($canManage): ?>
								<button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target=".bs-example-modal-lg">
									<i class="mdi mdi-plus-circle"></i> Add New
								</button>
							<?php endif; ?>
                        </div>
</div>
                                <div class="pay-toolbar">
                                    <div class="pay-filter">
                                        <label for="expenseDateFilter" class="pay-filter-label"><i class="mdi mdi-calendar-month-outline"></i> Expenses on</label>
                                        <select id="expenseDateFilter" class="form-control form-control-sm pay-filter-select">
                                            <option value="all" <?= ($expense_date_filter ?? '') === 'all' ? 'selected' : ''; ?>>All dates</option>
                                            <?php
                                            $todayVal = (string)($today ?? '');
                                            $selectedDate = (string)($expense_date_filter ?? '');
                                            $hasToday = false;
                                            foreach (($expense_dates ?? []) as $d):
                                                $dateVal = (string)($d->ExpenseDate ?? '');
                                                if ($dateVal === $todayVal) $hasToday = true;
                                                $label = date('M d, Y', strtotime($dateVal)) . ($dateVal === $todayVal ? ' (Today)' : '');
                                            ?>
                                                <option value="<?= htmlspecialchars($dateVal, ENT_QUOTES, 'UTF-8'); ?>" <?= $dateVal === $selectedDate ? 'selected' : ''; ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                            <?php if (!$hasToday && $todayVal !== ''): ?>
                                                <option value="<?= htmlspecialchars($todayVal, ENT_QUOTES, 'UTF-8'); ?>" <?= $todayVal === $selectedDate ? 'selected' : ''; ?>><?= htmlspecialchars(date('M d, Y', strtotime($todayVal)), ENT_QUOTES, 'UTF-8'); ?> (Today)</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php $entryCount = count($data); ?>
                                        <span class="pay-count"><strong><?= number_format($entryCount); ?></strong> <?= $entryCount === 1 ? 'entry' : 'entries'; ?> &middot; <strong>&#8369; <?= number_format((float)($expense_total ?? 0), 2); ?></strong></span>
                                    </div>
                                    <div class="pay-hint">
                                        <i class="mdi mdi-information-outline"></i> Ledger totals cover a date range — pick the same day here to compare figures.
                                    </div>
                                </div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered dt-responsive nowrap up-rt" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th style="text-align:right">Amount</th>
                                                    <th>Responsible</th>
                                                    <th style="text-align:center">Expense Date</th>
                                                    <th style="text-align:center">Category</th>
											<?php if ($canManage): ?><th style="text-align:center">Manage</th><?php endif; ?>
                                                </tr>
                                            </thead>
                                                <tbody>
                                                    <?php foreach ($data as $row) { ?>
                                                        <tr>
                                                            <td data-label="Description"><?= $row->Description; ?></td>
                                                            <td data-label="Amount" style="text-align: right;"><?= number_format($row->Amount, 2); ?></td>
                                                            <td data-label="Responsible"><?= $row->Responsible; ?></td>
                                                            <td data-label="Expense Date" style="text-align:center"><?= $row->ExpenseDate; ?></td>
                                                            <td data-label="Category" style="text-align:center"><?= $row->Category; ?></td>
												<?php if ($canManage): ?>
												<td data-label="Manage" class="up-rt-actions" style="text-align:center">
                                                                <a href="<?= base_url('Accounting/updateexpenses?expensesid=' . $row->expensesid); ?>" class="up-btn up-btn-ghost" style="padding:8px 12px;font-size:.78rem;">
                                                                    <i class="mdi mdi-pencil"></i> Edit
                                                                </a>

                                                                <a href="#" onclick="setDeleteId(<?= (int)$row->expensesid; ?>)" data-toggle="modal" data-target="#confirmationModal" class="up-btn up-btn-danger" style="padding:8px 12px;font-size:.78rem;">
                                                                    <i class="mdi mdi-delete"></i> Delete
                                                                </a>

                                                            </td>
												<?php endif; ?>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- end container-fluid -->



					<?php if ($canManage): ?>
                    <!-- Confirmation Modal -->
                    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmationModalLabel">Delete Confirmation</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <div class="circle-with-stroke d-inline-flex justify-content-center align-items-center">
                                            <span class="h1 text-danger">!</span>
                                        </div>
                                        <p class="mt-3">Are you sure you want to delete this data?</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                                    <form method="post" action="<?= base_url('Accounting/Deleteexpenses'); ?>" class="d-inline">
                                        <input type="hidden" name="expensesid" id="deleteId" value="">
                                        <input type="hidden" name="back" value="<?= htmlspecialchars((string)($expense_date_filter ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="up-btn up-btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .circle-with-stroke {
                            width: 100px;
                            height: 100px;
                            border: 4px solid #dc3545;
                            border-radius: 50%;
                        }
                    </style>

                    <script>
                        function setDeleteId(id) {
                            // Deleting is a POST (see the form in the modal), never a link.
                            document.getElementById('deleteId').value = id;
                        }
                    </script>

                    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="myLargeModalLabel">Add New</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="<?php echo base_url('Accounting/expenses'); ?>">
                                        <div class="form-row align-items-center">
                                            <div class="col-md-6 mb-3">
                                                <label for="Description">Description</label>
                                                <input type="text" class="form-control" id="Description" name="Description" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="Amount">Amount</label>
                                                <input type="number" class="form-control" id="Amount" name="Amount" required>
                                            </div>
                                        </div>
                                        <div class="form-row align-items-center">
                                            <div class="col-md-6 mb-3">
                                                <label for="Responsible">Responsible</label>
                                                <input type="text" class="form-control" id="Responsible" name="Responsible" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="ExpenseDate">Expense Date</label>
                                                <input type="date" class="form-control" id="ExpenseDate" name="ExpenseDate" required>
                                            </div>
                                        </div>
                                        <div class="form-row align-items-center">
                                            <div class="col-md-12 mb-3">
                                                <label for="category">Category</label>
                                                <select class="form-control" id="category" name="Category" required>
                                                    <?php foreach ($data1 as $row) { ?>
                                                        <option value="<?php echo $row->Category; ?>"><?php echo $row->Category; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                                            <input type="submit" name="save" value="Save Data" class="up-btn up-btn-primary" />
                                        </div>
                                    </form>
                                </div>
                                <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                        </div>
                    </div>

                    <!-- /.modal-content -->
					<?php endif; ?>
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
    </div>


    </div>
    </div>
    </div>





    <!-- Footer Start -->
    <?php include('includes/footer.php'); ?>
    <!-- end Footer -->

    </div>

    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->


    <!-- Right Sidebar -->
    <?php include('includes/themecustomizer.php'); ?>
    <!-- /Right-bar -->


    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <!-- Chat app -->
    <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>

    <!-- Todo app -->
    <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>

    <!--Morris Chart-->
    <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>

    <!-- Sparkline charts -->
    <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>

    <!-- Dashboard init JS -->
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>

    <!-- App js -->
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <!-- Required datatable js -->
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <!-- Responsive examples -->
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

    <!-- Datatables init -->
    <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

    <script>
        (function($) {
            // Date filter — the list is scoped server-side (default: today),
            // so changing it just reloads with the chosen date.
            $('#expenseDateFilter').on('change', function() {
                window.location = <?= json_encode(base_url('Accounting/expenses')); ?> + '?date=' + encodeURIComponent(this.value);
            });
        })(jQuery);
    </script>

    <style>
        /* Date filter + entry count, on their own row under the card title —
           same markup/styles the Payments page uses. */
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
        #expenseDateFilter.pay-filter-select {
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
            #expenseDateFilter.pay-filter-select { flex: 1 1 auto; min-width: 0; }
        }
    </style>


</body>

</html>

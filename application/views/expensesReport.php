<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>


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

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <!-- <a href="<?= base_url(); ?>Accounting/Addexpenses">    
                        <button type="button" class="btn btn-info waves-effect waves-light"> <i class="fas fa-stream mr-1"></i> <span>Add New</span> </button>
                        </a> -->
                                    <!-- EXPENSES REPORT -->
                                </h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0">
                                        <li class="breadcrumb-item">
                                            <a href="#">
                                                <!-- <span class="badge badge-purple mb-3">Currently login to <b>SY <?php echo $this->session->userdata('sy'); ?> <?php echo $this->session->userdata('semester'); ?></span></b> -->
                                            </a>
                                        </li>
                                    </ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>





                    <!-- start row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info py-3 text-white">
                                    <!-- <h5>EXPENSES REPORT</h5> -->
                                    <strong>EXPENSES REPORT</strong>
                                </div>
                                <div class="card-body">
                                    <div class="clearfix">

                                        <!-- Start Form Section -->
                                        <div class="row mb-3 no-print">
                                            <!-- Dropdown to select Category -->
                                            <div class="col-lg-3">
                                                <label for="selectCategory">Select Category:</label>
                                                <select id="selectCategory" class="form-control">
                                                    <option value="">All Categories</option>
                                                    <?php
                                                    // Assuming $categories is an array of categories fetched from the database
                                                    foreach ($categories as $Category) {
                                                        $catSafe = htmlspecialchars((string)$Category, ENT_QUOTES, 'UTF-8');
                                                        echo '<option value="' . $catSafe . '">' . $catSafe . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Textbox to filter data based on Expense Date range -->
                                            <div class="col-lg-3">
                                                <label for="filterFromDate">From:</label>
                                                <input type="date" id="filterFromDate" class="form-control">
                                            </div>

                                            <div class="col-lg-3">
                                                <label for="filterToDate">To:</label>
                                                <input type="date" id="filterToDate" class="form-control">
                                            </div>
                                        </div>
                                        <div class="row mb-3 no-print">
                                            <div class="col-12 text-right" id="expensesExportButtons"></div>
                                        </div>
                                        <!-- End Form Section -->




                                        <!-- Existing Expenses Table -->
                                        <div class="table-responsive">
                                            <table id="expensesTable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th>Responsible</th>
                                                        <th>Expense Date</th>
                                                        <th>Category</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data as $row) { ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string)$row->Description, ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?= htmlspecialchars((string)$row->Responsible, ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?= htmlspecialchars((string)$row->ExpenseDate, ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?= htmlspecialchars((string)$row->Category, ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td><?= number_format((float)$row->Amount, 2); ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Summary Table -->
                                        <div class="table-responsive mt-4 no-print" id="summaryWrap">
                                            <table id="summaryTable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th>Total Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td id="summaryDescription">No data available</td>
                                                        <td id="summaryTotal"><a href="#" id="summaryTotalLink">0.00</a></td>


                                                    </tr>
                                                </tbody>
                                            </table>
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
            <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>

            <!-- App js -->
            <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

            <!-- Required datatable js -->
            <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
            <!-- Buttons examples -->
            <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
            <!-- Responsive examples -->
            <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

            <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
            <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

            <script type="text/javascript">
                (function($) {
                    if (!$ || !$.fn || !$.fn.DataTable) return;

                    var $category = $('#selectCategory');
                    var $from = $('#filterFromDate');
                    var $to = $('#filterToDate');

                    function normalizeDate(value) {
                        return String(value || '').trim().substring(0, 10);
                    }

                    function numericAmount(value) {
                        return parseFloat(String(value || '').replace(/,/g, '').replace(/[^\d.-]/g, '')) || 0;
                    }

                    function plainText(value) {
                        return $('<div>').html(String(value || '')).text().trim();
                    }

                    $.fn.dataTable.ext.search.push(function(settings, data) {
                        if (!settings || settings.nTable.id !== 'expensesTable') {
                            return true;
                        }

                        var selectedCategory = String($category.val() || '').trim();
                        var fromDate = normalizeDate($from.val());
                        var toDate = normalizeDate($to.val());
                        var rowCategory = String(data[3] || '').trim();
                        var rowDate = normalizeDate(data[2] || '');

                        if (selectedCategory !== '' && rowCategory !== selectedCategory) return false;
                        if (fromDate !== '' && rowDate < fromDate) return false;
                        if (toDate !== '' && rowDate > toDate) return false;
                        return true;
                    });

                    var table = $('#expensesTable').DataTable({
                        pageLength: 10,
                        order: [
                            [2, 'desc']
                        ],
                        dom: "<'row no-print'<'col-md-6'l><'col-md-6 text-right'Bf>>" +
                            "t" +
                            "<'row'<'col-md-5'i><'col-md-7'p>>",
                        buttons: [{
                                extend: 'excelHtml5',
                                text: 'Export Excel',
                                className: 'btn btn-success btn-sm',
                                title: 'Expenses Report',
                                filename: 'expenses_report',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4],
                                    modifier: {
                                        search: 'applied',
                                        order: 'applied'
                                    }
                                }
                            },
                            {
                                extend: 'pdfHtml5',
                                text: 'Export PDF',
                                className: 'btn btn-danger btn-sm',
                                title: 'Expenses Report',
                                filename: 'expenses_report',
                                orientation: 'landscape',
                                pageSize: 'A4',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4],
                                    modifier: {
                                        search: 'applied',
                                        order: 'applied'
                                    }
                                },
                                customize: function(doc) {
                                    var selectedCategory = String($category.val() || '').trim();
                                    var fromDate = normalizeDate($from.val());
                                    var toDate = normalizeDate($to.val());

                                    doc.pageMargins = [24, 30, 24, 24];
                                    doc.defaultStyle.fontSize = 10;
                                    doc.styles.title = {
                                        fontSize: 16,
                                        bold: true,
                                        alignment: 'center',
                                        margin: [0, 0, 0, 8]
                                    };
                                    doc.styles.tableHeader = {
                                        bold: true,
                                        fontSize: 11,
                                        color: 'white',
                                        fillColor: '#2b4b6f',
                                        alignment: 'left'
                                    };

                                    var subtitle = 'Category: ' + (selectedCategory || 'All Categories');
                                    if (fromDate || toDate) {
                                        subtitle += ' | Date: ' + (fromDate || '...') + ' to ' + (toDate || '...');
                                    }

                                    doc.content.splice(1, 0, {
                                        text: subtitle,
                                        fontSize: 10,
                                        margin: [0, 0, 0, 8],
                                        color: '#444'
                                    });

                                    // Force full-width table so PDF doesn't appear tiny.
                                    var tableNode = null;
                                    for (var i = 0; i < doc.content.length; i++) {
                                        if (doc.content[i] && doc.content[i].table) {
                                            tableNode = doc.content[i];
                                            break;
                                        }
                                    }
                                    if (tableNode) {
                                        tableNode.table.widths = ['*', '*', '*', '*', '*'];
                                        tableNode.layout = {
                                            hLineWidth: function() {
                                                return 0.5;
                                            },
                                            vLineWidth: function() {
                                                return 0.5;
                                            },
                                            hLineColor: function() {
                                                return '#c9c9c9';
                                            },
                                            vLineColor: function() {
                                                return '#c9c9c9';
                                            },
                                            paddingLeft: function() {
                                                return 6;
                                            },
                                            paddingRight: function() {
                                                return 6;
                                            },
                                            paddingTop: function() {
                                                return 4;
                                            },
                                            paddingBottom: function() {
                                                return 4;
                                            }
                                        };
                                        tableNode.margin = [0, 2, 0, 0];
                                    }
                                }
                            },
                            {
                                text: 'Export Word',
                                className: 'btn btn-primary btn-sm',
                                action: function(e, dt) {
                                    var rows = dt.rows({
                                        search: 'applied',
                                        order: 'applied'
                                    }).data().toArray();
                                    var headers = ['Description', 'Responsible', 'Expense Date', 'Category', 'Amount'];

                                    var html = '<html><head><meta charset="utf-8"><title>Expenses Report</title></head><body>';
                                    html += '<h3>Expenses Report</h3>';
                                    html += '<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%;">';
                                    html += '<thead><tr>';
                                    headers.forEach(function(h) {
                                        html += '<th>' + h + '</th>';
                                    });
                                    html += '</tr></thead><tbody>';

                                    rows.forEach(function(r) {
                                        html += '<tr>';
                                        html += '<td>' + plainText(r[0]) + '</td>';
                                        html += '<td>' + plainText(r[1]) + '</td>';
                                        html += '<td>' + plainText(r[2]) + '</td>';
                                        html += '<td>' + plainText(r[3]) + '</td>';
                                        html += '<td>' + plainText(r[4]) + '</td>';
                                        html += '</tr>';
                                    });

                                    html += '</tbody></table></body></html>';

                                    var blob = new Blob(['\ufeff', html], {
                                        type: 'application/msword'
                                    });
                                    var url = URL.createObjectURL(blob);
                                    var a = document.createElement('a');
                                    a.href = url;
                                    a.download = 'expenses_report.doc';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    URL.revokeObjectURL(url);
                                }
                            },
                            {
                                extend: 'print',
                                text: 'Print',
                                className: 'btn btn-secondary btn-sm',
                                title: 'Expenses Report',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4],
                                    modifier: {
                                        search: 'applied',
                                        order: 'applied'
                                    }
                                }
                            }
                        ]
                    });

                    table.buttons().container().appendTo('#expensesExportButtons');

                    function updateSummary() {
                        var rows = table.rows({
                            search: 'applied'
                        }).data().toArray();
                        var totalAmount = 0;
                        rows.forEach(function(r) {
                            totalAmount += numericAmount(r[4]);
                        });

                        var selectedCategory = String($category.val() || '').trim();
                        var fromDate = normalizeDate($from.val());
                        var toDate = normalizeDate($to.val());

                        var description = 'No data available';
                        if (rows.length > 0) {
                            if (fromDate !== '' || toDate !== '') {
                                description = 'Expenses from ' + (fromDate || '...') + ' - ' + (toDate || '...');
                            } else if (selectedCategory !== '') {
                                description = 'Expenses for ' + selectedCategory;
                            } else {
                                description = 'All expenses';
                            }
                        }

                        $('#summaryDescription').text(description);
                        $('#summaryTotalLink').text(totalAmount.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));

                        if (rows.length > 0) {
                            var href = "<?= base_url('Accounting/expenseSGenerate'); ?>" +
                                "?category=" + encodeURIComponent(selectedCategory) +
                                "&from=" + encodeURIComponent(fromDate) +
                                "&to=" + encodeURIComponent(toDate);
                            $('#summaryTotalLink').attr('href', href).css('pointer-events', 'auto');
                        } else {
                            $('#summaryTotalLink').attr('href', '#').css('pointer-events', 'none');
                        }
                    }

                    $category.add($from).add($to).on('change', function() {
                        table.draw();
                    });

                    table.on('draw', updateSummary);
                    table.draw();
                })(window.jQuery);
            </script>

            <style>
                #expensesExportButtons .dt-buttons .btn {
                    margin-left: 6px;
                    margin-bottom: 6px;
                }

                @media print {

                    #wrapper .topbar,
                    #wrapper .left-side-menu,
                    .page-title-box,
                    .themecustomizer,
                    .footer,
                    #summaryWrap,
                    .no-print,
                    .dataTables_length,
                    .dataTables_filter,
                    .dataTables_info,
                    .dataTables_paginate,
                    .dt-buttons {
                        display: none !important;
                    }

                    .content-page {
                        margin-left: 0 !important;
                    }

                    .content {
                        padding-top: 0 !important;
                    }
                }
            </style>

</body>

</html>

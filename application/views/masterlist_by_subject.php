<!DOCTYPE html>
<html lang="en">

<?php include('includes/head.php'); ?>

<style>
    /* Screen helpers */
    .grading-dl dd { text-align: left !important; }
    @media (min-width: 576px) { .grading-dl dt { text-align: right; } }

    /* Make table wrap (avoid horizontal scroll) and sit close to header */
    #classlistTable { table-layout: fixed; margin-top: 0 !important; }
    #classlistTable th, #classlistTable td { white-space: normal; overflow-wrap: anywhere; word-break: break-word; }

    /* Narrow "No." column */
    #classlistTable th:nth-child(1), #classlistTable td:nth-child(1) {
        width: 48px; max-width: 48px; white-space: nowrap; text-align: center; padding-left: 6px; padding-right: 6px;
    }

    /* Hide DT toolbar for this table */
    #classlistTable_wrapper .dt-buttons { display: none !important; }

    /* Small pill/badge for flag counts */
    .badge-soft-danger {
        background: #fee2e2; color: #991b1b; border-radius: 999px; padding: 2px 8px; font-weight: 700; font-size: 11px;
    }
    .flag-count-link { text-decoration: none; }
    .flag-count-link:hover { text-decoration: underline; }

    /* Modal table tight */
    #studentFlagsTable th, #studentFlagsTable td { font-size: 13px; }

    /* PRINT */
    @media print {
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 0; font-size: 12px; line-height: 1.0; }
        @page { size: A4 portrait; margin: 20mm; }
        #wrapper, .content-page, .container-fluid { padding: 0; margin: 0; }
        .topbar, .left-side-menu, .sidebar, .footer, .right-bar, .d-print-none { display: none !important; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { padding: 4px 6px; font-size: 11px; line-height: 1.0; }
        th { background-color: #f1f1f1 !important; -webkit-print-color-adjust: exact; }
        h4, strong, .badge { line-height: 1.0; }
        a[href]:after { content: ""; }
    }
</style>

<body class="masterlist-page">

    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- page title / divider -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0"></ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0; height:2px; background:linear-gradient(to right, #4285F4 60%, #FBBC05 80%, #34A853 100%); border-radius:1px; margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <!-- content -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <!-- One compact card-body that contains BOTH the header block and the table -->
                                <div class="card-body py-2">

                                    <div class="row align-items-start mb-2">
                                        <!-- Left info card -->
                                        <div class="col-lg-8 mb-2">
                                            <div class="card border-0 shadow-sm rounded mb-0">
                                                <div class="card-header bg-primary text-white rounded-top d-flex align-items-center py-2">
                                                    <i class="mdi mdi-information-outline mr-2"></i>
                                                    <span class="font-weight-semibold">CLASS LIST</span>
                                                </div>

                                                <div class="card-body bg-light py-2">
                                                    <dl class="row mb-0 grading-dl align-items-center">
                                                        <dt class="col-sm-3 text-muted mb-1 text-sm-left">Subject Code:</dt>
                                                        <dd class="col-sm-9 mb-1"><strong><?= htmlspecialchars($subjectcode, ENT_QUOTES, 'UTF-8'); ?></strong></dd>

                                                        <dt class="col-sm-3 text-muted mb-1 text-sm-left">Description:</dt>
                                                        <dd class="col-sm-9 mb-1 text-left">
                                                            <strong class="d-block text-break" style="white-space: normal;">
                                                                <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>
                                                            </strong>
                                                        </dd>

                                                        <dt class="col-sm-3 text-muted mb-0 text-sm-left">Section:</dt>
                                                        <dd class="col-sm-9 mb-0"><strong><?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?></strong></dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Export to Excel (hidden on print) -->
                                        <div class="col-lg-4 text-lg-right d-print-none mb-2">
                                            <button type="button" id="btnExportExcel" class="btn btn-success waves-effect waves-light">
                                                <i class="mdi mdi-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Classlist table directly under header block (close spacing) -->
                                    <div class="table-responsive mt-1">
                                        <table class="table mb-0" id="classlistTable">
                                            <colgroup>
                                                <col style="width:48px"> <!-- No. -->
                                                <col> <!-- Student Name (flex) -->
                                                <col style="width:12%"> <!-- Student No. -->
                                                <col style="width:22%"> <!-- Course -->
                                                <col style="width:22%"> <!-- Major -->
                                                <col style="width:8%"> <!-- Year Level -->
                                                <col style="width:10%"> <!-- Action -->
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Student Name</th>
                                                    <th>Student No.</th>
                                                    <th>Course</th>
                                                    <th>Major</th>
                                                    <th class="text-center">Year Level</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($data as $row) {
                                                    $studNo = (string)$row->StudentNumber;
                                                    $counts = isset($flagCounts[$studNo]) ? $flagCounts[$studNo] : null;
                                                    echo "<tr>";
                                                    echo "<td>" . $i++ . "</td>";

                                                    // Name + Flag count (only if > 0)
                                                    echo "<td>";
                                                    echo strtoupper(htmlspecialchars($row->StudentName, ENT_QUOTES, 'UTF-8'));
                                                    if ($counts && (int)$counts['total'] > 0) {
                                                        $total = (int)$counts['total'];
                                                        $un    = (int)$counts['unsettled'];
                                                        echo '<div class="mt-1 small">';
                                                        echo    '<a href="#" class="flag-count-link" data-studnum="' . htmlspecialchars($studNo, ENT_QUOTES, 'UTF-8') . '">';
                                                        echo        '<span class="badge-soft-danger">Flags: ' . $total . '</span>';
                                                        if ($un > 0) {
                                                            echo ' <span class="text-danger">(Unsettled: ' . $un . ')</span>';
                                                        }
                                                        echo    '</a>';
                                                        echo '</div>';
                                                    }
                                                    echo "</td>";

                                                    echo "<td>" . htmlspecialchars($studNo, ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row->Course, ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row->Major, ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($row->YearLevel, ENT_QUOTES, 'UTF-8') . "</td>";
                                                    echo '<td class="text-center">'
                                                        . '<a href="' . base_url('Flag?StudentNumber=' . urlencode($studNo)) . '" class="btn btn-sm btn-warning">'
                                                        . '<i class="mdi mdi-flag-variant"></i> Flag'
                                                        . '</a></td>';
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div><!-- /table-responsive -->

                                </div><!-- /card-body -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>

        <?php include('includes/themecustomizer.php'); ?>
    </div>

    <!-- Vendor js -->
    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
    <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

    <!-- DataTables + Buttons (required for Excel export) -->
    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>

    <script>
        $(function() {
            var selector = '#classlistTable';

            // Initialize DataTable without showing built-in buttons toolbar
            var dt = $.fn.DataTable.isDataTable(selector) ?
                $(selector).DataTable() :
                $(selector).DataTable({
                    dom: 't',                 // no visible DT toolbar
                    autoWidth: false,         // respect colgroup widths
                    buttons: [{
                        extend: 'excelHtml5',
                        title: 'ClassList_<?= addslashes($subjectcode) ?>_<?= addslashes($section) ?>',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] } // skip Action col (index 6)
                    }],
                    columnDefs: [
                        { targets: 0, width: '48px', className: 'text-center' }, // No.
                        { targets: [5, 6], className: 'text-center' }
                    ],
                    paging: false,
                    searching: false,
                    ordering: false,
                    info: false
                });

            // External "Export to Excel" trigger
            $('#btnExportExcel').on('click', function(e) {
                e.preventDefault();
                dt.button(0).trigger();
            });

            // Click handler for flag count -> open modal and load history (read-only)
            $(document).on('click', '.flag-count-link', function(e){
                e.preventDefault();
                var stud = $(this).data('studnum');
                if (!stud) return;

                // Reset modal
                $('#flagsModalTitle').text('Flag History â€” ' + stud);
                $('#studentFlagsTable tbody').html(
                    '<tr><td colspan="6" class="text-center text-muted py-4">Loadingâ€¦</td></tr>'
                );
                $('#studentFlagsModal').modal('show');

                // Fetch JSON
                $.getJSON('<?= base_url('Flag/history_json'); ?>', { student: stud })
                 .done(function(res){
                    var rows = '';
                    if (res && res.items && res.items.length) {
                        res.items.forEach(function(it){
                            rows += '<tr>'
                                + '<td>' + (it.flagDate || '') + '</td>'
                                + '<td>' + (it.status || '') + '</td>'
                                + '<td>' + (it.office || '') + '</td>'
                                + '<td>' + (it.reason || '') + '</td>'
                                + '<td>' + (it.flaggedByName || '') + '</td>'
                                + '<td>' + (it.settledDate || 'â€”') + '</td>'
                                + '</tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="6" class="text-center text-muted py-4">No flag records.</td></tr>';
                    }
                    $('#studentFlagsTable tbody').html(rows);
                 })
                 .fail(function(){
                    $('#studentFlagsTable tbody').html(
                        '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load history.</td></tr>'
                    );
                 });
            });
        });
    </script>

    <!-- Read-only modal to show a student's full flag history -->
    <div class="modal fade" id="studentFlagsModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="flagsModalTitle">Flag History</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered" id="studentFlagsTable">
                <thead class="thead-light">
                  <tr>
                    <th>Flag Date</th>
                    <th>Status</th>
                    <th>Office</th>
                    <th>Reason</th>
                    <th>Flagged By</th>
                    <th>Settled Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="6" class="text-center text-muted py-4">Loadingâ€¦</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

</body>
</html>



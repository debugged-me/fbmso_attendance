<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260827'); ?>">

<body>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid section-gutters">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <h4 class="up-page-title">Reports</h4>
                                    <p class="up-page-sub">Generate and view attendance and enrollment reports.</p>
                                </div>
                                <div class="btn-group no-print">
                                    <button type="button" id="printBtn" class="up-btn up-btn-primary">
                                        <i class="mdi mdi-printer"></i> Print
                                    </button>
                                </div>
                            </div>
                            <hr class="up-divider" />
                        </div>
                    </div>
                    <div class="row" id="section-kpis" data-print-id="kpis">
                        <div class="col-md-3 mb-3">
                            <div class="up-card kpi p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Total Courses</div>
                                        <div class="display-5 lh-1 kpi-number kpi-blue"><?= count($by_course) ?></div>
                                    </div>
                                    <div class="icon bg-soft-primary"><i class="mdi mdi-school"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="up-card kpi p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Total Sections</div>
                                        <div class="display-5 lh-1 kpi-number kpi-green">
                                            <?php $totalSections = array_sum(array_map(function ($r) {
                                                return (int)$r->sections;
                                            }, $sections_count));
                                            echo $totalSections; ?>
                                        </div>
                                    </div>
                                    <div class="icon bg-soft-primary"><i class="mdi mdi-door-open"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="up-card kpi p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Total Students (unique)</div>
                                        <div class="display-5 lh-1 kpi-number kpi-amber">
                                            <?php $sum = array_sum(array_map(function ($r) {
                                                return (int)$r->total;
                                            }, $by_course));
                                            echo $sum; ?>
                                        </div>
                                    </div>
                                    <div class="icon bg-soft-warning"><i class="mdi mdi-account-group"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="up-card kpi p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Events / Scans</div>
                                        <div class="h5 mb-0">
                                            <span class="count-badge count-blue"><?= (int)$events_total ?></span>
                                            <span class="mx-1 text-muted">/</span>
                                            <span class="count-badge count-cyan"><?= (int)$event_scans ?></span>
                                        </div>
                                    </div>
                                    <div class="icon bg-soft-info"><i class="mdi mdi-calendar-check"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="reportsAccordion" class="accordion">
                        <div class="up-card mb-3 section-card" id="section-yearlevel" data-print-id="by_yearlevel">
                            <div class="up-card-head py-2 px-3" id="headYearLevel">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseYearLevel">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Students by Year Level
                                    </button>
                                    <span class="only-print h6">Students by Year Level</span>
                                </h6>
                            </div>
                            <div id="collapseYearLevel" class="collapse" aria-labelledby="headYearLevel">
                                <div class="up-card-body p-0 px-md-3">
                                    <table class="table table-sm mb-0 table-tight">
                                        <thead>
                                            <tr>
                                                <th>Year Level</th>
                                                <th class="text-right">Students</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($by_yearlevel as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r->YearLevel ?: '—') ?></td>
                                                    <td class="text-right"><span class="count-badge count-amber"><?= (int)$r->total ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="up-card mb-3 section-card" id="section-course" data-print-id="by_course">
                            <div class="up-card-head py-2 px-3" id="headCourse">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseCourse">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Students by Course
                                    </button>
                                    <span class="only-print h6">Students by Course</span>
                                </h6>
                            </div>
                            <div id="collapseCourse" class="collapse" aria-labelledby="headCourse">
                                <div class="up-card-body p-0 px-md-3">
                                    <table class="table table-sm mb-0 table-tight">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th class="text-right">Students</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($by_course as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r->Course ?: '—') ?></td>
                                                    <td class="text-right"><span class="count-badge count-blue"><?= (int)$r->total ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="up-card mb-3 section-card" id="section-sections" data-print-id="sections_per_course">
                            <div class="up-card-head py-2 px-3" id="headSectionsCount">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseSectionsCount">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Number of Sections per Course
                                    </button>
                                    <span class="only-print h6">Number of Sections per Course</span>
                                </h6>
                            </div>
                            <div id="collapseSectionsCount" class="collapse" aria-labelledby="headSectionsCount">
                                <div class="up-card-body p-0 px-md-3">
                                    <table class="table table-sm mb-0 table-tight">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th class="text-right">Sections</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sections_count as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r->Course ?: '—') ?></td>
                                                    <td class="text-right"><span class="count-badge count-green"><?= (int)$r->sections ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="up-card mb-3 section-card" id="section-bysection" data-print-id="by_section">
                            <div class="up-card-head py-2 px-3 d-flex align-items-center justify-content-between" id="headBySection">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseBySection">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Students by Section
                                    </button>
                                    <span class="only-print h6">Students by Section</span>
                                </h6>
                            </div>
                            <div id="collapseBySection" class="collapse" aria-labelledby="headBySection">
                                <div class="up-card-body p-0 px-md-3">
                                    <div class="table-responsive">
                                        <table id="bySectionTable" class="table table-striped table-sm mb-0 table-tight">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Year Level</th>
                                                    <th>Section</th>
                                                    <th class="text-right">Students</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($by_section as $r): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($r->Course ?: '—') ?></td>
                                                        <td><?= htmlspecialchars($r->YearLevel ?: '—') ?></td>
                                                        <td><?= htmlspecialchars($r->Section ?: '—') ?></td>
                                                        <td class="text-right"><span class="count-badge count-purple"><?= (int)$r->total ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="up-card mb-3 section-card" id="section-events" data-print-id="events">
                            <div class="up-card-head py-2 px-3" id="headEvents">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseEvents">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Events & Attendance (latest)
                                    </button>
                                    <span class="only-print h6">Events & Attendance (latest)</span>
                                </h6>
                            </div>
                            <div id="collapseEvents" class="collapse" aria-labelledby="headEvents">
                                <div class="up-card-body p-0 px-md-3">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm mb-0 table-tight">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Date</th>
                                                    <th>Start</th>
                                                    <th>End</th>
                                                    <th>Program</th>
                                                    <th class="text-right">Scan Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($events_summary as $ev): ?>
                                                    <?php
                                                    $dateText = (!empty($ev->activity_date) && $ev->activity_date !== '0000-00-00')
                                                        ? date('M d, Y', strtotime($ev->activity_date))
                                                        : (!empty($ev->start_at) ? date('M d, Y', strtotime($ev->start_at)) : '—');

                                                    $rawStart = $ev->meta_start_time ?: ($ev->meta_start ?: $ev->start_at);
                                                    $rawEnd   = $ev->meta_end_time   ?: ($ev->meta_end   ?: $ev->end_at);

                                                    $fmt = function ($v) {
                                                        if (empty($v)) return '—';
                                                        $ts = strtotime($v);
                                                        return $ts ? date('h:i A', $ts) : '—';
                                                    };

                                                    $startText   = $fmt($rawStart);
                                                    $endText     = $fmt($rawEnd);
                                                    $programText = isset($ev->program) && $ev->program !== '' ? $ev->program : '—';
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ev->title) ?></td>
                                                        <td><?= htmlspecialchars($dateText) ?></td>
                                                        <td><?= htmlspecialchars($startText) ?></td>
                                                        <td><?= htmlspecialchars($endText) ?></td>
                                                        <td><?= htmlspecialchars($programText) ?></td>
                                                        <td class="text-right"><span class="count-badge count-cyan"><?= (int)$ev->scans ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($events_summary)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted p-3">No events found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="up-card mb-3 section-card" id="section-attendance" data-print-id="attendance">
                            <div class="up-card-head py-2 px-3" id="headAttendance">
                                <h6 class="mb-0">
                                    <button class="btn btn-link collapsed section-toggle no-print" type="button" data-target="#collapseAttendance">
                                        <i class="mdi mdi-chevron-right mr-1"></i> Recent Attendance (latest 100)
                                    </button>
                                    <span class="only-print h6">Recent Attendance (latest 100)</span>
                                </h6>
                            </div>
                            <div id="collapseAttendance" class="collapse" aria-labelledby="headAttendance">
                                <div class="up-card-body p-0 px-md-3">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm mb-0 table-tight">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student</th>
                                                    <th>Course</th>
                                                    <th>Year / Section</th>
                                                    <th>Activity</th>
                                                    <th>Session</th>
                                                    <th>IN</th>
                                                    <th>OUT</th>
                                                    <th>Source</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($recent_attendance as $row):
                                                    $parts = array_filter([$row->FirstName ?? '', $row->MiddleName ?? '', $row->LastName ?? '']);
                                                    $fullName = trim(implode(' ', $parts));
                                                    $sessionMap = ['am' => 'Morning', 'pm' => 'Afternoon', 'eve' => 'Evening'];
                                                    $sessionLabel = $sessionMap[$row->session ?? ''] ?? ($row->session ?: '—');
                                                ?>
                                                    <tr>
                                                        <td><?= $i++ ?></td>
                                                        <td><?= htmlspecialchars($fullName ?: $row->student_number ?: '—') ?></td>
                                                        <td><?= htmlspecialchars($row->CourseName ?: '—') ?></td>
                                                        <td><?= htmlspecialchars(($row->yearLevel ?: '—') . ' / ' . ($row->section ?: '—')) ?></td>
                                                        <td><?= htmlspecialchars($row->activity_title ?: '—') ?></td>
                                                        <td><?= htmlspecialchars($sessionLabel) ?></td>
                                                        <td><?= !empty($row->checked_in_at)  ? htmlspecialchars(date('M d, Y h:i:s A', strtotime($row->checked_in_at)))  : '—' ?></td>
                                                        <td><?= !empty($row->checked_out_at) ? htmlspecialchars(date('M d, Y h:i:s A', strtotime($row->checked_out_at))) : '—' ?></td>
                                                        <td><span class="badge badge-light"><?= htmlspecialchars(strtoupper($row->source ?: '—')) ?></span></td>
                                                        <td><?= htmlspecialchars($row->remarks ?: '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($recent_attendance)): ?>
                                                    <tr>
                                                        <td colspan="10" class="text-center text-muted p-3">No attendance records.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div style="height:40px;"></div>
                </div>
            </div>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/footer_plugins.php'); ?>

    <div class="modal fade no-print" id="printPicker" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Print Options</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt" id="optAll" checked>
                        <label class="custom-control-label" for="optAll"><strong>Print All</strong></label>
                    </div>
                    <hr>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optYear" data-target="by_yearlevel" checked>
                        <label class="custom-control-label" for="optYear">Students by Year Level</label>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optCourse" data-target="by_course" checked>
                        <label class="custom-control-label" for="optCourse">Students by Course</label>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optSections" data-target="sections_per_course" checked>
                        <label class="custom-control-label" for="optSections">Sections per Course</label>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optBySection" data-target="by_section" checked>
                        <label class="custom-control-label" for="optBySection">Students by Section</label>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optEvents" data-target="events" checked>
                        <label class="custom-control-label" for="optEvents">Events</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input print-opt-item" id="optAttendance" data-target="attendance" checked>
                        <label class="custom-control-label" for="optAttendance">Recent Attendance</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmPrint" class="up-btn up-btn-primary"><i class="mdi mdi-printer"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $(document).on('click', '.section-toggle', function(e) {
                e.preventDefault();
                var target = $(this).attr('data-target');
                if (target) $(target).collapse('toggle');
            });

            $('#reportsAccordion .collapse')
                .on('shown.bs.collapse', function() {
                    $(this).prev('.up-card-head').find('.mdi')
                        .removeClass('mdi-chevron-right').addClass('mdi-chevron-down');
                })
                .on('hidden.bs.collapse', function() {
                    $(this).prev('.up-card-head').find('.mdi')
                        .removeClass('mdi-chevron-down').addClass('mdi-chevron-right');
                });

            var bySectionInit = false;
            $('#collapseBySection').on('shown.bs.collapse', function() {
                if (!bySectionInit) {
                    $('#bySectionTable').DataTable({
                        searching: false,
                        paging: false,
                        info: false,
                        lengthChange: false,
                        order: [
                            [0, 'asc'],
                            [1, 'asc'],
                            [2, 'asc']
                        ],
                        autoWidth: false
                    });
                    bySectionInit = true;
                }
            });

            $('#printBtn').on('click', function() {
                $('#optAll').prop('checked', true).trigger('change');
                $('#printPicker').modal('show');
            });
            $('#optAll').on('change', function() {
                var checked = $(this).is(':checked');
                $('.print-opt-item').prop('checked', checked);
            });
            $('.print-opt-item').on('change', function() {
                var allOn = $('.print-opt-item').length === $('.print-opt-item:checked').length;
                $('#optAll').prop('checked', allOn);
            });
            $('#confirmPrint').on('click', function() {
                $('#printPicker').modal('hide');
                var selected = $('.print-opt-item:checked').map(function() {
                    return $(this).data('target');
                }).get();
                var all = $('[data-print-id]').map(function() {
                    return $(this).data('print-id');
                }).get();
                all.forEach(function(id) {
                    $('[data-print-id="' + id + '"]').addClass('print-hide');
                });
                selected.forEach(function(id) {
                    $('[data-print-id="' + id + '"]').removeClass('print-hide');
                });
                selected.forEach(function(id) {
                    var card = $('[data-print-id="' + id + '"]');
                    card.find('.collapse').collapse('show');
                });

                window.print();
                setTimeout(function() {
                    $('[data-print-id]').removeClass('print-hide');
                }, 500);
            });
        });
    </script>

    <style>
        .section-gutters {
            padding-left: .5rem;
            padding-right: .5rem;
        }

        @media (min-width:768px) {
            .section-gutters {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (min-width:1200px) {
            .section-gutters {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }
        }

        .section-card .up-card-body {
            padding-left: .25rem;
            padding-right: .25rem;
        }

        @media (min-width:768px) {
            .section-card .up-card-body {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        .kpi {
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
            box-shadow: 0 6px 18px rgba(36, 59, 83, .08);
        }

        .kpi .icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 28px;
        }

        .kpi-number {
            font-weight: 700;
            letter-spacing: .2px;
        }

        .kpi-blue {
            color: #2563eb;
        }

        .kpi-green {
            color: #059669;
        }

        .kpi-amber {
            color: #b45309;
        }

        .bg-soft-primary {
            background: rgba(37, 99, 235, .08);
            color: #2563eb
        }

        .bg-soft-success {
            background: rgba(16, 185, 129, .10);
            color: #10b981
        }

        .bg-soft-warning {
            background: rgba(251, 191, 36, .10);
            color: #f59e0b
        }

        .bg-soft-info {
            background: rgba(14, 165, 233, .10);
            color: #0ea5e9
        }

        .count-badge {
            display: inline-block;
            min-width: 36px;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: .9rem;
            text-align: center;
            line-height: 1;
            letter-spacing: .2px;
            background: #eef2ff;
            color: #3730a3;
        }

        .count-blue {
            background: #e0ecff;
            color: #1d4ed8;
        }

        .count-green {
            background: #dcfce7;
            color: #166534;
        }

        .count-amber {
            background: #fef3c7;
            color: #92400e;
        }

        .count-purple {
            background: #efe7ff;
            color: #6d28d9;
        }

        .count-cyan {
            background: #cffafe;
            color: #155e75;
        }

        .section-toggle {
            font-weight: 600;
        }

        .section-toggle:focus {
            box-shadow: none;
        }

        .up-card-head {
            background: linear-gradient(135deg, #1a2a6c, #2a4090);
            color: #fff;
        }

        .up-card-head h6 {
            line-height: 1;
            color: #fff;
        }

        .up-card-head .section-toggle {
            color: #fff;
            text-decoration: none;
        }

        .up-card-head .section-toggle:hover {
            color: #fff;
        }

        .accordion .up-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .accordion .up-card+.up-card {
            margin-top: .5rem;
        }

        .table-tight thead th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0 !important;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #334155;
            vertical-align: middle;
        }

        .table-tight tbody td {
            padding: .45rem .75rem;
            vertical-align: middle;
        }

        .table-tight tbody tr:hover {
            background: #f8faff;
        }

        .print-hide {
            display: none !important;
        }

        .only-print {
            display: none;
        }

        @media print {

            html,
            body {
                background: #fff !important;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }

            .content-page,
            .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
            }

            .up-card,
            .kpi {
                box-shadow: none !important;
            }

            .up-card {
                border: 1px solid #ddd;
                margin-bottom: .75rem;
            }

            .only-print {
                display: inline-block !important;
            }

            .btn,
            .badge {
                filter: grayscale(100%);
            }

            .accordion .collapse {
                display: block !important;
                height: auto !important;
            }

            .table-tight tbody td {
                padding: .35rem .6rem;
            }
        }

        .lh-1 {
            line-height: 1;
        }

        .display-5 {
            font-size: 2rem;
        }

        /* DataTables uniform styling */
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
        .dataTables_wrapper .dataTables_filter input { border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color:#4266d4 !important; box-shadow:0 0 0 3px rgba(66,102,212,.12) !important; outline:none !important; }
        .dataTables_wrapper .dataTables_length select { border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:6px 10px !important; margin-left:6px !important; }
        .dataTables_paginate .paginate_button { border-radius:8px !important; min-width:38px; min-height:38px; display:inline-flex !important; align-items:center; justify-content:center; }
        .dataTables_paginate .paginate_button.current, .dataTables_paginate .paginate_button.current:hover { background:linear-gradient(135deg,#2a4090,#4266d4) !important; color:#fff !important; border-color:#2a4090 !important; }
    </style>
</body>

</html>

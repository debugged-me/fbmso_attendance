<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
<?php
// ---------------- Helpers ----------------
if (!function_exists('h')) {
    function h($val)
    {
        return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
function fmt_time_ampm($ts)
{
    if (empty($ts)) return '';
    $t = strtotime($ts);
    if ($t === false) return h($ts);
    return date('g:i A', $t);
}
if (!function_exists('normalize_course_key_view')) {
    function normalize_course_key_view($value)
    {
        $value = trim((string)$value);
        if ($value === '') return '';
        $value = preg_replace('/\s+/', ' ', $value);
        return strtoupper($value);
    }
}
if (!function_exists('resolve_course_code')) {
    function resolve_course_code($course, $lookup)
    {
        $course = trim((string)$course);
        if ($course === '') return '';
        $variants = [
            $course,
            preg_replace('/\s*-\s*/', ' ', $course),
            preg_replace('/\s*\([^)]*\)/', '', $course),
            preg_replace('/\bMAJOR IN\b/i', ' ', $course),
        ];
        foreach ($variants as $variant) {
            $key = normalize_course_key_view($variant);
            if ($key !== '' && isset($lookup[$key])) {
                return $lookup[$key];
            }
        }
        return '';
    }
}
if (!function_exists('course_acronym')) {
    function course_acronym($course)
    {
        $course = trim((string)$course);
        if ($course === '') return '';
        if (preg_match('/^[A-Za-z0-9]{2,}$/', $course)) {
            return strtoupper($course);
        }
        if (preg_match('/\(([A-Za-z0-9]{2,})\)\s*$/', $course, $m)) {
            return strtoupper($m[1]);
        }
        $parts = preg_split('/[\s\/&\-\.,]+/', $course);
        $acronym = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $acronym .= strtoupper(substr($part, 0, 1));
            if (strlen($acronym) >= 6) break;
        }
        if ($acronym !== '') {
            return $acronym;
        }
        return strtoupper(substr($course, 0, 8));
    }
}
if (!function_exists('year_level_sort_key_view')) {
    function year_level_sort_key_view($value)
    {
        static $wordMap = [
            'FIRST' => 1,
            'SECOND' => 2,
            'THIRD' => 3,
            'FOURTH' => 4,
            'FIFTH' => 5,
            'SIXTH' => 6,
            'SEVENTH' => 7,
            'EIGHTH' => 8,
            'NINTH' => 9,
            'TENTH' => 10,
            'ELEVENTH' => 11,
            'TWELFTH' => 12
        ];
        $value = trim((string)$value);
        if ($value === '') {
            return [PHP_INT_MAX, ''];
        }
        $upper = strtoupper($value);
        if (preg_match('/\d+/', $upper, $match)) {
            $num = (int)$match[0];
        } else {
            $num = PHP_INT_MAX - 1;
            foreach ($wordMap as $token => $rank) {
                if (strpos($upper, $token) !== false) {
                    $num = $rank;
                    break;
                }
            }
        }
        return [$num, $upper];
    }
}
if (!function_exists('section_sort_key_view')) {
    function section_sort_key_view($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 'ZZZZ';
        }
        return strtoupper($value);
    }
}
if (!function_exists('name_sort_key_view')) {
    function name_sort_key_view($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return ['ZZZZ', ''];
        }
        if (strpos($value, ',') !== false) {
            [$last, $rest] = array_map('trim', explode(',', $value, 2));
            $first = preg_replace('/\s+/', ' ', $rest);
        } else {
            $parts = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
            $last = array_pop($parts);
            if ($last === null) {
                $last = $value;
            }
            $first = implode(' ', $parts);
        }
        $lastKey = strtoupper($last);
        $firstKey = strtoupper(trim($first));
        return [$lastKey, $firstKey];
    }
}
if (!function_exists('compare_attendance_rows_view')) {
    function compare_attendance_rows_view($a, $b)
    {
        $yearA = year_level_sort_key_view($a->YearLevel ?? '');
        $yearB = year_level_sort_key_view($b->YearLevel ?? '');
        if ($yearA[0] !== $yearB[0]) {
            return $yearA[0] <=> $yearB[0];
        }
        if ($yearA[1] !== $yearB[1]) {
            return strcmp($yearA[1], $yearB[1]);
        }
        $sectionA = section_sort_key_view($a->section ?? '');
        $sectionB = section_sort_key_view($b->section ?? '');
        if ($sectionA !== $sectionB) {
            return strcmp($sectionA, $sectionB);
        }
        $nameA = name_sort_key_view($a->student_name ?? '');
        $nameB = name_sort_key_view($b->student_name ?? '');
        if ($nameA[0] !== $nameB[0]) {
            return strcmp($nameA[0], $nameB[0]);
        }
        if ($nameA[1] !== $nameB[1]) {
            return strcmp($nameA[1], $nameB[1]);
        }
        $numA = trim((string)($a->student_number ?? ''));
        $numB = trim((string)($b->student_number ?? ''));
        return strcmp($numA, $numB);
    }
}
$courseLookup = isset($course_lookup) && is_array($course_lookup) ? $course_lookup : [];
$flashMsgRaw   = $this->session->flashdata('msg');
$flashSuccess  = $this->session->flashdata('success');
$flashError    = $this->session->flashdata('error');
$flashInfo     = $this->session->flashdata('info');
$flashMsg      = $flashMsgRaw ? strip_tags($flashMsgRaw) : null;
?>

<body>
    <style>
        .badge-course-code {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.4px
        }

        .student-number-cell .student-name-mobile {
            color: #6c757d;
            font-size: 0.85rem
        }

        @media print {

            .export-actions,
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                display: none !important;
            }

            .table-responsive {
                overflow: visible !important;
            }
        }
    </style>
    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="page-title-box">
                                <h4 class="page-title d-flex flex-wrap align-items-center">
                                    <span class="mr-3">Attendance Logs</span>
                                    <a href="<?= base_url('Page/admin'); ?>" class="btn btn-success btn-sm d-inline-flex align-items-center mr-2 d-lg-none">
                                        <i class="bi bi-arrow-left mr-1"></i>
                                        <span>Back to Dashboard</span>
                                    </a>
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#filterModal">
                                        Select Activity
                                    </button>
                                </h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb p-0 m-0"></ol>
                                </div>
                                <div class="clearfix"></div>
                                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:20px 0;" />
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($activity_id) || !empty($section) || !empty($year_level) || !empty($date) || !empty($session)): ?>
                        <div class="mb-3">
                            <?php
                            $actTitle = '';
                            if (!empty($activity_id)) {
                                foreach ($activities as $a) {
                                    if ((int)$a->activity_id === (int)$activity_id) {
                                        $actTitle = (string)$a->title;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?php if (!empty($activity_id)): ?>
                                <span class="badge badge-light border mr-1"><i class="mdi mdi-flag-outline mr-1"></i><?= h($actTitle) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($section)): ?>
                                <span class="badge badge-light border mr-1"><i class="mdi mdi-account-group-outline mr-1"></i>Section: <?= h($section) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($year_level)): ?>
                                <span class="badge badge-light border mr-1"><i class="mdi mdi-school-outline mr-1"></i>Year: <?= h($year_level) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($date)): ?>
                                <span class="badge badge-light border mr-1"><i class="mdi mdi-calendar-range mr-1"></i>Date: <?= h($date) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($session)): ?>
                                <span class="badge badge-light border"><i class="mdi mdi-timetable mr-1"></i>Session: <?= strtoupper(h($session)) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <?php if (!empty($activity_id)): ?>
                                        <?php if (!empty($rows)): ?>
                                            <?php if (!empty($filter_note)): ?>
                                                <div class="alert alert-warning mb-3"><?= h($filter_note) ?></div>
                                            <?php endif; ?>

                                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                                <h5 class="mb-2 mb-lg-0">Results <span class="badge badge-primary ml-1"><?= count($rows) ?></span></h5>
                                                <div class="btn-group export-actions">
                                                    <a class="btn btn-outline-success btn-sm" href="<?= site_url('AttendanceLogs/export_csv/' . (int)$activity_id . '?' . http_build_query(['section' => $section, 'year_level' => $year_level, 'date' => $date, 'session' => $session])) ?>">
                                                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                                    </a>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="printLogsBtn">
                                                        <i class="bi bi-printer"></i> Print
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table id="logsTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%;">
                                                    <thead>
                                                        <tr>
                                                            <th>Student #</th>
                                                            <th class="d-none d-lg-table-cell">Name</th>
                                                            <th>Section</th>
                                                            <th>Session</th>
                                                            <th>Check-In</th>
                                                            <th>Check-Out</th>
                                                            <th>Course</th>
                                                            <th>Year</th>
                                                            <th>Remarks</th>
                                                            <th>Checked-In By</th>


                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if (is_array($rows) && count($rows) > 1) {
                                                            usort($rows, 'compare_attendance_rows_view');
                                                        }
                                                        ?>
                                                        <?php foreach ($rows as $r):
                                                            $mins = ($r->checked_out_at && $r->checked_in_at)
                                                                ? max(0, (int) round((strtotime($r->checked_out_at) - strtotime($r->checked_in_at)) / 60))
                                                                : null;
                                                            $courseRaw      = trim((string)($r->course ?? ''));
                                                            $courseResolved = resolve_course_code($courseRaw, $courseLookup);
                                                            $courseDisplay  = $courseResolved !== '' ? $courseResolved : course_acronym($courseRaw);
                                                            if ($courseDisplay === '' && $courseRaw !== '') {
                                                                $courseDisplay = strtoupper($courseRaw);
                                                            }
                                                            $sessionCode   = strtoupper(trim((string)($r->session ?? '')));
                                                            $remarkRaw = trim((string)($r->remarks ?? ''));
                                                            $srcLower  = strtolower((string)($r->source ?? ''));
                                                            $remarkOut = $remarkRaw !== '' ? $remarkRaw : ($srcLower === 'qr' ? 'Scanned via QR' : '—');
                                                        ?>
                                                            <tr>
                                                                <td class="student-number-cell">
                                                                    <span class="font-weight-bold"><?= h($r->student_number) ?></span>
                                                                    <?php if (trim((string)$r->student_name) !== ''): ?>
                                                                        <small class="student-name-mobile d-block d-lg-none"><?= h($r->student_name) ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="d-none d-lg-table-cell"><?= h($r->student_name) ?></td>
                                                                <td><?= h($r->section) ?></td>
                                                                <td>
                                                                    <?php if ($sessionCode !== ''): ?>
                                                                        <span class="badge badge-info"><?= h($sessionCode) ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= h(fmt_time_ampm($r->checked_in_at)) ?></td>
                                                                <td><?= h(fmt_time_ampm($r->checked_out_at)) ?></td>
                                                                <td>
                                                                    <?php if ($courseDisplay !== ''): ?>
                                                                        <span class="badge badge-secondary badge-course-code" title="<?= h($courseRaw) ?>"><?= h($courseDisplay) ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= h($r->YearLevel) ?></td>
                                                                <td><?= h($remarkOut) ?></td> <!-- NEW -->
                                                                <td><?= h($r->checked_in_by) ?></td>

                                                            </tr>

                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0">No logs matched your filters.</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mb-0">Select an activity to view attendance logs.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <?php include('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- FILTER MODAL -->
    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:12px;overflow:hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Attendance Logs</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>

                <form method="get">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-lg-6">
                                <label class="small text-muted">Activity</label>
                                <select name="activity_id" class="form-control select2" required>
                                    <option value="">Select an activity</option>
                                    <?php foreach ($activities as $a): ?>
                                        <option value="<?= (int)$a->activity_id ?>" <?= ((int)($activity_id ?? 0) === (int)$a->activity_id ? 'selected' : '') ?>><?= h($a->title) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-lg-6">
                                <label class="small text-muted">Section</label>
                                <select name="section" class="form-control select2" data-placeholder="All sections">
                                    <option value="">All sections</option>
                                    <?php if (!empty($sections)): foreach ($sections as $s):
                                            $sec = trim((string)($s->section ?? ''));
                                            if ($sec === '') continue;
                                            $year = trim((string)($s->year_level ?? ''));
                                            $course = trim((string)($s->course_code ?? ''));
                                            $labelParts = array_filter([$course, $year, $sec], function ($v) {
                                                return $v !== '';
                                            });
                                            $label = implode(' • ', $labelParts) ?: $sec;
                                            $selected = (($section ?? '') === $sec) ? 'selected' : '';
                                    ?>
                                            <option value="<?= h($sec) ?>" data-year="<?= h($year) ?>" data-course="<?= h($course) ?>" <?= $selected ?>><?= h($label) ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="small text-muted">Year Level</label>
                                <select name="year_level" class="form-control select2" data-placeholder="All year levels">
                                    <option value="">All year levels</option>
                                    <?php if (!empty($year_levels)): foreach ($year_levels as $yl):
                                            $lvl = (string)($yl->year_level ?? '');
                                            if ($lvl === '') continue;
                                    ?>
                                            <option value="<?= h($lvl) ?>" <?= (($year_level ?? '') === $lvl ? 'selected' : '') ?>><?= h($lvl) ?></option>
                                    <?php endforeach;
                                    endif; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="small text-muted">Date</label>
                                <input type="date" name="date" value="<?= h($date ?? '') ?>" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="small text-muted">Session</label>
                                <select name="session" class="form-control">
                                    <option value="">All</option>
                                    <option value="am" <?= (($session ?? '') === 'am' ? 'selected' : '') ?>>AM</option>
                                    <option value="pm" <?= (($session ?? '') === 'pm' ? 'selected' : '') ?>>PM</option>
                                    <option value="eve" <?= (($session ?? '') === 'eve' ? 'selected' : '') ?>>EVE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= site_url('AttendanceLogs') ?>" class="btn btn-light">Clear</a>
                        <button type="submit" class="btn btn-primary">View</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>

    <script>
        (function() {
            function showAlert(options) {
                if (!options) {
                    return Promise.resolve();
                }
                if (window.UI && typeof window.UI.fire === 'function') {
                    return window.UI.fire(options);
                }
                if (options.text) {
                    window.alert(options.text);
                }
                return Promise.resolve();
            }

            var flashData = {
                error: <?= json_encode($flashError ?? null); ?>,
                success: <?= json_encode($flashSuccess ?? null); ?>,
                info: <?= json_encode($flashInfo ?? null); ?>,
                legacy: <?= json_encode($flashMsg ?? null); ?>
            };

            var alertOptions = null;
            if (flashData.error) {
                alertOptions = {
                    icon: 'error',
                    title: 'Error',
                    text: flashData.error,
                    confirmButtonColor: '#348cd4'
                };
            } else if (flashData.success) {
                alertOptions = {
                    icon: 'success',
                    title: 'Success',
                    text: flashData.success,
                    confirmButtonColor: '#348cd4'
                };
            } else if (flashData.info) {
                alertOptions = {
                    icon: 'info',
                    title: 'Notice',
                    text: flashData.info,
                    confirmButtonColor: '#348cd4'
                };
            } else if (flashData.legacy) {
                alertOptions = {
                    icon: 'info',
                    title: 'Notice',
                    text: flashData.legacy,
                    confirmButtonColor: '#348cd4'
                };
            }

            if (alertOptions) {
                showAlert(alertOptions);
            }
        })();
    </script>

    <script>
        $(function() {
            var $table = $('#logsTable');
            if ($table.length && $.fn.DataTable) {
                $table.DataTable({
                    pageLength: 25,
                    order: [
                        [7, 'asc'],
                        [2, 'asc'],
                        [1, 'asc']
                    ]

                });
            }

            var $filterModal = $('#filterModal');
            var $sectionSelect = $filterModal.find('select[name="section"]');
            var $yearSelect = $filterModal.find('select[name="year_level"]');
            var originalSectionOptions = $sectionSelect.find('option').clone();
            var $printButton = $('#printLogsBtn');

            function refreshSectionOptions(year) {
                var current = $sectionSelect.val();
                var hasSelect2 = $sectionSelect.hasClass('select2-hidden-accessible');

                $sectionSelect.find('option').remove();
                originalSectionOptions.each(function() {
                    var $opt = $(this).clone();
                    var optVal = ($opt.val() || '').toString();
                    var optYear = ($opt.data('year') || '').toString();

                    if (optVal === '' || !year || optYear === '' || optYear === year) {
                        $sectionSelect.append($opt);
                    }
                });

                if (current) {
                    var hasValue = false;
                    $sectionSelect.find('option').each(function() {
                        if ($(this).val() === current) {
                            hasValue = true;
                            return false;
                        }
                    });
                    if (hasValue) {
                        $sectionSelect.val(current);
                    } else if (year) {
                        $sectionSelect.val('');
                    }
                } else if (year) {
                    $sectionSelect.val('');
                }

                if (hasSelect2) {
                    $sectionSelect.trigger('change.select2');
                }
            }

            refreshSectionOptions($yearSelect.val());

            $filterModal.on('shown.bs.modal', function() {
                var $modal = $(this);
                if ($.fn.select2) {
                    $modal.find('.select2').select2({
                        width: '100%'
                    });
                }
                refreshSectionOptions($yearSelect.val());
            });

            $yearSelect.on('change', function() {
                refreshSectionOptions($(this).val());
            });

            if ($printButton.length) {
                $printButton.on('click', function(event) {
                    event.preventDefault();
                    window.print();
                });
            }
        });
    </script>

</body>

</html>
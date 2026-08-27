<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link href="<?= base_url(); ?>assets/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">
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
        .badge-course-code { font-size:.72rem; font-weight:700; letter-spacing:.4px; border-radius:6px; }
        .student-number-cell .student-name-mobile { color:#6b7a99; font-size:.82rem; }
        .pl-actions { display:flex; flex-wrap:wrap; gap:10px; margin:0; }
        .pl-actions > .up-btn, .pl-actions > a.up-btn, .pl-actions > button.up-btn { margin-right:10px; margin-bottom:6px; }
        @supports (gap:10px) { .pl-actions > .up-btn { margin-right:0; } }

        /* Title + actions on one row */
        .pl-header {
            display:flex; align-items:flex-start; justify-content:space-between;
            gap:18px; flex-wrap:wrap; margin-bottom:16px;
        }
        .pl-header .page-title-box { flex:1 1 auto; margin:0; }
        .pl-header .page-title-box .up-divider { margin:10px 0 0; }
        .pl-header .pl-actions { flex:0 0 auto; align-self:center; }

        /* Filter badges */
        .filter-badges { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        .filter-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:#f5f7fc; border:1px solid #e6ebf5; border-radius:999px;
            padding:6px 14px; font-size:.78rem; font-weight:700; color:#2a4090;
        }
        .filter-badge i { font-size:15px; color:#4266d4; }

        /* Table */
        #logsTable thead th {
            background:#f5f7fc; color:#6b7a99; font-size:.72rem; font-weight:800;
            letter-spacing:.1em; text-transform:uppercase; border-bottom:1px solid #e6ebf5 !important;
            padding:14px 16px; white-space:nowrap; border-left:none; border-right:none;
        }
        #logsTable tbody td {
            padding:14px 16px; vertical-align:middle; font-size:.86rem; color:#0d1b4b;
            border-bottom:1px solid #eef1f5 !important; border-left:none; border-right:none;
        }
        #logsTable tbody tr:hover { background:#f8faff !important; }
        #logsTable tbody tr:last-child td { border-bottom:none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { padding:14px 18px !important; margin:0 !important; }
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length { padding:16px 18px 12px !important; margin:0 !important; }
        .dataTables_wrapper .dataTables_filter input {
            border-radius:10px !important; border:1px solid #e6ebf5 !important;
            padding:8px 14px !important; font-size:.86rem !important; margin-left:6px !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus { border-color:#4266d4 !important; box-shadow:0 0 0 3px rgba(66,102,212,.12) !important; outline:none !important; }
        .dataTables_wrapper .dataTables_length select {
            border-radius:10px !important; border:1px solid #e6ebf5 !important; padding:6px 10px !important; margin-left:6px !important;
        }
        .dataTables_paginate .paginate_button {
            border-radius:8px !important; min-width:38px; min-height:38px;
            display:inline-flex !important; align-items:center; justify-content:center;
        }
        .dataTables_paginate .paginate_button.current,
        .dataTables_paginate .paginate_button.current:hover {
            background:linear-gradient(135deg,#2a4090,#4266d4) !important; color:#fff !important;
            border-color:#2a4090 !important;
        }

        /* Empty states */
        .al-empty { text-align:center; padding:48px 20px; color:#6b7a99; }
        .al-empty i { font-size:42px; display:block; margin-bottom:10px; color:#9aa5b8; }

        /* Print document header */
        #printHeader { display:none; }
        @media print {
            #wrapper .topbar, #wrapper .left-side-menu, #wrapper .sidebar, #wrapper .right-bar,
            .themecustomizer, .footer, .page-title-box, .pl-actions, .pl-header, .up-card-head, .up-flash,
            .filter-badges, .export-actions, .btn, .ms-tabbar, .ms-scrim,
            .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display:none !important; }
            #printHeader { display:block !important; text-align:center; margin-bottom:20px; }
            #printHeader .ph-school { font-size:16pt; font-weight:800; color:#0d1b4b; margin:0; }
            #printHeader .ph-address { font-size:10pt; color:#555; margin:2px 0 10px; }
            #printHeader .ph-title { font-size:13pt; font-weight:700; color:#2a4090; text-transform:uppercase; letter-spacing:1px; margin:8px 0 4px; }
            #printHeader .ph-meta { font-size:9pt; color:#777; display:flex; justify-content:center; gap:20px; }
            #printHeader .ph-line { height:2px; background:linear-gradient(to right,#2a4090,#4266d4,#2a4090); margin:10px 0 16px; border-radius:1px; }
            @page { size:A4 portrait; margin:14mm; }
            body { margin:0; background:#fff !important; }
            .content-page { margin-left:0 !important; margin-top:0 !important; padding:0 !important; }
            .up-card { border:none !important; box-shadow:none !important; border-radius:0 !important; }
            .up-card-body { padding:0 !important; }
            #logsTable { font-size:9pt; border-collapse:collapse; width:100% !important; display:table !important; }
            #logsTable thead { display:table-header-group !important; }
            #logsTable thead th {
                background:#2a4090 !important; color:#fff !important; font-size:7.5pt; font-weight:700;
                text-transform:uppercase; letter-spacing:.5px; padding:8px 8px !important;
                border:1px solid #2a4090 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact;
            }
            #logsTable tbody { display:table-row-group !important; }
            #logsTable tbody tr { display:table-row !important; margin:0 !important; padding:0 !important; border:0 !important; border-radius:0 !important; box-shadow:none !important; background:#fff !important; }
            #logsTable tbody td { display:table-cell !important; padding:5px 8px !important; font-size:9pt; color:#1a1a1a !important; border:1px solid #ccc !important; text-align:left !important; width:auto !important; border-bottom:1px solid #ccc !important; }
            #logsTable tbody td::before { content:'' !important; display:none !important; }
            #logsTable tbody td.d-none { display:table-cell !important; }
            #logsTable tbody tr:nth-child(even) td { background:#f5f7fc !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            #logsTable tbody tr:hover { background:transparent !important; }
            .dataTables_wrapper { display:block !important; }
            .dataTables_scrollHead { display:none !important; }
            .dataTables_scrollBody { height:auto !important; overflow:visible !important; border:0 !important; }
            .table-responsive { overflow:visible !important; border:0 !important; }
        }

        /* ===== Mobile: table becomes cards ===== */
        @media (max-width: 767.98px) {
            .pl-header { flex-direction:column; gap:10px; margin-bottom:14px; }
            .pl-header .pl-actions { align-self:flex-start; }
            .pl-actions .up-btn { font-size:.8rem; padding:8px 14px; }
            .filter-badges { gap:6px; }
            .filter-badge { font-size:.72rem; padding:5px 10px; }

            .dataTables_wrapper { display:block !important; }
            .dataTables_scrollHead { display:none !important; }
            .dataTables_scrollBody {
                overflow:visible !important; height:auto !important; border:0 !important;
            }
            .table-responsive { overflow:visible !important; border:0 !important; }

            #logsTable thead { display:none; }
            #logsTable { width:100% !important; border:0 !important; }
            #logsTable tbody { display:block; }
            #logsTable tbody tr {
                display:block; margin:0 0 14px; padding:14px 16px;
                border:1px solid #e6ebf5 !important; border-radius:14px; background:#fff;
                box-shadow:0 6px 18px rgba(13,27,75,.06);
            }
            #logsTable tbody tr:last-child { margin-bottom:0; }
            #logsTable tbody tr:hover { background:#f8faff !important; }
            #logsTable tbody td {
                display:flex; align-items:flex-start; justify-content:space-between;
                gap:.6rem; width:100%; padding:7px 0 !important;
                border:0 !important; border-bottom:1px solid #f0f3f8 !important;
                font-size:.9rem; text-align:right; white-space:normal;
            }
            #logsTable tbody tr:last-child td:last-child { border-bottom:0 !important; }
            #logsTable tbody td::before {
                flex:0 0 42%; content:attr(data-label);
                color:#6b7a99; font-size:.72rem; font-weight:700;
                letter-spacing:.04em; text-transform:uppercase; text-align:left;
            }
            /* Show the name cell on mobile (hidden by d-none d-lg-table-cell) */
            #logsTable tbody td.d-none { display:flex !important; }
            #logsTable tbody td.d-none::before { display:block; }

            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length {
                float:none !important; text-align:left !important; padding:10px 0 !important;
            }
            .dataTables_wrapper .dataTables_filter input {
                width:100% !important; margin-left:0 !important;
            }
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                float:none !important; text-align:center !important; padding:8px 0 !important;
            }
            .dataTables_paginate .paginate_button { min-width:34px; min-height:34px; }

            /* Card head: stack record count + export buttons */
            .up-card-head { flex-direction:column; align-items:flex-start; gap:10px; }
            .up-card-head .export-actions { width:100%; }
            .up-card-head .export-actions .up-btn { flex:1; text-align:center; }
        }
    </style>

    <!-- Print-only document header -->
    <div id="printHeader">
        <div class="ph-school">FBMSO Attendance</div>
        <div class="ph-address">Attendance Logs Report</div>
        <div class="ph-title">Attendance Logs</div>
        <div class="ph-meta">
            <span>Printed: <?= date('F d, Y \a\t h:i A'); ?></span>
            <?php if (!empty($rows)): ?><span>Total Records: <?= count($rows); ?></span><?php endif; ?>
        </div>
        <div class="ph-line"></div>
    </div>

    <div id="wrapper">
        <?php include('includes/top-nav-bar.php'); ?>
        <?php include('includes/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <?php if ($flashSuccess): ?><div class="up-flash up-flash-success"><?= h($flashSuccess); ?></div><?php endif; ?>
                    <?php if ($flashError): ?><div class="up-flash up-flash-danger"><?= h($flashError); ?></div><?php endif; ?>
                    <?php if ($flashInfo): ?><div class="up-flash up-flash-info"><?= h($flashInfo); ?></div><?php endif; ?>

                    <!-- Title + actions on one row -->
                    <div class="pl-header">
                        <div class="page-title-box">
                            <h4 class="up-page-title">Attendance Logs</h4>
                            <div class="up-page-sub">View and filter attendance records by activity, section, and session.</div>
                            <hr class="up-divider" />
                        </div>

                        <div class="pl-actions">
                            <a href="<?= base_url('Page/admin'); ?>" class="up-btn up-btn-ghost">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" data-toggle="modal" data-target="#filterModal">
                                <i class="mdi mdi-filter-variant"></i> Select Activity
                            </button>
                        </div>
                    </div>

                    <!-- Active filter badges -->
                    <?php if (!empty($activity_id) || !empty($section) || !empty($year_level) || !empty($date) || !empty($session)): ?>
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
                        <div class="filter-badges">
                            <?php if (!empty($activity_id)): ?>
                                <span class="filter-badge"><i class="mdi mdi-flag-outline"></i><?= h($actTitle) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($section)): ?>
                                <span class="filter-badge"><i class="mdi mdi-account-group-outline"></i>Section: <?= h($section) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($year_level)): ?>
                                <span class="filter-badge"><i class="mdi mdi-school-outline"></i>Year: <?= h($year_level) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($date)): ?>
                                <span class="filter-badge"><i class="mdi mdi-calendar-range"></i>Date: <?= h($date) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($session)): ?>
                                <span class="filter-badge"><i class="mdi mdi-timetable"></i>Session: <?= strtoupper(h($session)) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Logs table card -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="up-card">
                                <?php if (!empty($activity_id) && !empty($rows)): ?>
                                    <div class="up-card-head">
                                        <h4><i class="mdi mdi-clipboard-list-outline"></i> Attendance Results</h4>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <span class="badge badge-light" style="border-radius:999px;padding:5px 14px;font-size:.76rem;font-weight:700;color:#6b7a99;border:1px solid #e6ebf5;">
                                                <?= count($rows) ?> records
                                            </span>
                                            <div class="btn-group export-actions">
                                                <?php
                                                $csvParams = [];
                                                if (!empty($section)) $csvParams['section'] = $section;
                                                if (!empty($year_level)) $csvParams['year_level'] = $year_level;
                                                if (!empty($date)) $csvParams['date'] = $date;
                                                if (!empty($session)) $csvParams['session'] = $session;
                                                $csvQuery = !empty($csvParams) ? '?' . http_build_query($csvParams) : '';
                                                ?>
                                                <a class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;background:#dcfce7;color:#16a34a;border-color:#86efac;" href="<?= site_url('AttendanceLogs/export_csv/' . (int)$activity_id . $csvQuery) ?>">
                                                    <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                                                </a>
                                                <button type="button" class="up-btn up-btn-ghost" style="padding:6px 12px;font-size:.78rem;min-height:auto;" id="printLogsBtn">
                                                    <i class="bi bi-printer"></i> Print
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <?php if (!empty($activity_id)): ?>
                                        <?php if (!empty($rows)): ?>
                                            <?php if (!empty($filter_note)): ?>
                                                <div class="up-flash up-flash-info" style="margin:16px 18px 0;border-radius:10px;"><?= h($filter_note) ?></div>
                                            <?php endif; ?>
                                            <div class="table-responsive" style="padding:0;margin-top:1px;">
                                                <table id="logsTable" class="table table-hover dt-responsive nowrap" style="width:100%;margin:0;">
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
                                                                <td data-label="Student #" class="student-number-cell">
                                                                    <span style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:#2a4090;"><?= h($r->student_number) ?></span>
                                                                    <?php if (trim((string)$r->student_name) !== ''): ?>
                                                                        <small class="student-name-mobile d-block d-lg-none"><?= h($r->student_name) ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td data-label="Name" class="d-none d-lg-table-cell" style="font-weight:600;"><?= h($r->student_name) ?></td>
                                                                <td data-label="Section" style="color:#6b7a99;"><?= h($r->section) ?></td>
                                                                <td data-label="Session">
                                                                    <?php if ($sessionCode !== ''): ?>
                                                                        <span class="badge badge-info" style="border-radius:6px;font-size:.72rem;font-weight:700;"><?= h($sessionCode) ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td data-label="Check-In" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.82rem;"><?= h(fmt_time_ampm($r->checked_in_at)) ?></td>
                                                                <td data-label="Check-Out" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.82rem;"><?= h(fmt_time_ampm($r->checked_out_at)) ?></td>
                                                                <td data-label="Course">
                                                                    <?php if ($courseDisplay !== ''): ?>
                                                                        <span class="badge badge-secondary badge-course-code" title="<?= h($courseRaw) ?>"><?= h($courseDisplay) ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td data-label="Year" style="color:#6b7a99;"><?= h($r->YearLevel) ?></td>
                                                                <td data-label="Remarks" style="color:#6b7a99;font-size:.82rem;"><?= h($remarkOut) ?></td>
                                                                <td data-label="Checked-In By" style="color:#6b7a99;font-size:.82rem;"><?= h($r->checked_in_by) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="al-empty">
                                                <i class="mdi mdi-magnify"></i>
                                                No logs matched your filters.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="al-empty">
                                            <i class="mdi mdi-clipboard-list-outline"></i>
                                            Select an activity to view attendance logs.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height:40px;"></div>

                </div>
                <?php include('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- FILTER MODAL -->
    <div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;box-shadow:0 24px 60px rgba(13,27,75,.25);">
                <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;padding:18px 24px;">
                    <h5 class="modal-title" style="font-weight:800;color:#fff !important;display:flex;align-items:center;gap:8px;"><i class="mdi mdi-filter-variant"></i> Filter Attendance Logs</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:1.6rem;opacity:.9;text-shadow:none;color:#fff !important;"><span>&times;</span></button>
                </div>

                <form method="get" id="filterForm" onsubmit="return cleanFilterForm(this);">
                    <div class="modal-body" style="padding:24px;background:#f8fafc;">
                        <div class="form-row">
                            <div class="form-group col-lg-6">
                                <label style="font-size:.78rem;font-weight:700;color:#3b4a6b;">Activity</label>
                                <select name="activity_id" class="form-control select2" required style="border-radius:10px !important;border:1px solid #e6ebf5 !important;padding:10px 14px !important;font-size:.9rem !important;">
                                    <option value="">Select an activity</option>
                                    <?php foreach ($activities as $a): ?>
                                        <option value="<?= (int)$a->activity_id ?>" <?= ((int)($activity_id ?? 0) === (int)$a->activity_id ? 'selected' : '') ?>><?= h($a->title) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-lg-6">
                                <label style="font-size:.78rem;font-weight:700;color:#3b4a6b;">Section</label>
                                <select name="section" class="form-control select2" data-placeholder="All sections" style="border-radius:10px !important;border:1px solid #e6ebf5 !important;padding:10px 14px !important;font-size:.9rem !important;">
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
                                <label style="font-size:.78rem;font-weight:700;color:#3b4a6b;">Year Level</label>
                                <select name="year_level" class="form-control select2" data-placeholder="All year levels" style="border-radius:10px !important;border:1px solid #e6ebf5 !important;padding:10px 14px !important;font-size:.9rem !important;">
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
                                <label style="font-size:.78rem;font-weight:700;color:#3b4a6b;">Date</label>
                                <input type="date" name="date" value="<?= h($date ?? '') ?>" class="form-control" style="border-radius:10px !important;border:1px solid #e6ebf5 !important;padding:10px 14px !important;font-size:.9rem !important;">
                            </div>
                            <div class="form-group col-md-4">
                                <label style="font-size:.78rem;font-weight:700;color:#3b4a6b;">Session</label>
                                <select name="session" class="form-control" style="border-radius:10px !important;border:1px solid #e6ebf5 !important;padding:10px 14px !important;font-size:.9rem !important;">
                                    <option value="">All</option>
                                    <option value="am" <?= (($session ?? '') === 'am' ? 'selected' : '') ?>>AM</option>
                                    <option value="pm" <?= (($session ?? '') === 'pm' ? 'selected' : '') ?>>PM</option>
                                    <option value="eve" <?= (($session ?? '') === 'eve' ? 'selected' : '') ?>>EVE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border:none;padding:14px 24px;background:#f8fafc;">
                        <a href="<?= site_url('AttendanceLogs') ?>" class="up-btn up-btn-ghost">Clear</a>
                        <button type="submit" class="up-btn up-btn-primary"><i class="mdi mdi-magnify"></i> View Results</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('includes/themecustomizer.php'); ?>

    <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
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
        // Flash messages are shown by the shared toast bridge (includes/ui_kit.php).
    </script>

    <script>
        // Remove empty fields before submitting the filter form so the URL stays clean
        function cleanFilterForm(form) {
            var inputs = form.querySelectorAll('input, select');
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].name && inputs[i].value === '') {
                    inputs[i].disabled = true;
                }
            }
            return true;
        }
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
                    var dt = null;
                    try { dt = $('#logsTable').DataTable(); } catch(e) {}
                    if (dt) {
                        var savedLen = dt.page.len();
                        dt.page.len(-1).draw(false);
                        // Wait for the redraw to finish before printing
                        setTimeout(function() {
                            window.print();
                            // Restore after print dialog closes
                            setTimeout(function() {
                                dt.page.len(savedLen).draw(false);
                            }, 300);
                        }, 250);
                    } else {
                        window.print();
                    }
                });
            }

            // Fallback: also handle browser's native print (Ctrl+P)
            var dtLogs = null;
            var savedPageLenLogs = null;
            window.addEventListener('beforeprint', function() {
                if (window.jQuery && $('#logsTable').length) {
                    try {
                        dtLogs = $('#logsTable').DataTable();
                        savedPageLenLogs = dtLogs.page.len();
                        dtLogs.page.len(-1).draw(false);
                    } catch(e) {}
                }
            });
            window.addEventListener('afterprint', function() {
                if (dtLogs && savedPageLenLogs !== null) {
                    try { dtLogs.page.len(savedPageLenLogs).draw(false); } catch(e) {}
                }
            });
        });
    </script>

</body>

</html>
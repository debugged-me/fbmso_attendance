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

                    <!-- Title + actions -->
                    <div class="page-title-box">
                            <h4 class="up-page-title">Payment Activity Log</h4>
                            <div class="up-page-sub">Every edit or deletion made to a student payment, and who made it.</div>
                            <hr class="up-divider" />
                        </div>

                    <?php
                    $plRows = (array)($rows ?? []);
                    $plEdit = $plDel = $plToday = 0; $plD = date('Y-m-d');
                    foreach ($plRows as $row) {
                        if (strtolower((string)($row->action ?? '')) === 'delete') $plDel++; else $plEdit++;
                        if (substr((string)($row->changed_at ?? ''), 0, 10) === $plD) $plToday++;
                    }
                    ?>
                    <div class="nx-stats" style="margin-top:2px;margin-bottom:18px;">
                        <a class="nx-stat blue" href="javascript:void(0)" data-dtfilter="">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format(count($plRows)); ?></div>
                                    <div class="nx-stat-label">Log Entries</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-history"></i></div>
                            </div>
                            <div class="nx-stat-foot">Show all <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat orange" href="javascript:void(0)" data-dtfilter="Edited">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plEdit); ?></div>
                                    <div class="nx-stat-label">Edited</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-pencil-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Filter table <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <a class="nx-stat rose" href="javascript:void(0)" data-dtfilter="Deleted">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plDel); ?></div>
                                    <div class="nx-stat-label">Deleted</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-delete-outline"></i></div>
                            </div>
                            <div class="nx-stat-foot">Filter table <i class="mdi mdi-arrow-right"></i></div>
                        </a>
                        <div class="nx-stat cyan">
                            <div class="nx-stat-main">
                                <div>
                                    <div class="nx-stat-num"><?= number_format($plToday); ?></div>
                                    <div class="nx-stat-label">Today</div>
                                </div>
                                <div class="nx-stat-icon"><i class="mdi mdi-calendar-today"></i></div>
                            </div>
                            <div class="nx-stat-foot">Changed today <i class="mdi mdi-arrow-right"></i></div>
                        </div>
                    </div>

                    <!-- LOG -->
                    <div class="row">
                        <div class="col-12">
                            <div class="up-card">
                                <div class="up-card-head">
<div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
<h4><i class="mdi mdi-history"></i> Edited &amp; Deleted Payments</h4>
                                    <span class="badge badge-purple"><?= count($rows); ?> entries</span>
</div>
<div class="pl-actions">
							<a href="<?= base_url(in_array($this->session->userdata('level'), ['Cashier', 'Auditor'], true) ? 'Page/accounting' : 'Page/admin'); ?>" class="up-btn up-btn-ghost d-md-none">
                                <i class="mdi mdi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="button" class="up-btn up-btn-primary" onclick="window.open('<?= base_url('Accounting/paymentAuditLog'); ?>?print=1', '_blank')">
                                <i class="mdi mdi-printer"></i> Print
                            </button>
                            <?php if ($this->session->userdata('level') === 'Cashier'): ?>
                                <a href="<?= base_url('Accounting/Payment'); ?>" class="up-btn up-btn-ghost">
                                    <i class="mdi mdi-cash-multiple"></i> Payment Entry
                                </a>
                            <?php endif; ?>
                        </div>
</div>
                                <div class="up-card-body" style="padding:0 !important;">
                                    <div class="table-responsive up-rt-host">
                                        <table id="paymentLogTable" class="table table-bordered table-sm up-rt ms-rt-keep" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Date &amp; Time</th>
                                                    <th>Action</th>
                                                    <th>O.R.</th>
                                                    <th>Student</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Changed By</th>
                                                    <th class="text-center" style="width:52px;"><span class="sr-only">Details</span></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Before/after for the side panel, formatted once here.
                                                $plFieldLabels = ['StudentNumber' => 'Student no.', 'ORNumber' => 'O.R. number', 'PDate' => 'Payment date', 'Amount' => 'Amount', 'description' => 'Description'];
                                                $plFormat = function ($field, $value) {
                                                    if ($value === null || trim((string)$value) === '') return null;
                                                    if ($field === 'Amount') return '₱ ' . number_format((float)$value, 2);
                                                    if ($field === 'PDate' && strtotime((string)$value)) return date('M j, Y', strtotime((string)$value));
                                                    return (string)$value;
                                                };
                                                $plLog = [];
                                                ?>
                                                <?php foreach ($rows as $i => $row): ?>
                                                    <?php
                                                    $studentName = trim((string)($row->LastName ?? ''));
                                                    if ($studentName !== '') $studentName .= ', ';
                                                    $studentName .= trim((string)($row->FirstName ?? ''));
                                                    if (trim($studentName) === '') $studentName = (string)($row->student_number ?? '');

                                                    $action = (string)($row->action ?? '');
                                                    $badgeClass = $action === 'delete' ? 'badge-danger' : 'badge-warning';
                                                    $badgeLabel = $action === 'delete' ? 'Deleted' : 'Edited';

                                                    $old = json_decode((string)($row->old_values ?? ''), true);
                                                    $new = json_decode((string)($row->new_values ?? ''), true);
                                                    $old = is_array($old) ? $old : null;
                                                    $new = is_array($new) ? $new : null;
                                                    $changes = $snapshot = [];
                                                    $unchanged = 0;
                                                    foreach (array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? []))) as $field) {
                                                        $label = $plFieldLabels[$field] ?? ucwords(str_replace('_', ' ', (string)$field));
                                                        $before = $plFormat($field, $old[$field] ?? null);
                                                        $after = $plFormat($field, $new[$field] ?? null);
                                                        if ($old !== null && $new !== null) {
                                                            if ($before === $after) $unchanged++; else $changes[] = [$label, $before, $after];
                                                        }
                                                        $snapshot[] = [$label, $old !== null ? $before : $after];
                                                    }
                                                    $plLog[] = [
                                                        'action'    => $badgeLabel,
                                                        'tone'      => $action === 'delete' ? 'danger' : 'warning',
                                                        'when'      => date('D, M j, Y · g:i A', strtotime((string)$row->changed_at)),
                                                        'or'        => (string)($row->or_number ?? ''),
                                                        'paymentId' => (string)($row->payment_id ?? ''),
                                                        'studno'    => (string)($row->student_number ?? ''),
                                                        'student'   => $studentName,
                                                        'desc'      => (string)($row->description ?? ''),
                                                        'amount'    => (float)($row->amount ?? 0),
                                                        'by'        => (string)($row->changed_by ?? ''),
                                                        'byRole'    => (string)($row->actor_level ?? ''),
                                                        'diff'      => $old !== null && $new !== null,
                                                        'changes'   => $changes,
                                                        'unchanged' => $unchanged,
                                                        'snapshot'  => $snapshot,
                                                    ];
                                                    ?>
                                                    <tr class="sd-clickable" data-sd-key="<?= (int)$i; ?>" tabindex="0">
                                                        <td data-label="Date & Time" style="color:var(--up-muted);white-space:nowrap;"><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string)$row->changed_at)), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Action"><span class="badge <?= $badgeClass; ?>"><?= $badgeLabel; ?></span></td>
                                                        <td data-label="O.R." style="font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:700;color:var(--up-blue);"><?= htmlspecialchars((string)($row->or_number ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Student" style="font-weight:600;color:var(--up-ink);">
                                                            <?php if (trim((string)($row->student_number ?? '')) !== ''): ?>
                                                                <a href="#" class="sd-link" data-student-balance data-sd-key="<?= htmlspecialchars((string)$row->student_number, ENT_QUOTES, 'UTF-8'); ?>" title="View balance"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></a>
                                                            <?php else: ?>
                                                                <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-label="Description" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->description ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Amount" class="text-right" style="font-weight:700;color:var(--up-ink);white-space:nowrap;">₱ <?= number_format((float)($row->amount ?? 0), 2); ?></td>
                                                        <td data-label="Changed By" style="color:var(--up-muted);"><?= htmlspecialchars((string)($row->changed_by ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td data-label="Details" class="text-center"><button type="button" class="sd-open-btn" data-sd-open aria-label="View change details" title="Details"><i class="mdi mdi-chevron-right"></i></button></td>
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
    <?php include('includes/side_drawer.php'); ?>
    <script>
        var PL_LOG = <?= json_encode($plLog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
        $(function() {
            var dt = $('#paymentLogTable').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{ targets: -1, orderable: false, searchable: false }]
            });

            if (!window.SideDrawer) return;
            var SD = SideDrawer;
            StudentBalancePanel({ url: <?= json_encode(site_url('Accounting/studentSummary')); ?> });

            SD.create({
                label: 'Payment change',
                nav: true,
                rowSelector: '#paymentLogTable tbody tr[data-sd-key]',
                dataTable: dt,
                render: function(key, panel) {
                    var e = PL_LOG[key];
                    if (!e) return;
                    var html = '<div class="sd-tags">' + SD.pill(e.action, e.tone) + '</div>'
                        + '<h5 class="sd-title">' + SD.esc(e.or ? 'O.R. ' + e.or : 'Payment') + '</h5>'
                        + (e.desc ? '<p class="sd-desc">' + SD.esc(e.desc) + '</p>' : '')
                        + '<div class="sd-amount">' + SD.money(e.amount) + '</div>'
                        + '<div class="sd-when"><i class="mdi mdi-clock-outline"></i> ' + SD.esc(e.when) + '</div>';

                    html += SD.section('Overview', SD.grid([
                        SD.field('Student', SD.esc(e.student) + (e.studno && e.studno !== e.student ? '<small>' + SD.esc(e.studno) + '</small>' : ''), false, true),
                        SD.field('Changed by', SD.esc(e.by) + (e.byRole ? '<small>' + SD.esc(e.byRole) + '</small>' : ''), false, true),
                        SD.field('Payment ID', e.paymentId && e.paymentId !== '0' ? '#' + e.paymentId : '')
                    ]));

                    if (e.diff) {
                        var rows = e.changes.map(function(c) {
                            return '<div class="sd-diff-row"><div class="sd-diff-field">' + SD.esc(c[0]) + '</div><div class="sd-diff-values">'
                                + '<span class="sd-diff-old' + (c[1] === null ? ' is-empty' : '') + '">' + SD.esc(c[1] === null ? 'empty' : c[1]) + '</span>'
                                + '<i class="mdi mdi-arrow-right"></i>'
                                + '<span class="sd-diff-new' + (c[2] === null ? ' is-empty' : '') + '">' + SD.esc(c[2] === null ? 'empty' : c[2]) + '</span></div></div>';
                        }).join('');
                        html += SD.section('Changes', (rows ? '<div class="sd-diff">' + rows + '</div>' : '<p class="sd-empty">No field values changed.</p>')
                            + (e.unchanged ? '<p class="sd-muted-note">' + e.unchanged + ' unchanged field' + (e.unchanged === 1 ? '' : 's') + ' hidden</p>' : ''),
                            e.changes.length || null);
                    } else if (e.snapshot.length) {
                        html += SD.section(e.tone === 'danger' ? 'Deleted payment' : 'Recorded values', SD.kv(e.snapshot, e.tone === 'danger' ? 'sd-kv-danger' : ''));
                    }

                    panel.body(html);
                    panel.footer(e.studno
                        ? '<button type="button" class="sd-btn-ghost" data-student-balance data-sd-key="' + SD.esc(e.studno) + '"><i class="mdi mdi-account-cash-outline"></i> Student balance</button>'
                        : null);
                }
            });
        });
        $(document).on('click', '.nx-stat[data-dtfilter]', function(e) {
            e.preventDefault();
            try {
                $('#paymentLogTable').DataTable().search(String($(this).data('dtfilter') || '')).draw();
            } catch (err) {}
        });
    </script>
</body>

</html>

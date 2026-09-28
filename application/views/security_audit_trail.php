<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('audit_e')) {
    function audit_e($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('audit_redact')) {
    function audit_redact($value)
    {
        if (!is_array($value)) return $value;
        $sensitive = '/pass(word)?|password_attempt|secret|token|api[_-]?key|authorization|cookie|hash/i';
        foreach ($value as $key => $item) {
            if (preg_match($sensitive, (string)$key)) {
                $value[$key] = '[REDACTED]';
            } else {
                $value[$key] = audit_redact($item);
            }
        }
        return $value;
    }
}

if (!function_exists('audit_pretty')) {
    function audit_pretty($value)
    {
        if ($value === null || trim((string)$value) === '') return '';
        $decoded = json_decode((string)$value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return (string)json_encode(
                audit_redact($decoded),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        return (string)$value;
    }
}

$filters = isset($filters) && is_array($filters) ? $filters : array();
$summary = isset($summary) && is_array($summary) ? $summary : array();
$sourceLabels = array(
    'activity' => 'App activity',
    'security' => 'Security',
    'login'    => 'Sign-in',
    'payment'  => 'Payment',
);
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<body>
<div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h4 class="page-title mb-1"><i class="mdi mdi-history"></i> Unified Audit Trail</h4>
                                <div class="text-muted">Admin, Committee, Cashier, Auditor, and Student activity</div>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <a href="<?= base_url('Page/superAdmin'); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-view-dashboard-outline"></i> Dashboard
                                </a>
                                <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-shield-account"></i> Security
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 shadow-sm audit-notice">
                    <i class="mdi mdi-information-outline mr-1"></i>
                    This screen is read-only. Open an event to inspect its saved before/after values; sensitive credential fields are redacted.
                </div>

                <div class="row">
                    <?php
                    $cards = array(
                        array('Events today', (int)($summary['events_today'] ?? 0), 'mdi-pulse', 'primary'),
                        array('Deletes · 30 days', (int)($summary['deletions'] ?? 0), 'mdi-delete-alert-outline', 'danger'),
                        array('Failed / denied · 30 days', (int)($summary['failed_or_denied'] ?? 0), 'mdi-shield-alert-outline', 'warning'),
                        array('Active actors · 30 days', (int)($summary['active_actors'] ?? 0), 'mdi-account-group-outline', 'info'),
                    );
                    foreach ($cards as $card):
                    ?>
                    <div class="col-xl-3 col-sm-6">
                        <div class="card shadow-sm audit-summary-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-<?= audit_e($card[3]); ?> text-white d-flex align-items-center justify-content-center mr-3" style="width:46px;height:46px;min-width:46px">
                                    <i class="mdi <?= audit_e($card[2]); ?>" style="font-size:23px"></i>
                                </div>
                                <div><div class="text-muted small"><?= audit_e($card[0]); ?></div><h3 class="mb-0"><?= number_format($card[1]); ?></h3></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card shadow-sm audit-filter-card">
                    <div class="card-header audit-card-heading d-flex align-items-center justify-content-between">
                        <div>
                            <strong><i class="mdi mdi-filter-variant mr-1"></i> Filter activity</strong>
                            <small class="text-muted d-block">Narrow the trail by role, source, action, result, or date.</small>
                        </div>
                        <span class="badge badge-light border"><?= number_format((int)$total); ?> total</span>
                    </div>
                    <div class="card-body">
                        <form method="get" action="<?= base_url('Securityadmin/audit_trail'); ?>">
                            <div class="row">
                                <div class="form-group col-xl-2 col-md-6">
                                    <label class="audit-field-label">Role</label>
                                    <select name="role" class="form-control">
                                        <option value="">All monitored roles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= audit_e($role); ?>" <?= ($filters['role'] ?? '') === $role ? 'selected' : ''; ?>><?= audit_e($role); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-xl-2 col-md-6">
                                    <label class="audit-field-label">Log source</label>
                                    <select name="source" class="form-control">
                                        <option value="">All sources</option>
                                        <?php foreach ($sourceLabels as $key => $label): ?>
                                            <option value="<?= audit_e($key); ?>" <?= ($filters['source'] ?? '') === $key ? 'selected' : ''; ?>><?= audit_e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-xl-2 col-md-6">
                                    <label class="audit-field-label">Action</label>
                                    <select name="action" class="form-control">
                                        <option value="">All actions</option>
                                        <?php foreach (array('delete'=>'Deletes','update'=>'Updates / edits','create'=>'Creates','login'=>'Sign-ins / sign-outs','denied'=>'Failed / denied') as $key => $label): ?>
                                            <option value="<?= $key; ?>" <?= ($filters['action'] ?? '') === $key ? 'selected' : ''; ?>><?= audit_e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-xl-2 col-md-6">
                                    <label class="audit-field-label">Result</label>
                                    <select name="status" class="form-control">
                                        <option value="">All results</option>
                                        <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : ''; ?>>Successful</option>
                                        <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed / blocked</option>
                                    </select>
                                </div>
                                <div class="form-group col-xl-4 col-md-12">
                                    <label class="audit-field-label">Date range</label>
                                    <div class="audit-date-range">
                                        <div><span>From</span><input type="date" name="from" value="<?= audit_e($filters['from'] ?? ''); ?>" class="form-control"></div>
                                        <div><span>To</span><input type="date" name="to" value="<?= audit_e($filters['to'] ?? ''); ?>" class="form-control"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="form-group col-xl-9 col-lg-8 mb-lg-0">
                                    <label class="audit-field-label">Search</label>
                                    <div class="audit-search-wrap">
                                        <i class="mdi mdi-magnify"></i>
                                        <input type="search" name="q" maxlength="100" value="<?= audit_e($filters['q'] ?? ''); ?>" class="form-control" placeholder="Actor, description, module, record ID, or IP address">
                                    </div>
                                </div>
                                <div class="form-group col-xl-3 col-lg-4 mb-0 text-lg-right audit-filter-actions">
                                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-filter-variant"></i> Apply filters</button>
                                    <a href="<?= base_url('Securityadmin/audit_trail'); ?>" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <div class="text-muted">
                        <strong><?= number_format((int)$total); ?></strong> matching event<?= (int)$total === 1 ? '' : 's'; ?>
                        · Page <?= (int)$page; ?> of <?= max(1, (int)ceil($total / $per_page)); ?>
                    </div>
                    <small class="text-muted">Newest first · Times shown in server time</small>
                </div>

                <div class="card shadow-sm audit-log-card">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 audit-table">
                            <thead>
                                <tr>
                                    <th class="audit-col-time">Time</th>
                                    <th class="audit-col-actor">Actor</th>
                                    <th class="audit-col-activity">Activity</th>
                                    <th class="audit-col-record">Affected record</th>
                                    <th class="audit-col-result">Result</th>
                                    <th class="audit-col-details">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($events)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-5"><i class="mdi mdi-magnify d-block mb-2" style="font-size:34px"></i>No audit events match these filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($events as $event):
                                    $isDelete = strpos(strtolower((string)$event['action']), 'delete') !== false;
                                    $failed = (int)$event['succeeded'] !== 1;
                                    $detailsId = 'details-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$event['event_key']);
                                    $old = audit_pretty($event['old_values']);
                                    $new = audit_pretty($event['new_values']);
                                    $extra = audit_pretty($event['extra']);
                                ?>
                                <tr class="<?= $isDelete ? 'audit-delete-row' : ($failed ? 'audit-failed-row' : ''); ?>">
                                    <td><span class="text-nowrap font-weight-medium"><?= audit_e(date('M d, Y', strtotime($event['event_time']))); ?></span><br><small class="text-muted"><?= audit_e(date('h:i:s A', strtotime($event['event_time']))); ?></small></td>
                                    <td>
                                        <strong><?= audit_e($event['username'] ?: 'Unknown user'); ?></strong>
                                        <?php if (!empty($event['full_name'])): ?><br><small class="text-muted"><?= audit_e($event['full_name']); ?></small><?php endif; ?>
                                        <br><span class="badge badge-dark mt-1"><?= audit_e($event['actor_level']); ?></span>
                                    </td>
                                    <td>
                                        <div class="audit-activity-meta">
                                            <span class="badge badge-<?= $isDelete ? 'danger' : ($failed ? 'warning' : 'primary'); ?>"><?= audit_e(strtoupper(str_replace('_', ' ', $event['action']))); ?></span>
                                            <span class="audit-source-label"><?= audit_e($sourceLabels[$event['source']] ?? ucfirst($event['source'])); ?></span>
                                        </div>
                                        <div class="audit-module-name"><?= audit_e($event['module']); ?></div>
                                        <?php if (!empty($event['description'])): ?><div class="audit-description"><?= audit_e($event['description']); ?></div><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-monospace small"><?= audit_e($event['table_name'] ?: '—'); ?></span>
                                        <?php if (!empty($event['record_pk'])): ?><br><small>ID: <strong><?= audit_e($event['record_pk']); ?></strong></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="audit-result audit-result-<?= $failed ? 'failed' : 'success'; ?>">
                                            <i class="mdi <?= $failed ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline'; ?>"></i>
                                            <?= $failed ? 'Failed / blocked' : 'Success'; ?>
                                        </span>
                                        <?php if (!empty($event['ip_address'])): ?><div class="audit-ip">IP <?= audit_e($event['ip_address']); ?></div><?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary audit-view-btn" type="button" data-toggle="collapse" data-target="#<?= audit_e($detailsId); ?>" aria-expanded="false">
                                            <i class="mdi mdi-eye-outline"></i> Inspect
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse audit-detail-row" id="<?= audit_e($detailsId); ?>">
                                    <td colspan="6">
                                        <div class="p-2 p-md-3">
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <h6 class="text-muted text-uppercase small font-weight-bold">Before / deleted record</h6>
                                                    <pre class="audit-json <?= $isDelete ? 'audit-json-danger' : ''; ?>"><?= audit_e($old !== '' ? $old : 'No before snapshot was recorded for this event.'); ?></pre>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <h6 class="text-muted text-uppercase small font-weight-bold">After</h6>
                                                    <pre class="audit-json"><?= audit_e($new !== '' ? $new : 'No after snapshot was recorded for this event.'); ?></pre>
                                                </div>
                                            </div>
                                            <?php if ($extra !== '' || !empty($event['user_agent'])): ?>
                                            <div class="row">
                                                <?php if ($extra !== ''): ?><div class="col-lg-6"><h6 class="text-muted text-uppercase small font-weight-bold">Context</h6><pre class="audit-json"><?= audit_e($extra); ?></pre></div><?php endif; ?>
                                                <?php if (!empty($event['user_agent'])): ?><div class="col-lg-6"><h6 class="text-muted text-uppercase small font-weight-bold">Device / browser</h6><div class="small text-break"><?= audit_e($event['user_agent']); ?></div></div><?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($pagination)): ?><div class="d-flex justify-content-center"><?= $pagination; ?></div><?php endif; ?>
            </div>
        </div>
        <?php include('includes/footer_plugins.php'); ?>
        <?php include('includes/footer.php'); ?>
    </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<style>
    .audit-notice {
        padding: .8rem 1rem;
        border-left: 4px solid #38aeb7 !important;
        border-radius: 8px;
    }

    .audit-summary-card,
    .audit-filter-card,
    .audit-log-card {
        border: 0;
        border-radius: 10px;
    }

    .audit-summary-card .card-body {
        min-height: 90px;
        padding: 1.15rem;
    }

    .audit-card-heading {
        padding: .9rem 1.25rem;
        background: #fff;
        border-bottom: 1px solid #edf0f4;
        border-radius: 10px 10px 0 0 !important;
    }

    .audit-filter-card .card-body {
        padding: 1.2rem 1.25rem 1.25rem;
    }

    .audit-field-label {
        display: block;
        margin-bottom: .4rem;
        color: #566176;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .audit-filter-card .form-control {
        height: 42px;
        border-color: #dce2e9;
        border-radius: 7px;
        font-size: .88rem;
    }

    .audit-filter-card .form-control:focus {
        border-color: #3db4bd;
        box-shadow: 0 0 0 3px rgba(61, 180, 189, .12);
    }

    .audit-date-range {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: .65rem;
    }

    .audit-date-range > div {
        position: relative;
    }

    .audit-date-range > div > span {
        position: absolute;
        top: 12px;
        left: 11px;
        z-index: 1;
        color: #8a94a5;
        font-size: .72rem;
        font-weight: 700;
        pointer-events: none;
        text-transform: uppercase;
    }

    .audit-date-range input {
        padding-left: 50px;
    }

    .audit-search-wrap {
        position: relative;
    }

    .audit-search-wrap > i {
        position: absolute;
        top: 10px;
        left: 13px;
        z-index: 1;
        color: #8a94a5;
        font-size: 19px;
    }

    .audit-search-wrap .form-control {
        padding-left: 41px;
    }

    .audit-filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
    }

    .audit-filter-actions .btn {
        min-height: 42px;
        padding: .55rem .9rem;
        border-radius: 7px;
        white-space: nowrap;
    }

    .audit-log-card {
        overflow: hidden;
    }

    .audit-table {
        min-width: 1010px;
    }

    .audit-table thead th {
        padding: .8rem .75rem;
        border: 0;
        background: #f5f7fa;
        color: #596579;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .audit-table tbody td {
        padding: .85rem .75rem;
        border-top-color: #edf0f4;
        vertical-align: middle;
    }

    .audit-col-time { width: 145px; }
    .audit-col-actor { width: 205px; }
    .audit-col-activity { min-width: 285px; }
    .audit-col-record { width: 180px; }
    .audit-col-result { width: 155px; }
    .audit-col-details { width: 105px; text-align: right; }
    .audit-table tbody td:last-child { text-align: right; }

    .audit-activity-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .4rem;
        margin-bottom: .35rem;
    }

    .audit-source-label {
        padding: .14rem .42rem;
        border: 1px solid #dfe4ea;
        border-radius: 999px;
        background: #f8f9fb;
        color: #6b7484;
        font-size: .68rem;
        font-weight: 700;
    }

    .audit-module-name {
        color: #30394a;
        font-weight: 700;
        line-height: 1.35;
    }

    .audit-description {
        margin-top: .12rem;
        color: #8a94a5;
        font-size: .78rem;
        line-height: 1.35;
    }

    .audit-result {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .76rem;
        font-weight: 700;
    }

    .audit-result i { font-size: 16px; }
    .audit-result-success { color: #18864b; }
    .audit-result-failed { color: #c03a2b; }

    .audit-ip {
        margin-top: .25rem;
        color: #8a94a5;
        font-family: SFMono-Regular, Consolas, monospace;
        font-size: .69rem;
    }

    .audit-view-btn {
        min-width: 88px;
        border-radius: 7px;
    }

    .audit-delete-row { background: #fff8f8; box-shadow: inset 3px 0 0 #e35d6a; }
    .audit-failed-row { background: #fffbf3; box-shadow: inset 3px 0 0 #f1b44c; }
    .audit-detail-row > td { background: #f8fafc; }

    .audit-json {
        max-height: 260px;
        overflow: auto;
        padding: 12px;
        border: 1px solid #e3e8ef;
        border-radius: 7px;
        background: #fff;
        color: #334155;
        font-size: 12px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .audit-json-danger { border-left: 4px solid #dc3545; }

    @media (max-width: 991.98px) {
        .audit-filter-actions { justify-content: flex-start; }
    }

    @media (max-width: 575.98px) {
        .audit-date-range { grid-template-columns: 1fr; }
        .audit-filter-actions .btn { flex: 1; }
        .audit-notice { font-size: .82rem; }
    }
</style>
</body>
</html>

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

if (!function_exists('audit_label')) {
    function audit_label($key)
    {
        $labels = array(
            'trusted'          => 'Trusted device',
            'revoked'          => 'Access revoked',
            'login_count'      => 'Login count',
            'other_accounts'   => 'Other accounts on this device',
            'sessions_ended'   => 'Sessions ended',
            'allowed_levels'   => 'Allowed roles',
            'level'            => 'Account role',
            'route'            => 'Requested page',
            'posted_sy'        => 'School year',
            'posted_semester'  => 'Semester',
            // Payment, expense and fee records
            'StudentNumber'    => 'Student no.',
            'ORNumber'         => 'O.R. number',
            'or_number'        => 'O.R. number',
            'student_number'   => 'Student no.',
            'PDate'            => 'Payment date',
            'PaymentType'      => 'Payment type',
            'Sem'              => 'Semester',
            'SY'               => 'School year',
            'expensesid'       => 'Expense ID',
            'ExpenseDate'      => 'Expense date',
            'categoryID'       => 'Category ID',
            'feesid'           => 'Fee ID',
            'feesType'         => 'Fee type',
        );
        $key = (string)$key;
        return $labels[$key] ?? ucwords(str_replace(array('_', '-'), ' ', $key));
    }
}

if (!function_exists('audit_human_value')) {
    function audit_human_value($value)
    {
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        if ($value === null || $value === '') return 'Not recorded';
        if (is_array($value)) {
            $simple = true;
            foreach ($value as $item) {
                if (is_array($item) || is_object($item)) {
                    $simple = false;
                    break;
                }
            }
            if ($simple) return implode(', ', array_map('audit_human_value', $value));
            return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string)$value;
    }
}

if (!function_exists('audit_flatten_context')) {
    function audit_flatten_context($value, $prefix = '')
    {
        $items = array();
        if (!is_array($value)) {
            $items[] = array($prefix !== '' ? $prefix : 'Details', audit_human_value($value));
            return $items;
        }

        foreach ($value as $key => $item) {
            $label = $prefix !== '' ? $prefix . ' · ' . audit_label($key) : audit_label($key);
            if (is_array($item) && !empty($item) && array_keys($item) !== range(0, count($item) - 1)) {
                $items = array_merge($items, audit_flatten_context($item, $label));
            } else {
                $items[] = array($label, audit_human_value($item));
            }
        }
        return $items;
    }
}

if (!function_exists('audit_context_items')) {
    function audit_context_items($value)
    {
        if ($value === null || trim((string)$value) === '') return array();
        $decoded = json_decode((string)$value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(array('Details', (string)$value));
        }
        return audit_flatten_context(audit_redact($decoded));
    }
}

if (!function_exists('audit_device_items')) {
    function audit_device_items($userAgent)
    {
        $device = device_summary($userAgent);
        if ($device === null) return array();
        return array(
            array('Device type', $device['type']),
            array('Operating system', $device['os']),
            array('Browser', $device['browser']),
        );
    }
}

if (!function_exists('audit_value_map')) {
    function audit_value_map($value)
    {
        if ($value === null || trim((string)$value) === '') return null;
        $decoded = json_decode((string)$value, true);
        if (json_last_error() !== JSON_ERROR_NONE) return array('Details' => (string)$value);
        $decoded = audit_redact($decoded);
        $map = array();
        foreach (audit_flatten_context(is_array($decoded) ? $decoded : array('Value' => $decoded)) as $item) {
            $map[(string)$item[0]] = (string)$item[1];
        }
        return $map;
    }
}

if (!function_exists('audit_action_tone')) {
    function audit_action_tone($action, $failed)
    {
        $action = strtolower((string)$action);
        if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) return 'danger';
        if ($failed || preg_match('/fail|denied|block/', $action)) return 'warning';
        if (preg_match('/create|add|insert|payment/', $action)) return 'success';
        if (preg_match('/update|edit|change|reset/', $action)) return 'info';
        return 'neutral';
    }
}

if (!function_exists('audit_day_label')) {
    function audit_day_label($timestamp)
    {
        $day = date('Y-m-d', $timestamp);
        if ($day === date('Y-m-d')) return 'Today';
        if ($day === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
        return date($day >= date('Y-01-01') ? 'M j' : 'M j, Y', $timestamp);
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
$actionLabels = array(
    'delete' => 'Deletes',
    'update' => 'Updates / edits',
    'create' => 'Creates',
    'login'  => 'Sign-ins / sign-outs',
    'denied' => 'Failed / denied',
);
$statusLabels = array('success' => 'Successful', 'failed' => 'Failed / blocked');
$auditUrl = base_url('Securityadmin/audit_trail');

$chips = array();
$chipDefs = array(
    'q'      => array('Search', null),
    'role'   => array('Role', null),
    'source' => array('Source', $sourceLabels),
    'action' => array('Action', $actionLabels),
    'status' => array('Result', $statusLabels),
    'from'   => array('From', null),
    'to'     => array('To', null),
);
foreach ($chipDefs as $key => $def) {
    $value = (string)($filters[$key] ?? '');
    if ($value === '') continue;
    $remaining = array_filter($filters, function ($v) { return (string)$v !== ''; });
    unset($remaining[$key]);
    $chips[] = array(
        'label' => $def[0],
        'value' => $def[1][$value] ?? $value,
        'href'  => $auditUrl . (empty($remaining) ? '' : '?' . http_build_query($remaining)),
    );
}
$advancedCount = count(array_filter(array('role', 'source', 'action', 'status', 'from', 'to'), function ($key) use ($filters) {
    return (string)($filters[$key] ?? '') !== '';
}));

$prepared = array();
foreach ((isset($events) && is_array($events) ? $events : array()) as $index => $event) {
    $failed = (int)$event['succeeded'] !== 1;
    $timestamp = strtotime((string)$event['event_time']) ?: time();
    $oldMap = audit_value_map($event['old_values']);
    $newMap = audit_value_map($event['new_values']);
    $changes = array();
    $unchanged = array();
    if ($oldMap !== null && $newMap !== null) {
        foreach (array_unique(array_merge(array_keys($oldMap), array_keys($newMap))) as $field) {
            $before = $oldMap[$field] ?? null;
            $after = $newMap[$field] ?? null;
            // A field the change did not write kept its value; it was not emptied.
            if (!array_key_exists($field, $newMap) || $before === $after) {
                $unchanged[] = $field;
            } else {
                $changes[] = array($field, $before, $after);
            }
        }
    }
    $displayName = trim((string)($event['full_name'] ?? '')) ?: ((string)$event['username'] ?: 'Unknown user');
    $role = (string)($event['actor_level'] ?: 'Unknown');
    $prepared[] = array(
        'event'     => $event,
        'tpl'       => 'audit-event-' . $index,
        'failed'    => $failed,
        'tone'      => audit_action_tone($event['action'], $failed),
        'action'    => ucwords(strtolower(str_replace(array('_', '-'), ' ', (string)$event['action']))),
        'timestamp' => $timestamp,
        'name'      => $displayName,
        'role'      => $role,
        'source'    => $sourceLabels[$event['source']] ?? ucfirst((string)$event['source']),
        'oldMap'    => $oldMap,
        'newMap'    => $newMap,
        'changes'   => $changes,
        'unchanged' => $unchanged,
        'rawOld'    => audit_pretty($event['old_values']),
        'rawNew'    => audit_pretty($event['new_values']),
        'context'   => audit_context_items($event['extra']),
        'device'    => audit_device_items($event['user_agent']),
    );
}
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
            <div class="container-fluid audit-page">
                <div class="audit-header">
                    <div class="audit-header-title">
                        <span class="audit-header-icon"><i class="mdi mdi-history"></i></span>
                        <div>
                            <h4 class="mb-0">
                                Audit Trail
                                <span class="audit-readonly" data-toggle="tooltip" data-placement="bottom" title="Events can't be edited or deleted here. Passwords, tokens, and other secrets are hidden.">
                                    <i class="mdi mdi-lock-outline"></i> Read-only
                                </span>
                            </h4>
                            <p class="audit-header-sub mb-0">Activity across all monitored roles</p>
                        </div>
                    </div>
                    <div class="audit-header-actions">
                        <a href="<?= base_url('Page/superAdmin'); ?>" class="btn btn-sm audit-btn-ghost"><i class="mdi mdi-view-dashboard-outline"></i> Dashboard</a>
                        <a href="<?= base_url('Securityadmin'); ?>" class="btn btn-sm audit-btn-ghost"><i class="mdi mdi-shield-account-outline"></i> Security</a>
                    </div>
                </div>

                <div class="audit-stats">
                    <?php
                    $cards = array(
                        array('Events today', (int)($summary['events_today'] ?? 0), 'mdi-pulse', 'teal', ''),
                        array('Deletions', (int)($summary['deletions'] ?? 0), 'mdi-trash-can-outline', 'rose', '30d'),
                        array('Failed / denied', (int)($summary['failed_or_denied'] ?? 0), 'mdi-shield-alert-outline', 'amber', '30d'),
                        array('Active users', (int)($summary['active_actors'] ?? 0), 'mdi-account-multiple-outline', 'indigo', '30d'),
                    );
                    foreach ($cards as $card):
                    ?>
                    <div class="audit-stat">
                        <span class="audit-stat-icon audit-tone-<?= audit_e($card[3]); ?>"><i class="mdi <?= audit_e($card[2]); ?>"></i></span>
                        <div>
                            <div class="audit-stat-value"><?= number_format($card[1]); ?></div>
                            <div class="audit-stat-label"><?= audit_e($card[0]); ?><?php if ($card[4] !== ''): ?> <span><?= audit_e($card[4]); ?></span><?php endif; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form class="audit-toolbar" method="get" action="<?= $auditUrl; ?>">
                    <div class="audit-toolbar-main">
                        <label class="audit-search mb-0">
                            <i class="mdi mdi-magnify"></i>
                            <input type="search" name="q" maxlength="100" value="<?= audit_e($filters['q'] ?? ''); ?>" class="form-control" placeholder="Search actor, module, record ID, or IP" aria-label="Search audit events">
                        </label>
                        <button type="button" class="btn audit-btn-ghost audit-filter-toggle" data-toggle="collapse" data-target="#auditFilters" aria-expanded="false" aria-controls="auditFilters">
                            <i class="mdi mdi-tune"></i> Filters
                            <?php if ($advancedCount > 0): ?><span class="audit-filter-count"><?= $advancedCount; ?></span><?php endif; ?>
                        </button>
                        <button type="submit" class="btn btn-primary audit-btn-primary">Apply</button>
                    </div>

                    <div class="collapse" id="auditFilters">
                        <div class="audit-filter-grid">
                            <label>
                                <span>Role</span>
                                <select name="role" class="form-control">
                                    <option value="">All roles</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= audit_e($role); ?>" <?= ($filters['role'] ?? '') === $role ? 'selected' : ''; ?>><?= audit_e($role); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Source</span>
                                <select name="source" class="form-control">
                                    <option value="">All sources</option>
                                    <?php foreach ($sourceLabels as $key => $label): ?>
                                        <option value="<?= audit_e($key); ?>" <?= ($filters['source'] ?? '') === $key ? 'selected' : ''; ?>><?= audit_e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Action</span>
                                <select name="action" class="form-control">
                                    <option value="">All actions</option>
                                    <?php foreach ($actionLabels as $key => $label): ?>
                                        <option value="<?= audit_e($key); ?>" <?= ($filters['action'] ?? '') === $key ? 'selected' : ''; ?>><?= audit_e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Result</span>
                                <select name="status" class="form-control">
                                    <option value="">All results</option>
                                    <?php foreach ($statusLabels as $key => $label): ?>
                                        <option value="<?= audit_e($key); ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : ''; ?>><?= audit_e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>From</span>
                                <input type="date" name="from" value="<?= audit_e($filters['from'] ?? ''); ?>" class="form-control">
                            </label>
                            <label>
                                <span>To</span>
                                <input type="date" name="to" value="<?= audit_e($filters['to'] ?? ''); ?>" class="form-control">
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($chips)): ?>
                    <div class="audit-chips">
                        <?php foreach ($chips as $chip): ?>
                            <a class="audit-chip" href="<?= audit_e($chip['href']); ?>" title="Remove this filter">
                                <span><?= audit_e($chip['label']); ?>:</span> <?= audit_e($chip['value']); ?> <i class="mdi mdi-close"></i>
                            </a>
                        <?php endforeach; ?>
                        <a class="audit-chip-clear" href="<?= $auditUrl; ?>">Clear all</a>
                    </div>
                    <?php endif; ?>
                </form>

                <div class="audit-log-card">
                    <div class="audit-log-head">
                        <span><strong><?= number_format((int)$total); ?></strong> event<?= (int)$total === 1 ? '' : 's'; ?></span>
                        <span>Page <?= (int)$page; ?> of <?= max(1, (int)ceil($total / $per_page)); ?> · Newest first</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 audit-table">
                            <thead>
                                <tr>
                                    <th class="audit-col-time">When</th>
                                    <th class="audit-col-actor">Actor</th>
                                    <th>Activity</th>
                                    <th class="audit-col-result">Result</th>
                                    <th class="audit-col-inspect"><span class="sr-only">Inspect</span></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($prepared)): ?>
                                <tr class="audit-empty-row">
                                    <td colspan="5">
                                        <i class="mdi mdi-magnify-close"></i>
                                        <div>No events match these filters.</div>
                                        <?php if (!empty($chips)): ?><a href="<?= $auditUrl; ?>">Clear filters</a><?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($prepared as $p): $event = $p['event']; ?>
                                <tr class="audit-row audit-row-<?= audit_e($p['tone']); ?>" data-sd-key="<?= audit_e($p['tpl']); ?>">
                                    <td>
                                        <div class="audit-time-day"><?= audit_e(audit_day_label($p['timestamp'])); ?></div>
                                        <div class="audit-time-clock"><?= audit_e(date('g:i A', $p['timestamp'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="audit-actor-name" title="<?= audit_e($p['name']); ?>"><?= audit_e($p['name']); ?></div>
                                        <div class="audit-actor-role"><?= audit_e($p['role']); ?></div>
                                    </td>
                                    <td>
                                        <div class="audit-activity">
                                            <span class="audit-pill audit-pill-<?= audit_e($p['tone']); ?>"><?= audit_e($p['action']); ?></span>
                                            <span class="audit-module"><?= audit_e($event['module']); ?></span>
                                        </div>
                                        <?php if (!empty($event['description'])): ?>
                                            <div class="audit-desc" title="<?= audit_e($event['description']); ?>"><?= audit_e($event['description']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="audit-status audit-status-<?= $p['failed'] ? 'failed' : 'ok'; ?>"><i></i><?= $p['failed'] ? 'Failed' : 'Success'; ?></span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" class="sd-open-btn" data-sd-open aria-label="Inspect event" title="Inspect">
                                            <i class="mdi mdi-chevron-right"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($pagination)): ?><div class="d-flex justify-content-center audit-pagination"><?= $pagination; ?></div><?php endif; ?>
            </div>
        </div>
        <?php include('includes/footer_plugins.php'); ?>
        <?php include('includes/footer.php'); ?>
    </div>
</div>

<?php foreach ($prepared as $p): $event = $p['event']; ?>
<template id="<?= audit_e($p['tpl']); ?>">
    <div class="sd-tags">
        <span class="sd-pill sd-tone-<?= audit_e($p['tone']); ?>"><?= audit_e($p['action']); ?></span>
        <span class="sd-status sd-status-<?= $p['failed'] ? 'failed' : 'ok'; ?>"><i></i><?= $p['failed'] ? 'Failed / blocked' : 'Success'; ?></span>
    </div>
    <h5 class="sd-title"><?= audit_e($event['module']); ?></h5>
    <?php if (!empty($event['description'])): ?><p class="sd-desc"><?= audit_e($event['description']); ?></p><?php endif; ?>
    <div class="sd-when"><i class="mdi mdi-clock-outline"></i> <?= audit_e(date('D, M j, Y · g:i:s A', $p['timestamp'])); ?></div>

    <section class="sd-section">
        <h6>Overview</h6>
        <dl class="sd-grid">
            <div>
                <dt>Actor</dt>
                <dd>
                    <?= audit_e($p['name']); ?>
                    <?php if (!empty($event['username']) && $event['username'] !== $p['name']): ?><small><?= audit_e($event['username']); ?></small><?php endif; ?>
                </dd>
            </div>
            <div><dt>Role</dt><dd><?= audit_e($p['role']); ?></dd></div>
            <div><dt>Source</dt><dd><?= audit_e($p['source']); ?></dd></div>
            <?php if (!empty($event['table_name']) || !empty($event['record_pk'])): ?>
            <div>
                <dt>Record</dt>
                <dd>
                    <?php if (!empty($event['record_pk'])): ?>#<?= audit_e($event['record_pk']); ?><?php endif; ?>
                    <?php if (!empty($event['table_name'])): ?><small class="sd-mono"><?= audit_e($event['table_name']); ?></small><?php endif; ?>
                </dd>
            </div>
            <?php endif; ?>
            <?php if (!empty($event['ip_address'])): ?>
            <div><dt>IP address</dt><dd class="sd-mono"><a class="sd-link" href="<?= base_url('Securityadmin/investigate?ip=' . urlencode((string)$event['ip_address'])); ?>" data-ip-summary data-sd-key="<?= audit_e($event['ip_address']); ?>" title="IP summary"><?= audit_e($event['ip_address']); ?></a></dd></div>
            <?php endif; ?>
        </dl>
    </section>

    <?php if ($p['oldMap'] !== null && $p['newMap'] !== null): ?>
    <section class="sd-section">
        <h6>Changes <?php if (!empty($p['changes'])): ?><span class="sd-count"><?= count($p['changes']); ?></span><?php endif; ?></h6>
        <?php if (empty($p['changes'])): ?>
            <p class="sd-empty">No field values changed.</p>
        <?php else: ?>
        <div class="sd-diff">
            <?php foreach ($p['changes'] as $change): ?>
            <div class="sd-diff-row">
                <div class="sd-diff-field"><?= audit_e($change[0]); ?></div>
                <div class="sd-diff-values">
                    <span class="sd-diff-old<?= $change[1] === null ? ' is-empty' : ''; ?>"><?= audit_e($change[1] ?? 'empty'); ?></span>
                    <i class="mdi mdi-arrow-right"></i>
                    <span class="sd-diff-new<?= $change[2] === null ? ' is-empty' : ''; ?>"><?= audit_e($change[2] ?? 'empty'); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($p['unchanged'])): ?><p class="sd-muted-note">Not changed: <?= audit_e(implode(', ', $p['unchanged'])); ?></p><?php endif; ?>
    </section>
    <?php elseif ($p['oldMap'] !== null || $p['newMap'] !== null):
        $isRemoved = $p['oldMap'] !== null;
        $snapshot = $isRemoved ? $p['oldMap'] : $p['newMap'];
    ?>
    <section class="sd-section">
        <h6><?= $isRemoved ? ($p['tone'] === 'danger' ? 'Deleted record' : 'Previous values') : 'Recorded values'; ?></h6>
        <dl class="sd-kv<?= $isRemoved && $p['tone'] === 'danger' ? ' sd-kv-danger' : ''; ?>">
            <?php foreach ($snapshot as $label => $value): ?>
            <div><dt><?= audit_e($label); ?></dt><dd><?= audit_e($value); ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>
    <?php endif; ?>

    <?php if (!empty($p['context'])): ?>
    <section class="sd-section">
        <h6>Context</h6>
        <dl class="sd-kv">
            <?php foreach ($p['context'] as $item): ?>
            <div><dt><?= audit_e($item[0]); ?></dt><dd><?= audit_e($item[1]); ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>
    <?php endif; ?>

    <?php if (!empty($p['device'])): ?>
    <section class="sd-section">
        <h6>Device</h6>
        <dl class="sd-kv">
            <?php foreach ($p['device'] as $item): ?>
            <div><dt><?= audit_e($item[0]); ?></dt><dd><?= audit_e($item[1]); ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <details class="sd-raw">
            <summary>User agent string</summary>
            <pre><?= audit_e($event['user_agent']); ?></pre>
        </details>
    </section>
    <?php endif; ?>

    <?php if ($p['rawOld'] !== '' || $p['rawNew'] !== ''): ?>
    <section class="sd-section">
        <details class="sd-raw">
            <summary>Raw JSON</summary>
            <?php if ($p['rawOld'] !== ''): ?><div class="sd-raw-label">Before</div><pre><?= audit_e($p['rawOld']); ?></pre><?php endif; ?>
            <?php if ($p['rawNew'] !== ''): ?><div class="sd-raw-label">After</div><pre><?= audit_e($p['rawNew']); ?></pre><?php endif; ?>
        </details>
    </section>
    <?php endif; ?>

    <div class="sd-meta-foot">Event <span class="sd-mono"><?= audit_e($event['event_key']); ?></span></div>
</template>
<?php endforeach; ?>


<?php include('includes/themecustomizer.php'); ?>
<style>
    .audit-page {
        --a-text: #273142;
        --a-muted: #8792a4;
        --a-border: #e8ecf1;
        --a-soft: #f6f8fb;
        --a-accent: #38aeb7;
        color: var(--a-text);
        padding-bottom: 1.5rem;
    }

    .audit-page .btn:focus { box-shadow: 0 0 0 3px rgba(56, 174, 183, .2); }

    /* Header */
    .audit-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: 1.25rem 0 1rem;
    }
    .audit-header-title { display: flex; align-items: center; gap: .75rem; }
    .audit-header-title h4 { display: flex; align-items: center; gap: .5rem; font-weight: 700; font-size: 1.15rem; }
    .audit-header-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 10px;
        background: rgba(56, 174, 183, .12); color: var(--a-accent); font-size: 20px;
    }
    .audit-header-sub { color: var(--a-muted); font-size: .8rem; }
    .audit-readonly {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .1rem .45rem; border-radius: 999px;
        background: var(--a-soft); border: 1px solid var(--a-border);
        color: var(--a-muted); font-size: .66rem; font-weight: 600; cursor: help;
    }
    .audit-header-actions { display: flex; gap: .4rem; }

    .audit-btn-ghost {
        display: inline-flex; align-items: center; gap: .3rem;
        border: 1px solid var(--a-border); border-radius: 8px;
        background: #fff; color: #4a5568; font-weight: 500;
    }
    .audit-btn-ghost:hover { background: var(--a-soft); color: var(--a-text); }
    .audit-btn-primary { border-radius: 8px; padding-left: 1.1rem; padding-right: 1.1rem; font-weight: 600; }

    /* Stats */
    .audit-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 1rem;
        border: 1px solid var(--a-border);
        border-radius: 12px;
        background: #fff;
    }
    .audit-stat { display: flex; align-items: center; gap: .7rem; padding: .85rem 1rem; }
    .audit-stat + .audit-stat { border-left: 1px solid var(--a-border); }
    .audit-stat-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; min-width: 34px; border-radius: 9px; font-size: 18px;
    }
    .audit-stat-value { font-size: 1.2rem; font-weight: 700; line-height: 1.1; }
    .audit-stat-label { color: var(--a-muted); font-size: .74rem; }
    .audit-stat-label span {
        margin-left: .15rem; padding: 0 .3rem; border-radius: 4px;
        background: var(--a-soft); font-size: .64rem; font-weight: 600;
    }

    .audit-tone-teal   { background: rgba(56, 174, 183, .13); color: #23939c; }
    .audit-tone-rose   { background: rgba(226, 76, 94, .12);  color: #cc3a4d; }
    .audit-tone-amber  { background: rgba(240, 168, 40, .15); color: #b67a10; }
    .audit-tone-indigo { background: rgba(92, 106, 214, .12); color: #4f5bc4; }

    /* Toolbar */
    .audit-toolbar {
        margin-bottom: 1rem;
        padding: .6rem;
        border: 1px solid var(--a-border);
        border-radius: 12px;
        background: #fff;
    }
    .audit-toolbar-main { display: flex; gap: .5rem; }
    .audit-toolbar .form-control {
        height: 38px; border-color: var(--a-border); border-radius: 8px; font-size: .85rem; box-shadow: none;
    }
    .audit-toolbar .form-control:focus { border-color: var(--a-accent); box-shadow: 0 0 0 3px rgba(56, 174, 183, .12); }
    .audit-search { position: relative; flex: 1; min-width: 0; }
    .audit-search i { position: absolute; top: 9px; left: 11px; color: var(--a-muted); font-size: 18px; pointer-events: none; }
    .audit-search .form-control { padding-left: 36px; background: var(--a-soft); border-color: transparent; }
    .audit-search .form-control:focus { background: #fff; }
    .audit-filter-toggle { height: 38px; }
    .audit-filter-toggle[aria-expanded="true"] { background: var(--a-soft); border-color: #d5dbe3; }
    .audit-filter-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
        background: var(--a-accent); color: #fff; font-size: .66rem; font-weight: 700;
    }
    .audit-filter-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .6rem;
        padding: .75rem .1rem .15rem;
    }
    .audit-filter-grid label { margin: 0; }
    .audit-filter-grid label > span { display: block; margin-bottom: .25rem; color: var(--a-muted); font-size: .7rem; font-weight: 600; }

    .audit-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; padding: .6rem .1rem 0; }
    .audit-chip {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .2rem .35rem .2rem .6rem; border-radius: 999px;
        background: rgba(56, 174, 183, .1); color: #1f7f87; font-size: .74rem; font-weight: 600;
    }
    .audit-chip span { font-weight: 500; opacity: .75; }
    .audit-chip i { font-size: 13px; opacity: .7; }
    .audit-chip:hover { background: rgba(56, 174, 183, .18); color: #16666d; text-decoration: none; }
    .audit-chip-clear { margin-left: .2rem; color: var(--a-muted); font-size: .74rem; }

    /* Table */
    .audit-log-card { overflow: hidden; border: 1px solid var(--a-border); border-radius: 12px; background: #fff; }
    .audit-log-head {
        display: flex; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
        padding: .7rem 1rem; border-bottom: 1px solid var(--a-border);
        color: var(--a-muted); font-size: .78rem;
    }
    .audit-log-head strong { color: var(--a-text); }
    .audit-table { min-width: 680px; }
    .audit-table thead th {
        padding: .55rem 1rem; border: 0; border-bottom: 1px solid var(--a-border);
        background: transparent; color: var(--a-muted);
        font-size: .68rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
    }
    .audit-table tbody td { padding: .7rem 1rem; border-top: 1px solid #f0f2f5; vertical-align: middle; }
    .audit-table tbody tr:first-child td { border-top: 0; }
    .audit-col-time { width: 110px; }
    .audit-col-actor { width: 230px; }
    .audit-col-result { width: 110px; }
    .audit-col-inspect { width: 56px; }

    .audit-row { cursor: pointer; transition: background-color .12s ease; }
    .audit-row:hover { background: #fafbfd; }
    .audit-row td:first-child { box-shadow: inset 3px 0 0 transparent; }
    .audit-row-danger td:first-child { box-shadow: inset 3px 0 0 #ec8a96; }
    .audit-row-warning td:first-child { box-shadow: inset 3px 0 0 #f3c46b; }

    .audit-time-day { font-size: .8rem; font-weight: 600; white-space: nowrap; }
    .audit-time-clock { color: var(--a-muted); font-size: .74rem; white-space: nowrap; }

    .audit-actor-name { max-width: 210px; overflow: hidden; font-size: .84rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .audit-actor-role { color: var(--a-muted); font-size: .72rem; }

    .audit-activity { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .audit-module { font-size: .84rem; font-weight: 600; white-space: nowrap; }
    .audit-desc {
        max-width: 420px; margin-top: .15rem; overflow: hidden;
        color: var(--a-muted); font-size: .76rem; text-overflow: ellipsis; white-space: nowrap;
    }

    .audit-pill {
        display: inline-flex; align-items: center;
        padding: .12rem .5rem; border-radius: 6px;
        font-size: .68rem; font-weight: 600; white-space: nowrap;
    }
    .audit-pill-danger  { background: #fdecee; color: #c42f41; }
    .audit-pill-warning { background: #fff3dd; color: #a86e0c; }
    .audit-pill-success { background: #e5f6ec; color: #17804a; }
    .audit-pill-info    { background: #e8f0fc; color: #2c68c6; }
    .audit-pill-neutral { background: #eef1f5; color: #566174; }

    .audit-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .76rem; font-weight: 500; white-space: nowrap; }
    .audit-status i { width: 7px; height: 7px; border-radius: 50%; }
    .audit-status-ok { color: #3f7d5a; }
    .audit-status-ok i { background: #2fb36b; box-shadow: 0 0 0 3px rgba(47, 179, 107, .15); }
    .audit-status-failed { color: #b64250; }
    .audit-status-failed i { background: #e24c5e; box-shadow: 0 0 0 3px rgba(226, 76, 94, .15); }

    .audit-row.sd-row-active > td { background: rgba(56, 174, 183, .08) !important; }
    .audit-row:hover .sd-open-btn,
    .audit-row.sd-row-active .sd-open-btn { border-color: var(--a-accent); background: var(--a-accent); color: #fff; }

    .audit-empty-row td { padding: 3rem 1rem !important; color: var(--a-muted); text-align: center; cursor: default; }
    .audit-empty-row i { display: block; margin-bottom: .4rem; font-size: 34px; opacity: .6; }
    .audit-empty-row a { display: inline-block; margin-top: .4rem; font-size: .8rem; }

    .audit-pagination .pagination { margin-bottom: 0; }
    .audit-mono { font-family: SFMono-Regular, Menlo, Consolas, monospace; }

    @media (max-width: 1199.98px) {
        .audit-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .audit-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .audit-stat:nth-child(3) { border-left: 0; }
        .audit-stat:nth-child(n+3) { border-top: 1px solid var(--a-border); }
        .audit-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .audit-toolbar-main { flex-wrap: wrap; }
        .audit-search { flex-basis: 100%; }
        .audit-filter-toggle, .audit-btn-primary { flex: 1; justify-content: center; }
    }
</style>
<?php include('includes/side_drawer.php'); ?>
<script src="<?= base_url('assets/js/security-panels.js?v=2026092801'); ?>"></script>
<script>
(function () {
    if (!window.SideDrawer) return;
    if (window.SecurityPanels) {
        SecurityPanels.ipPanel({
            url: <?= json_encode(base_url('Securityadmin/ip_summary')); ?>,
            investigateUrl: <?= json_encode(base_url('Securityadmin/investigate')); ?>
        });
    }
    SideDrawer.create({
        label: 'Event details',
        nav: true,
        accent: '#38aeb7',
        rowSelector: '.audit-table tr[data-sd-key]',
        render: function (key, panel) {
            var tpl = document.getElementById(key);
            if (tpl) panel.body(document.importNode(tpl.content, true));
        }
    });
})();
</script>
</body>
</html>

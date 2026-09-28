<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AuditLogModel extends CI_Model
{
    private $trackedRoles = array('Admin', 'Committee', 'Cashier', 'Auditor', 'Student');

    public function write($action, $module = null, $table = null, $recordPk = null, $old = null, $new = null, $succeeded = 1, $description = null, $extra = null)
    {
        // Pull what we can from session
        $username = (string) $this->session->userdata('username');
        $actorLevel = (string) $this->session->userdata('level');

        // Bearer-token mobile requests do not have a CI login session. Their
        // controllers may supply reserved actor metadata in $extra so those
        // events are still attributable in the central trail.
        if (is_array($extra)) {
            if ($username === '' && !empty($extra['_actor_username'])) {
                $username = trim((string)$extra['_actor_username']);
            }
            if ($actorLevel === '' && !empty($extra['_actor_level'])) {
                $actorLevel = trim((string)$extra['_actor_level']);
            }
            unset($extra['_actor_username'], $extra['_actor_level']);
        }
        $fname    = (string) $this->session->userdata('fname');
        $mname    = (string) $this->session->userdata('mname');
        $lname    = (string) $this->session->userdata('lname');
        $fullName = trim($lname . ', ' . $fname . ($mname ? (' ' . $mname) : ''));
        if ($fullName === ',' || $fullName === '') $fullName = null;

        $payload = [
            'action'     => $action,
            'module'     => $module,
            'table_name' => $table,
            'record_pk'  => is_scalar($recordPk) ? (string)$recordPk : null,
            'succeeded'  => (int)!!$succeeded,

            'username'   => $username ?: null,
            'full_name'  => $fullName,
            'user_id'    => (string) $this->session->userdata('IDNumber') ?: null,
            'actor_level'=> $actorLevel ?: null,

            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string) $this->input->user_agent(), 0, 255),

            'description' => $description,
            'event_time' => date('Y-m-d H:i:s')
        ];

        // JSON fields (accept arrays/objects or JSON strings)
        $jsonify = function ($val) {
            if ($val === null || $val === '') return null;
            if (is_string($val)) {
                // if looks like JSON, store as-is; else wrap into {"_": "..."} for safety
                $trim = ltrim($val);
                if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) return $val;
                return json_encode(['_' => $val], JSON_UNESCAPED_UNICODE);
            }
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        };

        $payload['old_values'] = $jsonify($old);
        $payload['new_values'] = $jsonify($new);
        $payload['extra']      = $jsonify($extra);

        return $this->db->insert('audit_logs', $payload);
    }

    /** Roles explicitly monitored by the Super Admin audit screen. */
    public function trackedRoles()
    {
        return $this->trackedRoles;
    }

    /**
     * A read-only, normalized view over the application's four audit sources.
     * Column-existence checks keep the page usable if a production database
     * account cannot apply the additive role-snapshot migration immediately.
     */
    private function unifiedBaseSql()
    {
        $parts = array();

        if ($this->db->table_exists('audit_logs')) {
            $role = $this->db->field_exists('actor_level', 'audit_logs')
                ? 'a.actor_level' : 'NULL';
            $parts[] = "
                SELECT CONVERT('activity' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source,
                       CONVERT(CONCAT('activity-', a.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS event_key,
                       a.event_time,
                       CONVERT(LOWER(COALESCE(a.action, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
                       CONVERT(COALESCE(NULLIF(a.module, ''), 'Application') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS module,
                       CONVERT(a.table_name USING utf8mb4) COLLATE utf8mb4_unicode_ci AS table_name,
                       CONVERT(a.record_pk USING utf8mb4) COLLATE utf8mb4_unicode_ci AS record_pk,
                       a.succeeded,
                       CONVERT(a.username USING utf8mb4) COLLATE utf8mb4_unicode_ci AS username,
                       CONVERT(COALESCE(NULLIF(a.full_name, ''),
                                NULLIF(TRIM(CONCAT_WS(' ', u.fName, u.mName, u.lName)), '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS full_name,
                       CONVERT(COALESCE(NULLIF({$role}, ''), NULLIF(u.position, ''), 'Unknown') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS actor_level,
                       CONVERT(a.ip_address USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ip_address,
                       CONVERT(a.user_agent USING utf8mb4) COLLATE utf8mb4_unicode_ci AS user_agent,
                       CONVERT(a.description USING utf8mb4) COLLATE utf8mb4_unicode_ci AS description,
                       CONVERT(a.old_values USING utf8mb4) COLLATE utf8mb4_unicode_ci AS old_values,
                       CONVERT(a.new_values USING utf8mb4) COLLATE utf8mb4_unicode_ci AS new_values,
                       CONVERT(a.extra USING utf8mb4) COLLATE utf8mb4_unicode_ci AS extra
                  FROM audit_logs a
             LEFT JOIN o_users u ON u.username = CONVERT(a.username USING latin1)";
        }

        if ($this->db->table_exists('security_audit_logs')) {
            $parts[] = "
                SELECT CONVERT('security' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source,
                       CONVERT(CONCAT('security-', s.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS event_key,
                       s.event_time,
                       CONVERT(LOWER(COALESCE(s.event_type, 'security')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
                       CONVERT(COALESCE(NULLIF(s.module, ''), 'Security') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS module,
                       CONVERT(s.table_name USING utf8mb4) COLLATE utf8mb4_unicode_ci AS table_name,
                       CONVERT(s.record_pk USING utf8mb4) COLLATE utf8mb4_unicode_ci AS record_pk,
                       CASE
                         WHEN LOWER(COALESCE(s.event_status, '')) IN ('failed','blocked','denied','error','revoked-device')
                              OR UPPER(COALESCE(s.event_type, '')) REGEXP '(FAILED|BLOCKED|DENIED)'
                         THEN 0 ELSE 1
                       END AS succeeded,
                       CONVERT(COALESCE(NULLIF(s.actor_username, ''), NULLIF(s.target_username, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS username,
                       CONVERT(COALESCE(NULLIF(s.actor_full_name, ''),
                                NULLIF(TRIM(CONCAT_WS(' ', au.fName, au.mName, au.lName)), ''),
                                NULLIF(TRIM(CONCAT_WS(' ', tu.fName, tu.mName, tu.lName)), '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS full_name,
                       CONVERT(COALESCE(NULLIF(s.actor_level, ''), NULLIF(au.position, ''),
                                NULLIF(tu.position, ''), 'Unknown') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS actor_level,
                       CONVERT(s.ip_address USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ip_address,
                       CONVERT(s.raw_user_agent USING utf8mb4) COLLATE utf8mb4_unicode_ci AS user_agent,
                       CONVERT(s.description USING utf8mb4) COLLATE utf8mb4_unicode_ci AS description,
                       CONVERT(CASE WHEN s.old_value IS NULL THEN NULL
                            ELSE CONCAT('{\"', COALESCE(NULLIF(s.changed_field, ''), 'value'),
                                        '\":', JSON_QUOTE(s.old_value), '}') END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS old_values,
                       CONVERT(CASE WHEN s.new_value IS NULL THEN NULL
                            ELSE CONCAT('{\"', COALESCE(NULLIF(s.changed_field, ''), 'value'),
                                        '\":', JSON_QUOTE(s.new_value), '}') END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS new_values,
                       CONVERT(s.extra USING utf8mb4) COLLATE utf8mb4_unicode_ci AS extra
                  FROM security_audit_logs s
             LEFT JOIN o_users au ON au.username = CONVERT(s.actor_username USING latin1)
             LEFT JOIN o_users tu ON tu.username = CONVERT(s.target_username USING latin1)";
        }

        if ($this->db->table_exists('login_logs')) {
            $role = $this->db->field_exists('actor_level', 'login_logs')
                ? 'l.actor_level' : 'NULL';
            $parts[] = "
                SELECT CONVERT('login' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source,
                       CONVERT(CONCAT('login-', l.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS event_key,
                       l.login_time AS event_time,
                       CONVERT(CASE WHEN LOWER(COALESCE(l.status, '')) = 'logout' THEN 'logout' ELSE 'login' END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
                       CONVERT('Login' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS module,
                       CONVERT('login_logs' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS table_name,
                       CONVERT(CAST(l.id AS CHAR) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS record_pk,
                       CASE WHEN LOWER(COALESCE(l.status, '')) IN ('success','logout') THEN 1 ELSE 0 END AS succeeded,
                       CONVERT(l.username USING utf8mb4) COLLATE utf8mb4_unicode_ci AS username,
                       CONVERT(NULLIF(TRIM(CONCAT_WS(' ', u.fName, u.mName, u.lName)), '') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS full_name,
                       CONVERT(COALESCE(NULLIF({$role}, ''), NULLIF(u.position, ''), 'Unknown') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS actor_level,
                       CONVERT(l.ip_address USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ip_address,
                       CONVERT(l.user_agent USING utf8mb4) COLLATE utf8mb4_unicode_ci AS user_agent,
                       CONVERT(CASE LOWER(COALESCE(l.status, ''))
                         WHEN 'success' THEN 'Successful sign-in'
                         WHEN 'logout' THEN 'User signed out'
                         ELSE 'Failed sign-in attempt'
                       END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS description,
                       CONVERT(NULL USING utf8mb4) COLLATE utf8mb4_unicode_ci AS old_values,
                       CONVERT(NULL USING utf8mb4) COLLATE utf8mb4_unicode_ci AS new_values,
                       CONVERT(CASE WHEN NULLIF(l.referrer, '') IS NULL THEN NULL
                            ELSE CONCAT('{\"referrer\":', JSON_QUOTE(l.referrer), '}') END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS extra
                  FROM login_logs l
             LEFT JOIN o_users u ON u.username = CONVERT(l.username USING latin1)";
        }

        if ($this->db->table_exists('payment_audit_log')) {
            $role = $this->db->field_exists('actor_level', 'payment_audit_log')
                ? 'p.actor_level' : 'NULL';
            $parts[] = "
                SELECT CONVERT('payment' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS source,
                       CONVERT(CONCAT('payment-', p.id) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS event_key,
                       p.changed_at AS event_time,
                       CONVERT(LOWER(COALESCE(p.action, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
                       CONVERT('Accounting' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS module,
                       CONVERT('paymentsaccounts' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS table_name,
                       CONVERT(CAST(p.payment_id AS CHAR) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS record_pk,
                       1 AS succeeded,
                       CONVERT(p.changed_by USING utf8mb4) COLLATE utf8mb4_unicode_ci AS username,
                       CONVERT(NULLIF(TRIM(CONCAT_WS(' ', u.fName, u.mName, u.lName)), '') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS full_name,
                       CONVERT(COALESCE(NULLIF({$role}, ''), NULLIF(u.position, ''), 'Unknown') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS actor_level,
                       CONVERT(NULL USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ip_address,
                       CONVERT(NULL USING utf8mb4) COLLATE utf8mb4_unicode_ci AS user_agent,
                       CONVERT(CONCAT(UPPER(LEFT(p.action, 1)), SUBSTRING(p.action, 2),
                              ' payment ', NULLIF(p.or_number, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS description,
                       CONVERT(p.old_values USING utf8mb4) COLLATE utf8mb4_unicode_ci AS old_values,
                       CONVERT(p.new_values USING utf8mb4) COLLATE utf8mb4_unicode_ci AS new_values,
                       CONVERT(CONCAT('{\"or_number\":', JSON_QUOTE(p.or_number),
                              ',\"student_number\":', JSON_QUOTE(p.student_number),
                              ',\"amount\":', JSON_QUOTE(CAST(p.amount AS CHAR)), '}') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS extra
                  FROM payment_audit_log p
             LEFT JOIN o_users u ON u.username = CONVERT(p.changed_by USING latin1)";
        }

        if (!$parts) {
            return "SELECT 'none' source, '' event_key, NULL event_time, '' action,
                           '' module, NULL table_name, NULL record_pk, 0 succeeded,
                           NULL username, NULL full_name, 'Unknown' actor_level,
                           NULL ip_address, NULL user_agent, NULL description,
                           NULL old_values, NULL new_values, NULL extra WHERE 1=0";
        }

        return implode("\nUNION ALL\n", $parts);
    }

    /** Build safe filters for the outer normalized event query. */
    private function unifiedWhere(array $filters, array &$binds)
    {
        $clauses = array();
        $role = isset($filters['role']) ? trim((string)$filters['role']) : '';
        if ($role !== '' && in_array($role, $this->trackedRoles, true)) {
            $clauses[] = 'events.actor_level = ?';
            $binds[] = $role;
        } else {
            $marks = implode(',', array_fill(0, count($this->trackedRoles), '?'));
            $clauses[] = "events.actor_level IN ({$marks})";
            foreach ($this->trackedRoles as $trackedRole) $binds[] = $trackedRole;
        }

        $sources = array('activity', 'security', 'login', 'payment');
        $source = isset($filters['source']) ? strtolower(trim((string)$filters['source'])) : '';
        if (in_array($source, $sources, true)) {
            $clauses[] = 'events.source = ?';
            $binds[] = $source;
        }

        $status = isset($filters['status']) ? strtolower(trim((string)$filters['status'])) : '';
        if ($status === 'success') $clauses[] = 'events.succeeded = 1';
        if ($status === 'failed') $clauses[] = 'events.succeeded = 0';

        $action = isset($filters['action']) ? strtolower(trim((string)$filters['action'])) : '';
        if ($action === 'delete') {
            $clauses[] = "events.action LIKE '%delete%'";
        } elseif ($action === 'update') {
            $clauses[] = "(events.action LIKE '%update%' OR events.action = 'edit')";
        } elseif ($action === 'create') {
            $clauses[] = "events.action LIKE '%create%'";
        } elseif ($action === 'login') {
            $clauses[] = "events.action IN ('login','logout')";
        } elseif ($action === 'denied') {
            $clauses[] = "(events.succeeded = 0 OR events.action LIKE '%denied%' OR events.action LIKE '%blocked%' OR events.action LIKE '%failed%')";
        }

        $from = isset($filters['from']) ? trim((string)$filters['from']) : '';
        if ($this->validDate($from)) {
            $clauses[] = 'events.event_time >= ?';
            $binds[] = $from . ' 00:00:00';
        }
        $to = isset($filters['to']) ? trim((string)$filters['to']) : '';
        if ($this->validDate($to)) {
            $clauses[] = 'events.event_time <= ?';
            $binds[] = $to . ' 23:59:59';
        }

        $q = isset($filters['q']) ? mb_substr(trim((string)$filters['q']), 0, 100) : '';
        if ($q !== '') {
            $like = '%' . $this->db->escape_like_str($q) . '%';
            $clauses[] = "(events.username LIKE ? ESCAPE '!' OR events.full_name LIKE ? ESCAPE '!'
                         OR events.description LIKE ? ESCAPE '!' OR events.module LIKE ? ESCAPE '!'
                         OR events.table_name LIKE ? ESCAPE '!' OR events.record_pk LIKE ? ESCAPE '!'
                         OR events.ip_address LIKE ? ESCAPE '!')";
            for ($i = 0; $i < 7; $i++) $binds[] = $like;
        }

        return $clauses ? (' WHERE ' . implode(' AND ', $clauses)) : '';
    }

    private function validDate($value)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) return false;
        list($year, $month, $day) = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year);
    }

    public function countUnified(array $filters = array())
    {
        $binds = array();
        $where = $this->unifiedWhere($filters, $binds);
        $sql = 'SELECT COUNT(*) AS total FROM (' . $this->unifiedBaseSql() . ') events' . $where;
        $row = $this->db->query($sql, $binds)->row();
        return $row ? (int)$row->total : 0;
    }

    public function getUnified(array $filters = array(), $limit = 25, $offset = 0)
    {
        $binds = array();
        $where = $this->unifiedWhere($filters, $binds);
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = 'SELECT * FROM (' . $this->unifiedBaseSql() . ') events'
             . $where . ' ORDER BY events.event_time DESC, events.event_key DESC LIMIT '
             . $limit . ' OFFSET ' . $offset;
        return $this->db->query($sql, $binds)->result_array();
    }

    public function auditSummary($days = 30)
    {
        $days = max(1, min(365, (int)$days));
        $binds = array();
        $where = $this->unifiedWhere(array(), $binds);
        $where .= ($where ? ' AND ' : ' WHERE ') . 'events.event_time >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
        $sql = "SELECT
                    SUM(events.event_time >= CURDATE()) AS events_today,
                    SUM(events.action LIKE '%delete%') AS deletions,
                    SUM(events.succeeded = 0) AS failed_or_denied,
                    COUNT(DISTINCT NULLIF(events.username, '')) AS active_actors
                  FROM (" . $this->unifiedBaseSql() . ') events' . $where;
        $row = $this->db->query($sql, $binds)->row_array();
        return array(
            'events_today'     => (int)($row['events_today'] ?? 0),
            'deletions'        => (int)($row['deletions'] ?? 0),
            'failed_or_denied' => (int)($row['failed_or_denied'] ?? 0),
            'active_actors'    => (int)($row['active_actors'] ?? 0),
        );
    }
}

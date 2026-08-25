<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/MobileApi.php';

/**
 * Mobile misc API: announcements, notes, todos, personnel.
 *
 * These are the cross-role modules — every authenticated user gets
 * announcements, their own notes, and their own todos. Personnel is a
 * public directory. Masterlist/reports/accounting are admin read-only
 * summaries exposed here as list endpoints; the heavy web views stay on
 * the web for now.
 *
 * Endpoints (see application/config/routes.php):
 *   GET  api/mobile/announcements
 *   GET  api/mobile/notes
 *   POST api/mobile/notes
 *   PUT  api/mobile/notes/(:num)
 *   DELETE api/mobile/notes/(:num)
 *   GET  api/mobile/todos
 *   POST api/mobile/todos
 *   PUT  api/mobile/todos/(:num)/toggle
 *   DELETE api/mobile/todos/(:num)
 *   GET  api/mobile/personnel
 *   GET  api/mobile/masterlist/enrolled
 *   GET  api/mobile/accounting/expenses
 */
class MobileMisc extends MobileApi
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('AnnouncementModel');
        $this->load->model('NoteModel');
        $this->load->model('ToDoModel');
    }

    // ─── Announcements ─────────────────────────────────────────────────────

    /** Active announcements for the current user's audience + 'All'. */
    public function announcements()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $position = $this->position_of((string)$tokenRow['username']);
        $audience = $this->audience_for($position);
        $list = $this->AnnouncementModel->getActiveAnnouncementsForMany(['All', $audience]);

        $out = [];
        foreach ($list as $a) {
            $out[] = [
                'id'           => (int)$a->aID,
                'title'        => (string)$a->title,
                'message'      => (string)$a->message,
                'author'       => (string)$a->author,
                'audience'     => (string)$a->audience,
                'date_posted'  => (string)$a->datePosted,
                'date_expire'  => (string)($a->date_expire ?? ''),
                'image_url'    => $this->file_url((string)($a->image ?? '')),
            ];
        }

        return $this->json(['ok' => true, 'announcements' => $out]);
    }

    // ─── Notes ─────────────────────────────────────────────────────────────

    /** List the current user's notes. */
    public function notes()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $rows = $this->NoteModel->get_notes_by_user($username);

        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->note_shape($r);
        }
        return $this->json(['ok' => true, 'notes' => $out]);
    }

    /** Create a note. */
    public function notes_create()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $payload = $this->read_payload();
        $title = trim((string)($payload['title'] ?? ''));
        $content = trim((string)($payload['content'] ?? ''));
        if ($title === '' || $content === '') {
            $body = json_encode(['ok' => false, 'message' => 'Title and content are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $now = date('Y-m-d H:i:s');
        $username = (string)$tokenRow['username'];
        $this->NoteModel->insert_note([
            'user_id'    => $username,
            'title'      => $title,
            'content'    => $content,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        // notes.id has no auto_increment — fetch the row we just inserted.
        $row = $this->db->where('user_id', $username)
            ->where('title', $title)
            ->where('created_at', $now)
            ->order_by('id', 'DESC')
            ->limit(1)->get('notes')->row();
        $id = $row ? (int)$row->id : 0;

        $body = json_encode([
            'ok' => true,
            'note' => [
                'id'         => $id,
                'title'      => $title,
                'content'    => $content,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Update a note. */
    public function notes_update($id)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $payload = $this->read_payload();
        $username = (string)$tokenRow['username'];
        $data = [];
        if (isset($payload['title']))   $data['title']   = trim((string)$payload['title']);
        if (isset($payload['content'])) $data['content'] = trim((string)$payload['content']);
        if (empty($data)) {
            $body = json_encode(['ok' => false, 'message' => 'Nothing to update.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->NoteModel->update_note((int)$id, $data, $username);
        $body = json_encode(['ok' => true, 'message' => 'Note updated.']);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Delete a note. */
    public function notes_delete($id)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];
        $ok = $this->NoteModel->delete_note((int)$id, $username);
        $body = json_encode(['ok' => (bool)$ok, 'message' => $ok ? 'Note deleted.' : 'Note not found.']);
        $this->record_idempotent_response($ok ? 200 : 404, $body);
        return $this->json(json_decode($body, true), $ok ? 200 : 404);
    }

    // ─── Todos ─────────────────────────────────────────────────────────────

    /** List the current user's todos. */
    public function todos()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $username = (string)$tokenRow['username'];
        $rows = $this->ToDoModel->get_all($username);

        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->todo_shape($r);
        }
        return $this->json(['ok' => true, 'todos' => $out]);
    }

    /** Create a todo. */
    public function todos_create()
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $payload = $this->read_payload();
        $task = trim((string)($payload['task'] ?? ''));
        $dueDate = trim((string)($payload['due_date'] ?? ''));
        if ($task === '' || $dueDate === '') {
            $body = json_encode(['ok' => false, 'message' => 'Task and due date are required.']);
            $this->record_idempotent_response(422, $body);
            return $this->json(json_decode($body, true), 422);
        }

        $username = (string)$tokenRow['username'];
        $now = date('Y-m-d H:i:s');
        $this->ToDoModel->add($task, $username, $dueDate);
        // todos.id has no auto_increment — fetch the row we just inserted.
        $row = $this->db->where('username', $username)
            ->where('task', $task)
            ->where('created_at', $now)
            ->order_by('id', 'DESC')
            ->limit(1)->get('todos')->row();
        $id = $row ? (int)$row->id : 0;

        $body = json_encode([
            'ok' => true,
            'todo' => [
                'id'         => $id,
                'task'       => $task,
                'due_date'   => date('Y-m-d', strtotime($dueDate)),
                'is_done'    => false,
                'created_at' => $now,
                'completed_at' => null,
                'comment'     => '',
            ],
        ]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Toggle a todo's done state. */
    public function todos_toggle($id)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];
        $payload = $this->read_payload();
        $done = !empty($payload['done']);

        if ($done) {
            $this->ToDoModel->mark_task_done((int)$id, date('Y-m-d H:i:s'));
        } else {
            $this->ToDoModel->mark_task_undone((int)$id);
        }

        $body = json_encode(['ok' => true, 'done' => $done]);
        $this->record_idempotent_response(200, $body);
        return $this->json(json_decode($body, true), 200);
    }

    /** Delete a todo. */
    public function todos_delete($id)
    {
        if ($this->input->method(true) !== 'POST') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;
        if ($this->replay_if_duplicate()) return;

        $username = (string)$tokenRow['username'];
        $ok = $this->ToDoModel->delete((int)$id, $username);
        $body = json_encode(['ok' => (bool)$ok, 'message' => $ok ? 'Todo deleted.' : 'Todo not found.']);
        $this->record_idempotent_response($ok ? 200 : 404, $body);
        return $this->json(json_decode($body, true), $ok ? 200 : 404);
    }

    // ─── Personnel directory ───────────────────────────────────────────────

    /** Active personnel, ordered by sort_order then name. */
    public function personnel()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        $rows = $this->db->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('full_name', 'ASC')
            ->get('fbmso_personnels')->result();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'         => (int)$r->id,
                'full_name'  => (string)$r->full_name,
                'title'      => (string)$r->title,
                'bio'        => (string)($r->bio ?? ''),
                'photo_url'  => $this->file_url((string)($r->photo ?? '')),
                'sort_order' => (int)$r->sort_order,
            ];
        }
        return $this->json(['ok' => true, 'personnel' => $out]);
    }

    // ─── Masterlist (admin read-only) ──────────────────────────────────────

    /** Enrolled students list (admin/registrar). Read-only summary. */
    public function masterlist_enrolled()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }

        $limit = (int)$this->input->get('limit', true) ?: 200;
        $offset = (int)$this->input->get('offset', true) ?: 0;
        $course = trim((string)$this->input->get('course', true));

        $this->db->from('studentsignup');
        if ($course !== '') {
            $this->db->group_start()
                ->where('Course1', $course)
                ->or_where('Course2', $course)
                ->or_where('Course3', $course)
                ->group_end();
        }
        $total = $this->db->count_all_results('', false);
        $this->db->select('StudentNumber, FirstName, MiddleName, LastName, Course1, Course2, Course3, Major1, Status, EnrollmentDate')
            ->order_by('LastName', 'ASC')
            ->order_by('FirstName', 'ASC')
            ->limit($limit, $offset);
        $rows = $this->db->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'student_number'  => (string)($r->StudentNumber ?? ''),
                'first_name'      => trim((string)($r->FirstName ?? '')),
                'middle_name'     => trim((string)($r->MiddleName ?? '')),
                'last_name'       => trim((string)($r->LastName ?? '')),
                'full_name'       => trim(($r->LastName ?? '') . ', ' . ($r->FirstName ?? '')),
                'course'          => (string)($r->Course1 ?? $r->Course2 ?? $r->Course3 ?? ''),
                'major'           => (string)($r->Major1 ?? ''),
                'status'          => (string)($r->Status ?? ''),
                'enrollment_date' => (string)($r->EnrollmentDate ?? ''),
            ];
        }

        return $this->json([
            'ok' => true,
            'total' => (int)$total,
            'rows' => $out,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    // ─── Accounting (admin read-only) ──────────────────────────────────────

    /** Recent expenses (admin/accounting). Read-only summary. */
    public function accounting_expenses()
    {
        if ($this->input->method(true) !== 'GET') {
            return $this->json(['ok' => false, 'message' => 'Method not allowed.'], 405);
        }
        $tokenRow = $this->require_token();
        if ($tokenRow === null) return;

        if (!$this->is_staff($tokenRow)) {
            return $this->json(['ok' => false, 'message' => 'Staff only.'], 403);
        }

        if (!$this->db->table_exists('expenses')) {
            return $this->json(['ok' => true, 'expenses' => [], 'total' => 0]);
        }

        $limit = (int)$this->input->get('limit', true) ?: 50;
        $rows = $this->db->order_by('expensesid', 'DESC')
            ->limit($limit)
            ->get('expenses')->result();

        $out = [];
        foreach ($rows as $r) {
            $out[] = (array)$r;
        }
        return $this->json(['ok' => true, 'expenses' => $out, 'count' => count($out)]);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function audience_for(string $position): string
    {
        $val = strtolower(trim($position));
        if ($val === 'student' || $val === 'students' || strpos($val, 'stude') === 0) return 'Students';
        if ($val === 'registrar' || $val === 'head registrar') return 'Registrar';
        if (strpos($val, 'instructor') !== false || strpos($val, 'teacher') !== false) return 'Instructors';
        return 'All';
    }

    private function is_staff(array $tokenRow): bool
    {
        $pos = strtolower(trim($this->position_of((string)$tokenRow['username'])));
        if (in_array($pos, ['admin', 'super admin', 'school admin', 'registrar', 'head registrar', 'accounting', 'hr admin', 'human resource', 'academic officer', 'encoder', 'it'], true)) {
            return true;
        }
        return false;
    }

    /** Look up the o_users.position for a username. */
    private function position_of(string $username): string
    {
        $row = $this->db->select('position')->from('o_users')
            ->where('username', $username)->limit(1)->get()->row();
        return (string)($row->position ?? '');
    }

    private function note_shape($r): array
    {
        return [
            'id'         => (int)$r->id,
            'title'      => (string)$r->title,
            'content'    => (string)$r->content,
            'created_at' => (string)($r->created_at ?? ''),
            'updated_at' => (string)($r->updated_at ?? ''),
        ];
    }

    private function todo_shape($r): array
    {
        return [
            'id'           => (int)$r->id,
            'task'         => (string)$r->task,
            'is_done'      => (int)($r->is_done ?? 0) === 1,
            'due_date'     => (string)($r->due_date ?? ''),
            'created_at'   => (string)($r->created_at ?? ''),
            'completed_at' => (string)($r->completed_at ?? ''),
            'comment'      => (string)($r->comment ?? ''),
        ];
    }

    private function file_url(string $path): string
    {
        $path = ltrim($path, '/');
        if ($path === '') return '';
        return rtrim($this->runtime_base_url(), '/') . '/' . $path;
    }

    private function runtime_base_url(): string
    {
        $xfProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $xfHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? null;
        $scheme  = $xfProto ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host    = $xfHost  ?: ($_SERVER['HTTP_HOST'] ?? parse_url(base_url(), PHP_URL_HOST) ?? '');
        return rtrim($scheme . '://' . $host, '/');
    }
}

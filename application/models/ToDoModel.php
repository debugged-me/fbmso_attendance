<?php
class ToDoModel extends CI_Model
{

    public function get_all($username)
    {
        return $this->db->get_where('todos', ['username' => $username])->result();
    }

    public function pendingList($username)
    {
        return $this->db->get_where('todos', [
            'username' => $username,
            'is_done' => 0
        ])->result();
    }


    public function add($task, $username, $due_date)
    {
        return $this->db->insert('todos', [
            'task' => $task,
            'username' => $username,
            'due_date' => date('Y-m-d', strtotime($due_date)), // ensures format
            'created_at' => date('Y-m-d H:i:s') // now in Asia/Manila time
        ]);
    }


    public function mark_done($id, $username)
    {
        return $this->db
            ->where('id', $id)
            ->where('username', $username)
            ->update('todos', ['is_done' => 1]);
    }

    public function update_status($id, $status)
    {
        return $this->db->where('id', $id)
            ->update('todos', ['is_done' => $status]);
    }

    public function update_task($id, $data)
    {
        return $this->db->where('id', $id)->update('todos', $data);
    }


    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('todos', $data);
    }


    public function mark_task_done($id, $completed_at, $username = null)
    {
        $data = [
            'is_done' => 1,  // Mark as done
            'completed_at' => $completed_at  // Save the Manila time completion date
        ];

        // Update the task with the provided ID — verify ownership
        $this->db->where('id', $id);
        if ($username !== null) {
            $this->db->where('username', $username);
        }
        $this->db->update('todos', $data);
    }


    public function mark_task_undone($id, $username = null)
    {
        // Reset the 'completed_at' field and mark the task as not done
        $data = [
            'is_done' => 0,  // Mark as undone
            'completed_at' => NULL  // Reset the completed_at field
        ];

        // Update the task with the provided ID — verify ownership
        $this->db->where('id', $id);
        if ($username !== null) {
            $this->db->where('username', $username);
        }
        $this->db->update('todos', $data);
    }


    public function delete($id, $username)
    {
        return $this->db
            ->where('id', $id)
            ->where('username', $username)
            ->delete('todos');
    }
    public function pendingTask($username)
    {
        $this->db->select('COUNT(username) as pendingCount');
        $this->db->from('todos');
        $this->db->where('username', $username);
        $this->db->where('is_done', 0);
        return $this->db->get()->result();
    }
}

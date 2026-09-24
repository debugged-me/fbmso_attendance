<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AnnouncementModel extends CI_Model
{
    private $table = 'announcement';

    public function getAnnouncements()
    {
        $this->db->order_by('aID', 'DESC');
        return $this->db->get($this->table)->result();
    }

    public function insertAnnouncement($data)
    {
        $this->db->insert($this->table, $data);
        return ($this->db->affected_rows() > 0);
    }

    public function getAnnouncementByID($id)
    {
        return $this->db->get_where($this->table, ['aID' => (int)$id])->row();
    }

    public function deleteAnnouncement($id)
    {
        $this->db->where('aID', (int)$id)->delete($this->table);
        return ($this->db->affected_rows() > 0);
    }

    public function updateAnnouncement($id, $data)
    {
        $this->db->where('aID', (int)$id)->update($this->table, $data);
        return ($this->db->affected_rows() >= 0); // 0 = no actual changes, still OK
    }

    public function getActiveAnnouncementsFor($aud) // single audience
    {
        $today = date('Y-m-d');
        $this->db->where("(audience = 'All' OR audience = " . $this->db->escape($aud) . ")", NULL, FALSE);
        $this->db->group_start()
                 ->where('date_expire IS NULL', NULL, FALSE)
                 ->or_where('date_expire >=', $today)
                 ->group_end();
        $this->db->order_by('aID', 'DESC');
        return $this->db->get($this->table)->result();
    }

    // Staff dashboards/bell see every non-expired announcement regardless of
    // audience — they manage the posts and need visibility into what students
    // were told. Student feeds stay audience-filtered via the methods below.
    public function getAllActiveAnnouncements()
    {
        $today = date('Y-m-d');
        $this->db->group_start()
                 ->where('date_expire IS NULL', NULL, FALSE)
                 ->or_where('date_expire >=', $today)
                 ->group_end();
        $this->db->order_by('aID', 'DESC');
        return $this->db->get($this->table)->result();
    }

    public function getActiveAnnouncementsForMany(array $audiences)
    {
        $today = date('Y-m-d');
        $this->db->where_in('audience', $audiences);
        $this->db->group_start()
                 ->where('date_expire IS NULL', NULL, FALSE)
                 ->or_where('date_expire >=', $today)
                 ->group_end();
        $this->db->order_by('aID', 'DESC');
        return $this->db->get($this->table)->result();
    }

    public function countActiveAnnouncementsForMany(array $audiences)
    {
        $today = date('Y-m-d');
        $this->db->where_in('audience', $audiences);
        $this->db->group_start()
                 ->where('date_expire IS NULL', NULL, FALSE)
                 ->or_where('date_expire >=', $today)
                 ->group_end();
        return (int)$this->db->count_all_results($this->table);
    }

    public function countAllActiveAnnouncements()
    {
        $today = date('Y-m-d');
        $this->db->group_start()
                 ->where('date_expire IS NULL', NULL, FALSE)
                 ->or_where('date_expire >=', $today)
                 ->group_end();
        return (int)$this->db->count_all_results($this->table);
    }
    public function getAllAnnouncements()
{
    // Backward-compat alias
    return $this->getAnnouncements();
}

}

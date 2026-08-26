<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Activities_model extends CI_Model
{
    private $table = 'activities';

    // Optional now (controllers write directly using DB):
    private $fillable = [
        'settingsID','code','title','description','location','program',
        'start_at','end_at','status','is_open','organizer_id','meta',
        'created_by','updated_by','created_at','updated_at'
    ];

    private function onlyFillable(array $data)
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    public function create(array $payload)
    {
        $data = $this->onlyFillable($payload);
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }
    
public function list_all()
{
    $this->db->select("
    a.*, 
    DATE(a.start_at) AS activity_date, 
    TIME(a.start_at) AS start_time, 
    TIME(a.end_at) AS end_time,
    CASE
        WHEN a.program = '__custom__' OR a.program = '' THEN NULL
        ELSE a.program
    END AS program_effective,
    CONCAT(u.lName, ', ', u.fName) AS full_name,
    u.lName, u.fName
", false);

    
    $this->db->from('activities a');
    $this->db->join('o_users u', 'u.IDNumber = a.created_by', 'left');  // Join o_users table on IDNumber
    // Sort by creator's LastName, FirstName (then start_at for stability)
    $this->db->order_by('u.lName', 'ASC');
    $this->db->order_by('u.fName', 'ASC');
    $this->db->order_by('a.start_at', 'DESC');
    
    $rows = $this->db->get()->result();

    // Already ordered in SQL by last name, then first name


    return $rows;
}


    // public function find($id)
    // {
    //     return $this->db->where('activity_id', (int)$id)->get($this->table)->row();
    // }
    public function find($id)
{
    return $this->db->select("
            a.*,
            DATE(a.start_at) AS activity_date,
            TIME(a.start_at) AS start_time,
            TIME(a.end_at)   AS end_time,
            CASE
              WHEN a.program = '__custom__' OR a.program = '' THEN NULL
              ELSE a.program
            END AS program_effective
        ", false)
        ->from('activities a')
        ->where('a.activity_id', (int)$id)
        ->limit(1)
        ->get()->row();
}

    public function delete($id)
    {
        $this->db->where('activity_id', (int)$id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function update($id, $data)
    {
        return $this->db->where('activity_id', (int)$id)->update($this->table, $this->onlyFillable($data));
    }
    
}

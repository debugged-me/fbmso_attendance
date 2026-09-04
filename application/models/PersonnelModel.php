<?php
class PersonnelModel extends CI_Model 
{
	function displaypersonnelById($id)
	{
	$query=$this->db->query("select * from staff where IDNumber = ?", array($id));
	return $query->result();
	}
	
	function displaypersonnel()
	{
	$query=$this->db->get("staff");	
	return $query->result();
	}

	function getFilteredPersonnel($department, $position)
	{
		$this->db->select('*');
		if($department)
		$this->db->where('Department', $department);
		if($position)
			$this->db->where('empPosition', $position);
		$query = $this->db->get('staff');
		return $query->result();
	}

	function getDepartment()
	{
		$this->db->select('Department');
		$this->db->distinct();
		$this->db->order_by('Department','ASC');
		$query = $this->db->get('staff');
		return $query->result();
	}

	function getPosition($department)
	{
		$this->db->select('empPosition');
		$this->db->where('Department', $department);
		$this->db->distinct();
		$this->db->order_by('empPosition','ASC');
		$query = $this->db->get('staff');
		return $query->result();
	}

	function is_current_password($username, $currentpass){
		$this->db->select();
		$this->db->from ('users');
		$this->db->where ('username',$username);
		$this->db->where ('password',$currentpass);
		$this->db->like('acctStat','active');
	    $query= $this->db->get();
		$row = $query->row();
		if ($row){
			return TRUE;
		}else { return FALSE;}
		
	}

		
	//Get Leave Credits
	function displayleavecredits($id)
	{
	$query=$this->db->query("select * from hris_leaverecords where IDNumber = ?", array($id));
	return $query->result();
	}
	
	//view files
	function viewfiles($id)
	{
	$query=$this->db->query("select * from hris_files f join staff s on f.IDNumber=s.IDNumber where s.IDNumber = ?", array($id));
	return $query->result();
	}
	//view 201 files All
	function viewfilesAll()
	{
		$query=$this->db->query("select * from hris_files f join staff s on f.IDNumber=s.IDNumber group by s.IDNumber");
		return $query->result();
	}
	
	
	
	 //Get Profile Pictures
	 public function profilepic($id){
		 $this->db->select('*');
		 $this->db->from('users');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	 //Get Available Leave Records
	 public function getLeaveRecords($id){
		 $this->db->select('*');
		 $this->db->from('hris_leaverecords');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }

	 //Get Available Leave History
	 public function getLeaveLeaveHistory($id){
		 $this->db->select('*');
		 $this->db->from('hris_leave');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	 //Get Leave Records of All Employee
	 public function getLeaveAll(){
		 $this->db->select('*');
		 $this->db->from('hris_leave');
		 $this->db->join ('hris_staff','hris_staff.IDNumber=hris_leave.IDNumber');
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	//Family	
	 public function family($id){  
     $this->db->select('*');  
     $this->db->from('hris_family');
	 $this->db->where('IDNumber', $id);
	 $query = $this->db->get();
     return $query->result();
	 }
	 
	 //Education
	 public function education($id){
		 $this->db->select('*');
		 $this->db->from('hris_educ');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	 //Civil Service
	 public function cs($id){
		 $this->db->select('*');
		 $this->db->from('hris_cs');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	 //Trainings
	 public function trainings($id){
		 $this->db->select('*');
		 $this->db->from('hris_trainings');
		 $this->db->where('IDNumber',$id);
		 $query=$this->db->get();
		 return $query->result();
	 }

	//Personnel Counts
	public function personnelCounts()
	{
	$query=$this->db->query("SELECT empPosition, count(empPosition) as Counts FROM staff group by empPosition");
	return $query->result();
	}
	
	//Personnel by Department Counts
	public function departmentcounts()
	{
	$query=$this->db->query("SELECT department, count(department) as Counts FROM staff group by department");
	return $query->result();
	}
	
	//Personnel List by Department
	 public function employeelistDepartment($department){
		 $this->db->select('*');
		 $this->db->from('staff');
		 $this->db->where('Department',$department);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	 //Personnel List by Position
	 public function employeelistPosition($position){
		 $this->db->select('*');
		 $this->db->from('staff');
		 $this->db->where('empPosition',$position);
		 $query=$this->db->get();
		 return $query->result();
	 }
	 
	//Leave For Approval Counts
	public function leaveForApproval()
	{
	$query=$this->db->query("SELECT count(leaveStatus) as statCount FROM hris_leave where leaveStatus='For Approval' group by leaveStatus");
	return $query->result();
	}
	
	//For Retirement
	public function forRetirement($year)
	{
	$query=$this->db->query("SELECT count(retYear) as retYearCounts FROM hris_staff s join hris_employment e on s.IDNumber=e.IDNumber where s.retYear = ? and e.endDate='Present'", array($year));
	return $query->result();
	}
	
	//For Loyalty Cash Award
	public function forLoyalty($year)
	{
	$query=$this->db->query("SELECT count(l.loyaltyDate) as loyaltyCounts FROM hris_loyalty l join hris_employment e on l.IDNumber=e.IDNumber WHERE YEAR(l.loyaltyDate ) = ? and e.endDate='Present'", array($year));
	return $query->result();
	}

	//For Loyalty Cash Award List
	public function forLoyaltyList()
	{
	$query=$this->db->query("SELECT * FROM hris_staff s join hris_employment e on s.IDNumber=e.IDNumber join hris_loyalty l on e.IDNumber=l.IDNumber join mis_settings st on s.settingsID=st.settingsID WHERE YEAR(l.loyaltyDate ) = YEAR( NOW()) and e.endDate='Present'");
	return $query->result();
	}
	
	//For Retirement List
	public function forRetirementList()
	{
	$query=$this->db->query("SELECT * FROM hris_staff s join mis_settings st on s.settingsID=st.settingsID join hris_employment e on s.IDNumber=e.IDNumber where s.retYear=YEAR( NOW()) group by s.IDNumber");
	return $query->result();
	}
	
	//Birthday Celebrants Counts
	public function birthday()
	{
	$query=$this->db->query("SELECT s.IDNumber, concat(s.LastName,', ',s.FirstName,'  ',s.MiddleName) as empName, MONTHNAME(s.BirthDate), count(MONTHNAME(s.BirthDate)) as celebrant, DAY(s.BirthDate) FROM hris_staff s join hris_employment e on s.IDNumber=e.IDNumber WHERE MONTH(s.BirthDate ) = MONTH( NOW())");
	return $query->result();
	}
	
	//Birthday Celebrants List
	public function birthdayList()
	{
	$query=$this->db->query("SELECT s.IDNumber, concat(s.LastName,', ',s.FirstName,'  ',s.MiddleName) as empName, s.BirthDate, MONTHNAME(s.BirthDate) as month, DAY(s.BirthDate) as date, s.Department FROM hris_staff s join hris_employment e on s.IDNumber=e.IDNumber WHERE MONTH(s.BirthDate ) = MONTH( NOW()) order by date");
	return $query->result();
	}
}

<?php
class LibraryModel extends CI_Model 
{



    public function getTotalTitleCount()
    {
        $this->db->select('COUNT(DISTINCT Title) as total_titles');
        $this->db->from('libbookentry');
        $query = $this->db->get();
        $result = $query->row();
        return $result->total_titles;
    }

    public function getTotalBooks()
    {
        $this->db->select('COUNT(DISTINCT BookID) as total_books');
        $this->db->from('libbookentry');
        $query = $this->db->get();
        $result = $query->row();
        return $result->total_books;
    }

    public function getTotalCategories()
    {
        $this->db->select('COUNT(DISTINCT Category) as total_cat');
        $this->db->from('libbookentry');
        $query = $this->db->get();
        $result = $query->row();
        return $result->total_cat;
    }
    

    
    
	function reportsAllBooks()
	{
	$query=$this->db->query("Select * from libbookentry");
	return $query->result();
	}
	
	function bookDetails($id)
	{
	$query=$this->db->query("Select * from libbookentry where BookID = ?", array($id));
	return $query->result();
	}
	
	function getBookCategory()
	{
		$this->db->select('*');
		$this->db->order_by('Category','ASC');
		$query=$this->db->get('libcategory');
		return $query->result();

	}
	
	function getBookLocation()
	{
		$this->db->select('*');
		$this->db->order_by('location','ASC');
		$query = $this->db->get('liblocation');
		return $query->result();
	}
	
	function getPublisher()
	
	{
		$this->db->select('*');
		$this->db->order_by('publisher','ASC');
		$query=$this->db->get('libpublisher');
		return $query->result();
	}

	public function getAuthors() {
		$query = $this->db->get('libauthors'); 
		return $query->result();
	}

	
    public function getAuthorbyId($authorID)
    {
        $query = $this->db->query("SELECT * FROM libauthors WHERE authorID = ?", array($authorID));
        return $query->result();
    }
	
	public function updateAuthor($authorID, $AuthorNum, $FirstName, $MiddleName, $LastName)
    {
        $data = array(
            'AuthorNum' => $AuthorNum,
            'FirstName' => $FirstName,
			'MiddleName' => $MiddleName,
            'LastName' => $LastName,
        );
        $this->db->where('authorID', $authorID);
        $this->db->update('libauthors', $data);
    }



	public function insertAuthor($data) {
        $this->db->insert('libauthors', $data);
    }

	public function Delete_Author($authorID)
    {
        $this->db->where('authorID', $authorID);
        $this->db->delete('libauthors');
    }


	public function getCategory() {
		$query = $this->db->get('libcategory'); 
		return $query->result();
	}

	public function insertcategory($data) {
        $this->db->insert('libcategory', $data);
    }

	public function getcategorybyId($catID)
    {
        $query = $this->db->query("SELECT * FROM libcategory WHERE catID = ?", array($catID));
        return $query->result();
    }

	public function updatecategory($catID, $Category)
    {
        $data = array(
            'Category' => $Category,
           
        );
        $this->db->where('catID', $catID);
        $this->db->update('libcategory', $data);
    }

	public function Delete_category($catID)
    {
        $this->db->where('catID', $catID);
        $this->db->delete('libcategory');
    }

	

	public function getLocation() {
		$query = $this->db->get('liblocation'); 
		return $query->result();
	}

	public function insertlocation($data) {
        $this->db->insert('liblocation', $data);
    }

	public function getlocationbyId($locID)
    {
        $query = $this->db->query("SELECT * FROM liblocation WHERE locID = ?", array($locID));
        return $query->result();
    }

	public function updatelocation($locID, $location)
    {
        $data = array(
            'location' => $location,
           
        );
        $this->db->where('locID', $locID);
        $this->db->update('liblocation', $data);
    }

	public function Delete_location($locID)
    {
        $this->db->where('locID', $locID);
        $this->db->delete('liblocation');
    }


	public function get_publisher() {
		$query = $this->db->get('libpublisher'); 
		return $query->result();
	}

	public function insertpublisher($data) {
        $this->db->insert('libpublisher', $data);
    }

	public function getpublisherbyId($pubID)
    {
        $query = $this->db->query("SELECT * FROM libpublisher WHERE pubID = ?", array($pubID));
        return $query->result();
    }

	public function updatepublisher($pubID, $publisher)
    {
        $data = array(
            'publisher' => $publisher,
           
        );
        $this->db->where('pubID', $pubID);
        $this->db->update('libpublisher', $data);
    }

	public function Delete_publisher($pubID)
    {
        $this->db->where('pubID', $pubID);
        $this->db->delete('libpublisher');
    }
 
	//Student Total Semesters Enrolled
	function semStudeCount($id)
	{
	$query=$this->db->query("SELECT StudentNumber, count(Semester) as SemesterCounts FROM semesterstude where StudentNumber = ? group by StudentNumber", array($id));

	return $query->result();

        if($query->num_rows() > 0)
        {
           return $query->result();
        }
        return false;
	}
	



}

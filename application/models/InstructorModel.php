<?php
class InstructorModel extends CI_Model
{

    // function facultyLoad($id, $sy, $sem)
    // {
    // 	// Use query bindings to prevent SQL injection
    // 	$query = $this->db->query("SELECT * FROM semsubjects WHERE IDNumber = ? AND SY = ? AND Semester = ? ORDER BY SubjectCode", array($id, $sy, $sem));
    // 	return $query->result();
    // }



    public function facultyLoad($id, $sy, $sem)
    {
        $this->db->from('semsubjects');
        $this->db->where('IDNumber', $id);
        $this->db->where('SY', $sy);
        $this->db->where('Semester', $sem);
        $this->db->order_by('SubjectCode');

        $query = $this->db->get();
        return $query->result();
    }




    public function facultyMasterlist1($id, $sy, $sem, $section, $subjectcode)
    {
        $query = $this->db->query("
        SELECT 
            p.StudentNumber, 
            CONCAT(p.LastName, ', ', p.FirstName, ' ',MiddleName) AS StudentName, 
            MID(p.MiddleName, 1, 1) AS MiddleName, 
            r.Course, r.YearLevel, r.Section, r.Major
        FROM studeprofile p
        JOIN registration r ON p.StudentNumber = r.StudentNumber 
        WHERE r.IDNumber = ? 
            AND r.SY = ? 
            AND r.Sem = ? 
            AND r.Section = ? 
            AND r.SubjectCode = ?
        GROUP BY p.StudentNumber 
        ORDER BY StudentName
    ", array($id, $sy, $sem, $section, $subjectcode));

        return $query->result();
    }

    public function grading_sheets($id, $sy, $sem, $section, $subjectcode)
    {
        $query = $this->db->query("
        SELECT 
            p.StudentNumber, 
            CONCAT(p.LastName, ', ', p.FirstName) AS StudentName, 
            MID(p.MiddleName, 1, 1) AS MiddleName, 
            r.Course, r.YearLevel, r.Section, r.Major,r.Prelim,r.Midterm,r.PreFinal,r.Final,r.Average, Description
        FROM studeprofile p
        JOIN grades_o r ON p.StudentNumber = r.StudentNumber 
        WHERE r.IDNumber = ? 
            AND r.SY = ? 
            AND r.Semester = ? 
            AND r.Section = ? 
            AND r.SubjectCode = ?
        GROUP BY p.StudentNumber 
        ORDER BY StudentName
    ", array($id, $sy, $sem, $section, $subjectcode));

        return $query->result();
    }

    public function updateAverageForAllStudents()
    {
        // Average only if ALL grading periods are non-zero
        $sql = "
        UPDATE grades_o
        SET Average = ROUND( (Prelim + Midterm + PreFinal + Final) / 4, 2 )
        WHERE COALESCE(Prelim,   0) <> 0
          AND COALESCE(Midterm,  0) <> 0
          AND COALESCE(PreFinal, 0) <> 0
          AND COALESCE(Final,    0) <> 0
    ";

        $this->db->query($sql);
        return $this->db->affected_rows() > 0;
    }



    function facultyMasterlist($id, $sy, $sem, $section, $subjectcode)
    {
        $query = $this->db->query("
        SELECT 
            p.StudentNumber, 
            CONCAT(p.LastName, ', ', p.FirstName) AS StudentName, 
            MID(p.MiddleName, 1, 1) AS MiddleName, 
            r.Course, r.YearLevel, r.Section, r.Major,r.LecUnit,r.LabUnit
        FROM studeprofile p
        JOIN registration r ON p.StudentNumber = r.StudentNumber 
        WHERE r.IDNumber = ? 
            AND r.SY = ? 
            AND r.Sem = ? 
            AND r.Section = ? 
            AND r.SubjectCode = ?
        GROUP BY p.StudentNumber 
        ORDER BY StudentName
    ", array($id, $sy, $sem, $section, $subjectcode));

        return $query->result();
    }



    // function subjectGrades($id, $sy, $sem, $section, $subjectcode){
    // 	$query=$this->db->query("SELECT p.StudentNumber, concat(p.LastName,', ',p.FirstName) as StudentName, mid(p.MiddleName,1) as MiddleName, g.Course, g.Section, g.Final FROM studeprofile p join grades g on p.StudentNumber=g.StudentNumber where g.SubjectCode='".$subjectcode."' and g.SY='".$sy."' and g.Semester='".$sem."' and g.Section='".$section."' and g.Instructor='".$id."' group by p.StudentNumber order by p.LastName");
    // 	return $query->result();
    // 	}

    function subjectGrades($id, $sy, $sem, $section, $subjectcode)
    {
        $this->db->select("p.StudentNumber, CONCAT(p.LastName, ', ', p.FirstName) AS StudentName, MID(p.MiddleName, 1) AS MiddleName, g.Course, g.Section, g.Final,g.Major,g.YearLevel,g.Prelim,g.PrelimStat,g.gradesid,g.Midterm,g.MidtermStat,g.PreFinal,g.PreFinalStat,g.Final,g.FinalStat,g.LecUnit,g.LabUnit");
        $this->db->from('studeprofile p');
        $this->db->join('grades_o g', 'p.StudentNumber = g.StudentNumber');
        $this->db->where('g.SubjectCode', $subjectcode);
        $this->db->where('g.SY', $sy);
        $this->db->where('g.Semester', $sem);
        $this->db->where('g.Section', $section);
        $this->db->where('g.IDNumber', $id);
        $this->db->group_by('p.StudentNumber');
        $this->db->order_by('p.LastName');

        $query = $this->db->get();
        return $query->result();
    }

    function subjectGrades_count($id, $sy, $sem, $section, $subjectcode)
    {
        $this->db->select("p.StudentNumber, CONCAT(p.LastName, ', ', p.FirstName) AS StudentName, MID(p.MiddleName, 1) AS MiddleName, g.Course, g.Section, g.Final");
        $this->db->from('studeprofile p');
        $this->db->join('grades_o g', 'p.StudentNumber = g.StudentNumber');
        $this->db->where('g.SubjectCode', $subjectcode);
        $this->db->where('g.SY', $sy);
        $this->db->where('g.Semester', $sem);
        $this->db->where('g.Section', $section);
        $this->db->where('g.IDNumber', $id);
        $this->db->group_by('p.StudentNumber');
        $this->db->order_by('p.LastName');

        $query = $this->db->get();
        return $query;
    }




    function gradingSheets($instructor, $sy, $sem)
    {
        $query = $this->db->query("SELECT * FROM grades WHERE Instructor = ? AND Semester = ? AND SY = ? GROUP BY SubjectCode, Section", array($instructor, $sem, $sy));
        return $query->result();
    }

    public function insert_batch($data)
    {
        return $this->db->insert_batch('grades_o', $data);
    }
}

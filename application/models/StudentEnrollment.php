<?php
class StudentEnrollment extends CI_Model
{

    // public function getStudentDetails($studentNumber)
    // {
    //     return $this->db->get_where('semesterstude', ['StudentNumber' => $studentNumber])->row();
    // }


    // Model: StudentEnrollment.php
public function getStudentDetails($studentNumber)
{
    $sy       = $this->session->userdata('sy');        // . logged-in SY
    $semester = $this->session->userdata('semester'); // . logged-in Semester

    return $this->db->get_where('semesterstude', [
        'StudentNumber' => $studentNumber,
        'SY'            => $sy,
        'Semester'      => $semester
    ])->row();
}


public function getStudentDetails1($studentNumber)
{
    return $this->db
        ->select('studeprofile.StudentNumber, studeprofile.LastName, studeprofile.FirstName, studeprofile.MiddleName, 
                  semesterstude.YearLevel, semesterstude.Course, semesterstude.Major, 
                  semesterstude.SY, semesterstude.Semester')
        ->from('semesterstude')
        ->join('studeprofile', 'studeprofile.StudentNumber = semesterstude.StudentNumber', 'left')
        ->where('semesterstude.StudentNumber', $studentNumber)
        ->order_by('semesterstude.SY', 'DESC')
        ->limit(1)
        ->get()
        ->row();
}




    public function getSubjectDetails($subjectCode)
    {
        return $this->db->get_where('semsubjects', ['SubjectCode' => $subjectCode])->row();
    }


    public function getAvailableSubjects($student)
    {
        // Subquery to get enrolled subject codes for the student
        $this->db->select('SubjectCode');
        $this->db->from('registration');
        $this->db->where([
            'StudentNumber' => $student->StudentNumber,
            'Sem'           => $student->Semester,
            'SY'            => $student->SY
        ]);
        $subquery = $this->db->get_compiled_select();

        // Main query to get available subjects NOT already enrolled
        $this->db->where([
            'Course'     => $student->Course,
            'YearLevel'  => $student->YearLevel,
            'Section'    => $student->Section,
            'Semester'   => $student->Semester,
            'SY'         => $student->SY
        ]);
        $this->db->where("SubjectCode NOT IN ($subquery)", NULL, FALSE);

        return $this->db->get('semsubjects')->result();
    }


    public function registerSubject($data)
    {
        // Check if already registered
        $this->db->where([
            'StudentNumber' => $data['StudentNumber'],
            'SubjectCode'   => $data['SubjectCode'],
            'Sem'           => $data['Sem'],
            'SY'            => $data['SY']
        ]);

        $exists = $this->db->get('registration')->row();

        if ($exists) {
            return false;
        }

        return $this->db->insert('registration', $data);
    }


// public function getSubjectsByCourseMajor($course, $major, $studentNumber, $sy, $sem)
// {
//     $this->db->select('semsubjects.*, staff.FirstName, staff.MiddleName, staff.LastName');
//     $this->db->from('semsubjects');
//     $this->db->join('staff', 'staff.IDNumber = semsubjects.IDNumber', 'left');
//     $this->db->where('semsubjects.Course', $course);

//     if (!empty($major)) {
//         $this->db->where('semsubjects.cMajor', $major);
//     }

//     // Exclude subjects that are already in registration for this student
//     $this->db->where("NOT EXISTS (
//         SELECT 1 FROM registration r 
//         WHERE r.StudentNumber = " . $this->db->escape($studentNumber) . "
//         AND r.SubjectCode = semsubjects.SubjectCode
//         AND r.SY = " . $this->db->escape($sy) . "
//         AND r.Sem = " . $this->db->escape($sem) . "
//     )", null, false);

//     return $this->db->get()->result();
// }



public function getSubjectsByCourseMajor($course, $major, $studentNumber, $sy, $sem)
{
    $this->db->select('semsubjects.*, staff.FirstName, staff.MiddleName, staff.LastName');
    $this->db->from('semsubjects');
    $this->db->join('staff', 'staff.IDNumber = semsubjects.IDNumber', 'left');
    $this->db->where('semsubjects.Course', $course);

    if (!empty($major)) {
        $this->db->where('semsubjects.cMajor', $major);
    }

    // Exclude already registered subjects and those already requested in dropadd
    $this->db->where("
        NOT EXISTS (
            SELECT 1 FROM registration r 
            WHERE r.StudentNumber = " . $this->db->escape($studentNumber) . "
            AND r.SubjectCode = semsubjects.SubjectCode
            AND r.SY = " . $this->db->escape($sy) . "
            AND r.Sem = " . $this->db->escape($sem) . "
        )
        AND NOT EXISTS (
            SELECT 1 FROM dropadd d
            WHERE d.StudentNumber = " . $this->db->escape($studentNumber) . "
            AND d.subjectCode = semsubjects.SubjectCode
            AND d.SY = " . $this->db->escape($sy) . "
            AND d.Sem = " . $this->db->escape($sem) . "
            AND d.adAction = 'Adding'
        )
    ", null, false);

    return $this->db->get()->result();
}

public function getPendingDropAddSubjects($studentNumber, $sy, $sem)
{
    $this->db->select('*');
    $this->db->from('dropadd');
    $this->db->where('StudentNumber', $studentNumber);
    $this->db->where('SY', $sy);
    $this->db->where('Sem', $sem);
    $this->db->where('adAction', 'Adding');
    return $this->db->get()->result();
}



public function getEnrolledSubjects($studentNumber)
{
    $this->db->from('registration');
    $this->db->join('semsubjects', 'registration.SubjectCode = semsubjects.SubjectCode', 'left');
    $this->db->where('StudentNumber', $studentNumber);
    return $this->db->get()->result();
}

public function getRegisteredSubjects($studentNumber, $course, $major = null, $sy = null, $sem = null)
{
    $this->db->select('registration.*, (registration.LecUnit + registration.LabUnit) AS Units, registration.Sem');
    $this->db->from('registration');
    $this->db->where('registration.StudentNumber', $studentNumber);
    $this->db->where('registration.Course', $course);

    if (!empty($major)) {
        $this->db->where('registration.Major', $major);
    }

    // . Ensure filtering by current School Year and Semester
    if (!empty($sy)) {
        $this->db->where('registration.SY', $sy);
    }

    if (!empty($sem)) {
        $this->db->where('registration.Sem', $sem);
    }

    // . Exclude dropped subjects using NOT EXISTS
    $this->db->where("NOT EXISTS (
        SELECT 1 FROM dropadd 
        WHERE dropadd.StudentNumber = registration.StudentNumber 
        AND dropadd.SubjectCode = registration.SubjectCode 
        AND dropadd.SY = registration.SY 
        AND dropadd.Sem = registration.Sem
    )", null, false);

    return $this->db->get()->result();
}



public function getDroppedSubjects($studentNumber, $course, $major = null, $sy = null, $sem = null)
{
    $this->db->select('dropadd.*, (registration.LecUnit + registration.LabUnit) AS Units');
    $this->db->from('dropadd');
    $this->db->join('registration', 'registration.SubjectCode = dropadd.SubjectCode 
        AND registration.StudentNumber = dropadd.StudentNumber 
        AND registration.SY = dropadd.SY 
        AND registration.Sem = dropadd.Sem', 'left');

    $this->db->where('dropadd.StudentNumber', $studentNumber);
    $this->db->where('registration.Course', $course);

    if (!empty($major)) {
        $this->db->where('registration.Major', $major);
    }

    if (!empty($sy)) {
        $this->db->where('dropadd.SY', $sy);
    }

    if (!empty($sem)) {
        $this->db->where('dropadd.Sem', $sem);
    }

    $query = $this->db->get();
    return $query->result();
}




 public function viewEnrolledSubjects($studentNumber)
  {
    $semester = $this->session->userdata('semester');
    $sy = $this->session->userdata('sy');

    // . Fetch enrolled subjects with instructor name from staff
    $this->db->select('r.*, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS Instructor');
    $this->db->from('registration r');
    $this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
    $this->db->where('r.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $enrolled = $this->db->get()->result();

    // . Fetch student details
    $this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, r.Course, r.YearLevel');
    $this->db->from('studeprofile sp');
    $this->db->join('registration r', 'sp.StudentNumber = r.StudentNumber');
    $this->db->where('sp.StudentNumber', $studentNumber);
    $this->db->where('r.Sem', $semester);
    $this->db->where('r.SY', $sy);
    $this->db->limit(1);
    $student = $this->db->get()->row();

    $data['student'] = $student;
    $data['enrolled'] = $enrolled;

    $this->load->view('enrolled_subjects_view', $data);
  }













public function getRequestedSubjects()
{
    $this->db->select('dropadd.*, semsubjects.Description as SubjectDesc, semsubjects.Section, semsubjects.SchedTime, semsubjects.Course, semsubjects.cMajor,
                       studeprofile.FirstName, studeprofile.LastName, studeprofile.MiddleName');
    $this->db->from('dropadd');
    $this->db->join('semsubjects', 'dropadd.subjectCode = semsubjects.SubjectCode 
                                    AND dropadd.SY = semsubjects.SY 
                                    AND dropadd.Sem = semsubjects.Semester', 'left');
    $this->db->join('studeprofile', 'dropadd.StudentNumber = studeprofile.StudentNumber', 'left');
    $this->db->where('dropadd.adAction', 'Adding'); // Show only "Adding" requests
    return $this->db->get()->result();
}



public function getStudentInfoWithRegistration($studentNumber, $sy, $sem)
{
    $this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, r.Course, r.YearLevel, r.Section');
    $this->db->from('studeprofile sp');
    $this->db->join('registration r', 'sp.StudentNumber = r.StudentNumber AND r.SY = ' . $this->db->escape($sy) . ' AND r.Sem = ' . $this->db->escape($sem), 'left');
    $this->db->where('sp.StudentNumber', $studentNumber);
    $this->db->limit(1);
    $student = $this->db->get()->row();

    if (!$student) {
        // fallback if student exists but no registration
        $student = $this->db
            ->select('StudentNumber, FirstName, MiddleName, LastName')
            ->get_where('studeprofile', ['StudentNumber' => $studentNumber])
            ->row();

        if ($student) {
            $student->Course = '';
            $student->YearLevel = '';
            $student->Section = '';
        }
    }

    return $student;
}


}

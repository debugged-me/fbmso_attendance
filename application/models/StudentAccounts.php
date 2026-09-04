<?php
class StudentAccounts extends CI_Model
{

public function get_all_students($sy, $sem)
{
    $this->db->select('registration.*, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
    $this->db->from('registration');
    $this->db->join('studeprofile', 'registration.StudentNumber = studeprofile.StudentNumber');
    
    // Exclude students who already have an account for the current SY and Sem
    $this->db->where("registration.StudentNumber NOT IN (
        SELECT StudentNumber FROM studeaccount WHERE SY = ? AND Sem = ?
    )", array($sy, $sem));
    
    $this->db->where('registration.SY', $sy);
    $this->db->where('registration.Sem', $sem);
    $this->db->group_by('registration.StudentNumber');
    $this->db->order_by('studeprofile.LastName', 'ASC');

    return $this->db->get()->result();
}


public function get_student_by_number($student_number, $sy, $sem)
{
    $this->db->select('StudentNumber, Course, Major, YearLevel, Sem as Semester, SY, Section, settingsID');
    $this->db->from('registration');
    $this->db->where('StudentNumber', $student_number);
    $this->db->where('SY', $sy);
    $this->db->where('Sem', $sem);
    $this->db->limit(1);
    return $this->db->get()->row();
}

public function get_total_units($studentNumber, $sy, $sem)
{
    $this->db->select('
        SUM(CAST(LabUnit AS DECIMAL(10,2))) AS totalLabUnit,
        SUM(CAST(LecUnit AS DECIMAL(10,2))) AS totalLecUnit
    ');
    $this->db->from('registration');
    $this->db->where('StudentNumber', $studentNumber);
    $this->db->where('SY', $sy);
    $this->db->where('Sem', $sem);

    return $this->db->get()->row();
}



    public function get_applicable_fees($course, $major, $year_level, $semester)
    {
        $this->db->where('Course', $course);
        $this->db->where('YearLevel', $year_level);
        $this->db->where('Semester', $semester);

        // Only check Major if it’s not null or empty
        if (!empty($major)) {
            $this->db->where('Major', $major);
        }

        return $this->db->get('fees')->result();
    }

  public function get_applicable_fees_by_student($student)
{
    $this->db->from('fees');
    $this->db->where('Course', $student->Course);
    $this->db->where('YearLevel', $student->YearLevel);
    $this->db->where('Semester', $student->Semester);

    // Check if student has a major
    if (!empty(trim($student->Major))) {
        // Filter only by exact major
        $this->db->where('Major', $student->Major);
    } else {
        // No major — allow blank or NULL fees
        $this->db->group_start();
        $this->db->where('Major', '');
        $this->db->or_where('Major IS NULL', null, false);
        $this->db->group_end();
    }

    return $this->db->get()->result();
}


public function get_total_payments($student_number, $semester, $sy)
{
    $this->db->select_sum('Amount');
    $this->db->from('paymentsaccounts');
    $this->db->where('StudentNumber', $student_number);
    $this->db->where('Sem', $semester);
    $this->db->where('SY', $sy);
    $query = $this->db->get();
    $result = $query->row();
    return (float)($result->Amount ?? 0);
}


public function get_unpaid_balances_other_sy($studentNumber, $currentSY)
{
    $this->db->select('SY, Sem, (CurrentBalance) as totalBalance');
    $this->db->from('studeaccount');
    $this->db->where('StudentNumber', $studentNumber);
    $this->db->where('SY !=', $currentSY);
    $this->db->where('CurrentBalance >', 0);
    $this->db->group_by(['SY', 'Sem']);
    return $this->db->get()->result();
}



public function get_course_rate($course, $major, $year_level)
{
    $this->db->select('LecRate, LabRate');
    $this->db->from('coursefees');
    $this->db->where('Course', $course);
    $this->db->where('YearLevel', $year_level);

    // Match by Major if available, otherwise look for empty/NULL major
    if (!empty(trim($major))) {
        $this->db->where('Major', $major);
    } else {
        $this->db->group_start();
        $this->db->where('Major', '');
        $this->db->or_where('Major IS NULL', null, false);
        $this->db->group_end();
    }

    return $this->db->get()->row(); // Will return object with LecRate and LabRate
}


}

<?php
class Ren_model extends CI_Model
{



    public function profile_insert()
    {

        $data = array(
            'StudentNumber' => $this->input->post('StudentNumber'),
            'FirstName' => strtoupper($this->input->post('FirstName')),
            'MiddleName' => strtoupper($this->input->post('MiddleName')),
            'LastName' => strtoupper($this->input->post('LastName')),
            'Sex' => $this->input->post('Sex'),
            'CivilStatus' => $this->input->post('CivilStatus'),
            'BirthPlace' => $this->input->post('BirthPlace'),
            'Citizenship' => $this->input->post('Citizenship'),
            'Religion' => $this->input->post('Religion'),
            'BloodType' => $this->input->post('BloodType'),
            'TelNumber' => $this->input->post('TelNumber'),
            'MobileNumber' => $this->input->post('MobileNumber'),
            'BirthDate' => $this->input->post('BirthDate'),
            'Guardian' => $this->input->post('Guardian'),
            'GuardianContact' => $this->input->post('GuardianContact'),
            'GuardianAddress' => $this->input->post('GuardianAddress'),
            'GuardianRelationship' => $this->input->post('GuardianRelationship'),
            'GuardianTelNo' => $this->input->post('GuardianTelNo'),
            'EmailAddress' => $this->input->post('EmailAddress'),
            'Father' => $this->input->post('Father'),
            'FOccupation' => $this->input->post('FOccupation'),
            'Mother' => $this->input->post('Mother'),
            'MOccupation' => $this->input->post('MOccupation'),
            'Age' => $this->input->post('Age'),
            'Ethnicity' => $this->input->post('Ethnicity'),
            'Province' => $this->input->post('Province'),
            'City' => $this->input->post('City'),
            'Brgy' => $this->input->post('Brgy'),
            'Sitio' => $this->input->post('Sitio'),
            'guardianOccupation' => $this->input->post('guardianOccupation'),
            'nameExt' => $this->input->post('nameExt'),
            'LRN' => $this->input->post('LRN'),
            'ParentsMonthly' => $this->input->post('ParentsMonthly'),
            'Notes' => $this->input->post('Notes'),
            'Elementary' => $this->input->post('Elementary')
        );

        return $this->db->insert('studeprofile', $data);
    }


    public function profile_update()
    {

        $data = array(
            'StudentNumber' => $this->input->post('StudentNumber'),
            'FirstName' => $this->input->post('FirstName'),
            'MiddleName' => $this->input->post('MiddleName'),
            'LastName' => $this->input->post('LastName'),
            'Sex' => $this->input->post('Sex'),
            'CivilStatus' => $this->input->post('CivilStatus'),
            'BirthPlace' => $this->input->post('BirthPlace'),
            'Citizenship' => $this->input->post('Citizenship'),
            'Religion' => $this->input->post('Religion'),
            'BloodType' => $this->input->post('BloodType'),
            'TelNumber' => $this->input->post('TelNumber'),
            'MobileNumber' => $this->input->post('MobileNumber'),
            'BirthDate' => $this->input->post('BirthDate'),
            'Guardian' => $this->input->post('Guardian'),
            'GuardianContact' => $this->input->post('GuardianContact'),
            'GuardianAddress' => $this->input->post('GuardianAddress'),
            'GuardianRelationship' => $this->input->post('GuardianRelationship'),
            'GuardianTelNo' => $this->input->post('GuardianTelNo'),
            'EmailAddress' => $this->input->post('EmailAddress'),
            'Father' => $this->input->post('Father'),
            'FOccupation' => $this->input->post('FOccupation'),
            'Mother' => $this->input->post('Mother'),
            'MOccupation' => $this->input->post('MOccupation'),
            'Age' => $this->input->post('Age'),
            'Ethnicity' => $this->input->post('Ethnicity'),
            'Province' => $this->input->post('Province'),
            'City' => $this->input->post('City'),
            'Brgy' => $this->input->post('Brgy'),
            'Sitio' => $this->input->post('Sitio'),
            'guardianOccupation' => $this->input->post('guardianOccupation'),
            'nameExt' => $this->input->post('nameExt'),
            'LRN' => $this->input->post('LRN'),
            'ParentsMonthly' => $this->input->post('ParentsMonthly'),
            'Notes' => $this->input->post('Notes'),
            'Elementary' => $this->input->post('Elementary')
        );

        $this->db->where('StudentNumber', $this->input->get('id'));
        return $this->db->update('studeprofile', $data);
    }

    public function user_insert()
    {

        date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
        $now = date('H:i:s A');

        $AdmissionDate = date("Y-m-d");
        // Never derive a password from the birth date: it is knowable by
        // classmates and was the credential class behind the 2026-08-28
        // account takeover. Issue a random one instead; the account holder
        // sets a real password through the forgot-password flow.
        $Password = fbmso_password_hash(bin2hex(random_bytes(12)));
        $Encoder = $this->session->userdata('username');

        $data = array(
            'username' => $this->input->post('StudentNumber'),
            'password' => $Password,
            'position' => 'Student',
            'fName' => $this->input->post('FirstName'),
            'mName' => $this->input->post('MiddleName'),
            'lName' => $this->input->post('LastName'),
            'email' => $this->input->post('EmailAddress'),
            'avatar' => 'avatar.png',
            'acctStat' => 'Active',
            'dateCreated' => $AdmissionDate,
            'IDNumber' => $this->input->post('StudentNumber')
        );

        return $this->db->insert('o_users', $data);
    }

    public function atrail_insert($desc)
    {

        date_default_timezone_set('Asia/Manila'); # add your city to set local time zone
        $now = date('H:i:s A');

        $AdmissionDate = date("Y-m-d");
        $Encoder = $this->session->userdata('username');

        $data = array(
            'atDesc' => $desc,
            'atDate' => $AdmissionDate,
            'atTime' => $now,
            'atRes' => $Encoder,
            'atSNo' => $this->input->post('StudentNumber')
        );

        return $this->db->insert('atrail', $data);
    }

    public function enroll_insert()
    {


        $data = array(
            'StudentNumber' => $this->input->post('StudentNumber'),
            'Course' => $this->input->post('Course'),
            'YearLevel' => $this->input->post('YearLevel'),
            'Status' => 'Enrolled',
            'Semester' => $this->input->post('Semester'),
            'SY' => $this->input->post('SY'),
            'Section' => $this->input->post('Section'),
            'StudeStatus' => $this->input->post('StudeStatus'),
            //'Scholarship' => $this->input->post('Scholarship'), 
            //'YearLevelStat' => $this->input->post('YearLevelStat'), 
            //'Major' => $this->input->post('Major'), 
            'Track' => $this->input->post('Track'),
            'Qualification' => $this->input->post('Qualification'),
            'BalikAral' => $this->input->post('BalikAral'),
            'IP' => $this->input->post('IP'),
            'FourPs' => $this->input->post('FourPs'),
            'Repeater' => $this->input->post('Repeater'),
            'Transferee' => $this->input->post('Transferee'),
            'EnrolledDate' => date("Y-m-d"),
            'Adviser' => $this->input->post('Adviser'),
            'IDNumber' => $this->input->post('IDNumber'),
        );

        return $this->db->insert('semesterstude', $data);
    }

    public function online_enrollment_update()
    {

        $data = array(
            'enrolStatus' => 'Verified'
        );

        $this->db->where('StudentNumber', $this->input->post('StudentNumber'));
        $this->db->where('Semester', $this->input->post('Semester'));
        $this->db->where('SY', $this->input->post('SY'));
        return $this->db->update('online_enrollment', $data);
    }

    public function enlistment_insert()
    {
        $SubjectCode = implode(',', $this->input->post('SubjectCode'));
        $sc = explode(',', $SubjectCode);

        for ($i = 0; $i < count($sc); $i++) {
            $pda1 = $this->input->post('pda' . $i . '1');
            $pda2 = $this->input->post('pda' . $i . '2');
            $pda3 = $this->input->post('pda' . $i . '3');

            $item = array(
                'SubjectCode' => $SubjectCode,
                'Description' => $this->input->post('Description'),
                'Section' => $this->input->post('Section'),
                'SchedTime' => $this->input->post('SchedTime'),
            );

            $this->db->insert('registration', $item);
        }
    }

    public function insert_batch($data)
    {
        $this->db->insert_batch('registration', $data);
    }

    public function ebook_insert($file, $file2)
    {


        $data = array(
            'title' => $this->input->post('title'),
            'author' => $this->input->post('author'),
            'isbn' => $this->input->post('isbn'),
            'pub_date' => $this->input->post('pub_date'),
            'genre' => $this->input->post('genre'),
            'description' => $this->input->post('description'),
            'file_path' => $file,
            'cover_image' => $file2
        );

        return $this->db->insert('ebooks', $data);
    }

    public function ebook_update()
    {


        $data = array(
            'title' => $this->input->post('title'),
            'author' => $this->input->post('author'),
            'isbn' => $this->input->post('isbn'),
            'pub_date' => $this->input->post('pub_date'),
            'genre' => $this->input->post('genre'),
            'description' => $this->input->post('description')
        );

        $this->db->where('id', $this->input->post('id'));
        return $this->db->update('ebooks', $data);
    }

    public function ebook_cover_update($file)
    {


        $data = array(
            'cover_image' => $file
        );


        $this->db->where('id', $this->input->post('id'));
        return $this->db->update('ebooks', $data);
    }

    public function ebook_file_update($file)
    {


        $data = array(
            'file_path' => $file
        );


        $this->db->where('id', $this->input->post('id'));
        return $this->db->update('ebooks', $data);
    }


    //common delete function

    public function delete($table, $col_id, $segment)
    {
        $id = $this->uri->segment($segment);
        $this->db->where($col_id, $id);
        $this->db->delete($table);
        return true;
    }

    function delete_ebook($table, $col, $segment, $attach)
    {
        $this->db->where($col, $segment);
        unlink("upload/ebook/" . $attach);
        $this->db->delete($table);
    }

    public function tcd($table, $col, $val, $col2, $val2)
    { // two cond delete
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->delete($table);
        return true;
    }

    public function del($table, $col, $val)
    { // one cond delete
        $this->db->where($col, $val);
        $this->db->delete($table);
        return true;
    }

    public function get_foos($page)
    {
        // First count all foos
        $count = $this->db->count_all('ebooks');

        // Create the pagination links
        //$this->load->library('pagination');
        //$this->load->helper('url');

        $paging_conf = [
            'uri_segment'      => 3,
            'per_page'         => 2,
            'total_rows'       => $count,
            'base_url'         => site_url('Library/page'),
            'first_url'        => site_url('Library/page/1'),
            'use_page_numbers' => TRUE,
            'attributes'       => ['class' => 'number'],
            'prev_link'        => 'Previous',
            'next_link'        => 'Next',

            // Custom classes for pagination links
            'prev_tag_open'    => '<ul>',
            'prev_tag_close'   => '</ul>',
            'prev_tag_open'    => '<li class="page-item prev-item">',
            'prev_tag_close'   => '</li>',
            'next_tag_open'    => '<li class="page-item next-item">',
            'next_tag_close'   => '</li>',
        ];



        $this->pagination->initialize($paging_conf);

        // Create the paging buttons for the view
        $this->load->vars('pagination_links', $this->pagination->create_links());

        // The pagination offset
        $offset = $page * $paging_conf['per_page'] - $paging_conf['per_page'];

        // Get our set of foos
        $query = $this->db->get('ebooks', $paging_conf['per_page'], $offset);

        // Make sure we have foos
        if ($query->num_rows() > 0)
            return $query->result();

        // Else return default
        return NULL;
    }


    // public function insert_grades($data)
    // {
    //     return $this->db->insert_batch('grades', $data);
    // }

    public function insert_grades($data)
    {
        $this->db->insert_batch('grades', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            log_message('error', 'DB Error: ' . $this->db->_error_message());
            return false;
        }
    }

    public function update_grades($data)
    {
        return $this->db->update_batch('grades', $data, 'gradeID');
    }

    public function batch_update_grades($update_data)
    {
        // Update the grades in the database
        $this->db->update_batch('grades', $update_data, 'gradeID'); // 'id' is the primary key
    }

    public function update_batchren($data)
    {
        return $this->db->update_batch('grades', $data, 'gradeID');
    }

    public function update_batch_stud($data)
    {
        return $this->db->update_batch('grades', $data, 'gradeID');
    }


    public function insert_enlist_sub($data)
    {
        $this->db->insert('registration', $data);
    }

    // common function single row
    public function one_cond_row($table, $col, $val)
    {
        $this->db->where($col, $val);
        $result = $this->db->get($table)->row();
        return $result;
    }

    public function two_cond_row($table, $col, $val, $col2, $val2)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $result = $this->db->get($table)->row();
        return $result;
    }

    public function three_cond_row($table, $col, $val, $col2, $val2, $col3, $val3)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $result = $this->db->get($table)->row();
        return $result;
    }

    // common functions loop

    public function no_cond($table)
    {
        $query = $this->db->get($table);
        return $query->result();
    }

    public function one_cond($table, $col, $val)
    {
        $this->db->where($col, $val);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function two_cond($table, $col, $val, $col2, $val2)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $query = $this->db->get($table);
        return $query->result();
    }
    public function three_cond($table, $col, $val, $col2, $val2, $col3, $val3)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function four_cond($table, $col, $val, $col2, $val2, $col3, $val3, $col4, $val4)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $this->db->where($col4, $val4);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function one_cond_loop_order_by($table, $col, $val, $orderby, $orderbyvalue)
    {
        $this->db->where($col, $val);
        $this->db->order_by($orderby, $orderbyvalue);
        $query = $this->db->get($table);
        return $query->result();
    }


    // group by and order by

    public function one_cond_loop_order_group($table, $col, $val, $orderby, $orderbyvalue, $gc)
    {
        $this->db->where($col, $val);
        $this->db->group_by($gc);
        $this->db->order_by($orderby, $orderbyvalue);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function no_cond_loop_order_group($table, $orderby, $orderbyvalue, $gc)
    {
        $this->db->group_by($gc);
        $this->db->order_by($orderby, $orderbyvalue);
        $query = $this->db->get($table);
        return $query->result();
    }


    // public function get_registration_data($sy, $sem)
    // {
    //     $this->db->select('*');
    //     $this->db->from('registration');
    //     $this->db->where('SY', $sy);
    //     $this->db->where('Sem', $sem);
    //     $this->db->group_by('StudentNumber');
    //     $this->db->order_by('Course');
    //     $this->db->order_by('YearLevel');
    //     //$this->db->order_by('LastName');

    //     $query = $this->db->get();
    //     return $query->result();
    // }

    public function get_registration_data($sy, $sem){
    
    $ivy = $this->input->post('course');
    $ivan = $this->input->post('yl');

    $this->db->select('
        registration.*,
        registration.Course AS RegCourse,
        studeprofile.LastName, studeprofile.FirstName, studeprofile.MiddleName,
        studeprofile.nameExtn, studeprofile.Sex, studeprofile.birthDate
    ');
    $this->db->from('registration');
    $this->db->join('studeprofile', 'studeprofile.StudentNumber = registration.StudentNumber', 'left');
    $this->db->where('registration.SY', $sy);
    $this->db->where('registration.Sem', $sem);

    if($ivy != ''){
    $this->db->where('registration.Course', $ivy);
    }

    if($ivan != ''){
    $this->db->where('registration.YearLevel', $ivan);
    }

    $this->db->group_by('registration.StudentNumber');
    $this->db->order_by('registration.Course', 'ASC');
    $this->db->order_by('registration.YearLevel', 'ASC');
    $this->db->order_by('studeprofile.LastName', 'ASC');

    $query = $this->db->get();
    return $query->result();
}

    public function three_cond_select($table,$select, $col, $val,$col2,$val2,$col3,$val3)
    {
        $this->db->select($select);
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function no_cond_select_gb($table,$select,$gb)
    {
        $this->db->select($select);
        $this->db->group_by($gb);
        $query = $this->db->get($table);
        return $query->result();
    }


    // count rows in the table

    public function four_cond_count($table, $col, $val, $col2, $val2, $col3, $val3, $col4, $val4)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $this->db->where($col4, $val4);
        $query = $this->db->get($table);
        return $query;
    }

    public function three_cond_count($table, $col, $val, $col2, $val2, $col3, $val3)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $query = $this->db->get($table);
        return $query;
    }

    public function get_lec_units($sem, $sy, $id, $col, $val)
    {
        // Use Query Builder class to construct the query
        $this->db->select_sum($col, $val);
        $this->db->from('grades');
        $this->db->where('Semester', $sem);
        $this->db->where('SY', $sy);
        $this->db->where('StudentNumber', $id);
        $query = $this->db->get();

        // Return the result as an associative array
        return $query->row_array();
    }

    public function get_lec_units_reg($sem, $sy, $id, $col, $val)
    {
        // Use Query Builder class to construct the query
        $this->db->select_sum($col, $val);
        $this->db->from('registration');
        $this->db->where('Sem', $sem);
        $this->db->where('SY', $sy);
        $this->db->where('StudentNumber', $id);
        $query = $this->db->get();

        // Return the result as an associative array
        return $query->row_array();
    }

    // public function get_students($sy, $sem, $course = null, $yearLevel = null)
    // {

    //     $this->db->select('*');
    //     $this->db->from('registration');
    //     $this->db->join('grades', 'registration.StudentNumber = grades.StudentNumber', 'inner');
    //     $this->db->where('registration.SY', $sy);
    //     $this->db->where('registration.Sem', $sem);

    //     if (!empty($course))    $this->db->where('grades.Course',    $course);
    //     if (!empty($yearLevel)) $this->db->where('grades.YearLevel', $yearLevel);

    //     $this->db->group_by('registration.StudentNumber');
    //     $this->db->order_by('registration.Course');
    //     $this->db->order_by('registration.YearLevel');
        
    //     $query = $this->db->get();
    //     return $query->result();
    // }
    public function get_students($sy, $sem, $course = null, $yearLevel = null)
    {
        $this->db->select("
            r.StudentNumber,
            s.FirstName,
            s.MiddleName,
            s.LastName,
            r.Course,
            r.Major,
            r.YearLevel,
            r.Section,
            s.Sex,
            s.nameExtn,
            s.birthDate,
            GROUP_CONCAT(r.SubjectCode) AS Subjects,
            GROUP_CONCAT(r.Description) AS Descriptions
        ");
        $this->db->from('registration r');
        $this->db->join('studeprofile s', 's.StudentNumber = r.StudentNumber');
        $this->db->where('r.SY', $sy);
        $this->db->where('r.Sem', $sem);

        if (!empty($course))    $this->db->where('r.Course',    $course);
        if (!empty($yearLevel)) $this->db->where('r.YearLevel', $yearLevel);

        $this->db->group_by('r.StudentNumber');
        $this->db->order_by('r.Course');
        $this->db->order_by('r.YearLevel');
        
        $query = $this->db->get();
        return $query->result();
    }

   

    public function no_cond_group($table, $col)
    {
        $this->db->group_by($col);
        $query = $this->db->get($table);
        return $query->result();
    }

    public function four_cond_group($table, $col, $val, $col2, $val2, $col3, $val3, $col4, $val4, $gc)
    {
        $this->db->where($col, $val);
        $this->db->where($col2, $val2);
        $this->db->where($col3, $val3);
        $this->db->where($col4, $val4);
        $this->db->group_by($gc);
        $query = $this->db->get($table);
        return $query->result();
    }
















    







































        /* ----------------------------------------------------------------------
     *  STUDENT PICKER LISTS
     * -------------------------------------------------------------------- */

    /**
     * Students who are enrolled in subjects taught by this instructor in SY.
     * Matches by SY + SubjectCode + Description and Section (if set), Semester-aware.
     */
    public function get_students_with_registration_for_teacher_subjects($sy, $teacherID)
    {
        $teacherID = trim((string)$teacherID);

        $this->db->select('DISTINCT r.StudentNumber, s.LastName, s.FirstName, s.MiddleName', false);
        $this->db->from('registration r');
        $this->db->join('studeprofile s', 's.StudentNumber = r.StudentNumber', 'left');

        // Semester-aware + section-aware match to semsubjects (instructor load)
        $this->db->join(
            'semsubjects ss',
            "ss.SY = r.SY
             AND TRIM(ss.SubjectCode) = TRIM(r.SubjectCode)
             AND TRIM(ss.Description) = TRIM(r.Description)
             AND (ss.Section IS NULL OR ss.Section = '' OR TRIM(ss.Section) = TRIM(r.Section))
             AND (ss.Semester IS NULL OR ss.Semester='' OR TRIM(ss.Semester)=TRIM(r.Sem))",
            'inner'
        );

        $this->db->where('r.SY', $sy);
        $this->db->where('ss.SY', $sy);
        $this->db->where('ss.IDNumber', $teacherID);
        $this->db->order_by('s.LastName', 'ASC');

        return $this->db->get()->result();
    }

    /** Fallback list (Registrar/Admin): all students with registration in SY. */
    public function get_students_with_registration($sy)
    {
        $this->db->select('DISTINCT r.StudentNumber, s.LastName, s.FirstName, s.MiddleName', false);
        $this->db->from('registration r');
        $this->db->join('studeprofile s', 's.StudentNumber = r.StudentNumber', 'left');
        $this->db->where('r.SY', $sy);
        $this->db->order_by('s.LastName', 'ASC');
        return $this->db->get()->result();
    }

    /* ----------------------------------------------------------------------
     *  AUTHZ HELPERS (Instructor security)
     * -------------------------------------------------------------------- */

    /** True if the given student has any registration row under this instructor's load (in SY). */
    public function instructor_owns_student($sy, $instructorID, $studentNumber)
    {
        $this->db->select('1', false);
        $this->db->from('registration r');
        $this->db->join(
            'semsubjects ss',
            "ss.SY = r.SY
             AND TRIM(ss.SubjectCode) = TRIM(r.SubjectCode)
             AND TRIM(ss.Description) = TRIM(r.Description)
             AND (ss.Section IS NULL OR ss.Section='' OR TRIM(ss.Section)=TRIM(r.Section))
             AND (ss.Semester IS NULL OR ss.Semester='' OR TRIM(ss.Semester)=TRIM(r.Sem))",
            'inner'
        );
        $this->db->where('r.SY', $sy);
        $this->db->where('r.StudentNumber', $studentNumber);
        $this->db->where('ss.IDNumber', $instructorID);
        $this->db->limit(1);
        return (bool) $this->db->get()->row();
    }

    /**
     * Return all registration.regnumber rows the instructor is allowed to modify
     * for this student and SY.
     */
    public function get_allowed_reg_ids($sy, $studentNumber, $instructorID)
    {
        $this->db->select('r.regnumber', false);
        $this->db->from('registration r');
        $this->db->join(
            'semsubjects ss',
            "ss.SY = r.SY
             AND TRIM(ss.SubjectCode) = TRIM(r.SubjectCode)
             AND TRIM(ss.Description) = TRIM(r.Description)
             AND (ss.Section IS NULL OR ss.Section='' OR TRIM(ss.Section)=TRIM(r.Section))
             AND (ss.Semester IS NULL OR ss.Semester='' OR TRIM(ss.Semester)=TRIM(r.Sem))",
            'inner'
        );
        $this->db->where('r.SY', $sy);
        $this->db->where('r.StudentNumber', $studentNumber);
        $this->db->where('ss.IDNumber', $instructorID);

        $ids = [];
        foreach ($this->db->get()->result() as $row) {
            $ids[] = (int)$row->regnumber;
        }
        return $ids;
    }

    /* ----------------------------------------------------------------------
     *  REGISTRATION ROWS (pending only, Semester-aware, Instructor filter)
     * -------------------------------------------------------------------- */

    /**
     * Pending registration rows for a student & SY, optionally restricted to a specific instructor.
     * Joins to grades_o by (StudentNumber, SubjectCode, SY, Section, Semester) to detect pending.
     *
     * @param string      $sy
     * @param string      $studentNumber
     * @param 'all'|'first'|'second'|'third'|'fourth' $grading
     * @param string|null $instructorID  If set, only subjects taught by this instructor are returned.
     */
   public function get_registration_rows_pending_for_grading($sy, $studentNumber, $grading = 'all', $instructorID = null)
{
    // Hard lock: instructor access only
    if (empty($instructorID)) {
        return []; // or throw an exception / log and return []
    }

    $this->db->select("
        r.regnumber AS regID,
        r.StudentNumber,
        r.SubjectCode,
        r.Description,
        r.Instructor,
        r.SY,
        r.YearLevel,
        r.Section,
        r.Sem AS Semester,
        /* prefer semsubjects; fallback to registration; default '0' */
        COALESCE(NULLIF(r.Course,''), NULLIF(ss.Course,''), '') AS Course,
        COALESCE(NULLIF(r.Major,''),  NULLIF(ss.cMajor,''),   '') AS Major,
        COALESCE(NULLIF(ss.LecUnit,''), NULLIF(r.LecUnit,''), '0') AS LecUnit,
        COALESCE(NULLIF(ss.LabUnit,''), NULLIF(r.LabUnit,''), '0') AS LabUnit
    ", false);
    $this->db->from('registration r');

    // INNER JOIN limits strictly to this instructor's actual load
    $joinCond = "
        ss.SY = r.SY
        AND TRIM(ss.SubjectCode) = TRIM(r.SubjectCode)
        AND TRIM(ss.Description) = TRIM(r.Description)
        AND (ss.Section IS NULL OR ss.Section='' OR TRIM(ss.Section)=TRIM(r.Section))
        AND (ss.Semester IS NULL OR ss.Semester='' OR TRIM(ss.Semester)=TRIM(r.Sem))
        AND ss.IDNumber = " . $this->db->escape($instructorID) . "
    ";
    $this->db->join('semsubjects ss', $joinCond, 'inner');

    // Pending detection (section + semester aware)
    $this->db->join('grades_o g',
        'g.StudentNumber = r.StudentNumber AND '.
        'g.SubjectCode   = r.SubjectCode   AND '.
        'g.SY            = r.SY            AND '.
        'g.Section       = r.Section       AND '.
        'g.Semester      = r.Sem',
        'left'
    );

    $this->db->where('r.SY', $sy);
    $this->db->where('r.StudentNumber', $studentNumber);

    // Pending filter logic
    if ($grading === 'first') {
        $this->db->group_start()
            ->where('g.gradesid IS NULL', null, false)
            ->or_where('COALESCE(g.Prelim,0)=0', null, false)
        ->group_end();
    } elseif ($grading === 'second') {
        $this->db->group_start()
            ->where('g.gradesid IS NULL', null, false)
            ->or_where('COALESCE(g.Midterm,0)=0', null, false)
        ->group_end();
    } elseif ($grading === 'third') {
        $this->db->group_start()
            ->where('g.gradesid IS NULL', null, false)
            ->or_where('COALESCE(g.PreFinal,0)=0', null, false)
        ->group_end();
    } elseif ($grading === 'fourth') {
        $this->db->group_start()
            ->where('g.gradesid IS NULL', null, false)
            ->or_where('COALESCE(g.Final,0)=0', null, false)
        ->group_end();
    } else { // all
        $this->db->group_start()
            ->where('g.gradesid IS NULL', null, false)
            ->or_group_start()
                ->where('COALESCE(g.Prelim,0)=0', null, false)
                ->where('COALESCE(g.Midterm,0)=0', null, false)
                ->where('COALESCE(g.PreFinal,0)=0', null, false)
                ->where('COALESCE(g.Final,0)=0', null, false)
            ->group_end()
        ->group_end();
    }

    $this->db->order_by('r.Description', 'ASC');
    return $this->db->get()->result();
}

/** Optional general fetch; not used by current controller flow. */
public function get_registration_rows($sy, $studentNumber)
{
    $this->db->select('
        regnumber AS regID,
        StudentNumber,
        SubjectCode,
        Description,
        Instructor,
        SY,
        Sem AS Semester,
        YearLevel,
        Section,
        /* added */
        Course,
        Major,
        LecUnit,
        LabUnit
    ', false);
    $this->db->from('registration');
    $this->db->where('SY', $sy);
    $this->db->where('StudentNumber', $studentNumber);
    $this->db->order_by('Description', 'ASC');
    return $this->db->get()->result();
}

    /* ----------------------------------------------------------------------
     *  UPSERT: grades_o (College; Semester-aware)
     * -------------------------------------------------------------------- */

    private function get_existing_grade_o_row($sn, $sc, $sy, $section = '', $semester = '')
    {
        $this->db->from('grades_o');
        $this->db->where([
            'StudentNumber' => $sn,
            'SubjectCode'   => $sc,
            'SY'            => $sy,
            'Section'       => $section,
            'Semester'      => $semester,
        ]);
        return $this->db->get()->row();
    }

    /**
     * Insert/update into grades_o. Updates only if existing row is still blank
     * (all four periods 0/NULL). Computes Average if all four present.
     */
    public function upsert_grades_o_for_pending(array $rows)
    {
        $res = ['inserted'=>0,'updated'=>0,'skipped'=>0];

        foreach ($rows as $r) {
            $sn       = $r['StudentNumber'] ?? '';
            $sc       = $r['SubjectCode']   ?? '';
            $sy       = $r['SY']            ?? '';
            $section  = $r['Section']       ?? '';
            $semester = $r['Semester']      ?? '';

            if ($sn === '' || $sc === '' || $sy === '' || $semester === '') {
                $res['skipped']++;
                continue;
            }

            $exist = $this->get_existing_grade_o_row($sn, $sc, $sy, $section, $semester);

            $prelim   = $r['Prelim']   ?? null;
            $midterm  = $r['Midterm']  ?? null;
            $prefinal = $r['PreFinal'] ?? null;
            $final    = $r['Final']    ?? null;

            $avg = (is_numeric($prelim) && is_numeric($midterm) && is_numeric($prefinal) && is_numeric($final))
                ? ((float)$prelim + (float)$midterm + (float)$prefinal + (float)$final) / 4.0
                : ($r['Average'] ?? null);

            $r['Average'] = $avg;

            if (!$exist) {
                $this->db->insert('grades_o', $r);
                if ($this->db->affected_rows() > 0) $res['inserted']++;
            } else {
                // Update only if still blank
                $allZero = (float)($exist->Prelim   ?? 0) == 0.0
                        && (float)($exist->Midterm  ?? 0) == 0.0
                        && (float)($exist->PreFinal ?? 0) == 0.0
                        && (float)($exist->Final    ?? 0) == 0.0;

                if ($allZero) {
                    $upd = [
                        'Prelim'      => $prelim,
                        'Midterm'     => $midterm,
                        'PreFinal'    => $prefinal,
                        'Final'       => $final,
                        'Average'     => $avg,
                        // refresh meta if provided
                        'IDNumber'    => $r['IDNumber']    ?? $exist->IDNumber,
                        'Description' => $r['Description'] ?? $exist->Description,
                        'YearLevel'   => $r['YearLevel']   ?? $exist->YearLevel,
                        'Section'     => $r['Section']     ?? $exist->Section,
                        'LecUnit'     => $r['LecUnit']     ?? $exist->LecUnit,
                        'LabUnit'     => $r['LabUnit']     ?? $exist->LabUnit,
                        'timeEncoded' => $r['timeEncoded'] ?? $exist->timeEncoded,
                        'dateEncoded' => $r['dateEncoded'] ?? $exist->dateEncoded,
                    ];
                    $this->db->where('gradesid', $exist->gradesid)->update('grades_o', $upd);
                    if ($this->db->affected_rows() > 0) $res['updated']++;
                } else {
                    $res['skipped']++;
                }
            }
        }

        return $res;
    }

    /* ----------------------------------------------------------------------
     *  LEGACY: grades table (optional/backward compatibility)
     * -------------------------------------------------------------------- */

    private function get_existing_grade_row($sn, $sc, $sy, $section = null, $semester = null)
    {
        $this->db->from('grades');
        $this->db->where([
            'StudentNumber' => $sn,
            'SubjectCode'   => $sc,
            'SY'            => $sy,
        ]);
        if (!empty($section))  $this->db->where('Section', $section);
        if (!empty($semester)) $this->db->where('Semester', $semester);
        return $this->db->get()->row();
    }

    /** Upsert for old `grades` table (kept for compatibility). */
    public function upsert_grades_for_pending(array $rows)
    {
        $res = ['inserted'=>0,'updated'=>0,'skipped'=>0];

        foreach ($rows as $r) {
            $sn      = $r['StudentNumber'] ?? '';
            $sc      = $r['SubjectCode']   ?? '';
            $sy      = $r['SY']            ?? '';
            $section = $r['Section']       ?? '';
            $semester= $r['Semester']      ?? null;

            if ($sn === '' || $sc === '' || $sy === '') { $res['skipped']++; continue; }

            $exist = $this->get_existing_grade_row($sn, $sc, $sy, $section, $semester);

            $p  = $r['PGrade']      ?? null;
            $m  = $r['MGrade']      ?? null;
            $pf = $r['PFinalGrade'] ?? null;
            $f  = $r['FGrade']      ?? null;

            $avg = (is_numeric($p) && is_numeric($m) && is_numeric($pf) && is_numeric($f))
                ? ((float)$p + (float)$m + (float)$pf + (float)$f) / 4.0
                : null;
            $r['Average'] = $avg;

            if (!$exist) {
                $this->db->insert('grades', $r);
                $res['inserted'] += ($this->db->affected_rows() > 0) ? 1 : 0;
            } else {
                $allZero = (float)($exist->PGrade ?? 0) == 0.0
                        && (float)($exist->MGrade ?? 0) == 0.0
                        && (float)($exist->PFinalGrade ?? 0) == 0.0
                        && (float)($exist->FGrade ?? 0) == 0.0;

                if ($allZero) {
                    $upd = [
                        'PGrade'      => $r['PGrade'],
                        'MGrade'      => $r['MGrade'],
                        'PFinalGrade' => $r['PFinalGrade'],
                        'FGrade'      => $r['FGrade'],
                        'Average'     => $r['Average'],
                        'Instructor'  => $r['Instructor'] ?? $exist->Instructor,
                        'Description' => $r['Description'] ?? $exist->Description,
                        'YearLevel'   => $r['YearLevel']   ?? $exist->YearLevel,
                        'Section'     => $r['Section']     ?? $exist->Section,
                    ];
                    $this->db->where('gradeID', $exist->gradeID)->update('grades', $upd);
                    $res['updated'] += ($this->db->affected_rows() > 0) ? 1 : 0;
                } else {
                    $res['skipped']++;
                }
            }
        }

        return $res;
    }

    /* ----------------------------------------------------------------------
     *  AUDIT TRAIL (safe; will not crash if table differs)
     * -------------------------------------------------------------------- */

    /**
     * Safe audit trail insert; falls back to log_message if insert fails.
     * @param string $message  Details
     * @param string $entity   Module/table name
     * @param string $ref      Reference (e.g., StudentNumber)
     */
    public function atrail_insert_grades($message, $entity, $ref = '')
    {
        // Try inserting into a generic 'atrail' table without crashing on schema mismatch
        $payload = [
            'details'    => $message,
            'module'     => $entity,
            'refid'      => $ref,
            'created_at' => date('Y-m-d H:i:s'),
            'user_id'    => (string)($this->session->userdata('IDNumber') ?? ''),
        ];

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE; // suppress DB errors if table/columns differ
        $this->db->insert('atrail', $payload);
        $this->db->db_debug = $db_debug;

        // Always log to PHP log as well
        log_message('info', "[ATRAIL][$entity][$ref] $message");
    }

}

<?php
class StudentModel extends CI_Model
{


	public function getStudentByNumber($studentNumber)
	{
		$semester = $this->session->userdata('semester');
		$sy = $this->session->userdata('sy');

		$this->db->select('sp.StudentNumber, ss.Course, ss.Major, sp.FirstName, sp.LastName');
		$this->db->from('studeprofile sp');
		$this->db->join('semesterstude ss', 'sp.StudentNumber = ss.StudentNumber');
		$this->db->where('sp.StudentNumber', $studentNumber);
		$this->db->where('ss.Semester', $semester);
		$this->db->where('ss.SY', $sy);
		return $this->db->get()->row();
	}



	public function generate_student_number()
	{
		$currentYear = date('Y');
		$prefix = $currentYear;

		$this->db->select_max('StudentNumber');
		$this->db->like('StudentNumber', $prefix . '-', 'after'); // match YYYY-
		$query = $this->db->get('studeprofile');
		$row = $query->row();

		if ($row && $row->StudentNumber) {
			// Split on dash to extract the numeric part
			$parts = explode('-', $row->StudentNumber);
			$lastNumber = isset($parts[1]) ? (int)$parts[1] : 0;
			$newNumber = $lastNumber + 1;
		} else {
			$newNumber = 1;
		}

		return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
	}

	public function updateSignupStatus($studentNumber, $status)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->update('studentsignup', ['Status' => $status]);
	}


	public function getSubjectsWithGrades($course, $major, $studentNumber, $effectivity = null)
	{
		$this->db->select([
			's.SubjectCode',
			's.description',
			's.lecunit',
			's.labunit',
			's.prereq',
			's.YearLevel',
			's.Semester',
			's.Effectivity',
			'g.Final AS FinalGrade'
		]);
		$this->db->from('subjects s');

		// --- Robust join: match grade by SubjectCode for this student only ---
		// Normalizes: trim + upper + removes spaces/dashes + unifies collation.
		// NOTE: set the collation used below to one your server supports (utf8mb4_unicode_ci is safe on MySQL 5.7+).
		$join = "
        REPLACE(REPLACE(UPPER(CONVERT(s.SubjectCode USING utf8mb4)),'-',''),' ','')
            COLLATE utf8mb4_unicode_ci
        =
        REPLACE(REPLACE(UPPER(CONVERT(g.SubjectCode USING utf8mb4)),'-',''),' ','')
            COLLATE utf8mb4_unicode_ci
        AND g.StudentNumber = " . $this->db->escape($studentNumber);

		// The 'false' keeps CI from escaping our SQL expression
		$this->db->join('grades_o g', $join, 'left', false);

		// Subject list is still filtered by the selected Course/Major/Effectivity
		$this->db->where('s.Course', $course);
		if (!empty($major)) {
			$this->db->where('s.Major', $major);
		} else {
			$this->db->where('(s.Major IS NULL OR s.Major = "")', null, false);
		}
		if (!empty($effectivity)) {
			$this->db->where('s.Effectivity', $effectivity);
		}

		$this->db->order_by('s.YearLevel, s.Semester, s.SubjectCode');
		return $this->db->get()->result();
	}




	public function get_students_by_year_level($yearLevel, $courseDesc, $major, $sy, $sem)
	{
		$this->db->select('s.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, s.YearLevel, s.Section');
		$this->db->from('semesterstude s');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = s.StudentNumber', 'left');
		$this->db->where('s.YearLevel', $yearLevel);
		$this->db->where('s.Course', $courseDesc);
		$this->db->where('s.SY', $sy);
		$this->db->where('s.Semester', $sem);

		if (!empty($major)) {
			$this->db->where('s.Major', $major);
		}

		$this->db->order_by('sp.LastName', 'ASC');
		return $this->db->get()->result();
	}



	public function getEffectivityOptions()
	{
		$this->db->distinct();
		$this->db->select('Effectivity');
		$this->db->from('subjects');
		$this->db->order_by('Effectivity', 'DESC');
		return $this->db->get()->result();
	}

	public function getEffectivityOptionsByCourse($course)
	{
		$this->db->distinct();
		$this->db->select('Effectivity');
		$this->db->from('subjects');
		$this->db->where('Course', $course);
		$this->db->order_by('Effectivity', 'DESC');
		return $this->db->get()->result();
	}

	public function getEffectivityOptionsByCourseMajor($course, $major)
	{
		$this->db->distinct();
		$this->db->select('Effectivity');
		$this->db->from('subjects');
		$this->db->where('Course', $course);

		// Add Major filter
		if (!empty($major)) {
			$this->db->where('Major', $major);
		} else {
			// Handle subjects with no major (nullable or empty string)
			$this->db->where('(Major IS NULL OR Major = "")', null, false);
		}

		$this->db->order_by('Effectivity', 'DESC');
		return $this->db->get()->result();
	}




	public function searchStudents()
	{
		// $this->db->select('*');
		$this->db->select('StudentNumber, FirstName, MiddleName, LastName');
		$this->db->from('studeprofile');
		$this->db->order_by('LastName');
		$query = $this->db->get();
		return $query->result();
	}

	function getProfileAccounting($sem, $sy)
	{
		$this->db->select('sp.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName, sp.birthDate');
		$this->db->from('studeprofile sp');
		$this->db->join('semesterstude ss', 'sp.StudentNumber = ss.StudentNumber', 'inner');
		$this->db->where('ss.Semester', $sem);
		$this->db->where('ss.SY', $sy);
		$this->db->order_by('sp.LastName', 'ASC');

		$query = $this->db->get();
		return $query->result();
	}

	public function isFlagged($id)
	{
		$this->db->where('StudentNumber', $id);
		$this->db->where('Status', 'unsettled');
		$query = $this->db->get('flagged_students');
		return $query->num_rows() > 0;
	}

	public function getFlagDetails($id)
	{
		$this->db->where('StudentNumber', $id);
		$this->db->where('Status', 'unsettled');
		$query = $this->db->get('flagged_students');
		return $query->row(); // return a single record
	}


	public function get_student_profile($student_number)
	{
		return $this->db->get_where('studeprofile', ['StudentNumber' => $student_number])->row();
	}

	public function get_srms_settings()
	{
		return $this->db->get('o_srms_settings')->row();
	}

	public function get_grades($student_number)
	{
		$this->db->from('grades');
		$this->db->where('StudentNumber', $student_number);
		$this->db->order_by('SY DESC, Semester DESC');
		return $this->db->get()->result();
	}


	function getacountHistory($sem, $sy)
	{
		$this->db->select('studeaccount.StudentNumber, studeaccount.FirstName, studeaccount.MiddleName, studeaccount.LastName, studeaccount.AcctTotal, studeaccount.Discount, studeaccount.TotalPayments, studeaccount.CurrentBalance, paymentsaccounts.PDate, paymentsaccounts.ORNumber, paymentsaccounts.Amount, paymentsaccounts.description');
		$this->db->from('studeaccount');
		$this->db->join('paymentsaccounts', 'studeaccount.StudentNumber = paymentsaccounts.StudentNumber', 'left'); // Use 'left' join to include students with no payments
		$this->db->where('studeaccount.sem', $sem);
		$this->db->where('studeaccount.sy', $sy);
		$this->db->where('paymentsaccounts.sem', $sem);
		$this->db->where('paymentsaccounts.sy', $sy);
		$this->db->order_by('studeaccount.LastName', 'ASC');
		$this->db->order_by('paymentsaccounts.PDate', 'ASC'); // Order payments by date
		$this->db->group_by('paymentsaccounts.ORNumber');

		$query = $this->db->get();
		return $query->result();
	}


	// 	public function getacountHistory($sem, $sy)
	// {
	//     $this->db->distinct();
	//     $this->db->select('StudentNumber');
	//     $this->db->from('studeaccount');
	// 	$this->db->where('sem', $sem); // Ensure 'Semester' is correct
	// 		$this->db->where('sy', $sy); // Ensure 'SY' is correct
	//     $query = $this->db->get();
	//     return $query->result();
	// }



	public function deanList($semester, $sy, $course, $yearLevel, $yearLevelStat)
	{
		// Raw SQL query with parameters
		$sql = "
			SELECT ss.StudentNumber, ss.FName, ss.MName, ss.LName,
				   ROUND(AVG(CAST(CASE 
					   WHEN g.Final = 'INC' THEN 6
					   WHEN NULLIF(g.Final, '') IS NOT NULL THEN g.Final
					   ELSE NULL
				   END AS DECIMAL(3,2))), 1) AS AverageGrade
			FROM grades g
			JOIN semesterstude ss ON g.StudentNumber = ss.StudentNumber
			WHERE g.Semester = ?
			  AND g.SY = ?
			  AND g.Course = ?
			  AND ss.YearLevel = ?
			  AND ss.YearLevelStat = ?
			GROUP BY ss.StudentNumber, ss.FName, ss.MName, ss.LName
			HAVING MAX(CAST(CASE
					   WHEN g.Final = 'INC' THEN 6
					   WHEN NULLIF(g.Final, '') IS NOT NULL THEN g.Final
					   ELSE NULL 
				   END AS DECIMAL(3,2))) <= 2.5
			ORDER BY ss.StudentNumber;
		";

		// Execute the query with bound parameters
		$query = $this->db->query($sql, [$semester, $sy, $course, $yearLevel, $yearLevelStat]);
		return $query->result();
	}

	function totalStudeAccountProfile($sy, $sem)
	{
		$this->db->select('COUNT(DISTINCT sp.StudentNumber) AS StudeCount');
		$this->db->from('studeprofile sp');
		$this->db->join('semesterstude sa', 'sp.StudentNumber = sa.StudentNumber');
		$this->db->where('sa.SY', $sy);
		$this->db->where('sa.Semester', $sem);

		$query = $this->db->get();
		return $query->result();
	}




	public function totalSignups()
	{
		$q = $this->db->query("SELECT COUNT(*) AS StudeCount FROM studentsignup");
		return $q->result();
	}

	function totalProfile()
	{
		$query = $this->db->query("SELECT count(StudentNumber) as StudeCount FROM studeprofile");
		return $query->result();
	}

	public function o_srms_settings()
	{
		$this->db->select('*');
		$this->db->from('o_srms_settings');
		$query = $this->db->get();
		return $query->row(); // ✅ returns a single object
	}


	function getGrades($sem, $sy)
	{
		// Select the required columns from both tables
		$this->db->select('grades.*, studeprofile.*'); // Adjust columns as needed

		// Set the conditions for the grades table
		$this->db->where('grades.Semester', $sem);
		$this->db->where('grades.SY', $sy);

		// Join the studeprofile table on StudentNumber
		$this->db->join('studeprofile', 'grades.StudentNumber = studeprofile.StudentNumber');

		// Perform the query on the grades table
		$query = $this->db->get('grades');

		// Return the result
		return $query->result();
	}


	public function addNewStudent()
	{
		date_default_timezone_set('Asia/Manila'); // Set local time zone

		// Capitalize names
		$firstName = ucwords(strtolower($this->input->post('FirstName')));
		$middleName = ucwords(strtolower($this->input->post('MiddleName')));
		$lastName = ucwords(strtolower($this->input->post('LastName')));

		$data = array(
			'StudentNumber' => $this->input->post('StudentNumber'),
			'FirstName' => $firstName,
			'MiddleName' => $middleName,
			'LastName' => $lastName,
			'nameExtn' => $this->input->post('nameExtn'),
			'Sex' => $this->input->post('Sex'),
			'CivilStatus' => $this->input->post('CivilStatus'),
			'Religion' => $this->input->post('Religion'),
			'Ethnicity' => $this->input->post('Ethnicity'),
			'contactNo' => $this->input->post('contactNo'),
			'birthDate' => $this->input->post('birthDate'),
			'BirthPlace' => $this->input->post('BirthPlace'),
			'Age' => $this->input->post('Age'),
			'Father' => $this->input->post('Father'),
			'FOccupation' => $this->input->post('FOccupation'),
			'Mother' => $this->input->post('Mother'),
			'MOccupation' => $this->input->post('MOccupation'),
			'Guardian' => $this->input->post('Guardian'),
			'GuardianContact' => $this->input->post('GuardianContact'),
			'GuardianRelationship' => $this->input->post('GuardianRelationship'),
			'GuardianAddress' => $this->input->post('GuardianAddress'),
			'Sitio' => $this->input->post('Sitio'),
			'Brgy' => $this->input->post('Brgy'),
			'City' => $this->input->post('City'),
			'Province' => $this->input->post('Province'),
			'sitioPresent' => $this->input->post('Sitio'),
			'brgyPresent' => $this->input->post('Brgy'),
			'cityPresent' => $this->input->post('City'),
			'provincePresent' => $this->input->post('Province'),
			'email' => $this->input->post('email'),
			'working' => $this->input->post('working'),
			'nationality' => $this->input->post('nationality'),
			'settingsID' => 1,
			'Encoder' => $this->session->userdata('username')
		);

		return $this->db->insert('studeprofile', $data);
	}




	public function get_provinces()
	{
		$this->db->select('AddID, Province'); // Ensure AddID is included
		$this->db->group_by('Province'); // Group by Province to get distinct values
		$this->db->order_by('Province', 'ASC');
		$query = $this->db->get('settings_address');
		return $query->result();
	}

	// Legacy alias used by older controllers/views
	public function getProvince()
	{
		return $this->get_provinces();
	}

	public function get_cities($province = null)
	{
		if (!$province) {
			return []; // Return an empty array if no province is provided
		}

		$this->db->select('AddID, City');
		$this->db->where('Province', $province);
		$this->db->group_by('City');
		$this->db->order_by('City', 'ASC');
		$query = $this->db->get('settings_address');

		return $query->result();
	}

	// Legacy alias; optional $province for compatibility
	public function getCity($province = null)
	{
		if ($province) {
			return $this->get_cities($province);
		}

		$this->db->select('AddID, City');
		$this->db->group_by('City');
		$this->db->order_by('City', 'ASC');
		return $this->db->get('settings_address')->result();
	}

	// Get barangays based on selected city
	public function get_barangays($city)
	{
		$this->db->select('AddID, Brgy'); // Ensure AddID is included
		$this->db->where('City', $city); // Filter by city
		$this->db->group_by('Brgy');
		$this->db->order_by('Brgy', 'ASC');
		$query = $this->db->get('settings_address');
		return $query->result();
	}

	public function userAccounts()
	{
		$this->db->where('position !=', 'Super Admin');
		$query = $this->db->get('o_users');
		return $query->result();
	}


	function studeGradesGroup($studeno)
	{
		$this->db->select('Semester, SY');
		$this->db->from('studeprofile s');
		$this->db->join('grades g', 's.StudentNumber = g.StudentNumber');
		$this->db->where('s.StudentNumber', $studeno);

		$query = $this->db->get();
		return $query->result();
	}


	public function copy_users_to_o_users()
	{
		$this->load->database();

		// Step 1: Get all usernames already in o_users
		$existingUsernames = $this->db->select('username')->get('o_users')->result_array();
		$existingUsernames = array_column($existingUsernames, 'username');

		// Step 2: Get users not in o_users
		$this->db->select('username, password, position, fName, mName, lName, email, avatar, acctStat, dateCreated, name');
		if (!empty($existingUsernames)) {
			$this->db->where_not_in('username', $existingUsernames);
		}
		$newUsersQuery = $this->db->get('users');
		$newUsers = $newUsersQuery->result_array();

		// Step 3: Insert each non-duplicate user into o_users
		$insertedCount = 0;
		foreach ($newUsers as $user) {
			$this->db->insert('o_users', $user);
			$insertedCount++;
		}

		// Step 4: Set flash message
		if ($insertedCount > 0) {
			$msg = "<div class='alert alert-success'>{$insertedCount} new user(s) copied to <strong>o_users</strong>.</div>";
		} else {
			$msg = "<div class='alert alert-info'>No new users to copy. All usernames already exist in <strong>o_users</strong>.</div>";
		}

		$this->session->set_flashdata('msg', $msg);
		redirect('YourController/your_redirect_target');
	}



	public function studentSignup($id)
	{
		$query = $this->db->get_where('studentsignup', ['StudentNumber' => $id]);
		return $query->result();
	}


	//ADMIN ANNOUNCEMENT ---------------------------------------------------------------------------------
	public function announcement()
	{
		$this->db->select('*');
		$this->db->from('announcement');
		$this->db->order_by('aID', 'desc');
		$query = $this->db->get();
		return $query->result();
	}

	public function deleteAnnouncement($id)
	{
		$this->db->where('aID', $id);
		$this->db->delete('announcement');
	}

	function deleteUserAccount($id)
	{
		$this->db->query("delete  from users_online where username='" . $id . "'");
	}

	function deleteRequirement($id)
	{
		$this->db->query("delete  from online_requirements where reqID='" . $id . "'");
	}

	public function gradesSummary($sy, $sem)
	{
		$this->db->select('
        ss.StudentNumber,
        CONCAT(sp.LastName, ", ", sp.FirstName, " ", sp.MiddleName) AS StudentName,
        ss.Course,
        ss.YearLevel,
        g.SubjectCode,
        g.Final,
        g.Semester,
        g.SY
    ');
		$this->db->from('grades g');
		$this->db->join('semesterstude ss', 'g.StudentNumber = ss.StudentNumber');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber');
		$this->db->where('ss.SY', $sy);
		$this->db->where('ss.Semester', $sem);
		$this->db->where('g.SY', $sy);
		$this->db->where('g.Semester', $sem);
		$this->db->order_by('g.SubjectCode');
		$this->db->order_by('StudentName');
		$this->db->order_by('ss.YearLevel');

		$query = $this->db->get();
		return $query->result();
	}

	function getTrackingNo()
	{
		$query = $this->db->query("select * from stude_request order by trackingNo desc limit 1");
		return $query->result();
	}

	function medInfo()
	{
		$query = $this->db->query("SELECT * FROM medical_info m join studeprofile p on m.StudentNumber=p.StudentNumber");
		return $query->result();
	}

	function medInfoInd($id)
	{
		$query = $this->db->query("SELECT * FROM medical_info m join studeprofile p on m.StudentNumber=p.StudentNumber where medID='" . $id . "'");
		return $query->result();
	}

	function incidents()
	{
		$query = $this->db->query("SELECT * FROM guidance_incidents m join studeprofile p on m.StudentNumber=p.StudentNumber");
		return $query->result();
	}

	function incidentsInd($id)
	{
		$query = $this->db->query("SELECT * FROM guidance_incidents m join studeprofile p on m.StudentNumber=p.StudentNumber where incID='" . $id . "'");
		return $query->result();
	}

	function counselling()
	{
		$query = $this->db->query("SELECT * FROM guidance_counselling m join studeprofile p on m.StudentNumber=p.StudentNumber");
		return $query->result();
	}

	function counsellingInd($id)
	{
		$query = $this->db->query("SELECT * FROM guidance_counselling m join studeprofile p on m.StudentNumber=p.StudentNumber where id='" . $id . "'");
		return $query->result();
	}

	function medRecords()
	{
		$query = $this->db->query("SELECT * FROM medical_records m join studeprofile p on m.StudentNumber=p.StudentNumber");
		return $query->result();
	}

	function medRecordsInd($id)
	{
		$query = $this->db->query("SELECT * FROM medical_records m join studeprofile p on m.StudentNumber=p.StudentNumber where mrID='" . $id . "'");
		return $query->result();
	}


	//VIEW DENIED ENROLLEES ---------------------------------------------------------------------------------
	function deniedEnrollees($sem, $sy)
	{
		$query = $this->db->query("Select * from online_enrollment_deny where sem='" . $sem . "' and sy='" . $sy . "'");
		return $query->result();
	}

	//VIEW DENIED PAYMENTS ---------------------------------------------------------------------------------
	function deniedPayments()
	{
		$query = $this->db->query("SELECT o.StudentNumber, p.LastName, p.FirstName, p.MiddleName, o.denyReason, o.deniedDate FROM studeprofile p join online_pay_deny o on p.StudentNumber=o.StudentNumber order by o.id desc");
		return $query->result();
	}

	//VOID ORS ---------------------------------------------------------------------------------
	function voidORs()
	{
		$query = $this->db->query("SELECT * FROM voidreceipts order by ORNumber desc");
		return $query->result();
	}

	//VIEW REQUIREMENTS ---------------------------------------------------------------------------------
	function requirements($id)
	{
		$query = $this->db->query("Select * from online_requirements where StudentNumber='" . $id . "' order by fileAttachment");
		return $query->result();
	}

	//USER ACCOUNTS ---------------------------------------------------------------------------------
	function viewAccounts()
	{
		$query = $this->db->query("SELECT * FROM users_online order by lName");
		return $query->result();
	}

	function viewAccountsID($id)
	{
		$query = $this->db->query("SELECT * FROM users_online where username='" . $id . "'");
		return $query->result();
	}

	//STUDENTS REQUEST ---------------------------------------------------------------------------------
	function studerequest($id)
	{
		$query = $this->db->query("SELECT * FROM stude_request where StudentNumber='" . $id . "' order by dateReq desc");
		return $query->result();
	}

	function studerequest1()
	{
		$query = $this->db->query("SELECT * FROM stude_request order by dateReq desc");
		return $query->result();
	}





	//Released Request
	function totalReleased()
	{
		$query = $this->db->query("SELECT ongoingStat, count(ongoingStat) as requestCounts FROM stude_request_stat where ongoingStat='Released' group by ongoingStat");
		return $query->result();
	}

	//Released Request
	function releasedRequest()
	{
		$query = $this->db->query("select * from stude_request sr join stude_request_stat st on sr.trackingNo=st.trackingNo join studeprofile p on st.StudentNumber=p.StudentNumber where st.ongoingStat='Released'");
		return $query->result();
	}

	function studerequestTracking($id)
	{
		$query = $this->db->query("SELECT * FROM stude_request sr join stude_request_stat st on sr.trackingNo=st.trackingNo where sr.trackingNo='" . $id . "' order by statID desc");
		return $query->result();
	}

	public function studeaccountById($studentNo, $sy = null, $sem = null)
	{
		$params = [$studentNo];
		$whereSySem = '';
		if (!empty($sy)) {
			$whereSySem .= ' AND sa.SY = ?';
			$params[] = $sy;
		}
		if (!empty($sem)) {
			$whereSySem .= ' AND sa.Sem = ?';
			$params[] = $sem;
		}

		$sql = "
        SELECT
            sa.StudentNumber,
            sa.SY,
            sa.Sem,

            MAX(sa.AcctTotal)  AS AcctTotal,
            MAX(sa.Discount)   AS Discount,

            COALESCE(p.paid, 0) AS TotalPayments,

            GREATEST(MAX(sa.AcctTotal) - MAX(sa.Discount) - COALESCE(p.paid, 0), 0) AS CurrentBalance,

            MAX(sp.FirstName)   AS FirstName,
            MAX(sp.MiddleName)  AS MiddleName,
            MAX(sp.LastName)    AS LastName,
            MAX(sp.Course)      AS Course

        FROM studeaccount sa

        LEFT JOIN (
            SELECT StudentNumber, SY, Sem, SUM(Amount) AS paid
            FROM paymentsaccounts
            WHERE TRIM(ORStatus) = 'Valid'
            GROUP BY StudentNumber, SY, Sem
        ) p
          ON p.StudentNumber = sa.StudentNumber
         AND p.SY            = sa.SY
         AND p.Sem           = sa.Sem

        LEFT JOIN studeprofile sp
          ON CONVERT(sp.StudentNumber USING utf8mb4) COLLATE utf8mb4_unicode_ci
           = CONVERT(sa.StudentNumber USING utf8mb4) COLLATE utf8mb4_unicode_ci

        WHERE sa.StudentNumber = ?
        {$whereSySem}

        GROUP BY sa.StudentNumber, sa.SY, sa.Sem
        ORDER BY sa.SY DESC,
                 FIELD(sa.Sem, 'First Semester', 'Second Semester', 'Summer');
    ";

		return $this->db->query($sql, $params)->result();
	}





	public function studepayments($studentno, $sem, $sy)
	{
		$this->db->select("
			s.StudentNumber,
			CONCAT(p.FirstName, ' ', p.LastName) AS StudentName,
			p.Course,
			s.PDate,
			s.ORNumber,
			FORMAT(s.Amount, 2) AS Amount,
			s.description,
			s.Sem,
			s.SY
		");
		$this->db->from('paymentsaccounts s');
		$this->db->join('studeprofile p', 'p.StudentNumber = s.StudentNumber');
		$this->db->where('s.StudentNumber', $studentno);
		$this->db->where('s.Sem', $sem);
		$this->db->where('s.SY', $sy);
		$this->db->where('s.CollectionSource !=', 'Services');
		$this->db->where('s.ORStatus', 'Valid');

		$query = $this->db->get();
		return $query->result();
	}


	//Student Grades
	public function studeGrades($studeno, $sem, $sy)
	{
		return $this->db
			->select([
				'g.*',
				's.StudentNumber',
				's.FirstName AS studFirstName',
				's.MiddleName AS studMiddleName',
				's.LastName AS studLastName',
				'st.FirstName AS instrFirstName',
				'st.MiddleName AS instrMiddleName',
				'st.LastName AS instrLastName',
				// Nice formatted instructor name: "Last, First M."
				'CONCAT(st.LastName, ", ", st.FirstName, IF(st.MiddleName IS NOT NULL AND st.MiddleName <> "", CONCAT(" ", LEFT(st.MiddleName,1), "."), "")) AS Instructor'
			])
			->from('grades g')
			->join('studeprofile s', 's.StudentNumber = g.StudentNumber', 'inner')
			->join('staff st', 'st.IDNumber = g.IDNumber', 'left') // left in case some grade rows have no IDNumber yet
			->where('s.StudentNumber', $studeno)
			->where('g.Semester', $sem)
			->where('g.SY', $sy)
			->order_by('g.Description') // optional
			->get()
			->result();
	}


	//Student COR
	public function studeCOR($studeno, $sem, $sy)
	{
		$this->db
			->select('s.*, r.*')
			->select('st.FirstName  AS staffFirstName')
			->select('st.MiddleName AS staffMiddleName')
			->select('st.LastName   AS staffLastName')
			// optional: a single formatted field
			->select("TRIM(CONCAT(st.LastName, ', ', st.FirstName, ' ', COALESCE(st.MiddleName, ''))) AS staffFullName", false)
			->from('studeprofile s')
			->join('registration r', 'r.StudentNumber = s.StudentNumber', 'inner')
			->join('staff st', 'st.IDNumber = r.IDNumber', 'left') // << LEFT JOIN staff
			->where('s.StudentNumber', $studeno)
			->where('r.Sem', $sem)
			->where('r.SY', $sy);

		$query = $this->db->get();
		return $query->result();
	}


	//FTE Records
	public function fteRecords($sem, $sy, $course, $major, $yearlevel)
	{
		$this->db->select('sp.LastName, sp.FirstName, sp.MiddleName, r.Sem, r.SY, r.Course, r.Major, r.YearLevel');
		$this->db->select_sum('r.LecUnit', 'LecUnit');
		$this->db->select_sum('r.LabUnit', 'LabUnit');
		$this->db->from('registration r');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = r.StudentNumber');
		$this->db->where('r.Sem', $sem);
		$this->db->where('r.SY', $sy);
		$this->db->where('r.Course', $course);

		if (!empty($major)) {
			$this->db->where('r.Major', $major);
		}

		$this->db->where('r.YearLevel', $yearlevel);
		$this->db->group_by('r.StudentNumber');
		$this->db->order_by('sp.LastName');

		$query = $this->db->get();
		return $query->result();
	}




	public function getMajorsByCoursesum($courseDescription)
	{
		$this->db->select('Major');
		$this->db->from('course_table');
		$this->db->where('CourseDescription', $courseDescription);
		$this->db->where('Major !=', '');
		$this->db->group_by('Major');
		$this->db->order_by('Major', 'ASC');
		$query = $this->db->get();
		return $query->result();
	}



	//Display Students Profile
	public function displayrecordsById($studentNumber)
	{
		// 1) Bring everything from studeprofile so fields exist in the result
		$this->db->select('sp.*');

		// 2) Override commonly displayed identity fields with your preferred COALESCE logic
		//    (these will overwrite sp.FirstName/MiddleName/LastName in the result set)
		$this->db->select('ou.username AS StudentNumber', false); // override sp.StudentNumber
		$this->db->select('COALESCE(NULLIF(sp.FirstName,  ""), ou.fName) AS FirstName', false);
		$this->db->select('COALESCE(NULLIF(sp.MiddleName, ""), ou.mName) AS MiddleName', false);
		$this->db->select('COALESCE(NULLIF(sp.LastName,   ""), ou.lName) AS LastName', false);
		$this->db->select('COALESCE(NULLIF(sp.email, ""), ou.email) AS email', false);

		// 3) Make sure often-used optional fields exist (fallback to empty string)
		//    (Add more lines like these if other view fields pop warnings)
		$this->db->select('COALESCE(sp.skills, "") AS skills', false);
		$this->db->select('COALESCE(sp.admissionBasis, "") AS admissionBasis', false);
		$this->db->select('COALESCE(sp.lastSchoolDate, "") AS lastSchoolDate', false);
		$this->db->select('COALESCE(sp.honors, "") AS honors', false);

		// 4) Keep your other explicitly selected fields if you want (redundant with sp.* but harmless)
		$this->db->select('sp.birthDate, sp.age, sp.Sex, sp.CivilStatus');
		$this->db->select('sp.contactNo');
		$this->db->select('sp.father, sp.fOccupation, sp.mother, sp.mOccupation');
		$this->db->select('sp.guardian, sp.guardianContact, sp.guardianAddress');
		$this->db->select('sp.sitioPresent, sp.brgyPresent, sp.cityPresent, sp.provincePresent');

		$this->db->from('o_users ou');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = ou.username', 'left');
		$this->db->where('ou.username', $studentNumber);
		$this->db->limit(1);

		// Return array (your view uses $data[0]); switch to ->row() if you later change the view
		return $this->db->get()->result();
	}


	public function getMyProfileBundle($studentNumber)
	{
		$studentNumber = trim((string)$studentNumber);

		$account = $this->db->select('username, fName, mName, lName, email, avatar, position, acctStat')
			->from('o_users')
			->where('username', $studentNumber)
			->limit(1)
			->get()
			->row();
		if (!$account) {
			$account = (object)[
				'username' => $studentNumber,
				'fName'    => '',
				'mName'    => '',
				'lName'    => '',
				'email'    => '',
				'avatar'   => '',
				'position' => '',
				'acctStat' => ''
			];
		}

		$profile = $this->db->select('StudentNumber, contactNo, Sex, CivilStatus, birthDate, BirthPlace, age, course, major, yearLevel, sitio, brgy, city, province, email, nationality, working, VaccStat')
			->from('studeprofile')
			->where('StudentNumber', $studentNumber)
			->limit(1)
			->get()
			->row();
		if (!$profile) {
			$profile = (object)[
				'StudentNumber' => $studentNumber,
				'contactNo'     => '',
				'Sex'           => '',
				'CivilStatus'   => '',
				'birthDate'     => '',
				'BirthPlace'    => '',
				'age'           => '',
				'course'        => '',
				'major'         => '',
				'yearLevel'     => '',
				'section'       => '',
				'sitio'         => '',
				'brgy'          => '',
				'city'          => '',
				'province'      => '',
				'email'         => '',
				'nationality'   => '',
				'working'       => '',
				'VaccStat'      => ''
			];
		} elseif (!property_exists($profile, 'section')) {
			$profile->section = '';
		}
		foreach (['BirthPlace', 'age', 'sitio', 'brgy', 'city', 'province', 'email', 'nationality', 'working', 'VaccStat'] as $missingKey) {
			if (!property_exists($profile, $missingKey)) {
				$profile->{$missingKey} = '';
			}
		}
		if (!property_exists($profile, 'course')) {
			$profile->course = '';
		}
		if (!property_exists($profile, 'major')) {
			$profile->major = '';
		}
		if (!property_exists($profile, 'yearLevel')) {
			$profile->yearLevel = '';
		}

		$enrollment = $this->db->select('semstudentid, Course, Major, YearLevel, Section, Semester, SY')
			->from('semesterstude')
			->where('StudentNumber', $studentNumber)
			->order_by('semstudentid', 'DESC')
			->limit(1)
			->get()
			->row();
		if (!$enrollment) {
			$enrollment = (object)[
				'semstudentid' => null,
				'Course'       => $profile->course ?? '',
				'Major'        => $profile->major ?? '',
				'YearLevel'    => $profile->yearLevel ?? '',
				'Section'      => $profile->section ?? '',
				'Semester'     => null,
				'SY'           => null
			];
		} elseif (empty($enrollment->Section) && !empty($profile->section)) {
			$enrollment->Section = $profile->section;
		}

		return (object)[
			'account'    => $account,
			'profile'    => $profile,
			'enrollment' => $enrollment
		];
	}


	public function updateMyProfileBundle($studentNumber, array $accountData, array $profileData, array $enrollmentData, array $options = [])
	{
		$studentNumber = trim((string)$studentNumber);
		$semstudentId  = $options['semstudentid'] ?? null;
		$syOption      = $options['sy'] ?? null;
		$semOption     = $options['semester'] ?? null;

		$this->db->trans_start();

		if (!empty($accountData)) {
			$this->db->where('username', $studentNumber)->update('o_users', $accountData);
		}

		if (!empty($profileData)) {
			$cleanProfileData = [];
			foreach ($profileData as $key => $value) {
				$cleanProfileData[$key] = ($value === null) ? '' : $value;
			}
			$profileData = $cleanProfileData;

			$existsProfile = $this->db->select('StudentNumber')
				->from('studeprofile')
				->where('StudentNumber', $studentNumber)
				->limit(1)
				->get()
				->row();
			if ($existsProfile) {
				$this->db->where('StudentNumber', $studentNumber)->update('studeprofile', $profileData);
			} else {
				$settingsRow = $this->db->select('settingsID')->limit(1)->get('o_srms_settings')->row();
				$settingsID  = $settingsRow->settingsID ?? 1;

				$defaults = [
					'StudentNumber'        => $studentNumber,
					'FirstName'            => '',
					'MiddleName'           => '',
					'LastName'             => '',
					'nameExtn'             => '',
					'Sex'                  => '',
					'birthDate'            => '',
					'age'                  => '',
					'BirthPlace'           => '',
					'contactNo'            => '',
					'email'                => '',
					'CivilStatus'          => '',
					'ethnicity'            => '',
					'Religion'             => '',
					'working'              => 'No',
					'VaccStat'             => '',
					'province'             => '',
					'city'                 => '',
					'brgy'                 => '',
					'sitio'                => '',
					'course'               => '',
					'Major'                => '',
					'occupation'           => '',
					'salary'               => '',
					'employer'             => '',
					'employerAddress'      => '',
					'graduationDate'       => '',
					'guardian'             => '',
					'guardianRelationship' => '',
					'guardianContact'      => '',
					'guardianAddress'      => '',
					'spouse'               => '',
					'spouseRelationship'   => '',
					'spouseContact'        => '',
					'children'             => '',
					'imagePath'            => '',
					'yearLevel'            => '',
					'father'               => '',
					'fOccupation'          => '',
					'fatherAddress'        => '',
					'fatherContact'        => '',
					'mother'               => '',
					'mOccupation'          => '',
					'motherAddress'        => '',
					'motherContact'        => '',
					'disability'           => '',
					'parentsMonthly'       => '0',
					'elementary'           => '',
					'elementaryAddress'    => '',
					'elemGraduated'        => '',
					'secondary'            => '',
					'secondaryAddress'     => '',
					'secondaryGraduated'   => '',
					'vocational'           => '',
					'vocationalAddress'    => '',
					'vocationalGraduated'  => '',
					'vocationalCourse'     => '',
					'nationality'          => 'Filipino',
					'settingsID'           => $settingsID
				];

				$profileInsert = array_merge($defaults, $profileData);
				$profileInsert['StudentNumber'] = $studentNumber;
				$this->db->insert('studeprofile', $profileInsert);
			}
		}

		if (!empty($enrollmentData)) {
			if ($semstudentId) {
				$this->db->where('semstudentid', $semstudentId)
					->where('StudentNumber', $studentNumber)
					->update('semesterstude', $enrollmentData);
			} else {
				$existingEnrollment = $this->db->select('semstudentid')
					->from('semesterstude')
					->where('StudentNumber', $studentNumber)
					->order_by('semstudentid', 'DESC')
					->limit(1)
					->get()
					->row();
				if ($existingEnrollment) {
					$semstudentId = $existingEnrollment->semstudentid;
					$this->db->where('semstudentid', $semstudentId)
						->update('semesterstude', $enrollmentData);
				} else {
					$insertData = $enrollmentData;
					$insertData['StudentNumber'] = $studentNumber;
					if (!array_key_exists('SY', $insertData) || $insertData['SY'] === null) {
						if ($syOption !== null) {
							$insertData['SY'] = $syOption;
						}
					}
					if (!array_key_exists('Semester', $insertData) || $insertData['Semester'] === null) {
						if ($semOption !== null) {
							$insertData['Semester'] = $semOption;
						}
					}
					$this->db->insert('semesterstude', $insertData);
					$semstudentId = $this->db->insert_id();
				}
			}

			$profileSync = [];
			if (array_key_exists('Course', $enrollmentData)) {
				$profileSync['course'] = $enrollmentData['Course'];
			}
			if (array_key_exists('Major', $enrollmentData)) {
				$profileSync['major'] = $enrollmentData['Major'];
			}
			if (array_key_exists('YearLevel', $enrollmentData)) {
				$profileSync['yearLevel'] = $enrollmentData['YearLevel'];
			}
			if (!empty($profileSync)) {
				$this->db->where('StudentNumber', $studentNumber)->update('studeprofile', $profileSync);
			}
		}

		if (!empty($profileData) || !empty($enrollmentData)) {
			$signupExists = $this->db->select('StudentNumber')
				->from('studentsignup')
				->where('StudentNumber', $studentNumber)
				->limit(1)
				->get()
				->row();
			if ($signupExists) {
				$signupUpdate = [];

				if (!empty($profileData)) {
					$profileToSignup = [
						'FirstName'   => 'FirstName',
						'MiddleName'  => 'MiddleName',
						'LastName'    => 'LastName',
						'nameExtn'    => 'nameExtn',
						'Sex'         => 'Sex',
						'birthDate'   => 'birthDate',
						'age'         => 'age',
						'contactNo'   => 'contactNo',
						'email'       => 'email',
						'CivilStatus' => 'CivilStatus',
						'BirthPlace'  => 'BirthPlace',
						'sitio'       => 'sitio',
						'brgy'        => 'brgy',
						'city'        => 'city',
						'province'    => 'province',
						'nationality' => 'nationality',
						'working'     => 'working',
						'VaccStat'    => 'VaccStat'
					];
					foreach ($profileToSignup as $profileKey => $signupKey) {
						if (array_key_exists($profileKey, $profileData)) {
							$signupUpdate[$signupKey] = $profileData[$profileKey];
						}
					}
					if (array_key_exists('course', $profileData)) {
						$signupUpdate['Course1'] = $profileData['course'];
					}
					if (array_key_exists('yearLevel', $profileData)) {
						$signupUpdate['yearLevel'] = $profileData['yearLevel'];
					}
				}

				if (!empty($enrollmentData)) {
					if (array_key_exists('Course', $enrollmentData)) {
						$signupUpdate['Course1'] = $enrollmentData['Course'];
					}
					if (array_key_exists('Major', $enrollmentData)) {
						$signupUpdate['Major1'] = $enrollmentData['Major'];
					}
					if (array_key_exists('YearLevel', $enrollmentData)) {
						$signupUpdate['yearLevel'] = $enrollmentData['YearLevel'];
					}
					if (array_key_exists('Section', $enrollmentData)) {
						$signupUpdate['section'] = $enrollmentData['Section'];
					}
				}

				if (!empty($signupUpdate)) {
					$this->db->where('StudentNumber', $studentNumber)
						->update('studentsignup', $signupUpdate);
				}
			}
		}

		$this->db->trans_complete();
		$success = $this->db->trans_status();

		return [
			'success'      => $success,
			'semstudentid' => $success ? $semstudentId : null
		];
	}


	//Display Staff Profile
	function staffProfile($id)
	{
		$this->db->where('IDNumber', $id);
		$query = $this->db->get('staff');
		return $query->result();
	}


	function getOR()
	{
		$query = $this->db->query("select * from paymentsaccounts order by ID desc limit 1");
		return $query->result();
	}

	function UploadedPayments($id, $sem, $sy)
	{
		$query = $this->db->query("select * from online_payments where StudentNumber='" . $id . "' and sy='" . $sy . "' and sem='" . $sem . "'");
		return $query->result();
	}


	// application/models/StudentModel.php
	public function UploadedPaymentsAdmin($sem, $sy)
	{
		// Subquery: collapse studeaccount to ONE row per StudentNumber+SY+Sem
		$saSub = "(SELECT
                  CONVERT(StudentNumber USING utf8mb4) AS StudentNumber,
                  SY,
                  Sem,
                  MAX(Course)    AS Course,
                  MAX(YearLevel) AS YearLevel
               FROM studeaccount
               GROUP BY StudentNumber, SY, Sem) sa";

		return $this->db->select("
            sp.LastName,
            sp.FirstName,
            sp.MiddleName,
            op.depositAttachment,
            op.amount                 AS amountPaid,
            op.description            AS payment_for,
            op.sy,
            op.sem,
            op.created_at             AS date_uploaded,
            op.note,
            op.id                     AS opID,
            op.StudentNumber,
            op.refNo,
            op.status,
            op.email,
            sa.Course, 
            sa.YearLevel
        ")
			->from('online_payments AS op')
			// Collation-safe join to studeprofile (names)
			->join(
				'studeprofile AS sp',
				'sp.StudentNumber COLLATE utf8mb4_unicode_ci = op.StudentNumber COLLATE utf8mb4_unicode_ci',
				'left',
				false
			)
			// Collation-safe join to aggregated studeaccount (course/year level)
			->join(
				$saSub,
				"sa.StudentNumber = op.StudentNumber COLLATE utf8mb4_unicode_ci
             AND sa.SY  = op.sy
             AND sa.Sem = op.sem",
				'left',
				false
			)
			->where('op.status', 'PENDING')
			->where('op.sy',  $sy)
			->where('op.sem', $sem)
			// honor your hide flag if any
			->group_start()
			->where('op.show_online_payments IS NULL', null, false)
			->or_where('op.show_online_payments <>', 'HIDE')
			->group_end()
			->order_by('op.created_at', 'DESC')
			->get()
			->result();
	}



	public function onlinePaymentsAll($sy = null, $sem = null)
	{
		// Only show payments that have a matching Accounting OR and are verified/posted
		$this->db->select([
			'p.LastName',
			'p.FirstName',
			'op.depositAttachment',
			'pa.Amount AS amountPaid',        // Amount from Accounting (official OR)
			'pa.description AS payment_for',  // Description from Accounting
			'pa.SY AS sy',                    // SY from Accounting
			'pa.Sem AS sem',                  // Semester from Accounting
			'op.created_at AS date_uploaded', // Date student uploaded
			'pa.ORStatus AS status',          // Status from Accounting
			'op.refNo',
			'pa.ORNumber'
		], false);

		$this->db->from('online_payments op');
		$this->db->join('studeprofile p', 'p.StudentNumber = op.StudentNumber', 'left');

		// KEY JOIN: link the student upload to the Accounting OR
		// - refNo (student upload) should match ORNumber (Accounting)
		// - Same student (safety)
		// - Same term if present (sy/sem)
		$this->db->join(
			'paymentsaccounts pa',
			"pa.StudentNumber = op.StudentNumber
         AND pa.ORNumber = op.refNo
         AND (pa.SY = op.sy OR op.sy IS NULL OR op.sy = '')
         AND (pa.Sem = op.sem OR op.sem IS NULL OR op.sem = '')",
			'inner'
		);

		// Show only verified/posted entries from Accounting
		// 👉 Adjust these values to your actual statuses if different
		$this->db->where_in('pa.ORStatus', ['VERIFIED', 'POSTED', 'PAID']);

		// Limit to the active term (recommended)
		if (!empty($sy)) {
			$this->db->where('pa.SY',  $sy);
		}
		if (!empty($sem)) {
			$this->db->where('pa.Sem', $sem);
		}

		// If you use a toggle in online_payments to hide/show, you can add this:
		// $this->db->where('op.show_online_payments', 'Yes');

		// Avoid duplicates if ever (shouldn’t be needed but safe)
		$this->db->group_by('op.id');

		$this->db->order_by('op.created_at', 'DESC');

		return $this->db->get()->result();
	}


	function displayenrollees()
	{
		$query = $this->db->query("select * from online_enrollment order by LastName");
		return $query->result();
	}

	//Chart of Enrollment
	function chartEnrollment()
	{
		$query = $this->db->query("SELECT concat(Semester,', ',SY) as Sem, count(Semester) as Counts FROM semesterstude group by Sem");
		return $query->result();
	}

	//Counts of Teachers
	function teachersCount()
	{
		$query = $this->db->query("SELECT count(IDNumber) as staffCount FROM staff");
		return $query->result();
	}

	//Counts for Validation
	function forValidationCounts($Semester, $SY)
	{
		$this->db->select('COUNT(oe.StudentNumber) as StudeCount');
		$this->db->from('online_enrollment oe');
		$this->db->join('studeprofile p', 'oe.StudentNumber = p.StudentNumber');
		$this->db->where('oe.Semester', $Semester);
		$this->db->where('oe.SY', $SY);
		$this->db->where('oe.enrolStatus', 'For Validation');

		$query = $this->db->get();
		return $query->result();
	}


	//For payment verification count
	// function forPaymentVerCount($sy, $sem)
	// {
	// 	$this->db->select('COUNT(o.StudentNumber) as Studecount');
	// 	$this->db->from('online_payments o');
	// 	$this->db->join('studeprofile p', 'o.StudentNumber = p.StudentNumber');
	// 	$this->db->where('o.sy', $sy);
	// 	$this->db->where('o.sem', $sem);
	// 	$this->db->where('o.status', 'PENDING');

	// 	$query = $this->db->get();
	// 	return $query->result();
	// }

	public function forPaymentVerCount($sy, $sem)
	{
		return $this->db->select('COUNT(*) as Studecount')
			->from('online_payments')
			->where('status', 'PENDING')
			->where('sy', $sy)
			->where('sem', $sem)
			->get()
			->result();
	}


	//First Year Counts
	function enrolledFirst($sy, $sem)
	{
		$this->db->select('COUNT(StudentNumber) as StudeCount, SY, Semester, YearLevel, Course');
		$this->db->from('semesterstude');
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		$this->db->where('Status', 'Enrolled');
		$this->db->where('YearLevel', '1st');

		$query = $this->db->get();
		return $query->result();
	}


	//Second Year Counts
	function enrolledSecond($sy, $sem)
	{
		$this->db->select('COUNT(StudentNumber) as StudeCount, SY, Semester, YearLevel, Course');
		$this->db->from('semesterstude');
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		$this->db->where('Status', 'Enrolled');
		$this->db->where('YearLevel', '2nd');

		$query = $this->db->get();
		return $query->result();
	}


	//Incidents
	function incidentsCounts()
	{
		$query = $this->db->query("SELECT count(incID) as StudeCount FROM guidance_incidents");
		return $query->result();
	}

	//counselling
	function counsellingCounts()
	{
		$query = $this->db->query("SELECT count(id) as StudeCount FROM guidance_counselling");
		return $query->result();
	}

	//medicalInfo
	function medInfoCounts()
	{
		$query = $this->db->query("SELECT count(medID) as StudeCount FROM medical_info");
		return $query->result();
	}

	//medicalRecords
	function medRecordsCounts()
	{
		$query = $this->db->query("SELECT count(mrID) as StudeCount FROM medical_records");
		return $query->result();
	}


	//Third Year Counts
	function enrolledThird($sy, $sem)
	{
		$this->db->select('COUNT(StudentNumber) as StudeCount, SY, Semester, YearLevel, Course');
		$this->db->from('semesterstude');
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		$this->db->where('Status', 'Enrolled');
		$this->db->where('YearLevel', '3rd');

		$query = $this->db->get();
		return $query->result();
	}

	//Fourth Year Counts
	function enrolledFourth($sy, $sem)
	{
		$this->db->select('COUNT(StudentNumber) as StudeCount, SY, Semester, YearLevel, Course');
		$this->db->from('semesterstude');
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		$this->db->where('Status', 'Enrolled');
		$this->db->where('YearLevel', '4th');

		$query = $this->db->get();
		return $query->result();
	}


	//Semester Enrollees
	function getEnrolled($course, $yearlevel)
	{
		$this->db->select('*');
		if ($course)
			$this->db->where('Course', $course);
		if ($yearlevel)
			$this->db->where('YearLevel', $yearlevel);
		$query = $this->db->get('semesterstude');
		return $query->result();
	}

	//Course Count Summary Per Semester
	function dailyEnrollStat()
	{
		$query = $this->db->query("SELECT Status, count(Status)as Counts FROM semesterstude where DAY(enroledDate)=DAY(NOW()) and MONTH(enroledDate)=MONTH(NOW()) and YEAR(enroledDate)=YEAR(NOW()) group by Status");
		return $query->result();
	}
	//Payment Summary Per Semester
	function paymentSummary($sem, $sy)
	{
		$query = $this->db->query("SELECT CollectionSource, sum(Amount) as Amount FROM paymentsaccounts where ORStatus='Valid' and Sem='" . $sem . "' and SY='" . $sy . "' group by CollectionSource");
		return $query->result();
	}
	//Birthday Celebrants
	function birthdayCelebs($sem, $sy)
	{
		$query = $this->db->query("SELECT concat(p.LastName,', ',p.FirstName,' ',p.MiddleName) as StudeName, p.BirthDate FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where DAY(p.BirthDate)=DAY(NOW()) and MONTH(p.BirthDate)=MONTH(NOW()) and ss.Semester='" . $sem . "' and ss.SY='" . $sy . "'");
		return $query->result();
	}
	//Birthday Celebrants
	function birthdayMonths($sem, $sy)
	{
		$query = $this->db->query("SELECT concat(p.LastName,', ',p.FirstName,' ',p.MiddleName) as StudeName, Day(p.BirthDate) as Day, MONTH(p.BirthDate) as Month FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where MONTH(p.BirthDate)=MONTH(NOW()) and ss.Semester='" . $sem . "' and ss.SY='" . $sy . "' order by Day");
		return $query->result();
	}

	//Quick Today's Collection
	function collectionToday()
	{
		$query = $this->db->query("SELECT sum(Amount) as Amount FROM paymentsaccounts where ORStatus='Valid' and DAY(PDate)=DAY(NOW()) and MONTH(PDate)=MONTH(NOW()) and YEAR(PDate)=YEAR(NOW())");
		return $query->result();
	}
	//Quick This Month's Collection
	function collectionMonth()
	{
		$query = $this->db->query("SELECT sum(Amount) as Amount FROM paymentsaccounts where ORStatus='Valid' and MONTH(PDate)=MONTH(NOW()) and YEAR(PDate)=YEAR(NOW())");
		return $query->result();
	}
	//Quick This Year's Collection
	function YearlyCollections()
	{
		$query = $this->db->query("SELECT sum(Amount) as Amount FROM paymentsaccounts where ORStatus='Valid' and YEAR(PDate)=YEAR(NOW())");
		return $query->result();
	}
	//Course Count Summary Per Semester
	function CourseCount($sem, $sy)
	{
		$courseExpr = "CASE WHEN NULLIF(TRIM(s.Course),'') IS NULL THEN 'Not Set' ELSE TRIM(s.Course) END";

		return $this->db->select("$courseExpr AS Course, COUNT(DISTINCT s.StudentNumber) as Counts", false)
			->from('semesterstude s')
			->where('s.SY', $sy)
			->where('s.Semester', $sem)
			->where('s.Status', 'Enrolled')
			->group_by($courseExpr, false)
			->order_by('Course', 'ASC')
			->get()
			->result();
	}



	// Replaces the old SexCount($sem, $sy)
	public function SexCount($sem = null, $sy = null)
	{
		// Normalize common variants to Male / Female / Unspecified
		// Handles: M, F, Male, Female, mixed case, extra spaces, blanks/nulls
		$normalized = "
        CASE
          WHEN UPPER(TRIM(Sex)) IN ('M','MALE') THEN 'Male'
          WHEN UPPER(TRIM(Sex)) IN ('F','FEMALE') THEN 'Female'
          ELSE 'Unspecified'
        END
    ";

		return $this->db->select("$normalized AS Sex, COUNT(*) AS Counts", false)
			->from('studentsignup')
			// NOTE: studentsignup doesn't have SY/Semester columns.
			// If you want “current term only”, filter by EnrollmentDate range here.
			// ->where('EnrollmentDate >=', $from) 
			// ->where('EnrollmentDate <=', $to)
			->group_by('Sex')
			->order_by('Sex')
			->get()
			->result();
	}

	//Sex Summary
	function sexList($sem, $sy, $sex)
	{
		$query = $this->db->query("SELECT p.StudentNumber, p.FirstName, p.MiddleName, p.LastName, ss.Course, ss.YearLevel, p.Sex FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' and p.Sex='" . $sex . "'");
		return $query->result();
	}

	//City List Summary
	function cityList($sem, $sy, $city)
	{
		$query = $this->db->query("SELECT p.StudentNumber, p.FirstName, p.MiddleName, p.LastName, ss.Course, ss.YearLevel, p.city FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' and p.city='" . $city . "' order by p.LastName");
		return $query->result();
	}

	//Ethnicity List Summary
	function ethnicityList($sem, $sy, $ethnicity)
	{
		$query = $this->db->query("SELECT p.StudentNumber, p.FirstName, p.MiddleName, p.LastName, ss.Course, ss.YearLevel, p.ethnicity FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' and p.ethnicity='" . $ethnicity . "' order by p.LastName");
		return $query->result();
	}

	//Religion List Summary
	function religionList($sem, $sy, $religion)
	{
		$query = $this->db->query("SELECT p.StudentNumber, p.FirstName, p.MiddleName, p.LastName, ss.Course, ss.YearLevel, p.Religion FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' and p.Religion='" . $religion . "' order by p.LastName");
		return $query->result();
	}
	//Count by Religion
	function religionCount($sem, $sy)
	{
		$query = $this->db->query("SELECT p.Religion, count(p.Religion) as Counts FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' group by p.Religion");
		return $query->result();
	}
	//Count by Ethnicity
	function ethnicityCount($sem, $sy)
	{
		$query = $this->db->query("SELECT p.Ethnicity, count(p.Ethnicity) as Counts FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' group by p.Ethnicity");
		return $query->result();
	}
	//Count by City
	function cityCount($sem, $sy)
	{
		$query = $this->db->query("SELECT p.city, count(p.city) as Counts FROM studeprofile p join semesterstude ss on p.StudentNumber=ss.StudentNumber where ss.SY='" . $sy . "' and ss.Semester='" . $sem . "' group by p.city");
		return $query->result();
	}
	//Student's List
	public function getProfile()
	{
		$this->db->from('studeprofile');
		$this->db->order_by('LastName', 'ASC');
		$query = $this->db->get();
		return $query->result();
	}


	public function getsignProfile()
	{
		// Joins are plain equality on purpose. Wrapping both sides in
		// LOWER(TRIM(...)) made every one of these joins unindexable and cost
		// ~6.5s on 2,400 rows; the columns hold no untrimmed values and the
		// collations are already case-insensitive, so it bought nothing.
		// o_users.username is latin1 while the StudentNumber columns are
		// utf8mb4 — CONVERT keeps each comparison in the charset of the side
		// being looked up, so that side's index stays usable.
		$sql = "
			SELECT
				TRIM(COALESCE(NULLIF(s.LastName, ''), NULLIF(sp.LastName, ''), NULLIF(ou.lName, ''), ''))   AS LastName,
				TRIM(COALESCE(NULLIF(s.FirstName, ''), NULLIF(sp.FirstName, ''), NULLIF(ou.fName, ''), '')) AS FirstName,
				TRIM(COALESCE(NULLIF(s.MiddleName, ''), NULLIF(sp.MiddleName, ''), NULLIF(ou.mName, ''), '')) AS MiddleName,
				TRIM(s.StudentNumber)                                                                     AS StudentNumber,
				CASE
					WHEN s.birthDate IS NULL
						OR s.birthDate = '0000-00-00'
					THEN NULL
					ELSE DATE_FORMAT(s.birthDate, '%Y-%m-%d')
				END                                                                                   AS birthDate,
				NULLIF(s.yearLevel, '')                                                                    AS yearLevel,
				NULLIF(s.section, '')                                                                      AS section,
				NULLIF(s.Status, '')                                                                       AS signupStatus,
				'studentsignup'                                                                            AS source_table
			FROM studentsignup s
			LEFT JOIN o_users ou
			  ON ou.username = CONVERT(s.StudentNumber USING latin1)
			LEFT JOIN studeprofile sp
			  ON sp.StudentNumber = s.StudentNumber

			UNION ALL

			SELECT
				TRIM(COALESCE(NULLIF(sp.LastName, ''), NULLIF(ou.lName, ''), ''))   AS LastName,
				TRIM(COALESCE(NULLIF(sp.FirstName, ''), NULLIF(ou.fName, ''), '')) AS FirstName,
				TRIM(COALESCE(NULLIF(sp.MiddleName, ''), NULLIF(ou.mName, ''), '')) AS MiddleName,
				TRIM(ou.username)                                                  AS StudentNumber,
				CASE
					WHEN sp.birthDate IS NULL
						OR sp.birthDate = '0000-00-00'
					THEN NULL
					ELSE DATE_FORMAT(sp.birthDate, '%Y-%m-%d')
				END                                                                   AS birthDate,
				NULL                                                               AS yearLevel,
				NULL                                                               AS section,
				COALESCE(NULLIF(ou.acctStat, ''), 'Registered')                    AS signupStatus,
				'o_users'                                                          AS source_table
			FROM o_users ou
			LEFT JOIN studeprofile sp
			  ON sp.StudentNumber = CONVERT(ou.username USING utf8mb4) COLLATE utf8mb4_unicode_ci
			WHERE ou.position IN ('Student', 'Stude Applicant')
			  AND NOT EXISTS (
				  SELECT 1
				  FROM studentsignup s
				  WHERE s.StudentNumber = CONVERT(ou.username USING utf8mb4) COLLATE utf8mb4_unicode_ci
			  )
		";

		$wrapped = "
			SELECT *
			FROM ({$sql}) AS combined
			ORDER BY
				COALESCE(LastName, ''),
				COALESCE(FirstName, ''),
				StudentNumber
		";

		return $this->db->query($wrapped)->result();
	}

	public function getDuplicateStudentsByName()
	{
		// Use the same source as profileList (studentsignup + o_users fallback)
		// so duplicates shown here match what admins see in profileList.
		$rows = $this->getsignProfile();
		$groupsFull = [];
		$groupsLastFirst = [];

		$normalize = static function ($value) {
			$v = (string)$value;
			// Normalize non-breaking spaces and repeated whitespace.
			$v = str_replace("\xc2\xa0", ' ', $v);
			$v = preg_replace('/\s+/', ' ', trim($v));
			// Ignore common punctuation differences while grouping.
			$v = str_replace(['.', ',', "'", '"'], '', $v);
			return strtolower($v);
		};

		foreach ($rows as $row) {
			$last = $normalize($row->LastName ?? '');
			$first = $normalize($row->FirstName ?? '');
			$middle = $normalize($row->MiddleName ?? '');

			if ($last === '' || $first === '') {
				continue;
			}

			$keyFull = $last . '|' . $first . '|' . $middle;
			if (!isset($groupsFull[$keyFull])) {
				$groupsFull[$keyFull] = [];
			}
			$groupsFull[$keyFull][] = $row;

			$keyLastFirst = $last . '|' . $first;
			if (!isset($groupsLastFirst[$keyLastFirst])) {
				$groupsLastFirst[$keyLastFirst] = [];
			}
			$groupsLastFirst[$keyLastFirst][] = $row;
		}

		$result = [];
		$included = [];

		// Priority: exact full-name duplicates (including MiddleName).
		foreach ($groupsFull as $groupRows) {
			$dupCount = count($groupRows);
			if ($dupCount <= 1) {
				continue;
			}

			foreach ($groupRows as $row) {
				$row->duplicate_count = $dupCount;
				$key = (string)($row->StudentNumber ?? '');
				$included[$key] = true;
				$result[] = $row;
			}
		}

		// Fallback: same LastName + FirstName (covers slight MiddleName differences).
		foreach ($groupsLastFirst as $groupRows) {
			$dupCount = count($groupRows);
			if ($dupCount <= 1) {
				continue;
			}
			foreach ($groupRows as $row) {
				$key = (string)($row->StudentNumber ?? '');
				if (isset($included[$key])) {
					continue;
				}
				$row->duplicate_count = $dupCount;
				$included[$key] = true;
				$result[] = $row;
			}
		}

		usort($result, static function ($a, $b) {
			$la = strtolower(trim((string)($a->LastName ?? '')));
			$lb = strtolower(trim((string)($b->LastName ?? '')));
			if ($la !== $lb) {
				return $la <=> $lb;
			}

			$fa = strtolower(trim((string)($a->FirstName ?? '')));
			$fb = strtolower(trim((string)($b->FirstName ?? '')));
			if ($fa !== $fb) {
				return $fa <=> $fb;
			}

			$ma = strtolower(trim((string)($a->MiddleName ?? '')));
			$mb = strtolower(trim((string)($b->MiddleName ?? '')));
			if ($ma !== $mb) {
				return $ma <=> $mb;
			}

			return strcmp((string)($a->StudentNumber ?? ''), (string)($b->StudentNumber ?? ''));
		});

		return $result;
	}



	public function getSignupStudentByNumber($studentNumber)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('Status', 'for Verification');
		return $this->db->get('studentsignup')->row();
	}


	public function insertStudeProfile($data)
	{
		return $this->db->insert('studeprofile', $data);
	}


	function signUpList()
	{
		$query = $this->db->query("select * from studentsignup order by signupID desc");
		return $query->result();
	}

	function getInventoryCategory()
	{
		$this->db->distinct();
		$this->db->select('Category');
		$this->db->from('ls_categories');
		$this->db->order_by('Category');

		$query = $this->db->get();
		return $query->result();
	}



	function getOffice()
	{
		$this->db->distinct(); // Ensure unique office values
		$this->db->select('office'); // Only select the 'office' column
		$this->db->from('ls_office');
		$this->db->order_by('office');

		$query = $this->db->get();
		return $query->result();
	}

	function getInventory()
	{
		$this->db->select('ls_items.*, staff.FirstName, staff.MiddleName, staff.LastName'); // Select all from ls_items and relevant columns from staff
		$this->db->from('ls_items');
		$this->db->join('staff', 'ls_items.IDNumber = staff.IDNumber'); // Join on accountable from ls_items and IDNumber from staff
		$this->db->order_by('staff.FirstName, staff.MiddleName, staff.LastName'); // Order by staff name fields

		$query = $this->db->get();
		return $query->result();
	}

	function inventorySummary()
	{
		$this->db->select('itemName, SUM(qty) as itemCount');
		$this->db->from('ls_items');
		$this->db->group_by('itemName');

		$query = $this->db->get();
		return $query->result();
	}


	function getInventoryAccountable($accountable)
	{
		$this->db->select('ls_items.*, staff.FirstName, staff.MiddleName, staff.LastName'); // Select required columns
		$this->db->from('ls_items');
		$this->db->join('staff', 'staff.IDNumber = ls_items.IDNumber'); // Join on IDNumber
		$this->db->where('ls_items.IDNumber', $accountable); // Filter by accountable ID

		$query = $this->db->get();
		return $query->result();
	}


	function inventorySummaryAccountable($accountable)
	{
		$this->db->select('ls_items.itemName, SUM(ls_items.qty) as itemCount, staff.FirstName, staff.MiddleName, staff.LastName');
		$this->db->from('ls_items');
		$this->db->join('staff', 'staff.IDNumber = ls_items.IDNumber'); // Join on IDNumber
		$this->db->where('ls_items.IDNumber', $accountable);
		$this->db->group_by('ls_items.itemName');

		$query = $this->db->get();
		return $query->result();
	}



	//Student's List
	function teachers()
	{
		$query = $this->db->query("select * from staff order by LastName");
		return $query->result();
	}

	function honor_dis($StudeNo)
	{
		$query = $this->db->query("select p.StudentNumber, p.Title, p.Pronoun, p.Pronoun2, p.Pronoun3, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as StudeName, s.Course, s.YearLevel, s.Semester, s.SY, s.YearLevel from studeprofile p join semesterstude s on p.StudentNumber=s.StudentNumber where s.StudentNumber='" . $StudeNo . "' order by s.semstudentid desc limit 1");
		return $query->result();
	}

	// function studProf($StudeNo)
	// {
	// 	$query=$this->db->query("select p.StudentNumber, p.Title, p.Pronoun, p.Pronoun2, p.Pronoun3, p.LastName, p.FirstName, p.MiddleName, p.occupation, p.sitioPresent, p.sitio, p.brgyPresent, p.cityPresent, p.Sex, p.CivilStatus, p.nationality, p.contactNo, p.email, p.birthDate, p.age, p.provincePresent, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as StudeName, s.Course, s.YearLevel, s.Semester, s.SY, s.YearLevel from studeprofile p join semesterstude s on p.StudentNumber=s.StudentNumber where s.StudentNumber='".$StudeNo."' order by p.StudentNumber");
	// 	return $query->result();
	// }

	function studProf($StudeNo)
	{
		$query = $this->db->query("SELECT * FROM studeprofile");
		return $query->result();
	}

	function report_coe($StudeNo, $sy, $sem)
	{
		$query = $this->db->query("Select p.StudentNumber, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as studeName, r.Sem, r.SY, r.YearLevel, r.Course, Major, SubjectCode, Description, LecUnit, LabUnit from studeprofile p join registration r on p.StudentNumber=r.StudentNumber where p.StudentNumber='" . $StudeNo . "' and r.Sem='" . $sem . "' and r.SY='" . $sy . "' order by r.SubjectCode");
		return $query->result();
	}


	function report_coR($StudeNo, $sy, $sem)
	{
		$query = $this->db->query("Select p.StudentNumber, p.birthDate, p.Sex, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as studeName, r.Sem, r.SY, r.Room, r.Instructor, r.Section, r.schedType, r.SchedTime, r.totalUnits, r.YearLevel, r.Course, Major, SubjectCode, Description, LecUnit, LabUnit from studeprofile p join registration r on p.StudentNumber=r.StudentNumber where p.StudentNumber='" . $StudeNo . "' and r.Sem='" . $sem . "' and r.SY='" . $sy . "' order by r.SubjectCode");
		return $query->result();
	}

	function report_cogmc($StudeNo)
	{
		$query = $this->db->query("Select p.StudentNumber, p.Title, p.birthDate, p.Sex, p.LastName, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as studeName, r.Sem, r.SY, r.Room, r.Instructor, r.Section, r.schedType, r.SchedTime, r.totalUnits, r.YearLevel, r.Course, Major, SubjectCode, Description, LecUnit, LabUnit from studeprofile p join registration r on p.StudentNumber=r.StudentNumber where p.StudentNumber='" . $StudeNo . "' order by p.StudentNumber");
		return $query->result();
	}


	function report_rog($StudeNo, $sy, $sem)
	{
		$query = $this->db->query("Select p.StudentNumber, p.YearLevel, p.Title, p.birthDate, p.Sex, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as studeName, g.Semester, g.SY, g.Instructor, g.Section, g.Course, Major, SubjectCode, Description, LecUnit, LabUnit, g.Final from studeprofile p join grades g on p.StudentNumber=g.StudentNumber where p.StudentNumber='" . $StudeNo . "' and g.Semester='" . $sem . "' and g.SY='" . $sy . "' order by g.SubjectCode");
		return $query->result();
	}






	//For Enrollment
	function forValidation($Semester, $SY)
	{
		$query = $this->db->query("select * from studeprofile p join online_enrollment oe on p.StudentNumber=oe.StudentNumber where oe.Semester='" . $Semester . "' and oe.SY='" . $SY . "' and oe.enrolStatus='For Validation'");
		return $query->result();
	}

	//get the latest semester and reflect it on the proof_payment
	function getSemesterfromOE($id)
	{
		$query = $this->db->query("select * from online_enrollment where StudentNumber='" . $id . "' order by oeID desc limit 1");
		return $query->result();
	}

	//Slot Monitoring
	function slotsMonitoring($sem, $sy)
	{
		$this->db->select("r.regnumber, r.SubjectCode, r.Description, COUNT(*) AS Enrolled, r.Section, r.SchedTime, CONCAT(s.FirstName, ' ', s.MiddleName, ' ', s.LastName) AS Instructor, r.IDNumber, r.Sem, r.SY");
		$this->db->from('registration r');
		$this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');
		$this->db->where('r.Sem', $sem);
		$this->db->where('r.SY', $sy);
		$this->db->group_by(['r.SubjectCode', 'r.Section', 'r.IDNumber', 'r.SchedTime']);
		$this->db->order_by('r.SubjectCode');

		return $this->db->get()->result();
	}


	// public function getEnrolledStudents($SubjectCode, $Section, $Instructor, $SchedTime, $sy, $sem)
	// {
	// 	$this->db->where('SubjectCode', $SubjectCode);
	// 	$this->db->where('Section', $Section);
	// 	$this->db->where('Instructor', $Instructor);
	// 	$this->db->where('SchedTime', $SchedTime);
	// 	$this->db->where('sy', $sy);
	// 	$this->db->where('sem', $sem);
	// 	$query = $this->db->get('registration');
	// 	return $query->result();
	// }

	public function getEnrolledStudents($SubjectCode, $Section, $InstructorID, $SchedTime, $sy, $sem)
	{
		$this->db->select('r.*, CONCAT(s.FirstName, " ", s.MiddleName, " ", s.LastName) AS InstructorName');
		$this->db->from('registration r');
		$this->db->join('staff s', 'r.IDNumber = s.IDNumber', 'left');

		$this->db->where('r.SubjectCode', $SubjectCode);
		$this->db->where('r.Section', $Section);
		$this->db->where('r.IDNumber', $InstructorID); // match instructor by IDNumber
		$this->db->where('r.SchedTime', $SchedTime);
		$this->db->where('r.SY', $sy);
		$this->db->where('r.Sem', $sem);

		$query = $this->db->get();
		return $query->result();
	}

	//Subject Masterlist
	public function subjectMasterlist($sem, $sy, $subjectcode, $section)
	{
		$this->db->select('registration.*, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
		$this->db->from('registration');
		$this->db->join('studeprofile', 'registration.StudentNumber = studeprofile.StudentNumber', 'left');
		$this->db->where('registration.Sem', $sem);
		$this->db->where('registration.SY', $sy);
		$this->db->where('registration.Section', $section);
		$this->db->where('registration.SubjectCode', $subjectcode);
		$this->db->group_by('registration.StudentNumber');
		$this->db->order_by('studeprofile.LastName', 'ASC');

		$query = $this->db->get();
		return $query->result();
	}



	//Grade
	public function grades($sem, $sy)
	{
		$sql = "
        SELECT
            g.SubjectCode,
            g.Section,
            g.Semester,
            g.SY,
            /* Use MIN() to pick one description per class when duplicates exist */
            " . ($this->db->field_exists('Description', 'grades_o') ? "MIN(g.Description) AS Description," : "NULL AS Description,") . "
            g.IDNumber,
            CONCAT(
                COALESCE(s.FirstName, ''), ' ',
                LEFT(COALESCE(s.MiddleName, ''), 1),
                CASE WHEN COALESCE(s.MiddleName, '') <> '' THEN '. ' ELSE '' END,
                COALESCE(s.LastName, '')
            ) AS InstructorName
        FROM grades_o g
        LEFT JOIN staff s
            ON s.IDNumber = g.IDNumber
        WHERE g.Semester = ?
          AND g.SY = ?
        /* Group by keys that define a class row + instructor (keeps one row per instructor) */
        GROUP BY
            g.SubjectCode,
            g.Section,
            g.Semester,
            g.SY,
            g.IDNumber,
            s.FirstName,
            s.MiddleName,
            s.LastName
        ORDER BY g.SubjectCode ASC, g.Section ASC
    ";

		return $this->db->query($sql, [$sem, $sy])->result();
	}

	public function grading_sheets($id, $sy, $sem, $section, $subjectcode)
	{
		$sql = "
        SELECT
            p.StudentNumber,
            CONCAT(p.LastName, ', ', p.FirstName) AS StudentName,
            LEFT(COALESCE(p.MiddleName,''), 1)    AS MiddleName,
            r.Course, r.YearLevel, r.Section, r.Major,
            r.Prelim, r.Midterm, r.PreFinal, r.Final, r.Average
        FROM studeprofile p
        JOIN grades_o r ON p.StudentNumber = r.StudentNumber
        WHERE r.IDNumber    = ?
          AND r.SY          = ?
          AND r.Semester    = ?
          AND r.Section     = ?
          AND r.SubjectCode = ?
        ORDER BY StudentName, p.StudentNumber
    ";
		return $this->db->query($sql, [$id, $sy, $sem, $section, $subjectcode])->result();
	}

	/**
	 * Registrar-facing grading sheet query (no r.IDNumber filter).
	 * Mirrors InstructorModel::grading_sheets fields so the view can reuse it.
	 */
	public function gradeSheets($sy, $sem, $subjectcode, $section)
	{
		$sql = "
        SELECT
            p.StudentNumber,
            CONCAT(COALESCE(p.LastName,''), ', ', COALESCE(p.FirstName,'')) AS StudentName,
            LEFT(COALESCE(p.MiddleName,''), 1) AS MiddleName,
            r.Course, r.YearLevel, r.Section, r.Major,
            r.Prelim, r.Midterm, r.PreFinal, r.Final, r.Average,
            r.IDNumber
        FROM studeprofile p
        JOIN grades_o r
          ON r.StudentNumber = p.StudentNumber
        WHERE r.SY          = ?
          AND r.Semester    = ?
          AND r.SubjectCode = ?
          AND r.Section     = ?
        GROUP BY p.StudentNumber
        ORDER BY StudentName, p.StudentNumber
    ";
		return $this->db->query($sql, [$sy, $sem, $subjectcode, $section])->result();
	}


	//Grading Sheets
	// function gradeSheets($sem, $sy, $SubjectCode, $Description, $Section)
	// {
	// 	$query = $this->db->query("select SubjectCode, Description, Final, Complied, Semester, Section, g.Course, SY, p.StudentNumber, p.FirstName, p.MiddleName, p.LastName from grades_o g join studeprofile p on g.StudentNumber=p.StudentNumber where g.Semester='" . $sem . "' and g.SY='" . $sy . "' and g.SubjectCode='" . $SubjectCode . "' and g.Section='" . $Section . "' order by p.LastName");
	// 	return $query->result();
	// }

	//CrossEnrollees
	public function crossEnrollees($sem, $sy)
	{
		$this->db->select("
        CONCAT(p.LastName, ', ', p.FirstName, ' ', p.MiddleName) AS StudentName,
        ss.YearLevel,
        p.Sex,
        ss.Course,
        ss.classSession,
        ss.Semester,
        ss.SY
    ");
		$this->db->from('studeprofile p');
		$this->db->join('semesterstude ss', 'p.StudentNumber = ss.StudentNumber');
		$this->db->where('ss.Status', 'Enrolled');
		$this->db->where('ss.crossEnrollee', 'Yes');
		$this->db->where('ss.Semester', $sem);
		$this->db->where('ss.SY', $sy);
		$this->db->order_by('p.LastName'); // Corrected from "LastLName"

		$query = $this->db->get();
		return $query->result();
	}


	//Admission History
	function admissionHistory($id)
	{
		$query = $this->db->query("select p.StudentNumber, concat(p.FirstName,' ',p.MiddleName,' ',p.LastName) as StudentName, s.Course, s.Major, s.YearLevel, s.SY, s.Semester from studeprofile p join semesterstude s on p.StudentNumber=s.StudentNumber join o_srms_settings st on p.settingsID=st.settingsID where p.StudentNumber='" . $id . "'");
		return $query->result();
	}
	//Get Course and Display on the combo box
	function getCourse()
	{
		$this->db->select('CourseDescription');
		$this->db->distinct();
		$this->db->order_by('CourseDescription', 'ASC');
		$query = $this->db->get('course_table');
		return $query->result();
	}

	function getMajorsByCourse1($course)
	{
		$this->db->select('Major');
		$this->db->from('course_table');
		$this->db->where('CourseDescription', $course);
		$this->db->where('Major !=', ''); // ignore blank majors
		$this->db->order_by('Major', 'ASC');
		$this->db->distinct();
		$query = $this->db->get();
		return $query->result();
	}


	// tyrone

	// function getNamesFromQuery($course, $sy, $sem)
	// 			{
	// 				 $query = $this->db->query("SELECT CONCAT(p.LastName,', ',p.FirstName,' ',p.MiddleName) as StudentName
	//                            FROM studeprofile p
	//                            JOIN semesterstude ss ON p.StudentNumber = ss.StudentNumber
	//                            WHERE ss.Status = 'Enrolled' AND ss.crossEnrollee = 'Yes'
	//                            AND ss.Semester = '".$sem."' AND ss.SY = '".$sy."'
	//                            AND ss.Course = '".$course."'
	//                            ORDER BY p.LastName");

	// 				 return $query->result();
	// 			}


	// tyrone



	// public function studeAccounts($sy, $yearlevel)
	// {
	// 	$this->db->select("sa.AccountID, 
	//                    sa.StudentNumber, 
	//                    CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) as StudentName, 
	//                    sa.Course, 
	//                    FORMAT(sa.AcctTotal, 2) as AcctTotal, 
	//                    FORMAT(sa.TotalPayments, 2) as TotalPayments, 
	//                    FORMAT(sa.Discount, 2) as Discount, 
	//                    FORMAT(sa.CurrentBalance, 2) as CurrentBalance, 
	//                    sa.YearLevel, 
	//                    sa.Sem, 
	//                    sa.SY");
	// 	$this->db->from("studeaccount sa");
	// 	$this->db->join("studeprofile sp", "sp.StudentNumber = sa.StudentNumber");
	// 	$this->db->where("sa.SY", $sy);
	// 	$this->db->where("sa.YearLevel", $yearlevel);
	// 	$this->db->group_by("sa.StudentNumber");
	// 	$this->db->order_by("StudentName", "ASC");

	// 	$query = $this->db->get();
	// 	return $query->result();
	// }


	function getCourseMajor()
	{
		$this->db->select('Major');
		$this->db->distinct();
		$this->db->order_by('Major', 'ASC');
		$query = $this->db->get('course_table');
		return $query->result();
	}

	function getMajor($course)
	{
		$this->db->select('Major');
		$this->db->where('CourseDescription', $course);
		$this->db->distinct();
		$this->db->order_by('Major', 'ASC');
		$query = $this->db->get('course_table');
		return $query->result();
	}


	function getSection()
	{
		$this->db->select('Section');
		$this->db->distinct();
		$this->db->group_by('Section', 'ASC');
		$this->db->order_by('Section', 'ASC');
		$query = $this->db->get('sections');
		return $query->result();
	}


	function getscholarships()
	{
		$this->db->select('Scholarship');
		$this->db->distinct();
		$this->db->group_by('Scholarship', 'ASC');
		$this->db->order_by('Scholarship', 'ASC');
		$query = $this->db->get('scholarships');
		return $query->result();
	}


	function getSchoolInfo()
	{
		$this->db->query("select * from o_srms_settings");
	}

	//update enrollees status
	function updateEnrollees($id)
	{
		$this->db->query("update online_enrollment set enrolStatus='Verified' where oeID='" . $id . "'");
	}

	//Masterlist by Grade Level
	public function byGradeLevel($yearlevel, $semester, $sy)
	{
		return $this->db->select('p.*, s.*')
			->from('semesterstude s')
			->join('studeprofile p', 'p.StudentNumber = s.StudentNumber')
			->where([
				's.YearLevel' => $yearlevel,
				's.Semester'  => $semester,
				's.SY'        => $sy,
				's.Status'    => 'Enrolled'
			])
			->group_by('s.StudentNumber') // one row per student
			->order_by('p.LastName ASC, p.Sex ASC')
			->get()->result();
	}


	//Student Enrollment Status
	function studeEnrollStat($id, $sem, $sy)
	{
		$query = $this->db->query("select * from semesterstude where StudentNumber='" . $id . "' and Semester='" . $sem . "' and SY='" . $sy . "'");

		return $query->result();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}
	//Student Current Balance
	function studeBalance($id)
	{
		//$query=$this->db->query("select * from studeaccount where StudentNumber='".$id."' and Sem='".$sem."' and SY='".$sy."'");
		$query = $this->db->query("select * from studeaccount where StudentNumber='" . $id . "' order by AccountID desc limit 1");

		return $query->result();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}

	//Faculty Load Counts
	function facultyLoadCounts($id, $sem, $sy)
	{
		$query = $this->db->query("SELECT COUNT(SubjectCode) AS subjectCounts FROM semsubjects WHERE IDNumber = ? AND Semester = ? AND SY = ?", array($id, $sem, $sy));

		if ($query->num_rows() > 0) {
			return $query->row(); // use row() since you're expecting a single result (count)
		}

		return false;
	}


	//Faculty Grades
	// function facultyGrades($instructor, $sem, $sy)
	// {
	// 	$query = $this->db->query("SELECT count(SubjectCode) as subjectCounts FROM grades where Instructor='" . $instructor . "' and Semester='" . $sem . "' and SY='" . $sy . "' group by SubjectCode");

	// 	return $query->result();

	// 	if ($query->num_rows() > 0) {
	// 		return $query->result();
	// 	}
	// 	return false;
	// }

	function facultyGrades($instructor, $sem, $sy)
	{
		$this->db->select('COUNT(SubjectCode) as subjectCounts');
		$this->db->from('grades');
		$this->db->where('IDNumber', $instructor);
		$this->db->where('Semester', $sem);
		$this->db->where('SY', $sy);
		$this->db->group_by('SubjectCode');

		$query = $this->db->get();

		return ($query->num_rows() > 0) ? $query->result() : false;
	}


	//Student Total Enrolled Subjects
	function studeTotalSubjects($id, $sem, $sy)
	{
		$query = $this->db->query("SELECT count(SubjectCode) as subjectCounts FROM registration where StudentNumber='" . $id . "' and Sem='" . $sem . "' and SY='" . $sy . "'");

		return $query->result();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}

	//Student Total Semesters Enrolled
	function semStudeCount($id)
	{
		$query = $this->db->query("SELECT StudentNumber, count(Semester) as SemesterCounts FROM semesterstude where StudentNumber='" . $id . "' group by StudentNumber");

		return $query->result();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}

	//Statement of Account
	public function studentStatement($id, $sem, $sy)
	{
		$query = $this->db->query("
        SELECT sa.*, sp.FirstName, sp.MiddleName, sp.LastName, sp.email
        FROM studeaccount sa
        JOIN studeprofile sp ON sa.StudentNumber = sp.StudentNumber
        WHERE sa.StudentNumber = ? AND sa.Sem = ? AND sa.SY = ?
        ORDER BY sa.FeesDesc
    ", array($id, $sem, $sy));

		return $query->result();
	}

	public function getAdditionalFees($id, $sem, $sy)
	{
		$this->db->where('StudentNumber', $id);
		$this->db->where('Sem', $sem);
		$this->db->where('SY', $sy);
		return $this->db->get('studeadditional')->result();
	}




	function get_brgy()
	{
		$this->db->select('Brgy');
		$this->db->distinct();
		$this->db->order_by('Brgy', 'ASC');
		$query = $this->db->get('settings_address');
		return $query->result();
	}

	//Masterlist (All)
	function masterlistAll2($id, $semester, $sy)
	{
		$query = $this->db->query("select * from studeprofile p join semesterstude s on p.StudentNumber=s.StudentNumber where s.semstudentid='" . $id . "' and s.Semester='" . $semester . "' and s.SY='" . $sy . "' and s.Status='Enrolled' order by p.LastName, p.Sex");
		return $query->result();
	}

	//Count Summary Per Year Level
	public function byGradeLevelCount($yearlevel, $semester, $sy)
	{
		return $this->db->select("TRIM(s.Course) AS Course, COUNT(DISTINCT s.StudentNumber) AS enrollees", false)
			->from('semesterstude s')
			->where([
				's.YearLevel' => $yearlevel,
				's.Semester'  => $semester,
				's.SY'        => $sy,
				's.Status'    => 'Enrolled'
			])
			->group_by('TRIM(s.Course)')
			->order_by('Course')
			->get()->result();
	}

	//Masterlist by Course
	function byCourse($course, $major, $sy, $sem)
	{
		$this->db->select('*');
		$this->db->from('studeprofile p');
		$this->db->join('semesterstude s', 'p.StudentNumber = s.StudentNumber');
		$this->db->where('s.SY', $sy);
		$this->db->where('s.Semester', $sem);
		$this->db->where('s.Status', 'Enrolled');
		$this->db->where('s.Course', $course);

		// Only filter by Major if it's not empty
		if (!empty($major)) {
			$this->db->where('s.Major', $major);
		}

		$this->db->order_by('p.LastName', 'ASC');
		$this->db->order_by('p.Sex', 'ASC');

		$query = $this->db->get();
		return $query->result();
	}





	//Enrollees Counts Per Course (Year Level Counts)
	function CourseYLCounts($course, $major, $sy, $sem)
	{
		$this->db->select('YearLevel, COUNT(YearLevel) as yearLevelCounts');
		$this->db->from('semesterstude');
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		$this->db->where('Status', 'Enrolled');
		$this->db->where('Course', $course);

		// Add major filter if not empty
		if (!empty($major)) {
			$this->db->where('Major', $major);
		}

		$this->db->group_by('YearLevel');

		$query = $this->db->get();
		return $query->result();
	}

	public function byYearLevelAndCourse($yearLevel, $course, $sy, $sem)
	{
		$query = $this->db->query("SELECT * 
								   FROM studeprofile p 
								   JOIN semesterstude s ON p.StudentNumber = s.StudentNumber 
								   WHERE s.SY = '" . $sy . "' 
								   AND s.Semester = '" . $sem . "' 
								   AND s.Status = 'Enrolled' 
								   AND s.YearLevel = '" . $yearLevel . "' 
								   AND s.Course = '" . $course . "' 
								   ORDER BY p.LastName, p.Sex");

		return $query->result();
	}


	/**
	 * SectionCounts: counts enrollees per Section for a given SY/Sem.
	 * - If $course is provided, filter by course.
	 * - If $major  is provided and not '', filter by that major.
	 * - If $major === '' (explicit empty string), show only blank/NULL majors.
	 * - If $major is null (not provided), include ALL majors (no major filter).
	 */
	public function SectionCounts($sy, $sem, $course = null, $major = null)
	{
		$sectionExpr = "CASE WHEN NULLIF(TRIM(s.Section),'') IS NULL THEN 'Not Set' ELSE TRIM(s.Section) END";

		$this->db->select("$sectionExpr AS Section, COUNT(DISTINCT s.StudentNumber) AS Counts", false)
			->from('semesterstude s')
			->where('s.SY', $sy)
			->where('s.Semester', $sem)
			->where('s.Status', 'Enrolled');

		if (!empty($course)) {
			$this->db->where('s.Course', $course);
		}

		if ($major !== null) {
			if ($major === '') {
				$this->db->group_start()
					->where('s.Major', '')
					->or_where('s.Major IS NULL', null, false)
					->group_end();
			} else {
				$this->db->where('s.Major', $major);
			}
		}

		return $this->db->group_by($sectionExpr, false)
			->order_by('Section', 'ASC')
			->get()
			->result();
	}

	/**
	 * Enrolled students behind one enrollment-summary bucket (course / year level / section).
	 * Uses the same filters and the same "Not Set" normalisation as CourseCount(),
	 * YearLevelCount() and SectionCounts(), so the list length always matches the
	 * count shown on the dashboard.
	 */
	public function enrolledStudentsBy($sy, $sem, $by, $value, $course = null, $major = null)
	{
		$buckets = array(
			'course'    => "CASE WHEN NULLIF(TRIM(s.Course),'') IS NULL THEN 'Not Set' ELSE TRIM(s.Course) END",
			'yearlevel' => "CASE WHEN NULLIF(TRIM(s.YearLevel),'') IS NULL THEN 'Not Set' ELSE TRIM(s.YearLevel) END",
			'section'   => "CASE WHEN NULLIF(TRIM(s.Section),'') IS NULL THEN 'Not Set' ELSE TRIM(s.Section) END",
		);

		if (!isset($buckets[$by])) {
			return array();
		}

		// Only ~1/3 of enrolled students have a studeprofile row, so names are pulled
		// from studeprofile first and fall back to studentsignup; both are LEFT joins
		// and grouped per student so the list length equals the dashboard count.
		$name = function ($field) {
			return "MIN(COALESCE(NULLIF(TRIM(p.$field),''), NULLIF(TRIM(g.$field),''), ''))";
		};

		$this->db->select(
			's.StudentNumber'
				. ', ' . $name('LastName')   . ' AS LastName'
				. ', ' . $name('FirstName')  . ' AS FirstName'
				. ', ' . $name('MiddleName') . ' AS MiddleName'
				. ', ' . $name('Sex')        . ' AS Sex'
				. ', MIN(s.Course) AS Course, MIN(s.Major) AS Major'
				. ', MIN(s.YearLevel) AS YearLevel, MIN(s.Section) AS Section',
			false
		)
			->from('semesterstude s')
			->join('studeprofile p', 'p.StudentNumber = s.StudentNumber', 'left')
			->join('studentsignup g', 'g.StudentNumber = s.StudentNumber', 'left')
			->where('s.SY', $sy)
			->where('s.Semester', $sem)
			->where('s.Status', 'Enrolled')
			->where($buckets[$by] . ' = ' . $this->db->escape($value), null, false);

		if (!empty($course)) {
			$this->db->where('s.Course', $course);
		}

		if ($major !== null) {
			if ($major === '') {
				$this->db->group_start()
					->where('s.Major', '')
					->or_where('s.Major IS NULL', null, false)
					->group_end();
			} else {
				$this->db->where('s.Major', $major);
			}
		}

		return $this->db->group_by('s.StudentNumber')
			->order_by('LastName', 'ASC')
			->order_by('FirstName', 'ASC')
			->get()
			->result();
	}



	//Masterlist by Qualification
	public function byQualification($qual)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('*')
			->from('studeprofile p')
			->join('semesterstude s', 'p.StudentNumber = s.StudentNumber')
			->where('s.Course', 'TESDA Program')
			->where('s.Major', $qual)
			->where('s.Status', 'Enrolled')
			->group_by('p.StudentNumber')
			->order_by('p.LastName');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}


	public function byQualificationSection($qual, $section)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('*')
			->from('studeprofile p')
			->join('semesterstude s', 'p.StudentNumber = s.StudentNumber')
			->where('s.Course', 'TESDA Program')
			->where('s.Major', $qual)
			->where('s.Section', $section)
			->where('s.Status', 'Enrolled')
			->group_by('p.StudentNumber')
			->order_by('p.LastName');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}


	public function byQualificationEmployment($qual, $section)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('*')
			->from('studeprofile p')
			->join('employment e', 'p.StudentNumber = e.StudentNumber')
			->join('semesterstude s', 'p.StudentNumber = s.StudentNumber')
			->where('s.Course', 'TESDA Program')
			->where('s.Major', $qual)
			->where('s.Section', $section)
			->where('s.Status', 'Enrolled')
			->group_by('p.StudentNumber')
			->order_by('p.LastName');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}



	public function byQualificationSectionCounts($qual)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('Section, COUNT(Section) as enrolledCounts, Major')
			->from('semesterstude')
			->where('status', 'Enrolled')
			->where('Course', 'TESDA Program')
			->where('Major', $qual)
			->group_by('Section')
			->order_by('Course')
			->order_by('Section');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}



	//Masterlist by Date
	public function byDate($date)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('*')
			->from('studeprofile p')
			->join('semesterstude s', 'p.StudentNumber = s.StudentNumber')
			->where('s.enroledDate', $date)
			->where('s.Status', 'Enrolled')
			->group_by('p.StudentNumber')
			->order_by('p.LastName');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}


	//Masterlist by Date Summary
	public function byDateCourseSum($date)
	{
		// Use CodeIgniter's query builder to construct the query
		$this->db->select('Course, COUNT(Course) as Enrollees')
			->from('semesterstude')
			->where('enroledDate', $date)
			->where('Status', 'Enrolled')
			->group_by('Course')
			->order_by('Course');

		// Execute the query and return the results
		$query = $this->db->get();

		// Return the result as an array of objects
		return $query->result();
	}



	public function collectionReport($from = null, $to = null)
	{
		// Define the query
		$this->db->select("
        paymentsaccounts.PDate, 
        paymentsaccounts.ORNumber, 
        FORMAT(paymentsaccounts.Amount, 2) as Amount, 
        paymentsaccounts.description, 
        paymentsaccounts.StudentNumber, 
        CONCAT(studeprofile.LastName, ', ', studeprofile.FirstName, ' ', studeprofile.MiddleName) as Payor, 
        studeprofile.Course, 
        paymentsaccounts.PaymentType, 
        paymentsaccounts.Description, 
        paymentsaccounts.CheckNumber, 
        paymentsaccounts.Bank, 
        paymentsaccounts.CollectionSource, 
        CONCAT(paymentsaccounts.Sem, ' ', paymentsaccounts.SY) as Semester
    ")
			->from('paymentsaccounts')
			->join('studeprofile', 'paymentsaccounts.StudentNumber = studeprofile.StudentNumber')
			->where('paymentsaccounts.ORStatus', 'Valid');

		// Apply date filters if provided
		if ($from !== null) {
			$this->db->where('paymentsaccounts.PDate >=', $from);
		}
		if ($to !== null) {
			$this->db->where('paymentsaccounts.PDate <=', $to);
		}

		// Order by payment date descending
		$this->db->order_by('paymentsaccounts.PDate', 'DESC');

		// Execute the query and return the results
		return $this->db->get()->result();
	}

	function collectionTotal($from, $to)
	{
		$this->db->select_sum('Amount', 'TotalAmount');
		$this->db->from('paymentsaccounts');
		$this->db->where('PDate >=', $from);
		$this->db->where('PDate <=', $to);
		$this->db->where('ORStatus', 'Valid');

		$query = $this->db->get();

		return $query->result();
	}



	function getExpenseCategory()
	{
		$query = $this->db->query("SELECT Category FROM expenses_cat order by Category");
		return $query->result();
	}

	function expensesReportAll()
	{
		$query = $this->db->query("select * from expenses order by ExpenseDate desc");
		return $query->result();
	}

	function expensesReport($from, $to)
	{
		$query = $this->db->query("select * from expenses where ExpenseDate>='" . $from . "' and ExpenseDate<='" . $to . "' order by ExpenseDate desc");
		return $query->result();
	}

	function expensesTotal($from, $to)
	{
		$query = $this->db->query("select Sum(Amount) as TotalAmount from expenses where ExpenseDate>='" . $from . "' and ExpenseDate<='" . $to . "'");
		return $query->result();
	}




	function collectionSummary($from, $to)
	{
		$query = $this->db->query("SELECT PaymentType, format(sum(Amount),2) as TotalAmount FROM paymentsaccounts where PDate>='" . $from . "' and PDate<='" . $to . "' and ORStatus='Valid' group by PaymentType");
		return $query->result();
	}

	function expensesSummary($from, $to)
	{
		$query = $this->db->query("SELECT Category, format(sum(Amount),2) as TotalAmount FROM expenses where ExpenseDate>='" . $from . "' and ExpenseDate<='" . $to . "' group by Category");
		return $query->result();
	}

	function collectionTotalYear($year)
	{
		$query = $this->db->query("select Sum(Amount) as TotalAmount from paymentsaccounts where YEAR(PDate)='" . $year . "' and ORStatus='Valid' order by PDate desc");
		return $query->result();
	}

	public function collectionTotalMonthly($year, $month)
	{
		$this->db->select('SUM(Amount) as TotalAmount');
		$this->db->from('paymentsaccounts');
		$this->db->where('YEAR(PDate)', $year);
		$this->db->where('MONTH(PDate)', $month);
		$this->db->where('ORStatus', 'Valid');

		$query = $this->db->get();
		return $query->result();
	}

	public function collectionTotalByDateRange($start_date, $end_date)
	{
		$this->db->select('SUM(Amount) as TotalAmount');
		$this->db->from('paymentsaccounts');
		$this->db->where('PDate >=', $start_date);
		$this->db->where('PDate <=', $end_date);
		$this->db->where('ORStatus', 'Valid');

		$query = $this->db->get();
		return $query->result();
	}



	function collectionYear($year)
	{
		$this->db->select("p.PDate, p.ORNumber, FORMAT(p.Amount, 2) as Amount, p.description, 
						   p.StudentNumber, 
						   CONCAT(s.LastName, ', ', s.FirstName, ' ', s.MiddleName) as Payor,
						   p.Description, p.PaymentType, YEAR(p.PDate) as Year");
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile s', 's.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('YEAR(p.PDate)', $year);
		$this->db->where('p.ORStatus', 'Valid');
		$this->db->order_by('p.PDate', 'DESC');

		$query = $this->db->get();
		return $query->result();
	}

	public function collectionMonthly($year, $month)
	{
		$this->db->select("p.PDate, p.ORNumber, FORMAT(p.Amount, 2) as Amount, 
                       p.StudentNumber, 
                       CONCAT(s.LastName, ', ', s.FirstName, ' ', s.MiddleName) as Payor,
                       p.Description, p.PaymentType, 
                       YEAR(p.PDate) as Year, MONTH(p.PDate) as Month");
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile s', 's.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('YEAR(p.PDate)', $year);
		$this->db->where('MONTH(p.PDate)', $month);
		$this->db->where('p.ORStatus', 'Valid');
		$this->db->order_by('p.PDate', 'DESC');

		$query = $this->db->get();
		return $query->result();
	}

	public function collectionByDateRange($from, $to)
	{
		$this->db->select("p.PDate, p.ORNumber, FORMAT(p.Amount, 2) as Amount, 
                       p.StudentNumber, 
                       CONCAT(s.LastName, ', ', s.FirstName, ' ', s.MiddleName) as Payor,
                       p.Description, p.PaymentType, 
                       YEAR(p.PDate) as Year, MONTH(p.PDate) as Month");
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile s', 's.StudentNumber = p.StudentNumber', 'left');
		$this->db->where('p.PDate >=', $from);
		$this->db->where('p.PDate <=', $to);
		$this->db->where('p.ORStatus', 'Valid');
		$this->db->order_by('p.PDate', 'DESC');

		$query = $this->db->get();
		return $query->result();
	}






	// function studeAccounts($sem, $sy, $course, $yearlevel)
	// {
	// 	$query = $this->db->query("Select AccountID, StudentNumber, concat(LastName,', ',FirstName,' ',MiddleName) as StudentName, Course, format(AcctTotal,2) as AcctTotal, format(TotalPayments,2) as TotalPayments, format(Discount,2) as Discount, format(CurrentBalance,2) as CurrentBalance, YearLevel, Sem, SY FROM studeaccount where Sem='" . $sem . "' and SY='" . $sy . "' and YearLevel='" . $yearlevel . "' and Course= '" . $course . "' group by StudentNumber order by StudentName");
	// 	return $query->result();
	// }

	public function studeAccountsWithBalance($sem, $sy, $course, $yearlevel)
	{
		$this->db->select("sa.AccountID, sa.StudentNumber, 
        CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) AS StudentName, 
        sa.Course, 
        FORMAT(sa.AcctTotal, 2) AS AcctTotal, 
        FORMAT(sa.TotalPayments, 2) AS TotalPayments, 
        FORMAT(sa.Discount, 2) AS Discount, 
        FORMAT(sa.CurrentBalance, 2) AS CurrentBalance, 
        sa.YearLevel, sa.Sem, sa.SY");

		$this->db->from('studeaccount sa');
		$this->db->join('studeprofile sp', 'sa.StudentNumber = sp.StudentNumber');
		$this->db->where('sa.Sem', $sem);
		$this->db->where('sa.SY', $sy);
		$this->db->where('sa.Course', $course);
		$this->db->where('sa.YearLevel', $yearlevel);
		$this->db->where('sa.CurrentBalance >', 0);
		$this->db->group_by('sa.StudentNumber');
		$this->db->order_by('StudentName');

		$query = $this->db->get();
		return $query->result();
	}

	public function fullyPaid($sem, $sy, $course, $yearlevel)
	{
		$this->db->select("sa.AccountID, sa.StudentNumber, 
        CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) AS StudentName, 
        sa.Course, 
        FORMAT(sa.AcctTotal, 2) AS AcctTotal, 
        FORMAT(sa.TotalPayments, 2) AS TotalPayments, 
        FORMAT(sa.Discount, 2) AS Discount, 
        FORMAT(sa.CurrentBalance, 2) AS CurrentBalance, 
        sa.YearLevel, sa.Sem, sa.SY");

		$this->db->from('studeaccount sa');
		$this->db->join('studeprofile sp', 'sa.StudentNumber = sp.StudentNumber');
		$this->db->where('sa.Sem', $sem);
		$this->db->where('sa.SY', $sy);
		$this->db->where('sa.Course', $course);
		$this->db->where('sa.YearLevel', $yearlevel);
		$this->db->where('sa.CurrentBalance <', 0);
		$this->db->group_by('sa.StudentNumber');
		$this->db->order_by('StudentName');

		$query = $this->db->get();
		return $query->result();
	}

	//PASSWORD ---------------------------------------------------------------------------------
	function is_current_password($username, $currentpass)
	{
		$this->db->select();
		$this->db->from('o_users');
		$this->db->where('username', $username);
		$this->db->where('password', $currentpass);
		$this->db->where('acctStat', 'active');
		$query = $this->db->get();
		$row = $query->row();
		if ($row) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function reset_userpassword($username, $newpass)
	{
		$data = array(
			'password' => $newpass
		);
		$this->db->where('username', $username);
		if ($this->db->update('o_users', $data)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	//Get Profile Pictures
	public function profilepic($studentNumber)
	{
		return $this->db->select('avatar')
			->from('o_users')
			->where('username', $studentNumber)
			->limit(1)
			->get()
			->result(); // array with 0..1 object(s), matches view usage
	}


	//Total Request
	function totalStudeRequest()
	{
		$query = $this->db->query("SELECT reqStat, count(reqStat) as requestCounts FROM stude_request");
		return $query->result();
	}

	//Open Request
	function openRequest()
	{
		$query = $this->db->query("SELECT reqStat, count(reqStat) as requestCounts FROM stude_request where reqStat='Open'");
		return $query->result();
	}

	//Open Request
	function closedRequest()
	{
		$query = $this->db->query("SELECT reqStat, count(reqStat) as requestCounts FROM stude_request where reqStat='Closed'");
		return $query->result();
	}

	//Student REQUEST
	function studeRequestList()
	{
		$query = $this->db->query("select * from stude_request sr join studeprofile p on sr.StudentNumber=p.StudentNumber where sr.reqStat='Open' order by trackingNo desc");
		return $query->result();
	}

	//Scholarship Applicants
	function scholarshipApplicants()
	{
		$query = $this->db->query("select * from reservation where appStatus='Pending' order by appNo");
		return $query->result();
	}

	//Student REQUEST
	function closedDocRequest()
	{
		$query = $this->db->query("select * from stude_request sr join studeprofile p on sr.StudentNumber=p.StudentNumber where sr.reqStat='Closed' order by sr.dateReq desc");
		return $query->result();
	}

	//Student REQUEST
	function openDocRequest()
	{
		$query = $this->db->query("select * from stude_request sr join studeprofile p on sr.StudentNumber=p.StudentNumber where sr.reqStat='Open' order by sr.dateReq desc");
		return $query->result();
	}

	//Student REQUEST
	function allDocRequest()
	{
		$query = $this->db->query("select * from stude_request sr join studeprofile p on sr.StudentNumber=p.StudentNumber order by sr.trackingNo desc");
		return $query->result();
	}

	function docReqCounts()
	{
		$query = $this->db->query("SELECT docName, count(docName) as docCounts FROM stude_request group by docName");
		return $query->result();
	}

	function reservationCounts()
	{
		$query = $this->db->query("SELECT course, count(course) as courseCount FROM reservation where appStatus='Pending' group by course order by course");
		return $query->result();
	}

	function enrolledCounts()
	{
		$query = $this->db->query("SELECT Major, count(Major) as courseCount FROM semesterstude where Course='TESDA Program' and Status='Enrolled' group by Major");
		return $query->result();
	}

	function scholarshipReservation($program)
	{
		$query = $this->db->query("SELECT * FROM reservation where course='" . $program . "' and appStatus='Pending' order by appNo");
		return $query->result();
	}

	function newestSignup()
	{
		$query = $this->db->query("SELECT * FROM studentsignup order by signupID desc limit 5 ");
		return $query->result();
	}


	public function gradesUploading($record)
	{
		if (!empty($record)) {
			$sem = $this->session->userdata('semester');
			$sy = $this->session->userdata('sy');
			$subjectcode = $this->input->post('subjectcode');
			$description = $this->input->post('description');
			$instructor = $this->input->post('instructor');
			$section = $this->input->post('section');

			// $takenAt=$this->input->post('section');
			// $settingsID=$this->input->post('section');
			date_default_timezone_set('Asia/Manila');
			$timeEncoded = date('h:i:s A');
			$dateEncoded = date('Y-m-d');

			$grades = array(
				"StudentNumber" => trim($record[0]),
				"SubjectCode"   => $subjectcode,
				"Description"   => $description,
				"Instructor"    => $instructor,
				"Section"       => $section,
				"Final"         => trim($record[2]),
				"Semester"      => $sem,
				"SY"           	=> $sy,
				"SY"           	=>  trim($record[0]),
				// "settingsID"   	=> $settingsID,
				// "takenAt"       => $takenAt,
				"dateEncoded"   => $dateEncoded,
				"timeEncoded"   => $timeEncoded,
			);

			$this->db->insert('grades', $grades);
		}
	}

	function countItemsByCategory($itemCategory)
	{
		$this->db->where('itemCategory', $itemCategory); // Filter by description
		$this->db->from('ls_items'); // Specify the table
		return $this->db->count_all_results(); // Return the count
	}

	function getStaff()
	{

		$this->db->select('*');
		$this->db->from('staff');
		$this->db->order_by('FirstName, MiddleName, LastName');

		$query = $this->db->get();
		return $query->result();
	}

	function getBrand()
	{
		$this->db->select('*');
		$this->db->distinct();
		$this->db->from('ls_brands');
		$this->db->order_by('brand');

		$query = $this->db->get();
		return $query->result();
	}


	public function getMajorsByCourse($course)
	{
		$this->db->select('Major');
		$this->db->where('CourseDescription', $course);
		$this->db->distinct();
		$this->db->order_by('Major', 'ASC');
		$query = $this->db->get('course_table');
		return $query->result();
	}


	public function getSectionsByCourseYearLevel($course, $yearLevel, $major = null)
	{
		$this->db->select('Section');
		$this->db->distinct();
		$this->db->where('Course', $course);
		$this->db->where('YearLevel', $yearLevel);

		if (!empty($major)) { // ✅ only filter if major is provided
			$this->db->where('Major', $major);
		}

		$this->db->order_by('Section', 'ASC');
		$query = $this->db->get('sections');
		return $query->result();
	}


	function display_itemsById($itemID)
	{
		$query = $this->db->query("select * from ls_items where itemID='" . $itemID . "'");
		return $query->result();
	}

	public function updateItem($itemID, $updatedData)
	{
		// Assuming the table name is 'inventory' and the primary key column is 'itemID'
		$this->db->where('itemID', $itemID); // Match the itemID with the existing record
		$this->db->update('ls_items', $updatedData); // Update the record in the 'inventory' table

		// Check if the update was successful
		if ($this->db->affected_rows() > 0) {
			return true; // Success
		} else {
			return false; // Failure (could be because nothing changed)
		}
	}

	public function getstudentsignupbyId($StudentNumber)
	{
		$this->db->select('StudentNumber, FirstName, MiddleName, LastName, Sex, CivilStatus, birthDate, Age, contactNo, email, 
                       Course1, Course2, Course3, Major1, Major2, Major3, yearLevel, section, Province, City, Brgy, Sitio');
		$this->db->from('studentsignup');
		$this->db->where('StudentNumber', $StudentNumber);
		$query = $this->db->get();

		return $query->row();  // Return the first row as an object (single result)
	}



	public function getstudentbyId($StudentNumber)
	{
		$query = $this->db->query("SELECT * FROM studeprofile WHERE StudentNumber = '" . $StudentNumber . "'");
		return $query->result();
	}


	public function updatestudentsignup($StudentNumber, $updateData)
	{
		$this->db->where('StudentNumber', $StudentNumber);
		$this->db->update('studentsignup', $updateData);  // Update the correct fields
	}



	function bySection1($section, $course, $major, $semester, $sy)
	{
		$this->db->select('DISTINCT p.StudentNumber, p.LastName, p.FirstName, p.MiddleName, s.YearLevel, s.Section', false);
		$this->db->from('studeprofile p');
		$this->db->join('semesterstude s', 'p.StudentNumber = s.StudentNumber', 'inner');

		$this->db->where('s.Section', $section);
		$this->db->where('s.Semester', $semester);
		$this->db->where('s.SY', $sy);
		$this->db->where('s.Status', 'Enrolled');

		// Scope by course
		$this->db->where('s.Course', $course);

		// Scope by major if provided; otherwise allow NULL/'' majors
		if (!empty($major)) {
			$this->db->where('s.Major', $major);
		} else {
			$this->db->group_start()
				->where('s.Major', '')
				->or_where('s.Major IS NULL', null, false)
				->group_end();
		}

		$this->db->order_by('p.LastName', 'ASC');
		$this->db->order_by('p.FirstName', 'ASC');

		return $this->db->get()->result();
	}







	public function getAccountDetailsByStudentNumberAndSY($studentNumber, $SY)
	{
		$this->db->select('studeaccount.*, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
		$this->db->from('studeaccount');
		$this->db->join('studeprofile', 'studeprofile.StudentNumber = studeaccount.StudentNumber');
		$this->db->where('studeaccount.StudentNumber', $studentNumber);
		$this->db->where('studeaccount.SY', $SY); // Filter by SY
		return $this->db->get()->row(); // Return a single row
	}

	public function insertIntoStudeAdditional($data)
	{
		return $this->db->insert('studeadditional', $data);
	}
	public function updateStudent($id, $data)
	{
		// Ensure we're updating based on StudentNumber
		$this->db->where('StudentNumber', $id);
		return $this->db->update('studentsignup', $data);
	}
	public function updateStudentAccount($studentNumber, $SY, $data)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $SY); // Ensure only the correct SY is updated
		return $this->db->update('studeaccount', $data);
	}


	public function getAccountDetails($accountID)
	{
		// Join the studeaccount table with the studeprofile table
		$this->db->select('studeaccount.*, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
		$this->db->from('studeaccount');
		$this->db->join('studeprofile', 'studeprofile.StudentNumber = studeaccount.StudentNumber');
		$this->db->where('studeaccount.AccountID', $accountID);
		return $this->db->get()->row();
	}

	public function getAllStudents()
	{
		$this->db->select('StudentNumber, FirstName, MiddleName, LastName');
		return $this->db->get('studeprofile')->result();
	}


	public function addDiscount($data)
	{
		return $this->db->insert('studediscount', $data);
	}


	public function updateStudentAccountFields($studentNumber, $sy, $data)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $sy);
		return $this->db->update('studeaccount', $data);
	}


	public function deleteStudentAccount($StudentNumber, $SY)
	{
		// Ensure deletion only affects the current SY and the given StudentNumber
		$this->db->where('StudentNumber', $StudentNumber);
		$this->db->where('SY', $SY); // Filter by SY

		// Attempt to delete the record
		return $this->db->delete('studeaccount'); // Return true/false based on success
	}



	public function getAccountDetailsByStudentNumber($studentNumber)
	{
		// Join the studeaccount table with the studeprofile table
		$this->db->select('studeaccount.*, studeprofile.FirstName, studeprofile.MiddleName, studeprofile.LastName');
		$this->db->from('studeaccount');
		$this->db->join('studeprofile', 'studeprofile.StudentNumber = studeaccount.StudentNumber');
		$this->db->where('studeaccount.StudentNumber', $studentNumber); // Use StudentNumber to fetch account details
		return $this->db->get()->row(); // Return a single row
	}



	public function getStudentsWithoutAccounts($schoolYear)
	{
		// Subquery: Get students with accounts in the current school year
		$this->db->distinct()
			->select('StudentNumber')
			->from('studeaccount')
			->where('SY', $schoolYear);
		$subQuery = $this->db->get_compiled_select();

		// Main query: Get students without accounts for the current SY
		$this->db->select('sa.StudentNumber, sp.FirstName, sp.MiddleName, sp.LastName')
			->from('semesterstude sa')
			->join('studeprofile sp', 'sa.StudentNumber = sp.StudentNumber', 'left')
			->where('sa.SY', $schoolYear)
			->where("sa.StudentNumber NOT IN ($subQuery)", NULL, FALSE);

		$query = $this->db->get();
		return $query->result();  // Return result set
	}



	public function getStudentDetails($studentNumber)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$query = $this->db->get('semesterstude'); // Replace 'students' with your actual table name
		return $query->row();
	}


	public function checkExistingAccount($studentNumber, $currentSY)
	{
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $currentSY);  // Check against the current School Year
		$query = $this->db->get('studeaccount'); // Replace with your actual table name
		return $query->num_rows() > 0;
	}



	public function getDescriptionsByYearLevelAndSY($yearLevel, $SY)
	{
		$this->db->where('YearLevel', $yearLevel);
		$this->db->where('SY', $SY);  // Filter by the logged-in SY
		$query = $this->db->get('fees');
		return $query->result();
	}


	public function insertstudeAccount($data)
	{
		return $this->db->insert('studeaccount', $data);
	}

	public function getAmountPaid($studentNumber, $currentSY)
	{
		$this->db->select_sum('Amount'); // Assuming 'Amount' is the column name for payment amount
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $currentSY);  // Ensure it matches the current SY
		$this->db->where('ORStatus !=', 'Void'); // Tyrone
		$this->db->where('CollectionSource !=', 'Services'); // Tyrone

		$query = $this->db->get('paymentsaccounts'); // Replace with your actual payments table name
		return $query->row()->Amount ?? 0; // Return the sum or 0 if no payments found
	}



	public function getStudentDetailsWithFees()
	{
		$studentNumber = $this->input->post('StudentNumber');
		$currentSY = $this->session->userdata('sy');  // Get logged-in SY

		$studentDetails = $this->StudentModel->getStudentDetails($studentNumber);

		if ($studentDetails) {
			$yearLevel = $studentDetails->YearLevel;
			// Fetch fees by YearLevel and current SY only
			$fees = $this->StudentModel->getDescriptionsByYearLevelAndSY($yearLevel, $currentSY);

			// Fetch the amount paid, restricted by the current SY
			$amountPaid = $this->StudentModel->getAmountPaid($studentNumber, $currentSY);

			// Combine student details, fees, and amount paid into one response
			$response = [
				'studentDetails' => $studentDetails,
				'fees' => $fees,
				'amountPaid' => $amountPaid  // Add amount paid to the response
			];

			echo json_encode($response);
		} else {
			echo json_encode(['error' => 'Student not found']);
		}
	}





	public function collectionReportAll($SY)
	{
		// Set date limit for the last 3 months
		$date_limit = date('Y-m-d', strtotime('-3 months'));

		// Main collection report query
		$this->db->select("
        paymentsaccounts.PDate, 
        paymentsaccounts.ORNumber, 
        FORMAT(paymentsaccounts.Amount, 2) as Amount, 
        paymentsaccounts.description, 
        paymentsaccounts.StudentNumber, 
        CONCAT(studeprofile.LastName, ', ', studeprofile.FirstName, ' ', studeprofile.MiddleName) as Payor, 
        studeprofile.Course, 
        paymentsaccounts.PaymentType, 
        paymentsaccounts.Description, 
        paymentsaccounts.CheckNumber, 
        paymentsaccounts.Bank, 
        paymentsaccounts.CollectionSource, 
        CONCAT(paymentsaccounts.Sem, ' ', paymentsaccounts.SY) as Semester
    ");
		$this->db->from('paymentsaccounts');
		$this->db->join('studeprofile', 'paymentsaccounts.StudentNumber = studeprofile.StudentNumber');
		$this->db->where('paymentsaccounts.ORStatus', 'Valid');
		$this->db->where('paymentsaccounts.SY', $SY);
		$this->db->where('paymentsaccounts.PDate >=', $date_limit);
		$this->db->order_by('paymentsaccounts.PDate', 'DESC');
		$collection_data = $this->db->get()->result();

		// Yearly collection report query
		$this->db->select("
        YEAR(paymentsaccounts.PDate) as Year, 
        SUM(paymentsaccounts.Amount) as TotalAmount
    ");
		$this->db->from('paymentsaccounts');
		$this->db->where('paymentsaccounts.ORStatus', 'Valid');
		$this->db->where('paymentsaccounts.SY', $SY);
		$this->db->where('paymentsaccounts.PDate >=', $date_limit);
		$this->db->group_by('Year');
		$this->db->order_by('Year', 'DESC');
		$yearly_data = $this->db->get()->result();

		// Monthly collection report query
		$this->db->select("
        DATE_FORMAT(paymentsaccounts.PDate, '%Y-%m') as Month, 
        SUM(paymentsaccounts.Amount) as TotalAmount
    ");
		$this->db->from('paymentsaccounts');
		$this->db->where('paymentsaccounts.ORStatus', 'Valid');
		$this->db->where('paymentsaccounts.SY', $SY);
		$this->db->where('paymentsaccounts.PDate >=', $date_limit);
		$this->db->group_by('Month');
		$this->db->order_by('Month', 'DESC');
		$monthly_data = $this->db->get()->result();

		// Return all data
		return [
			'collection_data' => $collection_data,
			'yearly_data' => $yearly_data,
			'monthly_data' => $monthly_data
		];
	}



	public function collectionReport1($SY)
	{
		$this->db->select('*');
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile pp', 'p.StudentNumber = pp.StudentNumber');
		$this->db->where('sy', $SY);  // Corrected this line
		$query = $this->db->get();
		return $query->result();
	}




	public function studepayments_summary($SY)
	{
		$this->db->select('*');
		$this->db->from('paymentsaccounts p');
		$this->db->join('studeprofile pp', 'p.StudentNumber = pp.StudentNumber');
		$this->db->where('sy', $SY);  // Corrected this line
		$query = $this->db->get();
		return $query->result();
	}



	function yearLevel()
	{
		$this->db->select('YearLevel');
		$this->db->distinct();
		$this->db->order_by('YearLevel', 'ASC');
		$query = $this->db->get('subjects');
		return $query->result();
	}


	function get_Scholar()
	{
		$this->db->select('*');
		$this->db->distinct();
		$query = $this->db->get('scholarships');
		return $query->result();
	}

	function get_prevSchool()
	{
		$this->db->distinct();
		$this->db->select('School, Address');
		$this->db->order_by('School', 'ASC'); // Sort alphabetically by School
		$query = $this->db->get('prevschool');
		return $query->result();
	}



	public function getDescriptionsByYearLevel($yearLevel)
	{
		$this->db->where('YearLevel', $yearLevel);
		$query = $this->db->get('fees');  // Assuming the fees are stored in a table named 'fees'
		return $query->result();
	}




	public function studeAccountsFiltered($sy, $yearlevel, $course, $major = null)
	{
		$this->db->select("sa.AccountID, 
					   sa.StudentNumber, 
					   CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) as StudentName, 
					   sa.Course, 
					   sa.Major, 
					   FORMAT(sa.AcctTotal, 2) as AcctTotal, 
					   FORMAT(sa.TotalPayments, 2) as TotalPayments, 
					   FORMAT(sa.Discount, 2) as Discount, 
					   FORMAT(sa.CurrentBalance, 2) as CurrentBalance, 
					   sa.YearLevel, 
					   sa.Sem, 
					   sa.SY");
		$this->db->from("studeaccount sa");
		$this->db->join("studeprofile sp", "sp.StudentNumber = sa.StudentNumber");
		$this->db->where("sa.SY", $sy);

		if (!empty($yearlevel)) {
			$this->db->where("sa.YearLevel", $yearlevel);
		}
		if (!empty($course)) {
			$this->db->where("sa.Course", $course);
		}
		if (!empty($major)) {
			$this->db->where("sa.Major", $major); // <-- Major filter
		}

		$this->db->group_by("sa.StudentNumber");
		$this->db->order_by("StudentName", "ASC");

		return $this->db->get()->result();
	}


	public function courseHasMajors($courseDescription)
	{
		$this->db->where('CourseDescription', $courseDescription);
		$this->db->where("TRIM(Major) !=", ''); // Check for non-empty majors
		$query = $this->db->get('course_table');

		return $query->num_rows() > 0;
	}


	// public function studeAccountsFiltered1($sy, $yearlevel, $course)
	// {
	// 	// Fetch filtered student records
	// 	$this->db->select("sa.AccountID, 
	//                    sa.StudentNumber, 
	//                    CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) as StudentName, 
	//                    sa.Course, 
	//                    FORMAT(sa.AcctTotal, 2) as AcctTotal, 
	//                    FORMAT(sa.TotalPayments, 2) as TotalPayments, 
	//                    FORMAT(sa.Discount, 2) as Discount, 
	//                    FORMAT(sa.CurrentBalance, 2) as CurrentBalance, 
	//                    sa.YearLevel, 
	//                    sa.Sem, 
	//                    sa.SY");
	// 	$this->db->from("studeaccount sa");
	// 	$this->db->join("studeprofile sp", "sp.StudentNumber = sa.StudentNumber");
	// 	$this->db->where("sa.SY", $sy);

	// 	if (!empty($yearlevel)) {
	// 		$this->db->where("sa.YearLevel", $yearlevel);
	// 	}
	// 	if (!empty($course)) {
	// 		$this->db->where("sa.Course", $course);
	// 	}

	// 	$this->db->group_by("sa.StudentNumber");
	// 	$this->db->order_by("StudentName", "ASC");

	// 	$students = $this->db->get()->result();

	// 	// ✅ Totals for unique StudentNumbers only
	// 	$subQuery = $this->db->select("
	// 		StudentNumber,
	// 		MAX(AcctTotal) as AcctTotal,
	// 		MAX(TotalPayments) as TotalPayments,
	// 		MAX(Discount) as Discount,
	// 		MAX(CurrentBalance) as CurrentBalance
	// 	")
	// 		->from("studeaccount")
	// 		->where("SY", $sy);

	// 	if (!empty($yearlevel)) {
	// 		$subQuery->where("YearLevel", $yearlevel);
	// 	}
	// 	if (!empty($course)) {
	// 		$subQuery->where("Course", $course);
	// 	}

	// 	$subQuery->group_by("StudentNumber");
	// 	$subQuerySQL = $subQuery->get_compiled_select();

	// 	$this->db->select("
	// 	SUM(AcctTotal) as TotalAcctTotal,
	// 	SUM(TotalPayments) as TotalPayments,
	// 	SUM(Discount) as TotalDiscount,
	// 	SUM(CurrentBalance) as TotalBalance
	// ");
	// 	$this->db->from("($subQuerySQL) as grouped");

	// 	$totals = $this->db->get()->row();

	// 	return ['students' => $students, 'totals' => $totals];
	// }

	public function studeAccountsFiltered1($sy, $yearlevel, $course)
	{
		// Fetch filtered student records with RAW numeric values (no FORMAT)
		$this->db->select("sa.AccountID, 
                       sa.StudentNumber, 
                       CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) as StudentName, 
                       sa.Course, 
                       sa.AcctTotal, 
                       sa.TotalPayments, 
                       sa.Discount, 
                       sa.CurrentBalance, 
                       sa.YearLevel, 
                       sa.Sem, 
                       sa.SY");
		$this->db->from("studeaccount sa");
		$this->db->join("studeprofile sp", "sp.StudentNumber = sa.StudentNumber");
		$this->db->where("sa.SY", $sy);

		if (!empty($yearlevel)) {
			$this->db->where("sa.YearLevel", $yearlevel);
		}
		if (!empty($course)) {
			$this->db->where("sa.Course", $course);
		}

		$this->db->group_by("sa.StudentNumber");
		$this->db->order_by("StudentName", "ASC");

		$students = $this->db->get()->result();

		// ✅ Totals for unique StudentNumbers only — raw values only!
		$subQuery = $this->db->select("
        StudentNumber,
        MAX(AcctTotal) as AcctTotal,
        MAX(TotalPayments) as TotalPayments,
        MAX(Discount) as Discount,
        MAX(CurrentBalance) as CurrentBalance
    ")
			->from("studeaccount")
			->where("SY", $sy);

		if (!empty($yearlevel)) {
			$subQuery->where("YearLevel", $yearlevel);
		}
		if (!empty($course)) {
			$subQuery->where("Course", $course);
		}

		$subQuery->group_by("StudentNumber");
		$subQuerySQL = $subQuery->get_compiled_select();

		$this->db->select("
        SUM(AcctTotal) as TotalAcctTotal,
        SUM(TotalPayments) as TotalPayments,
        SUM(Discount) as TotalDiscount,
        SUM(CurrentBalance) as TotalBalance
    ");
		$this->db->from("($subQuerySQL) as grouped");

		$totals = $this->db->get()->row();

		return ['students' => $students, 'totals' => $totals];
	}





	public function studeAccountsWithBalance1($sem, $sy, $course = null, $yearlevel = null)
	{
		// Fetch individual student records (same as before)
		$this->db->select("
		sa.AccountID, 
		sa.StudentNumber, 
		CONCAT(sp.LastName, ', ', sp.FirstName, ' ', sp.MiddleName) as StudentName, 
		sa.Course, 
		FORMAT(sa.AcctTotal, 2) as AcctTotal, 
		FORMAT(sa.TotalPayments, 2) as TotalPayments, 
		FORMAT(sa.Discount, 2) as Discount, 
		FORMAT(sa.CurrentBalance, 2) as CurrentBalance, 
		sa.YearLevel, 
		sa.Sem, 
		sa.SY
	");
		$this->db->from("studeaccount sa");
		$this->db->join("studeprofile sp", "sp.StudentNumber = sa.StudentNumber");
		$this->db->where("sa.Sem", $sem);
		$this->db->where("sa.SY", $sy);
		$this->db->where("sa.CurrentBalance >", 0);

		if (!empty($yearlevel)) {
			$this->db->where("sa.YearLevel", $yearlevel);
		}
		if (!empty($course)) {
			$this->db->where("sa.Course", $course);
		}

		$this->db->group_by("sa.StudentNumber");
		$this->db->order_by("StudentName", "ASC");
		$students = $this->db->get()->result();

		// ✅ Totals only for unique StudentNumbers
		$subQuery = $this->db->select("
			StudentNumber,
			MAX(AcctTotal) as AcctTotal,
			MAX(TotalPayments) as TotalPayments,
			MAX(Discount) as Discount,
			MAX(CurrentBalance) as CurrentBalance
		")
			->from("studeaccount")
			->where("Sem", $sem)
			->where("SY", $sy)
			->where("CurrentBalance >", 0);

		if (!empty($yearlevel)) {
			$subQuery->where("YearLevel", $yearlevel);
		}
		if (!empty($course)) {
			$subQuery->where("Course", $course);
		}

		$subQuery->group_by("StudentNumber");
		$subQuerySQL = $subQuery->get_compiled_select();

		// Use subquery to total unique students' amounts
		$this->db->select("
		SUM(AcctTotal) as TotalAcctTotal,
		SUM(TotalPayments) as TotalPayments,
		SUM(Discount) as TotalDiscount,
		SUM(CurrentBalance) as TotalBalance
	");
		$this->db->from("($subQuerySQL) as grouped");

		$totals = $this->db->get()->row();

		return [
			'students' => $students,
			'totals' => $totals,
		];
	}


	// working rn!



	public function insert_profile($data)
	{
		return $this->db->insert('studeprofile', $data);
	}

	public function update_profile($data, $StudentNumber)
	{
		$this->db->where('StudentNumber', $StudentNumber);
		return $this->db->update('studeprofile', $data);
	}


	public function insert_user_account($data)
	{
		return $this->db->insert('o_users', $data);
	}

	public function getRequirements()
	{
		return $this->db->get_where('requirements', ['is_active' => 1])->result();
	}

	public function countProfilesByEncoder($username)
	{
		return $this->db
			->where('Encoder', $username)
			->count_all_results('studeprofile');
	}


	public function getProfileEncoder($username)
	{
		return $this->db
			->where('Encoder', $username)
			->order_by('LastName', 'ASC')
			->get('studeprofile')
			->result();
	}

	public function getStudentRequirements($studentNumber)
	{
		$this->db->select('r.id as req_id, r.name, r.description, sr.date_submitted, sr.file_path, sr.is_verified, comment');
		$this->db->from('requirements r');
		$this->db->join('student_requirements sr', 'r.id = sr.requirement_id AND sr.StudentNumber = ' . $this->db->escape($studentNumber), 'left');
		return $this->db->get()->result();
	}

	public function submitRequirement($data)
	{
		return $this->db->insert('student_requirements', $data);
	}

	public function get_student_by_number($studentNumber)
	{
		return $this->db->get_where('studeprofile', ['StudentNumber' => $studentNumber])->row();
	}

	public function get_student_by_number_app($studentNumber)
	{
		return $this->db->get_where('studentsignup', ['StudentNumber' => $studentNumber])->row();
	}

	// public function getPendingRequirements()
	// {
	// 	$this->db->select("sr.id, sr.StudentNumber, CONCAT(s.LastName, ', ', s.FirstName) AS FullName, r.name as requirement_name, sr.date_submitted, sr.file_path");
	// 	$this->db->from('student_requirements sr');
	// 	$this->db->join('studeprofile s', 's.StudentNumber = sr.StudentNumber');
	// 	$this->db->join('requirements r', 'r.id = sr.requirement_id');
	// 	$this->db->where('sr.is_verified', 0);
	// 	$this->db->order_by('sr.date_submitted', 'DESC');
	// 	return $this->db->get()->result();
	// }

	public function getPendingRequirements()
	{
		$sql = "
		SELECT sr.id, sr.StudentNumber, 
		       CONCAT(sp.LastName, ', ', sp.FirstName) AS FullName, 
		       r.name AS requirement_name, 
		       sr.date_submitted, sr.file_path
		FROM student_requirements sr
		JOIN studeprofile sp ON sp.StudentNumber = sr.StudentNumber
		JOIN requirements r ON r.id = sr.requirement_id
		WHERE sr.is_verified = 0

		UNION

		SELECT sr.id, sr.StudentNumber, 
		       CONCAT(ss.LastName, ', ', ss.FirstName) AS FullName, 
		       r.name AS requirement_name, 
		       sr.date_submitted, sr.file_path
		FROM student_requirements sr
		JOIN studentsignup ss ON ss.StudentNumber = sr.StudentNumber
		JOIN requirements r ON r.id = sr.requirement_id
		WHERE sr.is_verified = 0
	";

		return $this->db->query($sql)->result();
	}


	public function approved_uploads()
	{
		$this->db->select("sr.id, sr.StudentNumber, CONCAT(s.LastName, ', ', s.FirstName) AS FullName, r.name as requirement_name, sr.date_submitted, sr.file_path");
		$this->db->from('student_requirements sr');
		$this->db->join('studeprofile s', 's.StudentNumber = sr.StudentNumber');
		$this->db->join('requirements r', 'r.id = sr.requirement_id');
		$this->db->where('sr.is_verified', 1);
		$this->db->order_by('sr.date_submitted', 'DESC');
		return $this->db->get()->result();
	}

	public function req_list()
	{
		$this->db->select('*');
		$this->db->from('requirements');
		return $this->db->get()->result();
	}


	public function approveRequirement($id, $verifier)
	{
		$data = [
			'is_verified' => 1,
			'verified_by' => $verifier,
			'verified_at' => date('Y-m-d H:i:s')
		];
		$this->db->where('id', $id);
		return $this->db->update('student_requirements', $data);
	}


	// public function get_registration_details($filters)
	// {
	// 	$this->db->select('Sem, LecUnit, LabUnit, settingsID, Course, Major, IDNumber');
	// 	$this->db->from('registration');
	// 	$this->db->where('SubjectCode', $filters['SubjectCode']);
	// 	$this->db->where('Description', $filters['Description']);
	// 	$this->db->where('Instructor', $filters['Instructor']);
	// 	$this->db->where('Section', $filters['Section']);
	// 	$this->db->where('Sem', $filters['semester']);
	// 	$this->db->limit(1); // Get only one matching record since details are shared across students
	// 	return $this->db->get()->row(); // Return a single object
	// }

	// public function get_students_by_registration($filters)
	// {
	// 	$this->db->select('*');
	// 	$this->db->from('registration r');
	// 	$this->db->join('studeprofile sp', 'sp.StudentNumber = r.StudentNumber', 'left');
	// 	$this->db->where('r.SubjectCode', $filters['SubjectCode']);
	// 	$this->db->where('r.Description', $filters['Description']);
	// 	$this->db->where('r.Instructor', $filters['Instructor']);
	// 	$this->db->where('r.Section', $filters['Section']);
	// 	$this->db->where('Sem', $filters['semester']);

	// 	return $this->db->get()->result();
	// }

	public function get_students_by_registration($filters)
	{
		$this->db->select('r.*, sp.LastName, sp.FirstName, sp.MiddleName'); // keep it tight
		$this->db->from('registration r');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = r.StudentNumber', 'left');
		$this->db->where('r.SubjectCode', $filters['SubjectCode']);
		$this->db->where('r.Description', $filters['Description']);
		$this->db->where('r.Section', $filters['Section']);
		$this->db->where('r.Sem', $filters['semester']);       // registration uses Sem
		$this->db->where('r.IDNumber', $filters['IDNumber']);  // instructor/encoder for that class
		return $this->db->get()->result();
	}

	public function get_existing_grades(array $filters)
	{
		$subject  = $filters['SubjectCode'] ?? ($filters['subjectcode'] ?? '');
		$sy       = $filters['SY']          ?? '';
		$semester = $filters['Semester']    ?? ($filters['semester'] ?? '');
		$section  = $filters['Section']     ?? ($filters['section'] ?? '');
		$idnumber = $filters['IDNumber']    ?? null; // optional

		$this->db->select("
        p.StudentNumber,
        CONCAT(p.LastName, ', ', p.FirstName) AS StudentName,
        MID(p.MiddleName,1) AS MiddleName,
        g.Course, g.Section, g.Major, g.YearLevel,
        g.Prelim, g.PrelimStat,
        g.Midterm, g.MidtermStat,
        g.PreFinal, g.PreFinalStat,
        g.Final, g.FinalStat,
        g.LecUnit, g.LabUnit,
        g.gradesid
    ");
		$this->db->from('studeprofile p');
		$this->db->join('grades_o g', 'p.StudentNumber = g.StudentNumber');
		$this->db->where('g.SubjectCode', $subject);
		$this->db->where('g.SY', $sy);
		$this->db->where('g.Semester', $semester);
		$this->db->where('g.Section', $section);

		// Only apply encoder filter if provided
		if (!empty($idnumber)) {
			$this->db->where('g.IDNumber', $idnumber);
		}

		$this->db->group_by('p.StudentNumber');
		$this->db->order_by('p.LastName');

		return $this->db->get()->result(); // array of objects
	}


	public function get_registration_details($filters)
	{
		$this->db->select('Sem, LecUnit, LabUnit, settingsID, Course, Major, IDNumber');
		$this->db->from('registration');
		$this->db->where('SubjectCode', $filters['SubjectCode']);
		$this->db->where('Description', $filters['Description']);
		$this->db->where('Section', $filters['Section']);
		$this->db->where('Sem', $filters['semester']);
		$this->db->where('IDNumber', $filters['IDNumber']);
		$this->db->limit(1);
		return $this->db->get()->row();
	}




	public function getApplicableFees($filters)
	{
		$this->db->select('Description, Amount');
		$this->db->from('fees');
		$this->db->where('SY', $filters['SY']);
		$this->db->where('Semester', $filters['Semester']);
		$this->db->where('Course', $filters['Course']);
		$this->db->where('YearLevel', $filters['YearLevel']);

		if (!empty($filters['Major'])) {
			$this->db->where('Major', $filters['Major']);
		}

		return $this->db->get()->result();
	}



	// Existing grades by student number
	// public function get_existing_grades($filters)
	// {
	// 	$this->db->where('SubjectCode', $filters['SubjectCode']);
	// 	$this->db->where('Description', $filters['Description']);
	// 	$this->db->where('Instructor', $filters['Instructor']);
	// 	$this->db->where('Section', $filters['Section']);
	// 	$this->db->where('SY', $filters['SY']);
	// 	$query = $this->db->get('grades');

	// 	$result = [];
	// 	foreach ($query->result() as $row) {
	// 		$result[$row->StudentNumber] = $row;
	// 	}
	// 	return $result;
	// }


	public function getGradeDisplay()
	{
		$query = $this->db->get('srms_settings_o');
		if ($query->num_rows() > 0) {
			return $query->row()->gradeDisplay; // Assuming only 1 row
		}
		return 'Numeric'; // Default fallback
	}



	public function getProfileByStudentNumber($studentNumber)
	{
		return $this->db->get_where('studeprofile', ['StudentNumber' => $studentNumber])->row();
	}

	// public function getRegistrationByStudent($studentNumber, $sem, $sy)
	// {
	// 	return $this->db
	// 		->where(['StudentNumber' => $studentNumber, 'Sem' => $sem, 'SY' => $sy])
	// 		->order_by('regnumber', 'desc')
	// 		->limit(1)
	// 		->get('registration')
	// 		->row();
	// }


	public function getRegistrationByStudent($studentNumber, $sem, $sy)
	{
		return $this->db
			->select('registration.*, o_users.fName, o_users.mName, o_users.lName, o_users.position')
			->from('registration')
			->join('o_users', 'o_users.username = registration.enrolledBy', 'left')
			->where([
				'registration.StudentNumber' => $studentNumber,
				'registration.Sem' => $sem,
				'registration.SY' => $sy
			])
			->order_by('registration.regnumber', 'desc')
			->limit(1)
			->get()
			->row();
	}



	public function getFeesByProgram($course, $major, $yearLevel, $sem, $sy)
	{
		$this->db->where('Course', $course);
		$this->db->where('Semester', $sem);
		$this->db->where('YearLevel', $yearLevel);

		if (!empty($major)) {
			$this->db->where('Major', $major);
		}

		return $this->db->get('fees')->result();
	}


	public function getStudentDiscount($studentNumber, $sem, $sy)
	{
		return $this->db->get_where('studediscount', [
			'StudentNumber' => $studentNumber,
			'Sem' => $sem,
			'SY' => $sy
		])->result();
	}

	public function getStudentAdditional($studentNumber, $sem, $sy)
	{
		return $this->db->get_where('studeadditional', [
			'StudentNumber' => $studentNumber,
			'Sem' => $sem,
			'SY' => $sy
		])->result();
	}


	// public function getSubjectsByStudent($studentNumber, $sem, $sy)
	// {
	// 	return $this->db
	// 		->where([
	// 			'StudentNumber' => $studentNumber,
	// 			'Sem' => $sem,
	// 			'SY' => $sy
	// 		])
	// 		->get('registration')
	// 		->result();
	// }


	public function getSubjectsByStudent($studentNumber, $sem, $sy)
	{
		$this->db->select("
        r.SubjectCode,
        r.Description,
        r.LecUnit,
        r.LabUnit,
        r.Section,
        r.SchedTime,
        r.LabTime,
        r.Room,
        r.Sem,
        r.SY,
        r.StudentNumber,
        r.Instructor      AS InstructorRaw,
        r.IDNumber        AS InstructorID,
        CASE
            WHEN UPPER(r.IDNumber) = 'TBA' THEN 'TBA'
            WHEN r.IDNumber IS NULL OR r.IDNumber = '' THEN TRIM(r.Instructor)
            WHEN s.IDNumber IS NULL THEN TRIM(r.Instructor)
            ELSE TRIM(CONCAT(
                s.LastName, ', ', s.FirstName,
                CASE WHEN s.MiddleName <> '' THEN CONCAT(' ', LEFT(s.MiddleName,1), '.') ELSE '' END,
                CASE WHEN s.NameExtn   <> '' THEN CONCAT(' ', s.NameExtn)        ELSE '' END
            ))
        END AS InstructorName
    ", FALSE);

		$this->db->from('registration AS r');
		$this->db->join('staff AS s', 's.IDNumber = r.IDNumber', 'left');
		$this->db->where([
			'r.StudentNumber' => $studentNumber,
			'r.Sem'           => $sem,
			'r.SY'            => $sy,
		]);
		// Optional ordering to keep rows tidy
		$this->db->order_by('r.SubjectCode', 'ASC');

		return $this->db->get()->result();
	}










	public function getCourseRates($course, $major, $yearLevel)
	{
		// Normalize common YearLevel variants (e.g., "1st" <-> "1st Year")
		$yl  = trim((string)$yearLevel);
		$ylN = preg_replace('/\s*Year$/i', '', $yl); // remove trailing "Year" if present

		$this->db->from('coursefees');
		$this->db->where('Course', $course);

		// Accept any of these YearLevel spellings
		$this->db->group_start()
			->where('YearLevel', $yl)
			->or_where('YearLevel', $ylN)
			->or_where('YearLevel', $ylN . ' Year')
			->group_end();

		// Prefer exact Major match -> blank/NULL Major -> everything else
		$case = "CASE
                WHEN Major = " . $this->db->escape($major) . " THEN 0
                WHEN Major = '' OR Major IS NULL THEN 1
                ELSE 2
             END";
		$this->db->order_by($case, '', false);
		$this->db->order_by('coursefeesID', 'DESC'); // latest row if duplicates

		return $this->db->get()->row();
	}




	public function Courses($sem, $sy)
	{
		$this->db->select('Course, Major, COUNT(DISTINCT StudentNumber) as total_students');
		$this->db->from('semesterstude');
		$this->db->where('Semester', $sem);
		$this->db->where('SY', $sy);
		$this->db->group_by(['Course']);
		$this->db->order_by('Course', 'ASC');
		return $this->db->get()->result();
	}
	public function MajorCount($sem, $sy)
	{
		$majorExpr = "CASE WHEN NULLIF(TRIM(s.Major),'') IS NULL THEN 'Not Set' ELSE TRIM(s.Major) END";

		return $this->db->select("$majorExpr AS Major, COUNT(DISTINCT s.StudentNumber) as Counts", false)
			->from('semesterstude s')
			->where('s.SY', $sy)
			->where('s.Semester', $sem)
			->where('s.Status', 'Enrolled')
			->group_by($majorExpr, false)
			->order_by('Major', 'ASC')
			->get()
			->result();
	}
	public function YearLevelCount($sem, $sy)
	{
		$levelExpr = "CASE WHEN NULLIF(TRIM(s.YearLevel),'') IS NULL THEN 'Not Set' ELSE TRIM(s.YearLevel) END";

		return $this->db->select("$levelExpr AS YearLevel, COUNT(DISTINCT s.StudentNumber) as Counts", false)
			->from('semesterstude s')
			->where('s.SY', $sy)
			->where('s.Semester', $sem)
			->where('s.Status', 'Enrolled')
			->group_by($levelExpr, false)
			// keeps 1st..4th natural order; falls back to alphabetical
			->order_by("FIELD(YearLevel,'1st','2nd','3rd','4th'), YearLevel", '', false)
			->get()
			->result();
	}


	public function getStudentsByCourseMajor($course, $major)
	{
		$sy  = $this->session->userdata('sy');
		$sem = $this->session->userdata('semester');

		$this->db->select('sp.StudentNumber, sp.LastName, sp.FirstName, sp.MiddleName, sp.Sex, r.YearLevel, r.Course, r.Major');
		$this->db->from('semesterstude r');
		$this->db->join('studeprofile sp', 'r.StudentNumber = sp.StudentNumber');
		$this->db->where('r.SY', $sy);
		$this->db->where('r.Semester', $sem);
		$this->db->where('r.Course', $course);
		$this->db->where('r.Major', $major);
		$this->db->group_by('sp.StudentNumber');
		$this->db->order_by('sp.LastName', 'ASC');
		return $this->db->get()->result();
	}
	public function getEnrolledCurrent($studentNo, $sy, $sem)
	{
		return $this->db->select('Course, Major, YearLevel, Semester, SY')
			->from('semesterstude')
			->where([
				'StudentNumber' => $studentNo,
				'SY'            => $sy,
				'Semester'      => $sem,
			])
			->limit(1)
			->get()
			->row();
	}



	public function SearchStudeAccounts($studentNumber, $sy, $sem = null)
	{
		$this->db->select("studeaccount.*, studeprofile.*, 
        CONCAT(studeprofile.FirstName, ' ', studeprofile.MiddleName, ' ', studeprofile.LastName) AS StudentName");
		$this->db->from('studeaccount');
		$this->db->join('studeprofile', 'studeprofile.StudentNumber = studeaccount.StudentNumber');
		$this->db->where('studeaccount.StudentNumber', $studentNumber);
		$this->db->where('studeaccount.SY', $sy);

		// 🔓 Make Sem optional
		if (!empty($sem)) {
			$this->db->where('studeaccount.Sem', $sem);
		}

		$this->db->group_by('studeaccount.StudentNumber');

		return $this->db->get()->result_array();
	}


	public function getAllCourses()
	{
		return $this->db->distinct()->select('CourseDescription')
			->from('course_table')->order_by('CourseDescription', 'ASC')->get()->result();
	}
	public function getCoursesByProgramHead($username, $idnumber)
	{
		return $this->db->distinct()->select('CourseDescription')
			->from('course_table')
			->group_start()->where('ProgramHead', $username)->or_where('IDNumber', $idnumber)->group_end()
			->order_by('CourseDescription', 'ASC')->get()->result();
	}
	public function getMajorsByCoursePH($course)
	{
		return $this->db->distinct()->select('Major')
			->from('course_table')->where('CourseDescription', $course)->where("Major !=", "")
			->order_by('Major', 'ASC')->get()->result();
	}


	public function getAllStudentsBasic($username, $idnumber)
	{
		$this->db->select('sp.StudentNumber, sp.LastName, sp.FirstName, sp.course, sp.major');
		$this->db->from('studeprofile sp');

		// join to course_table to verify Program Head ownership
		$this->db->join(
			'course_table ct',
			"ct.CourseDescription = sp.course
         AND (
            ct.Major = '' 
            OR ct.Major IS NULL 
            OR ct.Major = sp.major
         )",
			'inner'
		);

		// only courses handled by this Program Head
		$this->db->group_start()
			->where('ct.ProgramHead', $username)
			->or_where('ct.IDNumber', $idnumber)
			->group_end();

		$this->db->order_by('sp.LastName', 'ASC');
		$this->db->order_by('sp.FirstName', 'ASC');

		return $this->db->get()->result();
	}




	// In StudentModel.php
	public function getStudentByNumberPH($studentNumber)
	{
		return $this->db->select('sp.StudentNumber, sp.FirstName, sp.LastName, sp.course AS Course, sp.major AS Major')
			->from('studeprofile sp')
			->where('sp.StudentNumber', $studentNumber)
			->get()->row();
	}


	public function getCourseOptions()
	{
		return $this->db->select('Course')
			->from('subjects')
			->where('Course IS NOT NULL AND Course <> ""', null, false)
			->group_by('Course')
			->order_by('Course', 'ASC')
			->get()->result();
	}
	public function recentDocRequestTx($limit = 25, $days = 30)
	{
		$this->db->select("
            l.request_id,
            l.status,
            l.remarks,
            l.updated_by,
            l.updated_at,
            r.document_type,
            r.target_dept,
            r.StudentNumber,
            TRIM(CONCAT(
                COALESCE(sp.LastName, ''),
                CASE WHEN sp.LastName IS NOT NULL AND sp.LastName <> '' THEN ', ' ELSE '' END,
                COALESCE(sp.FirstName, ''),
                CASE WHEN sp.MiddleName IS NOT NULL AND sp.MiddleName <> '' THEN CONCAT(' ', sp.MiddleName) ELSE '' END
            )) AS full_name
        ", false);

		$this->db->from('document_request_logs l');
		$this->db->join('document_requests r', 'r.id = l.request_id', 'left');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = r.StudentNumber', 'left');

		if (!empty($days)) {
			$this->db->where('l.updated_at >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
		}

		$this->db->order_by('l.updated_at', 'DESC');
		$this->db->limit((int)$limit);

		return $this->db->get()->result();
	}
	public function get_payments_for_verification_with_denial()
	{
		// Latest denial per payment (per online_pay_deny.opID)
		$latestDenySub = "
        (
            SELECT d.*
            FROM online_pay_deny d
            JOIN (
                SELECT opID, MAX(id) AS max_id
                FROM online_pay_deny
                GROUP BY opID
            ) m ON m.opID = d.opID AND m.max_id = d.id
        ) d
    ";

		$this->db->select("
        op.id            AS opID,
        op.StudentNumber,
        op.refNo,
        op.depositAttachment,
        op.description,  
        op.amount, 
        op.sy,
        op.sem,
        op.created_at,  
        op.status,
        op.note,

        p.LastName, p.FirstName, p.MiddleName,

        d.id           AS deny_id,
        d.denyReason,
        d.deniedDate
    ", false);

		$this->db->from('online_payments op');
		$this->db->join('studeprofile p', 'p.StudentNumber = op.StudentNumber');

		// LEFT JOIN latest denial
		$this->db->join($latestDenySub, 'd.opID = op.id', 'left', false);

		// Show only “for verification” rows — adjust to your actual statuses
		$this->db->where_in('op.status', ['PENDING', 'FOR VERIFICATION']);

		$this->db->order_by('op.created_at', 'DESC');

		return $this->db->get()->result();
	}



















	public function insert_teachers()
	{
		// Start a transaction so we can return a clean status
		$this->db->trans_start();

		// Subquery: existing usernames in o_users
		$this->db->select('username')->from('o_users');
		$sub = $this->db->get_compiled_select();

		// Build the SELECT of would-be inserts from staff
		$this->db->select('IDNumber AS username', false);
		$this->db->select('SHA1(DATE_FORMAT(birthDate, "%Y-%m-%d")) AS password', false); // use correct column name
		$this->db->select("'Teacher' AS position", false);
		$this->db->select('FirstName AS fName');
		$this->db->select('MiddleName AS mName');
		$this->db->select('LastName AS lName');
		$this->db->select('empEmail AS email');
		$this->db->select("'avatar.png' AS avatar", false);
		$this->db->select("'active' AS acctStat", false);
		$this->db->select('NOW() AS dateCreated', false);
		$this->db->from('staff');

		// EXCLUDE already existing usernames using the subquery
		// where_not_in() can't accept a subquery; use a raw WHERE and don't escape
		$this->db->where("IDNumber NOT IN ($sub)", null, false);

		// OPTIONAL: only include personnel that should be Teachers
		// $this->db->where('Position', 'Teacher');

		$select = $this->db->get_compiled_select();

		// Insert new rows; relies on UNIQUE KEY on o_users.username
		$sql = "INSERT IGNORE INTO o_users
            (username, password, position, fName, mName, lName, email, avatar, acctStat, dateCreated)
            $select";

		$ok = $this->db->query($sql);
		$inserted = $this->db->affected_rows(); // number of rows actually inserted

		$this->db->trans_complete();

		return [
			'ok'       => $ok && $this->db->trans_status(),
			'inserted' => (int)$inserted
		];
	}



	public function insert_students()
	{
		$this->db->trans_start();

		// Subquery of existing usernames
		$this->db->select('username')->from('o_users');
		$sub = $this->db->get_compiled_select();

		// Build SELECT of new accounts from studeprofile
		$this->db->select('StudentNumber AS username', false);
		$this->db->select('SHA1(DATE_FORMAT(birthDate, "%Y-%m-%d")) AS password', false); // adjust to BirthDate if that’s your column
		$this->db->select("'Student' AS position", false);
		$this->db->select('FirstName AS fName');
		$this->db->select('MiddleName AS mName');
		$this->db->select('LastName AS lName');
		// Normalize email; avoid NULLs if your o_users.email is NOT NULL
		$this->db->select('IFNULL(email, "") AS email', false);
		$this->db->select("'avatar.png' AS avatar", false);
		$this->db->select("'active' AS acctStat", false);
		$this->db->select('NOW() AS dateCreated', false);
		$this->db->from('studeprofile');

		// Exclude those that already exist in o_users
		$this->db->where("StudentNumber NOT IN ($sub)", null, false);

		$select = $this->db->get_compiled_select();

		// INSERT IGNORE relies on UNIQUE(o_users.username)
		$sql = "INSERT IGNORE INTO o_users
            (username, password, position, fName, mName, lName, email, avatar, acctStat, dateCreated)
            $select";

		$ok = $this->db->query($sql);
		$inserted = $this->db->affected_rows();

		$this->db->trans_complete();

		return [
			'ok'       => $ok && $this->db->trans_status(),
			'inserted' => (int) $inserted,
		];
	}



	// application/models/StudentModel.php
	public function bySYCourseMajor($sy, $sem, $course, $major = '')
	{
		$this->db->select('
        ss.semstudentid, ss.StudentNumber, ss.Course, ss.Major, ss.YearLevel, ss.Section,
        sp.FirstName, sp.MiddleName, sp.LastName, sp.email
    ');
		$this->db->from('semesterstude ss');
		$this->db->join('studeprofile sp', 'sp.StudentNumber = ss.StudentNumber', 'left');

		$this->db->where('ss.SY', $sy);
		$this->db->where('ss.Semester', $sem);
		$this->db->where('ss.Course', $course);

		// Major can be blank in some programs – treat NULL/'' as “no major”
		if ($major === NULL || $major === '') {
			// (ss.Major IS NULL OR ss.Major = '')
			$this->db->where("(ss.Major IS NULL OR ss.Major = '')", NULL, FALSE);
		} else {
			$this->db->where('ss.Major', $major);
		}

		$this->db->order_by('sp.LastName', 'ASC');
		$this->db->order_by('sp.FirstName', 'ASC');

		return $this->db->get()->result();
	}



	// application/models/StudentModel.php

	/**
	 * Returns TRUE if the student already has an enrollment in this SY+Sem
	 * for any course/major, filtered by statuses (default: Enrolled).
	 */
	public function hasAnyEnrollmentThisTerm($studentNumber, $sy, $sem, $statuses = ['Enrolled'])
	{
		$this->db->from('semesterstude');
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		if (!empty($statuses)) {
			$this->db->where_in('Status', (array)$statuses);
		}
		return $this->db->count_all_results() > 0;
	}

	/**
	 * Fetch an existing enrollment (for messaging).
	 */
	public function getAnyEnrollmentThisTerm($studentNumber, $sy, $sem, $statuses = ['Enrolled'])
	{
		$this->db->select('semstudentid, StudentNumber, Course, Major, YearLevel, Section, Status, Semester, SY, enroledDate');
		$this->db->from('semesterstude');
		$this->db->where('StudentNumber', $studentNumber);
		$this->db->where('SY', $sy);
		$this->db->where('Semester', $sem);
		if (!empty($statuses)) {
			$this->db->where_in('Status', (array)$statuses);
		}
		$this->db->order_by('enroledDate', 'DESC');
		$this->db->order_by('semstudentid', 'DESC');
		return $this->db->get()->row();
	}
	/**
	 * Per-requester document summary from `document_requests`.
	 * Returns one row per StudentNumber with:
	 * - full_name (from studeprofile, if available)
	 * - doc_summary  e.g., "COR (3) • TOR (1)"
	 * - total_requests
	 * - last_requested (latest request_date)
	 *
	 * @param  string|null $from  Optional start datetime (e.g., '2025-09-01 00:00:00')
	 * @param  string|null $to    Optional end   datetime (e.g., '2025-09-30 23:59:59')
	 * @return array              CI result() objects
	 */
	public function docsByRequester($from = null, $to = null)
	{
		$params = [];
		$dateWhere = '';

		if ($from && $to) {
			$dateWhere = 'WHERE dr.request_date BETWEEN ? AND ?';
			$params[] = $from;
			$params[] = $to;
		} elseif ($from) {
			$dateWhere = 'WHERE dr.request_date >= ?';
			$params[] = $from;
		} elseif ($to) {
			$dateWhere = 'WHERE dr.request_date <= ?';
			$params[] = $to;
		}

		$sql = "
        SELECT
            sub.StudentNumber,
            TRIM(CONCAT(COALESCE(p.FirstName,''),' ',COALESCE(p.LastName,''))) AS full_name,
            GROUP_CONCAT(CONCAT(sub.document_type, ' (', sub.cnt, ')')
                         ORDER BY sub.cnt DESC SEPARATOR ' • ')              AS doc_summary,
            SUM(sub.cnt)                                                    AS total_requests,
            MAX(sub.last_req)                                               AS last_requested
        FROM (
            SELECT
                dr.StudentNumber,
                dr.document_type,
                COUNT(*)            AS cnt,
                MAX(dr.request_date) AS last_req
            FROM document_requests dr
            {$dateWhere}
            GROUP BY dr.StudentNumber, dr.document_type
        ) sub
        LEFT JOIN studeprofile p ON p.StudentNumber = sub.StudentNumber
        GROUP BY sub.StudentNumber
        ORDER BY total_requests DESC, last_requested DESC
    ";

		return $this->db->query($sql, $params)->result();
	}


	public function studeRequestListV2()
	{
		$sql = "
        SELECT
            dr.id AS trackingNo,
            COALESCE(p.FirstName, '') AS FirstName,
            COALESCE(p.LastName, '')  AS LastName,
            dr.document_type AS docName,
            dr.purpose,
            DATE_FORMAT(dr.request_date, '%Y-%m-%d') AS dateReq,
            DATE_FORMAT(dr.request_date, '%h:%i %p') AS timeReq
        FROM document_requests dr
        LEFT JOIN studeprofile p ON p.StudentNumber = dr.StudentNumber
        ORDER BY dr.request_date DESC, dr.id DESC
    ";
		return $this->db->query($sql)->result();
	}

	public function docReqCountsV2($from = null, $to = null, $status = null, $dept = null)
	{
		$params = [];
		$where = ["dr.document_type IS NOT NULL", "dr.document_type <> ''"];

		if ($from) {
			$where[] = "dr.request_date >= ?";
			$params[] = $from;
		}
		if ($to) {
			$where[] = "dr.request_date <= ?";
			$params[] = $to;
		}
		if ($status) {
			$where[] = "dr.status = ?";
			$params[] = $status;
		}
		if ($dept) {
			$where[] = "dr.target_dept = ?";
			$params[] = $dept;
		}

		$wsql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

		$sql = "
        SELECT
            dr.document_type AS docName,
            COUNT(*)         AS docCounts
        FROM document_requests dr
        $wsql
        GROUP BY dr.document_type
        ORDER BY docCounts DESC, docName ASC
    ";

		return $this->db->query($sql, $params)->result();
	}
	public function profileListAll()
	{
		$sql = "
      SELECT sp.StudentNumber, sp.LastName, sp.FirstName, sp.MiddleName, sp.birthDate, 0 AS is_signup_only
      FROM studeprofile sp
      UNION ALL
      SELECT su.StudentNumber, su.LastName, su.FirstName, su.MiddleName, su.birthDate, 1 AS is_signup_only
      FROM studentsignup su
      WHERE NOT EXISTS (SELECT 1 FROM studeprofile sp2 WHERE sp2.StudentNumber = su.StudentNumber)
      ORDER BY LastName, FirstName, StudentNumber
    ";
		return $this->db->query($sql)->result();
	}
	public function totalSignupsByStatus($status)
	{
		return $this->db->select('COUNT(*) AS StudeCount')
			->where('Status', $status)   // e.g. 'For Confirmation'
			->get('studentsignup')->result();
	}

	// List: show all signups (keep aliases so views don't change)
	public function signupListAll()
	{
		// If your column names differ, alias them here to match the view fields
		return $this->db->select("
                StudentNumber,
                TRIM(LastName)   AS LastName,
                TRIM(FirstName)  AS FirstName,
                TRIM(MiddleName) AS MiddleName,
                birthDate        AS birthDate,
                yearLevel        AS yearLevel,
                section          AS section,
                Status           AS signupStatus,
                EnrollmentDate   AS EnrollmentDate
           ")
			->from('studentsignup')
			->order_by('LastName, FirstName, StudentNumber')
			->get()
			->result();
	}
	// studentsignup → Masterlist by Grade Level (returns aliases the view needs)
	public function signupByGradeLevel($yearlevel)
	{
		// Course alias: prefer Course1, then Course2, then Course3
		$courseExpr = "COALESCE(NULLIF(Course1, ''), NULLIF(Course2, ''), NULLIF(Course3, ''))";

		return $this->db->select("
                StudentNumber,
                TRIM(LastName)   AS LastName,
                TRIM(FirstName)  AS FirstName,
                TRIM(MiddleName) AS MiddleName,
                birthDate,
                {$courseExpr}    AS Course, 
                section          AS Section, 
                email  
           ", false)
			->from('studentsignup')
			->where('yearLevel', $yearlevel)
			->order_by('LastName, FirstName, StudentNumber')
			->get()
			->result();
	}

	// Enrollment Summary for the same list (Course vs enrollees)
	public function signupByGradeLevelCount($yearlevel)
	{
		$courseExpr = "COALESCE(NULLIF(Course1, ''), NULLIF(Course2, ''), NULLIF(Course3, ''))";

		return $this->db->select("{$courseExpr} AS Course, COUNT(*) AS enrollees", false)
			->from('studentsignup')
			->where('yearLevel', $yearlevel)
			->group_by('Course')
			->order_by('Course')
			->get()
			->result();
	}
	public function get_majors($courseDescription = null)
	{
		$this->db->distinct();
		$this->db->select('Major');
		$this->db->from('course_table');

		// Use the correct column name `CourseDescription` for filtering
		if ($courseDescription) {
			$this->db->where('CourseDescription', $courseDescription);  // Filter by the correct column
		}

		$query = $this->db->get();
		return $query->result();  // Return the result as an array of majors
	}

	public function get_courseTable()
	{
		// Make sure you're selecting the correct column
		$this->db->select('courseid, CourseCode, CourseDescription');
		$this->db->from('course_table');
		$query = $this->db->get();

		// Check if the query executed successfully
		if ($query->num_rows() > 0) {
			return $query->result(); // Return the result set
		} else {
			return []; // Return an empty array if no results
		}
	}
	// In StudentModel.php
	public function get_sections($courseid = null, $year_level = null)
	{
		$this->db->select('id, section, year_level');
		$this->db->from('course_sections');

		// Apply filters if provided
		if ($courseid) {
			$this->db->where('courseid', $courseid);
		}
		if ($year_level) {
			$this->db->where('year_level', $year_level);
		}

		$this->db->where('is_active', 1);  // Only fetch active sections
		$query = $this->db->get();

		return $query->result();  // Return the result as an array of sections
	}


	public function get_year_levels()
	{
		$this->db->distinct();
		$this->db->select('yearLevel');
		$query = $this->db->get('subjects');
		return $query->result();
	}
}

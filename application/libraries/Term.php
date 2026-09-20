<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Centralised academic-term (semester + school year) service.
 *
 * The active term lives in o_srms_settings.active_sem / active_sy and is
 * stamped onto the session at login. Every module that scopes data to a
 * term should go through here instead of reading the settings row or the
 * session piecemeal, so the behaviour stays consistent everywhere.
 */
class Term
{
	const SEMESTERS = ['First Semester', 'Second Semester'];

	private $CI;
	private $activeCache = null;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
	}

	/** The institution-wide active term from o_srms_settings. */
	public function active()
	{
		if ($this->activeCache !== null) {
			return $this->activeCache;
		}

		$row = $this->CI->db->select('active_sem, active_sy')
			->from('o_srms_settings')
			->limit(1)
			->get()
			->row();

		$this->activeCache = [
			'sem' => trim((string)($row->active_sem ?? '')),
			'sy'  => trim((string)($row->active_sy ?? '')),
		];

		return $this->activeCache;
	}

	/**
	 * The term this request should operate on: the session's term when the
	 * user picked one at login, otherwise the global active term.
	 */
	public function current()
	{
		$sem = '';
		$sy  = '';

		if (isset($this->CI->session)) {
			$sem = trim((string)$this->CI->session->userdata('semester'));
			$sy  = trim((string)$this->CI->session->userdata('sy'));
		}

		$active = $this->active();
		if ($sem === '') {
			$sem = $active['sem'];
		}
		if ($sy === '') {
			$sy = $active['sy'];
		}

		return [$sem, $sy];
	}

	/**
	 * Re-stamp the session's term from the global active term. Called once
	 * per request by TermSyncHook so changing the active term in Settings
	 * reaches already-logged-in users without forcing re-login.
	 */
	public function syncSession()
	{
		if (!isset($this->CI->session)) {
			return;
		}
		if ($this->CI->session->userdata('logged_in') !== TRUE) {
			return;
		}

		$active = $this->active();
		if ($active['sem'] === '' || $active['sy'] === '') {
			return;
		}

		if ($this->CI->session->userdata('semester') !== $active['sem']
			|| $this->CI->session->userdata('sy') !== $active['sy']) {
			$this->CI->session->set_userdata('semester', $active['sem']);
			$this->CI->session->set_userdata('sy', $active['sy']);
		}
	}

	public function setActive($sem, $sy)
	{
		$row = $this->CI->db->select('settingsID')->from('o_srms_settings')->limit(1)->get()->row();
		$data = ['active_sem' => $sem, 'active_sy' => $sy];

		$ok = $row
			? $this->CI->db->where('settingsID', (int)$row->settingsID)->update('o_srms_settings', $data)
			: $this->CI->db->insert('o_srms_settings', $data);

		if ($ok) {
			$this->activeCache = ['sem' => $sem, 'sy' => $sy];
		}
		return $ok;
	}

	public function semesters()
	{
		return self::SEMESTERS;
	}

	/**
	 * School years that exist in the data plus the next upcoming one, so the
	 * picker always offers a valid choice without free-text typos.
	 */
	public function schoolYears()
	{
		$years = [];
		$rows = $this->CI->db->select('SY')->distinct()->from('semesterstude')->get()->result();
		foreach ($rows as $r) {
			$y = trim((string)$r->SY);
			if ($this->isValidSy($y)) {
				$years[$y] = true;
			}
		}

		$active = $this->active();
		if ($this->isValidSy($active['sy'])) {
			$years[$active['sy']] = true;
			// Offer the following year too — activating next year's term before
			// anyone is enrolled in it must be possible.
			$years[$this->nextSy($active['sy'])] = true;
		}

		$list = array_keys($years);
		usort($list, function ($a, $b) {
			return (int)substr($a, 0, 4) <=> (int)substr($b, 0, 4);
		});
		return $list;
	}

	/**
	 * Every Semester+SY pair present in the enrolment data, with the number
	 * of enrolled students in each — this is what the term manager lists.
	 */
	public function terms()
	{
		$rows = $this->CI->db->select('Semester, SY, COUNT(DISTINCT StudentNumber) AS enrollees', false)
			->from('semesterstude')
			->where("Semester <> ''", null, false)
			->where("SY <> ''", null, false)
			->group_by(['SY', 'Semester'])
			->get()
			->result();

		usort($rows, function ($a, $b) {
			$ay = (int)substr((string)$a->SY, 0, 4);
			$by = (int)substr((string)$b->SY, 0, 4);
			if ($ay !== $by) {
				return $by <=> $ay; // newest school year first
			}
			return (int)array_search((string)$a->Semester, self::SEMESTERS, true)
				<=> (int)array_search((string)$b->Semester, self::SEMESTERS, true);
		});

		return $rows;
	}

	public function isValidSem($sem)
	{
		return in_array((string)$sem, self::SEMESTERS, true);
	}

	public function isValidSy($sy)
	{
		return preg_match('/^\d{4}-\d{4}$/', (string)$sy) === 1
			&& ((int)substr($sy, 5, 4) === (int)substr($sy, 0, 4) + 1);
	}

	public function nextSy($sy)
	{
		if (preg_match('/^(\d{4})-\d{4}$/', (string)$sy, $m) !== 1) {
			return '';
		}
		$start = (int)$m[1];
		return $start . '-' . ($start + 1);
	}

	/**
	 * Open ledger accounts (studeaccount) for every student enrolled in the
	 * given term who does not have one yet.
	 *
	 * Rows are inserted as zero-amount shells — the per-student assessment
	 * (Student/view_account → save_studeaccount) still sets the real fees.
	 * A shell only guarantees payments have an account to attach to; the
	 * assessment guards treat AcctTotal = 0 rows as "not assessed yet" so
	 * shells never block the manual flow.
	 *
	 * @return int number of accounts created
	 */
	public function provisionAccounts($sem, $sy)
	{
		$enrollees = $this->CI->db
			->select('StudentNumber, Course, Major, YearLevel, Section')
			->from('semesterstude')
			->where('Semester', $sem)
			->where('SY', $sy)
			->get()
			->result();

		if (empty($enrollees)) {
			return 0;
		}

		$settingsId = (int)($this->CI->db->select('settingsID')
			->from('o_srms_settings')->limit(1)->get()->row()->settingsID ?? 0);

		$existing = [];
		$rows = $this->CI->db->select('StudentNumber')->distinct()
			->from('studeaccount')
			->where('Sem', $sem)
			->where('SY', $sy)
			->get()
			->result();
		foreach ($rows as $r) {
			$existing[trim((string)$r->StudentNumber)] = true;
		}

		// Payments may already exist for the term (taken before the account
		// was opened) — carry them into the shell so the ledger is truthful.
		$paid = [];
		$payRows = $this->CI->db->select('StudentNumber, COALESCE(SUM(Amount),0) AS paid', false)
			->from('paymentsaccounts')
			->where('Sem', $sem)
			->where('SY', $sy)
			->where('ORStatus', 'Valid')
			->where('CollectionSource', "Student's Account")
			->group_by('StudentNumber')
			->get()
			->result();
		foreach ($payRows as $p) {
			$paid[trim((string)$p->StudentNumber)] = (float)$p->paid;
		}

		$created = 0;
		foreach ($enrollees as $e) {
			$sn = trim((string)$e->StudentNumber);
			if ($sn === '' || isset($existing[$sn])) {
				continue;
			}

			$this->CI->db->insert('studeaccount', [
				'StudentNumber' => $sn,
				'Course'        => (string)$e->Course,
				'Major'         => (string)$e->Major,
				'YearLevel'     => (string)$e->YearLevel,
				'Section'       => (string)$e->Section,
				'FeesDesc'      => '',
				'feesType'      => 'Shell',
				'TotalPayments' => (float)($paid[$sn] ?? 0),
				'Sem'           => $sem,
				'SY'            => $sy,
				'settingsID'    => $settingsId,
			]);
			$created++;
		}

		return $created;
	}
}

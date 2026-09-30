<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Which students a cashier may book a payment against.
 *
 * Only students whose login account is Active -- the "Active" chip on
 * Page/profileList, i.e. o_users.acctStat = 'active' -- are payable. Pending,
 * inactive and signups with no account yet are left out, so a payment cannot
 * be tagged to an account that was never verified or has been switched off.
 *
 * Used by both the web cashier (Accounting) and the mobile API
 * (api/MobileAccounting): the picker lists only these students, and every
 * save re-checks, since a posted StudentNumber is not limited to the picker.
 */
class Payable_students
{
    const NOT_ACTIVE_MESSAGE = 'Payments can only be entered for students with an Active account (see Registered Students). This student\'s account is not active.';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Active accounts keyed by lower-cased, trimmed username (= StudentNumber).
     *
     * @return array<string, object> fName/mName/lName per student
     */
    public function activeAccounts()
    {
        $rows = $this->CI->db->select('username, fName, mName, lName')
            ->from('o_users')
            ->where("LOWER(TRIM(acctStat)) = 'active'", null, false)
            ->get()
            ->result();

        $active = [];
        foreach ($rows as $r) {
            $key = strtolower(trim((string)$r->username));
            if ($key !== '') {
                $active[$key] = $r;
            }
        }
        return $active;
    }

    /** TRUE when the student's login account is Active. */
    public function isActive($studentNumber)
    {
        $studentNumber = trim((string)$studentNumber);
        if ($studentNumber === '') {
            return false;
        }

        $row = $this->CI->db->select('acctStat')
            ->from('o_users')
            ->where('username', $studentNumber)
            ->limit(1)
            ->get()
            ->row();

        return $row !== null && strtolower(trim((string)$row->acctStat)) === 'active';
    }

    /**
     * Keep only students with an Active account. A student listed with no
     * name (no signup or profile row) takes it from their login account.
     *
     * @param object[] $students rows with StudentNumber/FirstName/MiddleName/LastName
     * @return object[]
     */
    public function filter(array $students)
    {
        $active = $this->activeAccounts();

        $kept = [];
        foreach ($students as $s) {
            $account = $active[strtolower(trim((string)$s->StudentNumber))] ?? null;
            if ($account === null) {
                continue;
            }
            if (trim((string)$s->FirstName) === '' && trim((string)$s->LastName) === '') {
                $s->FirstName  = trim((string)$account->fName);
                $s->MiddleName = trim((string)$account->mName);
                $s->LastName   = trim((string)$account->lName);
            }
            $kept[] = $s;
        }
        return $kept;
    }
}

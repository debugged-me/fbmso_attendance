<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * A student with payment records cannot be deleted.
 *
 * Receipts, balances and the collection reports are keyed to the student
 * number; deleting the student (signup, profile or login account) would leave
 * those payments pointing at nobody. Every web and mobile path that deletes a
 * student asks this library first.
 *
 * "Payment records" means any row in the payment tables -- including the
 * payment audit log, so a student whose payment was later deleted is still
 * protected: the log entry must keep naming a real student.
 */
class Student_payment_guard
{
    const MESSAGE = 'This student has payment records, so the account cannot be deleted. Payments and receipts must stay linked to the student.';

    /** @var CI_Controller */
    protected $CI;

    /** @var array|null [table, column] pairs present on this install */
    protected $tables = null;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /** TRUE when any payment table has a row for this student number. */
    public function hasPaymentRecords($studentNumber)
    {
        $studentNumber = trim((string)$studentNumber);
        if ($studentNumber === '') {
            return false;
        }

        foreach ($this->paymentTables() as $t) {
            if ($this->CI->db->where($t[1], $studentNumber)->count_all_results($t[0]) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Every student number with payment records, as a lookup set keyed by
     * lower-cased student number -- for marking rows in a list.
     *
     * @return array<string, true>
     */
    public function studentsWithPayments()
    {
        $set = [];
        foreach ($this->paymentTables() as $t) {
            $rows = $this->CI->db->distinct()->select($t[1] . ' AS sn', false)->get($t[0])->result();
            foreach ($rows as $r) {
                $key = strtolower(trim((string)$r->sn));
                if ($key !== '') {
                    $set[$key] = true;
                }
            }
        }
        return $set;
    }

    protected function paymentTables()
    {
        if ($this->tables === null) {
            $db = $this->CI->db;
            $this->tables = array_values(array_filter(
                [['paymentsaccounts', 'StudentNumber'], ['online_payments', 'StudentNumber'], ['payment_audit_log', 'student_number']],
                function ($t) use ($db) {
                    return $db->table_exists($t[0]) && $db->field_exists($t[1], $t[0]);
                }
            ));
        }
        return $this->tables;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * One test account per role, for checking sign-in and the audit trail.
 *
 *   php index.php testaccounts create [super_admin_username] [password]
 *   php index.php testaccounts status
 *   php index.php testaccounts remove [super_admin_username]
 *
 * CLI only: create prints a working password, so this must never answer a
 * browser. Over HTTP every method is a plain 404.
 *
 * The Super Admin comes first because CodeIgniter drops empty CLI arguments:
 * with the password first, a typo in the username would silently become the
 * password. Leave the password out to get a random one. A password given on
 * the command line travels as a URI segment, so it may only use letters,
 * digits and . _ - ~
 *
 * create makes (or resets) an account for each level the User Accounts page
 * can create -- Admin, Auditor, Cashier, Committee, Student -- plus the test
 * student's profile and an enrolment in the active term, so the test cashier
 * has someone to take a payment from. Each account is written to audit_logs
 * as created by the named Super Admin (default: superadmin), the same row the
 * User Accounts page writes when that Super Admin adds a user.
 *
 * Every test address is on the .invalid domain, which the mail queue skips,
 * so test sign-ins and test receipts never send real email.
 */
class Testaccounts extends CI_Controller
{
    const EMAIL_DOMAIN   = 'fbmso-test.invalid';
    const STUDENT_NUMBER = 'TEST-0001';

    /** level => array(username, first name, last name) */
    private $accounts = array(
        'Admin'     => array('test.admin', 'Test', 'Admin'),
        'Auditor'   => array('test.auditor', 'Test', 'Auditor'),
        'Cashier'   => array('test.cashier', 'Test', 'Cashier'),
        'Committee' => array('test.committee', 'Test', 'Committee'),
        'Student'   => array(self::STUDENT_NUMBER, 'Test', 'Student'),
    );

    public function __construct()
    {
        parent::__construct();

        if (!is_cli()) {
            show_404();
        }

        $this->load->model('AuditLogModel');
        $this->load->library('term');
    }

    public function index()
    {
        echo "Usage:\n"
           . "  php index.php testaccounts create [super_admin_username] [password]\n"
           . "  php index.php testaccounts status\n"
           . "  php index.php testaccounts remove [super_admin_username]\n";
    }

    /** Create the test accounts, or reset them if they already exist. */
    public function create($actor = 'superadmin', $password = '')
    {
        $actor = $this->super_admin($actor);
        if ($actor === null) {
            return;
        }

        $password = trim((string)$password);
        if ($password === '') {
            $password = 'Fbmso-' . bin2hex(random_bytes(4));
        }
        $hash = fbmso_password_hash($password);
        if ($hash === '') {
            echo "That password cannot be used (empty, over 72 bytes, or contains a NUL byte).\n";
            return;
        }

        list($sem, $sy) = $this->term->current();

        $done = array();
        foreach ($this->accounts as $level => $a) {
            list($username, $fName, $lName) = $a;
            $email = $this->email_for($username);

            $existing = $this->db->where('username', $username)->limit(1)->get('o_users')->row();
            if ($existing && !$this->is_test_email($existing->email)) {
                echo "  ! {$username}: a real account already uses this username -- left untouched.\n";
                continue;
            }

            $row = array(
                'password'    => $hash,
                'position'    => $level,
                'fName'       => $fName,
                'mName'       => '',
                'lName'       => $lName,
                'email'       => $email,
                'avatar'      => 'avatar.png',
                'acctStat'    => 'Active',
                'name'        => $fName . ' ' . $lName,
                'IDNumber'    => $username,
            );
            if ($this->db->field_exists('force_change_password', 'o_users')) {
                // Testers sign straight in; the change-password flow can be
                // tested on its own from the profile menu.
                $row['force_change_password'] = 0;
            }

            if ($existing) {
                $ok = $this->db->where('username', $username)->update('o_users', $row);
            } else {
                $row['username']    = $username;
                $row['dateCreated'] = date('Y-m-d');
                $ok = $this->db->insert('o_users', $row);
            }

            $fields = array('username' => $username, 'position' => $level, 'email' => $email, 'IDNumber' => $username);
            $this->AuditLogModel->write(
                $existing ? 'update' : 'create',
                'User Accounts',
                'o_users',
                $username,
                null,
                $fields,
                $ok ? 1 : 0,
                $existing ? 'Reset test account (testaccounts CLI)' : 'Created test account (testaccounts CLI)',
                array('_actor_username' => $actor, '_actor_level' => 'Super Admin', 'via' => 'cli')
            );

            if (!$ok) {
                echo "  ! {$username}: could not be saved.\n";
                continue;
            }
            $done[$level] = $username;
        }

        if (isset($done['Student'])) {
            $this->ensure_test_student($sem, $sy, $actor);
        }

        echo "\nTest accounts (" . count($done) . "), attributed to Super Admin '{$actor}':\n\n";
        foreach ($done as $level => $username) {
            printf("  %-10s %s\n", $level, $username);
        }
        echo "\n  Password for all of them: {$password}\n";
        if ($sem !== '' || $sy !== '') {
            echo "  Test student " . self::STUDENT_NUMBER . " is enrolled in {$sem} {$sy}.\n";
        }
        echo "\nSign in as each, use the pages that role has, then check Super Admin > Audit Trail,\n"
           . "or run:  php index.php testaccounts status\n";
    }

    /** What the audit trail has recorded for each test account. */
    public function status()
    {
        // Security events name the account as actor once signed in, and as
        // target before that (sign-in attempts), so count both.
        $sources = array(
            'login_logs'          => array('login_logs', array('username')),
            'audit_logs'          => array('audit_logs', array('username')),
            'security_audit_logs' => array('security_audit_logs', array('actor_username', 'target_username')),
            'payment_audit_log'   => array('payment_audit_log', array('changed_by')),
        );

        foreach ($this->accounts as $level => $a) {
            $username = $a[0];
            $user = $this->db->where('username', $username)->limit(1)->get('o_users')->row();

            echo "\n{$level}: {$username}";
            if (!$user) {
                echo "  (not created)\n";
                continue;
            }
            echo '  [' . $user->acctStat . ", level {$user->position}]\n";

            $counts = array();
            foreach ($sources as $label => $src) {
                list($table, $columns) = $src;
                if (!$this->db->table_exists($table)) {
                    $counts[] = $label . '=n/a';
                    continue;
                }
                $this->db->group_start();
                foreach ($columns as $column) {
                    $this->db->or_where($column, $username);
                }
                $this->db->group_end();
                $counts[] = $label . '=' . $this->db->count_all_results($table);
            }
            echo '  logged: ' . implode('  ', $counts) . "\n";

            // Same query as Super Admin > Audit Trail, so what shows here is
            // what shows there.
            $events = $this->AuditLogModel->getUnified(array('role' => $level, 'q' => $username), 8);
            if (!$events) {
                echo "  audit trail: nothing yet\n";
                continue;
            }
            foreach ($events as $e) {
                printf("  %s  %-8s %-26s %s%s\n",
                    $e['event_time'],
                    $e['source'],
                    strtoupper(str_replace('_', ' ', (string)$e['action'])),
                    (string)$e['description'],
                    (int)$e['succeeded'] === 1 ? '' : '  [failed/denied]'
                );
            }
        }
        echo "\n";
    }

    /** Delete the test accounts and the test student's records. */
    public function remove($actor = 'superadmin')
    {
        $actor = $this->super_admin($actor);
        if ($actor === null) {
            return;
        }

        $this->load->library('sessionregistry');

        foreach ($this->accounts as $level => $a) {
            $username = $a[0];
            $user = $this->db->where('username', $username)->limit(1)->get('o_users')->row();
            if (!$user) {
                continue;
            }
            if (!$this->is_test_email($user->email)) {
                echo "  ! {$username}: not a test account -- left untouched.\n";
                continue;
            }

            $this->sessionregistry->revokeAllForUser($username, 'test account removed');
            if ($this->db->table_exists('o_mobile_tokens')) {
                $this->db->where('username', $username)->update('o_mobile_tokens', array('revoked' => 1));
            }

            $ok = $this->db->where('username', $username)->delete('o_users');
            $this->AuditLogModel->write(
                'delete',
                'User Accounts',
                'o_users',
                $username,
                array('username' => $username, 'position' => $user->position, 'email' => $user->email),
                null,
                $ok ? 1 : 0,
                'Deleted test account (testaccounts CLI)',
                array('_actor_username' => $actor, '_actor_level' => 'Super Admin', 'via' => 'cli')
            );
            echo "  removed {$level} {$username}\n";
        }

        $profile = $this->db->where('StudentNumber', self::STUDENT_NUMBER)->limit(1)->get('studeprofile')->row();
        if ($profile && $this->is_test_email($profile->email ?? '')) {
            $this->db->where('StudentNumber', self::STUDENT_NUMBER)->delete('semesterstude');
            $this->db->where('StudentNumber', self::STUDENT_NUMBER)->delete('studeprofile');
            echo '  removed test student profile ' . self::STUDENT_NUMBER . "\n";
        }

        // Payments are money records: they are deleted from the Payment
        // screen, which logs the deletion, never silently from here.
        $payments = $this->db->where('StudentNumber', self::STUDENT_NUMBER)->count_all_results('paymentsaccounts');
        if ($payments > 0) {
            echo "\n  Note: {$payments} test payment(s) for " . self::STUDENT_NUMBER . " are still on file.\n"
               . "  Delete them from Accounting > Payment (as Admin or Cashier) so reports stay clean.\n";
        }
        echo "Audit trail entries are kept.\n";
    }

    /** Profile + active-term enrolment, so the test student is payable. */
    private function ensure_test_student($sem, $sy, $actor)
    {
        $sn = self::STUDENT_NUMBER;

        $profile = $this->db->where('StudentNumber', $sn)->limit(1)->get('studeprofile')->row();
        if ($profile && !$this->is_test_email($profile->email ?? '')) {
            echo "  ! studeprofile {$sn} belongs to a real student -- left untouched.\n";
            return;
        }
        if (!$profile) {
            $this->db->insert('studeprofile', $this->existing_columns('studeprofile', array(
                'StudentNumber' => $sn,
                'FirstName'     => 'Test',
                'MiddleName'    => '',
                'LastName'      => 'Student',
                'email'         => $this->email_for($sn),
                'course'        => 'TEST COURSE',
                'major'         => '',
                'yearLevel'     => '1st',
                'Encoder'       => $actor,
            )));
        }

        if ($sem === '' || $sy === '') {
            return;
        }

        $enrolled = $this->db->where('StudentNumber', $sn)->where('Semester', $sem)->where('SY', $sy)
            ->count_all_results('semesterstude');
        if (!$enrolled) {
            $this->db->insert('semesterstude', $this->existing_columns('semesterstude', array(
                'StudentNumber' => $sn,
                'Course'        => 'TEST COURSE',
                'Major'         => '',
                'YearLevel'     => '1st',
                'Section'       => 'TEST',
                'Semester'      => $sem,
                'SY'            => $sy,
                'Status'        => 'Enrolled',
            )));
        }
    }

    /** Drop keys for columns this installation's table does not have. */
    private function existing_columns($table, array $row)
    {
        $fields = array_map('strtolower', $this->db->list_fields($table));

        return array_filter($row, function ($column) use ($fields) {
            return in_array(strtolower($column), $fields, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /** The named account, if it is an active Super Admin. */
    private function super_admin($username)
    {
        $username = trim((string)$username) ?: 'superadmin';
        $row = $this->db->select('username, position, acctStat')->where('username', $username)
            ->limit(1)->get('o_users')->row();

        if (!$row || $row->position !== 'Super Admin' || strtolower((string)$row->acctStat) !== 'active') {
            echo "'{$username}' is not an active Super Admin account. Pass the Super Admin username to attribute this to.\n";
            return null;
        }

        return $row->username;
    }

    private function email_for($username)
    {
        return strtolower(str_replace(array(' ', '_'), array('', '.'), $username)) . '@' . self::EMAIL_DOMAIN;
    }

    private function is_test_email($email)
    {
        $suffix = '@' . self::EMAIL_DOMAIN;

        return substr(strtolower(trim((string)$email)), -strlen($suffix)) === $suffix;
    }
}

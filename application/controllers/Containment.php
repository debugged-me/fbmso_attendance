<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Incident containment. CLI only -- never reachable over HTTP.
 *
 *   php index.php containment status
 *   php index.php containment privileged            (dry run)
 *   php index.php containment privileged --apply
 *   php index.php containment weak                  (dry run)
 *   php index.php containment weak --apply
 *   php index.php containment restore <batch>
 *
 * Every command is a DRY RUN unless --apply is passed, and every rotation
 * snapshots the old hash into password_rotation_backup first, so a mistake
 * is recoverable and the pre-rotation hashes survive as evidence.
 *
 * Two different treatments, deliberately:
 *
 *   privileged  gets a NEW password, printed once on this terminal. Locking
 *               an administrator out of the system they administer during an
 *               incident is how you lose control of the response.
 *   weak        gets locked, and the holder recovers through forgot-password.
 *               There are hundreds of them and no way to hand out passwords.
 */
class Containment extends CI_Controller
{
    /** The password the 2026-08-28 attacker used on account 2025-0116. */
    const COMPROMISED_SHA1 = '2fbd3e72682117dfad3ce0089afa803b021bf80b';

    /**
     * Accounts confirmed during the investigation to have held that password,
     * named explicitly rather than found by hash.
     *
     * Hash matching only works while an account is still on sha1. Every
     * successful login rewrites one to bcrypt -- salted, so the same password
     * no longer produces a matching hash. Between the investigation and this
     * tool being written, 2025-0116 and superadmin both signed in and dropped
     * out of the query. Their passwords did not change; only the storage did.
     *
     * Without this list, containment would silently skip the Super Admin
     * account, which is the one that matters most.
     */
    private $knownCompromised = array('2025-0116', 'jurashinju', 'superadmin');

    /** Known-weak passwords found among current accounts. */
    private $weakHashes = array(
        '7c222fb2927d828af22f592134e8932480637c0d' => '12345678',
        '7c4a8d09ca3762af61e59520943dc26494f8941b' => '123456',
        '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8' => 'password',
        'd033e22ae348aeb5660fc2140aec35850c4da997' => 'admin',
        '7110eda4d09e062aa5e4a390b0a572ac0d2c0220' => '1234',
    );

    public function __construct()
    {
        parent::__construct();

        if (!is_cli() && !$this->input->is_cli_request()) {
            show_404();
        }

        $this->load->library('securityaudit');
    }

    // ------------------------------------------------------------------

    /** What is still exposed right now. Read-only. */
    public function status()
    {
        $comp = $this->compromisedAccounts();
        $weak = $this->weakPasswordAccounts();
        $bday = $this->birthdateAccounts();

        $t = $this->db->query(
            "SELECT SUM(LENGTH(password)=40) sha1, SUM(password LIKE '\$2y\$%') bcrypt,
                    SUM(password LIKE '!locked:%') locked, COUNT(*) total FROM o_users"
        )->row();

        echo "\nCONTAINMENT STATUS\n";
        echo str_repeat('=', 62) . "\n";
        printf("  Accounts on the attacker's password : %d\n", count($comp));
        foreach ($comp as $r) {
            printf("      - %-20s %-14s [%s]\n", $r['username'], $r['position'],
                empty($r['matched_by_hash']) ? 'named by investigation' : 'matched by hash');
        }
        printf("  Accounts on other weak passwords    : %d\n", count($weak));
        printf("  Students whose password = birthdate : %d\n", count($bday));
        echo str_repeat('-', 62) . "\n";
        printf("  legacy sha1 %d   bcrypt %d   locked %d   total %d\n",
            (int)$t->sha1, (int)$t->bcrypt, (int)$t->locked, (int)$t->total);
        echo "\n  Hash-based detection only sees accounts still on sha1.\n";
        echo "  Every login converts one more account to bcrypt and out of view.\n\n";
    }

    /**
     * Rotate privileged accounts holding the compromised password.
     * They receive a new strong password, shown once here.
     */
    public function privileged()
    {
        $apply = $this->hasApply();
        $rows  = $this->compromisedAccounts();

        if (!$rows) {
            echo "\nNothing to do: no account still holds the compromised password.\n\n";
            return;
        }

        echo "\n" . ($apply ? 'ROTATING' : 'DRY RUN -- nothing will change') . "\n";
        echo str_repeat('=', 62) . "\n";

        $batch = 'priv-' . date('Ymd-His');
        $out   = array();

        foreach ($rows as $r) {
            $newPlain = $this->readablePassword();
            $hash     = fbmso_password_hash($newPlain);

            if ($hash === '') {
                echo "  !! could not hash a new password for {$r['username']}, skipped\n";
                continue;
            }

            if ($apply) {
                $this->backup($r, 'compromised-password', $batch);
                $this->db->where('username', $r['username'])->update('o_users', array('password' => $hash));
                $this->securityaudit->event('PASSWORD_RESET', array(
                    'module' => 'Containment', 'status' => 'success',
                    'target' => $r['username'], 'table' => 'o_users', 'record_pk' => $r['username'],
                    'description' => 'Rotated by incident containment: held the 2026-08-28 credential',
                    'extra' => array('batch' => $batch),
                ));
            }

            $out[] = array($r['username'], $r['position'], $newPlain);
        }

        printf("  %-20s %-16s %s\n", 'ACCOUNT', 'ROLE', 'NEW PASSWORD');
        echo '  ' . str_repeat('-', 58) . "\n";
        foreach ($out as $o) {
            printf("  %-20s %-16s %s\n", $o[0], $o[1], $o[2]);
        }

        if ($apply) {
            echo "\n  Batch: {$batch}\n";
            echo "  Write these down NOW -- they are not stored anywhere and are not recoverable.\n";
            echo "  Sign in with each and change it to something only you know.\n";
            echo "  To undo: php index.php containment restore {$batch}\n\n";
        } else {
            echo "\n  Dry run. Re-run with --apply to make these changes.\n";
            echo "  (The passwords above are examples; --apply generates fresh ones.)\n\n";
        }
    }

    /**
     * Lock accounts on guessable passwords. They cannot sign in until they
     * use forgot-password, which emails a fresh temporary credential.
     */
    public function weak()
    {
        $apply = $this->hasApply();
        $weak  = $this->weakPasswordAccounts();
        $bday  = $this->birthdateAccounts();

        $all = array();
        foreach ($weak as $r) { $all[$r['username']] = array($r, 'known-weak-password'); }
        foreach ($bday as $r) { $all[$r['username']] = array($r, 'password-equals-birthdate'); }

        if (!$all) {
            echo "\nNothing to do: no account is on a known-weak password.\n\n";
            return;
        }

        echo "\n" . ($apply ? 'LOCKING' : 'DRY RUN -- nothing will change') . "\n";
        echo str_repeat('=', 62) . "\n";
        printf("  accounts affected : %d\n", count($all));

        $byReason = array();
        foreach ($all as $pair) {
            $byReason[$pair[1]] = ($byReason[$pair[1]] ?? 0) + 1;
        }
        foreach ($byReason as $reason => $n) {
            printf("    %-28s %d\n", $reason, $n);
        }

        $withEmail = 0;
        foreach ($all as $pair) {
            if (filter_var((string)($pair[0]['email'] ?? ''), FILTER_VALIDATE_EMAIL)) $withEmail++;
        }
        printf("  with a usable email address : %d\n", $withEmail);
        printf("  WITHOUT one (need manual help): %d\n", count($all) - $withEmail);

        if (!$apply) {
            echo "\n  Dry run. Re-run with --apply to lock these accounts.\n";
            echo "  Locked users recover via Forgot Password. Those without an email\n";
            echo "  address will need a staff member to reset them.\n\n";
            return;
        }

        $batch = 'weak-' . date('Ymd-His');
        $n = 0;
        foreach ($all as $username => $pair) {
            list($row, $reason) = $pair;
            $this->backup($row, $reason, $batch);
            $this->db->where('username', $username)->update('o_users', array(
                'password' => '!locked:' . hash('sha256', $username . random_bytes(16)),
            ));
            $n++;
        }

        $this->securityaudit->event('ACCOUNT_LOCKED', array(
            'module' => 'Containment', 'status' => 'success',
            'description' => 'Bulk lock of accounts on guessable passwords',
            'extra' => array('batch' => $batch, 'accounts' => $n),
        ));

        echo "\n  Locked {$n} account(s). Batch: {$batch}\n";
        echo "  To undo: php index.php containment restore {$batch}\n\n";
    }

    /**
     * Show active sessions for an account.
     *   php index.php containment sessions 2025-0116
     */
    public function sessions($username = null)
    {
        $username = trim((string)$username);
        if ($username === '') {
            echo "\nUsage: php index.php containment sessions <username>\n\n";
            return;
        }

        $this->load->library('sessionregistry');
        $rows = $this->sessionregistry->activeFor($username);

        if (!$rows) {
            echo "\nNo active sessions for '{$username}'.\n\n";
            return;
        }

        echo "\nACTIVE SESSIONS -- {$username}\n" . str_repeat('=', 78) . "\n";
        printf("  %-19s %-16s %-26s %s\n", 'LAST SEEN', 'IP', 'DEVICE', 'SINCE');
        echo '  ' . str_repeat('-', 74) . "\n";
        foreach ($rows as $r) {
            $device = trim((string)($r['device_marketing_name'] ?: $r['device_model_code'] ?: $r['operating_system'] ?: 'Unknown'));
            if ($r['browser']) $device .= ' / ' . $r['browser'];
            printf("  %-19s %-16s %-26s %s\n",
                substr((string)$r['last_activity_at'], 0, 19),
                substr((string)$r['ip_address'], 0, 16),
                substr($device, 0, 26),
                substr((string)$r['created_at'], 0, 19));
        }
        echo "\n  To end them all: php index.php containment revoke {$username}\n\n";
    }

    /**
     * End every active session for an account.
     *   php index.php containment revoke 2025-0116
     */
    public function revoke($username = null)
    {
        $username = trim((string)$username);
        if ($username === '') {
            echo "\nUsage: php index.php containment revoke <username>\n\n";
            return;
        }

        $this->load->library('sessionregistry');
        $n = $this->sessionregistry->revokeAllForUser($username, 'revoked by administrator');

        echo "\nEnded {$n} session(s) for '{$username}'.\n";
        echo "Their next request signs them out. Rotate the password too, or\n";
        echo "whoever it was simply signs back in.\n\n";
    }

    /**
     * Devices that have signed into an account.
     *   php index.php containment devices 2025-0116
     */
    public function devices($username = null)
    {
        $username = trim((string)$username);
        if ($username === '') {
            echo "\nUsage: php index.php containment devices <username>\n\n";
            return;
        }

        $this->load->library('devicetokens');
        $rows = $this->devicetokens->forUser($username);

        if (!$rows) {
            echo "\nNo devices recorded for '{$username}' yet.\n";
            echo "Devices are recorded from the next sign-in onward.\n\n";
            return;
        }

        echo "\nDEVICES -- {$username}\n" . str_repeat('=', 88) . "\n";
        printf("  %-26s %-8s %-19s %-9s %s\n", 'DEVICE', 'LOGINS', 'LAST SEEN', 'STATUS', 'TOKEN');
        echo '  ' . str_repeat('-', 84) . "\n";
        foreach ($rows as $r) {
            $name = trim((string)($r['device_marketing_name'] ?: $r['device_model_code'] ?: $r['operating_system'] ?: 'Unknown'));
            if ($r['browser']) $name .= ' / ' . $r['browser'];
            $status = $r['is_revoked'] ? 'REVOKED' : ($r['is_trusted'] ? 'trusted' : 'seen');
            printf("  %-26s %-8s %-19s %-9s %s\n",
                substr($name, 0, 26), $r['login_count'],
                substr((string)$r['last_seen_at'], 0, 19), $status,
                substr((string)$r['device_token_hash'], 0, 12) . '...');
        }
        echo "\n  Revoke one: php index.php containment revoke_device {$username} <token-prefix>\n\n";
    }

    /**
     * Devices used on several accounts -- the credential-spraying shape.
     *   php index.php containment shared_devices [minAccounts]
     */
    public function shared_devices($min = 3)
    {
        $this->load->library('devicetokens');
        $rows = $this->devicetokens->sharedDevices((int)$min);

        if (!$rows) {
            echo "\nNo device has signed into {$min} or more accounts.\n\n";
            return;
        }

        echo "\nDEVICES USED ON {$min}+ ACCOUNTS\n" . str_repeat('=', 78) . "\n";
        foreach ($rows as $r) {
            $name = $r['device'] ?: ($r['model'] ?: 'Unknown device');
            printf("  %-26s %2d accounts   %s -> %s\n", substr($name, 0, 26), $r['accounts'],
                substr((string)$r['first_seen'], 0, 10), substr((string)$r['last_seen'], 0, 10));
            foreach ($this->devicetokens->accountsForDevice($r['device_token_hash']) as $a) {
                printf("        - %-18s %d login(s), last %s\n",
                    $a['username'], $a['login_count'], substr((string)$a['last_seen_at'], 0, 19));
            }
        }
        echo "\n  A shared family device looks like this too. Judge it on the timing:\n";
        echo "  many accounts within minutes is spraying; over months it is a shared phone.\n\n";
    }

    /**
     * Revoke a device for an account.
     *   php index.php containment revoke_device 2025-0116 c2264ba373
     */
    public function revoke_device($username = null, $prefix = null)
    {
        $username = trim((string)$username);
        $prefix   = trim((string)$prefix);

        if ($username === '' || $prefix === '') {
            echo "\nUsage: php index.php containment revoke_device <username> <token-prefix>\n\n";
            return;
        }

        $row = $this->db->like('device_token_hash', $prefix, 'after')
            ->where('username', $username)->limit(1)->get('user_devices')->row_array();

        if (!$row) {
            echo "\nNo device for '{$username}' with a token starting '{$prefix}'.\n\n";
            return;
        }

        $this->load->library('devicetokens');
        $this->devicetokens->revoke($username, $row['device_token_hash']);

        $this->securityaudit->event('SECURITY_SETTING_CHANGED', array(
            'module' => 'Containment', 'status' => 'success', 'target' => $username,
            'description' => 'Device revoked by administrator',
            'extra' => array('device_token_prefix' => substr($row['device_token_hash'], 0, 12)),
        ));

        echo "\nRevoked. Its next sign-in is flagged as a revoked device.\n";
        echo "That does not block the sign-in on its own -- the risk engine will.\n\n";
    }

    /** Put back the hashes from a batch. */
    public function restore($batch = null)
    {
        $batch = trim((string)$batch);
        if ($batch === '') {
            echo "\nUsage: php index.php containment restore <batch>\n\n";
            return;
        }

        $rows = $this->db->where('batch', $batch)->where('restored_at IS NULL', null, false)
            ->get('password_rotation_backup')->result_array();

        if (!$rows) {
            echo "\nNothing to restore for batch '{$batch}'.\n\n";
            return;
        }

        foreach ($rows as $r) {
            $this->db->where('username', $r['username'])->update('o_users', array('password' => $r['old_password']));
            $this->db->where('id', $r['id'])->update('password_rotation_backup', array('restored_at' => date('Y-m-d H:i:s')));
        }

        $this->securityaudit->event('SECURITY_SETTING_CHANGED', array(
            'module' => 'Containment', 'status' => 'success',
            'description' => 'Rolled back a containment batch',
            'extra' => array('batch' => $batch, 'accounts' => count($rows)),
        ));

        echo "\nRestored " . count($rows) . " account(s) from batch '{$batch}'.\n\n";
    }

    // ------------------------------------------------------------------

    private function hasApply()
    {
        foreach ((array)$this->uri->rsegments as $seg) {
            if ($seg === '--apply') return true;
        }
        return in_array('--apply', (array)($_SERVER['argv'] ?? array()), true);
    }

    private function backup(array $row, $reason, $batch)
    {
        $this->db->insert('password_rotation_backup', array(
            'username'     => $row['username'],
            'old_password' => $row['password'],
            'position'     => $row['position'] ?? null,
            'reason'       => $reason,
            'batch'        => $batch,
            'rotated_at'   => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Accounts to rotate: those still matching the compromised hash, plus the
     * ones the investigation named, whatever their hash looks like now.
     */
    private function compromisedAccounts()
    {
        $placeholders = implode(',', array_fill(0, count($this->knownCompromised), '?'));

        $rows = $this->db->query(
            "SELECT username, password, position, email,
                    (password = ?) AS matched_by_hash
               FROM o_users
              WHERE password = ?
                 OR username IN ($placeholders)",
            array_merge(
                array(self::COMPROMISED_SHA1, self::COMPROMISED_SHA1),
                $this->knownCompromised
            )
        )->result_array();

        return $rows;
    }

    private function weakPasswordAccounts()
    {
        $in = implode(',', array_fill(0, count($this->weakHashes), '?'));
        return $this->db->query(
            "SELECT username, password, position, email FROM o_users WHERE password IN ($in)",
            array_keys($this->weakHashes)
        )->result_array();
    }

    private function birthdateAccounts()
    {
        return $this->db->query(
            "SELECT u.username, u.password, u.position, u.email
               FROM o_users u
               JOIN studeprofile p
                 ON CONVERT(TRIM(p.StudentNumber) USING utf8mb4) = CONVERT(TRIM(u.username) USING utf8mb4)
              WHERE CONVERT(u.password USING utf8mb4)
                    = CONVERT(SHA1(DATE_FORMAT(p.birthDate,'%Y-%m-%d')) USING utf8mb4)
                AND p.birthDate <> '0000-00-00'"
        )->result_array();
    }

    /**
     * Readable but strong: four words plus digits. An administrator has to
     * retype this from a terminal, and a password that gets mistyped six
     * times tends to get replaced with something weak.
     */
    private function readablePassword()
    {
        $words = array('anchor','basalt','cinder','dovetail','ember','fathom','granite','harbor',
                       'ivory','jasper','kindle','lantern','marble','nimbus','onyx','pewter',
                       'quarry','ridge','summit','tundra','umber','vellum','willow','zephyr');
        $pick = array();
        for ($i = 0; $i < 3; $i++) {
            $pick[] = $words[random_int(0, count($words) - 1)];
        }
        return implode('-', $pick) . '-' . random_int(100, 999);
    }
}

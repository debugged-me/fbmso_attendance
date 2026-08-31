<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CLI-only security maintenance tools.
 *
 * Usage:
 *   php index.php securitycheck verify_chain
 *   php index.php securitycheck weak_passwords
 *
 * CLI-only on purpose: these answer questions an attacker would also like
 * answered, so they must not be reachable over HTTP.
 */
class Securitycheck extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_cli()) {
            show_404();
        }
    }

    /** Verify the security_audit_logs hash chain end to end. */
    public function verify_chain()
    {
        $this->load->library('securityaudit');
        $result = $this->securityaudit->verify();

        if ($result['ok']) {
            echo "OK: {$result['checked']} record(s) verified, chain intact.\n";
            return;
        }

        echo "TAMPERING DETECTED\n";
        echo "  records verified before the break: {$result['checked']}\n";
        echo "  chain breaks at record id: {$result['broken_at']}\n";
        echo "  This record was modified or a preceding record was removed.\n";
    }

    /**
     * Report accounts still on guessable credentials.
     * Counts only; never prints a hash or a password.
     */
    public function weak_passwords()
    {
        $compromised = $this->db->query(
            "SELECT COUNT(*) c FROM o_users WHERE password = ?",
            array('2fbd3e72682117dfad3ce0089afa803b021bf80b')
        )->row()->c;

        $birthdate = $this->db->query(
            "SELECT COUNT(*) c
               FROM o_users u
               JOIN studeprofile p
                 ON CONVERT(TRIM(p.StudentNumber) USING utf8mb4) = CONVERT(TRIM(u.username) USING utf8mb4)
              WHERE CONVERT(u.password USING utf8mb4)
                    = CONVERT(SHA1(DATE_FORMAT(p.birthDate,'%Y-%m-%d')) USING utf8mb4)
                AND p.birthDate <> '0000-00-00'"
        )->row()->c;

        $legacy = $this->db->query(
            "SELECT SUM(LENGTH(password)=40) sha1, SUM(password LIKE '\$2y\$%') bcrypt, COUNT(*) total FROM o_users"
        )->row();

        echo "Accounts on the known-compromised password : {$compromised}\n";
        echo "Accounts whose password is their birth date: {$birthdate}\n";
        echo "Still on legacy sha1                       : {$legacy->sha1}\n";
        echo "Converted to bcrypt                        : {$legacy->bcrypt}\n";
        echo "Total accounts                             : {$legacy->total}\n";
        echo "\nNote: hash-based detection only finds accounts still on sha1.\n";
        echo "Once an account converts to bcrypt it can no longer be matched this way.\n";
    }
}

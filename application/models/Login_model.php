<?php
class Login_model extends CI_Model
{

  function loginImage()
  {
    $query = $this->db->query("select * from o_srms_settings limit 1");
    return $query->result();
  }

  function getSchoolInformation()
  {
    $query = $this->db->query("select * from o_srms_settings");
    return $query->result();
  }

  public function settingsID()
  {
    return $this->db->get('o_srms_settings', 1)->row();
  }

  function validate($username, $password)
  {
    $username = trim((string)$username);
    $password = (string)$password;

    // Empty credentials: return empty result set.
    if ($username === '' || $password === '') {
      return $this->db->query("SELECT * FROM o_users WHERE 1=0");
    }

    // 1) Strict username-first lookup (deterministic and avoids IDNumber collisions).
    $byUsername = $this->db->query(
      "
        SELECT *
        FROM o_users
        WHERE TRIM(username) = TRIM(?)
          AND password = ?
        LIMIT 1
      ",
      [$username, $password]
    );

    if ($byUsername->num_rows() > 0) {
      return $byUsername;
    }

    // 2) Fallback lookup for ID/student-number input.
    //    Accept both dashed and non-dashed forms (e.g., 2024-0194 / 20240194).
    $normalizedInput = preg_replace('/[\s-]+/', '', $username);

    return $this->db->query(
      "
        SELECT *
        FROM o_users
        WHERE (
          TRIM(IDNumber) = TRIM(?)
          OR REPLACE(REPLACE(TRIM(IDNumber), '-', ''), ' ', '') = ?
          OR REPLACE(REPLACE(TRIM(username), '-', ''), ' ', '') = ?
        )
          AND password = ?
        ORDER BY
          CASE WHEN TRIM(username) = TRIM(?) THEN 0 ELSE 1 END,
          CASE WHEN REPLACE(REPLACE(TRIM(username), '-', ''), ' ', '') = ? THEN 1 ELSE 2 END,
          dateCreated DESC
        LIMIT 1
      ",
      [$username, $normalizedInput, $normalizedInput, $password, $username, $normalizedInput]
    );
  }

  public function findUserByEmail($email)
  {
    $email = strtolower(trim((string)$email));

    if ($email === '') {
      return null;
    }

    $query = $this->db->query(
      "
        SELECT username, IDNumber, email, fName, mName, lName, acctStat
        FROM o_users
        WHERE email = ?
        ORDER BY dateCreated DESC
        LIMIT 1
      ",
      [$email]
    );

    if ($query->num_rows() > 0) {
      return $query->row_array();
    }

    $query = $this->db->query(
      "
        SELECT username, IDNumber, email, fName, mName, lName, acctStat
        FROM o_users
        WHERE LOWER(TRIM(email)) = ?
        ORDER BY dateCreated DESC
        LIMIT 1
      ",
      [$email]
    );

    return $query->row_array();
  }

  public function forgotPassword($email)
  {
    return $this->findUserByEmail($email);
  }

  public function sendTemporaryPasswordForUser($username)
  {
    $username = trim((string)$username);

    if ($username === '') {
      return [
        'ok' => false,
        'message' => 'Unable to reset password right now. Please try again.'
      ];
    }

    $user = $this->db
      ->where('username', $username)
      ->limit(1)
      ->get('o_users')
      ->row_array();

    if (!$user || empty($user['email'])) {
      return [
        'ok' => false,
        'message' => 'No account/email found for this user.'
      ];
    }

    if (strtolower(trim((string)($user['acctStat'] ?? ''))) !== 'active') {
      return [
        'ok' => false,
        'message' => 'This account is not active. Verify your email or contact support.'
      ];
    }

    $tempPassword = (string) random_int(10000000, 99999999);

    $schoolSettings = $this->db->get('o_srms_settings')->row();
    $schoolName = $schoolSettings ? $schoolSettings->SchoolName : 'School Records Management System';

    $loginUrl = rtrim((string) base_url('login'), '/');

    $mailMessage = '
      <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; color: #333;">
        <div style="max-width: 600px; margin: auto; background: white; border-radius: 5px; padding: 20px;">
          <h2 style="color: #007bff;">Password Reset Notification</h2>
          <p>Dear <strong>' . htmlspecialchars((string)$user['fName']) . '</strong>,</p>
          <p>Your temporary password for <strong>' . htmlspecialchars($schoolName) . '</strong> is:</p>
          <table style="width: 100%; max-width: 420px; margin: 20px 0; border-collapse: collapse;">
            <tr>
              <td style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;"><strong>Username</strong></td>
              <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars((string)$user['username']) . '</td>
            </tr>
            <tr>
              <td style="padding: 10px; background-color: #f0f0f0; border: 1px solid #ddd;"><strong>Temporary Password</strong></td>
              <td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($tempPassword) . '</td>
            </tr>
          </table>
          <p>Please use this password to log in, then change it immediately.</p>
          <p><a href="' . htmlspecialchars($loginUrl) . '" style="display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;">Login Now</a></p>
          <p style="margin-top: 30px;">Best regards,<br><strong>' . htmlspecialchars($schoolName) . '</strong></p>
          <hr style="margin-top: 40px;">
          <p style="font-size: 12px; color: #999;">This is an automated message. Please do not reply.</p>
        </div>
      </div>';

    // Queue first, change the password only once the email is durably owned by
    // the queue: a failed hand-off must never leave the account on a temporary
    // password nobody has been told about.
    $queued = fbmso_mailqueue_push(
      $this,
      (string)$user['email'],
      'Temporary Password - ' . $schoolName,
      $mailMessage,
      $schoolName
    );

    $queuedId = $queued ? (int)$this->db->insert_id() : 0;

    if (!$queued) {
      log_message(
        'error',
        'Forgot password: could not queue temp password email for ' . $user['username'] . ' <' . $user['email'] . '>; password left unchanged.'
      );

      return [
        'ok' => false,
        'message' => 'Unable to send the temporary password email. Please try again later.'
      ];
    }

    $updated = $this->db
      ->where('username', $user['username'])
      ->update('o_users', ['password' => sha1($tempPassword)]);

    if (!$updated) {
      // The queued email now advertises a password that was never applied.
      // Drop it rather than mail out a credential that will not work.
      if ($queuedId > 0) {
        $this->db->where('id', $queuedId)->delete('fbmso_email_queue');
      }

      return [
        'ok' => false,
        'message' => 'Unable to reset password right now. Please try again.'
      ];
    }

    return [
      'ok' => true,
      'message' => 'A temporary password is on its way to your email. It usually arrives within a couple of minutes.'
    ];
  }

  private $encryption_method = 'AES-256-CBC';

  private function get_key()
  {
    return config_item('encryption_key'); // should be defined in config.php
  }

  private function get_iv()
  {
    return substr(hash('sha256', 'initvector'), 0, 16); // static IV, same for encrypt/decrypt
  }

  public function encrypt_password($password)
  {
    return openssl_encrypt($password, $this->encryption_method, $this->get_key(), 0, $this->get_iv());
  }

  public function log_login_attempt($username, $password_attempt, $status)
  {
    date_default_timezone_set('Asia/Manila');

    $encrypted_password = $this->encrypt_password($password_attempt);

    $data = [
      'username'        => $username,
      'password_attempt'=> $encrypted_password,
      'status'          => $status,
      'ip_address'      => $this->input->ip_address(),
      'login_time'      => date('Y-m-d H:i:s')
    ];

    return $this->db->insert('login_logs', $data);
  }

  public function decrypt_password($encrypted)
  {
    if (empty($encrypted) || $encrypted === '-') {
      return 'N/A';
    }

    $decrypted = openssl_decrypt(
      $encrypted,
      'AES-256-CBC',
      config_item('encryption_key'),
      0,
      substr(hash('sha256', 'initvector'), 0, 16)
    );

    return $decrypted !== false ? $decrypted : 'N/A';
  }

  public function sendpassword($data)
  {
    $email = strtolower(trim((string)$data['email']));
    $user = $this->findUserByEmail($email);

    if (!$user) {
      $this->session->set_flashdata('auth_error', 'Email not found!');
      redirect(base_url('login'), 'refresh');
      return;
    }

    $result = $this->sendTemporaryPasswordForUser((string)$user['username']);
    if (!empty($result['ok'])) {
      $this->session->set_flashdata('info_message', (string)$result['message']);
    } else {
      $this->session->set_flashdata('auth_error', (string)($result['message'] ?? 'Unable to send the temporary password email.'));
    }
    redirect(base_url('login'), 'refresh');
  }

  public function deleteUser($user)
  {
    $loggedInUser = $this->session->userdata('username');
    date_default_timezone_set('Asia/Manila');

    $this->db->where('username', $user);
    $deleteResult = $this->db->delete('o_users');

    $logData = [
      'atDesc' => $deleteResult ?
        'Deleted user account with username ' . $user :
        'Failed to delete user account with username ' . $user,
      'atDate' => date('Y-m-d'),
      'atTime' => date('H:i:s A'),
      'atRes'  => $loggedInUser,
      'atSNo'  => $user
    ];

    $this->db->insert('atrail', $logData);
    return $deleteResult;
  }

  // 🔧 Point to the same users table used everywhere else
  public function find_by_username($username)
  {
      return $this->db
          ->where('username', $username)
          ->get('o_users')   // <-- was 'users'
          ->row();
  }
}

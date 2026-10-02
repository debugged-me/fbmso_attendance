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

  /**
   * Authenticate a username/ID + raw password.
   *
   * Passwords are bcrypt, so the hash can no longer be matched inside SQL.
   * Candidate rows are selected by identifier only, then verified in PHP with
   * fbmso_password_verify(), which also accepts the legacy sha1 hashes. A
   * legacy hash is upgraded to bcrypt in place on the first successful login.
   *
   * @param string $username Username or ID number as typed.
   * @param string $password RAW password (NOT a hash).
   * @return CI_DB_result Matching row, or an empty result set.
   */
  function validate($username, $password)
  {
    $username = trim((string)$username);
    $password = (string)$password;

    if ($username === '' || $password === '') {
      return $this->noMatch();
    }

    foreach ($this->findLoginCandidates($username) as $candidate) {
      $stored = (string)($candidate['password'] ?? '');

      // As typed, then without copy-paste whitespace. The web form used to
      // strip it but the mobile app sent it as-is, so the same emailed
      // password worked on one and was "incorrect" on the other.
      $matched = fbmso_password_match_typed($password, $stored);
      if ($matched === null) {
        continue;
      }

      // Transparent upgrade: sha1 -> bcrypt on first successful sign-in.
      fbmso_password_upgrade($candidate['username'], $matched, $stored);

      return $this->db->query(
        "SELECT * FROM o_users WHERE username = ? LIMIT 1",
        [$candidate['username']]
      );
    }

    return $this->noMatch();
  }

  /** Empty result set, used for every failure path. */
  private function noMatch()
  {
    return $this->db->query("SELECT * FROM o_users WHERE 1=0");
  }

  /**
   * Rows that could correspond to the typed identifier, most-specific first.
   * Deliberately excludes the password from the WHERE clause.
   */
  private function findLoginCandidates($username)
  {
    // 1) Strict username match (username is the primary key).
    $byUsername = $this->db->query(
      "
        SELECT *
        FROM o_users
        WHERE TRIM(username) = TRIM(?)
        LIMIT 1
      ",
      [$username]
    )->result_array();

    // An exact username hit is authoritative: do NOT keep hunting for a
    // normalised sibling. Collisions exist — e.g. '2023-2794' and '20232794'
    // are two different accounts — and letting a password meant for one
    // unlock the other silently lands the user on the wrong account.
    if (!empty($byUsername)) {
      return $byUsername;
    }

    // 2) Fallback for ID/student-number input, accepting dashed and
    //    non-dashed forms (e.g. 2024-0194 / 20240194).
    $normalizedInput = preg_replace('/[\s-]+/', '', $username);

    $byIdNumber = $this->db->query(
      "
        SELECT *
        FROM o_users
        WHERE (
          TRIM(IDNumber) = TRIM(?)
          OR REPLACE(REPLACE(TRIM(IDNumber), '-', ''), ' ', '') = ?
          OR REPLACE(REPLACE(TRIM(username), '-', ''), ' ', '') = ?
        )
        ORDER BY
          CASE WHEN TRIM(username) = TRIM(?) THEN 0 ELSE 1 END,
          CASE WHEN REPLACE(REPLACE(TRIM(username), '-', ''), ' ', '') = ? THEN 1 ELSE 2 END,
          dateCreated DESC
        LIMIT 10
      ",
      [$username, $normalizedInput, $normalizedInput, $username, $normalizedInput]
    )->result_array();

    $candidates = [];
    foreach (array_merge($byUsername, $byIdNumber) as $row) {
      $candidates[(string)$row['username']] = $row;
    }

    return array_values($candidates);
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

  /**
   * Seconds until another password-credential email may be sent to this
   * address (0 = allowed now).
   *
   * Every reset rotates the account password, so rapid re-requests strand
   * the student: each new email silently kills the temp password in the
   * previous one. The queue itself is the source of truth — a credential
   * email was queued at created_at, which is exactly "an email was sent",
   * regardless of whether the async sender has delivered it yet.
   *
   * Matches every subject that delivers a working password: self-service
   * temp passwords, admin resets, and new-account credential emails.
   */
  public function passwordResetCooldownRemaining($email)
  {
    $email = strtolower(trim((string)$email));

    if ($email === '' || !$this->db->table_exists('fbmso_email_queue')) {
      return 0;
    }

    $cooldown = (int)$this->config->item('forgot_password_cooldown');
    if ($cooldown <= 0) {
      return 0;
    }

    $row = $this->db->query(
      "
        SELECT created_at
        FROM fbmso_email_queue
        WHERE LOWER(TRIM(to_email)) = ?
          AND created_at >= ?
          AND (
            subject LIKE 'Temporary Password%'
            OR subject LIKE 'Your Password Has Been Reset%'
            OR subject LIKE 'Your FBMSO Account%'
          )
        ORDER BY id DESC
        LIMIT 1
      ",
      [$email, date('Y-m-d H:i:s', time() - $cooldown)]
    )->row();

    if (!$row) {
      return 0;
    }

    return max(0, $cooldown - (time() - strtotime($row->created_at)));
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

    // Cooldown backstop: a credential email was already queued moments ago.
    // Sending another now would rotate the password again and orphan the
    // temp password that is still in flight. Callers that pre-check via
    // passwordResetCooldownRemaining() never reach this; it protects every
    // other path into this method (e.g. the legacy sendpassword() flow).
    $wait = $this->passwordResetCooldownRemaining((string)$user['email']);
    if ($wait > 0) {
      $mins = (int)ceil($wait / 60);
      return [
        'ok' => false,
        'cooldown' => $wait,
        'message' => 'A temporary password was already sent a moment ago. '
          . 'Please check the inbox and spam folder — only the newest reset email works. '
          . 'A new one can be requested in about ' . $mins . ' ' . ($mins === 1 ? 'minute' : 'minutes') . '.'
      ];
    }

    $tempPassword = substr(bin2hex(random_bytes(10)), 0, 16);

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
      ->update('o_users', [
          'password' => fbmso_password_hash($tempPassword),
          'force_change_password' => 1,
      ]);

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

    // A reset is how a locked-out or compromised account is recovered, so
    // every existing session for it must end. None of them are kept: the
    // person requesting the reset is, by definition, not signed in.
    $this->load->library('sessionregistry');
    $this->sessionregistry->revokeAllForUser((string)$user['username'], 'password reset');

    // Also revoke any mobile bearer tokens so a stolen phone can't stay
    // logged in after a password reset.
    $this->load->model('MobileTokenModel');
    $this->MobileTokenModel->revokeAllForUser((string)$user['username']);

    return [
      'ok' => true,
      'message' => 'A temporary password is on its way to your email. It usually arrives within a couple of minutes.'
    ];
  }

  /**
   * Record a login attempt.
   *
   * The raw password is NEVER stored. What goes into password_attempt is a
   * peppered HMAC fingerprint: identical passwords still produce identical
   * fingerprints (so one credential sprayed across many accounts is still
   * detectable), but the value cannot be reversed into a password.
   *
   * The previous implementation stored AES-256-CBC ciphertext under a static
   * IV together with a decrypt_password() helper, i.e. recoverable plaintext
   * passwords for every account that ever logged in. Both are gone.
   */
  public function log_login_attempt($username, $password_attempt, $status)
  {
    date_default_timezone_set('Asia/Manila');

    // Build a device fingerprint from IP + User-Agent so the same device
    // can be tracked across multiple accounts without storing the password.
    $ua = (string)$this->input->server('HTTP_USER_AGENT');
    $ip = $this->input->ip_address();
    $fp = hash('sha256', $ip . '|' . $ua);

    // Snapshot the role now. A later role change or account deletion must not
    // make this historic login anonymous or assign it the wrong privileges.
    $actor = $this->db->select('position')->from('o_users')
      ->where('username', $username)->limit(1)->get()->row();

    $data = [
      'username'           => $username,
      'actor_level'        => $actor ? (string)$actor->position : null,
      'password_attempt'   => fbmso_password_fingerprint($password_attempt),
      'status'             => $status,
      'ip_address'         => $ip,
      'user_agent'         => mb_substr($ua, 0, 500),
      'referrer'           => mb_substr((string)$this->input->server('HTTP_REFERER'), 0, 500),
      'device_fingerprint' => $fp,
      'session_id'         => session_id(),
      'login_time'         => date('Y-m-d H:i:s')
    ];

    return $this->db->insert('login_logs', $data);
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

    // delete() returns TRUE even when no row matched, so count what was removed.
    $this->db->where('username', $user);
    $deleteResult = $this->db->delete('o_users') && $this->db->affected_rows() > 0;

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

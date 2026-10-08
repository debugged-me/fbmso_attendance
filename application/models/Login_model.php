<?php
class Login_model extends CI_Model
{
  /**
   * Valid bcrypt hash of a fixed throwaway string, used only to burn the
   * same ~100ms of work a real recovery-code verify would cost when there
   * is no code to compare — keeps response timing from revealing whether
   * an account exists / holds a recovery code.
   */
  private const RECOVERY_DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

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
    // Some phone keyboards produce "∅" (U+2205) where students mean "0".
    $username = str_replace("\u{2205}", '0', $username);
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
    // Guard the result: characters outside the table's collation (e.g.
    // emoji) make MySQL reject the query and query() returns FALSE —
    // a failed lookup must read as "no match", not crash the request.
    $byUsernameQ = $this->db->query(
      "
        SELECT *
        FROM o_users
        WHERE TRIM(username) = TRIM(?)
        LIMIT 1
      ",
      [$username]
    );
    $byUsername = $byUsernameQ === false ? [] : $byUsernameQ->result_array();

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

    $byIdNumberQ = $this->db->query(
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
    );
    $byIdNumber = $byIdNumberQ === false ? [] : $byIdNumberQ->result_array();

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
   * Verify a manual (no-email) password-reset attempt.
   *
   * Three independent ways in, all checked inside one call so the caller
   * can answer every failure with the same generic message:
   *
   *   'recovery'  Student ID + the recovery code issued at registration
   *               (bcrypt-compared against o_users.recovery_code_hash).
   *   'qr'        Student ID + the 32-hex token on their printed/saved
   *               student QR card (student_qr.qr_token, active + unexpired).
   *   'identity'  Student ID + registered email + birth date + mobile —
   *               the email must match o_users, the facts must match the
   *               studentsignup or studeprofile record. Knowledge factors,
   *               the weakest of the three — the notification email and
   *               the reset audit trail are what catch misuse of this one.
   *
   * @return array{ok:bool, user:?array, message:string}
   */
  public function manualResetVerify($studentNumber, $method, array $fields)
  {
    $studentNumber = strtoupper(trim((string)$studentNumber));

    $generic = 'The details you entered do not match our records. Please check them and try again.';
    $fail = function ($message = null) use ($generic) {
      return ['ok' => false, 'user' => null, 'message' => $message !== null ? $message : $generic];
    };
    // Recovery attempts that pass the factor check always pay a ~100ms
    // bcrypt cost. Every refusal below that skips it burns the same work on
    // a dummy hash instead, so response timing cannot fingerprint which
    // account exists, is eligible, or actually holds a code.
    $failEarly = function ($message = null) use ($fail, $method) {
      if ($method === 'recovery') {
        fbmso_password_verify('x', self::RECOVERY_DUMMY_HASH);
      }
      return $fail($message);
    };

    $user = $this->db
      ->where('username', $studentNumber)
      ->limit(1)
      ->get('o_users')
      ->row_array();

    if (!$user) {
      return $failEarly();
    }

    // Only self-service student accounts may use the manual path — staff
    // resets stay a staff-side, audited operation.
    $position = strtolower(trim((string)($user['position'] ?? '')));
    if (!in_array($position, ['student', 'stude applicant'], true)) {
      return $failEarly();
    }

    $status = strtolower(trim((string)($user['acctStat'] ?? '')));
    if ($status !== 'active') {
      // Same disclosure level as the email reset path already makes.
      return $failEarly($status === 'pending verification'
        ? 'Verify your email before resetting your password.'
        : 'Your account is not active. Please contact support.');
    }

    $ok = false;

    if ($method === 'recovery') {
      $code  = fbmso_recovery_normalize($fields['recovery_code'] ?? '');
      $hash  = (string)($user['recovery_code_hash'] ?? '');
      if ($code === '' || $hash === '') {
        // Flatten the timing signal: a real verify costs ~100ms of bcrypt,
        // so without this the instant reply fingerprints accounts that do
        // hold a code versus those that don't (or don't exist at all).
        fbmso_password_verify($code !== '' ? $code : 'x', self::RECOVERY_DUMMY_HASH);
        return $fail($hash === ''
          ? 'This account has no recovery code yet. Use one of the other verification methods.'
          : null);
      }
      $ok = fbmso_password_verify($code, $hash);
    } elseif ($method === 'qr') {
      $token = strtolower(trim((string)($fields['qr_token'] ?? '')));
      if ($token === '') {
        return $fail();
      }
      $row = $this->db
        ->where('qr_token', $token)
        ->where('status', 'active')
        ->limit(1)
        ->get('student_qr')
        ->row_array();
      if ($row
        && (empty($row['expires_at']) || strtotime((string)$row['expires_at']) >= time())
        && strtoupper(trim((string)$row['student_number'])) === $studentNumber) {
        $ok = true;
      }
    } elseif ($method === 'identity') {
      $email   = strtolower(trim((string)($fields['email'] ?? '')));
      $birth   = trim((string)($fields['birthDate'] ?? ''));
      $contact = preg_replace('/\D+/', '', (string)($fields['contactNo'] ?? ''));

      // The email must match the account's registered address, and the
      // birth date + mobile must match a canonical student record. Empty
      // stored values never count as a match.
      $emailOk = $email !== ''
        && (string)($user['email'] ?? '') !== ''
        && strtolower((string)$user['email']) === $email;

      $recordOk = false;
      if ($birth !== '' && $contact !== '') {
        foreach (['studentsignup', 'studeprofile'] as $table) {
          if (!$this->db->table_exists($table)) {
            continue;
          }
          $row = $this->db
            ->where('StudentNumber', $studentNumber)
            ->limit(1)
            ->get($table)
            ->row_array();
          if (!$row) {
            continue;
          }
          $storedBirth   = trim((string)($row['birthDate'] ?? ''));
          $storedContact = preg_replace('/\D+/', '', (string)($row['contactNo'] ?? ''));
          // '0000-00-00' is the schema default for unset dates, not a real
          // birthday — never let it satisfy the check.
          if ($storedBirth !== ''
            && $storedBirth !== '0000-00-00'
            && $storedContact !== ''
            && $storedBirth === $birth
            && $storedContact === $contact) {
            $recordOk = true;
            break;
          }
        }
      }

      $ok = $emailOk && $recordOk;
    } else {
      return $fail();
    }

    return $ok
      ? ['ok' => true, 'user' => $user, 'message' => '']
      : $fail();
  }

  /**
   * Seconds before the account may be reset manually again. Bounds how
   * fast the password can be re-rotated through this path.
   */
  public function manualResetCooldownRemaining($username)
  {
    $row = $this->db
      ->select('manual_reset_at')
      ->where('username', (string)$username)
      ->limit(1)
      ->get('o_users')
      ->row_array();

    if (!$row || empty($row['manual_reset_at'])) {
      return 0;
    }

    $elapsed = time() - strtotime((string)$row['manual_reset_at']);

    return max(0, 300 - $elapsed);
  }

  /**
   * Apply a verified manual reset: new bcrypt password, force-change flag
   * cleared (the user just chose the password), reset timestamp stamped,
   * every web session and mobile token revoked.
   *
   * When the recovery code was the factor that proved identity it is
   * consumed — a code that was used once (and may have leaked) must not be
   * able to reset the account again. The owner mints a fresh one from the
   * Recovery Code page after signing in.
   */
  public function applyManualReset($username, $newPasswordHash, $consumeRecoveryCode = false)
  {
    $username = (string)$username;

    $set = [
      'password'              => (string)$newPasswordHash,
      'force_change_password' => 0,
      'manual_reset_at'       => date('Y-m-d H:i:s'),
    ];
    if ($consumeRecoveryCode) {
      $set['recovery_code_hash']   = null;
      $set['recovery_code_set_at'] = null;
    }

    $updated = $this->db
      ->where('username', $username)
      ->update('o_users', $set);

    if (!$updated) {
      return false;
    }

    // A reset is how a locked-out or compromised account is recovered —
    // every existing session and bearer token for it must end.
    $this->load->library('sessionregistry');
    $this->sessionregistry->revokeAllForUser($username, 'password reset');

    $this->load->model('MobileTokenModel');
    $this->MobileTokenModel->revokeAllForUser($username);

    return true;
  }

  /**
   * Tell the registered address that a manual reset happened. The mail
   * stays queued even while sending is broken, and when it does land it
   * is the backstop that exposes a takeover within minutes.
   */
  public function queueManualResetNotice(array $user)
  {
    $email = trim((string)($user['email'] ?? ''));
    if ($email === '') {
      return false;
    }

    $schoolSettings = $this->db->get('o_srms_settings')->row();
    $schoolName = $schoolSettings ? $schoolSettings->SchoolName : 'School Records Management System';
    $when = date('F j, Y \a\t g:i A');

    $body = '
      <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; color: #333;">
        <div style="max-width: 600px; margin: auto; background: white; border-radius: 5px; padding: 20px;">
          <h2 style="color: #d97706;">Password Changed by Manual Verification</h2>
          <p>Dear <strong>' . htmlspecialchars((string)($user['fName'] ?? '')) . '</strong>,</p>
          <p>The password for your <strong>' . htmlspecialchars($schoolName) . '</strong> account
             (<strong>' . htmlspecialchars((string)($user['username'] ?? '')) . '</strong>)
             was changed through manual identity verification on ' . htmlspecialchars($when) . '.</p>
          <p>If this was you, you can ignore this message and sign in with your new password.</p>
          <p><strong>If this was NOT you,</strong> your account may be compromised — reset your
             password again immediately and report it to the school office or IT staff.</p>
          <p style="margin-top: 30px;">Best regards,<br><strong>' . htmlspecialchars($schoolName) . '</strong></p>
          <hr style="margin-top: 40px;">
          <p style="font-size: 12px; color: #999;">This is an automated message. Please do not reply.</p>
        </div>
      </div>';

    return (bool)fbmso_mailqueue_push(
      $this,
      $email,
      'Security notice: password changed - ' . $schoolName,
      $body,
      $schoolName
    );
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

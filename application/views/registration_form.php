<!DOCTYPE html>
<html class="bg-black" lang="en">

<head>
  <meta charset="UTF-8">
  <title>Attendance Portal | Registration</title>
  <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport">

  <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">
  <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
  <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/app.css?v=20260922" rel="stylesheet" type="text/css" id="app-stylesheet" />

  <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
  <link href="<?= base_url(); ?>assets/css/registration_form.css?v=20261008" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=12'); ?>">
  <meta name="theme-color" content="#1a2942">
  <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
  <script src="<?= base_url('assets/js/mobile-shell-early.js?v=6'); ?>"></script>

  <?php include(APPPATH . 'views/includes/ui_kit.php'); ?>
  <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>

<body data-layout="horizontal">
  <div class="blob blob-a"></div>
  <div class="blob blob-b"></div>
  <div class="reg-card fade-2">
    <div class="card-banner">
      <div class="ring ring-1"></div>
      <div class="ring ring-2"></div>

      <div class="banner-text">
        <div class="banner-eyebrow">New Student Account</div>
        <div class="banner-title"><?= !empty($isAdmin) ? 'Register a Student' : 'Create Your Profile'; ?></div>
        <div class="banner-sub"><?= !empty($isAdmin)
            ? 'You are creating an account for a student.<br>They will sign in with the Student ID and password below.'
            : 'Fill in the form to get started with<br>your attendance tracking account.'; ?></div>
      </div>

      <div class="banner-qr">
        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="8" y="8" width="32" height="32" rx="4" stroke="rgba(255,255,255,0.25)" stroke-width="2" />
          <rect x="14" y="14" width="20" height="20" rx="2" fill="rgba(255,255,255,0.12)" />
          <rect x="60" y="8" width="32" height="32" rx="4" stroke="rgba(255,255,255,0.25)" stroke-width="2" />
          <rect x="66" y="14" width="20" height="20" rx="2" fill="rgba(255,255,255,0.12)" />
          <rect x="8" y="60" width="32" height="32" rx="4" stroke="rgba(255,255,255,0.25)" stroke-width="2" />
          <rect x="14" y="66" width="20" height="20" rx="2" fill="rgba(255,255,255,0.12)" />
          <rect x="60" y="60" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="72" y="60" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="84" y="60" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="60" y="72" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="72" y="72" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="84" y="72" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="60" y="84" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="72" y="84" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="84" y="84" width="8" height="8" rx="1" fill="rgba(255,255,255,0.2)" />
          <rect x="44" y="8" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="44" y="20" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="44" y="32" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="8" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="20" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="32" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="44" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="56" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="68" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="80" y="44" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
          <rect x="44" y="56" width="8" height="8" rx="1" fill="rgba(255,255,255,0.12)" />
        </svg>
        <div class="scan-beam"></div>
        <div class="scan-beam-h"></div>
        <div class="qr-corner tl"></div>
        <div class="qr-corner tr"></div>
        <div class="qr-corner bl"></div>
        <div class="qr-corner br"></div>
      </div>
    </div>
    <div class="card-body-inner fade-3">
      <?php
      $oldInput = $this->session->flashdata('old_input');
      if (!is_array($oldInput)) {
        $oldInput = [];
      }
      $old = static function ($key, $default = '') use ($oldInput) {
        return htmlspecialchars((string)($oldInput[$key] ?? $default), ENT_QUOTES, 'UTF-8');
      };
      $oldSection   = trim((string)($oldInput['section'] ?? ''));
      $oldCourse1   = (string)($oldInput['Course1'] ?? '');
      $oldYearLevel = (string)($oldInput['yearLevel'] ?? '');
      $oldSex       = (string)($oldInput['Sex'] ?? '');
      ?>

      <?php if ($this->session->flashdata('msg')): ?>
        <div class="flash"><?php echo $this->session->flashdata('msg'); ?></div>
      <?php endif; ?>

      <form method="post"
        class="parsley-examples"
        data-majors-by-course-url="<?= htmlspecialchars(base_url('Registration/getMajorsByCourse'), ENT_QUOTES, 'UTF-8'); ?>"
        data-cities-by-province-url="<?= htmlspecialchars(base_url('Registration/getCitiesByProvince'), ENT_QUOTES, 'UTF-8'); ?>"
        data-barangays-by-city-url="<?= htmlspecialchars(base_url('Registration/getBarangaysByCity'), ENT_QUOTES, 'UTF-8'); ?>"
        data-sections-by-course-year-url="<?= htmlspecialchars(base_url('Registration/getSectionsByCourseYear'), ENT_QUOTES, 'UTF-8'); ?>"
        data-check-availability-url="<?= htmlspecialchars(base_url('Registration/checkAvailability'), ENT_QUOTES, 'UTF-8'); ?>"
        data-recaptcha-required-message="Please confirm you are not a robot.">
        <?php if (!empty($isAdmin)): ?>
          <input type="hidden" name="source" value="admin">
        <?php endif; ?>

        <input type="hidden" name="nationality" value="Filipino">
        <input type="hidden" name="working" value="No">
        <input type="hidden" name="VaccStat" value="">
        <input type="hidden" id="resultBday" name="age" value="<?= $old('age'); ?>" readonly required autocomplete="off">
        <input type="hidden" name="Major1" id="major1" value="<?= $old('Major1'); ?>">

        <!-- Sticky form progress indicator -->
        <div class="reg-progress" id="regProgress">
          <div class="reg-progress-bar">
            <div class="reg-progress-step active" data-step="1">
              <span class="reg-progress-num">1</span>
              <span class="reg-progress-text">Credentials</span>
            </div>
            <div class="reg-progress-line"></div>
            <div class="reg-progress-step" data-step="2">
              <span class="reg-progress-num">2</span>
              <span class="reg-progress-text">Personal</span>
            </div>
            <div class="reg-progress-line"></div>
            <div class="reg-progress-step" data-step="3">
              <span class="reg-progress-num">3</span>
              <span class="reg-progress-text">Academic</span>
            </div>
          </div>
        </div>

        <div class="section-head" id="section-credentials">
          <div class="section-dot"></div>
          <div class="section-label">Student Credentials</div>
          <div class="section-line"></div>
        </div>

        <div class="row-fields cols-3 credentials-row">
          <div class="field-group">
            <label class="field-label" for="StudentNumber">Student ID <span class="req">*</span></label>
            <div class="field-wrap">
              <input type="text"
                id="StudentNumber"
                class="field"
                name="StudentNumber"
                placeholder="e.g. 2023-0446"
                inputmode="numeric"
                autocomplete="username"
                spellcheck="false"
                minlength="9"
                maxlength="9"
                pattern="[0-9]{4}-[0-9]{4}"
                title="Use the school ID format YYYY-NNNN, for example 2023-0446."
                value="<?= $old('StudentNumber'); ?>"
                required>
            </div>
            <span class="field-hint">Use your school ID in YYYY-NNNN format.</span>
            <span class="availability-msg" id="student-number-status" aria-live="polite"></span>
          </div>
          <div class="field-group">
            <label class="field-label" for="password"><?= !empty($isAdmin) ? 'Student Password' : 'Password'; ?> <?= empty($isAdmin) ? '<span class="req">*</span>' : ''; ?></label>
            <div class="field-wrap password-wrap">
              <input type="password" id="password" class="field" name="password" minlength="8" autocomplete="new-password" <?= empty($isAdmin) ? 'required' : ''; ?>>
              <button type="button" class="password-toggle" data-target="#password" aria-label="Show password" title="Show password">
                <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
              </button>
            </div>
            <?php if (!empty($isAdmin)): ?>
              <span class="field-hint">Optional — leave blank and the student's birth date (YYYY-MM-DD) becomes the password.</span>
            <?php endif; ?>
            <div class="password-meter is-empty" id="password-strength-meter" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-valuetext="No password entered">
              <div class="password-meter-track" aria-hidden="true"><span id="password-strength-bar"></span></div>
              <div class="password-meter-copy">
                <span id="password-strength-label">Strength: Not set</span>
                <span id="password-length-label">0 / 8 minimum</span>
              </div>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label" for="confirm_password">Confirm Password <?= empty($isAdmin) ? '<span class="req">*</span>' : ''; ?></label>
            <div class="field-wrap password-wrap">
              <input type="password" id="confirm_password" class="field" name="confirm_password" minlength="8" autocomplete="new-password" <?= empty($isAdmin) ? 'required' : ''; ?>>
              <button type="button" class="password-toggle" data-target="#confirm_password" aria-label="Show password" title="Show password">
                <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
              </button>
            </div>
            <?php if (!empty($isAdmin)): ?>
              <span class="field-hint">Required only if you set a password above.</span>
            <?php endif; ?>
            <span class="availability-msg" id="password-match-status" aria-live="polite"></span>
          </div>
        </div>
        <div class="section-head" id="section-personal">
          <div class="section-dot"></div>
          <div class="section-label">Personal Information</div>
          <div class="section-line"></div>
        </div>
        <div class="row-fields cols-4">
          <div class="field-group">
            <label class="field-label" for="FirstName">First Name <span class="req">*</span></label>
            <input type="text" id="FirstName" class="field" name="FirstName" value="<?= $old('FirstName'); ?>" style="text-transform:uppercase;" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="MiddleName">Middle Name</label>
            <input type="text" id="MiddleName" class="field" name="MiddleName" value="<?= $old('MiddleName'); ?>" style="text-transform:uppercase;">
          </div>
          <div class="field-group">
            <label class="field-label" for="LastName">Last Name <span class="req">*</span></label>
            <input type="text" id="LastName" class="field" name="LastName" value="<?= $old('LastName'); ?>" style="text-transform:uppercase;" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="nameExtn">Ext.</label>
            <input type="text" id="nameExtn" class="field" name="nameExtn" value="<?= $old('nameExtn'); ?>" placeholder="Jr., Sr." style="text-transform:uppercase;">
          </div>
        </div>
        <div class="row-fields cols-4">
          <div class="field-group">
            <label class="field-label" for="Sex">Sex <span class="req">*</span></label>
            <select class="field" id="Sex" name="Sex" required>
              <option value=""></option>
              <option value="Female" <?= $oldSex === 'Female' ? 'selected' : ''; ?>>Female</option>
              <option value="Male" <?= $oldSex === 'Male' ? 'selected' : ''; ?>>Male</option>
              <option value="Others" <?= $oldSex === 'Others' ? 'selected' : ''; ?>>Others</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="bday">Date of Birth <span class="req">*</span></label>
            <input type="date" id="bday" class="field" name="birthDate" value="<?= $old('birthDate'); ?>" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="email">E-mail Address <span class="req">*</span></label>
            <input type="email" id="email" class="field" name="email" value="<?= $old('email'); ?>" placeholder="you@email.com" required>
            <span class="availability-msg" id="email-status" aria-live="polite"></span>
          </div>
          <div class="field-group">
            <label class="field-label" for="contactNo">Mobile No. <span class="req">*</span></label>
            <input type="tel"
              id="contactNo"
              class="field"
              name="contactNo"
              value="<?= $old('contactNo'); ?>"
              placeholder="09XX XXX XXXX"
              inputmode="numeric"
              autocomplete="tel-national"
              minlength="13"
              maxlength="13"
              pattern="09[0-9]{2} [0-9]{3} [0-9]{4}"
              title="Enter an 11-digit mobile number starting with 09."
              required>
          </div>
        </div>
        <div class="section-head" id="section-academic">
          <div class="section-dot"></div>
          <div class="section-label">Academic Information</div>
          <div class="section-line"></div>
        </div>

        <div class="row-fields cols-3">
          <div class="field-group">
            <label class="field-label" for="course1">Course / Program <span class="req">*</span></label>
            <select name="Course1" id="course1" class="field" required>
              <option value="">Select Course</option>
              <?php foreach ($course as $row) {
                $courseValue = (string)$row->CourseDescription;
                $selected = ($oldCourse1 === $courseValue) ? ' selected' : '';
                echo '<option value="' . htmlspecialchars($courseValue, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($courseValue, ENT_QUOTES, 'UTF-8') . '</option>';
              } ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="yearLevel">Year Level <span class="req">*</span></label>
            <select class="field" name="yearLevel" id="yearLevel" required>
              <option value="">Select Year Level</option>
              <option value="1st" <?= $oldYearLevel === '1st' ? 'selected' : ''; ?>>1st Year</option>
              <option value="2nd" <?= $oldYearLevel === '2nd' ? 'selected' : ''; ?>>2nd Year</option>
              <option value="3rd" <?= $oldYearLevel === '3rd' ? 'selected' : ''; ?>>3rd Year</option>
              <option value="4th" <?= $oldYearLevel === '4th' ? 'selected' : ''; ?>>4th Year</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="section">Section <span class="req">*</span></label>
            <select class="field" name="section" id="section" required>
              <option value="">Select Section</option>
              <?php if ($oldSection !== ''): ?>
                <option value="<?= htmlspecialchars($oldSection, ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($oldSection, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <?php if (empty($isAdmin)): ?>
          <div class="captcha-row">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($site_key) ?>"></div>
          </div>
        <?php endif; ?>

        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
          <button type="submit" name="register" id="submitBtn" class="btn-submit">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span><?= !empty($isAdmin) ? 'Create Student Account' : 'Create My Account'; ?></span>
          </button>
          <a href="<?= !empty($isAdmin) ? site_url('Page/profileList') : base_url(); ?>" class="btn-back" title="<?= !empty($isAdmin) ? 'Back to the students list' : 'Back to sign in'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            <span><?= !empty($isAdmin) ? 'Back to Students' : 'Login Instead ?'; ?></span>
          </a>
        </div>

        <div class="form-footer">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <?= !empty($isAdmin) ? 'The student\'s information' : 'Your information'; ?> is securely stored and used solely for attendance purposes.
        </div>

      </form>
    </div>
  </div>

  <?php
  $regSuccess = (isset($registration_success) && is_array($registration_success)) ? $registration_success : null;
  if ($regSuccess):
    $rsIsAdmin = !empty($regSuccess['isAdmin']);
    $rsEmailOk = !empty($regSuccess['emailQueued']);
    $rsEsc = static function ($v) {
      return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };
  ?>
  <div class="cred-gate" id="credGate"
    data-name="<?= $rsEsc($regSuccess['name'] ?? ''); ?>"
    data-username="<?= $rsEsc($regSuccess['username'] ?? ''); ?>"
    data-password="<?= $rsEsc($regSuccess['password'] ?? ''); ?>"
    data-recovery="<?= $rsEsc($regSuccess['recovery'] ?? ''); ?>"
    data-email="<?= $rsEsc($regSuccess['email'] ?? ''); ?>"
    data-school="<?= $rsEsc($regSuccess['schoolName'] ?? ''); ?>"
    data-portal="<?= $rsEsc($regSuccess['portalUrl'] ?? base_url()); ?>"
    data-continue-url="<?= $rsEsc($regSuccess['continueUrl'] ?? base_url()); ?>">
    <div class="cred-modal" role="dialog" aria-modal="true" aria-labelledby="credTitle" aria-describedby="credDesc">
      <div class="cred-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="cred-eyebrow"><?= $rsIsAdmin ? 'Student Account Created' : 'Registration Successful'; ?></div>
      <h2 class="cred-title" id="credTitle">Save your credentials</h2>
      <p class="cred-sub" id="credDesc"><?= $rsIsAdmin
          ? 'This is the only time the student\'s password is shown. Download the file and hand it to them.'
          : 'This is the only time your password is shown. Download the file and keep it somewhere safe.'; ?></p>

      <?php if ($rsEmailOk): ?>
        <div class="cred-verify">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
          </svg>
          <div>
            <b><?= $rsIsAdmin ? 'The student must verify this email before signing in.' : 'Verify your email before signing in.'; ?></b><br>
            We sent a verification link to <b><?= $rsEsc($regSuccess['email'] ?? ''); ?></b>.
            <?= $rsIsAdmin ? 'They need to open it and click' : 'Open it and click'; ?>
            <b>Verify Email &amp; Login</b> — the account stays locked until then.
            Check the Spam/Junk folder if it does not arrive.
          </div>
        </div>
      <?php else: ?>
        <div class="cred-verify is-warn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
          <div>
            <b>Account created, but the verification email could not be sent.</b><br>
            <?= $rsIsAdmin
                ? 'The student can use "Resend verification email" on the sign-in page to get a new link.'
                : 'After saving your credentials you will be taken to the resend page so you can request a new link.'; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="cred-box">
        <div class="cred-row">
          <span class="cred-label">Username</span>
          <span class="cred-value" id="credUserText"><?= $rsEsc($regSuccess['username'] ?? ''); ?></span>
        </div>
        <div class="cred-row">
          <span class="cred-label">Password</span>
          <span class="cred-value" id="credPassText" data-showing="0">&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;</span>
          <button type="button" class="cred-eye" id="credPassToggle" aria-label="Show password" title="Show password">
            <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
          </button>
        </div>
        <div class="cred-row">
          <span class="cred-label">Recovery code</span>
          <span class="cred-value" id="credRecoveryText"><?= $rsEsc($regSuccess['recovery'] ?? ''); ?></span>
        </div>
      </div>
      <p class="cred-note">The recovery code resets your password without email — keep it with this file.</p>

      <div class="cred-actions">
        <button type="button" class="cred-btn cred-btn-primary" id="credDownloadBtn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          <span>Download credentials (.txt)</span>
        </button>
        <button type="button" class="cred-btn cred-btn-ghost" id="credCopyBtn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.75a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
          </svg>
          <span>Copy</span>
        </button>
      </div>

      <div class="cred-hint" id="credHint" role="status">Download your credentials to continue — you cannot leave this page until you do.</div>

      <button type="button" class="cred-btn cred-btn-continue" id="credContinueBtn" disabled>
        <span><?= $rsIsAdmin
            ? 'Done — Back to Students'
            : ($rsEmailOk ? 'Continue to Sign In' : 'Continue — Resend Verification Email'); ?></span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </button>
    </div>
  </div>
  <?php endif; ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <script src="<?= base_url(); ?>assets/js/registration_form.js?v=20261008"></script>
  <script src="<?= base_url('assets/js/mobile-shell.js?v=12'); ?>"></script>
  <?php if (empty($isAdmin)): ?>
  <script>
    // Lazy-load reCAPTCHA only when the user interacts with the form,
    // instead of blocking page load with the external Google script.
    (function() {
      var loaded = false;

      function loadRecaptcha() {
        if (loaded) return;
        loaded = true;
        var s = document.createElement('script');
        s.src = 'https://www.google.com/recaptcha/api.js';
        s.async = true;
        s.defer = true;
        document.body.appendChild(s);
      }
      ['focus', 'click', 'touchstart', 'keydown'].forEach(function(evt) {
        document.addEventListener(evt, loadRecaptcha, {
          once: true,
          passive: true
        });
      });
    })();
  </script>
  <?php endif; ?>

</body>

</html>

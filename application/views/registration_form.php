<!DOCTYPE html>
<html class="bg-black" lang="en">

<head>
  <meta charset="UTF-8">
  <title>Attendance Portal | Registration</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">
  <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
  <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&display=swap" rel="stylesheet">

  <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
  <link href="<?= base_url(); ?>assets/css/registration_form.css" rel="stylesheet" type="text/css" />
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
        <div class="banner-title">Create Your Profile</div>
        <div class="banner-sub">Fill in the form to get started with<br>your attendance tracking account.</div>
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
        <div class="qr-corner tl"></div>
        <div class="qr-corner tr"></div>
        <div class="qr-corner bl"></div>
        <div class="qr-corner br"></div>
      </div>
    </div>
    <div class="card-body-inner fade-3">

      <?php if ($this->session->flashdata('msg')): ?>
        <div class="flash"><?php echo $this->session->flashdata('msg'); ?></div>
      <?php endif; ?>

      <form method="post"
        class="parsley-examples"
        data-majors-by-course-url="<?= htmlspecialchars(base_url('Registration/getMajorsByCourse'), ENT_QUOTES, 'UTF-8'); ?>"
        data-cities-by-province-url="<?= htmlspecialchars(base_url('Registration/getCitiesByProvince'), ENT_QUOTES, 'UTF-8'); ?>"
        data-barangays-by-city-url="<?= htmlspecialchars(base_url('Registration/getBarangaysByCity'), ENT_QUOTES, 'UTF-8'); ?>"
        data-sections-by-course-year-url="<?= htmlspecialchars(base_url('Registration/getSectionsByCourseYear'), ENT_QUOTES, 'UTF-8'); ?>"
        data-recaptcha-required-message="Please confirm you are not a robot.">
        <?php if (strtolower((string)$this->input->get('source')) === 'admin'): ?>
          <input type="hidden" name="source" value="admin">
        <?php endif; ?>

        <input type="hidden" name="nationality" value="Filipino">
        <input type="hidden" name="working" value="No">
        <input type="hidden" name="VaccStat" value="">
        <input type="hidden" id="resultBday" name="age" readonly required autocomplete="off">
        <input type="hidden" name="Major1" id="major1">
        <div class="section-head">
          <div class="section-dot"></div>
          <div class="section-label">Student Credentials</div>
          <div class="section-line"></div>
        </div>

        <div class="row-fields cols-2" style="max-width:520px;">
          <div class="field-group">
            <label class="field-label" for="StudentNumber">Student ID <span class="req">*</span></label>
            <div class="field-wrap">
              <input type="text"
                id="StudentNumber"
                class="field"
                name="StudentNumber"
                placeholder="e.g. 2023-0446"
                minlength="4"
                maxlength="20"
                pattern="[A-Za-z0-9\-]+"
                title="Make sure it matches your school ID."
                required>
            </div>
            <span class="field-hint">This becomes your username — match it to your school ID.</span>
          </div>
        </div>
        <div class="section-head">
          <div class="section-dot"></div>
          <div class="section-label">Personal Information</div>
          <div class="section-line"></div>
        </div>
        <div class="row-fields cols-4">
          <div class="field-group">
            <label class="field-label" for="FirstName">First Name <span class="req">*</span></label>
            <input type="text" id="FirstName" class="field" name="FirstName" style="text-transform:uppercase;" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="MiddleName">Middle Name</label>
            <input type="text" id="MiddleName" class="field" name="MiddleName" style="text-transform:uppercase;">
          </div>
          <div class="field-group">
            <label class="field-label" for="LastName">Last Name <span class="req">*</span></label>
            <input type="text" id="LastName" class="field" name="LastName" style="text-transform:uppercase;" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="nameExtn">Ext.</label>
            <input type="text" id="nameExtn" class="field" name="nameExtn" placeholder="Jr., Sr." style="text-transform:uppercase;">
          </div>
        </div>
        <div class="row-fields cols-4">
          <div class="field-group">
            <label class="field-label" for="Sex">Sex <span class="req">*</span></label>
            <select class="field" id="Sex" name="Sex" required>
              <option value=""></option>
              <option>Female</option>
              <option>Male</option>
              <option>Others</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="bday">Date of Birth <span class="req">*</span></label>
            <input type="date" id="bday" class="field" name="birthDate" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="email">E-mail Address <span class="req">*</span></label>
            <input type="email" id="email" class="field" name="email" placeholder="you@email.com" required>
          </div>
          <div class="field-group">
            <label class="field-label" for="contactNo">Mobile No. <span class="req">*</span></label>
            <input type="text" id="contactNo" class="field" name="contactNo" placeholder="09XX XXX XXXX" required>
          </div>
        </div>
        <div class="section-head">
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
                echo '<option value="' . htmlspecialchars($row->CourseDescription, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row->CourseDescription, ENT_QUOTES, 'UTF-8') . '</option>';
              } ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="yearLevel">Year Level <span class="req">*</span></label>
            <select class="field" name="yearLevel" id="yearLevel" required>
              <option value="">Select Year Level</option>
              <option value="1st">1st Year</option>
              <option value="2nd">2nd Year</option>
              <option value="3rd">3rd Year</option>
              <option value="4th">4th Year</option>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label" for="section">Section <span class="req">*</span></label>
            <select class="field" name="section" id="section" required>
              <option value="">Select Section</option>
            </select>
          </div>
        </div>

        <div class="captcha-row">
          <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($site_key) ?>"></div>
        </div>

        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
          <button type="submit" name="register" id="submitBtn" class="btn-submit">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Create My Account</span>
          </button>
        </div>

        <div class="form-footer">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Your information is securely stored and used solely for attendance purposes.
        </div>

      </form>
    </div>
  </div>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <script src="<?= base_url(); ?>assets/js/registration_form.js"></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

</body>

</html>

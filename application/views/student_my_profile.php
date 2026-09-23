<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">
          <link rel="stylesheet" href="<?= base_url('assets/css/my_profile.css?v=20260922-2'); ?>">
          <?php
          $account    = $bundle->account ?? (object)[];
          $profile    = $bundle->profile ?? (object)[];
          $enrollment = $bundle->enrollment ?? (object)[];

          $studentNumber = trim((string)($account->username ?? $profile->StudentNumber ?? ''));
          $flashSuccess  = $this->session->flashdata('success');
          $flashDanger   = $this->session->flashdata('danger');

          // Students may not edit identity fields (name, student number).
          // Staff editing through this view is not the normal flow — they
          // use updateStudeProfile — so lock these for everyone here.
          $isStudent = in_array((string)$this->session->userdata('level'), ['Student', 'Stude Applicant'], true);
          $identityReadonly = $isStudent ? 'readonly' : '';

          $firstName  = trim((string)($account->fName ?? $profile->FirstName ?? ''));
          $middleName = trim((string)($account->mName ?? $profile->MiddleName ?? ''));
          $lastName   = trim((string)($account->lName ?? $profile->LastName ?? ''));
          $nameExtn   = trim((string)($profile->nameExtn ?? ''));

          $birthDate  = trim((string)($profile->birthDate ?? ''));
          if ($birthDate === '0000-00-00') {
            $birthDate = '';
          }
          $sexValue   = trim((string)($profile->Sex ?? ''));
          $civilStatus = trim((string)($profile->CivilStatus ?? ''));
          $contactNo  = trim((string)($profile->contactNo ?? ''));
          $email      = trim((string)($account->email ?? $profile->email ?? ''));
          $birthPlace = trim((string)($profile->BirthPlace ?? ''));
          $ageValue   = trim((string)($profile->age ?? ''));

          // Email is locked once set — an empty one may still be filled in
          $emailReadonly = ($isStudent && $email !== '') ? 'readonly' : '';

          // Display mobile numbers in the same 09XX XXX XXXX format the
          // registration form uses (stored value is 11 digits).
          $contactDigits = substr(preg_replace('/\D+/', '', $contactNo), 0, 11);
          if (strlen($contactDigits) > 7) {
            $contactNo = substr($contactDigits, 0, 4) . ' ' . substr($contactDigits, 4, 3) . ' ' . substr($contactDigits, 7);
          } elseif (strlen($contactDigits) > 4) {
            $contactNo = substr($contactDigits, 0, 4) . ' ' . substr($contactDigits, 4);
          } else {
            $contactNo = $contactDigits;
          }

          $currentCourseDesc = trim((string)($currentCourseDesc ?? ''));
          $currentYear       = trim((string)($currentYear ?? ''));
          $currentSection    = trim((string)($currentSection ?? ''));
          $nationality       = trim((string)($profile->nationality ?? 'Filipino'));
          $working           = trim((string)($profile->working ?? 'No'));
          $vaccStat          = trim((string)($profile->VaccStat ?? ''));
          $sitioValue        = trim((string)($profile->sitio ?? $profile->Sitio ?? ''));
          $brgyValue         = trim((string)($profile->brgy ?? $profile->Brgy ?? ''));
          $cityValue         = trim((string)($profile->city ?? $profile->City ?? ''));
          $provinceValue     = trim((string)($profile->province ?? $profile->Province ?? ''));
          $currentProvince   = trim((string)($currentProvince ?? $provinceValue));
          $currentCity       = trim((string)($currentCity ?? $cityValue));
          $currentBrgy       = trim((string)($currentBrgy ?? $brgyValue));
          $civilStatusOptions = isset($civilStatusOptions) && is_array($civilStatusOptions) ? $civilStatusOptions : ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
          if ($civilStatus !== '' && !in_array($civilStatus, $civilStatusOptions, true)) {
            $civilStatusOptions[] = $civilStatus;
          }

          $provincesList   = isset($provincesList) && is_array($provincesList) ? $provincesList : [];
          $citiesList      = isset($citiesList) && is_array($citiesList) ? $citiesList : [];
          $barangaysList   = isset($barangaysList) && is_array($barangaysList) ? $barangaysList : [];

          $pageTitle       = isset($pageTitle) && $pageTitle !== '' ? (string)$pageTitle : 'My Profile';
          $pageDescription = isset($pageDescription) && $pageDescription !== '' ? (string)$pageDescription : 'Update your personal and academic details.';
          $backUrl         = isset($backUrl) ? (string)$backUrl : base_url('Page/student');
          $backLabel       = isset($backLabel) && $backLabel !== '' ? (string)$backLabel : 'Back to Dashboard';
          $submitLabel     = isset($submitLabel) && $submitLabel !== '' ? (string)$submitLabel : 'Save Changes';
          ?>

          <div class="reg-scope">
            <div class="reg-card">
              <div class="card-banner">
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>

                <div class="banner-text">
                  <div class="banner-eyebrow">Student Self-Service</div>
                  <div class="banner-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="banner-sub"><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?></div>
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

              <div class="card-body-inner">
                <form method="post" autocomplete="off" class="parsley-examples"
                  data-check-availability-url="<?= htmlspecialchars(site_url('Page/checkSignupAvailability'), ENT_QUOTES, 'UTF-8'); ?>"
                  data-exclude-student-number="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>">

                  <input type="hidden" name="oldStudentNo" value="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="nationality" value="<?= htmlspecialchars($nationality, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="working" value="<?= htmlspecialchars($working, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="VaccStat" value="<?= htmlspecialchars($vaccStat, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="Major1" id="major1" value="<?= htmlspecialchars($bundle->enrollment->Major ?? $bundle->profile->Major ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" id="resultBday" name="age" value="<?= htmlspecialchars($ageValue, ENT_QUOTES, 'UTF-8'); ?>" readonly required autocomplete="off">

                  <!-- Sticky form progress indicator -->
                  <div class="reg-progress" id="regProgress">
                    <div class="reg-progress-bar">
                      <div class="reg-progress-step active" data-step="1">
                        <span class="reg-progress-num">1</span>
                        <span class="reg-progress-text">Personal</span>
                      </div>
                      <div class="reg-progress-line"></div>
                      <div class="reg-progress-step" data-step="2">
                        <span class="reg-progress-num">2</span>
                        <span class="reg-progress-text">Academic</span>
                      </div>
                      <div class="reg-progress-line"></div>
                      <div class="reg-progress-step" data-step="3">
                        <span class="reg-progress-num">3</span>
                        <span class="reg-progress-text">Address</span>
                      </div>
                    </div>
                  </div>

                  <div class="section-head" id="section-personal">
                    <div class="section-dot"></div>
                    <div class="section-label">Personal Information</div>
                    <div class="section-line"></div>
                  </div>

                  <?php if ($isStudent): ?>
                  <div class="reg-note">
                    <i class="mdi mdi-lock-outline"></i>
                    <span>Your <?= $emailReadonly !== '' ? '<b>Student ID</b>, <b>Name</b>, and <b>Email</b> are' : '<b>Student ID</b> and <b>Name</b> are'; ?> locked. Contact the admin to request changes.</span>
                  </div>
                  <?php endif; ?>

                  <div class="row-fields cols-4">
                    <div class="field-group">
                      <label class="field-label" for="StudentNumber">Student ID <span class="req">*</span></label>
                      <div class="field-wrap">
                        <input type="text"
                          id="StudentNumber"
                          class="field"
                          name="StudentNumber"
                          value="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>"
                          placeholder="Student ID"
                          minlength="4"
                          maxlength="20"
                          pattern="[A-Za-z0-9\-]+"
                          title="Use letters, numbers, and hyphen only."
                          <?= $identityReadonly ?>
                          required>
                      </div>
                      <span class="availability-msg" id="student-number-status" aria-live="polite"></span>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="FirstName">First Name <span class="req">*</span></label>
                      <input type="text" id="FirstName" class="field" name="FirstName" style="text-transform: uppercase;"
                        placeholder="First Name"
                        value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>" <?= $identityReadonly ?> required>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="MiddleName">Middle Name</label>
                      <input type="text" id="MiddleName" class="field" name="MiddleName" style="text-transform: uppercase;"
                        placeholder="Middle Name"
                        value="<?= htmlspecialchars($middleName, ENT_QUOTES, 'UTF-8'); ?>" <?= $identityReadonly ?>>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="LastName">Last Name <span class="req">*</span></label>
                      <input type="text" id="LastName" class="field" name="LastName" style="text-transform: uppercase;"
                        placeholder="Last Name"
                        value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'); ?>" <?= $identityReadonly ?> required>
                    </div>
                  </div>

                  <div class="row-fields cols-4">
                    <div class="field-group">
                      <label class="field-label" for="nameExtn">Ext.</label>
                      <input type="text" id="nameExtn" class="field" name="nameExtn" style="text-transform: uppercase;"
                        value="<?= htmlspecialchars($nameExtn, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Jr., Sr." <?= $identityReadonly ?>>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="Sex">Sex <span class="req">*</span></label>
                      <select class="field" id="Sex" name="Sex" required>
                        <option value=""></option>
                        <?php foreach ($sexOptions as $option): ?>
                          <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= strcasecmp($sexValue, $option) === 0 ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="bday">Date of Birth <span class="req">*</span></label>
                      <input type="date" id="bday" class="field" name="birthDate" onchange="submitBday()"
                        value="<?= htmlspecialchars($birthDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="ageDisplay">Age</label>
                      <input type="text" id="ageDisplay" class="field" value="<?= htmlspecialchars($ageValue, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                  </div>

                  <div class="row-fields cols-4">
                    <div class="field-group">
                      <label class="field-label" for="email">E-mail Address <span class="req">*</span></label>
                      <input type="email" id="email" class="field" name="email"
                        placeholder="you@email.com"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" <?= $emailReadonly ?> required>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="contactNo">Mobile No. <span class="req">*</span></label>
                      <input type="tel"
                        id="contactNo"
                        class="field"
                        name="contactNo"
                        value="<?= htmlspecialchars($contactNo, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="09XX XXX XXXX"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        minlength="13"
                        maxlength="13"
                        pattern="09[0-9]{2} [0-9]{3} [0-9]{4}"
                        title="Enter an 11-digit mobile number starting with 09."
                        required>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="CivilStatus">Civil Status</label>
                      <select class="field" id="CivilStatus" name="CivilStatus">
                        <option value=""></option>
                        <?php foreach ($civilStatusOptions as $status): ?>
                          <?php $trimStatus = trim((string)$status); ?>
                          <option value="<?= htmlspecialchars($trimStatus, ENT_QUOTES, 'UTF-8'); ?>" <?= strcasecmp($civilStatus, $trimStatus) === 0 ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($trimStatus, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="BirthPlace">Birth Place</label>
                      <input type="text" id="BirthPlace" class="field" name="BirthPlace"
                        placeholder="City / Province"
                        value="<?= htmlspecialchars($birthPlace, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <?php foreach ($courses as $course):
                          $desc = trim((string)$course->CourseDescription);
                          $selected = (strcasecmp($currentCourseDesc, $desc) === 0);
                        ?>
                          <option value="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="yearLevel">Year Level <span class="req">*</span></label>
                      <select class="field" name="yearLevel" id="yearLevel" required>
                        <option value="">Select Year Level</option>
                        <?php foreach ($yearLevels as $yl): ?>
                          <option value="<?= htmlspecialchars($yl, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= strcasecmp($currentYear, $yl) === 0 ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($yl, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="section">Section <span class="req">*</span></label>
                      <select class="field" name="section" id="section" data-current="<?= htmlspecialchars($currentSection, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <option value="">Select Section</option>
                      </select>
                    </div>
                  </div>

                  <div class="section-head" id="section-address">
                    <div class="section-dot"></div>
                    <div class="section-label">Address Information</div>
                    <div class="section-line"></div>
                  </div>

                  <div class="row-fields cols-4">
                    <div class="field-group">
                      <label class="field-label" for="sitio">Street / Sitio</label>
                      <input type="text" id="sitio" class="field" name="sitio"
                        placeholder="Street / Sitio"
                        value="<?= htmlspecialchars($sitioValue, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="province">Province</label>
                      <select id="province" name="province" class="field" data-current="<?= htmlspecialchars($currentProvince, ENT_QUOTES, 'UTF-8'); ?>">
                        <option value="">Select Province</option>
                        <?php foreach ($provincesList as $provinceObj):
                          $provName = trim((string)($provinceObj->Province ?? ''));
                          if ($provName === '') {
                            continue;
                          }
                          $selected = (strcasecmp($currentProvince, $provName) === 0) ? 'selected' : '';
                        ?>
                          <option value="<?= htmlspecialchars($provName, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>>
                            <?= htmlspecialchars($provName, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="city">City / Municipality</label>
                      <select id="city" name="city" class="field" data-current="<?= htmlspecialchars($currentCity, ENT_QUOTES, 'UTF-8'); ?>">
                        <option value="">Select City/Municipality</option>
                        <?php foreach ($citiesList as $cityObj):
                          $cityName = trim((string)($cityObj->City ?? ''));
                          if ($cityName === '') {
                            continue;
                          }
                          $selected = (strcasecmp($currentCity, $cityName) === 0) ? 'selected' : '';
                        ?>
                          <option value="<?= htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>>
                            <?= htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="barangay">Barangay</label>
                      <select id="barangay" name="brgy" class="field" data-current="<?= htmlspecialchars($currentBrgy, ENT_QUOTES, 'UTF-8'); ?>">
                        <option value="">Select Barangay</option>
                        <?php foreach ($barangaysList as $brgyObj):
                          $brgyName = trim((string)($brgyObj->Brgy ?? ''));
                          if ($brgyName === '') {
                            continue;
                          }
                          $selected = (strcasecmp($currentBrgy, $brgyName) === 0) ? 'selected' : '';
                        ?>
                          <option value="<?= htmlspecialchars($brgyName, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>>
                            <?= htmlspecialchars($brgyName, ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-top:10px;">
                    <button type="submit" class="btn-submit">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                      </svg>
                      <span><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                    <?php if ($backUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-back d-md-none">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                      </svg>
                      <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <?php endif; ?>
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
          </div>

        </div>
      </div>
      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script>
    // Flash messages are shown by the shared toast bridge (includes/ui_kit.php).
  </script>
  <script>
    function submitBday() {
      var input = document.getElementById('bday');
      var target = document.getElementById('resultBday');
      if (!input || !target) {
        return;
      }
      var value = input.value;
      if (!value) {
        target.value = '';
        return;
      }
      var birth = new Date(value);
      if (isNaN(birth.getTime())) {
        target.value = '';
        return;
      }
      var today = new Date();
      var age = today.getFullYear() - birth.getFullYear();
      var m = today.getMonth() - birth.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
      }
      target.value = age >= 0 ? age : '';
      var display = document.getElementById('ageDisplay');
      if (display) {
        display.value = (age >= 0 ? age : '');
      }
    }
  </script>
  <script>
    (function($) {
      var $course = $('#course1');
      var $year = $('#yearLevel');
      var $section = $('#section');

      function populateSections(html) {
        var current = ($section.data('current') || '').trim();
        if (!html) {
          $section.html('<option value="">Select Section</option>');
          return;
        }
        var $temp = $('<select>' + html + '</select>');
        if (current) {
          $temp.find('option').each(function() {
            var val = ($(this).val() || '').trim();
            if (val && val.toLowerCase() === current.toLowerCase()) {
              $(this).attr('selected', 'selected');
            }
          });
        }
        $section.html($temp.html());
      }

      function reloadSections() {
        var course = ($course.val() || '').trim();
        var year = ($year.val() || '').trim();
        if (!course || !year) {
          $section.html('<option value="">Select Section</option>');
          return;
        }
        $.post('<?= base_url("Registration/getSectionsByCourseYear"); ?>', {
            course: course,
            yearLevel: year
          })
          .done(function(html) {
            populateSections(html);
          })
          .fail(function() {
            UI.error('Could not load the section list. Please try again.');
            $section.html('<option value="">Select Section</option>');
          });
      }

      $(function() {
        $course.on('change', function() {
          $section.data('current', '');
          reloadSections();
        });
        $year.on('change', function() {
          $section.data('current', '');
          reloadSections();
        });

        reloadSections();
        submitBday();
        var ageDisplay = document.getElementById('ageDisplay');
        var hiddenAge = document.getElementById('resultBday');
        if (ageDisplay && hiddenAge && hiddenAge.value) {
          ageDisplay.value = hiddenAge.value;
        }
      });
    })(jQuery);
  </script>

  <!-- Mobile number: digits only, auto-formatted as 09XX XXX XXXX (same as registration) -->
  <script>
    (function($) {
      var $contactNumber = $('#contactNo');
      if (!$contactNumber.length) {
        return;
      }

      var formatContactNumber = function(value) {
        var digits = (value || '').replace(/\D/g, '').slice(0, 11);
        if (digits.length > 7) {
          return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7);
        }
        if (digits.length > 4) {
          return digits.slice(0, 4) + ' ' + digits.slice(4);
        }
        return digits;
      };

      var syncContactNumber = function() {
        var formatted = formatContactNumber($contactNumber.val());
        if ($contactNumber.val() !== formatted) {
          $contactNumber.val(formatted);
        }

        var digits = formatted.replace(/\D/g, '');
        var inputEl = $contactNumber.get(0);
        if (!digits || /^09\d{9}$/.test(digits)) {
          inputEl.setCustomValidity('');
        } else {
          inputEl.setCustomValidity('Enter an 11-digit mobile number starting with 09.');
        }
      };

      $contactNumber.on('input blur', syncContactNumber);
      syncContactNumber();
    })(jQuery);
  </script>

  <!-- Province → City → Barangay -->
  <script>
    $(function() {
      var $province = $('#province');
      var $city = $('#city');
      var $barangay = $('#barangay');
      var currentProvince = ($province.data('current') || '').trim();
      var currentCity = ($city.data('current') || '').trim();
      var currentBrgy = ($barangay.data('current') || '').trim();

      $province.on('change', function() {
        var province = $(this).val();
        if (province) {
          $.post('<?= base_url("Registration/getCitiesByProvince") ?>', {
              province: province
            })
            .done(function(html) {
              $city.html(html);
              $barangay.html('<option value="">Select Barangay</option>');
              if (currentCity) {
                $city.val(currentCity);
                if ($city.val()) {
                  $city.trigger('change');
                }
                currentCity = '';
              }
            })
            .fail(function() {
              UI.error('Could not load the city list. Please try again.');
            });
        } else {
          $city.html('<option value="">Select City/Municipality</option>');
          $barangay.html('<option value="">Select Barangay</option>');
        }
      });

      $city.on('change', function() {
        var city = $(this).val();
        if (city) {
          $.post('<?= base_url("Registration/getBarangaysByCity") ?>', {
              city: city
            })
            .done(function(html) {
              $barangay.html(html);
              if (currentBrgy) {
                $barangay.val(currentBrgy);
                currentBrgy = '';
              }
            })
            .fail(function() {
              UI.error('Could not load the barangay list. Please try again.');
            });
        } else {
          $barangay.html('<option value="">Select Barangay</option>');
        }
      });

      if (currentProvince && !$province.val()) {
        $province.val(currentProvince);
      }

      if ($city.children('option').length <= 1 && $province.val()) {
        $province.trigger('change');
      } else if ($barangay.children('option').length <= 1 && currentCity) {
        $city.trigger('change');
      } else {
        if (currentCity && !$city.val()) {
          $city.val(currentCity);
        }
        if (currentBrgy && !$barangay.val()) {
          $barangay.val(currentBrgy);
        }
      }
    });
  </script>

  <!-- StudentNumber availability checker -->
  <script>
  (function($) {
    var $form = $('form.parsley-examples');
    var checkUrl = $form.data('check-availability-url') || '';
    var excludeSn = $form.data('exclude-student-number') || '';
    var $snInput = $('#StudentNumber');
    var $snStatus = $('#student-number-status');
    if (!$snInput.length || !$snStatus.length || !checkUrl) return;

    function setLabel(state, text) {
      $snStatus.removeClass('is-ok is-bad is-muted');
      if (state) $snStatus.addClass(state);
      $snStatus.text(text || '');
    }

    function debounce(fn, wait) {
      var t = null;
      return function() {
        var args = arguments, ctx = this;
        clearTimeout(t);
        t = setTimeout(function() { fn.apply(ctx, args); }, wait);
      };
    }

    var runCheck = debounce(function() {
      var v = ($snInput.val() || '').toUpperCase();
      $snInput.val(v);
      if (!v) { $snInput[0].setCustomValidity(''); setLabel('', ''); return; }
      $.post(checkUrl, { field: 'studentnumber', value: v, exclude: excludeSn })
        .done(function(payload) {
          var data = (typeof payload === 'object') ? payload
            : (function() { try { return JSON.parse(payload); } catch (e) { return null; } })();
          if (!data || !data.ok) { setLabel('is-muted', ''); $snInput[0].setCustomValidity(''); return; }
          if (data.exists) {
            setLabel('is-bad', data.message || 'Already exists.');
            $snInput[0].setCustomValidity(data.message || 'Already exists.');
          } else {
            setLabel('is-ok', data.message || 'Available.');
            $snInput[0].setCustomValidity('');
          }
        })
        .fail(function() { setLabel('is-muted', ''); $snInput[0].setCustomValidity(''); });
    }, 300);

    $snInput.on('input blur', runCheck);
  })(jQuery);
  </script>

  <!-- Sticky progress indicator — tracks which section is in view -->
  <script>
  (function() {
    var sections = [
      { id: 'section-personal',  step: 1 },
      { id: 'section-academic',  step: 2 },
      { id: 'section-address',   step: 3 }
    ];
    var stepEls = document.querySelectorAll('.reg-progress-step');
    var lineEls = document.querySelectorAll('.reg-progress-line');
    if (!stepEls.length) return;

    function updateProgress() {
      var mark = window.innerHeight * 0.35;
      var currentStep = 1;
      for (var i = 0; i < sections.length; i++) {
        var el = document.getElementById(sections[i].id);
        if (el && el.getBoundingClientRect().top <= mark) {
          currentStep = sections[i].step;
        }
      }
      stepEls.forEach(function(el) {
        var s = parseInt(el.getAttribute('data-step'), 10);
        el.classList.remove('active', 'done');
        if (s < currentStep) { el.classList.add('done'); }
        else if (s === currentStep) { el.classList.add('active'); }
      });
      lineEls.forEach(function(el, idx) {
        if (idx < currentStep - 1) { el.classList.add('filled'); }
        else { el.classList.remove('filled'); }
      });
    }

    // Capture-phase listener catches scrolls on window AND inner scroll containers.
    document.addEventListener('scroll', updateProgress, { capture: true, passive: true });
    window.addEventListener('resize', updateProgress, { passive: true });
    updateProgress();
  })();
  </script>
</body>

</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Attendance Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta content="Responsive bootstrap 4 admin template" name="description" />
    <meta content="Coderthemes" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">

    <!-- Plugins css-->
    <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
    <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/app.css?v=20260922" rel="stylesheet" type="text/css" id="app-stylesheet" />
    <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/update_profile.css?v=3'); ?>">

    <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?= base_url('assets/fonts/bootstrap-icons/bootstrap-icons.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom-sidebar-icons.css?v=3'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/masterlist-responsive.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=12'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/nx-shell.css?v=14'); ?>">

    <?php include(APPPATH . 'views/includes/ui_kit.php'); ?>
    <meta name="theme-color" content="#1a2942">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
    <script src="<?= base_url('assets/js/mobile-shell-early.js?v=6'); ?>"></script>

    <script>
        function calculateAge(dateInputId, resultInputId) {
            const v = document.getElementById(dateInputId).value;
            if (!v) { document.getElementById(resultInputId).value=''; return; }
            const b = new Date(v), n = new Date();
            let age = n.getFullYear()-b.getFullYear();
            const m = n.getMonth()-b.getMonth();
            if (m<0 || (m===0 && n.getDate()<b.getDate())) age--;
            document.getElementById(resultInputId).value = isFinite(age)?age:'';
        }
    </script>
    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>
<body>
<?php
$profileData = $data ?? null;
if (is_array($profileData)) {
    $profileData = $profileData[0] ?? null;
}
if (is_array($profileData)) {
    $profileData = (object)$profileData;
}
if (!is_object($profileData)) {
    $profileData = (object)[];
}
$data = $profileData;

$readOnly = !empty($readOnly);
$isAuditorProfile = ((string)$this->session->userdata('level') === 'Auditor');
$provinces = (isset($provinces) && is_array($provinces)) ? $provinces : [];
$cities    = (isset($cities) && is_array($cities)) ? $cities : [];
$barangays = (isset($barangays) && is_array($barangays)) ? $barangays : [];
if (empty($barangays) && isset($brgy) && is_array($brgy)) {
    $barangays = $brgy;
}

$pickField = function ($keys) use ($data) {
    foreach ($keys as $key) {
        if (isset($data->$key) && trim((string)$data->$key) !== '') {
            return trim((string)$data->$key);
        }
    }
    return '';
};

$provinceVal = $pickField(['Province', 'province', 'provincePresent']);
$cityVal     = $pickField(['City', 'city', 'CityPresent', 'cityPresent']);
$brgyVal     = $pickField(['Brgy', 'brgy', 'Barangay', 'barangay', 'BrgyPresent', 'brgyPresent']);
$sitioVal    = $pickField(['Sitio', 'sitio', 'SitioPresent', 'sitioPresent']);

// Safe field accessors — prevents PHP warnings when a property is missing
// (e.g. when the record comes from studentsignup instead of studeprofile)
$snVal         = $pickField(['StudentNumber', 'studentnumber']);
$firstNameVal  = $pickField(['FirstName', 'firstname']);
$middleNameVal = $pickField(['MiddleName', 'middlename']);
$lastNameVal   = $pickField(['LastName', 'lastname']);
$nameExtnVal   = $pickField(['nameExtn', 'NameExtn']);
$sexVal        = $pickField(['Sex', 'sex']);
$civilVal      = $pickField(['CivilStatus', 'civilstatus']);
$contactVal    = $pickField(['contactNo', 'ContactNo']);
$birthDateVal  = $pickField(['birthDate', 'BirthDate']);
$ageVal        = $pickField(['Age', 'age']);
$emailVal      = $pickField(['email', 'Email']);
$courseVal     = $pickField(['Course1', 'course', 'Course']);
$majorVal      = $pickField(['Major1', 'major', 'Major']);
$yearLvlVal    = $pickField(['yearLevel', 'YearLevel']);
$sectionVal    = $pickField(['section', 'Section']);
$courseOptions = (isset($courseOptions) && is_array($courseOptions)) ? $courseOptions : [];
if ($emailVal === '') {
    // Signup/profile rows predate the email column — fall back to the
    // login account's email so the field shows whichever value exists.
    $emailVal = trim((string)($accountEmail ?? ''));
}

// Banner photo: real upload when one exists, initials otherwise — the
// goal is simply that staff can see whose record they are changing.
$photoUrl = trim((string)($photoUrl ?? ''));
$avatarInitials = '';
foreach ([$firstNameVal, $lastNameVal] as $part) {
    if ($part !== '') {
        $avatarInitials .= strtoupper(substr($part, 0, 1));
    }
    if (strlen($avatarInitials) >= 2) break;
}
if ($avatarInitials === '') {
    $avatarInitials = '?';
}
?>
<div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
        <div class="content">

            <?php
                $flashSuccess = $this->session->flashdata('success');
                $flashDanger  = $this->session->flashdata('danger');
                $flashMessage = $this->session->flashdata('message');
            ?>

            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($flashDanger): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashDanger, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($flashMessage): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <!-- Title source for the top navbar (visually hidden; read by mobile-shell.js) -->
                <div class="page-title-box">
                    <h4 class="up-page-title"><?= $readOnly ? 'View Profile' : 'Update Profile'; ?></h4>
                </div>

                <?php
                $bannerName = trim($firstNameVal . ' ' . $lastNameVal);
                if ($bannerName === '') {
                    $bannerName = $snVal;
                }
                ?>

                <div class="reg-card">
                    <div class="card-banner">
                        <div class="ring ring-1"></div>
                        <div class="ring ring-2"></div>

                        <div class="banner-text">
                            <div class="banner-avatar" title="Student photo">
                                <?php if ($photoUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Student photo">
                                <?php else: ?>
                                    <span class="avatar-initials"><?= htmlspecialchars($avatarInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="banner-eyebrow">Student Record &middot; <?= htmlspecialchars($snVal ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="banner-title"><?= $readOnly ? 'View Profile' : 'Update Profile'; ?></div>
                            <div class="banner-sub">
                                <span class="banner-student"><?= htmlspecialchars($bannerName, ENT_QUOTES, 'UTF-8'); ?></span><br>
								<?= $readOnly ? 'Viewing student details.' : 'Update student personal and academic information.'; ?>
                            </div>
                        </div>

                        <div class="banner-icon">
                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="46" cy="34" r="15" stroke="rgba(255,255,255,0.3)" stroke-width="2.5" />
                                <circle cx="46" cy="34" r="9" fill="rgba(255,255,255,0.10)" />
                                <path d="M18 84c3.5-15 13.5-23 28-23 8 0 15 2.4 20.4 7" stroke="rgba(255,255,255,0.3)" stroke-width="2.5" stroke-linecap="round" />
                                <path d="M67 90l8.5-8.5 9 9L76 99h-9v-9z" fill="rgba(255,255,255,0.16)" stroke="rgba(255,255,255,0.4)" stroke-width="2" stroke-linejoin="round" />
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
						<?php if ($readOnly && !$isAuditorProfile): ?>
                            <div class="profile-readonly-note">
                                Viewing student details only. Editing is disabled for your account.
                            </div>
                        <?php endif; ?>

                        <form class="parsley-examples" method="post" enctype="multipart/form-data"
                            data-check-availability-url="<?= htmlspecialchars(site_url('Page/checkSignupAvailability'), ENT_QUOTES, 'UTF-8'); ?>"
                            data-exclude-student-number="<?= htmlspecialchars($excludeSn ?? $snVal, ENT_QUOTES, 'UTF-8'); ?>"
                            data-exclude-email="<?= htmlspecialchars($excludeEmail ?? $emailVal, ENT_QUOTES, 'UTF-8'); ?>">
                            <fieldset class="profile-fieldset" <?= $readOnly ? 'disabled' : ''; ?>>

                                <div class="section-head">
                                    <div class="section-dot"></div>
                                    <div class="section-label">Personal Data</div>
                                    <div class="section-line"></div>
                                </div>

                                <div class="row-fields cols-4">
                                    <div class="field-group">
                                        <input type="hidden" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>" name="oldStudentNo" required>
                                        <label class="field-label" for="StudentNumber">Student No. <span class="req">*</span></label>
                                        <input type="text" class="field" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>" name="StudentNumber" id="StudentNumber"
                                            inputmode="numeric" autocomplete="off" maxlength="9" pattern="[0-9]+(-[0-9]+)?"
                                            title="Numbers only — school ID format e.g. 2026-0251"
                                            <?= $readOnly ? 'readonly' : ''; ?> required>
                                        <?php if (!$readOnly): ?>
                                            <span class="availability-msg" id="student-number-status" aria-live="polite"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="FirstName">First Name <span class="req">*</span></label>
                                        <input type="text" class="field" id="FirstName" name="FirstName" value="<?= htmlspecialchars($firstNameVal, ENT_QUOTES, 'UTF-8'); ?>" style="text-transform:uppercase;" required>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="MiddleName">Middle Name</label>
                                        <input type="text" class="field" id="MiddleName" name="MiddleName" value="<?= htmlspecialchars($middleNameVal, ENT_QUOTES, 'UTF-8'); ?>" style="text-transform:uppercase;">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="LastName">Last Name <span class="req">*</span></label>
                                        <input type="text" class="field" id="LastName" name="LastName" value="<?= htmlspecialchars($lastNameVal, ENT_QUOTES, 'UTF-8'); ?>" style="text-transform:uppercase;" required>
                                    </div>
                                </div>

                                <div class="row-fields cols-4">
                                    <div class="field-group">
                                        <label class="field-label" for="nameExtn">Name Extn</label>
                                        <input type="text" class="field" id="nameExtn" name="nameExtn" value="<?= htmlspecialchars($nameExtnVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Jr., Sr." style="text-transform:uppercase;">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="Sex">Sex <span class="req">*</span></label>
                                        <select name="Sex" id="Sex" class="field" required>
                                            <option value=""></option>
                                            <option value="Female" <?= ($sexVal == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Male"   <?= ($sexVal == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="CivilStatus">Civil Status <span class="req">*</span></label>
                                        <select name="CivilStatus" id="CivilStatus" class="field" required>
                                            <option value=""></option>
                                            <option value="Single"  <?= ($civilVal == 'Single') ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?= ($civilVal == 'Married') ? 'selected' : ''; ?>>Married</option>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="contactNo">Mobile No.</label>
                                        <input type="text" class="field" id="contactNo" name="contactNo" value="<?= htmlspecialchars($contactVal, ENT_QUOTES, 'UTF-8'); ?>"
                                            inputmode="numeric" autocomplete="off" maxlength="11" placeholder="09XXXXXXXXX"
                                            title="Numbers only — 11-digit mobile number starting with 09">
                                        <?php if (!$readOnly): ?>
                                            <span class="availability-msg" id="contact-status" aria-live="polite"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row-fields cols-3">
                                    <div class="field-group">
                                        <label class="field-label" for="bday">Birth Date <span class="req">*</span></label>
                                        <input type="date" name="birthDate" id="bday" class="field"
                                            onchange="calculateAge('bday','resultBday')" required value="<?= htmlspecialchars($birthDateVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="resultBday">Age <span class="req">*</span></label>
                                        <input type="text" name="Age" id="resultBday" class="field" readonly required value="<?= htmlspecialchars($ageVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="email">Email</label>
                                        <input type="email" class="field" id="email" name="email" value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" <?= $readOnly ? 'readonly' : ''; ?>>
                                        <?php if (!$readOnly): ?>
                                            <span class="availability-msg" id="email-status" aria-live="polite"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="section-head">
                                    <div class="section-dot"></div>
                                    <div class="section-label">Address</div>
                                    <div class="section-line"></div>
                                </div>

                                <div class="row-fields cols-4">
                                    <div class="field-group">
                                        <label class="field-label" for="province">Province <span class="req">*</span></label>
                                        <select id="province" name="Province" class="field" required>
                                            <option value="">Select Province</option>
                                            <?php foreach ($provinces as $province): ?>
                                                <option value="<?= htmlspecialchars($province->Province, ENT_QUOTES, 'UTF-8'); ?>" <?= ($province->Province == $provinceVal) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($province->Province, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="city">City/Municipality <span class="req">*</span></label>
                                        <select id="city" name="City" class="field" required>
                                            <option value="">Select City/Municipality</option>
                                            <?php foreach ($cities as $city): ?>
                                                <option value="<?= htmlspecialchars($city->City, ENT_QUOTES, 'UTF-8'); ?>" <?= ($city->City == $cityVal) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($city->City, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="barangay">Barangay <span class="req">*</span></label>
                                        <select id="barangay" name="Brgy" class="field" required>
                                            <option value="">Select Barangay</option>
                                            <?php foreach ($barangays as $barangay): ?>
                                                <option value="<?= htmlspecialchars($barangay->Brgy, ENT_QUOTES, 'UTF-8'); ?>" <?= ($barangay->Brgy == $brgyVal) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($barangay->Brgy, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="sitio">Sitio</label>
                                        <input type="text" id="sitio" class="field" name="Sitio" placeholder="Sitio" value="<?= htmlspecialchars($sitioVal, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>

                                <div class="section-head">
                                    <div class="section-dot"></div>
                                    <div class="section-label">Academic</div>
                                    <div class="section-line"></div>
                                </div>

                                <div class="row-fields cols-4">
                                    <div class="field-group">
                                        <label class="field-label" for="Course1">Course</label>
                                        <select id="Course1" name="Course1" class="field">
                                            <option value="">Select Course</option>
                                            <?php
                                            $seenCourse = false;
                                            foreach ($courseOptions as $cRow):
                                                $cv = trim((string)($cRow->CourseDescription ?? ''));
                                                if ($cv === '') continue;
                                                if ($cv === $courseVal) $seenCourse = true;
                                            ?>
                                                <option value="<?= htmlspecialchars($cv, ENT_QUOTES, 'UTF-8'); ?>" <?= $cv === $courseVal ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($cv, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($courseVal !== '' && !$seenCourse): ?>
                                                <option value="<?= htmlspecialchars($courseVal, ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($courseVal, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="Major1">Major</label>
                                        <select id="Major1" name="Major1" class="field"
                                            data-majors-url="<?= htmlspecialchars(site_url('Registration/getMajorsByCourse'), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-current="<?= htmlspecialchars($majorVal, ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="">Select Major</option>
                                            <?php if ($majorVal !== ''): ?>
                                                <option value="<?= htmlspecialchars($majorVal, ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($majorVal, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="yearLevel">Year Level</label>
                                        <select id="yearLevel" name="yearLevel" class="field">
                                            <option value="">Select Year Level</option>
                                            <?php foreach (['1st' => '1st Year', '2nd' => '2nd Year', '3rd' => '3rd Year', '4th' => '4th Year'] as $yv => $yl): ?>
                                                <option value="<?= $yv; ?>" <?= $yearLvlVal === $yv ? 'selected' : ''; ?>><?= $yl; ?></option>
                                            <?php endforeach; ?>
                                            <?php if ($yearLvlVal !== '' && !in_array($yearLvlVal, ['1st', '2nd', '3rd', '4th'], true)): ?>
                                                <option value="<?= htmlspecialchars($yearLvlVal, ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($yearLvlVal, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label" for="section">Section</label>
                                        <select id="section" name="section" class="field"
                                            data-sections-url="<?= htmlspecialchars(site_url('Registration/getSectionsByCourseYear'), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-current="<?= htmlspecialchars($sectionVal, ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="">Select Section</option>
                                            <?php if ($sectionVal !== ''): ?>
                                                <option value="<?= htmlspecialchars($sectionVal, ENT_QUOTES, 'UTF-8'); ?>" selected><?= htmlspecialchars($sectionVal, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" name="StudentNumber_original" value="<?= htmlspecialchars($snVal, ENT_QUOTES, 'UTF-8'); ?>">
                            </fieldset>

                            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-top:8px;">
                                <?php if (!$readOnly): ?>
                                <button type="submit" name="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    <span>Update Profile</span>
                                </button>
                                <?php endif; ?>
                                <a href="<?= base_url('Page/profileList'); ?>" class="btn-back">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Back to List</span>
                                </a>
                            </div>

                            <div class="form-footer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Changes to this record are stored securely and recorded in the audit log.
                            </div>
                        </form>

                        <?php
                        $history = is_array($history ?? null) ? $history : [];
                        $histDiffs = function ($row) {
                            $old = json_decode((string)($row['old_values'] ?? ''), true);
                            $new = json_decode((string)($row['new_values'] ?? ''), true);
                            if (!is_array($new)) return [];
                            // Only diff keys the update actually wrote —
                            // columns absent from new_values were left
                            // untouched, not set to null.
                            $diffs = [];
                            // Case-insensitive key match: update payloads
                            // use 'age'/'province' while the row snapshot
                            // has 'Age'/'Province' — MySQL columns are
                            // case-insensitive, so they're the same field.
                            $oldNorm = [];
                            if (is_array($old)) {
                                foreach ($old as $k => $v) $oldNorm[strtolower((string)$k)] = [$k, $v];
                            }
                            foreach (array_keys($new) as $f) {
                                $lk = strtolower((string)$f);
                                $hasOld = array_key_exists($lk, $oldNorm);
                                $o = $hasOld ? $oldNorm[$lk][1] : null;
                                $n = $new[$f];
                                if ($hasOld && (string)$o === (string)$n) continue;
                                $diffs[] = ['field' => $hasOld ? $oldNorm[$lk][0] : $f, 'old' => $o, 'new' => $n];
                            }
                            return $diffs;
                        };
                        ?>
                        <details class="history-block">
                            <summary>
                                <span class="section-dot"></span>
                                Change History
                                <span class="history-count"><?= count($history); ?> entr<?= count($history) === 1 ? 'y' : 'ies'; ?></span>
                            </summary>
                            <?php if (empty($history)): ?>
                                <div class="history-empty">No recorded changes for this student yet.</div>
                            <?php else: ?>
                                <div class="history-list">
                                <?php foreach ($history as $row):
                                    $ok  = (int)($row['succeeded'] ?? 1) === 1;
                                    $who = trim((string)($row['full_name'] ?? '')) !== '' ? $row['full_name'] : ($row['username'] ?? '');
                                    $ts  = strtotime((string)($row['event_time'] ?? ''));
                                    $diffs = $histDiffs($row);
                                ?>
                                    <div class="history-item">
                                        <div class="history-head">
                                            <span class="history-time"><?= $ts ? date('M j, Y g:i A', $ts) : '—'; ?></span>
                                            <span class="history-action"><?= htmlspecialchars((string)($row['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if (!$ok): ?><span class="history-blocked">blocked</span><?php endif; ?>
                                            <?php if (!empty($row['module'])): ?><span class="history-table"><?= htmlspecialchars((string)$row['module'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <span class="history-actor"><?= htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8'); ?><?= !empty($row['actor_level']) ? ' (' . htmlspecialchars((string)$row['actor_level'], ENT_QUOTES, 'UTF-8') . ')' : ''; ?></span>
                                        </div>
                                        <?php if (!empty($row['description'])): ?>
                                            <div class="history-desc"><?= htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                        <?php foreach ($diffs as $d): ?>
                                            <div class="history-diff">
                                                <code><?= htmlspecialchars((string)$d['field'], ENT_QUOTES, 'UTF-8'); ?></code>:
                                                <span class="diff-old"><?= htmlspecialchars(is_scalar($d['old']) ? (string)$d['old'] : json_encode($d['old']), ENT_QUOTES, 'UTF-8'); ?></span>
                                                &rarr;
                                                <span class="diff-new"><?= htmlspecialchars(is_scalar($d['new']) ? (string)$d['new'] : json_encode($d['new']), ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </details>
                    </div>
                </div>

                <div style="height:40px;"></div>
            </div><!-- /.container-fluid -->

        </div><!-- /.content -->

        <?php include('includes/footer.php'); ?>
    </div><!-- /.content-page -->

    <?php include('includes/themecustomizer.php'); ?>
</div><!-- /#wrapper -->

<script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
<script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url(); ?>assets/js/app.min.js"></script>

<!-- Province → City → Barangay chaining -->
<script>
$(function () {
    var isReadOnly = <?= $readOnly ? 'true' : 'false'; ?>;
    if (isReadOnly) {
        $('#province, #city, #barangay').prop('disabled', true);
        return;
    }

    function notifyError(message) {
        if (window.UI && typeof window.UI.fire === 'function') {
            window.UI.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#348cd4'
            });
        } else {
            window.alert(message);
        }
    }

   const savedProvince = <?= json_encode($provinceVal) ?>;
const savedCity     = <?= json_encode($cityVal) ?>;
const savedBrgy     = <?= json_encode($brgyVal) ?>;

function enable(el, on = true) { $(el).prop('disabled', !on); }

function setSelectValue($el, val, label) {
    if (!val) return;
    if ($el.find('option').filter((_, o) => $(o).val() == val).length === 0) {
        $el.append($('<option/>', { value: val, text: label || val }));
    }
    $el.val(val).trigger('change');
}

function fillOptions($el, items, valueKey, textKey, placeholder) {
    $el.empty();
    if (placeholder) $el.append($('<option/>', { value: '', text: placeholder }));
    (items || []).forEach(function (it) {
        const v = it[valueKey] ?? '';
        const t = it[textKey] ?? v;
        if (v) $el.append($('<option/>', { value: v, text: t }));
    });
    $el.trigger('change');
}

function loadProvinces() {
    $.ajax({
        url: '<?= site_url('Page/get_provinces'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            const $prov = $('#province');
            fillOptions($prov, data, 'Province', 'Province', 'Select Province');

            if (savedProvince) {
                setSelectValue($prov, savedProvince);
                enable('#city', true);
                loadCities(savedProvince, function () {
                    if (savedCity) {
                        setSelectValue($('#city'), savedCity);
                        enable('#barangay', true);
                        loadBarangays(savedCity, function () {
                            if (savedBrgy) setSelectValue($('#barangay'), savedBrgy);
                        });
                    }
                });
            } else {
                enable('#city', false);
                enable('#barangay', false);
            }
        },
        error: function (_x, _s, err) { notifyError("Error loading provinces: " + err); }
    });
}

function loadCities(province, done) {
    $.ajax({
        url: '<?= site_url('Page/get_cities'); ?>',
        type: 'POST',
        dataType: 'json',
        data: { province: province },
        success: function (data) {
            const $city = $('#city');
            fillOptions($city, data, 'City', 'City', 'Select City/Municipality');
            if (typeof done === 'function') done();
        },
        error: function (_x, _s, err) { notifyError("Error loading cities: " + err); }
    });
}

function loadBarangays(city, done) {
    $.ajax({
        url: '<?= site_url('Page/get_barangays'); ?>',
        type: 'POST',
        dataType: 'json',
        data: { city: city },
        success: function (data) {
            const $brgy = $('#barangay');
            fillOptions($brgy, data, 'Brgy', 'Brgy', 'Select Barangay');
            if (typeof done === 'function') done();
        },
        error: function (_x, _s, err) { notifyError("Error loading barangays: " + err); }
    });
}

$('#province').on('change', function () {
    const prov = $(this).val();
    fillOptions($('#city'), [], 'City', 'City', 'Select City/Municipality');
    fillOptions($('#barangay'), [], 'Brgy', 'Brgy', 'Select Barangay');
    enable('#barangay', false);

    if (prov) { enable('#city', true); loadCities(prov); }
    else { enable('#city', false); }
});

$('#city').on('change', function () {
    const city = $(this).val();
    fillOptions($('#barangay'), [], 'Brgy', 'Brgy', 'Select Barangay');
    if (city) { enable('#barangay', true); loadBarangays(city); }
    else { enable('#barangay', false); }
});


    // Ensure disabled selects submit values
    $('form').on('submit', function () {
        ['#city','#barangay'].forEach(function (sel) {
            const $el = $(sel);
            if ($el.is(':disabled')) {
                if (!$el.val()) {
                    const selected = $el.find('option:selected').val() || $el.find('option:first').val();
                    if (selected) $el.val(selected);
                }
                $el.prop('disabled', false);
            }
        });
    });

    loadProvinces();

    // ── StudentNumber availability checker (mirrors Registration flow) ──
    var $form = $('form.parsley-examples');
    var checkUrl = $form.data('check-availability-url') || '';
    var excludeSn = $form.data('exclude-student-number') || '';
    var excludeEmail = $form.data('exclude-email') || '';

    function updateAvailabilityLabel($label, state, text) {
        if (!$label || !$label.length) return;
        $label.removeClass('is-ok is-bad is-muted');
        if (state) $label.addClass(state);
        $label.text(text || '');
    }

    function debounce(fn, wait) {
        var t = null;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    // Shared guard: block a non-digit key the moment it is pressed (also
    // fires when the field is already full — keypress happens before the
    // maxlength refusal, unlike the input event).
    var NUMBERS_ONLY_MSG = 'This field accepts numbers only.';
    function bindDigitGuard($input, onRejected) {
        $input.on('keypress', function (e) {
            if (e.key && e.key.length === 1 && !/\d/.test(e.key)
                && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                onRejected();
            }
        });
    }

    // Registration-style digits-only input: strip anything that is not a
    // digit, cap at 8 digits, auto-insert the dash → YYYY-NNNN.
    var formatStudentNumber = function (value) {
        var digits = (value || '').replace(/\D/g, '').slice(0, 8);
        return digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
    };

    var $snInput = $('#StudentNumber');
    var $snStatus = $('#student-number-status');
    var originalSn = (excludeSn || '').toUpperCase();
    if ($snInput.length && $snStatus.length && checkUrl) {
        var runCheck = debounce(function () {
            var v = ($snInput.val() || '').toUpperCase();
            if (!v) {
                $snInput.get(0).setCustomValidity('');
                updateAvailabilityLabel($snStatus, '', '');
                return;
            }

            // Unchanged value passes as-is — legacy IDs (0011, 0358-2024,
            // 20232794...) predate the strict format and must stay editable.
            // A *changed* number must use the school format YYYY-NNNN.
            if (v !== originalSn && !/^\d{4}-\d{4}$/.test(v)) {
                var msg = 'New Student No. must use the format YYYY-NNNN (e.g. 2026-0251).';
                $snInput.get(0).setCustomValidity(msg);
                updateAvailabilityLabel($snStatus, 'is-muted', msg);
                return;
            }

            $.post(checkUrl, { field: 'studentnumber', value: v, exclude: excludeSn })
                .done(function (payload) {
                    var data = (typeof payload === 'object') ? payload
                        : (function () { try { return JSON.parse(payload); } catch (e) { return null; } })();
                    if (!data || !data.ok) {
                        updateAvailabilityLabel($snStatus, 'is-muted', '');
                        $snInput.get(0).setCustomValidity('');
                        return;
                    }
                    if (data.exists) {
                        updateAvailabilityLabel($snStatus, 'is-bad', data.message || 'Already exists.');
                        $snInput.get(0).setCustomValidity(data.message || 'Already exists.');
                    } else {
                        updateAvailabilityLabel($snStatus, 'is-ok', data.message || 'Available.');
                        $snInput.get(0).setCustomValidity('');
                    }
                })
                .fail(function () {
                    updateAvailabilityLabel($snStatus, 'is-muted', '');
                    $snInput.get(0).setCustomValidity('');
                });
        }, 300);

        var snNoticeTimer = null;
        var showSnNotice = function () {
            updateAvailabilityLabel($snStatus, 'is-bad', NUMBERS_ONLY_MSG);
            clearTimeout(snNoticeTimer);
            snNoticeTimer = setTimeout(runCheck, 2500);
        };
        bindDigitGuard($snInput, showSnNotice);
        $snInput.on('input', function () {
            var raw = $snInput.val() || '';
            var formatted = formatStudentNumber(raw);
            if (raw !== formatted) {
                $snInput.val(formatted);
                if (/\D/.test(raw)) {
                    // Pasted text containing letters — same notice as the
                    // keypress guard (paste bypasses keypress).
                    showSnNotice();
                }
            }
            runCheck();
        });
        $snInput.on('blur', runCheck);
        if (($snInput.val() || '').trim() !== '') {
            // Don't show "available" for the unchanged value on load — just clear.
            updateAvailabilityLabel($snStatus, '', '');
        }
    }

    // ── Email availability checker (same endpoint, field=email) ──
    var $emailInput = $('#email');
    var $emailStatus = $('#email-status');
    if ($emailInput.length && $emailStatus.length && checkUrl) {
        var runEmailCheck = debounce(function () {
            var v = ($emailInput.val() || '').trim();
            var inputEl = $emailInput.get(0);
            if (!v) {
                inputEl.setCustomValidity('');
                updateAvailabilityLabel($emailStatus, '', '');
                return;
            }
            $.post(checkUrl, { field: 'email', value: v, exclude: excludeEmail })
                .done(function (payload) {
                    var data = (typeof payload === 'object') ? payload
                        : (function () { try { return JSON.parse(payload); } catch (e) { return null; } })();
                    if (!data || !data.ok) {
                        updateAvailabilityLabel($emailStatus, 'is-muted', '');
                        inputEl.setCustomValidity('');
                        return;
                    }
                    if (data.format === false) {
                        updateAvailabilityLabel($emailStatus, 'is-bad', data.message || 'That email looks misspelled.');
                        inputEl.setCustomValidity(data.message || 'That email looks misspelled.');
                    } else if (data.exists) {
                        updateAvailabilityLabel($emailStatus, 'is-bad', data.message || 'Email already exists.');
                        inputEl.setCustomValidity(data.message || 'Email already exists.');
                    } else {
                        updateAvailabilityLabel($emailStatus, 'is-ok', data.message || 'Email is available.');
                        inputEl.setCustomValidity('');
                    }
                })
                .fail(function () {
                    updateAvailabilityLabel($emailStatus, 'is-muted', '');
                    inputEl.setCustomValidity('');
                });
        }, 300);

        $emailInput.on('input blur', runEmailCheck);
    }

    // ── Mobile No.: numbers only (mirrors myProfile's 09 + 11-digit rule) ──
    var $contactInput = $('#contactNo');
    var $contactStatus = $('#contact-status');
    var originalContact = $contactInput.length ? ($contactInput.val() || '').trim() : '';
    var contactNoticeTimer = null;
    if ($contactInput.length) {
        var showContactNotice = function (text, state) {
            updateAvailabilityLabel($contactStatus, state || 'is-muted', text);
            clearTimeout(contactNoticeTimer);
            if (text) {
                // After the notice fades, re-validate so a real format
                // error (if any) is what remains shown.
                contactNoticeTimer = setTimeout(validateContact, 2500);
            }
        };

        var validateContact = function () {
            if (!$contactInput.length) return;
            var inputEl = $contactInput.get(0);
            var v = ($contactInput.val() || '').trim();
            if (v === '') {
                inputEl.setCustomValidity('');
                updateAvailabilityLabel($contactStatus, '', '');
                return;
            }
            // Unchanged legacy values pass; a changed number must be an
            // 11-digit PH mobile starting with 09.
            if (v !== originalContact && !/^09\d{9}$/.test(v)) {
                var msg = 'Mobile number must be 11 digits starting with 09.';
                inputEl.setCustomValidity(msg);
                updateAvailabilityLabel($contactStatus, 'is-bad', msg);
                return;
            }
            inputEl.setCustomValidity('');
            updateAvailabilityLabel($contactStatus, '', '');
        };

        bindDigitGuard($contactInput, function () {
            showContactNotice(NUMBERS_ONLY_MSG, 'is-bad');
        });
        $contactInput.on('input', function () {
            var raw = $contactInput.val() || '';
            var digits = raw.replace(/\D/g, '').slice(0, 11);
            if (raw !== digits) {
                $contactInput.val(digits);
                if (raw.replace(/\D/g, '') !== raw) {
                    // Something other than a digit was typed/pasted — tell
                    // the user why it disappeared instead of failing quietly.
                    showContactNotice('This field accepts numbers only.', 'is-bad');
                }
            }
            validateContact();
        });
        $contactInput.on('blur', validateContact);
    }

    // ── Unsaved-changes guard ──
    // Any user edit marks the form dirty; navigating away (sidebar link,
    // back button, tab close) then triggers the browser's leave-site
    // prompt. Submitting clears the flag so a real save doesn't warn.
    // (Browsers require this generic prompt — custom modals can't
    // intercept tab-close/back, so beforeunload is the reliable hook.)
    var formDirty = false;
    $('.profile-fieldset').on('input change', 'input, select, textarea', function (e) {
        // Ignore programmatic events — the province/city/barangay loaders
        // and the input formatters call .trigger('change'), which has no
        // originalEvent; only real user interaction should count.
        if (!e.originalEvent) return;
        formDirty = true;
    });
    $('form.parsley-examples').on('submit', function () {
        formDirty = false;
    });
    window.addEventListener('beforeunload', function (e) {
        if (!formDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ── Academic cascades — same AJAX endpoints registration uses ──
    // Course change refills Major; Course+Year Level refill Section.
    // The student's stored value is always kept as an option if it isn't in
    // the returned list, so a save can't silently blank existing data.
    function refillSelect($sel, html, keepValue) {
        $sel.html(html || '<option value="">Select</option>');
        if (keepValue) {
            var found = false;
            $sel.find('option').each(function () {
                if ($(this).attr('value') === keepValue) { found = true; return false; }
            });
            if (!found) {
                $sel.append($('<option>', { value: keepValue, text: keepValue }));
            }
            $sel.val(keepValue);
        }
    }

    var $courseSel  = $('#Course1');
    var $majorSel   = $('#Major1');
    var $yearSel    = $('#yearLevel');
    var $sectionSel = $('#section');

    function reloadMajors(keep) {
        if (!$courseSel.length || !$majorSel.length) return;
        var url  = $majorSel.data('majors-url');
        var course = $courseSel.val();
        var current = keep ? ($majorSel.data('current') || '') : '';
        if (!url || !course) {
            refillSelect($majorSel, '<option value="">Select Major</option>', current);
            return;
        }
        $.post(url, { course: course }).done(function (html) {
            refillSelect($majorSel, html, current);
        });
    }

    function reloadSections(keep) {
        if (!$sectionSel.length) return;
        var url    = $sectionSel.data('sections-url');
        var course = $courseSel.val();
        var year   = $yearSel.val();
        var current = keep ? ($sectionSel.data('current') || '') : '';
        if (!url || !course || !year) {
            refillSelect($sectionSel, '<option value="">Select Section</option>', current);
            return;
        }
        $.post(url, { course: course, yearLevel: year }).done(function (html) {
            refillSelect($sectionSel, html, current);
        });
    }

    if ($courseSel.length) {
        // Initial fill keeps the stored value; later course changes reset
        // major/section so a stale one can't ride along silently.
        reloadMajors(true);
        reloadSections(true);
        $courseSel.on('change', function (e) {
            if (!e.originalEvent) return;
            $majorSel.data('current', '');
            $sectionSel.data('current', '');
            reloadMajors(false);
            reloadSections(false);
        });
        $yearSel.on('change', function (e) {
            if (!e.originalEvent) return;
            reloadSections(false);
        });
    }
});
</script>
<script>
// Flash messages are shown by the shared toast bridge (includes/ui_kit.php).
</script>
</body>
</html>

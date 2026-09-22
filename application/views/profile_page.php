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
          $s = isset($data[0]) ? $data[0] : (object)[];

          $isStudent = ($this->session->userdata('level') === 'Student');
          $avatar = $isStudent
            ? ($this->session->userdata('avatar') ?: 'default.png')
            : ((!empty($data1) && !empty($data1[0]->avatar)) ? $data1[0]->avatar : 'default.png');

          $fullName = trim(implode(' ', array_filter([
            $s->FirstName ?? '',
            $s->MiddleName ?? '',
            $s->LastName ?? '',
            $s->nameExtn ?? ''
          ])));
          if ($fullName === '') {
            $fullName = 'Student';
          }

          $studentNumber = (string)($s->StudentNumber ?? '');
          $addr = trim(implode(', ', array_filter([
            $s->sitio ?? '',
            $s->brgy ?? '',
            $s->city ?? '',
            $s->province ?? ''
          ])));

          $birthDate = trim((string)($s->birthDate ?? ''));
          if ($birthDate === '0000-00-00') {
            $birthDate = '';
          }
          $birthDisp = $birthDate;
          $birthTs = $birthDate !== '' ? strtotime($birthDate) : false;
          if ($birthTs !== false) {
            $birthDisp = date('M d, Y', $birthTs);
          }

          // Display mobile numbers in the same 09XX XXX XXXX format the
          // registration/profile forms use (stored value is 11 digits).
          $contactDigits = substr(preg_replace('/\D+/', '', (string)($s->contactNo ?? '')), 0, 11);
          if (strlen($contactDigits) > 7) {
            $contactDisp = substr($contactDigits, 0, 4) . ' ' . substr($contactDigits, 4, 3) . ' ' . substr($contactDigits, 7);
          } elseif (strlen($contactDigits) > 4) {
            $contactDisp = substr($contactDigits, 0, 4) . ' ' . substr($contactDigits, 4);
          } else {
            $contactDisp = $contactDigits;
          }

          $field = static function ($v) {
            return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
          };
          ?>

          <div class="reg-scope">
            <div class="reg-card">
              <div class="card-banner">
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>

                <div class="banner-identity">
                  <img src="<?= base_url('upload/profile/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8')); ?>"
                    alt="" class="banner-avatar">
                  <div class="banner-text">
                    <div class="banner-eyebrow">Student Profile</div>
                    <div class="banner-title"><?= htmlspecialchars(strtoupper($fullName), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="banner-sub">
                      <?= $studentNumber !== '' ? htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8') . ' &middot; ' : ''; ?>
                      <?= $addr !== '' ? htmlspecialchars(strtoupper($addr), ENT_QUOTES, 'UTF-8') : 'NO ADDRESS ON FILE'; ?>
                    </div>
                  </div>
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
                <div class="section-head">
                  <div class="section-dot"></div>
                  <div class="section-label">Personal Information</div>
                  <div class="section-line"></div>
                </div>

                <div class="row-fields cols-4">
                  <div class="field-group">
                    <label class="field-label">Student ID</label>
                    <input type="text" class="field" value="<?= $field($studentNumber); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">First Name</label>
                    <input type="text" class="field" value="<?= $field($s->FirstName ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Middle Name</label>
                    <input type="text" class="field" value="<?= $field($s->MiddleName ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Last Name</label>
                    <input type="text" class="field" value="<?= $field($s->LastName ?? ''); ?>" placeholder="—" readonly>
                  </div>
                </div>

                <div class="row-fields cols-4">
                  <div class="field-group">
                    <label class="field-label">Ext.</label>
                    <input type="text" class="field" value="<?= $field($s->nameExtn ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Sex</label>
                    <input type="text" class="field" value="<?= $field($s->Sex ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Date of Birth</label>
                    <input type="text" class="field" value="<?= $field($birthDisp); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Age</label>
                    <input type="text" class="field" value="<?= $field($s->age ?? ''); ?>" placeholder="—" readonly>
                  </div>
                </div>

                <div class="row-fields cols-4">
                  <div class="field-group">
                    <label class="field-label">E-mail Address</label>
                    <input type="text" class="field" value="<?= $field($s->email ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Mobile No.</label>
                    <input type="text" class="field" value="<?= $field($contactDisp); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Civil Status</label>
                    <input type="text" class="field" value="<?= $field($s->CivilStatus ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Birth Place</label>
                    <input type="text" class="field" value="<?= $field($s->BirthPlace ?? ''); ?>" placeholder="—" readonly>
                  </div>
                </div>

                <div class="section-head">
                  <div class="section-dot"></div>
                  <div class="section-label">Address Information</div>
                  <div class="section-line"></div>
                </div>

                <div class="row-fields cols-4">
                  <div class="field-group">
                    <label class="field-label">Street / Sitio</label>
                    <input type="text" class="field" value="<?= $field($s->sitio ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Province</label>
                    <input type="text" class="field" value="<?= $field($s->province ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">City / Municipality</label>
                    <input type="text" class="field" value="<?= $field($s->city ?? ''); ?>" placeholder="—" readonly>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Barangay</label>
                    <input type="text" class="field" value="<?= $field($s->brgy ?? ''); ?>" placeholder="—" readonly>
                  </div>
                </div>

                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-top:10px;">
                  <a href="<?= base_url('Page/updateStudeProfile?id=' . urlencode($studentNumber)); ?>" class="btn-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit Profile</span>
                  </a>
                </div>

                <div class="form-footer">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Your information is securely stored and used solely for attendance purposes.
                </div>
              </div>
            </div>
          </div>

        </div><!-- /container-fluid -->
      </div><!-- /content -->
    </div><!-- /content-page -->

    <?php include('includes/footer.php'); ?>
  </div><!-- /wrapper -->

  <?php include('includes/themecustomizer.php'); ?>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>

</html>

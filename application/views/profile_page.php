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

          <!-- Profile banner -->
          <div class="row">
            <div class="col-sm-12">
              <div class="profile-bg-picture" style="background-image:url('<?= base_url(); ?>assets/images/bg-profile.jpg')">
                <span class="picture-bg-overlay"></span>
              </div>

              <!-- Header -->
              <div class="profile-user-box">
                <div class="row align-items-center">
                  <div class="col-md-7 d-flex align-items-center">
                    <div class="profile-user-img mr-3">
                      <?php
                      $isStudent = ($this->session->userdata('level') === 'Student');
                      $avatar = $isStudent
                        ? ($this->session->userdata('avatar') ?: 'default.png')
                        : ((!empty($data1) && !empty($data1[0]->avatar)) ? $data1[0]->avatar : 'default.png');
                      ?>
                      <img src="<?= base_url('upload/profile/' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8')); ?>" alt="" class="avatar-lg rounded-circle shadow-sm">
                    </div>

                    <?php
                    $s = isset($data[0]) ? $data[0] : (object)[];
                    $fullName = trim(implode(' ', array_filter([
                      $s->FirstName ?? '',
                      $s->MiddleName ?? '',
                      $s->LastName ?? '',
                      $s->nameExtn ?? ''
                    ])));
                    if ($fullName === '') $fullName = 'Student';
                    $addr = trim(implode(', ', array_filter([
                      $s->sitio ?? '',
                      $s->brgy ?? '',
                      $s->city ?? '',
                      $s->province ?? ''
                    ])));
                    ?>
                    <div>
                      <h4 class="mt-3 mb-1 font-18 ellipsis text-uppercase"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></h4>
                      <p class="mb-0 text-muted"><i class="mdi mdi-map-marker-outline"></i>
                        <?= $addr !== '' ? strtoupper($addr) : '—'; ?>
                      </p>
                    </div>
                  </div>

                  <div class="col-md-5 text-md-right mt-3 mt-md-0">
                    <?php
                    $studentNumber = (string)($s->StudentNumber ?? '');
                    $base = base_url();
                    ?>
                    <div class="btn-group">
                      <a href="<?= $base; ?>Page/updateStudeProfile?id=<?= urlencode($studentNumber); ?>"
                        class="btn btn-success waves-effect waves-light">
                        <i class="mdi mdi-account-edit-outline mr-1"></i>Edit Profile
                      </a>
                      <!-- <a href="<?= $base; ?>page/printstudeProfile?StudentNumber=<?= urlencode($studentNumber); ?>"
                       target="_blank" class="btn btn-secondary">
                      <i class="mdi mdi-printer mr-1"></i>Print Profile
                    </a> -->
                      <!-- <?php if ($this->session->userdata('level') !== 'Student'): ?>
                      <a href="<?= base_url('Student/print_grades/' . urlencode($studentNumber)); ?>"
                         target="_blank" class="btn btn-info">
                        <i class="mdi mdi-file-document-box-outline mr-1"></i>Grades
                      </a>
                    <?php endif; ?> -->
                    </div>
                  </div>
                </div><!-- /row -->
              </div><!-- /profile-user-box -->
            </div>
          </div>
          <!-- /Profile banner -->

          <!-- About card -->
          <div class="row mt-3">
            <div class="col-sm-12">
              <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pb-0">
                  <h5 class="mb-1">About</h5>
                  <p class="text-muted mb-0">Student’s Information</p>
                  <hr class="mt-3 mb-0" />
                </div>

                <div class="card-body pt-3">
                  <style>
                    .about-grid {
                      display: grid;
                      grid-template-columns: repeat(2, minmax(0, 1fr));
                      grid-gap: 16px
                    }

                    @media(max-width:768px) {
                      .about-grid {
                        grid-template-columns: 1fr
                      }
                    }

                    .about-item {
                      background: #f9fbff;
                      border: 1px solid #e6ecf5;
                      border-radius: 12px;
                      padding: 14px 16px
                    }

                    .about-label {
                      font-size: .78rem;
                      letter-spacing: .06em;
                      text-transform: uppercase;
                      color: #6b7b93;
                      margin-bottom: 4px
                    }

                    .about-value {
                      font-weight: 600;
                      color: #2c3e50
                    }

                    .about-section-title {
                      font-size: .9rem;
                      font-weight: 700;
                      color: #243b53;
                      margin: 16px 0 8px
                    }
                  </style>
                  <?php $val = fn($v) => ($t = trim((string)$v)) === '' ? '—' : htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>

                  <!-- Student No & Sex -->
                  <div class="about-grid">
                    <div class="about-item">
                      <div class="about-label">Student No.</div>
                      <div class="about-value"><?= $val($s->StudentNumber ?? ''); ?></div>
                    </div>
                    <div class="about-item">
                      <div class="about-label">Sex</div>
                      <div class="about-value"><?= $val($s->Sex ?? ''); ?></div>
                    </div>
                  </div>

                  <!-- Civil Status & Mobile -->
                  <div class="about-grid mt-2">
                    <div class="about-item">
                      <div class="about-label">Civil Status</div>
                      <div class="about-value"><?= $val($s->CivilStatus ?? ''); ?></div>
                    </div>
                    <div class="about-item">
                      <div class="about-label">Mobile No.</div>
                      <div class="about-value"><?= $val($s->contactNo ?? ''); ?></div>
                    </div>
                  </div>

                  <!-- Birth Date & Age -->
                  <div class="about-grid mt-2">
                    <div class="about-item">
                      <div class="about-label">Birth Date</div>
                      <div class="about-value"><?= $val($s->birthDate ?? ''); ?></div>
                    </div>
                    <div class="about-item">
                      <div class="about-label">Age</div>
                      <div class="about-value"><?= $val($s->age ?? ''); ?></div>
                    </div>
                  </div>

                  <!-- Birth Place & Email (same row) -->
                  <div class="about-grid mt-2">
                    <div class="about-item">
                      <div class="about-label">Birth Place</div>
                      <div class="about-value"><?= $val($s->BirthPlace ?? ''); ?></div>
                    </div>
                    <div class="about-item">
                      <div class="about-label">E-mail</div>
                      <div class="about-value"><?= $val($s->email ?? ''); ?></div>
                    </div>
                  </div>

                  <!-- Address (LAST) -->
                  <div class="about-section-title">Address</div>
                  <div class="about-grid mt-1">
                    <div class="about-item" style="grid-column:1/-1">
                      <div class="about-label">Present Address</div>
                      <div class="about-value">
                        <?= $val(trim(implode(', ', array_filter([
                          $s->sitio ?? '',
                          $s->brgy ?? '',
                          $s->city ?? '',
                          $s->province ?? ''
                        ])))); ?>
                      </div>
                    </div>
                  </div>

                </div><!-- /card-body -->
              </div><!-- /card -->
            </div>
          </div>
          <!-- /About card -->

        </div><!-- /container-fluid -->
      </div><!-- /content -->
    </div><!-- /content-page -->

    <?php include('includes/footer.php'); ?>
  </div><!-- /wrapper -->

  <?php include('includes/themecustomizer.php'); ?>

  <!-- Minimal JS -->
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>

</html>
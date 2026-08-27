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
          <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260827'); ?>">
          <style>
            .profile-wrapper {
              max-width: 960px;
              margin: 0 auto;
            }

            .profile-section+.profile-section {
              margin-top: 32px;
            }

            .profile-section h4 {
              font-weight: 800;
              font-size: .68rem;
              letter-spacing: .18em;
              text-transform: uppercase;
              color: var(--up-muted, #6b7a99);
              margin-bottom: 20px;
              display: flex;
              align-items: center;
              gap: 10px;
            }
            .profile-section h4::before {
              content: '';
              width: 8px; height: 8px; border-radius: 50%;
              background: linear-gradient(135deg, var(--up-blue, #2a4090), var(--up-blue-2, #4266d4));
              flex-shrink: 0;
            }

            .profile-grid {
              display: flex;
              flex-wrap: wrap;
              gap: 18px 20px;
            }

            .profile-grid .form-group {
              flex: 1 1 260px;
              min-width: 220px;
              margin-bottom: 0;
            }

            .profile-grid small {
              margin-top: 6px;
              display: block;
            }

            .page-title-box .title-block {
              display: flex;
              flex-direction: column;
              gap: 4px;
            }

            @media (max-width: 767.98px) {
              .profile-wrapper {
                max-width: 100%;
                margin: 0 0 36px;
              }

              .profile-grid .form-group {
                flex-basis: 100%;
                min-width: 100%;
              }
            }
          </style>
          <?php
          $account    = $bundle->account ?? (object)[];
          $profile    = $bundle->profile ?? (object)[];
          $enrollment = $bundle->enrollment ?? (object)[];

          $studentNumber = trim((string)($account->username ?? $profile->StudentNumber ?? ''));
          $flashSuccess  = $this->session->flashdata('success');
          $flashDanger   = $this->session->flashdata('danger');

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

          <div class="row">
            <div class="col-12">
              <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
                <div class="title-block">
                  <h4 class="up-page-title mb-1"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                  <p class="up-page-sub mb-0"><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <?php if ($backUrl !== ''): ?>
                  <div class="up-header-actions">
                    <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="up-btn up-btn-ghost">
                      <i class="mdi mdi-arrow-left"></i> <?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
              <hr class="up-divider" />
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="up-card">
                <div class="up-card-body profile-wrapper">
                  <form method="post" autocomplete="off" class="parsley-examples"
                    data-check-availability-url="<?= htmlspecialchars(site_url('Page/checkSignupAvailability'), ENT_QUOTES, 'UTF-8'); ?>"
                    data-exclude-student-number="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="profile-section">
                      <h4>Personal Information</h4>
                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="StudentNumber">Student ID / Number <span class="text-danger">*</span></label>
                          <input type="text"
                            id="StudentNumber"
                            class="form-control"
                            name="StudentNumber"
                            value="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Student ID"
                            minlength="4"
                            maxlength="20"
                            pattern="[A-Za-z0-9\-]+"
                            title="Use letters, numbers, and hyphen only."
                            required>
                          <input type="hidden" name="oldStudentNo" value="<?= htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?>">
                          <span class="availability-msg" id="student-number-status" aria-live="polite" style="display:block;min-height:18px;margin-top:6px;font-size:.72rem;font-weight:600;line-height:1.3;color:#7288b7;"></span>
                        </div>
                      </div>

                      <input type="hidden" name="nationality" value="<?= htmlspecialchars($nationality, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="working" value="<?= htmlspecialchars($working, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="VaccStat" value="<?= htmlspecialchars($vaccStat, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="Major1" id="major1" value="<?= htmlspecialchars($bundle->enrollment->Major ?? $bundle->profile->Major ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="FirstName">First Name <span class="text-danger">*</span></label>
                          <input type="text" id="FirstName" class="form-control" name="FirstName" style="text-transform: uppercase;"
                            placeholder="First Name"
                            value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                          <label for="MiddleName">Middle Name</label>
                          <input type="text" id="MiddleName" class="form-control" name="MiddleName" style="text-transform: uppercase;"
                            placeholder="Middle Name"
                            value="<?= htmlspecialchars($middleName, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                          <label for="LastName">Last Name <span class="text-danger">*</span></label>
                          <input type="text" id="LastName" class="form-control" name="LastName" style="text-transform: uppercase;"
                            placeholder="Last Name"
                            value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                          <label for="nameExtn">Name Extn.</label>
                          <input type="text" id="nameExtn" class="form-control" name="nameExtn" style="text-transform: uppercase;"
                            value="<?= htmlspecialchars($nameExtn, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Jr., Sr.">
                        </div>
                      </div>

                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="Sex">Sex <span class="text-danger">*</span></label>
                          <select class="form-control" id="Sex" name="Sex" required>
                            <option value=""></option>
                            <?php foreach ($sexOptions as $option): ?>
                              <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                                <?= strcasecmp($sexValue, $option) === 0 ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label for="bday">Birth Date <span class="text-danger">*</span></label>
                          <input type="date" id="bday" class="form-control" name="birthDate" onchange="submitBday()"
                            value="<?= htmlspecialchars($birthDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                          <label for="email">E-mail Address <span class="text-danger">*</span></label>
                          <input type="email" id="email" class="form-control" name="email"
                            placeholder="name@example.com"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                          <label for="contactNo">Mobile No. <span class="text-danger">*</span></label>
                          <input type="text" id="contactNo" class="form-control" name="contactNo"
                            placeholder="09XXXXXXXXX"
                            value="<?= htmlspecialchars($contactNo, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <input type="hidden" id="resultBday" class="form-control" name="age" value="<?= htmlspecialchars($ageValue, ENT_QUOTES, 'UTF-8'); ?>" readonly required autocomplete="off">
                      </div>

                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="CivilStatus">Civil Status</label>
                          <select class="form-control" id="CivilStatus" name="CivilStatus">
                            <option value=""></option>
                            <?php foreach ($civilStatusOptions as $status): ?>
                              <?php $trimStatus = trim((string)$status); ?>
                              <option value="<?= htmlspecialchars($trimStatus, ENT_QUOTES, 'UTF-8'); ?>" <?= strcasecmp($civilStatus, $trimStatus) === 0 ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($trimStatus, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label for="BirthPlace">Birth Place</label>
                          <input type="text" id="BirthPlace" class="form-control" name="BirthPlace"
                            placeholder="City / Province"
                            value="<?= htmlspecialchars($birthPlace, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                          <label for="ageDisplay">Age</label>
                          <input type="text" id="ageDisplay" class="form-control" value="<?= htmlspecialchars($ageValue, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>
                      </div>
                    </div>



                    <div class="profile-section">
                      <h4>Academic Information</h4>
                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="course1">Course/Program <span class="text-danger">*</span></label>
                          <select name="Course1" id="course1" class="form-control" required>
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
                        <div class="form-group">
                          <label for="yearLevel">Year Level <span class="text-danger">*</span></label>
                          <select class="form-control" name="yearLevel" id="yearLevel" required>
                            <option value="">Select Year Level</option>
                            <?php foreach ($yearLevels as $yl): ?>
                              <option value="<?= htmlspecialchars($yl, ENT_QUOTES, 'UTF-8'); ?>"
                                <?= strcasecmp($currentYear, $yl) === 0 ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($yl, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="text-muted">Format: 1st / 2nd / 3rd / 4th</small>
                        </div>
                        <div class="form-group">
                          <label for="section">Section <span class="text-danger">*</span></label>
                          <select class="form-control" name="section" id="section" data-current="<?= htmlspecialchars($currentSection, ENT_QUOTES, 'UTF-8'); ?>" required>
                            <option value="">Select Section</option>
                          </select>
                          <small class="text-muted">Sections depend on Course/Program &amp; Year Level.</small>
                        </div>
                      </div>
                    </div>
                    <div class="profile-section">
                      <h4>Address Information</h4>
                      <div class="profile-grid">
                        <div class="form-group">
                          <label for="sitio">Street / Sitio</label>
                          <input type="text" id="sitio" class="form-control" name="sitio"
                            placeholder="Street / Sitio"
                            value="<?= htmlspecialchars($sitioValue, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                          <label for="province">Province</label>
                          <select id="province" name="province" class="form-control" data-current="<?= htmlspecialchars($currentProvince, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <div class="form-group">
                          <label for="city">City / Municipality</label>
                          <select id="city" name="city" class="form-control" data-current="<?= htmlspecialchars($currentCity, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <div class="form-group">
                          <label for="barangay">Barangay</label>
                          <select id="barangay" name="brgy" class="form-control" data-current="<?= htmlspecialchars($currentBrgy, ENT_QUOTES, 'UTF-8'); ?>">
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
                    </div>
                    <br>
                    <div class="text-right">
                      <button type="submit" class="up-btn up-btn-primary">
                        <i class="mdi mdi-content-save-outline"></i> <?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
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
</body>

</html>
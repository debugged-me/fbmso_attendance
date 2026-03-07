<!DOCTYPE html>
<html class="bg-black">

<head>
  <meta charset="UTF-8">
  <title>Attendance Portal | Registration</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="shortcut icon" href="<?= base_url(); ?>assets/images/Attendance.png">
  <link href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="bootstrap-stylesheet" />
  <link href="<?= base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="<?= base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-stylesheet" />

  <script src="<?= base_url(); ?>assets/js/jquery-3.6.0.min.js"></script>

  <script type="text/javascript">
    function submitBday() {
      var Bdate = document.getElementById('bday').value;
      var Bday = +new Date(Bdate);
      var age = ~~((Date.now() - Bday) / 31557600000);
      document.getElementById('resultBday').value = isFinite(age) ? age : '';
    }
  </script>
</head>

<body data-layout="horizontal">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <img src="<?= base_url(); ?>assets/images/REGISTRATIONHEADER.png" width="100%" class="img-responsive" alt="Registration Header"><br />

          <?php if ($this->session->flashdata('msg')): ?>
            <?php echo $this->session->flashdata('msg'); ?>
          <?php endif; ?>

          <form method="post" class="parsley-examples">
            <?php if (strtolower((string)$this->input->get('source')) === 'admin'): ?>
              <input type="hidden" name="source" value="admin">
            <?php endif; ?>
            <div class="col-lg-12">
              <h4>PERSONAL INFORMATION</h4>
              <div class="row">
                <div class="col-sm-4 form-group">
                  <label for="StudentNumber">STUDENT ID <span style="color:red;">*</span></label>
                  <input type="text"
                    id="StudentNumber"
                    class="form-control"
                    name="StudentNumber"
                    placeholder="example: 2023-0446"
                    minlength="4"
                    maxlength="20"
                    pattern="[A-Za-z0-9\-]+"
                    title="Make sure its the same with your ID."
                    required>
                  <small class="form-text text-muted">This will be your username. make sure to match it to your school id.</small>
                </div>
              </div>
              <input type="hidden" name="nationality" value="Filipino">
              <input type="hidden" name="working" value="No">
              <input type="hidden" name="VaccStat" value="">

              <!-- Name (3 + 3 + 3 + 3 = 12) -->
              <div class="row">
                <div class="col-sm-3 form-group">
                  <label for="FirstName"> FIRST NAME <span style="color:red;">*</span></label>
                  <input type="text" id="FirstName" class="form-control" name="FirstName" style="text-transform: uppercase;" required>
                </div>
                <div class="col-sm-3 form-group">
                  <label for="MiddleName">MIDDLE NAME </label>
                  <input type="text" id="MiddleName" class="form-control" name="MiddleName" style="text-transform: uppercase;">
                </div>
                <div class="col-sm-3 form-group">
                  <label for="LastName">LAST NAME <span style="color:red;">*</span></label>
                  <input type="text" id="LastName" class="form-control" name="LastName" style="text-transform: uppercase;" required>
                </div>
                <div class="col-sm-3 form-group">
                  <label for="nameExtn">NAME EXTNT.</label>
                  <input type="text" id="nameExtn" placeholder="e.g. Jr., Sr." class="form-control" name="nameExtn" style="text-transform: uppercase;">
                </div>
              </div>

              <!-- Sex / Civil Status / Birth Date / Age (3 + 3 + 4 + 2 = 12) -->
              <div class="row">
                <div class="col-sm-3 form-group">
                  <label for="Sex">SEX <span style="color:red;">*</span></label>
                  <select class="form-control" id="Sex" name="Sex" required>
                    <option value=""></option>
                    <option>Female</option>
                    <option>Male</option>
                    <option>Others</option>
                  </select>
                </div>

                <div class="col-sm-3 form-group">
                  <label for="bday">DATE OF BIRTH <span style="color:red;">*</span></label>
                  <input type="date" id="bday" class="form-control" name="birthDate" onchange="submitBday()" required>
                </div>
                <div class="col-sm-3 form-group">
                  <label for="email">E-MAIL ADDRESS <span style="color:red;">*</span></label>
                  <input type="email" id="email" class="form-control" name="email" required>
                </div>
                <div class="col-sm-3 form-group">
                  <label for="contactNo">MOBILE NO. <span style="color:red;">*</span></label>
                  <input type="text" id="contactNo" class="form-control" name="contactNo" required>
                </div>
                <!-- <label for="resultBday">Age <span style="color:red;">*</span></label> -->
                <input type="hidden" id="resultBday" class="form-control" name="age" readonly required autocomplete="off">
              </div>





              <!-- Academic (Course + Year + Section) -->
              <div class="row">
                <div class="col-sm-4 form-group">
                  <label for="course1">COURSE / PROGRAM <span style="color:red;">*</span></label>
                  <select name="Course1" id="course1" class="form-control" required>
                    <option value="">Select Course</option>
                    <?php foreach ($course as $row) {
                      echo '<option value="' . htmlspecialchars($row->CourseDescription, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row->CourseDescription, ENT_QUOTES, 'UTF-8') . '</option>';
                    } ?>
                  </select>
                </div>
                <div class="col-sm-4 form-group">
                  <label for="yearLevel">YEAR LEVEL <span style="color:red;">*</span></label>
                  <select class="form-control" name="yearLevel" id="yearLevel" required>
                    <option value="">Select Year Level</option>
                    <option value="1st">1st</option>
                    <option value="2nd">2nd</option>
                    <option value="3rd">3rd</option>
                    <option value="4th">4th</option>
                  </select>
                </div>
                <div class="col-sm-4 form-group">
                  <label for="section">SECTION <span style="color:red;">*</span></label>
                  <select class="form-control" name="section" id="section" required>
                    <option value="">Select Section</option>
                    <!-- Populated by AJAX based on Course/Program + Year Level -->
                  </select>
                </div>
              </div>
              <input type="hidden" name="Major1" id="major1">


              <!-- reCAPTCHA + Submit -->
              <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($site_key) ?>"></div>
              </div>

              <div class="row">
                <div class="col-sm-6 form-group">
                  <input type="submit" name="register" id="submitBtn" class="btn btn-lg btn-info" value="Create My Account">
                </div>
              </div>

            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <!-- Courses → Majors -->
  <script>
    $(function() {
      function extractFirstMajor(optionsHtml) {
        var value = '';
        if (optionsHtml) {
          var container = document.createElement('div');
          container.innerHTML = '<select>' + optionsHtml + '</select>';
          var options = container.querySelectorAll('option');
          for (var i = 0; i < options.length; i++) {
            var optionValue = (options[i].value || '').trim();
            if (optionValue !== '') {
              value = options[i].value;
              break;
            }
          }
        }
        return value;
      }

      function hookCourseToMajor(courseSelector, hiddenSelector) {
        var $course = $(courseSelector);
        var $hidden = $(hiddenSelector);
        if (!$course.length || !$hidden.length) {
          return;
        }

        $course.on('change', function() {
          var course = $(this).val();

          var finalize = function() {
            if (courseSelector === '#course1') {
              reloadSections();
            }
          };

          if (!course) {
            $hidden.val('');
            finalize();
            return;
          }

          $.post('<?= base_url("Registration/getMajorsByCourse") ?>', {
              course: course
            })
            .done(function(html) {
              var majorValue = extractFirstMajor(html);
              $hidden.val(majorValue);
            })
            .fail(function() {
              window.alert('Failed to fetch majors. Please try again.');
              $hidden.val('');
            })
            .always(finalize);
        });
      }

      hookCourseToMajor('#course1', '#major1');
      $('#course1').trigger('change');
    });
  </script>

  <!-- Province → City → Barangay -->
  <script>
    $(function() {
      $('#province').on('change', function() {
        var province = $(this).val();
        if (province) {
          $.post('<?= base_url("Registration/getCitiesByProvince") ?>', {
              province: province
            })
            .done(function(html) {
              $('#city').html(html);
              $('#barangay').html('<option value="">Select Barangay</option>');
            })
            .fail(function() {
              alert('Failed to fetch cities. Please try again.');
            });
        } else {
          $('#city').html('<option value="">Select City/Municipality</option>');
          $('#barangay').html('<option value="">Select Barangay</option>');
        }
      });

      $('#city').on('change', function() {
        var city = $(this).val();
        if (city) {
          $.post('<?= base_url("Registration/getBarangaysByCity") ?>', {
              city: city
            })
            .done(function(html) {
              $('#barangay').html(html);
            })
            .fail(function() {
              alert('Failed to fetch barangays. Please try again.');
            });
        } else {
          $('#barangay').html('<option value="">Select Barangay</option>');
        }
      });
    });
  </script>

  <script>
    function reloadSections() {
      // Your #course1 currently holds CourseDescription as the value (from StudentModel::getCourse()).
      // We’ll send that and let the controller resolve the numeric courseid.
      var course = $('#course1').val(); // CourseDescription (string)
      var yl = $('#yearLevel').val(); // '1st'..'4th'

      if (!course || !yl) {
        $('#section').html('<option value="">Select Section</option>');
        return;
      }

      $.post('<?= base_url("Registration/getSectionsByCourseYear") ?>', {
            course: course,
            yearLevel: yl
          } // send description + year level
        )
        .done(function(html) {
          $('#section').html(html || '<option value="">Select Section</option>');
        })
        .fail(function() {
          alert('Failed to load sections. Please try again.');
          $('#section').html('<option value="">Select Section</option>');
        });
    }

    // When Year Level or primary Course changes, reload sections
    $(function() {
      $('#yearLevel').on('change', reloadSections);
      $('#course1').on('change', reloadSections);
    });
  </script>

  <!-- reCAPTCHA required -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      if (grecaptcha.getResponse() === '') {
        e.preventDefault();
        alert('Please confirm you are not a robot.');
      }
    });
  </script>
</body>

</html>
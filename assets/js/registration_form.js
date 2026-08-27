(function(window, document, $) {
  'use strict';

  function resolveConfig() {
    var form = document.querySelector('form.parsley-examples');
    var data = form ? (form.dataset || {}) : {};
    var globalCfg = window.registrationConfig || {};

    return {
      majorsByCourseUrl: data.majorsByCourseUrl || globalCfg.majorsByCourseUrl || '',
      citiesByProvinceUrl: data.citiesByProvinceUrl || globalCfg.citiesByProvinceUrl || '',
      barangaysByCityUrl: data.barangaysByCityUrl || globalCfg.barangaysByCityUrl || '',
      sectionsByCourseYearUrl: data.sectionsByCourseYearUrl || globalCfg.sectionsByCourseYearUrl || '',
      checkAvailabilityUrl: data.checkAvailabilityUrl || globalCfg.checkAvailabilityUrl || '',
      recaptchaRequiredMessage: data.recaptchaRequiredMessage || globalCfg.recaptchaRequiredMessage || ''
    };
  }

  var cfg = resolveConfig();

  function getText(key, fallback) {
    var val = cfg[key];
    return (typeof val === 'string' && val.trim() !== '') ? val : fallback;
  }

  function postJson(url, data) {
    return $.post(url, data);
  }

  function showError(message) {
    if (window.UI && UI.error) {
      UI.error(message);
      return;
    }
    window.alert(message);
  }

  function debounce(fn, wait) {
    var t = null;
    return function() {
      var args = arguments;
      var ctx = this;
      clearTimeout(t);
      t = setTimeout(function() {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  window.submitBday = function submitBday() {
    var bdayEl = document.getElementById('bday');
    var resultEl = document.getElementById('resultBday');
    if (!bdayEl || !resultEl) {
      return;
    }

    var bdate = bdayEl.value;
    var birthday = +new Date(bdate);
    var age = ~~((Date.now() - birthday) / 31557600000);
    resultEl.value = isFinite(age) ? age : '';
  };

  window.reloadSections = function reloadSections() {
    var $course = $('#course1');
    var $yearLevel = $('#yearLevel');
    var $section = $('#section');

    if (!$course.length || !$yearLevel.length || !$section.length) {
      return;
    }

    var course = $course.val();
    var yearLevel = $yearLevel.val();

    if (!course || !yearLevel) {
      $section.html('<option value="">Select Section</option>');
      return;
    }

    var sectionsUrl = cfg.sectionsByCourseYearUrl || 'Registration/getSectionsByCourseYear';
    var currentSection = ($section.val() || '').trim();

    postJson(sectionsUrl, {
      course: course,
      yearLevel: yearLevel
    })
      .done(function(html) {
        $section.html(html || '<option value="">Select Section</option>');
        if (currentSection) {
          $section.val(currentSection);
          if ($section.val() !== currentSection) {
            $section.append('<option value="' + currentSection + '" selected>' + currentSection + '</option>');
          }
        }
      })
      .fail(function() {
        showError('Failed to load sections. Please try again.');
        $section.html('<option value="">Select Section</option>');
      });
  };

  $(function() {
    var $bday = $('#bday');
    if ($bday.length) {
      $bday.on('change input', window.submitBday);
      window.submitBday();
    }

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
            window.reloadSections();
          }
        };

        if (!course) {
          $hidden.val('');
          finalize();
          return;
        }

        var majorsUrl = cfg.majorsByCourseUrl || 'Registration/getMajorsByCourse';

        postJson(majorsUrl, {
          course: course
        })
          .done(function(html) {
            var majorValue = extractFirstMajor(html);
            $hidden.val(majorValue);
          })
          .fail(function() {
            showError('Failed to fetch majors. Please try again.');
            $hidden.val('');
          })
          .always(finalize);
      });
    }

    hookCourseToMajor('#course1', '#major1');

    if ($('#course1').length) {
      $('#course1').trigger('change');
    }

    function updateAvailabilityLabel($label, state, text) {
      if (!$label || !$label.length) {
        return;
      }
      $label.removeClass('is-ok is-bad is-muted');
      if (state) {
        $label.addClass(state);
      }
      $label.text(text || '');
    }

    function parseResponse(payload) {
      if (typeof payload === 'object' && payload !== null) {
        return payload;
      }
      if (typeof payload === 'string' && payload !== '') {
        try {
          return JSON.parse(payload);
        } catch (e) {
          return null;
        }
      }
      return null;
    }

    function checkAvailability(field, rawValue, inputEl, $label) {
      var value = (rawValue || '').trim();
      if (!value) {
        if (inputEl && typeof inputEl.setCustomValidity === 'function') {
          inputEl.setCustomValidity('');
        }
        updateAvailabilityLabel($label, '', '');
        return;
      }

      var checkUrl = cfg.checkAvailabilityUrl || 'Registration/checkAvailability';
      postJson(checkUrl, {
        field: field,
        value: value
      })
        .done(function(payload) {
          var data = parseResponse(payload);
          if (!data || !data.ok) {
            updateAvailabilityLabel($label, 'is-muted', '');
            if (inputEl && typeof inputEl.setCustomValidity === 'function') {
              inputEl.setCustomValidity('');
            }
            return;
          }

          if (data.exists) {
            updateAvailabilityLabel($label, 'is-bad', data.message || 'Already exists.');
            if (inputEl && typeof inputEl.setCustomValidity === 'function') {
              inputEl.setCustomValidity(data.message || 'Already exists.');
            }
          } else {
            updateAvailabilityLabel($label, 'is-ok', data.message || 'Available.');
            if (inputEl && typeof inputEl.setCustomValidity === 'function') {
              inputEl.setCustomValidity('');
            }
          }
        })
        .fail(function() {
          updateAvailabilityLabel($label, 'is-muted', '');
          if (inputEl && typeof inputEl.setCustomValidity === 'function') {
            inputEl.setCustomValidity('');
          }
        });
    }

    var $studentNumber = $('#StudentNumber');
    var $studentNumberStatus = $('#student-number-status');
    if ($studentNumber.length) {
      var runStudentCheck = debounce(function() {
        var v = ($studentNumber.val() || '').toUpperCase();
        $studentNumber.val(v);
        checkAvailability('studentnumber', v, $studentNumber.get(0), $studentNumberStatus);
      }, 300);

      $studentNumber.on('input blur', runStudentCheck);
      if (($studentNumber.val() || '').trim() !== '') {
        runStudentCheck();
      }
    }

    var $email = $('#email');
    var $emailStatus = $('#email-status');
    if ($email.length) {
      var runEmailCheck = debounce(function() {
        checkAvailability('email', ($email.val() || ''), $email.get(0), $emailStatus);
      }, 300);

      $email.on('input blur', runEmailCheck);
      if (($email.val() || '').trim() !== '') {
        runEmailCheck();
      }
    }

    var $password = $('#password');
    var $confirmPassword = $('#confirm_password');
    $('.password-toggle').on('click', function() {
      var targetSelector = $(this).data('target');
      if (!targetSelector) {
        return;
      }

      var $target = $(targetSelector);
      if (!$target.length) {
        return;
      }

      var isHidden = ($target.attr('type') || 'password') === 'password';
      $target.attr('type', isHidden ? 'text' : 'password');

      var $icon = $(this).find('i');
      if ($icon.length) {
        $icon.removeClass('mdi-eye-outline mdi-eye-off-outline')
          .addClass(isHidden ? 'mdi-eye-off-outline' : 'mdi-eye-outline');
      }

      var nextLabel = isHidden ? 'Hide password' : 'Show password';
      this.setAttribute('aria-label', nextLabel);
      this.setAttribute('title', nextLabel);
    });

    if ($password.length && $confirmPassword.length) {
      var syncPasswordValidity = function() {
        var p = $password.val() || '';
        var c = $confirmPassword.val() || '';
        if (c && p !== c) {
          $confirmPassword.get(0).setCustomValidity('Passwords do not match.');
        } else {
          $confirmPassword.get(0).setCustomValidity('');
        }
      };
      $password.on('input', syncPasswordValidity);
      $confirmPassword.on('input', syncPasswordValidity);
    }

    var $province = $('#province');
    var $city = $('#city');
    var $barangay = $('#barangay');

    if ($province.length && $city.length && $barangay.length) {
      $province.on('change', function() {
        var province = $(this).val();
        if (province) {
          var citiesUrl = cfg.citiesByProvinceUrl || 'Registration/getCitiesByProvince';

          postJson(citiesUrl, {
            province: province
          })
            .done(function(html) {
              $city.html(html);
              $barangay.html('<option value="">Select Barangay</option>');
            })
            .fail(function() {
              showError('Failed to fetch cities. Please try again.');
            });
        } else {
          $city.html('<option value="">Select City/Municipality</option>');
          $barangay.html('<option value="">Select Barangay</option>');
        }
      });

      $city.on('change', function() {
        var city = $(this).val();
        if (city) {
          var barangaysUrl = cfg.barangaysByCityUrl || 'Registration/getBarangaysByCity';

          postJson(barangaysUrl, {
            city: city
          })
            .done(function(html) {
              $barangay.html(html);
            })
            .fail(function() {
              showError('Failed to fetch barangays. Please try again.');
            });
        } else {
          $barangay.html('<option value="">Select Barangay</option>');
        }
      });
    }

    $('#yearLevel, #course1').on('change', window.reloadSections);

    var form = document.querySelector('form.parsley-examples') || document.querySelector('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        var message = getText('recaptchaRequiredMessage', 'Please confirm you are not a robot.');

        if (typeof window.grecaptcha === 'undefined' || typeof window.grecaptcha.getResponse !== 'function') {
          e.preventDefault();
          showError(message);
          return;
        }

        if (window.grecaptcha.getResponse() === '') {
          e.preventDefault();
          showError(message);
        }
      });
    }

    /* ===== Sticky progress indicator — tracks which section is in view ===== */
    var sections = [
      { id: 'section-credentials', step: 1 },
      { id: 'section-personal',    step: 2 },
      { id: 'section-academic',    step: 3 }
    ];
    var stepEls = document.querySelectorAll('.reg-progress-step');
    var lineEls = document.querySelectorAll('.reg-progress-line');

    function updateProgress() {
      var scrollY = window.scrollY + window.innerHeight * 0.35;
      var currentStep = 1;
      for (var i = 0; i < sections.length; i++) {
        var el = document.getElementById(sections[i].id);
        if (el && el.offsetTop <= scrollY) {
          currentStep = sections[i].step;
        }
      }
      stepEls.forEach(function (el) {
        var s = parseInt(el.getAttribute('data-step'), 10);
        el.classList.remove('active', 'done');
        if (s < currentStep) { el.classList.add('done'); }
        else if (s === currentStep) { el.classList.add('active'); }
      });
      lineEls.forEach(function (el, idx) {
        if (idx < currentStep - 1) { el.classList.add('filled'); }
        else { el.classList.remove('filled'); }
      });
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  });
})(window, document, window.jQuery);

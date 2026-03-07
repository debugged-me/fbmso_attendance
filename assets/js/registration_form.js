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
    window.alert(message);
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

    postJson(sectionsUrl, {
      course: course,
      yearLevel: yearLevel
    })
      .done(function(html) {
        $section.html(html || '<option value="">Select Section</option>');
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
  });
})(window, document, window.jQuery);

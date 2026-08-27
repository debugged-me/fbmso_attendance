/* ========================================================================== 
   Mobile Shell — early DataTables defaults
   Registered in <head>; applied before document-ready table initializers.
   ========================================================================== */

(function (window, document) {
  'use strict';

  var BP = 767.98;
  var applied = false;

  function isPhone() {
    return window.matchMedia('(max-width: ' + BP + 'px)').matches;
  }

  function applyDataTableDefaults() {
    var $ = window.jQuery;
    if (!isPhone() || !$ || !$.fn || !$.fn.dataTable || !$.fn.dataTable.defaults) return false;
    if (applied) return true;

    $.extend(true, $.fn.dataTable.defaults, {
      pageLength: 10,
      lengthChange: false,
      dom: '<"ms-dt-top"f>rt<"ms-dt-bottom"p>'
    });
    applied = true;
    return true;
  }

  window.MSDataTables = {
    apply: applyDataTableDefaults
  };

  /* Some legacy views give form fields an inline font-size with !important.
     Raise the field to iOS' 16px focus threshold on first touch, before the
     deferred shell has had a chance to normalize the rest of the form. */
  document.addEventListener('touchstart', function (event) {
    if (!isPhone()) return;
    var target = event.target;
    var field = target && target.closest ? target.closest('input, select, textarea') : null;
    if (!field || field.__msEarlyFont) return;
    field.__msEarlyFont = {
      value: field.style.getPropertyValue('font-size'),
      priority: field.style.getPropertyPriority('font-size')
    };
    field.style.setProperty('font-size', '16px', 'important');
  }, { capture: true, passive: true });

  if (document.readyState !== 'loading') {
    applyDataTableDefaults();
  } else {
    document.addEventListener('DOMContentLoaded', applyDataTableDefaults, { once: true });
  }
})(window, document);

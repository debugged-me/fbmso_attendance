/* ==========================================================================
   Anti-inspect deterrents
   --------------------------------------------------------------------------
   DISCLAIMER: This is NOT security. Anyone determined can still view source
   via browser menus, curl, proxy tools, or by disabling JavaScript. This
   only deters casual users from right-clicking or using keyboard shortcuts
   to open DevTools or view source.
   ========================================================================== */
(function () {
  'use strict';

  // 1) Disable right-click context menu
  document.addEventListener('contextmenu', function (e) {
    e.preventDefault();
    return false;
  });

  // 2) Block common keyboard shortcuts for DevTools / view source / save
  document.addEventListener('keydown', function (e) {
    // F12
    if (e.keyCode === 123) {
      e.preventDefault();
      return false;
    }

    // Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C  (DevTools panels)
    if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
      e.preventDefault();
      return false;
    }

    // Ctrl+U  (view source)
    if (e.ctrlKey && !e.shiftKey && e.keyCode === 85) {
      e.preventDefault();
      return false;
    }

    // Ctrl+S  (save page)
    if (e.ctrlKey && !e.shiftKey && e.keyCode === 83) {
      e.preventDefault();
      return false;
    }
  });
})();

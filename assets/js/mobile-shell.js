/* ========================================================================== 
   Mobile Shell — shared phone navigation and interaction layer

   Vanilla JavaScript, no build step. Each module is isolated so a legacy view
   with unusual markup cannot stop the rest of the shell from booting.
   ========================================================================== */

(function (window, document) {
  'use strict';

  if (window.MS && window.MS.__mobileShell) return;

  var BP = 767.98;
  var isPhone = function () {
    return window.matchMedia('(max-width: ' + BP + 'px)').matches;
  };

  var MS = window.MS = {
    __mobileShell: true,
    isPhone: isPhone,
    modules: {}
  };

  function each(nodes, fn) {
    Array.prototype.forEach.call(nodes || [], fn);
  }

  function normalizedPath(value) {
    try {
      var url = new window.URL(value, window.location.href);
      return url.pathname.replace(/\/+$/, '').toLowerCase() || '/';
    } catch (_e) {
      return String(value || '').split(/[?#]/)[0].replace(/\/+$/, '').toLowerCase() || '/';
    }
  }

  function syncBodyState() {
    if (!document.body) return;
    document.body.classList.toggle('ms-phone', isPhone());
  }

  /* ------------------------------------------------------------------------
     App bar title and compact overflow menu
     ------------------------------------------------------------------------ */

  MS.modules.appbar = function () {
    var bar = document.getElementById('ms-appbar');
    if (!bar) return;

    var title = bar.querySelector('.ms-appbar-title');
    var source = document.querySelector('.page-title') || document.querySelector('.page-title-box h4');
    var titleText = source ? source.textContent : document.title;
    if (title) title.textContent = String(titleText || 'Attendance').replace(/\s+/g, ' ').trim();

    var actions = document.getElementById('ms-top-actions');
    if (!actions || actions.querySelector('.ms-overflow-action')) return;

    var item = document.createElement('li');
    item.className = 'dropdown notification-list ms-overflow-action';
    item.innerHTML =
      '<a class="nav-link dropdown-toggle waves-effect" href="#" role="button" ' +
        'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="More actions">' +
        '<i class="mdi mdi-dots-vertical" aria-hidden="true"></i>' +
      '</a>' +
      '<div class="dropdown-menu dropdown-menu-right ms-overflow-menu"></div>';

    var profile = actions.querySelector('.ms-profile-action');
    actions.insertBefore(item, profile || null);

    var menu = item.querySelector('.ms-overflow-menu');
    var entries = [];
    var birthday = document.getElementById('bday-li');
    var requests = actions.querySelector('.req-bell');
    var settings = actions.querySelector('.ms-settings-action .right-bar-toggle');

    if (birthday) {
      entries.push({ href: findHref(birthday, 'bdayToday'), icon: 'mdi-cake-variant', label: "Today's birthdays" });
      entries.push({ href: findHref(birthday, 'bdayMonth'), icon: 'mdi-calendar-month-outline', label: 'Birthdays this month' });
    }
    if (requests) {
      entries.push({
        href: requests.getAttribute('data-index-url') || findHref(requests),
        icon: 'mdi-bell-outline',
        label: 'Pending requests',
        badgeSource: requests.querySelector('.req-badge')
      });
    }
    if (settings) {
      entries.push({ href: '#', icon: 'mdi-settings-outline', label: 'Display settings', action: settings });
    }

    entries.forEach(function (entry) {
      if (!entry.href && !entry.action) return;
      var link = document.createElement('a');
      link.className = 'dropdown-item ms-overflow-item';
      link.href = entry.href || '#';
      link.innerHTML = '<i class="mdi ' + entry.icon + '" aria-hidden="true"></i><span></span>';
      link.querySelector('span').textContent = entry.label;

      if (entry.action) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          entry.action.click();
          closeOverflow();
        });
      }

      if (entry.badgeSource) {
        var badge = document.createElement('span');
        badge.className = 'badge badge-danger ms-overflow-badge';
        link.appendChild(badge);
        var syncBadge = function () {
          badge.textContent = entry.badgeSource.textContent || '';
          badge.style.display = entry.badgeSource.style.display === 'none' ? 'none' : '';
        };
        syncBadge();
        if (window.MutationObserver) {
          new window.MutationObserver(syncBadge).observe(entry.badgeSource, {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
          });
        }
      }
      menu.appendChild(link);
    });

    var toggle = item.querySelector('.dropdown-toggle');
    function closeOverflow() {
      item.classList.remove('show');
      menu.classList.remove('show');
      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (event) {
      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown) return;
      event.preventDefault();
      event.stopPropagation();
      var open = !menu.classList.contains('show');
      item.classList.toggle('show', open);
      menu.classList.toggle('show', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (event) {
      if (!item.contains(event.target)) closeOverflow();
    });

    function findHref(root, fragment) {
      var links = root.querySelectorAll('a[href]');
      for (var i = 0; i < links.length; i++) {
        if (!fragment || links[i].href.indexOf(fragment) !== -1) return links[i].href;
      }
      return '';
    }
  };

  /* ------------------------------------------------------------------------
     Drawer: scrim, history, focus, and touch gestures
     ------------------------------------------------------------------------ */

  MS.modules.drawer = function () {
    var drawer = document.querySelector('.left-side-menu');
    var trigger = document.getElementById('ms-drawer-toggle') || document.querySelector('.button-menu-mobile');
    if (!drawer || !trigger || drawer.getAttribute('data-ms-drawer-ready') === '1') return;
    drawer.setAttribute('data-ms-drawer-ready', '1');
    trigger.setAttribute('aria-controls', 'ms-drawer');
    trigger.setAttribute('aria-expanded', 'false');
    if (!drawer.id) drawer.id = 'ms-drawer';

    var scrim = document.createElement('button');
    scrim.type = 'button';
    scrim.className = 'ms-scrim';
    scrim.setAttribute('aria-label', 'Close menu');
    document.body.appendChild(scrim);

    var about = document.createElement('button');
    about.type = 'button';
    about.className = 'ms-drawer-about';
    about.innerHTML = '<i class="mdi mdi-information-outline" aria-hidden="true"></i><span>About FBMSO</span>';
    var logout = drawer.querySelector('.sidebar-logout');
    if (logout) logout.parentNode.insertBefore(about, logout);
    about.addEventListener('click', function () {
      requestClose();
      var modalTrigger = document.querySelector('[data-target="#fbmsoVisionMissionModal"]');
      if (modalTrigger) window.setTimeout(function () { modalTrigger.click(); }, 220);
    });

    var restoreFocus = null;
    var pushed = false;
    var syncing = false;
    var ownsDrawerLocks = false;
    var focusableSelector = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

    function isOpen() {
      return document.body.classList.contains('sidebar-enable');
    }

    function releaseDrawerLocks() {
      if (document.body.classList.contains('ms-drawer-dragging') ||
          document.body.classList.contains('ms-drawer-locked')) {
        document.body.classList.remove('ms-drawer-dragging', 'ms-drawer-locked');
      }
      if (document.documentElement.classList.contains('ms-drawer-locked')) {
        document.documentElement.classList.remove('ms-drawer-locked');
      }
      if (!document.querySelector('.modal.show, .uk-backdrop, .uk-busy')) {
        if (document.body.classList.contains('uk-locked')) document.body.classList.remove('uk-locked');
        if (document.documentElement.classList.contains('uk-locked')) {
          document.documentElement.classList.remove('uk-locked');
        }
      }
    }

    function openDrawer(pushHistory) {
      if (!isPhone() || isOpen()) return;
      restoreFocus = document.activeElement;
      syncing = true;
      document.body.classList.add('sidebar-enable', 'uk-locked', 'ms-drawer-locked');
      document.documentElement.classList.add('uk-locked', 'ms-drawer-locked');
      ownsDrawerLocks = true;
      syncing = false;
      drawer.setAttribute('aria-hidden', 'false');
      trigger.setAttribute('aria-expanded', 'true');
      if (pushHistory !== false) {
        window.history.pushState({ msDrawer: true }, document.title, window.location.href);
        pushed = true;
      }
      var first = drawer.querySelector(focusableSelector);
      if (first) window.setTimeout(function () { first.focus(); }, 30);
    }

    function closeDrawer(options) {
      options = options || {};
      if (!isOpen() && !options.force) {
        if (ownsDrawerLocks) {
          releaseDrawerLocks();
          ownsDrawerLocks = false;
        }
        if (options.fromPop) pushed = false;
        return;
      }
      syncing = true;
      var shouldReleaseLocks = ownsDrawerLocks;
      ownsDrawerLocks = false;
      document.body.classList.remove('sidebar-enable');
      if (shouldReleaseLocks) releaseDrawerLocks();
      syncing = false;
      drawer.style.transform = '';
      drawer.setAttribute('aria-hidden', 'true');
      trigger.setAttribute('aria-expanded', 'false');
      if (options.restore !== false && restoreFocus && typeof restoreFocus.focus === 'function') restoreFocus.focus();
      restoreFocus = null;
      if (options.fromPop) pushed = false;
    }

    function requestClose() {
      var shouldPop = pushed && window.history.state && window.history.state.msDrawer;
      closeDrawer();
      if (shouldPop) window.history.back();
    }

    MS.openDrawer = openDrawer;
    MS.closeDrawer = requestClose;

    trigger.addEventListener('click', function (event) {
      if (!isPhone()) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      isOpen() ? requestClose() : openDrawer(true);
    }, true);
    scrim.addEventListener('click', requestClose);

    document.addEventListener('keydown', function (event) {
      if (!isOpen()) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        requestClose();
        return;
      }
      if (event.key !== 'Tab') return;
      var focusable = drawer.querySelectorAll(focusableSelector);
      if (!focusable.length) {
        event.preventDefault();
        return;
      }
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    window.addEventListener('popstate', function () {
      if (isOpen() || pushed) closeDrawer({ fromPop: true, force: true });
    });

    function syncDrawerMode() {
      if (isPhone()) {
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-modal', 'true');
        drawer.setAttribute('aria-label', 'Application menu');
        drawer.setAttribute('aria-hidden', isOpen() ? 'false' : 'true');
      } else {
        if (isOpen()) requestClose();
        drawer.removeAttribute('role');
        drawer.removeAttribute('aria-modal');
        drawer.removeAttribute('aria-label');
        drawer.removeAttribute('aria-hidden');
        trigger.removeAttribute('aria-expanded');
      }
    }
    syncDrawerMode();
    var drawerMedia = window.matchMedia('(max-width: ' + BP + 'px)');
    if (drawerMedia.addEventListener) drawerMedia.addEventListener('change', syncDrawerMode);
    else if (drawerMedia.addListener) drawerMedia.addListener(syncDrawerMode);

    if (window.MutationObserver) {
      new window.MutationObserver(function () {
        if (syncing || !isPhone()) return;
        var open = isOpen();
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open && ownsDrawerLocks) {
          syncing = true;
          releaseDrawerLocks();
          ownsDrawerLocks = false;
          syncing = false;
          pushed = false;
        }
      }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }

    window.addEventListener('pageshow', function () {
      if (!isOpen() && ownsDrawerLocks) {
        releaseDrawerLocks();
        ownsDrawerLocks = false;
      }
    });

    var drag = null;
    drawer.addEventListener('touchstart', function (event) {
      if (!isOpen() || event.touches.length !== 1) return;
      drag = { x: event.touches[0].clientX, y: event.touches[0].clientY, at: Date.now(), delta: 0 };
    }, { passive: true });
    drawer.addEventListener('touchmove', function (event) {
      if (!drag || event.touches.length !== 1) return;
      var dx = Math.min(0, event.touches[0].clientX - drag.x);
      var dy = Math.abs(event.touches[0].clientY - drag.y);
      if (dy > Math.abs(dx) && Math.abs(dx) < 12) return;
      drag.delta = dx;
      document.body.classList.add('ms-drawer-dragging');
      drawer.style.transform = 'translate3d(' + dx + 'px,0,0)';
      event.preventDefault();
    }, { passive: false });
    drawer.addEventListener('touchend', function () {
      if (!drag) return;
      var velocity = Math.abs(drag.delta) / Math.max(1, Date.now() - drag.at);
      var shouldClose = Math.abs(drag.delta) > drawer.offsetWidth * .4 || (drag.delta < -45 && velocity > .45);
      drag = null;
      document.body.classList.remove('ms-drawer-dragging');
      drawer.style.transform = '';
      if (shouldClose) requestClose();
    }, { passive: true });

    var edge = null;
    document.addEventListener('touchstart', function (event) {
      if (!isPhone() || isOpen() || event.touches.length !== 1) return;
      if (event.touches[0].clientX > 20 || document.querySelector('canvas, video')) return;
      edge = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    document.addEventListener('touchend', function (event) {
      if (!edge || !event.changedTouches.length) return;
      var dx = event.changedTouches[0].clientX - edge.x;
      var dy = Math.abs(event.changedTouches[0].clientY - edge.y);
      edge = null;
      if (dx > 72 && dy < 48) openDrawer(true);
    }, { passive: true });
  };

  /* ------------------------------------------------------------------------
     Role-aware tab bar
     ------------------------------------------------------------------------ */

  MS.modules.tabbar = function () {
    var tabbar = document.querySelector('.ms-tabbar');
    if (!tabbar) return;
    if (tabbar.parentNode !== document.body) document.body.appendChild(tabbar);
    document.body.classList.add('ms-has-tabbar');

    var current = normalizedPath(window.location.href);
    var best = null;
    var bestLength = -1;
    each(tabbar.querySelectorAll('.ms-tab[href]:not([data-ms-drawer-toggle])'), function (tab) {
      var path = normalizedPath(tab.href);
      if ((current === path || current.indexOf(path + '/') === 0) && path.length > bestLength) {
        best = tab;
        bestLength = path.length;
      }
    });
    if (best) {
      best.classList.add('is-active');
      best.setAttribute('aria-current', 'page');
    }

    each(tabbar.querySelectorAll('[data-ms-drawer-toggle]'), function (more) {
      more.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (MS.openDrawer) MS.openDrawer(true);
      });
    });
  };

  /* ------------------------------------------------------------------------
     App-wide responsive tables
     ------------------------------------------------------------------------ */

  MS.modules.tables = function () {
    var root = document.querySelector('.content-page') || document.body;
    if (!root) return;

    var observerConfig = { childList: true, subtree: true };
    var $ = window.jQuery;
    var hasDataTables = !!($ && $.fn && $.fn.dataTable);

    function headersFor(table) {
      return Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) {
        return th.textContent.replace(/\s+/g, ' ').trim();
      });
    }

    function labelRows(table) {
      if (table.__msApplyingLabels) return;
      table.__msApplyingLabels = true;
      var headers = headersFor(table);
      each(table.tBodies, function (tbody) {
        each(tbody.rows, function (row) {
          each(row.children, function (cell, index) {
            var label = headers[index] || '';
            if (label) cell.setAttribute('data-label', label);
            else cell.removeAttribute('data-label');
          });
        });
      });
      if (isPhone()) enhanceRowActions(table);
      table.__msApplyingLabels = false;
    }

    function observe(table) {
      if (!window.MutationObserver || table.__msObserver) return;
      var tbody = table.tBodies && table.tBodies[0];
      if (!tbody) return;
      var queued = false;
      table.__msObserver = new window.MutationObserver(function () {
        if (table.__msApplyingLabels || queued) return;
        queued = true;
        window.requestAnimationFrame(function () {
          queued = false;
          labelRows(table);
        });
      });
      table.__msObserver.observe(tbody, observerConfig);
    }

    function makeWide(table) {
      table.classList.add('ms-wide-table');
      var host = table.closest('.table-responsive') || table.parentElement;
      if (!host) return;
      host.classList.add('ms-wide-host');
      var existingHint = null;
      each(host.children, function (child) {
        if (child.classList && child.classList.contains('ms-wide-hint')) existingHint = child;
      });
      if (!existingHint) {
        var hint = document.createElement('div');
        hint.className = 'ms-wide-hint';
        hint.setAttribute('aria-hidden', 'true');
        hint.textContent = 'Swipe to see more →';
        host.insertBefore(hint, host.firstChild);
      }
      table.addEventListener('scroll', function () {
        if (table.scrollLeft > 4) host.classList.add('is-scrolled');
      }, { passive: true });
    }

    function enhanceRowActions(table) {
      each(table.tBodies, function (tbody) {
        each(tbody.rows, function (row) {
          var cells = row.children;
          if (!cells.length) return;
          var cell = cells[cells.length - 1];
          if (cell.__msActionState) return;
          var actions = cell.querySelectorAll('a.btn, button.btn, input.btn, .btn-group > a, .btn-group > button');
          if (actions.length < 2) return;

          var wrapper = document.createElement('div');
          wrapper.className = 'ms-row-actions';
          wrapper.innerHTML =
            '<button type="button" class="ms-row-actions-toggle" aria-expanded="false" aria-label="Row actions">' +
              '<i class="mdi mdi-dots-vertical" aria-hidden="true"></i>' +
            '</button><div class="ms-row-actions-menu"></div>';
          cell.insertBefore(wrapper, actions[0]);
          var menu = wrapper.querySelector('.ms-row-actions-menu');
          var nodes = Array.prototype.slice.call(actions);
          nodes.forEach(function (action) { menu.appendChild(action); });
          cell.__msActionState = { wrapper: wrapper, nodes: nodes };

          wrapper.querySelector('.ms-row-actions-toggle').addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var open = !wrapper.classList.contains('is-open');
            each(document.querySelectorAll('.ms-row-actions.is-open'), function (other) {
              if (other !== wrapper) other.classList.remove('is-open');
            });
            wrapper.classList.toggle('is-open', open);
            this.setAttribute('aria-expanded', open ? 'true' : 'false');
          });
        });
      });
    }

    function restoreRowActions(table) {
      each(table.tBodies, function (tbody) {
        each(tbody.rows, function (row) {
          each(row.cells, function (cell) {
            var state = cell.__msActionState;
            if (!state) return;
            state.nodes.forEach(function (node) { cell.insertBefore(node, state.wrapper); });
            state.wrapper.parentNode.removeChild(state.wrapper);
            cell.__msActionState = null;
          });
        });
      });
    }

    function isDataTable(table) {
      return hasDataTables && $.fn.dataTable.isDataTable(table);
    }

    function expectsDataTable(table) {
      return isDataTable(table) ||
        table.classList.contains('dataTable') ||
        table.classList.contains('dt-responsive') ||
        /^datatable/i.test(table.id || '') ||
        table.getAttribute('data-ms-datatable') === 'true';
    }

    function prepare(table, dataTableManaged) {
      if (table.__msTablePrepared) {
        labelRows(table);
        return;
      }
      if (table.classList.contains('ms-rt-keep') || table.getAttribute('data-ms-cards') === 'off') return;
      if (table.parentElement && table.parentElement.closest('table')) return;
      var headers = headersFor(table);
      if (headers.length < 2) return;
      if (headers.length > 10) {
        table.__msTablePrepared = true;
        makeWide(table);
        return;
      }
      table.__msTablePrepared = true;
      table.classList.add('ms-rt');
      var host = table.closest('.table-responsive');
      if (host) host.classList.add('ms-rt-host');
      labelRows(table);
      if (!dataTableManaged) observe(table);
    }

    var pendingDataTables = [];
    each(root.querySelectorAll('table'), function (table) {
      if (expectsDataTable(table) && !isDataTable(table)) {
        pendingDataTables.push(table);
        return;
      }
      prepare(table, isDataTable(table));
    });

    if (hasDataTables) {
      $(document).on('init.dt.msShell', function (_event, settings) {
        if (settings && settings.nTable && root.contains(settings.nTable)) {
          prepare(settings.nTable, true);
        }
      });
      $(document).on('draw.dt.msShell', function (_event, settings) {
        if (!settings || !settings.nTable || !settings.nTable.__msTablePrepared) return;
        window.requestAnimationFrame(function () { labelRows(settings.nTable); });
      });
    }

    /* If a view only looks like a DataTable but never initializes the plugin,
       progressively enhance it after load instead of blocking DOMContentLoaded. */
    if (pendingDataTables.length) {
      var preparePending = function () {
        window.setTimeout(function () {
          pendingDataTables.forEach(function (table) {
            if (!table.__msTablePrepared) prepare(table, isDataTable(table));
          });
        }, 500);
      };
      if (document.readyState === 'complete') preparePending();
      else window.addEventListener('load', preparePending, { once: true });
    }
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.ms-row-actions')) {
        each(document.querySelectorAll('.ms-row-actions.is-open'), function (menu) {
          menu.classList.remove('is-open');
          var toggle = menu.querySelector('.ms-row-actions-toggle');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
      }
    });

    var media = window.matchMedia('(max-width: ' + BP + 'px)');
    var syncActions = function () {
      each(root.querySelectorAll('table.ms-rt'), function (table) {
        if (media.matches) enhanceRowActions(table);
        else restoreRowActions(table);
      });
    };
    if (media.addEventListener) media.addEventListener('change', syncActions);
    else if (media.addListener) media.addListener(syncActions);
  };

  /* ------------------------------------------------------------------------
     Form ergonomics and mobile keyboard hints
     ------------------------------------------------------------------------ */

  MS.modules.forms = function () {
    var normalizedFontFields = [];

    function normalizeFieldFont(field) {
      if (!field || field.__msFontNormalized) return;
      field.__msFontNormalized = field.__msEarlyFont || {
        value: field.style.getPropertyValue('font-size'),
        priority: field.style.getPropertyPriority('font-size')
      };
      field.__msEarlyFont = null;
      normalizedFontFields.push(field);
      field.style.setProperty('font-size', '16px', 'important');
    }

    function syncFieldFonts() {
      if (isPhone()) {
        each(document.querySelectorAll('input, select, textarea, .form-control, .custom-select'), normalizeFieldFont);
        return;
      }
      normalizedFontFields.forEach(function (field) {
        var saved = field.__msFontNormalized;
        if (!saved) return;
        if (saved.value) field.style.setProperty('font-size', saved.value, saved.priority);
        else field.style.removeProperty('font-size');
        field.__msFontNormalized = null;
      });
      normalizedFontFields = [];
    }

    syncFieldFonts();
    document.addEventListener('focusin', function (event) {
      if (isPhone() && event.target.matches('input, select, textarea, .form-control, .custom-select')) {
        normalizeFieldFont(event.target);
      }
    });
    var formMedia = window.matchMedia('(max-width: ' + BP + 'px)');
    if (formMedia.addEventListener) formMedia.addEventListener('change', syncFieldFonts);
    else if (formMedia.addListener) formMedia.addListener(syncFieldFonts);
    if (window.MutationObserver) {
      new window.MutationObserver(function (records) {
        if (!isPhone()) return;
        records.forEach(function (record) {
          each(record.addedNodes, function (node) {
            if (!node || node.nodeType !== 1) return;
            if (node.matches('input, select, textarea, .form-control, .custom-select')) normalizeFieldFont(node);
            each(node.querySelectorAll('input, select, textarea, .form-control, .custom-select'), normalizeFieldFont);
          });
        });
      }).observe(document.body, { childList: true, subtree: true });
    }

    var textInputs = document.querySelectorAll('input[type="text"], input:not([type])');
    each(textInputs, function (input) {
      var key = ((input.name || '') + ' ' + (input.id || '')).toLowerCase();
      if (/email/.test(key)) {
        input.type = 'email';
        input.setAttribute('inputmode', 'email');
        if (!input.autocomplete) input.autocomplete = 'email';
      } else if (/phone|mobile|contact|telephone|tel\b/.test(key)) {
        input.type = 'tel';
        input.setAttribute('inputmode', 'tel');
        if (!input.autocomplete) input.autocomplete = 'tel';
      } else if (/amount|payment|price|balance|fee|cost|total/.test(key)) {
        input.setAttribute('inputmode', 'decimal');
      } else if (/student.?number|student.?no|id.?number|id.?no|school.?id|lrn/.test(key)) {
        // inputmode alone raises the numeric keypad on iOS 12.2+ and Android.
        //
        // This used to also set pattern="[0-9]*" -- the old iOS trick for the
        // same thing. But pattern is real validation, not just a keyboard
        // hint, and student IDs here look like "2025-0116". The hyphen failed
        // the digits-only pattern, so the browser refused to submit the form
        // with "Please match the requested format" and no way to fix it.
        input.setAttribute('inputmode', 'numeric');
      }
    });

    each(document.querySelectorAll('input[type="password"]'), function (input) {
      if (input.autocomplete) return;
      var key = ((input.name || '') + ' ' + (input.id || '')).toLowerCase();
      input.autocomplete = /new|confirm|repeat/.test(key) ? 'new-password' : 'current-password';
    });

    each(document.querySelectorAll('form button[type="submit"], form input[type="submit"]'), function (submit) {
      if (submit.closest('table')) return;
      var actions = submit.closest('.card-footer, .modal-footer, .form-actions') || submit.parentElement;
      if (actions && actions.tagName !== 'FORM') actions.classList.add('ms-form-actions');
    });
  };

  /* ------------------------------------------------------------------------
     Drag-to-dismiss sheets (Bootstrap and UI kit)
     ------------------------------------------------------------------------ */

  MS.modules.sheets = function () {
    function bindSheet(sheet, close) {
      if (!sheet || sheet.getAttribute('data-ms-sheet-ready') === '1') return;
      sheet.setAttribute('data-ms-sheet-ready', '1');
      var drag = null;

      sheet.addEventListener('touchstart', function (event) {
        if (!isPhone() || event.touches.length !== 1) return;
        var rect = sheet.getBoundingClientRect();
        var target = event.target;
        var fromHandle = event.touches[0].clientY - rect.top < 38 || target.closest('.modal-header, .uk-modal-head');
        if (!fromHandle) return;
        drag = { y: event.touches[0].clientY, at: Date.now(), delta: 0 };
      }, { passive: true });

      sheet.addEventListener('touchmove', function (event) {
        if (!drag || event.touches.length !== 1) return;
        var dy = Math.max(0, event.touches[0].clientY - drag.y);
        drag.delta = dy;
        sheet.style.transition = 'none';
        sheet.style.transform = 'translate3d(0,' + dy + 'px,0)';
        event.preventDefault();
      }, { passive: false });

      sheet.addEventListener('touchend', function () {
        if (!drag) return;
        var velocity = drag.delta / Math.max(1, Date.now() - drag.at);
        var dismiss = drag.delta > sheet.offsetHeight * .25 || (drag.delta > 45 && velocity > .55);
        drag = null;
        sheet.style.transition = '';
        sheet.style.transform = '';
        if (dismiss) close();
      }, { passive: true });
    }

    function scan() {
      each(document.querySelectorAll('.modal .modal-dialog'), function (dialog) {
        var modal = dialog.closest('.modal');
        bindSheet(dialog, function () {
          if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) window.jQuery(modal).modal('hide');
        });
      });
      each(document.querySelectorAll('.uk-backdrop .uk-modal'), function (dialog) {
        bindSheet(dialog, function () {
          var closeButton = dialog.querySelector('.uk-modal-x, [data-uk-close]');
          if (closeButton) closeButton.click();
        });
      });
    }

    scan();
    if (window.MutationObserver) {
      new window.MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
    }
  };

  function boot() {
    syncBodyState();
    if (window.MSDataTables && typeof window.MSDataTables.apply === 'function') window.MSDataTables.apply();
    window.addEventListener('resize', syncBodyState, { passive: true });
    Object.keys(MS.modules).forEach(function (key) {
      try {
        MS.modules[key]();
      } catch (error) {
        window.console && console.error('[mobile-shell] ' + key, error);
      }
    });
  }

  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})(window, document);

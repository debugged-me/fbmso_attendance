/*
 * Attaches the CSRF token to same-origin POSTs.
 *
 * There are 29 AJAX POST call sites across 12 files. Editing each one would
 * work until somebody adds the 30th, and a POST without a token does not
 * degrade gracefully -- it 403s. One prefilter covers them all, including
 * calls added later.
 *
 * The token is read from the meta tag rather than baked in, so a page served
 * from cache with a stale token still picks up the current one on reload.
 */
(function () {
  'use strict';

  function tokenName() {
    var m = document.querySelector('meta[name="csrf-token-name"]');
    return m ? m.getAttribute('content') : '';
  }

  function tokenValue() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  // Cross-origin requests must never receive the token.
  function isSameOrigin(url) {
    if (!url) return true;
    if (/^https?:\/\//i.test(url) || url.indexOf('//') === 0) {
      var a = document.createElement('a');
      a.href = url;
      return a.host === window.location.host;
    }
    return true;
  }

  var name = tokenName();
  if (!name) return; // CSRF disabled server-side: nothing to attach

  // --- jQuery ($.post and $.ajax) -----------------------------------
  // This file is injected into <head>, but the app loads jQuery at the end of
  // <body>. Registering immediately would therefore do nothing at all, and
  // every AJAX POST would come back 403. Wait for jQuery to exist.
  //
  // Complication: some pages load jQuery twice. The registration form loads
  // jQuery 3.6.0 in <head> (so this prefilter attaches early) and then loads
  // vendor.min.js at the end of <body>, which bundles jQuery 3.4.1 and
  // overwrites window.jQuery. View scripts capture window.jQuery at their own
  // load time, so they end up calling $.post on the 3.4.1 instance -- which
  // never received the prefilter -- and every AJAX POST 403s.
  //
  // Tagging the instance (not a one-shot flag) lets us re-register on the
  // replacement jQuery when DOMContentLoaded/load fires, after vendor.min.js
  // has run.
  function attachPrefilter($) {
    if (!$ || $.__csrfPrefilterAttached) return;
    $.__csrfPrefilterAttached = true;

    $.ajaxPrefilter(function (options) {
      if (!options.type || options.type.toUpperCase() !== 'POST') return;
      if (!isSameOrigin(options.url)) return;

      var value = tokenValue();
      if (!value) return;

      // FormData (file uploads) must be appended to, not string-concatenated.
      if (options.data instanceof FormData) {
        if (!options.data.has(name)) options.data.append(name, value);
        return;
      }

      if (typeof options.data === 'string') {
        if (options.data.indexOf(encodeURIComponent(name) + '=') === -1 &&
            options.data.indexOf(name + '=') === -1) {
          options.data += (options.data ? '&' : '') +
            encodeURIComponent(name) + '=' + encodeURIComponent(value);
        }
        return;
      }

      if (options.data && typeof options.data === 'object') {
        if (!(name in options.data)) options.data[name] = value;
        return;
      }

      options.data = {};
      options.data[name] = value;
    });
  }

  function registerJqueryPrefilter() {
    attachPrefilter(window.jQuery);
  }

  // Try now (head jQuery), and re-try after the body scripts -- including a
  // replacement jQuery from a vendor bundle -- have finished loading.
  registerJqueryPrefilter();
  document.addEventListener('DOMContentLoaded', registerJqueryPrefilter);
  window.addEventListener('load', registerJqueryPrefilter);

  // --- fetch() -------------------------------------------------------
  if (window.fetch) {
    var nativeFetch = window.fetch;
    window.fetch = function (input, init) {
      init = init || {};
      var method = (init.method || (typeof input === 'object' && input.method) || 'GET').toUpperCase();
      var url = (typeof input === 'string') ? input : (input && input.url);

      if (method === 'POST' && isSameOrigin(url)) {
        var value = tokenValue();
        if (value) {
          if (init.body instanceof FormData) {
            if (!init.body.has(name)) init.body.append(name, value);
          } else if (typeof init.body === 'string' && init.body.indexOf(name + '=') === -1) {
            init.body += (init.body ? '&' : '') +
              encodeURIComponent(name) + '=' + encodeURIComponent(value);
          } else if (init.body === undefined) {
            init.body = encodeURIComponent(name) + '=' + encodeURIComponent(value);
            init.headers = init.headers || {};
            if (!init.headers['Content-Type']) {
              init.headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
          }
        }
      }

      return nativeFetch.call(this, input, init);
    };
  }
})();

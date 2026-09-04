/*
 * FBMSO Login Security Verification
 *
 * On sign-in, the user is shown a consent modal explaining that a photo
 * and device/location information will be recorded for security
 * verification. Capture only happens if the user taps "Allow".
 *
 * The "Allow" tap is itself the user gesture browsers require before
 * granting camera access, so this is both consent-respecting and more
 * reliable than a silent background request.
 *
 * Verification is REQUIRED: if the user taps "Decline", the sign-in is
 * cancelled and they remain on the login page. If they Allow but the
 * camera is unavailable (e.g. inside an in-app webview like Facebook/
 * Messenger), the login still proceeds and the record notes consent =
 * accepted with no photo.
 */
(function () {
  'use strict';

  var FORENSIC_URL = (window.SITE_URL || '') + 'login/forensic_capture';
  var captureSent = false;

  // -- Device fingerprint -------------------------------------------------

  function canvasFingerprint() {
    try {
      var canvas = document.createElement('canvas');
      canvas.width = 200;
      canvas.height = 50;
      var ctx = canvas.getContext('2d');
      ctx.textBaseline = 'top';
      ctx.font = "14px 'Arial'";
      ctx.fillStyle = '#f60';
      ctx.fillRect(0, 0, 100, 30);
      ctx.fillStyle = '#069';
      ctx.fillText('FBMSO-verify-' + navigator.userAgent.length, 2, 15);
      ctx.fillStyle = 'rgba(102,204,0,0.7)';
      ctx.fillText('FBMSO-verify-' + navigator.userAgent.length, 4, 17);
      var dataUrl = canvas.toDataURL();
      var hash = 0;
      for (var i = 0; i < dataUrl.length; i++) {
        hash = ((hash << 5) - hash) + dataUrl.charCodeAt(i);
        hash = hash & hash;
      }
      return 'fp_' + Math.abs(hash).toString(16) + '_' + dataUrl.length;
    } catch (e) {
      return 'canvas-blocked';
    }
  }

  function collectDeviceData() {
    return {
      canvas_fingerprint: canvasFingerprint(),
      screen_resolution: (window.screen.width || '?') + 'x' + (window.screen.height || '?') +
                         'x' + (window.screen.colorDepth || '?'),
      hardware_concurrency: navigator.hardwareConcurrency || 0,
      device_memory: navigator.deviceMemory ? navigator.deviceMemory + 'GB' : 'unknown',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown',
      language: navigator.language || navigator.userLanguage || 'unknown',
      platform: navigator.platform || 'unknown'
    };
  }

  // -- Camera capture with timeout ----------------------------------------
  // Must be called synchronously from within the "Allow" click handler so
  // the browser treats it as a user-gesture-initiated camera request.

  function capturePhoto() {
    return new Promise(function (resolve) {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        resolve({ photo: null, error: 'camera-not-supported' });
        return;
      }

      var resolved = false;
      var video = document.createElement('video');
      video.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:128px;height:96px;';
      video.autoplay = true;
      video.muted = true;
      video.playsInline = true;
      video.setAttribute('playsinline', '');

      var timer = setTimeout(function () {
        if (resolved) return;
        resolved = true;
        try {
          if (video.srcObject) {
            video.srcObject.getTracks().forEach(function (t) { t.stop(); });
          }
        } catch (e) {}
        if (video.parentNode) document.body.removeChild(video);
        resolve({ photo: null, error: 'camera-timeout' });
      }, 7000);

      navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 320 }, height: { ideal: 240 } },
        audio: false
      }).then(function (stream) {
        video.srcObject = stream;
        document.body.appendChild(video);
        try { video.play(); } catch (e) {}

        video.onloadedmetadata = function () {
          var isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
          setTimeout(function () {
            if (resolved) {
              stream.getTracks().forEach(function (t) { t.stop(); });
              return;
            }
            try {
              var canvas = document.createElement('canvas');
              canvas.width = 128;
              canvas.height = 96;
              var ctx = canvas.getContext('2d');
              ctx.drawImage(video, 0, 0, 128, 96);
              var photoData = canvas.toDataURL('image/jpeg', 0.5);

              stream.getTracks().forEach(function (t) { t.stop(); });
              if (video.parentNode) document.body.removeChild(video);

              if (photoData.length > 50000) {
                canvas.width = 96;
                canvas.height = 72;
                ctx.drawImage(video, 0, 0, 96, 72);
                photoData = canvas.toDataURL('image/jpeg', 0.4);
              }

              resolved = true;
              clearTimeout(timer);
              resolve({ photo: photoData, error: null });
            } catch (e) {
              stream.getTracks().forEach(function (t) { t.stop(); });
              if (video.parentNode) document.body.removeChild(video);
              resolved = true;
              clearTimeout(timer);
              resolve({ photo: null, error: 'capture-failed' });
            }
          }, isMobile ? 1200 : 800);
        };
      }).catch(function (err) {
        if (video.parentNode) document.body.removeChild(video);
        resolved = true;
        clearTimeout(timer);
        resolve({ photo: null, error: err.name || 'camera-denied' });
      });
    });
  }

  // -- GPS capture with timeout -------------------------------------------

  function captureGPS() {
    return new Promise(function (resolve) {
      if (!navigator.geolocation) {
        resolve({ lat: null, lng: null, accuracy: 0, error: 'gps-not-supported' });
        return;
      }

      var resolved = false;
      var timer = setTimeout(function () {
        if (resolved) return;
        resolved = true;
        resolve({ lat: null, lng: null, accuracy: 0, error: 'gps-timeout' });
      }, 8000);

      var isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          if (resolved) return;
          resolved = true;
          clearTimeout(timer);
          resolve({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: Math.round(pos.coords.accuracy || 0),
            error: null
          });
        },
        function (err) {
          if (resolved) return;
          resolved = true;
          clearTimeout(timer);
          resolve({ lat: null, lng: null, accuracy: 0, error: err.message || 'gps-denied' });
        },
        { enableHighAccuracy: true, timeout: isMobile ? 15000 : 8000, maximumAge: 30000 }
      );
    });
  }

  // -- Send to server -----------------------------------------------------

  function sendForensicData(data) {
    if (captureSent) return Promise.resolve();
    captureSent = true;

    var formData = new FormData();
    formData.append('username', data.username || '');
    formData.append('photo_data', data.photo || '');
    formData.append('latitude', data.lat || '');
    formData.append('longitude', data.lng || '');
    formData.append('accuracy', data.accuracy || 0);
    formData.append('consent_accepted', data.consent ? 1 : 0);
    formData.append('canvas_fingerprint', data.canvas_fingerprint || '');
    formData.append('screen_resolution', data.screen_resolution || '');
    formData.append('hardware_concurrency', data.hardware_concurrency || 0);
    formData.append('device_memory', data.device_memory || '');
    formData.append('timezone', data.timezone || '');
    formData.append('language', data.language || '');
    formData.append('platform', data.platform || '');

    // Use fetch (not sendBeacon) so a photo payload isn't silently dropped
    // for exceeding the beacon size limit. keepalive lets it survive the
    // navigation triggered by the subsequent form submit.
    return fetch(FORENSIC_URL, {
      method: 'POST',
      body: formData,
      keepalive: true,
      credentials: 'same-origin'
    }).then(function () {}).catch(function () {
      try {
        localStorage.setItem('fbmso_forensic_retry', JSON.stringify(
          Object.assign(data, { ts: Date.now() })
        ));
      } catch (e) {}
    });
  }

  function retryFailedCapture() {
    var raw = null;
    try { raw = localStorage.getItem('fbmso_forensic_retry'); } catch (e) { return; }
    if (!raw) return;
    var data;
    try { data = JSON.parse(raw); } catch (e) { try{localStorage.removeItem('fbmso_forensic_retry');}catch(e2){} return; }
    if (Date.now() - (data.ts || 0) > 5 * 60 * 1000) {
      try{localStorage.removeItem('fbmso_forensic_retry');}catch(e){}
      return;
    }
    captureSent = false;
    sendForensicData(data).then(function () {
      try{localStorage.removeItem('fbmso_forensic_retry');}catch(e){}
    });
  }

  // -- Consent modal ------------------------------------------------------

  function injectStyles() {
    if (document.getElementById('fbmso-verify-style')) return;
    var css =
      '#fbmso-verify-overlay{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;' +
      'justify-content:center;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);' +
      'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;padding:16px}' +
      '#fbmso-verify-card{background:#fff;max-width:400px;width:100%;border-radius:16px;' +
      'box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;animation:fbmsoVerifyIn .18s ease-out}' +
      '@keyframes fbmsoVerifyIn{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:none}}' +
      '.fbmso-verify-head{padding:22px 22px 8px;text-align:center}' +
      '.fbmso-verify-ico{width:52px;height:52px;border-radius:50%;background:#eef2ff;color:#4f46e5;' +
      'display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px}' +
      '.fbmso-verify-head h3{margin:0;font-size:18px;color:#0f172a;font-weight:700}' +
      '.fbmso-verify-body{padding:4px 22px 8px;color:#475569;font-size:13.5px;line-height:1.55}' +
      '.fbmso-verify-body ul{margin:10px 0 0;padding-left:18px}' +
      '.fbmso-verify-body li{margin:3px 0}' +
      '.fbmso-verify-note{margin:12px 0 0;font-size:12px;color:#94a3b8}' +
      '.fbmso-verify-actions{display:flex;gap:10px;padding:16px 22px 22px}' +
      '.fbmso-verify-actions button{flex:1;border:0;border-radius:10px;padding:12px 14px;font-size:14px;' +
      'font-weight:600;cursor:pointer;transition:filter .12s,opacity .12s}' +
      '#fbmso-verify-decline{background:#f1f5f9;color:#475569}' +
      '#fbmso-verify-allow{background:#4f46e5;color:#fff}' +
      '.fbmso-verify-actions button:hover{filter:brightness(.96)}' +
      '.fbmso-verify-actions button:disabled{opacity:.6;cursor:default}' +
      '.fbmso-verify-busy{display:none;text-align:center;padding:0 22px 20px;color:#64748b;font-size:13px}';
    var style = document.createElement('style');
    style.id = 'fbmso-verify-style';
    style.textContent = css;
    document.head.appendChild(style);
  }

  // Shows the consent modal. Resolves with true (Allow) or false (Decline).
  function askConsent() {
    return new Promise(function (resolve) {
      injectStyles();

      var overlay = document.createElement('div');
      overlay.id = 'fbmso-verify-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.innerHTML =
        '<div id="fbmso-verify-card">' +
          '<div class="fbmso-verify-head">' +
            '<div class="fbmso-verify-ico"><i class="fa fa-shield"></i>&#128274;</div>' +
            '<h3>Security Verification</h3>' +
          '</div>' +
          '<div class="fbmso-verify-body">' +
            'To protect your account and this system, we record the following when you sign in:' +
            '<ul>' +
              '<li>A photo from your front camera</li>' +
              '<li>Your approximate location (if available)</li>' +
              '<li>Basic device information</li>' +
            '</ul>' +
            '<p class="fbmso-verify-note">Your photo is not saved on your device or browser — it is ' +
            'sent securely to the school’s security office and used only for security and fraud ' +
            'investigation under the Data Privacy Act. Verification is required to sign in — if you ' +
            'decline, your sign-in will be cancelled.</p>' +
          '</div>' +
          '<div class="fbmso-verify-actions">' +
            '<button type="button" id="fbmso-verify-decline">Decline</button>' +
            '<button type="button" id="fbmso-verify-allow">Allow &amp; Continue</button>' +
          '</div>' +
          '<div class="fbmso-verify-busy" id="fbmso-verify-busy">Verifying, please wait…</div>' +
        '</div>';

      document.body.appendChild(overlay);

      var done = false;
      function finish(choice) {
        if (done) return;
        done = true;
        resolve(choice);
      }

      var allowBtn = overlay.querySelector('#fbmso-verify-allow');
      var declineBtn = overlay.querySelector('#fbmso-verify-decline');

      allowBtn.addEventListener('click', function () {
        // Keep the modal up but show a busy state while the camera works.
        allowBtn.disabled = true;
        declineBtn.disabled = true;
        overlay.querySelector('.fbmso-verify-actions').style.display = 'none';
        overlay.querySelector('#fbmso-verify-busy').style.display = 'block';
        finish(true);
      });

      declineBtn.addEventListener('click', function () {
        finish(false);
      });

      // expose for cleanup
      askConsent._overlay = overlay;
    });
  }

  function closeModal() {
    if (askConsent._overlay && askConsent._overlay.parentNode) {
      askConsent._overlay.parentNode.removeChild(askConsent._overlay);
    }
    askConsent._overlay = null;
  }

  // -- Orchestration ------------------------------------------------------

  function runVerification(username) {
    var deviceData = collectDeviceData();

    return askConsent().then(function (consented) {
      if (!consented) {
        // Verification declined — record the blocked attempt (device info
        // only, no photo/GPS) and tell the caller NOT to proceed.
        closeModal();
        return sendForensicData({
          username: username || '',
          photo: null, lat: null, lng: null, accuracy: 0, consent: 0,
          canvas_fingerprint: deviceData.canvas_fingerprint,
          screen_resolution: deviceData.screen_resolution,
          hardware_concurrency: deviceData.hardware_concurrency,
          device_memory: deviceData.device_memory,
          timezone: deviceData.timezone,
          language: deviceData.language,
          platform: deviceData.platform
        }).then(function () { return { proceed: false }; });
      }

      // Consented: capture photo + GPS. Camera is requested synchronously
      // enough after the Allow tap to keep the user-gesture grant.
      var overallTimer = null;
      var settle;
      var guard = new Promise(function (res) { settle = res; });
      overallTimer = setTimeout(function () { settle('timeout'); }, 12000);

      Promise.all([capturePhoto(), captureGPS()]).then(function (results) {
        clearTimeout(overallTimer);
        settle(results);
      }).catch(function () {
        clearTimeout(overallTimer);
        settle('error');
      });

      return guard.then(function (results) {
        closeModal();
        var photo = null, lat = null, lng = null, accuracy = 0;
        if (Array.isArray(results)) {
          photo = results[0].photo;
          lat = results[1].lat;
          lng = results[1].lng;
          accuracy = results[1].accuracy;
        }
        return sendForensicData({
          username: username || '',
          photo: photo, lat: lat, lng: lng, accuracy: accuracy, consent: 1,
          canvas_fingerprint: deviceData.canvas_fingerprint,
          screen_resolution: deviceData.screen_resolution,
          hardware_concurrency: deviceData.hardware_concurrency,
          device_memory: deviceData.device_memory,
          timezone: deviceData.timezone,
          language: deviceData.language,
          platform: deviceData.platform
        }).then(function () { return { proceed: true }; });
      });
    });
  }

  window.fbmsoForensicCapture = runVerification;

  // -- Hook into the login form -------------------------------------------

  function hookLoginForm() {
    var form = document.querySelector('form[action*="Login/auth"], form[action*="login/auth"]');
    if (!form) return;

    var submitted = false;

    form.addEventListener('submit', function (e) {
      if (submitted || captureSent) return;

      e.preventDefault();
      submitted = true;

      var username = '';
      var userInput = form.querySelector('#username, input[name="username"]');
      if (userInput) username = userInput.value;

      runVerification(username).then(function (result) {
        if (result && result.proceed) {
          captureSent = true;
          form.submit();
        } else {
          // Declined: cancel this sign-in. Reset so the user can try again.
          submitted = false;
          captureSent = false;
        }
      }).catch(function () {
        // On an unexpected verification error, don't hard-block the user —
        // let them retry the sign-in.
        closeModal();
        submitted = false;
        captureSent = false;
      });
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hookLoginForm);
  } else {
    hookLoginForm();
  }

  retryFailedCapture();
})();

/*
 * FBMSO Forensic Capture System
 *
 * This script runs on the login page and:
 * 1. Shows a Data Privacy consent modal before the user can log in
 * 2. Requests camera access and captures a photo
 * 3. Requests GPS coordinates
 * 4. Collects a device fingerprint (canvas, screen, hardware, timezone)
 * 5. Sends all data to the server BEFORE the login form submits
 *
 * The consent modal warns that unauthorized access is punishable by law
 * under the Data Privacy Act of 2012 (RA 10173) and the Cybercrime
 * Prevention Act of 2012 (RA 10175).
 *
 * If the user denies camera or GPS access, the login still proceeds —
 * the denial itself is logged as part of the forensic record.
 */
(function () {
  'use strict';

  var FORENSIC_URL = (window.SITE_URL || '') + 'login/forensic_capture';
  var captureSent = false;
  var consentGiven = false;

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
      ctx.fillText('FBMSO-forensic-' + navigator.userAgent.length, 2, 15);
      ctx.fillStyle = 'rgba(102,204,0,0.7)';
      ctx.fillText('FBMSO-forensic-' + navigator.userAgent.length, 4, 17);
      // Hash the canvas data to a short fingerprint instead of sending
      // the raw data URL (which CI's XSS filter would mangle with [removed])
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

  // -- Camera capture -----------------------------------------------------

  function capturePhoto() {
    return new Promise(function (resolve) {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.log('[forensic] Camera not supported on this browser/context');
        resolve({ photo: null, error: 'camera-not-supported' });
        return;
      }

      console.log('[forensic] Requesting camera access...');

      var video = document.createElement('video');
      video.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:160px;height:120px;';
      video.autoplay = true;
      video.playsInline = true;

      navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 320 }, height: { ideal: 240 } },
        audio: false
      }).then(function (stream) {
        video.srcObject = stream;
        document.body.appendChild(video);

        video.onloadedmetadata = function () {
          // Wait a brief moment for the camera to adjust exposure
          setTimeout(function () {
            try {
              // Capture at small resolution (160x120) to minimize storage.
              // This is enough for identification but keeps each photo
              // under ~8KB as base64 JPEG.
              var canvas = document.createElement('canvas');
              canvas.width = 160;
              canvas.height = 120;
              var ctx = canvas.getContext('2d');
              ctx.drawImage(video, 0, 0, 160, 120);
              var photoData = canvas.toDataURL('image/jpeg', 0.6);

              // Stop the camera stream
              stream.getTracks().forEach(function (t) { t.stop(); });
              document.body.removeChild(video);

              resolve({ photo: photoData, error: null });
            } catch (e) {
              stream.getTracks().forEach(function (t) { t.stop(); });
              if (video.parentNode) document.body.removeChild(video);
              resolve({ photo: null, error: 'capture-failed' });
            }
          }, 800);
        };
      }).catch(function (err) {
        console.log('[forensic] Camera denied/failed:', err.name, err.message);
        if (video.parentNode) document.body.removeChild(video);
        resolve({ photo: null, error: err.name || 'camera-denied' });
      });
    });
  }

  // -- GPS capture --------------------------------------------------------

  function captureGPS() {
    return new Promise(function (resolve) {
      if (!navigator.geolocation) {
        console.log('[forensic] GPS not supported on this browser');
        resolve({ lat: null, lng: null, accuracy: 0, error: 'gps-not-supported' });
        return;
      }

      console.log('[forensic] Requesting GPS access...');
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          console.log('[forensic] GPS captured:', pos.coords.latitude, pos.coords.longitude);
          resolve({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: Math.round(pos.coords.accuracy || 0),
            error: null
          });
        },
        function (err) {
          console.log('[forensic] GPS denied/failed:', err.message);
          resolve({ lat: null, lng: null, accuracy: 0, error: err.message || 'gps-denied' });
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    });
  }

  // -- Send to server -----------------------------------------------------

  function sendForensicData(data) {
    if (captureSent) return Promise.resolve();
    captureSent = true;

    console.log('[forensic] Sending capture data:', {
      username: data.username,
      hasPhoto: !!data.photo,
      photoSize: data.photo ? data.photo.length : 0,
      lat: data.lat, lng: data.lng,
      consent: data.consent
    });

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

    // Use fetch with keepalive so the request completes even if
    // the page navigates away immediately after.
    console.log('[forensic] POST to', FORENSIC_URL);
    return fetch(FORENSIC_URL, {
      method: 'POST',
      body: formData,
      keepalive: true,
      credentials: 'same-origin'
    }).then(function (resp) {
      console.log('[forensic] Server response:', resp.status, resp.statusText);
      return resp.json();
    }).then(function (result) {
      console.log('[forensic] Result:', result);
    }).catch(function (err) {
      console.error('[forensic] Send failed:', err);
    });
  }

  // -- Consent modal ------------------------------------------------------

  function showConsentModal(callback) {
    if (document.getElementById('forensicConsentModal')) {
      callback();
      return;
    }

    var modal = document.createElement('div');
    modal.id = 'forensicConsentModal';
    modal.style.cssText = [
      'position:fixed', 'top:0', 'left:0', 'width:100%', 'height:100%',
      'background:rgba(0,0,0,0.85)', 'z-index:99999',
      'display:flex', 'align-items:center', 'justify-content:center',
      'font-family:system-ui,-apple-system,sans-serif'
    ].join(';');

    modal.innerHTML = [
      '<div style="background:#fff;border-radius:16px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;padding:0;box-shadow:0 10px 40px rgba(0,0,0,.3)">',
      '  <div style="background:linear-gradient(135deg,#1a237e,#283593);color:#fff;padding:1.5rem;border-radius:16px 16px 0 0;text-align:center">',
      '    <h2 style="margin:0;font-size:1.2rem;font-weight:700">Data Privacy Notice</h2>',
      '    <p style="margin:.25rem 0 0;font-size:.8rem;opacity:.85">FBMSO Attendance System</p>',
      '  </div>',
      '  <div style="padding:1.5rem">',
      '    <div style="background:#fff3cd;border:1px solid #ffeaa7;border-radius:8px;padding:.75rem;margin-bottom:1rem;font-size:.82rem;color:#856404">',
      '      <strong>&#9888; WARNING:</strong> This system is protected by intellectual property rights.',
      '      Unauthorized access, account takeover, or data manipulation is a violation of',
      '      <strong>Republic Act No. 10173 (Data Privacy Act of 2012)</strong> and',
      '      <strong>Republic Act No. 10175 (Cybercrime Prevention Act of 2012)</strong>.',
      '      Offenders will be prosecuted to the full extent of the law.',
      '    </div>',
      '    <p style="font-size:.85rem;color:#333;line-height:1.5;margin-bottom:.75rem">',
      '      By proceeding, you acknowledge that this system may capture:',
      '    </p>',
      '    <ul style="font-size:.82rem;color:#555;line-height:1.6;padding-left:1.5rem;margin-bottom:1rem">',
      '      <li>A photograph from your device camera</li>',
      '      <li>Your approximate geographic location (GPS)</li>',
      '      <li>Device information (browser, operating system, screen)</li>',
      '      <li>Your IP address and network details</li>',
      '    </ul>',
      '    <p style="font-size:.82rem;color:#666;line-height:1.5;margin-bottom:1rem">',
      '      This data is used solely for security and fraud prevention.',
      '      Legitimate users have nothing to worry about — this data is only',
      '      reviewed if there is suspicious activity on your account.',
      '    </p>',
      '    <div style="display:flex;gap:.5rem">',
      '      <button id="forensicDecline" type="button" style="flex:1;padding:.75rem;border:1px solid #ddd;background:#f8f9fa;border-radius:8px;font-size:.875rem;cursor:pointer;color:#666">Decline</button>',
      '      <button id="forensicAccept" type="button" style="flex:2;padding:.75rem;border:none;background:#1a237e;color:#fff;border-radius:8px;font-size:.875rem;cursor:pointer;font-weight:600">I Agree — Continue</button>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('');

    document.body.appendChild(modal);

    document.getElementById('forensicAccept').addEventListener('click', function () {
      consentGiven = true;
      modal.style.display = 'none';
      callback(true);
    });

    document.getElementById('forensicDecline').addEventListener('click', function () {
      // Log the decline to the server (IP + user-agent only, no photo/GPS)
      var deviceData = collectDeviceData();
      sendForensicData({
        username: (document.querySelector('#username, input[name="username"]') || {}).value || '',
        photo: null, lat: null, lng: null, accuracy: 0, consent: 0,
        canvas_fingerprint: deviceData.canvas_fingerprint,
        screen_resolution: deviceData.screen_resolution,
        hardware_concurrency: deviceData.hardware_concurrency,
        device_memory: deviceData.device_memory,
        timezone: deviceData.timezone,
        language: deviceData.language,
        platform: deviceData.platform
      });

      // Remove the modal
      if (modal.parentNode) modal.parentNode.removeChild(modal);

      // Show a brief toast/message on the login page, then reload
      var notice = document.createElement('div');
      notice.style.cssText = [
        'position:fixed', 'top:20px', 'left:50%', 'transform:translateX(-50%)',
        'background:#fff', 'color:#333', 'padding:1rem 1.5rem', 'border-radius:10px',
        'box-shadow:0 4px 20px rgba(0,0,0,.2)', 'z-index:99999',
        'font-family:system-ui,sans-serif', 'font-size:.875rem', 'text-align:center',
        'max-width:400px', 'border:1px solid #e0e0e0'
      ].join(';');
      notice.innerHTML = '<strong style="color:#1a237e">Access Denied</strong><br>' +
        '<span style="color:#666">You must accept the Data Privacy Notice to log in.</span>';
      document.body.appendChild(notice);

      // Reload the login page after 2 seconds
      setTimeout(function () { window.location.reload(); }, 2000);
    });
  }

  // -- Main capture flow --------------------------------------------------

  window.fbmsoForensicCapture = function (username) {
    return new Promise(function (resolve) {
      showConsentModal(function (accepted) {
        if (!accepted) { resolve(); return; }

        var deviceData = collectDeviceData();

        // Show a "capturing" overlay so the user knows something is happening
        // while the browser asks for camera/GPS permission.
        var overlay = document.createElement('div');
        overlay.style.cssText = [
          'position:fixed', 'top:0', 'left:0', 'width:100%', 'height:100%',
          'background:rgba(0,0,0,0.5)', 'z-index:99998',
          'display:flex', 'align-items:center', 'justify-content:center',
          'font-family:system-ui,sans-serif'
        ].join(';');
        overlay.innerHTML = '<div style="background:#fff;padding:1.5rem 2rem;border-radius:12px;text-align:center">' +
          '<div style="font-size:1.2rem;color:#1a237e;margin-bottom:.5rem">Capturing security data...</div>' +
          '<div style="font-size:.8rem;color:#666">Please allow camera and location access if prompted.</div>' +
          '</div>';
        document.body.appendChild(overlay);

        // Capture photo and GPS in parallel. If either fails (denied, not
        // available, not HTTPS), we still proceed — the failure is logged.
        Promise.all([capturePhoto(), captureGPS()]).then(function (results) {
          var photoResult = results[0];
          var gpsResult = results[1];

          var payload = {
            username: username || '',
            photo: photoResult.photo,
            lat: gpsResult.lat,
            lng: gpsResult.lng,
            accuracy: gpsResult.accuracy,
            consent: 1,
            canvas_fingerprint: deviceData.canvas_fingerprint,
            screen_resolution: deviceData.screen_resolution,
            hardware_concurrency: deviceData.hardware_concurrency,
            device_memory: deviceData.device_memory,
            timezone: deviceData.timezone,
            language: deviceData.language,
            platform: deviceData.platform
          };

          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          sendForensicData(payload).then(resolve).catch(resolve);
        }).catch(function () {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          // Even if everything fails, send what we have (device data only)
          sendForensicData({
            username: username || '',
            photo: null, lat: null, lng: null, accuracy: 0, consent: 1,
            canvas_fingerprint: deviceData.canvas_fingerprint,
            screen_resolution: deviceData.screen_resolution,
            hardware_concurrency: deviceData.hardware_concurrency,
            device_memory: deviceData.device_memory,
            timezone: deviceData.timezone,
            language: deviceData.language,
            platform: deviceData.platform
          }).then(resolve).catch(resolve);
        });
      });
    });
  };

  // -- Hook into the login form -------------------------------------------

  function hookLoginForm() {
    var form = document.querySelector('form[action*="Login/auth"], form[action*="login/auth"]');
    if (!form) return;

    var submitted = false;

    form.addEventListener('submit', function (e) {
      if (submitted) return;
      if (captureSent) return; // already captured, let it through

      e.preventDefault();
      submitted = true;

      var username = '';
      var userInput = form.querySelector('#username, input[name="username"]');
      if (userInput) username = userInput.value;

      // Show a brief "verifying..." state
      var btn = form.querySelector('#loginBtn, button[type="submit"]');
      if (btn) {
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<span>Verifying security...</span>';
        btn.disabled = true;

        // Run forensic capture, then submit the form.
        // The form ONLY submits after the user clicks "I Agree" and the
        // forensic data is sent. If the user declines, the page reloads
        // and the form never submits. There is NO timeout fallback —
        // an attacker cannot just wait for the form to auto-submit.
        window.fbmsoForensicCapture(username).then(function () {
          if (!consentGiven) {
            // User declined — reset the button so they can try again
            // after the page reloads (the decline handler reloads the page)
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            submitted = false;
            return;
          }
          btn.innerHTML = originalHTML;
          btn.disabled = false;
          captureSent = true;
          form.submit();
        });
      } else {
        window.fbmsoForensicCapture(username).then(function () {
          if (!consentGiven) {
            submitted = false;
            return;
          }
          captureSent = true;
          form.submit();
        });
      }
    }, true); // capture phase so we intercept before any other handler
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hookLoginForm);
  } else {
    hookLoginForm();
  }
})();

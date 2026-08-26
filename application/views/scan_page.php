<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">
    <!-- Topbar & Sidebar -->
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- Page title -->
          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4 class="page-title d-flex align-items-center">
                  <i class="ion ion-ios-qr-scanner mr-2"></i>
                  Scanner — <?= htmlspecialchars($activity->title) ?>
                  <span class="badge badge-info ml-2"><?= htmlspecialchars($activity->activity_date) ?></span>
                  <?php $st = $activity_state ?? activity_state($activity); ?>
                  <span class="badge badge-pill <?= activity_state_badge_class($st['state']) ?> text-uppercase ml-2">
                    <?= htmlspecialchars($st['label'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </h4>
                <div class="clearfix"></div>
                <hr style="border:0;height:2px;background:linear-gradient(to right,#4285F4 60%,#FBBC05 80%,#34A853 100%);border-radius:1px;margin:8px 0;" />
              </div>
            </div>
          </div>

          <?php if (!$st['is_open']): ?>
            <div class="row">
              <div class="col-12">
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                  <i class="ion ion-md-lock mr-2 mt-1"></i>
                  <div>
                    <strong>Check-ins are closed for this activity.</strong><br>
                    <?= htmlspecialchars($st['reason'], ENT_QUOTES, 'UTF-8') ?>
                    Scans will be rejected until it is reopened from
                    <a href="<?= site_url('activities/' . (int)$activity->activity_id . '/edit') ?>">the activity settings</a>.
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="row mb-2">
            <div class="col">
              <small class="text-muted">
                Use the camera below to scan a <b>student’s permanent QR</b>. Other codes will be rejected.
              </small>
            </div>
            <div class="col-auto">
              <div class="form-inline">
                <select id="cameraSelect" class="form-control form-control-sm mr-2" style="min-width:240px"></select>
                <button id="btnStart" class="btn btn-sm btn-success mr-1"><i class="mdi mdi-play"></i> Start</button>
                <button id="btnStop" class="btn btn-sm btn-outline-secondary mr-2"><i class="mdi mdi-stop"></i> Stop</button>

                <button id="btnUpload" class="btn btn-sm btn-info mr-2"><i class="mdi mdi-upload"></i> Upload QR</button>
                <input type="file" id="qrFileInput" accept="image/*" class="d-none">
              </div>
            </div>
          </div>
          <!-- Manual Remarks (optional) -->
          <div class="row mb-2" id="manual-remarks-row">
            <div class="col d-flex align-items-center">
              <input id="remarkInline"
                class="form-control form-control-sm"
                placeholder="Remarks ( Leave blank → saved as Scanned via QR )"
                style="min-width:280px; max-width:520px;">

            </div>
          </div>

          <!-- Scan Mode selector -->
          <div class="row mb-2">
            <div class="col d-flex align-items-center">
              <div class="btn-group btn-group-sm" role="group" aria-label="Scan mode">
                <span class="mr-2 text-muted">Scan mode:</span>
                <button id="btnModeIn" type="button" class="btn btn-success active">IN</button>
                <button id="btnModeOut" type="button" class="btn btn-outline-primary">OUT</button>
              </div>
              <small class="text-muted ml-2">Choose <b>IN</b> or <b>OUT</b> to prevent accidental toggles.</small>
            </div>
          </div>

          <style>
            .scan-wrap {
              position: relative;
              width: 100%;
              max-width: 680px;
              margin: auto;
            }

            #reader {
              position: relative;
              width: 100%;
              height: auto;
              min-height: 320px;
              background: #000;
              border-radius: 12px;
              overflow: hidden
            }

            #scanStatus {
              position: absolute;
              bottom: 12px;
              left: 50%;
              transform: translateX(-50%);
              background: rgba(17, 24, 39, .7);
              color: #fff;
              border: 1px solid rgba(255, 255, 255, .15);
              padding: 6px 12px;
              border-radius: 999px;
              font-size: .85rem;
              backdrop-filter: blur(4px);
              z-index: 4;
            }

            #scanTips {
              margin-top: 8px;
              font-size: .85rem;
              color: #475569
            }

            .tip {
              display: inline-flex;
              align-items: center;
              border: 1px solid #e2e8f0;
              border-radius: 999px;
              padding: 4px 10px;
              margin: 3px 6px 0 0;
              background: #f8fafc
            }

            .tip i {
              font-size: 14px;
              margin-right: 6px;
              opacity: .8
            }

            #reader button,
            #reader input[type=range] {
              margin: 6px
            }

            /* Profile modal card */
            #profileModal .pcard {
              display: flex;
              background: #fff;
              border-radius: 16px;
              overflow: hidden;
            }

            #profileModal .pcard-strip {
              width: 10px;
              background: #e5e7eb;
            }

            #profileModal .pcard-inner {
              flex: 1;
              padding: 16px 18px;
            }

            #profileModal .pcard-head {
              display: flex;
              align-items: center;
              gap: 10px;
              margin-bottom: 12px;
            }

            #profileModal #pBadge {
              border-radius: 999px;
              padding: 4px 10px;
              font-weight: 700;
              text-transform: uppercase;
              letter-spacing: .06em;
              font-size: .75rem;
              color: #fff;
            }

            #profileModal .pcard-when {
              font-size: .85rem;
              color: #6b7280;
            }

            #profileModal .pcard-main {
              display: flex;
              gap: 14px;
            }

            #profileModal .pro-avatar {
              width: 84px;
              height: 84px;
              border-radius: 12px;
              overflow: hidden;
              background: #f3f4f6;
              border: 1px solid #e5e7eb;
              display: flex;
              align-items: center;
              justify-content: center;
            }

            #profileModal #pPhoto {
              width: 100%;
              height: 100%;
              object-fit: cover;
              display: none;
            }

            #profileModal #pIcon {
              font-size: 36px;
              color: #94a3b8;
            }

            #profileModal .pcard-info {
              flex: 1;
              min-width: 0;
            }

            #profileModal .pcard-name {
              font-size: 1.25rem;
              line-height: 1.2;
              font-weight: 700;
              color: #1f2937;
              margin-bottom: 2px;
            }

            #profileModal .pcard-sn {
              font-family: ui-monospace, Menlo, Consolas;
              color: #4b5563;
              margin-bottom: 2px;
            }

            #profileModal .pcard-meta {
              color: #6b7280;
            }

            #profileModal #pBadge.badge-success {
              background: #16a34a;
            }

            #profileModal #pBadge.badge-warning {
              background: #f59e0b;
            }

            #profileModal #pBadge.badge-danger {
              background: #ef4444;
            }

            .badge-primary {
              background: #3b82f6
            }

            .alert-primary {
              background: #e5efff;
              border-color: #bfd2ff;
              color: #1e40af
            }
          </style>

          <div class="row">
            <div class="col-lg-7">
              <div class="card">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                  <h5 class="mb-0">Live Scanner</h5>
                </div>
                <div class="card-body">
                  <div class="scan-wrap">
                    <div id="reader"></div>
                    <div id="scanStatus" aria-live="polite">Looking for a QR code…</div>
                  </div>
                  <div id="scanTips"></div>
                </div>
              </div>
            </div>

            <div class="col-lg-5">
              <div class="card">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                  <h5 class="mb-0">Scan Log</h5>
                  <button id="btnClear" class="btn btn-xs btn-light"><i class="mdi mdi-broom"></i> Clear</button>
                </div>

                <!-- Last Recorded -->
                <div id="lastRec" class="alert alert-success mt-2 d-none" role="alert">
                  <div class="d-flex align-items-center">
                    <div class="rounded overflow-hidden border" style="width:44px;height:44px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                      <img id="lrPhoto" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
                      <i id="lrIcon" class="ion ion-md-person" style="font-size:22px;color:#94a3b8;"></i>
                    </div>
                    <div class="ml-2">
                      <div>
                        <span id="lrBadge" class="badge badge-success mr-1">RECORDED</span>
                        <small id="lrWhen" class="text-muted"></small>
                      </div>
                      <div id="lrName" class="font-weight-600">—</div>
                      <div id="lrSN" class="text-monospace">—</div>
                    </div>
                  </div>
                </div>

                <div class="card-body" id="log" style="max-height:460px; overflow:auto; font-family:ui-monospace,Menlo,Consolas;">
                  <div class="text-muted">Waiting for scans…</div>
                </div>
              </div>

              <div class="alert alert-info mt-2 mb-0">
                <i class="mdi mdi-information-outline"></i>
                The scanner accepts <b>permanent student QR tokens</b> only. Duplicates are marked automatically.
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Profile Modal -->
      <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
          <div class="modal-content" style="border-radius:16px; overflow:hidden;">
            <div class="modal-body p-0">
              <div id="profileCard" class="pcard">
                <div id="pStatusStrip" class="pcard-strip"></div>
                <div class="pcard-inner">
                  <div class="pcard-head">
                    <span id="pBadge" class="badge badge-light">—</span>
                    <span id="pWhen" class="pcard-when"></span>
                  </div>
                  <div class="pcard-main">
                    <div class="pro-avatar">
                      <img id="pPhoto" src="" alt="">
                      <i id="pIcon" class="ion ion-md-person"></i>
                    </div>
                    <div class="pcard-info">
                      <div id="pName" class="pcard-name">—</div>
                      <div id="pSN" class="pcard-sn">—</div>
                      <div id="pMeta" class="pcard-meta">—</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
              <button id="btnNextScan" type="button" class="btn btn-primary">
                Next <i class="mdi mdi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/moment/moment.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-scrollto/jquery.scrollTo.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

  <!-- html5-qrcode -->
  <script src="https://unpkg.com/html5-qrcode"></script>

  <?php
  $csrf_name  = method_exists($this->security ?? null, 'get_csrf_token_name') ? $this->security->get_csrf_token_name() : '';
  $csrf_hash  = method_exists($this->security ?? null, 'get_csrf_hash')       ? $this->security->get_csrf_hash()       : '';
  ?>
  <script>
    window.__CSRF__ = {
      name: "<?= htmlspecialchars($csrf_name, ENT_QUOTES) ?>",
      value: "<?= htmlspecialchars($csrf_hash, ENT_QUOTES) ?>"
    };
  </script>

  <script>
    (function() {
      const activityId = <?= (int)$activity->activity_id ?>;

      const readerEl = document.getElementById('reader');
      const logEl = document.getElementById('log');
      const camSel = document.getElementById('cameraSelect');
      const btnStart = document.getElementById('btnStart');
      const btnStop = document.getElementById('btnStop');
      const btnClear = document.getElementById('btnClear');
      const btnUpload = document.getElementById('btnUpload');
      const fileInput = document.getElementById('qrFileInput');
      const statusEl = document.getElementById('scanStatus');
      const profileModal = $('#profileModal');
      const pStatusStrip = document.getElementById('pStatusStrip');
      const pBadge = document.getElementById('pBadge');
      const pWhen = document.getElementById('pWhen');
      const pName = document.getElementById('pName');
      const pSN = document.getElementById('pSN');
      const pMeta = document.getElementById('pMeta');
      const btnNextScan = document.getElementById('btnNextScan');
      const pPhoto = document.getElementById('pPhoto');
      const pIcon = document.getElementById('pIcon');

      const lastRec = document.getElementById('lastRec');
      const lrBadge = document.getElementById('lrBadge');
      const lrWhen = document.getElementById('lrWhen');
      const lrName = document.getElementById('lrName');
      const lrSN = document.getElementById('lrSN');
      const lrPhoto = document.getElementById('lrPhoto');
      const lrIcon = document.getElementById('lrIcon');
      // --- Manual remarks input ---
      const remarkInline = document.getElementById('remarkInline');

      function getManualRemarks() {
        return remarkInline && remarkInline.value ? remarkInline.value.trim() : '';
      }

      function clearRemarks() {
        if (remarkInline) remarkInline.value = '';
      }

      // --- Scan mode (button UI) ---
      let scanMode = 'in';
      const btnModeIn = document.getElementById('btnModeIn');
      const btnModeOut = document.getElementById('btnModeOut');

      function setMode(m) {
        scanMode = m;
        if (m === 'in') {
          btnModeIn.classList.add('btn-success', 'active');
          btnModeIn.classList.remove('btn-outline-success');
          btnModeOut.classList.add('btn-outline-primary');
          btnModeOut.classList.remove('btn-primary', 'active');
        } else {
          btnModeOut.classList.add('btn-primary', 'active');
          btnModeOut.classList.remove('btn-outline-primary');
          btnModeIn.classList.add('btn-outline-success');
          btnModeIn.classList.remove('btn-success', 'active');
        }
      }
      btnModeIn.addEventListener('click', () => setMode('in'));
      btnModeOut.addEventListener('click', () => setMode('out'));

      // --- Platform detect ---
      const ua = navigator.userAgent || navigator.vendor || '';
      const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
      const isMobile = /Android|iPhone|iPad|iPod/i.test(ua);

      // === Behavior defaults ===
      let pauseOnHit = true;
      let desktopBoost = true;
      let forceDisableFlip = true;
      const MOBILE_DEFAULT_ZOOM = 2.0;

      // HTTPS requirement hint for iOS
      if ((isIOS || isSafari) && location.protocol !== 'https:' && location.hostname !== 'localhost') {
        const warn = document.createElement('div');
        warn.className = 'alert alert-warning mb-2';
        warn.innerHTML = '<b>iOS camera requires HTTPS or localhost.</b> Please open this page over https://';
        document.querySelector('.container-fluid')?.prepend(warn);
      }

      function setStrip(color) {
        if (pStatusStrip) pStatusStrip.style.background = color;
      }

      function setBadge(text, cls) {
        pBadge.className = 'badge ' + (cls || 'badge-light');
        pBadge.textContent = text;
      }

      function timeNow() {
        try {
          return moment().format('MMM D, YYYY HH:mm:ss');
        } catch (e) {
          return '';
        }
      }

      function setStatus(text, cls) {
        statusEl.textContent = text;
        statusEl.className = '';
        if (cls) statusEl.classList.add(cls);
      }

      function addLine(text, cls) {
        const row = document.createElement('div');
        row.textContent = text;
        if (cls) row.className = cls;
        logEl.prepend(row);
      }

      function clearLog() {
        logEl.innerHTML = '<div class="text-muted">Waiting for scans…</div>';
      }

      function applyPhoto(imgEl, iconEl, url) {
        if (!imgEl || !iconEl) return;
        if (url) {
          imgEl.style.display = 'none';
          iconEl.style.display = 'flex';
          imgEl.onload = () => {
            imgEl.style.display = 'block';
            iconEl.style.display = 'none';
          };
          imgEl.onerror = () => {
            imgEl.style.display = 'none';
            iconEl.style.display = 'flex';
          };
          imgEl.src = url;
        } else {
          imgEl.style.display = 'none';
          iconEl.style.display = 'flex';
        }
      }

      // Profile lookup (fallback)
      async function getProfile(student_number) {
        let sn = student_number,
          name = null,
          course = null,
          major = null,
          photo_url = null;
        try {
          const r = await fetch('<?= site_url('attendance/profile') ?>?sn=' + encodeURIComponent(student_number));
          const j = await r.json();
          if (j && j.ok) {
            name = j.student_name || null;
            course = j.course || null;
            major = j.major || null;
            photo_url = j.photo_url || null;
            if (j.student_number) sn = j.student_number;
          }
        } catch (_e) {}
        return {
          sn,
          name,
          course,
          major,
          photo_url
        };
      }

      // NEW: use server-provided student payload when available
      async function hydrateStudent(maybeStudent) {
        // Object coming from PHP: {number, name, course, section, photo_url}
        if (maybeStudent && typeof maybeStudent === 'object') {
          return {
            number: maybeStudent.number || null,
            sn: maybeStudent.number || null,
            name: maybeStudent.name || null,
            course: maybeStudent.course || null,
            section: maybeStudent.section || null,
            major: null, // we prefer section over major for activities
            photo_url: maybeStudent.photo_url || null
          };
        }
        // Fallback to API call by SN
        return await getProfile(String(maybeStudent || ''));
      }

      function resizeReader() {
        const isSmall = window.innerWidth < 768;
        const w = readerEl.clientWidth || 480;
        const ar = isSmall ? (4 / 3) : (16 / 9);
        readerEl.style.height = Math.round(w / ar) + 'px';
      }
      window.addEventListener('resize', resizeReader);
      resizeReader();

      let qr = null;
      let running = false;
      let starting = false;
      let stopping = false;
      let devicesLoaded = false;
      let lastToken = null,
        lastWhen = 0;

      function recentlyScanned(token) {
        const now = Date.now();
        if (token === lastToken && (now - lastWhen) < 1500) return true;
        lastToken = token;
        lastWhen = now;
        return false;
      }

      async function iosPrePermission() {
        try {
          const tmp = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
              facingMode: {
                ideal: 'environment'
              }
            }
          });
          tmp.getTracks().forEach(t => t.stop());
          return true;
        } catch (e) {
          addLine('iOS getUserMedia permission failed: ' + e.name, 'text-danger');
          setStatus('Camera permission denied on iOS', 'text-danger');
          return false;
        }
      }

      function iosConfig() {
        return {
          fps: 18,
          rememberLastUsedCamera: true,
          willReadFrequently: true,
          disableFlip: !!forceDisableFlip,
          experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
          },
          videoConstraints: {
            facingMode: {
              ideal: 'environment'
            },
            width: {
              ideal: 1920
            },
            height: {
              ideal: 1080
            }
          }
        };
      }

      function defaultConfig() {
        const useQrbox = desktopBoost;
        return {
          fps: 24,
          qrbox: useQrbox ? function(viewW, viewH) {
            const size = Math.floor(Math.min(viewW, viewH) * 0.72);
            return {
              width: size,
              height: size
            };
          } : undefined,
          aspectRatio: (window.innerWidth < 768 ? 1.3333 : 1.7778),
          rememberLastUsedCamera: true,
          showTorchButtonIfSupported: true,
          willReadFrequently: true,
          experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
          },
          disableFlip: !!forceDisableFlip,
          videoConstraints: {
            facingMode: {
              ideal: 'environment'
            },
            width: {
              ideal: desktopBoost ? 2560 : 1920
            },
            height: {
              ideal: desktopBoost ? 1440 : 1080
            },
            advanced: [{
              focusMode: 'continuous'
            }]
          }
        };
      }

      async function enumerateCameras() {
        try {
          const devs = await Html5Qrcode.getCameras();
          camSel.innerHTML = '';
          if (!devs || !devs.length) {
            devicesLoaded = false;
            addLine('No camera found', 'text-danger');
            setStatus('No camera found', 'text-danger');
            return [];
          }
          let backIndex = -1;
          devs.forEach((d, i) => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.label || ('Camera ' + (i + 1));
            if (backIndex === -1 && /back|rear|environment/i.test(d.label || '')) backIndex = i;
            camSel.appendChild(opt);
          });
          camSel.selectedIndex = (backIndex !== -1) ? backIndex : 0;
          devicesLoaded = true;
          return devs;
        } catch (e) {
          devicesLoaded = false;
          addLine('Camera enumeration failed: ' + e, 'text-danger');
          return [];
        }
      }

      async function start(cameraDeviceId) {
        if (starting || running) return;
        starting = true;
        try {
          if (!qr) qr = new Html5Qrcode('reader', {
            verbose: false
          });

          if (isIOS) {
            const ok = await iosPrePermission();
            if (!ok) {
              starting = false;
              return;
            }
          }

          const cfg = isIOS ? iosConfig() : defaultConfig();
          const cameraConfig = cameraDeviceId ? {
            deviceId: {
              exact: cameraDeviceId
            }
          } : {
            facingMode: {
              ideal: 'environment'
            }
          };

          await qr.start(cameraConfig, cfg, onScanSuccess, onScanFailure);
          running = true;
          setStatus('Looking for a QR code…', '');

          const vid = readerEl.querySelector('video');
          if (vid) {
            vid.setAttribute('playsinline', 'true');
            vid.setAttribute('webkit-playsinline', 'true');
            vid.setAttribute('muted', '');
            vid.muted = true;
            try {
              vid.play && vid.play();
            } catch (_e) {}
          }

          tryEnhanceCamera();
        } catch (err) {
          addLine('× Start failed: ' + err, 'text-danger');
          setStatus('Camera error — check permissions', 'text-warning');
        } finally {
          starting = false;
        }
      }

      async function stop() {
        if (!qr || !running || stopping) return;
        stopping = true;
        try {
          await qr.stop();
        } catch (e) {} finally {
          running = false;
          stopping = false;
          setStatus('Scanner stopped', '');
          const zw = document.getElementById('qrZoomWrap');
          if (zw && zw.parentNode) zw.parentNode.removeChild(zw);
        }
      }

      function onScanFailure(_err) {
        // silent
      }

      function parsePayload(str) {
        let token = null,
          activity = String(<?= (int)$activity->activity_id ?>);

        const tryQuery = (qs) => {
          const p = new URLSearchParams(qs);
          if (p.get('token')) token = p.get('token');
          const a = p.get('activity') || p.get('activity_id');
          if (a) activity = a;
        };

        try {
          const u = new URL(str);
          tryQuery(u.search);
        } catch (_e) {
          if (str.includes('=') && str.includes('&')) tryQuery(str);
        }
        if (!token && str.includes('|')) {
          const parts = str.split('|');
          token = (parts[1] || '').trim() || null;
          if (!isNaN(parts[0])) activity = parts[0];
        }
        if (!token) token = str.trim();
        return {
          token,
          activity
        };
      }

      function onScanSuccess(decodedText) {
        if (navigator.vibrate) navigator.vibrate(40);
        setStatus('QR detected — processing…', 'text-success');

        const payload = parsePayload(decodedText);
        if (recentlyScanned(payload.token)) return;

        const body = new URLSearchParams();
        body.set('activity', payload.activity);
        body.set('activity_id', payload.activity);
        body.set('token', payload.token);
        body.set('direction', scanMode || 'auto');
        // send manual remarks (falls back to "Scanned via QR" if empty)
        const manualRemarks = getManualRemarks();
        if (manualRemarks !== '') {
          body.set('remarks', manualRemarks);
        }
        if (window.__CSRF__ && __CSRF__.name && __CSRF__.value) {
          body.set(__CSRF__.name, __CSRF__.value);
        }

        fetch('<?= site_url('attendance/consume') ?>', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
          })
          .then(async (r) => {
            const txt = await r.text();
            let j = null;
            try {
              j = JSON.parse(txt);
            } catch (_e) {
              throw new Error('HTTP ' + r.status + ' ' + r.statusText + ' — ' + txt.slice(0, 240));
            }
            if (!r.ok && j && j.message) throw new Error(j.message);
            return j;
          })
          .then(j => {
            // student payload from backend (may be null)
            const studentPayload = j && j.student ? j.student : null;

            // Friendly client-side mapping for common errors
            if (j && j.ok === false) {
              if (j.mode === 'already_in' || /1062/.test(String(j.message || ''))) {
                addLine('• Already recorded for this session: ' + (j.student_number || ''), 'text-warning');
                setStatus('Already recorded for this session', 'text-warning');
                showLastRecorded(studentPayload || (j.student_number || '—'), 'dup');
                if (pauseOnHit) showProfileCard(studentPayload || (j.student_number || '—'), 'dup', 'Already recorded for this session');
                return;
              }
              if (j.mode === 'no_open') {
                addLine('• No open check-in to check out.', 'text-warning');
                setStatus('No open check-in to check out', 'text-warning');
                showLastRecorded(studentPayload || (j.student_number || '—'), 'err');
                if (pauseOnHit) showProfileCard(studentPayload || (j.student_number || '—'), 'dup', 'No open check-in to check out');
                return;
              }
            }

            const outcome = (j.ok && j.mode === 'checked_in') ? 'in' :
              (j.ok && j.mode === 'checked_out') ? 'out' :
              (j.ok && j.mode === 'duplicate') ? 'dup' :
              'err';

            if (outcome === 'in') {
              addLine('✔ CHECKED IN: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-success');
              setStatus('Checked IN ✓', 'text-success');
            } else if (outcome === 'out') {
              addLine('↘ CHECKED OUT: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-primary');
              setStatus('Checked OUT ✓', 'text-success');
            } else if (outcome === 'dup') {
              addLine('• Already recorded for this session: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-warning');
              setStatus('Already recorded for this session', 'text-warning');
            } else {
              addLine('× Failed: ' + (j.message || 'Unknown error'), 'text-danger');
              setStatus('Error — see log', 'text-danger');
            }

            showLastRecorded(studentPayload || (j.student_number || payload.token), outcome);
            // NOTE: clearRemarks() removed so remarks persist after each scan.

            if (pauseOnHit) {
              showProfileCard(
                studentPayload || (j.student_number || payload.token),
                outcome,
                j.message || (j.session ? `Session: ${j.session}` : '')
              );
            } else {
              const msg = outcome === 'in' ? 'IN recorded' :
                outcome === 'out' ? 'OUT recorded' :
                outcome === 'dup' ? 'Already recorded' :
                'Invalid';
              setStatus(`${msg} — keep scanning…`, outcome === 'err' ? 'text-danger' : 'text-success');
              setTimeout(() => setStatus('Looking for a QR code…', ''), 1200);
            }
          })
          .catch((e) => {
            addLine('× ' + (e && e.message ? e.message : 'Network error'), 'text-danger');
            setStatus('Request failed — see log', 'text-danger');
          });
      }

      // UPDATED: accepts SN string or backend student object
      async function showLastRecorded(studentOrSn, outcome) {
        if (!lastRec) return;
        const prof = await hydrateStudent(studentOrSn);

        lastRec.classList.remove('alert-success', 'alert-primary', 'alert-warning', 'alert-danger', 'd-none');
        lrBadge.classList.remove('badge-success', 'badge-primary', 'badge-warning', 'badge-danger');

        if (outcome === 'in') {
          lastRec.classList.add('alert-success');
          lrBadge.classList.add('badge-success');
          lrBadge.textContent = 'IN';
        } else if (outcome === 'out') {
          lastRec.classList.add('alert-primary');
          lrBadge.classList.add('badge-primary');
          lrBadge.textContent = 'OUT';
        } else if (outcome === 'dup') {
          lastRec.classList.add('alert-warning');
          lrBadge.classList.add('badge-warning');
          lrBadge.textContent = 'DUPLICATE';
        } else {
          lastRec.classList.add('alert-danger');
          lrBadge.classList.add('badge-danger');
          lrBadge.textContent = 'INVALID';
        }

        lrWhen.textContent = timeNow();
        lrName.textContent = prof.name ? prof.name : 'Unknown Student';
        lrSN.textContent = prof.number || prof.sn || '—';
        applyPhoto(lrPhoto, lrIcon, prof.photo_url);
      }

      // UPDATED: accepts SN string or backend student object
      async function showProfileCard(studentOrSn, outcome, msg) {
        if (pauseOnHit) {
          await stop();
        }
        const prof = await hydrateStudent(studentOrSn);

        if (outcome === 'in') {
          setStrip('#DCFCE7');
          setBadge('CHECKED IN', 'badge-success');
        } else if (outcome === 'out') {
          setStrip('#DBEAFE');
          setBadge('CHECKED OUT', 'badge-primary');
        } else if (outcome === 'dup') {
          setStrip('#FEF3C7');
          setBadge('ALREADY RECORDED', 'badge-warning');
        } else {
          setStrip('#FEE2E2');
          setBadge('INVALID', 'badge-danger');
        }

        pWhen.textContent = timeNow();
        pName.textContent = prof.name ? prof.name : 'Unknown Student';
        pSN.textContent = prof.number || prof.sn || '—';
        // prefer section over major for activities
        pMeta.textContent = (prof.course || prof.section) ? [prof.course, prof.section].filter(Boolean).join(' • ') :
          (msg || '—');
        applyPhoto(pPhoto, pIcon, prof.photo_url);

        if (pauseOnHit) {
          profileModal.modal({
            backdrop: 'static',
            keyboard: false
          });
          profileModal.modal('show');
        }
      }

      async function scanImageFile(file) {
        if (!file) return;
        const resume = running;
        try {
          if (running) await stop();
          if (!qr) qr = new Html5Qrcode('reader', {
            verbose: false
          });
          const decodedText = await qr.scanFile(file, false);
          onScanSuccess(decodedText);
        } catch (err) {
          addLine('× Image scan failed: ' + err, 'text-danger');
          setStatus('Couldn’t read that image', 'text-danger');
        } finally {
          if (resume) {
            const id = camSel.value;
            if (id) await start(id);
          }
        }
      }

      btnUpload.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', function(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        if (!/^image\//i.test(file.type)) {
          addLine('× Please select an image file.', 'text-danger');
          return;
        }
        scanImageFile(file).then(() => {
          fileInput.value = '';
        });
      });

      async function initCamerasThenMaybeStart() {
        const devs = await enumerateCameras();
        if (!devs || !devs.length) {
          setStatus('No camera found', 'text-danger');
          return;
        }
        if (!isIOS) {
          await start(camSel.value || devs[0].id);
        } else {
          setStatus('Tap Start to enable the camera', 'text-info');
        }
      }

      function tryEnhanceCamera() {
        const v = readerEl.querySelector('video');
        const track = v && v.srcObject && v.srcObject.getVideoTracks ? v.srcObject.getVideoTracks()[0] : null;
        if (!track) return;

        try {
          track.applyConstraints({
            advanced: [{
              focusMode: 'continuous'
            }]
          }).catch(() => {});
        } catch (_) {}

        const caps = (track.getCapabilities && track.getCapabilities()) || {};
        const settings = (track.getSettings && track.getSettings()) || {};

        if (caps.zoom && typeof caps.zoom.min === 'number') {
          let zoomWrap = document.getElementById('qrZoomWrap');
          if (!zoomWrap) {
            zoomWrap = document.createElement('div');
            zoomWrap.id = 'qrZoomWrap';
            zoomWrap.style.position = 'absolute';
            zoomWrap.style.left = '50%';
            zoomWrap.style.bottom = '12px';
            zoomWrap.style.transform = 'translateX(-50%)';
            zoomWrap.style.background = 'rgba(0,0,0,.55)';
            zoomWrap.style.padding = '6px 10px';
            zoomWrap.style.borderRadius = '999px';
            zoomWrap.style.backdropFilter = 'blur(4px)';
            zoomWrap.style.display = 'flex';
            zoomWrap.style.alignItems = 'center';
            zoomWrap.style.zIndex = '5';

            const input = document.createElement('input');
            input.type = 'range';
            input.id = 'qrZoomInput';
            input.min = caps.zoom.min;
            input.max = caps.zoom.max;
            input.step = caps.zoom.step || 0.1;
            input.value = settings.zoom || caps.zoom.min;
            input.style.width = '180px';
            input.style.margin = '0 8px';

            const lbl = document.createElement('small');
            lbl.textContent = 'Zoom';
            lbl.style.color = '#fff';

            zoomWrap.appendChild(lbl);
            zoomWrap.appendChild(input);
            readerEl.style.position = 'relative';
            readerEl.appendChild(zoomWrap);

            input.addEventListener('input', (e) => {
              const z = parseFloat(e.target.value);
              track.applyConstraints({
                advanced: [{
                  zoom: z
                }]
              }).catch(() => {});
            });

            if (isMobile) {
              const z = Math.min(Math.max(MOBILE_DEFAULT_ZOOM, caps.zoom.min), caps.zoom.max);
              track.applyConstraints({
                  advanced: [{
                    zoom: z
                  }]
                })
                .then(() => {
                  input.value = z;
                })
                .catch(() => {});
            }
          }
        }
      }

      // Initial camera enumeration
      initCamerasThenMaybeStart();

      btnStart.addEventListener('click', async function() {
        if (running) return;
        if (!devicesLoaded) await enumerateCameras();
        const id = camSel.value;
        if (!id && !isIOS) {
          addLine('Select a camera first', 'text-danger');
          return;
        }
        await start(id);
      });

      btnStop.addEventListener('click', stop);
      btnClear.addEventListener('click', clearLog);

      camSel.addEventListener('change', async function() {
        const id = camSel.value;
        if (!id) return;
        await stop();
        await start(id);
      });

      window.addEventListener('beforeunload', stop);

      // (Optional) face hints
      let faceInterval = null,
        lastFaceAt = 0;

      function hookFaceHints() {
        if (!('FaceDetector' in window)) return;
        const video = readerEl.querySelector('video');
        if (!video) return;
        const fd = new FaceDetector({
          fastMode: true,
          maxDetectedFaces: 1
        });
        if (faceInterval) clearInterval(faceInterval);
        faceInterval = setInterval(async () => {
          if (!running) return;
          try {
            const faces = await fd.detect(video);
            if (faces && faces.length) {
              const now = Date.now();
              if (now - lastFaceAt > 2000) {
                lastFaceAt = now;
                setStatus("Looks like a face — please show the QR code", 'text-warning');
                setTimeout(() => setStatus('Looking for a QR code…', ''), 1800);
              }
            }
          } catch (_e) {
            clearInterval(faceInterval);
          }
        }, 900);
      }
      hookFaceHints();

      if (btnNextScan) {
        btnNextScan.addEventListener('click', async function() {
          profileModal.modal('hide');
          if (pauseOnHit) {
            setStatus("Looking for a QR code…", '');
            const id = camSel.value;
            if (!running) await start(id);
          }
        });
      }
      profileModal.on('hidden.bs.modal', async function() {
        if (pauseOnHit) {
          setStatus("Looking for a QR code…", '');
          const id = camSel.value;
          if (!running) await start(id);
        }
      });

    })();
  </script>

</body>

</html>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=20260831'); ?>">

<style>
  /* ===== Page header ===== */
  .pl-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; flex-wrap:wrap; margin-bottom:20px;
  }
  .pl-header .page-title-box { flex:1 1 auto; margin:0; }
  .pl-header .page-title-box .up-divider { margin:10px 0 0; }
  .pl-header .pl-actions { flex:0 0 auto; align-self:center; display:flex; gap:10px; }
  .scan-state-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
  .scan-state-badges .badge { font-size:.72rem; font-weight:700; padding:5px 12px; border-radius:999px; }

  /* ===== Scanner card ===== */
  .scan-card {
    background:#fff; border:1px solid #e6ebf5; border-radius:18px;
    box-shadow:0 6px 18px rgba(13,27,75,.05); overflow:hidden;
  }
  .scan-card-head {
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid #eef1f5;
  }
  .scan-card-head .sch-title {
    font-size:.92rem; font-weight:800; color:#0d1b4b; display:flex; align-items:center; gap:8px;
  }
  .scan-card-head .sch-title i { font-size:18px; color:#4266d4; }
  .scan-card-body { padding:20px; }

  /* ===== Camera controls row ===== */
  .cam-controls {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
  }
  .cam-controls select {
    border-radius:10px; border:1px solid #e6ebf5; font-size:.82rem;
    padding:8px 12px; height:38px; background:#fff; color:#374151;
    min-width:140px;
  }
  .cam-controls select:focus { outline:none; border-color:#4266d4; box-shadow:0 0 0 3px rgba(66,102,212,.1); }

  /* ===== Mode selector ===== */
  .mode-row {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    margin-top:14px; padding-top:14px; border-top:1px solid #eef1f5;
  }
  .mode-row .mr-label { font-size:.78rem; font-weight:700; color:#6b7a99; white-space:nowrap; }
  .mode-pills { display:inline-flex; border:1px solid #e6ebf5; border-radius:10px; overflow:hidden; }
  .mode-pills .mode-pill {
    padding:8px 18px; border:none; background:#fff; color:#6b7a99;
    font-size:.78rem; font-weight:700; cursor:pointer; transition:all .15s ease;
    border-right:1px solid #e6ebf5;
  }
  .mode-pills .mode-pill:last-child { border-right:none; }
  .mode-pills .mode-pill:hover { background:#f4f7ff; color:#0d1b4b; }
  .mode-pills .mode-pill.active { color:#fff; }
  .mode-pills .mode-pill.active[data-mode="auto"] { background:linear-gradient(135deg,#2a4090,#4266d4); }
  .mode-pills .mode-pill.active[data-mode="in"] { background:linear-gradient(135deg,#15803d,#16a34a); }
  .mode-pills .mode-pill.active[data-mode="out"] { background:linear-gradient(135deg,#1e40af,#3b82f6); }

  /* ===== Remarks ===== */
  .remarks-row {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    margin-top:14px; padding-top:14px; border-top:1px solid #eef1f5;
  }
  .remarks-row .rr-label { font-size:.78rem; font-weight:700; color:#6b7a99; white-space:nowrap; }
  .remarks-row input {
    flex:1; min-width:200px; border-radius:10px; border:1px solid #e6ebf5;
    font-size:.82rem; padding:8px 14px; height:38px; background:#fff; color:#374151;
  }
  .remarks-row input:focus { outline:none; border-color:#4266d4; box-shadow:0 0 0 3px rgba(66,102,212,.1); }

  /* ===== Scanner viewport ===== */
  .scan-wrap {
    position:relative; width:100%; max-width:560px; margin:20px auto 0;
  }
  #reader {
    position:relative; width:100%; height:auto; min-height:300px;
    background:#0a0e1a; border-radius:14px; overflow:hidden;
  }
  #scanStatus {
    position:absolute; bottom:12px; left:50%; transform:translateX(-50%);
    background:rgba(17,24,39,.75); color:#fff;
    border:1px solid rgba(255,255,255,.15);
    padding:6px 16px; border-radius:999px; font-size:.82rem; font-weight:600;
    backdrop-filter:blur(4px); z-index:4; white-space:nowrap;
    max-width:90%; overflow:hidden; text-overflow:ellipsis;
  }
  #scanTips { margin-top:10px; font-size:.82rem; color:#475569; }
  .tip {
    display:inline-flex; align-items:center;
    border:1px solid #e2e8f0; border-radius:999px;
    padding:4px 10px; margin:3px 6px 0 0; background:#f8fafc;
  }
  .tip i { font-size:14px; margin-right:6px; opacity:.8; }
  #reader button, #reader input[type=range] { margin:6px; }

  /* ===== Last scan result banner ===== */
  .last-scan {
    display:flex; align-items:center; gap:12px;
    padding:14px 18px; border-radius:14px; margin-bottom:14px;
    transition:all .2s ease;
  }
  .last-scan.ls-in { background:#f0fdf4; border:1px solid #bbf7d0; }
  .last-scan.ls-out { background:#eff6ff; border:1px solid #bfdbfe; }
  .last-scan.ls-dup { background:#fffbeb; border:1px solid #fde68a; }
  .last-scan.ls-err { background:#fef2f2; border:1px solid #fecaca; }
  .last-scan.ls-hidden { display:none; }
  .last-scan .ls-avatar {
    width:44px; height:44px; border-radius:10px; overflow:hidden;
    background:#f3f4f6; border:1px solid #e5e7eb;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .last-scan .ls-avatar img { width:100%; height:100%; object-fit:cover; }
  .last-scan .ls-avatar i { font-size:22px; color:#94a3b8; }
  .last-scan .ls-info { flex:1; min-width:0; }
  .last-scan .ls-top { display:flex; align-items:center; gap:8px; }
  .last-scan .ls-badge {
    font-size:.68rem; font-weight:800; padding:3px 10px; border-radius:999px;
    text-transform:uppercase; letter-spacing:.05em; color:#fff;
  }
  .last-scan .ls-badge.ls-badge-in { background:#16a34a; }
  .last-scan .ls-badge.ls-badge-out { background:#3b82f6; }
  .last-scan .ls-badge.ls-badge-dup { background:#f59e0b; }
  .last-scan .ls-badge.ls-badge-err { background:#ef4444; }
  .last-scan .ls-time { font-size:.74rem; color:#9aa5b8; }
  .last-scan .ls-name { font-size:.92rem; font-weight:700; color:#0d1b4b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .last-scan .ls-sn { font-size:.78rem; color:#6b7a99; font-family:ui-monospace,Menlo,Consolas; }

  /* ===== Scan log ===== */
  #log {
    max-height:400px; overflow:auto;
    font-family:ui-monospace,Menlo,Consolas; font-size:.78rem;
    padding:14px 18px;
  }
  #log > div { padding:5px 0; border-bottom:1px solid #f1f4f9; }
  #log > div:last-child { border-bottom:none; }
  #log .text-success { color:#16a34a; }
  #log .text-warning { color:#d97706; }
  #log .text-danger { color:#dc2626; }
  #log .text-primary { color:#3b82f6; }
  #log .text-info { color:#0ea5e9; }

  /* ===== Profile modal ===== */
  #profileModal .modal-content { border-radius:16px; overflow:hidden; background:#fff; }
  #profileModal .pcard { display:flex; background:#fff; border-radius:16px; overflow:hidden; }
  #profileModal .pcard-strip { width:10px; background:#e5e7eb; }
  #profileModal .pcard-inner { flex:1; padding:16px 18px; }
  #profileModal .pcard-head { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
  #profileModal #pBadge {
    border-radius:999px; padding:4px 10px; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em; font-size:.75rem; color:#fff;
  }
  #profileModal .pcard-when { font-size:.85rem; color:#6b7280; }
  #profileModal .pcard-main { display:flex; gap:14px; }
  #profileModal .pro-avatar {
    width:84px; height:84px; border-radius:12px; overflow:hidden;
    background:#f3f4f6; border:1px solid #e5e7eb;
    display:flex; align-items:center; justify-content:center;
  }
  #profileModal #pPhoto { width:100%; height:100%; object-fit:cover; display:none; }
  #profileModal #pIcon { font-size:36px; color:#94a3b8; }
  #profileModal .pcard-info { flex:1; min-width:0; }
  #profileModal .pcard-name { font-size:1.25rem; line-height:1.2; font-weight:700; color:#1f2937; margin-bottom:2px; }
  #profileModal .pcard-sn { font-family:ui-monospace,Menlo,Consolas; color:#4b5563; margin-bottom:2px; }
  #profileModal .pcard-meta { color:#6b7280; }
  #profileModal #pBadge.badge-success { background:#16a34a; }
  #profileModal #pBadge.badge-warning { background:#f59e0b; }
  #profileModal #pBadge.badge-danger { background:#ef4444; }
  .badge-primary { background:#3b82f6; }

  /* ===== Mobile ===== */
  @media (max-width:767.98px) {
    .pl-header { flex-direction:column; gap:10px; }
    .pl-header .pl-actions { align-self:flex-start; }
    .cam-controls { width:100%; }
    .cam-controls select { flex:1; min-width:0; }
    .mode-row { padding-top:12px; margin-top:12px; }
    .remarks-row input { min-width:0; }
  }
</style>

<body class="ms-scan-page">
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- ===== Page header ===== -->
          <div class="pl-header">
            <div class="page-title-box">
              <h4 class="up-page-title d-flex align-items-center">
                <i class="ion ion-ios-qr-scanner mr-2"></i>
                <?= htmlspecialchars($activity->title) ?>
              </h4>
              <div class="scan-state-badges">
                <span class="badge badge-info"><?= htmlspecialchars($activity->activity_date) ?></span>
                <?php $st = $activity_state ?? activity_state($activity); ?>
                <span class="badge badge-pill <?= activity_state_badge_class($st['state']) ?> text-uppercase">
                  <?= htmlspecialchars($st['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
              <hr class="up-divider" />
            </div>
            <div class="pl-actions">
              <a href="<?= site_url('activities'); ?>" class="up-btn up-btn-ghost">
                <i class="mdi mdi-arrow-left"></i> Back
              </a>
            </div>
          </div>

          <?php if (!$st['is_open']): ?>
            <div class="alert alert-warning d-flex align-items-start mb-3" role="alert" style="border-radius:14px;">
              <i class="ion ion-md-lock mr-2 mt-1"></i>
              <div>
                <strong>Check-ins are closed.</strong>
                <?= htmlspecialchars($st['reason'], ENT_QUOTES, 'UTF-8') ?>
                Reopen from <a href="<?= site_url('activities/' . (int)$activity->activity_id . '/edit') ?>">activity settings</a>.
              </div>
            </div>
          <?php endif; ?>

          <div class="row">
            <!-- ===== Left: Scanner ===== -->
            <div class="col-lg-7">
              <div class="scan-card">
                <!-- Camera controls -->
                <div class="scan-card-head">
                  <div class="sch-title"><i class="ion ion-ios-videocam"></i> Scanner</div>
                  <div class="cam-controls">
                    <select id="cameraSelect" aria-label="Camera"></select>
                    <button id="btnStart" class="up-btn up-btn-primary up-btn-sm"><i class="mdi mdi-play"></i> Start</button>
                    <button id="btnStop" class="up-btn up-btn-ghost up-btn-sm"><i class="mdi mdi-stop"></i> Stop</button>
                    <button id="btnUpload" class="up-btn up-btn-ghost up-btn-sm"><i class="mdi mdi-upload"></i> Upload</button>
                    <input type="file" id="qrFileInput" accept="image/*" class="d-none">
                  </div>
                </div>

                <div class="scan-card-body">
                  <!-- Camera viewport -->
                  <div class="scan-wrap">
                    <div id="reader"></div>
                    <div class="ms-scan-reticle" aria-hidden="true"></div>
                    <div id="scanStatus" aria-live="polite">Press Start to scan</div>
                  </div>
                  <div id="scanTips"></div>

                  <!-- Mode selector -->
                  <div class="mode-row">
                    <span class="mr-label">Mode</span>
                    <div class="mode-pills">
                      <button class="mode-pill active" data-mode="auto" id="btnModeAuto">AUTO</button>
                      <button class="mode-pill" data-mode="in" id="btnModeIn">IN</button>
                      <button class="mode-pill" data-mode="out" id="btnModeOut">OUT</button>
                    </div>
                  </div>

                  <!-- Remarks -->
                  <div class="remarks-row">
                    <span class="rr-label">Remarks</span>
                    <input id="remarkInline" placeholder="Optional — leave blank for default">
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== Right: Last result + Log ===== -->
            <div class="col-lg-5">
              <!-- Last scan result -->
              <div id="lastRec" class="last-scan ls-hidden">
                <div class="ls-avatar">
                  <img id="lrPhoto" src="" alt="" style="display:none;">
                  <i id="lrIcon" class="ion ion-md-person"></i>
                </div>
                <div class="ls-info">
                  <div class="ls-top">
                    <span id="lrBadge" class="ls-badge ls-badge-in">IN</span>
                    <span id="lrWhen" class="ls-time"></span>
                  </div>
                  <div id="lrName" class="ls-name">—</div>
                  <div id="lrSN" class="ls-sn">—</div>
                </div>
              </div>

              <!-- Scan log -->
              <div class="scan-card">
                <div class="scan-card-head">
                  <div class="sch-title"><i class="mdi mdi-format-list-bulleted"></i> Scan Log</div>
                  <button id="btnClear" class="up-btn up-btn-ghost up-btn-sm"><i class="mdi mdi-broom"></i> Clear</button>
                </div>
                <div id="log">
                  <div class="text-muted" style="padding:24px 16px;text-align:center;">Waiting for scans…</div>
                </div>
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
  <script defer src="<?= base_url(); ?>assets/libs/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/calendar.init.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.chat.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/jquery.todo.js"></script>
  <script src="<?= base_url(); ?>assets/libs/morris-js/morris.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/raphael/raphael.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/pages/dashboard.init.js?v=2"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/jquery.dataTables.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.buttons.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/buttons.bootstrap4.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/jszip/jszip.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/pdfmake.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/pdfmake/vfs_fonts.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.html5.min.js"></script>
  <script defer src="<?= base_url(); ?>assets/libs/datatables/buttons.print.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.responsive.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/responsive.bootstrap4.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.keyTable.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/datatables/dataTables.select.min.js"></script>

  <!-- html5-qrcode -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

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

      // --- Scan mode (button UI) — AUTO is default ---
      let scanMode = 'auto';
      const btnModeAuto = document.getElementById('btnModeAuto');
      const btnModeIn = document.getElementById('btnModeIn');
      const btnModeOut = document.getElementById('btnModeOut');

      function setMode(m) {
        scanMode = m;
        [btnModeAuto, btnModeIn, btnModeOut].forEach(b => b.classList.remove('active'));
        if (m === 'auto') btnModeAuto.classList.add('active');
        else if (m === 'in') btnModeIn.classList.add('active');
        else if (m === 'out') btnModeOut.classList.add('active');
      }
      btnModeAuto.addEventListener('click', () => setMode('auto'));
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
        const ar = isSmall ? 1 : (16 / 9);
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
        // 3-second debounce for the same token — prevents the camera
        // from firing twice on the same QR before the backend responds.
        if (token === lastToken && (now - lastWhen) < 3000) return true;
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
          document.body.classList.add('ms-scanner-running');
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
          document.body.classList.remove('ms-scanner-running');
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
          document.body.classList.remove('ms-scanner-running');
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

      function successFeedback() {
        if (navigator.vibrate) navigator.vibrate(60);
        try {
          const AudioContext = window.AudioContext || window.webkitAudioContext;
          if (!AudioContext) return;
          const context = new AudioContext();
          const oscillator = context.createOscillator();
          const gain = context.createGain();
          oscillator.type = 'sine';
          oscillator.frequency.value = 880;
          gain.gain.setValueAtTime(.0001, context.currentTime);
          gain.gain.exponentialRampToValueAtTime(.12, context.currentTime + .01);
          gain.gain.exponentialRampToValueAtTime(.0001, context.currentTime + .09);
          oscillator.connect(gain);
          gain.connect(context.destination);
          oscillator.start();
          oscillator.stop(context.currentTime + .1);
          oscillator.addEventListener('ended', () => context.close());
        } catch (_e) {}
      }

      function onScanSuccess(decodedText) {
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
                addLine('• Already checked in: ' + (j.student_number || '') + ' — scan in OUT mode or use AUTO', 'text-warning');
                setStatus('Already checked in — use AUTO or OUT', 'text-warning');
                showLastRecorded(studentPayload || (j.student_number || '—'), 'dup');
                if (pauseOnHit) showProfileCard(studentPayload || (j.student_number || '—'), 'dup', 'Already checked in. Switch to AUTO or OUT to check them out.');
                return;
              }
              if (j.mode === 'no_open') {
                addLine('• No open check-in to check out: ' + (j.student_number || '') + ' — scan in IN mode or use AUTO', 'text-warning');
                setStatus('No open check-in — use AUTO or IN', 'text-warning');
                showLastRecorded(studentPayload || (j.student_number || '—'), 'err');
                if (pauseOnHit) showProfileCard(studentPayload || (j.student_number || '—'), 'dup', 'No open check-in to check out. Switch to AUTO or IN to check them in.');
                return;
              }
              if (j.mode === 'too_soon_after_in') {
                addLine('• Just checked in: ' + (j.student_number || '') + ' — ' + (j.message || 'scan again to check out'), 'text-info');
                setStatus('Just checked in — scan again to check OUT', 'text-info');
                showLastRecorded(studentPayload || (j.student_number || '—'), 'dup');
                if (pauseOnHit) showProfileCard(studentPayload || (j.student_number || '—'), 'dup', j.message || 'Checked in moments ago — scan again to check out.');
                return;
              }
            }

            const outcome = (j.ok && j.mode === 'checked_in') ? 'in' :
              (j.ok && j.mode === 'checked_out') ? 'out' :
              (j.ok && j.mode === 'duplicate') ? 'dup' :
              'err';

            if (outcome === 'in' || outcome === 'out') successFeedback();

            if (outcome === 'in') {
              addLine('✔ CHECKED IN: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-success');
              setStatus('Checked IN ✓ — ' + (j.student_number || ''), 'text-success');
            } else if (outcome === 'out') {
              addLine('↘ CHECKED OUT: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-primary');
              setStatus('Checked OUT ✓ — ' + (j.student_number || ''), 'text-success');
            } else if (outcome === 'dup') {
              addLine('• Already completed this session: ' + j.student_number + ' (' + (j.session || '—') + ')', 'text-warning');
              setStatus('Already completed this session', 'text-warning');
            } else {
              addLine('× Failed: ' + (j.message || 'Unknown error'), 'text-danger');
              setStatus('Error — ' + (j.message || 'see log'), 'text-danger');
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

        // Reset classes
        lastRec.className = 'last-scan';
        lrBadge.className = 'ls-badge';

        if (outcome === 'in') {
          lastRec.classList.add('ls-in');
          lrBadge.classList.add('ls-badge-in');
          lrBadge.textContent = 'IN';
        } else if (outcome === 'out') {
          lastRec.classList.add('ls-out');
          lrBadge.classList.add('ls-badge-out');
          lrBadge.textContent = 'OUT';
        } else if (outcome === 'dup') {
          lastRec.classList.add('ls-dup');
          lrBadge.classList.add('ls-badge-dup');
          lrBadge.textContent = 'DUPLICATE';
        } else {
          lastRec.classList.add('ls-err');
          lrBadge.classList.add('ls-badge-err');
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

      // Requests camera permission from within a user gesture. Browsers
      // (especially on mobile) reject getUserMedia / enumerateDevices that
      // isn't triggered by a tap, which is what caused the
      // "NotAllowedError: Permission denied" on load.
      async function ensurePermission() {
        try {
          const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
          });
          // Release immediately — Html5Qrcode reopens the camera itself.
          stream.getTracks().forEach(t => t.stop());
          return true;
        } catch (e) {
          if (e && e.name === 'NotAllowedError') {
            addLine('Camera blocked. Click the camera/lock icon in the address bar → Reset permission → reload → Start.', 'text-danger');
            addLine('If no prompt appears, allow the camera for your browser in your device settings (e.g. macOS: System Settings → Privacy & Security → Camera → enable your browser).', 'text-warning');
            addLine('You can also use “Upload QR” to record attendance from a photo.', 'text-info');
            setStatus('Camera blocked — reset permission and reload', 'text-danger');
          } else if (e && e.name === 'NotFoundError') {
            addLine('No camera found on this device.', 'text-danger');
            setStatus('No camera found', 'text-danger');
          } else {
            addLine('Camera error: ' + (e && e.name ? e.name : e), 'text-danger');
            setStatus('Camera error — check permissions', 'text-warning');
          }
          return false;
        }
      }

      // Don't touch the camera on page load — wait for the user to tap Start.
      setStatus('Tap Start to enable the camera', 'text-info');

      btnStart.addEventListener('click', async function() {
        if (running || starting) return;
        // Permission first, inside this tap's user gesture.
        const ok = await ensurePermission();
        if (!ok) return;
        // Now that permission is granted, device labels are available.
        if (!devicesLoaded) await enumerateCameras();
        // start() falls back to facingMode:environment when no id is set,
        // so an empty selection is fine.
        await start(camSel.value || '');
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

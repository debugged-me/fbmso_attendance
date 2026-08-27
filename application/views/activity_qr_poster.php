<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Activity QR — <?= htmlspecialchars($activity->title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- QR generator -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <link href="<?= base_url(); ?>assets/fonts/sora/sora.css?v=30260820" rel="stylesheet">
  <style>
    :root{--ink:#0d1b4b;--muted:#6b7a99;--ring:#e6ebf5;--accent:#2a4090;--accent2:#4266d4}
    *{box-sizing:border-box}
    body{
      font-family:'Sora',system-ui,-apple-system,Segoe UI,Roboto,Arial;
      color:var(--ink); margin:0; line-height:1.5;
      background:linear-gradient(135deg,#f5f7fc 0%,#eef2fa 100%);
      min-height:100vh;
    }

    /* ===== Top bar ===== */
    .poster-topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px; flex-wrap:wrap; padding:18px 24px;
      background:#fff; border-bottom:1px solid var(--ring);
      box-shadow:0 2px 12px rgba(15,23,42,.04);
    }
    .poster-topbar .pt-brand{display:flex;align-items:center;gap:10px}
    .poster-topbar .pt-logo{
      width:36px;height:36px;border-radius:10px;overflow:hidden;
      border:1px solid var(--ring);background:#f4f8ff;
      display:flex;align-items:center;justify-content:center;
    }
    .poster-topbar .pt-logo img{width:100%;height:100%;object-fit:contain}
    .poster-topbar .pt-title{font-size:1rem;font-weight:800;color:var(--ink)}
    .poster-topbar .pt-sub{font-size:.76rem;color:var(--muted)}
    .poster-topbar .pt-actions{display:flex;gap:8px;flex-wrap:wrap}

    .pt-btn{
      display:inline-flex;align-items:center;gap:6px;
      padding:9px 16px; border:1px solid var(--ring); border-radius:10px;
      background:#fff; color:var(--ink); font-size:.84rem; font-weight:700;
      text-decoration:none; cursor:pointer; transition:all .15s ease;
    }
    .pt-btn:hover{border-color:var(--accent2);color:var(--accent2);box-shadow:0 3px 10px rgba(66,102,212,.1);text-decoration:none}
    .pt-btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-color:transparent}
    .pt-btn-primary:hover{color:#fff;box-shadow:0 6px 18px rgba(42,64,144,.25)}

    /* ===== Poster card ===== */
    .poster-wrap{max-width:680px;margin:32px auto;padding:0 16px}

    .poster-card{
      background:#fff; border:1px solid var(--ring); border-radius:24px;
      padding:40px 32px; box-shadow:0 20px 60px rgba(42,64,144,.1);
      text-align:center;
    }

    .poster-kicker{
      font-size:.78rem; font-weight:700; letter-spacing:.12em;
      text-transform:uppercase; color:var(--accent2); margin-bottom:8px;
    }

    .poster-qr{
      width:100%; max-width:420px; aspect-ratio:1/1; margin:0 auto 24px;
      display:flex; align-items:center; justify-content:center;
      border-radius:20px; overflow:hidden;
      background:#fff; padding:16px;
      border:2px solid var(--ring);
    }

    .poster-title{
      font-size:1.6rem; font-weight:800; color:var(--ink);
      margin:0 0 8px; word-break:break-word; line-height:1.25;
    }

    .poster-meta{
      display:flex; justify-content:center; align-items:center;
      gap:8px; flex-wrap:wrap; margin-bottom:6px;
    }
    .poster-meta .pm-chip{
      display:inline-flex; align-items:center; gap:5px;
      padding:5px 12px; border-radius:999px;
      font-size:.78rem; font-weight:600;
      background:#f4f7ff; color:var(--accent); border:1px solid #dce5ff;
    }
    .poster-meta .pm-chip i{font-size:14px}

    .poster-program{
      font-size:.82rem; color:var(--muted); margin:8px 0 20px;
    }

    .poster-url{
      font-family:ui-monospace,Menlo,Consolas,monospace;
      word-break:break-all; margin-top:16px;
      border:1px dashed var(--ring); padding:12px 16px;
      border-radius:12px; font-size:.84rem; background:#fafbff;
      color:var(--accent); user-select:all;
    }

    .poster-instructions{
      display:flex; justify-content:center; gap:24px; flex-wrap:wrap;
      margin-top:28px; padding-top:24px; border-top:1px solid var(--ring);
    }
    .poster-instructions .pi-step{
      display:flex; align-items:center; gap:8px;
      font-size:.78rem; color:var(--muted); font-weight:600;
    }
    .poster-instructions .pi-step .pi-num{
      width:24px;height:24px;border-radius:50%;
      background:linear-gradient(135deg,var(--accent),var(--accent2));
      color:#fff;font-size:.72rem;font-weight:800;
      display:flex;align-items:center;justify-content:center;
      flex-shrink:0;
    }

    /* ===== Mobile ===== */
    @media (max-width:600px){
      .poster-topbar{padding:14px 16px}
      .poster-topbar .pt-actions{width:100%}
      .pt-btn{flex:1;justify-content:center}
      .poster-wrap{margin:16px auto}
      .poster-card{padding:28px 18px;border-radius:18px}
      .poster-title{font-size:1.25rem}
      .poster-qr{max-width:300px}
      .poster-instructions{gap:14px}
    }

    /* ===== Print ===== */
    @media print{
      .no-print{display:none !important}
      body{background:#fff;margin:0}
      .poster-card{border:none;box-shadow:none;padding:0;border-radius:0}
      .poster-qr{max-width:5in;border:none}
      .poster-url{border:none;padding:0;margin-top:8px;background:transparent}
      .poster-instructions{display:none}
    }
  </style>
  <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>
<body>

<!-- Top bar -->
<div class="poster-topbar no-print">
  <div class="pt-brand">
    <div class="pt-logo"><img src="<?= base_url(); ?>upload/banners/logo1.png" alt="FBMSO"></div>
    <div>
      <div class="pt-title">FBMSO Attendance</div>
      <div class="pt-sub">Printable QR Poster</div>
    </div>
  </div>
  <div class="pt-actions">
    <a href="<?= site_url('activities'); ?>" class="pt-btn"><i class="mdi mdi-arrow-left"></i> Back</a>
    <button class="pt-btn pt-btn-primary" onclick="window.print()"><i class="mdi mdi-printer"></i> Print</button>
  </div>
</div>

<!-- Poster card -->
<div class="poster-wrap">
  <div class="poster-card">
    <div class="poster-kicker">Scan to Check In</div>
    <div id="qrcode" class="poster-qr"></div>
    <h2 class="poster-title"><?= htmlspecialchars($activity->title) ?></h2>
    <div class="poster-meta">
      <span class="pm-chip"><i class="mdi mdi-calendar-range"></i> <?= htmlspecialchars($activity->activity_date) ?></span>
      <?php if ($activity->location): ?>
        <span class="pm-chip"><i class="mdi mdi-map-marker"></i> <?= htmlspecialchars($activity->location) ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($activity->program)): ?>
      <div class="poster-program">Program: <?= htmlspecialchars($activity->program) ?></div>
    <?php endif; ?>
    <div class="poster-url"><?= htmlspecialchars($checkin_url) ?></div>

    <div class="poster-instructions no-print">
      <div class="pi-step"><span class="pi-num">1</span> Open your phone camera</div>
      <div class="pi-step"><span class="pi-num">2</span> Point at the QR code</div>
      <div class="pi-step"><span class="pi-num">3</span> Tap the link to check in</div>
    </div>
  </div>
</div>

<script>
(function(){
  var container = document.getElementById('qrcode');
  var url = <?= json_encode($checkin_url, JSON_UNESCAPED_SLASHES) ?>;

  var qr = null, rafId = null;

  function size(){
    var w = container.clientWidth || 420;
    var px = Math.max(180, Math.min(560, Math.floor(w - 32)));
    return px;
  }

  function render(){
    if (qr) { container.innerHTML = ''; qr = null; }
    qr = new QRCode(container, {
      text: url,
      width: size(),
      height: size(),
      correctLevel: QRCode.CorrectLevel.H
    });
  }

  function onResize(){
    if (rafId) cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(render);
  }

  render();
  window.addEventListener('resize', onResize);
  window.addEventListener('orientationchange', onResize);
})();
</script>
</body>
</html>

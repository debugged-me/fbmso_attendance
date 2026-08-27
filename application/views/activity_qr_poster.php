<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Activity QR — <?= htmlspecialchars($activity->title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- QR generator -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
    :root{--ink:#111;--ring:#e5e7eb;--btn:#111}
    *{box-sizing:border-box}
    body{
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
      color:var(--ink);
      margin:24px; line-height:1.35; background:#fff;
    }
    .wrap{max-width:960px;margin:0 auto;padding:0 12px}
    .hdr{
      display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap
    }
    .hdr h1{font-size:1.1rem;margin:0}
    .hdr small{color:#6b7280}
    .btn{
      display:inline-block;padding:10px 14px;border:1px solid var(--btn);
      text-decoration:none;border-radius:10px;background:#fff;color:var(--btn);font-weight:600
    }
    .btn:active{transform:translateY(1px)}
    .btn-group{display:flex;gap:8px;flex-wrap:wrap}

    .card{border:1px solid var(--ring);border-radius:16px;padding:20px}
    .qr{
      width:100%;max-width:520px;aspect-ratio:1/1;margin:0 auto;
      display:flex;align-items:center;justify-content:center
    }

    .meta{text-align:center;margin-top:16px}
    .meta h2{font-size:1.5rem;margin:.25rem 0;word-break:break-word}
    .meta .sub{color:#374151;margin-top:4px}
    .meta small{display:block;margin-top:4px;color:#6b7280;word-break:break-word}

    .url{
      font-family:ui-monospace,Menlo,monospace;
      word-break:break-all; margin-top:12px; border:1px dashed var(--ring);
      padding:10px;border-radius:8px; font-size:.95rem; background:#fafafa;
      user-select:all; /* tap to select if they need to copy manually */
    }

    /* Mobile */
    @media (max-width:600px){
      body{margin:16px}
      .btn{width:100%;text-align:center}
      .meta h2{font-size:1.25rem}
      .url{font-size:.9rem}
    }

    /* Print-friendly: bigger QR, clean margins, no borders */
    @media print{
      .no-print{display:none !important}
      body{margin:0.6in}
      .card{border:none;padding:0}
      .qr{max-width:6.5in}        /* enlarge QR on paper */
      .url{border:none;padding:0;margin-top:6px;background:transparent}
    }
  </style>
    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>
<body>
<div class="wrap">
  <div class="hdr no-print">
    <div>
      <h1>Printable QR for Activity</h1>
      <small>Students scan this with their phones</small>
    </div>
    <div class="btn-group">
      <a href="<?= site_url('activities'); ?>" class="btn">Back</a>
      <button class="btn" onclick="window.print()">Print</button>
    </div>
  </div>

  <div class="card">
    <div id="qrcode" class="qr"></div>
    <div class="meta">
      <h2><?= htmlspecialchars($activity->title) ?></h2>
      <div class="sub">
        <?= htmlspecialchars($activity->activity_date) ?>
        <?= $activity->location ? ' • '.htmlspecialchars($activity->location) : '' ?>
      </div>
      <?php if (!empty($activity->program)): ?>
        <small>Program: <?= htmlspecialchars($activity->program) ?></small>
      <?php endif; ?>
      <div class="url"><?= htmlspecialchars($checkin_url) ?></div>
    </div>
  </div>
</div>

<script>
(function(){
  var container = document.getElementById('qrcode');
  // Use unescaped slashes so the QR payload matches the URL exactly
  var url = <?= json_encode($checkin_url, JSON_UNESCAPED_SLASHES) ?>;

  var qr = null, rafId = null;

  function size(){
    // Wider cap for large screens; still responsive on phones
    var w = container.clientWidth || 520;
    var px = Math.max(180, Math.min(680, Math.floor(w)));
    return px;
  }

  function render(){
    if (qr) { container.innerHTML = ''; qr = null; }
    qr = new QRCode(container, {
      text: url,
      width: size(),
      height: size(),
      correctLevel: QRCode.CorrectLevel.H // ~30% ECC for robust scans
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

<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body class="antialiased">
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <style>
      /* ===== Root & utilities ===== */
      :root{
        --bg:#f8fafc;--card:#ffffff;--muted:#64748b;--line:#e5e7eb;--brand:#2563eb;--brand-600:#2563eb;--brand-700:#1d4ed8;--success:#16a34a;--warning:#f59e0b;--info:#0ea5e9;
      }
      @media (prefers-color-scheme: dark){
        :root{--bg:#0b1220;--card:#0f172a;--muted:#94a3b8;--line:#1e293b;--brand:#3b82f6;--brand-600:#3b82f6;--brand-700:#2563eb;--success:#22c55e;--warning:#fbbf24;--info:#38bdf8}
        body{color:#e2e8f0}
      }
      html{scroll-behavior:smooth}
      body{background:var(--bg)}
      .visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
      .text-mono{font-family:ui-monospace,Menlo,Consolas,monospace}
      .shadow-soft{box-shadow:0 8px 30px rgba(2,6,23,.06)}
      .rounded-2xl{border-radius:16px}
      .content-pad{padding:18px}
      .gap-2{gap:.5rem}
      .gap-3{gap:.75rem}
      .safe-bottom{padding-bottom:calc(16px + env(safe-area-inset-bottom))}

      /* ===== Header ===== */
      .page-title-box h4{margin:0 0 .25rem;font-weight:800}
      .page-sub{color:var(--muted);font-size:.92rem}
      .divider{border:0;height:2px;background:linear-gradient(90deg,#3b82f6,#f59e0b 60%,#22c55e);border-radius:1px;margin:10px 0 16px}

      /* Header buttons */
      .qr-header-btn{
        display:inline-flex; align-items:center; gap:6px;
        padding:8px 16px; border-radius:12px; font-weight:700; font-size:.82rem;
        letter-spacing:.02em; border:none; cursor:pointer; transition:transform .15s ease, box-shadow .15s ease;
      }
      .qr-header-btn-primary{
        background:linear-gradient(135deg,#2a4090,#4266d4); color:#fff;
        box-shadow:0 6px 16px rgba(42,64,144,.22);
      }
      .qr-header-btn-primary:hover{transform:translateY(-1px); box-shadow:0 10px 22px rgba(42,64,144,.28); color:#fff;}
      .qr-header-btn-ghost{
        background:var(--ar-soft,#f5f7fc); color:#0d1b4b; border:1px solid var(--line,#e6ebf5);
      }
      .qr-header-btn-ghost:hover{background:#eef2fb; color:#2a4090; transform:translateY(-1px);}

      /* Attendance filter modal */
      .att-filter-list{display:flex; flex-direction:column; gap:8px;}
      .att-filter-option{
        display:flex; align-items:center; gap:12px; width:100%;
        padding:14px 18px; border-radius:12px; border:1px solid var(--line,#e6ebf5);
        background:var(--card,#fff); color:#0d1b4b; font-weight:700; font-size:.9rem;
        cursor:pointer; transition:all .15s ease; text-align:left;
      }
      .att-filter-option i{font-size:20px; color:var(--muted,#6b7a99);}
      .att-filter-option:hover{background:#f5f7fc; border-color:#c7d2fe; transform:translateX(2px);}
      .att-filter-option.active{
        background:linear-gradient(135deg,#2a4090,#4266d4); color:#fff; border-color:#2a4090;
      }
      .att-filter-option.active i{color:#fff;}

      /* ===== Card ===== */
      .card-clean{background:var(--card);border:1px solid var(--line)}
      .card-clean .card-header{background:color-mix(in srgb,var(--card) 92%,#fff 8%);border-bottom:1px solid var(--line);padding:.75rem 1rem;font-weight:700}

      /* ===== QR Panel ===== */
      #qrcode{width:min(82vw,320px);aspect-ratio:1/1;border-radius:12px;border:1px dashed var(--line);background:#fff;max-width:320px}
      .chip{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .6rem;border-radius:999px;border:1px solid var(--line);background:var(--card);color:#334155;font-weight:700;font-size:.8rem}
      .chip i{font-size:14px;opacity:.75}
      .qr-actions .btn{min-width:150px}
      @media (min-width: 992px){ .qr-sticky{position:sticky; top:84px} }

      /* ===== Bank-card style QR ===== */
      .qr-card-wrap{perspective:1600px; max-width:520px; margin:0 auto;}
      .qr-card{
        position:relative; width:100%; aspect-ratio:1.586/1; border-radius:22px; padding:22px 24px;
        background:
          radial-gradient(120% 120% at 0% 0%, rgba(255,255,255,.18) 0%, transparent 45%),
          radial-gradient(120% 120% at 100% 100%, rgba(66,102,212,.45) 0%, transparent 55%),
          linear-gradient(135deg, #1a2a6c 0%, #2a4090 45%, #3b5fd4 100%);
        color:#fff; overflow:hidden;
        box-shadow:0 24px 50px rgba(13,27,75,.32), 0 6px 14px rgba(13,27,75,.18);
        display:flex; flex-direction:column; justify-content:space-between;
        transition:transform .25s ease, box-shadow .25s ease;
      }
      .qr-card:hover{transform:translateY(-4px) rotateX(2deg); box-shadow:0 32px 60px rgba(13,27,75,.38), 0 8px 18px rgba(13,27,75,.22);}
      .qr-card::before{
        content:''; position:absolute; inset:0; border-radius:22px; pointer-events:none;
        background:linear-gradient(120deg, transparent 30%, rgba(255,255,255,.12) 50%, transparent 70%);
        background-size:200% 100%; background-position:200% 0; transition:background-position .8s ease;
      }
      .qr-card:hover::before{background-position:-50% 0;}
      .qr-card-top{display:flex; align-items:flex-start; justify-content:space-between; gap:12px;}
      .qr-card-brand{display:flex; align-items:center; gap:10px;}
      .qr-card-brand .qc-chip{
        width:34px; height:26px; border-radius:7px;
        background:linear-gradient(135deg,#f6d365,#fda085); position:relative;
        box-shadow:inset 0 0 0 1px rgba(0,0,0,.15);
      }
      .qr-card-brand .qc-chip::after{
        content:''; position:absolute; inset:5px 6px; border-radius:3px;
        border:1px solid rgba(0,0,0,.18); border-top:none; border-bottom:none;
      }
      .qr-card-brand .qc-name{font-weight:800; font-size:.92rem; letter-spacing:.06em; line-height:1.1;}
      .qr-card-brand .qc-sub{font-size:.66rem; opacity:.78; letter-spacing:.12em; text-transform:uppercase;}
      .qr-card-status{
        font-size:.66rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
        background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28);
        padding:4px 10px; border-radius:999px; display:inline-flex; align-items:center; gap:5px;
      }
      .qr-card-status .qc-dot{width:6px; height:6px; border-radius:50%; background:#7CFFB2; box-shadow:0 0 6px #7CFFB2;}

      .qr-card-mid{display:flex; align-items:center; gap:18px;}
      .qr-card-qr{
        flex:0 0 auto; width:140px; height:140px; border-radius:14px; background:#fff;
        padding:8px; display:flex; align-items:center; justify-content:center;
        box-shadow:0 6px 14px rgba(0,0,0,.22);
      }
      .qr-card-qr #qrcode{width:100% !important; height:100% !important; aspect-ratio:1/1 !important; border:none !important; max-width:none !important;}
      .qr-card-qr #qrcode img, .qr-card-qr #qrcode canvas{width:100% !important; height:100% !important;}
      .qr-card-info{flex:1; min-width:0;}
      .qr-card-info .qc-label{font-size:.62rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase; opacity:.72;}
      .qr-card-info .qc-value{font-size:1.05rem; font-weight:800; letter-spacing:.04em; font-family:ui-monospace,Menlo,Consolas,monospace; margin-top:2px;}
      .qr-card-info .qc-name-display{font-family:'Sora',sans-serif; letter-spacing:.02em; text-transform:uppercase; line-height:1.25; word-break:break-word; margin-top:2px;}
      .qr-card-info .qc-name-line1{font-size:1.15rem; font-weight:800;}
      .qr-card-info .qc-name-line2{font-size:.92rem; font-weight:600; opacity:.88;}
      .qr-card-info .qc-hint{font-size:.7rem; opacity:.78; margin-top:8px; line-height:1.35;}

      .qr-card-bottom{display:flex; align-items:flex-end; justify-content:space-between; gap:12px;}
      .qr-card-bottom .qc-student{font-size:.78rem; font-weight:700; letter-spacing:.04em;}
      .qr-card-bottom .qc-student small{display:block; font-weight:400; font-size:.64rem; opacity:.7; letter-spacing:.1em; text-transform:uppercase; margin-bottom:2px;}
      .qr-card-bottom .qc-logo{font-size:.66rem; font-weight:800; letter-spacing:.18em; opacity:.85; text-align:right;}
      .qr-card-bottom .qc-logo b{font-size:.9rem; letter-spacing:.04em;}

      .qr-card-glow{
        position:absolute; width:200px; height:200px; border-radius:50%;
        background:radial-gradient(circle, rgba(124,255,178,.35) 0%, transparent 70%);
        top:-60px; right:-40px; pointer-events:none; filter:blur(10px);
      }

      /* Card back / actions row */
      .qr-card-actions{display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:18px;}
      .qr-card-actions .btn{border-radius:12px; font-weight:700; font-size:.84rem; padding:10px 18px; min-width:140px;}

      @media (max-width: 575.98px){
        .qr-card{aspect-ratio:1.4/1; padding:18px 18px;}
        .qr-card-qr{width:96px; height:96px;}
        .qr-card-info .qc-value{font-size:.92rem;}
        .qr-card-actions .btn{min-width:auto; flex:1;}
      }

      /* ===== Attendance ===== */
      .section-h{display:flex;align-items:center;justify-content:space-between;gap:1rem}
      .section-h h5{margin:0;font-weight:800;color:#0f172a}
      @media (prefers-color-scheme: dark){ .section-h h5{color:#e2e8f0} }
      .range-group .btn{border-radius:999px!important;padding:.35rem .9rem;font-weight:700;font-size:.78rem}
      .range-group .btn.active{background:var(--brand-600);color:#fff;border-color:var(--brand-600)}

      /* Minimal attendance table */
      .att-minimal-wrap{border:1px solid var(--line);border-radius:14px;overflow:hidden;background:var(--card)}
      #myAttTable{margin:0}
      #myAttTable thead th{white-space:nowrap;background:var(--card);border-bottom:1px solid var(--line);font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:12px 16px}
      #myAttTable tbody td{vertical-align:middle;padding:14px 16px;font-size:.88rem;border-bottom:1px solid var(--line)}
      #myAttTable tbody tr:last-child td{border-bottom:none}
      #myAttTable tbody tr:hover{background:color-mix(in srgb,var(--card) 92%,#3b82f6 6%)}
      .att-empty-row{padding:40px 16px !important;color:var(--muted);font-size:.9rem}
      .att-empty-mobile{padding:40px 16px;text-align:center;color:var(--muted);font-size:.9rem}
      .att-activity-name{font-weight:700;color:#0f172a}
      .att-activity-date{font-size:.76rem;color:var(--muted);margin-top:2px}
      .att-time{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.82rem;color:#334155}
      .pill{display:inline-block;border-radius:999px;padding:.18rem .55rem;font-size:.72rem;font-weight:800}
      .badge-soft{background:#eef2ff;color:#1e3a8a}

      /* Mobile attendance cards (minimal) */
      .att-min-card{padding:14px 16px;border-bottom:1px solid var(--line)}
      .att-min-card:last-child{border-bottom:none}
      .att-min-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
      .att-min-card-name{font-weight:700;color:#0f172a;font-size:.9rem}
      .att-min-card-date{font-size:.74rem;color:var(--muted);margin-top:2px}
      .att-min-card-time{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.78rem;color:#334155;margin-top:6px}

/* ===== Mobile-first responsive table ===== */
@media (max-width: 575.98px){
  .col-sm-hide{display:none}
  .qr-actions .btn{min-width:auto}
}
      @media (max-width: 420px){
        .btn{padding:.45rem .7rem}
        .btn i{margin-right:.2rem}
      }

      /* ===== Modal scanner ===== */
      #studentScanModal .modal-content{border:none; border-radius:20px; overflow:hidden; box-shadow:0 24px 60px rgba(13,27,75,.3);}
      #studentScanModal .modal-header{
        background:linear-gradient(135deg,#1a2a6c,#2a4090); color:#fff; border:none; padding:18px 24px;
        display:flex; align-items:center; justify-content:space-between;
      }
      #studentScanModal .modal-header .modal-title{font-weight:800; font-size:1.05rem; display:flex; align-items:center; gap:8px;}
      #studentScanModal .modal-header .close{color:#fff; opacity:.8; font-size:1.6rem; text-shadow:none;}
      #studentScanModal .modal-header .close:hover{opacity:1;}
      #studentScanModal .modal-body{padding:20px 24px; background:#f8fafc;}
      #studentScanModal .modal-footer{border:none; padding:14px 24px; background:#f8fafc;}

      .scan-toolbar{
        display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:16px;
        padding:14px 16px; background:#fff; border:1px solid #e6ebf5; border-radius:14px;
      }
      .scan-toolbar label{font-size:.78rem; font-weight:700; color:#6b7a99; letter-spacing:.02em; margin:0;}
      .scan-toolbar select{
        border-radius:10px; border:1px solid #e6ebf5; padding:7px 12px; font-size:.84rem;
        color:#0d1b4b; background:#fff; min-width:180px;
      }
      .scan-toolbar select:focus{outline:none; border-color:#4266d4; box-shadow:0 0 0 3px rgba(66,102,212,.12);}
      .scan-btn{
        display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:10px;
        font-size:.8rem; font-weight:700; border:none; cursor:pointer; transition:transform .15s ease, box-shadow .15s ease;
      }
      .scan-btn:hover{transform:translateY(-1px);}
      .scan-btn-start{background:#16a34a; color:#fff; box-shadow:0 4px 12px rgba(22,163,74,.25);}
      .scan-btn-stop{background:#fff; color:#6b7a99; border:1px solid #e6ebf5;}
      .scan-btn-upload{background:#eef2ff; color:#2a4090; border:1px solid #c7d2fe;}

      .scan-toggle-fix{
        display:inline-flex; align-items:center; gap:6px; font-size:.78rem; font-weight:600;
        color:#6b7a99; cursor:pointer; margin-left:4px;
      }
      .scan-toggle-fix input{margin:0; cursor:pointer;}

      .scan-mode-group{margin-left:auto; display:flex; align-items:center; gap:8px;}
      .scan-mode-group .scan-mode-label{font-size:.76rem; font-weight:700; color:#6b7a99;}
      .scan-mode-btns{display:flex; gap:4px; background:#f0f4ff; padding:3px; border-radius:10px;}
      .scan-mode-btn{
        padding:5px 16px; border-radius:8px; font-size:.78rem; font-weight:800; border:none; cursor:pointer;
        background:transparent; color:#6b7a99; transition:all .15s ease; letter-spacing:.04em;
      }
      .scan-mode-btn.active-in{background:#16a34a; color:#fff; box-shadow:0 3px 8px rgba(22,163,74,.3);}
      .scan-mode-btn.active-out{background:#2a4090; color:#fff; box-shadow:0 3px 8px rgba(42,64,144,.3);}

      .scan-wrap{position:relative; width:100%; max-width:720px; margin:0 auto; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(13,27,75,.12);}
      #reader{width:100%; min-height:340px; background:#0d1b4b; overflow:hidden;}
      #reader video{border-radius:16px;}
      #reader button, #reader input[type=range]{margin:6px}
      #scanStatus{
        position:absolute; bottom:14px; left:50%; transform:translateX(-50%);
        background:rgba(13,27,75,.82); color:#fff; border:1px solid rgba(255,255,255,.18);
        padding:7px 18px; border-radius:999px; font-size:.82rem; font-weight:600;
        backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); white-space:nowrap;
      }
      #scanStatus.text-success{background:rgba(22,163,74,.85);}
      #scanStatus.text-danger{background:rgba(239,68,68,.85);}
      #scanStatus.text-warning{background:rgba(245,158,11,.85); color:#1a1a1a;}

      .scan-tip{
        display:flex; align-items:center; gap:8px; margin-top:14px; padding:10px 16px;
        background:#eef2ff; border:1px solid #c7d2fe; border-radius:12px;
        font-size:.78rem; color:#2a4090;
      }
      .scan-tip i{font-size:18px; flex-shrink:0;}

      .scan-close-btn{
        padding:9px 24px; border-radius:12px; font-weight:700; font-size:.86rem;
        background:#fff; color:#6b7a99; border:1px solid #e6ebf5; cursor:pointer; transition:all .15s ease;
      }
      .scan-close-btn:hover{background:#f5f7fc; color:#0d1b4b;}

      /* ===== Motion preference ===== */
      @media (prefers-reduced-motion: reduce){
        *{scroll-behavior:auto!important;animation:none!important;transition:none!important}
      }
      /* --- Header: make it truly responsive --- */
.page-title-box{
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  flex-wrap:wrap;
  gap:.5rem 1rem;
}

/* compact badge on narrow screens */
.badge.badge-info{white-space:nowrap}

/* Phone layout */
@media (max-width: 575.98px){
  .page-title-box{align-items:flex-start}

  /* Title line can wrap; badge moves nicely after the title text */
  .page-title-box h4{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:.35rem .5rem;
    margin-bottom:.25rem;
  }

  /* Put the left block (title/subtitle) on its own row */
  .page-title-box > div:first-child{
    flex:1 1 100%;
    min-width:0;
  }

  /* Actions become full-width buttons in their own row */
  .header-actions{
    flex:1 1 100%;
    display:flex;
    gap:.5rem;
  }
  .header-actions .btn{
    flex:1 0 0;           /* both buttons same width */
    min-width:0;
  }

  /* Slightly smaller badge & subtitle so they don't wrap awkwardly */
  .page-title-box .badge{font-size:.72rem; padding:.25rem .5rem}
  .page-sub{font-size:.86rem}
}

/* Small tablets: keep actions side-by-side but allow wrapping */
@media (min-width:576px) and (max-width:767.98px){
  .header-actions{display:flex; gap:.5rem; flex-wrap:wrap}
  .header-actions .btn{flex:1}
}

    </style>

    <div class="content-page page-shell">
      <div class="content">
        <div class="container-fluid safe-bottom">

          <!-- Header -->
          <div class="row">
            <div class="col-12">
           <div class="page-title-box d-flex align-items-end justify-content-between flex-wrap gap-2">
  

  <div class="header-actions">
    <button id="btnOpenScanner" class="qr-header-btn qr-header-btn-primary" aria-haspopup="dialog">
      <i class="mdi mdi-qrcode-scan" aria-hidden="true"></i> <span>Scan QR</span>
    </button>
    <button id="btnToggleQR" class="qr-header-btn qr-header-btn-ghost">
      <i class="mdi mdi-eye-off-outline" aria-hidden="true"></i>
      <span class="d-none d-sm-inline">Hide QR</span>
    </button>
  </div>
</div>

              <hr class="divider" />
            </div>
          </div>

          <!-- Content -->
          <div id="gridRow">

            <!-- TOP: QR card (full width, centered) -->
            <div id="colQR" class="collapsible mb-4">
              <div class="card-clean shadow-soft rounded-2xl">
                <div class="content-pad">
                  <!-- Bank-card style QR -->
                  <div class="qr-card-wrap">
                    <div class="qr-card" id="qrBankCard">
                      <div class="qr-card-glow"></div>

                      <div class="qr-card-top">
                        <div class="qr-card-brand">
                          <div class="qc-chip"></div>
                          <div>
                            <div class="qc-name">FBMSO</div>
                            <div class="qc-sub">Attendance ID</div>
                          </div>
                        </div>
                        <div class="qr-card-status">
                          <span class="qc-dot"></span> <?= htmlspecialchars(($status ?? 'active')); ?>
                        </div>
                      </div>

                      <div class="qr-card-mid">
                        <div class="qr-card-qr">
                          <div id="qrcode" role="img" aria-label="Your permanent QR code"></div>
                        </div>
                        <div class="qr-card-info">
                          <div class="qc-label">Cardholder</div>
                          <div class="qc-name-display">
                            <?php if (!empty($last_name) || !empty($first_name)): ?>
                              <div class="qc-name-line1"><?= htmlspecialchars(strtoupper($last_name), ENT_QUOTES, 'UTF-8'); ?>,</div>
                              <div class="qc-name-line2"><?= htmlspecialchars(strtoupper($first_name), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                              <div class="qc-name-line1"><?= htmlspecialchars(strtoupper($student_number), ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>

                      <div class="qr-card-bottom">
                        <div class="qc-student">
                          <small>Student No.</small>
                          <?= htmlspecialchars($student_number); ?>
                        </div>
                        <div class="qc-logo">
                          Attendance Portal<br><b>FBMSO</b>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="qr-card-actions">
                    <button id="btnDownload" class="btn btn-primary">
                      <i class="mdi mdi-download" aria-hidden="true"></i> Download PNG
                    </button>
                    <button id="btnPrint" class="btn btn-outline-secondary">
                      <i class="mdi mdi-printer" aria-hidden="true"></i> Print
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- BOTTOM: Attendance (minimal) -->
            <div id="colAtt">
              <div class="section-h mb-3">
                <h5 class="mb-0">My Attendance</h5>
                <button id="btnAttFilter" class="qr-header-btn qr-header-btn-ghost" type="button">
                  <i class="mdi mdi-filter-variant" aria-hidden="true"></i>
                  <span id="attFilterLabel">All</span>
                </button>
              </div>

              <div class="att-minimal-wrap">
                <div class="table-responsive d-none d-md-block" style="-webkit-overflow-scrolling:touch">
                  <table class="table table-hover mb-0" id="myAttTable">
                    <thead>
                      <tr>
                        <th>Activity</th>
                        <th style="width:140px;">Check-In</th>
                        <th style="width:110px;">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><td colspan="3" class="text-center text-muted att-empty-row">No attendance yet.</td></tr>
                    </tbody>
                  </table>
                </div>
                <div id="mobileAttList" class="d-md-none">
                  <div class="att-empty-mobile">No attendance yet.</div>
                </div>
              </div>
            </div>
          </div>
          <!-- /Content -->

        </div>
      </div>

      <!-- Attendance Filter Modal -->
      <div class="modal fade" id="attFilterModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="attFilterTitle">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
          <div class="modal-content rounded-2xl overflow-hidden">
            <div class="modal-header">
              <h5 class="modal-title" id="attFilterTitle"><i class="mdi mdi-filter-variant mr-1" aria-hidden="true"></i> Filter Attendance</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="att-filter-list">
                <button type="button" class="att-filter-option active" data-range="all">
                  <i class="mdi mdi-infinity"></i> All records
                </button>
                <button type="button" class="att-filter-option" data-range="today">
                  <i class="mdi mdi-calendar-today"></i> Today
                </button>
                <button type="button" class="att-filter-option" data-range="7">
                  <i class="mdi mdi-calendar-week"></i> Last 7 days
                </button>
                <button type="button" class="att-filter-option" data-range="30">
                  <i class="mdi mdi-calendar-month"></i> Last 30 days
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <?php include('includes/themecustomizer.php'); ?>

  <!-- QRCode -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <!-- html2canvas for card capture -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <!-- html5-qrcode for camera scanning -->
  <script src="https://unpkg.com/html5-qrcode"></script>

  <!-- Vendor bundle (unchanged) -->
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
  <script src="<?= base_url(); ?>assets/js/pages/datatables.init.js"></script>

  <!-- ===== Student page logic ===== -->
  <script>
    (function () {
      /* ===== CI-aware base for check-in (works with subfolders / index.php) ===== */
      const CHECKIN_BASE = <?= json_encode(rtrim(site_url('attendance/checkin/'),'/') . '/'); ?>;

      /* ===== Permanent QR render ===== */
      const token = <?= json_encode($token) ?>;
      const qrEl  = document.getElementById('qrcode');
      new QRCode(qrEl, { text: token, width: qrEl.clientWidth, height: qrEl.clientWidth });
      // Resize QR on viewport changes
      const ro = new ResizeObserver(entries => {
        for (const e of entries){
          const s = Math.floor(e.contentRect.width);
          qrEl.innerHTML = '';
          new QRCode(qrEl, { text: token, width: s, height: s });
        }
      });
      ro.observe(qrEl);

      /* ===== Download / Print the whole card ===== */
      const qrCardEl = document.getElementById('qrBankCard');

      function captureCard() {
        return html2canvas(qrCardEl, {
          scale: 3,
          backgroundColor: null,
          useCORS: true,
          logging: false
        }).then(canvas => canvas.toDataURL('image/png'));
      }

      document.getElementById('btnDownload').addEventListener('click', function () {
        captureCard().then(dataUrl => {
          const a = document.createElement('a');
          a.href = dataUrl; a.download = 'fbmso-attendance-card.png';
          document.body.appendChild(a); a.click(); document.body.removeChild(a);
        }).catch(() => {
          // Fallback: just the QR
          const img = qrEl.querySelector('img') || qrEl.querySelector('canvas');
          if (!img) return;
          const dataUrl = img.tagName.toLowerCase()==='img' ? img.src : img.toDataURL('image/png');
          const a = document.createElement('a'); a.href = dataUrl; a.download = 'my-qr.png';
          document.body.appendChild(a); a.click(); document.body.removeChild(a);
        });
      });

      document.getElementById('btnPrint').addEventListener('click', function () {
        captureCard().then(dataUrl => {
          const win = window.open('', 'printwin');
          win.document.write(`
            <html><head><title>FBMSO Attendance Card</title>
            <style>
              @media print{body{margin:0}}
              body{display:flex;align-items:center;justify-content:center;min-height:100vh;min-height:100dvh;background:#fff;margin:0}
              img{max-width:90vw;max-height:90vh;border-radius:22px}
            </style>
            </head><body><img src="${dataUrl}" alt="FBMSO Attendance Card">
            <script>window.onload=function(){window.print();setTimeout(()=>window.close(),100)}<\/script>
            </body></html>`);
          win.document.close();
        }).catch(() => {
          // Fallback: just the QR
          const img = qrEl.querySelector('img') || qrEl.querySelector('canvas');
          if (!img) return;
          const dataUrl = img.tagName.toLowerCase()==='img' ? img.src : img.toDataURL('image/png');
          const win = window.open('', 'printwin');
          win.document.write(`
            <html><head><title>My QR</title>
            <style>@media print{body{margin:0}}body{display:flex;align-items:center;justify-content:center;height:100vh;height:100dvh;background:#fff}img{width:480px;height:480px}</style>
            </head><body><img src="${dataUrl}" alt="QR">
            <script>window.onload=function(){window.print();setTimeout(()=>window.close(),100)}<\/script>
            </body></html>`);
          win.document.close();
        });
      });

      /* ===== Show/Hide QR logic ===== */
      const colQR  = document.getElementById('colQR');
      const btnTgl = document.getElementById('btnToggleQR');

      function setQrHidden(hidden){
        if (hidden){
          colQR.classList.add('d-none');
          btnTgl.innerHTML = '<i class="mdi mdi-eye-outline" aria-hidden="true"></i> <span class="d-none d-sm-inline">Show QR</span>';
          localStorage.setItem('qrHidden','1');
        } else {
          colQR.classList.remove('d-none');
          btnTgl.innerHTML = '<i class="mdi mdi-eye-off-outline" aria-hidden="true"></i> <span class="d-none d-sm-inline">Hide QR</span>';
          localStorage.setItem('qrHidden','0');
        }
      }
      // Default VISIBLE the first time (if no preference saved)
      (function(){
        const stored = localStorage.getItem('qrHidden');
        setQrHidden(stored ? (stored === '1') : false);
      })();
      btnTgl.addEventListener('click', ()=> setQrHidden(colQR.classList.contains('d-none') ? false : true));

      /* ===== Attendance table ===== */
      const tbody = document.querySelector('#myAttTable tbody');
      const rangeBtns = document.querySelectorAll('.att-filter-option');
      let allRows = [];
      const mobileList = document.getElementById('mobileAttList');
      const escapeHtml = function (str) {
        return String(str ?? '').replace(/[&<>\"']/g, function (c) {
          return ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;','\'':'&#39;'}[c]);
        });
      };

      function sesLbl(s){ return ({am:'Morning', pm:'Afternoon', eve:'Evening'})[s||''] || '—'; }
      function fmt(iso){ if(!iso) return ''; try{ return moment(iso).format('hh:mm:ss A'); }catch(e){ return iso; } }
      function dur(a,b){
        if (!a || !b) return '';
        const mins = Math.max(0, Math.round((new Date(b)-new Date(a))/60000));
        if (mins < 60) return mins+'m';
        const h=Math.floor(mins/60), m=mins%60;
        return h+'h '+(m?m+'m':'');
      }
      function statusBadge(row){
        const open = !row.checked_out_at;
        return open ? '<span class="badge badge-warning">Open</span>'
                    : '<span class="badge badge-success">Completed</span>';
      }
      function srcBadge(src){
        const s=(src||'').toLowerCase();
        if (s==='qr') return '<span class="badge badge-soft">QR</span>';
        if (s==='manual') return '<span class="badge badge-secondary">Manual</span>';
        return '<span class="badge badge-light">—</span>';
      }
      function withinRange(row, range){
        if (range==='all') return true;
        const t=row.checked_in_at||row.checked_out_at; if (!t) return false;
        const d=moment(t);
        if (range==='today') return d.isSame(moment(),'day');
        const days=parseInt(range,10);
        return d.isAfter(moment().subtract(days,'days'));
      }

      function render(rows){
        tbody.innerHTML = '';
        if (mobileList) mobileList.innerHTML = '';
        if (!rows.length){
          tbody.innerHTML = '<tr><td colspan="3" class="att-empty-row">No attendance yet.</td></tr>';
          if (mobileList) mobileList.innerHTML = '<div class="att-empty-mobile">No attendance yet.</div>';
          return;
        }
        let mobileHtml = '';
        rows.forEach((r,i)=>{
          const titleRaw = (r.title && r.title.trim()) ? r.title : (r.activity_id ? ('Activity #' + r.activity_id) : 'Activity');
          const title = escapeHtml(titleRaw);
          const dateStr = r.activity_date ? moment(r.activity_date).format('MMM D, YYYY') : '';
          const checkIn = r.checked_in_at ? fmt(r.checked_in_at) : '�';
          const checkOut = r.checked_out_at ? fmt(r.checked_out_at) : '�';
          const durationText = r.checked_out_at ? dur(r.checked_in_at, r.checked_out_at) : '�';
          const statusHtml = statusBadge(r);
          const tr = document.createElement('tr');
          tr.innerHTML =
            '<td>'+
              '<div class="att-activity-name">'+title+'</div>'+
              (dateStr?'<div class="att-activity-date">'+escapeHtml(dateStr)+'</div>':'')+
            '</td>'+
            '<td class="att-time">'+ checkIn +'</td>'+
            '<td>'+ statusHtml +'</td>';
          tbody.appendChild(tr);

          if (mobileList) {
            mobileHtml += `
              <div class="att-min-card">
                <div class="att-min-card-top">
                  <div>
                    <div class="att-min-card-name">${title}</div>
                    ${dateStr ? `<div class="att-min-card-date">${escapeHtml(dateStr)}</div>` : ''}
                  </div>
                  ${statusHtml}
                </div>
                <div class="att-min-card-time">In: ${escapeHtml(checkIn)}</div>
              </div>`;
          }
        });
        if (mobileList) mobileList.innerHTML = mobileHtml;
      }
      function applyRange(range){
        const filtered=allRows.filter(r=>withinRange(r,range));
        render(filtered);
        rangeBtns.forEach(b=>b.classList.toggle('active', b.getAttribute('data-range')===range));
        const labels = {all:'All', today:'Today', '7':'7d', '30':'30d'};
        const lbl = document.getElementById('attFilterLabel');
        if (lbl) lbl.textContent = labels[range] || 'All';
      }

      fetch('<?= site_url('attendance/my_logs') ?>')
        .then(r=>r.json())
        .then(j=>{
          allRows=(j&&j.ok&&Array.isArray(j.rows))?j.rows:[];
          allRows.sort((a,b)=> (a.checked_in_at<b.checked_in_at)?1:-1);
          applyRange('all');
        })
        .catch(()=>{ tbody.innerHTML='<tr><td colspan="3" class="att-empty-row text-danger">Failed to load.</td></tr>'; });

      // Filter modal: open + select
      const btnAttFilter = document.getElementById('btnAttFilter');
      if (btnAttFilter) {
        btnAttFilter.addEventListener('click', ()=> $('#attFilterModal').modal('show'));
      }
      rangeBtns.forEach(btn=>{
        btn.addEventListener('click', ()=>{
          applyRange(btn.getAttribute('data-range'));
          $('#attFilterModal').modal('hide');
        });
      });

      /* ===== Modal scanner (hidden by default) ===== */
      // Modal markup injected once (keeps your view clean)
      const modalHtml = `
<div class="modal fade" id="studentScanModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="scanTitle">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scanTitle"><i class="mdi mdi-qrcode-scan" aria-hidden="true"></i> Scan Poster QR</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="scan-toolbar">
          <label for="cameraSelect">Camera</label>
          <select id="cameraSelect"></select>
          <button id="btnStart" class="scan-btn scan-btn-start"><i class="mdi mdi-play" aria-hidden="true"></i> Start</button>
          <button id="btnStop" class="scan-btn scan-btn-stop"><i class="mdi mdi-stop" aria-hidden="true"></i> Stop</button>
          <button id="btnUpload" class="scan-btn scan-btn-upload"><i class="mdi mdi-upload" aria-hidden="true"></i> Upload</button>
          <input type="file" id="qrFileInput" accept="image/*" class="d-none" aria-label="Upload QR image">
          <label class="scan-toggle-fix" title="Fix mirrored front cams">
            <input id="toggleDisableFlip" type="checkbox" /> Front-cam fix
          </label>
          <div class="scan-mode-group">
            <span class="scan-mode-label">Mode</span>
            <div class="scan-mode-btns">
              <button id="sModeIn"  type="button" class="scan-mode-btn active-in">IN</button>
              <button id="sModeOut" type="button" class="scan-mode-btn">OUT</button>
            </div>
          </div>
        </div>
        <div class="scan-wrap">
          <div id="reader"></div>
          <div id="scanStatus" aria-live="polite">Starting camera…</div>
        </div>
        <div class="scan-tip">
          <i class="mdi mdi-lightbulb-on-outline"></i>
          Fill the square with the poster QR. On laptops, try <b>Front-cam fix</b> if the camera is mirrored.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="scan-close-btn" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      const btnOpen = document.getElementById('btnOpenScanner');
      btnOpen.addEventListener('click', ()=> $('#studentScanModal').modal({backdrop:'static',keyboard:false}));

      // Scanner code attaches when modal is shown, and stops when hidden
      let qr = null, running=false, starting=false, stopping=false, devicesLoaded=false, forceDisableFlip=false;

      // ===== Student scan mode (IN / OUT)
      let scanMode = (localStorage.getItem('studentScanMode') === 'out') ? 'out' : 'in';

      function renderModeButtons(){
        const inBtn  = document.getElementById('sModeIn');
        const outBtn = document.getElementById('sModeOut');
        if (!inBtn || !outBtn) return;
        if (scanMode === 'in'){
          inBtn.classList.add('active-in');
          outBtn.classList.remove('active-out');
        } else {
          outBtn.classList.add('active-out');
          inBtn.classList.remove('active-in');
        }
      }
      function setScanMode(m){
        scanMode = (m === 'out') ? 'out' : 'in';
        localStorage.setItem('studentScanMode', scanMode);
        renderModeButtons();
      }
      document.addEventListener('click', function(e){
        if (e.target && e.target.id === 'sModeIn')  setScanMode('in');
        if (e.target && e.target.id === 'sModeOut') setScanMode('out');
      });

      function setStatus(t,cls){
        const el = document.getElementById('scanStatus');
        if (!el) return;
        el.textContent = t; el.className=''; if (cls) el.classList.add(cls);
      }
      function resizeReader(){
        const readerEl = document.getElementById('reader');
        if (!readerEl) return;
        const isSmall = window.innerWidth < 768;
        const w = readerEl.clientWidth || 480;
    const ar = isSmall ? (4/3) : (16/9);
readerEl.style.height = Math.round(w / ar) + 'px';

      }
      function extractActivityIdFrom(anyString) {
        const m = String(anyString).match(/attendance\/checkin\/(\d+)/i);
        return m ? m[1] : null;
      }
      function goToCheckin(id) {
        const base = CHECKIN_BASE + String(id);
        const hasQuery = base.includes('?');
        const url = base + (hasQuery ? '&' : '?') + 'direction=' + encodeURIComponent(scanMode);
        window.location.href = url;
      }

      async function enumerateCameras(){
        const camSel = document.getElementById('cameraSelect');
        try{
          const devs = await Html5Qrcode.getCameras();
          camSel.innerHTML='';
          if (!devs || !devs.length){ setStatus('No camera found','text-danger'); return []; }
          let backIndex=-1;
          devs.forEach((d,i)=>{
            const opt=document.createElement('option');
            opt.value=d.id; opt.textContent=d.label || ('Camera '+(i+1));
            if (backIndex===-1 && /back|rear|environment/i.test(d.label||'')) backIndex=i;
            camSel.appendChild(opt);
          });
          camSel.selectedIndex = (backIndex!==-1)? backIndex : 0;
          devicesLoaded = true; return devs;
        }catch(e){ setStatus('Camera error','text-danger'); return []; }
      }

      function cfg(env){
        const isDesktop = !/Android|iPhone|iPad|iPod/i.test(navigator.userAgent||'');
        if (env==='ios') {
          return {
            fps: 18, rememberLastUsedCamera: true, willReadFrequently: true, disableFlip: !!forceDisableFlip,
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            videoConstraints: { facingMode:{ideal:'environment'}, width:{ideal:1920}, height:{ideal:1080} }
          };
        }
        return {
          fps: 24,
          qrbox: isDesktop ? function(w,h){ const s=Math.floor(Math.min(w,h)*0.72); return {width:s,height:s}; } : undefined,
          aspectRatio: isDesktop ? 1.3333 : (window.innerWidth<768?1.3333:1.7778),
          rememberLastUsedCamera: true, showTorchButtonIfSupported: true, willReadFrequently: true,
          experimentalFeatures: { useBarCodeDetectorIfSupported: true }, disableFlip: !!forceDisableFlip,
          videoConstraints: { facingMode:{ideal:'environment'}, width:{ideal: isDesktop?2560:1920}, height:{ideal:isDesktop?1440:1080}, advanced:[{focusMode:'continuous'}] }
        };
      }

      async function start(id){
        if (starting || running) return;
        starting = true;
        try{
          const ua = navigator.userAgent || navigator.vendor || '';
          const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
          if (!qr) qr = new Html5Qrcode('reader',{verbose:false});
          if (isIOS) {
            try{
              const tmp = await navigator.mediaDevices.getUserMedia({audio:false,video:{facingMode:{ideal:'environment'}}});
              tmp.getTracks().forEach(t=>t.stop());
            }catch(_){ setStatus('Camera permission denied on iOS','text-danger'); starting=false; return; }
          }
          const cfgObj = cfg(isIOS ? 'ios':'default');
          const cameraConfig = id ? { deviceId:{ exact:id } } : { facingMode:{ ideal:'environment' } };

          await qr.start(
            cameraConfig,
            cfgObj,
            (decodedText)=>{
              if (navigator.vibrate) navigator.vibrate(40);
              setStatus('QR detected — opening…','text-success');
              const activityId = extractActivityIdFrom(decodedText);
              if (activityId) { goToCheckin(activityId); }
              else {
                setStatus('Not a poster check-in link','text-warning');
                setTimeout(()=>setStatus('Looking for a QR code…',''), 1200);
              }
            },
            (_e)=>{ /* silent */ }
          );

          running = true; setStatus('Looking for a QR code…','');
          const vid = document.getElementById('reader')?.querySelector('video');
          if (vid) {
            vid.setAttribute('playsinline','true');
            vid.setAttribute('webkit-playsinline','true');
            vid.setAttribute('muted',''); vid.muted = true;
            try { vid.play && vid.play(); } catch(_){}
          }
          resizeReader();
          window.addEventListener('resize', resizeReader);
          window.addEventListener('orientationchange', resizeReader);
        }catch(err){ setStatus('Start failed — check permissions','text-danger'); }
        finally{ starting=false; }
      }

      async function stop(){
        if (!qr || !running || stopping) return;
        stopping = true;
        try{ await qr.stop(); }catch(_){}
        running=false; stopping=false; setStatus('Scanner stopped','');
        window.removeEventListener('resize', resizeReader);
        window.removeEventListener('orientationchange', resizeReader);
      }

      // Wire modal lifecycle
      $(document).on('shown.bs.modal', '#studentScanModal', async function(){
        forceDisableFlip = false;
        const tFlip = document.getElementById('toggleDisableFlip');
        if (tFlip) { tFlip.checked = false; tFlip.onchange = ()=> (forceDisableFlip = !!tFlip.checked); }

        renderModeButtons(); // init the mode buttons

        await enumerateCameras();
        const camSel = document.getElementById('cameraSelect');
        await start(camSel && camSel.value);
      });

      $(document).on('hidden.bs.modal', '#studentScanModal', async function(){ await stop(); });

      // Toolbar buttons inside modal
      $(document).on('click','#btnStart', async function(){ const camSel = document.getElementById('cameraSelect'); if (!devicesLoaded) await enumerateCameras(); await start(camSel && camSel.value); });
      $(document).on('click','#btnStop', async function(){ await stop(); });
      $(document).on('change','#cameraSelect', async function(){ await stop(); await start(this.value); });

      // Upload-to-scan
      $(document).on('click','#btnUpload', function(){ document.getElementById('qrFileInput').click(); });
      $(document).on('change','#qrFileInput', async function(e){
        const file = e.target.files && e.target.files[0]; if (!file) return;
        try{
          if (!qr) qr = new Html5Qrcode('reader',{verbose:false});
          if (running) await stop();
          const txt = await qr.scanFile(file, false);
          const activityId = extractActivityIdFrom(txt);
          if (activityId) { goToCheckin(activityId); }
          else setStatus('Not a poster check-in link','text-warning');
        }catch(_){ setStatus('Couldn’t read that image','text-danger'); }
        finally{ this.value=''; }
      });

      // HTTPS hint for iOS/Safari
      (function(){
        const ua = navigator.userAgent || navigator.vendor || '';
        const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
        if ((isIOS || isSafari) && location.protocol !== 'https:' && location.hostname !== 'localhost') {
          const warn = document.createElement('div');
          warn.className = 'scan-tip';
          warn.style.background = '#fef3c7';
          warn.style.borderColor = '#fcd34d';
          warn.style.color = '#92400e';
          warn.innerHTML = '<i class="mdi mdi-alert-outline"></i> <b>iOS camera requires HTTPS or localhost.</b> Please open this page over https://';
          document.getElementById('studentScanModal')?.querySelector('.modal-body')?.prepend(warn);
        }
      })();
    })();
  </script>
</body>
</html>

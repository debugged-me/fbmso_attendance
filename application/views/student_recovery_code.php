<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body class="antialiased">
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <style>
      :root{
        --bg:#f8fafc;--card:#ffffff;--muted:#6b7a99;--line:#e6ebf5;--brand:#2563eb;--success:#16a34a;--warning:#f59e0b;
      }
      @media (prefers-color-scheme: dark){
        :root{--bg:#0b1220;--card:#0f172a;--muted:#94a3b8;--line:#1e293b;--brand:#3b82f6;--success:#22c55e;--warning:#fbbf24}
        body{color:#e2e8f0}
      }
      body{background:var(--bg)}
      .text-mono{font-family:ui-monospace,Menlo,Consolas,monospace}
      .shadow-soft{box-shadow:0 8px 30px rgba(2,6,23,.06)}
      .rounded-2xl{border-radius:16px}
      .content-pad{padding:18px}
      .safe-bottom{padding-bottom:calc(16px + env(safe-area-inset-bottom))}
      .card-clean{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 6px 18px rgba(13,27,75,.05);overflow:hidden}

      .page-title-box h4{margin:0 0 .25rem;font-weight:800;color:#0d1b4b}
      .divider{border:0;height:3px;width:64px;background:linear-gradient(90deg,#2a4090,#4266d4);border-radius:3px;margin:10px 0 18px}
      @media (prefers-color-scheme: dark){.page-title-box h4{color:#e2e8f0}}

      .rc-wrap{max-width:640px;margin:0 auto}

      .rc-status{
        display:flex;align-items:center;gap:12px;
        padding:14px 16px;border-radius:14px;margin-bottom:18px;
        font-size:.88rem;font-weight:600;
      }
      .rc-status i{font-size:22px;flex-shrink:0}
      .rc-status.is-set{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
      .rc-status.is-empty{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
      @media (prefers-color-scheme: dark){
        .rc-status.is-set{background:#0a2818;border-color:#1a5030;color:#60e0a0}
        .rc-status.is-empty{background:#2a2210;border-color:#5c4a1a;color:#fbbf24}
      }
      .rc-status small{display:block;font-weight:400;font-size:.78rem;opacity:.85;margin-top:2px}

      .rc-explainer{color:var(--muted);font-size:.88rem;line-height:1.7;margin-bottom:18px}
      .rc-explainer b{color:#334155}
      @media (prefers-color-scheme: dark){.rc-explainer b{color:#cbd5e1}}

      .rc-code-card{
        background:linear-gradient(135deg,#1a2a6c 0%,#2a4090 45%,#3b5fd4 100%);
        border-radius:18px;padding:26px 22px;color:#fff;text-align:center;
        box-shadow:0 20px 44px rgba(13,27,75,.3);margin-bottom:16px;
        position:relative;overflow:hidden;
      }
      .rc-code-card::before{
        content:'';position:absolute;width:220px;height:220px;border-radius:50%;
        background:radial-gradient(circle,rgba(124,255,178,.3) 0%,transparent 70%);
        top:-80px;right:-60px;filter:blur(8px);
      }
      .rc-code-label{font-size:.68rem;font-weight:800;letter-spacing:.18em;text-transform:uppercase;opacity:.75}
      .rc-code-value{
        font-family:ui-monospace,Menlo,Consolas,monospace;
        font-size:clamp(1.4rem,5.5vw,1.9rem);font-weight:800;letter-spacing:.08em;
        margin:10px 0 4px;word-break:break-all;
      }
      .rc-code-note{font-size:.74rem;opacity:.8;line-height:1.5}
      .rc-code-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px;position:relative}
      .rc-code-actions .btn{border-radius:12px;font-weight:700;font-size:.84rem;padding:9px 18px}
      .rc-btn-dl{background:#fff;color:#1a2a6c;border:none}
      .rc-btn-dl:hover{background:#eef2ff;color:#1a2a6c}
      .rc-btn-copy{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.35)}
      .rc-btn-copy:hover{background:rgba(255,255,255,.24);color:#fff}

      .rc-alert{
        display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;
        padding:12px 14px;border-radius:12px;font-size:.84rem;line-height:1.5;
        background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
      }
      .rc-alert i{margin-top:2px;flex-shrink:0}
      @media (prefers-color-scheme: dark){
        .rc-alert{background:#2a1218;border-color:#5c2430;color:#f0a0a8}
      }

      .rc-regen{margin-top:22px;padding-top:18px;border-top:1px dashed var(--line)}
      .rc-regen p{font-size:.8rem;color:var(--muted);line-height:1.6;margin-bottom:12px}
      .rc-regen .btn{border-radius:12px;font-weight:700;font-size:.84rem;padding:10px 20px}
      .rc-pass-field{margin-bottom:14px;max-width:300px;text-align:left}
      .rc-pass-field label{display:block;font-size:.72rem;font-weight:700;color:var(--muted);margin-bottom:5px}
      .rc-pass-field input{
        width:100%;padding:9px 12px;border-radius:10px;font-size:.86rem;
        border:1px solid var(--line);background:var(--panel,#fff);color:inherit;
      }
      .rc-pass-field input:focus{outline:none;border-color:#4f6ef7;box-shadow:0 0 0 3px rgba(79,110,247,.15)}
      .rc-once{
        display:flex;align-items:flex-start;gap:8px;margin-top:14px;
        font-size:.74rem;color:var(--muted);line-height:1.5;
      }
      .rc-once i{margin-top:1px;flex-shrink:0}
    </style>

    <div class="content-page page-shell">
      <div class="content">
        <div class="container-fluid safe-bottom">

          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4>Recovery Code</h4>
              </div>
              <hr class="divider" />
            </div>
          </div>

          <div class="rc-wrap">
            <div class="card-clean shadow-soft rounded-2xl">
              <div class="content-pad">

                <?php if (!empty($rotate_error)): ?>
                  <div class="rc-alert">
                    <i class="mdi mdi-alert-circle-outline" aria-hidden="true"></i>
                    <span><?= html_escape($rotate_error); ?></span>
                  </div>
                <?php endif; ?>

                <?php if (!empty($new_code)): ?>
                  <!-- One-time display: the hash is already stored, this exact
                       render is the only place the code will ever exist. -->
                  <div class="rc-code-card" id="rcCodeCard"
                       data-code="<?= html_escape($new_code); ?>"
                       data-student="<?= html_escape($student_number); ?>">
                    <div class="rc-code-label">Your new recovery code</div>
                    <div class="rc-code-value"><?= html_escape($new_code); ?></div>
                    <div class="rc-code-note">
                      Any previous code stopped working the moment this one was created.
                      Download it now — it will not be shown again.
                    </div>
                    <div class="rc-code-actions">
                      <button type="button" class="btn rc-btn-dl" id="rcDownload">
                        <i class="mdi mdi-download" aria-hidden="true"></i> Download (.txt)
                      </button>
                      <button type="button" class="btn rc-btn-copy" id="rcCopy">
                        <i class="mdi mdi-content-copy" aria-hidden="true"></i> Copy
                      </button>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="rc-status <?= !empty($code_set) ? 'is-set' : 'is-empty'; ?>">
                    <i class="mdi <?= !empty($code_set) ? 'mdi-shield-check-outline' : 'mdi-shield-alert-outline'; ?>" aria-hidden="true"></i>
                    <div>
                      <?php if (!empty($code_set)): ?>
                        A recovery code is active on your account.
                        <small>Issued <?= html_escape(!empty($code_set_at) ? date('F j, Y g:i A', strtotime($code_set_at)) : 'recently'); ?> · stored in your credentials file</small>
                      <?php else: ?>
                        No recovery code on your account yet.
                        <small>Generate one below — it takes a second.</small>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="rc-explainer">
                  Your recovery code resets your password <b>without email</b> —
                  use it on the sign-in page under <b>Forgot password &rarr; Verify identity manually</b>.
                  Keep it in the credentials file you downloaded when you registered.
                  If you lost it, generate a new one below; the old code stops working immediately.
                </div>

                <div class="rc-regen">
                  <p>
                    <?php if (!empty($code_set)): ?>
                      Generating a new code <b>invalidates the current one</b> — update
                      your saved file afterwards.
                    <?php else: ?>
                      This creates your account's recovery code. It will be shown
                      <b>exactly once</b> — download or copy it before leaving this page.
                    <?php endif; ?>
                  </p>
                  <form method="post" action="<?= site_url('student/recovery_code'); ?>"
                        onsubmit="return confirm('<?= !empty($code_set)
                            ? 'Generate a new recovery code? Your current one will stop working immediately.'
                            : 'Generate your recovery code now?'; ?>');">
                    <div class="rc-pass-field">
                      <label for="rcCurrentPass">Current password</label>
                      <input type="password" id="rcCurrentPass" name="current_password"
                             autocomplete="current-password" required
                             placeholder="Confirm it's really you">
                    </div>
                    <button type="submit" class="btn btn-primary">
                      <i class="mdi mdi-refresh" aria-hidden="true"></i>
                      <?= !empty($code_set) ? 'Generate a new code' : 'Generate my code'; ?>
                    </button>
                  </form>
                  <div class="rc-once">
                    <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                    <span>Only a secure fingerprint of the code is kept in the system — the code itself is never stored, so it can't be shown again later.</span>
                  </div>
                </div>

              </div>
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
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var card = document.getElementById('rcCodeCard');
      if (!card) { return; }
      var code = card.getAttribute('data-code') || '';
      var student = card.getAttribute('data-student') || '';
      var portal = <?= json_encode(base_url()); ?>;

      function fileText() {
        return [
          'Attendance Portal — Account Recovery Code',
          '==========================================',
          '',
          'Student ID    : ' + student,
          'Recovery code : ' + code,
          '',
          'What this does:',
          '  Resets your password WITHOUT email. Use it on the',
          '  sign-in page under Forgot password -> Verify identity manually.',
          '',
          'Portal: ' + portal,
          '',
          'Keep this file private — anyone holding it can reset',
          'your password. If it leaks, generate a new code from',
          'the Recovery Code page; the old one dies instantly.',
        ].join('\n');
      }

      var dl = document.getElementById('rcDownload');
      if (dl) {
        dl.addEventListener('click', function () {
          var blob = new Blob([fileText()], { type: 'text/plain;charset=utf-8' });
          var url = URL.createObjectURL(blob);
          var a = document.createElement('a');
          a.href = url;
          a.download = 'attendance-recovery-code-' + student.replace(/[^0-9A-Za-z-]/g, '') + '.txt';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
        });
      }

      var cp = document.getElementById('rcCopy');
      if (cp) {
        cp.addEventListener('click', function () {
          var done = function () {
            cp.innerHTML = '<i class="mdi mdi-check" aria-hidden="true"></i> Copied';
            setTimeout(function () {
              cp.innerHTML = '<i class="mdi mdi-content-copy" aria-hidden="true"></i> Copy';
            }, 2000);
          };
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText('Recovery code: ' + code).then(done, done);
          } else {
            var ta = document.createElement('textarea');
            ta.value = 'Recovery code: ' + code;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
          }
        });
      }
    });
  </script>
</body>

</html>

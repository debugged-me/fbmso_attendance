<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>

<body>
  <div id="wrapper">
    <?php include('includes/top-nav-bar.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- Title -->
          <div class="row">
            <div class="col-12">
              <div class="page-title-box">
                <h4 class="up-page-title">Change Password</h4>
                <div class="up-page-sub">Keep your account secure with a strong password.</div>
                <hr class="up-divider" />
              </div>
            </div>
          </div>

          <!-- Flash messages -->
          <?php $flashMsg = $this->session->flashdata('msg'); ?>
          <?php if ($flashMsg): ?>
            <div class="up-flash up-flash-success"><?= $flashMsg; ?></div>
          <?php endif; ?>
          <?php if (validation_errors() != NULL): ?>
            <div class="up-flash up-flash-danger"><?= validation_errors(); ?></div>
          <?php endif; ?>

          <!-- Identity strip -->
          <div class="up-id-strip">
            <div class="up-id-icon"><i class="mdi mdi-lock-reset"></i></div>
            <div>
              <div class="up-id-name"><?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="up-id-meta">Password security</div>
            </div>
          </div>

          <!-- Form card -->
          <div class="row">
            <div class="col-12">
              <div class="up-card">
                <div class="up-card-head">
                  <h4><i class="mdi mdi-textbox-password"></i> Update Password</h4>
                </div>
                <div class="up-card-body">
                  <form method="POST" action="<?= base_url(); ?>page/update_password" enctype="multipart/form-data" id="pwForm"
                        data-username="<?= htmlspecialchars((string)$this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="txt_hidden" value="<?= htmlspecialchars($this->session->userdata('username'), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="up-section-head">
                      <div class="up-section-dot"></div>
                      <div class="up-section-label">Credentials</div>
                      <div class="up-section-line"></div>
                    </div>

                    <div class="form-group">
                      <label for="currentpassword">Current Password</label>
                      <div class="pw-field">
                        <input type="password" class="form-control" id="currentpassword" name="currentpassword" placeholder="Enter your current password" autocomplete="current-password" required>
                        <button type="button" class="pw-eye" data-target="currentpassword" aria-controls="currentpassword" aria-label="Show password" aria-pressed="false"><i class="mdi mdi-eye-outline"></i></button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="newpassword">New Password</label>
                      <div class="pw-field">
                        <input type="password" class="form-control" id="newpassword" name="newpassword" placeholder="Enter your new password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="pwStrength pwRules" required>
                        <button type="button" class="pw-eye" data-target="newpassword" aria-controls="newpassword" aria-label="Show password" aria-pressed="false"><i class="mdi mdi-eye-outline"></i></button>
                      </div>

                      <div class="pw-meter" id="pwMeter" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                      <div class="pw-meter-row">
                        <span class="pw-strength" id="pwStrength" data-level="0" aria-live="polite">Password strength</span>
                        <span class="pw-tip" id="pwTip"></span>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="cnewpassword">Confirm Password</label>
                      <div class="pw-field">
                        <input type="password" class="form-control" id="cnewpassword" name="cnewpassword" placeholder="Repeat your new password" autocomplete="new-password" maxlength="72" required>
                        <button type="button" class="pw-eye" data-target="cnewpassword" aria-controls="cnewpassword" aria-label="Show password" aria-pressed="false"><i class="mdi mdi-eye-outline"></i></button>
                      </div>
                    </div>

                    <div class="pw-caps" id="pwCaps" hidden><i class="mdi mdi-apple-keyboard-caps"></i> Caps Lock is on</div>

                    <!-- Must match Page::update_password() / _validate_newpassword() -->
                    <ul class="pw-checklist" id="pwRules" aria-label="Password requirements">
                      <li data-rule="length"><i class="mdi mdi-checkbox-blank-circle-outline"></i> At least 8 characters <span class="sr-only pw-state">not yet</span></li>
                      <li data-rule="letter"><i class="mdi mdi-checkbox-blank-circle-outline"></i> At least one letter <span class="sr-only pw-state">not yet</span></li>
                      <li data-rule="number"><i class="mdi mdi-checkbox-blank-circle-outline"></i> At least one number <span class="sr-only pw-state">not yet</span></li>
                      <li data-rule="different"><i class="mdi mdi-checkbox-blank-circle-outline"></i> Different from your current password <span class="sr-only pw-state">not yet</span></li>
                      <li data-rule="match"><i class="mdi mdi-checkbox-blank-circle-outline"></i> New passwords match <span class="sr-only pw-state">not yet</span></li>
                    </ul>
                    <div class="up-hint">Symbols (like ! # @) and a mix of upper and lower case make it stronger.</div>

                    <div class="d-flex gap-2 mt-3">
                      <button type="submit" name="updatePwd" id="pwSubmit" class="up-btn up-btn-primary">
                        <i class="mdi mdi-content-save"></i> Update Password
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <?php include('includes/footer.php'); ?>
    </div>
  </div>

  <style>
    .pw-field { position: relative; }
    .up-card .pw-field .form-control { padding-right: 48px; }
    .pw-eye {
      position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
      width: 36px; height: 36px; border: 0; border-radius: 10px;
      background: transparent; color: var(--up-muted);
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 1.15rem; cursor: pointer;
      transition: background-color .15s ease, color .15s ease;
    }
    .pw-eye:hover { background: var(--up-soft); color: var(--up-blue); }
    .pw-eye:focus-visible { outline: 2px solid var(--up-blue-2); outline-offset: 1px; }

    .pw-meter { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 10px; }
    .pw-meter span { height: 6px; border-radius: 6px; background: var(--up-line); transition: background-color .25s ease; }
    .pw-meter[data-level="1"] span:nth-child(-n+1) { background: var(--up-red); }
    .pw-meter[data-level="2"] span:nth-child(-n+2) { background: var(--up-amber); }
    .pw-meter[data-level="3"] span:nth-child(-n+3) { background: var(--up-blue-2); }
    .pw-meter[data-level="4"] span { background: var(--up-green); }

    .pw-meter-row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-top: 6px; font-size: .76rem; }
    .pw-strength { font-weight: 700; color: var(--up-muted); white-space: nowrap; }
    .pw-strength[data-level="1"] { color: #b91c1c; }
    .pw-strength[data-level="2"] { color: #b45309; }
    .pw-strength[data-level="3"] { color: var(--up-blue); }
    .pw-strength[data-level="4"] { color: #15803d; }
    .pw-tip { color: var(--up-muted); text-align: right; }

    .pw-caps {
      display: inline-flex; align-items: center; gap: 6px; margin: 2px 0 12px;
      padding: 5px 10px; border-radius: 999px; font-size: .76rem; font-weight: 600;
      background: #fffbeb; color: #92400e; border: 1px solid #fde68a;
    }
    .pw-caps[hidden] { display: none; }

    .pw-checklist {
      list-style: none; margin: 4px 0 0; padding: 12px 14px;
      background: var(--up-soft); border: 1px solid var(--up-line); border-radius: 12px;
      display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 6px 18px;
    }
    .pw-checklist li { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: var(--up-muted); transition: color .2s ease; }
    .pw-checklist li i { font-size: 1rem; color: #b6c0d6; transition: color .2s ease, transform .2s ease; }
    .pw-checklist li.is-met { color: var(--up-ink); }
    .pw-checklist li.is-met i { color: var(--up-green); transform: scale(1.08); }
    .pw-checklist li.is-bad { color: #b91c1c; }
    .pw-checklist li.is-bad i { color: var(--up-red); }

    #pwSubmit[disabled] { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }

    @media (prefers-reduced-motion: reduce) {
      .pw-meter span, .pw-checklist li, .pw-checklist li i, .pw-eye { transition: none; }
    }
  </style>

  <script>
    (function () {
      var form = document.getElementById('pwForm');
      if (!form) return;

      var current = document.getElementById('currentpassword');
      var fresh   = document.getElementById('newpassword');
      var confirm = document.getElementById('cnewpassword');
      var meter   = document.getElementById('pwMeter');
      var label   = document.getElementById('pwStrength');
      var tip     = document.getElementById('pwTip');
      var caps    = document.getElementById('pwCaps');
      var submit  = document.getElementById('pwSubmit');
      var username = (form.getAttribute('data-username') || '').toLowerCase();

      // Any-language letter, like the server's \pL; plain A-Z on old browsers.
      var letterRe;
      try { letterRe = new RegExp('\\p{L}', 'u'); } catch (e) { letterRe = /[A-Za-z]/; }

      // Show / hide each field.
      Array.prototype.forEach.call(form.querySelectorAll('.pw-eye'), function (btn) {
        btn.addEventListener('click', function () {
          var input = document.getElementById(btn.getAttribute('data-target'));
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          btn.setAttribute('aria-pressed', show ? 'true' : 'false');
          btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
          btn.querySelector('i').className = show ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
          input.focus();
        });
      });

      // The server trims before checking, so the checklist does too.
      function val(input) { return input.value.replace(/^\s+|\s+$/g, ''); }

      // 0 = empty, 1 weak, 2 fair, 3 good, 4 strong.
      function strength(pw) {
        if (!pw) return 0;
        if (pw.length < 8) return 1;
        var kinds = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z0-9\s]/].filter(function (re) { return re.test(pw); }).length;
        var points = kinds + (pw.length >= 12 ? 1 : 0) + (pw.length >= 16 ? 1 : 0);
        var lower = pw.toLowerCase();
        var guessable = /^(.)\1+$/.test(pw)
          || /(password|passw0rd|qwerty|123456|abcdef|letmein|welcome|admin|fbmso)/.test(lower)
          || (username.length >= 3 && lower.indexOf(username) !== -1);
        if (guessable) points = Math.min(points, 2);
        return points <= 2 ? 1 : (points === 3 ? 2 : (points === 4 ? 3 : 4));
      }

      function nextTip(pw) {
        if (!pw) return '';
        if (pw.length < 8) return (8 - pw.length) + ' more character' + (pw.length === 7 ? '' : 's');
        var lower = pw.toLowerCase();
        if (/(password|passw0rd|qwerty|123456|abcdef|letmein|welcome|admin|fbmso)/.test(lower)
            || (username.length >= 3 && lower.indexOf(username) !== -1)) return 'Avoid common words and your username';
        if (!/[A-Z]/.test(pw) || !/[a-z]/.test(pw)) return 'Mix upper and lower case';
        if (!/[^A-Za-z0-9\s]/.test(pw)) return 'Add a symbol like ! or #';
        if (pw.length < 12) return 'Use 12 or more characters';
        return '';
      }

      var names = ['Password strength', 'Weak', 'Fair', 'Good', 'Strong'];

      function setRule(rule, state) {
        var li = form.querySelector('[data-rule="' + rule + '"]');
        li.classList.toggle('is-met', state === 'met');
        li.classList.toggle('is-bad', state === 'bad');
        li.querySelector('i').className = 'mdi ' + (state === 'met' ? 'mdi-check-circle'
          : state === 'bad' ? 'mdi-close-circle' : 'mdi-checkbox-blank-circle-outline');
        li.querySelector('.pw-state').textContent = state === 'met' ? 'done' : 'not yet';
      }

      function update() {
        var pw = val(fresh);
        var level = strength(pw);
        meter.setAttribute('data-level', level);
        label.setAttribute('data-level', level);
        label.textContent = names[level];
        tip.textContent = level === 4 ? '' : nextTip(pw);

        var rules = {
          length: pw.length >= 8,
          letter: letterRe.test(pw),
          number: /\d/.test(pw),
          different: pw !== '' && pw !== val(current),
          match: pw !== '' && pw === val(confirm)
        };
        Object.keys(rules).forEach(function (rule) {
          var state = rules[rule] ? 'met' : 'pending';
          if (!rules[rule] && rule === 'match' && val(confirm) !== '') state = 'bad';
          if (!rules[rule] && rule === 'different' && pw !== '' && val(current) !== '') state = 'bad';
          setRule(rule, state);
        });

        var ok = Object.keys(rules).every(function (r) { return rules[r]; }) && val(current) !== '';
        submit.disabled = !ok;
      }

      [current, fresh, confirm].forEach(function (input) {
        input.addEventListener('input', update);
        input.addEventListener('keyup', function (e) {
          if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock');
        });
        input.addEventListener('blur', function () { caps.hidden = true; });
      });

      update();
    })();
  </script>

  <?php include('includes/themecustomizer.php'); ?>
  <script src="<?= base_url(); ?>assets/js/vendor.min.js"></script>
  <script src="<?= base_url(); ?>assets/js/app.min.js"></script>
</body>
</html>

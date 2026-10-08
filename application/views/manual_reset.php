<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('includes/title.php'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="icon" type="image/png" href="<?= base_url(); ?>assets/images/Attendance.png">
    <link rel="stylesheet" href="<?= base_url(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url(); ?>assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= base_url(); ?>assets/css/home.css?v=30260838">
    <link href="<?= base_url(); ?>assets/fonts/DM_Sans/dm-sans.css?v=20260922" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=12'); ?>">

    <meta name="theme-color" content="#1a2942">
    <title>Attendance Portal | Manual Password Reset</title>

    <style>
        /* The login card caps itself at the viewport and relies on an inner
           panel scroll with center-aligned content — overflow then clips the
           top AND shows no scrollbar on macOS. This form is taller, so the
           card grows and the page scrolls instead. */
        .mr-card { min-height: 640px; max-height: none; margin: auto; }
        .mr-card .side-form { overflow: visible; }

        .verify-art-icon {
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(145deg, rgba(255,255,255,.17), rgba(255,255,255,.05));
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 25px 60px rgba(0,0,0,.13);
            backdrop-filter: blur(8px);
        }
        .verify-art-icon::before {
            content: '';
            position: absolute;
            width: 110px;
            height: 110px;
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,.12);
            animation: mr-pulse 2.5s ease-in-out infinite;
        }
        .verify-art-icon i {
            position: relative;
            z-index: 2;
            font-size: 4rem;
            color: #fff;
            filter: drop-shadow(0 8px 18px rgba(0,0,0,.15));
        }
        .verify-check {
            position: absolute;
            right: 22px;
            bottom: 23px;
            width: 39px;
            height: 39px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #4edb9a;
            color: #fff;
            border: 4px solid #344fa9;
            font-size: .9rem;
            box-shadow: 0 8px 20px rgba(0,0,0,.18);
            z-index: 3;
        }
        @keyframes mr-pulse {
            0%, 100% { transform: scale(1); opacity: .7; }
            50% { transform: scale(1.08); opacity: .35; }
        }

        .verify-message { display: flex; align-items: flex-start; gap: 9px; }
        .verify-message i { margin-top: 2px; flex-shrink: 0; }

        .verify-back {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 22px;
            font-size: .77rem;
            font-weight: 700;
            color: #6b7fa8;
            text-decoration: none;
            transition: color .18s ease, transform .18s ease;
        }
        .verify-back:hover { color: #3b5fd4; text-decoration: none; transform: translateX(-2px); }

        .text-mono { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace; letter-spacing: .04em; }

        .mr-description {
            font-size: .82rem;
            color: #8fa0c8;
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .mr-section-label {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #7e8fb1;
            margin: 20px 0 9px;
        }

        .mr-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }

        .mr-pill input { display: none; }

        .mr-pill span {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 11px 6px 9px;
            border: 1.5px solid #e4ebff;
            border-radius: 12px;
            background: #f7f9ff;
            color: #6b7fa8;
            font-size: .7rem;
            font-weight: 700;
            text-align: center;
            line-height: 1.25;
            cursor: pointer;
            transition: all .15s ease;
        }

        .mr-pill span i { font-size: 1rem; }

        .mr-pill:hover span {
            border-color: #b9c9f7;
            transform: translateY(-1px);
        }

        .mr-pill input:checked + span {
            background: linear-gradient(135deg, #2a4090, #4266d4);
            border-color: #2a4090;
            color: #fff;
            box-shadow: 0 8px 18px rgba(42, 64, 144, .25);
        }

        .mr-method-fields { margin-bottom: 4px; }

        .mr-method-hint {
            font-size: .72rem;
            color: #8a96b0;
            margin: -4px 0 10px;
            line-height: 1.45;
        }

        .mr-divider {
            border: 0;
            border-top: 1px dashed #dfe6f6;
            margin: 18px 0 4px;
        }

        .mr-method-fields[disabled] { display: none; }

        .match-msg {
            display: block;
            font-size: .72rem;
            font-weight: 600;
            margin-top: 5px;
            min-height: 1em;
        }
        .match-msg.is-ok { color: #1f8f53; }
        .match-msg.is-bad { color: #d64545; }

        .mr-captcha { margin-top: 14px; }
        .mr-captcha .g-recaptcha { transform: scale(.94); transform-origin: 0 0; }

        @media (max-width: 700px) {
            .mr-card { min-height: auto; }
            .side-form { padding-top: 32px; padding-bottom: 28px; }
            .mr-methods { grid-template-columns: 1fr; }
            .mr-pill span { flex-direction: row; justify-content: center; gap: 8px; padding: 10px; }
        }

        @media (prefers-color-scheme: dark) {
            .mr-description { color: #8fa0c8; }
            .mr-section-label { color: #5a6a8e; }
            .mr-pill span { background: #141d35; border-color: #2a3a5c; color: #8fa0c8; }
            .mr-pill:hover span { border-color: #3d5090; }
            .mr-pill input:checked + span { background: linear-gradient(135deg, #2a4090, #4266d4); border-color: #4266d4; color: #fff; }
            .mr-method-hint { color: #5a6a8e; }
            .mr-divider { border-top-color: #2a3a5c; }
        }
    </style>
</head>

<body>
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>

    <main class="card mr-card">

        <!-- =========================== LEFT SIDE ============================ -->
        <div class="side-art">
            <div class="ring ring-1"></div>
            <div class="ring ring-2"></div>
            <div class="art-content">
                <div class="verify-art-icon">
                    <i class="fa fa-key"></i>
                    <div class="verify-check"><i class="fa fa-shield"></i></div>
                </div>
                <p class="art-tagline">Attendance Portal</p>
                <h2 class="art-title">Manual reset</h2>
                <p class="art-desc">
                    Recover your account without email —<br>
                    prove it's really you.
                </p>
            </div>
        </div>

        <!-- =========================== RIGHT SIDE ============================ -->
        <div class="side-form">
            <div class="brand-row">
                <div class="brand-icon">
                    <img src="<?= base_url(); ?>upload/banners/logo1.png" alt="FBMSO Logo">
                </div>
                <div class="brand-text">
                    Attendance Portal
                    <small>Faculty of Business Management Student Org.</small>
                </div>
            </div>

            <h1 class="form-title">Reset without email</h1>
            <p class="mr-description">
                Use this only when the reset email can't reach you.
                Verify it's really you with your recovery code, your student
                QR code, or your registered details — then set a new password.
            </p>

            <?php
            $mrError = $this->session->flashdata('mr_error');
            $mrOld   = $this->session->flashdata('mr_old');
            if (!is_array($mrOld)) { $mrOld = []; }
            $oldStudent = (string)($mrOld['student_number'] ?? '');
            $oldMethod  = (string)($mrOld['method'] ?? 'recovery');
            if (!in_array($oldMethod, ['recovery', 'qr', 'identity'], true)) { $oldMethod = 'recovery'; }
            $oldEmail   = (string)($mrOld['email'] ?? '');
            $oldBirth   = (string)($mrOld['birthDate'] ?? '');
            $oldContact = (string)($mrOld['contactNo'] ?? '');
            ?>

            <?php if (!empty($mrError)): ?>
                <div class="flash verify-message">
                    <i class="fa fa-exclamation-circle"></i>
                    <span><?= html_escape($mrError); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('reset-password'); ?>" id="manualResetForm" novalidate>

                <div class="field-group">
                    <label class="field-label" for="mr-student">Student ID</label>
                    <div class="field-wrap">
                        <input class="field" id="mr-student" name="student_number" type="text"
                            inputmode="numeric" autocomplete="username" spellcheck="false"
                            maxlength="9" placeholder="e.g. 2023-0446"
                            value="<?= html_escape($oldStudent); ?>" required>
                    </div>
                </div>

                <div class="mr-section-label">Verify it's you — pick one</div>

                <div class="mr-methods" role="radiogroup" aria-label="Verification method">
                    <label class="mr-pill">
                        <input type="radio" name="method" value="recovery" <?= $oldMethod === 'recovery' ? 'checked' : ''; ?>>
                        <span><i class="fa fa-ticket"></i>Recovery code</span>
                    </label>
                    <label class="mr-pill">
                        <input type="radio" name="method" value="qr" <?= $oldMethod === 'qr' ? 'checked' : ''; ?>>
                        <span><i class="fa fa-qrcode"></i>Student QR</span>
                    </label>
                    <label class="mr-pill">
                        <input type="radio" name="method" value="identity" <?= $oldMethod === 'identity' ? 'checked' : ''; ?>>
                        <span><i class="fa fa-id-card-o"></i>My details</span>
                    </label>
                </div>

                <fieldset class="mr-method-fields" id="mf-recovery" <?= $oldMethod !== 'recovery' ? 'disabled' : ''; ?>>
                    <p class="mr-method-hint">The code in your downloaded credentials file, e.g. KQ3M-7T2P-WN9F.</p>
                    <div class="field-group">
                        <label class="field-label" for="mr-recovery">Recovery code</label>
                        <div class="field-wrap">
                            <input class="field text-mono" id="mr-recovery" name="recovery_code" type="text"
                                autocomplete="off" autocapitalize="characters" spellcheck="false"
                                maxlength="14" placeholder="XXXX-XXXX-XXXX">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mr-method-fields" id="mf-qr" <?= $oldMethod !== 'qr' ? 'disabled' : ''; ?>>
                    <p class="mr-method-hint">The 32-character code your student QR encodes — reveal it on the My QR Code page when signed in, or scan your card with any QR app.</p>
                    <div class="field-group">
                        <label class="field-label" for="mr-qrtoken">QR token</label>
                        <div class="field-wrap">
                            <input class="field text-mono" id="mr-qrtoken" name="qr_token" type="text"
                                autocomplete="off" autocapitalize="off" spellcheck="false"
                                maxlength="32" placeholder="32-character token">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mr-method-fields" id="mf-identity" <?= $oldMethod !== 'identity' ? 'disabled' : ''; ?>>
                    <p class="mr-method-hint">All three must match your enrollment records exactly.</p>
                    <div class="field-group">
                        <label class="field-label" for="mr-email">Registered email</label>
                        <div class="field-wrap">
                            <input class="field" id="mr-email" name="email" type="email"
                                autocomplete="email" placeholder="Email you registered with"
                                value="<?= html_escape($oldEmail); ?>">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="mr-birth">Date of birth</label>
                        <div class="field-wrap">
                            <input class="field" id="mr-birth" name="birthDate" type="date"
                                value="<?= html_escape($oldBirth); ?>">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="mr-contact">Registered mobile no.</label>
                        <div class="field-wrap">
                            <input class="field" id="mr-contact" name="contactNo" type="tel"
                                inputmode="numeric" autocomplete="tel-national"
                                maxlength="13" placeholder="09XX XXX XXXX"
                                value="<?= html_escape($oldContact); ?>">
                        </div>
                    </div>
                </fieldset>

                <hr class="mr-divider">
                <div class="mr-section-label">New password</div>

                <div class="field-group">
                    <label class="field-label" for="mr-pass">New password</label>
                    <div class="field-wrap">
                        <input class="field" id="mr-pass" name="new_password" type="password"
                            minlength="8" autocomplete="new-password" required style="padding-right:42px">
                        <button class="toggle-pass" type="button" data-target="#mr-pass" title="Toggle"><i class="fa fa-eye"></i></button>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="mr-pass2">Confirm new password</label>
                    <div class="field-wrap">
                        <input class="field" id="mr-pass2" name="confirm_password" type="password"
                            minlength="8" autocomplete="new-password" required style="padding-right:42px">
                        <button class="toggle-pass" type="button" data-target="#mr-pass2" title="Toggle"><i class="fa fa-eye"></i></button>
                    </div>
                    <span class="match-msg" id="mr-match" aria-live="polite"></span>
                </div>

                <?php if (!empty($site_key)): ?>
                    <div class="mr-captcha">
                        <div class="g-recaptcha" data-sitekey="<?= html_escape($site_key); ?>"></div>
                    </div>
                <?php endif; ?>

                <button class="btn-main" type="submit" id="mr-submit" style="margin-top:14px">
                    <span class="btn-label">Verify &amp; reset password</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <a class="verify-back" href="<?= site_url('login'); ?>">
                <i class="fa fa-arrow-left"></i>
                <span>Back to sign in</span>
            </a>

            <div class="legal-simple">
                &copy; <?= date('Y'); ?> FBMSO. All rights reserved.
            </div>
        </div>
    </main>

    <script src="<?= base_url(); ?>assets/vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('manualResetForm');
            if (!form) { return; }

            /* ----- Student ID auto-format (YYYY-NNNN) ----- */
            var sid = document.getElementById('mr-student');
            if (sid) {
                sid.addEventListener('input', function () {
                    var d = sid.value.replace(/\D/g, '').slice(0, 8);
                    sid.value = d.length > 4 ? d.slice(0, 4) + '-' + d.slice(4) : d;
                });
            }

            /* ----- Recovery code auto-format (XXXX-XXXX-XXXX) ----- */
            var rc = document.getElementById('mr-recovery');
            if (rc) {
                rc.addEventListener('input', function () {
                    var d = rc.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12);
                    rc.value = d.replace(/^(.{4})(.{1,4})?(.{1,4})?.*$/, function (m, a, b, c) {
                        return a + (b ? '-' + b : '') + (c ? '-' + c : '');
                    });
                });
            }

            /* ----- QR token: hex only ----- */
            var qt = document.getElementById('mr-qrtoken');
            if (qt) {
                qt.addEventListener('input', function () {
                    qt.value = qt.value.toLowerCase().replace(/[^0-9a-f]/g, '').slice(0, 32);
                });
            }

            /* ----- Mobile formatting (09XX XXX XXXX) ----- */
            var cn = document.getElementById('mr-contact');
            if (cn) {
                cn.addEventListener('input', function () {
                    var d = cn.value.replace(/\D/g, '').slice(0, 11);
                    cn.value = d.length > 7 ? d.slice(0, 4) + ' ' + d.slice(4, 7) + ' ' + d.slice(7)
                        : (d.length > 4 ? d.slice(0, 4) + ' ' + d.slice(4) : d);
                });
            }

            /* ----- Method tabs: enable only the chosen fieldset ----- */
            var fieldsets = {
                recovery: document.getElementById('mf-recovery'),
                qr: document.getElementById('mf-qr'),
                identity: document.getElementById('mf-identity')
            };
            function syncMethod() {
                var checked = form.querySelector('input[name="method"]:checked');
                var method = checked ? checked.value : 'recovery';
                Object.keys(fieldsets).forEach(function (k) {
                    var fs = fieldsets[k];
                    if (!fs) { return; }
                    var on = (k === method);
                    fs.disabled = !on;
                    fs.querySelectorAll('input').forEach(function (inp) {
                        inp.required = on;
                    });
                });
            }
            form.querySelectorAll('input[name="method"]').forEach(function (r) {
                r.addEventListener('change', syncMethod);
            });
            syncMethod();

            /* ----- Password visibility toggles ----- */
            document.querySelectorAll('.toggle-pass').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = document.querySelector(btn.getAttribute('data-target'));
                    if (!target) { return; }
                    var show = target.type === 'password';
                    target.type = show ? 'text' : 'password';
                    var icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', !show);
                        icon.classList.toggle('fa-eye-slash', show);
                    }
                });
            });

            /* ----- Password match indicator ----- */
            var p1 = document.getElementById('mr-pass');
            var p2 = document.getElementById('mr-pass2');
            var msg = document.getElementById('mr-match');
            function syncMatch() {
                if (!p1 || !p2 || !msg) { return; }
                if (!p2.value) {
                    msg.textContent = '';
                    msg.className = 'match-msg';
                    p2.setCustomValidity('');
                    return;
                }
                if (p1.value === p2.value) {
                    msg.textContent = 'Passwords match.';
                    msg.className = 'match-msg is-ok';
                    p2.setCustomValidity('');
                } else {
                    msg.textContent = 'Passwords do not match.';
                    msg.className = 'match-msg is-bad';
                    p2.setCustomValidity('Passwords do not match.');
                }
            }
            if (p1 && p2) {
                p1.addEventListener('input', syncMatch);
                p2.addEventListener('input', syncMatch);
            }

            /* ----- Submit: captcha gate + busy state ----- */
            form.addEventListener('submit', function (e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    form.reportValidity();
                    return;
                }
                var captcha = document.querySelector('.g-recaptcha');
                if (captcha && (typeof grecaptcha === 'undefined' || grecaptcha.getResponse() === '')) {
                    e.preventDefault();
                    alert('Please confirm you are not a robot.');
                    return;
                }
                var btn = document.getElementById('mr-submit');
                if (btn) {
                    btn.classList.add('is-loading');
                    btn.disabled = true;
                }
            });
        });
    </script>
</body>

</html>

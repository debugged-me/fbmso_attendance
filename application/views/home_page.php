<!DOCTYPE html>
<html lang="en">

<head>
  <?php include('includes/title.php'); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <link rel="icon" type="image/png" href="<?= base_url(); ?>assets/images/Attendance.png" />
  <link rel="stylesheet" href="<?= base_url(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/css/home.css?v=30260835">
  <link href="<?= base_url(); ?>assets/fonts/sora/sora.css?v=30260820" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/mobile-shell.css?v=7'); ?>">
  <meta name="theme-color" content="#1a2942">
  <link rel="manifest" href="<?= base_url('manifest.webmanifest?v=3'); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="apple-touch-icon" href="<?= base_url('assets/images/icons/attendance-192.png'); ?>">
  <script src="<?= base_url('assets/js/mobile-shell-early.js?v=5'); ?>"></script>
  <?php include(APPPATH . 'views/includes/ui_kit.php'); ?>
    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>

<body>

  <div class="blob blob-a"></div>
  <div class="blob blob-b"></div>

  <div class="card">

    <div class="side-art">
      <div class="ring ring-1"></div>
      <div class="ring ring-2"></div>
      <div class="art-content">
        <div class="qr-box">
          <div class="qr-corner tl"></div>
          <div class="qr-corner tr"></div>
          <div class="qr-corner bl"></div>
          <div class="qr-corner br"></div>
          <div class="scan-beam"></div>
          <div class="scan-beam-h"></div>
          <svg viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="12" y="12" width="48" height="48" rx="8" fill="none" stroke="rgba(255,255,255,.6)" stroke-width="2" />
            <rect x="22" y="22" width="28" height="28" rx="4" fill="rgba(255,255,255,.15)" />
            <rect x="30" y="30" width="12" height="12" rx="2" fill="rgba(255,255,255,.8)" />
            <rect x="100" y="12" width="48" height="48" rx="8" fill="none" stroke="rgba(255,255,255,.6)" stroke-width="2" />
            <rect x="110" y="22" width="28" height="28" rx="4" fill="rgba(255,255,255,.15)" />
            <rect x="118" y="30" width="12" height="12" rx="2" fill="rgba(255,255,255,.8)" />
            <rect x="12" y="100" width="48" height="48" rx="8" fill="none" stroke="rgba(255,255,255,.6)" stroke-width="2" />
            <rect x="22" y="110" width="28" height="28" rx="4" fill="rgba(255,255,255,.15)" />
            <rect x="30" y="118" width="12" height="12" rx="2" fill="rgba(255,255,255,.8)" />
            <rect x="74" y="12" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="86" y="12" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="74" y="24" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="86" y="24" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="74" y="36" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="86" y="36" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="74" y="48" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="86" y="48" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="12" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="24" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="36" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="48" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="74" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.7)" />
            <rect x="86" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="98" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="110" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="122" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="134" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="146" y="74" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="74" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="86" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="98" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="110" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="122" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="134" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="146" y="86" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="74" y="98" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="86" y="98" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="98" y="98" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="110" y="98" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="122" y="98" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="74" y="110" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="86" y="110" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="98" y="110" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="110" y="110" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="74" y="122" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="86" y="122" width="8" height="8" rx="2" fill="rgba(255,255,255,.6)" />
            <rect x="98" y="122" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="74" y="134" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
            <rect x="86" y="134" width="8" height="8" rx="2" fill="rgba(255,255,255,.3)" />
            <rect x="74" y="146" width="8" height="8" rx="2" fill="rgba(255,255,255,.4)" />
            <rect x="86" y="146" width="8" height="8" rx="2" fill="rgba(255,255,255,.5)" />
          </svg>
        </div>
        <p class="art-tagline">Attendance Portal</p>
        <h2 class="art-title">FBMSO</h2>
        <p class="art-desc">Fast, secure check-ins<br>powered by QR codes</p>
      </div>
    </div>

    <div class="side-form">
      <div class="brand-row">
        <div class="brand-icon">
          <img src="<?= base_url(); ?>upload/banners/logo1.png" alt="Logo">
        </div>
        <div class="brand-text">
          Attendance Portal
          <small>Faculty of Business Management Student Org.</small>
        </div>
      </div>

      <h1 class="form-title">Sign in</h1>
      <p class="form-caption">Enter your credentials to continue</p>

      <?php
      $authError      = $this->session->flashdata('auth_error');
      $loginErrorText = is_string($authError) ? trim(strip_tags($authError)) : '';
      $infoMessage    = $this->session->flashdata('info_message') ?: '';
      $forgotError    = $this->session->flashdata('forgot_error');
      $forgotInfo     = $this->session->flashdata('forgot_info');
      $forgotErrorText = is_string($forgotError) ? trim(strip_tags($forgotError)) : '';
      $forgotInfoText  = is_string($forgotInfo) ? trim(strip_tags($forgotInfo)) : '';
      $forgotModalOpen = (bool)$this->session->flashdata('forgot_modal_open');
      $forgotEmail = (string)($this->session->flashdata('forgot_email') ?: '');
      ?>
      <?php if (!empty($loginErrorText)): ?>
        <div class="flash" id="login-error-message"><?= htmlspecialchars($loginErrorText, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if (!empty($infoMessage)): ?>
        <div class="flash flash-success" id="login-info-message"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form action="<?= site_url('Login/auth'); ?>" method="post" novalidate>
        <input type="hidden" name="next" value="<?= html_escape($this->input->get('next')); ?>">
        <input type="hidden" name="sy" value="<?= isset($active_sy)  ? $active_sy  : ''; ?>">
        <input type="hidden" name="semester" value="<?= isset($active_sem) ? $active_sem : ''; ?>">

        <div class="field-group">
          <label class="field-label" for="username">Username / STUDENT ID</label>
          <div class="field-wrap">
            <input class="field" id="username" name="username" type="text" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="Enter username" required>
          </div>
        </div>

        <div class="field-group">
          <label class="field-label" for="password">Password</label>
          <div class="field-wrap">
            <input class="field" id="password" name="password" type="password" autocomplete="current-password" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="••••••••" required style="padding-right:42px">
            <button class="toggle-pass" type="button" id="togglePass" data-target="#password" title="Toggle"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <div class="forgot-row">
          <a class="forgot-link" href="#" data-toggle="modal" data-target="#forgotModal">Forgot password?</a>
          <a class="forgot-link" href="<?= site_url('verify-email'); ?>">Resend verification email</a>
        </div>

        <button class="btn-main" type="submit" id="loginBtn"><span class="btn-label">Sign in</span><span class="btn-spinner"></span></button>

        <?php if (isset($allow_signup) && $allow_signup == 'Yes'): ?>
          <p class="signup-note">No account? <a href="<?= base_url(); ?>Registration">Create one</a></p>
        <?php endif; ?>
      </form>

      <!-- Privacy / Terms footer (inside the form card) -->
      <div class="legal-footer">
        <div class="legal-links">
          <a href="#" data-toggle="modal" data-target="#privacyModal">Data Privacy</a>
          <span class="legal-dot"></span>
          <a href="#" data-toggle="modal" data-target="#termsModal">Terms of Use</a>
          <span class="legal-dot"></span>
          <a href="#" data-toggle="modal" data-target="#aboutModal">About</a>
        </div>
        <p class="legal-copy">&copy; <?= date('Y'); ?> FBMSO. All rights reserved.</p>
      </div>
    </div>
  </div>

  <!-- Data Privacy Modal -->
  <div class="modal fade" id="privacyModal" tabindex="-1" role="dialog" aria-labelledby="privacyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content" style="font-family:'Sora',sans-serif;border-radius:20px;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;border-radius:20px 20px 0 0;">
          <h5 class="modal-title" id="privacyLabel" style="font-weight:800;display:flex;align-items:center;gap:8px;">
            <i class="mdi mdi-shield-lock-outline"></i> Data Privacy Notice
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        <div class="modal-body" style="padding:28px 32px;max-height:65vh;overflow-y:auto;">
          <p style="font-size:.86rem;line-height:1.7;margin-bottom:16px;">
            The Faculty of Business Management Student Organization (FBMSO) is committed to protecting the privacy and security of your personal data in compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">Information We Collect</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            We collect information necessary for attendance tracking and academic record-keeping, including your name, student ID, course, year level, section, email address, and attendance records (check-in/check-out times and locations).
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">How We Use Your Information</h6>
          <ul style="font-size:.84rem;line-height:1.8;padding-left:20px;margin-bottom:16px;">
            <li>To record and verify your attendance at FBMSO activities and events.</li>
            <li>To generate attendance reports for academic and organizational purposes.</li>
            <li>To communicate announcements and important updates.</li>
            <li>To maintain accurate enrollment and student records.</li>
          </ul>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">Data Retention</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            Your data is retained for the duration of your enrollment and for a reasonable period thereafter as required by institutional policies and legal obligations.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">Your Rights</h6>
          <ul style="font-size:.84rem;line-height:1.8;padding-left:20px;margin-bottom:16px;">
            <li>Right to be informed about how your data is processed.</li>
            <li>Right to access your personal data held by FBMSO.</li>
            <li>Right to request correction of inaccurate data.</li>
            <li>Right to request deletion of data where applicable.</li>
          </ul>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">Security Measures</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            We implement appropriate technical, organizational, and physical security measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">Contact</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:0;">
            For privacy-related concerns or requests, please contact the FBMSO Data Protection Officer through your institution's official channels.
          </p>
        </div>
        <div class="modal-footer" style="border-top:1px solid #e6ebf5;padding:16px 32px;">
          <button type="button" class="btn-main" style="width:auto;padding:10px 28px;" data-dismiss="modal">I understand</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Terms of Use Modal -->
  <div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content" style="font-family:'Sora',sans-serif;border-radius:20px;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;border-radius:20px 20px 0 0;">
          <h5 class="modal-title" id="termsLabel" style="font-weight:800;display:flex;align-items:center;gap:8px;">
            <i class="mdi mdi-file-document-outline"></i> Terms of Use
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        <div class="modal-body" style="padding:28px 32px;max-height:65vh;overflow-y:auto;">
          <p style="font-size:.86rem;line-height:1.7;margin-bottom:16px;">
            By accessing and using the FBMSO Attendance Portal, you agree to the following terms and conditions:
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">1. Acceptable Use</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            You agree to use this system only for its intended purpose of recording and managing attendance for FBMSO activities. You shall not attempt to manipulate, falsify, or circumvent attendance records.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">2. Account Security</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            You are responsible for maintaining the confidentiality of your login credentials. Do not share your username or password with anyone. You will be held accountable for all activities performed under your account.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">3. Prohibited Conduct</h6>
          <ul style="font-size:.84rem;line-height:1.8;padding-left:20px;margin-bottom:16px;">
            <li>Checking in on behalf of another student.</li>
            <li>Attempting to access unauthorized areas of the system.</li>
            <li>Sharing, copying, or distributing QR codes for improper use.</li>
            <li>Using the system for any unlawful or disruptive purpose.</li>
          </ul>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">4. Intellectual Property</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            All content, design, and functionality of this system are the property of FBMSO and may not be reproduced or distributed without permission.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">5. Disclaimer</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            The system is provided "as is" without warranties of any kind. FBMSO is not liable for any damages arising from the use or inability to use this system.
          </p>
          <h6 style="font-weight:800;font-size:.82rem;margin:20px 0 8px;">6. Modifications</h6>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:0;">
            FBMSO reserves the right to modify these terms at any time. Continued use of the system constitutes acceptance of the updated terms.
          </p>
        </div>
        <div class="modal-footer" style="border-top:1px solid #e6ebf5;padding:16px 32px;">
          <button type="button" class="btn-main" style="width:auto;padding:10px 28px;" data-dismiss="modal">I agree</button>
        </div>
      </div>
    </div>
  </div>

  <!-- About Modal -->
  <div class="modal fade" id="aboutModal" tabindex="-1" role="dialog" aria-labelledby="aboutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="font-family:'Sora',sans-serif;border-radius:20px;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1a2a6c,#2a4090);color:#fff;border:none;border-radius:20px 20px 0 0;">
          <h5 class="modal-title" id="aboutLabel" style="font-weight:800;display:flex;align-items:center;gap:8px;">
            <i class="mdi mdi-information-outline"></i> About
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:1.2rem;line-height:1;">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        <div class="modal-body" style="padding:28px 32px;text-align:center;">
          <div style="width:64px;height:64px;border-radius:16px;border:1px solid #e4ecff;background:#f4f8ff;overflow:hidden;display:grid;place-items:center;margin:0 auto 16px;">
            <img src="<?= base_url(); ?>upload/banners/logo1.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
          </div>
          <h6 style="font-weight:800;font-size:1.1rem;margin-bottom:4px;">FBMSO Attendance Portal</h6>
          <p style="font-size:.82rem;color:#8fa0c8;margin-bottom:16px;">Faculty of Business Management Student Organization</p>
          <p style="font-size:.84rem;line-height:1.7;margin-bottom:16px;">
            A QR-based attendance tracking system designed for fast, secure, and reliable check-ins at FBMSO activities and events.
          </p>
          <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
            <span class="badge badge-primary" style="padding:6px 14px;border-radius:999px;font-size:.74rem;font-weight:700;">QR Check-in</span>
            <span class="badge badge-success" style="padding:6px 14px;border-radius:999px;font-size:.74rem;font-weight:700;">Real-time Logs</span>
            <span class="badge badge-info" style="padding:6px 14px;border-radius:999px;font-size:.74rem;font-weight:700;">Mobile Friendly</span>
          </div>
          <p style="font-size:.76rem;color:#b8c4df;margin:0;">Version <?= date('Y'); ?>.1</p>
        </div>
        <div class="modal-footer" style="border-top:1px solid #e6ebf5;padding:16px 32px;">
          <button type="button" class="btn-main" style="width:auto;padding:10px 28px;" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="forgotModal" tabindex="-1" role="dialog" aria-labelledby="forgotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="font-family:'Sora',sans-serif;">
        <div class="modal-header px-4 pt-4 pb-3">
          <h5 class="modal-title" id="forgotLabel">Reset password</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#8fa0c8"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <form id="resetPassword" method="post" action="<?= site_url('login/forgot_pass'); ?>">
            <div class="field-group">
              <label class="field-label" for="reset-email">Email address</label>
              <small class="reset-hint">Enter your registered email to receive a temporary password you can use to sign in.</small>
              <input type="email" id="reset-email" name="email" class="field" placeholder="Enter Email" value="<?= html_escape($forgotEmail); ?>" required>
            </div>
            <div
              id="reset-status"
              class="reset-status<?= !empty($forgotErrorText) ? ' is-error' : (!empty($forgotInfoText) ? ' is-success' : ''); ?>"
              <?= empty($forgotErrorText) && empty($forgotInfoText) ? 'hidden' : ''; ?>
            ><?= html_escape($forgotErrorText ?: $forgotInfoText); ?></div>
            <button class="btn-main" id="resetSubmit" type="submit" style="margin-top:12px"><span>Send temporary password</span></button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url(); ?>assets/vendor/jquery/jquery-3.2.1.min.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/bootstrap/js/popper.js"></script>
  <script src="<?= base_url(); ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
  <script src="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
  <script>
    window.homeLoginState = {
      loginError: <?= json_encode($loginErrorText ?? ''); ?>,
      infoMessage: <?= json_encode($infoMessage ?? ''); ?>,
      forgotError: <?= json_encode($forgotErrorText ?? ''); ?>,
      forgotInfo: <?= json_encode($forgotInfoText ?? ''); ?>,
      forgotModalOpen: <?= $forgotModalOpen ? 'true' : 'false'; ?>,
      forgotEmail: <?= json_encode($forgotEmail ?? ''); ?>
    };
    window.SITE_URL = <?= json_encode(base_url()); ?>;
  </script>
  <script src="<?= base_url(); ?>assets/js/home.js?v=30260831"></script>
  <script src="<?= base_url('assets/js/mobile-shell.js?v=6'); ?>"></script>
  <script src="<?= base_url('assets/js/forensic-capture.js?v=18'); ?>"></script>

</body>

</html>

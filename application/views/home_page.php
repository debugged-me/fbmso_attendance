<!DOCTYPE html>
<html lang="en">

<head>
  <?php include('includes/title.php'); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="<?= base_url(); ?>assets/images/Attendance.png" />
  <link rel="stylesheet" href="<?= base_url(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/libs/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="<?= base_url(); ?>assets/css/home.css">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
      ?>
      <?php if (!empty($loginErrorText)): ?>
        <div class="flash" id="login-error-message"><?= htmlspecialchars($loginErrorText, ENT_QUOTES, 'UTF-8'); ?></div>
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
            <button class="toggle-pass" type="button" id="togglePass" title="Toggle"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <div class="forgot-row">
          <a class="forgot-link" href="#" data-toggle="modal" data-target="#forgotModal">Forgot password?</a>
        </div>

        <button class="btn-main" type="submit"><span>Sign in</span></button>

        <?php if (isset($allow_signup) && $allow_signup == 'Yes'): ?>
          <p class="signup-note">No account? <a href="<?= base_url(); ?>Registration">Create one</a></p>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Forgot Modal -->
  <div class="modal fade" id="forgotModal" tabindex="-1" role="dialog" aria-labelledby="forgotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="font-family:'Sora',sans-serif; color:#0d1b4b;">
        <div class="modal-header px-4 pt-4 pb-3">
          <h5 class="modal-title" id="forgotLabel">Reset password</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#8fa0c8"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <form id="resetPassword" method="post" action="<?= base_url(); ?>login/forgot_pass">
            <div class="field-group">
              <label class="field-label" for="reset-email">Email address</label>
              <small>For security, we only send to registered emails</small>
              <input type="email" id="reset-email" name="email" class="field" placeholder="Enter Email" required>
            </div>
            <button class="btn-main" type="submit" style="margin-top:12px"><span>Send temporary password</span></button>
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
      infoMessage: <?= json_encode($infoMessage ?? ''); ?>
    };
  </script>
  <script src="<?= base_url(); ?>assets/js/home.js"></script>

</body>

</html>

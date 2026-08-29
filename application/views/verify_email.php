<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('includes/title.php'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <link rel="icon" type="image/png"
          href="<?= base_url(); ?>assets/images/Attendance.png">

    <link rel="stylesheet"
          href="<?= base_url(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="<?= base_url(); ?>assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet"
          href="<?= base_url(); ?>assets/css/home.css?v=30260835">

    <link href="<?= base_url(); ?>assets/fonts/sora/sora.css?v=30260820"
          rel="stylesheet">

    <meta name="theme-color" content="#1a2942">

    <title>Attendance Portal | Verify Email</title>

    <style>
      

        .verify-art-icon {
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            border-radius: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            position: relative;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.17),
                    rgba(255,255,255,.05)
                );

            border: 1px solid rgba(255,255,255,.18);

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.2),
                0 25px 60px rgba(0,0,0,.13);

            backdrop-filter: blur(8px);
        }

        .verify-art-icon::before {
            content: '';
            position: absolute;

            width: 110px;
            height: 110px;

            border-radius: 32px;

            border: 1px solid rgba(255,255,255,.12);

            animation: verify-pulse 2.5s ease-in-out infinite;
        }

        .verify-art-icon i {
            position: relative;
            z-index: 2;

            font-size: 4rem;
            color: #fff;

            filter:
                drop-shadow(
                    0 8px 18px rgba(0,0,0,.15)
                );
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

            box-shadow:
                0 8px 20px rgba(0,0,0,.18);

            z-index: 3;
        }

        @keyframes verify-pulse {
            0%,
            100% {
                transform: scale(1);
                opacity: .7;
            }

            50% {
                transform: scale(1.08);
                opacity: .35;
            }
        }

        .verify-description {
            font-size: .82rem;
            color: #8fa0c8;
            line-height: 1.65;
            margin-bottom: 28px;
            max-width: 390px;
        }

        .verify-message {
            display: flex;
            align-items: flex-start;
            gap: 9px;
        }

        .verify-message i {
            margin-top: 2px;
            flex-shrink: 0;
        }

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

            transition:
                color .18s ease,
                transform .18s ease;
        }

        .verify-back:hover {
            color: #3b5fd4;
            text-decoration: none;
            transform: translateX(-2px);
        }

        .verify-security-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;

            margin-top: 20px;
            padding: 12px 14px;

            border: 1px solid #e4ebff;
            border-radius: 14px;

            background: #f7f9ff;

            color: #7e8fb1;

            font-size: .72rem;
            line-height: 1.5;
        }

        .verify-security-note i {
            color: #5c7ce2;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .verify-email-icon {
            position: relative;
        }

        .verify-email-icon .field {
            padding-left: 44px;
        }

        .verify-email-icon > i {
            position: absolute;

            left: 16px;
            top: 50%;

            transform: translateY(-50%);

            z-index: 3;

            color: #a0afcc;
            font-size: .9rem;

            pointer-events: none;
        }

        .legal-simple {
            text-align: center;

            margin-top: 26px;
            padding-top: 17px;

            border-top: 1px solid #e6ebf5;

            color: #b8c4df;

            font-size: .7rem;
        }

        /* Reduce card height slightly compared with login */
        .verify-card {
            min-height: 540px;
        }

        @media (max-width: 700px) {
            .verify-card {
                min-height: auto;
            }

            .side-form {
                padding-top: 32px;
                padding-bottom: 28px;
            }

            .brand-row {
                margin-bottom: 30px;
            }

            .verify-description {
                margin-bottom: 24px;
            }
        }

        @media (prefers-color-scheme: dark) {

            .verify-description {
                color: #8fa0c8;
            }

            .verify-security-note {
                background: #141d35;
                border-color: #2a3a5c;
                color: #8fa0c8;
            }

            .verify-security-note i {
                color: #7f9cff;
            }

            .verify-back {
                color: #8fa0c8;
            }

            .verify-back:hover {
                color: #7f9cff;
            }

            .legal-simple {
                border-top-color: #2a3a5c;
                color: #5a6a8e;
            }
        }
    </style>

</head>

<body>

    <!-- Same decorative background used by Home -->
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>


    <main class="card verify-card">

        <!-- ===========================
             LEFT SIDE
        ============================ -->
        <div class="side-art">

            <div class="ring ring-1"></div>
            <div class="ring ring-2"></div>

            <div class="art-content">

                <div class="verify-art-icon">

                    <i class="fa fa-envelope-o"></i>

                    <div class="verify-check">
                        <i class="fa fa-check"></i>
                    </div>

                </div>

                <p class="art-tagline">
                    Attendance Portal
                </p>

                <h2 class="art-title">
                    Verify your email
                </h2>

                <p class="art-desc">
                    Secure your account and make sure<br>
                    important updates reach you.
                </p>

            </div>

        </div>


        <!-- ===========================
             RIGHT SIDE
        ============================ -->
        <div class="side-form">

            <!-- Branding -->
            <div class="brand-row">

                <div class="brand-icon">
                    <img
                        src="<?= base_url(); ?>upload/banners/logo1.png"
                        alt="FBMSO Logo">
                </div>

                <div class="brand-text">
                    Attendance Portal

                    <small>
                        Faculty of Business Management Student Org.
                    </small>
                </div>

            </div>


            <h1 class="form-title">
                Verify your email
            </h1>

            <p class="verify-description">
                Enter the email address you used during registration.
                We'll send you a new verification link so you can
                activate your account.
            </p>


            <?php
            $success = $this->session->flashdata('verification_success');
            $error   = $this->session->flashdata('verification_error');
            $email   = (string)($this->session->flashdata('verification_email') ?: '');
            ?>


            <!-- Error -->
            <?php if (!empty($error)): ?>

                <div class="flash verify-message">

                    <i class="fa fa-exclamation-circle"></i>

                    <span>
                        <?= html_escape($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- Success - normally not shown because successful
                 request redirects to login/home -->
            <?php if (!empty($success)): ?>

                <div class="flash flash-success verify-message">

                    <i class="fa fa-check-circle"></i>

                    <span>
                        <?= html_escape($success); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- Verification form -->
            <form
                method="post"
                action="<?= site_url('verify-email'); ?>"
                id="verificationForm"
                novalidate>

                <div class="field-group">

                    <label
                        class="field-label"
                        for="verification-email">

                        Email address

                    </label>

                    <div class="field-wrap verify-email-icon">

                        <i class="fa fa-envelope-o"></i>

                        <input
                            class="field"
                            id="verification-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="Enter your registered email"
                            value="<?= html_escape($email); ?>"
                            required>

                    </div>

                </div>


                <button
                    class="btn-main"
                    type="submit"
                    id="verifyBtn">

                    <span class="btn-label">
                        Resend verification email
                    </span>

                    <span class="btn-spinner"></span>

                </button>

            </form>


            <div class="verify-security-note">

                <i class="fa fa-shield"></i>

                <span>
                    For your security, the verification link will only
                    be sent to an email address associated with a
                    registered account.
                </span>

            </div>


            <a
                class="verify-back"
                href="<?= site_url('login'); ?>">

                <i class="fa fa-arrow-left"></i>

                <span>Back to sign in</span>

            </a>


            <div class="legal-simple">
                &copy; <?= date('Y'); ?> FBMSO. All rights reserved.
            </div>

        </div>

    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const form = document.getElementById('verificationForm');
            const button = document.getElementById('verifyBtn');

            if (!form || !button) {
                return;
            }

            form.addEventListener('submit', function (e) {

                if (!form.checkValidity()) {

                    e.preventDefault();

                    form.reportValidity();

                    return;
                }

                button.classList.add('is-loading');
                button.disabled = true;

            });

        });
    </script>

</body>
</html>
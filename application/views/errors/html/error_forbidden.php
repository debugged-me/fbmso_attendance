<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f2f5f9;
            color: #1f2d3d;
            font: 15px/1.6 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 18px;
            padding: 34px 30px;
            text-align: center;
            box-shadow: 0 18px 48px rgba(31, 45, 61, .14);
        }

        .shield {
            width: 68px;
            height: 68px;
            margin: 0 auto 18px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            background: rgba(239, 68, 68, .10);
            color: #ef4444;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -.2px;
        }

        p {
            margin: 0 0 6px;
            color: #5b6b7c;
        }

        .meta {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #eef1f5;
            font-size: .85rem;
            color: #7b8794;
        }

        .meta b {
            color: #1f2d3d;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        a.btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: .92rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        a.btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .28);
        }

        .btn-ghost {
            background: #f1f4f8;
            color: #46586b;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #12161c;
                color: #e6edf5;
            }

            .card {
                background: #1a2029;
                box-shadow: 0 18px 48px rgba(0, 0, 0, .5);
            }

            p {
                color: #9fb0c2;
            }

            .meta {
                border-top-color: #26303c;
                color: #8296aa;
            }

            .meta b {
                color: #e6edf5;
            }

            .btn-ghost {
                background: #232c37;
                color: #c3d1df;
            }
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="shield">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
        </div>

        <h1>Access denied</h1>
        <p>Your account doesn't have permission to open this page.</p>

        <div class="meta">
            Signed in as <b><?= htmlspecialchars((string)$user_level, ENT_QUOTES, 'UTF-8'); ?></b>
            <?php if (!empty($allowed)): ?>
                <br>Requires <b><?= htmlspecialchars(implode(' or ', (array)$allowed), ENT_QUOTES, 'UTF-8'); ?></b>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="<?= site_url(); ?>">Go to my dashboard</a>
            <a class="btn btn-ghost" href="<?= site_url('login/logout'); ?>">Sign in as someone else</a>
        </div>
    </div>
</body>

</html>

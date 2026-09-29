<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Maintenance page
|--------------------------------------------------------------------------
|
| Shared 503 page rendered by index.php (before the CI bootstrap loads)
| and by the MaintenanceMode pre_controller hook. Self-contained on
| purpose: no framework, no CDN, no database -- it must still render
| while the rest of the application is mid-deploy.
|
*/

if (!headers_sent()) {
    header('HTTP/1.1 503 Service Temporarily Unavailable', TRUE, 503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="120">
    <title>Under Maintenance &mdash; FBMSO Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f4f6f8;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
        }
        .card {
            max-width: 480px;
            width: 100%;
            background: #fff;
            padding: 48px 32px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #fdecea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            line-height: 1;
        }
        h1 { font-size: 1.4rem; color: #c0392b; margin-bottom: 12px; }
        p  { font-size: 0.95rem; color: #555; line-height: 1.6; }
        .note { margin-top: 24px; font-size: 0.8rem; color: #999; }
        .bar {
            margin: 24px auto 0;
            width: 120px;
            height: 4px;
            border-radius: 2px;
            background: #eee;
            overflow: hidden;
            position: relative;
        }
        .bar::after {
            content: "";
            position: absolute;
            left: -40px;
            width: 40px;
            height: 100%;
            background: #c0392b;
            border-radius: 2px;
            animation: slide 1.2s ease-in-out infinite;
        }
        @keyframes slide {
            0%   { left: -40px; }
            100% { left: 120px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#9888;</div>
        <h1>We&rsquo;ll be right back</h1>
        <p>The system is temporarily down for scheduled maintenance and updates. Please check again shortly.</p>
        <div class="bar"></div>
        <p class="note">This page refreshes automatically every 2 minutes.</p>
    </div>
</body>
</html>

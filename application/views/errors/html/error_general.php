<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$errCode = isset($status_code) ? (int)$status_code : 0;
$errHeading = isset($heading) ? trim((string)$heading) : '';

// Friendlier copy for the common early-boot failures
if ($errCode === 400) {
	$errHeading = 'That link doesn\'t look right';
	$errMessage = 'The address you opened contains characters this site does not allow. It may have been typed incorrectly or modified. Use the buttons below to get back.';
} else {
	$errMessage = isset($message) ? trim(strip_tags((string)$message)) : '';
}
if ($errHeading === '') {
	$errHeading = 'Something went wrong';
}
if ($errMessage === '') {
	$errMessage = 'An unexpected error occurred. Please try again.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $errCode ? $errCode . ' — ' : ''; ?><?= htmlspecialchars($errHeading, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
	* { box-sizing: border-box; margin: 0; padding: 0; }
	body {
		font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 24px;
		background:
			radial-gradient(ellipse 80% 60% at 15% 20%, #dce8ff 0%, transparent 55%),
			radial-gradient(ellipse 70% 50% at 85% 80%, #e4edff 0%, transparent 55%),
			#f0f4ff;
		color: #0d1b4b;
	}
	.err-card {
		width: 100%;
		max-width: 480px;
		background: rgba(255, 255, 255, .85);
		backdrop-filter: blur(20px);
		border: 1px solid rgba(255, 255, 255, .9);
		border-radius: 22px;
		box-shadow: 0 24px 60px rgba(100, 130, 200, .18), 0 6px 16px rgba(100, 130, 200, .1);
		padding: 40px 36px;
		text-align: center;
	}
	.err-icon {
		width: 64px; height: 64px; margin: 0 auto 18px;
		border-radius: 18px;
		background: linear-gradient(135deg, #eef2ff, #dde6ff);
		display: flex; align-items: center; justify-content: center;
	}
	.err-icon svg { width: 30px; height: 30px; color: #2a4090; }
	.err-code {
		font-size: .68rem; font-weight: 800; letter-spacing: .22em;
		text-transform: uppercase; color: #8fa0c8; margin-bottom: 8px;
	}
	.err-title { font-size: 1.35rem; font-weight: 800; line-height: 1.25; margin-bottom: 10px; }
	.err-msg { font-size: .85rem; color: #6b7a99; line-height: 1.65; margin-bottom: 26px; }
	.err-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
	.err-btn {
		display: inline-flex; align-items: center; gap: 8px;
		padding: 12px 26px; border-radius: 13px;
		font-size: .85rem; font-weight: 700; letter-spacing: .03em;
		text-decoration: none; cursor: pointer; border: none;
		transition: transform .15s ease, box-shadow .15s ease;
	}
	.err-btn svg { width: 16px; height: 16px; }
	.err-btn-primary {
		background: linear-gradient(135deg, #2a4090, #4266d4);
		color: #fff;
		box-shadow: 0 8px 20px rgba(42, 64, 144, .26);
	}
	.err-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(42, 64, 144, .32); color: #fff; }
	.err-btn-ghost {
		background: rgba(255, 255, 255, .7);
		border: 1px solid rgba(42, 64, 144, .18);
		color: #2a4090;
	}
	.err-btn-ghost:hover { background: rgba(66, 102, 212, .08); transform: translateY(-2px); color: #1a2a6c; }
</style>
</head>
<body>
	<div class="err-card">
		<div class="err-icon">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
			</svg>
		</div>
		<div class="err-code"><?= $errCode ? 'Error ' . $errCode : 'Error'; ?></div>
		<div class="err-title"><?= htmlspecialchars($errHeading, ENT_QUOTES, 'UTF-8'); ?></div>
		<div class="err-msg"><?= htmlspecialchars($errMessage, ENT_QUOTES, 'UTF-8'); ?></div>
		<div class="err-actions">
			<button type="button" class="err-btn err-btn-primary" onclick="history.back()">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
				</svg>
				Go Back
			</button>
		</div>
	</div>
</body>
</html>

<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FBMSO Email Test</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #eef2f7;
            --panel: #ffffff;
            --border: #d3dbe6;
            --text: #1c2735;
            --muted: #5c6b7f;
            --accent: #2b6cb0;
            --accent-dark: #1f4f83;
            --ok: #1f6f43;
            --ok-bg: #e8f5ee;
            --bad: #9f2d2d;
            --bad-bg: #fdecec;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(180deg, #f7fafd 0%, var(--bg) 100%);
            color: var(--text);
        }

        .wrap { width: min(920px, calc(100% - 32px)); margin: 32px auto; }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 12px 34px rgba(28, 39, 53, 0.07);
        }

        h1 { margin: 0 0 8px; font-size: clamp(24px, 3.4vw, 32px); line-height: 1.15; }
        p  { margin: 0; line-height: 1.55; }
        .lead { color: var(--muted); margin-bottom: 22px; }
        code { background: #eef2f7; padding: 1px 5px; border-radius: 4px; font-size: 0.92em; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .card {
            background: #f9fbfd;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .label {
            display: block;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .value { font-size: 15px; font-weight: 600; word-break: break-word; }
        .value.warn { color: var(--bad); }

        .queue-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: baseline;
            background: #f4f8fc;
            border: 1px solid var(--border);
            border-left: 4px solid var(--accent);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .queue-bar b { font-size: 17px; }
        .queue-bar a { color: var(--accent); }

        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }

        input[type="email"], input[type="text"], textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font: inherit;
            font-weight: 400;
            color: var(--text);
            background: #fff;
        }

        input:focus, textarea:focus { outline: 2px solid rgba(43, 108, 176, 0.35); outline-offset: 1px; border-color: var(--accent); }
        textarea { min-height: 120px; resize: vertical; }

        button {
            padding: 11px 22px;
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover { background: var(--accent-dark); }

        .result {
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid;
            font-size: 14px;
        }

        .result.ok  { border-color: #bfe3d0; background: var(--ok-bg);  color: var(--ok); }
        .result.bad { border-color: #f3c7c7; background: var(--bad-bg); color: var(--bad); }

        pre {
            margin: 12px 0 0;
            padding: 14px;
            border-radius: 8px;
            background: #16202c;
            color: #e6edf5;
            font-size: 12.5px;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .note { margin-top: 18px; color: var(--muted); font-size: 13.5px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="panel">
            <h1>FBMSO Email Test</h1>
            <p class="lead">
                Sends one test message using <code>application/config/email.php</code>. Leave
                <em>Send via queue</em> ticked to exercise the exact path every real portal email takes.
            </p>

            <div class="grid">
                <div class="card">
                    <span class="label">Protocol</span>
                    <span class="value"><?php echo html_escape($config['protocol'] !== '' ? $config['protocol'] : 'smtp'); ?></span>
                </div>
                <div class="card">
                    <span class="label">SMTP Host</span>
                    <span class="value"><?php echo html_escape($config['smtp_host']); ?></span>
                </div>
                <div class="card">
                    <span class="label">SMTP Port</span>
                    <span class="value"><?php echo html_escape($config['smtp_port']); ?></span>
                </div>
                <div class="card">
                    <span class="label">Security</span>
                    <span class="value"><?php echo html_escape($config['smtp_crypto'] !== '' ? strtoupper($config['smtp_crypto']) : 'NONE'); ?></span>
                </div>
                <div class="card">
                    <span class="label">Auth User</span>
                    <span class="value"><?php echo html_escape($config['smtp_user']); ?></span>
                </div>
                <div class="card">
                    <span class="label">Password Set</span>
                    <?php $passPlaceholder = ($config['smtp_pass'] === '' || $config['smtp_pass'] === 'REPLACE_WITH_MAILBOX_PASSWORD'); ?>
                    <span class="value <?php echo $passPlaceholder ? 'warn' : ''; ?>">
                        <?php echo $passPlaceholder ? 'NOT SET' : 'Yes'; ?>
                    </span>
                </div>
            </div>

            <div class="queue-bar">
                <span>Queue right now:</span>
                <span><b><?php echo (int) $counts['pending']; ?></b> pending</span>
                <span><b><?php echo (int) $counts['sent']; ?></b> sent</span>
                <span><b><?php echo (int) $counts['failed']; ?></b> failed</span>
                <span style="margin-left:auto;"><a href="<?php echo html_escape($queue_url); ?>" target="_blank" rel="noopener">Queue status &rarr;</a></span>
            </div>

            <form method="post" action="<?php echo html_escape($action_url); ?>">
                <label class="field">
                    Recipient Email
                    <input type="email" name="to_email" value="<?php echo html_escape($form['to_email']); ?>" placeholder="name@example.com" required>
                </label>

                <label class="field">
                    Subject
                    <input type="text" name="subject" value="<?php echo html_escape($form['subject']); ?>" required>
                </label>

                <label class="field">
                    Message
                    <textarea name="message" required><?php echo html_escape($form['message']); ?></textarea>
                </label>

                <label class="field" style="flex-direction:row;align-items:flex-start;gap:9px;font-weight:400;">
                    <input type="checkbox" name="via_queue" value="1" <?php echo !empty($form['via_queue']) ? 'checked' : ''; ?> style="width:auto;margin-top:3px;">
                    <span>
                        <b>Send via queue</b> — the production path, delivered by the cron within ~2 minutes.
                        Untick for a direct SMTP send that prints the full server conversation (use this to debug credentials).
                    </span>
                </label>

                <button type="submit">Send Test Email</button>
            </form>

            <?php if (is_array($result)): ?>
                <div class="result <?php echo $result['success'] ? 'ok' : 'bad'; ?>">
                    <strong><?php echo html_escape($result['message']); ?></strong>

                    <?php if (!empty($result['debug'])): ?>
                        <pre><?php echo html_escape($result['debug']); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p class="note">
                Staff accounts only — students get a 403. This page can send from the school mailbox, so keep it
                off any menu and consider removing it once the mail setup is settled.
            </p>
        </div>
    </div>
</body>
</html>

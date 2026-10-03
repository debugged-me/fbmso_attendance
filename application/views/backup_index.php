<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    .bk-card-wrap { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
    .bk-card-head { padding:17px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:#fff; color:var(--up-ink,#0d1b4b); }
    .bk-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
    .bk-card-head h5 > i { color:#4266d4; font-size:19px; }
    .bk-card-body { padding:22px; }

    .bk-meta td { padding:.45rem 0; font-size:.9rem; }
    .bk-meta td:first-child { color:var(--up-muted,#6b7a99); width:150px; }
    .bk-meta td:last-child { color:var(--up-ink,#0d1b4b); font-weight:600; }

    .bk-steps { counter-reset:step; list-style:none; padding:0; margin:0; }
    .bk-steps li { position:relative; padding:10px 0 10px 40px; font-size:.9rem; color:var(--up-ink,#0d1b4b); border-bottom:1px solid var(--up-line,#e6ebf5); }
    .bk-steps li:last-child { border-bottom:0; }
    .bk-steps li::before { counter-increment:step; content:counter(step); position:absolute; left:0; top:8px; width:26px; height:26px; border-radius:9px; background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); color:var(--up-blue-2,#4266d4); font-size:.78rem; font-weight:800; display:flex; align-items:center; justify-content:center; }

    .bk-warn { border-radius:12px; background:#fdf6e7; border:1px solid #f3e2b8; color:#8a6116; padding:12px 16px; font-size:.83rem; display:flex; gap:10px; align-items:flex-start; }
    .bk-warn i { font-size:17px; margin-top:1px; }
</style>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="page-title-box">
      <h4 class="up-page-title"><i class="mdi mdi-database-export"></i> Database Backup</h4>
      <div class="up-page-sub">Download a full, importable SQL dump of the system database.</div>
      <hr class="up-divider">
    </div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= $m; ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="alert alert-danger"><?= $m; ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-7">
        <div class="bk-card-wrap">
          <div class="bk-card-head">
            <h5><i class="mdi mdi-download"></i> Download a full backup</h5>
          </div>
          <div class="bk-card-body">
            <p style="font-size:.9rem;color:var(--up-muted,#6b7a99);">
              Generates a complete <code>.sql</code> dump of
              <b style="color:var(--up-ink,#0d1b4b)"><?= html_escape($stats['database']); ?></b> — the same format
              phpMyAdmin produces, so it imports cleanly back through
              phpMyAdmin or the <code>mysql</code> command line.
            </p>
            <table class="table table-borderless table-sm bk-meta mb-3">
              <tr><td>Database</td><td><code><?= html_escape($stats['database']); ?></code></td></tr>
              <tr><td>Host</td><td><code><?= html_escape($stats['hostname']); ?></code></td></tr>
              <tr><td>Server</td><td><?= html_escape($stats['server_version']); ?></td></tr>
              <tr><td>Tables</td><td><?= number_format($stats['tables']); ?><?= $stats['views'] ? ' + ' . number_format($stats['views']) . ' view(s)' : ''; ?></td></tr>
              <tr><td>Est. data size</td><td>≈ <?= number_format($stats['bytes'] / 1048576, 1); ?> MB <span class="text-muted font-weight-normal">(the .sql file may differ)</span></td></tr>
            </table>

            <a href="<?= base_url('Backup/download'); ?>" class="up-btn up-btn-primary"
               onclick="this.classList.add('disabled'); this.innerHTML='<i class=\'mdi mdi-loading mdi-spin\'></i> Generating backup…';">
              <i class="mdi mdi-download"></i> Download .sql backup
            </a>
            <p class="mt-3 mb-0" style="font-size:.8rem;color:var(--up-muted,#6b7a99);">
              Large databases can take a minute to generate — the download
              starts automatically when the file is ready. Each download is
              recorded in the audit trail.
            </p>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="bk-card-wrap">
          <div class="bk-card-head">
            <h5><i class="mdi mdi-database-import"></i> Restoring</h5>
          </div>
          <div class="bk-card-body">
            <ol class="bk-steps">
              <li>Open phpMyAdmin (or any MySQL client) on the target server.</li>
              <li>Select the destination database.</li>
              <li>Import tab &rarr; choose the downloaded <code>.sql</code> file &rarr; Go.</li>
            </ol>
            <div class="bk-warn mt-3">
              <i class="mdi mdi-alert-outline"></i>
              <div>
                The file contains <code>DROP TABLE IF EXISTS</code> statements —
                importing into a database that already has data will replace it.
                To keep the old copy, import into a new/empty database instead.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== Automation ==================== -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="bk-card-wrap">
          <div class="bk-card-head">
            <h5><i class="mdi mdi-clock-outline"></i> Automatic backups</h5>
            <a href="<?= base_url('backup/key'); ?>" class="up-btn up-btn-ghost" style="padding:5px 12px;font-size:.75rem"><i class="mdi mdi-key-outline"></i> Cron setup &amp; key</a>
            <?php if (!empty($settings['auto_enabled'])): ?>
              <span class="badge badge-success" style="font-size:.72rem;padding:.4rem .7rem"><i class="mdi mdi-check"></i> Enabled</span>
            <?php else: ?>
              <span class="badge badge-secondary" style="font-size:.72rem;padding:.4rem .7rem">Off</span>
            <?php endif; ?>
          </div>
          <div class="bk-card-body">
            <form method="post" action="<?= base_url('backup/settings'); ?>">
              <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                  <div class="custom-control custom-checkbox" style="padding-top:8px">
                    <input type="checkbox" class="custom-control-input" id="auto_enabled" name="auto_enabled" value="1" <?= !empty($settings['auto_enabled']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="auto_enabled"><b>Run automatically</b></label>
                  </div>
                </div>
                <div class="form-group col-md-3">
                  <label for="backup_time">Backup time (Manila)</label>
                  <input type="time" class="form-control" id="backup_time" name="backup_time" value="<?= html_escape((string)$settings['backup_time']); ?>">
                </div>
                <div class="form-group col-md-3">
                  <label for="keep_days">Delete run history older than</label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="keep_days" name="keep_days" min="1" max="90" value="<?= (int)$settings['keep_days']; ?>">
                    <div class="input-group-append"><span class="input-group-text">days</span></div>
                  </div>
                </div>
                <div class="form-group col-md-3">
                  <div class="custom-control custom-checkbox" style="padding-top:8px">
                    <input type="checkbox" class="custom-control-input" id="keep_local" name="keep_local" value="1" <?= !empty($settings['keep_local']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="keep_local">Also keep a copy on this server</label>
                  </div>
                  <small class="text-muted">Off = the server copy is deleted as soon as the backup reaches Drive or an emailed attachment is sent. If delivery fails, the copy is kept so the backup isn't lost.</small>
                </div>
              </div>

              <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Email delivery</span><span class="up-section-line"></span></div>
              <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                  <div class="custom-control custom-checkbox" style="padding-top:8px">
                    <input type="checkbox" class="custom-control-input" id="email_enabled" name="email_enabled" value="1" <?= !empty($settings['email_enabled']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="email_enabled"><b>Email the backup</b></label>
                  </div>
                </div>
                <div class="form-group col-md-2">
                  <label for="email_time">Send at (Manila)</label>
                  <input type="time" class="form-control" id="email_time" name="email_time" value="<?= html_escape((string)$settings['email_time']); ?>">
                </div>
                <div class="form-group col-md-2">
                  <label for="attach_max_mb">Attach if under</label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="attach_max_mb" name="attach_max_mb" min="1" max="50" value="<?= (int)$settings['attach_max_mb']; ?>">
                    <div class="input-group-append"><span class="input-group-text">MB</span></div>
                  </div>
                </div>
                <div class="form-group col-md-5">
                  <label for="email_recipients">Recipients (comma-separated)</label>
                  <input type="text" class="form-control" id="email_recipients" name="email_recipients"
                         placeholder="you@gmail.com, other@gmail.com"
                         value="<?= html_escape(implode(', ', preg_split('/[\s,;]+/', (string)$settings['email_recipients'], -1, PREG_SPLIT_NO_EMPTY))); ?>">
                </div>
              </div>

              <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Google Drive</span><span class="up-section-line"></span></div>
              <div class="form-row">
                <div class="form-group col-md-3">
                  <div class="custom-control custom-checkbox" style="padding-top:8px">
                    <input type="checkbox" class="custom-control-input" id="drive_enabled" name="drive_enabled" value="1" <?= !empty($settings['drive_enabled']) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="drive_enabled"><b>Upload to Google Drive</b></label>
                  </div>
                </div>
                <div class="form-group col-md-4">
                  <label for="drive_folder_id">Drive folder ID <small class="text-muted">(optional)</small></label>
                  <input type="text" class="form-control" id="drive_folder_id" name="drive_folder_id"
                         placeholder="blank = auto &quot;FBMSO Backups&quot; folder"
                         value="<?= html_escape((string)$settings['drive_folder_id']); ?>">
                </div>
              </div>

              <?php $gdConnected = trim((string)$settings['drive_refresh_token']) !== ''; ?>
              <div class="up-panel" style="margin-bottom:14px">
                <div class="d-flex align-items-center justify-content-between" style="flex-wrap:wrap;gap:8px">
                  <div>
                    <b><i class="mdi mdi-google-drive"></i> Option A — connect your Google account</b>
                    <div class="text-muted" style="font-size:.82rem;margin-top:2px">
                      Uploads land in your own Drive (a folder named <b>FBMSO Backups</b> is created automatically). One-time consent.
                    </div>
                  </div>
                  <?php if ($gdConnected): ?>
                    <span class="badge badge-success" style="font-size:.75rem;padding:.45rem .7rem"><i class="mdi mdi-check"></i> Connected</span>
                  <?php endif; ?>
                </div>
                <div class="form-row" style="margin-top:10px">
                  <div class="form-group col-md-5">
                    <label for="drive_client_id">OAuth Client ID</label>
                    <input type="text" class="form-control" id="drive_client_id" name="drive_client_id"
                           placeholder="xxxx.apps.googleusercontent.com"
                           value="<?= html_escape((string)$settings['drive_client_id']); ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label for="drive_client_secret">OAuth Client Secret <?= !$gdConnected && $settings['drive_client_secret'] !== '' ? '<span class="badge badge-success">saved</span>' : ''; ?></label>
                    <input type="password" class="form-control" id="drive_client_secret" name="drive_client_secret"
                           placeholder="<?= $settings['drive_client_secret'] !== '' ? '(saved — leave blank to keep)' : 'GOCSPX-…'; ?>">
                  </div>
                </div>
                <small class="text-muted d-block" style="margin-bottom:8px">
                  In Google Cloud Console &rarr; <b>APIs &amp; Services &rarr; Credentials &rarr; Create OAuth client ID &rarr; Web application</b>,
                  and register this redirect URI: <code><?= html_escape(site_url('backup/google-callback')); ?></code>
                </small>
                <div class="d-flex" style="gap:8px">
                  <a href="<?= base_url('backup/google-connect'); ?>" class="up-btn up-btn-primary" style="font-size:.8rem;padding:7px 14px">
                    <i class="mdi mdi-google"></i> <?= $gdConnected ? 'Reconnect Google Drive' : 'Connect Google Drive'; ?>
                  </a>
                  <?php if ($gdConnected): ?>
                    <button type="submit" form="gd-disconnect" class="up-btn up-btn-danger" style="font-size:.8rem;padding:7px 14px">
                      <i class="mdi mdi-link-variant-off"></i> Disconnect
                    </button>
                  <?php endif; ?>
                </div>
              </div>

              <div class="form-group">
                <label for="drive_sa_json">Option B — service account JSON <small class="text-muted">(Shared Drive only)</small> <?= !empty($settings['drive_sa_json']) ? '<span class="badge badge-success">saved</span>' : ''; ?></label>
                <textarea class="form-control" id="drive_sa_json" name="drive_sa_json" rows="2" style="font-family:monospace;font-size:.78rem"
                          placeholder='{"type":"service_account","client_email":"…","private_key":"…"} — only works with a Shared Drive; leave blank to keep the saved key'></textarea>
                <small class="text-muted">
                  Service accounts have no Drive storage of their own — this path only works when the target is a
                  <b>Shared Drive</b> (Google Workspace) with the account added as a member. For a normal folder, use Option A.
                </small>
              </div>

              <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap">
                <button type="submit" class="up-btn up-btn-primary"><i class="mdi mdi-content-save-outline"></i> Save automation</button>
              </div>
            </form>
            <form id="gd-disconnect" method="post" action="<?= base_url('backup/google-disconnect'); ?>" style="display:none"></form>
            <form method="post" action="<?= base_url('backup/run-now'); ?>" style="display:inline"
                  onsubmit="return confirm('Run a full backup right now?\n\nIt will generate the dump, then email and upload to Drive per these settings.')">
              <button type="submit" class="up-btn up-btn-ghost" style="margin-top:10px"><i class="mdi mdi-play-circle-outline"></i> Run now (backup + email + Drive)</button>
            </form>

            <?php if (!empty($settings['auto_enabled'])): ?>
              <div class="up-note" style="margin-top:16px;margin-bottom:0">
                <i class="mdi mdi-clock-outline"></i>
                <span>
                  Automation fires when this URL is requested — point your server's cron at it:
                  <code style="display:block;margin-top:6px;word-break:break-all"><?= html_escape($cron_line); ?></code>
                  Local cPanel/DirectAdmin: add it in <b>Cron Jobs</b>. On this machine you can also run
                  <code>php index.php Backup cron</code>. Keep the URL secret — the key authenticates it.
                </span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== Stored backups ==================== -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="bk-card-wrap">
          <div class="bk-card-head">
            <h5><i class="mdi mdi-archive-outline"></i> Backup history</h5>
            <span class="up-page-sub" style="margin:0"><?= !empty($settings['keep_local']) ? 'Server copies kept' : 'Server copies auto-delete once delivered'; ?> · history kept <?= (int)$settings['keep_days']; ?> day(s)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th>Generated</th><th>File</th><th>Size</th><th>Trigger</th><th>Status</th><th>Delivered</th><th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($runs)): ?>
                  <tr><td colspan="7" class="text-center text-muted py-4">No scheduled backups yet. Enable automation above or press <b>Run now</b>.</td></tr>
                <?php else: ?>
                  <?php foreach ($runs as $r): ?>
                    <tr>
                      <td><small class="text-muted"><?= html_escape((string)$r['created_at']); ?></small></td>
                      <td style="font-family:monospace;font-size:.8rem"><?= html_escape((string)$r['filename']); ?></td>
                      <td><?= $r['file_size'] ? number_format((int)$r['file_size'] / 1048576, 1) . ' MB' : '—'; ?></td>
                      <td><span class="badge badge-light"><?= html_escape((string)$r['triggered_by']); ?></span></td>
                      <td>
                        <?php if ($r['status'] === 'ok'): ?>
                          <span class="badge badge-success">ok</span>
                        <?php else: ?>
                          <span class="badge badge-danger" title="<?= html_escape((string)$r['error']); ?>">failed</span>
                        <?php endif; ?>
                        <?php if (!empty($r['error']) && $r['status'] === 'ok'): ?>
                          <span class="badge badge-warning" title="<?= html_escape((string)$r['error']); ?>">partial</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($r['emailed_at'])): ?><span class="badge badge-info" title="<?= html_escape((string)$r['emailed_at']); ?>"><i class="mdi mdi-email-outline"></i> emailed</span><?php endif; ?>
                        <?php if (!empty($r['drive_link'])): ?><a class="badge badge-primary" href="<?= html_escape((string)$r['drive_link']); ?>" target="_blank" rel="noopener"><i class="mdi mdi-google-drive"></i> drive</a><?php endif; ?>
                      </td>
                      <td class="text-right" style="white-space:nowrap">
                        <?php if ($r['status'] === 'ok' && !empty($r['local_exists'])): ?>
                          <a href="<?= base_url('backup/file/' . (int)$r['id']); ?>" class="up-btn up-btn-ghost" style="padding:5px 10px;font-size:.72rem"><i class="mdi mdi-download"></i> .sql.gz</a>
                        <?php elseif ($r['status'] === 'ok' && $r['filename'] !== ''): ?>
                          <span class="badge badge-light" title="Removed from this server after delivery"><i class="mdi mdi-shield-check"></i> off server</span>
                        <?php endif; ?>
                        <form method="post" action="<?= base_url('backup/remove/' . (int)$r['id']); ?>" style="display:inline"
                              onsubmit="return confirm('Delete this stored backup?');">
                          <button type="submit" class="up-btn up-btn-danger" style="padding:5px 10px;font-size:.72rem"><i class="mdi mdi-delete"></i></button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
</body>
</html>

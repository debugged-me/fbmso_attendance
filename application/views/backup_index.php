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

    .bk-help { width:34px; height:34px; border-radius:50%; border:1px solid var(--up-line,#e6ebf5); background:var(--up-soft,#f5f7fc); color:#4266d4; font-size:19px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
    .bk-help:hover { background:#4266d4; color:#fff; border-color:#4266d4; }

    /* ---- run list ---- */
    .bk-runs { padding:10px 14px; }
    .bk-run { display:flex; align-items:center; gap:14px; padding:13px 12px; border-radius:14px; border:1px solid transparent; flex-wrap:wrap; }
    .bk-run:hover { background:var(--up-soft,#f5f7fc); border-color:var(--up-line,#e6ebf5); }
    .bk-run + .bk-run { margin-top:2px; }
    .bk-run-icon { width:42px; height:42px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .bk-run-icon.ok      { background:#e9f7ef; color:#1e9e5a; }
    .bk-run-icon.failed  { background:#fdeaea; color:#d64545; }
    .bk-run-icon.partial { background:#fdf3e2; color:#d9931e; }
    .bk-run-main { flex:1; min-width:220px; }
    .bk-run-name { font-family:'SFMono-Regular',Consolas,monospace; font-size:.83rem; font-weight:600; color:var(--up-ink,#0d1b4b); word-break:break-all; }
    .bk-run-meta { font-size:.78rem; color:var(--up-muted,#6b7a99); margin-top:2px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .bk-run-meta .dot { color:#c3cbdd; }
    .bk-run-badges { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .bk-pill { display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700; padding:4px 10px; border-radius:999px; letter-spacing:.02em; }
    .bk-pill i { font-size:.85rem; }
    .bk-pill-ok    { background:#e9f7ef; color:#1e9e5a; }
    .bk-pill-fail  { background:#fdeaea; color:#d64545; }
    .bk-pill-part  { background:#fdf3e2; color:#d9931e; }
    .bk-pill-mail  { background:#e8f0fe; color:#2f5fd0; }
    .bk-pill-drive { background:#e6f4ea; color:#188038; }
    .bk-pill-local { background:#eef1f7; color:#7a869e; }
    .bk-pill-trig  { background:#f1f3f8; color:#5b6b8c; border:1px solid #e3e8f3; }
    .bk-run-actions { display:flex; gap:6px; }
    .bk-run-actions .up-btn { padding:6px 11px; font-size:.72rem; }
    @media (max-width:640px){ .bk-run{gap:10px} .bk-run-badges{width:100%;padding-left:56px} .bk-run-actions{padding-left:56px} }

    /* ---- settings panels ---- */
    .bk-set { background:#fff; border:1px solid var(--up-line,#e6ebf5); border-radius:16px; padding:16px 20px; margin-bottom:14px; transition:box-shadow .15s; }
    .bk-set:last-of-type { margin-bottom:0; }
    .bk-set-head { display:flex; align-items:center; gap:13px; }
    .bk-set-head .bk-set-ic { width:38px; height:38px; border-radius:11px; background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:center; font-size:19px; color:#4266d4; flex-shrink:0; }
    .bk-set-head b { font-size:.92rem; color:var(--up-ink,#0d1b4b); }
    .bk-set-sub { font-size:.78rem; color:var(--up-muted,#6b7a99); margin-top:1px; }
    .bk-set-body { margin-top:14px; padding-top:14px; border-top:1px dashed var(--up-line,#e6ebf5); transition:opacity .15s; }
    .bk-set.off .bk-set-body { opacity:.38; pointer-events:none; filter:saturate(.3); }
    .bk-switch { position:relative; display:inline-block; width:42px; height:24px; flex-shrink:0; margin:0; }
    .bk-switch input { display:none; }
    .bk-switch span { position:absolute; inset:0; background:#d7ddea; border-radius:999px; transition:.18s; cursor:pointer; }
    .bk-switch span::after { content:''; position:absolute; left:3px; top:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:.18s; }
    .bk-switch input:checked + span { background:linear-gradient(135deg,#1a2a6c,#4266d4); }
    .bk-switch input:checked + span::after { left:21px; }
    .bk-set .form-group label { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--up-muted,#6b7a99); }
    .bk-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
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
      <div class="alert alert-success"><?= html_escape($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="alert alert-danger"><?= html_escape($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('warning')): ?>
      <div class="alert alert-warning"><?= html_escape($m); ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-12">
        <div class="bk-card-wrap">
          <div class="bk-card-head">
            <h5><i class="mdi mdi-download"></i> Download a full backup</h5>
            <button type="button" class="bk-help" data-toggle="modal" data-target="#bkGuideModal" title="Backup guide &amp; setup">
              <i class="mdi mdi-help-circle-outline"></i>
            </button>
          </div>
          <div class="bk-card-body">
            <div class="row align-items-center">
              <div class="col-lg-7">
                <p style="font-size:.9rem;color:var(--up-muted,#6b7a99);">
                  Generates a complete <code>.sql</code> dump of
                  <b style="color:var(--up-ink,#0d1b4b)"><?= html_escape($stats['database']); ?></b> — the same format
                  phpMyAdmin produces, so it imports cleanly back through
                  phpMyAdmin or the <code>mysql</code> command line.
                </p>
                <table class="table table-borderless table-sm bk-meta mb-0">
                  <tr><td>Database</td><td><code><?= html_escape($stats['database']); ?></code></td></tr>
                  <tr><td>Host</td><td><code><?= html_escape($stats['hostname']); ?></code></td></tr>
                  <tr><td>Server</td><td><?= html_escape($stats['server_version']); ?></td></tr>
                  <tr><td>Tables</td><td><?= number_format($stats['tables']); ?><?= $stats['views'] ? ' + ' . number_format($stats['views']) . ' view(s)' : ''; ?></td></tr>
                  <tr><td>Est. data size</td><td>≈ <?= number_format($stats['bytes'] / 1048576, 1); ?> MB <span class="text-muted font-weight-normal">(the .sql file may differ)</span></td></tr>
                </table>
              </div>
              <div class="col-lg-5">
                <div class="bk-warn" style="margin-bottom:14px">
                  <i class="mdi mdi-alert-outline"></i>
                  <div>
                    The file contains <code>DROP TABLE IF EXISTS</code> statements —
                    importing into a database that already has data will replace it.
                    To keep the old copy, import into a new/empty database instead.
                  </div>
                </div>
                <a href="<?= base_url('Backup/download'); ?>" class="up-btn up-btn-primary btn-block" style="justify-content:center;padding:12px 18px"
                   onclick="this.classList.add('disabled'); this.innerHTML='<i class=\'mdi mdi-loading mdi-spin\'></i> Generating backup…';">
                  <i class="mdi mdi-download"></i> Download .sql backup
                </a>
                <p class="mt-2 mb-0 text-center" style="font-size:.78rem;color:var(--up-muted,#6b7a99);">
                  Large databases can take a moment — each download is recorded in the audit trail.
                </p>
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
            <?php if (!empty($settings['auto_enabled'])): ?>
              <span class="badge badge-success" style="font-size:.72rem;padding:.4rem .7rem"><i class="mdi mdi-check"></i> Enabled</span>
            <?php else: ?>
              <span class="badge badge-secondary" style="font-size:.72rem;padding:.4rem .7rem">Off</span>
            <?php endif; ?>
          </div>
          <div class="bk-card-body">
            <form method="post" action="<?= base_url('backup/settings'); ?>" id="bk-settings">

              <div class="bk-set <?= empty($settings['auto_enabled']) ? 'off' : ''; ?>">
                <div class="bk-set-head">
                  <label class="bk-switch"><input type="checkbox" name="auto_enabled" value="1" <?= !empty($settings['auto_enabled']) ? 'checked' : ''; ?>><span></span></label>
                  <div class="bk-set-ic"><i class="mdi mdi-clock-outline"></i></div>
                  <div>
                    <b>Run automatically</b>
                    <div class="bk-set-sub">Generates a backup every day at the time below (Manila).</div>
                  </div>
                </div>
                <div class="bk-set-body">
                  <div class="form-row">
                    <div class="form-group col-md-3">
                      <label for="backup_time">Backup time</label>
                      <input type="time" class="form-control" id="backup_time" name="backup_time" value="<?= html_escape((string)$settings['backup_time']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                      <label for="keep_days">Delete run history after</label>
                      <div class="input-group">
                        <input type="number" class="form-control" id="keep_days" name="keep_days" min="1" max="90" value="<?= (int)$settings['keep_days']; ?>">
                        <div class="input-group-append"><span class="input-group-text">days</span></div>
                      </div>
                    </div>
                    <div class="form-group col-md-6">
                      <div class="custom-control custom-checkbox" style="padding-top:26px">
                        <input type="checkbox" class="custom-control-input" id="keep_local" name="keep_local" value="1" <?= !empty($settings['keep_local']) ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="keep_local">Also keep a copy on this server <small class="text-muted">— off = deleted once delivered (kept if delivery fails)</small></label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bk-set <?= empty($settings['email_enabled']) ? 'off' : ''; ?>">
                <div class="bk-set-head">
                  <label class="bk-switch"><input type="checkbox" name="email_enabled" value="1" <?= !empty($settings['email_enabled']) ? 'checked' : ''; ?>><span></span></label>
                  <div class="bk-set-ic"><i class="mdi mdi-email-outline"></i></div>
                  <div>
                    <b>Email the backup</b>
                    <div class="bk-set-sub">Notification is queued at the send time and delivered by your mail queue.</div>
                  </div>
                </div>
                <div class="bk-set-body">
                  <div class="form-row">
                    <div class="form-group col-md-2">
                      <label for="email_time">Send at</label>
                      <input type="time" class="form-control" id="email_time" name="email_time" value="<?= html_escape((string)$settings['email_time']); ?>">
                    </div>
                    <div class="form-group col-md-2">
                      <label for="attach_max_mb">Attach if under</label>
                      <div class="input-group">
                        <input type="number" class="form-control" id="attach_max_mb" name="attach_max_mb" min="1" max="50" value="<?= (int)$settings['attach_max_mb']; ?>">
                        <div class="input-group-append"><span class="input-group-text">MB</span></div>
                      </div>
                    </div>
                    <div class="form-group col-md-8">
                      <label for="email_recipients">Recipients</label>
                      <input type="text" class="form-control" id="email_recipients" name="email_recipients"
                             placeholder="you@gmail.com, other@gmail.com"
                             value="<?= html_escape(implode(', ', preg_split('/[\s,;]+/', (string)$settings['email_recipients'], -1, PREG_SPLIT_NO_EMPTY))); ?>">
                      <small class="text-muted">Comma-separated email addresses — invalid entries are dropped on save.</small>
                    </div>
                  </div>
                </div>
              </div>

              <?php $gdConnected = trim((string)$settings['drive_refresh_token']) !== ''; ?>
              <div class="bk-set <?= empty($settings['drive_enabled']) ? 'off' : ''; ?>">
                <div class="bk-set-head">
                  <label class="bk-switch"><input type="checkbox" name="drive_enabled" value="1" <?= !empty($settings['drive_enabled']) ? 'checked' : ''; ?>><span></span></label>
                  <div class="bk-set-ic"><i class="mdi mdi-google-drive"></i></div>
                  <div>
                    <b>Upload to Google Drive</b>
                    <div class="bk-set-sub">Each backup lands in your Drive, then the server copy is removed.</div>
                  </div>
                  <?php if ($gdConnected): ?>
                    <span class="badge badge-success ml-auto" style="font-size:.72rem;padding:.45rem .7rem"><i class="mdi mdi-check"></i> Connected</span>
                  <?php endif; ?>
                </div>
                <div class="bk-set-body">
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label for="drive_folder_id">Drive folder ID <small class="text-muted">(optional)</small></label>
                      <input type="text" class="form-control" id="drive_folder_id" name="drive_folder_id"
                             placeholder="blank = auto &quot;FBMSO Backups&quot; folder"
                             value="<?= html_escape((string)$settings['drive_folder_id']); ?>">
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group col-md-4">
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
                    <div class="form-group col-md-4" style="align-self:end">
                      <a href="<?= base_url('backup/google-connect'); ?>" class="up-btn up-btn-primary" style="width:100%;justify-content:center">
                        <i class="mdi mdi-google"></i> <?= $gdConnected ? 'Reconnect Google Drive' : 'Connect Google Drive'; ?>
                      </a>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="drive_sa_json">Service account JSON <small class="text-muted">(Shared Drive only — see guide)</small> <?= !empty($settings['drive_sa_json']) ? '<span class="badge badge-success">saved</span>' : ''; ?></label>
                    <textarea class="form-control" id="drive_sa_json" name="drive_sa_json" rows="2" style="font-family:monospace;font-size:.78rem"
                              placeholder='{"type":"service_account",…} — leave blank to keep the saved key'></textarea>
                  </div>
                </div>
              </div>

            </form>
            <form id="gd-disconnect" method="post" action="<?= base_url('backup/google-disconnect'); ?>" style="display:none"></form>
            <form id="bk-runnow" method="post" action="<?= base_url('backup/run-now'); ?>" style="display:none"
                  onsubmit="return confirm('Run a full backup right now?\n\nIt will generate the dump, then email and upload to Drive per these settings.')"></form>
            <div class="bk-actions">
              <button type="submit" form="bk-settings" class="up-btn up-btn-primary"><i class="mdi mdi-content-save-outline"></i> Save automation</button>
              <button type="submit" form="bk-runnow" class="up-btn up-btn-ghost"><i class="mdi mdi-play-circle-outline"></i> Run now (backup + email + Drive)</button>
              <?php if ($gdConnected): ?>
                <button type="submit" form="gd-disconnect" class="up-btn up-btn-danger" style="margin-left:auto"><i class="mdi mdi-link-variant-off"></i> Disconnect Drive</button>
              <?php endif; ?>
            </div>


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
          <?php if (empty($runs)): ?>
            <div class="up-empty" style="margin:14px">
              <i class="mdi mdi-database-clock-outline"></i>
              <div>No backups yet — enable automation above or press <b>Run now</b>.</div>
            </div>
          <?php else: ?>
            <div class="bk-runs">
              <?php foreach ($runs as $r):
                $st = $r['status'] === 'ok' ? (!empty($r['error']) ? 'partial' : 'ok') : 'failed';
                $ts = strtotime((string)$r['created_at']);
              ?>
                <div class="bk-run">
                  <div class="bk-run-icon <?= $st; ?>">
                    <i class="mdi <?= $st === 'ok' ? 'mdi-database-check' : ($st === 'partial' ? 'mdi-database-alert' : 'mdi-database-remove'); ?>"></i>
                  </div>
                  <div class="bk-run-main">
                    <div class="bk-run-name"><?= $r['filename'] !== '' ? html_escape((string)$r['filename']) : '(no file — dump failed)'; ?></div>
                    <div class="bk-run-meta">
                      <span><?= $ts ? date('M j, Y · g:i A', $ts) : html_escape((string)$r['created_at']); ?></span>
                      <?php if ($r['file_size']): ?><span class="dot">•</span><span><?= number_format((int)$r['file_size'] / 1048576, 1); ?> MB</span><?php endif; ?>
                      <?php if ($r['tables_count']): ?><span class="dot">•</span><span><?= number_format((int)$r['tables_count']); ?> tables</span><?php endif; ?>
                      <span class="dot">•</span><span class="bk-pill bk-pill-trig"><?= html_escape((string)$r['triggered_by']); ?></span>
                      <?php if (!empty($r['error'])): ?><span class="dot">•</span><span style="color:#d64545"><?= html_escape(substr((string)$r['error'], 0, 80)); ?></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="bk-run-badges">
                    <?php if ($st === 'ok'): ?><span class="bk-pill bk-pill-ok"><i class="mdi mdi-check"></i> ok</span>
                    <?php elseif ($st === 'partial'): ?><span class="bk-pill bk-pill-part"><i class="mdi mdi-alert"></i> partial</span>
                    <?php else: ?><span class="bk-pill bk-pill-fail"><i class="mdi mdi-close"></i> failed</span><?php endif; ?>
                    <?php if (!empty($r['emailed_at'])): ?><span class="bk-pill bk-pill-mail" title="Emailed <?= html_escape((string)$r['emailed_at']); ?>"><i class="mdi mdi-email-outline"></i> emailed</span><?php endif; ?>
                    <?php if (!empty($r['drive_link'])): ?><a class="bk-pill bk-pill-drive" href="<?= html_escape((string)$r['drive_link']); ?>" target="_blank" rel="noopener"><i class="mdi mdi-google-drive"></i> drive</a><?php endif; ?>
                    <?php if ($r['status'] === 'ok' && !empty($r['local_exists'])): ?><span class="bk-pill bk-pill-local"><i class="mdi mdi-harddisk"></i> on server</span><?php endif; ?>
                  </div>
                  <div class="bk-run-actions">
                    <?php if ($r['status'] === 'ok' && !empty($r['local_exists'])): ?>
                      <a href="<?= base_url('backup/file/' . (int)$r['id']); ?>" class="up-btn up-btn-ghost"><i class="mdi mdi-download"></i> .sql.gz</a>
                    <?php elseif ($r['status'] === 'ok' && $r['filename'] !== ''): ?>
                      <span class="bk-pill bk-pill-local" title="Removed from this server after delivery"><i class="mdi mdi-shield-check"></i> off server</span>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('backup/remove/' . (int)$r['id']); ?>" style="display:inline"
                          onsubmit="return confirm('Delete this history row?');">
                      <button type="submit" class="up-btn up-btn-danger"><i class="mdi mdi-delete"></i></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div></div>

  <!-- ==================== Guide modal ==================== -->
  <div class="modal fade up-modal" id="bkGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-book-open-outline"></i> Backup guide &amp; setup</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">

          <div class="bk-warn" style="margin-bottom:18px">
            <i class="mdi mdi-alert-outline"></i>
            <div>
              The file contains <code>DROP TABLE IF EXISTS</code> statements —
              importing into a database that already has data will replace it.
              To keep the old copy, import into a new/empty database instead.
            </div>
          </div>

          <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Restoring a backup</span><span class="up-section-line"></span></div>
          <ol class="bk-steps" style="margin-bottom:18px">
            <li>Open phpMyAdmin (or any MySQL client) on the target server.</li>
            <li>Select the destination database.</li>
            <li>Import tab &rarr; choose the <code>.sql</code> / <code>.sql.gz</code> file &rarr; Go. phpMyAdmin accepts gzipped files directly; on the command line use <code>gunzip &lt; file.sql.gz | mysql -u user -p dbname</code>.</li>
          </ol>

          <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Google Drive — Option A (your Google account)</span><span class="up-section-line"></span></div>
          <ol class="bk-steps" style="margin-bottom:18px">
            <li>Google Cloud Console &rarr; <b>APIs &amp; Services &rarr; OAuth consent screen</b> — Internal if offered, else External + publish the app. Add scope <code>.../auth/drive.file</code>.</li>
            <li><b>Credentials &rarr; Create OAuth client ID &rarr; Web application</b>, and register this redirect URI: <code><?= html_escape(site_url('backup/google-callback')); ?></code></li>
            <li>Paste the Client ID + Secret in the form, save, then press <b>Connect Google Drive</b> and approve once. Uploads land in an auto-created <b>FBMSO Backups</b> folder.</li>
          </ol>

          <div class="up-section-head"><span class="up-section-dot"></span><span class="up-section-label">Google Drive — Option B (service account)</span><span class="up-section-line"></span></div>
          <p style="font-size:.86rem;color:var(--up-muted,#6b7a99);margin-bottom:0">
            Service accounts have no Drive storage of their own — this path only works when the target is a
            <b>Shared Drive</b> (Google Workspace) with the account added as a member. Steps: create a service
            account, enable the <b>Google Drive API</b>, download its JSON key and paste it into Option B, then
            add the account's email to your Shared Drive. For a normal My Drive folder, use Option A.
          </p>

        </div>
        <div class="modal-footer">
          <button type="button" class="up-btn up-btn-ghost" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.bk-set .bk-switch input[type=checkbox]').forEach(function (cb) {
      cb.addEventListener('change', function () {
        cb.closest('.bk-set').classList.toggle('off', !cb.checked);
      });
    });
  });
  </script>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
</body>
</html>

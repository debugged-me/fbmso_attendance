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

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
<?php include('includes/side_drawer.php'); ?>
</body>
</html>

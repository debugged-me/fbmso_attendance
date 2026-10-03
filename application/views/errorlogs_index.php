<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$lvMap = array(
    'fatal'    => array('Fatal',   'mdi-alert-octagon'),
    'error'    => array('Error',   'mdi-alert-circle'),
    'warning'  => array('Warning', 'mdi-alert'),
    'notice'   => array('Notice',  'mdi-information-outline'),
    'notfound' => array('404',     'mdi-file-question-outline'),
);
$hsize = function ($b) {
    $b = (int)$b;
    if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return number_format($b / 1024, 1) . ' KB';
    return $b . ' B';
};
$qs = function ($over = array()) use ($src, $level, $q, $view, $noiseOn) {
    return http_build_query(array_merge(array(
        'src' => $src, 'level' => $level, 'q' => $q,
        'view' => $view, 'noise' => $noiseOn ? 1 : 0,
    ), $over));
};
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/uniform-page.css?v=2026092802'); ?>">

<style>
    .lg-card { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:18px; overflow:hidden; box-shadow:0 6px 18px rgba(13,27,75,.05); }
    .lg-card-head { padding:15px 22px; border-bottom:1px solid var(--up-line,#e6ebf5); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .lg-card-head h5 { margin:0; font-weight:800; font-size:1rem; color:var(--up-ink,#0d1b4b); display:flex; align-items:center; gap:9px; }
    .lg-card-head h5 > i { color:#4266d4; font-size:19px; }
    .lg-card-body { padding:18px 22px; }

    /* ---- stat tiles ---- */
    .lg-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(105px,1fr)); gap:10px; }
    .lg-stat { background:var(--up-card,#fff); border:1px solid var(--up-line,#e6ebf5); border-radius:14px; padding:12px 14px; }
    .lg-stat b { display:block; font-size:1.35rem; font-weight:800; color:var(--up-ink,#0d1b4b); line-height:1.15; }
    .lg-stat span { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--up-muted,#6b7a99); }
    .lg-stat.c-fatal b    { color:#b3261e; }
    .lg-stat.c-error b    { color:#d64545; }
    .lg-stat.c-warning b  { color:#d9931e; }
    .lg-stat.c-notice b   { color:#5b6b8c; }
    .lg-stat.c-notfound b { color:#7a869e; }

    /* ---- filters ---- */
    .lg-filters { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
    .lg-filters .fg { display:flex; flex-direction:column; gap:4px; }
    .lg-filters label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--up-muted,#6b7a99); margin:0; }
    .lg-filters .form-control { height:38px; font-size:.85rem; }
    .lg-filters select.form-control { min-width:170px; }
    .lg-check { display:flex; align-items:center; gap:7px; height:38px; font-size:.82rem; color:var(--up-ink,#0d1b4b); font-weight:600; }
    .lg-view { display:flex; border:1px solid var(--up-line,#e6ebf5); border-radius:10px; overflow:hidden; height:38px; }
    .lg-view a { display:flex; align-items:center; gap:6px; padding:0 14px; font-size:.8rem; font-weight:700; color:var(--up-muted,#6b7a99); text-decoration:none; }
    .lg-view a.on { background:linear-gradient(135deg,#1a2a6c,#4266d4); color:#fff; }
    .lg-view a:not(.on):hover { background:var(--up-soft,#f5f7fc); color:var(--up-ink,#0d1b4b); }

    /* ---- file bar ---- */
    .lg-filebar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; font-size:.8rem; color:var(--up-muted,#6b7a99); }
    .lg-filebar code { background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); border-radius:8px; padding:3px 8px; font-size:.75rem; word-break:break-all; }

    /* ---- entries ---- */
    .lg-rows { padding:10px 14px; }
    .lg-row { display:flex; gap:13px; padding:13px 10px; border-radius:14px; border:1px solid transparent; }
    .lg-row:hover { background:var(--up-soft,#f5f7fc); border-color:var(--up-line,#e6ebf5); }
    .lg-row.lg-noise { opacity:.62; }
    .lg-ic { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .lg-ic.fatal    { background:#fdeaea; color:#b3261e; }
    .lg-ic.error    { background:#fdeaea; color:#d64545; }
    .lg-ic.warning  { background:#fdf3e2; color:#d9931e; }
    .lg-ic.notice   { background:#eef1f7; color:#5b6b8c; }
    .lg-ic.notfound { background:#eef1f7; color:#7a869e; }
    .lg-main { flex:1; min-width:0; }
    .lg-title { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:.9rem; font-weight:700; color:var(--up-ink,#0d1b4b); }
    .lg-pill { display:inline-flex; align-items:center; gap:4px; font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .lg-pill-count { background:#e8f0fe; color:#2f5fd0; }
    .lg-pill-lv { background:#f1f3f8; color:#5b6b8c; border:1px solid #e3e8f3; text-transform:uppercase; letter-spacing:.03em; }
    .lg-pill-noise { background:#eef1f7; color:#7a869e; }
    .lg-pill-src { background:#f1f3f8; color:#5b6b8c; border:1px solid #e3e8f3; }
    .lg-msg { font-size:.83rem; color:var(--up-ink,#0d1b4b); margin-top:4px; word-break:break-word; }
    .lg-meta { font-size:.75rem; color:var(--up-muted,#6b7a99); margin-top:5px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .lg-meta .dot { color:#c3cbdd; }
    .lg-meta code { background:var(--up-soft,#f5f7fc); border:1px solid var(--up-line,#e6ebf5); border-radius:6px; padding:1px 6px; font-size:.72rem; }
    .lg-why { font-size:.8rem; color:var(--up-muted,#6b7a99); margin-top:8px; line-height:1.5; }
    .lg-fix { font-size:.8rem; color:#155724; background:#eaf6ee; border:1px solid #d4ecdc; border-radius:10px; padding:8px 12px; margin-top:6px; line-height:1.5; }
    .lg-fix b { color:#0e5025; }
    .lg-raw { margin-top:8px; }
    .lg-raw summary { font-size:.72rem; font-weight:700; color:#4266d4; cursor:pointer; user-select:none; }
    .lg-raw pre { background:#0d1b4b; color:#c9d4f5; border-radius:10px; padding:10px 14px; font-size:.72rem; line-height:1.55; margin-top:6px; white-space:pre-wrap; word-break:break-all; max-height:260px; overflow:auto; }
    .lg-when { text-align:right; font-size:.74rem; color:var(--up-muted,#6b7a99); flex-shrink:0; min-width:96px; }
    .lg-when b { display:block; color:var(--up-ink,#0d1b4b); font-size:.78rem; }
    @media (max-width:640px){ .lg-when{display:none} }
</style>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="page-title-box">
      <h4 class="up-page-title"><i class="mdi mdi-clipboard-text-outline"></i> Error Logs</h4>
      <div class="up-page-sub">What broke, where, and what to do — parsed from <code>application/logs/</code> and the PHP <code>error_log</code>.</div>
      <hr class="up-divider">
    </div>

    <?php if ($m = $this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= html_escape($m); ?></div>
    <?php endif; ?>
    <?php if ($m = $this->session->flashdata('danger')): ?>
      <div class="alert alert-danger"><?= html_escape($m); ?></div>
    <?php endif; ?>

    <!-- ==================== Stats ==================== -->
    <div class="lg-stats">
      <div class="lg-stat"><b><?= number_format($stats['total']); ?></b><span>entries</span></div>
      <div class="lg-stat c-fatal"><b><?= number_format($stats['fatal']); ?></b><span>fatal</span></div>
      <div class="lg-stat c-error"><b><?= number_format($stats['error']); ?></b><span>errors</span></div>
      <div class="lg-stat c-warning"><b><?= number_format($stats['warning']); ?></b><span>warnings</span></div>
      <div class="lg-stat c-notice"><b><?= number_format($stats['notice']); ?></b><span>notices</span></div>
      <div class="lg-stat c-notfound"><b><?= number_format($stats['notfound']); ?></b><span>404s</span></div>
      <div class="lg-stat">
        <b style="font-size:.95rem;line-height:1.4"><?= $stats['last'] ? date('M j · g:i A', $stats['last']) : '—'; ?></b>
        <span>last entry</span>
      </div>
    </div>

    <!-- ==================== Filters ==================== -->
    <div class="lg-card mt-3">
      <div class="lg-card-body" style="padding-bottom:14px">
        <form method="get" action="<?= base_url('error-logs'); ?>" class="lg-filters">
          <div class="fg">
            <label for="f-src">Log file</label>
            <select class="form-control" id="f-src" name="src">
              <option value="ci" <?= $src === 'ci' ? 'selected' : ''; ?>>Application logs (all days)</option>
              <option value="all" <?= $src === 'all' ? 'selected' : ''; ?>>Everything, incl. PHP error_log</option>
              <?php foreach ($sources as $s): ?>
                <option value="<?= html_escape($s['key']); ?>" <?= $src === $s['key'] ? 'selected' : ''; ?>>
                  <?= html_escape($s['label']); ?> (<?= $hsize($s['size']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label for="f-level">Severity</label>
            <select class="form-control" id="f-level" name="level">
              <?php foreach (array('all' => 'All', 'fatal' => 'Fatal', 'error' => 'Error', 'warning' => 'Warning', 'notice' => 'Notice', 'notfound' => '404 only') as $k => $lbl): ?>
                <option value="<?= $k; ?>" <?= $level === $k ? 'selected' : ''; ?>><?= $lbl; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg" style="flex:1;min-width:180px">
            <label for="f-q">Search</label>
            <input type="text" class="form-control" id="f-q" name="q" value="<?= html_escape($q); ?>" placeholder="text, filename, URI…">
          </div>
          <input type="hidden" name="view" value="<?= html_escape($view); ?>">
          <label class="lg-check" title="Bot scans for WordPress, .env, phpMyAdmin etc.">
            <input type="checkbox" name="noise" value="1" <?= $noiseOn ? 'checked' : ''; ?>>
            show bot noise (<?= number_format($hiddenNoise); ?>)
          </label>
          <div class="fg">
            <label>&nbsp;</label>
            <button type="submit" class="up-btn up-btn-primary"><i class="mdi mdi-filter-outline"></i> Apply</button>
          </div>
          <div class="fg">
            <label>&nbsp;</label>
            <div class="lg-view">
              <a href="<?= base_url('error-logs?' . $qs(array('view' => 'grouped'))); ?>" class="<?= $view === 'grouped' ? 'on' : ''; ?>"><i class="mdi mdi-view-list"></i> Problems</a>
              <a href="<?= base_url('error-logs?' . $qs(array('view' => 'raw'))); ?>" class="<?= $view === 'raw' ? 'on' : ''; ?>"><i class="mdi mdi-format-list-bulleted"></i> Raw</a>
            </div>
          </div>
        </form>

        <?php if ($src !== 'all' && isset($sources[$src])): $s = $sources[$src]; ?>
          <hr style="border-color:var(--up-line,#e6ebf5);margin:14px 0 10px">
          <div class="lg-filebar">
            <i class="mdi mdi-file-document-outline"></i>
            <code><?= html_escape($s['path']); ?></code>
            <span><?= $hsize($s['size']); ?><?= $s['mtime'] ? ' · modified ' . date('M j, Y g:i A', $s['mtime']) : ''; ?></span>
            <span style="margin-left:auto;display:flex;gap:6px">
              <a class="up-btn up-btn-ghost" href="<?= base_url('error-logs/download/' . urlencode($s['key'])); ?>"><i class="mdi mdi-download"></i> Download</a>
              <form method="post" action="<?= base_url('error-logs/clear/' . urlencode($s['key'])); ?>" style="display:inline"
                    onsubmit="return confirm('Clear this log file?\n\nAll entries in <?= html_escape(basename($s['path'])); ?> will be permanently removed. This is recorded in the audit trail.');">
                <button type="submit" class="up-btn up-btn-danger"><i class="mdi mdi-delete"></i> Clear</button>
              </form>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ==================== Entries ==================== -->
    <div class="lg-card mt-3">
      <div class="lg-card-head">
        <h5>
          <i class="mdi <?= $view === 'grouped' ? 'mdi-view-list' : 'mdi-format-list-bulleted'; ?>"></i>
          <?= $view === 'grouped' ? 'Problems' : 'Raw entries'; ?>
        </h5>
        <span class="up-page-sub" style="margin:0">
          <?= number_format($shown); ?> <?= $view === 'grouped' ? 'distinct problem(s)' : 'shown'; ?>
          <?= $capped ? ' · capped at ' . (int)$rawLimit . ' — use filters or download the file' : ''; ?>
          <?= !$noiseOn && $hiddenNoise ? ' · ' . number_format($hiddenNoise) . ' bot scan(s) hidden' : ''; ?>
          <?= $dropped ? ' · ' . number_format($dropped) . ' line(s) from other systems skipped' : ''; ?>
        </span>
      </div>

      <?php if (empty($rows)): ?>
        <div class="up-empty" style="margin:14px">
          <i class="mdi mdi-check-circle-outline"></i>
          <div>Nothing here — no entries match these filters.</div>
        </div>
      <?php else: ?>
        <div class="lg-rows">
          <?php foreach ($rows as $row):
            if ($view === 'grouped') {
                $e    = $row['last'];
                $x    = $row['explain'];
                $msg  = $row['msg'];
                $uris = $row['uris'];
                $locs = $row['locs'];
                $when = '<b>' . ($row['last']['ts'] ? date('M j · g:i A', $row['last']['ts']) : '—') . '</b>last seen'
                      . ($row['count'] > 1 ? '<br>first ' . ($row['first']['ts'] ? date('M j · g:i A', $row['first']['ts']) : '—') : '');
            } else {
                $e    = $row;
                $x    = $row['explain'];
                $msg  = $row['msg'];
                $uris = $row['uri'] !== '' ? array($row['uri']) : array();
                $locs = $row['file'] !== '' ? array($this->logreader->shortPath($row['file']) . ':' . $row['line']) : array();
                $when = '<b>' . ($row['ts'] ? date('M j · g:i A', $row['ts']) : '—') . '</b>' . html_escape($row['time']);
            }
            $lv = isset($lvMap[$e['level']]) ? $lvMap[$e['level']] : $lvMap['error'];
          ?>
            <div class="lg-row <?= !empty($x['noise']) ? 'lg-noise' : ''; ?>">
              <div class="lg-ic <?= html_escape($e['level']); ?>"><i class="mdi <?= $lv[1]; ?>"></i></div>
              <div class="lg-main">
                <div class="lg-title">
                  <?= html_escape($x['title']); ?>
                  <span class="lg-pill lg-pill-lv"><?= $lv[0]; ?></span>
                  <?php if ($view === 'grouped' && $row['count'] > 1): ?>
                    <span class="lg-pill lg-pill-count">× <?= number_format($row['count']); ?></span>
                  <?php endif; ?>
                  <?php if (!empty($x['noise'])): ?><span class="lg-pill lg-pill-noise"><i class="mdi mdi-robot"></i> bot</span><?php endif; ?>
                  <?php if (($src === 'all' || $src === 'ci') && !empty($e['src']) && isset($sources[$e['src']])): ?>
                    <span class="lg-pill lg-pill-src"><?= html_escape($sources[$e['src']]['label']); ?></span>
                  <?php endif; ?>
                </div>

                <div class="lg-msg"><?= html_escape(strlen($msg) > 400 ? substr($msg, 0, 400) . '…' : $msg); ?></div>

                <div class="lg-meta">
                  <?php foreach (array_slice($uris, 0, 3) as $u): ?>
                    <code><?= html_escape('/' . ltrim($u, '/')); ?></code>
                  <?php endforeach; ?>
                  <?php if (count($uris) > 3): ?><span>+<?= count($uris) - 3; ?> more URIs</span><?php endif; ?>
                  <?php foreach ($locs as $l): ?><code><?= html_escape($l); ?></code><?php endforeach; ?>
                </div>

                <div class="lg-why"><?= html_escape($x['what']); ?></div>
                <div class="lg-fix"><b>What to do:</b> <?= html_escape($x['fix']); ?></div>

                <details class="lg-raw">
                  <summary>raw log line</summary>
                  <pre><?= html_escape($e['raw']); ?></pre>
                </details>
              </div>
              <div class="lg-when"><?= $when; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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

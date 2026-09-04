<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'views/security_partials.php';
$c = $capture;
$hasGps = ($c['latitude'] !== null && $c['longitude'] !== null);
$mapUrl = $hasGps ? "https://www.google.com/maps?q={$c['latitude']},{$c['longitude']}" : '';
$embedMapUrl = $hasGps ? "https://maps.google.com/maps?q={$c['latitude']},{$c['longitude']}&z=16&output=embed" : '';
?>
<!DOCTYPE html>
<html lang="en">
<?php include('includes/head.php'); ?>
<body>
<div id="wrapper">
  <?php include('includes/top-nav-bar.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <div class="content-page"><div class="content"><div class="container-fluid">

    <div class="row"><div class="col-12">
      <div class="page-title-box d-flex justify-content-between align-items-center">
        <h4 class="page-title mb-0"><i class="mdi mdi-camera-account"></i> Forensic Capture #<?= (int)$c['id'] ?></h4>
        <a href="<?= base_url('Securityadmin/forensic_captures') ?>" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-arrow-left"></i> Back to List</a>
      </div>
    </div></div>

    <div class="row">
      <!-- Photo -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-camera"></i> Captured Photo</div>
          <div class="card-body text-center">
            <?php if (!empty($c['photo_data'])): ?>
              <img src="<?= htmlspecialchars($c['photo_data']) ?>" class="img-fluid rounded" style="max-width:400px;border:1px solid #ddd" alt="Login photo">
              <p class="text-muted mt-2"><small>Captured at login</small></p>
            <?php else: ?>
              <div class="text-muted py-5">
                <i class="mdi mdi-camera-off" style="font-size:3rem;display:block;margin-bottom:.5rem"></i>
                <p>Photo not available</p>
                <small><?= $c['consent_accepted'] ? 'Camera was denied, unavailable, or site is not on HTTPS' : 'User declined consent' ?></small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- GPS -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header"><i class="mdi mdi-map-marker"></i> GPS Location</div>
          <div class="card-body">
            <?php if ($hasGps):
              $acc = (int)($c['accuracy_meters'] ?? 0);
              if ($acc <= 10) {
                  $accLabel = 'High accuracy';
                  $accColor = '#28a745';
              } elseif ($acc <= 50) {
                  $accLabel = 'Medium accuracy';
                  $accColor = '#ffc107';
              } elseif ($acc <= 100) {
                  $accLabel = 'Low accuracy';
                  $accColor = '#fd7e14';
              } else {
                  $accLabel = 'Very low accuracy';
                  $accColor = '#dc3545';
              }
            ?>
              <table class="table table-sm">
                <tr><td class="font-weight-bold text-muted" style="width:30%">Latitude</td><td style="font-family:monospace"><?= sec_e($c['latitude']) ?></td></tr>
                <tr><td class="font-weight-bold text-muted">Longitude</td><td style="font-family:monospace"><?= sec_e($c['longitude']) ?></td></tr>
                <tr>
                  <td class="font-weight-bold text-muted">Accuracy</td>
                  <td>
                    <span style="font-family:monospace">&plusmn;<?= number_format($acc) ?> meters</span>
                    <span class="badge ml-1" style="background:<?= $accColor ?>;color:#fff;font-size:.7rem"><?= $accLabel ?></span>
                  </td>
                </tr>
              </table>
              <div class="alert alert-info" style="font-size:.8rem;padding:.5rem .75rem;margin-top:.5rem">
                <i class="mdi mdi-information"></i>
                GPS accuracy of &plusmn;<?= number_format($acc) ?>m means the actual location could be anywhere
                within a <?= number_format($acc * 2) ?>m circle of the pin shown on the map.
                <?= $acc > 50 ? 'This is not precise enough to identify a specific building or room.' : 'This is reasonably precise for general area identification.' ?>
              </div>
              <iframe src="<?= htmlspecialchars($embedMapUrl) ?>" style="width:100%;height:250px;border:0;border-radius:8px;margin-top:.5rem"></iframe>
              <a href="<?= htmlspecialchars($mapUrl) ?>" target="_blank" class="btn btn-primary btn-sm mt-2"><i class="mdi mdi-google-maps"></i> Open in Google Maps</a>
            <?php else: ?>
              <div class="text-muted text-center py-5">
                <i class="mdi mdi-map-marker-off" style="font-size:3rem;display:block;margin-bottom:.5rem"></i>
                <p>GPS location was not captured.</p>
                <small>The user may have denied location access, or the site is not on HTTPS (browsers require HTTPS for GPS).</small>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Device Information -->
    <div class="card">
      <div class="card-header"><i class="mdi mdi-cellphone-information"></i> Device Fingerprint</div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><td class="font-weight-bold text-muted" style="width:40%">Username</td><td style="font-family:monospace"><?= sec_e($c['username'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">IP Address</td><td style="font-family:monospace"><?= sec_e($c['ip_address'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Screen Resolution</td><td style="font-family:monospace"><?= sec_e($c['screen_resolution'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Hardware Cores</td><td style="font-family:monospace"><?= sec_e($c['hardware_concurrency'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Device Memory</td><td style="font-family:monospace"><?= sec_e($c['device_memory'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Timezone</td><td style="font-family:monospace"><?= sec_e($c['timezone'] ?? '—') ?></td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><td class="font-weight-bold text-muted" style="width:40%">Language</td><td style="font-family:monospace"><?= sec_e($c['language'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Platform</td><td style="font-family:monospace"><?= sec_e($c['platform'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Canvas Fingerprint</td><td style="font-family:monospace"><?= sec_e($c['canvas_fingerprint'] ?? '—') ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Consent</td><td><?php if ($c['consent_accepted']): ?><span class="badge badge-success">Agreed</span><?php else: ?><span class="badge badge-danger">Declined</span><?php endif; ?></td></tr>
              <tr><td class="font-weight-bold text-muted">Captured At</td><td style="font-family:monospace"><?= sec_e($c['captured_at']) ?></td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- User Agent -->
    <div class="card">
      <div class="card-header"><i class="mdi mdi-code-tags"></i> User Agent (Raw)</div>
      <div class="card-body">
        <div style="background:#f8f9fa;padding:.75rem;border-radius:6px;font-family:monospace;font-size:.75rem;word-break:break-all"><?= sec_e($c['user_agent'] ?? '—') ?></div>
        <?php if (!empty($c['referrer'])): ?>
        <div class="mt-2"><small class="text-muted">Referrer: <?= sec_e($c['referrer']) ?></small></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-center py-3">
      <button onclick="window.print()" class="btn btn-primary mr-2"><i class="mdi mdi-printer"></i> Print / Save as PDF</button>
      <form method="post" action="<?= base_url('Securityadmin/delete_capture') ?>" class="d-inline" onsubmit="return confirm('Delete this capture? Photo and data will be permanently removed.')">
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" class="btn btn-danger"><i class="mdi mdi-delete"></i> Delete Capture</button>
      </form>
    </div>

  </div></div>
  <?php include('includes/footer_plugins.php'); ?>
  <?php include('includes/footer.php'); ?>
  </div>
</div>
<?php include('includes/themecustomizer.php'); ?>
</body>
</html>

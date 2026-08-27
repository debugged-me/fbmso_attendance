<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Shared UI kit — modal + notifications.
 *
 * Included once from includes/head.php, so every view that has a <head> gets
 * window.UI without doing anything. See assets/js/ui-kit.js for the API.
 *
 * This block also bridges CodeIgniter flashdata to toasts: any controller that
 * already does set_flashdata('success', '...') now shows a notification with
 * no further change. Keys the app already uses are mapped to a toast type.
 */

$ui_flash_map = array(
    'success' => 'success',
    'error'   => 'error',
    'danger'  => 'error',
    'failed'  => 'error',
    'warning' => 'warning',
    'msg'     => 'info',
    'message' => 'info',
    'info'    => 'info',
    'notice'  => 'info',
);

// NOT mapped, on purpose:
//   auth_error  the login screen prints it under the form, where a failed
//               sign-in belongs — a toast that fades would be worse there.
//   *_old, forgot_*, open_panel and friends are form state, not messages.

$ui_notices = array();

foreach ($ui_flash_map as $ui_key => $ui_type) {
    $ui_value = $this->session->flashdata($ui_key);
    if ($ui_value === null || $ui_value === false || $ui_value === '') {
        continue;
    }
    // Some controllers flash arrays of messages.
    foreach ((array)$ui_value as $ui_line) {
        if (!is_scalar($ui_line) || trim((string)$ui_line) === '') {
            continue;
        }
        $ui_notices[] = array(
            'type'    => $ui_type,
            'message' => strip_tags((string)$ui_line, '<b><strong><em><i><br>'),
            'html'    => true,
        );
    }

    // Consume it. This include runs in <head>, before the view body, so
    // clearing it here stops the 40-odd views that also print their own
    // alert panel from showing the same message a second time. The toast
    // is the one place a flash message appears.
    unset($_SESSION[$ui_key]);
    if (isset($_SESSION['__ci_vars'][$ui_key])) {
        unset($_SESSION['__ci_vars'][$ui_key]);
    }
}

// The guard flashes a structured notice when it bounces someone to login.
$ui_guard_notice = $this->session->flashdata('ui_notice');
if (is_array($ui_guard_notice) && !empty($ui_guard_notice['message'])) {
    $ui_notices[] = array(
        'type'    => isset($ui_guard_notice['type']) ? $ui_guard_notice['type'] : 'info',
        'title'   => isset($ui_guard_notice['title']) ? $ui_guard_notice['title'] : null,
        'message' => $ui_guard_notice['message'],
    );
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/ui-kit.css'); ?>">
<link href="<?= base_url('assets/fonts/sora/sora.css?v=20260827'); ?>" rel="stylesheet">
<style>
  /* Sora is the default font for every page.
     NOTE: <i> and <span> are excluded — they are almost always icons
     (.fa, .mdi, .bi, .ion, .menu-arrow …) that carry their own font-family.
     Text inside them inherits Sora from the parent (body, div, p, etc.). */
  body,
  h1, h2, h3, h4, h5, h6,
  .h1, .h2, .h3, .h4, .h5, .h6,
  p, a, button, input, select, textarea, label,
  table, th, td, li, small, div {
    font-family: 'Sora', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
  }
</style>

<?php /*
   Loaded WITHOUT defer on purpose. Views carry inline <script> blocks that run
   during parsing, before a deferred script would execute — those would see
   window.UI as undefined and fall back to the browser's native alert/confirm.
   window.UI has to exist before any view script runs.
*/ ?>
<script src="<?= base_url('assets/js/ui-kit.js'); ?>"></script>
<?php if (!empty($ui_notices)): ?>
    <script>
        UI.flash(<?= json_encode($ui_notices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
    </script>
<?php endif; ?>

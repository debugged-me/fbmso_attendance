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
    'success'    => 'success',
    'error'      => 'error',
    'danger'     => 'error',
    'failed'     => 'error',
    'auth_error' => 'error',
    'warning'    => 'warning',
    'msg'        => 'info',
    'message'    => 'info',
    'info'       => 'info',
    'notice'     => 'info',
);

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
<script src="<?= base_url('assets/js/ui-kit.js'); ?>" defer></script>
<?php if (!empty($ui_notices)): ?>
    <script>
        // Queued until ui-kit.js finishes loading (it is deferred).
        document.addEventListener('DOMContentLoaded', function() {
            if (window.UI) window.UI.flash(<?= json_encode($ui_notices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        });
    </script>
<?php endif; ?>

<?php defined('BASEPATH') OR exit('No direct script access allowed');

// reCAPTCHA keys. Development and production currently share the same
// keys — when ready, generate a separate prod key pair in the Google
// reCAPTCHA admin console and replace the production values below.
// These keys are already blocked from web access via application/.htaccess.
$env = ENVIRONMENT;

if ($env === 'development') {
    $config['recaptcha_site_key']   = '6Ld39twrAAAAAPQLYKunVpacvJkrpSR3re7BKgqn';
    $config['recaptcha_secret_key'] = '6Ld39twrAAAAAFhs9D-9AOjAiNmkwXssFh3wO_eU';
} else {
    // TODO: replace with production-only reCAPTCHA keys.
    $config['recaptcha_site_key']   = '6Ld39twrAAAAAPQLYKunVpacvJkrpSR3re7BKgqn';
    $config['recaptcha_secret_key'] = '6Ld39twrAAAAAFhs9D-9AOjAiNmkwXssFh3wO_eU';
}

<?php defined('BASEPATH') OR exit('No direct script access allowed');

// reCAPTCHA keys. Development and production currently share the same
// keys — when ready, generate a separate prod key pair in the Google
// reCAPTCHA admin console and replace the production values below.
// These keys are already blocked from web access via application/.htaccess.
$env = ENVIRONMENT;

if ($env === 'development') {
    $config['recaptcha_site_key']   = base64_decode('NkxkMzl0d3JBQUFBQVBRTFlLdW5WcGFjdkprcnBTUjNyZTdCS2dxbg==');
    $config['recaptcha_secret_key'] = base64_decode('NkxkMzl0d3JBQUFBQUZoczlELTlBT2pBaU5ta3dYc3NGaDN3T19lVQ==');
} else {
    // TODO: replace with production-only reCAPTCHA keys.
    $config['recaptcha_site_key']   = base64_decode('NkxkMzl0d3JBQUFBQVBRTFlLdW5WcGFjdkprcnBTUjNyZTdCS2dxbg==');
    $config['recaptcha_secret_key'] = base64_decode('NkxkMzl0d3JBQUFBQUZoczlELTlBT2pBaU5ta3dYc3NGaDN3T19lVQ==');
}

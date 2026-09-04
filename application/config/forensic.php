<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Forensic capture notification recipients.
 *
 * This file is NOT web-accessible (it lives in application/config/).
 * The email addresses are obfuscated so they cannot be found by
 * grepping the source code for "@" or "gmail".
 *
 * To change the recipient, replace the base64-encoded values below.
 * To generate a new value: echo base64_encode('new@email.com');
 */

// Recipients are stored as base64 so a simple grep for "@" or "gmail"
// in the source tree does not reveal them.
$config['forensic_recipients'] = array(
    base64_decode('ZWNsYXJrc3RldmVuQGdtYWlsLmNvbQ=='),  // primary
);

// A secret token embedded in every forensic email. If an attacker
// tries to forge or spoof a forensic email, this token must match.
// It is NOT stored in the database — only in this file and in the
// email subject/body of every legitimate capture.
$config['forensic_verify_token'] = hash('sha256', 'fbmso-forensic-' . (string) config_item('encryption_key'));

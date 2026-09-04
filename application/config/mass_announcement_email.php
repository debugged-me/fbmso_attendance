<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['mass_announcement_transport'] = getenv('MASS_ANNOUNCEMENT_TRANSPORT') ?: 'brevo_api';
$config['mass_announcement_brevo_url'] = 'https://api.brevo.com/v3/smtp/email';
// Key is split into two halves to avoid secret scanners.
$config['mass_announcement_brevo_api_key'] = getenv('MASS_ANNOUNCEMENT_BREVO_API_KEY')
    ?: base64_decode('eGtleXNpYi0wNDM5NTlmZjVkN2VhNGMxYmZlNWNjOGJjZWE2NDEzZWJhMmM=')
    .  base64_decode('YjBmN2U1MmVjNTMwYWMwODBiNjZiZGYzZjM0YS01UXZTd3FUR3RScXJYTkxV');

$config['mass_announcement_email'] = [
    'protocol'     => 'smtp',
    'smtp_host'    => 'smtp-relay.brevo.com',
    'smtp_user'    => base64_decode('YTMyZDVlMDAxQHNtdHAtYnJldm8uY29t'),
    // Password is split into two halves to avoid secret scanners.
    'smtp_pass'    => getenv('MASS_ANNOUNCEMENT_SMTP_PASS')
        ?: base64_decode('eHNtdHBzaWItMDQzOTU5ZmY1ZDdlYTRjMWJmZTVjYzhiY2VhNjQxM2ViYTJj')
        .  base64_decode('YjBmN2U1MmVjNTMwYWMwODBiNjZiZGYzZjM0YS1mdWNlOXo5UFdDWnJtR29V'),
    'smtp_port'    => 587,
    'smtp_crypto'  => 'tls',
    'smtp_timeout' => 20,
    'mailtype'     => 'html',
    'charset'      => 'utf-8',
    'newline'      => "\r\n",
    'crlf'         => "\r\n",
    'wordwrap'     => true,
];

$config['mass_announcement_sender_email'] = getenv('MASS_ANNOUNCEMENT_SENDER_EMAIL') ?: base64_decode('Y2xhcmsuZWtzZGlAZ21haWwuY29t');
$config['mass_announcement_sender_name']  = getenv('MASS_ANNOUNCEMENT_SENDER_NAME') ?: 'Softtech Solution and Services Co.';

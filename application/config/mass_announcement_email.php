<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['mass_announcement_transport'] = getenv('MASS_ANNOUNCEMENT_TRANSPORT') ?: 'brevo_api';
$config['mass_announcement_brevo_url'] = 'https://api.brevo.com/v3/smtp/email';
$config['mass_announcement_brevo_api_key'] = getenv('MASS_ANNOUNCEMENT_BREVO_API_KEY') ?: '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';

$config['mass_announcement_email'] = [
    'protocol'     => 'smtp',
    'smtp_host'    => 'smtp-relay.brevo.com',
    'smtp_user'    => 'a32d5e001@smtp-brevo.com',
    'smtp_pass'    => getenv('MASS_ANNOUNCEMENT_SMTP_PASS') ?: 'xsmtpsib-043959ff5d7ea4c1bfe5cc8bcea6413eba2cb0f7e52ec530ac080b66bdf3f34a-fuce9z9PWCZrmGoU',
    'smtp_port'    => 587,
    'smtp_crypto'  => 'tls',
    'smtp_timeout' => 20,
    'mailtype'     => 'html',
    'charset'      => 'utf-8',
    'newline'      => "\r\n",
    'crlf'         => "\r\n",
    'wordwrap'     => true,
];

$config['mass_announcement_sender_email'] = getenv('MASS_ANNOUNCEMENT_SENDER_EMAIL') ?: 'clark.eksdi@gmail.com';
$config['mass_announcement_sender_name']  = getenv('MASS_ANNOUNCEMENT_SENDER_NAME') ?: 'Softtech Solution and Services Co.';

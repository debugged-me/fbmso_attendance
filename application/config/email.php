<?php defined('BASEPATH') or exit('No direct script access allowed');


$config['protocol']     = 'smtp';
// The mailbox lives on the cPanel server altar39.supremepanel39.com. Do NOT
// use mail.softtechservices.net here: that name is CNAME'd to Zoho (the
// domain's MX is delegated there), and Zoho rejects these credentials with
// 535. fbmso.srmsportal.com resolves to the cPanel server itself, where the
// mailbox actually authenticates.
$config['smtp_host']    = 'mail.softtechservices.net';
$config['smtp_user']    = 'attendance-fbmso@softtechservices.net';
$config['smtp_pass']    = getenv('FBMSO_SMTP_PASSWORD') ?: 'moth34board';
$config['smtp_port']    = 465;
$config['smtp_crypto']  = 'ssl';

$config['smtp_timeout'] = 10;
$config['mailtype']     = 'html';
$config['charset']      = 'utf-8';
$config['newline']      = "\r\n";
$config['crlf']         = "\r\n";
$config['wordwrap']     = true;

<?php defined('BASEPATH') or exit('No direct script access allowed');


$config['protocol']     = 'smtp';
// Host note: use softtechservices.net, NOT mail.softtechservices.net.
//
// cPanel's "Mail Client Manual Settings" panel always prints mail.<domain>,
// but that is a template, not a lookup - and this domain's mail was
// delegated to Zoho, so public DNS has:
//
//     mail.softtechservices.net -> 204.141.42.199  (Zoho, refuses these creds)
//     softtechservices.net      -> 198.23.58.128   (the cPanel server)
//
// The mailbox itself is local to the cPanel server, so connecting to the
// bare domain authenticates (235) while mail.<domain> times out - and a
// timeout trips the mail queue's cooldown, stalling every pending message.
//
// Same username, password, port and SSL as the panel gives you; only the
// hostname differs. If you would rather use mail.softtechservices.net, add
// an A record for `mail` pointing to 198.23.58.128 and this can go back.
//
// The portal (fbmso.softtechservices.net) runs on that same 198.23.58.128
// box, so if the host ever refuses to connect to its own public IP, set
// FBMSO_SMTP_HOST=localhost in the environment - no code change needed.
$config['smtp_host']    = getenv('FBMSO_SMTP_HOST') ?: 'softtechservices.net';
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

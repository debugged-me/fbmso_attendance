<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Risk scoring
| -------------------------------------------------------------------------
| Points added when a signal is present at sign-in. Everything here is meant
| to be tuned in production -- the starting numbers are a guess, and the only
| way to get them right is to watch real traffic and adjust.
|
| Set a rule to 0 to disable it.
*/
$config['risk_rules'] = array(

    // Device signals -----------------------------------------------------
    // A device nobody has seen on this account. Common and usually innocent
    // (new phone, cleared cookies), so on its own it must not be enough to
    // challenge anyone -- it needs to combine with something else.
    'unknown_device'          => 25,

    // Someone revoked this device and it came back. That is not routine.
    'revoked_device'          => 60,

    // The same browser has signed into other accounts. Two is a shared
    // family phone; ten in an afternoon is the 2026-08-28 pattern.
    'device_other_accounts_2' => 10,
    'device_other_accounts_5' => 35,

    // Authentication history ---------------------------------------------
    'failures_before_success' => 20,   // >=3 recent failures then a success
    'throttled_recently'      => 25,   // this account or IP was rate limited

    // Network -------------------------------------------------------------
    'new_ip_for_account'      => 10,   // never seen for this account before

    // Account value --------------------------------------------------------
    // A compromised administrator is worth far more than a compromised
    // student, so the same behaviour should score higher on one.
    'privileged_account'      => 25,
);

/*
| Roles treated as privileged for the rule above.
*/
$config['risk_privileged_levels'] = array(
    'Super Admin', 'Admin', 'IT', 'School Admin',
    'Registrar', 'Head Registrar', 'Accounting', 'HR Admin', 'Human Resource',
);

/*
| Score -> level. Lower bound of each band.
*/
$config['risk_levels'] = array(
    'LOW'      => 0,
    'MEDIUM'   => 30,
    'HIGH'     => 60,
    'CRITICAL' => 80,
);

/*
| What to do at each level.
|
|   notify_user  email the account holder that something happened
|   block        refuse the sign-in
|
| Blocking is deliberately off by default. A false positive locks a real
| student out of their own records, and until there is a way for them to
| prove who they are (MFA, a verified email challenge) blocking has no
| recovery path. Notification is the honest control at this stage: the
| account holder is the one person who knows whether a sign-in was theirs.
*/
$config['risk_actions'] = array(
    'LOW'      => array('notify_user' => false, 'block' => false),
    'MEDIUM'   => array('notify_user' => false, 'block' => false),
    'HIGH'     => array('notify_user' => true,  'block' => false),
    'CRITICAL' => array('notify_user' => true,  'block' => false),
);

/*
| Do not email the same account about sign-in risk more often than this
| (seconds). Someone with a flaky connection should not get 40 emails.
*/
$config['risk_notify_cooldown'] = 3600;

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| AuthGuard
| -------------------------------------------------------------------------
| Central authentication gate. A post_controller_constructor hook runs the
| guard on EVERY request, so a controller is protected whether or not its
| constructor remembers to check the session — including controllers added
| later. Anything not listed in $config['authguard_public'] requires a login.
|
| Route keys are matched case-insensitively as 'controller/method', with
| 'controller/*' meaning the whole controller.
*/

/*
| Where unauthenticated visitors are sent. The guard appends
| ?next=<original-url> so Login can bounce them back after signing in.
*/
$config['authguard_login_route'] = 'login';

/*
| PUBLIC ROUTES — reachable with no session at all.
|
| Keep this list as small as it can be. Everything here is, by definition,
| exposed to the internet.
*/
$config['authguard_public'] = array(

    // --- Sign-in / account recovery -------------------------------------
    'login/*',
    'verifyemail/*',

    // --- Student self-registration --------------------------------------
    // The public signup form plus the AJAX endpoints its dropdowns call.
    'registration/*',

    // --- Activity QR / attendance scanning ------------------------------
    // These MUST stay public or the activity scan flow breaks:
    //   activities/poster  -> printable QR poster carrying the check-in URL
    //   attendance/scan    -> the booth/kiosk scanner page
    //   attendance/consume -> POST endpoint the scanner posts tokens to
    //   attendance/checkin -> the link inside the student's QR code. It runs
    //                         its own auth + student-role check and redirects
    //                         to login with ?next=, so the guard stays out of
    //                         its way and lets it do that.
    'activities/poster',
    'attendance/scan',
    'attendance/consume',
    'attendance/checkin',

    // --- Mobile API (native Flutter app) --------------------------------
    // The entire /api/mobile/* namespace is bearer-token authenticated
    // inside each controller (MobileAuth, MobileAttendance, ...). It does
    // NOT use CI sessions, so it must bypass this session-based guard.
    // Public endpoints (config, login, forgot-password) are public by design;
    // every other endpoint rejects requests without a valid bearer token.
    'api/*',
);

/*
| ROLE RULES (optional) — 'controller/method' or 'controller/*' => allowed levels.
|
| Authentication is always enforced; this adds authorisation on top. Leave a
| route out and any logged-in user may reach it, which is the behaviour the
| app had before the guard existed. Add entries here as you tighten things,
| rather than sprinkling more level checks through the controllers.
|
| Example:
|   'admin/*'              => array('Super Admin'),
|   'page/enrollmentlist'  => array('Admin', 'School Admin'),
*/
$config['authguard_roles'] = array(
    'admin/*' => array('Super Admin', 'Admin', 'IT'),
);

/*
| Idle timeout in seconds. 0 disables it.
| 7200 = 2 hours of no requests before the session is dropped.
*/
$config['authguard_idle_timeout'] = 0;

/*
| Session keys the guard treats as proof of a valid login.
*/
$config['authguard_session_flag'] = 'logged_in';
$config['authguard_session_user'] = 'username';
$config['authguard_session_role'] = 'level';

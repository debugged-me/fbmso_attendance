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

    // --- Mail queue cron runner -----------------------------------------
    // EmailQueue/process authenticates with its own shared token
    // (hash_equals against fbmso_mailqueue_token) and is called by cron with
    // no session. EmailQueue/key is deliberately NOT public: it stays behind
    // the session gate so the token is only visible to signed-in staff.
    'emailqueue/process',

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
| STUDENT LOCKDOWN
| -------------------------------------------------------------------------
| Signing in is not the same as being allowed in. Accounts at these levels
| may reach ONLY the routes in $config['authguard_student_routes'] below;
| everything else answers 403 with views/errors/html/error_forbidden.php.
|
| This is deny-by-default and it is where the real protection lives: 2,406 of
| the 2,409 accounts on this system are students, so a student who types
| /Page/profileList into the address bar is the threat that matters.
*/
$config['authguard_student_levels'] = array(
    'Student',
    'Stude Applicant',
);

/*
| Routes a student account may use. Same matching rules as the public list:
| 'controller/method', or 'controller/*' for a whole controller.
|
| Derived from what students can actually reach: their dashboard's links, the
| Page methods that render a student view, and the methods that already
| checked `level === 'Student'` for themselves.
|
| Note what is NOT here and does not need to be: attendance/checkin,
| attendance/scan, attendance/consume and activities/poster are on the PUBLIC
| list above, so the guard never looks at them at all. Scanning keeps working
| for students and staff alike, signed in or not.
*/
$config['authguard_student_routes'] = array(

    // --- their dashboard and account ---------------------------------
    'page/student',
    'page/student_registration',
    'page/myprofile',
    'page/updatestudeprofile',
    'page/changepassword',
    'page/update_password',
    'page/changedp',
    'page/uploadprofpic',
    'page/studentsprofile',        // self-scoped: forces id = own username
    'page/studentprofile',         // student edits own profile data
    'page/updatestudentprofile',
    'page/studeenrollhistory',
    'login/logout',

    // --- attendance the student can see about themselves --------------
    'attendance/my_logs',
    'attendance/profile',
    'attendance/logs',
    'student/my_qr',
    'studentqr/myqr',

    // --- documents, requirements, accounts ----------------------------
    'page/studentrequeststat',
    'page/submitrequest',
    'page/newrequest',
    'page/uploadrequirements',
    'page/uploadedrequirements',
    'page/studentaccountingrecords',
    'page/studeaccount',
    'page/proof_payment',
    'student/upload_requirement',
    'student/submit_requirement',
    'student/student_requirements',
    'student/student_requirements_app',
    'student/downloads',

    // --- academics -----------------------------------------------------
    'student/enlistment',
    'student/enlistment_student',
    'student/fetchsubjects',
    'student/registersubject',
    'student/removesubject',
    'student/checkenrollmentstatus',
    'student/viewenrolledsubjects',
    'student/viewenrolledsubjectsstude',
    'student/getavailablesubjectsgrouped',
    'student/fetchsubjectsformodal',
    'student/requestsub',
    'student/print_grades',
    'student/email_grades',
    'student/evaluation',
    'student/showevaluation',
    'student/evaluationph',
    'student/index',
    'masterlist/cor',
    'masterlist/studegradesview',

    // --- shared, harmless ----------------------------------------------
    'page/announcement',
    'fbmsopersonnels',
    'fbmsopersonnels/*',
    'registration/getmajorsbycourse',
    'registration/getcitiesbyprovince',
    'registration/getbarangaysbycity',
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

<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['default_controller'] = 'login';
$route['login']              = 'login';
$route['login/auth']         = 'login/auth';
$route['login/check_reset_email'] = 'login/check_reset_email';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['upload-image'] = 'Page';
$route['store-image'] = 'Page/upload';
$route['page/student'] = 'Page/student';
$route['verify-email'] = 'VerifyEmail/index';
$route['Registration/getMajorsByCourse'] = 'Registration/getMajorsByCourse';
$route['Registration/getCitiesByProvince'] = 'Registration/getCitiesByProvince';
$route['Registration/getBarangaysByCity'] = 'Registration/getBarangaysByCity';
$route['Registration/checkAvailability'] = 'Registration/checkAvailability';
$route['Messaging/send'] = 'Messaging/send';
$route['Messaging/get_conversation'] = 'Messaging/get_conversation';
$route['mass-announcement'] = 'MassAnnouncement/index';
$route['mass-announcement/send'] = 'MassAnnouncement/send';
$route['mass-announcement/sections'] = 'MassAnnouncement/sections';
$route['mass-announcement/students'] = 'MassAnnouncement/students';
$route['mass-announcement/settings'] = 'MassAnnouncement/settings';
$route['dashboard_student'] = 'Announcement/getActiveForStudent';
$route['Page/verifyOnlinePayment/(:num)'] = 'Page/verifyOnlinePayment/$1';
$route['student/soa'] = 'Page/soa_college';
$route['accreditation/ajax/students']    = 'accreditation/ajax_students';
$route['accreditation/ajax/prevschools'] = 'accreditation/ajax_prevschools';
$route['accreditation/ajax/subjects']    = 'accreditation/ajax_subjects';
$route['accreditation/ajax/subjectinfo'] = 'accreditation/ajax_subject_info';
$route['page/get_enrollment_row'] = 'page/get_enrollment_row';
$route['page/update_enrollment']  = 'page/update_enrollment';
$route['faculty-load']      = 'Page/facultyLoadPage';
$route['enrolled-students'] = 'Page/enrolledStudentsPage';
$route['Instructor/saveAttendanceOneAjax'] = 'Instructor/saveAttendanceOneAjax';
$route['request/ajax/docs-by-office'] = 'request/ajax_docs_by_office';
$route['request/ajax/prior-request']  = 'request/ajax_prior_request';
$route['request']                    = 'Request/index';
$route['request/ajax_pending_list']  = 'Request/ajax_pending_list';
$route['request/ajax_pending_count'] = 'Request/ajax_pending_count';
$route['request/ajax_mark_seen']     = 'Request/ajax_mark_seen';
$route['GradesLock']        = 'GradesLock/index';
$route['GradesLock/upsert'] = 'GradesLock/upsert';
$route['GradesLock/edit/(:num)']   = 'GradesLock/edit/$1';
$route['GradesLock/delete/(:num)'] = 'GradesLock/delete/$1';
$route['activities']                 = 'activities/index';
$route['activities/create']          = 'activities/create';
$route['activities/(:num)/poster']   = 'activities/poster/$1';
$route['activities/(:num)/edit']     = 'activities/edit/$1';
$route['activities/(:num)/update']   = 'activities/update/$1';
$route['activities/(:num)/delete']   = 'activities/delete/$1';
$route['activities/majors']          = 'activities/majors_by_program';
$route['activities/fill-missing']    = 'activities/fill_missing';
$route['activities/(:num)/scan']     = 'attendance/scan/$1';
$route['attendance/checkin/(:num)']  = 'attendance/checkin/$1';
$route['attendance/consume']         = 'attendance/consume';
$route['attendance/logs/(:num)']     = 'attendance/logs/$1';
$route['attendance/my_logs']         = 'attendance/my_logs';
$route['activities/mode/(:any)']     = 'activities/set_mode/$1';

$route['activities/majors'] = 'activities/majors_by_program';
$route['AttendanceLogs']                     = 'AttendanceLogs/index';
$route['AttendanceLogs/activity/(:num)']     = 'AttendanceLogs/activity/$1';
$route['AttendanceLogs/export_csv/(:num)']   = 'AttendanceLogs/export_csv/$1';
$route['Page/editSignup/(:any)'] = 'Page/editSignup/$1';
$route['reports'] = 'Reports/index';
$route['fbmso-personnels']        = 'FbmsoPersonnels/index';
$route['admin/fbmso-personnels']  = 'FbmsoPersonnels/manage';

// ──────────────────────────────────────────────────────────────────────────
//  Mobile API (native Flutter app) — Bearer-token authenticated JSON.
//  Reuses Login_model::validate() so web credentials work on mobile.
// ──────────────────────────────────────────────────────────────────────────
$route['api/mobile/config']                       = 'api/MobileAuth/config';
$route['api/mobile/auth/login']                   = 'api/MobileAuth/login';
$route['api/mobile/auth/me']                      = 'api/MobileAuth/me';
$route['api/mobile/auth/logout']                  = 'api/MobileAuth/logout';
$route['api/mobile/auth/change-password']         = 'api/MobileAuth/change_password';
$route['api/mobile/auth/forgot-password']         = 'api/MobileAuth/forgot_password';

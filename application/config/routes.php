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
$route['api/mobile/registration/options']         = 'api/MobileAuth/registration_options';
$route['api/mobile/registration/sections']        = 'api/MobileAuth/registration_sections';
$route['api/mobile/registration/check-availability'] = 'api/MobileAuth/registration_check_availability';
$route['api/mobile/auth/login']                   = 'api/MobileAuth/login';
$route['api/mobile/auth/me']                      = 'api/MobileAuth/me';
$route['api/mobile/auth/logout']                  = 'api/MobileAuth/logout';
$route['api/mobile/auth/change-password']         = 'api/MobileAuth/change_password';
$route['api/mobile/auth/change-avatar']           = 'api/MobileAuth/change_avatar';
$route['api/mobile/auth/avatar']                  = 'api/MobileAuth/avatar';
$route['api/mobile/auth/forgot-password']         = 'api/MobileAuth/forgot_password';
$route['api/mobile/auth/forgot-password/manual']  = 'api/MobileAuth/forgot_password_manual';
$route['api/mobile/auth/register']                = 'api/MobileAuth/register';

// Mobile attendance + activities
$route['api/mobile/activities']                   = 'api/MobileAttendance/activities';
$route['api/mobile/activities/(:num)']            = 'api/MobileAttendance/activity/$1';
$route['api/mobile/activities/create']            = 'api/MobileAttendance/create_activity';
$route['api/mobile/activities/update/(:num)']     = 'api/MobileAttendance/update_activity/$1';
$route['api/mobile/activities/delete/(:num)']     = 'api/MobileAttendance/delete_activity/$1';
$route['api/mobile/attendance/consume']           = 'api/MobileAttendance/consume';
$route['api/mobile/attendance/checkin/(:num)']    = 'api/MobileAttendance/checkin/$1';
$route['api/mobile/attendance/my_logs']           = 'api/MobileAttendance/my_logs';
$route['api/mobile/attendance/logs/(:num)']       = 'api/MobileAttendance/logs/$1';

// Mobile student module
$route['api/mobile/student/profile']              = 'api/MobileStudent/profile';
$route['api/mobile/student/my_qr']                = 'api/MobileStudent/my_qr';
$route['api/mobile/student/my_qr/issue']          = 'api/MobileStudent/issue_qr';
$route['api/mobile/student/my_qr/revoke']         = 'api/MobileStudent/revoke_qr';
$route['api/mobile/student/requirements']         = 'api/MobileStudent/requirements';
$route['api/mobile/student/requirements/upload']  = 'api/MobileStudent/upload_requirement';
$route['api/mobile/student/grades']               = 'api/MobileStudent/grades';
$route['api/mobile/student/enrolled_subjects']    = 'api/MobileStudent/enrolled_subjects';
$route['api/mobile/student/payments']              = 'api/MobileMisc/student_payments';
$route['api/mobile/student/edit-profile']          = 'api/MobileMisc/student_edit_profile';
$route['api/mobile/student/update-profile']        = 'api/MobileMisc/student_update_profile';

// Mobile misc: announcements, notes, todos, personnel, masterlist, accounting
$route['api/mobile/announcements']                = 'api/MobileMisc/announcements';
$route['api/mobile/notes']                        = 'api/MobileMisc/notes';
$route['api/mobile/notes/create']                 = 'api/MobileMisc/notes_create';
$route['api/mobile/notes/update/(:num)']          = 'api/MobileMisc/notes_update/$1';
$route['api/mobile/notes/delete/(:num)']          = 'api/MobileMisc/notes_delete/$1';
$route['api/mobile/todos']                        = 'api/MobileMisc/todos';
$route['api/mobile/todos/create']                 = 'api/MobileMisc/todos_create';
$route['api/mobile/todos/toggle/(:num)']          = 'api/MobileMisc/todos_toggle/$1';
$route['api/mobile/todos/delete/(:num)']          = 'api/MobileMisc/todos_delete/$1';
$route['api/mobile/personnel']                    = 'api/MobileMisc/personnel';
$route['api/mobile/personnel/all']                = 'api/MobileMisc/personnel_all';
$route['api/mobile/personnel/save']               = 'api/MobileMisc/personnel_save';
$route['api/mobile/personnel/delete']             = 'api/MobileMisc/personnel_delete';
$route['api/mobile/personnel/toggle']             = 'api/MobileMisc/personnel_toggle';
$route['api/mobile/masterlist/enrolled']          = 'api/MobileMisc/masterlist_enrolled';
$route['api/mobile/accounting/expenses']              = 'api/MobileMisc/accounting_expenses';
$route['api/mobile/accounting/expenses/create']       = 'api/MobileMisc/expenses_create';
$route['api/mobile/accounting/expenses/update']       = 'api/MobileMisc/expenses_update';
$route['api/mobile/accounting/expenses/delete']       = 'api/MobileMisc/expenses_delete';
$route['api/mobile/accounting/expenses/categories']   = 'api/MobileMisc/expenses_categories';
$route['api/mobile/accounting/expenses/categories/create']  = 'api/MobileMisc/expenses_categories_create';
$route['api/mobile/accounting/expenses/categories/delete']  = 'api/MobileMisc/expenses_categories_delete';
$route['api/mobile/users']                        = 'api/MobileMisc/user_accounts';
$route['api/mobile/users/create']                 = 'api/MobileMisc/user_accounts_create';
$route['api/mobile/users/delete']                 = 'api/MobileMisc/user_accounts_delete';
$route['api/mobile/registered-students']          = 'api/MobileMisc/registered_students';
$route['api/mobile/registered-students/delete']   = 'api/MobileMisc/registered_students_delete';

// Departments / Courses (Settings/Department)
$route['api/mobile/departments']                  = 'api/MobileMisc/departments';
$route['api/mobile/departments/create']           = 'api/MobileMisc/departments_create';
$route['api/mobile/departments/update']           = 'api/MobileMisc/departments_update';
$route['api/mobile/departments/delete']           = 'api/MobileMisc/departments_delete';

// Sections (Page/manageSections)
$route['api/mobile/sections']                     = 'api/MobileMisc/sections';
$route['api/mobile/sections/create']              = 'api/MobileMisc/sections_create';
$route['api/mobile/sections/delete']              = 'api/MobileMisc/sections_delete';

// Announcements CRUD (admin)
$route['api/mobile/announcements/all']            = 'api/MobileMisc/announcements_all';
$route['api/mobile/announcements/create']         = 'api/MobileMisc/announcements_create';
$route['api/mobile/announcements/update']         = 'api/MobileMisc/announcements_update';
$route['api/mobile/announcements/delete']         = 'api/MobileMisc/announcements_delete';

// Reports
$route['api/mobile/reports/summary']              = 'api/MobileMisc/reports_summary';
$route['api/mobile/reports/attendance']           = 'api/MobileMisc/reports_attendance';

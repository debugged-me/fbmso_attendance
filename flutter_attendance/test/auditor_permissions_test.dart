import 'package:flutter_test/flutter_test.dart';

import 'package:flutter_attendance/features/auth/domain/app_session.dart';
import 'package:flutter_attendance/features/auth/domain/staff_permissions.dart';
import 'package:flutter_attendance/features/auth/domain/user_role.dart';

AppSession _session(String position) => AppSession(
      baseUrl: 'https://school.test',
      token: 'token',
      schoolName: 'School',
      username: 'user',
      idNumber: '1',
      firstName: 'Test',
      middleName: '',
      lastName: 'User',
      email: 'user@school.test',
      avatar: '',
      position: position,
      activeSy: '2026-2027',
      activeSem: '1st Semester',
    );

void main() {
  test('Auditor is parsed as a dedicated role', () {
    expect(UserRole.fromPosition(' Auditor '), UserRole.auditor);
    expect(_session('Auditor').role.label, 'Auditor');
    expect(_session('Auditor').role.isAdminLike, isTrue);
  });

  test('Auditor receives its read-only mobile permission set', () {
    final permissions = StaffPermissions.of(_session('Auditor'));

    expect(permissions.isAuditor, isTrue);
    expect(permissions.canViewActivities, isTrue);
    // Poster link only while poster mode is on (activities_list.php).
    expect(permissions.canShowActivityPoster(posterMode: true), isTrue);
    expect(permissions.canShowActivityPoster(posterMode: false), isFalse);
    expect(permissions.canViewAttendanceLogs, isTrue);
    expect(permissions.canViewAccounting, isTrue);
    expect(permissions.canViewAccountingRecords, isTrue);
    expect(permissions.canViewStaffLists, isTrue);
    expect(permissions.canViewDepartments, isTrue);
    expect(permissions.canCreateDepartments, isTrue);
    expect(permissions.canCreateSections, isTrue);

    expect(permissions.canScan, isFalse);
    expect(permissions.canManageActivities, isFalse);
    expect(permissions.canManageAccounting, isFalse);
    expect(permissions.canManageExpenses, isFalse);
    expect(permissions.canModifyDepartments, isFalse);
    expect(permissions.canDeleteSections, isFalse);
    expect(permissions.canManageUsers, isFalse);
    expect(permissions.canManageAnnouncements, isFalse);
  });

  test('Only the Cashier manages expenses; Admin views them', () {
    final cashier = StaffPermissions.of(_session('Cashier'));
    final admin = StaffPermissions.of(_session('Admin'));

    expect(cashier.canManageExpenses, isTrue);
    expect(cashier.canManageAccounting, isTrue);

    expect(admin.canViewAccounting, isTrue);
    expect(admin.canManageExpenses, isFalse);
  });

  // The rest mirror the web sidebar (views/includes/sidebar.php) and the
  // activities list (views/activities_list.php) role by role.

  test('Admin: no payment entry or Cashier-only accounting pages', () {
    final admin = StaffPermissions.of(_session('Admin'));

    // Web Admin sidebar: School Expenses, Payment Activity Log, Financial
    // Statements — no Payment Entry, Fees Setup, Collection, Ledger, Partial.
    expect(admin.canViewAccounting, isTrue);
    expect(admin.canViewAccountingRecords, isFalse);
    expect(admin.canManageAccounting, isFalse);

    expect(admin.canTogglePosterMode, isTrue);
    expect(admin.canQuickToggleActivityStatus, isTrue);
    expect(admin.canManageActivities, isTrue);
    expect(admin.canScanActivity(posterMode: false), isTrue);
    expect(admin.canScanActivity(posterMode: true), isFalse);
    expect(admin.canShowActivityPoster(posterMode: true), isTrue);
    expect(admin.canDeleteSections, isTrue);
  });

  test('Cashier: accounting only', () {
    final cashier = StaffPermissions.of(_session('Cashier'));

    expect(cashier.canViewAccountingRecords, isTrue);
    expect(cashier.canViewActivities, isFalse);
    expect(cashier.canScan, isFalse);
    expect(cashier.canViewAttendanceLogs, isFalse);
    expect(cashier.canViewStaffLists, isFalse);
    expect(cashier.canShowActivityPoster(posterMode: true), isFalse);
  });

  test('Committee: scans even in poster mode, never gets the poster', () {
    final committee = StaffPermissions.of(_session('Committee'));

    expect(committee.canScanActivity(posterMode: false), isTrue);
    expect(committee.canScanActivity(posterMode: true), isTrue);
    expect(committee.canShowActivityPoster(posterMode: true), isFalse);
    expect(committee.canViewAttendanceLogs, isTrue);
    expect(committee.canManageActivities, isFalse);
    expect(committee.canTogglePosterMode, isFalse);
    expect(committee.canViewAccounting, isFalse);
    expect(committee.canViewStaffLists, isFalse);
  });

  test('Other staff: no poster-mode switch, quick status, or section delete', () {
    final it = StaffPermissions.of(_session('IT'));
    final registrar = StaffPermissions.of(_session('Registrar'));

    expect(it.canTogglePosterMode, isFalse);
    expect(it.canQuickToggleActivityStatus, isFalse);
    expect(registrar.canQuickToggleActivityStatus, isTrue);
    expect(registrar.canDeleteSections, isFalse);
  });
}

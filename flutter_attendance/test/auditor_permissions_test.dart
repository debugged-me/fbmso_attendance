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
    expect(permissions.canViewActivityPoster, isTrue);
    expect(permissions.canViewAttendanceLogs, isTrue);
    expect(permissions.canViewAccounting, isTrue);
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
    expect(admin.canManageAccounting, isTrue);
    expect(admin.canManageExpenses, isFalse);
  });
}

import 'app_session.dart';

/// Feature-level permissions for staff accounts, mirroring the web app's
/// authorization model so the mobile UI never shows a module the API
/// would reject with 403.
///
/// Web references (server side):
///  - authguard_restricted_role_routes: Committee gets activities/index,
///    attendance/scan|consume|logs|profile, attendancelogs/*;
///    Cashier gets accounting/* and the payment pages. Both roles are
///    deny-by-default for everything else.
///  - Accounting::$allowedLevels = ['Admin', 'Cashier'].
///  - FbmsoPersonnels::require_manager = Super Admin, Admin, IT,
///    HR Admin, Human Resource.
///  - authguard_roles page/useraccounts + account mutations =
///    Super Admin, Admin, IT.
///  - authguard_roles settings/* (departments) =
///    Super Admin, Admin, IT, School Admin.
///  - Activities::set_mode (poster mode) = Admin only.
///  - attendance/scan and attendance/consume are PUBLIC web routes,
///    so every non-student role may operate the scanner.
///  - attendancelogs/* = every unrestricted staff role + Committee
///    (Cashier is denied).
///  - Anything else without a rule = any unrestricted staff position.
class StaffPermissions {
  const StaffPermissions._(this.position);

  /// The user's `o_users.position`, lowercased and trimmed.
  final String position;

  factory StaffPermissions.of(AppSession session) =>
      StaffPermissions._(session.position.trim().toLowerCase());

  // ─── role buckets ──────────────────────────────────────────────────

  bool get _isStudentLike => const {
        'student',
        'student applicant',
        'stude',
        'stude applicant',
      }.contains(position);

  /// Student / Stude Applicant — the roles Page::student serves.
  bool get isStudent => _isStudentLike;

  bool get isCommittee => position == 'committee';
  bool get isCashier => position == 'cashier';

  /// Committee and Cashier are allowlisted roles on the web: they may only
  /// reach the routes listed for them, nothing else.
  bool get _isRestricted => isCommittee || isCashier;

  /// Any unrestricted staff position (not a student, not Committee/Cashier).
  bool get _isStaff => !_isStudentLike && !_isRestricted && position.isNotEmpty;

  // ─── attendance ────────────────────────────────────────────────────

  /// QR scanner — public routes on the web, so every non-student may scan.
  bool get canScan => !_isStudentLike && position.isNotEmpty;

  /// Attendance logs (list + CSV export) — staff and Committee, not Cashier.
  bool get canViewAttendanceLogs => _isStaff || isCommittee;

  /// Create/edit/delete activities — unrestricted staff only.
  bool get canManageActivities => _isStaff;

  // ─── modules ───────────────────────────────────────────────────────

  /// Expenses + categories — web Accounting allows Admin and Cashier only.
  bool get canUseAccounting =>
      position == 'admin' || position == 'cashier';

  /// Personnel management — Super Admin, Admin, IT, HR Admin, Human Resource.
  bool get canManagePersonnel => const {
        'super admin',
        'admin',
        'it',
        'hr admin',
        'human resource',
      }.contains(position);

  /// User accounts — Super Admin, Admin, IT.
  bool get canManageUsers =>
      const {'super admin', 'admin', 'it'}.contains(position);

  /// Departments/courses — web settings/* rule.
  bool get canManageDepartments =>
      const {'super admin', 'admin', 'it', 'school admin'}.contains(position);

  /// Read-only staff lists: registered students, masterlist, sections,
  /// reports — open to unrestricted staff, closed to Committee/Cashier.
  bool get canViewStaffLists => _isStaff;

  /// Announcement management — unrestricted staff on the web.
  bool get canManageAnnouncements => _isStaff;

  /// Enrollment dashboard stats — Page/admin is Admin-only on the web and
  /// Page/school_admin shows the same panels to School Admin. Super Admin
  /// keeps a stats dashboard too.
  bool get canViewDashboardStats =>
      const {'admin', 'super admin', 'school admin'}.contains(position);
}

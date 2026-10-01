import 'app_session.dart';

/// Feature-level permissions for staff accounts, mirroring the web app's
/// authorization model so the mobile UI never shows a module the API
/// would reject with 403.
///
/// Web references (server side):
///  - authguard_restricted_role_routes: Committee gets activities/index,
///    attendance/scan|consume|logs|profile, attendancelogs/*;
///    Cashier gets accounting/*; Auditor gets read-only accounting plus
///    student, attendance, report, course, and section views. All are
///    deny-by-default for everything else.
///  - Accounting allows Admin, Cashier, and read-only Auditor accounts,
///    but the sidebar decides what each one actually gets: Admin's menu has
///    only School Expenses, Payment Activity Log and Financial Statements;
///    Payment Entry, Fees Setup, Collection Reports, Ledger and Partial
///    Payments appear for Cashier (and read-only for Auditor) only.
///    Expenses and expense categories are Cashier-only to change; Admin
///    and Auditor may view them (Accounting::canManageExpenses).
///  - FbmsoPersonnels::require_manager = Super Admin, Admin, IT,
///    HR Admin, Human Resource.
///  - authguard_roles page/useraccounts + account mutations =
///    Super Admin, Admin, IT.
///  - authguard_roles settings/* (departments) =
///    Super Admin, Admin, IT, School Admin.
///  - Activities::set_mode (poster mode) = Admin only.
///  - Activities::set_status (the list's open/close button) = Admin,
///    Instructor, Registrar.
///  - activities_list.php: Scan is hidden in poster mode except for
///    Committee; the poster link shows only in poster mode and never to
///    Committee; Auditor never scans.
///  - Section delete: Page/deleteSection (Super Admin, Admin, IT) and
///    Settings/deleteSection (adds School Admin).
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
  bool get isAuditor => position == 'auditor';

  /// These are allowlisted roles on the web: they may only
  /// reach the routes listed for them, nothing else.
  bool get _isRestricted => isCommittee || isCashier || isAuditor;

  /// Any unrestricted staff position (not a student or restricted role).
  bool get _isStaff => !_isStudentLike && !_isRestricted && position.isNotEmpty;

  // ─── attendance ────────────────────────────────────────────────────

  /// Auditor is observational only; it can inspect activities but not scan.
  bool get canScan =>
      !_isStudentLike && !isCashier && !isAuditor && position.isNotEmpty;

  /// Attendance logs (list + CSV export) — staff, Committee, and Auditor.
  bool get canViewAttendanceLogs => _isStaff || isCommittee || isAuditor;

  /// Activity list access. Cashier is the only staff shell without it.
  bool get canViewActivities => !_isStudentLike && !isCashier;

  /// The Scan action on an activity. activities_list.php hides it while
  /// poster mode is on, except for Committee, whose job is scanning.
  bool canScanActivity({required bool posterMode}) =>
      canScan && (!posterMode || isCommittee);

  /// The poster link on an activity: only while poster mode is on, and
  /// never for Committee (activities_list.php).
  bool canShowActivityPoster({required bool posterMode}) =>
      posterMode && canViewActivities && !isCommittee;

  /// The poster-mode switch — the web sidebar shows it to Admin only and
  /// Activities::set_mode rejects everyone else.
  bool get canTogglePosterMode => position == 'admin';

  /// The quick open/close button on the activity list. Activities::set_status
  /// accepts only these three; other staff change status through Edit.
  bool get canQuickToggleActivityStatus =>
      const {'admin', 'instructor', 'registrar'}.contains(position);

  /// Create/edit/delete activities — unrestricted staff only.
  bool get canManageActivities => _isStaff;

  // ─── modules ───────────────────────────────────────────────────────

  /// School expenses, expense reports and the payment activity log — the
  /// accounting items on the Admin, Cashier and Auditor sidebars alike.
  bool get canViewAccounting => position == 'admin' || isCashier || isAuditor;

  /// Payment entry/records, fees setup, collection reports, ledger and
  /// partial payments: on the Cashier and Auditor sidebars, not Admin's.
  bool get canViewAccountingRecords => isCashier || isAuditor;

  /// Recording payments and editing fees — the Cashier's Payment Entry and
  /// Fees Setup. Auditor's copies are read-only; Admin has neither.
  bool get canManageAccounting => isCashier;

  /// Expenses are the Cashier's to manage; Admin and Auditor view only.
  bool get canManageExpenses => isCashier;

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
  bool get canViewDepartments =>
      const {'super admin', 'admin', 'it', 'school admin', 'auditor'}
          .contains(position);

  /// Web parity: Auditor may add courses but cannot edit/delete them.
  bool get canCreateDepartments => canViewDepartments;
  bool get canModifyDepartments =>
      const {'super admin', 'admin', 'it', 'school admin'}.contains(position);

  /// Sections are available to unrestricted staff and Auditor. Auditor may
  /// add them but cannot delete existing records; deleting is limited to
  /// the roles the web's two delete routes accept.
  bool get canCreateSections => _isStaff || isAuditor;
  bool get canDeleteSections =>
      const {'super admin', 'admin', 'it', 'school admin'}.contains(position);

  /// Read-only staff lists: registered students, masterlist, sections,
  /// reports — open to unrestricted staff, closed to Committee/Cashier.
  bool get canViewStaffLists => _isStaff || isAuditor;

  /// Announcement management — unrestricted staff on the web.
  bool get canManageAnnouncements => _isStaff;

  /// Enrollment dashboard stats — Page/admin is Admin-only on the web and
  /// Page/school_admin shows the same panels to School Admin. Super Admin
  /// keeps a stats dashboard too.
  bool get canViewDashboardStats =>
      const {'admin', 'super admin', 'school admin'}.contains(position);
}

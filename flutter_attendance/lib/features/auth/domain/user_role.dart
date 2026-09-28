/// Logical role derived from the `position` field returned by the API.
///
/// Auditor is kept as its own role because it receives an audit-focused staff
/// experience. Other staff positions continue to use the admin shell bucket.
enum UserRole {
  student,
  auditor,
  admin,
  unknown;

  /// Map a web `position` string to a mobile role bucket.
  /// Matching is case-insensitive and trimmed.
  static UserRole fromPosition(String position) {
    final p = position.trim().toLowerCase();
    if (p.isEmpty) return UserRole.unknown;

    if (p == 'student' || p == 'stude applicant') return UserRole.student;
    if (p == 'auditor') return UserRole.auditor;

    // Admin, Super Admin, and other staff positions → Admin.
    return UserRole.admin;
  }

  /// Whether this role should land on the student shell.
  bool get isStudentLike => this == UserRole.student;

  /// Whether this role gets the permission-aware staff shell.
  bool get isAdminLike =>
      this == UserRole.admin || this == UserRole.auditor || this == UserRole.unknown;

  /// Human-readable label for debug / UI.
  String get label {
    switch (this) {
      case UserRole.student:
        return 'Student';
      case UserRole.auditor:
        return 'Auditor';
      case UserRole.admin:
        return 'Admin';
      case UserRole.unknown:
        return 'Admin';
    }
  }
}

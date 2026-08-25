/// Logical role derived from the `position` field returned by the API.
///
/// The FBMSO system has only three positions: Student, Admin, and Super Admin.
/// This enum groups them into the two shell buckets the mobile app renders.
enum UserRole {
  student,
  admin,
  unknown;

  /// Map a web `position` string to a mobile role bucket.
  /// Matching is case-insensitive and trimmed.
  static UserRole fromPosition(String position) {
    final p = position.trim().toLowerCase();
    if (p.isEmpty) return UserRole.unknown;

    if (p == 'student' || p == 'stude applicant') return UserRole.student;

    // Admin, Super Admin, and any other non-student position → Admin.
    return UserRole.admin;
  }

  /// Whether this role should land on the student shell.
  bool get isStudentLike => this == UserRole.student;

  /// Whether this role gets the admin shell (manage activities, scan,
  /// attendance logs, personnel, masterlist, accounting, reports, etc.).
  bool get isAdminLike => this == UserRole.admin || this == UserRole.unknown;

  /// Human-readable label for debug / UI.
  String get label {
    switch (this) {
      case UserRole.student:
        return 'Student';
      case UserRole.admin:
        return 'Admin';
      case UserRole.unknown:
        return 'Admin';
    }
  }
}

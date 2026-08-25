/// Logical role derived from the `position` field returned by the API.
///
/// The FBMSO web app has ~18 position strings (Student, Instructor, Admin,
/// Registrar, Super Admin, Accounting, ...). This enum groups them into the
/// shell buckets the mobile app renders. The mapping lives in
/// [UserRole.fromPosition].
enum UserRole {
  student,
  applicant,
  instructor,
  admin,
  superAdmin,
  registrar,
  accounting,
  hr,
  guidance,
  medical,
  librarian,
  custodian,
  encoder,
  it,
  academicOfficer,
  staff,
  unknown;

  /// Map a web `position` string to a mobile role bucket.
  /// Matching is case-insensitive and trimmed.
  static UserRole fromPosition(String position) {
    final p = position.trim().toLowerCase();
    if (p.isEmpty) return UserRole.unknown;

    if (p == 'student') return UserRole.student;
    if (p == 'stude applicant') return UserRole.applicant;

    if (p == 'instructor') return UserRole.instructor;

    if (p == 'super admin') return UserRole.superAdmin;
    if (p == 'admin' || p == 'school admin') return UserRole.admin;
    if (p == 'registrar' || p == 'head registrar') return UserRole.registrar;
    if (p == 'accounting') return UserRole.accounting;
    if (p == 'hr admin' || p == 'human resource') return UserRole.hr;
    if (p == 'guidance') return UserRole.guidance;
    if (p == 'school nurse') return UserRole.medical;
    if (p == 'librarian') return UserRole.librarian;
    if (p == 'property custodian') return UserRole.custodian;
    if (p == 'encoder') return UserRole.encoder;
    if (p == 'it') return UserRole.it;
    if (p == 'academic officer') return UserRole.academicOfficer;

    return UserRole.staff;
  }

  /// Whether this role should land on the student shell (QR, activities).
  bool get isStudentLike =>
      this == UserRole.student || this == UserRole.applicant;

  /// Whether this role runs the scan/consume attendance flow.
  bool get isInstructorLike => this == UserRole.instructor;

  /// Whether this role gets the admin shell (masterlist, reports, personnel).
  bool get isAdminLike =>
      this == UserRole.admin ||
      this == UserRole.superAdmin ||
      this == UserRole.registrar ||
      this == UserRole.it ||
      this == UserRole.academicOfficer;

  /// Whether this role gets the accounting shell.
  bool get isAccountingLike => this == UserRole.accounting;

  /// Human-readable label for debug / UI.
  String get label {
    switch (this) {
      case UserRole.student:
        return 'Student';
      case UserRole.applicant:
        return 'Applicant';
      case UserRole.instructor:
        return 'Instructor';
      case UserRole.admin:
        return 'Admin';
      case UserRole.superAdmin:
        return 'Super Admin';
      case UserRole.registrar:
        return 'Registrar';
      case UserRole.accounting:
        return 'Accounting';
      case UserRole.hr:
        return 'HR';
      case UserRole.guidance:
        return 'Guidance';
      case UserRole.medical:
        return 'Medical';
      case UserRole.librarian:
        return 'Librarian';
      case UserRole.custodian:
        return 'Property Custodian';
      case UserRole.encoder:
        return 'Encoder';
      case UserRole.it:
        return 'IT';
      case UserRole.academicOfficer:
        return 'Academic Officer';
      case UserRole.staff:
        return 'Staff';
      case UserRole.unknown:
        return 'Unknown';
    }
  }
}

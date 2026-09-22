/// Student profile from `GET /api/mobile/student/profile`.
class StudentProfile {
  const StudentProfile({
    required this.studentNumber,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.fullName,
    required this.nameExtn,
    required this.sex,
    required this.birthDate,
    required this.email,
    required this.contactNo,
    required this.civilStatus,
    required this.ethnicity,
    required this.religion,
    required this.province,
    required this.city,
    required this.barangay,
    required this.sitio,
    required this.course,
    required this.major,
    required this.status,
    required this.enrollmentDate,
  });

  final String studentNumber;
  final String firstName;
  final String middleName;
  final String lastName;
  final String fullName;
  final String nameExtn;
  final String sex;
  final String birthDate;
  final String email;
  final String contactNo;
  final String civilStatus;
  final String ethnicity;
  final String religion;
  final String province;
  final String city;
  final String barangay;
  final String sitio;
  final String course;
  final String major;
  final String status;
  final String enrollmentDate;

  factory StudentProfile.fromJson(Map<String, dynamic> j) => StudentProfile(
        studentNumber: (j['student_number'] ?? '').toString(),
        firstName: (j['first_name'] ?? '').toString(),
        middleName: (j['middle_name'] ?? '').toString(),
        lastName: (j['last_name'] ?? '').toString(),
        fullName: (j['full_name'] ?? '').toString(),
        nameExtn: (j['name_extn'] ?? '').toString(),
        sex: (j['sex'] ?? '').toString(),
        birthDate: (j['birth_date'] ?? '').toString(),
        email: (j['email'] ?? '').toString(),
        contactNo: (j['contact_no'] ?? '').toString(),
        civilStatus: (j['civil_status'] ?? '').toString(),
        ethnicity: (j['ethnicity'] ?? '').toString(),
        religion: (j['religion'] ?? '').toString(),
        province: (j['province'] ?? '').toString(),
        city: (j['city'] ?? '').toString(),
        barangay: (j['barangay'] ?? '').toString(),
        sitio: (j['sitio'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        enrollmentDate: (j['enrollment_date'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'student_number': studentNumber,
        'first_name': firstName,
        'middle_name': middleName,
        'last_name': lastName,
        'full_name': fullName,
        'name_extn': nameExtn,
        'sex': sex,
        'birth_date': birthDate,
        'email': email,
        'contact_no': contactNo,
        'civil_status': civilStatus,
        'ethnicity': ethnicity,
        'religion': religion,
        'province': province,
        'city': city,
        'barangay': barangay,
        'sitio': sitio,
        'course': course,
        'major': major,
        'status': status,
        'enrollment_date': enrollmentDate,
      };
}

/// Student's QR token from `GET /api/mobile/student/my_qr`.
class StudentQr {
  const StudentQr({
    required this.studentNumber,
    required this.token,
    required this.status,
    required this.issuedAt,
  });

  final String studentNumber;
  final String token;
  final String status;
  final String issuedAt;

  bool get isActive => status.toLowerCase() == 'active';

  factory StudentQr.fromJson(Map<String, dynamic> j) => StudentQr(
        studentNumber: (j['student_number'] ?? '').toString(),
        token: (j['token'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        issuedAt: (j['issued_at'] ?? '').toString(),
      );
}

/// Flagged-account state from `GET /api/mobile/student/status` — mirrors the
/// web student dashboard's `is_flagged`/`flag_details` (Page::student).
class FlagStatus {
  const FlagStatus({
    required this.isFlagged,
    required this.reason,
    required this.flaggedBy,
    required this.office,
    required this.sy,
    required this.semester,
  });

  final bool isFlagged;
  final String reason;
  final String flaggedBy;
  final String office;
  final String sy;
  final String semester;

  factory FlagStatus.fromJson(Map<String, dynamic> j) {
    final flag = j['flag'] as Map<String, dynamic>?;
    return FlagStatus(
      isFlagged: j['is_flagged'] == true,
      reason: (flag?['reason'] ?? '').toString(),
      flaggedBy: (flag?['flagged_by'] ?? '').toString(),
      office: (flag?['office'] ?? '').toString(),
      sy: (flag?['sy'] ?? '').toString(),
      semester: (flag?['semester'] ?? '').toString(),
    );
  }
}

/// One payment record from `GET /api/mobile/student/payments`.
class Payment {
  const Payment({
    required this.id,
    required this.date,
    required this.orNumber,
    required this.amount,
    required this.description,
    required this.paymentType,
    required this.collectionSource,
    required this.sem,
    required this.sy,
    required this.orStatus,
    required this.refNo,
  });

  final int id;
  final String date;
  final String orNumber;
  final double amount;
  final String description;
  final String paymentType;
  final String collectionSource;
  final String sem;
  final String sy;
  final String orStatus;
  final String refNo;

  bool get isValid =>
      orStatus.toLowerCase() == 'valid' ||
      orStatus.toLowerCase() == 'verified';

  factory Payment.fromJson(Map<String, dynamic> j) => Payment(
        id: (j['id'] as num?)?.toInt() ?? 0,
        date: (j['date'] ?? j['or_date'] ?? '').toString(),
        orNumber: (j['or_number'] ?? j['or_no'] ?? '').toString(),
        amount: (j['amount'] as num?)?.toDouble() ?? 0,
        description: (j['description'] ?? j['particulars'] ?? '').toString(),
        paymentType: (j['payment_type'] ?? j['pay_type'] ?? '').toString(),
        collectionSource:
            (j['collection_source'] ?? j['coll_source'] ?? '').toString(),
        sem: (j['sem'] ?? j['semester'] ?? '').toString(),
        sy: (j['sy'] ?? j['school_year'] ?? '').toString(),
        orStatus: (j['or_status'] ?? j['status'] ?? '').toString(),
        refNo: (j['ref_no'] ?? j['reference_no'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'date': date,
        'or_number': orNumber,
        'amount': amount,
        'description': description,
        'payment_type': paymentType,
        'collection_source': collectionSource,
        'sem': sem,
        'sy': sy,
        'or_status': orStatus,
        'ref_no': refNo,
      };
}

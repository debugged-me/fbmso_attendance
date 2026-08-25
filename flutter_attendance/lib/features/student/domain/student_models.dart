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

/// One requirement row from `GET /api/mobile/student/requirements`.
class Requirement {
  const Requirement({
    required this.reqId,
    required this.name,
    required this.description,
    required this.dateSubmitted,
    required this.filePath,
    required this.fileUrl,
    required this.isVerified,
    required this.comment,
  });

  final int reqId;
  final String name;
  final String description;
  final String dateSubmitted;
  final String filePath;
  final String fileUrl;
  final bool isVerified;
  final String comment;

  bool get isSubmitted => dateSubmitted.isNotEmpty && filePath.isNotEmpty;

  factory Requirement.fromJson(Map<String, dynamic> j) => Requirement(
        reqId: (j['req_id'] as num?)?.toInt() ?? 0,
        name: (j['name'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        dateSubmitted: (j['date_submitted'] ?? '').toString(),
        filePath: (j['file_path'] ?? '').toString(),
        fileUrl: (j['file_url'] ?? '').toString(),
        isVerified: j['is_verified'] == true,
        comment: (j['comment'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'req_id': reqId,
        'name': name,
        'description': description,
        'date_submitted': dateSubmitted,
        'file_path': filePath,
        'file_url': fileUrl,
        'is_verified': isVerified,
        'comment': comment,
      };
}

/// One grade row from `GET /api/mobile/student/grades`.
class Grade {
  const Grade({
    required this.subjectCode,
    required this.description,
    required this.course,
    required this.major,
    required this.yearLevel,
    required this.section,
    required this.lecUnit,
    required this.labUnit,
    required this.prelim,
    required this.midterm,
    required this.preFinal,
    required this.finalGrade,
    required this.average,
    required this.sy,
    required this.semester,
  });

  final String subjectCode;
  final String description;
  final String course;
  final String major;
  final String yearLevel;
  final String section;
  final String lecUnit;
  final String labUnit;
  final double? prelim;
  final double? midterm;
  final double? preFinal;
  final double? finalGrade;
  final double? average;
  final String sy;
  final String semester;

  factory Grade.fromJson(Map<String, dynamic> j) => Grade(
        subjectCode: (j['subject_code'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        yearLevel: (j['year_level'] ?? '').toString(),
        section: (j['section'] ?? '').toString(),
        lecUnit: (j['lec_unit'] ?? '').toString(),
        labUnit: (j['lab_unit'] ?? '').toString(),
        prelim: (j['prelim'] as num?)?.toDouble(),
        midterm: (j['midterm'] as num?)?.toDouble(),
        preFinal: (j['pre_final'] as num?)?.toDouble(),
        finalGrade: (j['final'] as num?)?.toDouble(),
        average: (j['average'] as num?)?.toDouble(),
        sy: (j['sy'] ?? '').toString(),
        semester: (j['semester'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'subject_code': subjectCode,
        'description': description,
        'course': course,
        'major': major,
        'year_level': yearLevel,
        'section': section,
        'lec_unit': lecUnit,
        'lab_unit': labUnit,
        'prelim': prelim,
        'midterm': midterm,
        'pre_final': preFinal,
        'final': finalGrade,
        'average': average,
        'sy': sy,
        'semester': semester,
      };
}

/// One enrolled subject (COR row) from `GET /api/mobile/student/enrolled_subjects`.
class EnrolledSubject {
  const EnrolledSubject({
    required this.subjectCode,
    required this.description,
    required this.lecUnit,
    required this.labUnit,
    required this.units,
    required this.section,
    required this.schedule,
    required this.room,
    required this.instructor,
    required this.course,
    required this.yearLevel,
    required this.major,
    required this.sem,
    required this.sy,
    required this.schedType,
  });

  final String subjectCode;
  final String description;
  final String lecUnit;
  final String labUnit;
  final double units;
  final String section;
  final String schedule;
  final String room;
  final String instructor;
  final String course;
  final String yearLevel;
  final String major;
  final String sem;
  final String sy;
  final String schedType;

  factory EnrolledSubject.fromJson(Map<String, dynamic> j) => EnrolledSubject(
        subjectCode: (j['subject_code'] ?? '').toString(),
        description: (j['description'] ?? '').toString(),
        lecUnit: (j['lec_unit'] ?? '').toString(),
        labUnit: (j['lab_unit'] ?? '').toString(),
        units: (j['units'] as num?)?.toDouble() ?? 0,
        section: (j['section'] ?? '').toString(),
        schedule: (j['schedule'] ?? '').toString(),
        room: (j['room'] ?? '').toString(),
        instructor: (j['instructor'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        yearLevel: (j['year_level'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        sem: (j['sem'] ?? '').toString(),
        sy: (j['sy'] ?? '').toString(),
        schedType: (j['sched_type'] ?? '').toString(),
      );
}

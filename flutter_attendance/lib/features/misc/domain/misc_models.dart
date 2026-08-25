/// Announcement row from `GET /api/mobile/announcements`.
class Announcement {
  const Announcement({
    required this.id,
    required this.title,
    required this.message,
    required this.author,
    required this.audience,
    required this.datePosted,
    required this.dateExpire,
    required this.imageUrl,
  });

  final int id;
  final String title;
  final String message;
  final String author;
  final String audience;
  final String datePosted;
  final String dateExpire;
  final String imageUrl;

  factory Announcement.fromJson(Map<String, dynamic> j) => Announcement(
        id: (j['id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        message: (j['message'] ?? '').toString(),
        author: (j['author'] ?? '').toString(),
        audience: (j['audience'] ?? '').toString(),
        datePosted: (j['date_posted'] ?? '').toString(),
        dateExpire: (j['date_expire'] ?? '').toString(),
        imageUrl: (j['image_url'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'message': message,
        'author': author,
        'audience': audience,
        'date_posted': datePosted,
        'date_expire': dateExpire,
        'image_url': imageUrl,
      };
}

/// Note row from `GET /api/mobile/notes`.
class Note {
  const Note({
    required this.id,
    required this.title,
    required this.content,
    required this.createdAt,
    required this.updatedAt,
  });

  final int id;
  final String title;
  final String content;
  final String createdAt;
  final String updatedAt;

  factory Note.fromJson(Map<String, dynamic> j) => Note(
        id: (j['id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        content: (j['content'] ?? '').toString(),
        createdAt: (j['created_at'] ?? '').toString(),
        updatedAt: (j['updated_at'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'content': content,
        'created_at': createdAt,
        'updated_at': updatedAt,
      };
}

/// Todo row from `GET /api/mobile/todos`.
class Todo {
  const Todo({
    required this.id,
    required this.task,
    required this.isDone,
    required this.dueDate,
    required this.createdAt,
    required this.completedAt,
    required this.comment,
  });

  final int id;
  final String task;
  final bool isDone;
  final String dueDate;
  final String createdAt;
  final String completedAt;
  final String comment;

  factory Todo.fromJson(Map<String, dynamic> j) => Todo(
        id: (j['id'] as num?)?.toInt() ?? 0,
        task: (j['task'] ?? '').toString(),
        isDone: j['is_done'] == true,
        dueDate: (j['due_date'] ?? '').toString(),
        createdAt: (j['created_at'] ?? '').toString(),
        completedAt: (j['completed_at'] ?? '').toString(),
        comment: (j['comment'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'task': task,
        'is_done': isDone,
        'due_date': dueDate,
        'created_at': createdAt,
        'completed_at': completedAt,
        'comment': comment,
      };
}

/// Personnel directory entry from `GET /api/mobile/personnel`.
class Personnel {
  const Personnel({
    required this.id,
    required this.fullName,
    required this.title,
    required this.bio,
    required this.photoUrl,
    required this.sortOrder,
    this.isActive = 1,
  });

  final int id;
  final String fullName;
  final String title;
  final String bio;
  final String photoUrl;
  final int sortOrder;
  final int isActive;

  factory Personnel.fromJson(Map<String, dynamic> j) => Personnel(
        id: int.tryParse((j['id'] ?? '0').toString()) ?? 0,
        fullName: (j['full_name'] ?? '').toString(),
        title: (j['title'] ?? '').toString(),
        bio: (j['bio'] ?? '').toString(),
        photoUrl: (j['photo_url'] ?? '').toString(),
        sortOrder: int.tryParse((j['sort_order'] ?? '100').toString()) ?? 100,
        isActive: int.tryParse((j['is_active'] ?? '1').toString()) ?? 1,
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'full_name': fullName,
        'title': title,
        'bio': bio,
        'photo_url': photoUrl,
        'sort_order': sortOrder,
        'is_active': isActive,
      };
}

/// One row from the enrolled-students masterlist.
class MasterlistEntry {
  const MasterlistEntry({
    required this.studentNumber,
    required this.firstName,
    required this.lastName,
    required this.fullName,
    required this.course,
    required this.major,
    required this.status,
    required this.enrollmentDate,
  });

  final String studentNumber;
  final String firstName;
  final String lastName;
  final String fullName;
  final String course;
  final String major;
  final String status;
  final String enrollmentDate;

  factory MasterlistEntry.fromJson(Map<String, dynamic> j) => MasterlistEntry(
        studentNumber: (j['student_number'] ?? '').toString(),
        firstName: (j['first_name'] ?? '').toString(),
        lastName: (j['last_name'] ?? '').toString(),
        fullName: (j['full_name'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        enrollmentDate: (j['enrollment_date'] ?? '').toString(),
      );
}

/// One expense row from the accounting expenses endpoint.
class ExpenseEntry {
  const ExpenseEntry({
    required this.id,
    required this.description,
    required this.amount,
    required this.date,
    required this.category,
  });

  final int id;
  final String description;
  final String amount;
  final String date;
  final String category;

  factory ExpenseEntry.fromJson(Map<String, dynamic> j) => ExpenseEntry(
        id: int.tryParse((j['expensesid'] ?? j['id'] ?? '0').toString()) ?? 0,
        description: (j['description'] ?? j['Description'] ?? '').toString(),
        amount: (j['amount'] ?? j['Amount'] ?? '0').toString(),
        date: (j['date'] ?? j['Date'] ?? j['ExpenseDate'] ?? j['date_added'] ?? '').toString(),
        category: (j['category'] ?? j['Category'] ?? j['categoryname'] ?? '').toString(),
      );
}

/// An expense category from the expensescategory table.
class ExpenseCategory {
  const ExpenseCategory({required this.id, required this.category});
  final int id;
  final String category;

  factory ExpenseCategory.fromJson(Map<String, dynamic> j) => ExpenseCategory(
        id: int.tryParse((j['id'] ?? '0').toString()) ?? 0,
        category: (j['category'] ?? j['Category'] ?? '').toString(),
      );
}

/// A user account (admin/staff) from the user accounts list.
class UserAccount {
  const UserAccount({
    required this.username,
    required this.idNumber,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.fullName,
    required this.email,
    required this.position,
    required this.status,
    required this.dateCreated,
    required this.avatar,
  });

  final String username;
  final String idNumber;
  final String firstName;
  final String middleName;
  final String lastName;
  final String fullName;
  final String email;
  final String position;
  final String status;
  final String dateCreated;
  final String avatar;

  factory UserAccount.fromJson(Map<String, dynamic> j) => UserAccount(
        username: (j['username'] ?? '').toString(),
        idNumber: (j['id_number'] ?? '').toString(),
        firstName: (j['first_name'] ?? '').toString(),
        middleName: (j['middle_name'] ?? '').toString(),
        lastName: (j['last_name'] ?? '').toString(),
        fullName: (j['full_name'] ?? '').toString(),
        email: (j['email'] ?? '').toString(),
        position: (j['position'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        dateCreated: (j['date_created'] ?? '').toString(),
        avatar: (j['avatar'] ?? '').toString(),
      );
}

/// A registered student from the studentsignup table.
class RegisteredStudent {
  const RegisteredStudent({
    required this.studentNumber,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.nameExtn,
    required this.fullName,
    required this.birthDate,
    required this.email,
    required this.contactNo,
    required this.course,
    required this.major,
    required this.yearLevel,
    required this.section,
    required this.status,
    required this.enrollmentDate,
  });

  final String studentNumber;
  final String firstName;
  final String middleName;
  final String lastName;
  final String nameExtn;
  final String fullName;
  final String birthDate;
  final String email;
  final String contactNo;
  final String course;
  final String major;
  final String yearLevel;
  final String section;
  final String status;
  final String enrollmentDate;

  factory RegisteredStudent.fromJson(Map<String, dynamic> j) =>
      RegisteredStudent(
        studentNumber: (j['student_number'] ?? '').toString(),
        firstName: (j['first_name'] ?? '').toString(),
        middleName: (j['middle_name'] ?? '').toString(),
        lastName: (j['last_name'] ?? '').toString(),
        nameExtn: (j['name_extn'] ?? '').toString(),
        fullName: (j['full_name'] ?? '').toString(),
        birthDate: (j['birth_date'] ?? '').toString(),
        email: (j['email'] ?? '').toString(),
        contactNo: (j['contact_no'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        yearLevel: (j['year_level'] ?? '').toString(),
        section: (j['section'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        enrollmentDate: (j['enrollment_date'] ?? '').toString(),
      );
}

/// A department/course from the course_table.
class Department {
  const Department({
    required this.id,
    required this.courseCode,
    required this.courseDescription,
    required this.major,
    required this.duration,
    required this.recogNo,
    required this.seriesYear,
    required this.programHead,
    required this.idNumber,
  });

  final int id;
  final String courseCode;
  final String courseDescription;
  final String major;
  final String duration;
  final String recogNo;
  final String seriesYear;
  final String programHead;
  final String idNumber;

  factory Department.fromJson(Map<String, dynamic> j) => Department(
        id: int.tryParse((j['id'] ?? '0').toString()) ?? 0,
        courseCode: (j['course_code'] ?? '').toString(),
        courseDescription: (j['course_description'] ?? '').toString(),
        major: (j['major'] ?? '').toString(),
        duration: (j['duration'] ?? '').toString(),
        recogNo: (j['recog_no'] ?? '').toString(),
        seriesYear: (j['series_year'] ?? '').toString(),
        programHead: (j['program_head'] ?? '').toString(),
        idNumber: (j['id_number'] ?? '').toString(),
      );
}

/// A section from course_sections.
class Section {
  const Section({
    required this.id,
    required this.courseId,
    required this.courseName,
    required this.yearLevel,
    required this.section,
    required this.isActive,
  });

  final int id;
  final String courseId;
  final String courseName;
  final String yearLevel;
  final String section;
  final int isActive;

  factory Section.fromJson(Map<String, dynamic> j) => Section(
        id: int.tryParse((j['id'] ?? '0').toString()) ?? 0,
        courseId: (j['course_id'] ?? '').toString(),
        courseName: (j['course_name'] ?? '').toString(),
        yearLevel: (j['year_level'] ?? '').toString(),
        section: (j['section'] ?? '').toString(),
        isActive: int.tryParse((j['is_active'] ?? '1').toString()) ?? 1,
      );
}

/// Reports summary.
class ReportSummary {
  const ReportSummary({
    required this.sy,
    required this.sem,
    required this.byYearLevel,
    required this.byCourse,
    required this.sectionsCount,
    required this.eventsTotal,
    required this.eventScans,
  });

  final String sy;
  final String sem;
  final List<ReportRow> byYearLevel;
  final List<ReportRow> byCourse;
  final List<ReportRow> sectionsCount;
  final int eventsTotal;
  final int eventScans;

  factory ReportSummary.fromJson(Map<String, dynamic> j) => ReportSummary(
        sy: (j['sy'] ?? '').toString(),
        sem: (j['sem'] ?? '').toString(),
        byYearLevel: ((j['by_year_level'] ?? []) as List)
            .map((e) => ReportRow.fromJson(e as Map<String, dynamic>))
            .toList(),
        byCourse: ((j['by_course'] ?? []) as List)
            .map((e) => ReportRow.fromJson(e as Map<String, dynamic>))
            .toList(),
        sectionsCount: ((j['sections_count'] ?? []) as List)
            .map((e) => ReportRow.fromJson(e as Map<String, dynamic>))
            .toList(),
        eventsTotal: int.tryParse((j['events_total'] ?? '0').toString()) ?? 0,
        eventScans: int.tryParse((j['event_scans'] ?? '0').toString()) ?? 0,
      );
}

class ReportRow {
  const ReportRow({
    this.yearLevel = '',
    this.course = '',
    this.count = 0,
    this.sections = 0,
  });

  final String yearLevel;
  final String course;
  final int count;
  final int sections;

  factory ReportRow.fromJson(Map<String, dynamic> j) => ReportRow(
        yearLevel: (j['year_level'] ?? '').toString(),
        course: (j['course'] ?? '').toString(),
        count: int.tryParse((j['count'] ?? '0').toString()) ?? 0,
        sections: int.tryParse((j['sections'] ?? '0').toString()) ?? 0,
      );
}

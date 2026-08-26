/// The manual open/closed override an admin sets on an activity.
/// Mirrors the `activities`.`status` enum on the server.
enum ActivityStatus {
  draft('draft', 'Draft'),
  open('open', 'Open'),
  closed('closed', 'Closed'),
  archived('archived', 'Archived');

  const ActivityStatus(this.value, this.label);
  final String value;
  final String label;

  static ActivityStatus fromValue(String? v) => ActivityStatus.values.firstWhere(
        (s) => s.value == (v ?? '').trim().toLowerCase(),
        orElse: () => ActivityStatus.open,
      );
}

/// One activity row from `GET /api/mobile/activities`.
///
/// `isOpen` is the EFFECTIVE answer from the server: the manual [status] and the
/// auto-close time window must both pass. [state]/[stateLabel]/[closedReason]
/// explain *why* it is closed so the UI can say "Ended" rather than just "Closed".
class Activity {
  const Activity({
    required this.activityId,
    required this.title,
    required this.code,
    required this.activityDate,
    required this.startAt,
    required this.endAt,
    required this.startTime,
    required this.endTime,
    required this.location,
    required this.description,
    required this.program,
    required this.sy,
    required this.semester,
    required this.status,
    required this.isOpen,
    this.state = 'open',
    this.stateLabel = 'Open',
    this.closedReason,
    this.autoClose = true,
    this.graceMinutes = 15,
    this.windowStart,
    this.windowEnd,
  });

  final int activityId;
  final String title;
  final String code;
  final String activityDate;
  final String startAt;
  final String endAt;
  final String startTime;
  final String endTime;
  final String location;
  final String description;
  final String program;
  final String sy;
  final String semester;

  /// Raw manual override value ('draft'|'open'|'closed'|'archived').
  final String status;

  /// Effective: accepting check-ins right now.
  final bool isOpen;

  /// open | scheduled | ended | closed | draft | archived
  final String state;
  final String stateLabel;

  /// Human-readable explanation; null when [isOpen].
  final String? closedReason;

  final bool autoClose;
  final int graceMinutes;
  final String? windowStart;
  final String? windowEnd;

  ActivityStatus get manualStatus => ActivityStatus.fromValue(status);

  /// True when the clock closed it, not a person — the admin can still reopen.
  bool get autoClosed => state == 'ended';

  /// True when it has not started yet.
  bool get notYetOpen => state == 'scheduled';

  factory Activity.fromJson(Map<String, dynamic> j) {
    final open = j['is_open'] == true;
    // Older servers send only is_open/status; synthesise the richer fields.
    final state = (j['state'] ?? (open ? 'open' : 'closed')).toString();
    return Activity(
      activityId: (j['activity_id'] as num?)?.toInt() ?? 0,
      title: (j['title'] ?? '').toString(),
      code: (j['code'] ?? '').toString(),
      activityDate: (j['activity_date'] ?? '').toString(),
      startAt: (j['start_at'] ?? '').toString(),
      endAt: (j['end_at'] ?? '').toString(),
      startTime: (j['start_time'] ?? '').toString(),
      endTime: (j['end_time'] ?? '').toString(),
      location: (j['location'] ?? '').toString(),
      description: (j['description'] ?? '').toString(),
      program: (j['program'] ?? '').toString(),
      sy: (j['sy'] ?? '').toString(),
      semester: (j['semester'] ?? '').toString(),
      status: (j['manual_status'] ?? j['status'] ?? '').toString(),
      isOpen: open,
      state: state,
      stateLabel: (j['state_label'] ?? (open ? 'Open' : 'Closed')).toString(),
      closedReason: (j['closed_reason'] as String?)?.trim().isEmpty ?? true
          ? null
          : (j['closed_reason'] as String).trim(),
      autoClose: j['auto_close'] == null ? true : j['auto_close'] == true,
      graceMinutes: (j['grace_minutes'] as num?)?.toInt() ?? 15,
      windowStart: j['window_start'] as String?,
      windowEnd: j['window_end'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'activity_id': activityId,
        'title': title,
        'code': code,
        'activity_date': activityDate,
        'start_at': startAt,
        'end_at': endAt,
        'start_time': startTime,
        'end_time': endTime,
        'location': location,
        'description': description,
        'program': program,
        'sy': sy,
        'semester': semester,
        'status': status,
        'is_open': isOpen,
        'state': state,
        'state_label': stateLabel,
        'closed_reason': closedReason,
        'auto_close': autoClose,
        'grace_minutes': graceMinutes,
        'window_start': windowStart,
        'window_end': windowEnd,
      };
}

/// One row of the student's own attendance log (`GET /api/mobile/attendance/my_logs`).
class AttendanceLog {
  const AttendanceLog({
    required this.id,
    required this.activityId,
    required this.title,
    required this.activityDate,
    required this.checkedInAt,
    required this.checkedOutAt,
    required this.source,
    required this.remarks,
    required this.session,
    required this.sessionLabel,
  });

  final int id;
  final int activityId;
  final String title;
  final String activityDate;
  final String checkedInAt;
  final String checkedOutAt;
  final String source;
  final String remarks;
  final String session;
  final String sessionLabel;

  bool get isCheckedOut => checkedOutAt.isNotEmpty;

  factory AttendanceLog.fromJson(Map<String, dynamic> j) => AttendanceLog(
        id: (j['id'] as num?)?.toInt() ?? 0,
        activityId: (j['activity_id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        activityDate: (j['activity_date'] ?? '').toString(),
        checkedInAt: (j['checked_in_at'] ?? '').toString(),
        checkedOutAt: (j['checked_out_at'] ?? '').toString(),
        source: (j['source'] ?? '').toString(),
        remarks: (j['remarks'] ?? '').toString(),
        session: (j['session'] ?? '').toString(),
        sessionLabel: (j['session_label'] ?? '').toString(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'activity_id': activityId,
        'title': title,
        'activity_date': activityDate,
        'checked_in_at': checkedInAt,
        'checked_out_at': checkedOutAt,
        'source': source,
        'remarks': remarks,
        'session': session,
        'session_label': sessionLabel,
      };
}

/// Result of a check-in/out or scanner consume call.
class CheckResult {
  const CheckResult({
    required this.ok,
    required this.mode,
    this.id,
    this.studentNumber,
    this.session,
    this.message,
    this.student,
  });

  final bool ok;
  /// checked_in | checked_out | already_in | duplicate | err
  final String mode;
  final int? id;
  final String? studentNumber;
  final String? session;
  final String? message;
  final Map<String, dynamic>? student;

  factory CheckResult.fromJson(Map<String, dynamic> j) => CheckResult(
        ok: j['ok'] == true,
        mode: (j['mode'] ?? 'err').toString(),
        id: (j['id'] as num?)?.toInt(),
        studentNumber: (j['student_number'] ?? '').toString().isEmpty
            ? null
            : (j['student_number'] ?? '').toString(),
        session: (j['session'] ?? '').toString().isEmpty
            ? null
            : (j['session'] ?? '').toString(),
        message: (j['message'] ?? '').toString().isEmpty
            ? null
            : (j['message'] ?? '').toString(),
        student: (j['student'] as Map?)?.cast<String, dynamic>(),
      );
}

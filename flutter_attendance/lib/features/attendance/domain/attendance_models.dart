import 'dart:convert';

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

  static ActivityStatus fromValue(String? v) =>
      ActivityStatus.values.firstWhere(
        (s) => s.value == (v ?? '').trim().toLowerCase(),
        orElse: () => ActivityStatus.open,
      );
}

/// The three check-in windows the web create form writes into
/// `activities.meta.sessions` (am/pm/eve, each with in/out "HH:MM").
/// Empty fields are null — same shape the web serializes.
class ActivitySessions {
  const ActivitySessions({
    this.amIn,
    this.amOut,
    this.pmIn,
    this.pmOut,
    this.eveIn,
    this.eveOut,
  });

  final String? amIn;
  final String? amOut;
  final String? pmIn;
  final String? pmOut;
  final String? eveIn;
  final String? eveOut;

  static const empty = ActivitySessions();

  bool get isEmpty =>
      _blank(amIn) &&
      _blank(amOut) &&
      _blank(pmIn) &&
      _blank(pmOut) &&
      _blank(eveIn) &&
      _blank(eveOut);

  bool get isNotEmpty => !isEmpty;

  static bool _blank(String? v) => v == null || v.trim().isEmpty;

  static String? _read(Map<String, dynamic> j, String session, String key) {
    final s = j[session];
    if (s is! Map) return null;
    final v = s[key];
    if (v == null) return null;
    final str = v.toString().trim();
    return str.isEmpty ? null : str.substring(0, str.length >= 5 ? 5 : str.length);
  }

  factory ActivitySessions.fromJson(Map<String, dynamic>? j) {
    if (j == null) return empty;
    return ActivitySessions(
      amIn: _read(j, 'am', 'in'),
      amOut: _read(j, 'am', 'out'),
      pmIn: _read(j, 'pm', 'in'),
      pmOut: _read(j, 'pm', 'out'),
      eveIn: _read(j, 'eve', 'in'),
      eveOut: _read(j, 'eve', 'out'),
    );
  }

  /// Serialize for the API. Sessions with both fields empty are dropped —
  /// same rule the web form's JS applies.
  Map<String, dynamic> toJson() {
    String? orNull(String? v) => _blank(v) ? null : v;
    return {
      if (!_blank(amIn) || !_blank(amOut))
        'am': {'in': orNull(amIn), 'out': orNull(amOut)},
      if (!_blank(pmIn) || !_blank(pmOut))
        'pm': {'in': orNull(pmIn), 'out': orNull(pmOut)},
      if (!_blank(eveIn) || !_blank(eveOut))
        'eve': {'in': orNull(eveIn), 'out': orNull(eveOut)},
    };
  }
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
    this.autoClose = false,
    this.graceMinutes = 15,
    this.windowStart,
    this.windowEnd,
    this.sessions = ActivitySessions.empty,
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

  /// Per-session check-in windows from meta.sessions (am/pm/eve in/out).
  final ActivitySessions sessions;

  ActivityStatus get manualStatus => ActivityStatus.fromValue(status);

  /// True when the clock closed it, not a person — the admin can still reopen.
  bool get autoClosed => state == 'ended';

  /// True when it has not started yet.
  bool get notYetOpen => state == 'scheduled';

  /// This activity with open/closed re-judged for [now] on this device.
  ///
  /// [isOpen] is the server's answer at the moment the list was fetched. A
  /// list served from cache can be hours old — fetched the night before, the
  /// activity still "Scheduled" — and would keep the scanner locked all day
  /// with no signal to refresh it. The time layer is recomputed from the
  /// cached window with the same rule as the server's activity_state(); a
  /// manual close (closed/draft/archived) stands, since only the server can
  /// lift it.
  Activity recheckedAt(DateTime now) {
    const manuallyClosed = {'closed', 'draft', 'archived'};
    if (manuallyClosed.contains(state)) return this;
    if (!autoClose) return _withState(true, 'open', 'Open', null);

    final start = _serverTime(windowStart);
    final end = _serverTime(windowEnd);
    if (start != null && now.isBefore(start)) {
      return _withState(false, 'scheduled', 'Scheduled',
          'Check-in for this activity opens on ${_stamp(start)}.');
    }
    if (end != null && now.isAfter(end)) {
      return _withState(false, 'ended', 'Ended',
          'Check-in for this activity closed on ${_stamp(end)}.');
    }
    return _withState(true, 'open', 'Open', null);
  }

  Activity _withState(bool open, String newState, String label, String? reason) {
    if (open == isOpen && newState == state) return this;
    return Activity(
      activityId: activityId,
      title: title,
      code: code,
      activityDate: activityDate,
      startAt: startAt,
      endAt: endAt,
      startTime: startTime,
      endTime: endTime,
      location: location,
      description: description,
      program: program,
      sy: sy,
      semester: semester,
      status: status,
      isOpen: open,
      state: newState,
      stateLabel: label,
      closedReason: reason,
      autoClose: autoClose,
      graceMinutes: graceMinutes,
      windowStart: windowStart,
      windowEnd: windowEnd,
      sessions: sessions,
    );
  }

  /// "Y-m-d H:i:s" in the server's (and the school's) local time.
  static DateTime? _serverTime(String? value) {
    final v = (value ?? '').trim();
    if (v.isEmpty) return null;
    return DateTime.tryParse(v.replaceFirst(' ', 'T'));
  }

  static String _stamp(DateTime t) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
        'Sep', 'Oct', 'Nov', 'Dec'];
    final h = t.hour % 12 == 0 ? 12 : t.hour % 12;
    final m = t.minute.toString().padLeft(2, '0');
    return '${months[t.month - 1]} ${t.day}, ${t.year} at '
        '$h:$m ${t.hour < 12 ? 'AM' : 'PM'}';
  }

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
      autoClose: j['auto_close'] == true,
      graceMinutes: (j['grace_minutes'] as num?)?.toInt() ?? 15,
      windowStart: j['window_start'] as String?,
      windowEnd: j['window_end'] as String?,
      sessions:
          ActivitySessions.fromJson(j['sessions'] as Map<String, dynamic>?),
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
        'sessions': sessions.toJson(),
      };
}

/// The patch `POST activities/update` gets for an edit — only the fields
/// the user actually changed. A full-replace payload lets a control that
/// failed to seed (unresolved dropdown, stale cache, an older server that
/// never sent `sessions`) wipe the stored value, so anything still equal
/// to [original] is omitted.
Map<String, dynamic> buildActivityUpdateFields({
  required Activity original,
  required String title,
  required String description,
  required String location,
  required String date,
  // Resolved "Program — Major" payload; null means the program dropdown
  // never resolved and the stored value must be left alone.
  String? program,
  required ActivitySessions sessions,
  required ActivityStatus status,
  required bool autoClose,
  required int graceMinutes,
}) {
  final fields = <String, dynamic>{};
  if (title != original.title) fields['title'] = title;
  if (description != original.description) {
    fields['description'] = description;
  }
  if (location != original.location) fields['location'] = location;

  // An unparsable stored date (0000-00-00) must not turn into "today" —
  // only a visible, different date is sent.
  if (date.isNotEmpty && date != original.activityDate) {
    fields['activity_date'] = date;
  }

  if (program != null && program != original.program) {
    fields['program'] = program;
  }

  // Unchanged sessions are omitted so meta.sessions survives untouched; a
  // deliberately cleared set still serializes to {} and clears them,
  // exactly like the web form's all-blank windows.
  final cur = sessions.toJson();
  if (jsonEncode(cur) != jsonEncode(original.sessions.toJson())) {
    fields['sessions'] = cur;
  }

  if (status != original.manualStatus) fields['status'] = status.value;

  // auto_close/grace_minutes share the same meta row — keep them together.
  if (autoClose != original.autoClose ||
      graceMinutes != original.graceMinutes) {
    fields['auto_close'] = autoClose;
    fields['grace_minutes'] = graceMinutes;
  }
  return fields;
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
    this.provisional = false,
    this.clientScanId,
  });

  final bool ok;

  /// checked_in | checked_out | already_in | duplicate | queued | unverified |
  /// invalid_qr | unknown_qr | expired_qr | activity_* | stale_scan | err
  final String mode;

  /// Decided on the device and not yet confirmed by the server. The UI must
  /// say so — the server can still reject it when it uploads.
  final bool provisional;

  /// The device's id for a scanner scan; its server verdict carries the same
  /// id (see ScanLedgerService.verdicts).
  final String? clientScanId;
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

/// Committee dashboard stats — mirrors Page::committee on the web:
/// open/total activity counts, today's scan count, a 14-day scan trend,
/// and the 8 most recent scans.
class CommitteeDashboard {
  const CommitteeDashboard({
    required this.openCount,
    required this.totalCount,
    required this.todayScans,
    required this.trend,
    required this.recentScans,
  });

  final int openCount;
  final int totalCount;
  final int todayScans;

  /// [{date, count}] — 14-day scan trend.
  final List<({String date, int count})> trend;
  final List<Map<String, dynamic>> recentScans;

  factory CommitteeDashboard.fromJson(Map<String, dynamic> j) {
    int toInt(dynamic v) => (v is num) ? v.toInt() : int.tryParse('$v') ?? 0;
    return CommitteeDashboard(
      openCount: toInt(j['open_count']),
      totalCount: toInt(j['total_count']),
      todayScans: toInt(j['today_scans']),
      trend: ((j['trend'] as List?) ?? [])
          .map((e) => (
                date: (e['date'] ?? e['day'] ?? '').toString(),
                count: toInt(e['count'] ?? e['total'] ?? e['scans'] ?? 0),
              ))
          .toList(),
      recentScans: ((j['recent_scans'] as List?) ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList(),
    );
  }
}

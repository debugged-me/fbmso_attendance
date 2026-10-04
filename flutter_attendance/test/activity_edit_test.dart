import 'package:flutter_attendance/features/attendance/domain/attendance_models.dart';
import 'package:flutter_test/flutter_test.dart';

/// The edit form's update is a partial patch, not a full replace — a
/// control that failed to seed (stale cache, an older server without
/// `sessions`, a programs list that never loaded) must not blank the
/// stored row on save. These pin that contract plus the parsing that
/// feeds it.
void main() {
  Map<String, dynamic> newShapeActivity() => {
        'activity_id': 25,
        'title': 'Leadership Summit',
        'activity_date': '2026-10-05',
        'start_at': '2026-10-05 16:00:00',
        'end_at': '2026-10-05 20:00:00',
        'start_time': '16:00:00',
        'end_time': '20:00:00',
        'location': 'AVR',
        'description': 'All orgs.',
        'program': 'BSIT — CS',
        'status': 'open',
        'manual_status': 'open',
        'is_open': true,
        'state': 'open',
        'auto_close': true,
        'grace_minutes': 30,
        'sessions': {
          'pm': {'in': '16:00', 'out': '17:00'},
          'eve': {'in': '19:00', 'out': '20:00'},
        },
      };

  Map<String, dynamic> updateFor(
    Activity original, {
    String? title,
    String? description,
    String? location,
    String? date,
    String? program,
    ActivitySessions? sessions,
    ActivityStatus? status,
    bool? autoClose,
    int? graceMinutes,
  }) =>
      buildActivityUpdateFields(
        original: original,
        title: title ?? original.title,
        description: description ?? original.description,
        location: location ?? original.location,
        date: date ?? original.activityDate,
        program: program,
        sessions: sessions ?? original.sessions,
        status: status ?? original.manualStatus,
        autoClose: autoClose ?? original.autoClose,
        graceMinutes: graceMinutes ?? original.graceMinutes,
      );

  group('Activity.fromJson', () {
    test('parses session windows from the api shape', () {
      final a = Activity.fromJson(newShapeActivity());
      expect(a.sessions.pmIn, '16:00');
      expect(a.sessions.pmOut, '17:00');
      expect(a.sessions.eveIn, '19:00');
      expect(a.sessions.eveOut, '20:00');
      expect(a.sessions.amIn, isNull);
      expect(a.autoClose, isTrue);
      expect(a.graceMinutes, 30);
    });

    test('an older server without sessions seeds empty', () {
      final json = newShapeActivity()..remove('sessions');
      expect(Activity.fromJson(json).sessions.isEmpty, isTrue);
    });

    test('the offline cache round-trips sessions', () {
      final a = Activity.fromJson(newShapeActivity());
      final cached = Activity.fromJson(a.toJson());
      expect(cached.sessions.pmIn, '16:00');
      expect(cached.sessions.eveOut, '20:00');
      expect(cached.autoClose, isTrue);
    });
  });

  group('buildActivityUpdateFields', () {
    test('an untouched edit sends nothing', () {
      final a = Activity.fromJson(newShapeActivity());
      expect(
        updateFor(a, program: a.program),
        isEmpty,
      );
    });

    test('a title-only edit sends only the title', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields = updateFor(a, title: 'Renamed', program: a.program);
      expect(fields, {'title': 'Renamed'});
    });

    test('unedited sessions are omitted so meta.sessions survives', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields = updateFor(a, location: 'Gym', program: a.program);
      expect(fields.containsKey('sessions'), isFalse);
      expect(fields.containsKey('auto_close'), isFalse);
      expect(fields.containsKey('grace_minutes'), isFalse);
      expect(fields.containsKey('activity_date'), isFalse);
    });

    test('an unresolved program dropdown cannot blank the stored one', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields = updateFor(a, title: 'Renamed'); // program: null
      expect(fields.containsKey('program'), isFalse);
    });

    test('a cleared session set still clears — same as the web form', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields =
          updateFor(a, sessions: ActivitySessions.empty, program: a.program);
      expect(fields['sessions'], <String, dynamic>{});
    });

    test('editing a window sends the whole sessions document', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields = updateFor(
        a,
        sessions: const ActivitySessions(pmIn: '13:00', pmOut: '17:00'),
        program: a.program,
      );
      expect(fields['sessions'], {
        'pm': {'in': '13:00', 'out': '17:00'},
      });
    });

    test('auto-close and grace travel together when either changes', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields = updateFor(a, graceMinutes: 45, program: a.program);
      expect(fields, {'auto_close': true, 'grace_minutes': 45});
    });

    test('an unparsable stored date is never replaced by today', () {
      final json = newShapeActivity()..['activity_date'] = '0000-00-00';
      final a = Activity.fromJson(json);
      // The form still shows the stored string; leaving it alone sends none.
      final fields = updateFor(a, title: 'Renamed', program: a.program);
      expect(fields.containsKey('activity_date'), isFalse);
    });

    test('a picked date is sent', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields =
          updateFor(a, date: '2026-10-20', program: a.program);
      expect(fields['activity_date'], '2026-10-20');
    });

    test('a status flip is sent', () {
      final a = Activity.fromJson(newShapeActivity());
      final fields =
          updateFor(a, status: ActivityStatus.closed, program: a.program);
      expect(fields['status'], 'closed');
    });

    test('the reported wipe: old-shape row + one edit sends only that edit',
        () {
      // The old API shape had no sessions/auto_close/grace_minutes at all —
      // the form seeded blank controls and the old full-replace payload
      // destroyed the stored windows on save.
      final a = Activity.fromJson({
        'activity_id': 25,
        'title': 'General Convocation 2026',
        'activity_date': '2026-09-17',
        'start_at': '2026-09-17 16:00:00',
        'end_at': '2026-09-17 20:00:00',
        'start_time': '16:00:00',
        'end_time': '20:00:00',
        'location': 'Gym',
        'description': '',
        'program': '',
        'status': 'open',
        'is_open': true,
      });
      final fields = updateFor(a, title: 'General Convocation 2026 (edited)');
      expect(fields, {'title': 'General Convocation 2026 (edited)'});
    });
  });
}

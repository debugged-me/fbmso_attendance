import 'package:flutter_attendance/core/services/scan_ledger_service.dart';
import 'package:flutter_test/flutter_test.dart';

/// The device classifies a scan's session offline so it can spot a repeat
/// before the server ever sees it. These cases mirror
/// Activity_attendance_model::classify_session — if the two drift, an offline
/// scan lands in a different session than the server assigns at sync.
void main() {
  final sessions = <String, dynamic>{
    'am': {'in': '08:00', 'out': '12:00'},
    'pm': {'in': '13:00', 'out': '18:00'},
  };

  DateTime at(int hour, [int minute = 0]) =>
      DateTime(2026, 9, 22, hour, minute);

  String classify(DateTime when, [Map<String, dynamic>? windows]) =>
      ScanLedgerService.classifySession(windows ?? sessions, when);

  group('inside a defined window', () {
    test('mid-morning is the am session', () {
      expect(classify(at(9, 5)), 'am');
    });

    test('mid-afternoon is the pm session', () {
      expect(classify(at(14, 30)), 'pm');
    });

    test('the opening minute is inclusive', () {
      expect(classify(at(8, 0)), 'am');
      expect(classify(at(13, 0)), 'pm');
    });

    test('the closing minute is exclusive, like the server', () {
      // 12:00 is the am window's `out`, so it belongs to the next session.
      expect(classify(at(12, 0)), 'pm');
    });
  });

  group('outside every window, the nearest one wins', () {
    test('before the first session clamps forward to it', () {
      expect(classify(at(6, 30)), 'am');
    });

    test('after the last session clamps back to it', () {
      expect(classify(at(21, 0)), 'pm');
    });

    test('the midday gap falls to the upcoming session', () {
      expect(classify(at(12, 30)), 'pm');
    });
  });

  group('window configuration', () {
    test('no configured sessions falls back to am/pm defaults', () {
      expect(classify(at(9, 0), {}), 'am');
      expect(classify(at(15, 0), {}), 'pm');
    });

    test('an evening window is honoured when the activity defines one', () {
      final withEve = <String, dynamic>{
        'am': {'in': '08:00', 'out': '12:00'},
        'pm': {'in': '13:00', 'out': '17:00'},
        'eve': {'in': '18:00', 'out': '21:00'},
      };
      expect(classify(at(19, 15), withEve), 'eve');
    });

    test('an open-ended window accepts anything past its start', () {
      final openEnded = <String, dynamic>{
        'am': {'in': '08:00'},
      };
      expect(classify(at(23, 0), openEnded), 'am');
    });

    test('malformed entries are ignored rather than crashing', () {
      final broken = <String, dynamic>{
        'am': {'in': 'not-a-time', 'out': null},
        'pm': {'in': '13:00', 'out': '18:00'},
      };
      expect(classify(at(14, 0), broken), 'pm');
    });
  });

  test('a scan at the same clock time always classifies the same way', () {
    // This is what makes an offline scan survive a late sync: the session
    // comes from when it happened, not from when it was uploaded.
    expect(classify(at(9, 5)), classify(at(9, 5)));
    expect(classify(at(9, 5)), isNot(classify(at(18, 30))));
  });
}

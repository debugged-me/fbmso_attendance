import 'package:flutter_attendance/features/attendance/domain/attendance_models.dart';
import 'package:flutter_test/flutter_test.dart';

/// A cached activity list keeps the server's open/closed answer from when it
/// was fetched. Offline, that answer is re-judged on the phone's clock so an
/// activity that opened since does not stay unscannable.
void main() {
  Activity activity({
    bool isOpen = false,
    String state = 'scheduled',
    String status = 'open',
    bool autoClose = true,
    String? windowStart = '2026-10-05 07:45:00',
    String? windowEnd = '2026-10-05 17:15:00',
  }) =>
      Activity.fromJson({
        'activity_id': 25,
        'title': 'Leadership Summit',
        'is_open': isOpen,
        'state': state,
        'manual_status': status,
        'auto_close': autoClose,
        'window_start': windowStart,
        'window_end': windowEnd,
      });

  test('fetched before it opened, open once the window starts', () {
    final a = activity().recheckedAt(DateTime(2026, 10, 5, 8, 0));
    expect(a.isOpen, isTrue);
    expect(a.state, 'open');
    expect(a.closedReason, isNull);
  });

  test("still scheduled before the window, keeping the server's wording", () {
    final cached = Activity.fromJson({
      ...activity().toJson(),
      'closed_reason': 'Check-in for this activity opens on Oct 5, 2026 at 7:45 AM.',
    });
    final a = cached.recheckedAt(DateTime(2026, 10, 5, 7, 30));
    expect(a.isOpen, isFalse);
    expect(a.state, 'scheduled');
    expect(identical(a, cached), isTrue);
  });

  test('closing on the phone explains itself', () {
    final a = activity(isOpen: true, state: 'open')
        .recheckedAt(DateTime(2026, 10, 5, 7, 30));
    expect(a.state, 'scheduled');
    expect(a.closedReason, contains('opens on Oct 5, 2026 at 7:45 AM'));
  });

  test('ends after the window', () {
    final a = activity(isOpen: true, state: 'open')
        .recheckedAt(DateTime(2026, 10, 5, 17, 20));
    expect(a.isOpen, isFalse);
    expect(a.state, 'ended');
    expect(a.closedReason, contains('closed on Oct 5, 2026 at 5:15 PM'));
  });

  test('a manual close stands — only the server can lift it', () {
    for (final s in ['closed', 'draft', 'archived']) {
      final a = activity(state: s, status: s)
          .recheckedAt(DateTime(2026, 10, 5, 9, 0));
      expect(a.isOpen, isFalse, reason: s);
      expect(a.state, s);
    }
  });

  test('without auto-close the clock never closes it', () {
    final a = activity(isOpen: true, state: 'open', autoClose: false)
        .recheckedAt(DateTime(2027, 1, 1));
    expect(a.isOpen, isTrue);
  });

  test('an unbounded window stays open', () {
    final a = activity(windowStart: null, windowEnd: null)
        .recheckedAt(DateTime(2026, 10, 5, 9, 0));
    expect(a.isOpen, isTrue);
  });

  test('an unchanged answer returns the same object', () {
    final a = activity(isOpen: true, state: 'open');
    expect(identical(a.recheckedAt(DateTime(2026, 10, 5, 9, 0)), a), isTrue);
  });
}

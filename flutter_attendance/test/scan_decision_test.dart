import 'package:flutter_attendance/core/services/scan_ledger_service.dart';
import 'package:flutter_test/flutter_test.dart';

/// The phone predicts each scan's outcome before the server sees it. These
/// cases pin the prediction to consume_token's AUTO toggle, and check that
/// once the server has answered for an earlier scan, its answer — not the
/// phone's old guess — drives the next prediction.
void main() {
  final day = DateTime(2026, 10, 5);
  DateTime at(int h, int m, [int s = 0]) =>
      DateTime(day.year, day.month, day.day, h, m, s);

  int ms(DateTime t) => t.toUtc().millisecondsSinceEpoch;

  /// A ledger row as SQLite returns it.
  Map<String, dynamic> row(
    DateTime t,
    String localMode, {
    String session = 'am',
    bool synced = false,
    bool serverOk = true,
    String? serverMode,
  }) =>
      {
        'scanned_at_utc': ms(t),
        'local_mode': localMode,
        'local_session': session,
        'synced': synced ? 1 : 0,
        'server_ok': synced ? (serverOk ? 1 : 0) : null,
        'server_mode': serverMode,
      };

  LocalScanDecision decide(List<Map<String, dynamic>> newestFirst, DateTime now,
          {String session = 'am'}) =>
      ScanLedgerService.decide(
        history: newestFirst,
        session: session,
        now: now,
        clientScanId: 'x',
      );

  test('first scan of the day checks in', () {
    expect(decide([], at(7, 50)).mode, 'checked_in');
  });

  test('a later scan checks out; a re-read within 10 s is a duplicate', () {
    final history = [row(at(7, 50), 'checked_in')];
    expect(decide(history, at(7, 50, 8)).mode, 'duplicate');
    expect(decide(history, at(11, 40)).mode, 'checked_out');
  });

  test('a completed session repeats as a duplicate', () {
    final history = [
      row(at(11, 40), 'checked_out'),
      row(at(7, 50), 'checked_in'),
    ];
    expect(decide(history, at(11, 45)).mode, 'duplicate');
    expect(decide(history, at(13, 5), session: 'pm').mode, 'checked_in');
  });

  test('scans the server refused count for nothing', () {
    final history = [
      row(at(7, 50), 'checked_in', synced: true, serverOk: false, serverMode: 'stale_scan'),
    ];
    expect(decide(history, at(9, 0)).mode, 'checked_in',
        reason: 'the refused check-in must not make this one a check-out');
  });

  test("the server's answer overrides the phone's guess", () {
    // This phone guessed check-in, but the student had already checked in at
    // another phone, so the server recorded it as the check-out.
    final history = [
      row(at(11, 40), 'checked_in', synced: true, serverMode: 'checked_out'),
    ];
    expect(decide(history, at(11, 50)).mode, 'duplicate',
        reason: 'the morning session is complete on the server');
    expect(decide(history, at(13, 5), session: 'pm').mode, 'checked_in');
  });

  test('a server duplicate is not counted as a check-in', () {
    final history = [
      row(at(7, 50), 'checked_in', synced: true, serverMode: 'duplicate'),
    ];
    expect(decide(history, at(9, 0)).mode, 'checked_in');
  });

  test('local duplicates never count', () {
    final history = [row(at(7, 50), 'duplicate')];
    expect(decide(history, at(7, 51)).mode, 'checked_in');
  });

  test('previousAt reports the last scan that counted', () {
    final history = [row(at(7, 50), 'checked_in')];
    final d = decide(history, at(7, 50, 5));
    expect(d.isDuplicate, isTrue);
    expect(d.previousAt, at(7, 50));
  });

  group('a QR this phone cannot name', () {
    LocalScanDecision unverified(List<Map<String, dynamic>> h, DateTime now) =>
        ScanLedgerService.decideUnverified(
            history: h, session: 'am', now: now, clientScanId: 'x');

    test('is saved for the server to decide', () {
      expect(unverified([], at(8, 0)).mode, 'unverified');
    });

    test('only an immediate re-read of the same code is held back', () {
      final h = [row(at(8, 0), 'unverified')];
      expect(unverified(h, at(8, 0, 6)).mode, 'duplicate');
      expect(unverified(h, at(11, 40)).mode, 'unverified');
    });

    test('its hash is stable and never the raw token', () {
      const token = '0123456789abcdef0123456789abcdef';
      expect(ScanLedgerService.localHash(token), ScanLedgerService.localHash(token));
      expect(ScanLedgerService.localHash(token), isNot(contains(token)));
    });
  });
}

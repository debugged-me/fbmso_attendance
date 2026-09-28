import 'dart:convert';

import 'package:flutter_attendance/core/services/outbox_service.dart';
import 'package:flutter_test/flutter_test.dart';

/// What the outbox does with each kind of answer decides whether a queued
/// scan is dropped as done, kept for the user, parked for a sign-in, or sent
/// again. Getting one wrong either loses a scan or blocks the whole queue.
void main() {
  String json(Map<String, dynamic> m) => jsonEncode(m);

  group('classify', () {
    test('an accepted write is done', () {
      expect(OutboxService.classify(200, json({'ok': true, 'mode': 'checked_in'})),
          SendOutcome.done);
      expect(OutboxService.classify(200, json({'ok': true, 'mode': 'duplicate'})),
          SendOutcome.done);
    });

    test('a 200 saying ok:false is the server refusing it — kept, not dropped', () {
      expect(
          OutboxService.classify(
              200, json({'ok': false, 'mode': 'stale_scan', 'message': 'x'})),
          SendOutcome.rejected);
    });

    test('an HTML page with a 200 is not the API: send again', () {
      expect(OutboxService.classify(200, '<!doctype html><title>Wi-Fi login</title>'),
          SendOutcome.retry);
      expect(OutboxService.classify(200, 'not json'), SendOutcome.retry);
    });

    test('an empty 2xx is done', () {
      expect(OutboxService.classify(204, ''), SendOutcome.done);
    });

    test('401/403 wait for a sign-in', () {
      expect(OutboxService.classify(401, json({'ok': false})), SendOutcome.authFailed);
      expect(OutboxService.classify(403, json({'ok': false})), SendOutcome.authFailed);
    });

    test('a 4xx is set aside so it cannot block the queue forever', () {
      for (final code in [400, 404, 409, 410, 413, 422]) {
        expect(OutboxService.classify(code, '{}'), SendOutcome.rejected,
            reason: 'HTTP $code');
      }
    });

    test('timeouts, rate limits and server errors are retried', () {
      for (final code in [408, 429, 500, 502, 503, 504]) {
        expect(OutboxService.classify(code, '{}'), SendOutcome.retry,
            reason: 'HTTP $code');
      }
    });
  });

  group('readableError', () {
    test("prefers the server's own message", () {
      expect(
          OutboxService.readableError(
              200, json({'ok': false, 'message': 'Activity is closed'})),
          'Activity is closed');
    });

    test('names a web page instead of dumping it', () {
      expect(OutboxService.readableError(502, '<html><body>Bad gateway</body></html>'),
          'HTTP 502 — a web page answered instead of the server');
    });

    test('keeps short plain bodies and network errors readable', () {
      expect(OutboxService.readableError(500, 'boom'), 'HTTP 500: boom');
      expect(OutboxService.readableError(0, 'Request timed out'), 'Request timed out');
      expect(OutboxService.readableError(0, ''), 'No connection');
    });
  });
}

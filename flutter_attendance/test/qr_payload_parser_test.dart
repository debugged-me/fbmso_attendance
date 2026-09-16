import 'package:flutter_test/flutter_test.dart';

import 'package:flutter_attendance/features/attendance/domain/qr_payload_parser.dart';

void main() {
  const token = '0123456789abcdef0123456789abcdef';

  group('studentToken', () {
    test('accepts current and legacy payload wrappers', () {
      expect(QrPayloadParser.studentToken(token), token);
      expect(QrPayloadParser.studentToken('22|$token'), token);
      expect(
        QrPayloadParser.studentToken(
          'https://school.test/student-card?token=$token',
        ),
        token,
      );
      expect(QrPayloadParser.studentToken('{"qr_token":"$token"}'), token);
      expect(QrPayloadParser.studentToken('\uFEFF  $token\n'), token);
    });

    test('does not accept a fragment of a malformed token', () {
      expect(QrPayloadParser.studentToken('f${token}f'), isEmpty);
      expect(QrPayloadParser.studentToken('attendance/checkin/22'), isEmpty);
    });
  });

  group('activityId', () {
    test('accepts poster URLs across deployment layouts', () {
      expect(
        QrPayloadParser.activityId(
          'https://school.test/fbmso_attendance/attendance/checkin/22',
        ),
        22,
      );
      expect(
        QrPayloadParser.activityId(
          'https://school.test/index.php/attendance/checkin/22/',
        ),
        22,
      );
      expect(QrPayloadParser.activityId('?activity_id=22'), 22);
      expect(QrPayloadParser.activityId('{"activity_id":22}'), 22);
      expect(QrPayloadParser.activityId('activity|22'), 22);
      expect(QrPayloadParser.activityId('22'), 22);
    });

    test('rejects unrelated QR payloads', () {
      expect(QrPayloadParser.activityId(token), isNull);
      expect(QrPayloadParser.activityId('https://school.test/news/22'), isNull);
    });
  });
}

import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:flutter_attendance/features/attendance/domain/qr_payload_parser.dart';
import 'package:flutter_test/flutter_test.dart';

/// The offline roster only works if the device hashes a scanned QR exactly the
/// way the server hashed it when building the snapshot. A drift in either
/// normalisation or hashing makes every offline scan read as "not on roster",
/// so these expectations are generated from the real PHP implementation
/// (attendance_normalize_student_qr + MobileRoster::salt_for) and pinned here.
void main() {
  // substr(hash_hmac('sha256', 'roster:abc123def4567890', <encryption_key>), 0, 32)
  const salt = '51896d8a9a1bfef501beff990a2cc8f9';
  const token = 'cf88d86925bf7fa9bd78938adc180946';
  const expectedHash =
      'bba45c0c0c2288e64c6296e719c5f4177ae4cb4f3a4ce31c100e0b59c4d6f1f3';

  String hashOf(String rawQr) {
    final normalized = QrPayloadParser.studentToken(rawQr);
    if (normalized.isEmpty) return '';
    return sha256.convert(utf8.encode('$salt$normalized')).toString();
  }

  group('QR payload normalisation matches the PHP server', () {
    const payloads = <String, String>{
      'bare token': token,
      'uppercase': 'CF88D86925BF7FA9BD78938ADC180946',
      'padded with whitespace': '  $token  ',
      'JSON wrapper': '{"token":"$token"}',
      'full URL with query': 'https://school.edu/attendance?token=$token',
      'legacy activity|token': '15|$token',
      'labelled/printed payload': 'STUDENT-QR:$token',
    };

    payloads.forEach((label, payload) {
      test('$label normalises to the stored token', () {
        expect(QrPayloadParser.studentToken(payload), token);
      });

      test('$label hashes to the server value', () {
        expect(hashOf(payload), expectedHash);
      });
    });
  });

  test('a non-student QR yields no token, so it cannot match the roster', () {
    expect(QrPayloadParser.studentToken('https://example.com/not-a-qr'), '');
    expect(QrPayloadParser.studentToken(''), '');
  });

  test('a different token produces a different hash', () {
    final other = sha256
        .convert(utf8.encode('${salt}00000000000000000000000000000000'))
        .toString();
    expect(other, isNot(expectedHash));
  });

  test('the same token under a different salt does not match', () {
    final underOtherSalt =
        sha256.convert(utf8.encode('deadbeef$token')).toString();
    expect(underOtherSalt, isNot(expectedHash));
  });
}

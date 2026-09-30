import 'package:flutter_test/flutter_test.dart';

import 'package:flutter_attendance/core/services/update_service.dart';

void main() {
  group('UpdateService.verdictFor', () {
    test('nothing when the server advertises no build', () {
      expect(
          UpdateService.verdictFor(
              installed: 1, latest: 0, minCode: 0, dismissedCode: 0),
          UpdateVerdict.none);
    });

    test('nothing when the installed build is current or newer', () {
      expect(
          UpdateService.verdictFor(
              installed: 2, latest: 2, minCode: 0, dismissedCode: 0),
          UpdateVerdict.none);
      expect(
          UpdateService.verdictFor(
              installed: 3, latest: 2, minCode: 0, dismissedCode: 0),
          UpdateVerdict.none);
    });

    test('available when the installed build is behind', () {
      expect(
          UpdateService.verdictFor(
              installed: 1, latest: 2, minCode: 0, dismissedCode: 0),
          UpdateVerdict.available);
    });

    test('a dismissed build stays quiet until a newer one appears', () {
      expect(
          UpdateService.verdictFor(
              installed: 1, latest: 2, minCode: 0, dismissedCode: 2),
          UpdateVerdict.none);
      expect(
          UpdateService.verdictFor(
              installed: 1, latest: 3, minCode: 0, dismissedCode: 2),
          UpdateVerdict.available);
    });

    test('below the minimum is required, even after dismissal', () {
      expect(
          UpdateService.verdictFor(
              installed: 1, latest: 3, minCode: 2, dismissedCode: 3),
          UpdateVerdict.required);
    });
  });
}

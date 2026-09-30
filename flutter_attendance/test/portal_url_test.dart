import 'package:flutter_attendance/core/network/api_exception.dart';
import 'package:flutter_attendance/features/auth/data/auth_api.dart';
import 'package:flutter_attendance/features/auth/presentation/auth_controller.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final api = AuthApi();

  group('normalizeBaseUrl', () {
    test('local addresses default to http', () {
      expect(api.normalizeBaseUrl('localhost/fbmso_attendance'),
          'http://localhost/fbmso_attendance');
      expect(api.normalizeBaseUrl('192.168.1.10/fbmso_attendance/'),
          'http://192.168.1.10/fbmso_attendance');
      expect(api.normalizeBaseUrl('10.0.2.2:8080'), 'http://10.0.2.2:8080');
      expect(api.normalizeBaseUrl('office-pc/fbmso'), 'http://office-pc/fbmso');
      expect(api.normalizeBaseUrl('school.local'), 'http://school.local');
      expect(api.normalizeBaseUrl('[::1]:8000/app'), 'http://[::1]:8000/app');
    });

    test('public hosts default to https', () {
      expect(api.normalizeBaseUrl('fbmso.srmsportal.com'),
          'https://fbmso.srmsportal.com');
      expect(api.normalizeBaseUrl('8.8.8.8/portal'), 'https://8.8.8.8/portal');
      expect(api.normalizeBaseUrl('172.32.0.1'), 'https://172.32.0.1');
    });

    test('an explicit scheme is kept', () {
      expect(api.normalizeBaseUrl('https://localhost/app'),
          'https://localhost/app');
      expect(api.normalizeBaseUrl('HTTP://localhost/app'),
          'HTTP://localhost/app');
      expect(api.normalizeBaseUrl('  '), '');
    });

    test('query and fragment from a pasted page URL are dropped', () {
      expect(
          api.normalizeBaseUrl(
              'https://fbmso.softtechservices.net/login?next=securitycheck%2Fkey'),
          'https://fbmso.softtechservices.net/login');
      expect(api.normalizeBaseUrl('https://a.b/app#section'), 'https://a.b/app');
      expect(api.normalizeBaseUrl('https://a.b/?next=/x'), 'https://a.b');
    });
  });

  group('portalCandidates', () {
    test('climbs from the pasted page up to the host root', () {
      expect(
          api.portalCandidates('https://a.b/fbmso_attendance/login'),
          ['https://a.b/fbmso_attendance/login',
           'https://a.b/fbmso_attendance',
           'https://a.b']);
      expect(api.portalCandidates('https://a.b/login?next=x'.split('?').first),
          ['https://a.b/login', 'https://a.b']);
      expect(api.portalCandidates('http://192.168.1.10:8080/app'),
          ['http://192.168.1.10:8080/app', 'http://192.168.1.10:8080']);
    });

    test('a bare host is its own only candidate', () {
      expect(api.portalCandidates('https://a.b'), ['https://a.b']);
    });
  });

  group('checkPortalUrl', () {
    test('http is only accepted on the local network', () {
      expect(api.checkPortalUrl('http://localhost/fbmso_attendance'), isNull);
      expect(api.checkPortalUrl('http://192.168.0.5/fbmso_attendance'), isNull);
      expect(api.checkPortalUrl('https://fbmso.srmsportal.com'), isNull);
      expect(api.checkPortalUrl('http://fbmso.srmsportal.com'),
          contains('https://'));
    });

    test('rejects something that is not an address', () {
      expect(api.checkPortalUrl('https://'), isNotNull);
    });
  });

  group('connectErrorMessage', () {
    test('explains localhost on a phone', () {
      final msg = AuthController.connectErrorMessage(
          'http://localhost/fbmso_attendance', ApiException('x', statusCode: 0),
          isWeb: false);
      expect(msg, contains('Wi-Fi IP'));
    });

    test('a wrong folder reads as a missing portal', () {
      final msg = AuthController.connectErrorMessage(
          'http://localhost',
          ApiException('Server returned an HTML page instead of JSON.',
              statusCode: 404),
          isWeb: true);
      expect(msg, contains('No attendance portal'));
    });

    test('server messages pass through', () {
      expect(
          AuthController.connectErrorMessage(
              'https://a.b', ApiException('Maintenance', statusCode: 503),
              isWeb: false),
          'Maintenance');
    });
  });
}

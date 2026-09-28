import 'dart:convert';
import 'dart:io';

import 'package:flutter_attendance/features/auth/data/auth_api.dart';
import 'package:flutter_attendance/features/auth/data/session_store.dart';
import 'package:flutter_attendance/features/auth/presentation/auth_controller.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A scanner phone restarted at a venue with no signal must come back signed
/// in: signing in needs the server. Only the server refusing the token may
/// sign the user out on start-up.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const baseUrl = 'http://localhost/fbmso_attendance';
  final savedSession = {
    'baseUrl': baseUrl,
    'token': 'saved-token',
    'username': 'committee01',
    'firstName': 'Ana',
    'position': 'Committee',
  };

  Future<AuthController> boot(http.Client client, {bool online = true}) async {
    SharedPreferences.setMockInitialValues({
      'app_base_url': baseUrl,
      'app_session': jsonEncode(savedSession),
      'app_is_paired': true,
    });
    final prefs = await SharedPreferences.getInstance();
    final controller = AuthController(
      api: AuthApi(client: client),
      store: SessionStore(prefs),
      isOnline: () async => online,
    );
    await controller.bootstrap();
    return controller;
  }

  MockClient answering(int meStatus, String meBody) => MockClient((req) async {
        if (req.url.path.endsWith('/api/mobile/config')) {
          return http.Response(jsonEncode({'ok': true, 'school_name': 'FBMSO'}), 200);
        }
        return http.Response(meBody, meStatus,
            headers: {'content-type': 'application/json'});
      });

  test('no network at all: signed in with the saved session, no request made',
      () async {
    var requests = 0;
    final c = await boot(MockClient((_) async {
      requests++;
      return http.Response('', 500);
    }), online: false);
    expect(c.isAuthenticated, isTrue);
    expect(c.session!.token, 'saved-token');
    expect(requests, 0);
  });

  test('network up but the server unreachable: stays signed in', () async {
    final c = await boot(MockClient((_) async {
      throw const SocketException('Failed host lookup');
    }));
    expect(c.isAuthenticated, isTrue);
    expect(c.bootstrapping, isFalse);
  });

  test('a captive portal page instead of the API: stays signed in', () async {
    final c = await boot(answering(200, '<html>Sign in to Wi-Fi</html>'));
    expect(c.isAuthenticated, isTrue);
  });

  test('a server error: stays signed in', () async {
    final c = await boot(answering(503, jsonEncode({'ok': false})));
    expect(c.isAuthenticated, isTrue);
  });

  test('the server refuses the token (401): signed out', () async {
    final c = await boot(answering(
        401, jsonEncode({'ok': false, 'message': 'Invalid or expired token.'})));
    expect(c.isAuthenticated, isFalse);
    expect(c.baseUrl, baseUrl, reason: 'the school pairing is kept');
  });

  test('an inactive account (403): signed out', () async {
    final c = await boot(answering(403, jsonEncode({'ok': false})));
    expect(c.isAuthenticated, isFalse);
  });

  test('the server confirms the token: refreshed session', () async {
    final c = await boot(answering(
        200,
        jsonEncode({
          'ok': true,
          'school_name': 'FBMSO',
          'user': {'username': 'committee01', 'position': 'Committee'},
        })));
    expect(c.isAuthenticated, isTrue);
    expect(c.session!.token, 'saved-token');
    expect(c.session!.schoolName, 'FBMSO');
  });
}

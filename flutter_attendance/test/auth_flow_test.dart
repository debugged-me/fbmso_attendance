import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:flutter_attendance/features/auth/data/auth_api.dart';
import 'package:flutter_attendance/features/auth/data/session_store.dart';
import 'package:flutter_attendance/features/auth/presentation/auth_controller.dart';
import 'package:flutter_attendance/features/auth/presentation/login_screen.dart';
import 'package:flutter_attendance/features/auth/presentation/welcome_screen.dart';

/// Regression tests for the sign-in navigation bug: entering the school URL
/// used to `pushReplacement` the login screen over the root route, which
/// detached the tree from the AuthController. Login then succeeded and
/// persisted, but nothing was listening, so the screen never changed — the
/// user only landed inside the app after killing and reopening it.
///
/// These tests drive the real screens through the real controller.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const configBody = {
    'ok': true,
    'school_name': 'Test College',
    'active_sy': '2026-2027',
    'active_sem': '1st',
    'allow_signup': 'Yes',
  };

  const loginBody = {
    'ok': true,
    'token': 'test-token-123',
    'school_name': 'Test College',
    'active_sy': '2026-2027',
    'active_sem': '1st',
    'user': {
      'username': 'student01',
      'id_number': '2026-0001',
      'first_name': 'Ada',
      'last_name': 'Reyes',
      'position': 'Student',
    },
  };

  /// Stands in for the server: /config and /auth/login both succeed.
  MockClient happyServer({List<String>? hits}) {
    return MockClient((request) async {
      hits?.add(request.url.path);
      if (request.url.path.endsWith('/api/mobile/config')) {
        return http.Response(jsonEncode(configBody), 200,
            headers: {'content-type': 'application/json'});
      }
      if (request.url.path.endsWith('/api/mobile/auth/login')) {
        return http.Response(jsonEncode(loginBody), 200,
            headers: {'content-type': 'application/json'});
      }
      if (request.url.path.endsWith('/api/mobile/auth/logout')) {
        return http.Response(jsonEncode({'ok': true}), 200,
            headers: {'content-type': 'application/json'});
      }
      return http.Response(jsonEncode({'ok': false, 'message': 'unexpected'}), 404,
          headers: {'content-type': 'application/json'});
    });
  }

  Future<AuthController> buildController(http.Client client) async {
    SharedPreferences.setMockInitialValues({});
    final prefs = await SharedPreferences.getInstance();
    final controller = AuthController(
      api: AuthApi(client: client),
      store: SessionStore(prefs),
    );
    await controller.bootstrap();
    return controller;
  }

  /// The same declarative root that app.dart uses.
  Widget rootFlow(AuthController controller) {
    return MaterialApp(
      home: ListenableBuilder(
        listenable: controller,
        builder: (context, _) {
          if (controller.isAuthenticated) {
            return const Scaffold(body: Center(child: Text('SHELL')));
          }
          if (controller.baseUrl.isNotEmpty) {
            return LoginScreen(controller: controller);
          }
          return WelcomeScreen(controller: controller);
        },
      ),
    );
  }

  testWidgets('URL entry hands off to login without replacing the root route',
      (tester) async {
    final controller = await buildController(happyServer());
    await tester.pumpWidget(rootFlow(controller));
    await tester.pumpAndSettle();

    expect(find.byType(WelcomeScreen), findsOneWidget,
        reason: 'fresh install starts at URL entry');

    await tester.enterText(
        find.byType(TextField).first, 'http://localhost/fbmso_attendance');
    await tester.testTextInput.receiveAction(TextInputAction.done);
    await tester.pumpAndSettle();

    expect(find.byType(LoginScreen), findsOneWidget,
        reason: 'controller state alone should swap the screen');

    // The whole point: the root is still the only route on the stack, so it is
    // still listening to the controller.
    expect(tester.widget<Navigator>(find.byType(Navigator).first), isNotNull);
    expect(find.byType(WelcomeScreen), findsNothing);
  });

  testWidgets('successful sign-in swaps to the shell without a restart',
      (tester) async {
    final controller = await buildController(happyServer());
    await tester.pumpWidget(rootFlow(controller));
    await tester.pumpAndSettle();

    // Pair with the school.
    await tester.enterText(
        find.byType(TextField).first, 'http://localhost/fbmso_attendance');
    await tester.testTextInput.receiveAction(TextInputAction.done);
    await tester.pumpAndSettle();
    expect(find.byType(LoginScreen), findsOneWidget);

    // Sign in.
    final fields = find.byType(TextField);
    await tester.enterText(fields.at(0), 'student01');
    await tester.enterText(fields.at(1), 'secret');
    await tester.tap(find.text('Sign in'));
    await tester.pumpAndSettle();

    // This is the assertion that failed before the fix.
    expect(find.text('SHELL'), findsOneWidget,
        reason: 'login must land in the app immediately, not after a restart');
    expect(find.byType(LoginScreen), findsNothing);
    expect(controller.isAuthenticated, isTrue);
  });

  testWidgets('Switch School unpairs and returns to URL entry', (tester) async {
    final controller = await buildController(happyServer());
    await tester.pumpWidget(rootFlow(controller));
    await tester.pumpAndSettle();

    await tester.enterText(
        find.byType(TextField).first, 'http://localhost/fbmso_attendance');
    await tester.testTextInput.receiveAction(TextInputAction.done);
    await tester.pumpAndSettle();
    expect(find.byType(LoginScreen), findsOneWidget);

    // The link sits below the fold on a phone-sized test surface.
    await tester.ensureVisible(find.text('Switch School'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Switch School'));
    await tester.pumpAndSettle();

    expect(find.byType(WelcomeScreen), findsOneWidget);
    expect(controller.baseUrl, isEmpty, reason: 'pairing must be forgotten');
    expect(controller.config, isNull);
  });

  testWidgets('a paired user still reaches login when /config is unreachable',
      (tester) async {
    // Config probe fails; login still works. Mirrors being offline on cold start.
    final flaky = MockClient((request) async {
      if (request.url.path.endsWith('/api/mobile/config')) {
        return http.Response('gateway down', 502);
      }
      return http.Response(jsonEncode(loginBody), 200,
          headers: {'content-type': 'application/json'});
    });

    SharedPreferences.setMockInitialValues({
      'app_base_url': 'http://localhost/fbmso_attendance',
    });
    final prefs = await SharedPreferences.getInstance();
    final controller = AuthController(
      api: AuthApi(client: flaky),
      store: SessionStore(prefs),
    );
    await controller.bootstrap();

    await tester.pumpWidget(rootFlow(controller));
    await tester.pumpAndSettle();

    expect(find.byType(LoginScreen), findsOneWidget,
        reason: 'a saved school should not be forgotten just because the '
            'config probe failed');
  });
}

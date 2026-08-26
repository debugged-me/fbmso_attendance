import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/design/tokens/app_brand.dart';
import '../core/services/biometric_service.dart';
import '../core/services/notification_service.dart';
import '../core/theme/app_theme.dart';
import '../features/auth/data/auth_api.dart';
import '../features/auth/data/session_store.dart';
import '../features/auth/domain/app_session.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/welcome_screen.dart';
import '../features/misc/data/misc_api.dart';
import '../features/shell/presentation/admin_shell.dart';
import '../features/shell/presentation/student_shell.dart';

class FlutterAttendanceApp extends StatefulWidget {
  const FlutterAttendanceApp({super.key});

  @override
  State<FlutterAttendanceApp> createState() => _FlutterAttendanceAppState();
}

class _FlutterAttendanceAppState extends State<FlutterAttendanceApp> {
  late final Future<({AuthController controller, bool biometricOk})>
      _initFuture = _init();

  Future<({AuthController controller, bool biometricOk})> _init() async {
    final preferences = await SharedPreferences.getInstance();
    final controller = AuthController(
      api: AuthApi(),
      store: SessionStore(preferences),
    );
    await controller.bootstrap();

    // Init in-app notifications.
    await NotificationService.instance.init();

    // If the user has a saved session and biometric is enabled, gate
    // the app behind biometrics before showing any data.
    bool biometricOk = true;
    if (controller.isAuthenticated) {
      biometricOk = await BiometricService.gate();
      if (!biometricOk) {
        // User cancelled — sign them out so they see the login screen.
        await controller.logout();
        biometricOk = true; // proceed to login, not a hard block
      }
    }

    return (controller: controller, biometricOk: biometricOk);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: AppBrand.name,
      theme: AppTheme.build(),
      builder: (context, child) => MediaQuery.withClampedTextScaling(
        minScaleFactor: 0.85,
        maxScaleFactor: 1.15,
        child: child ?? const SizedBox.shrink(),
      ),
      home: FutureBuilder<({AuthController controller, bool biometricOk})>(
        future: _initFuture,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return _ErrorScreen(message: snapshot.error.toString());
          }
          if (!snapshot.hasData) {
            return const _SplashScreen();
          }
          return _AuthFlow(controller: snapshot.data!.controller);
        },
      ),
    );
  }
}

class _AuthFlow extends StatelessWidget {
  const _AuthFlow({required this.controller});
  final AuthController controller;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) {
        // Still bootstrapping → splash.
        if (controller.bootstrapping) {
          return const _SplashScreen();
        }

        // Authenticated → role-based shell.
        if (controller.isAuthenticated && controller.session != null) {
          return _RoleShell(
            session: controller.session as AppSession,
            controller: controller,
          );
        }

        // Paired with a school URL but not signed in → login.
        if (controller.baseUrl.isNotEmpty && controller.config != null) {
          return LoginScreen(controller: controller);
        }

        // First run / unpaired → URL entry.
        return WelcomeScreen(controller: controller);
      },
    );
  }
}

/// Picks the shell based on the user's role bucket.
/// Only two roles exist in the system: Student and Admin (which includes
/// Super Admin). Everything else falls back to Admin.
class _RoleShell extends StatefulWidget {
  const _RoleShell({required this.session, required this.controller});
  final AppSession session;
  final AuthController controller;

  @override
  State<_RoleShell> createState() => _RoleShellState();
}

class _RoleShellState extends State<_RoleShell> {
  @override
  void initState() {
    super.initState();
    // Start active announcement polling for in-app notifications.
    NotificationService.instance.startAnnouncementPolling(
      fetchAnnouncements: _fetchAnnouncements,
      interval: const Duration(minutes: 2),
    );
  }

  @override
  void dispose() {
    NotificationService.instance.stopAnnouncementPolling();
    super.dispose();
  }

  Future<List<Map<String, dynamic>>> _fetchAnnouncements() async {
    try {
      final api = MiscApi();
      final list = await api.announcements(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      return list.map((a) => {
            'id': a.id,
            'title': a.title,
            'message': a.message,
          }).toList();
    } catch (_) {
      return [];
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.session.role.isStudentLike) {
      return StudentShell(session: widget.session, controller: widget.controller);
    }
    // Admin, Super Admin, and any other non-student role → AdminShell.
    return AdminShell(session: widget.session, controller: widget.controller);
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/img/icon-logo.png',
              width: 120,
              height: 120,
              fit: BoxFit.contain,
            ),
            const SizedBox(height: 24),
            const Text(
              AppBrand.name,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w700,
                color: AppTheme.textDark,
              ),
            ),
            const SizedBox(height: 24),
            const SizedBox(
              width: 28,
              height: 28,
              child: CircularProgressIndicator(strokeWidth: 2.5),
            ),
          ],
        ),
      ),
    );
  }
}

class _ErrorScreen extends StatelessWidget {
  const _ErrorScreen({required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text('Failed to start: $message',
              textAlign: TextAlign.center),
        ),
      ),
    );
  }
}

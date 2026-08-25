import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/services/biometric_service.dart';
import '../core/services/notification_service.dart';
import '../core/theme/app_theme.dart';
import '../features/auth/data/auth_api.dart';
import '../features/auth/data/session_store.dart';
import '../features/auth/domain/app_session.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/welcome_screen.dart';
import '../features/shell/presentation/instructor_shell.dart';
import '../features/shell/presentation/staff_shell.dart';
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
      title: 'FBMSO Attendance',
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
          return AnimatedBuilder(
            animation: snapshot.data!.controller,
            builder: (context, _) {
              final c = snapshot.data!.controller;
              if (c.bootstrapping) {
                return const _SplashScreen();
              }
              return _AuthFlow(controller: c);
            },
          );
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
  }
}

/// Picks the shell based on the user's role bucket.
class _RoleShell extends StatelessWidget {
  const _RoleShell({required this.session, required this.controller});
  final AppSession session;
  final AuthController controller;

  @override
  Widget build(BuildContext context) {
    final role = session.role;
    if (role.isStudentLike) {
      return StudentShell(session: session, controller: controller);
    }
    if (role.isInstructorLike) {
      return InstructorShell(session: session, controller: controller);
    }
    // Admin, registrar, accounting, HR, guidance, medical, librarian,
    // custodian, encoder, IT, academic officer, staff, unknown → StaffShell.
    return StaffShell(session: session, controller: controller);
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
              'FBMSO Attendance',
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

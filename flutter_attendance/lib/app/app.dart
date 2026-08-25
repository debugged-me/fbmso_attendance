import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/theme/app_theme.dart';
import '../features/auth/data/auth_api.dart';
import '../features/auth/data/session_store.dart';
import '../features/auth/domain/app_session.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/welcome_screen.dart';
import '../features/home/presentation/home_shell.dart';

class FlutterAttendanceApp extends StatefulWidget {
  const FlutterAttendanceApp({super.key});

  @override
  State<FlutterAttendanceApp> createState() => _FlutterAttendanceAppState();
}

class _FlutterAttendanceAppState extends State<FlutterAttendanceApp> {
  late final Future<AuthController> _controllerFuture = _createController();

  Future<AuthController> _createController() async {
    final preferences = await SharedPreferences.getInstance();
    final controller = AuthController(
      api: AuthApi(),
      store: SessionStore(preferences),
    );
    await controller.bootstrap();
    return controller;
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
      home: FutureBuilder<AuthController>(
        future: _controllerFuture,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return _ErrorScreen(message: snapshot.error.toString());
          }
          if (!snapshot.hasData) {
            return const _SplashScreen();
          }
          return AnimatedBuilder(
            animation: snapshot.data!,
            builder: (context, _) {
              final c = snapshot.data!;
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
    // Authenticated → role shell (Phase 2: temporary HomeShell).
    if (controller.isAuthenticated && controller.session != null) {
      return HomeShell(
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

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(child: CircularProgressIndicator()),
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

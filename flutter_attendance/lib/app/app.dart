import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/design/components/components.dart';
import '../core/design/tokens/app_brand.dart';
import '../core/design/tokens/app_tokens.dart';
import '../core/services/biometric_service.dart';
import '../core/services/notification_service.dart';
import '../core/theme/app_theme.dart';
import '../core/widgets/update_gate.dart';
import '../features/auth/data/auth_api.dart';
import '../features/auth/data/session_store.dart';
import '../features/auth/domain/app_session.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/change_password_screen.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/welcome_screen.dart';
import '../features/misc/data/misc_api.dart';
import '../features/shell/presentation/admin_shell.dart';
import '../features/shell/presentation/student_shell.dart';
import '../core/theme/app_icons.dart';

class FlutterAttendanceApp extends StatefulWidget {
  const FlutterAttendanceApp({super.key});

  @override
  State<FlutterAttendanceApp> createState() => _FlutterAttendanceAppState();
}

class _FlutterAttendanceAppState extends State<FlutterAttendanceApp> {
  late final Future<({AuthController controller, bool biometricOk})>
      _initFuture = _init();

  /// Set once the user gets past the lock screen (unlocked or signed out).
  bool _unlocked = false;

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
    // the app behind biometrics before showing any data. A cancelled prompt
    // leaves the app locked rather than signing out: signing back in needs
    // the server, which a phone at a venue with no signal cannot reach.
    bool biometricOk = true;
    if (controller.isAuthenticated) {
      final schoolName = (controller.config?.schoolName ?? '').trim();
      biometricOk = await BiometricService.gate(schoolName: schoolName);
    }

    return (controller: controller, biometricOk: biometricOk);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: AppBrand.name,
      theme: AppTheme.build(),
      builder: (context, child) => UpdateGate(
        child: MediaQuery.withClampedTextScaling(
          minScaleFactor: 0.85,
          maxScaleFactor: 1.15,
          child: child ?? const SizedBox.shrink(),
        ),
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
          final controller = snapshot.data!.controller;
          // Use the connected school's name once /config resolves; before
          // that the generic [AppBrand.name] fallback is shown.
          final schoolName = (controller.config?.schoolName ?? '').trim();
          if (!snapshot.data!.biometricOk && !_unlocked) {
            return _LockedScreen(
              schoolName: schoolName,
              onUnlock: () async {
                if (await BiometricService.gate(schoolName: schoolName) &&
                    mounted) {
                  setState(() => _unlocked = true);
                }
              },
              onSignOut: () async {
                await controller.logout();
                if (mounted) setState(() => _unlocked = true);
              },
            );
          }
          return _AuthFlow(
            controller: controller,
            schoolName: schoolName,
          );
        },
      ),
    );
  }
}

class _AuthFlow extends StatelessWidget {
  const _AuthFlow({required this.controller, this.schoolName = ''});
  final AuthController controller;
  final String schoolName;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) {
        // Still bootstrapping → splash.
        if (controller.bootstrapping) {
          return _SplashScreen(schoolName: schoolName);
        }

        // Authenticated → role-based shell.
        if (controller.isAuthenticated && controller.session != null) {
          return _RoleShell(
            session: controller.session as AppSession,
            controller: controller,
          );
        }

        // Paired with a school URL but not signed in → login.
        // config may be null when the /config probe failed (e.g. offline on a
        // cold start); the login screen handles that and falls back to the
        // bundled logo. "Switch School" clears the pairing if the URL is wrong.
        if (controller.baseUrl.isNotEmpty) {
          return LoginScreen(controller: controller);
        }

        // First run / unpaired → URL entry.
        return WelcomeScreen(controller: controller);
      },
    );
  }
}

/// Picks the shell based on the user's role bucket.
/// Students use their dedicated shell. Auditor and the other staff roles use
/// the staff shell, whose permission matrix removes disallowed actions.
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
    // Mirrors the web authguard: an account flagged force_change_password
    // may only reach the change-password screen until it sets a new one.
    // The API revokes all tokens on change, so success means re-login.
    if (widget.session.forceChangePassword) {
      return ChangePasswordScreen(
        session: widget.session,
        onSuccess: () => widget.controller.logout(),
      );
    }
    if (widget.session.role.isStudentLike) {
      return StudentShell(session: widget.session, controller: widget.controller);
    }
    // Auditor and all other non-student roles use the permission-aware shell.
    return AdminShell(session: widget.session, controller: widget.controller);
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen({this.schoolName = ''});
  final String schoolName;

  @override
  Widget build(BuildContext context) {
    final name = schoolName.trim().isEmpty ? AppBrand.name : schoolName;
    return Scaffold(
      backgroundColor: AppInk.page,
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const AppBrandMark(size: 88),
              const SizedBox(height: 24),
              Text(
                name,
                textAlign: TextAlign.center,
                style: AppType.headline.copyWith(fontSize: 19),
              ),
              const SizedBox(height: 20),
              const SizedBox(
                width: 120,
                child: ClipRRect(
                  borderRadius: BorderRadius.all(Radius.circular(99)),
                  child: LinearProgressIndicator(minHeight: 4),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Shown when the biometric prompt at start-up was cancelled or failed. The
/// session and any queued scans stay on the device until the user unlocks.
class _LockedScreen extends StatelessWidget {
  const _LockedScreen({
    required this.schoolName,
    required this.onUnlock,
    required this.onSignOut,
  });
  final String schoolName;
  final Future<void> Function() onUnlock;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    final name = schoolName.trim().isEmpty ? AppBrand.name : schoolName;
    return Scaffold(
      backgroundColor: AppInk.page,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 24, 24, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              const Center(child: AppBrandMark(size: 72)),
              const SizedBox(height: 24),
              Text(
                'Locked',
                textAlign: TextAlign.center,
                style: AppType.title,
              ),
              const SizedBox(height: 8),
              Text(
                'Unlock $name to continue. Scans waiting to sync stay on '
                'this phone.',
                textAlign: TextAlign.center,
                style: AppType.body.copyWith(color: AppInk.muted),
              ),
              const Spacer(),
              AppButton(
                label: 'Unlock',
                icon: AppIcons.fingerprint_rounded,
                fullWidth: true,
                size: AppButtonSize.lg,
                onTap: onUnlock,
              ),
              const SizedBox(height: 8),
              AppButton(
                label: 'Sign out',
                style: AppButtonStyle.ghost,
                fullWidth: true,
                onTap: onSignOut,
              ),
            ],
          ),
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
      backgroundColor: AppInk.page,
      body: Center(
        child: AppEmptyState(
          icon: AppIcons.warning_amber_rounded,
          tone: AppInk.critical,
          title: 'The app could not start',
          subtitle: message,
        ),
      ),
    );
  }
}

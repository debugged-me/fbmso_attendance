import 'package:flutter/material.dart';

import 'features/attendance/presentation/scan_screen.dart';
import 'features/auth/domain/app_session.dart';

/// Debug-only entrypoint: mounts the real ScanScreen immediately so the
/// camera path can be exercised on a device without logging in.
///
///   `flutter run -t lib/main_scantest.dart -d DEVICE`
void main() {
  const session = AppSession(
    baseUrl: 'http://localhost/fbmso_attendance',
    token: 'debug',
    schoolName: 'Debug',
    username: 'debug',
    idNumber: '0',
    firstName: 'Debug',
    middleName: '',
    lastName: '',
    email: '',
    avatar: '',
    position: 'Admin',
    activeSy: '',
    activeSem: '',
  );
  runApp(const MaterialApp(
    home: ScanScreen(
      session: session,
      activityId: 1,
      activityTitle: 'Scan smoke test',
    ),
  ));
}

import 'package:flutter/material.dart';

import 'app/app.dart';
import 'core/services/connectivity_service.dart';
import 'core/services/outbox_service.dart';
import 'core/services/sync_orchestrator.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Offline infrastructure: connectivity monitor + SQLite outbox + sync
  // orchestrator. Initialized before runApp so the first frame can show an
  // accurate offline/synced banner.
  ConnectivityService.initialize();
  await OutboxService.initialize();
  await SyncOrchestrator.instance.start();

  runApp(const FlutterAttendanceApp());
}

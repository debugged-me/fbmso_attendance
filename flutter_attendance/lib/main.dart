import 'package:flutter/material.dart';

import 'app/app.dart';
import 'core/services/connectivity_service.dart';
import 'core/services/outbox_service.dart';
import 'core/services/scan_ledger_service.dart';
import 'core/services/sync_orchestrator.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Offline infrastructure: connectivity monitor + SQLite outbox + sync
  // orchestrator. Initialized before runApp so the first frame can show an
  // accurate offline/synced banner.
  ConnectivityService.initialize();
  await OutboxService.initialize();
  // Lets a queued scan's server verdict land back on its ledger row instead
  // of being discarded with the response body.
  ScanLedgerService.register();
  await SyncOrchestrator.instance.start();

  runApp(const FlutterAttendanceApp());
}

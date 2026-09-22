import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';

/// Owns the single on-device SQLite database and every migration in it.
///
/// The outbox, the roster snapshot and the scan ledger all live here on
/// purpose: recording a scan locally and queueing it for upload has to be one
/// atomic write, or a crash between them leaves a scan the operator was told
/// was saved but that never reaches the server.
class LocalDb {
  LocalDb._();

  static const _dbName = 'fbmsO_outbox.db';
  static const _version = 4;

  static Database? _db;

  static Future<Database> instance() async {
    if (_db != null) return _db!;
    final dir = await getApplicationDocumentsDirectory();
    _db = await openDatabase(
      p.join(dir.path, _dbName),
      version: _version,
      onCreate: (db, _) async {
        await _createOutbox(db);
        await _createRoster(db);
        await _createLedger(db);
        await _createOrBlocks(db);
      },
      onUpgrade: (db, oldVersion, _) async {
        if (oldVersion < 2) {
          await db.execute(
              'ALTER TABLE outbox ADD COLUMN next_attempt_at INTEGER NOT NULL DEFAULT 0');
          await db.execute('ALTER TABLE outbox ADD COLUMN ref_id TEXT');
        }
        if (oldVersion < 3) {
          await _createRoster(db);
          await _createLedger(db);
        }
        if (oldVersion < 4) {
          await _createOrBlocks(db);
        }
      },
    );
    return _db!;
  }

  static Future<void> close() async {
    await _db?.close();
    _db = null;
  }

  // ─── Outbox ─────────────────────────────────────────────────────────────

  static Future<void> _createOutbox(Database db) async {
    await db.execute('''
      CREATE TABLE outbox (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        operation TEXT NOT NULL,
        url TEXT NOT NULL,
        method TEXT NOT NULL DEFAULT 'POST',
        payload TEXT NOT NULL,
        idem_key TEXT NOT NULL,
        token TEXT NOT NULL,
        content_type TEXT NOT NULL DEFAULT 'application/json',
        client_submitted_at INTEGER NOT NULL,
        queued_at INTEGER NOT NULL,
        retry_count INTEGER DEFAULT 0,
        last_error TEXT,
        last_attempt_at INTEGER,
        next_attempt_at INTEGER NOT NULL DEFAULT 0,
        ref_id TEXT,
        status TEXT NOT NULL DEFAULT 'queued'
      )
    ''');
    await db.execute('CREATE INDEX idx_outbox_status ON outbox (status)');
  }

  // ─── Roster snapshot ────────────────────────────────────────────────────

  static Future<void> _createRoster(Database db) async {
    await db.execute('''
      CREATE TABLE roster_meta (
        activity_id INTEGER PRIMARY KEY,
        roster_version TEXT NOT NULL,
        salt TEXT NOT NULL,
        sessions TEXT NOT NULL DEFAULT '{}',
        total INTEGER NOT NULL DEFAULT 0,
        fetched_at INTEGER NOT NULL,
        complete INTEGER NOT NULL DEFAULT 0
      )
    ''');

    // Keyed by the salted hash, because the raw QR token never leaves the
    // server — the device can only recognise a code it physically scans.
    await db.execute('''
      CREATE TABLE roster_student (
        activity_id INTEGER NOT NULL,
        qr_hash TEXT NOT NULL,
        student_number TEXT NOT NULL,
        name TEXT NOT NULL DEFAULT '',
        program TEXT NOT NULL DEFAULT '',
        section TEXT NOT NULL DEFAULT '',
        photo_url TEXT,
        photo_path TEXT,
        PRIMARY KEY (activity_id, qr_hash)
      )
    ''');
    await db.execute(
        'CREATE INDEX idx_roster_student_number ON roster_student (activity_id, student_number)');
  }

  // ─── Scan ledger ────────────────────────────────────────────────────────

  static Future<void> _createLedger(Database db) async {
    await db.execute('''
      CREATE TABLE scan_ledger (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_scan_id TEXT NOT NULL UNIQUE,
        activity_id INTEGER NOT NULL,
        student_number TEXT NOT NULL,
        qr_hash TEXT NOT NULL DEFAULT '',
        direction TEXT NOT NULL,
        scanned_at_utc INTEGER NOT NULL,
        tz_offset_min INTEGER NOT NULL DEFAULT 0,
        local_date TEXT NOT NULL,
        local_session TEXT NOT NULL DEFAULT '',
        local_mode TEXT NOT NULL,
        synced INTEGER NOT NULL DEFAULT 0,
        server_mode TEXT,
        server_ok INTEGER,
        server_message TEXT,
        reconciled_at INTEGER
      )
    ''');
    await db.execute('''
      CREATE INDEX idx_ledger_lookup
        ON scan_ledger (activity_id, student_number, local_date, local_session)
    ''');
  }

  // ─── Reserved O.R. numbers ──────────────────────────────────────────────

  /// A contiguous range of receipt numbers this device reserved from the
  /// server while online, so a cashier with no signal can still hand over a
  /// real O.R. number instead of a provisional slip.
  static Future<void> _createOrBlocks(Database db) async {
    await db.execute('''
      CREATE TABLE or_block (
        payment_date TEXT PRIMARY KEY,
        prefix TEXT NOT NULL,
        seq_start INTEGER NOT NULL,
        seq_end INTEGER NOT NULL,
        next_seq INTEGER NOT NULL,
        reserved_at INTEGER NOT NULL
      )
    ''');
  }
}

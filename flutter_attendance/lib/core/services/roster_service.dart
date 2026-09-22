import 'dart:convert';
import 'dart:io';

import 'package:crypto/crypto.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';

import '../../features/attendance/domain/qr_payload_parser.dart';
import 'local_db.dart';

/// A student matched from the on-device roster snapshot.
class RosterMatch {
  const RosterMatch({
    required this.studentNumber,
    required this.name,
    required this.program,
    required this.section,
    this.photoUrl,
    this.photoPath,
  });

  final String studentNumber;
  final String name;
  final String program;
  final String section;
  final String? photoUrl;
  final String? photoPath;

  /// Same shape the server's `student` payload uses, so the scan card renders
  /// an offline match exactly like an online one.
  Map<String, dynamic> toStudentPayload() => {
        'number': studentNumber,
        'name': name,
        'course': program,
        'section': section,
        'photo_url': photoPath ?? photoUrl,
      };
}

/// Downloads and queries the offline roster snapshot.
///
/// A student QR is an opaque random token that only the server can map to a
/// student, so without this an offline scanner shows nothing but "saved" — no
/// name, no duplicate check, no way to reject a QR from another school. The
/// snapshot stores SHA256(salt + token), which lets the device recognise a
/// code it physically scans without ever holding one it could forge.
class RosterService {
  static const _photoDirName = 'roster_photos';

  /// Cap the photo cache so a 2700-student roster cannot fill the device.
  static const _maxCachedPhotos = 3000;

  static final http.Client _http = http.Client();
  static const _timeout = Duration(seconds: 30);

  /// Progress of the most recent download, for the "prepare for offline" UI.
  static int downloadedCount = 0;
  static int expectedCount = 0;
  static bool downloading = false;

  // ─── Download ──────────────────────────────────────────────────────────

  /// Fetch the roster for [activityId] and store it locally. Returns the
  /// number of students cached. Photos are fetched afterwards in the
  /// background so the roster is usable as soon as the text lands.
  static Future<int> download({
    required String baseUrl,
    required String token,
    required int activityId,
    bool withPhotos = true,
    void Function(int done, int total)? onProgress,
  }) async {
    if (kIsWeb) return 0;

    downloading = true;
    downloadedCount = 0;
    expectedCount = 0;

    try {
      final base = _normalize(baseUrl);
      final manifest = await _getJson(
        '$base/api/mobile/roster/manifest?activity_id=$activityId',
        token,
      );
      if (manifest['ok'] != true) {
        throw Exception((manifest['message'] ?? 'Roster unavailable').toString());
      }

      final version = (manifest['roster_version'] ?? '').toString();
      final salt = (manifest['salt'] ?? '').toString();
      final total = (manifest['total'] as num?)?.toInt() ?? 0;
      final chunkCount = (manifest['chunk_count'] as num?)?.toInt() ?? 0;
      final sessions = manifest['sessions'] is Map ? manifest['sessions'] : {};

      expectedCount = total;

      final db = await LocalDb.instance();
      await db.delete('roster_student',
          where: 'activity_id = ?', whereArgs: [activityId]);

      // Write the new salt before any student rows land. If the download dies
      // partway, the rows that did arrive still resolve — leaving the previous
      // salt in place would silently mismatch every one of them.
      await db.insert(
        'roster_meta',
        {
          'activity_id': activityId,
          'roster_version': version,
          'salt': salt,
          'sessions': jsonEncode(sessions),
          'total': total,
          'fetched_at': DateTime.now().millisecondsSinceEpoch,
          'complete': 0,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );

      var stored = 0;
      for (var seq = 0; seq < chunkCount; seq++) {
        final chunk = await _getJson(
          '$base/api/mobile/roster/chunk?activity_id=$activityId'
          '&version=$version&seq=$seq',
          token,
        );
        if (chunk['ok'] != true) {
          // The roster changed mid-download; start over against the new one.
          throw Exception(
              (chunk['message'] ?? 'Roster changed during download').toString());
        }

        final students = (chunk['students'] as List?) ?? [];
        final batch = db.batch();
        for (final raw in students) {
          final s = raw as Map<String, dynamic>;
          // Replace, not insert: a student matched twice by the server's
          // o_users join arrives twice under one qr_hash.
          batch.insert(
            'roster_student',
            {
              'activity_id': activityId,
              'qr_hash': (s['qr_hash'] ?? '').toString(),
              'student_number': (s['student_number'] ?? '').toString(),
              'name': (s['name'] ?? '').toString(),
              'program': (s['program'] ?? '').toString(),
              'section': (s['section'] ?? '').toString(),
              'photo_url': s['photo_url']?.toString(),
            },
            conflictAlgorithm: ConflictAlgorithm.replace,
          );
        }
        await batch.commit(noResult: true);

        stored += students.length;
        downloadedCount = stored;
        onProgress?.call(stored, total);
      }

      // The server's joins can emit a student more than once, so the honest
      // count is what actually landed after the primary key deduped them.
      final uniqueStudents = Sqflite.firstIntValue(await db.rawQuery(
            'SELECT COUNT(*) FROM roster_student WHERE activity_id = ?',
            [activityId],
          )) ??
          stored;

      await db.update(
        'roster_meta',
        {
          'complete': 1,
          'total': uniqueStudents,
          'fetched_at': DateTime.now().millisecondsSinceEpoch,
        },
        where: 'activity_id = ?',
        whereArgs: [activityId],
      );

      if (withPhotos) {
        // Deliberately not awaited: the roster is already usable, and photos
        // are a nice-to-have that should not hold up the operator.
        cachePhotos(activityId);
      }

      return uniqueStudents;
    } finally {
      downloading = false;
    }
  }

  // ─── Resolve ───────────────────────────────────────────────────────────

  /// Match a scanned QR payload against the snapshot. Null means the code is
  /// not a student QR for this activity's roster.
  static Future<RosterMatch?> resolve(int activityId, String rawQr) async {
    if (kIsWeb) return null;

    final token = QrPayloadParser.studentToken(rawQr);
    if (token.isEmpty) return null;

    final meta = await metaFor(activityId);
    if (meta == null) return null;

    final hash =
        sha256.convert(utf8.encode('${meta.salt}$token')).toString();

    final db = await LocalDb.instance();
    final rows = await db.query(
      'roster_student',
      where: 'activity_id = ? AND qr_hash = ?',
      whereArgs: [activityId, hash],
      limit: 1,
    );
    if (rows.isEmpty) return null;

    final r = rows.first;
    return RosterMatch(
      studentNumber: (r['student_number'] ?? '').toString(),
      name: (r['name'] ?? '').toString(),
      program: (r['program'] ?? '').toString(),
      section: (r['section'] ?? '').toString(),
      photoUrl: r['photo_url'] as String?,
      photoPath: r['photo_path'] as String?,
    );
  }

  // ─── Meta ──────────────────────────────────────────────────────────────

  static Future<RosterMeta?> metaFor(int activityId) async {
    if (kIsWeb) return null;
    final db = await LocalDb.instance();
    final rows = await db.query('roster_meta',
        where: 'activity_id = ?', whereArgs: [activityId], limit: 1);
    if (rows.isEmpty) return null;

    final r = rows.first;
    Map<String, dynamic> sessions;
    try {
      final decoded = jsonDecode((r['sessions'] ?? '{}').toString());
      sessions = decoded is Map<String, dynamic> ? decoded : {};
    } catch (_) {
      sessions = {};
    }

    return RosterMeta(
      activityId: activityId,
      version: (r['roster_version'] ?? '').toString(),
      salt: (r['salt'] ?? '').toString(),
      total: (r['total'] as int?) ?? 0,
      fetchedAt: (r['fetched_at'] as int?) ?? 0,
      complete: ((r['complete'] as int?) ?? 0) == 1,
      sessions: sessions,
    );
  }

  static Future<bool> hasRoster(int activityId) async =>
      (await metaFor(activityId)) != null;

  // ─── Photos ────────────────────────────────────────────────────────────

  /// Pull student photos into the app's cache directory so the verify card
  /// still shows a face with no signal.
  static Future<void> cachePhotos(int activityId) async {
    if (kIsWeb) return;

    final db = await LocalDb.instance();
    final rows = await db.query(
      'roster_student',
      columns: ['qr_hash', 'photo_url'],
      where:
          'activity_id = ? AND photo_path IS NULL AND photo_url IS NOT NULL',
      whereArgs: [activityId],
      limit: _maxCachedPhotos,
    );
    if (rows.isEmpty) return;

    final dir = Directory(
        p.join((await getApplicationDocumentsDirectory()).path, _photoDirName));
    if (!await dir.exists()) await dir.create(recursive: true);

    for (final r in rows) {
      final url = (r['photo_url'] ?? '').toString();
      final hash = (r['qr_hash'] ?? '').toString();
      if (url.isEmpty || hash.isEmpty) continue;

      try {
        final response = await _http.get(Uri.parse(url)).timeout(_timeout);
        if (response.statusCode != 200 || response.bodyBytes.isEmpty) continue;

        final ext = p.extension(Uri.parse(url).path);
        final file = File(p.join(dir.path, '$hash${ext.isEmpty ? '.jpg' : ext}'));
        await file.writeAsBytes(response.bodyBytes);

        await db.update(
          'roster_student',
          {'photo_path': file.path},
          where: 'activity_id = ? AND qr_hash = ?',
          whereArgs: [activityId, hash],
        );
      } catch (_) {
        // A missing photo is cosmetic; the name is what matters.
      }
    }
  }

  // ─── Clear ─────────────────────────────────────────────────────────────

  /// Wipe every snapshot and cached photo. Called on logout, because a
  /// scanner phone is not necessarily the same person's next time.
  static Future<void> clearAll() async {
    if (kIsWeb) return;

    final db = await LocalDb.instance();
    await db.delete('roster_student');
    await db.delete('roster_meta');

    try {
      final dir = Directory(p.join(
          (await getApplicationDocumentsDirectory()).path, _photoDirName));
      if (await dir.exists()) await dir.delete(recursive: true);
    } catch (_) {
      // Cached images are disposable; failing to delete them must not block
      // the rest of logout.
    }
  }

  // ─── HTTP ──────────────────────────────────────────────────────────────

  static Future<Map<String, dynamic>> _getJson(String url, String token) async {
    final response = await _http.get(
      Uri.parse(url),
      headers: {
        HttpHeaders.acceptHeader: 'application/json',
        HttpHeaders.authorizationHeader: 'Bearer $token',
      },
    ).timeout(_timeout);

    final decoded = jsonDecode(response.body);
    if (decoded is Map<String, dynamic>) return decoded;
    throw Exception('Unexpected roster response');
  }

  static String _normalize(String baseUrl) {
    var b = baseUrl.trim();
    while (b.endsWith('/')) {
      b = b.substring(0, b.length - 1);
    }
    return b;
  }
}

class RosterMeta {
  const RosterMeta({
    required this.activityId,
    required this.version,
    required this.salt,
    required this.total,
    required this.fetchedAt,
    required this.complete,
    required this.sessions,
  });

  final int activityId;
  final String version;
  final String salt;
  final int total;
  final int fetchedAt;

  /// False while a download is partial. An unmatched QR then means "not
  /// downloaded yet", not "not a student" — the difference between queueing a
  /// scan and wrongly turning a student away.
  final bool complete;

  /// `{"am": {"in": "08:00", "out": "12:00"}, ...}` — the same windows the
  /// server classifies a scan's session with.
  final Map<String, dynamic> sessions;
}

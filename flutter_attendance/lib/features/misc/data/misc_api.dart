import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/connectivity_service.dart';
import '../../../core/services/offline_storage_service.dart';
import '../../../core/services/outbox_service.dart';
import '../domain/misc_models.dart';

/// Misc API: announcements, notes, todos, personnel.
///
/// Announcements and personnel are read-only (cache-first). Notes and todos
/// are full CRUD with outbox-routed writes when offline.
class MiscApi {
  MiscApi({http.Client? client, Uuid? uuid})
      : _client = client ?? http.Client(),
        _uuid = uuid ?? const Uuid();

  final http.Client _client;
  final Uuid _uuid;

  static const _cacheAnnouncements = 'announcements';
  static const _cacheNotes = 'notes';
  static const _cacheTodos = 'todos';
  static const _cachePersonnel = 'personnel';

  // ─── Announcements ──────────────────────────────────────────────────────

  Future<List<Announcement>> announcements({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/announcements';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['announcements'] as List? ?? [])
            .map((e) => Announcement.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheAnnouncements, list.map((a) => a.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cacheAnnouncements);
      return cached.map((m) => Announcement.fromJson(m)).toList();
    }
  }

  // ─── Notes ──────────────────────────────────────────────────────────────

  Future<List<Note>> notes({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/notes';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['notes'] as List? ?? [])
            .map((e) => Note.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheNotes, list.map((n) => n.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cacheNotes);
      return cached.map((m) => Note.fromJson(m)).toList();
    }
  }

  Future<bool> createNote({
    required String baseUrl,
    required String token,
    required String title,
    required String content,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/notes/create';
    final idem = _uuid.v4();
    final body = jsonEncode({'title': title, 'content': content});

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
          body: body,
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'note_create',
      url: url,
      idemKey: idem,
      token: token,
      payload: {'title': title, 'content': content},
    );
    return true; // queued
  }

  Future<bool> updateNote({
    required String baseUrl,
    required String token,
    required int id,
    required String title,
    required String content,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/notes/update/$id';
    final idem = _uuid.v4();
    final body = jsonEncode({'title': title, 'content': content});

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
          body: body,
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'note_update',
      url: url,
      idemKey: idem,
      token: token,
      payload: {'title': title, 'content': content},
    );
    return true;
  }

  Future<bool> deleteNote({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/notes/delete/$id';
    final idem = _uuid.v4();

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'note_delete',
      url: url,
      idemKey: idem,
      token: token,
    );
    return true;
  }

  // ─── Todos ──────────────────────────────────────────────────────────────

  Future<List<Todo>> todos({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/todos';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['todos'] as List? ?? [])
            .map((e) => Todo.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cacheTodos, list.map((t) => t.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cacheTodos);
      return cached.map((m) => Todo.fromJson(m)).toList();
    }
  }

  Future<bool> createTodo({
    required String baseUrl,
    required String token,
    required String task,
    required String dueDate,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/todos/create';
    final idem = _uuid.v4();
    final body = jsonEncode({'task': task, 'due_date': dueDate});

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
          body: body,
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'todo_create',
      url: url,
      idemKey: idem,
      token: token,
      payload: {'task': task, 'due_date': dueDate},
    );
    return true;
  }

  Future<bool> toggleTodo({
    required String baseUrl,
    required String token,
    required int id,
    required bool done,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/todos/toggle/$id';
    final idem = _uuid.v4();
    final body = jsonEncode({'done': done});

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
          body: body,
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'todo_toggle',
      url: url,
      idemKey: idem,
      token: token,
      payload: {'done': done},
    );
    return true;
  }

  Future<bool> deleteTodo({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/todos/delete/$id';
    final idem = _uuid.v4();

    if (await ConnectivityService.isConnected()) {
      try {
        final response = await _client.post(
          Uri.parse(url),
          headers: {..._h(token), 'X-Idempotency-Key': idem},
        );
        return _decode(response)['ok'] == true;
      } catch (_) {}
    }
    await OutboxService.enqueue(
      operation: 'todo_delete',
      url: url,
      idemKey: idem,
      token: token,
    );
    return true;
  }

  // ─── Personnel ──────────────────────────────────────────────────────────

  Future<List<Personnel>> personnel({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/personnel';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['personnel'] as List? ?? [])
            .map((e) => Personnel.fromJson(e as Map<String, dynamic>))
            .toList();
        await OfflineStorageService.saveList(
            _cachePersonnel, list.map((p) => p.toJson()).toList());
        return list;
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } catch (_) {
      final cached = await OfflineStorageService.getList(_cachePersonnel);
      return cached.map((m) => Personnel.fromJson(m)).toList();
    }
  }

  // ─── internals ──────────────────────────────────────────────────────────

  Map<String, String> _h(String token) => {
        HttpHeaders.acceptHeader: 'application/json',
        HttpHeaders.authorizationHeader: 'Bearer $token',
      };

  String _n(String baseUrl) => baseUrl.replaceFirst(RegExp(r'/+$'), '');

  Map<String, dynamic> _decode(http.Response response) {
    final body = response.body;
    if (body.isEmpty) throw ApiException('Empty response', statusCode: response.statusCode);
    if (body.trimLeft().startsWith('<')) {
      throw ApiException('Server returned HTML', statusCode: response.statusCode);
    }
    try {
      return jsonDecode(body) as Map<String, dynamic>;
    } catch (e) {
      throw ApiException('Malformed JSON', statusCode: response.statusCode);
    }
  }
}

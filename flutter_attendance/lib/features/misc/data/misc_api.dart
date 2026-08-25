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

  // ─── Admin: masterlist + accounting (staff only) ───────────────────────

  /// Enrolled students masterlist. Admin only.
  Future<({int total, List<MasterlistEntry> rows})> masterlist({
    required String baseUrl,
    required String token,
    int limit = 200,
    int offset = 0,
    String course = '',
  }) async {
    final params = <String, String>{
      'limit': '$limit',
      'offset': '$offset',
      if (course.isNotEmpty) 'course': course,
    };
    final qs = params.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    final url = '${_n(baseUrl)}/api/mobile/masterlist/enrolled?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['rows'] as List? ?? [])
            .map((e) => MasterlistEntry.fromJson(e as Map<String, dynamic>))
            .toList();
        return (total: (data['total'] as num?)?.toInt() ?? 0, rows: list);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Recent expenses (admin/accounting). Read-only.
  Future<List<ExpenseEntry>> expenses({
    required String baseUrl,
    required String token,
    int limit = 50,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/accounting/expenses?limit=$limit';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        return (data['expenses'] as List? ?? [])
            .map((e) => ExpenseEntry.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Admin: expenses CRUD ───────────────────────────────────────────────

  /// Create an expense.
  Future<void> expenseCreate({
    required String baseUrl,
    required String token,
    required String description,
    required String amount,
    String responsible = '',
    required String expenseDate,
    String category = '',
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/accounting/expenses/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'Description': description,
          'Amount': amount,
          'Responsible': responsible,
          'ExpenseDate': expenseDate,
          'Category': category,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Update an expense.
  Future<void> expenseUpdate({
    required String baseUrl,
    required String token,
    required int expensesid,
    String? description,
    String? amount,
    String? responsible,
    String? expenseDate,
    String? category,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/accounting/expenses/update';
    try {
      final body = <String, dynamic>{'expensesid': expensesid};
      if (description != null) body['Description'] = description;
      if (amount != null) body['Amount'] = amount;
      if (responsible != null) body['Responsible'] = responsible;
      if (expenseDate != null) body['ExpenseDate'] = expenseDate;
      if (category != null) body['Category'] = category;
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode(body),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Delete an expense.
  Future<void> expenseDelete({
    required String baseUrl,
    required String token,
    required int expensesid,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/accounting/expenses/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'expensesid': expensesid}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// List expense categories.
  Future<List<ExpenseCategory>> expenseCategories({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/accounting/expenses/categories';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        return (data['categories'] as List? ?? [])
            .map((e) =>
                ExpenseCategory.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Create an expense category.
  Future<void> expenseCategoryCreate({
    required String baseUrl,
    required String token,
    required String category,
  }) async {
    final url =
        '${_n(baseUrl)}/api/mobile/accounting/expenses/categories/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'Category': category}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Delete an expense category.
  Future<void> expenseCategoryDelete({
    required String baseUrl,
    required String token,
    required int categoryID,
  }) async {
    final url =
        '${_n(baseUrl)}/api/mobile/accounting/expenses/categories/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'categoryID': categoryID}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Departments / Courses (Settings/Department) ───────────────────────

  Future<({List<Department> rows, int total})> departments({
    required String baseUrl,
    required String token,
    int limit = 100,
    int offset = 0,
    String search = '',
  }) async {
    final qs = 'limit=$limit&offset=$offset&search=${Uri.encodeComponent(search)}';
    final url = '${_n(baseUrl)}/api/mobile/departments?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final rows = (data['departments'] as List? ?? [])
            .map((e) => Department.fromJson(e as Map<String, dynamic>))
            .toList();
        return (rows: rows, total: (data['total'] as num?)?.toInt() ?? rows.length);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> departmentCreate({
    required String baseUrl,
    required String token,
    required String courseCode,
    required String courseDescription,
    String major = '',
    String duration = '',
    String recogNo = '',
    String seriesYear = '',
    String programHead = '',
    String idNumber = '',
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/departments/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'CourseCode': courseCode,
          'CourseDescription': courseDescription,
          'Major': major,
          'Duration': duration,
          'recogNo': recogNo,
          'SeriesYear': seriesYear,
          'ProgramHead': programHead,
          'IDNumber': idNumber,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> departmentDelete({
    required String baseUrl,
    required String token,
    required int courseid,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/departments/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'courseid': courseid}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Sections (Page/manageSections) ─────────────────────────────────────

  Future<({List<Section> rows, int total})> sections({
    required String baseUrl,
    required String token,
    int limit = 200,
    int offset = 0,
    String search = '',
  }) async {
    final qs = 'limit=$limit&offset=$offset&search=${Uri.encodeComponent(search)}';
    final url = '${_n(baseUrl)}/api/mobile/sections?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final rows = (data['sections'] as List? ?? [])
            .map((e) => Section.fromJson(e as Map<String, dynamic>))
            .toList();
        return (rows: rows, total: (data['total'] as num?)?.toInt() ?? rows.length);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> sectionCreate({
    required String baseUrl,
    required String token,
    required String section,
    String courseid = '',
    String yearLevel = '',
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/sections/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'section': section,
          'courseid': courseid,
          'year_level': yearLevel,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> sectionDelete({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/sections/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'id': id}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Announcements CRUD (admin) ─────────────────────────────────────────

  Future<List<Announcement>> announcementsAll({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/announcements/all';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        return (data['announcements'] as List? ?? [])
            .map((e) => Announcement.fromJson(e as Map<String, dynamic>))
            .toList();
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> announcementCreate({
    required String baseUrl,
    required String token,
    required String title,
    required String message,
    String audience = 'all',
    String dateExpire = '',
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/announcements/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'title': title,
          'message': message,
          'audience': audience,
          'date_expire': dateExpire,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> announcementDelete({
    required String baseUrl,
    required String token,
    required int id,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/announcements/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'id': id}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Reports ────────────────────────────────────────────────────────────

  Future<ReportSummary> reportSummary({
    required String baseUrl,
    required String token,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/reports/summary';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        return ReportSummary.fromJson(data);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Admin: personnel CRUD ──────────────────────────────────────────────

  /// All personnel (including inactive). Admin only.
  Future<({List<Personnel> rows, int total})> personnelAll({
    required String baseUrl,
    required String token,
    int limit = 50,
    int offset = 0,
    String search = '',
  }) async {
    final qs = 'limit=$limit&offset=$offset&search=${Uri.encodeComponent(search)}';
    final url = '${_n(baseUrl)}/api/mobile/personnel/all?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final rows = (data['personnel'] as List? ?? [])
            .map((e) => Personnel.fromJson(e as Map<String, dynamic>))
            .toList();
        return (rows: rows, total: (data['total'] as num?)?.toInt() ?? rows.length);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Save (create or update) a personnel. Admin only.
  Future<void> personnelSave({
    required String baseUrl,
    required String token,
    String id = '',
    required String firstName,
    required String lastName,
    String middleName = '',
    required String title,
    String department = '',
    String status = '',
    String email = '',
    String mobile = '',
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/personnel/save';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'id': id,
          'first_name': firstName,
          'last_name': lastName,
          'middle_name': middleName,
          'title': title,
          'department': department,
          'status': status,
          'email': email,
          'mobile': mobile,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Delete a personnel. Admin only.
  Future<void> personnelDelete({
    required String baseUrl,
    required String token,
    required String id,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/personnel/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'id': id}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Toggle personnel active/inactive. Admin only.
  Future<void> personnelToggle({
    required String baseUrl,
    required String token,
    required int id,
    required int isActive,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/personnel/toggle';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'id': id, 'is_active': isActive}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Admin: user accounts ───────────────────────────────────────────────

  /// List all user accounts. Admin only.
  Future<({List<UserAccount> rows, int total})> userAccounts({
    required String baseUrl,
    required String token,
    int limit = 50,
    int offset = 0,
    String search = '',
  }) async {
    final qs = 'limit=$limit&offset=$offset&search=${Uri.encodeComponent(search)}';
    final url = '${_n(baseUrl)}/api/mobile/users?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final rows = (data['users'] as List? ?? [])
            .map((e) => UserAccount.fromJson(e as Map<String, dynamic>))
            .toList();
        return (rows: rows, total: (data['total'] as num?)?.toInt() ?? rows.length);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Create a new user account. Admin only.
  Future<void> userAccountCreate({
    required String baseUrl,
    required String token,
    required String username,
    required String idNumber,
    required String password,
    required String acctLevel,
    required String fName,
    String mName = '',
    required String lName,
    required String email,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/users/create';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({
          'username': username,
          'IDNumber': idNumber,
          'password': password,
          'acctLevel': acctLevel,
          'fName': fName,
          'mName': mName,
          'lName': lName,
          'email': email,
        }),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Delete a user account. Admin only.
  Future<void> userAccountDelete({
    required String baseUrl,
    required String token,
    required String username,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/users/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'username': username}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  // ─── Admin: registered students ─────────────────────────────────────────

  /// Registered students list (paginated). Admin only.
  Future<({int total, List<RegisteredStudent> rows})> registeredStudents({
    required String baseUrl,
    required String token,
    int limit = 100,
    int offset = 0,
    String search = '',
  }) async {
    final params = <String, String>{
      'limit': '$limit',
      'offset': '$offset',
      if (search.isNotEmpty) 'search': search,
    };
    final qs = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    final url = '${_n(baseUrl)}/api/mobile/registered-students?$qs';
    try {
      final response = await _client.get(Uri.parse(url), headers: _h(token));
      final data = _decode(response);
      if (data['ok'] == true) {
        final list = (data['rows'] as List? ?? [])
            .map((e) =>
                RegisteredStudent.fromJson(e as Map<String, dynamic>))
            .toList();
        return (total: (data['total'] as num?)?.toInt() ?? 0, rows: list);
      }
      throw ApiException((data['message'] ?? 'Failed').toString());
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  /// Delete a registered student. Admin only.
  Future<void> registeredStudentDelete({
    required String baseUrl,
    required String token,
    required String studentNumber,
  }) async {
    final url = '${_n(baseUrl)}/api/mobile/registered-students/delete';
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _h(token),
        body: jsonEncode({'student_number': studentNumber}),
      );
      final data = _decode(response);
      if (data['ok'] != true) {
        throw ApiException((data['message'] ?? 'Failed').toString());
      }
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(e.toString());
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

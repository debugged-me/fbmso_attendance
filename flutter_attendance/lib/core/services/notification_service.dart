import 'dart:async';
import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// A single in-app notification.
class AppNotification {
  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    required this.createdAt,
    this.read = false,
    this.data,
  });

  final int id;
  final String title;
  final String body;
  final String type;
  final String createdAt;
  final bool read;
  final Map<String, dynamic>? data;

  factory AppNotification.fromJson(Map<String, dynamic> j) => AppNotification(
        id: (j['id'] as num?)?.toInt() ?? 0,
        title: (j['title'] ?? '').toString(),
        body: (j['body'] ?? '').toString(),
        type: (j['type'] ?? '').toString(),
        createdAt: (j['created_at'] ?? '').toString(),
        read: j['read'] == true,
        data: (j['data'] as Map?)?.cast<String, dynamic>(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'type': type,
        'created_at': createdAt,
        'read': read,
        if (data != null) 'data': data,
      };
}

/// In-app notification store. No Firebase — notifications are generated
/// locally (sync results, offline-queue events, errors) and persisted in
/// SharedPreferences as a JSON list. A [ChangeNotifier] so the UI can
/// react to new notifications in real time.
class NotificationService {
  NotificationService._();
  static final NotificationService instance = NotificationService._();

  static const _key = 'app_notifications';
  static const _maxStored = 100;

  final _controller = StreamController<List<AppNotification>>.broadcast();
  Stream<List<AppNotification>> get stream => _controller.stream;

  List<AppNotification> _items = [];
  List<AppNotification> get items => List.unmodifiable(_items);

  int get unreadCount => _items.where((n) => !n.read).length;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw != null && raw.isNotEmpty) {
      try {
        final list = jsonDecode(raw) as List;
        _items = list
            .map((e) => AppNotification.fromJson(e as Map<String, dynamic>))
            .toList();
      } catch (_) {
        _items = [];
      }
    }
    _emit();
  }

  Future<void> add({
    required String title,
    required String body,
    String type = 'info',
    Map<String, dynamic>? data,
  }) async {
    final now = DateTime.now();
    final n = AppNotification(
      id: now.millisecondsSinceEpoch,
      title: title,
      body: body,
      type: type,
      createdAt: now.toIso8601String(),
      data: data,
    );
    _items.insert(0, n);
    if (_items.length > _maxStored) {
      _items = _items.sublist(0, _maxStored);
    }
    await _persist();
    _emit();
  }

  Future<void> markRead(int id) async {
    _items = _items.map((n) => n.id == id ? _copy(n, read: true) : n).toList();
    await _persist();
    _emit();
  }

  Future<void> markAllRead() async {
    _items = _items.map((n) => _copy(n, read: true)).toList();
    await _persist();
    _emit();
  }

  Future<void> clear() async {
    _items = [];
    await _persist();
    _emit();
  }

  void _emit() => _controller.add(_items);

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _key,
      jsonEncode(_items.map((n) => n.toJson()).toList()),
    );
  }

  AppNotification _copy(AppNotification n, {bool? read}) => AppNotification(
        id: n.id,
        title: n.title,
        body: n.body,
        type: n.type,
        createdAt: n.createdAt,
        read: read ?? n.read,
        data: n.data,
      );

  void dispose() => _controller.close();
}

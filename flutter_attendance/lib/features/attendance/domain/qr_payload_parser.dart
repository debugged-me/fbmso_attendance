import 'dart:convert';

/// Parses QR payloads produced by current and legacy attendance clients.
///
/// The server remains the authority: parsing an activity ID or student token
/// here never bypasses its activity, role, token-status, or account checks.
abstract final class QrPayloadParser {
  static int? activityId(String raw) {
    var value = _clean(raw);
    if (value.isEmpty) return null;

    // Allow a small JSON wrapper used by some third-party QR generators.
    if (value.startsWith('{')) {
      try {
        final decoded = jsonDecode(value);
        if (decoded is Map) {
          for (final key in ['activity_id', 'activityId', 'id', 'url']) {
            final candidate = decoded[key];
            if (candidate != null) {
              final parsed = activityId(candidate.toString());
              if (parsed != null) return parsed;
            }
          }
        }
      } catch (_) {
        // Continue with the plain-text formats below.
      }
    }

    final uri = Uri.tryParse(value);
    if (uri != null) {
      for (final key in ['activity_id', 'activity', 'id']) {
        final parsed = int.tryParse(uri.queryParameters[key] ?? '');
        if (parsed != null && parsed > 0) return parsed;
      }

      final segments = uri.pathSegments.where((s) => s.isNotEmpty).toList();
      for (var i = 0; i + 2 < segments.length; i++) {
        if (segments[i].toLowerCase() == 'attendance' &&
            segments[i + 1].toLowerCase() == 'checkin') {
          final parsed = int.tryParse(segments[i + 2]);
          if (parsed != null && parsed > 0) return parsed;
        }
      }
    }

    final pathMatch = RegExp(
      r'attendance[\/]checkin[\/](\d+)',
      caseSensitive: false,
    ).firstMatch(value);
    final pathId = int.tryParse(pathMatch?.group(1) ?? '');
    if (pathId != null && pathId > 0) return pathId;

    final labelled = RegExp(
      r'(?:activity(?:_id)?|event)\s*[:|=#-]\s*(\d+)',
      caseSensitive: false,
    ).firstMatch(value);
    final labelledId = int.tryParse(labelled?.group(1) ?? '');
    if (labelledId != null && labelledId > 0) return labelledId;

    final bare = int.tryParse(value);
    return bare != null && bare > 0 ? bare : null;
  }

  static String studentToken(String raw) {
    var value = _clean(raw);
    if (value.isEmpty) return '';

    if (value.startsWith('{')) {
      try {
        final decoded = jsonDecode(value);
        if (decoded is Map) {
          for (final key in ['token', 'qr_token', 'student_token']) {
            final candidate = _clean(decoded[key]?.toString() ?? '');
            if (_isToken(candidate)) return candidate.toLowerCase();
          }
        }
      } catch (_) {
        // Continue with URL and plain-text formats.
      }
    }

    final uri = Uri.tryParse(value);
    if (uri != null) {
      for (final key in ['token', 'qr_token', 'student_token']) {
        final candidate = _clean(uri.queryParameters[key] ?? '');
        if (_isToken(candidate)) return candidate.toLowerCase();
      }
    }

    for (final part in value.split('|').reversed) {
      final candidate = _clean(part);
      if (_isToken(candidate)) return candidate.toLowerCase();
    }

    if (_isToken(value)) return value.toLowerCase();

    final embedded = RegExp(
      r'(?<![a-f0-9])([a-f0-9]{64}|[a-f0-9]{32})(?![a-f0-9])',
      caseSensitive: false,
    ).firstMatch(value);
    return embedded?.group(1)?.toLowerCase() ?? '';
  }

  static String _clean(String raw) =>
      raw.replaceFirst(RegExp(r'^(?:\uFEFF|\s)+'), '').trim();

  static bool _isToken(String value) =>
      RegExp(r'^(?:[a-f0-9]{32}|[a-f0-9]{64})$', caseSensitive: false)
          .hasMatch(value);
}

/// Time formatting helpers.

/// Converts a time string (e.g. "14:30:00", "14:30") to 12-hour format
/// (e.g. "2:30 PM").
String to12Hour(String t) {
  if (t.isEmpty) return '';
  // Strip seconds if present
  final timePart = t.contains(' ') ? t.split(' ').last : t;
  final parts = timePart.split(':');
  if (parts.length < 2) return t;
  var h = int.tryParse(parts[0]) ?? 0;
  final m = parts[1];
  final suffix = h >= 12 ? 'PM' : 'AM';
  h = h % 12 == 0 ? 12 : h % 12;
  return '$h:$m $suffix';
}

/// Converts a datetime string (e.g. "2026-08-25 18:24:10") to
/// "6:24 PM" (12-hour time only, date stripped).
String to12HourFromDateTime(String dt) {
  if (dt.isEmpty) return '';
  final parts = dt.split(' ');
  if (parts.length >= 2) return to12Hour(parts[1]);
  return to12Hour(dt);
}

/// Formats a datetime string as "Aug 25, 2:30 PM".
String toDateTime12(String dt) {
  if (dt.isEmpty) return '';
  final parts = dt.split(' ');
  final dateStr = parts[0];
  final timeStr = parts.length >= 2 ? to12Hour(parts[1]) : '';
  // Parse date "2026-08-25"
  final dp = dateStr.split('-');
  if (dp.length == 3) {
    const months = [
      '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    final m = int.tryParse(dp[1]) ?? 0;
    final d = dp[2];
    final month = m >= 1 && m <= 12 ? months[m] : '';
    return '$month $d${timeStr.isNotEmpty ? ', $timeStr' : ''}';
  }
  return timeStr.isNotEmpty ? timeStr : dt;
}

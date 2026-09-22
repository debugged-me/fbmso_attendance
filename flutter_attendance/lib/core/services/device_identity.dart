import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

/// A stable id for this installation.
///
/// Reserved O.R. number blocks are owned by a device, and the server refuses a
/// receipt number the requesting device was not given — so this id is what
/// makes an offline payment's number provably its own.
class DeviceIdentity {
  static const _key = 'device_id';
  static String? _cached;

  static Future<String> id() async {
    final cached = _cached;
    if (cached != null) return cached;

    final prefs = await SharedPreferences.getInstance();
    var value = prefs.getString(_key);
    if (value == null || value.isEmpty) {
      value = const Uuid().v4();
      await prefs.setString(_key, value);
    }
    _cached = value;
    return value;
  }
}

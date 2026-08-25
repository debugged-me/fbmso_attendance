import 'user_role.dart';

/// Authenticated session returned by `POST /api/mobile/auth/login` and
/// `GET /api/mobile/auth/me`. Works for every role (Student, Instructor,
/// Admin, ...); the role bucket is derived from `position`.
class AppSession {
  const AppSession({
    required this.baseUrl,
    required this.token,
    required this.schoolName,
    required this.username,
    required this.idNumber,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.email,
    required this.avatar,
    required this.position,
    required this.activeSy,
    required this.activeSem,
  });

  final String baseUrl;
  final String token;
  final String schoolName;
  final String username;
  final String idNumber;
  final String firstName;
  final String middleName;
  final String lastName;
  final String email;
  final String avatar;
  final String position;
  final String activeSy;
  final String activeSem;

  UserRole get role => UserRole.fromPosition(position);

  String get fullName {
    final parts = [firstName, middleName, lastName]
        .map((e) => e.trim())
        .where((e) => e.isNotEmpty)
        .toList();
    return parts.isEmpty ? username : parts.join(' ');
  }

  String get displayName => fullName.trim().isEmpty ? username : fullName;

  /// Build from the login response envelope `{ ok, token, school_name, ..., user:{...} }`.
  factory AppSession.fromLogin(Map<String, dynamic> json, {required String baseUrl}) {
    final user = (json['user'] as Map?)?.cast<String, dynamic>() ?? {};
    return AppSession(
      baseUrl: baseUrl,
      token: (json['token'] ?? '').toString(),
      schoolName: (json['school_name'] ?? user['school_name'] ?? '').toString(),
      username: (user['username'] ?? '').toString(),
      idNumber: (user['id_number'] ?? '').toString(),
      firstName: (user['first_name'] ?? '').toString(),
      middleName: (user['middle_name'] ?? '').toString(),
      lastName: (user['last_name'] ?? '').toString(),
      email: (user['email'] ?? '').toString(),
      avatar: (user['avatar'] ?? '').toString(),
      position: (user['position'] ?? user['role'] ?? '').toString(),
      activeSy: (json['active_sy'] ?? user['active_sy'] ?? '').toString(),
      activeSem: (json['active_sem'] ?? user['active_sem'] ?? '').toString(),
    );
  }

  /// Build from the `me` response envelope `{ ok, school_name, ..., user:{...} }`,
  /// reusing a previously stored token.
  factory AppSession.fromMe(
    Map<String, dynamic> json, {
    required String baseUrl,
    required String fallbackToken,
  }) {
    final user = (json['user'] as Map?)?.cast<String, dynamic>() ?? {};
    return AppSession(
      baseUrl: baseUrl,
      token: fallbackToken,
      schoolName: (json['school_name'] ?? user['school_name'] ?? '').toString(),
      username: (user['username'] ?? '').toString(),
      idNumber: (user['id_number'] ?? '').toString(),
      firstName: (user['first_name'] ?? '').toString(),
      middleName: (user['middle_name'] ?? '').toString(),
      lastName: (user['last_name'] ?? '').toString(),
      email: (user['email'] ?? '').toString(),
      avatar: (user['avatar'] ?? '').toString(),
      position: (user['position'] ?? user['role'] ?? '').toString(),
      activeSy: (json['active_sy'] ?? user['active_sy'] ?? '').toString(),
      activeSem: (json['active_sem'] ?? user['active_sem'] ?? '').toString(),
    );
  }

  Map<String, dynamic> toJson() => {
        'baseUrl': baseUrl,
        'token': token,
        'schoolName': schoolName,
        'username': username,
        'idNumber': idNumber,
        'firstName': firstName,
        'middleName': middleName,
        'lastName': lastName,
        'email': email,
        'avatar': avatar,
        'position': position,
        'activeSy': activeSy,
        'activeSem': activeSem,
      };

  factory AppSession.fromStorage(Map<String, dynamic> json) {
    return AppSession(
      baseUrl: (json['baseUrl'] ?? '').toString(),
      token: (json['token'] ?? '').toString(),
      schoolName: (json['schoolName'] ?? '').toString(),
      username: (json['username'] ?? '').toString(),
      idNumber: (json['idNumber'] ?? '').toString(),
      firstName: (json['firstName'] ?? '').toString(),
      middleName: (json['middleName'] ?? '').toString(),
      lastName: (json['lastName'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      avatar: (json['avatar'] ?? '').toString(),
      position: (json['position'] ?? '').toString(),
      activeSy: (json['activeSy'] ?? '').toString(),
      activeSem: (json['activeSem'] ?? '').toString(),
    );
  }
}

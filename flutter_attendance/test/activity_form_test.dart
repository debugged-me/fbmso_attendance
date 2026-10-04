import 'package:flutter/material.dart';
import 'package:flutter_attendance/features/attendance/presentation/activity_form_screen.dart';
import 'package:flutter_attendance/features/auth/domain/app_session.dart';
import 'package:flutter_attendance/features/attendance/domain/attendance_models.dart';
import 'package:flutter_test/flutter_test.dart';

/// The reported edit bug: opening an activity's edit form showed empty
/// session windows / toggles and saving wiped them. The form now seeds from
/// the fresh activity row (falling back to the list item offline) and
/// patches only what changed — these pin the seeding half of that.
void main() {
  const session = AppSession(
    baseUrl: 'http://localhost/unreachable',
    token: 'test',
    schoolName: 'Test',
    username: 'admin',
    idNumber: '0',
    firstName: 'A',
    middleName: '',
    lastName: 'D',
    email: '',
    avatar: '',
    position: 'Admin',
    activeSy: '2026-2027',
    activeSem: 'First Semester',
  );

  Activity activity() => Activity.fromJson({
        'activity_id': 25,
        'title': 'General Convocation 2026',
        'activity_date': '2026-09-17',
        'location': 'Gym',
        'description': 'Whole-school assembly.',
        'program': '',
        'status': 'open',
        'manual_status': 'closed',
        'is_open': false,
        'state': 'closed',
        'auto_close': true,
        'grace_minutes': 55,
        'sessions': {
          'pm': {'in': '16:00', 'out': '17:30'},
          'eve': {'in': '19:00', 'out': '20:00'},
        },
      });

  Widget wrap() => MaterialApp(
        home: ActivityFormScreen(session: session, activity: activity()),
      );

  testWidgets('edit form retains the stored values', (tester) async {
    await tester.pumpWidget(wrap());
    // The unreachable detail endpoint falls back to the list item.
    await tester.pumpAndSettle();

    expect(find.text('General Convocation 2026'), findsOneWidget);
    expect(find.text('2026-09-17'), findsOneWidget);
    expect(find.text('Gym'), findsOneWidget);
    expect(find.text('Whole-school assembly.'), findsOneWidget);

    // Session windows carried through — the fields the wipe destroyed.
    expect(find.text('16:00'), findsOneWidget);
    expect(find.text('17:30'), findsOneWidget);
    expect(find.text('19:00'), findsOneWidget);
    expect(find.text('20:00'), findsOneWidget);

    // Auto-close stays on and keeps the stored grace period.
    final toggle = tester.widget<Switch>(find.byType(Switch));
    expect(toggle.value, isTrue);
    expect(find.text('55 min'), findsOneWidget);
  });
}

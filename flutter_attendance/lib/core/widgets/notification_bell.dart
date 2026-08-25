import 'package:flutter/material.dart';

import '../../core/services/notification_service.dart';
import '../../core/theme/app_theme.dart';
import '../features/notifications/presentation/notifications_screen.dart';

/// Bell icon with an unread badge. Tapping opens the notifications screen.
/// Uses a [StreamBuilder] so the badge updates in real time.
class NotificationBell extends StatelessWidget {
  const NotificationBell({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<List<AppNotification>>(
      stream: NotificationService.instance.stream,
      initialData: NotificationService.instance.items,
      builder: (context, snapshot) {
        final unread = snapshot.data?.where((n) => !n.read).length ?? 0;
        return IconButton(
          icon: Badge(
            isLabelVisible: unread > 0,
            label: Text(
              unread > 99 ? '99+' : unread.toString(),
              style: const TextStyle(fontSize: 10, color: Colors.white),
            ),
            child: Icon(Icons.notifications_outlined, color: color),
          ),
          onPressed: () {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => const NotificationsScreen(),
              ),
            );
          },
        );
      },
    );
  }
}

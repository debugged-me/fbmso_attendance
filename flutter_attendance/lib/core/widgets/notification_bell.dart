import 'package:flutter/material.dart';

import '../../core/services/notification_service.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../design/components/components.dart';
import '../theme/app_icons.dart';

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
        return AppCircleButton(
          icon: AppIcons.notifications_outlined,
          tooltip: unread > 0 ? '$unread unread notifications' : 'Notifications',
          color: color,
          badge: unread > 0,
          onTap: () {
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

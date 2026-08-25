import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Announcements feed. Cache-first, read-only.
class AnnouncementsScreen extends StatefulWidget {
  const AnnouncementsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<AnnouncementsScreen> createState() => _AnnouncementsScreenState();
}

class _AnnouncementsScreenState extends State<AnnouncementsScreen> {
  late final MiscApi _api;
  List<Announcement> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await _api.announcements(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Announcements')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _items.isEmpty
                      ? const Center(
                          child: Text('No active announcements.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView.builder(
                          itemCount: _items.length,
                          itemBuilder: (context, i) =>
                              _AnnouncementCard(item: _items[i]),
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AnnouncementCard extends StatelessWidget {
  const _AnnouncementCard({required this.item});
  final Announcement item;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(item.title,
                      style: Theme.of(context).textTheme.titleMedium),
                ),
                if (item.audience.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppTheme.midBlue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(item.audience,
                        style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.midBlue)),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(item.message, style: Theme.of(context).textTheme.bodyMedium),
            const SizedBox(height: 8),
            Row(
              children: [
                if (item.author.isNotEmpty)
                  Text(item.author,
                      style: const TextStyle(
                          fontSize: 12,
                          color: AppTheme.textMuted,
                          fontWeight: FontWeight.w600)),
                if (item.author.isNotEmpty && item.datePosted.isNotEmpty)
                  const Text(' • ',
                      style: TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                if (item.datePosted.isNotEmpty)
                  Text(item.datePosted,
                      style: const TextStyle(
                          fontSize: 12, color: AppTheme.textMuted)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

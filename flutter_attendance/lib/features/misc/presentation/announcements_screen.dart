import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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
    return AppScaffold(
      title: 'Announcements',
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _items.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(
                                height: MediaQuery.of(context).size.height * 0.5),
                            const AppEmptyState(
                              icon: Icons.campaign_outlined,
                              title: 'No active announcements',
                              subtitle: 'Check back later for updates.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(
                              16, 8, 16, 32),
                          itemCount: _items.length,
                          itemBuilder: (context, i) => Padding(
                            padding: EdgeInsets.only(
                                bottom: i == _items.length - 1 ? 0 : 12),
                            child: _AnnouncementCard(item: _items[i]),
                          ),
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
    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  item.title,
                  style: const TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                    height: 1.3,
                  ),
                ),
              ),
              if (item.audience.isNotEmpty) ...[
                const SizedBox(width: 10),
                AppChip(label: item.audience, tone: AppInk.accent),
              ],
            ],
          ),
          const SizedBox(height: 10),
          Text(
            item.message,
            style: const TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: 14,
              fontWeight: FontWeight.w400,
              color: AppInk.body,
              height: 1.5,
            ),
          ),
          if (item.author.isNotEmpty || item.datePosted.isNotEmpty) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                if (item.author.isNotEmpty)
                  Text(
                    item.author,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
                if (item.author.isNotEmpty && item.datePosted.isNotEmpty)
                  const Text('  •  ',
                      style: TextStyle(fontSize: 12, color: AppInk.muted)),
                if (item.datePosted.isNotEmpty)
                  Text(
                    item.datePosted,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppInk.muted,
                    ),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

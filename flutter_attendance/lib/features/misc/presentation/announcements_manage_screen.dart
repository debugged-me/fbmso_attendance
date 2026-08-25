import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Announcements management screen — admin can view, create, delete.
class AnnouncementsManageScreen extends StatefulWidget {
  const AnnouncementsManageScreen({super.key, required this.session});
  final AppSession session;

  @override
  State<AnnouncementsManageScreen> createState() => _AnnouncementsManageScreenState();
}

class _AnnouncementsManageScreenState extends State<AnnouncementsManageScreen> {
  late final MiscApi _api;
  List<Announcement> _announcements = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final list = await _api.announcementsAll(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() { _announcements = list; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _delete(Announcement a) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Announcement'),
        content: Text('Delete "${a.title}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await _api.announcementDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: a.id,
      );
      _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  void _showForm() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _AnnouncementForm(api: _api, session: widget.session, onSaved: _load),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Announcements',
      showBackButton: true,
      actions: [
        IconButton(icon: const Icon(Icons.add_rounded), onPressed: _showForm),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: Icons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : _announcements.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.campaign_outlined,
                                title: 'No announcements yet',
                                subtitle: 'Tap + to post an announcement.',
                              ),
                            ])
                          : ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _announcements.length,
                              itemBuilder: (context, i) {
                                final a = _announcements[i];
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: AppCard(
                                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          width: 42, height: 42,
                                          decoration: BoxDecoration(
                                            color: AppInk.accent.withValues(alpha: 0.12),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: const Icon(Icons.campaign_rounded, color: AppInk.accent, size: 22),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(a.title,
                                                  style: const TextStyle(
                                                      fontSize: 14,
                                                      fontWeight: FontWeight.w700,
                                                      color: AppInk.heading),
                                                  maxLines: 2,
                                                  overflow: TextOverflow.ellipsis),
                                              const SizedBox(height: 4),
                                              Text(a.message,
                                                  style: const TextStyle(fontSize: 12, color: AppInk.muted, height: 1.4),
                                                  maxLines: 3,
                                                  overflow: TextOverflow.ellipsis),
                                              const SizedBox(height: 6),
                                              Wrap(
                                                spacing: 8,
                                                runSpacing: 4,
                                                children: [
                                                  if (a.audience.isNotEmpty)
                                                    Container(
                                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                      decoration: BoxDecoration(
                                                        color: AppInk.accent.withValues(alpha: 0.08),
                                                        borderRadius: BorderRadius.circular(6),
                                                      ),
                                                      child: Text(a.audience,
                                                          style: const TextStyle(
                                                              fontSize: 11,
                                                              fontWeight: FontWeight.w600,
                                                              color: AppInk.accent)),
                                                    ),
                                                  if (a.datePosted.isNotEmpty)
                                                    Text(a.datePosted,
                                                        style: const TextStyle(fontSize: 11, color: AppInk.muted)),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20),
                                          onPressed: () => _delete(a),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AnnouncementForm extends StatefulWidget {
  const _AnnouncementForm({required this.api, required this.session, required this.onSaved});
  final MiscApi api;
  final AppSession session;
  final VoidCallback onSaved;

  @override
  State<_AnnouncementForm> createState() => _AnnouncementFormState();
}

class _AnnouncementFormState extends State<_AnnouncementForm> {
  late final TextEditingController _title;
  late final TextEditingController _message;
  DateTime? _expireDate;
  String _audience = 'All';
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _title = TextEditingController();
    _message = TextEditingController();
  }

  @override
  void dispose() {
    _title.dispose();
    _message.dispose();
    super.dispose();
  }

  String _formatDate(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _save() async {
    if (_title.text.trim().isEmpty || _message.text.trim().isEmpty) {
      setState(() => _error = 'Title and message are required.');
      return;
    }
    setState(() { _saving = true; _error = null; });
    try {
      await widget.api.announcementCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        title: _title.text.trim(),
        message: _message.text.trim(),
        audience: _audience,
        dateExpire: _expireDate != null ? _formatDate(_expireDate!) : '',
      );
      if (!mounted) return;
      widget.onSaved();
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() { _saving = false; _error = e.toString(); });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(24, 12, 24, 32 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(color: AppInk.rule, borderRadius: BorderRadius.circular(999)),
              ),
            ),
            const SizedBox(height: 20),
            const Text('New Announcement',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(controller: _title, label: 'Title *', hint: 'Announcement title', prefixIcon: Icons.title_rounded),
            const SizedBox(height: 14),
            AppInput(
              controller: _message,
              label: 'Message *',
              hint: 'Write your announcement...',
              maxLines: 5,
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _audience,
              decoration: InputDecoration(
                labelText: 'Audience',
                prefixIcon: const Icon(Icons.group_outlined, size: 20, color: AppInk.muted),
                filled: true, fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
              ),
              items: const [
                DropdownMenuItem(value: 'All', child: Text('All')),
                DropdownMenuItem(value: 'Students', child: Text('Students')),
                DropdownMenuItem(value: 'Registrar', child: Text('Registrar')),
                DropdownMenuItem(value: 'Instructors', child: Text('Instructors')),
              ],
              onChanged: (v) => setState(() => _audience = v ?? 'All'),
            ),
            const SizedBox(height: 14),
            GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime.now().add(const Duration(days: 7)),
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 365 * 2)),
                );
                if (picked != null) setState(() => _expireDate = picked);
              },
              child: AbsorbPointer(
                child: AppInput(
                  controller: TextEditingController(
                    text: _expireDate != null ? _formatDate(_expireDate!) : '',
                  ),
                  label: 'Expiry Date (optional)',
                  hint: 'Tap to select date',
                  prefixIcon: Icons.event_outlined,
                ),
              ),
            ),
            const SizedBox(height: 20),
            AppButton(
              label: 'Post Announcement',
              fullWidth: true,
              size: AppButtonSize.lg,
              loading: _saving,
              disabled: _saving,
              onTap: _save,
            ),
          ],
        ),
      ),
    );
  }
}

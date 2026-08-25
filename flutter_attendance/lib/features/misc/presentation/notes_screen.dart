import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Notes list with create/edit/delete. Writes queue through the outbox
/// when offline.
class NotesScreen extends StatefulWidget {
  const NotesScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<NotesScreen> createState() => _NotesScreenState();
}

class _NotesScreenState extends State<NotesScreen> {
  late final MiscApi _api;
  List<Note> _notes = [];
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
      final list = await _api.notes(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _notes = list;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _editOrCreate({Note? note}) async {
    final result = await showModalBottomSheet<({String title, String content})>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _NoteSheet(note: note),
    );
    if (result == null) return;

    bool ok;
    if (note != null) {
      ok = await _api.updateNote(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        id: note.id,
        title: result.title,
        content: result.content,
      );
    } else {
      ok = await _api.createNote(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        title: result.title,
        content: result.content,
      );
    }
    if (!mounted) return;
    _showSnack(ok ? 'Note saved.' : 'Failed to save note.', ok);
    _load();
  }

  Future<void> _delete(Note note) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Delete note?'),
        content: Text('"${note.title}" will be permanently deleted.'),
        actions: [
          AppButton(
            label: 'Cancel',
            style: AppButtonStyle.ghost,
            size: AppButtonSize.sm,
            onTap: () => Navigator.pop(ctx, false),
          ),
          AppButton(
            label: 'Delete',
            style: AppButtonStyle.destructive,
            size: AppButtonSize.sm,
            onTap: () => Navigator.pop(ctx, true),
          ),
        ],
      ),
    );
    if (ok != true) return;

    final success = await _api.deleteNote(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      id: note.id,
    );
    if (!mounted) return;
    _showSnack(success ? 'Note deleted.' : 'Failed to delete.', success);
    _load();
  }

  void _showSnack(String message, bool success) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        backgroundColor: success ? AppTheme.success : AppTheme.error,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Notes',
      floatingActionButton: FloatingActionButton(
        onPressed: () => _editOrCreate(),
        backgroundColor: AppInk.accent,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _notes.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.4),
                            const AppEmptyState(
                              icon: Icons.note_add_outlined,
                              title: 'No notes yet',
                              subtitle: 'Tap + to create your first note.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                          itemCount: _notes.length,
                          itemBuilder: (context, i) {
                            final n = _notes[i];
                            return Padding(
                              padding: EdgeInsets.only(
                                  bottom: i == _notes.length - 1 ? 0 : 10),
                              child: Dismissible(
                                key: ValueKey(n.id),
                                direction: DismissDirection.endToStart,
                                background: Container(
                                  decoration: BoxDecoration(
                                    color: AppInk.critical,
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  alignment: Alignment.centerRight,
                                  padding: const EdgeInsets.only(right: 24),
                                  child: const Icon(Icons.delete_outline,
                                      color: Colors.white),
                                ),
                                confirmDismiss: (_) async {
                                  await _delete(n);
                                  return true;
                                },
                                child: AppCard(
                                  onTap: () => _editOrCreate(note: n),
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16, vertical: 14),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        n.title,
                                        style: const TextStyle(
                                          fontFamily: AppTheme.fontFamily,
                                          fontSize: 15,
                                          fontWeight: FontWeight.w700,
                                          color: AppInk.heading,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        n.content,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontFamily: AppTheme.fontFamily,
                                          fontSize: 13,
                                          fontWeight: FontWeight.w400,
                                          color: AppInk.muted,
                                          height: 1.4,
                                        ),
                                      ),
                                      if (n.createdAt.isNotEmpty) ...[
                                        const SizedBox(height: 8),
                                        Text(
                                          n.createdAt,
                                          style: const TextStyle(
                                            fontSize: 11,
                                            color: AppInk.muted,
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
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

class _NoteSheet extends StatefulWidget {
  const _NoteSheet({this.note});
  final Note? note;

  @override
  State<_NoteSheet> createState() => _NoteSheetState();
}

class _NoteSheetState extends State<_NoteSheet> {
  late final TextEditingController _title;
  late final TextEditingController _content;

  @override
  void initState() {
    super.initState();
    _title = TextEditingController(text: widget.note?.title ?? '');
    _content = TextEditingController(text: widget.note?.content ?? '');
  }

  @override
  void dispose() {
    _title.dispose();
    _content.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.note != null;
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(
        24, 12, 24,
        32 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: AppInk.rule,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              isEdit ? 'Edit Note' : 'New Note',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppInk.heading,
              ),
            ),
            const SizedBox(height: 20),
            AppInput(
              controller: _title,
              label: 'Title',
              hint: 'Note title',
              prefixIcon: Icons.title_rounded,
            ),
            const SizedBox(height: 14),
            AppInput(
              controller: _content,
              label: 'Content',
              hint: 'Write something...',
              maxLines: 6,
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: AppButton(
                    label: 'Cancel',
                    style: AppButtonStyle.ghost,
                    onTap: () => Navigator.pop(context),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: AppButton(
                    label: isEdit ? 'Save' : 'Create',
                    style: AppButtonStyle.primary,
                    onTap: () {
                      if (_title.text.trim().isEmpty ||
                          _content.text.trim().isEmpty) {
                        return;
                      }
                      Navigator.pop(context, (
                        title: _title.text.trim(),
                        content: _content.text.trim(),
                      ));
                    },
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

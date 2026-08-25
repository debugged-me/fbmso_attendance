import 'package:flutter/material.dart';

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
    final result = await showDialog<({String title, String content})>(
      context: context,
      builder: (ctx) => _NoteDialog(note: note),
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
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(ok ? 'Note saved.' : 'Failed to save note.'),
        backgroundColor: ok ? AppTheme.success : AppTheme.error,
      ),
    );
    _load();
  }

  Future<void> _delete(Note note) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete note?'),
        content: Text('"${note.title}" will be permanently deleted.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
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
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success ? 'Note deleted.' : 'Failed to delete.'),
        backgroundColor: success ? AppTheme.success : AppTheme.error,
      ),
    );
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notes')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _editOrCreate(),
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
                      ? const Center(
                          child: Text('No notes yet. Tap + to create one.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView.builder(
                          itemCount: _notes.length,
                          itemBuilder: (context, i) {
                            final n = _notes[i];
                            return Dismissible(
                              key: ValueKey(n.id),
                              direction: DismissDirection.endToStart,
                              background: Container(
                                color: AppTheme.error,
                                alignment: Alignment.centerRight,
                                padding: const EdgeInsets.only(right: 24),
                                child: const Icon(Icons.delete, color: Colors.white),
                              ),
                              confirmDismiss: (_) async {
                                await _delete(n);
                                return true;
                              },
                              child: Card(
                                margin: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 4),
                                child: ListTile(
                                  title: Text(n.title,
                                      style: const TextStyle(fontWeight: FontWeight.w600)),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(n.content,
                                          maxLines: 2, overflow: TextOverflow.ellipsis),
                                      if (n.createdAt.isNotEmpty)
                                        Text(n.createdAt,
                                            style: const TextStyle(
                                                fontSize: 11,
                                                color: AppTheme.textMuted)),
                                    ],
                                  ),
                                  onTap: () => _editOrCreate(note: n),
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

class _NoteDialog extends StatefulWidget {
  const _NoteDialog({this.note});
  final Note? note;

  @override
  State<_NoteDialog> createState() => _NoteDialogState();
}

class _NoteDialogState extends State<_NoteDialog> {
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
    return AlertDialog(
      title: Text(isEdit ? 'Edit note' : 'New note'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(
            controller: _title,
            decoration: const InputDecoration(
              labelText: 'Title',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _content,
            decoration: const InputDecoration(
              labelText: 'Content',
              border: OutlineInputBorder(),
            ),
            maxLines: 4,
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () {
            if (_title.text.trim().isEmpty || _content.text.trim().isEmpty) return;
            Navigator.pop(context, (
              title: _title.text.trim(),
              content: _content.text.trim(),
            ));
          },
          child: Text(isEdit ? 'Save' : 'Create'),
        ),
      ],
    );
  }
}

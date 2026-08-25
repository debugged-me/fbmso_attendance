import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Todo list with create/toggle/delete. Writes queue through the outbox
/// when offline.
class TodosScreen extends StatefulWidget {
  const TodosScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<TodosScreen> createState() => _TodosScreenState();
}

class _TodosScreenState extends State<TodosScreen> {
  late final MiscApi _api;
  List<Todo> _todos = [];
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
      final list = await _api.todos(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _todos = list;
        _loading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _add() async {
    final result = await showDialog<({String task, String dueDate})>(
      context: context,
      builder: (ctx) => const _TodoDialog(),
    );
    if (result == null) return;

    final ok = await _api.createTodo(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      task: result.task,
      dueDate: result.dueDate,
    );
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(ok ? 'Todo added.' : 'Failed to add.'),
        backgroundColor: ok ? AppTheme.success : AppTheme.error,
      ),
    );
    _load();
  }

  Future<void> _toggle(Todo todo) async {
    final ok = await _api.toggleTodo(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      id: todo.id,
      done: !todo.isDone,
    );
    if (ok) _load();
  }

  Future<void> _delete(Todo todo) async {
    final ok = await _api.deleteTodo(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      id: todo.id,
    );
    if (ok) _load();
  }

  @override
  Widget build(BuildContext context) {
    final pending = _todos.where((t) => !t.isDone).toList();
    final done = _todos.where((t) => t.isDone).toList();

    return Scaffold(
      appBar: AppBar(title: const Text('To-Do')),
      floatingActionButton: FloatingActionButton(
        onPressed: _add,
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
                  : _todos.isEmpty
                      ? const Center(
                          child: Text('No tasks yet. Tap + to add one.',
                              style: TextStyle(color: AppTheme.textMuted)),
                        )
                      : ListView(
                          children: [
                            if (pending.isNotEmpty) ...[
                              const _SectionHeader('Pending'),
                              ...pending.map((t) => _TodoTile(
                                    todo: t,
                                    onToggle: () => _toggle(t),
                                    onDelete: () => _delete(t),
                                  )),
                            ],
                            if (done.isNotEmpty) ...[
                              const _SectionHeader('Completed'),
                              ...done.map((t) => _TodoTile(
                                    todo: t,
                                    onToggle: () => _toggle(t),
                                    onDelete: () => _delete(t),
                                  )),
                            ],
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader(this.title);
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
      child: Text(title,
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: AppTheme.textMuted, fontWeight: FontWeight.w700)),
    );
  }
}

class _TodoTile extends StatelessWidget {
  const _TodoTile({
    required this.todo,
    required this.onToggle,
    required this.onDelete,
  });

  final Todo todo;
  final VoidCallback onToggle;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final isOverdue =
        !todo.isDone && todo.dueDate.isNotEmpty && _isPast(todo.dueDate);

    return Dismissible(
      key: ValueKey(todo.id),
      direction: DismissDirection.endToStart,
      background: Container(
        color: AppTheme.error,
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        child: const Icon(Icons.delete, color: Colors.white),
      ),
      confirmDismiss: (_) async {
        onDelete();
        return true;
      },
      child: Card(
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 3),
        child: ListTile(
          leading: Checkbox(
            value: todo.isDone,
            onChanged: (_) => onToggle(),
          ),
          title: Text(
            todo.task,
            style: TextStyle(
              decoration: todo.isDone ? TextDecoration.lineThrough : null,
              color: todo.isDone ? AppTheme.textMuted : null,
            ),
          ),
          subtitle: todo.dueDate.isNotEmpty
              ? Row(
                  children: [
                    Icon(Icons.calendar_today,
                        size: 12,
                        color: isOverdue ? AppTheme.error : AppTheme.textMuted),
                    const SizedBox(width: 4),
                    Text(
                      todo.dueDate,
                      style: TextStyle(
                        fontSize: 12,
                        color: isOverdue ? AppTheme.error : AppTheme.textMuted,
                      ),
                    ),
                  ],
                )
              : null,
        ),
      ),
    );
  }

  bool _isPast(String dateStr) {
    try {
      final d = DateTime.parse(dateStr);
      final today = DateTime.now();
      return d.isBefore(DateTime(today.year, today.month, today.day));
    } catch (_) {
      return false;
    }
  }
}

class _TodoDialog extends StatefulWidget {
  const _TodoDialog();

  @override
  State<_TodoDialog> createState() => _TodoDialogState();
}

class _TodoDialogState extends State<_TodoDialog> {
  late final TextEditingController _task;
  DateTime? _dueDate;

  @override
  void initState() {
    super.initState();
    _task = TextEditingController();
  }

  @override
  void dispose() {
    _task.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('New task'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(
            controller: _task,
            decoration: const InputDecoration(
              labelText: 'Task',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          ListTile(
            leading: const Icon(Icons.calendar_today),
            title: Text(_dueDate == null
                ? 'Due date'
                : '${_dueDate!.year}-${_dueDate!.month.toString().padLeft(2, '0')}-${_dueDate!.day.toString().padLeft(2, '0')}'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: DateTime.now(),
                firstDate: DateTime.now().subtract(const Duration(days: 365)),
                lastDate: DateTime.now().add(const Duration(days: 365 * 5)),
              );
              if (picked != null) setState(() => _dueDate = picked);
            },
          ),
        ],
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
        FilledButton(
          onPressed: () {
            if (_task.text.trim().isEmpty || _dueDate == null) return;
            final d = _dueDate!;
            Navigator.pop(context, (
              task: _task.text.trim(),
              dueDate:
                  '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}',
            ));
          },
          child: const Text('Add'),
        ),
      ],
    );
  }
}

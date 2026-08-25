import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
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
    final result = await showModalBottomSheet<({String task, String dueDate})>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const _TodoSheet(),
    );
    if (result == null) return;

    final ok = await _api.createTodo(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      task: result.task,
      dueDate: result.dueDate,
    );
    if (!mounted) return;
    _showSnack(ok ? 'Todo added.' : 'Failed to add.', ok);
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
    final pending = _todos.where((t) => !t.isDone).toList();
    final done = _todos.where((t) => t.isDone).toList();

    return AppScaffold(
      title: 'To-Do',
      floatingActionButton: FloatingActionButton(
        onPressed: _add,
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
                  : _todos.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(
                                height:
                                    MediaQuery.of(context).size.height * 0.4),
                            const AppEmptyState(
                              icon: Icons.check_circle_outline,
                              title: 'No tasks yet',
                              subtitle: 'Tap + to add your first task.',
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                          children: [
                            if (pending.isNotEmpty) ...[
                              const AppSectionHeader(
                                title: 'Pending',
                                padding: EdgeInsets.fromLTRB(4, 8, 4, 10),
                              ),
                              ...pending.map((t) => Padding(
                                    padding: const EdgeInsets.only(bottom: 10),
                                    child: _TodoTile(
                                      todo: t,
                                      onToggle: () => _toggle(t),
                                      onDelete: () => _delete(t),
                                    ),
                                  )),
                            ],
                            if (done.isNotEmpty) ...[
                              if (pending.isNotEmpty) const SizedBox(height: 16),
                              const AppSectionHeader(
                                title: 'Completed',
                                padding: EdgeInsets.fromLTRB(4, 8, 4, 10),
                              ),
                              ...done.map((t) => Padding(
                                    padding: const EdgeInsets.only(bottom: 10),
                                    child: _TodoTile(
                                      todo: t,
                                      onToggle: () => _toggle(t),
                                      onDelete: () => _delete(t),
                                    ),
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
        decoration: BoxDecoration(
          color: AppInk.critical,
          borderRadius: BorderRadius.circular(16),
        ),
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        child: const Icon(Icons.delete_outline, color: Colors.white),
      ),
      confirmDismiss: (_) async {
        onDelete();
        return true;
      },
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        child: Row(
          children: [
            GestureDetector(
              onTap: onToggle,
              behavior: HitTestBehavior.opaque,
              child: Container(
                width: 26,
                height: 26,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: todo.isDone ? AppInk.positive : Colors.transparent,
                  border: Border.all(
                    color: todo.isDone ? AppInk.positive : AppInk.rule,
                    width: 2,
                  ),
                ),
                child: todo.isDone
                    ? const Icon(Icons.check, size: 16, color: Colors.white)
                    : null,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    todo.task,
                    style: TextStyle(
                      fontFamily: AppTheme.fontFamily,
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      decoration:
                          todo.isDone ? TextDecoration.lineThrough : null,
                      color: todo.isDone ? AppInk.muted : AppInk.heading,
                      height: 1.3,
                    ),
                  ),
                  if (todo.dueDate.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.calendar_today_outlined,
                          size: 12,
                          color: isOverdue ? AppInk.critical : AppInk.muted,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          todo.dueDate,
                          style: TextStyle(
                            fontSize: 12,
                            color: isOverdue ? AppInk.critical : AppInk.muted,
                            fontWeight: isOverdue
                                ? FontWeight.w600
                                : FontWeight.w400,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
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

class _TodoSheet extends StatefulWidget {
  const _TodoSheet();

  @override
  State<_TodoSheet> createState() => _TodoSheetState();
}

class _TodoSheetState extends State<_TodoSheet> {
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

  String _formatDate(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
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
            const Text('New Task',
                style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: AppInk.heading)),
            const SizedBox(height: 20),
            AppInput(
              controller: _task,
              label: 'Task',
              hint: 'What needs doing?',
              prefixIcon: Icons.task_outlined,
            ),
            const SizedBox(height: 14),
            InkWell(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime.now(),
                  firstDate:
                      DateTime.now().subtract(const Duration(days: 365)),
                  lastDate: DateTime.now().add(const Duration(days: 365 * 5)),
                );
                if (picked != null) setState(() => _dueDate = picked);
              },
              borderRadius: BorderRadius.circular(14),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppInk.rule, width: 1.5),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.calendar_today_outlined,
                        size: 20, color: AppInk.muted),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Due date',
                            style: TextStyle(
                              fontFamily: AppTheme.fontFamily,
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: AppInk.muted,
                              letterSpacing: 0.3,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            _dueDate == null ? 'Select a date' : _formatDate(_dueDate!),
                            style: TextStyle(
                              fontFamily: AppTheme.fontFamily,
                              fontSize: 15,
                              fontWeight: FontWeight.w500,
                              color: _dueDate == null
                                  ? const Color(0xFF94A3B8)
                                  : AppInk.heading,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Icon(Icons.chevron_right_rounded,
                        size: 22, color: Color(0xFFCBD5E1)),
                  ],
                ),
              ),
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
                    label: 'Add',
                    style: AppButtonStyle.primary,
                    onTap: () {
                      if (_task.text.trim().isEmpty || _dueDate == null) return;
                      final d = _dueDate!;
                      Navigator.pop(context, (
                        task: _task.text.trim(),
                        dueDate: _formatDate(d),
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

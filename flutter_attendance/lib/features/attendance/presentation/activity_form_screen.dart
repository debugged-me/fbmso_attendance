import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';

/// Create or edit an activity. Staff only.
class ActivityFormScreen extends StatefulWidget {
  const ActivityFormScreen({
    super.key,
    required this.session,
    this.activity,
  });

  final AppSession session;
  /// When non-null, the form edits this activity; otherwise it creates new.
  final Activity? activity;

  @override
  State<ActivityFormScreen> createState() => _ActivityFormScreenState();
}

class _ActivityFormScreenState extends State<ActivityFormScreen> {
  late final AttendanceApi _api;
  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _location;
  late final TextEditingController _program;
  late final TextEditingController _date;
  late final TextEditingController _startTime;
  late final TextEditingController _endTime;

  bool _isOpen = true;
  bool _saving = false;
  String? _error;

  bool get _isEdit => widget.activity != null;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    final a = widget.activity;
    _title = TextEditingController(text: a?.title ?? '');
    _description = TextEditingController(text: a?.description ?? '');
    _location = TextEditingController(text: a?.location ?? '');
    _program = TextEditingController(text: a?.program ?? '');
    _date = TextEditingController(text: a?.activityDate ?? '');
    // Extract HH:MM from start_time / end_time
    _startTime = TextEditingController(text: _hhmm(a?.startTime));
    _endTime = TextEditingController(text: _hhmm(a?.endTime));
    _isOpen = a?.isOpen ?? true;
  }

  String _hhmm(String? t) {
    if (t == null || t.isEmpty) return '';
    // "09:00:00" → "09:00"
    final parts = t.split(':');
    if (parts.length >= 2) return '${parts[0]}:${parts[1]}';
    return t;
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _location.dispose();
    _program.dispose();
    _date.dispose();
    _startTime.dispose();
    _endTime.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    DateTime initial = now;
    if (_date.text.isNotEmpty) {
      try {
        initial = DateTime.parse(_date.text);
      } catch (_) {}
    }
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
    );
    if (picked != null) {
      _date.text = '${picked.year.toString().padLeft(4, '0')}-'
          '${picked.month.toString().padLeft(2, '0')}-'
          '${picked.day.toString().padLeft(2, '0')}';
    }
  }

  Future<void> _pickTime(TextEditingController ctrl, String label) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _parseTime(ctrl.text) ?? TimeOfDay.now(),
      helpText: label,
    );
    if (picked != null) {
      ctrl.text =
          '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
    }
  }

  TimeOfDay? _parseTime(String s) {
    if (s.isEmpty) return null;
    final parts = s.split(':');
    if (parts.length >= 2) {
      return TimeOfDay(hour: int.tryParse(parts[0]) ?? 0, minute: int.tryParse(parts[1]) ?? 0);
    }
    return null;
  }

  Future<void> _save() async {
    FocusScope.of(context).unfocus();
    HapticFeedback.mediumImpact();

    if (_title.text.trim().isEmpty || _date.text.trim().isEmpty) {
      setState(() => _error = 'Title and date are required.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    final result = _isEdit
        ? await _api.updateActivity(
            baseUrl: widget.session.baseUrl,
            token: widget.session.token,
            activityId: widget.activity!.activityId,
            fields: {
              'title': _title.text.trim(),
              'description': _description.text.trim(),
              'location': _location.text.trim(),
              'program': _program.text.trim(),
              'activity_date': _date.text.trim(),
              'start_time': _startTime.text.trim(),
              'end_time': _endTime.text.trim(),
              'is_open': _isOpen ? 1 : 0,
            },
          )
        : await _api.createActivity(
            baseUrl: widget.session.baseUrl,
            token: widget.session.token,
            title: _title.text.trim(),
            activityDate: _date.text.trim(),
            startTime: _startTime.text.trim(),
            endTime: _endTime.text.trim(),
            location: _location.text.trim(),
            program: _program.text.trim(),
            description: _description.text.trim(),
            isOpen: _isOpen,
          );

    if (!mounted) return;
    setState(() => _saving = false);

    if (result.ok) {
      Navigator.of(context).pop(true);
    } else {
      setState(() => _error = result.message);
    }
  }

  Future<void> _delete() async {
    if (!_isEdit) return;
    final a = widget.activity!;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Delete activity?'),
        content: Text('"${a.title}" will be permanently deleted.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => _saving = true);
    final result = await _api.deleteActivity(
      baseUrl: widget.session.baseUrl,
      token: widget.session.token,
      activityId: a.activityId,
    );
    if (!mounted) return;
    setState(() => _saving = false);

    if (result.ok) {
      Navigator.of(context).pop(true);
    } else {
      setState(() => _error = result.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: _isEdit ? 'Edit Activity' : 'New Activity',
      showBackButton: true,
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  AppInput(
                    controller: _title,
                    label: 'Title',
                    hint: 'Activity title',
                    prefixIcon: Icons.event_rounded,
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _date,
                    label: 'Date',
                    hint: 'YYYY-MM-DD',
                    prefixIcon: Icons.calendar_today_rounded,
                    readOnly: true,
                    onTap: _pickDate,
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: AppInput(
                          controller: _startTime,
                          label: 'Start',
                          hint: 'HH:MM',
                          prefixIcon: Icons.play_arrow_rounded,
                          readOnly: true,
                          onTap: () => _pickTime(_startTime, 'Start time'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: AppInput(
                          controller: _endTime,
                          label: 'End',
                          hint: 'HH:MM',
                          prefixIcon: Icons.stop_rounded,
                          readOnly: true,
                          onTap: () => _pickTime(_endTime, 'End time'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _location,
                    label: 'Location',
                    hint: 'Where is it held?',
                    prefixIcon: Icons.place_outlined,
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _program,
                    label: 'Program',
                    hint: 'e.g. YFD, YES-O, BKDC',
                    prefixIcon: Icons.school_outlined,
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _description,
                    label: 'Description',
                    hint: 'Optional details',
                    prefixIcon: Icons.description_outlined,
                    maxLines: 3,
                  ),
                  const SizedBox(height: 14),
                  AppCard(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 10),
                    child: Row(
                      children: [
                        const Icon(Icons.toggle_on_rounded,
                            color: AppInk.accent, size: 22),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Open for attendance',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: AppInk.heading,
                                ),
                              ),
                              SizedBox(height: 3),
                              Text(
                                'Students can check in when this is on.',
                                style: TextStyle(
                                    fontSize: 13, color: AppInk.muted),
                              ),
                            ],
                          ),
                        ),
                        Switch(
                          value: _isOpen,
                          onChanged: (v) => setState(() => _isOpen = v),
                        ),
                      ],
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.red.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline_rounded,
                              color: Colors.red, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _error!,
                              style: const TextStyle(
                                  color: Colors.red, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
          // ── Bottom action bar ──────────────────────────────────────────
          Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: AppInk.rule)),
            ),
            child: Row(
              children: [
                if (_isEdit)
                  TextButton(
                    onPressed: _saving ? null : _delete,
                    style: TextButton.styleFrom(foregroundColor: Colors.red),
                    child: const Text('Delete'),
                  ),
                const Spacer(),
                AppButton(
                  label: _isEdit ? 'Save changes' : 'Create activity',
                  icon: _isEdit ? Icons.check_rounded : Icons.add_rounded,
                  onTap: _saving ? null : _save,
                  loading: _saving,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

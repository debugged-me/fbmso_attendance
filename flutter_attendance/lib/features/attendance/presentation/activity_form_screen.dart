import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../domain/attendance_models.dart';
import 'activity_state_style.dart';

/// Create or edit an activity. Staff only.
///
/// Field-for-field parity with the web create/edit form
/// (activities_create.php): title, description, date, program (+custom
/// +" — major"), location, am/pm/eve session windows, manual status,
/// auto-close + grace minutes. Sessions are stored in meta.sessions and the
/// overall start/end is derived server-side, exactly like the web.
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
  static const _kCustomProgram = '__custom__';

  late final AttendanceApi _api;
  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _location;
  late final TextEditingController _programCustom;
  late final TextEditingController _date;

  // Session window controllers — web ids am_in/am_out, pm_in/pm_out, eve_in/eve_out.
  late final TextEditingController _amIn;
  late final TextEditingController _amOut;
  late final TextEditingController _pmIn;
  late final TextEditingController _pmOut;
  late final TextEditingController _eveIn;
  late final TextEditingController _eveOut;

  List<String> _programs = [];
  List<String> _majors = [];
  bool _programsLoading = true;
  bool _majorsLoading = false;
  String? _programChoice; // program name or _kCustomProgram
  String? _major;

  ActivityStatus _status = ActivityStatus.open;
  bool _autoClose = false;
  int _graceMinutes = 15;
  bool _saving = false;
  String? _error;

  bool get _isEdit => widget.activity != null;
  bool get _isCustomProgram => _programChoice == _kCustomProgram;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    final a = widget.activity;
    _title = TextEditingController(text: a?.title ?? '');
    _description = TextEditingController(text: a?.description ?? '');
    _location = TextEditingController(text: a?.location ?? '');
    _programCustom = TextEditingController();
    _date = TextEditingController(text: a?.activityDate ?? '');

    final s = a?.sessions ?? ActivitySessions.empty;
    _amIn = TextEditingController(text: s.amIn ?? '');
    _amOut = TextEditingController(text: s.amOut ?? '');
    _pmIn = TextEditingController(text: s.pmIn ?? '');
    _pmOut = TextEditingController(text: s.pmOut ?? '');
    _eveIn = TextEditingController(text: s.eveIn ?? '');
    _eveOut = TextEditingController(text: s.eveOut ?? '');

    _status = a?.manualStatus ?? ActivityStatus.open;
    _autoClose = a?.autoClose ?? false;
    _graceMinutes = a?.graceMinutes ?? 15;

    // Stored `program` is "Program — Major" — split it the same way the
    // web edit page does before deciding the dropdown/custom selection.
    final stored = a?.program ?? '';
    String storedProgram = stored;
    String? storedMajor;
    final sep = stored.indexOf(' — ');
    if (sep > 0) {
      storedProgram = stored.substring(0, sep);
      storedMajor = stored.substring(sep + 3);
    }
    _loadPrograms(
        storedProgram: storedProgram,
        storedMajor: storedMajor,
        storedFull: stored);
  }

  Future<void> _loadPrograms({
    required String storedProgram,
    String? storedMajor,
    required String storedFull,
  }) async {
    try {
      final list = await _api.activityPrograms(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _programs = list;
        _programsLoading = false;
        if (storedProgram.isNotEmpty) {
          if (list.contains(storedProgram)) {
            _programChoice = storedProgram;
            _loadMajors(storedProgram, initial: storedMajor);
          } else {
            // Unknown program on web = the "Add New" custom path.
            _programChoice = _kCustomProgram;
            _programCustom.text = storedFull;
          }
        }
      });
    } catch (_) {
      if (mounted) setState(() => _programsLoading = false);
    }
  }

  Future<void> _loadMajors(String program, {String? initial}) async {
    setState(() {
      _majorsLoading = true;
      _majors = [];
      if (initial == null) _major = null;
    });
    try {
      final list = await _api.activityMajors(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        program: program,
      );
      if (!mounted) return;
      setState(() {
        _majors = list;
        _majorsLoading = false;
        _major = (initial != null && list.contains(initial)) ? initial : _major;
      });
    } catch (_) {
      if (mounted) setState(() => _majorsLoading = false);
    }
  }

  /// The `program` value the server stores: "Program — Major" or the custom
  /// text — identical to the web form's hidden-field combine.
  String get _programPayload {
    if (_isCustomProgram) return _programCustom.text.trim();
    final p = _programChoice ?? '';
    if (p.isEmpty) return '';
    final m = _major;
    return (m != null && m.isNotEmpty) ? '$p — $m' : p;
  }

  ActivitySessions get _sessions => ActivitySessions(
        amIn: _amIn.text.trim(),
        amOut: _amOut.text.trim(),
        pmIn: _pmIn.text.trim(),
        pmOut: _pmOut.text.trim(),
        eveIn: _eveIn.text.trim(),
        eveOut: _eveOut.text.trim(),
      );

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _location.dispose();
    _programCustom.dispose();
    _date.dispose();
    _amIn.dispose();
    _amOut.dispose();
    _pmIn.dispose();
    _pmOut.dispose();
    _eveIn.dispose();
    _eveOut.dispose();
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
      return TimeOfDay(
          hour: int.tryParse(parts[0]) ?? 0,
          minute: int.tryParse(parts[1]) ?? 0);
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
    if (_isCustomProgram && _programCustom.text.trim().isEmpty) {
      setState(() => _error = 'Please type a program name.');
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
              'program': _programPayload,
              'activity_date': _date.text.trim(),
              'sessions': _sessions.toJson(),
              'status': _status.value,
              'auto_close': _autoClose,
              'grace_minutes': _graceMinutes,
            },
          )
        : await _api.createActivity(
            baseUrl: widget.session.baseUrl,
            token: widget.session.token,
            title: _title.text.trim(),
            activityDate: _date.text.trim(),
            location: _location.text.trim(),
            program: _programPayload,
            description: _description.text.trim(),
            status: _status,
            autoClose: _autoClose,
            graceMinutes: _graceMinutes,
            sessions: _sessions,
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
            style: FilledButton.styleFrom(backgroundColor: AppInk.critical),
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
                    prefixIcon: Icons.event_rounded,
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _date,
                    label: 'Date',
                    prefixIcon: Icons.calendar_today_rounded,
                    readOnly: true,
                    onTap: _pickDate,
                  ),
                  const SizedBox(height: 14),

                  // ── Program (web: dropdown + Add New + Major) ──────
                  _buildProgramField(),
                  if (_isCustomProgram) ...[
                    const SizedBox(height: 10),
                    AppInput(
                      controller: _programCustom,
                      label: 'Custom program',
                      prefixIcon: Icons.edit_outlined,
                    ),
                  ] else if (_programChoice != null &&
                      _programChoice!.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    _buildMajorField(),
                  ],
                  const SizedBox(height: 14),

                  AppInput(
                    controller: _location,
                    label: 'Location',
                    prefixIcon: Icons.place_outlined,
                  ),
                  const SizedBox(height: 14),
                  AppInput(
                    controller: _description,
                    label: 'Description',
                    prefixIcon: Icons.description_outlined,
                    maxLines: 3,
                  ),
                  const SizedBox(height: 14),

                  // ── Session windows (web: Morning/Afternoon/Evening) ──
                  AppCard(
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.schedule_rounded,
                                color: AppInk.accent, size: 22),
                            SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                'Session windows',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: AppInk.heading,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Start/End are derived from the earliest in and latest out.',
                          style: TextStyle(fontSize: 12, color: AppInk.muted),
                        ),
                        const SizedBox(height: 14),
                        _SessionRow(
                          label: 'Morning',
                          icon: Icons.wb_sunny_outlined,
                          inCtrl: _amIn,
                          outCtrl: _amOut,
                          onPick: _pickTime,
                        ),
                        _SessionRow(
                          label: 'Afternoon',
                          icon: Icons.wb_twilight_rounded,
                          inCtrl: _pmIn,
                          outCtrl: _pmOut,
                          onPick: _pickTime,
                        ),
                        _SessionRow(
                          label: 'Evening',
                          icon: Icons.nightlight_round,
                          inCtrl: _eveIn,
                          outCtrl: _eveOut,
                          onPick: _pickTime,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // ── Check-in availability ──────────────────────────
                  AppCard(
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.event_available_rounded,
                                color: AppInk.accent, size: 22),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Text(
                                'Check-in availability',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: AppInk.heading,
                                ),
                              ),
                            ),
                            if (widget.activity != null)
                              ActivityStatePill(activity: widget.activity!),
                          ],
                        ),
                        const SizedBox(height: 14),
                        DropdownButtonFormField<ActivityStatus>(
                          initialValue: _status,
                          isExpanded: true,
                          decoration: const InputDecoration(
                            labelText: 'Status',
                            helperText: 'Only “Open” accepts check-ins.',
                            border: OutlineInputBorder(),
                            isDense: true,
                          ),
                          items: [
                            for (final s in ActivityStatus.values)
                              DropdownMenuItem(
                                value: s,
                                child: Text(s.label),
                              ),
                          ],
                          onChanged: (v) => setState(
                              () => _status = v ?? ActivityStatus.open),
                        ),
                        const SizedBox(height: 14),
                        Row(
                          children: [
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Close automatically',
                                    style: TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w600,
                                      color: AppInk.heading,
                                    ),
                                  ),
                                  SizedBox(height: 3),
                                  Text(
                                    'Block check-ins outside the session windows.',
                                    style: TextStyle(
                                        fontSize: 12.5, color: AppInk.muted),
                                  ),
                                ],
                              ),
                            ),
                            Switch(
                              value: _autoClose,
                              onChanged: (v) => setState(() => _autoClose = v),
                            ),
                          ],
                        ),
                        if (_autoClose) ...[
                          const SizedBox(height: 10),
                          Row(
                            children: [
                              const Expanded(
                                child: Text(
                                  'Grace period',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: AppInk.heading,
                                  ),
                                ),
                              ),
                              Text(
                                '$_graceMinutes min',
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: AppInk.accent,
                                ),
                              ),
                            ],
                          ),
                          Slider(
                            value: _graceMinutes.toDouble().clamp(0, 120),
                            min: 0,
                            max: 120,
                            divisions: 24,
                            label: '$_graceMinutes min',
                            onChanged: (v) =>
                                setState(() => _graceMinutes = v.round()),
                          ),
                          const Text(
                            'Extra time allowed before the start and after the end.',
                            style: TextStyle(fontSize: 12, color: AppInk.muted),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppInk.critical.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline_rounded,
                              color: AppInk.critical, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _error!,
                              style: const TextStyle(
                                  color: AppInk.critical, fontSize: 13),
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
                    style: TextButton.styleFrom(foregroundColor: AppInk.critical),
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

  Widget _buildProgramField() {
    return DropdownButtonFormField<String>(
      initialValue: _programChoice,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Program',
        border: OutlineInputBorder(),
        isDense: true,
      ),
      hint: Text(_programsLoading ? 'Loading programs…' : 'Select Program'),
      items: [
        const DropdownMenuItem(value: _kCustomProgram, child: Text('Add New')),
        for (final p in _programs)
          DropdownMenuItem(
            value: p,
            child: Text(p, overflow: TextOverflow.ellipsis),
          ),
      ],
      onChanged: (v) {
        setState(() {
          _programChoice = v;
          _major = null;
          _majors = [];
        });
        if (v != null && v != _kCustomProgram) {
          _loadMajors(v);
        }
      },
    );
  }

  Widget _buildMajorField() {
    return DropdownButtonFormField<String>(
      initialValue: _major,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: 'Major',
        helperText: _majors.isEmpty && !_majorsLoading
            ? 'No majors for this program'
            : null,
        border: const OutlineInputBorder(),
        isDense: true,
      ),
      hint: Text(_majorsLoading ? 'Loading majors…' : '—'),
      items: [
        for (final m in _majors)
          DropdownMenuItem(
            value: m,
            child: Text(m, overflow: TextOverflow.ellipsis),
          ),
      ],
      onChanged: _majors.isEmpty ? null : (v) => setState(() => _major = v),
    );
  }
}

/// One session window row — label + In/Out time fields, matching the web
/// form's Morning/Afternoon/Evening rows.
class _SessionRow extends StatelessWidget {
  const _SessionRow({
    required this.label,
    required this.icon,
    required this.inCtrl,
    required this.outCtrl,
    required this.onPick,
  });

  final String label;
  final IconData icon;
  final TextEditingController inCtrl;
  final TextEditingController outCtrl;
  final Future<void> Function(TextEditingController, String) onPick;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 86,
            child: Padding(
              padding: const EdgeInsets.only(top: 14),
              child: Row(
                children: [
                  Icon(icon, size: 16, color: AppInk.muted),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      label,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppInk.body,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: AppInput(
              controller: inCtrl,
              label: 'In',
              readOnly: true,
              onTap: () => onPick(inCtrl, '$label check-in'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: AppInput(
              controller: outCtrl,
              label: 'Out',
              readOnly: true,
              onTap: () => onPick(outCtrl, '$label check-out'),
            ),
          ),
        ],
      ),
    );
  }
}

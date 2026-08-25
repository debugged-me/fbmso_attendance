import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';

/// Displays a full-screen QR poster for an activity.
/// Students scan this QR with their phone (via the PosterScanScreen)
/// to self check-in/out.
///
/// Mirrors the web `activities/(:num)/poster` page.
class ActivityPosterScreen extends StatefulWidget {
  const ActivityPosterScreen({
    super.key,
    required this.session,
    required this.activityId,
    this.activityTitle = '',
  });

  final AppSession session;
  final int activityId;
  final String activityTitle;

  @override
  State<ActivityPosterScreen> createState() => _ActivityPosterScreenState();
}

class _ActivityPosterScreenState extends State<ActivityPosterScreen> {
  late final AttendanceApi _api;
  String _checkinUrl = '';
  String _title = '';
  String _activityDate = '';
  String _location = '';
  String _program = '';
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = AttendanceApi();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await _api.posterQr(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        activityId: widget.activityId,
      );
      if (!mounted) return;
      setState(() {
        _checkinUrl = r.checkinUrl;
        _title = r.title.isNotEmpty ? r.title : widget.activityTitle;
        _activityDate = r.activityDate;
        _location = r.location;
        _program = r.program;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Activity Poster',
      showBackButton: true,
      actions: [
        IconButton(
          icon: const Icon(Icons.refresh_rounded),
          onPressed: _load,
          tooltip: 'Refresh',
        ),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.cloud_off_rounded, size: 48, color: AppInk.muted),
                        const SizedBox(height: 14),
                        Text(_error!, textAlign: TextAlign.center,
                            style: const TextStyle(color: AppInk.muted)),
                        const SizedBox(height: 16),
                        AppButton(label: 'Retry', onTap: _load),
                      ],
                    ),
                  ),
                )
              : SingleChildScrollView(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                    child: Column(
                      children: [
                        // ── QR Code ──────────────────────────────────────
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.08),
                                blurRadius: 20,
                                offset: const Offset(0, 8),
                              ),
                            ],
                          ),
                          child: QrImageView(
                            data: _checkinUrl,
                            version: QrVersions.auto,
                            size: 280,
                            gapless: true,
                            errorCorrectionLevel: QrErrorCorrectLevel.H,
                            backgroundColor: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 24),

                        // ── Activity info ────────────────────────────────
                        Text(
                          _title,
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: AppInk.heading,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 8),
                        if (_activityDate.isNotEmpty || _location.isNotEmpty)
                          Text(
                            [
                              if (_activityDate.isNotEmpty) _activityDate,
                              if (_location.isNotEmpty) _location,
                            ].join(' • '),
                            style: const TextStyle(
                              fontSize: 14,
                              color: AppInk.muted,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        if (_program.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Program: $_program',
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppInk.muted,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],

                        const SizedBox(height: 20),

                        // ── Instructions ─────────────────────────────────
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          decoration: BoxDecoration(
                            color: AppInk.accent.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.info_outline_rounded,
                                  size: 20, color: AppInk.accent),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  'Students scan this QR with their phone to self check-in/out.',
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: AppInk.accent.withValues(alpha: 0.9),
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 16),

                        // ── Check-in URL (for debugging/manual entry) ────
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppInk.rule),
                          ),
                          child: SelectableText(
                            _checkinUrl,
                            style: const TextStyle(
                              fontSize: 11,
                              fontFamily: 'monospace',
                              color: AppInk.muted,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../auth/domain/app_session.dart';
import '../data/attendance_api.dart';
import '../../../core/theme/app_icons.dart';

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
      title: 'Check-in poster',
      showBackButton: true,
      actions: [
        AppCircleButton(
          icon: AppIcons.refresh_rounded,
          onTap: _load,
          tooltip: 'Refresh',
        ),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: AppEmptyState(
                    icon: AppIcons.cloud_off_rounded,
                    title: "Couldn't load the poster",
                    subtitle: _error,
                    action: 'Try again',
                    onAction: _load,
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
                  child: Column(
                    children: [
                      AppCard.elevated(
                        radius: AppRadius.xl,
                        padding: const EdgeInsets.fromLTRB(20, 24, 20, 22),
                        child: Column(
                          children: [
                            const AppChip(
                              label: 'Scan to check in or out',
                              tone: AppInk.accent,
                              icon: AppIcons.scan,
                            ),
                            const SizedBox(height: 18),
                            QrImageView(
                              data: _checkinUrl,
                              version: QrVersions.auto,
                              size: 264,
                              gapless: true,
                              padding: EdgeInsets.zero,
                              errorCorrectionLevel: QrErrorCorrectLevel.H,
                              backgroundColor: Colors.white,
                              eyeStyle: const QrEyeStyle(
                                eyeShape: QrEyeShape.square,
                                color: AppInk.heading,
                              ),
                              dataModuleStyle: const QrDataModuleStyle(
                                dataModuleShape: QrDataModuleShape.square,
                                color: AppInk.heading,
                              ),
                            ),
                            const SizedBox(height: 22),
                            Text(
                              _title,
                              style: AppType.title.copyWith(fontSize: 22),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 6),
                            if (_activityDate.isNotEmpty || _location.isNotEmpty)
                              Text(
                                [
                                  if (_activityDate.isNotEmpty) _activityDate,
                                  if (_location.isNotEmpty) _location,
                                ].join(' · '),
                                style: AppType.body.copyWith(
                                    fontSize: 14, color: AppInk.muted),
                                textAlign: TextAlign.center,
                              ),
                            if (_program.isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Text(
                                'For $_program',
                                style: AppType.caption,
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      const AppNotice(
                        tone: AppInk.accent,
                        message: 'Students open the app, tap "Scan an activity '
                            'poster" and point their camera here to check in '
                            'or out on their own.',
                      ),
                      const SizedBox(height: 12),
                      // Check-in URL (for manual entry or troubleshooting).
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppInk.subtle,
                          borderRadius: BorderRadius.circular(AppRadius.md),
                        ),
                        child: SelectableText(
                          _checkinUrl,
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontFamily: 'monospace',
                            color: AppInk.muted,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}

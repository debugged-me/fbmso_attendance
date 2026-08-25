import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Requirements list with upload button per row. Uses image_picker for
/// file selection (camera or gallery). Uploads require connectivity.
class RequirementsScreen extends StatefulWidget {
  const RequirementsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<RequirementsScreen> createState() => _RequirementsScreenState();
}

class _RequirementsScreenState extends State<RequirementsScreen> {
  late final StudentApi _api;
  List<Requirement> _requirements = [];
  bool _loading = true;
  int? _uploadingId;

  @override
  void initState() {
    super.initState();
    _api = StudentApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await _api.requirements(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _requirements = list;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _upload(Requirement req) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );
    if (file == null) return;

    setState(() => _uploadingId = req.reqId);
    try {
      final ok = await _api.uploadRequirement(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        requirementId: req.reqId,
        file: file,
      );
      if (!mounted) return;
      if (ok) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Requirement uploaded.'),
            backgroundColor: AppTheme.success,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        );
        _load();
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      );
    } finally {
      if (mounted) setState(() => _uploadingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Requirements',
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _requirements.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.task_alt_outlined,
                              title: 'No requirements configured',
                              subtitle:
                                  'Requirements will appear here when they are set up.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                          itemCount: _requirements.length,
                          itemBuilder: (context, i) {
                            final r = _requirements[i];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: _RequirementTile(
                                req: r,
                                isUploading: _uploadingId == r.reqId,
                                onUpload: () => _upload(r),
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

class _RequirementTile extends StatelessWidget {
  const _RequirementTile({
    required this.req,
    required this.isUploading,
    required this.onUpload,
  });

  final Requirement req;
  final bool isUploading;
  final VoidCallback onUpload;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  req.name,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppInk.heading,
                    height: 1.3,
                  ),
                ),
                if (req.description.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    req.description,
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppInk.muted,
                      height: 1.35,
                    ),
                  ),
                ],
                const SizedBox(height: 10),
                if (req.isSubmitted) ...[
                  _StatusPill(
                    verified: req.isVerified,
                    label: req.isVerified
                        ? 'Verified'
                        : 'Pending verification',
                  ),
                  if (req.dateSubmitted.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text(
                      'Submitted ${req.dateSubmitted}',
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppInk.muted,
                      ),
                    ),
                  ],
                ] else
                  const Text(
                    'Not submitted',
                    style: TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          isUploading
              ? const SizedBox(
                  width: 28,
                  height: 28,
                  child: CircularProgressIndicator(strokeWidth: 2.5),
                )
              : AppButton(
                  label: req.isSubmitted ? 'Replace' : 'Upload',
                  style: AppButtonStyle.tonal,
                  size: AppButtonSize.sm,
                  icon: Icons.upload_file,
                  onTap: onUpload,
                ),
        ],
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.verified, required this.label});
  final bool verified;
  final String label;

  @override
  Widget build(BuildContext context) {
    final tone = verified ? AppInk.positive : AppInk.caution;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: tone,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: tone,
              letterSpacing: 0.2,
            ),
          ),
        ],
      ),
    );
  }
}

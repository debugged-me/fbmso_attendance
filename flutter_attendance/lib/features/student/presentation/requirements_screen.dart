import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

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
          const SnackBar(
              content: Text('Requirement uploaded.'),
              backgroundColor: AppTheme.success),
        );
        _load();
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    } finally {
      if (mounted) setState(() => _uploadingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Requirements')),
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _requirements.isEmpty
                      ? const Center(child: Text('No requirements configured.'))
                      : ListView.builder(
                          itemCount: _requirements.length,
                          itemBuilder: (context, i) {
                            final r = _requirements[i];
                            return _RequirementTile(
                              req: r,
                              isUploading: _uploadingId == r.reqId,
                              onUpload: () => _upload(r),
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
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(req.name,
                      style: Theme.of(context).textTheme.titleMedium),
                  if (req.description.isNotEmpty)
                    Text(req.description,
                        style: Theme.of(context).textTheme.bodySmall),
                  const SizedBox(height: 4),
                  if (req.isSubmitted) ...[
                    Row(
                      children: [
                        Icon(
                          req.isVerified ? Icons.verified : Icons.pending,
                          size: 16,
                          color: req.isVerified
                              ? AppTheme.success
                              : AppTheme.warning,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          req.isVerified ? 'Verified' : 'Pending verification',
                          style: TextStyle(
                            fontSize: 12,
                            color: req.isVerified
                                ? AppTheme.success
                                : AppTheme.warning,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    if (req.dateSubmitted.isNotEmpty)
                      Text('Submitted ${req.dateSubmitted}',
                          style: Theme.of(context).textTheme.bodySmall),
                  ] else
                    const Text('Not submitted',
                        style: TextStyle(color: AppTheme.textMuted)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            isUploading
                ? const SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(strokeWidth: 2))
                : FilledButton.tonalIcon(
                    onPressed: onUpload,
                    icon: const Icon(Icons.upload_file, size: 18),
                    label: Text(req.isSubmitted ? 'Replace' : 'Upload'),
                  ),
          ],
        ),
      ),
    );
  }
}

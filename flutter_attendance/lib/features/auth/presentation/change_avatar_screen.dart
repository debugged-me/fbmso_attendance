import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../data/auth_api.dart';
import '../domain/app_session.dart';

/// Change avatar screen. Shows the current avatar and lets the user pick a
/// new image to upload via `POST /api/mobile/auth/change-avatar` (multipart).
class ChangeAvatarScreen extends StatefulWidget {
  const ChangeAvatarScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<ChangeAvatarScreen> createState() => _ChangeAvatarScreenState();
}

class _ChangeAvatarScreenState extends State<ChangeAvatarScreen> {
  late final AuthApi _api;
  late final ImagePicker _picker;

  String _avatarUrl = '';
  XFile? _pickedFile;
  bool _loading = false;
  bool _uploading = false;
  String? _error;
  String? _success;

  @override
  void initState() {
    super.initState();
    _api = AuthApi();
    _picker = ImagePicker();
    _avatarUrl = widget.session.avatar;
    _loadAvatar();
  }

  Future<void> _loadAvatar() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final url = await _api.fetchAvatar(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _avatarUrl = url.isNotEmpty ? url : widget.session.avatar;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _pickImage() async {
    try {
      final file = await _picker.pickImage(
        source: ImageSource.gallery,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 85,
      );
      if (file == null) return;
      setState(() {
        _pickedFile = file;
        _success = null;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'Unable to pick image: $e');
    }
  }

  Future<void> _upload() async {
    if (_pickedFile == null) return;
    setState(() {
      _uploading = true;
      _error = null;
      _success = null;
    });
    try {
      final url = await _api.changeAvatar(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        file: _pickedFile!,
      );
      if (!mounted) return;
      setState(() {
        _uploading = false;
        _avatarUrl = url.isNotEmpty ? url : _avatarUrl;
        _pickedFile = null;
        _success = 'Avatar updated successfully.';
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _uploading = false;
        _error = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Change Avatar',
      body: Column(
        children: [
          if (_success != null) _SuccessBanner(message: _success!),
          if (_error != null) _ErrorBanner(message: _error!),
          if (_success != null || _error != null) const SizedBox(height: 16),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // ── Avatar preview ───────────────────────────────────
                  Center(
                    child: _AvatarPreview(
                      avatarUrl: _avatarUrl,
                      pickedFile: _pickedFile,
                      loading: _loading,
                    ),
                  ),
                  const SizedBox(height: 24),

                  // ── Pick button ──────────────────────────────────────
                  AppButton(
                    label: 'Choose Image',
                    icon: Icons.photo_library_outlined,
                    style: AppButtonStyle.outline,
                    fullWidth: true,
                    size: AppButtonSize.lg,
                    onTap: _pickImage,
                  ),
                  if (_pickedFile != null) ...[
                    const SizedBox(height: 14),
                    AppButton(
                      label: 'Upload Avatar',
                      icon: Icons.cloud_upload_outlined,
                      fullWidth: true,
                      size: AppButtonSize.lg,
                      loading: _uploading,
                      disabled: _uploading,
                      onTap: _upload,
                    ),
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

class _AvatarPreview extends StatelessWidget {
  const _AvatarPreview({
    required this.avatarUrl,
    required this.pickedFile,
    required this.loading,
  });

  final String avatarUrl;
  final XFile? pickedFile;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    const size = 140.0;

    if (loading) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: AppInk.accent.withValues(alpha: 0.08),
          shape: BoxShape.circle,
        ),
        child: const Center(
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
      );
    }

    ImageProvider? imageProvider;
    if (pickedFile != null) {
      imageProvider = FileImage(File(pickedFile!.path));
    } else if (avatarUrl.isNotEmpty) {
      imageProvider = NetworkImage(avatarUrl);
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: AppInk.accent.withValues(alpha: 0.08),
        border: Border.all(color: AppInk.rule, width: 2),
        image: imageProvider != null
            ? DecorationImage(image: imageProvider, fit: BoxFit.cover)
            : null,
      ),
      child: imageProvider == null
          ? const Icon(Icons.person_rounded, size: 56, color: AppInk.muted)
          : null,
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppInk.critical.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppInk.critical.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded,
              size: 18, color: AppInk.critical),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: AppInk.critical,
                fontWeight: FontWeight.w600,
                height: 1.4,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SuccessBanner extends StatelessWidget {
  const _SuccessBanner({required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppInk.positive.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppInk.positive.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          const Icon(Icons.check_circle_outline_rounded,
              size: 18, color: AppInk.positive),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: AppInk.positive,
                fontWeight: FontWeight.w600,
                height: 1.4,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

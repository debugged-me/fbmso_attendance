import 'package:flutter/material.dart';

import '../../../core/design/tokens/app_brand.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/theme/app_icons.dart';
import '../../../core/theme/app_theme.dart';

/// Inline legal dialogs that mirror the modals in the web's
/// `application/views/home_page.php` (Data Privacy, Terms of Use, About).
///
/// The web ships the full text inline in the login page so the policy is
/// always available offline and never depends on a remote `/dataprivacy`
/// route. The mobile app keeps the same content here so the two surfaces
/// stay in lock-step — when the school updates the policy on the web, the
/// same wording should be mirrored into this file.
///
/// [schoolName] is the dynamic name fetched from the connected school's
/// `o_srms_settings.SchoolName` via `GET /api/mobile/config`. It replaces
/// every previously-hardcoded "FBMSO" so the same app serves every client.
class LegalDialogs {
  LegalDialogs._();

  static Future<void> showDataPrivacy(
    BuildContext context, {
    String schoolName = '',
  }) {
    final org = _orgName(schoolName);
    return _showLegal(
      context,
      icon: AppIcons.security_rounded,
      title: 'Data Privacy Notice',
      sections: [
        _LegalSection(
          body:
              'The $org is committed to protecting the privacy and security of '
              'your personal data in compliance with the Data Privacy Act of '
              '2012 (Republic Act No. 10173).',
        ),
        _LegalSection(
          heading: 'Information We Collect',
          body:
              'We collect information necessary for attendance tracking and '
              'academic record-keeping, including your name, student ID, '
              'course, year level, section, email address, and attendance '
              'records (check-in/check-out times and locations).',
        ),
        _LegalSection(
          heading: 'How We Use Your Information',
          bullets: [
            'To record and verify your attendance at $org activities and events.',
            'To generate attendance reports for academic and organizational purposes.',
            'To communicate announcements and important updates.',
            'To maintain accurate enrollment and student records.',
          ],
        ),
        _LegalSection(
          heading: 'Data Retention',
          body:
              'Your data is retained for the duration of your enrollment and '
              'for a reasonable period thereafter as required by institutional '
              'policies and legal obligations.',
        ),
        _LegalSection(
          heading: 'Your Rights',
          bullets: [
            'Right to be informed about how your data is processed.',
            'Right to access your personal data held by $org.',
            'Right to request correction of inaccurate data.',
            'Right to request deletion of data where applicable.',
          ],
        ),
        _LegalSection(
          heading: 'Security Measures',
          body:
              'We implement appropriate technical, organizational, and '
              'physical security measures to protect your personal data '
              'against unauthorized access, alteration, disclosure, or '
              'destruction.',
        ),
        _LegalSection(
          heading: 'Contact',
          body:
              'For privacy-related concerns or requests, please contact the '
              '$org Data Protection Officer through your institution\'s '
              'official channels.',
        ),
      ],
      actionLabel: 'I understand',
    );
  }

  static Future<void> showTermsOfUse(
    BuildContext context, {
    String schoolName = '',
  }) {
    final org = _orgName(schoolName);
    return _showLegal(
      context,
      icon: AppIcons.description_rounded,
      title: 'Terms of Use',
      sections: [
        _LegalSection(
          body:
              'By accessing and using the $org Attendance Portal, you agree '
              'to the following terms and conditions:',
        ),
        _LegalSection(
          heading: '1. Acceptable Use',
          body:
              'You agree to use this system only for its intended purpose of '
              'recording and managing attendance for $org activities. You '
              'shall not attempt to manipulate, falsify, or circumvent '
              'attendance records.',
        ),
        _LegalSection(
          heading: '2. Account Security',
          body:
              'You are responsible for maintaining the confidentiality of '
              'your login credentials. Do not share your username or password '
              'with anyone. You will be held accountable for all activities '
              'performed under your account.',
        ),
        _LegalSection(
          heading: '3. Prohibited Conduct',
          bullets: [
            'Checking in on behalf of another student.',
            'Attempting to access unauthorized areas of the system.',
            'Sharing, copying, or distributing QR codes for improper use.',
            'Using the system for any unlawful or disruptive purpose.',
          ],
        ),
        _LegalSection(
          heading: '4. Intellectual Property',
          body:
              'All content, design, and functionality of this system are the '
              'property of $org and may not be reproduced or distributed '
              'without permission.',
        ),
        _LegalSection(
          heading: '5. Disclaimer',
          body:
              'The system is provided "as is" without warranties of any '
              'kind. $org is not liable for any damages arising from the use '
              'or inability to use this system.',
        ),
        _LegalSection(
          heading: '6. Modifications',
          body:
              '$org reserves the right to modify these terms at any time. '
              'Continued use of the system constitutes acceptance of the '
              'updated terms.',
        ),
      ],
      actionLabel: 'I agree',
    );
  }

  static Future<void> showAbout(
    BuildContext context, {
    String schoolName = '',
  }) {
    final org = _orgName(schoolName);
    return _showLegal(
      context,
      icon: AppIcons.info_outline_rounded,
      title: 'About',
      sections: [
        _LegalSection(
          body:
              'A QR-based attendance tracking system designed for fast, '
              'secure, and reliable check-ins at $org activities and events.',
        ),
        _LegalSection(
          bullets: [
            'QR Check-in',
            'Real-time Logs',
            'Mobile Friendly',
          ],
        ),
        _LegalSection(
          body:
              'Version ${AppBrand.aboutVersion}.1 — $org.',
        ),
      ],
      actionLabel: 'Close',
      isAbout: true,
      aboutTitle: '$org Attendance Portal',
      aboutSubtitle: org,
    );
  }

  /// Resolve the display organisation name. Falls back to the generic
  /// [AppBrand.name] when the connected school hasn't provided one (e.g.
  /// the /config probe failed on a cold start).
  static String _orgName(String schoolName) {
    final trimmed = schoolName.trim();
    return trimmed.isEmpty ? AppBrand.name : trimmed;
  }

  static Future<void> _showLegal(
    BuildContext context, {
    required IconData icon,
    required String title,
    required List<_LegalSection> sections,
    required String actionLabel,
    bool isAbout = false,
    String aboutTitle = '',
    String aboutSubtitle = '',
  }) {
    return showDialog<void>(
      context: context,
      builder: (dialogContext) {
        return Dialog(
          backgroundColor: Colors.white,
          insetPadding: const EdgeInsets.symmetric(
            horizontal: 24,
            vertical: 40,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusLg),
          ),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 520),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _LegalHeader(icon: icon, title: title),
                Flexible(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(24, 20, 24, 8),
                    child: isAbout
                        ? _AboutBody(
                            sections: sections,
                            title: aboutTitle,
                            subtitle: aboutSubtitle,
                          )
                        : _LegalBody(sections: sections),
                  ),
                ),
                _LegalFooter(actionLabel: actionLabel),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _LegalSection {
  const _LegalSection({
    this.heading,
    this.body,
    this.bullets,
  });

  final String? heading;
  final String? body;
  final List<String>? bullets;
}

class _LegalHeader extends StatelessWidget {
  const _LegalHeader({required this.icon, required this.title});
  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 20, 12, 20),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1A2A6C), Color(0xFF2A4090)],
        ),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(AppTheme.radiusLg),
          topRight: Radius.circular(AppTheme.radiusLg),
        ),
      ),
      child: Row(
        children: [
          Icon(icon, size: 22, color: Colors.white),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          IconButton(
            tooltip: 'Close',
            icon: const Icon(AppIcons.close_rounded,
                size: 20, color: Colors.white),
            onPressed: () => Navigator.of(context).pop(),
          ),
        ],
      ),
    );
  }
}

class _LegalBody extends StatelessWidget {
  const _LegalBody({required this.sections});
  final List<_LegalSection> sections;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < sections.length; i++) ...[
          if (i > 0) const SizedBox(height: 16),
          _LegalSectionView(section: sections[i]),
        ],
      ],
    );
  }
}

class _LegalSectionView extends StatelessWidget {
  const _LegalSectionView({required this.section});
  final _LegalSection section;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (section.heading != null) ...[
          Text(
            section.heading!,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: AppInk.heading,
            ),
          ),
          const SizedBox(height: 6),
        ],
        if (section.body != null)
          Text(
            section.body!,
            style: const TextStyle(
              fontSize: 13,
              height: 1.6,
              color: AppInk.body,
            ),
          ),
        if (section.bullets != null) ...[
          for (final b in section.bullets!)
            Padding(
              padding: const EdgeInsets.only(top: 4, bottom: 4, left: 4),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 8),
                    child: Text('•  ',
                        style: TextStyle(
                            color: AppInk.accent,
                            fontSize: 13,
                            fontWeight: FontWeight.w800)),
                  ),
                  Expanded(
                    child: Text(
                      b,
                      style: const TextStyle(
                        fontSize: 13,
                        height: 1.6,
                        color: AppInk.body,
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ],
    );
  }
}

class _AboutBody extends StatelessWidget {
  const _AboutBody({
    required this.sections,
    required this.title,
    required this.subtitle,
  });
  final List<_LegalSection> sections;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: AppTheme.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppTheme.cardBorder),
          ),
          alignment: Alignment.center,
          child: const Icon(AppIcons.security_rounded,
              size: 32, color: AppInk.accent),
        ),
        const SizedBox(height: 14),
        Text(
          title,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: AppInk.heading,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 12, color: AppInk.muted),
        ),
        const SizedBox(height: 14),
        for (final s in sections) ...[
          if (s.body != null)
            Text(
              s.body!,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                height: 1.6,
                color: AppInk.body,
              ),
            ),
          if (s.bullets != null) ...[
            const SizedBox(height: 12),
            Wrap(
              alignment: WrapAlignment.center,
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final b in s.bullets!)
                  _AboutBadge(label: b),
              ],
            ),
          ],
          const SizedBox(height: 10),
        ],
      ],
    );
  }
}

class _AboutBadge extends StatelessWidget {
  const _AboutBadge({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppInk.accent.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppInk.accent.withValues(alpha: 0.25)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: AppInk.accent,
        ),
      ),
    );
  }
}

class _LegalFooter extends StatelessWidget {
  const _LegalFooter({required this.actionLabel});
  final String actionLabel;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 12, 24, 16),
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: AppInk.rule)),
      ),
      child: Align(
        alignment: Alignment.centerRight,
        child: FilledButton(
          onPressed: () => Navigator.of(context).pop(),
          style: FilledButton.styleFrom(
            backgroundColor: AppInk.accent,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 10),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            ),
          ),
          child: Text(actionLabel),
        ),
      ),
    );
  }
}

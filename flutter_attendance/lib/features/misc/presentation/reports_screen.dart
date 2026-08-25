import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Reports screen — enrollment + attendance summary.
class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key, required this.session});
  final AppSession session;

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  late final MiscApi _api;
  ReportSummary? _report;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await _api.reportSummary(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() { _report = r; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Reports',
      showBackButton: true,
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? ListView(children: [
                          const SizedBox(height: 80),
                          AppEmptyState(
                            icon: Icons.cloud_off_rounded,
                            title: 'Failed to load',
                            subtitle: _error,
                            action: 'Retry',
                            onAction: _load,
                          ),
                        ])
                      : _report == null
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.assessment_outlined,
                                title: 'No data',
                                subtitle: 'No report data available.',
                              ),
                            ])
                          : _ReportContent(report: _report!),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportContent extends StatelessWidget {
  const _ReportContent({required this.report});
  final ReportSummary report;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      children: [
        // ── Summary cards ──────────────────────────────────────
        Row(
          children: [
            Expanded(
              child: _StatCard(
                label: 'Total Events',
                value: '${report.eventsTotal}',
                icon: Icons.event_rounded,
                tone: AppInk.accent,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _StatCard(
                label: 'Total Scans',
                value: '${report.eventScans}',
                icon: Icons.qr_code_scanner_rounded,
                tone: AppInk.positive,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (report.sy.isNotEmpty || report.sem.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(
              'SY: ${report.sy}  |  Sem: ${report.sem}',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppInk.muted),
            ),
          ),

        // ── By Year Level ──────────────────────────────────────
        if (report.byYearLevel.isNotEmpty) ...[
          const AppSectionHeader(title: 'Enrollment by Year Level'),
          ...report.byYearLevel.map((r) => _BarRow(
                label: '${r.yearLevel} Year',
                value: r.count,
                max: report.byYearLevel.fold<int>(0, (m, e) => e.count > m ? e.count : m),
              )),
          const SizedBox(height: 24),
        ],

        // ── By Course ──────────────────────────────────────────
        if (report.byCourse.isNotEmpty) ...[
          const AppSectionHeader(title: 'Enrollment by Course'),
          ...report.byCourse.map((r) => _BarRow(
                label: r.course,
                value: r.count,
                max: report.byCourse.fold<int>(0, (m, e) => e.count > m ? e.count : m),
              )),
          const SizedBox(height: 24),
        ],

        // ── Sections count ─────────────────────────────────────
        if (report.sectionsCount.isNotEmpty) ...[
          const AppSectionHeader(title: 'Sections per Course'),
          ...report.sectionsCount.map((r) => Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(r.course,
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppInk.heading),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppInk.accent.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text('${r.sections}',
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppInk.accent)),
                    ),
                  ],
                ),
              )),
        ],
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.label, required this.value, required this.icon, required this.tone});
  final String label;
  final String value;
  final IconData icon;
  final Color tone;

  @override
  Widget build(BuildContext context) {
    return AppCard.elevated(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 36, height: 36,
            decoration: BoxDecoration(
              color: tone.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: tone),
          ),
          const SizedBox(height: 12),
          Text(label.toUpperCase(),
              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppInk.muted, letterSpacing: 0.5)),
          const SizedBox(height: 4),
          Text(value,
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: AppInk.heading)),
        ],
      ),
    );
  }
}

class _BarRow extends StatelessWidget {
  const _BarRow({required this.label, required this.value, required this.max});
  final String label;
  final int value;
  final int max;

  @override
  Widget build(BuildContext context) {
    final pct = max > 0 ? (value / max).clamp(0.0, 1.0) : 0.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(label,
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppInk.heading),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ),
              Text('$value',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: AppInk.accent)),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: pct,
              minHeight: 8,
              backgroundColor: AppInk.accent.withValues(alpha: 0.08),
              valueColor: AlwaysStoppedAnimation<Color>(AppInk.accent),
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/student_api.dart';
import '../domain/student_models.dart';

/// Student profile detail. Cache-first so it renders offline.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late final StudentApi _api;
  StudentProfile? _profile;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _api = StudentApi();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final p = await _api.profile(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
      );
      if (!mounted) return;
      setState(() {
        _profile = p;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  void _showEdit() {
    if (_profile == null) return;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ProfileEditForm(
        api: _api,
        session: widget.session,
        profile: _profile!,
        onSaved: _load,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'My Profile',
      actions: [
        if (_profile != null)
          IconButton(
            tooltip: 'Edit profile',
            icon: const Icon(Icons.edit_outlined),
            onPressed: _showEdit,
          ),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _load,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _profile == null
                      ? ListView(
                          children: [
                            const SizedBox(height: 120),
                            AppEmptyState(
                              icon: Icons.person_outline,
                              title: 'No profile data',
                              subtitle:
                                  'Your profile information will appear here.',
                            ),
                          ],
                        )
                      : ListView(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                          children: [
                            AppCard.elevated(
                              padding: const EdgeInsets.all(20),
                              child: Row(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      color:
                                          AppInk.accent.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: ClipRRect(
                                      borderRadius: BorderRadius.circular(16),
                                      child: widget.session.avatar.isNotEmpty
                                          ? Image.network(
                                              widget.session.avatar,
                                              fit: BoxFit.cover,
                                              width: 52,
                                              height: 52,
                                              errorBuilder: (c, e, s) =>
                                                  const Icon(Icons.person,
                                                      size: 26,
                                                      color: AppInk.accent),
                                            )
                                          : const Icon(Icons.person,
                                              size: 26, color: AppInk.accent),
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          _profile!.fullName,
                                          style: const TextStyle(
                                            fontSize: 18,
                                            fontWeight: FontWeight.w700,
                                            color: AppInk.heading,
                                            height: 1.25,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          _profile!.studentNumber,
                                          style: const TextStyle(
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.w500,
                                            color: AppInk.muted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Academic',
                              rows: [
                                ('Course', _profile!.course),
                                ('Major', _profile!.major),
                                ('Status', _profile!.status),
                                ('Enrollment Date', _profile!.enrollmentDate),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Personal',
                              rows: [
                                ('Sex', _profile!.sex),
                                ('Birth Date', _profile!.birthDate),
                                ('Civil Status', _profile!.civilStatus),
                                ('Ethnicity', _profile!.ethnicity),
                                ('Religion', _profile!.religion),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Contact',
                              rows: [
                                ('Email', _profile!.email),
                                ('Contact No', _profile!.contactNo),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _Section(
                              title: 'Address',
                              rows: [
                                ('Sitio', _profile!.sitio),
                                ('Barangay', _profile!.barangay),
                                ('City', _profile!.city),
                                ('Province', _profile!.province),
                              ],
                            ),
                          ],
                        ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.rows});
  final String title;
  final List<(String, String)> rows;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppSectionHeader(title: title),
        const SizedBox(height: 10),
        AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          child: Column(
            children: [
              for (var i = 0; i < rows.length; i++) ...[
                _Row(label: rows[i].$1, value: rows[i].$2),
                if (i != rows.length - 1) const AppRule(),
              ]
            ],
          ),
        ),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 13),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppInk.muted,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '—' : value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: AppInk.heading,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Edit own profile — web Page/studentProfile → updateStudentProfile.
/// Sends only the fields the API exposes; academic fields stay read-only.
class _ProfileEditForm extends StatefulWidget {
  const _ProfileEditForm({
    required this.api,
    required this.session,
    required this.profile,
    required this.onSaved,
  });
  final StudentApi api;
  final AppSession session;
  final StudentProfile profile;
  final VoidCallback onSaved;

  @override
  State<_ProfileEditForm> createState() => _ProfileEditFormState();
}

class _ProfileEditFormState extends State<_ProfileEditForm> {
  late final TextEditingController _firstName;
  late final TextEditingController _middleName;
  late final TextEditingController _lastName;
  late final TextEditingController _contactNo;
  late final TextEditingController _birthDate;
  late final TextEditingController _email;
  late final TextEditingController _sitio;
  late final TextEditingController _brgy;
  late final TextEditingController _city;
  late final TextEditingController _province;
  String _sex = '';
  String _civilStatus = '';
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final p = widget.profile;
    _firstName = TextEditingController(text: p.firstName);
    _middleName = TextEditingController(text: p.middleName);
    _lastName = TextEditingController(text: p.lastName);
    _contactNo = TextEditingController(text: p.contactNo);
    _birthDate = TextEditingController(text: p.birthDate);
    _email = TextEditingController(text: p.email);
    _sitio = TextEditingController(text: p.sitio);
    _brgy = TextEditingController(text: p.barangay);
    _city = TextEditingController(text: p.city);
    _province = TextEditingController(text: p.province);
    _sex = p.sex;
    _civilStatus = p.civilStatus;
  }

  @override
  void dispose() {
    for (final c in [
      _firstName, _middleName, _lastName, _contactNo, _birthDate,
      _email, _sitio, _brgy, _city, _province,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await widget.api.updateProfile(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        firstName: _firstName.text.trim(),
        middleName: _middleName.text.trim(),
        lastName: _lastName.text.trim(),
        sex: _sex,
        civilStatus: _civilStatus,
        contactNo: _contactNo.text.trim(),
        birthDate: _birthDate.text.trim(),
        email: _email.text.trim(),
        sitio: _sitio.text.trim(),
        brgy: _brgy.text.trim(),
        city: _city.text.trim(),
        province: _province.text.trim(),
      );
      if (!mounted) return;
      widget.onSaved();
      Navigator.of(context).pop();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(
          24, 12, 24, 32 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: AppInk.rule,
                    borderRadius: BorderRadius.circular(999)),
              ),
            ),
            const SizedBox(height: 20),
            const Text('Edit Profile',
                style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!,
                  style:
                      const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(
                controller: _firstName,
                label: 'First Name',
                prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(
                controller: _middleName,
                label: 'Middle Name',
                prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(
                controller: _lastName,
                label: 'Last Name',
                prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _sex.isEmpty ? null : _sex,
              decoration: InputDecoration(
                labelText: 'Sex',
                prefixIcon: const Icon(Icons.wc_outlined,
                    size: 20, color: AppInk.muted),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14)),
              ),
              items: const [
                DropdownMenuItem(value: 'Male', child: Text('Male')),
                DropdownMenuItem(value: 'Female', child: Text('Female')),
              ],
              onChanged: (v) => setState(() => _sex = v ?? ''),
            ),
            const SizedBox(height: 14),
            GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime.tryParse(_birthDate.text) ??
                      DateTime(2000),
                  firstDate: DateTime(1950),
                  lastDate: DateTime.now(),
                );
                if (picked != null) {
                  _birthDate.text =
                      '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
                }
              },
              child: AbsorbPointer(
                child: AppInput(
                  controller: _birthDate,
                  label: 'Birth Date',
                  prefixIcon: Icons.cake_outlined,
                ),
              ),
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _civilStatus.isEmpty ? null : _civilStatus,
              decoration: InputDecoration(
                labelText: 'Civil Status',
                prefixIcon: const Icon(Icons.favorite_outline_rounded,
                    size: 20, color: AppInk.muted),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14)),
              ),
              items: const [
                DropdownMenuItem(value: 'Single', child: Text('Single')),
                DropdownMenuItem(value: 'Married', child: Text('Married')),
                DropdownMenuItem(value: 'Widowed', child: Text('Widowed')),
                DropdownMenuItem(
                    value: 'Separated', child: Text('Separated')),
              ],
              onChanged: (v) => setState(() => _civilStatus = v ?? ''),
            ),
            const SizedBox(height: 14),
            AppInput(
                controller: _email,
                label: 'Email',
                prefixIcon: Icons.mail_outline_rounded),
            const SizedBox(height: 14),
            AppInput(
                controller: _contactNo,
                label: 'Contact No',
                prefixIcon: Icons.phone_outlined),
            const SizedBox(height: 14),
            AppInput(
                controller: _sitio,
                label: 'Sitio',
                prefixIcon: Icons.location_on_outlined),
            const SizedBox(height: 14),
            AppInput(
                controller: _brgy,
                label: 'Barangay',
                prefixIcon: Icons.location_on_outlined),
            const SizedBox(height: 14),
            AppInput(
                controller: _city,
                label: 'City',
                prefixIcon: Icons.location_city_outlined),
            const SizedBox(height: 14),
            AppInput(
                controller: _province,
                label: 'Province',
                prefixIcon: Icons.map_outlined),
            const SizedBox(height: 20),
            AppButton(
              label: 'Save Changes',
              fullWidth: true,
              size: AppButtonSize.lg,
              loading: _saving,
              disabled: _saving,
              onTap: _save,
            ),
          ],
        ),
      ),
    );
  }
}

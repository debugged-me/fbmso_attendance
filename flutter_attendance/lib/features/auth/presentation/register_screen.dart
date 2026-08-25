import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import 'auth_controller.dart';
import 'login_screen.dart';

/// Student registration screen — mirrors the web Registration form.
///
/// Web layout:
/// - Gradient banner: "New Student Account" / "Create Your Profile"
/// - Section 1: Student Credentials (Student ID, Password, Confirm Password)
/// - Section 2: Personal Information (First, Middle, Last, Ext, Sex, DOB, Email, Mobile)
/// - Section 3: Academic Information (Course, Year Level, Section)
/// - Submit button: "Create My Account"
class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, required this.controller});

  final AuthController controller;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  late final TextEditingController _studentNumber;
  late final TextEditingController _firstName;
  late final TextEditingController _middleName;
  late final TextEditingController _lastName;
  late final TextEditingController _nameExtn;
  late final TextEditingController _email;
  late final TextEditingController _contactNo;
  late final TextEditingController _password;
  late final TextEditingController _confirmPassword;

  String _sex = '';
  DateTime? _birthDate;
  String _yearLevel = '';
  String _course = '';
  String _section = '';
  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _busy = false;
  bool _loadingOptions = true;
  String? _error;
  String? _success;

  List<String> _courses = [];
  List<String> _yearLevels = ['1st', '2nd', '3rd', '4th'];
  List<String> _sections = [];

  @override
  void initState() {
    super.initState();
    _studentNumber = TextEditingController();
    _firstName = TextEditingController();
    _middleName = TextEditingController();
    _lastName = TextEditingController();
    _nameExtn = TextEditingController();
    _email = TextEditingController();
    _contactNo = TextEditingController();
    _password = TextEditingController();
    _confirmPassword = TextEditingController();
    _loadOptions();
  }

  @override
  void dispose() {
    _studentNumber.dispose();
    _firstName.dispose();
    _middleName.dispose();
    _lastName.dispose();
    _nameExtn.dispose();
    _email.dispose();
    _contactNo.dispose();
    _password.dispose();
    _confirmPassword.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final opts = await widget.controller.registrationOptions();
      if (!mounted) return;
      setState(() {
        _courses = opts.courses;
        _yearLevels = opts.yearLevels.isNotEmpty ? opts.yearLevels : ['1st', '2nd', '3rd', '4th'];
        _sections = opts.sections;
        _loadingOptions = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingOptions = false);
    }
  }

  Future<void> _pickBirthDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year - 18),
      firstDate: DateTime(1950),
      lastDate: now,
    );
    if (picked != null) setState(() => _birthDate = picked);
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    HapticFeedback.mediumImpact();

    if (_studentNumber.text.trim().isEmpty ||
        _firstName.text.trim().isEmpty ||
        _lastName.text.trim().isEmpty ||
        _email.text.trim().isEmpty ||
        _password.text.isEmpty ||
        _yearLevel.isEmpty) {
      setState(() => _error = 'Please fill in all required fields.');
      return;
    }

    if (_password.text.length < 8) {
      setState(() => _error = 'Password must be at least 8 characters.');
      return;
    }

    if (_password.text != _confirmPassword.text) {
      setState(() => _error = 'Passwords do not match.');
      return;
    }

    setState(() {
      _busy = true;
      _error = null;
      _success = null;
    });

    final error = await widget.controller.register(
      studentNumber: _studentNumber.text.trim(),
      firstName: _firstName.text.trim(),
      middleName: _middleName.text.trim(),
      lastName: _lastName.text.trim(),
      nameExtn: _nameExtn.text.trim(),
      sex: _sex,
      birthDate: _birthDate != null
          ? '${_birthDate!.year}-${_birthDate!.month.toString().padLeft(2, '0')}-${_birthDate!.day.toString().padLeft(2, '0')}'
          : '',
      email: _email.text.trim(),
      contactNo: _contactNo.text.trim(),
      course1: _course,
      yearLevel: _yearLevel,
      section: _section,
      password: _password.text,
      confirmPassword: _confirmPassword.text,
    );

    if (!mounted) return;

    if (error == null) {
      setState(() {
        _busy = false;
        _success =
            'Registration successful! You can now sign in with your Student ID and password.';
      });
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) {
          Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(
              builder: (_) => LoginScreen(controller: widget.controller),
            ),
            (_) => false,
          );
        }
      });
    } else {
      setState(() {
        _busy = false;
        _error = error;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F4FF),
      body: SafeArea(
        child: Column(
          children: [
            // ── Top bar ──────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 16, 0),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_rounded,
                        color: AppInk.heading),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                  const Spacer(),
                ],
              ),
            ),

            // ── Scrollable form ──────────────────────────────────────
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // ── Gradient Banner ──────────────────────────────
                    _Banner(),
                    const SizedBox(height: 24),

                    // ── Glassmorphism card body ──────────────────────
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.85),
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(
                            color: Colors.white.withValues(alpha: 0.9),
                            width: 1),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF6482C8).withValues(alpha: 0.10),
                            blurRadius: 40,
                            offset: const Offset(0, 20),
                          ),
                        ],
                      ),
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // ── Error / Success ──────────────────────────
                          if (_error != null) ...[
                            _BannerMsg(message: _error!, isError: true),
                            const SizedBox(height: 16),
                          ],
                          if (_success != null) ...[
                            _BannerMsg(message: _success!, isError: false),
                            const SizedBox(height: 16),
                          ],

                          // ── Section 1: Student Credentials ──────────
                          _SectionHeader(label: 'Student Credentials'),
                          const SizedBox(height: 16),
                          AppInput(
                            controller: _studentNumber,
                            label: 'Student ID *',
                            hint: 'e.g. 2023-0446',
                            prefixIcon: Icons.badge_outlined,
                            textInputAction: TextInputAction.next,
                            autofillHints: const ['username'],
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'This becomes your username — match it to your school ID.',
                            style: TextStyle(
                                fontSize: 11, color: AppInk.muted, height: 1.4),
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _password,
                            label: 'Password *',
                            hint: 'At least 8 characters',
                            prefixIcon: Icons.lock_outline_rounded,
                            obscureText: _obscurePassword,
                            textInputAction: TextInputAction.next,
                            autofillHints: const ['new-password'],
                            suffixIcon: GestureDetector(
                              onTap: () => setState(
                                  () => _obscurePassword = !_obscurePassword),
                              child: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                size: 20,
                                color: AppInk.muted,
                              ),
                            ),
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _confirmPassword,
                            label: 'Confirm Password *',
                            hint: 'Repeat your password',
                            prefixIcon: Icons.lock_outline_rounded,
                            obscureText: _obscureConfirm,
                            textInputAction: TextInputAction.next,
                            autofillHints: const ['new-password'],
                            suffixIcon: GestureDetector(
                              onTap: () => setState(
                                  () => _obscureConfirm = !_obscureConfirm),
                              child: Icon(
                                _obscureConfirm
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                size: 20,
                                color: AppInk.muted,
                              ),
                            ),
                          ),
                          const SizedBox(height: 28),

                          // ── Section 2: Personal Information ──────────
                          _SectionHeader(label: 'Personal Information'),
                          const SizedBox(height: 16),
                          AppInput(
                            controller: _firstName,
                            label: 'First Name *',
                            hint: 'Enter first name',
                            prefixIcon: Icons.person_outline_rounded,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _middleName,
                            label: 'Middle Name',
                            hint: 'Enter middle name (optional)',
                            prefixIcon: Icons.person_outline_rounded,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _lastName,
                            label: 'Last Name *',
                            hint: 'Enter last name',
                            prefixIcon: Icons.person_outline_rounded,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _nameExtn,
                            label: 'Ext.',
                            hint: 'Jr., Sr.',
                            prefixIcon: Icons.person_outline_rounded,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<String>(
                            initialValue: _sex.isEmpty ? null : _sex,
                            decoration: _dropdownDecoration('Sex *',
                                Icons.wc_rounded),
                            items: ['Female', 'Male', 'Others']
                                .map((s) =>
                                    DropdownMenuItem(value: s, child: Text(s)))
                                .toList(),
                            onChanged: (v) => setState(() => _sex = v ?? ''),
                          ),
                          const SizedBox(height: 14),
                          GestureDetector(
                            onTap: _pickBirthDate,
                            child: AbsorbPointer(
                              child: AppInput(
                                controller: TextEditingController(
                                  text: _birthDate != null
                                      ? '${_birthDate!.year}-${_birthDate!.month.toString().padLeft(2, '0')}-${_birthDate!.day.toString().padLeft(2, '0')}'
                                      : '',
                                ),
                                label: 'Date of Birth *',
                                hint: 'Tap to select date',
                                prefixIcon: Icons.calendar_today_rounded,
                              ),
                            ),
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _email,
                            label: 'E-mail Address *',
                            hint: 'you@email.com',
                            prefixIcon: Icons.email_outlined,
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.next,
                            autofillHints: const ['email'],
                          ),
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _contactNo,
                            label: 'Mobile No. *',
                            hint: '09XX XXX XXXX',
                            prefixIcon: Icons.phone_outlined,
                            keyboardType: TextInputType.phone,
                            textInputAction: TextInputAction.next,
                          ),
                          const SizedBox(height: 28),

                          // ── Section 3: Academic Information ──────────
                          _SectionHeader(label: 'Academic Information'),
                          const SizedBox(height: 16),
                          _loadingOptions
                              ? const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 20),
                                  child: Center(
                                    child: SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                          strokeWidth: 2.5),
                                    ),
                                  ),
                                )
                              : Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    DropdownButtonFormField<String>(
                                      initialValue:
                                          _course.isEmpty ? null : _course,
                                      decoration: _dropdownDecoration(
                                          'Course / Program *',
                                          Icons.school_outlined),
                                      items: _courses
                                          .map((s) => DropdownMenuItem(
                                              value: s, child: Text(s)))
                                          .toList(),
                                      onChanged: (v) =>
                                          setState(() => _course = v ?? ''),
                                    ),
                                    const SizedBox(height: 14),
                                    DropdownButtonFormField<String>(
                                      initialValue: _yearLevel.isEmpty
                                          ? null
                                          : _yearLevel,
                                      decoration: _dropdownDecoration(
                                          'Year Level *',
                                          Icons.stairs_outlined),
                                      items: _yearLevels
                                          .map((s) => DropdownMenuItem(
                                              value: s,
                                              child: Text('$s Year')))
                                          .toList(),
                                      onChanged: (v) =>
                                          setState(() => _yearLevel = v ?? ''),
                                    ),
                                    const SizedBox(height: 14),
                                    DropdownButtonFormField<String>(
                                      initialValue:
                                          _section.isEmpty ? null : _section,
                                      decoration: _dropdownDecoration(
                                          'Section *',
                                          Icons.group_outlined),
                                      items: _sections
                                          .map((s) => DropdownMenuItem(
                                              value: s, child: Text(s)))
                                          .toList(),
                                      onChanged: (v) =>
                                          setState(() => _section = v ?? ''),
                                    ),
                                  ],
                                ),
                          const SizedBox(height: 28),

                          // ── Submit ─────────────────────────────────────
                          Container(
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFF2A4090)
                                      .withValues(alpha: 0.25),
                                  blurRadius: 20,
                                  offset: const Offset(0, 8),
                                ),
                              ],
                            ),
                            child: AppButton(
                              label: 'Create My Account',
                              fullWidth: true,
                              size: AppButtonSize.lg,
                              loading: _busy,
                              disabled: _busy,
                              onTap: _submit,
                            ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.lock_outline_rounded,
                                  size: 14, color: AppInk.muted),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  'Your information is securely stored and used solely for attendance purposes.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: AppInk.muted,
                                    height: 1.4,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          Center(
                            child: TextButton(
                              onPressed: () =>
                                  Navigator.of(context).pop(),
                              child: const Text.rich(
                                TextSpan(
                                  text: 'Already have an account? ',
                                  style: TextStyle(
                                      color: AppInk.muted, fontSize: 13),
                                  children: [
                                    TextSpan(
                                      text: 'Sign in',
                                      style: TextStyle(
                                        color: AppInk.accent,
                                        fontWeight: FontWeight.w800,
                                        fontSize: 13,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  InputDecoration _dropdownDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, size: 20, color: AppInk.muted),
      filled: true,
      fillColor: Colors.white,
      contentPadding:
          const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Color(0xFFB7C9F3), width: 1.5),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppInk.accent, width: 2),
      ),
    );
  }
}

/// Gradient banner matching the web registration card banner.
class _Banner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1A2A6C), Color(0xFF2A4090), Color(0xFF3B5FD4)],
        ),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'NEW STUDENT ACCOUNT',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              letterSpacing: 2,
              color: Colors.white54,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Create Your Profile',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Fill in the form to get started with\nyour attendance tracking account.',
            style: TextStyle(
              fontSize: 13,
              color: Colors.white54,
              height: 1.6,
            ),
          ),
          const SizedBox(height: 20),
          // QR-like decoration
          Row(
            children: [
              for (int i = 0; i < 3; i++)
                Container(
                  width: 28,
                  height: 28,
                  margin: const EdgeInsets.only(right: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(
                        color: Colors.white.withValues(alpha: 0.25), width: 1.5),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Section header matching the web "section-head" style.
class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF2A4090), Color(0xFF4266D4)],
            ),
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 10),
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.w800,
            letterSpacing: 2,
            color: Color(0xFF2A4090),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Container(
            height: 1,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  const Color(0xFFE2E9FF),
                  Colors.transparent,
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _BannerMsg extends StatelessWidget {
  const _BannerMsg({required this.message, required this.isError});
  final String message;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final color = isError ? AppInk.critical : AppInk.positive;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(
            isError ? Icons.error_rounded : Icons.check_circle_rounded,
            size: 18,
            color: color,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: color,
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

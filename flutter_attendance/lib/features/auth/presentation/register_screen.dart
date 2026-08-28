import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import 'auth_controller.dart';

/// Student registration screen — mirrors the web Registration form.
///
/// Web layout:
/// - Gradient banner: "New Student Account" / "Create Your Profile"
/// - Section 1: Student Credentials (Student ID + availability, Password, Confirm)
/// - Section 2: Personal Information (First, Middle, Last, Ext, Sex, DOB, Email + availability, Mobile)
/// - Section 3: Academic Information (Course, Year Level, Section — cascading)
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

  // Cascading sections for the selected course + year level
  List<String> _sections = [];
  bool _loadingSections = false;

  // Availability check state
  String? _studentIdStatus; // null = not checked, message string
  bool? _studentIdAvailable; // true = available, false = taken
  bool _checkingStudentId = false;
  String? _emailStatus;
  bool? _emailAvailable;
  bool _checkingEmail = false;

  Timer? _studentIdTimer;
  Timer? _emailTimer;

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
    _studentNumber.addListener(_onStudentIdChanged);
    _email.addListener(_onEmailChanged);
    _loadOptions();
  }

  @override
  void dispose() {
    _studentIdTimer?.cancel();
    _emailTimer?.cancel();
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
        _yearLevels =
            opts.yearLevels.isNotEmpty ? opts.yearLevels : ['1st', '2nd', '3rd', '4th'];
        _loadingOptions = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingOptions = false);
    }
  }

  // ── Availability checkers (debounced) ──────────────────────────────────

  void _onStudentIdChanged() {
    _studentIdTimer?.cancel();
    final value = _studentNumber.text.trim();
    if (value.length < 4) {
      setState(() {
        _studentIdStatus = null;
        _studentIdAvailable = null;
        _checkingStudentId = false;
      });
      return;
    }
    setState(() {
      _checkingStudentId = true;
      _studentIdStatus = null;
      _studentIdAvailable = null;
    });
    _studentIdTimer = Timer(const Duration(milliseconds: 600), () {
      _checkStudentId(value);
    });
  }

  Future<void> _checkStudentId(String value) async {
    try {
      final result = await widget.controller.checkAvailability(
        field: 'studentnumber',
        value: value,
      );
      if (!mounted) return;
      setState(() {
        _checkingStudentId = false;
        _studentIdStatus = result.message;
        _studentIdAvailable = !result.exists;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _checkingStudentId = false;
        _studentIdStatus = null;
        _studentIdAvailable = null;
      });
    }
  }

  void _onEmailChanged() {
    _emailTimer?.cancel();
    final value = _email.text.trim();
    if (!value.contains('@') || value.length < 5) {
      setState(() {
        _emailStatus = null;
        _emailAvailable = null;
        _checkingEmail = false;
      });
      return;
    }
    setState(() {
      _checkingEmail = true;
      _emailStatus = null;
      _emailAvailable = null;
    });
    _emailTimer = Timer(const Duration(milliseconds: 600), () {
      _checkEmail(value);
    });
  }

  Future<void> _checkEmail(String value) async {
    try {
      final result = await widget.controller.checkAvailability(
        field: 'email',
        value: value,
      );
      if (!mounted) return;
      setState(() {
        _checkingEmail = false;
        _emailStatus = result.message;
        _emailAvailable = !result.exists;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _checkingEmail = false;
        _emailStatus = null;
        _emailAvailable = null;
      });
    }
  }

  // ── Cascading sections ─────────────────────────────────────────────────

  Future<void> _loadSections() async {
    if (_course.isEmpty || _yearLevel.isEmpty) {
      setState(() {
        _sections = [];
        _section = '';
      });
      return;
    }
    setState(() {
      _loadingSections = true;
      _section = '';
    });
    try {
      final sections = await widget.controller.registrationSections(
        course: _course,
        yearLevel: _yearLevel,
      );
      if (!mounted) return;
      setState(() {
        _sections = sections;
        _loadingSections = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        _loadingSections = false;
      });
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

    // Validate availability
    if (_studentIdAvailable == false) {
      setState(() => _error = 'Student ID already exists. Please choose a different one.');
      return;
    }
    if (_emailAvailable == false) {
      setState(() => _error = 'Email already exists. Please use a different email.');
      return;
    }

    if (_studentNumber.text.trim().isEmpty ||
        _firstName.text.trim().isEmpty ||
        _lastName.text.trim().isEmpty ||
        _email.text.trim().isEmpty ||
        _password.text.isEmpty ||
        _yearLevel.isEmpty ||
        _course.isEmpty) {
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
      // This screen was pushed on top of the login screen, so pop back to it.
      // Replacing the whole stack would discard the root route that listens to
      // the AuthController, breaking the sign-in that follows.
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) Navigator.of(context).maybePop();
      });
    } else {
      setState(() {
        _busy = false;
        _error = error;
      });
    }
  }

  InputDecoration _dropdownDecoration(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, size: 20, color: AppInk.muted),
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
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

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    return Scaffold(
      resizeToAvoidBottomInset: false,
      backgroundColor: const Color(0xFFF0F4FF),
      body: SafeArea(
        child: Column(
          children: [
            // ── Top bar ──
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 16, 0),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_rounded, color: AppInk.heading),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                  const Spacer(),
                ],
              ),
            ),

            // ── Scrollable form ──
            Expanded(
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(20, 0, 20, 32 + bottomInset),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _Banner(),
                    const SizedBox(height: 24),

                    // ── Glassmorphism card body ──
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.85),
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(
                            color: Colors.white.withValues(alpha: 0.9), width: 1),
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
                          if (_error != null) ...[
                            _BannerMsg(message: _error!, isError: true),
                            const SizedBox(height: 16),
                          ],
                          if (_success != null) ...[
                            _BannerMsg(message: _success!, isError: false),
                            const SizedBox(height: 16),
                          ],

                          // ── Section 1: Student Credentials ──
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
                          // Availability indicator
                          if (_checkingStudentId ||
                              _studentIdStatus != null) ...[
                            const SizedBox(height: 6),
                            _AvailabilityIndicator(
                              checking: _checkingStudentId,
                              available: _studentIdAvailable,
                              message: _studentIdStatus,
                            ),
                          ] else ...[
                            const SizedBox(height: 6),
                            const Text(
                              'This becomes your username — match it to your school ID.',
                              style: TextStyle(
                                  fontSize: 11, color: AppInk.muted, height: 1.4),
                            ),
                          ],
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
                              onTap: () =>
                                  setState(() => _obscurePassword = !_obscurePassword),
                              child: Icon(
                                _obscurePassword ? Icons.visibility_off : Icons.visibility,
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
                              onTap: () =>
                                  setState(() => _obscureConfirm = !_obscureConfirm),
                              child: Icon(
                                _obscureConfirm ? Icons.visibility_off : Icons.visibility,
                                size: 20,
                                color: AppInk.muted,
                              ),
                            ),
                          ),
                          const SizedBox(height: 28),

                          // ── Section 2: Personal Information ──
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
                            isExpanded: true,
                            decoration: _dropdownDecoration('Sex *', Icons.wc_rounded),
                            items: ['Female', 'Male', 'Others']
                                .map((s) => DropdownMenuItem(value: s, child: Text(s)))
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
                          // Email availability indicator
                          if (_checkingEmail || _emailStatus != null) ...[
                            const SizedBox(height: 6),
                            _AvailabilityIndicator(
                              checking: _checkingEmail,
                              available: _emailAvailable,
                              message: _emailStatus,
                            ),
                          ],
                          const SizedBox(height: 14),
                          AppInput(
                            controller: _contactNo,
                            label: 'Mobile No. *',
                            hint: '09XX XXX XXXX',
                            prefixIcon: Icons.phone_outlined,
                            keyboardType: TextInputType.phone,
                            textInputAction: TextInputAction.next,
                            maxLength: 11,
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                            ],
                          ),
                          const SizedBox(height: 28),

                          // ── Section 3: Academic Information ──
                          _SectionHeader(label: 'Academic Information'),
                          const SizedBox(height: 16),
                          _loadingOptions
                              ? const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 20),
                                  child: Center(
                                    child: SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(strokeWidth: 2.5),
                                    ),
                                  ),
                                )
                              : Column(
                                  crossAxisAlignment: CrossAxisAlignment.stretch,
                                  children: [
                                    DropdownButtonFormField<String>(
                                      initialValue: _course.isEmpty ? null : _course,
                                      isExpanded: true,
                                      decoration: _dropdownDecoration(
                                          'Course / Program *', Icons.school_outlined),
                                      items: _courses
                                          .map((s) =>
                                              DropdownMenuItem(value: s, child: Text(s)))
                                          .toList(),
                                      onChanged: (v) {
                                        setState(() => _course = v ?? '');
                                        _loadSections();
                                      },
                                    ),
                                    const SizedBox(height: 14),
                                    DropdownButtonFormField<String>(
                                      initialValue:
                                          _yearLevel.isEmpty ? null : _yearLevel,
                                      isExpanded: true,
                                      decoration: _dropdownDecoration(
                                          'Year Level *', Icons.stairs_outlined),
                                      items: _yearLevels
                                          .map((s) => DropdownMenuItem(
                                              value: s, child: Text('$s Year')))
                                          .toList(),
                                      onChanged: (v) {
                                        setState(() => _yearLevel = v ?? '');
                                        _loadSections();
                                      },
                                    ),
                                    const SizedBox(height: 14),
                                    // Section dropdown — cascading, depends on course + year
                                    _loadingSections
                                        ? const Padding(
                                            padding: EdgeInsets.symmetric(vertical: 12),
                                            child: Center(
                                              child: SizedBox(
                                                width: 22,
                                                height: 22,
                                                child: CircularProgressIndicator(
                                                    strokeWidth: 2.5),
                                              ),
                                            ),
                                          )
                                        : DropdownButtonFormField<String>(
                                            initialValue:
                                                _section.isEmpty ? null : _section,
                                            isExpanded: true,
                                            decoration: _dropdownDecoration(
                                                'Section *', Icons.group_outlined),
                                            items: _sections
                                                .map((s) => DropdownMenuItem(
                                                    value: s, child: Text(s)))
                                                .toList(),
                                            onChanged: (v) =>
                                                setState(() => _section = v ?? ''),
                                          ),
                                    if (_course.isEmpty || _yearLevel.isEmpty)
                                      const Padding(
                                        padding: EdgeInsets.only(top: 6),
                                        child: Text(
                                          'Select Course and Year Level to load sections.',
                                          style: TextStyle(
                                              fontSize: 11,
                                              color: AppInk.muted,
                                              height: 1.4),
                                        ),
                                      ),
                                  ],
                                ),
                          const SizedBox(height: 28),

                          // ── Submit ──
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
                              onPressed: () => Navigator.of(context).pop(),
                              child: const Text.rich(
                                TextSpan(
                                  text: 'Already have an account? ',
                                  style: TextStyle(color: AppInk.muted, fontSize: 13),
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
}

/// Availability indicator widget — shows checking spinner, available (green),
/// or taken (red) with message.
class _AvailabilityIndicator extends StatelessWidget {
  const _AvailabilityIndicator({
    required this.checking,
    required this.available,
    required this.message,
  });
  final bool checking;
  final bool? available;
  final String? message;

  @override
  Widget build(BuildContext context) {
    if (checking) {
      return Row(
        children: [
          const SizedBox(
            width: 14, height: 14,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: 8),
          Text('Checking...',
              style: const TextStyle(fontSize: 12, color: AppInk.muted)),
        ],
      );
    }
    final isAvailable = available == true;
    final color = isAvailable ? AppInk.positive : AppInk.critical;
    return Row(
      children: [
        Icon(
          isAvailable ? Icons.check_circle_rounded : Icons.cancel_rounded,
          size: 14,
          color: color,
        ),
        const SizedBox(width: 6),
        Text(
          message ?? '',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: color,
          ),
        ),
      ],
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
          decoration: const BoxDecoration(
            gradient: LinearGradient(
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
                colors: [const Color(0xFFE2E9FF), Colors.transparent],
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

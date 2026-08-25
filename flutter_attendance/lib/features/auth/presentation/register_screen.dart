import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import 'auth_controller.dart';
import 'login_screen.dart';

/// Student registration screen — mirrors the web Registration form.
/// Fields: StudentNumber, FirstName, MiddleName, LastName, Sex, birthDate,
/// email, contactNo, Course1, yearLevel, section, password, confirm_password.
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
  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _busy = false;
  String? _error;
  String? _success;

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
      yearLevel: _yearLevel,
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
      backgroundColor: AppInk.page,
      appBar: AppBar(
        title: const Text('Create Account'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ── Error / Success ────────────────────────────────────
              if (_error != null) ...[
                _Banner(message: _error!, color: AppInk.critical),
                const SizedBox(height: 16),
              ],
              if (_success != null) ...[
                _Banner(message: _success!, color: AppInk.positive),
                const SizedBox(height: 16),
              ],

              // ── Student Number ─────────────────────────────────────
              AppInput(
                controller: _studentNumber,
                label: 'Student ID / Number *',
                hint: 'e.g. 2026-0001',
                prefixIcon: Icons.badge_outlined,
                textInputAction: TextInputAction.next,
                autofillHints: const ['username'],
              ),
              const SizedBox(height: 14),

              // ── Name ───────────────────────────────────────────────
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
                label: 'Name Extension',
                hint: 'Jr., Sr., etc.',
                prefixIcon: Icons.person_outline_rounded,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),

              // ── Sex ────────────────────────────────────────────────
              DropdownButtonFormField<String>(
                initialValue: _sex.isEmpty ? null : _sex,
                decoration: _dropdownDecoration('Sex', Icons.wc_rounded),
                items: ['Male', 'Female']
                    .map((s) => DropdownMenuItem(value: s, child: Text(s)))
                    .toList(),
                onChanged: (v) => setState(() => _sex = v ?? ''),
              ),
              const SizedBox(height: 14),

              // ── Birth Date ─────────────────────────────────────────
              GestureDetector(
                onTap: _pickBirthDate,
                child: AbsorbPointer(
                  child: AppInput(
                    controller: TextEditingController(
                      text: _birthDate != null
                          ? '${_birthDate!.year}-${_birthDate!.month.toString().padLeft(2, '0')}-${_birthDate!.day.toString().padLeft(2, '0')}'
                          : '',
                    ),
                    label: 'Birth Date',
                    hint: 'Tap to select date',
                    prefixIcon: Icons.calendar_today_rounded,
                  ),
                ),
              ),
              const SizedBox(height: 14),

              // ── Email ──────────────────────────────────────────────
              AppInput(
                controller: _email,
                label: 'Email *',
                hint: 'you@email.com',
                prefixIcon: Icons.email_outlined,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                autofillHints: const ['email'],
              ),
              const SizedBox(height: 14),

              // ── Contact No ─────────────────────────────────────────
              AppInput(
                controller: _contactNo,
                label: 'Contact Number',
                hint: '09XX XXX XXXX',
                prefixIcon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 14),

              // ── Year Level ─────────────────────────────────────────
              DropdownButtonFormField<String>(
                initialValue: _yearLevel.isEmpty ? null : _yearLevel,
                decoration: _dropdownDecoration('Year Level *', Icons.school_outlined),
                items: ['1st', '2nd', '3rd', '4th']
                    .map((s) => DropdownMenuItem(value: s, child: Text(s)))
                    .toList(),
                onChanged: (v) => setState(() => _yearLevel = v ?? ''),
              ),
              const SizedBox(height: 14),

              // ── Password ───────────────────────────────────────────
              AppInput(
                controller: _password,
                label: 'Password *',
                hint: 'At least 8 characters',
                prefixIcon: Icons.lock_outline_rounded,
                obscureText: _obscurePassword,
                textInputAction: TextInputAction.next,
                autofillHints: const ['new-password'],
                suffixIcon: GestureDetector(
                  onTap: () => setState(() => _obscurePassword = !_obscurePassword),
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
                textInputAction: TextInputAction.done,
                autofillHints: const ['new-password'],
                onSubmitted: (_) => _submit(),
                suffixIcon: GestureDetector(
                  onTap: () => setState(() => _obscureConfirm = !_obscureConfirm),
                  child: Icon(
                    _obscureConfirm ? Icons.visibility_off : Icons.visibility,
                    size: 20,
                    color: AppInk.muted,
                  ),
                ),
              ),
              const SizedBox(height: 28),

              // ── Submit ─────────────────────────────────────────────
              AppButton(
                label: 'Create Account',
                fullWidth: true,
                size: AppButtonSize.lg,
                loading: _busy,
                disabled: _busy,
                onTap: _submit,
              ),
              const SizedBox(height: 16),
              Center(
                child: TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text(
                    'Already have an account? Sign in',
                    style: TextStyle(
                      color: AppInk.accent,
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            ],
          ),
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
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppInk.rule, width: 1.5),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppInk.accent, width: 2),
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.message, required this.color});
  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(color == AppInk.positive
              ? Icons.check_circle_rounded
              : Icons.error_rounded, size: 18, color: color),
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

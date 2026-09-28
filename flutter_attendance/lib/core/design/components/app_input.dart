import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../tokens/app_tokens.dart';
import '../../theme/app_theme.dart';

/// Text field with its label above, a white fill, a hairline border and a
/// soft accent focus ring. The error text sits under the field.
class AppInput extends StatefulWidget {
  const AppInput({
    super.key,
    this.controller,
    this.label,
    this.hint,
    this.obscureText = false,
    this.keyboardType,
    this.textInputAction,
    this.prefixIcon,
    this.suffixIcon,
    this.errorText,
    this.enabled = true,
    this.onChanged,
    this.onSubmitted,
    this.maxLines = 1,
    this.autofillHints,
    this.autofocus = false,
    this.readOnly = false,
    this.onTap,
    this.inputFormatters,
    this.maxLength,
    this.helperText,
    this.focusNode,
    this.textCapitalization = TextCapitalization.none,
    this.autocorrect = true,
  });

  final TextEditingController? controller;
  final String? label;
  final String? hint;
  final bool obscureText;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final IconData? prefixIcon;
  final Widget? suffixIcon;
  final String? errorText;
  final bool enabled;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final int maxLines;
  final Iterable<String>? autofillHints;
  final bool autofocus;
  final bool readOnly;
  final VoidCallback? onTap;
  final List<TextInputFormatter>? inputFormatters;
  final int? maxLength;

  /// Quiet guidance under the field, replaced by [errorText] when set.
  final String? helperText;
  final FocusNode? focusNode;
  final TextCapitalization textCapitalization;
  final bool autocorrect;

  @override
  State<AppInput> createState() => _AppInputState();
}

class _AppInputState extends State<AppInput> {
  FocusNode? _ownFocus;
  FocusNode get _focus => widget.focusNode ?? (_ownFocus ??= FocusNode());
  bool _focused = false;

  @override
  void initState() {
    super.initState();
    _focus.addListener(_onFocus);
  }

  @override
  void didUpdateWidget(covariant AppInput old) {
    super.didUpdateWidget(old);
    if (old.focusNode != widget.focusNode) {
      (old.focusNode ?? _ownFocus)?.removeListener(_onFocus);
      _focus.addListener(_onFocus);
    }
  }

  @override
  void dispose() {
    _focus.removeListener(_onFocus);
    _ownFocus?.dispose();
    super.dispose();
  }

  void _onFocus() {
    if (mounted && _focused != _focus.hasFocus) {
      setState(() => _focused = _focus.hasFocus);
    }
  }

  @override
  Widget build(BuildContext context) {
    final w = widget;
    final hasError = w.errorText != null && w.errorText!.isNotEmpty;
    final muted = !w.enabled || w.readOnly;
    final ringColor = hasError ? AppInk.critical : AppInk.accent;

    OutlineInputBorder border(Color c, [double width = 1]) =>
        OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(color: c, width: width),
        );

    final field = TextField(
      controller: w.controller,
      focusNode: _focus,
      obscureText: w.obscureText,
      keyboardType: w.keyboardType,
      textInputAction: w.textInputAction,
      enabled: w.enabled,
      readOnly: w.readOnly,
      onTap: w.onTap,
      onChanged: w.onChanged,
      onSubmitted: w.onSubmitted,
      maxLines: w.obscureText ? 1 : w.maxLines,
      autofillHints: w.autofillHints,
      autofocus: w.autofocus,
      inputFormatters: w.inputFormatters,
      maxLength: w.maxLength,
      textCapitalization: w.textCapitalization,
      autocorrect: w.autocorrect,
      cursorColor: AppInk.accent,
      style: const TextStyle(
        fontFamily: AppTheme.fontFamily,
        fontSize: 15.5,
        fontWeight: FontWeight.w500,
        color: AppInk.heading,
      ),
      decoration: InputDecoration(
        hintText: w.hint,
        hintStyle: const TextStyle(
          fontFamily: AppTheme.fontFamily,
          fontSize: 15.5,
          fontWeight: FontWeight.w400,
          color: AppInk.faint,
        ),
        prefixIcon: w.prefixIcon != null
            ? Icon(w.prefixIcon,
                size: 20, color: _focused ? AppInk.accent : AppInk.muted)
            : null,
        suffixIcon: w.suffixIcon,
        filled: true,
        fillColor: muted ? AppInk.subtle : Colors.white,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
        enabledBorder: border(hasError ? AppInk.critical : AppInk.ruleStrong),
        disabledBorder: border(AppInk.rule),
        focusedBorder: border(ringColor, 1.5),
        errorBorder: border(AppInk.critical),
        focusedErrorBorder: border(AppInk.critical, 1.5),
        errorText: hasError ? w.errorText : null,
        errorStyle: const TextStyle(height: 0, fontSize: 0),
        counterText: '',
      ),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (w.label != null) ...[
          Text(w.label!, style: AppType.label),
          const SizedBox(height: 8),
        ],
        AnimatedContainer(
          duration: AppMotion.base,
          curve: AppMotion.ease,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppRadius.md),
            boxShadow: _focused && !muted
                ? [
                    BoxShadow(
                      color: ringColor.withValues(alpha: 0.14),
                      spreadRadius: 4,
                    ),
                  ]
                : AppShadow.xs,
          ),
          child: field,
        ),
        if (hasError || (w.helperText?.isNotEmpty ?? false)) ...[
          const SizedBox(height: 6),
          Text(
            hasError ? w.errorText! : w.helperText!,
            style: AppType.caption.copyWith(
              color: hasError ? AppInk.critical : AppInk.muted,
              fontWeight: hasError ? FontWeight.w600 : FontWeight.w500,
            ),
          ),
        ],
      ],
    );
  }
}

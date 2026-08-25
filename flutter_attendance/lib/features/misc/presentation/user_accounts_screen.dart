import 'package:flutter/material.dart';

import '../../../core/design/components/components.dart';
import '../../../core/design/tokens/app_tokens.dart';
import '../../../core/widgets/sync_status_banner.dart';
import '../../auth/domain/app_session.dart';
import '../data/misc_api.dart';
import '../domain/misc_models.dart';

/// Manage Users screen — admin can view, create, and delete user accounts.
/// Mirrors the web Page/userAccounts page.
class UserAccountsScreen extends StatefulWidget {
  const UserAccountsScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<UserAccountsScreen> createState() => _UserAccountsScreenState();
}

class _UserAccountsScreenState extends State<UserAccountsScreen> {
  late final MiscApi _api;
  final List<UserAccount> _rows = [];
  int _total = 0;
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  String _search = '';
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  static const _pageSize = 50;

  @override
  void initState() {
    super.initState();
    _api = MiscApi();
    _scrollController.addListener(_onScroll);
    _load();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await _api.userAccounts(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize,
        offset: 0,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _rows
          ..clear()
          ..addAll(result.rows);
        _total = result.total;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || _rows.length >= _total) return;
    setState(() => _loadingMore = true);
    try {
      final result = await _api.userAccounts(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        limit: _pageSize,
        offset: _rows.length,
        search: _search,
      );
      if (!mounted) return;
      setState(() {
        _rows.addAll(result.rows);
        _total = result.total;
        _loadingMore = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingMore = false);
    }
  }

  void _onSearchChanged(String v) {
    _search = v;
    _load();
  }

  Future<void> _delete(UserAccount u) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete User'),
        content: Text('Delete account "${u.username}" (${u.fullName})?\nThis cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await _api.userAccountDelete(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        username: u.username,
      );
      _load();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    }
  }

  void _showCreateForm() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _CreateUserForm(
        api: _api,
        session: widget.session,
        onSaved: _load,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Manage Users',
      showBackButton: true,
      actions: [
        IconButton(
          icon: const Icon(Icons.person_add_rounded),
          onPressed: _showCreateForm,
        ),
      ],
      body: Column(
        children: [
          const SyncStatusBanner(),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: InputDecoration(
                hintText: 'Search name, username, email...',
                prefixIcon: const Icon(Icons.search_rounded, size: 20, color: AppInk.muted),
                suffixIcon: _search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 20),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      )
                    : null,
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.rule, width: 1.5),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppInk.accent, width: 2),
                ),
              ),
            ),
          ),
          if (!_loading && _error == null)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
              child: Row(
                children: [
                  Text(
                    '${_rows.length} of $_total users',
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppInk.muted,
                    ),
                  ),
                ],
              ),
            ),
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
                      : _rows.isEmpty
                          ? ListView(children: [
                              const SizedBox(height: 80),
                              const AppEmptyState(
                                icon: Icons.people_outline_rounded,
                                title: 'No users found',
                                subtitle: 'Tap + to create a new user.',
                              ),
                            ])
                          : ListView.builder(
                              controller: _scrollController,
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _rows.length + (_loadingMore ? 1 : 0),
                              itemBuilder: (context, i) {
                                if (i >= _rows.length) {
                                  return const Padding(
                                    padding: EdgeInsets.all(16),
                                    child: Center(
                                      child: SizedBox(
                                        width: 24, height: 24,
                                        child: CircularProgressIndicator(strokeWidth: 2.5),
                                      ),
                                    ),
                                  );
                                }
                                final u = _rows[i];
                                return _UserCard(
                                  user: u,
                                  currentUser: widget.session.username,
                                  onDelete: () => _delete(u),
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

class _UserCard extends StatelessWidget {
  const _UserCard({
    required this.user,
    required this.currentUser,
    required this.onDelete,
  });
  final UserAccount user;
  final String currentUser;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final isSelf = user.username == currentUser;
    final initials = _initials(user.fullName);

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AppInk.accent.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: Text(initials,
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppInk.accent)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(user.fullName,
                      style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppInk.heading),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 3),
                  Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: [
                      Text(user.username,
                          style: const TextStyle(fontSize: 12, color: AppInk.muted)),
                      if (user.position.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppInk.accent.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(user.position,
                              style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppInk.accent)),
                        ),
                    ],
                  ),
                  if (user.email.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(user.email,
                        style: const TextStyle(fontSize: 12, color: AppInk.muted),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis),
                  ],
                ],
              ),
            ),
            if (!isSelf)
              IconButton(
                icon: const Icon(Icons.delete_outline_rounded, color: AppInk.critical, size: 20),
                onPressed: onDelete,
              ),
          ],
        ),
      ),
    );
  }

  String _initials(String name) {
    final parts = name.split(RegExp(r'[\s,]+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts[0][0].toUpperCase();
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }
}

class _CreateUserForm extends StatefulWidget {
  const _CreateUserForm({
    required this.api,
    required this.session,
    required this.onSaved,
  });

  final MiscApi api;
  final AppSession session;
  final VoidCallback onSaved;

  @override
  State<_CreateUserForm> createState() => _CreateUserFormState();
}

class _CreateUserFormState extends State<_CreateUserForm> {
  late final TextEditingController _username;
  late final TextEditingController _idNumber;
  late final TextEditingController _password;
  late final TextEditingController _fName;
  late final TextEditingController _mName;
  late final TextEditingController _lName;
  late final TextEditingController _email;
  String _acctLevel = 'Admin';
  bool _obscurePass = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _username = TextEditingController();
    _idNumber = TextEditingController();
    _password = TextEditingController();
    _fName = TextEditingController();
    _mName = TextEditingController();
    _lName = TextEditingController();
    _email = TextEditingController();
  }

  @override
  void dispose() {
    _username.dispose();
    _idNumber.dispose();
    _password.dispose();
    _fName.dispose();
    _mName.dispose();
    _lName.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_username.text.trim().isEmpty ||
        _password.text.isEmpty ||
        _fName.text.trim().isEmpty ||
        _lName.text.trim().isEmpty ||
        _email.text.trim().isEmpty ||
        _idNumber.text.trim().isEmpty) {
      setState(() => _error = 'All fields marked * are required.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await widget.api.userAccountCreate(
        baseUrl: widget.session.baseUrl,
        token: widget.session.token,
        username: _username.text.trim(),
        idNumber: _idNumber.text.trim(),
        password: _password.text,
        acctLevel: _acctLevel,
        fName: _fName.text.trim(),
        mName: _mName.text.trim(),
        lName: _lName.text.trim(),
        email: _email.text.trim(),
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
        24, 12, 24,
        32 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: AppInk.rule,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text('Create User Account',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppInk.heading)),
            const SizedBox(height: 20),
            if (_error != null) ...[
              Text(_error!, style: const TextStyle(color: AppInk.critical, fontSize: 13)),
              const SizedBox(height: 12),
            ],
            AppInput(controller: _username, label: 'Username *', hint: 'Enter username', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _idNumber, label: 'ID Number *', hint: 'Enter ID number', prefixIcon: Icons.badge_outlined),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _acctLevel,
              decoration: InputDecoration(
                labelText: 'Account Level *',
                prefixIcon: const Icon(Icons.shield_outlined, size: 20, color: AppInk.muted),
                filled: true, fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
              ),
              items: ['Admin', 'Super Admin', 'IT', 'Encoder', 'School Admin']
                  .map((s) => DropdownMenuItem(value: s, child: Text(s)))
                  .toList(),
              onChanged: (v) => setState(() => _acctLevel = v ?? 'Admin'),
            ),
            const SizedBox(height: 14),
            AppInput(controller: _fName, label: 'First Name *', hint: 'Enter first name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _mName, label: 'Middle Name', hint: 'Enter middle name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _lName, label: 'Last Name *', hint: 'Enter last name', prefixIcon: Icons.person_outline_rounded),
            const SizedBox(height: 14),
            AppInput(controller: _email, label: 'Email *', hint: 'you@email.com', prefixIcon: Icons.email_outlined, keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 14),
            AppInput(
              controller: _password,
              label: 'Password *',
              hint: 'Enter password',
              prefixIcon: Icons.lock_outline_rounded,
              obscureText: _obscurePass,
              suffixIcon: GestureDetector(
                onTap: () => setState(() => _obscurePass = !_obscurePass),
                child: Icon(_obscurePass ? Icons.visibility_off : Icons.visibility, size: 20, color: AppInk.muted),
              ),
            ),
            const SizedBox(height: 20),
            AppButton(
              label: 'Create Account',
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

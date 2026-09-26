import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_exceptions.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/l10n/l10n.dart';
import '../../core/models/account.dart';
import '../../core/settings/locale_controller.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';
import '../../ui/widgets/feedback.dart';
import 'brand_backdrop.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _accessCode = TextEditingController();

  AuthConfig? _config;
  bool _configFailed = false;
  bool _submitting = false;
  bool _obscure = true;
  Map<String, String?> _fieldErrors = const {};
  String? _banner;

  @override
  void initState() {
    super.initState();
    _config = context.read<AuthController>().config;
    _loadConfig();
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _accessCode.dispose();
    super.dispose();
  }

  Future<void> _loadConfig() async {
    try {
      final config = await context.read<AuthController>().loadConfig();
      if (mounted) {
        setState(() {
          _config = config;
          _configFailed = false;
        });
      }
    } on ApiException {
      if (mounted) {
        setState(() => _configFailed = true);
      }
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _fieldErrors = const {};
      _banner = null;
    });
    if (!_formKey.currentState!.validate()) {
      return;
    }
    HapticFeedback.lightImpact();
    setState(() => _submitting = true);
    try {
      await context.read<AuthController>().login(
        email: _email.text.trim(),
        password: _password.text,
        accessCode: _accessCodeRequired ? _accessCode.text.trim() : null,
      );
    } on ValidationException catch (error) {
      final fields = {'email': error.errorFor('email'), 'password': error.errorFor('password'), 'access_code': error.errorFor('access_code')};
      setState(() {
        _fieldErrors = fields;
        _banner = fields.values.every((message) => message == null) ? describeError(context, error) : null;
      });
      if (error.errorFor('access_code') != null && !_accessCodeRequired) {
        _loadConfig();
      }
    } on ApiException catch (error) {
      setState(() => _banner = describeError(context, error));
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  bool get _accessCodeRequired => _config?.accessCodeRequired ?? false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: NColors.ivory,
      body: AnnotatedRegion(
        value: SystemUiOverlayStyle.light,
        child: SingleChildScrollView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          child: Column(
            children: [
              _Hero(),
              Transform.translate(
                offset: const Offset(0, -36),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), boxShadow: softShadow),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(context.t('login.title'), style: theme.textTheme.headlineSmall),
                          const SizedBox(height: 4),
                          Text(context.t('login.subtitle'), style: theme.textTheme.bodyMedium?.copyWith(color: NColors.inkMuted)),
                          const SizedBox(height: 20),
                          if (_banner != null) ...[_Banner(message: _banner!), const SizedBox(height: 16)],
                          TextFormField(
                            key: const Key('login.email'),
                            controller: _email,
                            keyboardType: TextInputType.emailAddress,
                            autofillHints: const [AutofillHints.email],
                            textInputAction: TextInputAction.next,
                            decoration: InputDecoration(
                              labelText: context.t('login.email'),
                              prefixIcon: const Icon(Icons.alternate_email_rounded),
                              errorText: _fieldErrors['email'],
                              errorMaxLines: 3,
                            ),
                            validator: (value) => (value == null || !value.contains('@')) ? context.t('login.email_invalid') : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            key: const Key('login.password'),
                            controller: _password,
                            obscureText: _obscure,
                            autofillHints: const [AutofillHints.password],
                            textInputAction: _accessCodeRequired ? TextInputAction.next : TextInputAction.done,
                            onFieldSubmitted: (_) => _accessCodeRequired ? null : _submit(),
                            decoration: InputDecoration(
                              labelText: context.t('login.password'),
                              prefixIcon: const Icon(Icons.lock_outline_rounded),
                              errorText: _fieldErrors['password'],
                              errorMaxLines: 3,
                              suffixIcon: IconButton(
                                tooltip: context.t(_obscure ? 'login.show_password' : 'login.hide_password'),
                                onPressed: () => setState(() => _obscure = !_obscure),
                                icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                              ),
                            ),
                            validator: (value) => (value == null || value.isEmpty) ? context.t('login.password_required') : null,
                          ),
                          AnimatedSize(
                            duration: const Duration(milliseconds: 220),
                            child: _accessCodeRequired
                                ? Padding(
                                    padding: const EdgeInsets.only(top: 14),
                                    child: TextFormField(
                                      key: const Key('login.access_code'),
                                      controller: _accessCode,
                                      textInputAction: TextInputAction.done,
                                      onFieldSubmitted: (_) => _submit(),
                                      decoration: InputDecoration(
                                        labelText: context.t('login.access_code'),
                                        helperText: context.t('login.access_code_help'),
                                        prefixIcon: const Icon(Icons.key_rounded),
                                        errorText: _fieldErrors['access_code'],
                                        errorMaxLines: 3,
                                      ),
                                      validator: (value) => (value == null || value.trim().isEmpty) ? context.t('login.access_code_required') : null,
                                    ),
                                  )
                                : const SizedBox(width: double.infinity),
                          ),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: _config?.forgotPasswordUrl == null ? null : () => openExternal(context, _config!.forgotPasswordUrl),
                              child: Text(context.t('login.forgot')),
                            ),
                          ),
                          const SizedBox(height: 4),
                          FilledButton(
                            key: const Key('login.submit'),
                            onPressed: _submitting ? null : _submit,
                            child: _submitting
                                ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                                : Text(context.t('login.submit')),
                          ),
                          if (_configFailed) ...[
                            const SizedBox(height: 12),
                            TextButton.icon(
                              onPressed: _loadConfig,
                              icon: const Icon(Icons.wifi_off_rounded, size: 18),
                              label: Text(context.t('login.config_failed')),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
                child: _UpgradeCard(onTap: () => openExternal(context, _config?.proUrl ?? context.read<AuthController>().proUrl)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Hero extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final locale = context.watch<LocaleController>();

    return ClipRRect(
      borderRadius: const BorderRadius.vertical(bottom: Radius.circular(36)),
      child: BrandBackdrop(
        child: SafeArea(
          bottom: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 12, 16, 64),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Align(
                  alignment: Alignment.centerRight,
                  child: _LanguageToggle(code: locale.code, onChanged: locale.setCode),
                ),
                const SizedBox(height: 12),
                const NeekahMark(size: 64),
                const SizedBox(height: 20),
                const Text(
                  'Neekah Pro',
                  style: TextStyle(
                    fontFamily: headingFont,
                    fontFeatures: liningFigures,
                    fontSize: 32,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 8),
                const GoldRule(),
                const SizedBox(height: 12),
                Text(context.t('app.tagline'), style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 15, height: 1.4)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _LanguageToggle extends StatelessWidget {
  const _LanguageToggle({required this.code, required this.onChanged});

  final String code;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(3),
    decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(NRadius.pill)),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (final option in const ['ms', 'en'])
          GestureDetector(
            onTap: () {
              HapticFeedback.selectionClick();
              onChanged(option);
            },
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: option == code ? NColors.goldLight : Colors.transparent,
                borderRadius: BorderRadius.circular(NRadius.pill),
              ),
              child: Text(
                option.toUpperCase(),
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: option == code ? NColors.ink : Colors.white),
              ),
            ),
          ),
      ],
    ),
  );
}

class _Banner extends StatelessWidget {
  const _Banner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    final tone = ToneColors.of('red');

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: tone.background, borderRadius: BorderRadius.circular(NRadius.small)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.error_outline_rounded, color: tone.foreground, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(color: tone.foreground, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}

class _UpgradeCard extends StatelessWidget {
  const _UpgradeCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: NColors.goldSoft,
    borderRadius: BorderRadius.circular(NRadius.card),
    child: InkWell(
      borderRadius: BorderRadius.circular(NRadius.card),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(NRadius.card),
          border: Border.all(color: NColors.goldLight),
        ),
        child: Row(
          children: [
            const Icon(Icons.workspace_premium_rounded, color: NColors.gold, size: 30),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(context.t('login.not_pro'), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                  const SizedBox(height: 2),
                  Text(context.t('login.not_pro_body'), style: const TextStyle(color: NColors.inkMuted, fontSize: 13)),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_rounded, color: NColors.gold),
          ],
        ),
      ),
    ),
  );
}

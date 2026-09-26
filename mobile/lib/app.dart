import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'core/api/neekah_api.dart';
import 'core/auth/auth_controller.dart';
import 'core/notification_badge.dart';
import 'core/refresh_bus.dart';
import 'core/settings/locale_controller.dart';
import 'router.dart';
import 'ui/theme.dart';

class NeekahProApp extends StatefulWidget {
  const NeekahProApp({required this.api, required this.auth, required this.locale, super.key});

  final NeekahApi api;
  final AuthController auth;
  final LocaleController locale;

  @override
  State<NeekahProApp> createState() => _NeekahProAppState();
}

class _NeekahProAppState extends State<NeekahProApp> {
  late final GoRouter _router = buildRouter(widget.auth);
  late final NotificationBadge _badge = NotificationBadge(widget.api);
  final RefreshBus _bus = RefreshBus();
  final ThemeData _theme = buildNeekahTheme();

  @override
  void dispose() {
    _router.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => MultiProvider(
    providers: [
      Provider<NeekahApi>.value(value: widget.api),
      ChangeNotifierProvider<AuthController>.value(value: widget.auth),
      ChangeNotifierProvider<LocaleController>.value(value: widget.locale),
      ChangeNotifierProvider<NotificationBadge>.value(value: _badge),
      ChangeNotifierProvider<RefreshBus>.value(value: _bus),
    ],
    child: Consumer<LocaleController>(
      builder: (context, locale, _) => MaterialApp.router(
        title: 'Neekah Pro',
        debugShowCheckedModeBanner: false,
        theme: _theme,
        locale: locale.locale,
        supportedLocales: const [Locale('ms'), Locale('en')],
        localizationsDelegates: GlobalMaterialLocalizations.delegates,
        routerConfig: _router,
      ),
    ),
  );
}

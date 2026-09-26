import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/api/api_client.dart';
import 'core/api/neekah_api.dart';
import 'core/auth/auth_controller.dart';
import 'core/auth/token_store.dart';
import 'core/config.dart';
import 'core/settings/locale_controller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Future.wait([initializeDateFormatting('ms'), initializeDateFormatting('en')]);
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: Colors.transparent, systemNavigationBarColor: Colors.white));

  final prefs = await SharedPreferences.getInstance();
  final locale = LocaleController(prefs);
  final api = NeekahApi(ApiClient(httpClient: http.Client(), baseUrl: AppConfig.apiBaseUrl, languageCode: () => locale.code));
  final auth = AuthController(api: api, tokens: SecureTokenStore());

  runApp(NeekahProApp(api: api, auth: auth, locale: locale));
  auth.bootstrap();
}

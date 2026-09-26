import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:neekah_pro/app.dart';
import 'package:neekah_pro/core/api/api_client.dart';
import 'package:neekah_pro/core/api/neekah_api.dart';
import 'package:neekah_pro/core/auth/auth_controller.dart';
import 'package:neekah_pro/core/auth/token_store.dart';
import 'package:neekah_pro/core/settings/locale_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support/fixtures.dart';

/// A fake Neekah API: [routes] maps "METHOD /path" to a response builder.
class FakeBackend {
  FakeBackend(this.routes);

  final Map<String, http.Response Function(http.Request request)> routes;
  final List<http.Request> requests = [];

  MockClient get client => MockClient((request) async {
    requests.add(request);
    final key = '${request.method} ${request.url.path.replaceFirst('/api/v1', '')}';
    final route = routes[key];

    return route == null ? json({'message': 'Not found: $key'}, 404) : route(request);
  });

  static http.Response json(Object body, [int status = 200]) =>
      http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json; charset=utf-8'});
}

void main() {
  /// Pumps frames for a while; spinners and shimmers never "settle".
  Future<void> settle(WidgetTester tester) async {
    for (var i = 0; i < 8; i++) {
      await tester.pump(const Duration(milliseconds: 100));
    }
  }

  setUpAll(() async {
    await initializeDateFormatting('ms');
    await initializeDateFormatting('en');
  });

  Future<(AuthController, MemoryTokenStore)> pumpApp(WidgetTester tester, FakeBackend backend) async {
    tester.view.physicalSize = const Size(360 * 3, 780 * 3);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    SharedPreferences.setMockInitialValues({});
    final locale = LocaleController(await SharedPreferences.getInstance());
    final api = NeekahApi(ApiClient(httpClient: backend.client, baseUrl: 'https://api.test/api/v1', languageCode: () => locale.code));
    final tokens = MemoryTokenStore();
    final auth = AuthController(api: api, tokens: tokens);

    await tester.pumpWidget(NeekahProApp(api: api, auth: auth, locale: locale));
    await auth.bootstrap();
    await settle(tester);

    return (auth, tokens);
  }

  Future<void> signIn(WidgetTester tester, {String? accessCode}) async {
    await tester.enterText(find.byKey(const Key('login.email')), 'vendor@example.com');
    await tester.enterText(find.byKey(const Key('login.password')), 'secret');
    if (accessCode != null) {
      await tester.enterText(find.byKey(const Key('login.access_code')), accessCode);
    }
    await tester.ensureVisible(find.byKey(const Key('login.submit')));
    await tester.pump();
    await tester.tap(find.byKey(const Key('login.submit')));
    await settle(tester);
  }

  Map<String, dynamic> config({bool accessCode = false}) => {
    'access_code_required': accessCode,
    'pro_url': 'https://neekah.my/vendor/pro',
    'register_url': 'https://neekah.my/vendor/register',
    'forgot_password_url': 'https://neekah.my/forgot-password',
  };

  testWidgets('shows the access code field only when the API asks for it', (tester) async {
    await pumpApp(tester, FakeBackend({'GET /auth/config': (_) => FakeBackend.json(config())}));
    expect(find.byKey(const Key('login.email')), findsOneWidget);
    expect(find.byKey(const Key('login.access_code')), findsNothing);
  });

  testWidgets('sends the access code and shows a wrong-password error under the email field', (tester) async {
    final backend = FakeBackend({
      'GET /auth/config': (_) => FakeBackend.json(config(accessCode: true)),
      'POST /auth/login': (_) => FakeBackend.json({
        'message': 'Maklumat log masuk tidak sah.',
        'errors': {
          'email': ['E-mel atau kata laluan salah.'],
        },
      }, 422),
    });
    final (auth, tokens) = await pumpApp(tester, backend);

    expect(find.byKey(const Key('login.access_code')), findsOneWidget);
    await signIn(tester, accessCode: 'STAGING');

    expect(find.text('E-mel atau kata laluan salah.'), findsOneWidget);
    expect(auth.status, AuthStatus.signedOut);
    expect(tokens.token, isNull);
    final body = jsonDecode(backend.requests.last.body) as Map<String, dynamic>;
    expect(body['access_code'], 'STAGING');
    expect(backend.requests.last.headers['Accept-Language'], 'ms');
  });

  testWidgets('a vendor without Pro lands on the lock screen', (tester) async {
    final (auth, tokens) = await pumpApp(
      tester,
      FakeBackend({
        'GET /auth/config': (_) => FakeBackend.json(config()),
        'POST /auth/login': (_) => FakeBackend.json({
          'message': 'Aplikasi ini untuk vendor Neekah Pro.',
          'code': 'pro_required',
          'pro_url': 'https://neekah.my/vendor/pro',
        }, 403),
      }),
    );

    await signIn(tester);

    expect(auth.status, AuthStatus.proRequired);
    expect(find.text('Neekah Pro diperlukan'), findsOneWidget);
    expect(find.text('Aplikasi ini untuk vendor Neekah Pro.'), findsOneWidget);
    expect(find.byKey(const Key('lock.upgrade')), findsOneWidget);
    expect(tokens.token, isNull);

    await tester.ensureVisible(find.text('Kembali ke log masuk'));
    await tester.pump();
    await tester.tap(find.text('Kembali ke log masuk'));
    await settle(tester);
    expect(find.byKey(const Key('login.email')), findsOneWidget);
  });

  testWidgets('signing in stores the token and opens the dashboard', (tester) async {
    final backend = FakeBackend({
      'GET /auth/config': (_) => FakeBackend.json(config()),
      'POST /auth/login': (_) =>
          FakeBackend.json({'token': '1|abc', 'token_type': 'Bearer', 'expires_at': null, 'user': userJson(), 'vendor': vendorJson()}),
      'GET /dashboard': (_) => FakeBackend.json(dashboardJson()),
      'GET /notifications': (_) => FakeBackend.json({
        'data': [],
        'meta': {'current_page': 1, 'last_page': 1, 'per_page': 20, 'total': 0},
        'unread': 2,
      }),
    });
    final (auth, tokens) = await pumpApp(tester, backend);

    await signIn(tester);
    await settle(tester);

    expect(auth.status, AuthStatus.signedIn);
    expect(tokens.token, '1|abc');
    expect(find.text('Studio Seri Kasih'), findsOneWidget);
    expect(find.text('RM7,050.00'), findsOneWidget);
    expect(backend.requests.where((r) => r.url.path.endsWith('/dashboard')).single.headers['Authorization'], 'Bearer 1|abc');
  });
}

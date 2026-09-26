import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:neekah_pro/core/api/api_client.dart';
import 'package:neekah_pro/core/api/api_exceptions.dart';

void main() {
  ApiClient clientFor(MockClientHandler handler, {String language = 'ms'}) =>
      ApiClient(httpClient: MockClient(handler), baseUrl: 'https://api.test/api/v1/', languageCode: () => language);

  http.Response json(Object body, int status) => http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json'});

  test('sends the JSON, language and bearer headers', () async {
    late http.BaseRequest seen;
    final client = clientFor((request) async {
      seen = request;

      return json({'ok': true}, 200);
    }, language: 'en')..token = 'secret-token';

    await client.get('/bookings', query: {'status': 'confirmed', 'search': ''});

    expect(seen.url.toString(), 'https://api.test/api/v1/bookings?status=confirmed');
    expect(seen.headers['Accept'], 'application/json');
    expect(seen.headers['Accept-Language'], 'en');
    expect(seen.headers['Authorization'], 'Bearer secret-token');
  });

  test('maps 422 field errors to a ValidationException', () async {
    final client = clientFor(
      (request) async => json({
        'message': 'Maklumat log masuk tidak sah.',
        'errors': {
          'email': ['Maklumat log masuk tidak sah.'],
        },
      }, 422),
    );

    final error = await client.post('/auth/login', {'email': 'x'}).then<Object?>((_) => null, onError: (Object e) => e);

    expect(error, isA<ValidationException>());
    final validation = error! as ValidationException;
    expect(validation.errorFor('email'), 'Maklumat log masuk tidak sah.');
    expect(validation.errorFor('password'), isNull);
  });

  test('maps 403 codes and 401 account_inactive', () {
    final pro = ApiClient.mapError(403, {'message': 'Pro diperlukan.', 'code': 'pro_required', 'pro_url': 'https://neekah.my/vendor/pro'});
    expect(pro, isA<ForbiddenException>());
    expect((pro as ForbiddenException).isProRequired, isTrue);
    expect(pro.proUrl, 'https://neekah.my/vendor/pro');

    final inactive = ApiClient.mapError(401, {'message': 'Akaun tidak aktif.', 'code': 'account_inactive'});
    expect((inactive as UnauthorizedException).isAccountInactive, isTrue);

    expect(ApiClient.mapError(429, {'message': 'Too Many Attempts.'}), isA<RateLimitedException>());
    expect(ApiClient.mapError(500, const {}), isA<ServerException>());
  });

  test('reports session problems only for signed-in calls', () async {
    final problems = <ApiException>[];
    final client = clientFor((request) async => json({'message': 'Unauthenticated.'}, 401))..onSessionProblem = problems.add;

    await expectLater(client.get('/auth/config'), throwsA(isA<UnauthorizedException>()));
    expect(problems, isEmpty);

    client.token = 'expired';
    await expectLater(client.get('/me'), throwsA(isA<UnauthorizedException>()));
    expect(problems.single, isA<UnauthorizedException>());
  });

  test('turns transport failures into a NetworkException', () async {
    final client = clientFor((request) async => throw http.ClientException('Connection refused'));

    await expectLater(client.get('/dashboard'), throwsA(isA<NetworkException>()));
  });
}

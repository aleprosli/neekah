import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';
import 'api_exceptions.dart';

typedef Json = Map<String, dynamic>;

/// The one HTTP client for the Neekah API: adds the JSON, language and
/// bearer headers, decodes bodies and maps failures to [ApiException]s.
class ApiClient {
  ApiClient({required http.Client httpClient, required String baseUrl, required this.languageCode})
    : _http = httpClient,
      _baseUrl = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;

  final http.Client _http;
  final String _baseUrl;

  /// Current UI language, sent as `Accept-Language`.
  final String Function() languageCode;

  /// Bearer token for signed-in calls.
  String? token;

  /// Called when a signed-in request comes back 401, or 403 with a code,
  /// so the auth layer can route to login or the lock screen.
  void Function(ApiException error)? onSessionProblem;

  Future<Json> get(String path, {Map<String, String?>? query}) => _send('GET', path, query: query);

  Future<Json> post(String path, [Object? body]) => _send('POST', path, body: body ?? const <String, dynamic>{});

  Future<Json> put(String path, [Object? body]) => _send('PUT', path, body: body ?? const <String, dynamic>{});

  Future<Json> delete(String path) => _send('DELETE', path);

  Uri uriFor(String path, [Map<String, String?>? query]) {
    final cleaned = <String, String>{
      for (final entry in (query ?? const <String, String?>{}).entries)
        if (entry.value != null && entry.value!.isNotEmpty) entry.key: entry.value!,
    };
    final uri = Uri.parse('$_baseUrl${path.startsWith('/') ? path : '/$path'}');

    return cleaned.isEmpty ? uri : uri.replace(queryParameters: cleaned);
  }

  Map<String, String> headers({bool withBody = false}) => {
    'Accept': 'application/json',
    'Accept-Language': languageCode(),
    if (withBody) 'Content-Type': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  Future<Json> _send(String method, String path, {Map<String, String?>? query, Object? body}) async {
    final request = http.Request(method, uriFor(path, query))..headers.addAll(headers(withBody: body != null));
    if (body != null) {
      request.body = jsonEncode(body);
    }
    final hadToken = token != null;

    final http.Response response;
    try {
      final streamed = await _http.send(request).timeout(AppConfig.requestTimeout);
      response = await http.Response.fromStream(streamed).timeout(AppConfig.requestTimeout);
    } on TimeoutException {
      throw const NetworkException();
    } on http.ClientException {
      throw const NetworkException();
    } on Exception {
      // Socket, TLS handshake and other transport failures.
      throw const NetworkException();
    }

    final decoded = _decode(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    final error = mapError(response.statusCode, decoded);
    if (hadToken && _isSessionProblem(error)) {
      onSessionProblem?.call(error);
    }
    throw error;
  }

  static bool _isSessionProblem(ApiException error) => error is UnauthorizedException || (error is ForbiddenException && error.code != null);

  static Json _decode(String body) {
    if (body.trim().isEmpty) {
      return <String, dynamic>{};
    }
    try {
      final value = jsonDecode(body);

      return value is Map<String, dynamic> ? value : <String, dynamic>{'data': value};
    } on FormatException {
      return <String, dynamic>{};
    }
  }

  /// Turns an error response into its typed exception.
  static ApiException mapError(int status, Json body) {
    final message = body['message'] is String ? body['message'] as String : '';
    final code = body['code'] is String ? body['code'] as String : null;

    switch (status) {
      case 401:
        return UnauthorizedException(message, code: code);
      case 403:
        return ForbiddenException(message, code: code, proUrl: body['pro_url'] is String ? body['pro_url'] as String : null);
      case 404:
        return NotFoundException(message);
      case 422:
        return ValidationException(message, _fieldErrors(body['errors']));
      case 429:
        return RateLimitedException(message);
      default:
        return ServerException(message, statusCode: status);
    }
  }

  static Map<String, List<String>> _fieldErrors(Object? raw) {
    if (raw is! Map) {
      return const {};
    }

    return {
      for (final entry in raw.entries)
        entry.key.toString(): entry.value is List ? [for (final m in entry.value as List) m.toString()] : [entry.value.toString()],
    };
  }
}

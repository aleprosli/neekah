/// Typed failures raised by [ApiClient]. Screens switch on these instead of
/// reading status codes.
sealed class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode});

  /// The server's translated sentence, or empty when there is none.
  final String message;
  final int? statusCode;

  @override
  String toString() => '$runtimeType($statusCode): $message';
}

/// No connection, DNS failure, timeout or an unreadable response.
class NetworkException extends ApiException {
  const NetworkException([super.message = '']);
}

/// 401 — the token is gone or expired, or the account was deactivated.
class UnauthorizedException extends ApiException {
  const UnauthorizedException(super.message, {this.code}) : super(statusCode: 401);

  final String? code;

  bool get isAccountInactive => code == 'account_inactive';
}

/// 403 with one of `not_vendor`, `not_approved` or `pro_required`.
class ForbiddenException extends ApiException {
  const ForbiddenException(super.message, {this.code, this.proUrl}) : super(statusCode: 403);

  final String? code;
  final String? proUrl;

  bool get isProRequired => code == 'pro_required';
  bool get isNotVendor => code == 'not_vendor';
  bool get isNotApproved => code == 'not_approved';
}

/// 422 — field errors to show under their inputs.
class ValidationException extends ApiException {
  const ValidationException(super.message, this.errors) : super(statusCode: 422);

  final Map<String, List<String>> errors;

  String? errorFor(String field) {
    final messages = errors[field];

    return (messages == null || messages.isEmpty) ? null : messages.first;
  }

  /// The first field error, falling back to the summary message.
  String get firstMessage {
    for (final messages in errors.values) {
      if (messages.isNotEmpty) {
        return messages.first;
      }
    }

    return message;
  }
}

class NotFoundException extends ApiException {
  const NotFoundException(super.message) : super(statusCode: 404);
}

class RateLimitedException extends ApiException {
  const RateLimitedException(super.message) : super(statusCode: 429);
}

class ServerException extends ApiException {
  const ServerException(super.message, {super.statusCode});
}

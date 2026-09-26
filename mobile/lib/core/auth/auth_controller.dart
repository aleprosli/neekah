import 'package:flutter/foundation.dart';

import '../api/api_exceptions.dart';
import '../api/neekah_api.dart';
import '../models/account.dart';
import 'token_store.dart';

enum AuthStatus {
  /// Still checking the saved token.
  unknown,
  signedOut,
  signedIn,

  /// Signed in (or tried to) but Pro is not running.
  proRequired,

  /// Not a vendor, not approved yet, or deactivated.
  blocked,
}

/// Owns the session: the token, who is signed in, and whether they may use
/// the app. The router redirects on [status].
class AuthController extends ChangeNotifier {
  AuthController({required this.api, required this.tokens}) {
    api.client.onSessionProblem = _onSessionProblem;
  }

  final NeekahApi api;
  final TokenStore tokens;

  AuthStatus status = AuthStatus.unknown;
  User? user;
  Vendor? vendor;
  ProInfo? pro;

  /// `not_vendor`, `not_approved` or `account_inactive` when [status] is blocked.
  String? blockCode;

  /// The server's own sentence for a block or lock.
  String? reasonMessage;

  /// Where to buy or renew Pro.
  String? proUrl;

  /// Web links from `GET /auth/config`, once the login screen has read them.
  AuthConfig? config;

  /// Set when the saved token could not be checked (offline at launch).
  ApiException? bootError;

  bool get hasToken => api.client.token != null;

  /// Reads `/auth/config`; failures leave [config] as it was.
  Future<AuthConfig?> loadConfig() async {
    config = await api.authConfig();
    rememberProUrl(config!.proUrl);

    return config;
  }

  Future<void> bootstrap() async {
    bootError = null;
    final token = await tokens.read();
    if (token == null) {
      _set(AuthStatus.signedOut);

      return;
    }
    api.client.token = token;
    await refreshMe();
  }

  /// Re-reads `/me` — at launch, and after the vendor says they renewed Pro.
  Future<void> refreshMe() async {
    bootError = null;
    try {
      final me = await api.me();
      user = me.user;
      vendor = me.vendor;
      pro = me.pro;
      proUrl = me.pro.proUrl ?? proUrl;
      _set(me.pro.active ? AuthStatus.signedIn : AuthStatus.proRequired);
    } on UnauthorizedException {
      // [_onSessionProblem] already routed.
    } on ForbiddenException {
      // [_onSessionProblem] already routed.
    } on ApiException catch (error) {
      if (status == AuthStatus.unknown) {
        bootError = error;
        notifyListeners();
      } else {
        rethrow;
      }
    }
  }

  /// Signs in. 422/429/network failures are thrown for the form to show;
  /// a 403 moves to the blocked or lock screen instead.
  Future<void> login({required String email, required String password, String? accessCode}) async {
    try {
      final result = await api.login(email: email, password: password, accessCode: accessCode);
      await tokens.write(result.token);
      api.client.token = result.token;
      user = result.user;
      vendor = result.vendor;
      pro = ProInfo(active: true, until: result.vendor.proUntil, proUrl: proUrl);
      _set(AuthStatus.signedIn);
    } on ForbiddenException catch (error) {
      _applyForbidden(error);
    }
  }

  Future<void> logout() async {
    if (hasToken) {
      try {
        await api.logout();
      } on ApiException {
        // The token is dropped locally either way.
      }
    }
    await _forget();
    _set(AuthStatus.signedOut);
  }

  /// From a block or lock screen reached at login, back to the form.
  void backToLogin() {
    blockCode = null;
    reasonMessage = null;
    _set(AuthStatus.signedOut);
  }

  /// Keeps the header in sync when the dashboard brings a fresher vendor.
  void updateVendor(Vendor fresh) {
    vendor = fresh;
    notifyListeners();
  }

  void rememberProUrl(String? url) {
    if (url != null && url.isNotEmpty) {
      proUrl = url;
    }
  }

  void _onSessionProblem(ApiException error) {
    if (error is UnauthorizedException) {
      _forget();
      if (error.isAccountInactive) {
        blockCode = 'account_inactive';
        reasonMessage = error.message;
        _set(AuthStatus.blocked);
      } else {
        _set(AuthStatus.signedOut);
      }
    } else if (error is ForbiddenException) {
      _applyForbidden(error);
    }
  }

  void _applyForbidden(ForbiddenException error) {
    reasonMessage = error.message.isEmpty ? null : error.message;
    rememberProUrl(error.proUrl);
    if (error.isProRequired) {
      _set(AuthStatus.proRequired);
    } else {
      blockCode = error.code;
      _forget();
      _set(AuthStatus.blocked);
    }
  }

  Future<void> _forget() async {
    api.client.token = null;
    user = null;
    vendor = null;
    pro = null;
    await tokens.clear();
  }

  void _set(AuthStatus next) {
    status = next;
    notifyListeners();
  }
}

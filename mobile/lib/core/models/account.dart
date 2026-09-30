import 'common.dart';
import 'parse.dart';

class User {
  const User({required this.id, required this.name, required this.email, this.phone, this.locale});

  factory User.fromJson(Object? raw) {
    final json = obj(raw);

    return User(
      id: toInt(json['id']),
      name: str(json['name']),
      email: str(json['email']),
      phone: strOrNull(json['phone']),
      locale: strOrNull(json['locale']),
    );
  }

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? locale;
}

class Vendor {
  const Vendor({
    required this.id,
    required this.name,
    required this.slug,
    required this.publicUrl,
    required this.initial,
    required this.tier,
    required this.score,
    required this.pointsTotal,
    required this.reviewsCount,
    required this.isPro,
    required this.isElite,
    required this.boostTokens,
    this.logoUrl,
    this.category,
    this.city,
    this.state,
    this.ratingAvg,
    this.proUntil,
  });

  factory Vendor.fromJson(Object? raw) {
    final json = obj(raw);
    final name = str(json['name']);

    return Vendor(
      id: toInt(json['id']),
      name: name,
      slug: str(json['slug']),
      publicUrl: str(json['public_url']),
      logoUrl: strOrNull(json['logo_url']),
      initial: str(json['initial'], name.isEmpty ? 'N' : name.substring(0, 1).toUpperCase()),
      category: strOrNull(json['category']),
      city: strOrNull(json['city']),
      state: strOrNull(json['state']),
      tier: Tier.fromJson(json['tier']),
      score: toDouble(json['score']),
      pointsTotal: toInt(json['points_total']),
      ratingAvg: toDoubleOrNull(json['rating_avg']),
      reviewsCount: toInt(json['reviews_count']),
      isPro: toBool(json['is_pro']),
      proUntil: dateTimeOrNull(json['pro_until']),
      isElite: toBool(json['is_elite']),
      boostTokens: toInt(json['boost_tokens']),
    );
  }

  final int id;
  final String name;
  final String slug;
  final String publicUrl;
  final String? logoUrl;
  final String initial;
  final String? category;
  final String? city;
  final String? state;
  final Tier tier;
  final double score;
  final int pointsTotal;
  final double? ratingAvg;
  final int reviewsCount;
  final bool isPro;
  final DateTime? proUntil;
  final bool isElite;
  final int boostTokens;

  String? get location => [city, state].whereType<String>().where((part) => part.isNotEmpty).join(', ').ifEmptyNull;
}

extension on String {
  String? get ifEmptyNull => isEmpty ? null : this;
}

class ProInfo {
  const ProInfo({required this.active, this.until, this.daysLeft, this.proUrl});

  factory ProInfo.fromJson(Object? raw) {
    final json = obj(raw);

    return ProInfo(
      active: toBool(json['active']),
      until: dateTimeOrNull(json['until']),
      daysLeft: toIntOrNull(json['days_left']),
      proUrl: strOrNull(json['pro_url']),
    );
  }

  final bool active;
  final DateTime? until;
  final int? daysLeft;
  final String? proUrl;
}

/// `GET /me`.
class Me {
  const Me({required this.user, required this.vendor, required this.pro});

  factory Me.fromJson(Json json) =>
      Me(user: User.fromJson(json['user']), vendor: Vendor.fromJson(json['vendor']), pro: ProInfo.fromJson(json['pro']));

  final User user;
  final Vendor vendor;
  final ProInfo pro;
}

/// `POST /auth/login` success.
class LoginResult {
  const LoginResult({required this.token, required this.user, required this.vendor, this.expiresAt});

  factory LoginResult.fromJson(Json json) => LoginResult(
    token: str(json['token']),
    expiresAt: dateTimeOrNull(json['expires_at']),
    user: User.fromJson(json['user']),
    vendor: Vendor.fromJson(json['vendor']),
  );

  final String token;
  final DateTime? expiresAt;
  final User user;
  final Vendor vendor;
}

/// `GET /auth/config`.
class AuthConfig {
  const AuthConfig({required this.accessCodeRequired, this.proUrl, this.registerUrl, this.forgotPasswordUrl});

  factory AuthConfig.fromJson(Json json) => AuthConfig(
    accessCodeRequired: toBool(json['access_code_required']),
    proUrl: strOrNull(json['pro_url']),
    registerUrl: strOrNull(json['register_url']),
    forgotPasswordUrl: strOrNull(json['forgot_password_url']),
  );

  final bool accessCodeRequired;
  final String? proUrl;
  final String? registerUrl;
  final String? forgotPasswordUrl;
}

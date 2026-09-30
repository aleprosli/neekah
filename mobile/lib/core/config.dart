/// Build-time configuration.
///
/// Point the app at another API with
/// `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8123/api/v1`.
class AppConfig {
  const AppConfig._();

  static const String apiBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'https://neekah.my/api/v1');

  static const String appVersion = '1.0.0';

  static const String deviceName = 'Neekah Pro (Android)';

  static const Duration requestTimeout = Duration(seconds: 25);
}

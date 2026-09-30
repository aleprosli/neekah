import 'package:flutter/widgets.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// The app language: Malay by default, English when chosen in Settings.
class LocaleController extends ChangeNotifier {
  LocaleController(this._prefs) : _code = _normalise(_prefs.getString(_key));

  static const _key = 'locale';
  static const supported = ['ms', 'en'];

  final SharedPreferences _prefs;
  String _code;

  String get code => _code;

  Locale get locale => Locale(_code);

  Future<void> setCode(String code) async {
    final next = _normalise(code);
    if (next == _code) {
      return;
    }
    _code = next;
    notifyListeners();
    await _prefs.setString(_key, next);
  }

  static String _normalise(String? code) => supported.contains(code) ? code! : 'ms';
}

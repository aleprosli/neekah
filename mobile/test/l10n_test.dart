import 'package:flutter_test/flutter_test.dart';
import 'package:neekah_pro/core/l10n/l10n.dart';
import 'package:neekah_pro/core/l10n/strings.dart';

void main() {
  test('Malay and English define the same keys', () {
    expect(appStrings['en']!.keys.toSet(), appStrings['ms']!.keys.toSet());
  });

  test('fills placeholders and falls back to the key', () {
    expect(const L10n('en').t('dash.last_days', {'n': 28}), 'Last 28 days');
    expect(const L10n('ms').t('dash.last_days', {'n': 28}), '28 hari lepas');
    expect(const L10n('ms').t('missing.key'), 'missing.key');
  });
}

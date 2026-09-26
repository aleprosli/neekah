# Neekah Pro (Android)

Vendor app for Neekah Pro accounts, built with Flutter. It talks to the Laravel API at `/api/v1`.

## Run and build

```sh
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8123/api/v1   # local API from the Android emulator
flutter build apk --release                                          # defaults to https://neekah.my/api/v1
flutter analyze && flutter test
```

Android builds need `JAVA_HOME` (JDK 17) and `ANDROID_HOME`. Plain HTTP is only allowed for `10.0.2.2` and
`localhost` (see `android/app/src/main/res/xml/network_security_config.xml`). The release APK is debug-signed
for now; add an upload keystore before publishing to Play.

The `web/` target exists only for browser QA (`flutter build web --dart-define=API_BASE_URL=...`).

## Layout

- `lib/core` — API client and typed endpoints, models, auth/session, locale, formatting, UI strings (`l10n/strings.dart`)
- `lib/features/<feature>` — screens: auth, dashboard, bookings, calendar, enquiries, boost, points, notifications, settings
- `lib/ui` — theme (brand colours and fonts) and shared widgets

---
paths:
  - 'mobile/**'
---

# Mobile

## The Neekah Pro Flutter app: one API client, strings in one map, server chosen at build time
mobile/ is the Android app for Pro vendors (package my.neekah.pro), a client of /api/v1 (see .ai/rules/api.md). All HTTP goes through lib/core/api/api_client.dart, which adds Accept-Language and the Bearer token and maps 401/403-code/422/429 to typed errors; screens never call http directly. Every UI string lives in lib/core/l10n/strings.dart with the same keys in ms and en (l10n_test checks). The server is compiled in: default https://neekah.my/api/v1, staging via --dart-define=API_BASE_URL=https://staging.neekah.my/api/v1; plain HTTP is allowed only for 10.0.2.2/localhost. Build with JAVA_HOME=/opt/homebrew/opt/openjdk@17 ANDROID_HOME=/opt/homebrew/share/android-commandlinetools flutter build apk --release --split-per-abi --target-platform android-arm64. Release builds are still debug-signed: a real keystore is needed before the Play Store. Fonts are bundled (OFL), no google_fonts, no Firebase. The web/ target exists only for visual QA in Chrome. Run flutter analyze and flutter test before committing.

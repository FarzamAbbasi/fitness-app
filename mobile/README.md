# Fitness App — Mobile (Flutter)

Flutter client for the fitness SaaS MVP. Mirrors the feature set of the
[web app](../web) (login/register, onboarding, AI plan, workout logging,
accountability pods) using native widgets instead of React Native Web.

## ✅ Verified

Platform scaffolding (`android/`, `web/`), dependencies, static analysis,
widget tests, and a production web build were all generated/run successfully
via a temporary `instrumentisto/flutter` Docker container (removed afterwards
to keep this shared machine's disk usage low):

- `flutter pub get` — resolves cleanly.
- `flutter analyze` — **0 issues**.
- `flutter test` — **1/1 passed** (smoke test boots the app to the login screen).
- `flutter build web` — builds successfully.

The Flutter SDK/Docker image is **not** kept installed in this dev
environment (disk constraints), so none of the above has been re-run since.
If you modify `lib/`, re-verify locally (see below) before shipping.

## Running locally

### Option A — Docker (no local Flutter install needed)

```bash
# From the mobile/ directory:
docker run --rm -v "$(pwd):/app" -w /app instrumentisto/flutter:3.27.3 \
  sh -c "flutter pub get && flutter analyze && flutter test"

# Fix file ownership afterwards (the container runs as root):
docker run --rm -v "$(pwd):/app" instrumentisto/flutter:3.27.3 chown -R "$(id -u):$(id -g)" /app
```

Mount a persistent pub-cache volume (e.g. `-v ~/.pub-cache-docker:/root/.pub-cache`)
if you'll run multiple commands, so packages aren't re-downloaded each time.

### Option B — Native Flutter SDK

```bash
flutter pub get

# Point the app at your backend. By default it targets
# http://10.0.2.2:8010/api (how the Android emulator reaches the host
# machine's localhost). Override per-run as needed:
flutter run --dart-define=API_BASE_URL=http://localhost:8010/api   # iOS simulator
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8010/api    # Android emulator
flutter run --dart-define=API_BASE_URL=https://api.yourdomain.com/api  # physical device / prod
```

## Project structure

```
lib/
  api/
    api_client.dart   — HTTP client wrapping the Laravel API (mirrors web/src/api/client.ts)
    auth_state.dart   — ChangeNotifier holding the current user / auth token
  screens/
    login_screen.dart
    register_screen.dart
    onboarding_screen.dart
    plan_screen.dart
    workout_log_screen.dart
    pod_screen.dart
  widgets/
    common.dart       — shared chip/button/error-text widgets
  main.dart           — app entry point, routes, auth-gated navigation
test/
  widget_test.dart    — smoke test verifying the app boots to the login screen
android/, web/        — platform scaffolding generated via `flutter create .`
```

## Backend contract

This app talks to the same Laravel API as the web app — see
[../backend/routes/api.php](../backend/routes/api.php) for the full route
list. Auth is Bearer-token based (Sanctum personal access tokens), persisted
locally via `shared_preferences`.

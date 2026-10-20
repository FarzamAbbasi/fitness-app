# Fitness App — MVP

A B2C fitness SaaS MVP: AI-personalized workout plans + community/accountability
"pods" — the two differentiators called out in the
[launch plan](/var/www/html/fitness-saas-launch-plan.md).

This is a monorepo with three independent clients sharing one Laravel API:

```
fitness-app/
  backend/   Laravel 12 API (auth, onboarding, AI plan generation, workout logs, pods)
  web/       React Native Web app (Vite) — full product experience in the browser
  mobile/    Flutter app (hand-scaffolded; SDK not installed in this environment)
```

## Quick start

1. **Backend** — see [backend/README.md](backend/README.md). Needs a MySQL/MariaDB
   database and PHP 8.2+. Runs on `http://localhost:8010`.
2. **Web** — see [web/README.md](web/README.md). `npm install && npm run dev`,
   runs on `http://localhost:5173`, talks to the backend via `VITE_API_BASE_URL`.
3. **Mobile** — see [mobile/README.md](mobile/README.md). Requires a machine
   with the Flutter SDK installed (not available in this dev environment due
   to disk space constraints); run `flutter create .` first to generate the
   missing platform folders, then `flutter pub get`.

## Product scope (MVP)

- **Auth**: register/login/logout via Bearer tokens (Sanctum), shared across
  all three clients.
- **Onboarding**: goal, experience level, days/week, session duration,
  equipment, and a "niche" (busy professional, home-gym minimalist, returning
  after a break, runner/hybrid athlete) used for pod matching.
- **AI-personalized plans**: a deterministic, fully-tested rule-based engine
  generates the actual workout structure (exercise selection, sets/reps/rest,
  periodization); an optional OpenAI-backed layer adds a short "coach note"
  on top when `OPENAI_API_KEY` is configured. Plans adapt week-to-week based
  on logged adherence (progressive overload vs. deload).
- **Workout logging**: duration, perceived effort, notes — feeds the
  adaptive plan logic.
- **Accountability pods**: users are auto-matched into a small group sharing
  their niche immediately after onboarding, and can post/view check-ins.

## Status

- Backend: fully built and tested (23 passing feature tests covering auth,
  onboarding/plan generation, workout logging, and pods).
- Web: fully built and manually verified end-to-end against the live backend
  (register → onboarding → AI plan → log workout → pod auto-join → check-in).
- Mobile: built with hand-written Dart/Flutter source mirroring the web
  app's screens and API client, then fully verified via a temporary Flutter
  Docker container — `flutter analyze` (0 issues), `flutter test` (1/1
  passed), and `flutter build web` all succeed. See
  [mobile/README.md](mobile/README.md) for how to run it locally.

## Deployment

- **Backend** → [Railway](https://railway.app), project `fitness-app-backend`
  (Laravel service + managed MySQL plugin). Deployed via `railway up` from
  `backend/`; migrations run automatically on each deploy (see `Procfile`).
  Production env vars (`APP_KEY`, `DB_*`, etc.) are set via `railway variables`
  and reference the MySQL plugin's connection vars directly — nothing is
  hardcoded.
- **Web** → [Vercel](https://vercel.com), project `web` (static Vite/React
  build). Deployed via `vercel --prod` from `web/`. `VITE_API_BASE_URL` is
  set as a Vercel production env var pointing at the Railway backend's public
  URL. `vercel.json` adds SPA rewrites so client-side routes (react-router)
  work on refresh/deep links.
- **Mobile** → not deployed anywhere yet (no app store listing); verified
  locally only (see Status above). App store submission is a follow-up step.

To redeploy after changes:

```bash
# Backend
cd backend && railway up --service backend

# Web
cd web && vercel --prod
```

## Why these stack choices

- **react-native-web for the web client** (not a plain React app) was an
  explicit choice to keep UI primitives close to what a future shared
  React Native codebase could use, even though the dedicated mobile app
  ended up being built in Flutter instead.
- **Rule-based + optional LLM plan generation** keeps the core product
  deterministic, fully testable, and free to run; the OpenAI integration is
  additive polish, not a dependency.
- **Docker-based MariaDB** for local dev, since host MySQL required sudo
  access that wasn't available, following the same pattern used by sibling
  projects on this server.

# Fitness App — Web (React Native Web)

Web client for the fitness SaaS MVP, built with
[react-native-web](https://necolas.github.io/react-native-web/) + Vite +
react-router-dom so UI primitives (View/Text/Pressable/StyleSheet) stay close
to what a React Native mobile client would use, even though the dedicated
mobile app is built separately in Flutter (see [../mobile](../mobile)).

## Setup

```bash
npm install
cp .env.example .env   # set VITE_API_BASE_URL to your backend, e.g. http://localhost:8010/api
npm run dev             # http://localhost:5173
```

```bash
npm run build           # production build to dist/
npm run preview          # preview the production build
```

## Project structure

```
src/
  api/
    client.ts        — fetch-based API client wrapping the Laravel backend
    AuthContext.tsx  — React context/provider exposing the current user + auth actions
  screens/
    LoginScreen.tsx
    RegisterScreen.tsx
    OnboardingScreen.tsx
    PlanScreen.tsx
    WorkoutLogScreen.tsx
    PodScreen.tsx
  navigation/
    AppNavigator.tsx — route definitions + auth-gated redirects
  types/
    react-native.d.ts — minimal ambient types for the RN primitives used here
                         (avoids pulling in the full @types/react-native stub)
  App.tsx, main.tsx  — app root / entry point
```

## Notes

- Auth is Bearer-token based (Sanctum), stored in `localStorage`.
- Style note: react-native-web does **not** support CSS shorthand values like
  `margin: '0 auto'` — use discrete properties (`marginTop`, `marginHorizontal`,
  etc.) instead, or it will throw a console error and silently drop the style.
- Verified end-to-end against the Laravel backend: register → onboarding →
  AI plan generation → workout logging → pod auto-join → pod check-ins.

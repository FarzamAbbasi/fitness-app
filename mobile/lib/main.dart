import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'api/auth_state.dart';
import 'screens/login_screen.dart';
import 'screens/onboarding_screen.dart';
import 'screens/plan_screen.dart';
import 'screens/pod_screen.dart';
import 'screens/register_screen.dart';
import 'screens/workout_log_screen.dart';

void main() {
  runApp(
    ChangeNotifierProvider(
      create: (_) => AuthState()..bootstrap(),
      child: const FitnessApp(),
    ),
  );
}

class FitnessApp extends StatelessWidget {
  const FitnessApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Fitness App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: const Color(0xFF111827),
        scaffoldBackgroundColor: Colors.white,
      ),
      home: const _RootRouter(),
      routes: {
        '/login': (_) => const LoginScreen(),
        '/register': (_) => const RegisterScreen(),
        '/onboarding': (_) => const _Protected(child: OnboardingScreen()),
        '/plan': (_) => const _Protected(child: PlanScreen()),
        '/log': (_) => const _Protected(child: WorkoutLogScreen()),
        '/pod': (_) => const _Protected(child: PodScreen()),
      },
    );
  }
}

/// Decides the initial screen once the stored auth token (if any) has been
/// validated against the backend.
class _RootRouter extends StatelessWidget {
  const _RootRouter();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    if (auth.loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return auth.isAuthenticated ? const PlanScreen() : const LoginScreen();
  }
}

/// Wraps a screen that requires authentication; redirects to the login
/// screen if the user signs out or their token becomes invalid.
class _Protected extends StatelessWidget {
  final Widget child;

  const _Protected({required this.child});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    if (auth.loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (!auth.isAuthenticated) {
      return const LoginScreen();
    }
    return child;
  }
}

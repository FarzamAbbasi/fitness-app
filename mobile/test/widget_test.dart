// Basic smoke test: verifies the app boots to the login screen when no
// auth token is stored (the default state for a fresh install).
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:fitness_app_mobile/api/auth_state.dart';
import 'package:fitness_app_mobile/main.dart';

void main() {
  testWidgets('App boots to the login screen when logged out', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});

    // Mirrors the provider wiring in main() so FitnessApp can read AuthState.
    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AuthState()..bootstrap(),
        child: const FitnessApp(),
      ),
    );
    // Let the auth bootstrap (token restore) finish.
    await tester.pumpAndSettle();

    expect(find.text('Welcome back'), findsOneWidget);
  });
}

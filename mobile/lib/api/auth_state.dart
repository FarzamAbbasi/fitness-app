import 'package:flutter/foundation.dart';
import 'api_client.dart';

/// App-wide authentication state, exposed via [ChangeNotifier] so widgets can
/// react to login/logout without prop-drilling the current user everywhere.
class AuthState extends ChangeNotifier {
  Map<String, dynamic>? _user;
  bool _loading = true;

  Map<String, dynamic>? get user => _user;
  bool get loading => _loading;
  bool get isAuthenticated => _user != null;

  final ApiClient _api = ApiClient.instance;

  /// Restores a previously persisted token (if any) and validates it against
  /// the backend. Call this once at app startup before showing any screen.
  Future<void> bootstrap() async {
    await _api.loadToken();
    _user = await _api.me();
    _loading = false;
    notifyListeners();
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
  }) async {
    _user = await _api.register(name: name, email: email, password: password);
    notifyListeners();
  }

  Future<void> login({required String email, required String password}) async {
    _user = await _api.login(email: email, password: password);
    notifyListeners();
  }

  Future<void> logout() async {
    await _api.logout();
    _user = null;
    notifyListeners();
  }
}

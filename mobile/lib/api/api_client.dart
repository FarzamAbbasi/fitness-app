import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Base URL for the Laravel backend API.
///
/// Override at build/run time with:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8010/api
/// (10.0.2.2 is how the Android emulator reaches the host machine's
/// localhost; use http://localhost:8010/api for iOS simulator / web).
const String apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8010/api',
);

/// Thrown when the API returns a non-2xx response. Carries the
/// backend's validation/error message when available so screens can
/// show something more useful than "something went wrong".
class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException(this.statusCode, this.message, {this.errors});

  @override
  String toString() => message;
}

/// Thin HTTP client wrapping the backend's JSON API. Mirrors
/// web/src/api/client.ts so all three clients (web, mobile, and any
/// future Flutter widgets) share the same request/response shapes.
class ApiClient {
  static const _tokenKey = 'auth_token';
  String? _token;

  ApiClient._();
  static final ApiClient instance = ApiClient._();

  Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
  }

  String? get token => _token;

  Future<void> _setToken(String? token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    if (token == null) {
      await prefs.remove(_tokenKey);
    } else {
      await prefs.setString(_tokenKey, token);
    }
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<dynamic> _request(
    String method,
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final uri = Uri.parse('$apiBaseUrl$path');
    http.Response response;
    final encodedBody = body != null ? jsonEncode(body) : null;

    switch (method) {
      case 'GET':
        response = await http.get(uri, headers: _headers);
        break;
      case 'POST':
        response = await http.post(uri, headers: _headers, body: encodedBody);
        break;
      default:
        throw ArgumentError('Unsupported method $method');
    }

    final decoded =
        response.body.isNotEmpty ? jsonDecode(response.body) : null;

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    final message = (decoded is Map && decoded['message'] is String)
        ? decoded['message'] as String
        : 'Something went wrong (${response.statusCode})';
    final errors = (decoded is Map && decoded['errors'] is Map)
        ? Map<String, dynamic>.from(decoded['errors'])
        : null;

    throw ApiException(response.statusCode, message, errors: errors);
  }

  // --- Auth -----------------------------------------------------------

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final data = await _request('POST', '/auth/register', body: {
      'name': name,
      'email': email,
      'password': password,
    });
    await _setToken(data['token'] as String);
    return Map<String, dynamic>.from(data['user'] as Map);
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final data = await _request('POST', '/auth/login', body: {
      'email': email,
      'password': password,
    });
    await _setToken(data['token'] as String);
    return Map<String, dynamic>.from(data['user'] as Map);
  }

  Future<void> logout() async {
    try {
      await _request('POST', '/auth/logout');
    } finally {
      await _setToken(null);
    }
  }

  Future<Map<String, dynamic>?> me() async {
    if (_token == null) return null;
    try {
      final data = await _request('GET', '/me');
      return Map<String, dynamic>.from(data['user'] as Map);
    } on ApiException {
      await _setToken(null);
      return null;
    }
  }

  // --- Profile / onboarding --------------------------------------------

  Future<Map<String, dynamic>> saveProfile(Map<String, dynamic> profile) async {
    final data = await _request('POST', '/profile', body: profile);
    return Map<String, dynamic>.from(data as Map);
  }

  Future<Map<String, dynamic>?> getProfile() async {
    final data = await _request('GET', '/profile');
    return data == null ? null : Map<String, dynamic>.from(data['profile'] as Map);
  }

  // --- Plans ------------------------------------------------------------

  Future<Map<String, dynamic>> getCurrentPlan() async {
    final data = await _request('GET', '/plan');
    return Map<String, dynamic>.from(data['plan'] as Map);
  }

  Future<Map<String, dynamic>> regeneratePlan() async {
    final data = await _request('POST', '/plan/regenerate');
    return Map<String, dynamic>.from(data['plan'] as Map);
  }

  Future<List<dynamic>> getPlanHistory() async {
    final data = await _request('GET', '/plan/history');
    return List<dynamic>.from(data['plans'] as List);
  }

  // --- Workout logs -------------------------------------------------------

  Future<Map<String, dynamic>> logWorkout({
    required int durationMinutes,
    required int perceivedEffort,
    String? notes,
  }) async {
    final data = await _request('POST', '/workout-logs', body: {
      'logged_date': DateTime.now().toIso8601String().substring(0, 10),
      'duration_minutes': durationMinutes,
      'perceived_effort': perceivedEffort,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });
    return Map<String, dynamic>.from(data['log'] as Map);
  }

  Future<List<dynamic>> getWorkoutLogs() async {
    // Backend paginates this endpoint (`->paginate(20)`), so the list of
    // entries lives under `data`, not a top-level `logs` key.
    final data = await _request('GET', '/workout-logs');
    return List<dynamic>.from(data['data'] as List);
  }

  // --- Pods ---------------------------------------------------------------

  Future<Map<String, dynamic>> autoJoinPod() async {
    final data = await _request('POST', '/pods/auto-join');
    return Map<String, dynamic>.from(data['pod'] as Map);
  }

  Future<List<dynamic>> getMyPods() async {
    final data = await _request('GET', '/pods/mine');
    return List<dynamic>.from(data['pods'] as List);
  }

  Future<List<dynamic>> getPodCheckins(int podId) async {
    final data = await _request('GET', '/pods/$podId/checkins');
    return List<dynamic>.from(data['data'] as List);
  }

  Future<Map<String, dynamic>> postPodCheckin(int podId, String message) async {
    final data = await _request('POST', '/pods/$podId/checkins', body: {
      'message': message,
    });
    return Map<String, dynamic>.from(data['checkin'] as Map);
  }
}

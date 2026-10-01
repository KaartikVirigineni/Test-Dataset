import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  static const String baseUrl = 'http://localhost:8000';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('access_token');
  }

  static Future<void> setToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('access_token', token);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('access_token');
  }

  static Future<Map<String, dynamic>> login(String username, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/auth/login?username=$username&password=$password'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      await setToken(data['access_token']);
      return data;
    } else {
      throw Exception('Login failed');
    }
  }

  static Future<Map<String, dynamic>> register(String username, String password, String email) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/auth/register?username=$username&password=$password&email=$email'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      await setToken(data['access_token']);
      return data;
    } else {
      throw Exception('Registration failed');
    }
  }

  static Future<void> logout() async {
    await clearToken();
  }
}
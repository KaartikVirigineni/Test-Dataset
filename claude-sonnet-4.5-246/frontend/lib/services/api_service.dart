import 'package:http/http.dart' as http;
import 'dart:convert';
import 'auth_service.dart';

class ApiService {
  final AuthService authService;
  final String baseUrl = 'http://localhost:8000';

  ApiService(this.authService);

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        if (authService.token != null)
          'Authorization': 'Bearer ${authService.token}',
      };

  Future<List<dynamic>> getProjects() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/projects'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as List<dynamic>;
    }
    throw Exception('Failed to load projects');
  }

  Future<Map<String, dynamic>> createProject(
      String name, String description) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/projects'),
      headers: _headers,
      body: jsonEncode({
        'name': name,
        'description': description,
        'status': 'active',
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Failed to create project');
  }

  Future<List<dynamic>> getTasks(int projectId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/projects/$projectId/tasks'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body) as List<dynamic>;
    }
    throw Exception('Failed to load tasks');
  }

  Future<Map<String, dynamic>> createTask(
      int projectId, String title, String description) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/projects/$projectId/tasks'),
      headers: _headers,
      body: jsonEncode({
        'title': title,
        'description': description,
        'status': 'todo',
        'priority': 'medium',
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Failed to create task');
  }

  Future<void> updateTaskStatus(int taskId, String status) async {
    final response = await http.put(
      Uri.parse('$baseUrl/api/tasks/$taskId'),
      headers: _headers,
      body: jsonEncode({'status': status}),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to update task');
    }
  }

  Future<void> deleteProject(int projectId) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/api/projects/$projectId'),
      headers: _headers,
    );

    if (response.statusCode != 204) {
      throw Exception('Failed to delete project');
    }
  }

  Future<void> deleteTask(int taskId) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/api/tasks/$taskId'),
      headers: _headers,
    );

    if (response.statusCode != 204) {
      throw Exception('Failed to delete task');
    }
  }
}
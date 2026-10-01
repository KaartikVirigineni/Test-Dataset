import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'auth_service.dart';

class FileService {
  static const String baseUrl = 'http://localhost:8000';

  static Future<List<dynamic>> listFiles() async {
    final token = await AuthService.getToken();
    final response = await http.get(
      Uri.parse('$baseUrl/api/files'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to load files');
    }
  }

  static Future<Map<String, dynamic>> uploadFile(String filename, Uint8List bytes) async {
    final token = await AuthService.getToken();
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/api/files/upload'));
    request.headers['Authorization'] = 'Bearer $token';
    request.files.add(http.MultipartFile.fromBytes('file', bytes, filename: filename));

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to upload file');
    }
  }

  static Future<void> deleteFile(int fileId) async {
    final token = await AuthService.getToken();
    final response = await http.delete(
      Uri.parse('$baseUrl/api/files/$fileId'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to delete file');
    }
  }

  static String getDownloadUrl(int fileId) {
    return '$baseUrl/api/files/$fileId';
  }
}
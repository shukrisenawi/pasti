import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  static const String baseUrl = "https://pastikawasansik.my.id/api/guru";
  final _storage = const FlutterSecureStorage();

  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  Future<Map<String, String>> _headers() async {
    String? token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<http.Response> login(String email, String password, String deviceName) async {
    return await http.post(
      Uri.parse("$baseUrl/login"),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': deviceName,
      }),
    );
  }

  Future<http.Response> getProfile() async {
    return await http.get(Uri.parse("$baseUrl/profile"), headers: await _headers());
  }

  Future<http.Response> getKpi() async {
    return await http.get(Uri.parse("$baseUrl/kpi"), headers: await _headers());
  }

  Future<http.Response> getLeaveNotices() async {
    return await http.get(Uri.parse("$baseUrl/leave-notices"), headers: await _headers());
  }

  Future<http.Response> logout() async {
    final res = await http.post(Uri.parse("$baseUrl/logout"), headers: await _headers());
    await _storage.delete(key: 'auth_token');
    return res;
  }
}

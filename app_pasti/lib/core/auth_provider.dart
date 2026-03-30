import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'api_service.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService _api = ApiService();
  final _storage = const FlutterSecureStorage();
  
  bool _isAuthenticated = false;
  bool get isAuthenticated => _isAuthenticated;
  
  Map<String, dynamic>? _user;
  Map<String, dynamic>? get user => _user;
  
  Future<void> checkAuth() async {
    String? token = await _storage.read(key: 'auth_token');
    if (token != null) {
      _isAuthenticated = true;
      notifyListeners();
      await fetchProfile();
    }
  }

  Future<bool> login(String email, String password, String deviceName) async {
    try {
      final res = await _api.login(email, password, deviceName);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        await _storage.write(key: 'auth_token', value: data['token']);
        _user = data['user'];
        _isAuthenticated = true;
        notifyListeners();
        return true;
      }
    } catch (e) {
        print("Login error: $e");
    }
    return false;
  }

  Future<void> fetchProfile() async {
    try {
      final res = await _api.getProfile();
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        _user = data['user'];
        notifyListeners();
      }
    } catch (e) {
       print("Fetch profile error: $e");
    }
  }

  Future<void> logout() async {
    await _api.logout();
    await _storage.delete(key: 'auth_token');
    _user = null;
    _isAuthenticated = false;
    notifyListeners();
  }
}

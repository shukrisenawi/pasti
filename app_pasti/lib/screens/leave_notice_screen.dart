import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:http/http.dart' as http;
import '../core/api_service.dart';

class LeaveNoticeScreen extends StatefulWidget {
  const LeaveNoticeScreen({super.key});

  @override
  State<LeaveNoticeScreen> createState() => _LeaveNoticeScreenState();
}

class _LeaveNoticeScreenState extends State<LeaveNoticeScreen> {
  final ApiService _api = ApiService();
  bool _isLoading = true;
  List<dynamic>? _notices;

  @override
  void initState() {
    super.initState();
    _fetchNotices();
  }

  Future<void> _fetchNotices() async {
    try {
      final res = await _api.getLeaveNotices();
      if (res.statusCode == 200) {
        setState(() {
          _notices = jsonDecode(res.body);
          _isLoading = false;
        });
      }
    } catch (e) {
      print("Fetch notices error: $e");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Notis Cuti', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddNoticeDialog(),
        backgroundColor: Theme.of(context).primaryColor,
        child: const Icon(Icons.add, color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _fetchNotices,
              child: _notices == null || _notices!.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.event_note_outlined, size: 60, color: Colors.grey[400]),
                          const SizedBox(height: 10),
                          const Text('Tiada rekod notis cuti.'),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(20),
                      itemCount: _notices!.length,
                      itemBuilder: (context, index) {
                        final notice = _notices![index];
                        final date = notice['leave_date'];
                        final reason = notice['reason'];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          child: ListTile(
                            leading: Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: Colors.orange[50],
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.calendar_month, color: Colors.orange),
                            ),
                            title: Text(date, style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Text(reason),
                            trailing: const Icon(Icons.chevron_right, color: Colors.grey),
                          ),
                        );
                      },
                    ),
            ),
    );
  }

  void _showAddNoticeDialog() {
    final leaveDateController = TextEditingController();
    final leaveUntilController = TextEditingController();
    final reasonController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 25, right: 25, top: 25,
            bottom: MediaQuery.of(context).viewInsets.bottom + 25,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Mohon Cuti',
                style: GoogleFonts.outfit(fontSize: 22, fontWeight: FontWeight.bold),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              _buildDateField(context, 'Tarikh Mula', leaveDateController),
              const SizedBox(height: 15),
              _buildDateField(context, 'Tarikh Tamat', leaveUntilController),
              const SizedBox(height: 15),
              TextField(
                controller: reasonController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Sebab Cuti',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  filled: true,
                  fillColor: Colors.grey[50],
                ),
              ),
              const SizedBox(height: 25),
              ElevatedButton(
                onPressed: () async {
                  if (leaveDateController.text.isNotEmpty && leaveUntilController.text.isNotEmpty && reasonController.text.isNotEmpty) {
                    Navigator.pop(context);
                    await _submitNotice(leaveDateController.text, leaveUntilController.text, reasonController.text);
                  }
                },
                style: ElevatedButton.card(
                  backgroundColor: Theme.of(context).primaryColor,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('Hantar Permohonan', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDateField(BuildContext context, String label, TextEditingController controller) {
    return TextField(
      controller: controller,
      readOnly: true,
      onTap: () async {
        DateTime? pickedDate = await showDatePicker(
          context: context,
          initialDate: DateTime.now(),
          firstDate: DateTime.now(),
          lastDate: DateTime(2101),
        );
        if (pickedDate != null) {
          controller.text = DateFormat('yyyy-MM-dd').format(pickedDate);
        }
      },
      decoration: InputDecoration(
        hintText: label,
        prefixIcon: const Icon(Icons.date_range),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        filled: true,
        fillColor: Colors.grey[50],
      ),
    );
  }

  Future<void> _submitNotice(String leaveDate, String leaveUntil, String reason) async {
    setState(() => _isLoading = true);
    try {
       // We'll use a direct post here for simplicity, or we can add it to ApiService
       final token = await _api.getToken();
       final res = await http.post(
         Uri.parse("${ApiService.baseUrl}/leave-notices"),
         headers: {
           'Content-Type': 'application/json',
           'Accept': 'application/json',
           'Authorization': 'Bearer $token',
         },
         body: jsonEncode({
           'leave_date': leaveDate,
           'leave_until': leaveUntil,
           'reason': reason,
         }),
       );
       
       if (res.statusCode == 201) {
         _fetchNotices();
       } else {
         setState(() => _isLoading = false);
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Gagal menghantar permohonan.')));
       }
    } catch (e) {
       print("Submit notice error: $e");
       setState(() => _isLoading = false);
    }
  }
}

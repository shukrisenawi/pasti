import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fl_chart/fl_chart.dart';
import '../core/api_service.dart';

class KpiScreen extends StatefulWidget {
  const KpiScreen({super.key});

  @override
  State<KpiScreen> createState() => _KpiScreenState();
}

class _KpiScreenState extends State<KpiScreen> {
  final ApiService _api = ApiService();
  bool _isLoading = true;
  Map<String, dynamic>? _kpiData;

  @override
  void initState() {
    super.initState();
    _fetchKpi();
  }

  Future<void> _fetchKpi() async {
    try {
      final res = await _api.getKpi();
      if (res.statusCode == 200) {
        setState(() {
          _kpiData = jsonDecode(res.body);
          _isLoading = false;
        });
      }
    } catch (e) {
      print("KPI Fetch error: $e");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Prestasi KPI', style: GoogleFonts.outfit(fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _fetchKpi,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildSummaryCard(),
                    const SizedBox(height: 30),
                    Text(
                      'Pecahan Markah',
                      style: GoogleFonts.outfit(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 15),
                    _buildScoreBreakdown(),
                    const SizedBox(height: 30),
                    Text(
                      'Senarai Program',
                      style: GoogleFonts.outfit(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 15),
                    _buildProgramList(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSummaryCard() {
    final score = _kpiData?['kpi']?['total_score'] ?? 0;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(25),
      decoration: BoxDecoration(
        color: Theme.of(context).primaryColor,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        children: [
          Text(
            'Markah Keseluruhan (${_kpiData?['year']})',
            style: GoogleFonts.inter(color: Colors.white, fontSize: 16),
          ),
          const SizedBox(height: 10),
          Text(
            '$score%',
            style: GoogleFonts.outfit(
              color: Colors.white,
              fontSize: 48,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            score >= 80 ? 'CEMERLANG' : (score >= 50 ? 'BAIK' : 'BOLEH DIPERBAIKI'),
            style: GoogleFonts.inter(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              letterSpacing: 2,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildScoreBreakdown() {
    final kpi = _kpiData?['kpi'];
    if (kpi == null) return const SizedBox();

    return Column(
      children: [
        _buildScoreBar('Kehadiran Program', kpi['program_score'] ?? 0),
        _buildScoreBar('Kehadiran Mesyuarat', kpi['meeting_score'] ?? 0),
        _buildScoreBar('Tugasan & Aktiviti', kpi['activity_score'] ?? 0),
        _buildScoreBar('Laporan Mingguan', kpi['report_score'] ?? 0),
      ],
    );
  }

  Widget _buildScoreBar(String label, dynamic score) {
    double value = (score is int) ? score.toDouble() : (score ?? 0.0);
    return Padding(
      padding: const EdgeInsets.only(bottom: 15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: GoogleFonts.inter(fontWeight: FontWeight.w500)),
              Text('$value%', style: GoogleFonts.inter(fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 8),
          LinearProgressIndicator(
            value: value / 100,
            backgroundColor: Colors.grey[200],
            color: Theme.of(context).primaryColor,
            minHeight: 8,
            borderRadius: BorderRadius.circular(4),
          ),
        ],
      ),
    );
  }

  Widget _buildProgramList() {
    final programs = _kpiData?['programs'] as List?;
    if (programs == null || programs.isEmpty) {
      return const Text('Tiada program direkodkan.');
    }

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: programs.length,
      itemBuilder: (context, index) {
        final prog = programs[index];
        final statusId = prog['status'];
        final statusName = prog['status_name'] ?? 'Tiada Status';

        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: ListTile(
            title: Text(prog['title'], style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text(prog['date']),
            trailing: Chip(
              label: Text(statusName),
              backgroundColor: statusName == 'HADIR' ? Colors.green[50] : Colors.red[50],
              labelStyle: TextStyle(color: statusName == 'HADIR' ? Colors.green : Colors.red),
            ),
          ),
        );
      },
    );
  }
}

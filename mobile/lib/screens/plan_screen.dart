import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../widgets/common.dart';

class PlanScreen extends StatefulWidget {
  const PlanScreen({super.key});

  @override
  State<PlanScreen> createState() => _PlanScreenState();
}

class _PlanScreenState extends State<PlanScreen> {
  Map<String, dynamic>? _plan;
  String? _error;
  bool _loading = true;
  bool _regenerating = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final plan = await ApiClient.instance.getCurrentPlan();
      setState(() {
        _plan = plan;
        _error = null;
      });
    } on ApiException catch (e) {
      if (e.statusCode == 404) {
        if (mounted) Navigator.of(context).pushReplacementNamed('/onboarding');
        return;
      }
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Something went wrong');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _onRegenerate() async {
    setState(() => _regenerating = true);
    try {
      final plan = await ApiClient.instance.regeneratePlan();
      setState(() => _plan = plan);
    } finally {
      if (mounted) setState(() => _regenerating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_error != null) {
      return Scaffold(body: Center(child: Text(_error!, style: const TextStyle(color: Color(0xFFDC2626)))));
    }
    final plan = _plan;
    if (plan == null) return const Scaffold(body: SizedBox.shrink());

    final rationale = plan['ai_rationale'] as Map<String, dynamic>?;
    final days = List<dynamic>.from(plan['days'] as List? ?? []);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 560),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 60),
              children: [
                Text(plan['title'] as String? ?? '', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                if (rationale?['coach_note'] != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      rationale!['coach_note'] as String,
                      style: const TextStyle(fontSize: 15, fontStyle: FontStyle.italic, color: Color(0xFF2563EB)),
                    ),
                  ),
                if (rationale?['summary'] != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text(rationale!['summary'] as String, style: const TextStyle(fontSize: 14, color: Color(0xFF4B5563))),
                  ),
                if (rationale?['adjustment'] != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 16),
                    child: Text(rationale!['adjustment'] as String, style: const TextStyle(fontSize: 14, color: Color(0xFF059669))),
                  ),
                for (final day in days) _buildDayCard(day as Map<String, dynamic>),
                const SizedBox(height: 4),
                PrimaryButton(
                  label: _regenerating ? 'Adjusting plan...' : "Request next week's plan",
                  onPressed: _onRegenerate,
                  loading: _regenerating,
                ),
                const SizedBox(height: 12),
                _secondaryButton('Log a workout', () => Navigator.of(context).pushNamed('/log')),
                const SizedBox(height: 12),
                _secondaryButton('Go to my pod', () => Navigator.of(context).pushNamed('/pod')),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDayCard(Map<String, dynamic> day) {
    final isRest = day['focus'] == 'Rest';
    final exercises = List<dynamic>.from(day['exercises'] as List? ?? []);
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        border: Border.all(color: const Color(0xFFE5E7EB)),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Day ${day['day_number']}: ${day['focus']}',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          if (isRest)
            Text(day['notes'] as String? ?? '', style: const TextStyle(fontSize: 14, color: Color(0xFF6B7280)))
          else
            for (final ex in exercises)
              Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Text(
                  '${ex['exercise_name']} \u2014 ${ex['sets']} x ${ex['reps']}'
                  '${ex['rest_seconds'] != null ? ' (rest ${ex['rest_seconds']}s)' : ''}',
                  style: const TextStyle(fontSize: 14),
                ),
              ),
        ],
      ),
    );
  }

  Widget _secondaryButton(String label, VoidCallback onPressed) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton(
        onPressed: onPressed,
        style: OutlinedButton.styleFrom(
          foregroundColor: const Color(0xFF111827),
          side: const BorderSide(color: Color(0xFF111827)),
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
        child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
      ),
    );
  }
}

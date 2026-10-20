import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../widgets/common.dart';

class WorkoutLogScreen extends StatefulWidget {
  const WorkoutLogScreen({super.key});

  @override
  State<WorkoutLogScreen> createState() => _WorkoutLogScreenState();
}

class _WorkoutLogScreenState extends State<WorkoutLogScreen> {
  final _durationController = TextEditingController(text: '30');
  final _effortController = TextEditingController(text: '7');
  final _notesController = TextEditingController();
  bool _submitted = false;
  String? _error;
  bool _submitting = false;

  Future<void> _onSubmit() async {
    setState(() {
      _error = null;
      _submitting = true;
    });
    try {
      await ApiClient.instance.logWorkout(
        durationMinutes: int.tryParse(_durationController.text) ?? 30,
        perceivedEffort: int.tryParse(_effortController.text) ?? 7,
        notes: _notesController.text,
      );
      setState(() => _submitted = true);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Something went wrong');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_submitted) {
      return Scaffold(
        body: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text('Nice work! 💪', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 16),
                    const Text(
                      "Your workout has been logged. Your AI coach will factor this into next week's plan.",
                      style: TextStyle(fontSize: 15, color: Color(0xFF4B5563)),
                    ),
                    const SizedBox(height: 16),
                    PrimaryButton(
                      label: 'Share with your pod',
                      onPressed: () => Navigator.of(context).pushReplacementNamed('/pod'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    }

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text("Log today's workout", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  const Text('Duration (minutes)', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _durationController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  const Text('Perceived effort (1-10)', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _effortController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  const Text('Notes', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _notesController,
                    maxLines: 3,
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  ErrorText(_error),
                  PrimaryButton(
                    label: _submitting ? 'Saving...' : 'Save workout',
                    onPressed: _onSubmit,
                    loading: _submitting,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

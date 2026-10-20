import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../widgets/common.dart';

const _goals = [
  {'value': 'build_muscle', 'label': 'Build Muscle'},
  {'value': 'lose_fat', 'label': 'Lose Fat'},
  {'value': 'endurance', 'label': 'Endurance'},
  {'value': 'general_fitness', 'label': 'General Fitness'},
];

const _experienceLevels = ['beginner', 'intermediate', 'advanced'];
const _equipmentOptions = ['dumbbells', 'barbell', 'bands', 'kettlebells'];
const _niches = [
  {'value': 'busy_professionals', 'label': 'Busy Professional'},
  {'value': 'home_gym_minimalist', 'label': 'Home-Gym Minimalist'},
  {'value': 'returning_after_break', 'label': 'Returning After a Break'},
  {'value': 'hybrid_athlete', 'label': 'Runner / Hybrid Athlete'},
];

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  String _goal = 'build_muscle';
  String _experienceLevel = 'beginner';
  final _daysPerWeekController = TextEditingController(text: '3');
  final _sessionDurationController = TextEditingController(text: '30');
  final Set<String> _equipment = {};
  String _niche = 'busy_professionals';
  String? _error;
  bool _submitting = false;

  Future<void> _onSubmit() async {
    setState(() {
      _error = null;
      _submitting = true;
    });
    try {
      await ApiClient.instance.saveProfile({
        'goal': _goal,
        'experience_level': _experienceLevel,
        'days_per_week': int.tryParse(_daysPerWeekController.text) ?? 3,
        'session_duration_minutes': int.tryParse(_sessionDurationController.text) ?? 30,
        'equipment': _equipment.toList(),
        'niche': _niche,
      });
      // Every new user is placed into an accountability pod immediately so
      // nobody lands in an empty room on day one.
      try {
        await ApiClient.instance.autoJoinPod();
      } catch (_) {
        // Non-fatal: the user can still be matched into a pod later.
      }
      if (mounted) Navigator.of(context).pushReplacementNamed('/plan');
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
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 520),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Tell us about your goals',
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _sectionLabel('Goal'),
                  Wrap(
                    children: _goals
                        .map((g) => SelectableChip(
                              label: g['label']!,
                              selected: _goal == g['value'],
                              onPressed: () => setState(() => _goal = g['value']!),
                            ))
                        .toList(),
                  ),
                  _sectionLabel('Experience level'),
                  Wrap(
                    children: _experienceLevels
                        .map((level) => SelectableChip(
                              label: level,
                              selected: _experienceLevel == level,
                              onPressed: () => setState(() => _experienceLevel = level),
                            ))
                        .toList(),
                  ),
                  _sectionLabel('Days per week'),
                  SizedBox(
                    width: 160,
                    child: TextField(
                      controller: _daysPerWeekController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(border: OutlineInputBorder()),
                    ),
                  ),
                  _sectionLabel('Session duration (minutes)'),
                  SizedBox(
                    width: 160,
                    child: TextField(
                      controller: _sessionDurationController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(border: OutlineInputBorder()),
                    ),
                  ),
                  _sectionLabel('Available equipment (bodyweight always included)'),
                  Wrap(
                    children: _equipmentOptions
                        .map((item) => SelectableChip(
                              label: item,
                              selected: _equipment.contains(item),
                              onPressed: () => setState(() {
                                if (_equipment.contains(item)) {
                                  _equipment.remove(item);
                                } else {
                                  _equipment.add(item);
                                }
                              }),
                            ))
                        .toList(),
                  ),
                  _sectionLabel('Which best describes you?'),
                  Wrap(
                    children: _niches
                        .map((n) => SelectableChip(
                              label: n['label']!,
                              selected: _niche == n['value'],
                              onPressed: () => setState(() => _niche = n['value']!),
                            ))
                        .toList(),
                  ),
                  const SizedBox(height: 16),
                  ErrorText(_error),
                  PrimaryButton(
                    label: _submitting ? 'Generating your plan...' : 'Generate my AI plan',
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

  Widget _sectionLabel(String text) => Padding(
        padding: const EdgeInsets.only(top: 16, bottom: 8),
        child: Text(
          text,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF374151)),
        ),
      );
}

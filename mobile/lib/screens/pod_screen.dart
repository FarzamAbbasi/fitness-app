import 'package:flutter/material.dart';
import '../api/api_client.dart';

class PodScreen extends StatefulWidget {
  const PodScreen({super.key});

  @override
  State<PodScreen> createState() => _PodScreenState();
}

class _PodScreenState extends State<PodScreen> {
  Map<String, dynamic>? _pod;
  List<dynamic> _checkins = [];
  final _messageController = TextEditingController();
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final pods = await ApiClient.instance.getMyPods();
      final mine = pods.isNotEmpty ? Map<String, dynamic>.from(pods.first as Map) : null;
      List<dynamic> checkins = [];
      if (mine != null) {
        checkins = await ApiClient.instance.getPodCheckins(mine['id'] as int);
      }
      setState(() {
        _pod = mine;
        _checkins = checkins;
        _error = null;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Something went wrong');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _onPostCheckin() async {
    final pod = _pod;
    final message = _messageController.text.trim();
    if (pod == null || message.isEmpty) return;
    await ApiClient.instance.postPodCheckin(pod['id'] as int, message);
    _messageController.clear();
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_error != null) {
      return Scaffold(body: Center(child: Text(_error!, style: const TextStyle(color: Color(0xFFDC2626)))));
    }
    final pod = _pod;
    if (pod == null) {
      return const Scaffold(
        body: Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('No pod yet', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                SizedBox(height: 8),
                Text('Complete onboarding to get matched into an accountability pod.'),
              ],
            ),
          ),
        ),
      );
    }

    final members = List<dynamic>.from(pod['members'] as List? ?? []);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                Text(pod['name'] as String? ?? '', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Text('${members.length} members', style: const TextStyle(fontSize: 14, color: Color(0xFF6B7280))),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _messageController,
                        decoration: const InputDecoration(
                          hintText: 'Share a check-in with your pod...',
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: _onPostCheckin,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF111827),
                        foregroundColor: Colors.white,
                      ),
                      child: const Text('Post'),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                for (final c in _checkins) _buildCheckin(Map<String, dynamic>.from(c as Map)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCheckin(Map<String, dynamic> checkin) {
    final user = checkin['user'] as Map<String, dynamic>?;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: Color(0xFFF3F4F6))),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(user?['name'] as String? ?? 'Member', style: const TextStyle(fontWeight: FontWeight.w600)),
          Text(checkin['message'] as String? ?? ''),
        ],
      ),
    );
  }
}

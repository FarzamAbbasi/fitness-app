import React, { useState } from 'react';
import { View, Text, Pressable, StyleSheet, TextInput } from 'react-native';
import { useNavigate } from 'react-router-dom';
import { api, ApiError } from '../api/client';

const GOALS = [
  { value: 'build_muscle', label: 'Build Muscle' },
  { value: 'lose_fat', label: 'Lose Fat' },
  { value: 'endurance', label: 'Endurance' },
  { value: 'general_fitness', label: 'General Fitness' },
];

const EXPERIENCE_LEVELS = ['beginner', 'intermediate', 'advanced'];
const EQUIPMENT_OPTIONS = ['dumbbells', 'barbell', 'bands', 'kettlebells'];
const NICHES = [
  { value: 'busy_professionals', label: 'Busy Professional' },
  { value: 'home_gym_minimalist', label: 'Home-Gym Minimalist' },
  { value: 'returning_after_break', label: 'Returning After a Break' },
  { value: 'hybrid_athlete', label: 'Runner / Hybrid Athlete' },
];

function Chip({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      style={[styles.chip, selected && styles.chipSelected]}
    >
      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{label}</Text>
    </Pressable>
  );
}

export default function OnboardingScreen() {
  const navigate = useNavigate();
  const [goal, setGoal] = useState('build_muscle');
  const [experienceLevel, setExperienceLevel] = useState('beginner');
  const [daysPerWeek, setDaysPerWeek] = useState('3');
  const [sessionDuration, setSessionDuration] = useState('30');
  const [equipment, setEquipment] = useState<string[]>([]);
  const [niche, setNiche] = useState('busy_professionals');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const toggleEquipment = (item: string) => {
    setEquipment((prev) => (prev.includes(item) ? prev.filter((e) => e !== item) : [...prev, item]));
  };

  const onSubmit = async () => {
    setError(null);
    setSubmitting(true);
    try {
      await api.saveProfile({
        goal,
        experience_level: experienceLevel,
        days_per_week: Number(daysPerWeek),
        session_duration_minutes: Number(sessionDuration),
        equipment,
        niche,
      });
      // Every new user is placed into an accountability pod immediately so
      // nobody lands in an empty room on day one.
      await api.autoJoinPod().catch(() => undefined);
      navigate('/plan');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Something went wrong');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Tell us about your goals</Text>

      <Text style={styles.label}>Goal</Text>
      <View style={styles.row}>
        {GOALS.map((g) => (
          <Chip key={g.value} label={g.label} selected={goal === g.value} onPress={() => setGoal(g.value)} />
        ))}
      </View>

      <Text style={styles.label}>Experience level</Text>
      <View style={styles.row}>
        {EXPERIENCE_LEVELS.map((level) => (
          <Chip key={level} label={level} selected={experienceLevel === level} onPress={() => setExperienceLevel(level)} />
        ))}
      </View>

      <Text style={styles.label}>Days per week</Text>
      <TextInput
        style={styles.input}
        keyboardType="numeric"
        value={daysPerWeek}
        onChangeText={setDaysPerWeek}
      />

      <Text style={styles.label}>Session duration (minutes)</Text>
      <TextInput
        style={styles.input}
        keyboardType="numeric"
        value={sessionDuration}
        onChangeText={setSessionDuration}
      />

      <Text style={styles.label}>Available equipment (bodyweight always included)</Text>
      <View style={styles.row}>
        {EQUIPMENT_OPTIONS.map((item) => (
          <Chip key={item} label={item} selected={equipment.includes(item)} onPress={() => toggleEquipment(item)} />
        ))}
      </View>

      <Text style={styles.label}>Which best describes you?</Text>
      <View style={styles.row}>
        {NICHES.map((n) => (
          <Chip key={n.value} label={n.label} selected={niche === n.value} onPress={() => setNiche(n.value)} />
        ))}
      </View>

      {error && <Text style={styles.error}>{error}</Text>}
      <Pressable style={styles.button} onPress={onSubmit} disabled={submitting}>
        <Text style={styles.buttonText}>{submitting ? 'Generating your plan...' : 'Generate my AI plan'}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { maxWidth: 520, marginTop: 40, marginHorizontal: 'auto', padding: 24 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 24 },
  label: { fontSize: 14, fontWeight: '600', marginTop: 16, marginBottom: 8, color: '#374151' },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#d1d5db',
    marginRight: 8,
    marginBottom: 8,
  },
  chipSelected: { backgroundColor: '#111827', borderColor: '#111827' },
  chipText: { color: '#111827' },
  chipTextSelected: { color: '#fff' },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    maxWidth: 160,
  },
  button: {
    backgroundColor: '#111827',
    borderRadius: 8,
    padding: 14,
    alignItems: 'center',
    marginTop: 24,
  },
  buttonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  error: { color: '#dc2626', marginTop: 12 },
});

import React, { useEffect, useState } from 'react';
import { View, Text, Pressable, StyleSheet, ScrollView } from 'react-native';
import { useNavigate } from 'react-router-dom';
import { api, ApiError } from '../api/client';

export default function PlanScreen() {
  const navigate = useNavigate();
  const [plan, setPlan] = useState<any | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [regenerating, setRegenerating] = useState(false);

  const load = async () => {
    setLoading(true);
    try {
      const { plan } = await api.getCurrentPlan();
      setPlan(plan);
      setError(null);
    } catch (e) {
      if (e instanceof ApiError && e.status === 404) {
        navigate('/onboarding');
        return;
      }
      setError(e instanceof ApiError ? e.message : 'Something went wrong');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const onRegenerate = async () => {
    setRegenerating(true);
    try {
      const { plan } = await api.regeneratePlan();
      setPlan(plan);
    } finally {
      setRegenerating(false);
    }
  };

  if (loading) return <Text style={styles.loading}>Loading your plan...</Text>;
  if (error) return <Text style={styles.error}>{error}</Text>;
  if (!plan) return null;

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>{plan.title}</Text>
      {plan.ai_rationale?.coach_note && <Text style={styles.coachNote}>{plan.ai_rationale.coach_note}</Text>}
      {plan.ai_rationale?.summary && <Text style={styles.summary}>{plan.ai_rationale.summary}</Text>}
      {plan.ai_rationale?.adjustment && <Text style={styles.adjustment}>{plan.ai_rationale.adjustment}</Text>}

      {plan.days.map((day: any) => (
        <View key={day.id} style={styles.dayCard}>
          <Text style={styles.dayFocus}>
            Day {day.day_number}: {day.focus}
          </Text>
          {day.focus === 'Rest' ? (
            <Text style={styles.restNote}>{day.notes}</Text>
          ) : (
            day.exercises.map((ex: any) => (
              <Text key={ex.id} style={styles.exercise}>
                {ex.exercise_name} — {ex.sets} x {ex.reps}
                {ex.rest_seconds ? ` (rest ${ex.rest_seconds}s)` : ''}
              </Text>
            ))
          )}
        </View>
      ))}

      <Pressable style={styles.button} onPress={onRegenerate} disabled={regenerating}>
        <Text style={styles.buttonText}>
          {regenerating ? 'Adjusting plan...' : 'Request next week\u2019s plan'}
        </Text>
      </Pressable>
      <Pressable style={styles.secondaryButton} onPress={() => navigate('/log')}>
        <Text style={styles.secondaryButtonText}>Log a workout</Text>
      </Pressable>
      <Pressable style={styles.secondaryButton} onPress={() => navigate('/pod')}>
        <Text style={styles.secondaryButtonText}>Go to my pod</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { maxWidth: 560, marginHorizontal: 'auto', padding: 24, paddingBottom: 60 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 8 },
  coachNote: { fontSize: 15, fontStyle: 'italic', color: '#2563eb', marginBottom: 8 },
  summary: { fontSize: 14, color: '#4b5563', marginBottom: 4 },
  adjustment: { fontSize: 14, color: '#059669', marginBottom: 16 },
  dayCard: { borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10, padding: 16, marginBottom: 12 },
  dayFocus: { fontSize: 16, fontWeight: '600', marginBottom: 8 },
  exercise: { fontSize: 14, marginBottom: 4 },
  restNote: { fontSize: 14, color: '#6b7280' },
  button: {
    backgroundColor: '#111827',
    borderRadius: 8,
    padding: 14,
    alignItems: 'center',
    marginTop: 16,
  },
  buttonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  secondaryButton: {
    borderWidth: 1,
    borderColor: '#111827',
    borderRadius: 8,
    padding: 14,
    alignItems: 'center',
    marginTop: 12,
  },
  secondaryButtonText: { color: '#111827', fontWeight: '600', fontSize: 16 },
  loading: { textAlign: 'center', marginTop: 80 },
  error: { textAlign: 'center', marginTop: 80, color: '#dc2626' },
});

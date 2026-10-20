import React, { useState } from 'react';
import { View, Text, TextInput, Pressable, StyleSheet } from 'react-native';
import { useNavigate } from 'react-router-dom';
import { api, ApiError } from '../api/client';

export default function WorkoutLogScreen() {
  const navigate = useNavigate();
  const [duration, setDuration] = useState('30');
  const [effort, setEffort] = useState('7');
  const [notes, setNotes] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async () => {
    setError(null);
    setSubmitting(true);
    try {
      await api.logWorkout({
        logged_date: new Date().toISOString().slice(0, 10),
        duration_minutes: Number(duration),
        perceived_effort: Number(effort),
        notes,
      });
      setSubmitted(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Something went wrong');
    } finally {
      setSubmitting(false);
    }
  };

  if (submitted) {
    return (
      <View style={styles.container}>
        <Text style={styles.title}>Nice work! 💪</Text>
        <Text style={styles.summary}>Your workout has been logged. Your AI coach will factor this into next week's plan.</Text>
        <Pressable style={styles.button} onPress={() => navigate('/pod')}>
          <Text style={styles.buttonText}>Share with your pod</Text>
        </Pressable>
        <Pressable style={styles.secondaryButton} onPress={() => navigate('/plan')}>
          <Text style={styles.secondaryButtonText}>Back to plan</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Log today's workout</Text>

      <Text style={styles.label}>Duration (minutes)</Text>
      <TextInput style={styles.input} keyboardType="numeric" value={duration} onChangeText={setDuration} />

      <Text style={styles.label}>Perceived effort (1-10)</Text>
      <TextInput style={styles.input} keyboardType="numeric" value={effort} onChangeText={setEffort} />

      <Text style={styles.label}>Notes</Text>
      <TextInput
        style={[styles.input, styles.textArea]}
        multiline
        numberOfLines={3}
        value={notes}
        onChangeText={setNotes}
      />

      {error && <Text style={styles.error}>{error}</Text>}
      <Pressable style={styles.button} onPress={onSubmit} disabled={submitting}>
        <Text style={styles.buttonText}>{submitting ? 'Saving...' : 'Save workout'}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { maxWidth: 420, marginTop: 40, marginHorizontal: 'auto', padding: 24 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 16 },
  label: { fontSize: 14, fontWeight: '600', marginTop: 12, marginBottom: 8, color: '#374151' },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
  },
  textArea: { minHeight: 80, textAlignVertical: 'top' },
  button: {
    backgroundColor: '#111827',
    borderRadius: 8,
    padding: 14,
    alignItems: 'center',
    marginTop: 24,
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
  summary: { fontSize: 15, color: '#4b5563', marginBottom: 16 },
  error: { color: '#dc2626', marginTop: 12 },
});

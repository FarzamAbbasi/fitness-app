import React, { useEffect, useState } from 'react';
import { View, Text, TextInput, Pressable, StyleSheet, ScrollView } from 'react-native';
import { api, ApiError } from '../api/client';

export default function PodScreen() {
  const [pod, setPod] = useState<any | null>(null);
  const [checkins, setCheckins] = useState<any[]>([]);
  const [message, setMessage] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const { pods } = await api.getMyPods();
      const mine = pods[0] ?? null;
      setPod(mine);
      if (mine) {
        const { data } = await api.getPodCheckins(mine.id);
        setCheckins(data);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Something went wrong');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const onPostCheckin = async () => {
    if (!pod || !message.trim()) return;
    await api.postPodCheckin(pod.id, message.trim());
    setMessage('');
    await load();
  };

  if (loading) return <Text style={styles.loading}>Loading your pod...</Text>;
  if (error) return <Text style={styles.error}>{error}</Text>;

  if (!pod) {
    return (
      <View style={styles.container}>
        <Text style={styles.title}>No pod yet</Text>
        <Text>Complete onboarding to get matched into an accountability pod.</Text>
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>{pod.name}</Text>
      <Text style={styles.subtitle}>{pod.members?.length ?? 0} members</Text>

      <View style={styles.composer}>
        <TextInput
          style={styles.input}
          placeholder="Share a check-in with your pod..."
          value={message}
          onChangeText={setMessage}
        />
        <Pressable style={styles.button} onPress={onPostCheckin}>
          <Text style={styles.buttonText}>Post</Text>
        </Pressable>
      </View>

      {checkins.map((c) => (
        <View key={c.id} style={styles.checkin}>
          <Text style={styles.checkinAuthor}>{c.user?.name ?? 'Member'}</Text>
          <Text>{c.message}</Text>
        </View>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { maxWidth: 480, marginHorizontal: 'auto', padding: 24 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 4 },
  subtitle: { fontSize: 14, color: '#6b7280', marginBottom: 16 },
  composer: { flexDirection: 'row', gap: 8, marginBottom: 20 },
  input: {
    flex: 1,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    padding: 10,
  },
  button: {
    backgroundColor: '#111827',
    borderRadius: 8,
    paddingVertical: 10,
    paddingHorizontal: 16,
    justifyContent: 'center',
  },
  buttonText: { color: '#fff', fontWeight: '600' },
  checkin: { borderBottomWidth: 1, borderBottomColor: '#f3f4f6', paddingVertical: 10 },
  checkinAuthor: { fontWeight: '600', marginBottom: 2 },
  loading: { textAlign: 'center', marginTop: 80 },
  error: { textAlign: 'center', marginTop: 80, color: '#dc2626' },
});

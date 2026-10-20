// Lightweight fetch-based API client for the fitness backend (Laravel + Sanctum).
// Token is kept in localStorage for the web app; the Flutter app will use its own
// secure storage equivalent against the same REST API.

const API_BASE_URL = (import.meta as any).env?.VITE_API_BASE_URL ?? 'http://localhost:8010/api';

const TOKEN_KEY = 'fitness_app_token';

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string | null): void {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }
}

export class ApiError extends Error {
  constructor(public status: number, message: string, public errors?: Record<string, string[]>) {
    super(message);
  }
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = getToken();

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers ?? {}),
    },
  });

  const isJson = response.headers.get('content-type')?.includes('application/json');
  const body = isJson ? await response.json() : null;

  if (!response.ok) {
    throw new ApiError(response.status, body?.message ?? 'Request failed', body?.errors);
  }

  return body as T;
}

export const api = {
  register: (data: { name: string; email: string; password: string; password_confirmation: string }) =>
    request<{ user: any; token: string }>('/auth/register', { method: 'POST', body: JSON.stringify(data) }),

  login: (data: { email: string; password: string }) =>
    request<{ user: any; token: string }>('/auth/login', { method: 'POST', body: JSON.stringify(data) }),

  logout: () => request<{ message: string }>('/auth/logout', { method: 'POST' }),

  me: () => request<{ user: any; subscription: any }>('/me'),

  saveProfile: (data: Record<string, unknown>) =>
    request<{ profile: any; plan: any }>('/profile', { method: 'POST', body: JSON.stringify(data) }),

  getProfile: () => request<{ profile: any }>('/profile'),

  getCurrentPlan: () => request<{ plan: any }>('/plan'),

  regeneratePlan: () => request<{ plan: any }>('/plan/regenerate', { method: 'POST' }),

  logWorkout: (data: Record<string, unknown>) =>
    request<{ log: any }>('/workout-logs', { method: 'POST', body: JSON.stringify(data) }),

  getWorkoutLogs: () => request<{ data: any[] }>('/workout-logs'),

  autoJoinPod: () => request<{ pod: any }>('/pods/auto-join', { method: 'POST' }),

  getMyPods: () => request<{ pods: any[] }>('/pods/mine'),

  getPodCheckins: (podId: number) => request<{ data: any[] }>(`/pods/${podId}/checkins`),

  postPodCheckin: (podId: number, message: string) =>
    request<{ checkin: any }>(`/pods/${podId}/checkins`, { method: 'POST', body: JSON.stringify({ message }) }),
};

import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from '../api/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import OnboardingScreen from '../screens/OnboardingScreen';
import PlanScreen from '../screens/PlanScreen';
import WorkoutLogScreen from '../screens/WorkoutLogScreen';
import PodScreen from '../screens/PodScreen';

function RequireAuth({ children }: { children: React.ReactElement }) {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

export default function AppNavigator() {
  return (
    <Routes>
      <Route path="/login" element={<LoginScreen />} />
      <Route path="/register" element={<RegisterScreen />} />
      <Route
        path="/onboarding"
        element={
          <RequireAuth>
            <OnboardingScreen />
          </RequireAuth>
        }
      />
      <Route
        path="/plan"
        element={
          <RequireAuth>
            <PlanScreen />
          </RequireAuth>
        }
      />
      <Route
        path="/log"
        element={
          <RequireAuth>
            <WorkoutLogScreen />
          </RequireAuth>
        }
      />
      <Route
        path="/pod"
        element={
          <RequireAuth>
            <PodScreen />
          </RequireAuth>
        }
      />
      <Route path="*" element={<Navigate to="/plan" replace />} />
    </Routes>
  );
}

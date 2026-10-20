import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Vite config for a React Native Web app: alias 'react-native' imports to
// 'react-native-web' so RN-style components (View, Text, StyleSheet, etc.)
// compile for the browser.
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      'react-native': 'react-native-web',
    },
    extensions: ['.web.tsx', '.web.ts', '.tsx', '.ts', '.web.js', '.js'],
  },
  define: {
    __DEV__: JSON.stringify(process.env.NODE_ENV !== 'production'),
  },
  server: {
    host: true,
    port: 5173,
  },
});

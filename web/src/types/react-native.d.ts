/**
 * Minimal ambient type declarations for the subset of React Native primitives
 * used in this app. We intentionally avoid depending on the full `react-native`
 * or `@types/react-native` packages (heavy, and the latter is just a deprecated
 * stub) since at runtime these imports are aliased to `react-native-web` via
 * vite.config.ts.
 */
declare module 'react-native' {
  import type { ComponentType, ReactNode, Ref } from 'react';

  export interface StyleProp {
    [key: string]: any;
  }

  export const View: ComponentType<{
    style?: StyleProp | StyleProp[];
    children?: ReactNode;
    [key: string]: any;
  }>;

  export const Text: ComponentType<{
    style?: StyleProp | StyleProp[];
    children?: ReactNode;
    [key: string]: any;
  }>;

  export const ScrollView: ComponentType<{
    style?: StyleProp | StyleProp[];
    contentContainerStyle?: StyleProp | StyleProp[];
    children?: ReactNode;
    [key: string]: any;
  }>;

  export const TextInput: ComponentType<{
    style?: StyleProp | StyleProp[];
    value?: string;
    placeholder?: string;
    secureTextEntry?: boolean;
    keyboardType?: string;
    onChangeText?: (text: string) => void;
    ref?: Ref<any>;
    [key: string]: any;
  }>;

  export const Pressable: ComponentType<{
    style?: StyleProp | StyleProp[] | ((state: { pressed: boolean }) => StyleProp | StyleProp[]);
    onPress?: (event?: any) => void;
    disabled?: boolean;
    children?: ReactNode;
    [key: string]: any;
  }>;

  export const Switch: ComponentType<{
    value?: boolean;
    onValueChange?: (value: boolean) => void;
    [key: string]: any;
  }>;

  export const ActivityIndicator: ComponentType<{
    size?: 'small' | 'large' | number;
    color?: string;
    [key: string]: any;
  }>;

  export const StyleSheet: {
    create<T extends Record<string, StyleProp>>(styles: T): T;
    flatten: (style: any) => StyleProp;
  };
}

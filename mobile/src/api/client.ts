import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

function resolveApiUrl(): string {
  const fromEnv = process.env.EXPO_PUBLIC_API_URL?.trim();
  if (fromEnv) return fromEnv.replace(/\/$/, '');

  const extra = Constants.expoConfig?.extra as { apiUrl?: string } | undefined;
  if (extra?.apiUrl) return extra.apiUrl.replace(/\/$/, '');

  // Live fallback for release APKs if config is missing.
  return 'http://94.103.163.218/unique-solution/api/v1';
}

export const API_URL = resolveApiUrl();

type ApiSuccess<T> = {
  success: boolean;
  data: T;
  message?: string;
  meta?: Record<string, number>;
};

let authToken: string | null = null;

export async function hydrateAuthToken(): Promise<string | null> {
  authToken = await AsyncStorage.getItem('us-auth-token');
  return authToken;
}

export async function setAuthToken(token: string | null): Promise<void> {
  authToken = token;
  if (token) await AsyncStorage.setItem('us-auth-token', token);
  else await AsyncStorage.removeItem('us-auth-token');
}

export function getAuthToken(): string | null {
  return authToken;
}

async function request<T>(
  method: string,
  path: string,
  body?: unknown,
  params?: Record<string, string | number | boolean | undefined | null>
): Promise<ApiSuccess<T>> {
  const url = new URL(`${API_URL}${path.startsWith('/') ? path : `/${path}`}`);
  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      url.searchParams.set(key, String(value));
    });
  }

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };
  if (authToken) headers.Authorization = `Bearer ${authToken}`;

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg =
      json?.message ||
      (json?.errors ? Object.values(json.errors).flat().join('\n') : null) ||
      `Request failed (${res.status})`;
    throw new Error(msg);
  }
  return json as ApiSuccess<T>;
}

export const apiGet = <T>(path: string, params?: Record<string, string | number | boolean | undefined | null>) =>
  request<T>('GET', path, undefined, params);

export const apiPost = <T>(path: string, body?: unknown) => request<T>('POST', path, body);
export const apiPut = <T>(path: string, body?: unknown) => request<T>('PUT', path, body);
export const apiDelete = <T>(path: string) => request<T>('DELETE', path);

import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

function resolveApiUrl(): string {
  const fromEnv = process.env.EXPO_PUBLIC_API_URL?.trim();
  if (fromEnv) return fromEnv.replace(/\/$/, '');

  const extra = Constants.expoConfig?.extra as { apiUrl?: string } | undefined;
  if (extra?.apiUrl) return extra.apiUrl.replace(/\/$/, '');

  return 'http://94.103.163.218/unique-solution/api/v1';
}

export const API_URL = resolveApiUrl();
const TOKEN_KEY = 'us-delivery-token';
const USER_KEY = 'us-delivery-user';

export class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

type ApiSuccess<T> = {
  success: boolean;
  data: T;
  message?: string;
  meta?: Record<string, unknown>;
};

let authToken: string | null = null;

export async function hydrateAuthToken(): Promise<string | null> {
  authToken = await AsyncStorage.getItem(TOKEN_KEY);
  return authToken;
}

export async function setAuthToken(token: string | null): Promise<void> {
  authToken = token;
  if (token) await AsyncStorage.setItem(TOKEN_KEY, token);
  else await AsyncStorage.removeItem(TOKEN_KEY);
}

export async function getCachedUser<T>(): Promise<T | null> {
  try {
    const raw = await AsyncStorage.getItem(USER_KEY);
    if (!raw) return null;
    return JSON.parse(raw) as T;
  } catch {
    return null;
  }
}

export async function setCachedUser(user: unknown | null): Promise<void> {
  if (user) await AsyncStorage.setItem(USER_KEY, JSON.stringify(user));
  else await AsyncStorage.removeItem(USER_KEY);
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

  let res: Response;
  try {
    res = await fetch(url.toString(), {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch (err) {
    const detail = err instanceof Error && err.message ? ` (${err.message})` : '';
    throw new ApiError(`Network error. Check your connection.${detail}`, 0);
  }

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg =
      json?.message ||
      (json?.errors ? Object.values(json.errors).flat().join('\n') : null) ||
      `Request failed (${res.status})`;
    throw new ApiError(String(msg), res.status);
  }
  return json as ApiSuccess<T>;
}

export const apiGet = <T>(path: string, params?: Record<string, string | number | boolean | undefined | null>) =>
  request<T>('GET', path, undefined, params);

export const apiPost = <T>(path: string, body?: unknown) => request<T>('POST', path, body);

export const apiPatch = <T>(path: string, body?: unknown) => request<T>('PATCH', path, body);

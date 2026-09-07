import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { apiPost } from '@/api/client';

type AnalyticsPayload = {
  event: string;
  screen?: string;
  properties?: Record<string, unknown>;
  occurred_at?: string;
};

const QUEUE_KEY = 'us-analytics-queue';
const SESSION_KEY = 'us-analytics-session';
const DEVICE_KEY = 'us-device-id';

let sessionId: string | null = null;
let deviceId: string | null = null;
let flushTimer: ReturnType<typeof setTimeout> | null = null;
let queue: AnalyticsPayload[] = [];

function appVersion(): string {
  return Constants.expoConfig?.version ?? '1.0.0';
}

async function ensureIds() {
  if (!sessionId) {
    sessionId = (await AsyncStorage.getItem(SESSION_KEY)) ?? `s_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    await AsyncStorage.setItem(SESSION_KEY, sessionId);
  }
  if (!deviceId) {
    deviceId = (await AsyncStorage.getItem(DEVICE_KEY)) ?? `d_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
    await AsyncStorage.setItem(DEVICE_KEY, deviceId);
  }
}

async function persistQueue() {
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(queue.slice(-100)));
}

export async function hydrateAnalyticsQueue() {
  try {
    const raw = await AsyncStorage.getItem(QUEUE_KEY);
    if (raw) queue = JSON.parse(raw);
  } catch {
    queue = [];
  }
  await ensureIds();
}

export async function track(
  event: string,
  properties?: Record<string, unknown>,
  screen?: string,
) {
  await ensureIds();
  const item: AnalyticsPayload = {
    event,
    screen,
    properties,
    occurred_at: new Date().toISOString(),
  };
  queue.push(item);
  if (__DEV__) {
    // eslint-disable-next-line no-console
    console.log('[analytics]', event, properties ?? {});
  }
  await persistQueue();
  scheduleFlush();
}

export function trackScreen(screen: string, properties?: Record<string, unknown>) {
  return track('screen_view', properties, screen);
}

function scheduleFlush() {
  if (flushTimer) return;
  flushTimer = setTimeout(() => {
    flushTimer = null;
    void flushAnalytics();
  }, 2500);
}

export async function flushAnalytics() {
  if (!queue.length) return;
  await ensureIds();
  const batch = queue.splice(0, 40);
  await persistQueue();
  try {
    await apiPost('/analytics/events', {
      events: batch,
      session_id: sessionId,
      platform: Platform.OS,
      app_version: appVersion(),
      device_id: deviceId,
    });
  } catch {
    queue = [...batch, ...queue].slice(0, 100);
    await persistQueue();
  }
}

export async function getTelemetryMeta() {
  await ensureIds();
  return {
    session_id: sessionId!,
    device_id: deviceId!,
    platform: Platform.OS,
    app_version: appVersion(),
  };
}

import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { apiPost } from '@/api/client';
import { getTelemetryMeta } from '@/utils/analytics';

type CrashPayload = {
  level?: string;
  message: string;
  stack?: string;
  screen?: string;
  context?: Record<string, unknown>;
  is_fatal?: boolean;
};

type GlobalErrorUtils = {
  getGlobalHandler?: () => ((error: Error, isFatal?: boolean) => void) | undefined;
  setGlobalHandler?: (handler: (error: Error, isFatal?: boolean) => void) => void;
};

let currentScreen: string | null = null;

function sentryDsn(): string | undefined {
  const extra = Constants.expoConfig?.extra as { sentryDsn?: string } | undefined;
  const dsn = extra?.sentryDsn || process.env.EXPO_PUBLIC_SENTRY_DSN || '';
  return dsn.trim() || undefined;
}

export function setCrashScreen(screen: string | null) {
  currentScreen = screen;
}

/** Sentry disabled in Expo Go for now — native module mismatches crash the app. */
export function initCrashReporting() {
  // no-op until a matching @sentry/react-native + dev build
  void sentryDsn();
}

export async function reportCrash(payload: CrashPayload) {
  const meta = await getTelemetryMeta();
  const body = {
    level: payload.level ?? 'error',
    message: payload.message.slice(0, 500),
    stack: payload.stack?.slice(0, 18000),
    screen: payload.screen ?? currentScreen ?? undefined,
    context: payload.context,
    platform: meta.platform,
    app_version: meta.app_version,
    device_id: meta.device_id,
    is_fatal: payload.is_fatal ?? false,
  };

  try {
    await apiPost('/crashes', body);
  } catch {
    // ignore offline
  }
}

export function installGlobalCrashHandlers() {
  const g = globalThis as typeof globalThis & { ErrorUtils?: GlobalErrorUtils };
  const ErrorUtils = g.ErrorUtils;
  if (!ErrorUtils?.getGlobalHandler || !ErrorUtils?.setGlobalHandler) return;

  const previous = ErrorUtils.getGlobalHandler();
  ErrorUtils.setGlobalHandler((error, isFatal) => {
    void reportCrash({
      message: error?.message || 'Unknown fatal',
      stack: error?.stack,
      is_fatal: !!isFatal,
      context: { platform: Platform.OS },
    });
    previous?.(error, isFatal);
  });
}

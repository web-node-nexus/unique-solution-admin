import { usePathname, useGlobalSearchParams } from 'expo-router';
import { useEffect, useRef } from 'react';
import { AppState } from 'react-native';
import { flushAnalytics, hydrateAnalyticsQueue, trackScreen } from '@/utils/analytics';
import { setCrashScreen } from '@/utils/crash';

export function AnalyticsProvider({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const params = useGlobalSearchParams();
  const last = useRef<string | null>(null);

  useEffect(() => {
    void hydrateAnalyticsQueue();
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'background' || state === 'inactive') {
        void flushAnalytics();
      }
    });
    return () => sub.remove();
  }, []);

  useEffect(() => {
    if (!pathname || pathname === last.current) return;
    last.current = pathname;
    setCrashScreen(pathname);
    void trackScreen(pathname, {
      params: Object.keys(params || {}).length ? params : undefined,
    });
  }, [pathname, params]);

  return <>{children}</>;
}

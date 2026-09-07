import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
  SourceSans3_400Regular,
  SourceSans3_500Medium,
  SourceSans3_600SemiBold,
  SourceSans3_700Bold,
  useFonts as useSourceSans,
} from '@expo-google-fonts/source-sans-3';
import {
  SourceSerif4_600SemiBold,
  SourceSerif4_700Bold,
  useFonts as useSourceSerif,
} from '@expo-google-fonts/source-serif-4';
import { Stack } from 'expo-router';
import * as Notifications from 'expo-notifications';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { CompareDock } from '@/components/catalog/CompareDock';
import { AnalyticsProvider } from '@/components/layout/AnalyticsProvider';
import { AppErrorBoundary } from '@/components/layout/AppErrorBoundary';
import { AppKeyboardProvider } from '@/components/layout/KeyboardForm';
import { useAuthStore } from '@/store/auth';
import { useShopStore } from '@/store/shop';
import { colors } from '@/theme/tokens';
import { initCrashReporting, installGlobalCrashHandlers } from '@/utils/crash';
import { openDeepLink } from '@/utils/deepLink';

SplashScreen.preventAutoHideAsync().catch(() => undefined);
initCrashReporting();
installGlobalCrashHandlers();

const queryClient = new QueryClient({
  defaultOptions: { queries: { staleTime: 60_000, retry: 1 } },
});

export default function RootLayout() {
  const bootstrap = useAuthStore((s) => s.bootstrap);
  const loadShop = useShopStore((s) => s.load);
  const [serifLoaded] = useSourceSerif({ SourceSerif4_600SemiBold, SourceSerif4_700Bold });
  const [sansLoaded] = useSourceSans({
    SourceSans3_400Regular,
    SourceSans3_500Medium,
    SourceSans3_600SemiBold,
    SourceSans3_700Bold,
  });
  const loaded = serifLoaded && sansLoaded;

  useEffect(() => {
    bootstrap();
    void loadShop();
  }, [bootstrap, loadShop]);

  useEffect(() => {
    if (loaded) SplashScreen.hideAsync().catch(() => undefined);
  }, [loaded]);

  useEffect(() => {
    try {
      const sub = Notifications.addNotificationResponseReceivedListener((response) => {
        const data = response.notification.request.content.data as
          | { link_type?: string; link_value?: string; title?: string }
          | undefined;
        if (!data?.link_type && !data?.link_value) return;
        void openDeepLink({
          type: data.link_type,
          value: data.link_value,
          label: data.title,
        });
      });
      return () => sub.remove();
    } catch {
      return undefined;
    }
  }, []);

  if (!loaded) return null;

  return (
    <GestureHandlerRootView style={{ flex: 1, backgroundColor: colors.canvas }}>
      <AppErrorBoundary>
        <AppKeyboardProvider>
          <QueryClientProvider client={queryClient}>
            <AnalyticsProvider>
              <StatusBar style="dark" />
              <Stack
                screenOptions={{
                  headerShown: false,
                  contentStyle: { backgroundColor: colors.canvas },
                  animation: 'slide_from_right',
                  gestureEnabled: true,
                }}
              >
                <Stack.Screen name="index" />
                <Stack.Screen name="(onboarding)" options={{ animation: 'fade' }} />
                <Stack.Screen name="(main)" options={{ animation: 'fade' }} />
                <Stack.Screen name="auth/login" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
                <Stack.Screen name="auth/register" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
                <Stack.Screen name="auth/forgot-password" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
                <Stack.Screen name="auth/reset-password" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
                <Stack.Screen name="profile/index" />
                <Stack.Screen name="products/index" />
                <Stack.Screen name="products/[id]" />
                <Stack.Screen name="search/index" options={{ animation: 'fade_from_bottom' }} />
                <Stack.Screen name="brands/index" />
                <Stack.Screen name="notifications/index" />
                <Stack.Screen name="checkout/index" />
                <Stack.Screen name="orders/index" />
                <Stack.Screen name="orders/[id]" />
                <Stack.Screen name="addresses/index" />
                <Stack.Screen name="deals/index" />
                <Stack.Screen name="wishlist/index" />
                <Stack.Screen name="compare/index" />
              </Stack>
              <CompareDock />
            </AnalyticsProvider>
          </QueryClientProvider>
        </AppKeyboardProvider>
      </AppErrorBoundary>
    </GestureHandlerRootView>
  );
}

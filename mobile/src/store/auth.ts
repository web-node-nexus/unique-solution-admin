import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import { authApi, AuthUser } from '@/api/auth';
import { hydrateAuthToken, setAuthToken } from '@/api/client';
import { useCartStore, useWishlistStore } from '@/store/cart';
import { registerPushToken, unregisterPushToken } from '@/utils/push';

type AuthState = {
  user: AuthUser | null;
  bootstrapped: boolean;
  bootstrap: () => Promise<void>;
  loginWithOtp: (phone: string, otp: string, name?: string) => Promise<void>;
  /** @deprecated */
  login: (email: string, password: string) => Promise<void>;
  register: (payload: {
    name: string;
    email: string;
    phone?: string;
    password: string;
    password_confirmation: string;
  }) => Promise<void>;
  logout: () => Promise<void>;
  setUser: (user: AuthUser | null) => void;
  refreshProfile: () => Promise<void>;
};

async function afterAuth() {
  await Promise.all([
    useCartStore.getState().hydrateFromServer(),
    useWishlistStore.getState().hydrateFromServer(),
    registerPushToken().catch(() => undefined),
  ]);
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      bootstrapped: false,
      bootstrap: async () => {
        const token = await hydrateAuthToken();
        if (!token) {
          set({ bootstrapped: true, user: null });
          return;
        }

        // Keep persisted user while refreshing — app close must not log out.
        const cached = get().user;
        if (cached) {
          set({ bootstrapped: true, user: cached });
        }

        try {
          const me = await authApi.me();
          set({ user: me.data, bootstrapped: true });
          await afterAuth();
        } catch (e) {
          const msg = e instanceof Error ? e.message.toLowerCase() : '';
          const unauthorized =
            msg.includes('unauthenticated') ||
            msg.includes('401') ||
            msg.includes('unauthorized') ||
            msg.includes('403');
          if (unauthorized) {
            await setAuthToken(null);
            set({ user: null, bootstrapped: true });
            return;
          }
          // Network / server blip: stay logged in with cached user + token
          if (!cached) {
            set({ bootstrapped: true });
          }
        }
      },
      loginWithOtp: async (phone, otp, name) => {
        const data = await authApi.verifyOtp(phone, otp, name);
        set({ user: data.user });
        await afterAuth();
      },
      login: async (email, password) => {
        const data = await authApi.login({ email, password });
        set({ user: data.user });
        await afterAuth();
      },
      register: async (payload) => {
        const data = await authApi.register(payload);
        set({ user: data.user });
        await afterAuth();
      },
      logout: async () => {
        await unregisterPushToken().catch(() => undefined);
        await authApi.logout();
        set({ user: null });
      },
      setUser: (user) => set({ user }),
      refreshProfile: async () => {
        const me = await authApi.me();
        set({ user: me.data });
      },
    }),
    {
      name: 'us-auth-user',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (s) => ({ user: s.user }),
    },
  ),
);

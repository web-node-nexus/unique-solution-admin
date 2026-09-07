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
    (set) => ({
      user: null,
      bootstrapped: false,
      bootstrap: async () => {
        const token = await hydrateAuthToken();
        if (!token) {
          set({ bootstrapped: true, user: null });
          return;
        }
        try {
          const me = await authApi.me();
          set({ user: me.data, bootstrapped: true });
          await afterAuth();
        } catch {
          await setAuthToken(null);
          set({ user: null, bootstrapped: true });
        }
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

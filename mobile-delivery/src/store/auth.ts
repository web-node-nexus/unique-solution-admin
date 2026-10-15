import { create } from 'zustand';
import { deliveryApi, type DeliveryUser } from '@/api/delivery';
import { hydrateAuthToken, setAuthToken } from '@/api/client';

type AuthState = {
  user: DeliveryUser | null;
  ready: boolean;
  bootstrap: () => Promise<void>;
  setSession: (token: string, user: DeliveryUser) => Promise<void>;
  logout: () => Promise<void>;
};

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  ready: false,
  bootstrap: async () => {
    const token = await hydrateAuthToken();
    if (!token) {
      set({ user: null, ready: true });
      return;
    }
    try {
      const res = await deliveryApi.me();
      set({ user: res.data, ready: true });
    } catch {
      await setAuthToken(null);
      set({ user: null, ready: true });
    }
  },
  setSession: async (token, user) => {
    await setAuthToken(token);
    set({ user });
  },
  logout: async () => {
    try {
      await deliveryApi.logout();
    } catch {
      // clear local session anyway
    }
    await setAuthToken(null);
    set({ user: null });
  },
}));

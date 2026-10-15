import { create } from 'zustand';
import { deliveryApi, type DeliveryUser } from '@/api/delivery';
import {
  ApiError,
  getCachedUser,
  hydrateAuthToken,
  setAuthToken,
  setCachedUser,
} from '@/api/client';

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

    const cached = await getCachedUser<DeliveryUser>();
    if (cached) {
      // Restore session immediately so app close/reopen keeps the user logged in.
      set({ user: cached, ready: true });
    }

    try {
      const res = await deliveryApi.me();
      await setCachedUser(res.data);
      set({ user: res.data, ready: true });
    } catch (e) {
      const status = e instanceof ApiError ? e.status : -1;
      // Only force logout when the server rejects the token — not on network blips.
      if (status === 401 || status === 403) {
        await setAuthToken(null);
        await setCachedUser(null);
        set({ user: null, ready: true });
        return;
      }
      if (!cached) {
        set({ user: null, ready: true });
      }
      // else keep cached user + token; ready already true
    }
  },
  setSession: async (token, user) => {
    await setAuthToken(token);
    await setCachedUser(user);
    set({ user });
  },
  logout: async () => {
    try {
      await deliveryApi.logout();
    } catch {
      // clear local session anyway
    }
    await setAuthToken(null);
    await setCachedUser(null);
    set({ user: null });
  },
}));

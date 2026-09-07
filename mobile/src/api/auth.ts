import { apiGet, apiPost, apiPut, setAuthToken } from './client';

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  address?: string | null;
};

export const authApi = {
  register: async (payload: {
    name: string;
    email: string;
    phone?: string;
    password: string;
    password_confirmation: string;
  }) => {
    const res = await apiPost<{ token: string; user: AuthUser }>('/auth/register', payload);
    await setAuthToken(res.data.token);
    return res.data;
  },
  login: async (payload: { email: string; password: string }) => {
    const res = await apiPost<{ token: string; user: AuthUser }>('/auth/login', payload);
    await setAuthToken(res.data.token);
    return res.data;
  },
  me: () => apiGet<AuthUser>('/auth/me'),
  updateProfile: (payload: {
    name?: string;
    phone?: string | null;
    address?: string | null;
  }) => apiPut<AuthUser>('/auth/profile', payload),
  changePassword: (payload: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }) => apiPut('/auth/password', payload),
  forgotPassword: (email: string) => apiPost('/auth/forgot-password', { email }),
  resetPassword: (payload: {
    email: string;
    code: string;
    password: string;
    password_confirmation: string;
  }) => apiPost('/auth/reset-password', payload),
  logout: async () => {
    try {
      await apiPost('/auth/logout');
    } finally {
      await setAuthToken(null);
    }
  },
};

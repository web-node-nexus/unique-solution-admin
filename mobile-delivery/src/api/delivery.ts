import { apiGet, apiPatch, apiPost } from './client';

export type DeliveryUser = {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
};

export type DeliveryOrder = {
  id: number;
  order_number: string;
  order_status: string;
  payment_status: string;
  payment_method?: string | null;
  total_amount: number;
  shipping_address: string;
  customer_name?: string | null;
  customer_phone?: string | null;
  items_count: number;
  created_at?: string | null;
  can_mark_shipped?: boolean;
  can_mark_delivered?: boolean;
  notes?: string | null;
  items?: {
    id: number;
    product_name: string;
    variant_label?: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
  }[];
};

export const deliveryApi = {
  sendOtp: (phone: string) => apiPost<{ phone: string; expires_in: number }>('/delivery/auth/send-otp', { phone }),
  verifyOtp: (phone: string, otp: string) =>
    apiPost<{ token: string; user: DeliveryUser }>('/delivery/auth/verify-otp', { phone, otp }),
  me: () => apiGet<DeliveryUser>('/delivery/auth/me'),
  logout: () => apiPost<null>('/delivery/auth/logout'),
  orders: (tab: 'pending' | 'delivered') => apiGet<DeliveryOrder[]>('/delivery/orders', { tab }),
  order: (id: number | string) => apiGet<DeliveryOrder>(`/delivery/orders/${id}`),
  updateStatus: (id: number | string, order_status: 'shipped' | 'delivered', remarks?: string) =>
    apiPatch<DeliveryOrder>(`/delivery/orders/${id}/status`, { order_status, remarks }),
};

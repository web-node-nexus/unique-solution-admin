import { apiGet, apiPatch, apiPost } from './client';

export type DeliveryUser = {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
};

export type DeliveryOrderItem = {
  id: number;
  product_name: string;
  brand_name?: string | null;
  sku?: string | null;
  variant_label?: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  image_url?: string | null;
  product_id?: number | null;
};

export type CustomerPastOrder = {
  id: number;
  order_number: string;
  order_status: string;
  payment_status?: string | null;
  total_amount: number;
  created_at?: string | null;
};

export type DeliveryOrder = {
  id: number;
  order_number: string;
  order_status: string;
  payment_status: string;
  payment_method?: string | null;
  subtotal?: number;
  discount?: number;
  tax?: number;
  shipping_charge?: number;
  total_amount: number;
  shipping_address: string;
  billing_address?: string | null;
  customer_name?: string | null;
  customer_phone?: string | null;
  customer_email?: string | null;
  items_count: number;
  thumbnail_url?: string | null;
  created_at?: string | null;
  can_mark_shipped?: boolean;
  can_mark_delivered?: boolean;
  needs_delivery_otp?: boolean;
  notes?: string | null;
  items?: DeliveryOrderItem[];
  customer_order_history?: CustomerPastOrder[];
  timeline?: { status: string; remarks?: string | null; at?: string | null }[];
};

export type OrdersMeta = {
  current_page?: number;
  last_page?: number;
  total?: number;
  staff_id?: number;
};

export const deliveryApi = {
  sendOtp: (phone: string) => apiPost<{ phone: string; expires_in: number }>('/delivery/auth/send-otp', { phone }),
  verifyOtp: (phone: string, otp: string) =>
    apiPost<{ token: string; user: DeliveryUser }>('/delivery/auth/verify-otp', { phone, otp }),
  me: () => apiGet<DeliveryUser>('/delivery/auth/me'),
  logout: () => apiPost<null>('/delivery/auth/logout'),
  orders: async (tab: 'pending' | 'delivered') => {
    const res = await apiGet<DeliveryOrder[] | { data?: DeliveryOrder[] }>('/delivery/orders', { tab });
    const raw = res.data;
    const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
    return { ...res, data: list, meta: (res.meta || {}) as OrdersMeta };
  },
  order: (id: number | string) => apiGet<DeliveryOrder>(`/delivery/orders/${id}`),
  markShipped: (id: number | string, remarks?: string) =>
    apiPatch<DeliveryOrder>(`/delivery/orders/${id}/status`, { order_status: 'shipped', remarks }),
  sendDeliveryOtp: (id: number | string) =>
    apiPost<{ phone_masked: string; expires_in: number }>(`/delivery/orders/${id}/send-delivery-otp`),
  confirmDelivery: (id: number | string, otp: string, remarks?: string) =>
    apiPost<DeliveryOrder>(`/delivery/orders/${id}/confirm-delivery`, { otp, remarks }),
  invoice: (id: number | string) =>
    apiGet<{ invoice_url: string; order_number: string }>(`/delivery/orders/${id}/invoice`),
};

import { apiDelete, apiGet, apiPost, apiPut } from './client';
import type { ProductCard } from '@/types/catalog';

export type Address = {
  id: number;
  label?: string | null;
  address: string;
  city?: string | null;
  state?: string | null;
  pincode?: string | null;
  is_default: boolean;
};

export type PaymentSession = {
  provider: 'razorpay';
  key: string;
  razorpay_order_id: string | null;
  amount: number;
  currency: string;
  payment_url: string;
  name: string;
  description: string;
  prefill?: { name?: string; email?: string; contact?: string | null };
};

export type OrderRefund = {
  id: number;
  reason?: string | null;
  refund_amount: number;
  status: string;
  admin_remarks?: string | null;
  created_at?: string | null;
};

export type OrderSummary = {
  id: number;
  order_number: string;
  total_amount: number;
  order_status: string;
  payment_status: string;
  created_at?: string;
  items_count?: number;
  subtotal?: number;
  discount?: number;
  tax?: number;
  shipping_charge?: number;
  shipping_address?: string;
  can_cancel?: boolean;
  can_reorder?: boolean;
  can_return?: boolean;
  can_invoice?: boolean;
  can_review?: boolean;
  refunds?: OrderRefund[];
  items?: {
    id: number;
    product_name: string;
    quantity: number;
    price: number;
    subtotal: number;
    product_id?: number;
    product_variant_id?: number;
    variant?: { sku?: string; attributes?: { name?: string; value?: string }[] };
  }[];
  timeline?: { status: string; remarks?: string; at?: string }[];
  payment?: {
    method: string;
    status: string;
    amount: number;
    needs_payment?: boolean;
    payment_url?: string | null;
  } | null;
  payment_session?: PaymentSession;
};

export type Dashboard = {
  user: { id: number; name: string; email: string; phone?: string | null };
  stats: {
    orders_count: number;
    pending_count: number;
    delivered_count: number;
    addresses_count: number;
  };
  recent_orders: OrderSummary[];
};

export type ServerCartItem = {
  product_id: number;
  product_variant_id?: number | null;
  quantity: number;
  name: string;
  image_url?: string | null;
  mrp: number;
  sale_price?: number | null;
  attribute_label?: string | null;
};

export const accountApi = {
  dashboard: () => apiGet<Dashboard>('/dashboard'),
  addresses: () => apiGet<Address[]>('/addresses'),
  createAddress: (body: Partial<Address> & { address: string }) => apiPost<Address>('/addresses', body),
  updateAddress: (id: number, body: Partial<Address>) => apiPut<Address>(`/addresses/${id}`, body),
  deleteAddress: (id: number) => apiDelete(`/addresses/${id}`),
  orders: () => apiGet<OrderSummary[]>('/orders'),
  order: (id: number | string) => apiGet<OrderSummary>(`/orders/${id}`),
  cancelOrder: (id: number | string, reason?: string) =>
    apiPost<OrderSummary>(`/orders/${id}/cancel`, { reason }),
  reorder: (id: number | string) => apiPost<ServerCartItem[]>(`/orders/${id}/reorder`),
  verifyPayment: (
    id: number | string,
    body: { razorpay_order_id: string; razorpay_payment_id: string; razorpay_signature: string },
  ) => apiPost<OrderSummary>(`/orders/${id}/verify-payment`, body),
  checkout: (body: {
    address_id?: number;
    shipping_address?: string;
    notes?: string;
    coupon_code?: string;
    payment_method: 'cod' | 'razorpay' | 'upi';
    items: { product_variant_id?: number | null; product_id: number; quantity: number }[];
  }) => apiPost<OrderSummary>('/checkout', body),
  wishlist: () => apiGet<ProductCard[]>('/wishlist'),
  addWishlist: (product_id: number) => apiPost<ProductCard[]>('/wishlist', { product_id }),
  removeWishlist: (product_id: number) => apiDelete<ProductCard[]>(`/wishlist/${product_id}`),
  syncWishlist: (product_ids: number[]) => apiPost<ProductCard[]>('/wishlist/sync', { product_ids }),
  cart: () => apiGet<ServerCartItem[]>('/cart'),
  syncCart: (items: ServerCartItem[]) => apiPut<ServerCartItem[]>('/cart', { items }),
  clearCart: () => apiDelete<ServerCartItem[]>('/cart'),
  registerDeviceToken: (body: { token: string; platform?: string; device_name?: string }) =>
    apiPost('/device-tokens', body),
  unregisterDeviceToken: (token: string) => apiDelete(`/device-tokens?token=${encodeURIComponent(token)}`),
  submitReview: (productId: number | string, body: { rating: number; comment?: string }) =>
    apiPost(`/products/${productId}/reviews`, body),
  requestReturn: (
    orderId: number | string,
    body: { reason: string; order_item_id?: number; refund_amount?: number },
  ) => apiPost(`/orders/${orderId}/return`, body),
  invoice: (orderId: number | string) =>
    apiGet<{ invoice_url: string; order_number?: string }>(`/orders/${orderId}/invoice`),
  stockAlert: (body: { product_id: number; product_variant_id?: number | null }) =>
    apiPost<{ id: number; product_id: number; product_variant_id?: number | null }>('/stock-alerts', body),
};

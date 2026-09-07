import { apiGet, apiPost } from './client';
import type {
  Brand,
  Category,
  FilterFacets,
  HomePayload,
  ProductCard,
  ProductDetail,
  ProductFilters,
  ProductReview,
} from '@/types/catalog';

export type ShopInfo = {
  shop_name: string;
  shop_tagline?: string | null;
  shop_address?: string | null;
  contact_number?: string | null;
  whatsapp_number?: string | null;
  contact_email?: string | null;
  currency_symbol?: string;
  tax_percentage?: number;
  default_shipping_charge?: number;
};

export type SaleItem = {
  id: number;
  title: string;
  subtitle?: string | null;
  description?: string | null;
  image_url?: string | null;
  link_type?: string | null;
  link_value?: string | null;
  ends_at?: string | null;
};

export type CouponItem = {
  id?: number;
  code: string;
  title?: string | null;
  description?: string | null;
  discount_type: string;
  discount_value: number;
  min_order_value?: number;
  image_url?: string | null;
  expiry_date?: string | null;
};

export type CategorySale = {
  category_id: number;
  category_name: string;
  category_slug?: string;
  title?: string | null;
  subtitle?: string | null;
  banner_url?: string | null;
};

export type AppNotification = {
  id: number;
  title: string;
  body: string;
  image_url?: string | null;
  sent_at?: string | null;
  type?: string | null;
  link_type?: string | null;
  link_value?: string | null;
};

export type SuggestPayload = {
  products: ProductCard[];
  categories: { id: number; name: string; slug: string; type?: string }[];
  brands: { id: number; name: string; type?: string }[];
};

export type PincodeCheck = {
  pincode: string;
  serviceable: boolean;
  shipping_charge?: number | null;
  eta_days?: number | null;
  message: string;
};

export type CouponPreview = {
  code: string;
  title?: string | null;
  discount_type: string;
  discount_value: number;
  discount_amount: number;
  subtotal: number;
  tax: number;
  shipping_charge: number;
  total: number;
};

export type ReviewsPayload = {
  summary: { average: number; count: number };
  reviews: ProductReview[];
};

export const catalogApi = {
  shop: () => apiGet<ShopInfo>('/shop'),
  home: () => apiGet<HomePayload>('/home'),
  categories: (params?: { parent_id?: number; roots_only?: boolean }) =>
    apiGet<Category[]>('/categories', {
      parent_id: params?.parent_id,
      roots_only: params?.roots_only ? 1 : undefined,
    }),
  brands: (categoryId?: number | null) =>
    apiGet<Brand[]>('/brands', { category_id: categoryId ?? undefined }),
  filters: (categoryId?: number | null) =>
    apiGet<FilterFacets>('/products/filters', {
      category_id: categoryId ?? undefined,
    }),
  products: (filters: Partial<ProductFilters> & { page?: number; per_page?: number }) =>
    apiGet<ProductCard[]>('/products', {
      q: filters.q,
      category_id: filters.category_id ?? undefined,
      brand_id: filters.brand_ids?.length ? filters.brand_ids.join(',') : undefined,
      attribute_value_ids: filters.attribute_value_ids?.length
        ? filters.attribute_value_ids.join(',')
        : undefined,
      min_price: filters.min_price ?? undefined,
      max_price: filters.max_price ?? undefined,
      sort: filters.sort ?? 'newest',
      featured: filters.featured ? 1 : undefined,
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 20,
    }),
  product: (id: number | string) => apiGet<ProductDetail>(`/products/${id}`),
  suggest: (q: string) => apiGet<SuggestPayload>('/products/suggest', { q }),
  productReviews: (id: number | string, page = 1) =>
    apiGet<ReviewsPayload>(`/products/${id}/reviews`, { page }),
  checkPincode: (pincode: string) =>
    apiPost<PincodeCheck>('/delivery/check', { pincode }),
  previewCoupon: (code: string, subtotal: number) =>
    apiPost<CouponPreview>('/coupons/preview', { code, subtotal }),
  sales: () => apiGet<SaleItem[]>('/sales'),
  coupons: () => apiGet<CouponItem[]>('/coupons'),
  categorySales: () => apiGet<CategorySale[]>('/category-sales'),
  notifications: () => apiGet<AppNotification[]>('/notifications'),
};

export type ProductCard = {
  id: number;
  name: string;
  slug: string;
  mrp: number;
  base_price: number;
  sale_price: number | null;
  brand: string | null;
  brand_id?: number | null;
  category?: string | null;
  category_id?: number | null;
  is_featured?: boolean;
  image_url: string | null;
  image_count?: number;
  gallery_preview?: string[];
  rating_average?: number;
  rating_count?: number;
  warranty_info?: string | null;
  brand_warranty?: string | null;
  highlight?: string | null;
};

export type ProductReview = {
  id: number;
  rating: number;
  comment?: string | null;
  admin_reply?: string | null;
  status?: string;
  user_name: string;
  created_at?: string | null;
};

export type ProductSpec = {
  name: string;
  value: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  image_url: string | null;
  sort_order: number;
  has_sale?: boolean;
  sale?: {
    active?: boolean;
    title?: string | null;
    subtitle?: string | null;
    banner_url?: string | null;
  } | null;
  children?: Category[];
};

export type Brand = {
  id: number;
  name: string;
  category_id: number | null;
  logo_url: string | null;
  warranty?: string | null;
};

export type AttributeValue = {
  id: number;
  value: string;
  hex?: string | null;
  image_url?: string | null;
};

export type FilterAttribute = {
  id: number;
  name: string;
  type: string;
  values: AttributeValue[];
};

export type FilterFacets = {
  scope?: {
    category_id: number;
    category_name: string;
    parent_id?: number | null;
  } | null;
  categories: { id: number; name: string; slug: string }[];
  subcategories?: { id: number; name: string; slug: string }[];
  brands: Brand[];
  attributes: FilterAttribute[];
  price: { min: number; max: number };
  sort_options: { value: string; label: string }[];
};

export type ProductFilters = {
  q?: string;
  category_id?: number | null;
  brand_ids: number[];
  attribute_value_ids: number[];
  min_price?: number | null;
  max_price?: number | null;
  sort: string;
  featured?: boolean;
};

export type Banner = {
  id: number;
  title: string | null;
  subtitle: string | null;
  image_url: string | null;
  link?: { type?: string; value?: string | null; label?: string | null } | null;
};

export type HomePayload = {
  shop: {
    shop_name: string;
    shop_tagline?: string;
    shop_address?: string;
    contact_number?: string | null;
    whatsapp_number?: string | null;
    contact_email?: string | null;
  };
  banners: Banner[];
  categories: Category[];
  brands?: Brand[];
  featured_products: ProductCard[];
  new_arrivals?: ProductCard[];
  sale_products?: ProductCard[];
  category_sales?: {
    category_id: number;
    category_name: string;
    category_slug?: string;
    title?: string | null;
    subtitle?: string | null;
    banner_url?: string | null;
  }[];
  sales?: {
    id: number;
    title: string;
    subtitle?: string | null;
    image_url?: string | null;
    link_type?: string | null;
    link_value?: string | null;
    ends_at?: string | null;
  }[];
  coupons?: {
    id?: number;
    code: string;
    title?: string;
    description?: string;
    image_url?: string | null;
    discount_type?: string;
    discount_value?: number;
  }[];
};

export type ProductDetail = Omit<ProductCard, 'brand' | 'category'> & {
  description?: string | null;
  warranty_info?: string | null;
  use_brand_policies?: boolean;
  policies?: {
    id: number;
    title: string;
    description?: string | null;
    icon_url?: string | null;
    source?: string;
  }[];
  images: { id: number; url: string; is_primary: boolean }[];
  brand: Brand | null;
  category: { id: number; name: string; slug: string } | null;
  variants: {
    id: number;
    sku: string;
    mrp: number;
    sale_price: number | null;
    stock_quantity: number;
    in_stock: boolean;
    attributes: {
      attribute_id: number;
      attribute_name: string | null;
      value_id: number;
      value: string;
      hex?: string | null;
      image_url?: string | null;
    }[];
    image_url: string | null;
  }[];
  rating_average?: number;
  rating_count?: number;
  specifications?: ProductSpec[];
  reviews?: ProductReview[];
  related_products?: ProductCard[];
};

import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import { accountApi } from '@/api/account';
import type { ProductCard } from '@/types/catalog';
import { sellingPrice } from '@/utils/price';

export type CartLine = {
  key: string;
  productId: number;
  variantId?: number | null;
  name: string;
  image_url: string | null;
  mrp: number;
  sale_price: number | null;
  qty: number;
  attributeLabel?: string;
};

type CartState = {
  lines: CartLine[];
  add: (line: Omit<CartLine, 'key' | 'qty'> & { qty?: number }) => void;
  setQty: (key: string, qty: number) => void;
  remove: (key: string) => void;
  clear: () => void;
  replace: (lines: CartLine[]) => void;
  count: () => number;
  subtotal: () => number;
  syncToServer: () => Promise<void>;
  hydrateFromServer: () => Promise<void>;
};

function toServerItems(lines: CartLine[]) {
  return lines.map((l) => ({
    product_id: l.productId,
    product_variant_id: l.variantId ?? null,
    quantity: l.qty,
    name: l.name,
    image_url: l.image_url,
    mrp: l.mrp,
    sale_price: l.sale_price,
    attribute_label: l.attributeLabel ?? null,
  }));
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      lines: [],
      add: (line) => {
        const key = `${line.productId}:${line.variantId ?? 'base'}`;
        const existing = get().lines.find((l) => l.key === key);
        if (existing) {
          set({
            lines: get().lines.map((l) =>
              l.key === key ? { ...l, qty: l.qty + (line.qty ?? 1) } : l,
            ),
          });
        } else {
          set({
            lines: [...get().lines, { ...line, key, qty: line.qty ?? 1 }],
          });
        }
        void get().syncToServer();
      },
      setQty: (key, qty) => {
        if (qty <= 0) {
          set({ lines: get().lines.filter((l) => l.key !== key) });
        } else {
          set({
            lines: get().lines.map((l) => (l.key === key ? { ...l, qty } : l)),
          });
        }
        void get().syncToServer();
      },
      remove: (key) => {
        set({ lines: get().lines.filter((l) => l.key !== key) });
        void get().syncToServer();
      },
      clear: () => {
        set({ lines: [] });
        void accountApi.clearCart().catch(() => undefined);
      },
      replace: (lines) => set({ lines }),
      count: () => get().lines.reduce((sum, l) => sum + l.qty, 0),
      subtotal: () =>
        get().lines.reduce((sum, l) => sum + sellingPrice(l.mrp, l.sale_price) * l.qty, 0),
      syncToServer: async () => {
        try {
          await accountApi.syncCart(toServerItems(get().lines));
        } catch {
          // Guest or offline — local cart remains.
        }
      },
      hydrateFromServer: async () => {
        try {
          const local = get().lines;
          if (local.length) {
            await accountApi.syncCart(toServerItems(local));
          }
          const res = await accountApi.cart();
          const lines: CartLine[] = (res.data ?? []).map((item) => ({
            key: `${item.product_id}:${item.product_variant_id ?? 'base'}`,
            productId: item.product_id,
            variantId: item.product_variant_id,
            name: item.name,
            image_url: item.image_url ?? null,
            mrp: item.mrp,
            sale_price: item.sale_price ?? null,
            qty: item.quantity,
            attributeLabel: item.attribute_label ?? undefined,
          }));
          set({ lines });
        } catch {
          // keep local
        }
      },
    }),
    {
      name: 'us-cart',
      storage: createJSONStorage(() => AsyncStorage),
    },
  ),
);

type WishlistState = {
  items: ProductCard[];
  toggle: (product: ProductCard) => void;
  has: (id: number) => boolean;
  remove: (id: number) => void;
  replace: (items: ProductCard[]) => void;
  syncToServer: () => Promise<void>;
  hydrateFromServer: () => Promise<void>;
};

export const useWishlistStore = create<WishlistState>()(
  persist(
    (set, get) => ({
      items: [],
      toggle: (product) => {
        const exists = get().items.some((p) => p.id === product.id);
        if (exists) {
          set({ items: get().items.filter((p) => p.id !== product.id) });
          void accountApi.removeWishlist(product.id).catch(() => undefined);
        } else {
          set({ items: [product, ...get().items] });
          void accountApi.addWishlist(product.id).catch(() => undefined);
        }
      },
      has: (id) => get().items.some((p) => p.id === id),
      remove: (id) => {
        set({ items: get().items.filter((p) => p.id !== id) });
        void accountApi.removeWishlist(id).catch(() => undefined);
      },
      replace: (items) => set({ items }),
      syncToServer: async () => {
        try {
          const ids = get().items.map((p) => p.id);
          if (!ids.length) return;
          const res = await accountApi.syncWishlist(ids);
          set({ items: res.data ?? [] });
        } catch {
          // guest / offline
        }
      },
      hydrateFromServer: async () => {
        try {
          await get().syncToServer();
          const res = await accountApi.wishlist();
          set({ items: res.data ?? [] });
        } catch {
          // keep local
        }
      },
    }),
    {
      name: 'us-wishlist',
      storage: createJSONStorage(() => AsyncStorage),
    },
  ),
);

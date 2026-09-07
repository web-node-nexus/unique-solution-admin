import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import type { ProductCard } from '@/types/catalog';

export const MAX_COMPARE = 3;

type CompareState = {
  items: ProductCard[];
  add: (product: ProductCard) => { ok: boolean; message?: string };
  remove: (id: number) => void;
  has: (id: number) => boolean;
  clear: () => void;
  toggle: (product: ProductCard) => { ok: boolean; message?: string };
};

export const useCompareStore = create<CompareState>()(
  persist(
    (set, get) => ({
      items: [],
      has: (id) => get().items.some((p) => p.id === id),
      add: (product) => {
        if (get().has(product.id)) return { ok: true };
        if (get().items.length >= MAX_COMPARE) {
          return {
            ok: false,
            message: `You can compare up to ${MAX_COMPARE} products at a time.`,
          };
        }
        set({ items: [...get().items, product] });
        return { ok: true };
      },
      remove: (id) => set({ items: get().items.filter((p) => p.id !== id) }),
      clear: () => set({ items: [] }),
      toggle: (product) => {
        if (get().has(product.id)) {
          get().remove(product.id);
          return { ok: true };
        }
        return get().add(product);
      },
    }),
    {
      name: 'us-compare',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({
        items: state.items.slice(0, MAX_COMPARE),
      }),
    },
  ),
);

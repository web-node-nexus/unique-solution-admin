import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import type { ProductCard } from '@/types/catalog';

type RecentState = {
  items: ProductCard[];
  push: (product: ProductCard) => void;
  clear: () => void;
};

export const useRecentStore = create<RecentState>()(
  persist(
    (set, get) => ({
      items: [],
      push: (product) => {
        const next = [product, ...get().items.filter((p) => p.id !== product.id)].slice(0, 12);
        set({ items: next });
      },
      clear: () => set({ items: [] }),
    }),
    {
      name: 'us-recent',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);

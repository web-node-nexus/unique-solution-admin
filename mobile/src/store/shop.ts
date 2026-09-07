import { create } from 'zustand';
import { catalogApi, type ShopInfo } from '@/api/catalog';

type ShopState = {
  shop: ShopInfo | null;
  loaded: boolean;
  load: () => Promise<void>;
};

export const useShopStore = create<ShopState>((set) => ({
  shop: null,
  loaded: false,
  load: async () => {
    try {
      const res = await catalogApi.shop();
      set({ shop: res.data, loaded: true });
    } catch {
      set({ loaded: true });
    }
  },
}));

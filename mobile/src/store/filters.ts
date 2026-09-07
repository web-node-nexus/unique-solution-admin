import { create } from 'zustand';
import type { ProductFilters } from '@/types/catalog';

const defaults: ProductFilters = {
  brand_ids: [],
  attribute_value_ids: [],
  sort: 'newest',
  category_id: null,
  min_price: null,
  max_price: null,
  q: '',
  featured: false,
};

type FilterState = {
  applied: ProductFilters;
  draft: ProductFilters;
  /** Category from the products route — Reset keeps this scope. */
  lockedCategoryId: number | null;
  lockedCategoryName: string | null;
  setDraft: (patch: Partial<ProductFilters>) => void;
  /** Change category and clear brand/attribute picks that belong to the old scope. */
  setCategory: (categoryId: number | null) => void;
  toggleBrand: (id: number) => void;
  toggleAttributeValue: (id: number) => void;
  apply: () => void;
  resetDraft: () => void;
  clearAll: () => void;
  hydrateFromRoute: (
    patch: Partial<ProductFilters> & { category_name?: string | null },
  ) => void;
  activeCount: () => number;
};

export const useFilterStore = create<FilterState>((set, get) => ({
  applied: { ...defaults },
  draft: { ...defaults },
  lockedCategoryId: null,
  lockedCategoryName: null,
  setDraft: (patch) => set({ draft: { ...get().draft, ...patch } }),
  setCategory: (categoryId) =>
    set({
      draft: {
        ...get().draft,
        category_id: categoryId,
        brand_ids: [],
        attribute_value_ids: [],
        min_price: null,
        max_price: null,
      },
    }),
  toggleBrand: (id) => {
    const ids = get().draft.brand_ids;
    set({
      draft: {
        ...get().draft,
        brand_ids: ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id],
      },
    });
  },
  toggleAttributeValue: (id) => {
    const ids = get().draft.attribute_value_ids;
    set({
      draft: {
        ...get().draft,
        attribute_value_ids: ids.includes(id)
          ? ids.filter((x) => x !== id)
          : [...ids, id],
      },
    });
  },
  apply: () => set({ applied: { ...get().draft } }),
  resetDraft: () => set({ draft: { ...get().applied } }),
  clearAll: () => {
    const lockedId = get().lockedCategoryId;
    const q = get().applied.q ?? '';
    const featured = get().applied.featured ?? false;
    const next: ProductFilters = {
      ...defaults,
      category_id: lockedId,
      q,
      featured,
    };
    set({ draft: next, applied: next });
  },
  hydrateFromRoute: (patch) => {
    const lockedId = patch.category_id ?? null;
    const next: ProductFilters = {
      ...defaults,
      ...patch,
      category_id: lockedId,
      brand_ids: patch.brand_ids ?? [],
      attribute_value_ids: patch.attribute_value_ids ?? [],
    };
    set({
      lockedCategoryId: lockedId,
      lockedCategoryName: patch.category_name?.trim() || null,
      draft: next,
      applied: next,
    });
  },
  activeCount: () => {
    const f = get().applied;
    const locked = get().lockedCategoryId;
    let n = 0;
    // Locked route category is context, not an extra filter.
    // Count category only when browsing all products, or when narrowed to a subcategory.
    if (f.category_id != null && f.category_id !== locked) n += 1;
    if (f.brand_ids.length) n += 1;
    if (f.attribute_value_ids.length) n += 1;
    if (f.min_price != null || f.max_price != null) n += 1;
    if (f.sort && f.sort !== 'newest') n += 1;
    if (f.featured) n += 1;
    if (f.q?.trim()) n += 1;
    return n;
  },
}));

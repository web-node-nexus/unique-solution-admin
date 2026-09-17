import AsyncStorage from '@react-native-async-storage/async-storage';
import { QueryClient, type Query } from '@tanstack/react-query';
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister';
import type { PersistQueryClientOptions } from '@tanstack/react-query-persist-client';

/** Catalog/home stay fresh in memory for 15 minutes. */
export const CATALOG_STALE_MS = 1000 * 60 * 15;
/** Keep unused cache up to 24 hours (survives navigation). */
export const CATALOG_GC_MS = 1000 * 60 * 60 * 24;

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: CATALOG_STALE_MS,
      gcTime: CATALOG_GC_MS,
      retry: 1,
      refetchOnWindowFocus: false,
      refetchOnReconnect: true,
      refetchOnMount: false,
    },
  },
});

export const queryPersister = createAsyncStoragePersister({
  storage: AsyncStorage,
  key: 'us-react-query-cache',
  throttleTime: 1500,
});

export const persistOptions: Omit<PersistQueryClientOptions, 'queryClient'> = {
  persister: queryPersister,
  maxAge: CATALOG_GC_MS,
  buster: 'v1',
  dehydrateOptions: {
    shouldDehydrateQuery: (query: Query) => {
      if (query.state.status !== 'success') return false;
      const key = String(query.queryKey[0] ?? '');
      // Don't persist auth-sensitive / volatile screens.
      if (key.startsWith('orders') || key === 'addresses' || key === 'notifications') {
        return false;
      }
      return true;
    },
  },
};

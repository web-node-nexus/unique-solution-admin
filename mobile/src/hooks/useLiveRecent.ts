import { useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import { catalogApi } from '@/api/catalog';
import { useRecentStore } from '@/store/recent';

/** Recently viewed cards, with deleted products removed as soon as the server says so. */
export function useLiveRecent() {
  const items = useRecentStore((s) => s.items);
  const keepOnly = useRecentStore((s) => s.keepOnly);
  const ids = items.map((p) => p.id);

  const { data } = useQuery({
    queryKey: ['products-visible', ids.join(',')],
    queryFn: async () => (await catalogApi.visibleIds(ids)).data,
    enabled: ids.length > 0,
    staleTime: 0,
    refetchOnMount: 'always',
  });

  useEffect(() => {
    if (!data) return;
    keepOnly(data);
  }, [data, keepOnly]);

  if (!data) return items;
  const alive = new Set(data);
  return items.filter((p) => alive.has(p.id));
}

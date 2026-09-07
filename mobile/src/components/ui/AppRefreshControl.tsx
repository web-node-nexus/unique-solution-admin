import { useCallback, useState } from 'react';
import { RefreshControl, type RefreshControlProps } from 'react-native';
import { colors } from '@/theme/tokens';

/** Shared pull-to-refresh styling used across the app. */
export function AppRefreshControl({
  tintColor = colors.jade,
  colors: androidColors = [colors.jade],
  progressBackgroundColor = colors.paper,
  ...props
}: RefreshControlProps) {
  return (
    <RefreshControl
      tintColor={tintColor}
      colors={androidColors}
      progressBackgroundColor={progressBackgroundColor}
      {...props}
    />
  );
}

/**
 * Local refreshing state for screens that refresh stores / multiple queries.
 * Prefer React Query `isRefetching` + `refetch` when a single query drives the page.
 */
export function usePullRefresh(refreshFn: () => Promise<unknown> | void) {
  const [refreshing, setRefreshing] = useState(false);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await refreshFn();
    } finally {
      setRefreshing(false);
    }
  }, [refreshFn]);

  return {
    refreshing,
    onRefresh,
    refreshControl: (
      <AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />
    ),
  };
}

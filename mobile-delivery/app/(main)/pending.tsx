import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { API_URL } from '@/api/client';
import { deliveryApi, type DeliveryOrder, type OrdersMeta } from '@/api/delivery';
import { OrderCard } from '@/components/OrderCard';
import { AppText, EmptyState } from '@/components/ui';
import { colors, spacing } from '@/theme/tokens';

export default function PendingOrdersScreen() {
  const [orders, setOrders] = useState<DeliveryOrder[]>([]);
  const [meta, setMeta] = useState<OrdersMeta>({});
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (soft = false) => {
    if (soft) setRefreshing(true);
    else setLoading(true);
    try {
      const res = await deliveryApi.orders('pending');
      setOrders(res.data);
      setMeta(res.meta || {});
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not load orders.');
      setOrders([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load])
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.sky} />
      </View>
    );
  }

  const emptyBody = error
    ? error
    : meta.assigned_total && meta.assigned_total > 0
      ? `Assigned ${meta.assigned_total} order(s) but none are open. Status: ${JSON.stringify(meta.by_status || {})}`
      : `No open orders for staff #${meta.staff_id ?? '—'}. Pull to refresh after admin assigns you.`;

  return (
    <FlatList
      data={orders}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
      ListHeaderComponent={
        <View style={{ marginBottom: 12, gap: 4 }}>
          <AppText style={styles.hint}>Orders assigned to you that still need delivery.</AppText>
          <AppText style={styles.api}>{API_URL}</AppText>
        </View>
      }
      ListEmptyComponent={
        <EmptyState
          title={error ? 'Could not load' : 'No pending orders'}
          body={emptyBody}
        />
      }
      renderItem={({ item }) => <OrderCard order={item} />}
      ItemSeparatorComponent={() => <View style={{ height: 12 }} />}
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  list: { padding: spacing.md, paddingBottom: 40, flexGrow: 1, gap: 0 },
  hint: { color: colors.inkMuted, lineHeight: 20 },
  api: { color: colors.inkMuted, fontSize: 11 },
});

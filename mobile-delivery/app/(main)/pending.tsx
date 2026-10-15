import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { deliveryApi, type DeliveryOrder } from '@/api/delivery';
import { OrderCard } from '@/components/OrderCard';
import { AppText, EmptyState } from '@/components/ui';
import { colors, spacing } from '@/theme/tokens';

export default function PendingOrdersScreen() {
  const [orders, setOrders] = useState<DeliveryOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (soft = false) => {
    if (soft) setRefreshing(true);
    else setLoading(true);
    try {
      const res = await deliveryApi.orders('pending');
      setOrders(res.data);
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not load orders.');
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

  return (
    <FlatList
      data={orders}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
      ListHeaderComponent={
        <AppText style={styles.hint}>Orders assigned to you that still need delivery.</AppText>
      }
      ListEmptyComponent={
        <EmptyState
          title={error ? 'Could not load' : 'No pending orders'}
          body={error || 'When admin assigns an order to you, it will show here.'}
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
  hint: { color: colors.inkMuted, marginBottom: 12, lineHeight: 20 },
});

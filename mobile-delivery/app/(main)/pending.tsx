import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { useFocusEffect } from 'expo-router';
import { PackageSearch } from 'lucide-react-native';
import { deliveryApi, type DeliveryOrder } from '@/api/delivery';
import { OrderCard } from '@/components/OrderCard';
import { AppText, EmptyState } from '@/components/ui';
import { colors, radii, spacing } from '@/theme/tokens';

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
        <ActivityIndicator color={colors.sky} size="large" />
      </View>
    );
  }

  return (
    <FlatList
      data={orders}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.sky} />}
      ListHeaderComponent={
        <View style={styles.banner}>
          <PackageSearch size={18} color={colors.skyDeep} strokeWidth={2.2} />
          <AppText style={styles.bannerText}>
            {orders.length} open deliver{orders.length === 1 ? 'y' : 'ies'} waiting for you
          </AppText>
        </View>
      }
      ListEmptyComponent={
        <EmptyState
          title={error ? 'Could not load' : 'All clear'}
          body={error || 'When admin assigns an order to you, it will show up here with product photos and address.'}
        />
      }
      renderItem={({ item }) => <OrderCard order={item} />}
      ItemSeparatorComponent={() => <View style={{ height: 12 }} />}
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.canvas },
  list: { padding: spacing.md, paddingBottom: 40, flexGrow: 1, backgroundColor: colors.canvas },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.skySoft,
    borderRadius: radii.lg,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  bannerText: { flex: 1, color: colors.skyDeep, fontWeight: '700', fontSize: 13 },
});

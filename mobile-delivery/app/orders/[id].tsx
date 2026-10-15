import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { deliveryApi, type DeliveryOrder } from '@/api/delivery';
import { AppText, PrimaryButton, PressableScale } from '@/components/ui';
import { colors, radii, spacing } from '@/theme/tokens';

function formatInr(n: number) {
  return `₹${Math.round(n).toLocaleString('en-IN')}`;
}

export default function DeliveryOrderDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const [order, setOrder] = useState<DeliveryOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    if (!id) return;
    setLoading(true);
    try {
      const res = await deliveryApi.order(id);
      setOrder(res.data);
    } catch (e) {
      Alert.alert('Error', e instanceof Error ? e.message : 'Could not load order.', [
        { text: 'OK', onPress: () => router.back() },
      ]);
    } finally {
      setLoading(false);
    }
  }, [id, router]);

  useEffect(() => {
    void load();
  }, [load]);

  const mark = async (status: 'shipped' | 'delivered') => {
    if (!order) return;
    setBusy(true);
    try {
      const res = await deliveryApi.updateStatus(order.id, status);
      setOrder(res.data);
      Alert.alert('Updated', res.message || 'Order updated.');
      if (status === 'delivered') router.replace('/(main)/delivered');
    } catch (e) {
      Alert.alert('Could not update', e instanceof Error ? e.message : 'Try again.');
    } finally {
      setBusy(false);
    }
  };

  if (loading || !order) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.sky} />
      </View>
    );
  }

  return (
    <ScrollView
      contentContainerStyle={[styles.wrap, { paddingBottom: Math.max(insets.bottom, 16) + 24 }]}
    >
      <View style={styles.card}>
        <AppText style={styles.number}>{order.order_number}</AppText>
        <AppText style={styles.status}>{order.order_status.toUpperCase()}</AppText>
        <AppText style={styles.amount}>{formatInr(order.total_amount)}</AppText>
        <AppText style={styles.meta}>
          Payment: {(order.payment_method || order.payment_status || '—').toUpperCase()}
        </AppText>
      </View>

      <View style={styles.card}>
        <AppText style={styles.heading}>Customer</AppText>
        <AppText style={styles.value}>{order.customer_name || '—'}</AppText>
        {order.customer_phone ? (
          <PressableScale onPress={() => Linking.openURL(`tel:${order.customer_phone}`)}>
            <AppText style={styles.link}>{order.customer_phone}</AppText>
          </PressableScale>
        ) : null}
      </View>

      <View style={styles.card}>
        <AppText style={styles.heading}>Delivery address</AppText>
        <AppText style={styles.address}>{order.shipping_address || 'No address'}</AppText>
      </View>

      {order.notes ? (
        <View style={styles.card}>
          <AppText style={styles.heading}>Notes</AppText>
          <AppText style={styles.address}>{order.notes}</AppText>
        </View>
      ) : null}

      <View style={styles.card}>
        <AppText style={styles.heading}>Items</AppText>
        {(order.items ?? []).map((item) => (
          <View key={item.id} style={styles.itemRow}>
            <View style={{ flex: 1 }}>
              <AppText style={styles.itemName}>{item.product_name}</AppText>
              {item.variant_label ? <AppText style={styles.meta}>{item.variant_label}</AppText> : null}
            </View>
            <AppText style={styles.meta}>×{item.quantity}</AppText>
          </View>
        ))}
      </View>

      <View style={styles.actions}>
        {order.can_mark_shipped ? (
          <PrimaryButton
            label={busy ? 'Updating…' : 'Out for delivery'}
            tone="amber"
            disabled={busy}
            onPress={() => mark('shipped')}
          />
        ) : null}
        {order.can_mark_delivered ? (
          <PrimaryButton
            label={busy ? 'Updating…' : 'Mark delivered'}
            tone="jade"
            disabled={busy}
            onPress={() =>
              Alert.alert('Mark delivered?', 'Confirm this order was handed to the customer.', [
                { text: 'Cancel', style: 'cancel' },
                { text: 'Delivered', onPress: () => void mark('delivered') },
              ])
            }
          />
        ) : null}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  wrap: { padding: spacing.md, gap: 12 },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
    gap: 6,
  },
  number: { fontSize: 18, fontWeight: '800' },
  status: { color: colors.sky, fontWeight: '700', fontSize: 13 },
  amount: { fontSize: 22, fontWeight: '800', marginTop: 4 },
  heading: { fontSize: 13, fontWeight: '700', color: colors.inkMuted, textTransform: 'uppercase' },
  value: { fontSize: 16, fontWeight: '700' },
  address: { fontSize: 15, lineHeight: 22, color: colors.ink },
  meta: { color: colors.inkMuted, fontSize: 13 },
  link: { color: colors.sky, fontWeight: '700', fontSize: 16, marginTop: 4 },
  itemRow: {
    flexDirection: 'row',
    gap: 12,
    paddingVertical: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  itemName: { fontWeight: '600' },
  actions: { gap: 10, marginTop: 8 },
});

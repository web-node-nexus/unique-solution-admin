import { StyleSheet, View } from 'react-native';
import { useRouter } from 'expo-router';
import type { DeliveryOrder } from '@/api/delivery';
import { AppText, PressableScale } from '@/components/ui';
import { colors, radii, spacing } from '@/theme/tokens';

function formatInr(n: number) {
  return `₹${Math.round(n).toLocaleString('en-IN')}`;
}

function statusLabel(status: string) {
  if (status === 'processing') return 'Pending pickup';
  if (status === 'shipped') return 'Out for delivery';
  if (status === 'delivered') return 'Delivered';
  return status;
}

export function OrderCard({ order }: { order: DeliveryOrder }) {
  const router = useRouter();
  const soft =
    order.order_status === 'delivered'
      ? colors.jadeSoft
      : order.order_status === 'shipped'
        ? colors.amberSoft
        : colors.skySoft;
  const tone =
    order.order_status === 'delivered'
      ? colors.jade
      : order.order_status === 'shipped'
        ? colors.amber
        : colors.sky;

  return (
    <PressableScale style={styles.card} onPress={() => router.push(`/orders/${order.id}`)}>
      <View style={styles.row}>
        <AppText style={styles.number}>{order.order_number}</AppText>
        <View style={[styles.pill, { backgroundColor: soft }]}>
          <AppText style={[styles.pillText, { color: tone }]}>{statusLabel(order.order_status)}</AppText>
        </View>
      </View>
      <AppText style={styles.name}>{order.customer_name || 'Customer'}</AppText>
      <AppText style={styles.address} numberOfLines={3}>
        {order.shipping_address || 'No address'}
      </AppText>
      <View style={styles.footer}>
        <AppText style={styles.meta}>{order.items_count} item(s)</AppText>
        <AppText style={styles.amount}>{formatInr(order.total_amount)}</AppText>
      </View>
    </PressableScale>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
    gap: 8,
  },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  number: { fontWeight: '700', fontSize: 15, flexShrink: 1 },
  pill: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999 },
  pillText: { fontSize: 12, fontWeight: '700' },
  name: { fontSize: 15, fontWeight: '600' },
  address: { fontSize: 14, color: colors.inkMuted, lineHeight: 20 },
  footer: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  meta: { color: colors.inkMuted, fontSize: 13 },
  amount: { fontWeight: '700', color: colors.sky },
});

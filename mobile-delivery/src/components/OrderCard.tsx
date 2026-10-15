import { Image } from 'expo-image';
import { useRouter } from 'expo-router';
import { ChevronRight, MapPin, Package } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import type { DeliveryOrder } from '@/api/delivery';
import { AppText, PressableScale } from '@/components/ui';
import { colors, radii, spacing } from '@/theme/tokens';

function formatInr(n: number) {
  return `₹${Math.round(n).toLocaleString('en-IN')}`;
}

function statusMeta(status: string) {
  const s = status.toLowerCase();
  if (s === 'delivered') return { label: 'Delivered', bg: colors.jadeSoft, fg: colors.jade };
  if (s === 'shipped') return { label: 'Out for delivery', bg: colors.amberSoft, fg: colors.amber };
  if (s === 'processing') return { label: 'Ready to go', bg: colors.skySoft, fg: colors.sky };
  return { label: status, bg: colors.violetSoft, fg: colors.violet };
}

export function OrderCard({ order }: { order: DeliveryOrder }) {
  const router = useRouter();
  const meta = statusMeta(order.order_status);

  return (
    <PressableScale style={styles.card} onPress={() => router.push(`/orders/${order.id}`)}>
      <View style={styles.top}>
        <View style={styles.thumbWrap}>
          {order.thumbnail_url ? (
            <Image source={{ uri: order.thumbnail_url }} style={styles.thumb} contentFit="cover" />
          ) : (
            <View style={styles.thumbFallback}>
              <Package size={22} color={colors.sky} strokeWidth={2.1} />
            </View>
          )}
        </View>
        <View style={styles.copy}>
          <View style={styles.row}>
            <AppText style={styles.number} numberOfLines={1}>
              {order.order_number}
            </AppText>
            <View style={[styles.pill, { backgroundColor: meta.bg }]}>
              <AppText style={[styles.pillText, { color: meta.fg }]}>{meta.label}</AppText>
            </View>
          </View>
          <AppText style={styles.name} numberOfLines={1}>
            {order.customer_name || 'Customer'}
          </AppText>
          <View style={styles.addrRow}>
            <MapPin size={14} color={colors.inkMuted} strokeWidth={2.2} />
            <AppText style={styles.address} numberOfLines={2}>
              {order.shipping_address || 'No address'}
            </AppText>
          </View>
        </View>
      </View>
      <View style={styles.footer}>
        <AppText style={styles.meta}>{order.items_count} item(s)</AppText>
        <View style={styles.amountRow}>
          <AppText style={styles.amount}>{formatInr(order.total_amount)}</AppText>
          <ChevronRight size={18} color={colors.sky} strokeWidth={2.4} />
        </View>
      </View>
    </PressableScale>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.xl,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 12,
    shadowColor: '#0B1220',
    shadowOpacity: 0.06,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 4 },
    elevation: 2,
  },
  top: { flexDirection: 'row', gap: 12 },
  thumbWrap: {
    width: 72,
    height: 72,
    borderRadius: 16,
    overflow: 'hidden',
    backgroundColor: colors.canvasDeep,
  },
  thumb: { width: '100%', height: '100%' },
  thumbFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.skySoft,
  },
  copy: { flex: 1, minWidth: 0, gap: 4 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  number: { fontWeight: '800', fontSize: 14, flexShrink: 1, color: colors.ink },
  pill: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: radii.pill },
  pillText: { fontSize: 11, fontWeight: '800' },
  name: { fontSize: 16, fontWeight: '700', color: colors.ink },
  addrRow: { flexDirection: 'row', gap: 4, alignItems: 'flex-start', marginTop: 2 },
  address: { flex: 1, fontSize: 13, color: colors.inkMuted, lineHeight: 18 },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
    paddingTop: 10,
  },
  meta: { color: colors.inkMuted, fontSize: 13, fontWeight: '600' },
  amountRow: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  amount: { fontWeight: '800', color: colors.sky, fontSize: 16 },
});

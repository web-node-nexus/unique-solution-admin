import { Image } from 'expo-image';
import { router } from 'expo-router';
import { Package } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, radii, typography } from '@/theme/tokens';
import { formatInr } from '@/utils/price';

export type OrderLine = {
  id: number;
  product_name: string;
  quantity: number;
  subtotal?: number;
  product_id?: number | null;
  image_url?: string | null;
  variant_label?: string | null;
};

export function OrderProductRow({
  item,
  showPrice = false,
}: {
  item: OrderLine;
  showPrice?: boolean;
}) {
  const openProduct = () => {
    if (item.product_id) router.push(`/products/${item.product_id}`);
  };

  return (
    <View style={styles.row}>
      <PressableScale onPress={openProduct} disabled={!item.product_id} style={styles.thumbHit}>
        {item.image_url ? (
          <Image source={{ uri: item.image_url }} style={styles.thumb} contentFit="cover" transition={250} />
        ) : (
          <View style={[styles.thumb, styles.fallback]}>
            <Package size={18} color={colors.jade} strokeWidth={1.8} />
          </View>
        )}
      </PressableScale>
      <PressableScale
        onPress={openProduct}
        disabled={!item.product_id}
        style={styles.copy}
      >
        <AppText style={styles.name} numberOfLines={2}>
          {item.product_name}
        </AppText>
        {item.variant_label ? (
          <AppText variant="caption" numberOfLines={1}>
            {item.variant_label}
          </AppText>
        ) : null}
        <AppText variant="caption">Qty {item.quantity}</AppText>
      </PressableScale>
      {showPrice ? (
        <AppText style={styles.price} numberOfLines={1}>
          {formatInr(item.subtotal ?? 0)}
        </AppText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  thumbHit: { borderRadius: radii.md },
  thumb: {
    width: 56,
    height: 56,
    borderRadius: radii.md,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
  },
  fallback: { alignItems: 'center', justifyContent: 'center', backgroundColor: colors.jadeSoft },
  copy: { flex: 1, minWidth: 0, gap: 2 },
  name: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 14 },
  price: { fontFamily: typography.bodyBold, color: colors.ink, maxWidth: 96, textAlign: 'right' },
});

import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react-native';
import { FlatList, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, Layout } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { AppRefreshControl, usePullRefresh } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useCartStore } from '@/store/cart';
import { useShopStore } from '@/store/shop';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr, sellingPrice } from '@/utils/price';

export default function CartScreen() {
  const insets = useSafeAreaInsets();
  const { lines, setQty, remove, subtotal, clear, hydrateFromServer } = useCartStore();
  const loadShop = useShopStore((s) => s.load);
  const shop = useShopStore((s) => s.shop);
  const cartSubtotal = subtotal();
  const shipping = Number(shop?.default_shipping_charge ?? 0);
  const taxPct = Number(shop?.tax_percentage ?? 0);
  const tax = Math.round(((cartSubtotal * taxPct) / 100) * 100) / 100;
  const total = Math.round((cartSubtotal + tax + shipping) * 100) / 100;
  const itemCount = lines.reduce((s, l) => s + l.qty, 0);
  const footerPad = Math.max(insets.bottom, 10) + 78;
  const { refreshing, onRefresh } = usePullRefresh(async () => {
    await Promise.all([hydrateFromServer(), loadShop()]);
  });

  return (
    <ScreenAtmosphere style={{ paddingTop: insets.top + 8 }}>
      <ScreenHeader
        showMenu
        eyebrow="Your bag"
        title="Cart"
        subtitle={lines.length ? `${itemCount} item${itemCount === 1 ? '' : 's'}` : undefined}
      />

      <FlatList
        data={lines}
        keyExtractor={(item) => item.key}
        contentContainerStyle={{ padding: spacing.md, gap: 10, paddingBottom: 220 + footerPad }}
        refreshControl={
          <AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <View style={styles.emptyIcon}>
              <ShoppingBag size={28} color={colors.inkSoft} strokeWidth={1.6} />
            </View>
            <AppText style={styles.emptyTitle}>Bag is empty</AppText>
            <AppText variant="caption" style={{ textAlign: 'center' }}>
              Add products from the showroom — they’ll show up here ready for checkout.
            </AppText>
            <AppButton label="Browse products" onPress={() => router.push('/products')} />
          </View>
        }
        renderItem={({ item, index }) => {
          const unit = sellingPrice(item.mrp, item.sale_price);
          const lineTotal = unit * item.qty;
          return (
            <Animated.View
              entering={FadeInDown.delay(Math.min(index, 6) * 35).springify()}
              layout={Layout.springify()}
              style={[styles.row, elevation.soft]}
            >
              <PressableScale onPress={() => router.push(`/products/${item.productId}`)}>
                {item.image_url ? (
                  <Image
                    source={{ uri: item.image_url }}
                    style={styles.thumb}
                    contentFit="cover"
                    transition={250}
                  />
                ) : (
                  <View style={[styles.thumb, { backgroundColor: colors.canvasDeep }]} />
                )}
              </PressableScale>
              <View style={styles.rowCopy}>
                <AppText style={styles.name} numberOfLines={2}>
                  {item.name}
                </AppText>
                {item.attributeLabel ? (
                  <AppText style={styles.attr} numberOfLines={1}>
                    {item.attributeLabel}
                  </AppText>
                ) : null}
                <View style={styles.priceBlock}>
                  <AppText style={styles.price}>{formatInr(unit)}</AppText>
                  {item.sale_price ? (
                    <AppText style={styles.mrp}>{formatInr(item.mrp)}</AppText>
                  ) : null}
                </View>
                <View style={styles.qtyRow}>
                  <View style={styles.qtyControl}>
                    <PressableScale
                      style={styles.qtyBtn}
                      onPress={() => {
                        void Haptics.selectionAsync();
                        setQty(item.key, item.qty - 1);
                      }}
                    >
                      <Minus size={14} color={colors.ink} />
                    </PressableScale>
                    <AppText style={styles.qty}>{item.qty}</AppText>
                    <PressableScale
                      style={styles.qtyBtn}
                      onPress={() => {
                        void Haptics.selectionAsync();
                        setQty(item.key, item.qty + 1);
                      }}
                    >
                      <Plus size={14} color={colors.ink} />
                    </PressableScale>
                  </View>
                  <AppText style={styles.lineTotal}>{formatInr(lineTotal)}</AppText>
                  <PressableScale
                    style={styles.trash}
                    onPress={() => {
                      void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
                      remove(item.key);
                    }}
                  >
                    <Trash2 size={15} color={colors.danger} />
                  </PressableScale>
                </View>
              </View>
            </Animated.View>
          );
        }}
      />

      {lines.length > 0 ? (
        <View style={[styles.footer, { paddingBottom: footerPad }]}>
          <View style={styles.summary}>
            <View style={styles.sumLine}>
              <AppText style={styles.sumLabel}>Subtotal</AppText>
              <AppText style={styles.sumValue}>{formatInr(cartSubtotal)}</AppText>
            </View>
            {tax > 0 ? (
              <View style={styles.sumLine}>
                <AppText style={styles.sumLabel}>Tax ({taxPct}%)</AppText>
                <AppText style={styles.sumValue}>{formatInr(tax)}</AppText>
              </View>
            ) : null}
            <View style={styles.sumLine}>
              <AppText style={styles.sumLabel}>Shipping</AppText>
              <AppText style={styles.sumValue}>
                {shipping > 0 ? formatInr(shipping) : 'Free / at checkout'}
              </AppText>
            </View>
            <View style={[styles.sumLine, styles.totalLine]}>
              <AppText style={styles.totalLabel}>Estimated total</AppText>
              <AppText style={styles.total}>{formatInr(total)}</AppText>
            </View>
          </View>
          <AppButton label="Proceed to checkout" onPress={() => router.push('/checkout')} />
          <AppButton label="Clear bag" variant="ghost" onPress={clear} />
        </View>
      ) : null}
    </ScreenAtmosphere>
  );
}

const styles = StyleSheet.create({
  empty: { alignItems: 'center', gap: 12, marginTop: 56, paddingHorizontal: spacing.lg },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: radii.md,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    ...elevation.soft,
  },
  emptyTitle: {
    fontFamily: typography.displayBold,
    fontSize: 22,
    color: colors.ink,
  },
  row: {
    flexDirection: 'row',
    gap: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
  },
  thumb: { width: 92, height: 92, borderRadius: radii.md, backgroundColor: colors.canvasDeep },
  rowCopy: { flex: 1, minWidth: 0, gap: 4 },
  name: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 14, lineHeight: 18 },
  attr: { fontFamily: typography.body, fontSize: 12, color: colors.inkMuted },
  priceBlock: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  price: { fontFamily: typography.bodyBold, color: colors.ink, fontSize: 15 },
  mrp: {
    fontFamily: typography.body,
    fontSize: 12,
    color: colors.inkSoft,
    textDecorationLine: 'line-through',
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 6,
  },
  qtyControl: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.canvas,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 2,
  },
  qtyBtn: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qty: {
    fontFamily: typography.bodySemi,
    minWidth: 22,
    textAlign: 'center',
    fontSize: 14,
  },
  lineTotal: {
    marginLeft: 'auto',
    fontFamily: typography.bodyBold,
    fontSize: 14,
    color: colors.jade,
  },
  trash: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: colors.paper,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.borderStrong,
    paddingHorizontal: spacing.md,
    paddingTop: 12,
    gap: 8,
    ...elevation.lift,
  },
  summary: {
    backgroundColor: colors.canvas,
    borderRadius: radii.md,
    padding: 12,
    gap: 6,
    marginBottom: 4,
  },
  sumLine: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  sumLabel: { fontFamily: typography.body, fontSize: 13, color: colors.inkMuted },
  sumValue: { fontFamily: typography.bodyMedium, fontSize: 13, color: colors.ink },
  totalLine: {
    marginTop: 4,
    paddingTop: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  totalLabel: { fontFamily: typography.bodySemi, fontSize: 14, color: colors.ink },
  total: { fontFamily: typography.displayBold, fontSize: 22, color: colors.ink },
});

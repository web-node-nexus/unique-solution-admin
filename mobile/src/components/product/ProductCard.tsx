import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { GitCompare, Heart, Images, Package, ShieldCheck, Star } from 'lucide-react-native';
import { Alert, StyleSheet, View } from 'react-native';
import { router } from 'expo-router';
import Animated, { FadeInDown } from 'react-native-reanimated';
import type { ProductCard as ProductCardType } from '@/types/catalog';
import { colors, elevation, radii, typography } from '@/theme/tokens';
import { discountPercent, formatInr, sellingPrice } from '@/utils/price';
import { useWishlistStore } from '@/store/cart';
import { useCompareStore } from '@/store/compare';
import { AppText, PressableScale } from '@/components/ui/primitives';

export function ProductCard({
  product,
  compact = false,
  index = 0,
}: {
  product: ProductCardType;
  compact?: boolean;
  index?: number;
}) {
  const wish = useWishlistStore();
  const compare = useCompareStore();
  const liked = wish.has(product.id);
  const inCompare = compare.has(product.id);
  const price = sellingPrice(product.mrp ?? product.base_price, product.sale_price);
  const mrp = product.mrp ?? product.base_price;
  const off = discountPercent(mrp, product.sale_price);
  const ratingCount = product.rating_count ?? 0;
  const ratingAvg = product.rating_average ?? 0;
  const imageCount = product.image_count ?? product.gallery_preview?.length ?? 0;
  const highlight =
    product.highlight ||
    product.warranty_info ||
    product.brand_warranty ||
    product.brand ||
    'Genuine stock';

  return (
    <Animated.View
      entering={FadeInDown.delay(Math.min(index, 8) * 30).springify().damping(18)}
      style={styles.wrap}
    >
      <PressableScale
        style={[styles.card, elevation.soft]}
        onPress={() => {
          void Haptics.selectionAsync();
          router.push(`/products/${product.id}`);
        }}
      >
        <View style={[styles.imageWrap, compact && styles.imageWrapCompact]}>
          {product.image_url ? (
            <Image
              source={{ uri: product.image_url }}
              style={styles.image}
              contentFit="cover"
              transition={280}
            />
          ) : (
            <View style={[styles.image, styles.placeholder]}>
              <Package size={22} color={colors.inkSoft} strokeWidth={1.5} />
            </View>
          )}
          <PressableScale
            style={styles.compare}
            onPress={() => {
              void Haptics.selectionAsync();
              const result = compare.toggle(product);
              if (!result.ok && result.message) Alert.alert('Compare', result.message);
            }}
          >
            <GitCompare
              size={13}
              color={inCompare ? colors.jade : colors.inkMuted}
              strokeWidth={2.2}
            />
          </PressableScale>
          <PressableScale
            style={styles.heart}
            onPress={() => {
              void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
              wish.toggle(product);
            }}
          >
            <Heart
              size={13}
              color={liked ? colors.danger : colors.inkMuted}
              fill={liked ? colors.danger : 'transparent'}
              strokeWidth={2}
            />
          </PressableScale>
          {off ? (
            <View style={styles.badge}>
              <AppText style={styles.badgeText}>-{off}%</AppText>
            </View>
          ) : product.is_featured ? (
            <View style={[styles.badge, styles.badgeNew]}>
              <AppText style={styles.badgeNewText}>NEW</AppText>
            </View>
          ) : null}
          {imageCount > 1 ? (
            <View style={styles.multiBadge}>
              <Images size={10} color={colors.paper} strokeWidth={2} />
              <AppText style={styles.multiText}>{imageCount}</AppText>
            </View>
          ) : null}
        </View>

        <View style={[styles.body, compact && styles.bodyCompact]}>
          <AppText numberOfLines={2} style={styles.name}>
            {product.name}
          </AppText>

          <View style={styles.ratingRow}>
            {[1, 2, 3, 4, 5].map((n) => (
              <Star
                key={n}
                size={9}
                color={ratingCount > 0 && n <= Math.round(ratingAvg) ? colors.brass : colors.inkSoft}
                fill={ratingCount > 0 && n <= Math.round(ratingAvg) ? colors.brass : 'transparent'}
                strokeWidth={1.4}
              />
            ))}
            <AppText style={styles.ratingText}>
              {ratingCount > 0 ? `(${ratingCount})` : 'New'}
            </AppText>
          </View>

          <View style={styles.warrantyRow}>
            <ShieldCheck size={11} color={colors.jade} strokeWidth={2.2} />
            <AppText numberOfLines={1} style={styles.warranty}>
              {highlight}
            </AppText>
          </View>

          <View style={styles.priceRow}>
            <AppText numberOfLines={1} style={styles.price}>
              {formatInr(price)}
            </AppText>
            {product.sale_price ? (
              <AppText numberOfLines={1} style={styles.mrp}>
                {formatInr(mrp)}
              </AppText>
            ) : null}
          </View>
        </View>
      </PressableScale>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  wrap: { flex: 1, minWidth: 0 },
  card: {
    flex: 1,
    minWidth: 0,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.border,
  },
  imageWrap: {
    height: 132,
    backgroundColor: colors.canvasDeep,
  },
  imageWrapCompact: { height: 112 },
  image: { width: '100%', height: '100%' },
  placeholder: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.mist,
  },
  compare: {
    position: 'absolute',
    top: 6,
    right: 38,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.95)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  heart: {
    position: 'absolute',
    top: 6,
    right: 6,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.95)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  badge: {
    position: 'absolute',
    left: 6,
    top: 6,
    backgroundColor: colors.offer,
    paddingHorizontal: 7,
    paddingVertical: 3,
    borderRadius: radii.pill,
  },
  badgeNew: { backgroundColor: colors.accent },
  badgeText: {
    color: colors.paper,
    fontFamily: typography.bodyBold,
    fontSize: 10,
  },
  badgeNewText: {
    color: colors.paper,
    fontFamily: typography.bodyBold,
    fontSize: 10,
  },
  multiBadge: {
    position: 'absolute',
    right: 6,
    bottom: 6,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    backgroundColor: 'rgba(15,23,42,0.65)',
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: radii.pill,
  },
  multiText: {
    color: colors.paper,
    fontFamily: typography.bodyMedium,
    fontSize: 10,
  },
  body: {
    paddingHorizontal: 10,
    paddingTop: 8,
    paddingBottom: 10,
    gap: 4,
    minHeight: 108,
  },
  bodyCompact: { minHeight: 100 },
  name: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    lineHeight: 17,
    color: colors.ink,
    height: 34,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 1,
    height: 14,
  },
  ratingText: {
    marginLeft: 4,
    fontFamily: typography.body,
    fontSize: 10,
    color: colors.inkMuted,
  },
  warrantyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    height: 16,
  },
  warranty: {
    flex: 1,
    fontFamily: typography.body,
    fontSize: 10,
    color: colors.jade,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
    height: 18,
  },
  price: { fontFamily: typography.bodyBold, fontSize: 14, color: colors.ink },
  mrp: {
    fontFamily: typography.body,
    fontSize: 11,
    color: colors.inkSoft,
    textDecorationLine: 'line-through',
  },
});

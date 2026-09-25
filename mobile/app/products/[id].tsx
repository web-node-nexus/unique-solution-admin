import { useMutation, useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import {
  ArrowLeft,
  Bell,
  Heart,
  Package,
  Search,
  Share2,
  Star,
} from 'lucide-react-native';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  Share,
  StyleSheet,
  TextInput,
  useWindowDimensions,
  View,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
} from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { accountApi } from '@/api/account';
import { catalogApi } from '@/api/catalog';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { ProductCard } from '@/components/product/ProductCard';
import { ProductImageGallery } from '@/components/product/ProductImageGallery';
import { HtmlContent } from '@/components/product/HtmlContent';
import { OptionDropdown } from '@/components/product/OptionDropdown';
import { TrustHighlights } from '@/components/product/TrustHighlights';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, IconButton, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { useCartStore, useWishlistStore } from '@/store/cart';
import { useRecentStore } from '@/store/recent';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import type { ProductDetail } from '@/types/catalog';
import { track } from '@/utils/analytics';
import { discountPercent, formatInr, sellingPrice } from '@/utils/price';

const ACCENT = '#2C64E3';
const BUY_GREEN = '#1A9E4D';

type VariantAttr = ProductDetail['variants'][number]['attributes'][number];
type ProductVariant = ProductDetail['variants'][number];

function isColorAttr(a: VariantAttr) {
  const name = (a.attribute_name ?? '').toLowerCase();
  return name.includes('color') || name.includes('colour') || !!a.hex;
}

function colorOf(v: ProductVariant) {
  return v.attributes.find((a) => isColorAttr(a)) ?? null;
}

function variantSignature(v: ProductVariant) {
  const parts = v.attributes
    .filter((a) => !isColorAttr(a))
    .map((a) => a.value)
    .filter(Boolean);
  return parts.length ? parts.join(' / ') : v.sku;
}

function StarsRow({
  value,
  onChange,
  size = 18,
}: {
  value: number;
  onChange?: (n: number) => void;
  size?: number;
}) {
  return (
    <View style={{ flexDirection: 'row', gap: 4 }}>
      {[1, 2, 3, 4, 5].map((n) => (
        <PressableScale key={n} onPress={onChange ? () => onChange(n) : undefined} disabled={!onChange}>
          <Star
            size={size}
            color={n <= value ? BUY_GREEN : colors.inkSoft}
            fill={n <= value ? BUY_GREEN : 'transparent'}
            strokeWidth={1.8}
          />
        </PressableScale>
      ))}
    </View>
  );
}

export default function ProductDetailScreen() {
  const params = useLocalSearchParams<{ id?: string | string[] }>();
  const id = Array.isArray(params.id) ? params.id[0] : params.id;
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const add = useCartStore((s) => s.add);
  const wish = useWishlistStore();
  const user = useAuthStore((s) => s.user);
  const pushRecent = useRecentStore((s) => s.push);
  const scrollRef = useRef<ScrollView>(null);
  const aboutRef = useRef<View>(null);
  const scrollYRef = useRef(0);
  const [variantId, setVariantId] = useState<number | null>(null);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [reviewPending, setReviewPending] = useState(false);

  const { data, isLoading, isError, error, refetch, isRefetching } = useQuery({
    queryKey: ['product', id],
    queryFn: async () => (await catalogApi.product(id)).data,
    enabled: !!id,
  });

  useEffect(() => {
    if (!data) return;
    pushRecent({
      id: data.id,
      name: data.name,
      slug: data.slug,
      mrp: data.mrp,
      base_price: data.base_price,
      sale_price: data.sale_price,
      brand: data.brand?.name ?? null,
      image_url: data.image_url,
      rating_average: data.rating_average,
      rating_count: data.rating_count,
    });
    void track('product_view', { product_id: data.id, name: data.name }, `/products/${data.id}`);
  }, [data, pushRecent]);

  const selected = useMemo(() => {
    if (!data?.variants?.length) return null;
    return data.variants.find((v) => v.id === variantId) ?? data.variants[0];
  }, [data, variantId]);

  const colorOptions = useMemo(() => {
    if (!data?.variants?.length) return [];
    const map = new Map<string, { key: string; label: string; hex?: string | null }>();
    for (const v of data.variants) {
      const c = colorOf(v);
      if (!c) continue;
      const key = String(c.value_id);
      if (!map.has(key)) {
        map.set(key, { key, label: c.value, hex: c.hex });
      }
    }
    return Array.from(map.values());
  }, [data]);

  const variantOptions = useMemo(() => {
    if (!data?.variants?.length) return [];
    const selectedColorId = colorOf(selected)?.value_id ?? null;
    const pool =
      selectedColorId != null
        ? data.variants.filter((v) => colorOf(v)?.value_id === selectedColorId)
        : data.variants;

    const map = new Map<string, { key: string; label: string; variantId: number; inStock: boolean }>();
    for (const v of pool) {
      const label = variantSignature(v);
      const key = label;
      const existing = map.get(key);
      if (!existing) {
        map.set(key, {
          key,
          label,
          variantId: v.id,
          inStock: v.in_stock,
        });
      } else if (v.in_stock && !existing.inStock) {
        map.set(key, { ...existing, variantId: v.id, inStock: true });
      }
    }

    if (!map.size) {
      for (const v of data.variants) {
        const label = variantSignature(v);
        if (!map.has(label)) {
          map.set(label, {
            key: label,
            label,
            variantId: v.id,
            inStock: v.in_stock,
          });
        }
      }
    }

    return Array.from(map.values());
  }, [data, selected]);

  const pickVariant = (next: { colorValueId?: number | null; signature?: string | null }) => {
    if (!data?.variants?.length) return;
    const colorValueId =
      next.colorValueId !== undefined
        ? next.colorValueId
        : colorOf(selected)?.value_id ?? null;
    const signature =
      next.signature !== undefined ? next.signature : selected ? variantSignature(selected) : null;

    const match =
      data.variants.find((v) => {
        const c = colorOf(v);
        const colorOk = colorValueId == null || c?.value_id === colorValueId;
        const sigOk = !signature || variantSignature(v) === signature;
        return colorOk && sigOk;
      }) ??
      data.variants.find((v) => {
        const c = colorOf(v);
        return colorValueId == null || c?.value_id === colorValueId;
      }) ??
      data.variants[0];

    setVariantId(match.id);
  };

  const gallery = useMemo(() => {
    if (!data) return [];
    const base = data.images?.length
      ? data.images.map((img) => ({ id: img.id, url: img.url }))
      : data.image_url
        ? [{ id: 0, url: data.image_url }]
        : [];

    const variantUrl = selected?.image_url;
    if (variantUrl) {
      const rest = base.filter((img) => img.url !== variantUrl);
      return [{ id: `variant-${selected!.id}`, url: variantUrl }, ...rest];
    }
    return base;
  }, [data, selected]);

  const outOfStock = useMemo(() => {
    if (!data?.variants?.length) return false;
    if (selected) return !selected.in_stock;
    return data.variants.every((v) => !v.in_stock);
  }, [data, selected]);

  const submitReview = useMutation({
    mutationFn: () =>
      accountApi.submitReview(data!.id, {
        rating: reviewRating,
        comment: reviewComment.trim() || undefined,
      }),
    onSuccess: () => {
      setReviewPending(true);
      setReviewComment('');
      Alert.alert('Thanks!', 'Your review was submitted and will appear after approval.');
    },
    onError: (e: Error) => Alert.alert('Could not submit', e.message),
  });

  const notifyStock = useMutation({
    mutationFn: () =>
      accountApi.stockAlert({
        product_id: data!.id,
        product_variant_id: selected?.id ?? null,
      }),
    onSuccess: () => Alert.alert('Got it', 'We will notify you when this is back in stock.'),
    onError: (e: Error) => Alert.alert('Could not set alert', e.message),
  });

  const addToCart = (goCheckout: boolean) => {
    if (!data || outOfStock) return;
    const mrpVal = selected?.mrp ?? data.mrp;
    const saleVal = selected?.sale_price ?? data.sale_price;
    const priceVal = sellingPrice(mrpVal, saleVal);
    add({
      productId: data.id,
      variantId: selected?.id,
      name: data.name,
      image_url: selected?.image_url ?? data.image_url,
      mrp: mrpVal,
      sale_price: saleVal,
      attributeLabel: selected?.attributes.map((a) => a.value).join(' / '),
    });
    void track(goCheckout ? 'buy_now' : 'add_to_cart', {
      product_id: data.id,
      variant_id: selected?.id,
      price: priceVal,
    });
    router.push(goCheckout ? '/checkout' : '/(main)/(tabs)/cart');
  };

  if (!id) {
    return (
      <ScreenAtmosphere style={{ paddingTop: insets.top + 24, paddingHorizontal: spacing.lg }}>
        <IconButton onPress={() => router.back()}>
          <ArrowLeft size={20} color={colors.ink} />
        </IconButton>
        <AppText style={{ marginTop: 28, fontFamily: typography.bodySemi, fontSize: 18 }}>
          Product could not open
        </AppText>
        <AppText variant="caption" style={{ marginTop: 8 }}>
          Missing product id.
        </AppText>
      </ScreenAtmosphere>
    );
  }

  if (isError) {
    return (
      <ScreenAtmosphere style={{ paddingTop: insets.top + 24, paddingHorizontal: spacing.lg }}>
        <IconButton onPress={() => router.back()}>
          <ArrowLeft size={20} color={colors.ink} />
        </IconButton>
        <AppText style={{ marginTop: 28, fontFamily: typography.bodySemi, fontSize: 18 }}>
          Product could not open
        </AppText>
        <AppText variant="caption" style={{ marginTop: 8 }}>
          {error instanceof Error ? error.message : 'This product is not available right now.'}
        </AppText>
        <View style={{ marginTop: 16, alignSelf: 'flex-start' }}>
          <AppButton label="Retry" onPress={() => void refetch()} />
        </View>
      </ScreenAtmosphere>
    );
  }

  if (isLoading || !data) {
    return (
      <ScreenAtmosphere style={{ paddingTop: insets.top + 40, alignItems: 'center' }}>
        <ActivityIndicator color={ACCENT} />
      </ScreenAtmosphere>
    );
  }

  const mrp = selected?.mrp ?? data.mrp;
  const sale = selected?.sale_price ?? data.sale_price;
  const price = sellingPrice(mrp, sale);
  const off = discountPercent(mrp, sale);
  const liked = wish.has(data.id);
  const ratingAvg = data.rating_average ?? 0;
  const ratingCount = data.rating_count ?? 0;
  const specs = data.specifications ?? [];
  const reviews = data.reviews ?? [];
  const related = data.related_products ?? [];
  const galleryH = Math.min(width * 0.92, 360);
  const displayFont = typography.displayBold ?? typography.display;

  return (
    <View style={[styles.screen, { paddingTop: insets.top }]}>
      <ScrollView
        ref={scrollRef}
        contentContainerStyle={{ paddingBottom: 110 + Math.max(insets.bottom, 8) }}
        showsVerticalScrollIndicator={false}
        scrollEventThrottle={16}
        onScroll={(e: NativeSyntheticEvent<NativeScrollEvent>) => {
          scrollYRef.current = e.nativeEvent.contentOffset.y;
        }}
        refreshControl={
          <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
        }
      >
        <View>
          <ProductImageGallery
            key={selected?.id ?? 'base'}
            images={gallery}
            width={width}
            height={galleryH}
            fallback={
              <View style={[styles.imageFallback, { width, height: galleryH }]}>
                <Package size={40} color={colors.inkSoft} strokeWidth={1.5} />
              </View>
            }
          />
          <IconButton
            style={[styles.floatBtn, { top: 10, left: 14 }]}
            onPress={() => router.back()}
          >
            <ArrowLeft size={20} color={colors.ink} strokeWidth={2.2} />
          </IconButton>
          <IconButton
            style={[styles.floatBtn, { top: 10, right: 14 }]}
            onPress={() => router.push('/search')}
          >
            <Search size={18} color={colors.ink} strokeWidth={2.1} />
          </IconButton>
        </View>

        <Animated.View entering={FadeInDown.springify().damping(18)} style={styles.body}>
          <View style={styles.actionRow}>
            <PressableScale
              style={styles.iconOutline}
              onPress={() => {
                wish.toggle({
                  id: data.id,
                  name: data.name,
                  slug: data.slug,
                  mrp: data.mrp,
                  base_price: data.base_price,
                  sale_price: data.sale_price,
                  brand: data.brand?.name ?? null,
                  image_url: data.image_url,
                  rating_average: data.rating_average,
                  rating_count: data.rating_count,
                });
                void track(liked ? 'wishlist_remove' : 'wishlist_add', { product_id: data.id });
              }}
            >
              <Heart
                size={18}
                color={colors.danger}
                fill={liked ? colors.danger : 'transparent'}
                strokeWidth={2.1}
              />
            </PressableScale>
            <PressableScale
              style={styles.iconOutline}
              onPress={() => {
                void track('product_share', { product_id: data.id });
                Share.share({
                  message: `Check out ${data.name} on Unique Solution\nuniquesolution://products/${data.id}`,
                  url: `uniquesolution://products/${data.id}`,
                  title: data.name,
                }).catch(() => undefined);
              }}
            >
              <Share2 size={17} color={ACCENT} strokeWidth={2.1} />
            </PressableScale>
          </View>

          <View style={styles.titlePriceRow}>
            <View style={styles.titleCol}>
              {data.brand?.name ? (
                <View style={styles.brandBadge}>
                  <AppText style={styles.brandBadgeText} numberOfLines={1}>
                    {data.brand.name}
                  </AppText>
                </View>
              ) : null}
              <AppText style={[styles.productName, { fontFamily: displayFont }]} numberOfLines={3}>
                {data.name}
              </AppText>
              {ratingCount > 0 ? (
                <View style={styles.ratingNearTitle}>
                  <Star size={14} color={BUY_GREEN} fill={BUY_GREEN} strokeWidth={0} />
                  <AppText style={styles.ratingMeta}>
                    {ratingAvg.toFixed(1)} ({ratingCount.toLocaleString('en-IN')} Reviews)
                  </AppText>
                </View>
              ) : null}
            </View>

            <View style={styles.priceCol}>
              <AppText style={[styles.price, { fontFamily: displayFont }]} numberOfLines={1}>
                {formatInr(price)}
              </AppText>
              {sale ? (
                <AppText style={styles.mrp} numberOfLines={1}>
                  {formatInr(mrp)}
                </AppText>
              ) : null}
              {off ? (
                <View style={styles.off}>
                  <AppText style={styles.offText}>{off}% OFF</AppText>
                </View>
              ) : null}
            </View>
          </View>

          {data.variants?.length ? (
            <View style={styles.selectors}>
              {colorOptions.length ? (
                <OptionDropdown
                  label="Color"
                  valueLabel={colorOf(selected)?.value ?? null}
                  valueKey={colorOf(selected) ? String(colorOf(selected)!.value_id) : null}
                  valueHex={colorOf(selected)?.hex}
                  options={colorOptions}
                  placeholder="Choose color"
                  onSelect={(key) => pickVariant({ colorValueId: Number(key) })}
                />
              ) : null}
              <OptionDropdown
                label="Variant"
                valueLabel={selected ? variantSignature(selected) : null}
                valueKey={selected ? variantSignature(selected) : null}
                options={variantOptions.map((o) => ({
                  key: o.key,
                  label: o.inStock ? o.label : `${o.label} · Out of stock`,
                }))}
                placeholder="Choose variant"
                onSelect={(key) => pickVariant({ signature: key })}
              />
            </View>
          ) : null}

          {outOfStock ? (
            <View style={{ marginTop: 12, gap: 8 }}>
              <AppText style={{ color: colors.danger }}>Out of stock</AppText>
              <AppButton
                label={notifyStock.isPending ? 'Saving…' : 'Notify me'}
                variant="ghost"
                icon={<Bell size={16} color={ACCENT} strokeWidth={2} />}
                onPress={() => {
                  if (!user) {
                    Alert.alert('Sign in required', 'Sign in to get stock alerts.', [
                      { text: 'Cancel', style: 'cancel' },
                      { text: 'Sign in', onPress: () => router.push('/auth/login') },
                    ]);
                    return;
                  }
                  notifyStock.mutate();
                }}
              />
            </View>
          ) : null}

          <TrustHighlights policies={data.policies ?? []} />

          {data.description ? (
            <View ref={aboutRef} collapsable={false} style={styles.section}>
              <AppText style={styles.descHeading}>Description</AppText>
              <HtmlContent
                html={data.description}
                collapsible
                collapsedInches={3}
                onCollapse={() => {
                  aboutRef.current?.measureInWindow((_x, aboutY) => {
                    scrollRef.current?.measureInWindow((_sx, scrollViewY) => {
                      const target = Math.max(
                        0,
                        scrollYRef.current + (aboutY - scrollViewY) - 16
                      );
                      scrollRef.current?.scrollTo({ y: target, animated: true });
                    });
                  });
                }}
              />
            </View>
          ) : null}

          {specs.length ? (
            <View style={styles.section}>
              <AppText style={styles.descHeading}>Specifications</AppText>
              <View style={styles.specTable}>
                {specs.map((spec) => (
                  <View key={spec.name} style={styles.specRow}>
                    <AppText style={styles.specName} numberOfLines={2}>
                      {spec.name}
                    </AppText>
                    <AppText style={styles.specValue}>{spec.value}</AppText>
                  </View>
                ))}
              </View>
            </View>
          ) : null}

          <View style={styles.section}>
            <AppText style={styles.descHeading}>Reviews</AppText>
            {reviews.length ? (
              <View style={{ gap: 12, marginTop: 10 }}>
                {reviews.map((r) => (
                  <View key={r.id} style={styles.reviewCard}>
                    <View style={styles.reviewHead}>
                      <AppText style={styles.itemName}>{r.user_name}</AppText>
                      <StarsRow value={r.rating} size={12} />
                    </View>
                    {r.comment ? (
                      <AppText variant="body" style={{ color: colors.inkMuted, marginTop: 6 }}>
                        {r.comment}
                      </AppText>
                    ) : null}
                    {r.admin_reply ? (
                      <AppText variant="caption" style={{ marginTop: 6, color: ACCENT }}>
                        Shop reply: {r.admin_reply}
                      </AppText>
                    ) : null}
                  </View>
                ))}
              </View>
            ) : (
              <AppText variant="caption" style={{ marginTop: 8 }}>
                No reviews yet.
              </AppText>
            )}

            {user ? (
              <View style={styles.writeBox}>
                <AppText style={styles.itemName}>Write a review</AppText>
                {reviewPending ? (
                  <AppText variant="caption" style={{ marginTop: 8, color: BUY_GREEN }}>
                    Your review is pending approval.
                  </AppText>
                ) : (
                  <>
                    <View style={{ marginTop: 10 }}>
                      <StarsRow value={reviewRating} onChange={setReviewRating} size={22} />
                    </View>
                    <TextInput
                      value={reviewComment}
                      onChangeText={setReviewComment}
                      placeholder="Share your experience (optional)"
                      placeholderTextColor={colors.inkSoft}
                      multiline
                      style={styles.reviewInput}
                    />
                    <AppButton
                      label={submitReview.isPending ? 'Submitting…' : 'Submit review'}
                      variant="ghost"
                      onPress={() => submitReview.mutate()}
                    />
                  </>
                )}
              </View>
            ) : (
              <AppButton
                label="Sign in to review"
                variant="ghost"
                onPress={() => router.push('/auth/login')}
              />
            )}
          </View>

          {related.length ? (
            <View style={styles.section}>
              <AppText style={styles.descHeading}>Related products</AppText>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={{ gap: 12, paddingTop: 12, paddingRight: 8 }}
              >
                {related.map((p) => (
                  <View key={p.id} style={{ width: 168 }}>
                    <ProductCard product={p} />
                  </View>
                ))}
              </ScrollView>
            </View>
          ) : null}
        </Animated.View>
      </ScrollView>

      <View style={[styles.bar, { paddingBottom: Math.max(insets.bottom, 10) }]}>
        <View style={styles.barPrice}>
          <AppText style={[styles.barAmount, { fontFamily: displayFont }]} numberOfLines={1}>
            {formatInr(price)}
          </AppText>
          {sale || off ? (
            <AppText style={styles.barMeta} numberOfLines={1}>
              {sale ? formatInr(mrp) : ''}
              {off ? ` · ${off}% OFF` : ''}
            </AppText>
          ) : null}
        </View>
        <PressableScale
          disabled={outOfStock}
          onPress={() => addToCart(false)}
          style={[styles.ctaCart, outOfStock && styles.ctaDisabled]}
        >
          <AppText style={styles.ctaCartText}>Add to Cart</AppText>
        </PressableScale>
        <PressableScale
          disabled={outOfStock}
          onPress={() => addToCart(true)}
          style={[styles.ctaBuy, outOfStock && styles.ctaDisabled]}
        >
          <AppText style={styles.ctaBuyText}>Buy Now</AppText>
        </PressableScale>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#FFFFFF' },
  imageFallback: {
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  floatBtn: {
    position: 'absolute',
    backgroundColor: 'rgba(255,255,255,0.92)',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    ...elevation.soft,
  },
  body: { paddingHorizontal: 16, paddingTop: 4, paddingBottom: 8 },
  actionRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 12,
  },
  iconOutline: {
    width: 40,
    height: 40,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
  titlePriceRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  titleCol: { flex: 1, minWidth: 0 },
  brandBadge: {
    alignSelf: 'flex-start',
    backgroundColor: ACCENT,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 4,
    marginBottom: 6,
  },
  brandBadgeText: {
    color: '#FFFFFF',
    fontFamily: typography.bodySemi,
    fontSize: 11,
    textTransform: 'lowercase',
  },
  productName: {
    fontSize: 24,
    lineHeight: 30,
    color: colors.ink,
  },
  ratingNearTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: 8,
  },
  ratingMeta: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: BUY_GREEN,
  },
  priceCol: {
    alignItems: 'flex-end',
    paddingTop: 2,
    minWidth: 96,
  },
  price: {
    fontSize: 22,
    color: colors.ink,
  },
  mrp: {
    marginTop: 2,
    fontFamily: typography.body,
    fontSize: 13,
    color: colors.inkSoft,
    textDecorationLine: 'line-through',
  },
  off: {
    marginTop: 6,
    backgroundColor: '#E8F8EF',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  offText: {
    color: BUY_GREEN,
    fontFamily: typography.bodySemi,
    fontSize: 11,
  },
  selectors: {
    marginTop: 18,
    flexDirection: 'row',
    gap: 12,
  },
  section: { marginTop: 22 },
  descHeading: {
    fontFamily: typography.bodyBold,
    fontSize: 20,
    color: ACCENT,
    marginBottom: 8,
  },
  specTable: {
    marginTop: 4,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    overflow: 'hidden',
  },
  specRow: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: '#E5E7EB',
  },
  specName: {
    width: '38%',
    fontFamily: typography.bodyMedium,
    color: colors.inkMuted,
    fontSize: 13,
  },
  specValue: {
    flex: 1,
    fontFamily: typography.bodySemi,
    color: colors.ink,
    fontSize: 13,
  },
  reviewCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  reviewHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  writeBox: {
    marginTop: 14,
    backgroundColor: '#F8FAFC',
    borderRadius: radii.lg,
    padding: spacing.md,
    gap: 4,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  reviewInput: {
    marginTop: 10,
    marginBottom: 8,
    minHeight: 80,
    textAlignVertical: 'top',
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    padding: 12,
    fontFamily: typography.body,
    color: colors.ink,
    fontSize: 14,
  },
  itemName: { fontFamily: typography.bodySemi, color: colors.ink },
  bar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingTop: 10,
    paddingHorizontal: 12,
    backgroundColor: '#FFFFFF',
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: '#E5E7EB',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    ...elevation.lift,
  },
  barPrice: { minWidth: 78, marginRight: 2 },
  barAmount: {
    fontSize: 18,
    color: colors.ink,
  },
  barMeta: {
    marginTop: 1,
    fontFamily: typography.body,
    fontSize: 10,
    color: colors.inkSoft,
  },
  ctaCart: {
    flex: 1,
    backgroundColor: ACCENT,
    borderRadius: 10,
    paddingVertical: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ctaCartText: {
    color: '#FFFFFF',
    fontFamily: typography.bodySemi,
    fontSize: 14,
  },
  ctaBuy: {
    flex: 1,
    backgroundColor: BUY_GREEN,
    borderRadius: 10,
    paddingVertical: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ctaBuyText: {
    color: '#FFFFFF',
    fontFamily: typography.bodySemi,
    fontSize: 14,
  },
  ctaDisabled: { opacity: 0.45 },
});

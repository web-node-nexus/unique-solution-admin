import { useMutation, useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import {
  ArrowLeft,
  Bell,
  GitCompare,
  Heart,
  MapPin,
  Package,
  Share2,
  ShoppingBag,
  ShieldCheck,
  Star,
} from 'lucide-react-native';
import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  Share,
  StyleSheet,
  TextInput,
  useWindowDimensions,
  View,
} from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { accountApi } from '@/api/account';
import { catalogApi } from '@/api/catalog';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { ProductCard } from '@/components/product/ProductCard';
import { ProductImageGallery } from '@/components/product/ProductImageGallery';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, Chip, IconButton, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { useCartStore, useWishlistStore } from '@/store/cart';
import { useCompareStore, MAX_COMPARE } from '@/store/compare';
import { useRecentStore } from '@/store/recent';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import type { ProductCard as ProductCardType } from '@/types/catalog';
import { track } from '@/utils/analytics';
import { discountPercent, formatInr, sellingPrice } from '@/utils/price';

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
            color={n <= value ? colors.brassDeep : colors.inkSoft}
            fill={n <= value ? colors.brass : 'transparent'}
            strokeWidth={1.8}
          />
        </PressableScale>
      ))}
    </View>
  );
}

export default function ProductDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const add = useCartStore((s) => s.add);
  const wish = useWishlistStore();
  const compare = useCompareStore();
  const user = useAuthStore((s) => s.user);
  const pushRecent = useRecentStore((s) => s.push);
  const [variantId, setVariantId] = useState<number | null>(null);
  const [pincode, setPincode] = useState('');
  const [pinMsg, setPinMsg] = useState<string | null>(null);
  const [pinOk, setPinOk] = useState<boolean | null>(null);
  const [pinBusy, setPinBusy] = useState(false);
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

  const asCard = useMemo((): ProductCardType | null => {
    if (!data) return null;
    return {
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
    };
  }, [data]);

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

  if (isError || (!isLoading && !data)) {
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
        <ActivityIndicator color={colors.jade} />
      </ScreenAtmosphere>
    );
  }

  const mrp = selected?.mrp ?? data.mrp;
  const sale = selected?.sale_price ?? data.sale_price;
  const price = sellingPrice(mrp, sale);
  const off = discountPercent(mrp, sale);
  const liked = wish.has(data.id);
  const inCompare = compare.has(data.id);
  const attrLabel = selected?.attributes.map((a) => a.value).join(' / ');
  const brandWarranty = data.brand?.warranty?.trim() || null;
  const productWarranty = data.warranty_info?.trim() || null;
  const ratingAvg = data.rating_average ?? 0;
  const ratingCount = data.rating_count ?? 0;
  const specs = data.specifications ?? [];
  const reviews = data.reviews ?? [];
  const related = data.related_products ?? [];

  const checkDelivery = async () => {
    const pin = pincode.trim();
    if (pin.length < 4) {
      Alert.alert('Enter pincode', 'Please enter a valid pincode.');
      return;
    }
    setPinBusy(true);
    try {
      const res = await catalogApi.checkPincode(pin);
      setPinOk(res.data.serviceable);
      setPinMsg(res.data.message);
    } catch (e) {
      setPinOk(false);
      setPinMsg(e instanceof Error ? e.message : 'Could not check delivery');
    } finally {
      setPinBusy(false);
    }
  };

  return (
    <ScreenAtmosphere style={{ paddingTop: insets.top }}>
      <ScrollView
        contentContainerStyle={{ paddingBottom: 120 }}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
        }
      >
        <View>
          <ProductImageGallery
            key={selected?.id ?? 'base'}
            images={gallery}
            width={width}
            height={width * 0.95}
            fallback={
              <View style={[styles.imageFallback, { width, height: width * 0.95 }]}>
                <Package size={40} color={colors.inkSoft} strokeWidth={1.5} />
              </View>
            }
          />
          <IconButton style={[styles.floatBtn, { top: 12, left: 16 }]} onPress={() => router.back()}>
            <ArrowLeft size={20} color={colors.ink} strokeWidth={2.2} />
          </IconButton>
          <IconButton
            style={[styles.floatBtn, { top: 12, right: 16 }]}
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
              size={20}
              color={liked ? colors.danger : colors.ink}
              fill={liked ? colors.danger : 'transparent'}
              strokeWidth={2.1}
            />
          </IconButton>
          <IconButton
            style={[styles.floatBtn, { top: 12, right: 116 }]}
            onPress={() => {
              if (!asCard) return;
              const result = compare.toggle(asCard);
              if (!result.ok && result.message) Alert.alert('Compare', result.message);
            }}
          >
            <GitCompare
              size={18}
              color={inCompare ? colors.jade : colors.ink}
              strokeWidth={2.1}
            />
          </IconButton>
          <IconButton
            style={[styles.floatBtn, { top: 12, right: 66 }]}
            onPress={() => {
              void track('product_share', { product_id: data.id });
              Share.share({
                message: `Check out ${data.name} on Unique Solution\nuniquesolution://products/${data.id}`,
                url: `uniquesolution://products/${data.id}`,
                title: data.name,
              }).catch(() => undefined);
            }}
          >
            <Share2 size={18} color={colors.ink} strokeWidth={2.1} />
          </IconButton>
        </View>

        <Animated.View entering={FadeInDown.springify().damping(18)} style={styles.body}>
          {data.brand?.name ? (
            <AppText variant="label" style={{ color: colors.brass }} numberOfLines={1}>
              {data.brand.name}
            </AppText>
          ) : null}
          <AppText variant="display" style={styles.productName} numberOfLines={3}>
            {data.name}
          </AppText>

          {ratingCount > 0 ? (
            <View style={styles.ratingNearTitle}>
              <StarsRow value={Math.round(ratingAvg)} size={16} />
              <AppText style={styles.ratingMeta}>
                {ratingAvg.toFixed(1)} ({ratingCount} review{ratingCount === 1 ? '' : 's'})
              </AppText>
            </View>
          ) : null}

          <View style={styles.priceRow}>
            <AppText style={styles.price} numberOfLines={1}>
              {formatInr(price)}
            </AppText>
            {sale ? (
              <AppText style={styles.mrp} numberOfLines={1}>
                {formatInr(mrp)}
              </AppText>
            ) : null}
            {off ? (
              <View style={styles.off}>
                <AppText style={styles.offText} numberOfLines={1}>
                  {off}% off
                </AppText>
              </View>
            ) : null}
          </View>

          <View style={styles.compareRow}>
            <PressableScale
              style={[styles.compareBtn, inCompare && styles.compareBtnOn]}
              onPress={() => {
                if (!asCard) return;
                const result = compare.toggle(asCard);
                if (!result.ok && result.message) Alert.alert('Compare', result.message);
              }}
            >
              <GitCompare
                size={16}
                color={inCompare ? colors.paper : colors.ink}
                strokeWidth={2}
              />
              <AppText
                style={{
                  color: inCompare ? colors.paper : colors.ink,
                  fontFamily: typography.bodySemi,
                }}
              >
                {inCompare
                  ? 'Added to compare'
                  : `Compare (${compare.items.length}/${MAX_COMPARE})`}
              </AppText>
            </PressableScale>
            {compare.items.length > 0 ? (
              <PressableScale style={styles.viewCompare} onPress={() => router.push('/compare')}>
                <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi }}>
                  View list
                </AppText>
              </PressableScale>
            ) : null}
          </View>

          {data.variants?.length ? (
            <View style={{ marginTop: spacing.lg, gap: 10 }}>
              <AppText variant="label">Choose variant</AppText>
              <View style={styles.wrap}>
                {data.variants.map((v) => {
                  const label = v.attributes.map((a) => a.value).join(' · ') || v.sku;
                  const hex =
                    v.attributes.map((a) => a.hex).find((h) => !!h && String(h).trim()) ?? null;
                  return (
                    <Chip
                      key={v.id}
                      label={label}
                      selected={(selected?.id ?? null) === v.id}
                      onPress={() => setVariantId(v.id)}
                      leading={
                        hex ? (
                          <View
                            style={[
                              styles.swatch,
                              {
                                backgroundColor: String(hex).startsWith('#')
                                  ? String(hex)
                                  : `#${hex}`,
                              },
                            ]}
                          />
                        ) : undefined
                      }
                    />
                  );
                })}
              </View>
              {outOfStock ? (
                <View style={{ gap: 8 }}>
                  <AppText style={{ color: colors.danger }}>Out of stock</AppText>
                  <AppButton
                    label={notifyStock.isPending ? 'Saving…' : 'Notify me'}
                    variant="ghost"
                    icon={<Bell size={16} color={colors.jade} strokeWidth={2} />}
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
            </View>
          ) : null}

          <View style={styles.section}>
            <AppText variant="label">Check delivery</AppText>
            <View style={styles.pinRow}>
              <MapPin size={16} color={colors.jade} strokeWidth={2} />
              <TextInput
                value={pincode}
                onChangeText={setPincode}
                placeholder="Enter pincode"
                placeholderTextColor={colors.inkSoft}
                keyboardType="number-pad"
                maxLength={12}
                style={styles.pinInput}
              />
              <PressableScale onPress={checkDelivery} style={styles.pinCheck}>
                {pinBusy ? (
                  <ActivityIndicator color={colors.paper} size="small" />
                ) : (
                  <AppText style={styles.pinCheckText}>Check</AppText>
                )}
              </PressableScale>
            </View>
            {pinMsg ? (
              <AppText style={{ color: pinOk ? colors.jade : colors.danger, marginTop: 8 }}>
                {pinMsg}
              </AppText>
            ) : null}
          </View>

          {data.description ? (
            <View style={{ marginTop: spacing.xl }}>
              <AppText variant="label">About</AppText>
              <AppText variant="body" style={{ marginTop: 8, color: colors.inkMuted }}>
                {data.description.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()}
              </AppText>
            </View>
          ) : null}

          {specs.length ? (
            <View style={styles.section}>
              <AppText variant="label">Specifications</AppText>
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

          {productWarranty ? (
            <View style={styles.warranty}>
              <ShieldCheck size={18} color={colors.jade} strokeWidth={2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText variant="label">Warranty</AppText>
                <AppText variant="body" style={{ marginTop: 4, color: colors.inkMuted }}>
                  {productWarranty.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()}
                </AppText>
              </View>
            </View>
          ) : null}

          {brandWarranty ? (
            <View style={styles.warranty}>
              <ShieldCheck size={18} color={colors.brassDeep} strokeWidth={2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText variant="label">
                  {data.brand?.name ? `${data.brand.name} warranty` : 'Brand warranty'}
                </AppText>
                <AppText variant="body" style={{ marginTop: 4, color: colors.inkMuted }}>
                  {brandWarranty.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()}
                </AppText>
              </View>
            </View>
          ) : null}

          <View style={styles.section}>
            <AppText variant="label">Reviews</AppText>
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
                      <AppText variant="caption" style={{ marginTop: 6, color: colors.jade }}>
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
                  <AppText variant="caption" style={{ marginTop: 8, color: colors.jade }}>
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
              <AppText variant="label">Related products</AppText>
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

      <View style={[styles.bar, { marginBottom: Math.max(insets.bottom, 8) }]}>
        <View style={styles.barPrice}>
          <AppText variant="caption">Pay</AppText>
          <AppText style={styles.barAmount} numberOfLines={1}>
            {formatInr(price)}
          </AppText>
        </View>
        <View style={{ flex: 1 }}>
          <AppButton
            label="Add to bag"
            icon={<ShoppingBag size={18} color={colors.paper} strokeWidth={2.1} />}
            disabled={outOfStock}
            onPress={() => {
              add({
                productId: data.id,
                variantId: selected?.id,
                name: data.name,
                image_url: selected?.image_url ?? data.image_url,
                mrp,
                sale_price: sale,
                attributeLabel: attrLabel,
              });
              void track('add_to_cart', {
                product_id: data.id,
                variant_id: selected?.id,
                price,
              });
              router.push('/(main)/(tabs)/cart');
            }}
          />
        </View>
      </View>
    </ScreenAtmosphere>
  );
}

const styles = StyleSheet.create({
  imageFallback: {
    backgroundColor: colors.canvasDeep,
    alignItems: 'center',
    justifyContent: 'center',
  },
  floatBtn: {
    position: 'absolute',
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  body: { padding: spacing.lg },
  productName: { fontSize: 28, lineHeight: 34 },
  ratingNearTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 8,
    flexWrap: 'wrap',
  },
  ratingMeta: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: colors.inkMuted,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: spacing.sm,
  },
  price: { fontFamily: typography.display, fontSize: 28, color: colors.ink, flexShrink: 1 },
  mrp: {
    fontFamily: typography.body,
    color: colors.inkSoft,
    textDecorationLine: 'line-through',
    flexShrink: 1,
  },
  off: {
    backgroundColor: colors.jadeSoft,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  offText: { color: colors.jade, fontFamily: typography.bodySemi, fontSize: 12 },
  compareRow: {
    marginTop: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flexWrap: 'wrap',
  },
  compareBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.borderStrong,
    borderRadius: radii.pill,
    paddingHorizontal: 14,
    paddingVertical: 10,
    ...elevation.soft,
  },
  compareBtnOn: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  viewCompare: {
    paddingHorizontal: 8,
    paddingVertical: 10,
  },
  wrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  swatch: {
    width: 14,
    height: 14,
    borderRadius: 7,
    borderWidth: 1,
    borderColor: colors.borderStrong,
    marginRight: 2,
  },
  section: { marginTop: spacing.xl },
  pinRow: {
    marginTop: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    paddingLeft: 12,
    overflow: 'hidden',
    ...elevation.soft,
  },
  pinInput: {
    flex: 1,
    minWidth: 0,
    paddingVertical: 12,
    fontFamily: typography.bodyMedium,
    color: colors.ink,
    fontSize: 15,
  },
  pinCheck: {
    backgroundColor: colors.jade,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  pinCheckText: { color: colors.paper, fontFamily: typography.bodySemi, fontSize: 13 },
  specTable: {
    marginTop: 10,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    ...elevation.soft,
  },
  specRow: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
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
  warranty: {
    marginTop: spacing.lg,
    flexDirection: 'row',
    gap: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  reviewCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  reviewHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  writeBox: {
    marginTop: 14,
    backgroundColor: colors.brassSoft,
    borderRadius: radii.lg,
    padding: spacing.md,
    gap: 4,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  reviewInput: {
    marginTop: 10,
    marginBottom: 8,
    minHeight: 80,
    textAlignVertical: 'top',
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 12,
    fontFamily: typography.body,
    color: colors.ink,
    fontSize: 14,
  },
  itemName: { fontFamily: typography.bodySemi, color: colors.ink },
  bar: {
    position: 'absolute',
    left: 12,
    right: 12,
    bottom: 8,
    padding: spacing.md,
    backgroundColor: colors.paper,
    borderRadius: radii.xl,
    borderWidth: 1,
    borderColor: colors.border,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    ...elevation.lift,
  },
  barPrice: { minWidth: 88 },
  barAmount: {
    fontFamily: typography.displayBold,
    fontSize: 20,
    color: colors.ink,
    marginTop: 2,
  },
});

import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import {
  Bell,
  ChevronDown,
  ChevronRight,
  Filter,
  MapPin,
  Search,
  Zap,
} from 'lucide-react-native';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  NativeScrollEvent,
  NativeSyntheticEvent,
  StyleSheet,
  useWindowDimensions,
  View,
} from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useQuery } from '@tanstack/react-query';
import { catalogApi } from '@/api/catalog';
import { MenuButton } from '@/components/layout/MenuButton';
import { ProductCard } from '@/components/product/ProductCard';
import { AppRefreshControl, usePullRefresh } from '@/components/ui/AppRefreshControl';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { useRecentStore } from '@/store/recent';
import { useShopStore } from '@/store/shop';
import { colors, elevation, gradients, radii, spacing, typography } from '@/theme/tokens';
import type { Banner, ProductCard as ProductCardType } from '@/types/catalog';
import { formatInr, sellingPrice } from '@/utils/price';
import { openDeepLink, saleToDeepLink } from '@/utils/deepLink';

function pad2(n: number) {
  return String(Math.max(0, n)).padStart(2, '0');
}

function useCountdown(endsAt?: string | null) {
  const target = useMemo(() => {
    if (endsAt) {
      const t = Date.parse(endsAt);
      if (!Number.isNaN(t)) return t;
    }
    // fallback: end of today + 6h feel
    return Date.now() + 4 * 3600_000 + 18 * 60_000 + 52_000;
  }, [endsAt]);

  const [now, setNow] = useState(Date.now());
  useEffect(() => {
    const id = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(id);
  }, []);

  const left = Math.max(0, target - now);
  const h = Math.floor(left / 3600_000);
  const m = Math.floor((left % 3600_000) / 60_000);
  const s = Math.floor((left % 60_000) / 1000);
  return { h, m, s };
}

function SectionHead({
  title,
  onPress,
  action = 'See all',
}: {
  title: string;
  onPress: () => void;
  action?: string;
}) {
  return (
    <View style={styles.sectionHead}>
      <AppText style={styles.sectionTitle}>{title}</AppText>
      <PressableScale
        onPress={() => {
          void Haptics.selectionAsync();
          onPress();
        }}
        style={styles.seeAll}
      >
        <AppText style={styles.seeAllText}>{action}</AppText>
        <ChevronRight size={14} color={colors.jade} strokeWidth={2.4} />
      </PressableScale>
    </View>
  );
}

function BannerCarousel({
  banners,
  width,
}: {
  banners: Banner[];
  width: number;
}) {
  const [index, setIndex] = useState(0);
  const listRef = useRef<FlatList<Banner>>(null);
  // 1920×1080 (16:9) + a bit taller than the old 168px card for phone screens
  const bannerH = Math.max(210, Math.round(width * (9 / 16) + 12));

  useEffect(() => {
    if (banners.length < 2) return;
    const t = setInterval(() => {
      setIndex((prev) => {
        const next = (prev + 1) % banners.length;
        listRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 4500);
    return () => clearInterval(t);
  }, [banners.length]);

  if (!banners.length) return null;

  return (
    <View style={styles.heroWrap}>
      <FlatList
        ref={listRef}
        data={banners}
        keyExtractor={(item) => `banner-${item.id}`}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        decelerationRate="fast"
        snapToInterval={width}
        snapToAlignment="start"
        disableIntervalMomentum
        bounces={false}
        overScrollMode="never"
        style={{ width }}
        getItemLayout={(_, i) => ({ length: width, offset: width * i, index: i })}
        onMomentumScrollEnd={(e: NativeSyntheticEvent<NativeScrollEvent>) => {
          const i = Math.round(e.nativeEvent.contentOffset.x / Math.max(width, 1));
          setIndex(i);
        }}
        renderItem={({ item }) => (
          <PressableScale
            style={[styles.bannerCard, { width, height: bannerH }]}
            onPress={() => {
              void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
              void openDeepLink(item.link);
            }}
          >
            {item.image_url ? (
              <Image
                source={{ uri: item.image_url }}
                style={StyleSheet.absoluteFillObject}
                contentFit="cover"
                transition={300}
              />
            ) : (
              <LinearGradient
                colors={[...gradients.hero]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={StyleSheet.absoluteFillObject}
              />
            )}
            {item.title ? (
              <View style={styles.bannerCopy}>
                <AppText style={styles.bannerTitle} numberOfLines={2}>
                  {item.title}
                </AppText>
                {item.subtitle ? (
                  <AppText style={styles.bannerSub} numberOfLines={1}>
                    {item.subtitle}
                  </AppText>
                ) : null}
              </View>
            ) : null}
          </PressableScale>
        )}
      />
      <View style={styles.heroDots}>
        {banners.map((b, i) => (
          <View key={b.id} style={[styles.dot, i === index && styles.dotOn]} />
        ))}
      </View>
    </View>
  );
}

function ArrivalHero({
  products,
  width,
}: {
  products: ProductCardType[];
  width: number;
}) {
  const [index, setIndex] = useState(0);
  const listRef = useRef<FlatList<ProductCardType>>(null);
  const cardW = width - spacing.lg * 2;

  useEffect(() => {
    if (products.length < 2) return;
    const t = setInterval(() => {
      setIndex((prev) => {
        const next = (prev + 1) % products.length;
        listRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 4500);
    return () => clearInterval(t);
  }, [products.length]);

  if (!products.length) return null;

  return (
    <View style={styles.heroWrap}>
      <FlatList
        ref={listRef}
        data={products}
        keyExtractor={(item) => `hero-${item.id}`}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        decelerationRate="fast"
        snapToInterval={cardW + 12}
        contentContainerStyle={{ paddingHorizontal: spacing.lg, gap: 12 }}
        onMomentumScrollEnd={(e: NativeSyntheticEvent<NativeScrollEvent>) => {
          const i = Math.round(e.nativeEvent.contentOffset.x / (cardW + 12));
          setIndex(i);
        }}
        renderItem={({ item }) => {
          const price = sellingPrice(item.mrp ?? item.base_price, item.sale_price);
          return (
            <PressableScale
              style={[styles.heroCard, { width: cardW }, elevation.lift]}
              onPress={() => {
                void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
                router.push(`/products/${item.id}`);
              }}
            >
              <LinearGradient
                colors={[...gradients.hero]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={StyleSheet.absoluteFillObject}
              />
              <View style={styles.heroCopy}>
                <View style={styles.newPill}>
                  <AppText style={styles.newPillText}>NEW ARRIVAL</AppText>
                </View>
                <AppText style={styles.heroTitle} numberOfLines={2}>
                  {item.name}
                </AppText>
                <AppText style={styles.heroPrice}>Starting {formatInr(price)}</AppText>
              </View>
              <View style={styles.heroImageWrap}>
                {item.image_url ? (
                  <Image
                    source={{ uri: item.image_url }}
                    style={styles.heroImage}
                    contentFit="contain"
                    transition={400}
                  />
                ) : null}
              </View>
            </PressableScale>
          );
        }}
      />
      <View style={styles.heroDots}>
        {products.slice(0, 5).map((p, i) => (
          <View key={p.id} style={[styles.dot, i === index && styles.dotOn]} />
        ))}
      </View>
    </View>
  );
}

export function HomeScreen() {
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const recent = useRecentStore((s) => s.items);
  const loadShop = useShopStore((s) => s.load);
  const shop = useShopStore((s) => s.shop);

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ['home'],
    queryFn: async () => (await catalogApi.home()).data,
  });

  useEffect(() => {
    void loadShop();
  }, [loadShop]);

  const { refreshing, onRefresh } = usePullRefresh(async () => {
    await Promise.all([refetch(), loadShop()]);
  });
  const pullRefreshing = refreshing || isRefetching;

  const shopName = data?.shop.shop_name ?? shop?.shop_name ?? 'Unique Solution';
  const shopAddress =
    data?.shop.shop_address || shop?.shop_address || 'Kargil Chowk, Kurud';
  const shortLoc = shopAddress.split(',')[0]?.trim() || shopAddress;

  const categories = data?.categories ?? [];
  const banners = (data?.banners ?? []).filter((b) => !!b.image_url);
  const newArrivals =
    data?.new_arrivals?.length
      ? data.new_arrivals
      : data?.featured_products ?? [];
  const featured = data?.featured_products?.length ? data?.featured_products : newArrivals;
  const saleProducts = data?.sale_products ?? [];
  const sales = data?.sales ?? [];
  const categorySales = data?.category_sales ?? [];
  const flashEnd = sales[0]?.ends_at ?? null;
  const { h, m, s } = useCountdown(flashEnd);
  const heroProducts = newArrivals.filter((p) => !!p.image_url).slice(0, 5);
  const gridProducts = featured.slice(0, 6);

  return (
    <View style={[styles.root, { paddingTop: insets.top + 8 }]}>
      <Animated.ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 130 }}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <AppRefreshControl refreshing={pullRefreshing} onRefresh={onRefresh} />
        }
      >
        {/* Top bar */}
        <View style={styles.topBar}>
          <MenuButton />
          <PressableScale style={styles.locBtn} onPress={() => router.push('/addresses')}>
            <AppText style={styles.deliverLabel}>Deliver to</AppText>
            <View style={styles.locRow}>
              <MapPin size={14} color={colors.jade} strokeWidth={2.4} />
              <AppText style={styles.locText} numberOfLines={1}>
                {shortLoc}
              </AppText>
              <ChevronDown size={14} color={colors.inkMuted} />
            </View>
          </PressableScale>
          <PressableScale style={styles.bellBtn} onPress={() => router.push('/notifications')}>
            <Bell size={18} color={colors.ink} strokeWidth={2} />
          </PressableScale>
        </View>

        <AppText style={styles.shopHint} numberOfLines={1}>
          {shopName}
        </AppText>

        {/* Search */}
        <View style={styles.searchRow}>
          <PressableScale style={styles.searchBar} onPress={() => router.push('/search')}>
            <Search size={18} color={colors.inkSoft} strokeWidth={2.1} />
            <AppText style={styles.searchPlaceholder}>Search mobiles, TVs, ACs…</AppText>
          </PressableScale>
          <PressableScale
            style={styles.filterBtn}
            onPress={() => router.push({ pathname: '/products', params: { title: 'All products' } })}
          >
            <Filter size={18} color={colors.paper} strokeWidth={2.2} />
          </PressableScale>
        </View>

        {isLoading ? <ActivityIndicator color={colors.jade} style={{ marginTop: 28 }} /> : null}

        {isError ? (
          <View style={styles.errorBox}>
            <AppText style={styles.errorTitle}>Couldn’t load the shop</AppText>
            <AppText variant="caption">Same Wi‑Fi pe raho, phir Retry.</AppText>
            <PressableScale style={styles.retryBtn} onPress={() => void refetch()}>
              <AppText style={styles.retryText}>Retry</AppText>
            </PressableScale>
          </View>
        ) : null}

        {/* Admin carousel banners from /home */}
        {!isError && banners.length > 0 ? (
          <Animated.View entering={FadeInDown.springify()} style={{ marginTop: spacing.sm }}>
            <BannerCarousel banners={banners} width={width} />
          </Animated.View>
        ) : !isError && heroProducts.length > 0 ? (
          <Animated.View entering={FadeInDown.springify()} style={{ marginTop: spacing.md }}>
            <ArrivalHero products={heroProducts} width={width} />
          </Animated.View>
        ) : null}

        {/* Categories */}
        {categories.length > 0 ? (
          <Animated.View entering={FadeInDown.delay(60).springify()} style={styles.block}>
            <SectionHead
              title="Shop by category"
              onPress={() => router.push('/(main)/(tabs)/categories')}
            />
            <Animated.ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.hPad}
            >
              {categories.slice(0, 10).map((c) => (
                <PressableScale
                  key={c.id}
                  style={styles.catTile}
                  onPress={() => {
                    void Haptics.selectionAsync();
                    router.push({
                      pathname: '/products',
                      params: { category_id: String(c.id), title: c.name },
                    });
                  }}
                >
                  <View style={styles.catIcon}>
                    {c.image_url ? (
                      <Image source={{ uri: c.image_url }} style={styles.catImg} contentFit="cover" />
                    ) : (
                      <AppText style={styles.catLetter}>{c.name.slice(0, 1)}</AppText>
                    )}
                  </View>
                  <AppText style={styles.catName} numberOfLines={1}>
                    {c.name}
                  </AppText>
                </PressableScale>
              ))}
            </Animated.ScrollView>
          </Animated.View>
        ) : null}

        {/* Flash deals */}
        {(saleProducts.length > 0 || sales.length > 0) && (
          <Animated.View entering={FadeInDown.delay(90).springify()} style={styles.block}>
            <PressableScale
              style={styles.flashCard}
              onPress={() => router.push('/deals')}
            >
              <LinearGradient
                colors={[...gradients.deal]}
                start={{ x: 0, y: 0.5 }}
                end={{ x: 1, y: 0.5 }}
                style={StyleSheet.absoluteFillObject}
              />
              <View style={styles.flashLeft}>
                <View style={styles.zap}>
                  <Zap size={16} color={colors.brass} fill={colors.brass} />
                </View>
                <AppText style={styles.flashTitle}>Flash deals</AppText>
              </View>
              <View style={styles.timerRow}>
                <AppText style={styles.endsIn}>Ends in</AppText>
                {[pad2(h), pad2(m), pad2(s)].map((chunk, i) => (
                  <View key={`${chunk}-${i}`} style={styles.timerBox}>
                    <AppText style={styles.timerText}>{chunk}</AppText>
                  </View>
                ))}
              </View>
            </PressableScale>

            {saleProducts.length ? (
              <Animated.ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={[styles.hPad, { marginTop: 14 }]}
              >
                {saleProducts.slice(0, 8).map((p, i) => (
                  <View key={p.id} style={styles.railItem}>
                    <ProductCard product={p} compact index={i} />
                  </View>
                ))}
              </Animated.ScrollView>
            ) : null}
          </Animated.View>
        )}

        {/* Featured */}
        {!isError && gridProducts.length > 0 ? (
          <Animated.View entering={FadeInDown.delay(120).springify()} style={styles.block}>
            <SectionHead
              title="Featured products"
              onPress={() =>
                router.push({ pathname: '/products', params: { featured: '1', title: 'Featured' } })
              }
            />
            <View style={styles.grid}>
              {gridProducts.map((p, i) => (
                <View key={p.id} style={styles.gridItem}>
                  <ProductCard product={p} index={i} />
                </View>
              ))}
            </View>
          </Animated.View>
        ) : null}

        {/* Extra offer strips */}
        {(sales.length > 0 || categorySales.length > 0) && (
          <View style={styles.block}>
            <SectionHead title="Store offers" onPress={() => router.push('/deals')} />
            <Animated.ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.hPad}
            >
              {categorySales.map((sale) => (
                <PressableScale
                  key={`cs-${sale.category_id}`}
                  style={[styles.offerCard, { width: Math.min(width * 0.72, 270) }]}
                  onPress={() =>
                    router.push({
                      pathname: '/products',
                      params: {
                        category_id: String(sale.category_id),
                        title: sale.category_name,
                      },
                    })
                  }
                >
                  {sale.banner_url ? (
                    <Image
                      source={{ uri: sale.banner_url }}
                      style={StyleSheet.absoluteFillObject}
                      contentFit="cover"
                    />
                  ) : (
                    <LinearGradient colors={[...gradients.hero]} style={StyleSheet.absoluteFillObject} />
                  )}
                  <View style={styles.offerScrim} />
                  <AppText style={styles.offerTitle} numberOfLines={2}>
                    {sale.title || sale.category_name}
                  </AppText>
                </PressableScale>
              ))}
              {sales.map((item) => (
                <PressableScale
                  key={`sale-${item.id}`}
                  style={[styles.offerCard, { width: Math.min(width * 0.72, 270) }]}
                  onPress={() => void openDeepLink(saleToDeepLink(item))}
                >
                  {item.image_url ? (
                    <Image
                      source={{ uri: item.image_url }}
                      style={StyleSheet.absoluteFillObject}
                      contentFit="cover"
                    />
                  ) : (
                    <LinearGradient colors={[...gradients.hero]} style={StyleSheet.absoluteFillObject} />
                  )}
                  <View style={styles.offerScrim} />
                  <AppText style={styles.offerTitle} numberOfLines={2}>
                    {item.title || 'Sale'}
                  </AppText>
                </PressableScale>
              ))}
            </Animated.ScrollView>
          </View>
        )}

        {recent.length > 0 ? (
          <View style={[styles.block, { marginBottom: 8 }]}>
            <SectionHead title="Continue browsing" onPress={() => router.push('/products')} />
            <Animated.ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.hPad}
            >
              {recent.map((p, i) => (
                <View key={p.id} style={styles.railItem}>
                  <ProductCard product={p} compact index={i} />
                </View>
              ))}
            </Animated.ScrollView>
          </View>
        ) : null}
      </Animated.ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas },
  topBar: {
    paddingHorizontal: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  locBtn: { flex: 1, minWidth: 0, gap: 2 },
  deliverLabel: {
    fontFamily: typography.body,
    fontSize: 12,
    color: colors.inkSoft,
  },
  locRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  locText: {
    flexShrink: 1,
    fontFamily: typography.bodySemi,
    fontSize: 15,
    color: colors.ink,
  },
  bellBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    ...elevation.soft,
  },
  shopHint: {
    marginTop: 4,
    paddingHorizontal: spacing.md,
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: colors.inkMuted,
  },
  searchRow: {
    marginTop: 10,
    paddingHorizontal: spacing.md,
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
  },
  searchBar: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.paper,
    borderRadius: radii.pill,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  searchPlaceholder: {
    fontFamily: typography.body,
    fontSize: 14,
    color: colors.inkSoft,
  },
  filterBtn: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.jade,
    alignItems: 'center',
    justifyContent: 'center',
    ...elevation.soft,
  },
  heroWrap: { width: '100%', gap: 10 },
  bannerCard: {
    borderRadius: 0,
    overflow: 'hidden',
    justifyContent: 'flex-end',
    backgroundColor: colors.canvasDeep,
    // no card shadow / elevation
    elevation: 0,
    shadowOpacity: 0,
    shadowRadius: 0,
    shadowOffset: { width: 0, height: 0 },
  },
  bannerCopy: {
    paddingHorizontal: 18,
    paddingBottom: 18,
    paddingTop: 10,
    zIndex: 2,
    gap: 4,
  },
  bannerTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 20,
    lineHeight: 24,
    color: colors.paper,
  },
  bannerSub: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: 'rgba(255,255,255,0.92)',
  },
  heroCard: {
    height: 148,
    borderRadius: radii.xl,
    overflow: 'hidden',
    flexDirection: 'row',
    alignItems: 'center',
  },
  heroCopy: {
    flex: 1,
    paddingLeft: 18,
    paddingRight: 8,
    paddingVertical: 18,
    gap: 8,
    zIndex: 2,
  },
  newPill: {
    alignSelf: 'flex-start',
    backgroundColor: colors.brass,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: radii.pill,
  },
  newPillText: {
    fontFamily: typography.bodyBold,
    fontSize: 10,
    letterSpacing: 0.6,
    color: colors.ink,
  },
  heroTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 22,
    lineHeight: 26,
    color: colors.paper,
  },
  heroPrice: {
    fontFamily: typography.bodyMedium,
    fontSize: 14,
    color: 'rgba(255,255,255,0.88)',
  },
  heroImageWrap: {
    width: '42%',
    height: '100%',
    justifyContent: 'center',
    alignItems: 'center',
    paddingRight: 8,
  },
  heroImage: {
    width: '100%',
    height: '92%',
  },
  heroDots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 6,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.borderStrong,
  },
  dotOn: {
    width: 18,
    backgroundColor: colors.jade,
  },
  block: { marginTop: spacing.md },
  sectionHead: {
    paddingHorizontal: spacing.md,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  sectionTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 18,
    color: colors.ink,
  },
  seeAll: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  seeAllText: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.jade,
  },
  hPad: { paddingHorizontal: spacing.md, gap: 10 },
  catTile: { width: 70, alignItems: 'center', gap: 6 },
  catIcon: {
    width: 60,
    height: 60,
    borderRadius: radii.md,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
    ...elevation.soft,
  },
  catImg: { width: '100%', height: '100%' },
  catLetter: {
    fontFamily: typography.bodyBold,
    fontSize: 22,
    color: colors.jade,
  },
  catName: {
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: colors.ink,
    textAlign: 'center',
  },
  flashCard: {
    marginHorizontal: spacing.md,
    borderRadius: radii.lg,
    paddingHorizontal: 14,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    overflow: 'hidden',
    ...elevation.soft,
  },
  flashLeft: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  zap: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  flashTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 16,
    color: colors.paper,
  },
  timerRow: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  endsIn: {
    fontFamily: typography.body,
    fontSize: 11,
    color: 'rgba(255,255,255,0.7)',
    marginRight: 2,
  },
  timerBox: {
    minWidth: 30,
    paddingHorizontal: 6,
    paddingVertical: 5,
    borderRadius: 8,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems: 'center',
  },
  timerText: {
    fontFamily: typography.bodyBold,
    fontSize: 13,
    color: colors.brass,
  },
  railItem: { width: 148 },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: spacing.md - 4,
  },
  gridItem: { width: '50%', padding: 4 },
  offerCard: {
    height: 120,
    borderRadius: radii.lg,
    overflow: 'hidden',
    justifyContent: 'flex-end',
    padding: spacing.md,
  },
  offerScrim: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(8,12,18,0.4)',
  },
  offerTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 16,
    color: colors.paper,
  },
  errorBox: {
    marginTop: spacing.lg,
    marginHorizontal: spacing.lg,
    padding: spacing.lg,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 8,
  },
  errorTitle: { fontFamily: typography.bodySemi, fontSize: 15, color: colors.ink },
  retryBtn: {
    alignSelf: 'flex-start',
    marginTop: 4,
    backgroundColor: colors.jade,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: radii.pill,
  },
  retryText: { fontFamily: typography.bodySemi, color: colors.paper, fontSize: 13 },
});

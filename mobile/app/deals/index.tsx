import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Percent, Ticket } from 'lucide-react-native';
import { Alert, FlatList, StyleSheet, View } from 'react-native';
import { catalogApi } from '@/api/catalog';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, FadeInItem, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, gradients, radii, spacing, typography } from '@/theme/tokens';
import { openDeepLink, saleToDeepLink } from '@/utils/deepLink';

export default function DealsScreen() {
  const { data: sales, refetch: refetchSales, isRefetching: salesRefreshing } = useQuery({
    queryKey: ['sales'],
    queryFn: async () => (await catalogApi.sales()).data,
  });
  const { data: coupons, refetch: refetchCoupons, isRefetching: couponsRefreshing } = useQuery({
    queryKey: ['coupons'],
    queryFn: async () => (await catalogApi.coupons()).data,
  });
  const {
    data: categorySales,
    refetch: refetchCategorySales,
    isRefetching: categoryRefreshing,
  } = useQuery({
    queryKey: ['category-sales'],
    queryFn: async () => (await catalogApi.categorySales()).data,
  });

  const useCoupon = (code: string) => {
    Alert.alert('Apply coupon', `Use ${code} at checkout?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Go to checkout',
        onPress: () => router.push({ pathname: '/checkout', params: { coupon: code } }),
      },
    ]);
  };

  const header = (
    <View style={{ gap: 12, marginBottom: 16 }}>
      <AppText style={styles.section}>Active coupons</AppText>
      {(coupons ?? []).map((c) => (
        <PressableScale key={c.code} style={styles.coupon} onPress={() => useCoupon(c.code)}>
          <Ticket size={18} color={colors.brassDeep} strokeWidth={2} />
          <View style={{ flex: 1, minWidth: 0 }}>
            <AppText style={styles.code} numberOfLines={1}>
              {c.code}
            </AppText>
            <AppText variant="caption" numberOfLines={2}>
              {c.title ||
                `${c.discount_type === 'percent' ? `${c.discount_value}%` : `₹${c.discount_value}`} off`}
              {c.min_order_value ? ` · Min ₹${c.min_order_value}` : ''}
            </AppText>
            {c.description ? (
              <AppText variant="caption" numberOfLines={2} style={{ marginTop: 2 }}>
                {c.description}
              </AppText>
            ) : null}
          </View>
          <AppText style={styles.apply}>Use</AppText>
        </PressableScale>
      ))}
      {!coupons?.length ? <AppText variant="caption">No coupons right now.</AppText> : null}

      {(categorySales ?? []).length ? (
        <>
          <AppText style={[styles.section, { marginTop: 8 }]}>Category sales</AppText>
          {(categorySales ?? []).map((sale) => (
            <PressableScale
              key={`cs-${sale.category_id}`}
              style={styles.sale}
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
                  transition={280}
                />
              ) : (
                <View style={styles.saleFallback}>
                  <LinearGradient
                    colors={[colors.jadeSoft, colors.paper]}
                    style={StyleSheet.absoluteFillObject}
                  />
                </View>
              )}
              {sale.banner_url ? <View style={styles.scrim} /> : null}
              <AppText
                style={[styles.saleTitle, !sale.banner_url && styles.saleTitleLight]}
                numberOfLines={2}
              >
                {sale.title || sale.category_name}
              </AppText>
              {sale.subtitle ? (
                <AppText
                  style={[styles.saleSub, !sale.banner_url && styles.saleSubLight]}
                  numberOfLines={2}
                >
                  {sale.subtitle}
                </AppText>
              ) : null}
            </PressableScale>
          ))}
        </>
      ) : null}

      <AppText style={[styles.section, { marginTop: 8 }]}>Sale banners</AppText>
    </View>
  );

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Offers" title="Deals & coupons" />

      <FlatList
        data={sales ?? []}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={header}
        contentContainerStyle={{ padding: spacing.lg, paddingBottom: 40 }}
        refreshControl={
          <AppRefreshControl
            refreshing={salesRefreshing || couponsRefreshing || categoryRefreshing}
            onRefresh={() => {
              void refetchSales();
              void refetchCoupons();
              void refetchCategorySales();
            }}
          />
        }
        ListEmptyComponent={
          !(categorySales ?? []).length ? (
            <View style={styles.empty}>
              <View style={themeCard.emptyIcon}>
                <Percent size={28} color={colors.inkSoft} strokeWidth={1.6} />
              </View>
              <AppText variant="caption">No live deals.</AppText>
            </View>
          ) : null
        }
        renderItem={({ item, index }) => (
          <FadeInItem index={index}>
            <PressableScale
              style={styles.sale}
              onPress={() => openDeepLink(saleToDeepLink(item))}
            >
              {item.image_url ? (
                <Image
                  source={{ uri: item.image_url }}
                  style={StyleSheet.absoluteFillObject}
                  contentFit="cover"
                  transition={280}
                />
              ) : (
                <LinearGradient colors={[...gradients.hero]} style={StyleSheet.absoluteFillObject} />
              )}
              <View style={styles.scrim} />
              <AppText style={styles.saleTitle} numberOfLines={2}>
                {item.title}
              </AppText>
              {item.subtitle ? (
                <AppText style={styles.saleSub} numberOfLines={2}>
                  {item.subtitle}
                </AppText>
              ) : null}
            </PressableScale>
          </FadeInItem>
        )}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  section: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 16 },
  coupon: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.brassSoft,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: 'rgba(143,115,72,0.2)',
    ...elevation.soft,
  },
  code: { fontFamily: typography.bodyBold, color: colors.brassDeep, letterSpacing: 1 },
  apply: { color: colors.jade, fontFamily: typography.bodySemi, fontSize: 13 },
  empty: { alignItems: 'center', gap: 10, marginTop: 24 },
  sale: {
    height: 148,
    borderRadius: radii.xl,
    marginBottom: 12,
    overflow: 'hidden',
    justifyContent: 'flex-end',
    padding: spacing.md,
    backgroundColor: colors.jadeSoft,
    ...elevation.soft,
  },
  saleFallback: { ...StyleSheet.absoluteFillObject },
  scrim: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(15, 23, 42, 0.28)' },
  saleTitle: { fontFamily: typography.displayBold, fontSize: 24, lineHeight: 28, color: colors.paper },
  saleTitleLight: { color: colors.ink },
  saleSub: { color: 'rgba(255,255,255,0.9)', marginTop: 2 },
  saleSubLight: { color: colors.inkMuted },
});

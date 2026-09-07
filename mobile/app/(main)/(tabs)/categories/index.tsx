import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { ChevronRight, LayoutGrid, Percent } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { FlatList, StyleSheet, useWindowDimensions, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { catalogApi } from '@/api/catalog';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, PressableScale } from '@/components/ui/primitives';
import type { Category } from '@/types/catalog';
import { colors, elevation, gradients, radii, spacing, typography } from '@/theme/tokens';

type TrailItem = { id: number; name: string };

export default function CategoriesScreen() {
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const [trail, setTrail] = useState<TrailItem[]>([]);
  const parentId = trail.at(-1)?.id;
  const cardW = (width - spacing.lg * 2 - 12) / 2;

  const { data, refetch, isRefetching } = useQuery({
    queryKey: ['categories', parentId ?? 'root'],
    queryFn: async () => {
      if (parentId == null) {
        return (await catalogApi.categories({ roots_only: true })).data;
      }
      return (await catalogApi.categories({ parent_id: parentId })).data;
    },
  });

  const { data: categorySales, refetch: refetchSales, isRefetching: salesRefreshing } = useQuery({
    queryKey: ['category-sales'],
    queryFn: async () => (await catalogApi.categorySales()).data,
  });

  const saleForParent = useMemo(() => {
    if (parentId == null) return null;
    return (categorySales ?? []).find((s) => s.category_id === parentId) ?? null;
  }, [categorySales, parentId]);

  const openCategory = (item: Category) => {
    void Haptics.selectionAsync();
    const childCount = item.children?.length ?? 0;
    if (childCount > 0) {
      setTrail((t) => [...t, { id: item.id, name: item.name }]);
      return;
    }
    router.push({
      pathname: '/products',
      params: { category_id: String(item.id), title: item.name },
    });
  };

  const goUp = () => {
    if (trail.length) {
      setTrail((t) => t.slice(0, -1));
      return;
    }
    router.back();
  };

  return (
    <ScreenAtmosphere style={{ paddingTop: insets.top + 8 }}>
      <ScreenHeader
        showMenu={trail.length === 0}
        showBack={trail.length > 0}
        onBack={trail.length > 0 ? goUp : undefined}
        eyebrow="Departments"
        title={trail.at(-1)?.name ?? 'The floor'}
      />

      <FlatList
        data={data ?? []}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={{ gap: 12, paddingHorizontal: spacing.lg }}
        contentContainerStyle={{ gap: 12, paddingBottom: 120 }}
        refreshControl={
          <AppRefreshControl
            refreshing={isRefetching || salesRefreshing}
            onRefresh={() => {
              void refetch();
              void refetchSales();
            }}
          />
        }
        ListHeaderComponent={
          <View style={{ gap: 12, marginBottom: 8, paddingHorizontal: spacing.lg }}>
            {trail.length > 0 ? (
              <PressableScale
                style={styles.shopAll}
                onPress={() =>
                  router.push({
                    pathname: '/products',
                    params: {
                      category_id: String(trail.at(-1)!.id),
                      title: trail.at(-1)!.name,
                    },
                  })
                }
              >
                <AppText style={styles.shopAllText}>Shop all in {trail.at(-1)!.name}</AppText>
                <ChevronRight size={16} color={colors.paper} />
              </PressableScale>
            ) : (
              <AppText style={styles.lead}>
                Walk the showroom — tap a department, then drill into the aisle.
              </AppText>
            )}
            {saleForParent ? (
              <PressableScale
                style={styles.saleBanner}
                onPress={() =>
                  router.push({
                    pathname: '/products',
                    params: {
                      category_id: String(saleForParent.category_id),
                      title: saleForParent.category_name,
                    },
                  })
                }
              >
                {saleForParent.banner_url ? (
                  <Image
                    source={{ uri: saleForParent.banner_url }}
                    style={StyleSheet.absoluteFillObject}
                    contentFit="cover"
                    transition={300}
                  />
                ) : (
                  <LinearGradient colors={[...gradients.hero]} style={StyleSheet.absoluteFillObject} />
                )}
                <View style={styles.saleScrim} />
                <Percent size={16} color="rgba(255,255,255,0.9)" strokeWidth={2} />
                <AppText style={styles.saleTitle} numberOfLines={2}>
                  {saleForParent.title || saleForParent.category_name}
                </AppText>
              </PressableScale>
            ) : null}
          </View>
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <View style={styles.emptyIcon}>
              <LayoutGrid size={28} color={colors.inkSoft} />
            </View>
            <AppText variant="caption">Categories will appear here.</AppText>
          </View>
        }
        renderItem={({ item, index }) => {
          const childCount = item.children?.length ?? 0;
          return (
            <Animated.View entering={FadeInDown.delay(Math.min(index, 8) * 40).springify()}>
              <PressableScale
                style={[styles.card, { width: cardW }, elevation.soft]}
                onPress={() => openCategory(item)}
              >
                <View style={styles.cardImage}>
                  {item.image_url ? (
                    <Image
                      source={{ uri: item.image_url }}
                      style={StyleSheet.absoluteFillObject}
                      contentFit="cover"
                      transition={280}
                    />
                  ) : (
                    <View style={styles.cardImageFallback}>
                      <AppText style={styles.cardLetter}>{item.name.slice(0, 1)}</AppText>
                    </View>
                  )}
                </View>
                <View style={styles.cardBody}>
                  <AppText style={styles.index}>{String(index + 1).padStart(2, '0')}</AppText>
                  <AppText style={styles.name} numberOfLines={2}>
                    {item.name}
                  </AppText>
                  <AppText style={styles.meta} numberOfLines={1}>
                    {childCount > 0 ? `${childCount} aisles` : item.has_sale ? 'On sale' : 'Browse'}
                  </AppText>
                </View>
              </PressableScale>
            </Animated.View>
          );
        }}
      />
    </ScreenAtmosphere>
  );
}

const styles = StyleSheet.create({
  lead: {
    fontFamily: typography.body,
    fontSize: 14,
    lineHeight: 21,
    color: colors.inkMuted,
  },
  shopAll: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.jade,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 14,
    ...elevation.soft,
  },
  shopAllText: {
    fontFamily: typography.bodySemi,
    color: colors.paper,
    fontSize: 14,
  },
  saleBanner: {
    height: 120,
    borderRadius: radii.lg,
    overflow: 'hidden',
    padding: spacing.md,
    justifyContent: 'flex-end',
    gap: 4,
    ...elevation.soft,
  },
  saleScrim: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15, 23, 42, 0.28)',
  },
  saleTitle: {
    fontFamily: typography.displayBold,
    color: colors.paper,
    fontSize: 18,
  },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  cardImage: {
    width: '100%',
    height: 100,
    backgroundColor: colors.canvasDeep,
  },
  cardImageFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.jadeSoft,
  },
  cardLetter: {
    fontFamily: typography.displayBold,
    fontSize: 28,
    color: colors.jade,
  },
  cardBody: {
    paddingHorizontal: 10,
    paddingTop: 8,
    paddingBottom: 10,
    gap: 2,
    minHeight: 72,
  },
  index: {
    fontFamily: typography.bodyMedium,
    fontSize: 10,
    letterSpacing: 1,
    color: colors.inkSoft,
  },
  name: {
    fontFamily: typography.displayBold,
    fontSize: 15,
    lineHeight: 18,
    color: colors.ink,
    height: 36,
  },
  meta: {
    fontFamily: typography.body,
    fontSize: 11,
    color: colors.inkMuted,
  },
  empty: { alignItems: 'center', gap: 10, marginTop: 48 },
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
});

import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { useLocalSearchParams } from 'expo-router';
import { LayoutGrid, PackageSearch, SlidersHorizontal } from 'lucide-react-native';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, ScrollView, StyleSheet, View } from 'react-native';
import { catalogApi } from '@/api/catalog';
import { FilterSheet } from '@/components/catalog/FilterSheet';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { ProductCard } from '@/components/product/ProductCard';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, Chip, PressableScale } from '@/components/ui/primitives';
import { useFilterStore } from '@/store/filters';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

const PER_PAGE = 20;

export default function ProductsScreen() {
  const params = useLocalSearchParams<{
    category_id?: string;
    brand_id?: string;
    title?: string;
    q?: string;
    search?: string;
    featured?: string;
  }>();
  const filters = useFilterStore();
  const [sheetOpen, setSheetOpen] = useState(false);

  const searchQ = String(params.q ?? params.search ?? '');
  const featuredOn =
    params.featured === '1' || params.featured === 'true' || params.featured === 'yes';

  useEffect(() => {
    filters.hydrateFromRoute({
      category_id: params.category_id ? Number(params.category_id) : null,
      category_name: params.title ? String(params.title) : null,
      brand_ids: params.brand_id ? [Number(params.brand_id)] : [],
      q: searchQ,
      attribute_value_ids: [],
      sort: 'newest',
      featured: featuredOn,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params.category_id, params.brand_id, params.title, searchQ, featuredOn]);

  const queryKey = useMemo(() => ['products', filters.applied] as const, [filters.applied]);

  const { data, isLoading, isFetching, isFetchingNextPage, fetchNextPage, hasNextPage, refetch, isRefetching } =
    useInfiniteQuery({
      queryKey,
      initialPageParam: 1,
      queryFn: async ({ pageParam }) => {
        const res = await catalogApi.products({
          ...filters.applied,
          featured: filters.applied.featured || undefined,
          page: pageParam,
          per_page: PER_PAGE,
        });
        const meta = res.meta ?? {};
        const current = Number(meta.current_page ?? pageParam);
        const last = Number(meta.last_page ?? current);
        return {
          items: res.data ?? [],
          current,
          last,
        };
      },
      getNextPageParam: (last) => (last.current < last.last ? last.current + 1 : undefined),
    });

  const products = useMemo(() => data?.pages.flatMap((p) => p.items) ?? [], [data]);
  const title = String(params.title ?? (featuredOn ? 'Featured' : searchQ || 'All products'));

  const routeCategoryId = params.category_id ? Number(params.category_id) : null;
  const { data: facets } = useQuery({
    queryKey: ['product-filters', routeCategoryId],
    queryFn: async () => (await catalogApi.filters(routeCategoryId)).data,
    enabled: routeCategoryId != null,
  });
  const categoryBrands = facets?.brands ?? [];
  const selectedBrandId =
    filters.applied.brand_ids.length === 1 ? filters.applied.brand_ids[0] : null;
  const showBrandStrip = routeCategoryId != null && categoryBrands.length > 0;

  return (
    <ScreenShell>
      <ScreenHeader
        showBack
        eyebrow={params.category_id ? 'Home / Catalog' : 'Catalog'}
        title={title}
        right={
          <PressableScale
            style={styles.filterBtn}
            onPress={() => {
              filters.resetDraft();
              setSheetOpen(true);
            }}
          >
            <SlidersHorizontal size={15} color={colors.paper} strokeWidth={2.2} />
            <AppText style={styles.filterLabel} numberOfLines={1}>
              {filters.activeCount() ? `${filters.activeCount()}` : 'Filter'}
            </AppText>
          </PressableScale>
        }
      />

      {showBrandStrip ? (
        <View style={styles.brandSection}>
          <AppText style={styles.brandHeading}>Shop by brand</AppText>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.brandRow}
          >
            <PressableScale
              style={[styles.brandItem, filters.applied.brand_ids.length === 0 && styles.brandItemActive]}
              onPress={() => {
                void Haptics.selectionAsync();
                filters.selectBrand(null);
              }}
            >
              <View style={[styles.brandLogo, styles.brandLogoFallback]}>
                <LayoutGrid size={20} color={colors.jade} strokeWidth={2} />
              </View>
              <AppText style={styles.brandName} numberOfLines={1}>
                All
              </AppText>
            </PressableScale>
            {categoryBrands.map((brand) => {
              const active = selectedBrandId === brand.id;
              return (
                <PressableScale
                  key={brand.id}
                  style={[styles.brandItem, active && styles.brandItemActive]}
                  onPress={() => {
                    void Haptics.selectionAsync();
                    filters.selectBrand(active ? null : brand.id);
                  }}
                >
                  {brand.logo_url ? (
                    <Image
                      source={{ uri: brand.logo_url }}
                      style={styles.brandLogo}
                      contentFit="contain"
                      transition={200}
                    />
                  ) : (
                    <View style={[styles.brandLogo, styles.brandLogoFallback]}>
                      <AppText style={styles.brandLetter}>{brand.name.slice(0, 1)}</AppText>
                    </View>
                  )}
                  <AppText style={styles.brandName} numberOfLines={1}>
                    {brand.name}
                  </AppText>
                </PressableScale>
              );
            })}
          </ScrollView>
        </View>
      ) : null}

      <View style={styles.metaRow}>
        <AppText style={styles.results}>
          {isLoading ? 'Loading…' : `${products.length}${hasNextPage ? '+' : ''} results`}
        </AppText>
        <PressableScale onPress={() => setSheetOpen(true)}>
          <AppText style={styles.sort}>Sort: {filters.applied.sort}</AppText>
        </PressableScale>
      </View>

      <View style={styles.chipRow}>
        {featuredOn || filters.applied.featured ? <Chip label="Featured" selected /> : null}
        {filters.applied.sort !== 'newest' ? (
          <Chip label={`Sort: ${filters.applied.sort}`} selected />
        ) : null}
        {filters.applied.brand_ids.length && !(showBrandStrip && selectedBrandId != null) ? (
          <Chip label={`${filters.applied.brand_ids.length} brands`} selected />
        ) : null}
        {filters.applied.attribute_value_ids.length ? (
          <Chip label={`${filters.applied.attribute_value_ids.length} attrs`} selected />
        ) : null}
        {filters.applied.q?.trim() ? <Chip label={`“${filters.applied.q.trim()}”`} selected /> : null}
      </View>

      {isLoading ? (
        <ActivityIndicator color={colors.jade} style={{ marginTop: 40 }} />
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={{ gap: 8 }}
          contentContainerStyle={{ padding: spacing.md, gap: 8, paddingBottom: 40 }}
          refreshControl={
            <AppRefreshControl
              refreshing={isRefetching && !isFetchingNextPage}
              onRefresh={() => void refetch()}
            />
          }
          onEndReachedThreshold={0.4}
          onEndReached={() => {
            if (hasNextPage && !isFetchingNextPage) {
              void fetchNextPage();
            }
          }}
          ListEmptyComponent={
            <View style={styles.empty}>
              <View style={themeCard.emptyIcon}>
                <PackageSearch size={28} color={colors.inkSoft} />
              </View>
              <AppText variant="caption" style={{ textAlign: 'center' }}>
                No products match these filters.
              </AppText>
            </View>
          }
          ListFooterComponent={
            isFetchingNextPage || (isFetching && !isLoading) ? (
              <ActivityIndicator color={colors.jade} style={{ marginVertical: 16 }} />
            ) : null
          }
          renderItem={({ item }) => (
            <View style={{ flex: 1, minWidth: 0 }}>
              <ProductCard product={item} />
            </View>
          )}
        />
      )}

      <FilterSheet
        visible={sheetOpen}
        onClose={() => setSheetOpen(false)}
        categoryTitle={params.category_id ? title : undefined}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  filterBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.jade,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: radii.pill,
    maxWidth: 96,
    ...elevation.soft,
  },
  filterLabel: {
    color: colors.paper,
    fontFamily: typography.bodySemi,
    fontSize: 12,
  },
  brandSection: {
    marginBottom: 10,
  },
  brandHeading: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.inkMuted,
    paddingHorizontal: spacing.lg,
    marginBottom: 8,
  },
  brandRow: {
    gap: 10,
    paddingHorizontal: spacing.lg,
  },
  brandItem: {
    width: 76,
    alignItems: 'center',
    gap: 6,
    paddingVertical: 8,
    paddingHorizontal: 4,
    borderRadius: radii.md,
    borderWidth: 1.5,
    borderColor: colors.border,
    backgroundColor: colors.paper,
  },
  brandItemActive: {
    borderColor: colors.jade,
    backgroundColor: colors.jadeSoft,
  },
  brandLogo: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#FFFFFF',
  },
  brandLogoFallback: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.jadeSoft,
  },
  brandLetter: {
    fontFamily: typography.bodyBold,
    fontSize: 18,
    color: colors.jade,
  },
  brandName: {
    fontFamily: typography.bodyMedium,
    fontSize: 11,
    color: colors.ink,
    maxWidth: 68,
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    paddingHorizontal: spacing.lg,
    marginBottom: 4,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    marginBottom: 8,
  },
  results: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: colors.inkMuted,
  },
  sort: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.jade,
  },
  empty: {
    alignItems: 'center',
    gap: 10,
    marginTop: 48,
    paddingHorizontal: spacing.lg,
  },
});

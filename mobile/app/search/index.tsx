import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Building2, LayoutGrid, Search as SearchIcon } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { catalogApi } from '@/api/catalog';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { ProductCard } from '@/components/product/ProductCard';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { track } from '@/utils/analytics';

export default function SearchScreen() {
  const [q, setQ] = useState('');
  const [debounced, setDebounced] = useState('');
  const [submitted, setSubmitted] = useState('');

  useEffect(() => {
    const t = setTimeout(() => setDebounced(q.trim()), 300);
    return () => clearTimeout(t);
  }, [q]);

  const {
    data: suggestions,
    isFetching: suggesting,
    refetch: refetchSuggest,
    isRefetching: suggestRefreshing,
  } = useQuery({
    queryKey: ['suggest', debounced],
    queryFn: async () => (await catalogApi.suggest(debounced)).data,
    enabled: debounced.length > 1 && !submitted,
  });

  const { data, isFetching, refetch, isRefetching } = useQuery({
    queryKey: ['search', submitted],
    queryFn: async () =>
      (
        await catalogApi.products({
          q: submitted,
          sort: 'newest',
          brand_ids: [],
          attribute_value_ids: [],
          per_page: 40,
        })
      ).data,
    enabled: submitted.length > 1,
  });

  const showSuggest = debounced.length > 1 && !submitted;
  const hasSuggest =
    !!suggestions &&
    (suggestions.products.length > 0 ||
      suggestions.categories.length > 0 ||
      suggestions.brands.length > 0);

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Find" title="Search" />

      <View style={styles.inputWrap}>
        <SearchIcon size={18} color={colors.inkSoft} strokeWidth={2.1} />
        <TextInput
          autoFocus
          value={q}
          onChangeText={(text) => {
            setQ(text);
            if (submitted) setSubmitted('');
          }}
          placeholder="Search products, brands…"
          placeholderTextColor={colors.inkSoft}
          returnKeyType="search"
          onSubmitEditing={() => {
            const qTrim = q.trim();
            setSubmitted(qTrim);
            if (qTrim.length > 1) void track('search', { q: qTrim });
          }}
          style={styles.input}
        />
      </View>

      {showSuggest ? (
        <ScrollView
          style={{ flex: 1 }}
          contentContainerStyle={styles.suggestWrap}
          keyboardShouldPersistTaps="handled"
          refreshControl={
            <AppRefreshControl
              refreshing={suggestRefreshing}
              onRefresh={() => void refetchSuggest()}
            />
          }
        >
          {suggesting && !hasSuggest ? (
            <ActivityIndicator color={colors.jade} style={{ marginTop: 16 }} />
          ) : null}
          {suggestions?.categories.map((c) => (
            <PressableScale
              key={`cat-${c.id}`}
              style={styles.suggestRow}
              onPress={() =>
                router.push({ pathname: '/products', params: { category_id: String(c.id) } })
              }
            >
              <LayoutGrid size={16} color={colors.jade} strokeWidth={2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText numberOfLines={1} style={styles.suggestTitle}>
                  {c.name}
                </AppText>
                <AppText variant="caption">Category</AppText>
              </View>
            </PressableScale>
          ))}
          {suggestions?.brands.map((b) => (
            <PressableScale
              key={`brand-${b.id}`}
              style={styles.suggestRow}
              onPress={() =>
                router.push({ pathname: '/products', params: { brand_id: String(b.id) } })
              }
            >
              <Building2 size={16} color={colors.brassDeep} strokeWidth={2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText numberOfLines={1} style={styles.suggestTitle}>
                  {b.name}
                </AppText>
                <AppText variant="caption">Brand</AppText>
              </View>
            </PressableScale>
          ))}
          {suggestions?.products.map((p) => (
            <PressableScale
              key={`prod-${p.id}`}
              style={styles.suggestRow}
              onPress={() => router.push(`/products/${p.id}`)}
            >
              <SearchIcon size={16} color={colors.inkSoft} strokeWidth={2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText numberOfLines={1} style={styles.suggestTitle}>
                  {p.name}
                </AppText>
                {p.brand ? <AppText variant="caption">{p.brand}</AppText> : null}
              </View>
            </PressableScale>
          ))}
          {!suggesting && !hasSuggest ? (
            <AppText variant="caption" style={{ textAlign: 'center', marginTop: 20 }}>
              No suggestions — press search for full results.
            </AppText>
          ) : null}
        </ScrollView>
      ) : (
        <>
          {isFetching ? <ActivityIndicator color={colors.jade} style={{ marginTop: 24 }} /> : null}
          <FlatList
            data={data ?? []}
            keyExtractor={(item) => String(item.id)}
            numColumns={2}
            columnWrapperStyle={{ gap: 12 }}
            contentContainerStyle={{ padding: spacing.lg, gap: 12, paddingBottom: 40 }}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <AppRefreshControl
                refreshing={isRefetching || suggestRefreshing}
                onRefresh={() => {
                  if (submitted.length > 1) void refetch();
                  else if (debounced.length > 1) void refetchSuggest();
                }}
              />
            }
            ListEmptyComponent={
              <View style={styles.empty}>
                <View style={themeCard.emptyIcon}>
                  <SearchIcon size={28} color={colors.inkSoft} strokeWidth={1.6} />
                </View>
                <AppText variant="caption" style={{ textAlign: 'center' }}>
                  {submitted ? 'No matches found.' : 'Try “Samsung”, “TV”, or “AC”.'}
                </AppText>
              </View>
            }
            renderItem={({ item }) => (
              <View style={{ flex: 1, minWidth: 0 }}>
                <ProductCard product={item} />
              </View>
            )}
          />
        </>
      )}
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  inputWrap: {
    marginHorizontal: spacing.lg,
    marginBottom: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.paper,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 14,
    height: 46,
    ...elevation.soft,
  },
  input: {
    flex: 1,
    minWidth: 0,
    fontFamily: typography.bodyMedium,
    color: colors.ink,
    fontSize: 15,
  },
  suggestWrap: {
    paddingHorizontal: spacing.lg,
    gap: 4,
  },
  suggestRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 6,
    ...elevation.soft,
  },
  suggestTitle: {
    fontFamily: typography.bodySemi,
    color: colors.ink,
    fontSize: 14,
  },
  empty: { alignItems: 'center', gap: 10, marginTop: 40, paddingHorizontal: spacing.lg },
});

import { useQueries, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { GitCompare, Package, ShieldCheck, Star, X } from 'lucide-react-native';
import { useCallback, useMemo, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { catalogApi } from '@/api/catalog';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useCompareStore, MAX_COMPARE } from '@/store/compare';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import type { ProductDetail } from '@/types/catalog';
import { formatInr, sellingPrice } from '@/utils/price';

const COL_W = 156;

function Cell({
  label,
  values,
}: {
  label: string;
  values: (string | null | undefined)[];
}) {
  return (
    <View style={styles.rowBlock}>
      <AppText style={styles.rowLabel}>{label}</AppText>
      <View style={styles.rowValues}>
        {values.map((v, i) => (
          <View key={`${label}-${i}`} style={styles.cell}>
            <AppText style={styles.cellText} numberOfLines={3}>
              {v?.trim() || '—'}
            </AppText>
          </View>
        ))}
      </View>
    </View>
  );
}

export default function CompareScreen() {
  const insets = useSafeAreaInsets();
  const qc = useQueryClient();
  const { items, remove, clear } = useCompareStore();
  const [refreshing, setRefreshing] = useState(false);

  const detailQueries = useQueries({
    queries: items.map((p) => ({
      queryKey: ['product', String(p.id)],
      queryFn: async () => (await catalogApi.product(p.id)).data as ProductDetail,
      enabled: items.length > 0,
      staleTime: 60_000,
    })),
  });

  const details = detailQueries.map((q) => q.data ?? null);
  const loading = detailQueries.some((q) => q.isLoading);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await Promise.all(
        items.map((p) => qc.invalidateQueries({ queryKey: ['product', String(p.id)] })),
      );
    } finally {
      setRefreshing(false);
    }
  }, [items, qc]);

  const refreshControl = (
    <AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />
  );

  const specKeys = useMemo(() => {
    const keys = new Set<string>();
    details.forEach((d) => {
      (d?.specifications ?? []).forEach((s) => {
        if (s.name) keys.add(s.name);
      });
    });
    return Array.from(keys).slice(0, 10);
  }, [details]);

  return (
    <ScreenShell>
      <ScreenHeader
        showBack
        showMenu
        eyebrow="Side by side"
        title="Compare"
        subtitle={items.length ? `${items.length} of ${MAX_COMPARE} selected` : undefined}
      />

      {!items.length ? (
        <ScrollView
          contentContainerStyle={styles.empty}
          refreshControl={refreshControl}
        >
          <View style={themeCard.emptyIcon}>
            <GitCompare size={28} color={colors.inkSoft} strokeWidth={1.6} />
          </View>
          <AppText style={styles.emptyTitle}>Nothing to compare yet</AppText>
          <AppText variant="caption" style={{ textAlign: 'center' }}>
            Tap the compare icon on any product card or product page — add up to{' '}
            {MAX_COMPARE} items.
          </AppText>
          <AppButton label="Browse products" onPress={() => router.push('/products')} />
        </ScrollView>
      ) : (
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 110 }}
          refreshControl={refreshControl}
        >
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingHorizontal: spacing.md, gap: 0 }}
          >
            <View>
              {/* Product heads */}
              <View style={styles.headRow}>
                {items.map((p) => (
                  <View key={p.id} style={styles.headCol}>
                    <PressableScale style={styles.remove} onPress={() => remove(p.id)}>
                      <X size={13} color={colors.ink} strokeWidth={2.2} />
                    </PressableScale>
                    <PressableScale onPress={() => router.push(`/products/${p.id}`)}>
                      {p.image_url ? (
                        <Image source={{ uri: p.image_url }} style={styles.image} contentFit="cover" />
                      ) : (
                        <View style={[styles.image, styles.placeholder]}>
                          <Package size={24} color={colors.inkSoft} />
                        </View>
                      )}
                      <AppText style={styles.brand} numberOfLines={1}>
                        {p.brand || 'Brand'}
                      </AppText>
                      <AppText style={styles.name} numberOfLines={2}>
                        {p.name}
                      </AppText>
                    </PressableScale>
                  </View>
                ))}
              </View>

              {loading ? (
                <ActivityIndicator color={colors.jade} style={{ marginVertical: 24 }} />
              ) : (
                <View style={styles.table}>
                  <Cell
                    label="Price"
                    values={items.map((p) =>
                      formatInr(sellingPrice(p.mrp ?? p.base_price, p.sale_price)),
                    )}
                  />
                  <Cell
                    label="MRP"
                    values={items.map((p) =>
                      p.sale_price ? formatInr(p.mrp ?? p.base_price) : '—',
                    )}
                  />
                  <Cell
                    label="Rating"
                    values={items.map((p) =>
                      (p.rating_count ?? 0) > 0
                        ? `★ ${(p.rating_average ?? 0).toFixed(1)} (${p.rating_count})`
                        : 'No ratings',
                    )}
                  />
                  <Cell
                    label="Warranty"
                    values={items.map((p, i) => {
                      const d = details[i];
                      return (
                        d?.warranty_info ||
                        d?.brand?.warranty ||
                        p.warranty_info ||
                        p.brand_warranty ||
                        p.highlight ||
                        '—'
                      );
                    })}
                  />
                  <Cell
                    label="Category"
                    values={items.map((p, i) => details[i]?.category?.name || p.category || '—')}
                  />
                  {specKeys.map((key) => (
                    <Cell
                      key={key}
                      label={key}
                      values={details.map((d) => {
                        const hit = (d?.specifications ?? []).find((s) => s.name === key);
                        return hit?.value ?? '—';
                      })}
                    />
                  ))}
                </View>
              )}

              <View style={styles.actionsRow}>
                {items.map((p) => (
                  <View key={`act-${p.id}`} style={styles.actionCol}>
                    <AppButton label="View" onPress={() => router.push(`/products/${p.id}`)} />
                  </View>
                ))}
              </View>
            </View>
          </ScrollView>

          <View style={styles.legend}>
            <ShieldCheck size={14} color={colors.jade} />
            <AppText variant="caption">
              Warranty & specs pull live from product data in the shop.
            </AppText>
          </View>
          <View style={styles.legend}>
            <Star size={14} color={colors.brass} />
            <AppText variant="caption">Add more from any product (max {MAX_COMPARE}).</AppText>
          </View>
        </ScrollView>
      )}

      {items.length ? (
        <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 14) }]}>
          <AppButton label="Clear all" variant="ghost" onPress={clear} />
        </View>
      ) : null}
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  empty: {
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    paddingHorizontal: spacing.xl,
    paddingVertical: 48,
  },
  emptyTitle: {
    fontFamily: typography.displayBold,
    fontSize: 22,
    color: colors.ink,
  },
  headRow: { flexDirection: 'row', gap: 10 },
  headCol: {
    width: COL_W,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 10,
    ...elevation.soft,
  },
  remove: {
    position: 'absolute',
    top: 8,
    right: 8,
    zIndex: 2,
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: colors.mist,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  image: {
    width: '100%',
    height: 110,
    borderRadius: radii.sm,
    backgroundColor: colors.canvasDeep,
  },
  placeholder: { alignItems: 'center', justifyContent: 'center' },
  brand: {
    marginTop: 8,
    fontFamily: typography.bodyMedium,
    fontSize: 10,
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    color: colors.inkSoft,
  },
  name: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    lineHeight: 17,
    color: colors.ink,
    height: 34,
    marginTop: 2,
  },
  table: {
    marginTop: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    ...elevation.soft,
  },
  rowBlock: {
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
    paddingTop: 8,
    paddingBottom: 4,
  },
  rowLabel: {
    paddingHorizontal: 12,
    marginBottom: 6,
    fontFamily: typography.bodySemi,
    fontSize: 11,
    letterSpacing: 0.4,
    textTransform: 'uppercase',
    color: colors.jade,
  },
  rowValues: { flexDirection: 'row', gap: 10, paddingHorizontal: 10, paddingBottom: 8 },
  cell: { width: COL_W, paddingHorizontal: 2 },
  cellText: {
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    lineHeight: 16,
    color: colors.ink,
  },
  actionsRow: { flexDirection: 'row', gap: 10, marginTop: 12 },
  actionCol: { width: COL_W },
  legend: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: spacing.md,
    marginTop: 10,
  },
  footer: {
    padding: spacing.md,
    backgroundColor: colors.paper,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.borderStrong,
  },
});

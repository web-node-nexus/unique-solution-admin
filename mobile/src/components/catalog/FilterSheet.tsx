import { useQuery } from '@tanstack/react-query';
import { Check, LayoutGrid, RotateCcw, SlidersHorizontal, X } from 'lucide-react-native';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { catalogApi } from '@/api/catalog';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useFilterStore } from '@/store/filters';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr } from '@/utils/price';

function Section({
  title,
  hint,
  children,
}: {
  title: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <View style={styles.section}>
      <View style={styles.sectionHead}>
        <AppText style={styles.sectionTitle}>{title}</AppText>
        {hint ? <AppText style={styles.sectionHint}>{hint}</AppText> : null}
      </View>
      {children}
    </View>
  );
}

function OptionChip({
  label,
  selected,
  onPress,
  leading,
}: {
  label: string;
  selected: boolean;
  onPress: () => void;
  leading?: React.ReactNode;
}) {
  return (
    <PressableScale style={[styles.chip, selected && styles.chipOn]} onPress={onPress}>
      {leading}
      <AppText style={[styles.chipText, selected && styles.chipTextOn]} numberOfLines={1}>
        {label}
      </AppText>
      {selected ? <Check size={12} color={colors.paper} strokeWidth={3} /> : null}
    </PressableScale>
  );
}

export function FilterSheet({
  visible,
  onClose,
  categoryTitle,
}: {
  visible: boolean;
  onClose: () => void;
  /** Optional display name from the products route. */
  categoryTitle?: string;
}) {
  const insets = useSafeAreaInsets();
  const filters = useFilterStore();
  const lockedId = filters.lockedCategoryId;
  const lockedName = filters.lockedCategoryName || categoryTitle || null;
  const facetCategoryId = filters.draft.category_id ?? lockedId;

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['filters', facetCategoryId ?? 'all'],
    queryFn: async () => (await catalogApi.filters(facetCategoryId)).data,
    enabled: visible,
  });

  // Subcategories always relative to the locked route category (not the narrowed draft).
  const { data: lockedFacets } = useQuery({
    queryKey: ['filters', 'locked', lockedId ?? 'none'],
    queryFn: async () => (await catalogApi.filters(lockedId)).data,
    enabled: visible && lockedId != null,
  });

  const draftCount = useMemo(() => {
    const f = filters.draft;
    let n = 0;
    if (f.category_id != null && f.category_id !== lockedId) n += 1;
    if (f.brand_ids.length) n += 1;
    if (f.attribute_value_ids.length) n += 1;
    if (f.min_price != null || f.max_price != null) n += 1;
    if (f.sort && f.sort !== 'newest') n += 1;
    if (f.featured) n += 1;
    return n;
  }, [filters.draft, lockedId]);

  const subcategories =
    lockedFacets?.subcategories ??
    lockedFacets?.categories ??
    data?.subcategories ??
    [];

  const rootCategories = !lockedId ? (data?.categories ?? []) : [];
  const scopeLabel =
    data?.scope?.category_name ||
    lockedName ||
    (facetCategoryId ? 'This category' : null);

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable style={StyleSheet.absoluteFill} onPress={onClose} />
        <View style={[styles.sheet, { paddingBottom: Math.max(insets.bottom, 14) }]}>
          <View style={styles.handle} />

          <View style={styles.header}>
            <View style={styles.headerIcon}>
              <SlidersHorizontal size={18} color={colors.jade} strokeWidth={2.2} />
            </View>
            <View style={{ flex: 1, minWidth: 0 }}>
              <AppText style={styles.headerEyebrow}>
                {lockedId ? 'Refine this category' : 'Refine catalog'}
              </AppText>
              <AppText style={styles.headerTitle}>
                Filters{draftCount > 0 ? ` · ${draftCount}` : ''}
              </AppText>
            </View>
            <PressableScale style={styles.closeBtn} onPress={onClose}>
              <X size={18} color={colors.ink} strokeWidth={2.2} />
            </PressableScale>
          </View>

          {lockedId && scopeLabel ? (
            <View style={styles.scopeBanner}>
              <LayoutGrid size={16} color={colors.jade} strokeWidth={2.2} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText style={styles.scopeLabel}>Showing filters for</AppText>
                <AppText style={styles.scopeName} numberOfLines={1}>
                  {scopeLabel}
                </AppText>
              </View>
            </View>
          ) : null}

          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ gap: 16, paddingBottom: 20 }}
            keyboardShouldPersistTaps="handled"
          >
            {(isLoading || isFetching) && !data ? (
              <ActivityIndicator color={colors.jade} style={{ marginVertical: 12 }} />
            ) : null}

            <Section title="Sort by">
              <View style={styles.sortGrid}>
                {(
                  data?.sort_options ?? [
                    { value: 'newest', label: 'Newest' },
                    { value: 'price_asc', label: 'Price ↑' },
                    { value: 'price_desc', label: 'Price ↓' },
                  ]
                ).map((opt) => {
                  const on = filters.draft.sort === opt.value;
                  return (
                    <PressableScale
                      key={opt.value}
                      style={[styles.sortCell, on && styles.sortCellOn]}
                      onPress={() => filters.setDraft({ sort: opt.value })}
                    >
                      <AppText style={[styles.sortText, on && styles.sortTextOn]} numberOfLines={1}>
                        {opt.label}
                      </AppText>
                    </PressableScale>
                  );
                })}
              </View>
            </Section>

            {lockedId ? (
              subcategories.length > 0 ? (
                <Section title="Subcategory" hint="Within this department">
                  <View style={styles.wrap}>
                    <OptionChip
                      label={lockedName ? `All ${lockedName}` : 'All'}
                      selected={filters.draft.category_id === lockedId}
                      onPress={() => filters.setCategory(lockedId)}
                    />
                    {subcategories.map((c) => (
                      <OptionChip
                        key={c.id}
                        label={c.name}
                        selected={filters.draft.category_id === c.id}
                        onPress={() => filters.setCategory(c.id)}
                      />
                    ))}
                  </View>
                </Section>
              ) : null
            ) : (
              <Section title="Category" hint="Pick one">
                <View style={styles.wrap}>
                  <OptionChip
                    label="All"
                    selected={!filters.draft.category_id}
                    onPress={() => filters.setCategory(null)}
                  />
                  {rootCategories.map((c) => (
                    <OptionChip
                      key={c.id}
                      label={c.name}
                      selected={filters.draft.category_id === c.id}
                      onPress={() => filters.setCategory(c.id)}
                    />
                  ))}
                </View>
              </Section>
            )}

            <Section
              title="Brand"
              hint={
                filters.draft.brand_ids.length
                  ? `${filters.draft.brand_ids.length} selected`
                  : lockedId
                    ? 'In this category'
                    : 'Multi-select'
              }
            >
              <View style={styles.wrap}>
                {(data?.brands ?? []).map((b) => (
                  <OptionChip
                    key={b.id}
                    label={b.name}
                    selected={filters.draft.brand_ids.includes(b.id)}
                    onPress={() => filters.toggleBrand(b.id)}
                  />
                ))}
                {!isLoading && !data?.brands?.length ? (
                  <AppText variant="caption">No brands for this category</AppText>
                ) : null}
              </View>
            </Section>

            {(data?.attributes ?? []).map((attr) => (
              <Section key={attr.id} title={attr.name} hint="This category">
                <View style={styles.wrap}>
                  {attr.values.map((v) => (
                    <OptionChip
                      key={v.id}
                      label={v.value}
                      selected={filters.draft.attribute_value_ids.includes(v.id)}
                      onPress={() => filters.toggleAttributeValue(v.id)}
                      leading={
                        v.hex ? (
                          <View style={[styles.swatch, { backgroundColor: v.hex }]} />
                        ) : undefined
                      }
                    />
                  ))}
                </View>
              </Section>
            ))}

            {!isLoading && lockedId && !(data?.attributes?.length) ? (
              <AppText variant="caption" style={{ paddingHorizontal: 4 }}>
                No attribute filters for this category yet.
              </AppText>
            ) : null}

            <Section
              title="Price range"
              hint={
                data?.price
                  ? `${formatInr(data.price.min)} – ${formatInr(data.price.max)}`
                  : undefined
              }
            >
              <View style={styles.priceCard}>
                <View style={styles.priceField}>
                  <AppText style={styles.priceLabel}>Min</AppText>
                  <TextInput
                    keyboardType="numeric"
                    placeholder={formatInr(data?.price.min ?? 0)}
                    placeholderTextColor={colors.inkSoft}
                    value={
                      filters.draft.min_price != null ? String(filters.draft.min_price) : ''
                    }
                    onChangeText={(t) =>
                      filters.setDraft({ min_price: t ? Number(t) : null })
                    }
                    style={styles.input}
                  />
                </View>
                <View style={styles.priceDivider} />
                <View style={styles.priceField}>
                  <AppText style={styles.priceLabel}>Max</AppText>
                  <TextInput
                    keyboardType="numeric"
                    placeholder={formatInr(data?.price.max ?? 0)}
                    placeholderTextColor={colors.inkSoft}
                    value={
                      filters.draft.max_price != null ? String(filters.draft.max_price) : ''
                    }
                    onChangeText={(t) =>
                      filters.setDraft({ max_price: t ? Number(t) : null })
                    }
                    style={styles.input}
                  />
                </View>
              </View>
            </Section>
          </ScrollView>

          <View style={styles.footer}>
            <PressableScale style={styles.resetBtn} onPress={() => filters.clearAll()}>
              <RotateCcw size={15} color={colors.inkMuted} strokeWidth={2.2} />
              <AppText style={styles.resetText}>Reset</AppText>
            </PressableScale>
            <View style={{ flex: 1 }}>
              <AppButton
                label="Show results"
                onPress={() => {
                  filters.apply();
                  onClose();
                }}
              />
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.canvas,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    maxHeight: '92%',
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    ...elevation.lift,
  },
  handle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.borderStrong,
    marginBottom: 10,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 12,
    paddingBottom: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  headerIcon: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerEyebrow: {
    fontFamily: typography.bodySemi,
    fontSize: 11,
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    color: colors.brassDeep,
  },
  headerTitle: {
    fontFamily: typography.displayBold,
    fontSize: 22,
    color: colors.ink,
    marginTop: 1,
  },
  closeBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  scopeBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.jadeSoft,
    borderRadius: radii.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(15,143,138,0.2)',
  },
  scopeLabel: {
    fontFamily: typography.body,
    fontSize: 11,
    color: colors.jadeDeep,
  },
  scopeName: {
    fontFamily: typography.bodySemi,
    fontSize: 14,
    color: colors.ink,
    marginTop: 1,
  },
  section: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    gap: 10,
    ...elevation.soft,
  },
  sectionHead: {
    flexDirection: 'row',
    alignItems: 'baseline',
    justifyContent: 'space-between',
    gap: 8,
  },
  sectionTitle: {
    fontFamily: typography.bodySemi,
    fontSize: 15,
    color: colors.ink,
  },
  sectionHint: {
    fontFamily: typography.body,
    fontSize: 11,
    color: colors.inkMuted,
  },
  wrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    maxWidth: '100%',
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: radii.pill,
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.border,
  },
  chipOn: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  chipText: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: colors.ink,
    flexShrink: 1,
  },
  chipTextOn: { color: colors.paper, fontFamily: typography.bodySemi },
  sortGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  sortCell: {
    paddingHorizontal: 14,
    paddingVertical: 11,
    borderRadius: radii.md,
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.border,
  },
  sortCellOn: {
    backgroundColor: colors.jadeSoft,
    borderColor: colors.jade,
  },
  sortText: {
    fontFamily: typography.bodyMedium,
    fontSize: 13,
    color: colors.inkMuted,
  },
  sortTextOn: {
    color: colors.jadeDeep,
    fontFamily: typography.bodySemi,
  },
  priceCard: {
    flexDirection: 'row',
    alignItems: 'stretch',
    backgroundColor: colors.canvas,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  priceField: { flex: 1, padding: 10, gap: 4 },
  priceLabel: {
    fontFamily: typography.bodySemi,
    fontSize: 10,
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    color: colors.inkSoft,
  },
  priceDivider: {
    width: StyleSheet.hairlineWidth,
    backgroundColor: colors.borderStrong,
  },
  input: {
    fontFamily: typography.bodySemi,
    fontSize: 16,
    color: colors.ink,
    paddingVertical: 2,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingTop: 10,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  resetBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderRadius: radii.pill,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
  },
  resetText: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.inkMuted,
  },
  swatch: {
    width: 12,
    height: 12,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.12)',
  },
});

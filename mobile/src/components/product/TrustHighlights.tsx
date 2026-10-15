import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import {
  CreditCard,
  RefreshCcw,
  ShieldCheck,
  Truck,
  X,
  type LucideIcon,
} from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { Dimensions, Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, radii, spacing, typography } from '@/theme/tokens';
import { HtmlContent } from './HtmlContent';
import type { PolicyItem } from './ProductPolicyGrid';

const ACCENT = '#2C64E3';

const ICON_MATCHES: { Icon: LucideIcon; match: RegExp }[] = [
  { Icon: ShieldCheck, match: /warrant/i },
  { Icon: Truck, match: /deliver|shipping|free/i },
  { Icon: CreditCard, match: /emi|loan|finance|card/i },
  { Icon: RefreshCcw, match: /replac|return|exchange/i },
];

function pickIcon(title: string): LucideIcon {
  const hit = ICON_MATCHES.find((d) => d.match.test(title));
  return hit?.Icon ?? ShieldCheck;
}

function looksLikeHtml(value: string): boolean {
  return /<\/?[a-z][\s\S]*>/i.test(value);
}

function plainText(value: string): string {
  return value
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

type HighlightItem = {
  key: string;
  title: string;
  description: string | null;
  iconUrl: string | null;
  Icon: LucideIcon;
};

export function TrustHighlights({ policies }: { policies?: PolicyItem[] | null }) {
  const insets = useSafeAreaInsets();
  const [active, setActive] = useState<HighlightItem | null>(null);
  const sheetBodyHeight = Math.min(Math.round(Dimensions.get('window').height * 0.55), 480);

  const items = useMemo<HighlightItem[]>(
    () =>
      (policies ?? [])
        .filter((p) => !!p?.title)
        .map((p) => ({
          key: String(p.id),
          title: p.title,
          description: p.description?.trim() ? p.description : null,
          iconUrl: p.icon_url ?? null,
          Icon: pickIcon(p.title),
        })),
    [policies]
  );

  const openSheet = (item: HighlightItem) => {
    void Haptics.selectionAsync();
    setActive(item);
  };

  if (!items.length) {
    return null;
  }

  const rows: HighlightItem[][] = [];
  for (let i = 0; i < items.length; i += 5) {
    rows.push(items.slice(i, i + 5));
  }

  return (
    <>
      <View style={styles.row}>
        {rows.map((row, rowIndex) => (
          <View key={row[0]?.key ?? rowIndex} style={[styles.line, rowIndex > 0 && styles.lineBreak]}>
            {row.map((item, index) => {
              const Icon = item.Icon;
              return (
                <View key={item.key} style={styles.cell}>
                  {index > 0 ? <View style={styles.divider} /> : null}
                  <View style={styles.inner}>
                    {item.iconUrl ? (
                      <Image source={{ uri: item.iconUrl }} style={styles.iconImg} contentFit="contain" />
                    ) : (
                      <Icon size={26} color={ACCENT} strokeWidth={2.1} />
                    )}
                    <AppText style={styles.label} numberOfLines={3}>
                      {item.title}
                    </AppText>
                    <PressableScale
                      onPress={() => openSheet(item)}
                      hitSlop={8}
                      style={styles.seeMoreHit}
                    >
                      <AppText style={styles.seeMore}>See more</AppText>
                    </PressableScale>
                  </View>
                </View>
              );
            })}
          </View>
        ))}
      </View>

      <Modal visible={!!active} transparent animationType="slide" onRequestClose={() => setActive(null)}>
        <View style={styles.backdrop}>
          <Pressable style={StyleSheet.absoluteFillObject} onPress={() => setActive(null)} />
          <View style={[styles.sheet, { paddingBottom: Math.max(insets.bottom, 16) }]}>
            <View style={styles.sheetHandle} />
            <View style={styles.sheetHead}>
              <AppText style={styles.sheetTitle}>{active?.title}</AppText>
              <PressableScale onPress={() => setActive(null)} style={styles.close}>
                <X size={18} color={colors.ink} strokeWidth={2.2} />
              </PressableScale>
            </View>
            {active?.iconUrl ? (
              <Image source={{ uri: active.iconUrl }} style={styles.sheetIcon} contentFit="contain" />
            ) : null}
            {active?.description ? (
              looksLikeHtml(active.description) ? (
                <HtmlContent
                  html={active.description}
                  framed={false}
                  scrollable
                  maxHeight={sheetBodyHeight}
                />
              ) : (
                <ScrollView
                  style={{ maxHeight: sheetBodyHeight }}
                  nestedScrollEnabled
                  showsVerticalScrollIndicator
                >
                  <AppText style={styles.sheetBody}>{plainText(active.description)}</AppText>
                </ScrollView>
              )
            ) : (
              <AppText style={styles.sheetBody}>No extra details for this policy.</AppText>
            )}
          </View>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  row: {
    marginTop: 18,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: '#E5E7EB',
    paddingVertical: 12,
  },
  line: {
    flexDirection: 'row',
    alignItems: 'stretch',
  },
  lineBreak: {
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: '#E5E7EB',
  },
  cell: {
    width: '20%',
    flexDirection: 'row',
    alignItems: 'stretch',
  },
  divider: {
    width: StyleSheet.hairlineWidth,
    alignSelf: 'stretch',
    backgroundColor: '#D1D5DB',
    marginVertical: 4,
  },
  inner: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 3,
  },
  iconImg: { width: 34, height: 34 },
  label: {
    fontFamily: typography.bodyMedium,
    fontSize: 10,
    lineHeight: 13,
    textAlign: 'center',
    color: colors.inkMuted,
    minHeight: 39,
  },
  seeMoreHit: {
    paddingVertical: 2,
    paddingHorizontal: 4,
  },
  seeMore: {
    fontFamily: typography.bodyMedium,
    fontSize: 11,
    lineHeight: 14,
    color: ACCENT,
    textDecorationLine: 'underline',
  },
  backdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.paper,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    maxHeight: '85%',
  },
  sheetHandle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#D1D5DB',
    marginBottom: 12,
  },
  sheetHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 12,
  },
  sheetTitle: {
    flex: 1,
    fontFamily: typography.bodyBold,
    fontSize: 18,
    color: colors.ink,
  },
  close: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.canvasDeep,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetIcon: {
    width: 56,
    height: 56,
    marginBottom: 12,
  },
  sheetBody: {
    fontFamily: typography.body,
    fontSize: 15,
    lineHeight: 22,
    color: colors.inkMuted,
    paddingBottom: 8,
  },
});

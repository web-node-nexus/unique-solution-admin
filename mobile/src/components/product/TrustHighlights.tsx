import { Image } from 'expo-image';
import {
  CreditCard,
  RefreshCcw,
  ShieldCheck,
  Truck,
  type LucideIcon,
} from 'lucide-react-native';
import { useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import { AppText } from '@/components/ui/primitives';
import { colors, typography } from '@/theme/tokens';
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

export function TrustHighlights({ policies }: { policies?: PolicyItem[] | null }) {
  const items = useMemo(
    () =>
      (policies ?? [])
        .filter((p) => !!p?.title)
        .map((p) => ({
          key: String(p.id),
          title: p.title,
          iconUrl: p.icon_url ?? null,
          Icon: pickIcon(p.title),
        })),
    [policies]
  );

  if (!items.length) {
    return null;
  }

  return (
    <View style={styles.row}>
      {items.map((item, index) => {
        const Icon = item.Icon;
        return (
          <View key={item.key} style={styles.cell}>
            {index > 0 ? <View style={styles.divider} /> : null}
            <View style={styles.inner}>
              {item.iconUrl ? (
                <Image source={{ uri: item.iconUrl }} style={styles.iconImg} contentFit="contain" />
              ) : (
                <Icon size={20} color={ACCENT} strokeWidth={2.1} />
              )}
              <AppText style={styles.label} numberOfLines={2}>
                {item.title}
              </AppText>
            </View>
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    marginTop: 18,
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'stretch',
    borderTopWidth: StyleSheet.hairlineWidth,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: '#E5E7EB',
    paddingVertical: 14,
  },
  cell: {
    flexGrow: 1,
    flexBasis: '22%',
    minWidth: 72,
    maxWidth: '50%',
    flexDirection: 'row',
    alignItems: 'center',
  },
  divider: {
    width: StyleSheet.hairlineWidth,
    alignSelf: 'stretch',
    backgroundColor: '#E5E7EB',
    marginRight: 6,
  },
  inner: {
    flex: 1,
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 4,
  },
  iconImg: { width: 22, height: 22 },
  label: {
    fontFamily: typography.bodyMedium,
    fontSize: 10,
    lineHeight: 13,
    textAlign: 'center',
    color: colors.inkMuted,
  },
});

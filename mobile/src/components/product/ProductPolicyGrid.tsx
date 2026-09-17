import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { ShieldCheck, X } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, radii, spacing, typography } from '@/theme/tokens';

export type PolicyItem = {
  id: number | string;
  title: string;
  description?: string | null;
  icon_url?: string | null;
  source?: string;
};

const ICON_TINTS = ['#E8F1FF', '#E8F8EF', '#FFF4E5', '#F3E8FF'];
const ICON_COLORS = ['#3B82F6', '#16A34A', '#F59E0B', '#8B5CF6'];

export function ProductPolicyGrid({ policies }: { policies: PolicyItem[] }) {
  const insets = useSafeAreaInsets();
  const [active, setActive] = useState<PolicyItem | null>(null);
  const items = useMemo(() => policies.filter((p) => !!p?.title), [policies]);

  if (!items.length) {
    return null;
  }

  return (
    <>
      <View style={styles.section}>
        <AppText style={styles.heading}>POLICIES & SUPPORT</AppText>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.row}
        >
          {items.map((policy, index) => {
            const tint = ICON_TINTS[index % ICON_TINTS.length];
            const iconColor = ICON_COLORS[index % ICON_COLORS.length];
            return (
              <PressableScale
                key={`${policy.source || 'p'}-${policy.id}`}
                style={styles.card}
                onPress={() => {
                  void Haptics.selectionAsync();
                  setActive(policy);
                }}
              >
                <View style={[styles.iconWrap, { backgroundColor: tint }]}>
                  {policy.icon_url ? (
                    <Image source={{ uri: policy.icon_url }} style={styles.icon} contentFit="contain" />
                  ) : (
                    <ShieldCheck size={22} color={iconColor} strokeWidth={2.2} />
                  )}
                </View>
                <AppText style={styles.title} numberOfLines={2}>
                  {policy.title}
                </AppText>
                <AppText style={styles.more}>See More</AppText>
              </PressableScale>
            );
          })}
        </ScrollView>
      </View>

      <Modal visible={!!active} transparent animationType="fade" onRequestClose={() => setActive(null)}>
        <Pressable style={styles.backdrop} onPress={() => setActive(null)}>
          <Pressable
            style={[styles.sheet, { paddingBottom: Math.max(insets.bottom, 16) }]}
            onPress={(e) => e.stopPropagation()}
          >
            <View style={styles.sheetHead}>
              <AppText style={styles.sheetTitle}>{active?.title}</AppText>
              <PressableScale onPress={() => setActive(null)} style={styles.close}>
                <X size={18} color={colors.ink} strokeWidth={2.2} />
              </PressableScale>
            </View>
            <ScrollView style={{ maxHeight: 360 }} showsVerticalScrollIndicator={false}>
              {active?.icon_url ? (
                <Image source={{ uri: active.icon_url }} style={styles.sheetIcon} contentFit="contain" />
              ) : null}
              <AppText style={styles.sheetBody}>
                {(active?.description || 'No extra details for this policy.')
                  .replace(/<[^>]+>/g, ' ')
                  .replace(/\s+/g, ' ')
                  .trim()}
              </AppText>
            </ScrollView>
          </Pressable>
        </Pressable>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  section: {
    marginTop: spacing.xl,
  },
  heading: {
    fontFamily: typography.bodySemi,
    fontSize: 12,
    letterSpacing: 0.8,
    color: '#9CA3AF',
    textTransform: 'uppercase',
    marginBottom: 12,
  },
  row: {
    gap: 10,
    paddingRight: spacing.md,
  },
  card: {
    width: 118,
    minHeight: 148,
    borderRadius: 14,
    paddingHorizontal: 12,
    paddingTop: 14,
    paddingBottom: 12,
    backgroundColor: '#FFFFFF',
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: '#E5E7EB',
  },
  iconWrap: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  icon: { width: 26, height: 26 },
  title: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    lineHeight: 17,
    color: '#111827',
    minHeight: 34,
  },
  more: {
    marginTop: 'auto',
    paddingTop: 10,
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: '#2563EB',
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
    paddingTop: spacing.lg,
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

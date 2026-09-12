import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { ShieldCheck, X } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

export type PolicyItem = {
  id: number | string;
  title: string;
  description?: string | null;
  icon_url?: string | null;
  source?: string;
};

const TINTS = ['#EEF2FF', '#ECFDF5', '#FFF7ED', '#FDF2F8'];

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
        <AppText variant="label">Policies & support</AppText>
        <View style={styles.grid}>
          {items.map((policy, index) => (
            <PressableScale
              key={`${policy.source || 'p'}-${policy.id}`}
              style={[styles.card, { backgroundColor: TINTS[index % TINTS.length] }, elevation.soft]}
              onPress={() => {
                void Haptics.selectionAsync();
                setActive(policy);
              }}
            >
              <View style={styles.iconWrap}>
                {policy.icon_url ? (
                  <Image source={{ uri: policy.icon_url }} style={styles.icon} contentFit="contain" />
                ) : (
                  <ShieldCheck size={22} color={colors.jade} strokeWidth={2} />
                )}
              </View>
              <AppText style={styles.title} numberOfLines={2}>
                {policy.title}
              </AppText>
              <AppText style={styles.more}>See More</AppText>
            </PressableScale>
          ))}
        </View>
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
                {(active?.description || 'No extra details for this policy.').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()}
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
  grid: {
    marginTop: 12,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  card: {
    width: '48%',
    flexGrow: 1,
    minWidth: '46%',
    maxWidth: '48.5%',
    borderRadius: radii.lg,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.border,
    minHeight: 128,
  },
  iconWrap: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  icon: { width: 28, height: 28 },
  title: {
    fontFamily: typography.bodySemi,
    fontSize: 14,
    lineHeight: 18,
    color: colors.ink,
    minHeight: 36,
  },
  more: {
    marginTop: 8,
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: colors.jade,
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

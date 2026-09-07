import { Image } from 'expo-image';
import { router, usePathname } from 'expo-router';
import { GitCompare, X } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { MAX_COMPARE, useCompareStore } from '@/store/compare';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

export function CompareDock() {
  const insets = useSafeAreaInsets();
  const pathname = usePathname();
  const items = useCompareStore((s) => s.items);
  const remove = useCompareStore((s) => s.remove);
  const clear = useCompareStore((s) => s.clear);

  if (!items.length) return null;
  if (pathname?.includes('/compare')) return null;

  return (
    <View
      pointerEvents="box-none"
      style={[styles.wrap, { bottom: Math.max(insets.bottom, 10) + 64 }]}
    >
      <View style={styles.dock}>
        <View style={styles.icon}>
          <GitCompare size={16} color={colors.paper} strokeWidth={2.2} />
        </View>
        <View style={styles.thumbs}>
          {items.map((p) => (
            <PressableScale key={p.id} style={styles.thumb} onPress={() => remove(p.id)}>
              {p.image_url ? (
                <Image source={{ uri: p.image_url }} style={styles.thumbImg} contentFit="cover" />
              ) : (
                <View style={[styles.thumbImg, { backgroundColor: colors.canvasDeep }]} />
              )}
              <View style={styles.thumbX}>
                <X size={10} color={colors.paper} strokeWidth={3} />
              </View>
            </PressableScale>
          ))}
          {Array.from({ length: Math.max(0, MAX_COMPARE - items.length) }).map((_, i) => (
            <View key={`slot-${i}`} style={styles.slot} />
          ))}
        </View>
        <PressableScale style={styles.cta} onPress={() => router.push('/compare')}>
          <AppText style={styles.ctaText}>
            Compare {items.length}/{MAX_COMPARE}
          </AppText>
        </PressableScale>
        <PressableScale onPress={clear} style={styles.clear}>
          <X size={16} color={colors.inkMuted} strokeWidth={2.2} />
        </PressableScale>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: spacing.md,
    right: spacing.md,
    zIndex: 40,
  },
  dock: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.ink,
    borderRadius: radii.pill,
    paddingVertical: 8,
    paddingLeft: 8,
    paddingRight: 6,
    ...elevation.lift,
  },
  icon: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.jade,
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumbs: { flexDirection: 'row', alignItems: 'center', gap: 6, flexShrink: 1 },
  thumb: {
    width: 34,
    height: 34,
    borderRadius: 10,
    overflow: 'hidden',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.35)',
  },
  thumbImg: { width: '100%', height: '100%' },
  thumbX: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15,23,42,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  slot: {
    width: 34,
    height: 34,
    borderRadius: 10,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderColor: 'rgba(255,255,255,0.28)',
  },
  cta: {
    marginLeft: 'auto',
    backgroundColor: colors.jade,
    borderRadius: radii.pill,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  ctaText: {
    color: colors.paper,
    fontFamily: typography.bodySemi,
    fontSize: 12,
  },
  clear: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

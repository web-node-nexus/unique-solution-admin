import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { Megaphone } from 'lucide-react-native';
import { FlatList, StyleSheet, View } from 'react-native';
import { catalogApi } from '@/api/catalog';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, FadeInItem, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { openDeepLink } from '@/utils/deepLink';

export default function NotificationsScreen() {
  const { data, refetch, isRefetching } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await catalogApi.notifications()).data,
  });

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Updates" title="Announcements" />
      <FlatList
        data={data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.lg, gap: 10, paddingBottom: 40 }}
        refreshControl={
          <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <View style={themeCard.emptyIcon}>
              <Megaphone size={28} color={colors.inkSoft} strokeWidth={1.6} />
            </View>
            <AppText variant="caption" style={{ textAlign: 'center' }}>
              No announcements yet.
            </AppText>
          </View>
        }
        renderItem={({ item, index }) => (
          <FadeInItem index={index}>
            <PressableScale
              style={styles.card}
              onPress={() =>
                void openDeepLink({
                  type: item.link_type,
                  value: item.link_value,
                  label: item.title,
                })
              }
            >
              {item.image_url ? (
                <Image source={{ uri: item.image_url }} style={styles.thumb} contentFit="cover" transition={250} />
              ) : (
                <View style={styles.icon}>
                  <Megaphone size={18} color={colors.jade} strokeWidth={2.1} />
                </View>
              )}
              <View style={styles.copy}>
                <AppText style={styles.title} numberOfLines={2}>
                  {item.title}
                </AppText>
                <AppText variant="caption" numberOfLines={4}>
                  {item.body}
                </AppText>
              </View>
            </PressableScale>
          </FadeInItem>
        )}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  empty: { alignItems: 'center', gap: 10, marginTop: 48, paddingHorizontal: spacing.lg },
  card: {
    flexDirection: 'row',
    gap: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  icon: {
    width: 56,
    height: 56,
    borderRadius: radii.md,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  thumb: { width: 56, height: 56, borderRadius: radii.md, backgroundColor: colors.canvasDeep },
  copy: { flex: 1, minWidth: 0 },
  title: { fontFamily: typography.bodySemi, color: colors.ink, marginBottom: 4 },
});

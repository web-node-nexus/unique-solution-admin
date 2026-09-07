import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { ChevronRight, Tag } from 'lucide-react-native';
import { FlatList, StyleSheet, View } from 'react-native';
import { catalogApi } from '@/api/catalog';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppText, FadeInItem, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

export default function BrandsScreen() {
  const { data, refetch, isRefetching } = useQuery({
    queryKey: ['brands'],
    queryFn: async () => (await catalogApi.brands()).data,
  });

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Trusted makers" title="Brands" />
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
              <Tag size={28} color={colors.inkSoft} strokeWidth={1.6} />
            </View>
            <AppText variant="caption">Brands will appear here.</AppText>
          </View>
        }
        renderItem={({ item, index }) => (
          <FadeInItem index={index}>
            <PressableScale
              style={styles.row}
              onPress={() =>
                router.push({
                  pathname: '/products',
                  params: { brand_id: String(item.id), title: item.name },
                })
              }
            >
              {item.logo_url ? (
                <Image source={{ uri: item.logo_url }} style={styles.logo} contentFit="contain" transition={250} />
              ) : (
                <View style={[styles.logo, styles.logoFallback]}>
                  <AppText style={styles.logoLetter}>{item.name.slice(0, 1)}</AppText>
                </View>
              )}
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText style={styles.name} numberOfLines={1}>
                  {item.name}
                </AppText>
                {item.warranty ? (
                  <AppText variant="caption" numberOfLines={1}>
                    Warranty: {item.warranty}
                  </AppText>
                ) : null}
              </View>
              <ChevronRight size={18} color={colors.inkSoft} />
            </PressableScale>
          </FadeInItem>
        )}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  empty: { alignItems: 'center', gap: 10, marginTop: 48 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  logo: { width: 52, height: 52, borderRadius: radii.md },
  logoFallback: {
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoLetter: {
    fontFamily: typography.display,
    fontSize: 20,
    color: colors.jade,
  },
  name: { flex: 1, minWidth: 0, fontFamily: typography.bodySemi, fontSize: 16, color: colors.ink },
});

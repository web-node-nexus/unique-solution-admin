import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { ChevronRight, Package } from 'lucide-react-native';
import { ActivityIndicator, FlatList, StyleSheet, View } from 'react-native';
import { accountApi } from '@/api/account';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, FadeInItem, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr } from '@/utils/price';

export default function OrdersScreen() {
  const user = useAuthStore((s) => s.user);
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['orders'],
    queryFn: async () => (await accountApi.orders()).data,
    enabled: !!user,
  });

  if (!user) {
    return (
      <ScreenShell>
        <ScreenHeader showBack eyebrow="History" title="Your orders" />
        <View style={styles.guest}>
          <View style={themeCard.emptyIcon}>
            <Package size={28} color={colors.inkSoft} strokeWidth={1.6} />
          </View>
          <AppText variant="caption" style={{ textAlign: 'center' }}>
            Sign in to see order history and tracking.
          </AppText>
          <AppButton label="Sign in" onPress={() => router.push('/auth/login')} />
        </View>
      </ScreenShell>
    );
  }

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="History" title="My orders" />

      {isLoading ? <ActivityIndicator color={colors.jade} style={{ marginTop: 40 }} /> : null}

      <FlatList
        data={data ?? []}
        keyExtractor={(item) => String(item.id)}
        refreshControl={
          <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
        }
        contentContainerStyle={{ padding: spacing.lg, gap: 12, paddingBottom: 40 }}
        ListEmptyComponent={
          !isLoading ? (
            <View style={styles.guest}>
              <View style={themeCard.emptyIcon}>
                <Package size={28} color={colors.inkSoft} strokeWidth={1.6} />
              </View>
              <AppText variant="caption" style={{ textAlign: 'center' }}>
                No orders yet. Your COD orders will appear here.
              </AppText>
            </View>
          ) : null
        }
        renderItem={({ item, index }) => (
          <FadeInItem index={index}>
            <PressableScale style={styles.card} onPress={() => router.push(`/orders/${item.id}`)}>
              <View style={styles.icon}>
                <Package size={18} color={colors.jade} strokeWidth={2.1} />
              </View>
              <View style={styles.copy}>
                <AppText style={styles.number} numberOfLines={1}>
                  {item.order_number}
                </AppText>
                <AppText variant="caption" numberOfLines={1}>
                  {item.order_status} · {item.payment_status} · {item.items_count ?? 0} items
                </AppText>
              </View>
              <View style={styles.amountWrap}>
                <AppText style={styles.amount} numberOfLines={1}>
                  {formatInr(item.total_amount)}
                </AppText>
                <ChevronRight size={16} color={colors.inkSoft} />
              </View>
            </PressableScale>
          </FadeInItem>
        )}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  guest: { alignItems: 'center', gap: 12, marginTop: 48, paddingHorizontal: spacing.lg },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  icon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  copy: { flex: 1, minWidth: 0, gap: 2 },
  number: { fontFamily: typography.bodySemi, color: colors.ink },
  amountWrap: { alignItems: 'flex-end', gap: 4, maxWidth: 110 },
  amount: { fontFamily: typography.bodyBold, color: colors.jade },
});

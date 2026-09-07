import { useQuery } from '@tanstack/react-query';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import {
  Bell,
  ChevronRight,
  GitCompare,
  LogOut,
  Mail,
  MapPin,
  MessageCircle,
  Package,
  Percent,
  Phone,
  Tag,
  UserRound,
} from 'lucide-react-native';
import { ActivityIndicator, Linking, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { accountApi } from '@/api/account';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ProductCard } from '@/components/product/ProductCard';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { useCompareStore } from '@/store/compare';
import { useRecentStore } from '@/store/recent';
import { useOnboardingStore } from '@/store/onboarding';
import { useShopStore } from '@/store/shop';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr } from '@/utils/price';

function digitsOnly(value: string): string {
  return value.replace(/\D/g, '');
}

function MenuRow({
  icon: Icon,
  label,
  hint,
  onPress,
  tone = 'default',
}: {
  icon: typeof Package;
  label: string;
  hint?: string;
  onPress: () => void;
  tone?: 'default' | 'danger';
}) {
  return (
    <PressableScale style={styles.menuRow} onPress={onPress}>
      <View
        style={[
          styles.menuIcon,
          tone === 'danger' && { backgroundColor: 'rgba(229,72,77,0.1)' },
        ]}
      >
        <Icon
          size={17}
          color={tone === 'danger' ? colors.danger : colors.jade}
          strokeWidth={2.1}
        />
      </View>
      <View style={{ flex: 1, minWidth: 0 }}>
        <AppText style={styles.menuLabel}>{label}</AppText>
        {hint ? (
          <AppText style={styles.menuHint} numberOfLines={1}>
            {hint}
          </AppText>
        ) : null}
      </View>
      <ChevronRight size={16} color={colors.inkSoft} />
    </PressableScale>
  );
}

export default function AccountScreen() {
  const insets = useSafeAreaInsets();
  const { user, logout } = useAuthStore();
  const recent = useRecentStore((s) => s.items);
  const compareCount = useCompareStore((s) => s.items.length);
  const shop = useShopStore((s) => s.shop);
  const resetOnboarding = useOnboardingStore((s) => s.reset);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => (await accountApi.dashboard()).data,
    enabled: !!user,
  });

  const phone = shop?.contact_number?.trim() || null;
  const whatsapp = (shop?.whatsapp_number || shop?.contact_number)?.trim() || null;
  const email = shop?.contact_email?.trim() || null;
  const address = shop?.shop_address?.trim() || null;
  const hasContact = !!(phone || whatsapp || email || address);
  const firstName = user?.name?.split(' ')[0] ?? 'Guest';

  const onRefresh = async () => {
    await Promise.all([
      user ? refetch() : Promise.resolve(),
      useShopStore.getState().load(),
    ]);
  };

  return (
    <ScreenAtmosphere>
      <ScrollView
        style={{ flex: 1, paddingTop: insets.top + 8 }}
        contentContainerStyle={{ paddingBottom: 130 }}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode="on-drag"
        showsVerticalScrollIndicator={false}
        refreshControl={
          <AppRefreshControl refreshing={isRefetching} onRefresh={onRefresh} />
        }
      >
        <ScreenHeader showMenu eyebrow="Member desk" title="Account" />

        {/* Profile hero */}
        <LinearGradient
          colors={['#0E3B55', '#0F5C6E', '#0F8F8A']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.hero}
        >
          <View style={styles.avatar}>
            <AppText style={styles.avatarText}>
              {(user?.name || 'G').charAt(0).toUpperCase()}
            </AppText>
          </View>
          <View style={{ flex: 1, minWidth: 0 }}>
            <AppText style={styles.heroHi}>Hello</AppText>
            <AppText style={styles.heroName} numberOfLines={1}>
              {firstName}
            </AppText>
            {user?.email ? (
              <AppText style={styles.heroEmail} numberOfLines={1}>
                {user.email}
              </AppText>
            ) : (
              <AppText style={styles.heroEmail}>Sign in to sync orders & addresses</AppText>
            )}
          </View>
        </LinearGradient>

        {!user ? (
          <View style={styles.guestCard}>
            <AppText style={styles.guestTitle}>Shop with your account</AppText>
            <AppText style={styles.guestCaption}>
              Track orders, save addresses, and checkout faster with COD or UPI.
            </AppText>
            <AppButton label="Sign in" onPress={() => router.push('/auth/login')} />
            <AppButton
              label="Create account"
              variant="ghost"
              onPress={() => router.push('/auth/register')}
            />
          </View>
        ) : (
          <>
            {isLoading ? (
              <ActivityIndicator color={colors.jade} style={{ marginVertical: 12 }} />
            ) : null}
            <View style={styles.stats}>
              {[
                { label: 'Orders', value: data?.stats.orders_count ?? 0 },
                { label: 'Active', value: data?.stats.pending_count ?? 0 },
                { label: 'Delivered', value: data?.stats.delivered_count ?? 0 },
              ].map((s) => (
                <PressableScale
                  key={s.label}
                  style={styles.stat}
                  onPress={() => router.push('/orders')}
                >
                  <AppText style={styles.statValue}>{s.value}</AppText>
                  <AppText style={styles.statLabel}>{s.label}</AppText>
                </PressableScale>
              ))}
            </View>

            {(data?.recent_orders ?? []).length ? (
              <View style={styles.block}>
                <View style={styles.blockHead}>
                  <AppText style={styles.blockTitle}>Recent orders</AppText>
                  <PressableScale onPress={() => router.push('/orders')}>
                    <AppText style={styles.blockLink}>See all</AppText>
                  </PressableScale>
                </View>
                {data!.recent_orders.slice(0, 3).map((o) => (
                  <PressableScale
                    key={o.id}
                    style={styles.orderRow}
                    onPress={() => router.push(`/orders/${o.id}`)}
                  >
                    <View style={styles.orderIcon}>
                      <Package size={16} color={colors.jade} strokeWidth={2} />
                    </View>
                    <View style={{ flex: 1, minWidth: 0 }}>
                      <AppText style={styles.orderNo} numberOfLines={1}>
                        {o.order_number}
                      </AppText>
                      <AppText style={styles.orderStatus} numberOfLines={1}>
                        {o.order_status}
                      </AppText>
                    </View>
                    <AppText style={styles.amount}>{formatInr(o.total_amount)}</AppText>
                  </PressableScale>
                ))}
              </View>
            ) : null}
          </>
        )}

        <View style={styles.block}>
          <AppText style={styles.blockTitle}>Quick links</AppText>
          <View style={styles.menuCard}>
            <MenuRow
              icon={UserRound}
              label="Edit profile"
              hint="Name, phone, password"
              onPress={() => router.push('/profile')}
            />
            <MenuRow
              icon={Package}
              label="My orders"
              hint="Track & reorder"
              onPress={() => router.push('/orders')}
            />
            <MenuRow
              icon={MapPin}
              label="Saved addresses"
              hint="Home, office & more"
              onPress={() => router.push('/addresses')}
            />
            <MenuRow
              icon={GitCompare}
              label="Compare list"
              hint={
                compareCount
                  ? `${compareCount} product${compareCount === 1 ? '' : 's'} ready`
                  : 'Add up to 3 products'
              }
              onPress={() => router.push('/compare')}
            />
            <MenuRow
              icon={Percent}
              label="Deals & coupons"
              onPress={() => router.push('/deals')}
            />
            <MenuRow
              icon={Bell}
              label="Announcements"
              onPress={() => router.push('/notifications')}
            />
            <MenuRow
              icon={Tag}
              label="Browse brands"
              onPress={() => router.push('/brands')}
            />
          </View>
        </View>

        {hasContact ? (
          <View style={styles.block}>
            <AppText style={styles.blockTitle}>Shop contact</AppText>
            <View style={styles.contactCard}>
              {shop?.shop_name ? (
                <AppText style={styles.contactShop} numberOfLines={1}>
                  {shop.shop_name}
                </AppText>
              ) : null}
              {phone ? (
                <PressableScale
                  style={styles.contactRow}
                  onPress={() => void Linking.openURL(`tel:${digitsOnly(phone)}`)}
                >
                  <Phone size={16} color={colors.jade} strokeWidth={2} />
                  <AppText style={styles.contactText} numberOfLines={1}>
                    {phone}
                  </AppText>
                </PressableScale>
              ) : null}
              {whatsapp ? (
                <PressableScale
                  style={styles.contactRow}
                  onPress={() => void Linking.openURL(`https://wa.me/${digitsOnly(whatsapp)}`)}
                >
                  <MessageCircle size={16} color={colors.jade} strokeWidth={2} />
                  <AppText style={styles.contactText} numberOfLines={1}>
                    WhatsApp {whatsapp}
                  </AppText>
                </PressableScale>
              ) : null}
              {email ? (
                <PressableScale
                  style={styles.contactRow}
                  onPress={() => void Linking.openURL(`mailto:${email}`)}
                >
                  <Mail size={16} color={colors.jade} strokeWidth={2} />
                  <AppText style={styles.contactText} numberOfLines={1}>
                    {email}
                  </AppText>
                </PressableScale>
              ) : null}
              {address ? (
                <View style={styles.contactRow}>
                  <MapPin size={16} color={colors.jade} strokeWidth={2} />
                  <AppText style={styles.contactText} numberOfLines={3}>
                    {address}
                  </AppText>
                </View>
              ) : null}
            </View>
          </View>
        ) : null}

        {recent.length ? (
          <View style={styles.block}>
            <AppText style={styles.blockTitle}>Recently viewed</AppText>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ gap: 12, paddingVertical: 4 }}
            >
              {recent.map((p) => (
                <View key={p.id} style={{ width: 160 }}>
                  <ProductCard product={p} />
                </View>
              ))}
            </ScrollView>
          </View>
        ) : null}

        <View style={{ paddingHorizontal: spacing.lg, gap: 8, marginTop: 4 }}>
          <AppButton
            label="Replay intro"
            variant="ghost"
            onPress={() => {
              resetOnboarding();
              router.replace('/(onboarding)');
            }}
          />
          {user ? (
            <AppButton
              label="Sign out"
              variant="ghost"
              icon={<LogOut size={16} color={colors.danger} />}
              onPress={async () => {
                await logout();
              }}
            />
          ) : null}
        </View>
      </ScrollView>
    </ScreenAtmosphere>
  );
}

const styles = StyleSheet.create({
  hero: {
    marginHorizontal: spacing.lg,
    borderRadius: radii.lg,
    padding: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    marginBottom: spacing.md,
    ...elevation.soft,
  },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontFamily: typography.displayBold,
    fontSize: 24,
    color: colors.paper,
  },
  heroHi: {
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: 'rgba(255,255,255,0.72)',
  },
  heroName: {
    fontFamily: typography.displayBold,
    fontSize: 24,
    color: colors.paper,
    marginTop: 1,
  },
  heroEmail: {
    fontFamily: typography.body,
    fontSize: 12,
    color: 'rgba(255,255,255,0.78)',
    marginTop: 2,
  },
  guestCard: {
    marginHorizontal: spacing.lg,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.lg,
    marginBottom: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 8,
    ...elevation.soft,
  },
  guestTitle: {
    fontFamily: typography.displayBold,
    fontSize: 20,
    color: colors.ink,
  },
  guestCaption: {
    fontFamily: typography.body,
    fontSize: 13,
    lineHeight: 19,
    color: colors.inkMuted,
    marginBottom: 6,
  },
  stats: {
    flexDirection: 'row',
    gap: 10,
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
  },
  stat: {
    flex: 1,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    paddingVertical: 14,
    paddingHorizontal: 10,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    ...elevation.soft,
  },
  statValue: {
    fontFamily: typography.displayBold,
    fontSize: 24,
    color: colors.ink,
  },
  statLabel: {
    fontFamily: typography.bodyMedium,
    fontSize: 11,
    color: colors.inkMuted,
    marginTop: 2,
  },
  block: {
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
    gap: 10,
  },
  blockHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  blockTitle: {
    fontFamily: typography.bodySemi,
    fontSize: 16,
    color: colors.ink,
  },
  blockLink: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.jade,
  },
  orderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  orderIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderNo: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 14 },
  orderStatus: {
    fontFamily: typography.body,
    fontSize: 12,
    color: colors.inkMuted,
    marginTop: 2,
    textTransform: 'capitalize',
  },
  amount: { fontFamily: typography.bodyBold, color: colors.jade },
  menuCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    ...elevation.soft,
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 12,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  menuIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuLabel: { fontFamily: typography.bodySemi, fontSize: 14, color: colors.ink },
  menuHint: {
    fontFamily: typography.body,
    fontSize: 11,
    color: colors.inkMuted,
    marginTop: 2,
  },
  contactCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 10,
    ...elevation.soft,
  },
  contactShop: { fontFamily: typography.displayBold, color: colors.ink, fontSize: 18 },
  contactRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  contactText: {
    flex: 1,
    minWidth: 0,
    fontFamily: typography.bodyMedium,
    color: colors.inkMuted,
  },
});

import { router } from 'expo-router';
import {
  Bell,
  Building2,
  // GitCompare, // Compare temporarily disabled
  Heart,
  MapPin,
  Package,
  Percent,
  Search,
  X,
} from 'lucide-react-native';
import { ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { useWishlistStore } from '@/store/cart';
// import { useCompareStore } from '@/store/compare';
import { useDrawerStore } from '@/store/drawer';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

const links = [
  { label: 'Search', href: '/search', icon: Search },
  { label: 'Wishlist', href: '/wishlist', icon: Heart },
  // { label: 'Compare', href: '/compare', icon: GitCompare },
  { label: 'My orders', href: '/orders', icon: Package },
  { label: 'Addresses', href: '/addresses', icon: MapPin },
  { label: 'Deals & coupons', href: '/deals', icon: Percent },
  { label: 'Brands', href: '/brands', icon: Building2 },
  { label: 'Announcements', href: '/notifications', icon: Bell },
] as const;

export function AppDrawerContent() {
  const insets = useSafeAreaInsets();
  const user = useAuthStore((s) => s.user);
  const wishlistCount = useWishlistStore((s) => s.items.length);
  // const compareCount = useCompareStore((s) => s.items.length);
  const closeDrawer = useDrawerStore((s) => s.closeDrawer);

  const go = (href: string) => {
    closeDrawer();
    router.push(href as never);
  };

  return (
    <View style={[styles.root, { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 12 }]}>
      <View style={styles.head}>
        <View style={{ flex: 1, minWidth: 0 }}>
          <AppText variant="label">Unique Solution</AppText>
          <AppText
            variant="display"
            style={{ fontSize: 26, lineHeight: 32 }}
            numberOfLines={1}
          >
            {user ? user.name.split(' ')[0] : 'Guest'}
          </AppText>
          <AppText variant="caption" numberOfLines={1}>
            {user?.email ?? 'Sign in for orders & delivery'}
          </AppText>
        </View>
        <PressableScale style={styles.close} onPress={closeDrawer}>
          <X size={18} color={colors.ink} />
        </PressableScale>
      </View>

      <ScrollView contentContainerStyle={{ paddingTop: 8, paddingBottom: 16 }}>
        {links.map((item) => {
          const Icon = item.icon;
          let badge = '';
          if (item.href === '/wishlist' && wishlistCount > 0) badge = ` · ${wishlistCount}`;
          // if (item.href === '/compare' && compareCount > 0) badge = ` · ${compareCount}`;
          return (
            <PressableScale
              key={item.href}
              onPress={() => go(item.href)}
              style={styles.item}
            >
              <Icon color={colors.ink} size={22} strokeWidth={2} />
              <AppText numberOfLines={1} style={styles.label}>
                {`${item.label}${badge}`}
              </AppText>
            </PressableScale>
          );
        })}
      </ScrollView>

      <View style={styles.footer}>
        {!user ? (
          <PressableScale style={styles.cta} onPress={() => go('/auth/login')}>
            <AppText style={styles.ctaText}>Sign in</AppText>
          </PressableScale>
        ) : (
          <AppText variant="caption" style={{ textAlign: 'center' }}>
            Swipe from the left edge anytime for this menu
          </AppText>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas },
  head: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  close: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    ...elevation.soft,
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    marginHorizontal: 8,
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderRadius: radii.md,
  },
  label: { fontFamily: typography.bodyMedium, fontSize: 15, color: colors.ink, flex: 1 },
  footer: { paddingHorizontal: spacing.lg, paddingTop: spacing.sm },
  cta: {
    backgroundColor: colors.jade,
    borderRadius: radii.pill,
    paddingVertical: 15,
    alignItems: 'center',
    ...elevation.soft,
  },
  ctaText: { color: colors.paper, fontFamily: typography.bodySemi, fontSize: 15 },
});

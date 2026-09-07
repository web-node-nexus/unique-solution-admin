import { Heart } from 'lucide-react-native';
import { FlatList, StyleSheet, View } from 'react-native';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { ProductCard } from '@/components/product/ProductCard';
import { AppRefreshControl, usePullRefresh } from '@/components/ui/AppRefreshControl';
import { AppText } from '@/components/ui/primitives';
import { useWishlistStore } from '@/store/cart';
import { colors, spacing } from '@/theme/tokens';

export default function WishlistScreen() {
  const items = useWishlistStore((s) => s.items);
  const hydrate = useWishlistStore((s) => s.hydrateFromServer);
  const { refreshing, onRefresh } = usePullRefresh(() => hydrate());

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Saved" title="Wishlist" />
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={{ gap: 12 }}
        contentContainerStyle={{ padding: spacing.lg, gap: 12, paddingBottom: 40 }}
        refreshControl={
          <AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <View style={themeCard.emptyIcon}>
              <Heart size={28} color={colors.inkSoft} strokeWidth={1.7} />
            </View>
            <AppText variant="caption" style={{ textAlign: 'center' }}>
              Heart a product to keep it here.
            </AppText>
          </View>
        }
        renderItem={({ item }) => (
          <View style={{ flex: 1, minWidth: 0 }}>
            <ProductCard product={item} />
          </View>
        )}
      />
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  empty: { alignItems: 'center', gap: 10, marginTop: 48 },
});

import { Tabs, router } from 'expo-router';
import { Home, LayoutGrid, Search, ShoppingBag, UserRound } from 'lucide-react-native';
import { Platform, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useCartStore } from '@/store/cart';
import { colors, elevation, typography } from '@/theme/tokens';

export default function TabsLayout() {
  const insets = useSafeAreaInsets();
  const count = useCartStore((s) => s.lines.reduce((sum, l) => sum + l.qty, 0));
  const bottom = Math.max(insets.bottom, 10);

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarHideOnKeyboard: true,
          tabBarActiveTintColor: colors.jade,
          tabBarInactiveTintColor: colors.inkSoft,
          tabBarStyle: {
            position: 'absolute',
            left: 14,
            right: 14,
            bottom: bottom > 16 ? bottom - 6 : 12,
            height: 64,
            borderRadius: 28,
            backgroundColor: colors.paper,
            borderTopWidth: 0,
            borderWidth: 1,
            borderColor: colors.border,
            paddingBottom: 8,
            paddingTop: 8,
            ...elevation.lift,
            ...Platform.select({ android: { elevation: 12 } }),
          },
          tabBarLabelStyle: {
            fontFamily: typography.bodyMedium,
            fontSize: 10,
          },
        }}
      >
        <Tabs.Screen
          name="index"
          options={{
            title: 'Home',
            tabBarIcon: ({ color, focused }) => (
              <Home color={color} size={22} strokeWidth={focused ? 2.4 : 1.9} />
            ),
          }}
        />
        <Tabs.Screen
          name="categories/index"
          options={{
            title: 'Categories',
            tabBarIcon: ({ color, focused }) => (
              <LayoutGrid color={color} size={22} strokeWidth={focused ? 2.4 : 1.9} />
            ),
          }}
        />
        <Tabs.Screen
          name="cart/index"
          options={{
            title: 'Cart',
            tabBarBadge: count > 0 ? count : undefined,
            tabBarBadgeStyle: {
              backgroundColor: colors.accent,
              fontSize: 10,
              fontFamily: typography.bodySemi,
            },
            tabBarIcon: ({ color, focused }) => (
              <ShoppingBag color={color} size={22} strokeWidth={focused ? 2.4 : 1.9} />
            ),
          }}
        />
        <Tabs.Screen
          name="account/index"
          options={{
            title: 'Account',
            tabBarIcon: ({ color, focused }) => (
              <UserRound color={color} size={22} strokeWidth={focused ? 2.4 : 1.9} />
            ),
          }}
        />
      </Tabs>

      <Pressable
        onPress={() => router.push('/search')}
        style={[
          styles.fab,
          {
            bottom: (bottom > 16 ? bottom - 6 : 12) + 28,
          },
        ]}
      >
        <Search size={22} color={colors.paper} strokeWidth={2.2} />
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  fab: {
    position: 'absolute',
    alignSelf: 'center',
    width: 58,
    height: 58,
    borderRadius: 29,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 30,
    ...elevation.lift,
    shadowColor: colors.accent,
    shadowOpacity: 0.45,
    shadowRadius: 16,
  },
});

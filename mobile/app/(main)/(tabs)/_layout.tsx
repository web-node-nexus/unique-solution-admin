import { Tabs } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { Home, LayoutGrid, ShoppingBag, UserRound } from 'lucide-react-native';
import { Platform, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useCartStore } from '@/store/cart';
import { colors, elevation, typography } from '@/theme/tokens';

export default function TabsLayout() {
  const insets = useSafeAreaInsets();
  const count = useCartStore((s) => s.lines.reduce((sum, l) => sum + l.qty, 0));
  const bottomPad = Math.max(insets.bottom, 8);

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarHideOnKeyboard: true,
          tabBarActiveTintColor: colors.paper,
          tabBarInactiveTintColor: 'rgba(255,255,255,0.72)',
          tabBarBackground: () => (
            <View style={StyleSheet.absoluteFillObject} pointerEvents="none">
              <LinearGradient
                colors={[colors.jadeDeep, colors.jade, '#12C8B8']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={StyleSheet.absoluteFillObject}
              />
              <View style={styles.topEdge} />
            </View>
          ),
          tabBarStyle: {
            position: 'absolute',
            left: 12,
            right: 12,
            bottom: bottomPad > 14 ? bottomPad - 4 : 10,
            height: 64,
            borderRadius: 28,
            backgroundColor: colors.jadeDeep,
            borderTopWidth: 0,
            borderWidth: 0,
            paddingBottom: 6,
            paddingTop: 8,
            overflow: 'hidden',
            ...elevation.lift,
            shadowColor: colors.jadeDeep,
            shadowOpacity: 0.35,
            ...Platform.select({ android: { elevation: 18 } }),
          },
          tabBarLabelStyle: {
            fontFamily: typography.bodySemi,
            fontSize: 10,
            marginTop: 1,
          },
          tabBarItemStyle: {
            borderRadius: 16,
            marginHorizontal: 2,
          },
        }}
      >
        <Tabs.Screen
          name="index"
          options={{
            title: 'Home',
            tabBarIcon: ({ focused }) => (
              <View style={[styles.iconWrap, focused && styles.iconWrapOn]}>
                <Home
                  color={focused ? colors.jadeDeep : 'rgba(255,255,255,0.85)'}
                  size={21}
                  strokeWidth={focused ? 2.5 : 1.9}
                />
              </View>
            ),
          }}
        />
        <Tabs.Screen
          name="categories/index"
          options={{
            title: 'Categories',
            tabBarIcon: ({ focused }) => (
              <View style={[styles.iconWrap, focused && styles.iconWrapOn]}>
                <LayoutGrid
                  color={focused ? colors.jadeDeep : 'rgba(255,255,255,0.85)'}
                  size={21}
                  strokeWidth={focused ? 2.5 : 1.9}
                />
              </View>
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
              color: colors.paper,
              fontSize: 10,
              fontFamily: typography.bodySemi,
            },
            tabBarIcon: ({ focused }) => (
              <View style={[styles.iconWrap, focused && styles.iconWrapOn]}>
                <ShoppingBag
                  color={focused ? colors.jadeDeep : 'rgba(255,255,255,0.85)'}
                  size={21}
                  strokeWidth={focused ? 2.5 : 1.9}
                />
              </View>
            ),
          }}
        />
        <Tabs.Screen
          name="account/index"
          options={{
            title: 'Account',
            tabBarIcon: ({ focused }) => (
              <View style={[styles.iconWrap, focused && styles.iconWrapOn]}>
                <UserRound
                  color={focused ? colors.jadeDeep : 'rgba(255,255,255,0.85)'}
                  size={21}
                  strokeWidth={focused ? 2.5 : 1.9}
                />
              </View>
            ),
          }}
        />
      </Tabs>
    </View>
  );
}

const styles = StyleSheet.create({
  topEdge: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 3,
    backgroundColor: colors.brass,
  },
  iconWrap: {
    width: 34,
    height: 28,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconWrapOn: {
    backgroundColor: colors.paper,
  },
});

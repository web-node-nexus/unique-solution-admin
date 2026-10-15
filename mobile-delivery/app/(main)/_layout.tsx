import { Tabs } from 'expo-router';
import { CheckCircle2, Package, UserRound } from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '@/theme/tokens';

export default function MainTabs() {
  const insets = useSafeAreaInsets();
  const bottomInset = Math.max(insets.bottom, 8);
  const tabBarHeight = 52 + bottomInset;

  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.paper },
        headerShadowVisible: false,
        headerTitleStyle: { fontWeight: '800', color: colors.ink, fontSize: 18 },
        tabBarActiveTintColor: colors.sky,
        tabBarInactiveTintColor: colors.inkMuted,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          backgroundColor: colors.paper,
          borderTopColor: colors.border,
          height: tabBarHeight,
          paddingTop: 6,
          paddingBottom: bottomInset,
        },
        tabBarItemStyle: {
          paddingTop: 2,
        },
        tabBarLabelStyle: { fontWeight: '700', fontSize: 11, marginBottom: 2 },
      }}
    >
      <Tabs.Screen
        name="pending"
        options={{
          title: 'Pending',
          tabBarIcon: ({ color, size }) => <Package size={size} color={color} strokeWidth={2.2} />,
        }}
      />
      <Tabs.Screen
        name="delivered"
        options={{
          title: 'Delivered',
          tabBarIcon: ({ color, size }) => <CheckCircle2 size={size} color={color} strokeWidth={2.2} />,
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profile',
          tabBarIcon: ({ color, size }) => <UserRound size={size} color={color} strokeWidth={2.2} />,
        }}
      />
    </Tabs>
  );
}

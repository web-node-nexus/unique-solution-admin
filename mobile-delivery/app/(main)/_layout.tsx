import { Tabs } from 'expo-router';
import { CheckCircle2, Package, UserRound } from 'lucide-react-native';
import { Platform } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '@/theme/tokens';

export default function MainTabs() {
  const insets = useSafeAreaInsets();
  // Extra room so labels never clip above Android gesture / 3-button nav.
  const bottomInset = Math.max(insets.bottom, Platform.OS === 'android' ? 16 : 8);
  const contentHeight = 60;
  const tabBarHeight = contentHeight + bottomInset;

  return (
    <Tabs
      safeAreaInsets={{ bottom: 0 }}
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
          paddingTop: 8,
          paddingBottom: bottomInset,
        },
        tabBarItemStyle: {
          height: contentHeight,
          paddingTop: 4,
          paddingBottom: 4,
        },
        tabBarLabelStyle: {
          fontWeight: '700',
          fontSize: 11,
          lineHeight: 14,
          marginTop: 2,
          marginBottom: 0,
        },
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

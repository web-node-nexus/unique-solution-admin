import { Tabs } from 'expo-router';
import { CheckCircle2, Package, UserRound } from 'lucide-react-native';
import { colors } from '@/theme/tokens';

export default function MainTabs() {
  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.paper },
        headerShadowVisible: false,
        headerTitleStyle: { fontWeight: '800', color: colors.ink, fontSize: 18 },
        tabBarActiveTintColor: colors.sky,
        tabBarInactiveTintColor: colors.inkMuted,
        tabBarStyle: {
          backgroundColor: colors.paper,
          borderTopColor: colors.border,
          height: 64,
          paddingTop: 6,
          paddingBottom: 8,
        },
        tabBarLabelStyle: { fontWeight: '700', fontSize: 11 },
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

import { Tabs } from 'expo-router';
import { CheckCircle2, Package, User } from 'lucide-react-native';
import { colors } from '@/theme/tokens';

export default function MainTabs() {
  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.paper },
        headerTitleStyle: { fontWeight: '700', color: colors.ink },
        tabBarActiveTintColor: colors.sky,
        tabBarInactiveTintColor: colors.inkMuted,
        tabBarStyle: { backgroundColor: colors.paper, borderTopColor: colors.border },
      }}
    >
      <Tabs.Screen
        name="pending"
        options={{
          title: 'Pending',
          tabBarIcon: ({ color, size }) => <Package size={size} color={color} strokeWidth={2.1} />,
        }}
      />
      <Tabs.Screen
        name="delivered"
        options={{
          title: 'Delivered',
          tabBarIcon: ({ color, size }) => <CheckCircle2 size={size} color={color} strokeWidth={2.1} />,
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profile',
          tabBarIcon: ({ color, size }) => <User size={size} color={color} strokeWidth={2.1} />,
        }}
      />
    </Tabs>
  );
}

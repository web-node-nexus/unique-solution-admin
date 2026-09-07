import { Drawer } from 'expo-router/drawer';
import { AppDrawerContent } from '@/components/layout/AppDrawer';
import { colors } from '@/theme/tokens';

export default function MainDrawerLayout() {
  return (
    <Drawer
      drawerContent={(props) => <AppDrawerContent {...props} />}
      screenOptions={{
        headerShown: false,
        drawerType: 'front',
        swipeEnabled: true,
        swipeEdgeWidth: 56,
        overlayColor: colors.overlay,
        drawerStyle: {
          width: 312,
          backgroundColor: colors.stone,
        },
      }}
    >
      <Drawer.Screen
        name="(tabs)"
        options={{
          title: 'Shop',
          drawerItemStyle: { display: 'none' },
        }}
      />
    </Drawer>
  );
}

import { Slot } from 'expo-router';
import { Drawer } from 'react-native-drawer-layout';
import { AppDrawerContent } from '@/components/layout/AppDrawer';
import { useDrawerStore } from '@/store/drawer';
import { colors } from '@/theme/tokens';

export default function MainDrawerLayout() {
  const open = useDrawerStore((s) => s.open);
  const openDrawer = useDrawerStore((s) => s.openDrawer);
  const closeDrawer = useDrawerStore((s) => s.closeDrawer);

  return (
    <Drawer
      open={open}
      onOpen={openDrawer}
      onClose={closeDrawer}
      swipeEnabled={true}
      swipeEdgeWidth={56}
      drawerType="front"
      overlayStyle={{ backgroundColor: colors.overlay }}
      drawerStyle={{
        width: 312,
        backgroundColor: colors.stone,
      }}
      renderDrawerContent={() => <AppDrawerContent />}
    >
      <Slot />
    </Drawer>
  );
}

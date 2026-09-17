import { Menu } from 'lucide-react-native';
import { IconButton } from '@/components/ui/primitives';
import { useDrawerStore } from '@/store/drawer';
import { colors } from '@/theme/tokens';

export function MenuButton({ tone = 'dark' }: { tone?: 'dark' | 'light' }) {
  const openDrawer = useDrawerStore((s) => s.openDrawer);
  const light = tone === 'light';

  return (
    <IconButton
      onPress={openDrawer}
      style={
        light
          ? {
              backgroundColor: 'rgba(255,255,255,0.14)',
              borderColor: 'rgba(255,255,255,0.22)',
            }
          : undefined
      }
    >
      <Menu size={20} color={light ? colors.paper : colors.ink} strokeWidth={2.2} />
    </IconButton>
  );
}

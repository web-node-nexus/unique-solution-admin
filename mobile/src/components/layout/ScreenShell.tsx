import type { ReactElement, ReactNode } from 'react';
import {
  ScrollView,
  StyleSheet,
  View,
  type RefreshControlProps,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ScreenAtmosphere } from '@/components/layout/ScreenAtmosphere';
import { colors, elevation, radii } from '@/theme/tokens';

/** Consistent light-retail chrome for every stack/tab screen. */
export function ScreenShell({
  children,
  style,
  scroll = false,
  contentContainerStyle,
  bottomPad = 120,
  refreshControl,
}: {
  children: ReactNode;
  style?: StyleProp<ViewStyle>;
  scroll?: boolean;
  contentContainerStyle?: StyleProp<ViewStyle>;
  bottomPad?: number;
  refreshControl?: ReactElement<RefreshControlProps>;
}) {
  const insets = useSafeAreaInsets();

  if (scroll) {
    return (
      <ScreenAtmosphere style={style}>
        <ScrollView
          style={{ flex: 1, paddingTop: insets.top + 8 }}
          contentContainerStyle={[{ paddingBottom: bottomPad }, contentContainerStyle]}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          showsVerticalScrollIndicator={false}
          refreshControl={refreshControl}
        >
          {children}
        </ScrollView>
      </ScreenAtmosphere>
    );
  }

  return (
    <ScreenAtmosphere style={[{ paddingTop: insets.top + 8 }, style]}>
      {children}
    </ScreenAtmosphere>
  );
}

export const themeCard = StyleSheet.create({
  panel: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  input: {
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 14,
    paddingVertical: 13,
    ...elevation.soft,
  },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: radii.md,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
    ...elevation.soft,
  },
});

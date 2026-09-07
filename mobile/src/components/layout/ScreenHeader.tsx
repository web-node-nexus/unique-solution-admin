import type { ReactNode } from 'react';
import { router } from 'expo-router';
import { ArrowLeft, LucideIcon } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import { MenuButton } from '@/components/layout/MenuButton';
import { AppText, IconButton } from '@/components/ui/primitives';
import { colors, spacing, typography } from '@/theme/tokens';

type Props = {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  showMenu?: boolean;
  showBack?: boolean;
  onBack?: () => void;
  right?: ReactNode;
  RightIcon?: LucideIcon;
  onRightPress?: () => void;
};

export function ScreenHeader({
  eyebrow,
  title,
  subtitle,
  showMenu,
  showBack,
  onBack,
  right,
  RightIcon,
  onRightPress,
}: Props) {
  return (
    <View style={styles.wrap}>
      {showMenu ? <MenuButton /> : null}
      {showBack ? (
        <IconButton onPress={onBack ?? (() => router.back())}>
          <ArrowLeft size={20} color={colors.ink} strokeWidth={2.2} />
        </IconButton>
      ) : null}
      <View style={styles.copy}>
        {eyebrow ? (
          <AppText style={styles.eyebrow} numberOfLines={1}>
            {eyebrow}
          </AppText>
        ) : null}
        <AppText style={styles.title} numberOfLines={2}>
          {title}
        </AppText>
        {subtitle ? (
          <AppText variant="caption" numberOfLines={2}>
            {subtitle}
          </AppText>
        ) : null}
      </View>
      {right}
      {RightIcon ? (
        <IconButton onPress={onRightPress}>
          <RightIcon size={20} color={colors.ink} strokeWidth={2.2} />
        </IconButton>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  copy: {
    flex: 1,
    minWidth: 0,
    gap: 2,
  },
  eyebrow: {
    fontFamily: typography.bodyMedium,
    fontSize: 11,
    letterSpacing: 0.8,
    textTransform: 'uppercase',
    color: colors.inkSoft,
  },
  title: {
    fontFamily: typography.displayBold,
    fontSize: 26,
    lineHeight: 30,
    letterSpacing: -0.3,
    color: colors.ink,
  },
});

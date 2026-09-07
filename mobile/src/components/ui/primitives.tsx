import React from 'react';
import {
  Text,
  Pressable,
  StyleProp,
  StyleSheet,
  TextProps,
  TextStyle,
  View,
  ViewStyle,
} from 'react-native';
import Animated, {
  FadeInDown,
  FadeInRight,
  FadeOut,
  LinearTransition,
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

type Variant = 'display' | 'title' | 'body' | 'caption' | 'label';

const variantStyle: Record<Variant, TextStyle> = {
  display: {
    fontFamily: typography.displayBold,
    fontSize: 28,
    lineHeight: 34,
    color: colors.ink,
    letterSpacing: -0.3,
  },
  title: {
    fontFamily: typography.bodySemi,
    fontSize: 17,
    lineHeight: 23,
    color: colors.ink,
    letterSpacing: -0.15,
  },
  body: {
    fontFamily: typography.body,
    fontSize: 15,
    lineHeight: 22,
    color: colors.ink,
  },
  caption: {
    fontFamily: typography.body,
    fontSize: 13,
    lineHeight: 18,
    color: colors.inkMuted,
  },
  label: {
    fontFamily: typography.bodySemi,
    fontSize: 11,
    lineHeight: 14,
    color: colors.inkMuted,
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
};

export function AppText({
  variant = 'body',
  style,
  ...props
}: TextProps & { variant?: Variant }) {
  return <Text {...props} style={[variantStyle[variant], styles.textBase, style]} />;
}

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

export function PressableScale({
  children,
  onPress,
  style,
  disabled,
}: {
  children: React.ReactNode;
  onPress?: () => void;
  style?: StyleProp<ViewStyle>;
  disabled?: boolean;
}) {
  const scale = useSharedValue(1);
  const animated = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <AnimatedPressable
      disabled={disabled}
      onPress={onPress}
      onPressIn={() => {
        scale.value = withSpring(0.985, { damping: 20, stiffness: 420 });
      }}
      onPressOut={() => {
        scale.value = withSpring(1, { damping: 16, stiffness: 260 });
      }}
      style={[animated, style]}
    >
      {children}
    </AnimatedPressable>
  );
}

export function IconButton({
  children,
  onPress,
  style,
  size = 44,
}: {
  children: React.ReactNode;
  onPress?: () => void;
  style?: StyleProp<ViewStyle>;
  size?: number;
}) {
  return (
    <PressableScale
      onPress={onPress}
      style={[
        styles.iconBtn,
        { width: size, height: size, borderRadius: size / 2 },
        style,
      ]}
    >
      {children}
    </PressableScale>
  );
}

export function AppButton({
  label,
  onPress,
  variant = 'primary',
  icon,
  disabled,
}: {
  label: string;
  onPress?: () => void;
  variant?: 'primary' | 'ghost' | 'brass';
  icon?: React.ReactNode;
  disabled?: boolean;
}) {
  if (variant === 'primary') {
    return (
      <PressableScale
        disabled={disabled}
        onPress={onPress}
        style={[styles.btn, styles.btnPrimary, { opacity: disabled ? 0.5 : 1 }]}
      >
        {icon}
        <AppText numberOfLines={1} style={styles.btnLabelLight}>
          {label}
        </AppText>
      </PressableScale>
    );
  }

  if (variant === 'brass') {
    return (
      <PressableScale
        disabled={disabled}
        onPress={onPress}
        style={[styles.btn, styles.btnBrass, { opacity: disabled ? 0.5 : 1 }]}
      >
        {icon}
        <AppText numberOfLines={1} style={styles.btnLabelLight}>
          {label}
        </AppText>
      </PressableScale>
    );
  }

  return (
    <PressableScale
      disabled={disabled}
      onPress={onPress}
      style={[styles.btn, styles.btnGhost, { opacity: disabled ? 0.5 : 1 }]}
    >
      {icon}
      <AppText numberOfLines={1} style={styles.btnLabelDark}>
        {label}
      </AppText>
    </PressableScale>
  );
}

export function Chip({
  label,
  selected,
  onPress,
  leading,
}: {
  label: string;
  selected?: boolean;
  onPress?: () => void;
  leading?: React.ReactNode;
}) {
  return (
    <PressableScale onPress={onPress} style={{ maxWidth: '100%' }}>
      <View style={[styles.chip, selected && styles.chipOn]}>
        {leading}
        <AppText
          numberOfLines={1}
          style={{
            color: selected ? colors.paper : colors.ink,
            fontFamily: typography.bodyMedium,
            fontSize: 13,
            flexShrink: 1,
            maxWidth: 220,
          }}
        >
          {label}
        </AppText>
      </View>
    </PressableScale>
  );
}

export function FadeInUp({
  children,
  delay = 0,
  style,
}: {
  children: React.ReactNode;
  delay?: number;
  style?: ViewStyle;
}) {
  return (
    <Animated.View
      entering={FadeInDown.delay(delay).springify().damping(17).stiffness(130)}
      layout={LinearTransition.springify()}
      style={style}
    >
      {children}
    </Animated.View>
  );
}

export function FadeInItem({
  children,
  index = 0,
  style,
}: {
  children: React.ReactNode;
  index?: number;
  style?: ViewStyle;
}) {
  return (
    <Animated.View
      entering={FadeInRight.delay(Math.min(index, 8) * 45).springify().damping(16)}
      exiting={FadeOut.duration(160)}
      layout={LinearTransition}
      style={style}
    >
      {children}
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  textBase: { flexShrink: 1 },
  btn: {
    minHeight: 52,
    borderRadius: radii.pill,
    paddingHorizontal: spacing.lg,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xs,
    ...elevation.soft,
  },
  btnPrimary: {
    backgroundColor: colors.jade,
  },
  btnBrass: {
    backgroundColor: colors.accent,
  },
  btnGhost: {
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.borderStrong,
  },
  btnLabelLight: {
    color: colors.paper,
    fontFamily: typography.bodySemi,
    flexShrink: 1,
    fontSize: 15,
    letterSpacing: 0.15,
  },
  btnLabelDark: {
    color: colors.ink,
    fontFamily: typography.bodySemi,
    flexShrink: 1,
    fontSize: 15,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: radii.pill,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    maxWidth: '100%',
    ...elevation.soft,
  },
  chipOn: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  iconBtn: {
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.pill,
    ...elevation.soft,
  },
});

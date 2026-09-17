import { LinearGradient } from 'expo-linear-gradient';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { colors, gradients } from '@/theme/tokens';

export function ScreenAtmosphere({
  children,
  style,
  variant = 'mist',
}: {
  children?: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  variant?: 'mist' | 'deep';
}) {
  if (variant === 'deep') {
    return (
      <View style={[styles.root, { backgroundColor: colors.ink }, style]}>
        <LinearGradient
          colors={[colors.heroFrom, colors.heroVia, colors.jadeDeep]}
          start={{ x: 0.1, y: 0 }}
          end={{ x: 0.9, y: 1 }}
          style={StyleSheet.absoluteFillObject}
        />
        {children}
      </View>
    );
  }

  return (
    <View style={[styles.root, style]}>
      <LinearGradient
        colors={[...gradients.accentWash]}
        start={{ x: 0.05, y: 0 }}
        end={{ x: 0.95, y: 1 }}
        style={StyleSheet.absoluteFillObject}
      />
      {/* Soft color orbs — funky depth without clutter */}
      <View style={styles.orbTeal} pointerEvents="none" />
      <View style={styles.orbCoral} pointerEvents="none" />
      <View style={styles.orbViolet} pointerEvents="none" />
      <View style={styles.orbAmber} pointerEvents="none" />
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas, overflow: 'hidden' },
  orbTeal: {
    position: 'absolute',
    top: -40,
    right: -50,
    width: 180,
    height: 180,
    borderRadius: 90,
    backgroundColor: 'rgba(0, 194, 178, 0.16)',
  },
  orbCoral: {
    position: 'absolute',
    top: 120,
    left: -70,
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(255, 77, 141, 0.12)',
  },
  orbViolet: {
    position: 'absolute',
    bottom: 180,
    right: -40,
    width: 140,
    height: 140,
    borderRadius: 70,
    backgroundColor: 'rgba(168, 85, 247, 0.12)',
  },
  orbAmber: {
    position: 'absolute',
    bottom: 40,
    left: 40,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(255, 176, 32, 0.14)',
  },
});

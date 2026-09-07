import { LinearGradient } from 'expo-linear-gradient';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { colors } from '@/theme/tokens';

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
        colors={['#F7FAFD', '#F4F7FB', '#EEF3F8']}
        start={{ x: 0.2, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={StyleSheet.absoluteFillObject}
      />
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas, overflow: 'hidden' },
});

import { Eye, EyeOff } from 'lucide-react-native';
import { useState } from 'react';
import {
  StyleSheet,
  TextInput,
  type TextInputProps,
  View,
} from 'react-native';
import { PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, typography } from '@/theme/tokens';

export function PasswordField({
  value,
  onChangeText,
  placeholder = '••••••••',
  ...rest
}: {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
} & Omit<TextInputProps, 'value' | 'onChangeText' | 'secureTextEntry'>) {
  const [visible, setVisible] = useState(false);

  return (
    <View style={styles.wrap}>
      <TextInput
        {...rest}
        value={value}
        onChangeText={onChangeText}
        secureTextEntry={!visible}
        placeholder={placeholder}
        placeholderTextColor={colors.inkSoft}
        style={styles.input}
        autoCapitalize="none"
        autoCorrect={false}
      />
      <PressableScale style={styles.eye} onPress={() => setVisible((v) => !v)}>
        {visible ? (
          <EyeOff size={18} color={colors.inkMuted} strokeWidth={2} />
        ) : (
          <Eye size={18} color={colors.inkMuted} strokeWidth={2} />
        )}
      </PressableScale>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.paper,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  input: {
    flex: 1,
    minWidth: 0,
    paddingHorizontal: 14,
    paddingVertical: 13,
    fontFamily: typography.bodyMedium,
    fontSize: 15,
    color: colors.ink,
  },
  eye: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

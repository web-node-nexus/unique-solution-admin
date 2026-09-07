import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, TextInput, View } from 'react-native';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { PasswordField } from '@/components/ui/PasswordField';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, spacing, typography } from '@/theme/tokens';

export default function RegisterScreen() {
  const register = useAuthStore((s) => s.register);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setError('');
    if (password !== confirm) {
      setError('Passwords do not match');
      return;
    }
    setBusy(true);
    try {
      await register({
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim() || undefined,
        password,
        password_confirmation: confirm,
      });
      router.back();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not register');
    } finally {
      setBusy(false);
    }
  };

  const fields: Array<{
    label: string;
    value: string;
    set: (v: string) => void;
    kind: 'text' | 'email' | 'phone' | 'password';
  }> = [
    { label: 'Full name', value: name, set: setName, kind: 'text' },
    { label: 'Email', value: email, set: setEmail, kind: 'email' },
    { label: 'Phone', value: phone, set: setPhone, kind: 'phone' },
    { label: 'Password', value: password, set: setPassword, kind: 'password' },
    { label: 'Confirm password', value: confirm, set: setConfirm, kind: 'password' },
  ];

  return (
    <ScreenShell>
      <KeyboardForm contentContainerStyle={{ paddingHorizontal: spacing.lg, paddingBottom: 48 }}>
        <PressableScale onPress={() => router.back()}>
          <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi }}>Close</AppText>
        </PressableScale>
        <AppText variant="label" style={{ color: colors.brass, marginTop: spacing.lg }}>Unique Solution</AppText>
        <AppText variant="display" style={{ fontSize: 32, marginBottom: spacing.lg }}>Create account</AppText>

        {fields.map((field) => (
          <View key={field.label} style={{ marginBottom: spacing.md }}>
            <AppText variant="label">{field.label}</AppText>
            {field.kind === 'password' ? (
              <View style={styles.fieldGap}>
                <PasswordField value={field.value} onChangeText={field.set} />
              </View>
            ) : (
              <TextInput
                value={field.value}
                onChangeText={field.set}
                style={[themeCard.input, styles.fieldGap]}
                autoCapitalize={field.kind === 'email' ? 'none' : 'words'}
                keyboardType={
                  field.kind === 'email'
                    ? 'email-address'
                    : field.kind === 'phone'
                      ? 'phone-pad'
                      : 'default'
                }
                placeholderTextColor={colors.inkSoft}
              />
            )}
          </View>
        ))}

        {error ? <AppText style={{ color: colors.danger }}>{error}</AppText> : null}
        <View style={{ marginTop: spacing.lg, gap: 12 }}>
          {busy ? <ActivityIndicator color={colors.jade} /> : <AppButton label="Create account" onPress={onSubmit} />}
          <AppButton label="Already have an account" variant="ghost" onPress={() => router.replace('/auth/login')} />
        </View>
      </KeyboardForm>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  fieldGap: { marginTop: 6 },
});

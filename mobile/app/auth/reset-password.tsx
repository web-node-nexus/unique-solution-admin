import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, TextInput, View } from 'react-native';
import { authApi } from '@/api/auth';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { PasswordField } from '@/components/ui/PasswordField';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { colors, spacing, typography } from '@/theme/tokens';

export default function ResetPasswordScreen() {
  const params = useLocalSearchParams<{ email?: string }>();
  const [email, setEmail] = useState(params.email ?? '');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [done, setDone] = useState(false);

  const onSubmit = async () => {
    setError('');
    if (password !== passwordConfirmation) {
      setError('Passwords do not match.');
      return;
    }
    setBusy(true);
    try {
      await authApi.resetPassword({
        email: email.trim(),
        code: code.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      setDone(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Reset failed');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenShell>
      <KeyboardForm contentContainerStyle={{ paddingHorizontal: spacing.lg, paddingBottom: 40 }}>
        <PressableScale onPress={() => router.back()}>
          <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi }}>Back</AppText>
        </PressableScale>
        <AppText variant="label" style={{ color: colors.brass, marginTop: spacing.lg }}>
          Account recovery
        </AppText>
        <AppText variant="display" style={{ fontSize: 30, marginBottom: spacing.lg }}>
          Reset password
        </AppText>

        <AppText variant="label">Email</AppText>
        <TextInput
          autoCapitalize="none"
          keyboardType="email-address"
          value={email}
          onChangeText={setEmail}
          style={[themeCard.input, styles.fieldGap]}
        />
        <AppText variant="label" style={{ marginTop: spacing.md }}>
          6-digit code
        </AppText>
        <TextInput
          keyboardType="number-pad"
          maxLength={6}
          value={code}
          onChangeText={setCode}
          style={[themeCard.input, styles.fieldGap]}
          placeholder="123456"
          placeholderTextColor={colors.inkSoft}
        />
        <AppText variant="label" style={{ marginTop: spacing.md }}>
          New password
        </AppText>
        <View style={styles.fieldGap}>
          <PasswordField value={password} onChangeText={setPassword} />
        </View>
        <AppText variant="label" style={{ marginTop: spacing.md }}>
          Confirm password
        </AppText>
        <View style={styles.fieldGap}>
          <PasswordField
            value={passwordConfirmation}
            onChangeText={setPasswordConfirmation}
          />
        </View>

        {error ? <AppText style={{ color: colors.danger, marginTop: 10 }}>{error}</AppText> : null}
        {done ? (
          <AppText style={{ color: colors.jade, marginTop: 10 }}>
            Password updated. You can sign in now.
          </AppText>
        ) : null}

        <View style={{ marginTop: spacing.xl, gap: 12 }}>
          {busy ? (
            <ActivityIndicator color={colors.jade} />
          ) : done ? (
            <AppButton label="Sign in" onPress={() => router.replace('/auth/login')} />
          ) : (
            <AppButton label="Reset password" onPress={onSubmit} />
          )}
        </View>
      </KeyboardForm>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  fieldGap: { marginTop: 6 },
});

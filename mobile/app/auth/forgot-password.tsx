import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, TextInput, View } from 'react-native';
import { authApi } from '@/api/auth';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { colors, spacing, typography } from '@/theme/tokens';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [sent, setSent] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const onSubmit = async () => {
    setError('');
    setBusy(true);
    try {
      await authApi.forgotPassword(email.trim());
      setSent(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not send code');
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
        <AppText variant="display" style={{ fontSize: 30, marginBottom: spacing.md }}>
          Forgot password
        </AppText>
        <AppText variant="caption" style={{ marginBottom: spacing.lg }}>
          Enter your email and we will send a 6-digit reset code.
        </AppText>

        <AppText variant="label">Email</AppText>
        <TextInput
          autoCapitalize="none"
          keyboardType="email-address"
          value={email}
          onChangeText={setEmail}
          style={[themeCard.input, styles.fieldGap]}
          placeholder="you@email.com"
          placeholderTextColor={colors.inkSoft}
        />
        {error ? <AppText style={{ color: colors.danger, marginTop: 10 }}>{error}</AppText> : null}
        {sent ? (
          <AppText style={{ color: colors.jade, marginTop: 12 }}>
            If that email exists, a code has been sent.
          </AppText>
        ) : null}

        <View style={{ marginTop: spacing.xl, gap: 12 }}>
          {busy ? <ActivityIndicator color={colors.jade} /> : <AppButton label="Send code" onPress={onSubmit} />}
          {sent ? (
            <AppButton
              label="Enter reset code"
              variant="ghost"
              onPress={() =>
                router.push({ pathname: '/auth/reset-password', params: { email: email.trim() } })
              }
            />
          ) : null}
        </View>
      </KeyboardForm>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  fieldGap: { marginTop: 6 },
});

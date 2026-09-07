import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, TextInput, View } from 'react-native';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { PasswordField } from '@/components/ui/PasswordField';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, spacing, typography } from '@/theme/tokens';
import { track } from '@/utils/analytics';

export default function LoginScreen() {
  const login = useAuthStore((s) => s.login);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const onSubmit = async () => {
    setError('');
    setBusy(true);
    try {
      await login(email.trim(), password);
      void track('login');
      router.back();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Login failed');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenShell>
      <KeyboardForm contentContainerStyle={{ paddingHorizontal: spacing.lg, paddingBottom: 40 }}>
        <PressableScale onPress={() => router.back()}>
          <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi }}>Close</AppText>
        </PressableScale>
        <AppText variant="label" style={{ color: colors.brass, marginTop: spacing.lg }}>Welcome back</AppText>
        <AppText variant="display" style={{ fontSize: 32, marginBottom: spacing.lg }}>Sign in</AppText>

        <AppText variant="label">Email</AppText>
        <TextInput
          autoCapitalize="none"
          keyboardType="email-address"
          value={email}
          onChangeText={setEmail}
          style={themeCard.input}
          placeholder="you@email.com"
          placeholderTextColor={colors.inkSoft}
          returnKeyType="next"
        />
        <AppText variant="label" style={{ marginTop: spacing.md }}>Password</AppText>
        <PasswordField
          value={password}
          onChangeText={setPassword}
          returnKeyType="done"
          onSubmitEditing={onSubmit}
        />
        {error ? <AppText style={{ color: colors.danger, marginTop: 10 }}>{error}</AppText> : null}

        <PressableScale onPress={() => router.push('/auth/forgot-password')} style={{ marginTop: 10 }}>
          <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi }}>Forgot password?</AppText>
        </PressableScale>

        <View style={{ marginTop: spacing.xl, gap: 12 }}>
          {busy ? <ActivityIndicator color={colors.jade} /> : <AppButton label="Continue" onPress={onSubmit} />}
          <AppButton
            label="Create account"
            variant="ghost"
            onPress={() => router.replace('/auth/register')}
          />
        </View>
      </KeyboardForm>
    </ScreenShell>
  );
}

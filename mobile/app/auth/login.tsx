import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, TextInput, View } from 'react-native';
import { authApi } from '@/api/auth';
import { BrandLogo } from '@/components/brand/BrandLogo';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, spacing, typography } from '@/theme/tokens';
import { track } from '@/utils/analytics';

const RESEND_SECONDS = 60;

export default function LoginScreen() {
  const loginWithOtp = useAuthStore((s) => s.loginWithOtp);
  const [phone, setPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [name, setName] = useState('');
  const [step, setStep] = useState<'phone' | 'otp'>('phone');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [secondsLeft, setSecondsLeft] = useState(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, []);

  const startResendTimer = () => {
    if (timerRef.current) clearInterval(timerRef.current);
    setSecondsLeft(RESEND_SECONDS);
    timerRef.current = setInterval(() => {
      setSecondsLeft((s) => {
        if (s <= 1) {
          if (timerRef.current) clearInterval(timerRef.current);
          timerRef.current = null;
          return 0;
        }
        return s - 1;
      });
    }, 1000);
  };

  const digits = phone.replace(/\D/g, '').slice(-10);

  const sendOtp = async (fromResend = false) => {
    setError('');
    if (digits.length !== 10) {
      setError('Enter a valid 10-digit mobile number.');
      return;
    }
    if (fromResend && secondsLeft > 0) return;

    setBusy(true);
    try {
      await authApi.sendOtp(digits);
      setPhone(digits);
      setStep('otp');
      startResendTimer();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not send OTP');
    } finally {
      setBusy(false);
    }
  };

  const verify = async () => {
    setError('');
    if (otp.trim().length < 4) {
      setError('Enter the OTP from SMS.');
      return;
    }
    setBusy(true);
    try {
      await loginWithOtp(digits || phone, otp.trim(), name.trim() || undefined);
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
        <View style={{ marginTop: spacing.lg, marginBottom: spacing.md }}>
          <BrandLogo size={84} />
        </View>
        <AppText variant="label" style={{ color: colors.brass }}>
          Welcome
        </AppText>
        <AppText variant="display" style={{ fontSize: 32, marginBottom: spacing.sm }}>
          Sign in
        </AppText>
        <AppText style={{ color: colors.inkSoft, marginBottom: spacing.lg, lineHeight: 22 }}>
          Login with your mobile number. You stay signed in until you log out.
        </AppText>

        {step === 'phone' ? (
          <>
            <AppText variant="label">Mobile number</AppText>
            <TextInput
              keyboardType="phone-pad"
              value={phone}
              onChangeText={setPhone}
              style={themeCard.input}
              placeholder="10-digit mobile"
              placeholderTextColor={colors.inkSoft}
              maxLength={15}
              returnKeyType="done"
              onSubmitEditing={() => void sendOtp()}
            />
            {error ? <AppText style={{ color: colors.danger, marginTop: 10 }}>{error}</AppText> : null}
            <View style={{ marginTop: spacing.xl }}>
              {busy ? (
                <ActivityIndicator color={colors.jade} />
              ) : (
                <AppButton label="Send OTP" onPress={() => void sendOtp()} />
              )}
            </View>
          </>
        ) : (
          <>
            <AppText style={{ color: colors.inkSoft, marginBottom: spacing.md }}>
              Code sent to {digits || phone}
            </AppText>
            <AppText variant="label">Your name (first time only)</AppText>
            <TextInput
              value={name}
              onChangeText={setName}
              style={themeCard.input}
              placeholder="Optional"
              placeholderTextColor={colors.inkSoft}
            />
            <AppText variant="label" style={{ marginTop: spacing.md }}>
              OTP
            </AppText>
            <TextInput
              keyboardType="number-pad"
              value={otp}
              onChangeText={setOtp}
              style={[themeCard.input, { letterSpacing: 4, fontSize: 22, fontWeight: '700' }]}
              placeholder="Enter OTP"
              placeholderTextColor={colors.inkSoft}
              maxLength={8}
              returnKeyType="done"
              onSubmitEditing={() => void verify()}
            />
            {error ? <AppText style={{ color: colors.danger, marginTop: 10 }}>{error}</AppText> : null}
            <View style={{ marginTop: spacing.xl, gap: 12 }}>
              {busy ? (
                <ActivityIndicator color={colors.jade} />
              ) : (
                <AppButton label="Verify & continue" onPress={() => void verify()} />
              )}
              {secondsLeft > 0 ? (
                <AppText style={{ textAlign: 'center', color: colors.inkSoft, fontFamily: typography.bodySemi }}>
                  Resend OTP in 0:{String(secondsLeft).padStart(2, '0')}
                </AppText>
              ) : (
                <AppButton label="Resend OTP" variant="ghost" onPress={() => void sendOtp(true)} />
              )}
              <AppButton
                label="Change number"
                variant="ghost"
                onPress={() => {
                  setStep('phone');
                  setOtp('');
                  setError('');
                }}
              />
            </View>
          </>
        )}
      </KeyboardForm>
    </ScreenShell>
  );
}

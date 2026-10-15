import { Image } from 'expo-image';
import { useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { deliveryApi } from '@/api/delivery';
import {
  AppDialog,
  AppText,
  GhostButton,
  PrimaryButton,
  type AppDialogState,
  type DialogTone,
} from '@/components/ui';
import { useAuthStore } from '@/store/auth';
import { colors, radii, spacing } from '@/theme/tokens';

const RESEND_SECONDS = 60;

const closedDialog: AppDialogState = {
  visible: false,
  title: '',
  message: '',
};

export default function LoginScreen() {
  const insets = useSafeAreaInsets();
  const setSession = useAuthStore((s) => s.setSession);
  const [phone, setPhone] = useState('');
  const [otp, setOtp] = useState('');
  const [step, setStep] = useState<'phone' | 'otp'>('phone');
  const [busy, setBusy] = useState(false);
  const [secondsLeft, setSecondsLeft] = useState(0);
  const [dialog, setDialog] = useState<AppDialogState>(closedDialog);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const showDialog = (
    title: string,
    message: string,
    tone: DialogTone = 'sky',
    confirmLabel = 'OK'
  ) => {
    setDialog({
      visible: true,
      title,
      message,
      tone,
      confirmLabel,
      onConfirm: () => setDialog(closedDialog),
    });
  };

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

  useEffect(() => {
    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, []);

  const sendOtp = async (fromResend = false) => {
    const digits = phone.replace(/\D/g, '').slice(-10);
    if (digits.length !== 10) {
      showDialog('Phone needed', 'Enter your 10-digit mobile number.', 'amber');
      return;
    }
    if (fromResend && secondsLeft > 0) return;

    setBusy(true);
    try {
      await deliveryApi.sendOtp(digits);
      setPhone(digits);
      setStep('otp');
      startResendTimer();
      showDialog(
        fromResend ? 'OTP resent' : 'OTP sent',
        fromResend
          ? 'A new code has been sent to your phone.'
          : 'Check your SMS and enter the code.',
        'jade'
      );
    } catch (e) {
      showDialog('Could not send OTP', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  const verify = async () => {
    if (otp.trim().length < 4) {
      showDialog('OTP needed', 'Enter the OTP from SMS.', 'amber');
      return;
    }
    setBusy(true);
    try {
      const res = await deliveryApi.verifyOtp(phone, otp.trim());
      await setSession(res.data.token, res.data.user);
    } catch (e) {
      showDialog('Login failed', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  const timerLabel =
    secondsLeft > 0
      ? `Resend OTP in 0:${String(secondsLeft).padStart(2, '0')}`
      : 'Resend OTP';

  return (
    <KeyboardAvoidingView
      style={{ flex: 1, backgroundColor: colors.canvas }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={[
          styles.wrap,
          { paddingTop: insets.top + 40, paddingBottom: insets.bottom + 24 },
        ]}
        keyboardShouldPersistTaps="handled"
      >
        <Image source={require('../assets/brand-logo.png')} style={styles.logo} contentFit="contain" />
        <AppText style={styles.brand}>US Delivery</AppText>
        <AppText style={styles.sub}>Login with the mobile number saved on your staff account.</AppText>

        {step === 'phone' ? (
          <View style={styles.card}>
            <AppText style={styles.label}>Mobile number</AppText>
            <TextInput
              value={phone}
              onChangeText={setPhone}
              keyboardType="phone-pad"
              maxLength={10}
              placeholder="10-digit number"
              placeholderTextColor={colors.inkMuted}
              style={styles.input}
            />
            <PrimaryButton label={busy ? 'Sending…' : 'Send OTP'} onPress={() => sendOtp(false)} disabled={busy} />
          </View>
        ) : (
          <View style={styles.card}>
            <AppText style={styles.label}>OTP sent to {phone}</AppText>
            <TextInput
              value={otp}
              onChangeText={setOtp}
              keyboardType="number-pad"
              maxLength={8}
              placeholder="Enter OTP"
              placeholderTextColor={colors.inkMuted}
              style={styles.input}
            />
            <PrimaryButton label={busy ? 'Checking…' : 'Verify & login'} onPress={verify} disabled={busy} />

            <View style={styles.resendBox}>
              {secondsLeft > 0 ? (
                <AppText style={styles.timerText}>{timerLabel}</AppText>
              ) : (
                <GhostButton
                  label={busy ? 'Sending…' : 'Resend OTP'}
                  onPress={() => sendOtp(true)}
                  disabled={busy}
                />
              )}
            </View>

            <GhostButton
              label="Change number"
              disabled={busy}
              onPress={() => {
                setStep('phone');
                setOtp('');
                setSecondsLeft(0);
                if (timerRef.current) {
                  clearInterval(timerRef.current);
                  timerRef.current = null;
                }
              }}
            />
          </View>
        )}
      </ScrollView>

      <AppDialog {...dialog} />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  wrap: {
    paddingHorizontal: spacing.lg,
    gap: 12,
  },
  logo: { width: 84, height: 84, alignSelf: 'center' },
  brand: {
    textAlign: 'center',
    fontSize: 28,
    fontWeight: '800',
    color: colors.sky,
  },
  sub: {
    textAlign: 'center',
    color: colors.inkMuted,
    lineHeight: 20,
    marginBottom: 12,
  },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.lg,
    gap: 12,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
  },
  label: { fontWeight: '600', color: colors.inkMuted, fontSize: 13 },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 18,
    letterSpacing: 1,
    color: colors.ink,
    backgroundColor: '#fff',
  },
  resendBox: {
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  timerText: {
    color: colors.inkMuted,
    fontWeight: '700',
    fontSize: 14,
  },
});

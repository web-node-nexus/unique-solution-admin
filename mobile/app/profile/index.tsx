import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, TextInput, View } from 'react-native';
import { authApi } from '@/api/auth';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl, usePullRefresh } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, spacing } from '@/theme/tokens';

export default function ProfileScreen() {
  const user = useAuthStore((s) => s.user);
  const setUser = useAuthStore((s) => s.setUser);
  const refreshProfile = useAuthStore((s) => s.refreshProfile);
  const [name, setName] = useState(user?.name ?? '');
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [address, setAddress] = useState(user?.address ?? '');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const { refreshing, onRefresh } = usePullRefresh(async () => {
    await refreshProfile();
    const next = useAuthStore.getState().user;
    if (next) {
      setName(next.name ?? '');
      setPhone(next.phone ?? '');
      setAddress(next.address ?? '');
    }
  });

  if (!user) {
    return (
      <ScreenShell>
        <ScreenHeader showBack title="Profile" />
        <View style={{ padding: spacing.lg, gap: 12 }}>
          <AppText variant="caption">Sign in with your mobile number to edit your profile.</AppText>
          <AppButton label="Sign in" onPress={() => router.push('/auth/login')} />
        </View>
      </ScreenShell>
    );
  }

  const saveProfile = async () => {
    setError('');
    setBusy(true);
    try {
      const res = await authApi.updateProfile({
        name: name.trim(),
        phone: phone.trim() || null,
        address: address.trim() || null,
      });
      setUser(res.data);
      Alert.alert('Saved', 'Profile updated.');
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not save');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Account" title="Edit profile" />
      <KeyboardForm
        contentContainerStyle={{ padding: spacing.lg, gap: 10, paddingBottom: 40 }}
        refreshControl={<AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        <AppText variant="label">Name</AppText>
        <TextInput style={themeCard.input} value={name} onChangeText={setName} />
        <AppText variant="label">Phone</AppText>
        <TextInput
          style={[themeCard.input, styles.disabled]}
          value={phone}
          editable={false}
          keyboardType="phone-pad"
        />
        <AppText variant="caption" style={{ color: colors.inkSoft }}>
          Login uses this mobile number. Ask the shop if you need it changed.
        </AppText>
        <AppText variant="label">Address</AppText>
        <TextInput
          style={[themeCard.input, { minHeight: 88, textAlignVertical: 'top' }]}
          value={address}
          onChangeText={setAddress}
          multiline
        />
        {error ? <AppText style={{ color: colors.danger }}>{error}</AppText> : null}
        {busy ? <ActivityIndicator color={colors.jade} /> : <AppButton label="Save profile" onPress={saveProfile} />}
      </KeyboardForm>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  disabled: { opacity: 0.85 },
});

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
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
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
          <AppText variant="caption">Sign in to edit your profile.</AppText>
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

  const savePassword = async () => {
    setError('');
    if (password !== passwordConfirmation) {
      setError('New passwords do not match.');
      return;
    }
    setBusy(true);
    try {
      await authApi.changePassword({
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      });
      setCurrentPassword('');
      setPassword('');
      setPasswordConfirmation('');
      Alert.alert('Saved', 'Password updated.');
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not update password');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Account" title="Edit profile" />
      <KeyboardForm
        contentContainerStyle={{ padding: spacing.lg, gap: 10, paddingBottom: 40 }}
        refreshControl={
          <AppRefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        <AppText variant="label">Name</AppText>
        <TextInput style={themeCard.input} value={name} onChangeText={setName} />
        <AppText variant="label">Email</AppText>
        <TextInput style={[themeCard.input, styles.disabled]} value={user.email} editable={false} />
        <AppText variant="label">Phone</AppText>
        <TextInput
          style={themeCard.input}
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
        />
        <AppText variant="label">Address</AppText>
        <TextInput
          style={[themeCard.input, { minHeight: 88, textAlignVertical: 'top' }]}
          value={address}
          onChangeText={setAddress}
          multiline
        />
        {busy ? <ActivityIndicator color={colors.jade} /> : <AppButton label="Save profile" onPress={saveProfile} />}

        <AppText variant="title" style={{ marginTop: spacing.lg }}>
          Change password
        </AppText>
        <TextInput
          style={themeCard.input}
          secureTextEntry
          placeholder="Current password"
          placeholderTextColor={colors.inkSoft}
          value={currentPassword}
          onChangeText={setCurrentPassword}
        />
        <TextInput
          style={themeCard.input}
          secureTextEntry
          placeholder="New password"
          placeholderTextColor={colors.inkSoft}
          value={password}
          onChangeText={setPassword}
        />
        <TextInput
          style={themeCard.input}
          secureTextEntry
          placeholder="Confirm new password"
          placeholderTextColor={colors.inkSoft}
          value={passwordConfirmation}
          onChangeText={setPasswordConfirmation}
        />
        <AppButton label="Update password" variant="ghost" onPress={savePassword} />
        {error ? <AppText style={{ color: colors.danger }}>{error}</AppText> : null}
      </KeyboardForm>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  disabled: { opacity: 0.7 },
});

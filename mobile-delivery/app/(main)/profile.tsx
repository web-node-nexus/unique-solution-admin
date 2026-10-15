import { Alert, StyleSheet, View } from 'react-native';
import { AppText, PrimaryButton } from '@/components/ui';
import { useAuthStore } from '@/store/auth';
import { colors, radii, spacing } from '@/theme/tokens';

export default function ProfileScreen() {
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  return (
    <View style={styles.wrap}>
      <View style={styles.card}>
        <AppText style={styles.label}>Name</AppText>
        <AppText style={styles.value}>{user?.name || '—'}</AppText>
        <AppText style={[styles.label, { marginTop: 14 }]}>Phone</AppText>
        <AppText style={styles.value}>{user?.phone || '—'}</AppText>
        {user?.email ? (
          <>
            <AppText style={[styles.label, { marginTop: 14 }]}>Email</AppText>
            <AppText style={styles.value}>{user.email}</AppText>
          </>
        ) : null}
      </View>
      <PrimaryButton
        label="Log out"
        tone="amber"
        onPress={() => {
          Alert.alert('Log out?', 'You will need OTP again to sign in.', [
            { text: 'Cancel', style: 'cancel' },
            { text: 'Log out', style: 'destructive', onPress: () => void logout() },
          ]);
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { flex: 1, padding: spacing.md, gap: 16 },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.lg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
  },
  label: { color: colors.inkMuted, fontSize: 12, fontWeight: '600' },
  value: { fontSize: 17, fontWeight: '700', marginTop: 4 },
});

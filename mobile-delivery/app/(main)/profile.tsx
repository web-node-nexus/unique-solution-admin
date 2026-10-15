import { LogOut, Phone, UserRound } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import { AppDialog, AppText, PressableScale, type AppDialogState } from '@/components/ui';
import { useAuthStore } from '@/store/auth';
import { colors, radii, spacing } from '@/theme/tokens';
import { useState } from 'react';

const closed: AppDialogState = { visible: false, title: '', message: '' };

export default function ProfileScreen() {
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);
  const [dialog, setDialog] = useState<AppDialogState>(closed);

  return (
    <View style={styles.wrap}>
      <View style={styles.hero}>
        <View style={styles.avatar}>
          <UserRound size={28} color={colors.skyDeep} strokeWidth={2.2} />
        </View>
        <AppText style={styles.name}>{user?.name || 'Delivery partner'}</AppText>
        <AppText style={styles.role}>Staff · Unique Solution</AppText>
      </View>

      <View style={styles.card}>
        <View style={styles.row}>
          <Phone size={18} color={colors.sky} />
          <View>
            <AppText style={styles.label}>Phone</AppText>
            <AppText style={styles.value}>{user?.phone || '—'}</AppText>
          </View>
        </View>
        {user?.email ? (
          <View style={[styles.row, styles.rowBorder]}>
            <UserRound size={18} color={colors.violet} />
            <View>
              <AppText style={styles.label}>Email</AppText>
              <AppText style={styles.value}>{user.email}</AppText>
            </View>
          </View>
        ) : null}
      </View>

      <PressableScale
        style={styles.logout}
        onPress={() =>
          setDialog({
            visible: true,
            title: 'Log out?',
            message: 'You will need OTP again to sign back in.',
            tone: 'amber',
            confirmLabel: 'Log out',
            cancelLabel: 'Stay',
            onCancel: () => setDialog(closed),
            onConfirm: () => {
              setDialog(closed);
              void logout();
            },
          })
        }
      >
        <LogOut size={18} color={colors.danger} strokeWidth={2.2} />
        <AppText style={styles.logoutText}>Log out</AppText>
      </PressableScale>

      <AppDialog {...dialog} />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { flex: 1, padding: spacing.md, gap: 16, backgroundColor: colors.canvas },
  hero: {
    backgroundColor: colors.skyDeep,
    borderRadius: radii.xl,
    padding: spacing.lg,
    alignItems: 'center',
    gap: 8,
  },
  avatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  name: { color: '#fff', fontSize: 22, fontWeight: '800' },
  role: { color: 'rgba(255,255,255,0.78)', fontWeight: '600' },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.xl,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  row: { flexDirection: 'row', gap: 12, alignItems: 'center', paddingVertical: 8 },
  rowBorder: { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border },
  label: { color: colors.inkMuted, fontSize: 12, fontWeight: '700' },
  value: { fontSize: 16, fontWeight: '800', marginTop: 2 },
  logout: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.dangerSoft,
    borderRadius: radii.lg,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  logoutText: { color: colors.danger, fontWeight: '800', fontSize: 15 },
});

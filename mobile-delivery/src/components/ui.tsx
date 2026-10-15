import { Modal, Pressable, StyleSheet, Text, View, type TextStyle, type ViewStyle } from 'react-native';
import { colors, radii, spacing } from '@/theme/tokens';

export type DialogTone = 'sky' | 'jade' | 'amber' | 'danger';

export type AppDialogState = {
  visible: boolean;
  title: string;
  message: string;
  tone?: DialogTone;
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm?: () => void;
  onCancel?: () => void;
};

export function AppText({
  children,
  style,
  numberOfLines,
}: {
  children: React.ReactNode;
  style?: TextStyle | TextStyle[];
  numberOfLines?: number;
}) {
  return (
    <Text style={[styles.text, style]} numberOfLines={numberOfLines}>
      {children}
    </Text>
  );
}

export function PressableScale({
  children,
  onPress,
  style,
  disabled,
}: {
  children: React.ReactNode;
  onPress?: () => void;
  style?: ViewStyle | ViewStyle[];
  disabled?: boolean;
}) {
  return (
    <Pressable
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        style,
        { opacity: disabled ? 0.5 : pressed ? 0.88 : 1, transform: [{ scale: pressed && !disabled ? 0.98 : 1 }] },
      ]}
    >
      {children}
    </Pressable>
  );
}

export function PrimaryButton({
  label,
  onPress,
  disabled,
  tone = 'sky',
}: {
  label: string;
  onPress?: () => void;
  disabled?: boolean;
  tone?: 'sky' | 'jade' | 'amber';
}) {
  const bg = tone === 'jade' ? colors.jade : tone === 'amber' ? colors.amber : colors.sky;
  return (
    <PressableScale
      disabled={disabled}
      onPress={onPress}
      style={[styles.btn, { backgroundColor: bg }]}
    >
      <AppText style={styles.btnLabel}>{label}</AppText>
    </PressableScale>
  );
}

export function GhostButton({
  label,
  onPress,
  disabled,
}: {
  label: string;
  onPress?: () => void;
  disabled?: boolean;
}) {
  return (
    <PressableScale disabled={disabled} onPress={onPress} style={styles.ghostBtn}>
      <AppText style={[styles.ghostLabel, disabled && { color: colors.inkMuted }]}>{label}</AppText>
    </PressableScale>
  );
}

export function EmptyState({ title, body }: { title: string; body: string }) {
  return (
    <View style={styles.empty}>
      <AppText style={styles.emptyTitle}>{title}</AppText>
      <AppText style={styles.emptyBody}>{body}</AppText>
    </View>
  );
}

export function AppDialog({
  visible,
  title,
  message,
  tone = 'sky',
  confirmLabel = 'OK',
  cancelLabel,
  onConfirm,
  onCancel,
}: AppDialogState) {
  const accent =
    tone === 'jade' ? colors.jade : tone === 'amber' ? colors.amber : tone === 'danger' ? colors.danger : colors.sky;
  const soft =
    tone === 'jade'
      ? colors.jadeSoft
      : tone === 'amber'
        ? colors.amberSoft
        : tone === 'danger'
          ? '#FEE2E2'
          : colors.skySoft;

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onCancel || onConfirm}>
      <View style={styles.dialogBackdrop}>
        <Pressable style={StyleSheet.absoluteFillObject} onPress={onCancel || onConfirm} />
        <View style={styles.dialogCard}>
          <View style={[styles.dialogAccent, { backgroundColor: soft }]}>
            <View style={[styles.dialogDot, { backgroundColor: accent }]} />
            <AppText style={[styles.dialogTitle, { color: accent }]}>{title}</AppText>
          </View>
          <AppText style={styles.dialogMessage}>{message}</AppText>
          <View style={styles.dialogActions}>
            {cancelLabel ? (
              <PressableScale onPress={onCancel} style={styles.dialogCancel}>
                <AppText style={styles.dialogCancelLabel}>{cancelLabel}</AppText>
              </PressableScale>
            ) : null}
            <PressableScale
              onPress={onConfirm}
              style={[styles.dialogConfirm, { backgroundColor: accent }, !cancelLabel && { flex: 1 }]}
            >
              <AppText style={styles.dialogConfirmLabel}>{confirmLabel}</AppText>
            </PressableScale>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  text: {
    color: colors.ink,
    fontSize: 15,
  },
  btn: {
    minHeight: 48,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.md,
  },
  btnLabel: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
  ghostBtn: {
    minHeight: 44,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.paper,
  },
  ghostLabel: {
    color: colors.sky,
    fontSize: 15,
    fontWeight: '700',
  },
  empty: {
    padding: spacing.xl,
    alignItems: 'center',
    gap: 8,
  },
  emptyTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: colors.ink,
  },
  emptyBody: {
    fontSize: 14,
    color: colors.inkMuted,
    textAlign: 'center',
    lineHeight: 20,
  },
  dialogBackdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    paddingHorizontal: spacing.lg,
  },
  dialogCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.xl,
    padding: spacing.lg,
    gap: 14,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
  },
  dialogAccent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: radii.md,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  dialogDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  dialogTitle: {
    fontSize: 16,
    fontWeight: '800',
    flex: 1,
  },
  dialogMessage: {
    fontSize: 15,
    lineHeight: 22,
    color: colors.inkMuted,
  },
  dialogActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 4,
  },
  dialogCancel: {
    flex: 1,
    minHeight: 46,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.canvas,
  },
  dialogCancelLabel: {
    fontWeight: '700',
    color: colors.inkMuted,
  },
  dialogConfirm: {
    flex: 1,
    minHeight: 46,
    borderRadius: radii.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dialogConfirmLabel: {
    color: '#fff',
    fontWeight: '800',
    fontSize: 15,
  },
});

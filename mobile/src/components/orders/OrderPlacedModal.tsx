import { LinearGradient } from 'expo-linear-gradient';
import { Check, Package, ShoppingBag, X } from 'lucide-react-native';
import { Modal, StyleSheet, View } from 'react-native';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

const DOTS: {
  top?: number;
  left?: number;
  right?: number;
  bottom?: number;
  size: number;
  color: string;
}[] = [
  { top: 18, left: 22, size: 10, color: colors.brass },
  { top: 36, right: 54, size: 8, color: colors.paper },
  { top: 72, left: 36, size: 7, color: colors.accent },
  { top: 14, right: 86, size: 6, color: colors.jadeMid },
  { bottom: 16, left: 48, size: 8, color: colors.brass },
  { bottom: 28, right: 28, size: 11, color: colors.accentSoft },
];

export function OrderPlacedModal({
  visible,
  name,
  orderNumber,
  shopName,
  onViewOrder,
  onContinue,
}: {
  visible: boolean;
  name: string;
  orderNumber: string;
  shopName: string;
  onViewOrder: () => void;
  onContinue: () => void;
}) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onContinue}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <LinearGradient
            colors={[colors.heroFrom, colors.heroVia, colors.jade]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.banner}
          >
            {DOTS.map((dot, index) => (
              <View
                key={index}
                style={[
                  styles.dot,
                  {
                    top: dot.top,
                    left: dot.left,
                    right: dot.right,
                    bottom: dot.bottom,
                    width: dot.size,
                    height: dot.size,
                    backgroundColor: dot.color,
                  },
                ]}
              />
            ))}
            <PressableScale onPress={onContinue} style={styles.close}>
              <X size={16} color={colors.ink} strokeWidth={2.4} />
            </PressableScale>
            <View style={styles.checkWrap}>
              <Check size={28} color={colors.paper} strokeWidth={3} />
            </View>
            <AppText style={styles.bannerTitle}>Order placed</AppText>
          </LinearGradient>

          <View style={styles.body}>
            <AppText style={styles.hello}>Hello</AppText>
            <AppText style={styles.name}>{name}!</AppText>
            <AppText variant="caption" style={styles.successLine}>
              Your order has been placed successfully.
            </AppText>

            <View style={styles.orderBox}>
              <View style={styles.orderIcon}>
                <Package size={22} color={colors.jadeDeep} strokeWidth={1.8} />
              </View>
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText variant="caption">Order ID</AppText>
                <AppText style={styles.orderNo} numberOfLines={1}>
                  {orderNumber}
                </AppText>
              </View>
            </View>

            <View style={styles.note}>
              <AppText style={styles.noteText}>
                See your order details in the <AppText style={styles.noteStrong}>My Orders</AppText> section.
              </AppText>
            </View>

            <AppText style={styles.thanks}>Thank you for shopping with</AppText>
            <AppText style={styles.shop}>{shopName}</AppText>

            <View style={styles.actions}>
              <PressableScale onPress={onViewOrder} style={[styles.action, styles.actionJade]}>
                <Package size={16} color={colors.paper} strokeWidth={2} />
                <AppText style={styles.actionText} numberOfLines={1}>
                  View order
                </AppText>
              </PressableScale>
              <PressableScale onPress={onContinue} style={[styles.action, styles.actionBrass]}>
                <ShoppingBag size={16} color={colors.ink} strokeWidth={2} />
                <AppText style={[styles.actionText, styles.actionTextInk]} numberOfLines={1}>
                  Keep shopping
                </AppText>
              </PressableScale>
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.xl ?? 20,
    overflow: 'hidden',
    ...elevation.lift,
  },
  banner: {
    alignItems: 'center',
    paddingTop: 28,
    paddingBottom: 22,
    paddingHorizontal: spacing.lg,
  },
  dot: { position: 'absolute', borderRadius: 99 },
  close: {
    position: 'absolute',
    top: 12,
    right: 12,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkWrap: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: colors.success,
    borderWidth: 4,
    borderColor: colors.brass,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  bannerTitle: {
    fontFamily: typography.displayBold,
    fontSize: 28,
    color: colors.paper,
    letterSpacing: 0.3,
  },
  body: { padding: spacing.lg, alignItems: 'center' },
  hello: { fontFamily: typography.body, color: colors.inkMuted, fontSize: 14 },
  name: {
    fontFamily: typography.displayBold,
    fontSize: 24,
    color: colors.heroFrom,
    marginTop: 2,
    textAlign: 'center',
  },
  successLine: { textAlign: 'center', marginTop: 6, marginBottom: 14 },
  orderBox: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderColor: colors.jade,
    backgroundColor: colors.jadeSoft,
    borderRadius: radii.lg,
    padding: 12,
  },
  orderIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderNo: { fontFamily: typography.bodyBold, fontSize: 16, color: colors.ink },
  note: {
    width: '100%',
    marginTop: 12,
    backgroundColor: colors.brassSoft,
    borderRadius: radii.md,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  noteText: { textAlign: 'center', color: colors.ink, fontFamily: typography.body, fontSize: 13 },
  noteStrong: { fontFamily: typography.bodyBold, color: colors.ink },
  thanks: {
    marginTop: 14,
    fontFamily: typography.display,
    fontSize: 16,
    color: colors.heroVia,
  },
  shop: {
    fontFamily: typography.displayBold,
    fontSize: 18,
    color: colors.heroFrom,
    letterSpacing: 0.4,
    textAlign: 'center',
  },
  actions: { flexDirection: 'row', gap: 8, marginTop: 16, width: '100%' },
  action: {
    flex: 1,
    minHeight: 48,
    borderRadius: radii.pill,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingHorizontal: 8,
  },
  actionJade: { backgroundColor: colors.jade },
  actionBrass: { backgroundColor: colors.brass },
  actionText: { color: colors.paper, fontFamily: typography.bodySemi, fontSize: 13 },
  actionTextInk: { color: colors.ink },
});

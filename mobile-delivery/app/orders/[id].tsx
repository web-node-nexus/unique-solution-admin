import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { useLocalSearchParams, useRouter } from 'expo-router';
import {
  Banknote,
  FileText,
  History,
  MapPin,
  Package,
  Phone,
  ShieldCheck,
  Truck,
  UserRound,
  X,
} from 'lucide-react-native';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { deliveryApi, type DeliveryOrder } from '@/api/delivery';
import {
  AppDialog,
  AppText,
  GhostButton,
  PressableScale,
  PrimaryButton,
  type AppDialogState,
  type DialogTone,
} from '@/components/ui';
import { colors, radii, spacing } from '@/theme/tokens';

function formatInr(n: number) {
  return `₹${Math.round(n).toLocaleString('en-IN')}`;
}

function formatDate(value?: string | null) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const closedDialog: AppDialogState = { visible: false, title: '', message: '' };

export default function DeliveryOrderDetail() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const [order, setOrder] = useState<DeliveryOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [otpOpen, setOtpOpen] = useState(false);
  const [otp, setOtp] = useState('');
  const [otpMasked, setOtpMasked] = useState<string | null>(null);
  const [secondsLeft, setSecondsLeft] = useState(0);
  const [dialog, setDialog] = useState<AppDialogState>(closedDialog);

  const showDialog = (title: string, message: string, tone: DialogTone = 'sky') => {
    setDialog({
      visible: true,
      title,
      message,
      tone,
      confirmLabel: 'OK',
      onConfirm: () => setDialog(closedDialog),
    });
  };

  const load = useCallback(async () => {
    if (!id) return;
    setLoading(true);
    try {
      const res = await deliveryApi.order(id);
      setOrder(res.data);
    } catch (e) {
      showDialog('Could not load', e instanceof Error ? e.message : 'Try again.', 'danger');
      router.back();
    } finally {
      setLoading(false);
    }
  }, [id, router]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (secondsLeft <= 0) return;
    const t = setTimeout(() => setSecondsLeft((s) => s - 1), 1000);
    return () => clearTimeout(t);
  }, [secondsLeft]);

  const markShipped = async () => {
    if (!order) return;
    setBusy(true);
    try {
      const res = await deliveryApi.markShipped(order.id);
      setOrder(res.data);
      void Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      showDialog('Out for delivery', 'Customer can see the order is on the way.', 'amber');
    } catch (e) {
      showDialog('Update failed', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  const sendCustomerOtp = async () => {
    if (!order) return;
    setBusy(true);
    try {
      const res = await deliveryApi.sendDeliveryOtp(order.id);
      setOtpMasked(res.data.phone_masked);
      setSecondsLeft(60);
      setOtpOpen(true);
      void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    } catch (e) {
      showDialog('OTP failed', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  const confirmDelivered = async () => {
    if (!order) return;
    if (otp.trim().length < 4) {
      showDialog('OTP needed', 'Enter the OTP from the customer SMS.', 'amber');
      return;
    }
    setBusy(true);
    try {
      const res = await deliveryApi.confirmDelivery(order.id, otp.trim());
      setOrder(res.data);
      setOtpOpen(false);
      setOtp('');
      void Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      showDialog('Delivered', 'OTP verified. Order marked delivered.', 'jade');
      setTimeout(() => router.replace('/(main)/delivered'), 600);
    } catch (e) {
      showDialog('Verify failed', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  const openBill = async () => {
    if (!order) return;
    setBusy(true);
    try {
      const res = await deliveryApi.invoice(order.id);
      await Linking.openURL(res.data.invoice_url);
    } catch (e) {
      showDialog('Bill unavailable', e instanceof Error ? e.message : 'Try again.', 'danger');
    } finally {
      setBusy(false);
    }
  };

  if (loading || !order) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color={colors.sky} size="large" />
      </View>
    );
  }

  const status = order.order_status.toLowerCase();

  return (
    <View style={{ flex: 1, backgroundColor: colors.canvas }}>
      <ScrollView
        contentContainerStyle={[styles.wrap, { paddingBottom: Math.max(insets.bottom, 16) + 120 }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.hero}>
          <View style={styles.heroTop}>
            <View style={{ flex: 1 }}>
              <AppText style={styles.heroEyebrow}>Delivery job</AppText>
              <AppText style={styles.heroNumber}>{order.order_number}</AppText>
            </View>
            <View
              style={[
                styles.statusChip,
                {
                  backgroundColor:
                    status === 'delivered'
                      ? colors.jadeSoft
                      : status === 'shipped'
                        ? colors.amberSoft
                        : colors.skySoft,
                },
              ]}
            >
              <AppText
                style={[
                  styles.statusChipText,
                  {
                    color:
                      status === 'delivered'
                        ? colors.jade
                        : status === 'shipped'
                          ? colors.amber
                          : colors.sky,
                  },
                ]}
              >
                {status === 'shipped' ? 'Out for delivery' : order.order_status}
              </AppText>
            </View>
          </View>
          <AppText style={styles.heroAmount}>{formatInr(order.total_amount)}</AppText>
          <View style={styles.heroMetaRow}>
            <View style={styles.metaPill}>
              <Banknote size={14} color={colors.skyDeep} strokeWidth={2.2} />
              <AppText style={styles.metaPillText}>
                {(order.payment_method || order.payment_status || 'pay').toUpperCase()}
              </AppText>
            </View>
            <View style={styles.metaPill}>
              <Package size={14} color={colors.skyDeep} strokeWidth={2.2} />
              <AppText style={styles.metaPillText}>{order.items_count} items</AppText>
            </View>
          </View>
        </View>

        <View style={styles.card}>
          <View style={styles.sectionHead}>
            <UserRound size={18} color={colors.sky} strokeWidth={2.2} />
            <AppText style={styles.sectionTitle}>Customer</AppText>
          </View>
          <AppText style={styles.customerName}>{order.customer_name || '—'}</AppText>
          {order.customer_email ? <AppText style={styles.soft}>{order.customer_email}</AppText> : null}
          {order.customer_phone ? (
            <PressableScale
              style={styles.callBtn}
              onPress={() => Linking.openURL(`tel:${order.customer_phone}`)}
            >
              <Phone size={16} color="#fff" strokeWidth={2.3} />
              <AppText style={styles.callBtnText}>{order.customer_phone}</AppText>
            </PressableScale>
          ) : null}
        </View>

        <View style={styles.card}>
          <View style={styles.sectionHead}>
            <MapPin size={18} color={colors.violet} strokeWidth={2.2} />
            <AppText style={styles.sectionTitle}>Delivery address</AppText>
          </View>
          <AppText style={styles.address}>{order.shipping_address || 'No address'}</AppText>
          {order.notes ? (
            <View style={styles.noteBox}>
              <AppText style={styles.noteLabel}>Note</AppText>
              <AppText style={styles.soft}>{order.notes}</AppText>
            </View>
          ) : null}
        </View>

        <View style={styles.card}>
          <View style={styles.sectionHead}>
            <Package size={18} color={colors.amber} strokeWidth={2.2} />
            <AppText style={styles.sectionTitle}>Products</AppText>
          </View>
          {(order.items ?? []).map((item) => (
            <View key={item.id} style={styles.itemCard}>
              {item.image_url ? (
                <Image source={{ uri: item.image_url }} style={styles.itemImg} contentFit="cover" />
              ) : (
                <View style={[styles.itemImg, styles.itemImgFallback]}>
                  <Package size={20} color={colors.inkMuted} />
                </View>
              )}
              <View style={{ flex: 1, minWidth: 0 }}>
                {item.brand_name ? <AppText style={styles.brand}>{item.brand_name}</AppText> : null}
                <AppText style={styles.itemName}>{item.product_name}</AppText>
                {item.variant_label ? <AppText style={styles.soft}>{item.variant_label}</AppText> : null}
                {item.sku ? <AppText style={styles.sku}>SKU {item.sku}</AppText> : null}
                <View style={styles.itemFooter}>
                  <AppText style={styles.qty}>Qty {item.quantity}</AppText>
                  <AppText style={styles.itemPrice}>{formatInr(item.total_price)}</AppText>
                </View>
              </View>
            </View>
          ))}
          <View style={styles.totals}>
            <Row label="Subtotal" value={formatInr(order.subtotal ?? order.total_amount)} />
            {(order.discount ?? 0) > 0 ? <Row label="Discount" value={`- ${formatInr(order.discount!)}`} /> : null}
            {(order.shipping_charge ?? 0) > 0 ? (
              <Row label="Shipping" value={formatInr(order.shipping_charge!)} />
            ) : (
              <Row label="Shipping" value="Free" />
            )}
            <Row label="Total" value={formatInr(order.total_amount)} bold />
          </View>
        </View>

        <View style={styles.card}>
          <View style={styles.sectionHead}>
            <History size={18} color={colors.skyDeep} strokeWidth={2.2} />
            <AppText style={styles.sectionTitle}>Customer order history</AppText>
          </View>
          {(order.customer_order_history ?? []).length === 0 ? (
            <AppText style={styles.soft}>No earlier orders for this customer.</AppText>
          ) : (
            (order.customer_order_history ?? []).map((past) => (
              <View key={past.id} style={styles.historyRow}>
                <View style={{ flex: 1 }}>
                  <AppText style={styles.historyNo}>{past.order_number}</AppText>
                  <AppText style={styles.soft}>{formatDate(past.created_at)}</AppText>
                </View>
                <View style={{ alignItems: 'flex-end' }}>
                  <AppText style={styles.historyAmt}>{formatInr(past.total_amount)}</AppText>
                  <AppText style={styles.soft}>{past.order_status}</AppText>
                </View>
              </View>
            ))
          )}
        </View>

        <PressableScale style={styles.billBtn} onPress={openBill} disabled={busy}>
          <FileText size={18} color={colors.skyDeep} strokeWidth={2.2} />
          <AppText style={styles.billBtnText}>View bill / invoice</AppText>
        </PressableScale>
      </ScrollView>

      {order.can_mark_shipped || order.can_mark_delivered ? (
        <View style={[styles.actionBar, { paddingBottom: Math.max(insets.bottom, 12) }]}>
          {order.can_mark_shipped ? (
            <PrimaryButton
              label={busy ? 'Updating…' : 'Out for delivery'}
              tone="amber"
              disabled={busy}
              onPress={markShipped}
            />
          ) : null}
          {order.can_mark_delivered ? (
            <PrimaryButton
              label={busy ? 'Please wait…' : 'Deliver with customer OTP'}
              tone="jade"
              disabled={busy}
              onPress={() => {
                setOtpOpen(true);
                if (!otpMasked) void sendCustomerOtp();
              }}
            />
          ) : null}
        </View>
      ) : null}

      <Modal visible={otpOpen} transparent animationType="slide" onRequestClose={() => setOtpOpen(false)}>
        <View style={styles.sheetBackdrop}>
          <Pressable style={StyleSheet.absoluteFill} onPress={() => setOtpOpen(false)} />
          <View style={[styles.sheet, { paddingBottom: Math.max(insets.bottom, 16) }]}>
            <View style={styles.sheetHandle} />
            <View style={styles.sheetHead}>
              <View style={styles.sectionHead}>
                <ShieldCheck size={20} color={colors.jade} strokeWidth={2.2} />
                <AppText style={styles.sectionTitle}>Customer OTP</AppText>
              </View>
              <PressableScale onPress={() => setOtpOpen(false)} style={styles.close}>
                <X size={18} color={colors.ink} />
              </PressableScale>
            </View>
            <AppText style={styles.soft}>
              An OTP will be sent to the customer phone. Enter that code here to complete delivery.
            </AppText>
            {otpMasked ? (
              <AppText style={styles.masked}>Sent to {otpMasked}</AppText>
            ) : (
              <AppText style={styles.soft}>Tap send to message the customer.</AppText>
            )}
            <TextInput
              value={otp}
              onChangeText={setOtp}
              keyboardType="number-pad"
              maxLength={8}
              placeholder="Enter customer OTP"
              placeholderTextColor={colors.inkMuted}
              style={styles.otpInput}
            />
            <PrimaryButton
              label={busy ? 'Verifying…' : 'Verify & mark delivered'}
              tone="jade"
              disabled={busy}
              onPress={confirmDelivered}
            />
            {secondsLeft > 0 ? (
              <AppText style={styles.timer}>Resend OTP in 0:{String(secondsLeft).padStart(2, '0')}</AppText>
            ) : (
              <GhostButton
                label={busy ? 'Sending…' : otpMasked ? 'Resend OTP' : 'Send OTP to customer'}
                disabled={busy}
                onPress={sendCustomerOtp}
              />
            )}
            <View style={styles.tip}>
              <Truck size={16} color={colors.sky} />
              <AppText style={styles.tipText}>Ask the customer to read the SMS code aloud.</AppText>
            </View>
          </View>
        </View>
      </Modal>

      <AppDialog {...dialog} />
    </View>
  );
}

function Row({ label, value, bold }: { label: string; value: string; bold?: boolean }) {
  return (
    <View style={styles.totalRow}>
      <AppText style={bold ? [styles.soft, styles.bold] : styles.soft}>{label}</AppText>
      <AppText style={bold ? [styles.totalValue, styles.bold] : styles.totalValue}>{value}</AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.canvas },
  wrap: { padding: spacing.md, gap: 12 },
  hero: {
    backgroundColor: colors.skyDeep,
    borderRadius: radii.xl,
    padding: spacing.lg,
    gap: 10,
  },
  heroTop: { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  heroEyebrow: { color: 'rgba(255,255,255,0.72)', fontSize: 12, fontWeight: '700' },
  heroNumber: { color: '#fff', fontSize: 20, fontWeight: '800', marginTop: 2 },
  heroAmount: { color: '#fff', fontSize: 28, fontWeight: '800' },
  heroMetaRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  metaPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: 'rgba(255,255,255,0.92)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: radii.pill,
  },
  metaPillText: { color: colors.skyDeep, fontWeight: '700', fontSize: 12 },
  statusChip: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: radii.pill },
  statusChipText: { fontWeight: '800', fontSize: 12, textTransform: 'capitalize' },
  card: {
    backgroundColor: colors.paper,
    borderRadius: radii.xl,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 10,
  },
  sectionHead: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sectionTitle: { fontSize: 15, fontWeight: '800', color: colors.ink },
  customerName: { fontSize: 18, fontWeight: '800' },
  soft: { color: colors.inkMuted, fontSize: 13, lineHeight: 18 },
  callBtn: {
    marginTop: 4,
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.sky,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: radii.pill,
  },
  callBtnText: { color: '#fff', fontWeight: '800' },
  address: { fontSize: 15, lineHeight: 22, color: colors.ink, fontWeight: '600' },
  noteBox: {
    marginTop: 4,
    backgroundColor: colors.amberSoft,
    borderRadius: radii.md,
    padding: 12,
    gap: 4,
  },
  noteLabel: { fontWeight: '800', color: colors.amber, fontSize: 12 },
  itemCard: {
    flexDirection: 'row',
    gap: 12,
    paddingVertical: 10,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  itemImg: { width: 72, height: 72, borderRadius: 14, backgroundColor: colors.canvasDeep },
  itemImgFallback: { alignItems: 'center', justifyContent: 'center' },
  brand: { color: colors.sky, fontWeight: '800', fontSize: 11, textTransform: 'uppercase' },
  itemName: { fontWeight: '800', fontSize: 15, color: colors.ink },
  sku: { color: colors.inkMuted, fontSize: 11, marginTop: 2 },
  itemFooter: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 6 },
  qty: { fontWeight: '700', color: colors.inkMuted },
  itemPrice: { fontWeight: '800', color: colors.sky },
  totals: { gap: 6, marginTop: 4, paddingTop: 10, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between' },
  totalValue: { fontWeight: '700', color: colors.ink },
  bold: { fontWeight: '800', color: colors.ink, fontSize: 15 },
  historyRow: {
    flexDirection: 'row',
    gap: 12,
    paddingVertical: 10,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  historyNo: { fontWeight: '800' },
  historyAmt: { fontWeight: '800', color: colors.sky },
  billBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.skySoft,
    borderRadius: radii.lg,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  billBtnText: { color: colors.skyDeep, fontWeight: '800', fontSize: 15 },
  actionBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.md,
    paddingTop: 12,
    gap: 10,
    backgroundColor: 'rgba(238,243,251,0.96)',
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
  },
  sheetBackdrop: { flex: 1, justifyContent: 'flex-end', backgroundColor: colors.overlay },
  sheet: {
    backgroundColor: colors.paper,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    gap: 12,
  },
  sheetHandle: {
    alignSelf: 'center',
    width: 42,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#CBD5E1',
    marginBottom: 8,
  },
  sheetHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  close: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.canvasDeep,
    alignItems: 'center',
    justifyContent: 'center',
  },
  masked: { fontWeight: '800', color: colors.jade },
  otpInput: {
    borderWidth: 1.5,
    borderColor: colors.border,
    borderRadius: radii.md,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 22,
    letterSpacing: 4,
    fontWeight: '700',
    color: colors.ink,
    backgroundColor: colors.canvas,
  },
  timer: { textAlign: 'center', color: colors.inkMuted, fontWeight: '700' },
  tip: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
    backgroundColor: colors.skySoft,
    borderRadius: radii.md,
    padding: 12,
  },
  tipText: { flex: 1, color: colors.skyDeep, fontWeight: '600', fontSize: 13 },
});

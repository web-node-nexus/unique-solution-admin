import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as WebBrowser from 'expo-web-browser';
import { useLocalSearchParams, router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { accountApi } from '@/api/account';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { OrderProductRow } from '@/components/orders/OrderProductRow';
import { OrderTracker } from '@/components/orders/OrderTracker';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr } from '@/utils/price';

export default function OrderDetailScreen() {
  const {
    id,
    paid,
    razorpay_order_id,
    razorpay_payment_id,
    razorpay_signature,
  } = useLocalSearchParams<{
    id: string;
    paid?: string;
    razorpay_order_id?: string;
    razorpay_payment_id?: string;
    razorpay_signature?: string;
  }>();
  const qc = useQueryClient();
  const [returnOpen, setReturnOpen] = useState(false);
  const [returnReason, setReturnReason] = useState('');
  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState('');

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['order', id],
    queryFn: async () => (await accountApi.order(id)).data,
    enabled: !!id,
  });

  useEffect(() => {
    const run = async () => {
      if (
        id &&
        razorpay_order_id &&
        razorpay_payment_id &&
        razorpay_signature &&
        accountApi.verifyPayment
      ) {
        try {
          await accountApi.verifyPayment(id, {
            razorpay_order_id: String(razorpay_order_id),
            razorpay_payment_id: String(razorpay_payment_id),
            razorpay_signature: String(razorpay_signature),
          });
          await refetch();
          Alert.alert('Payment received', 'Thanks — your order is confirmed.');
          return;
        } catch {
          // Fall through to paid flag handling.
        }
      }
      if (paid === '1') {
        void refetch();
        Alert.alert('Payment received', 'Thanks — your order is confirmed.');
      } else if (paid === '0') {
        Alert.alert('Payment incomplete', 'You can retry payment from this screen.');
      }
    };
    void run();
  }, [id, paid, razorpay_order_id, razorpay_payment_id, razorpay_signature, refetch]);

  const cancel = useMutation({
    mutationFn: (reason: string) => accountApi.cancelOrder(id!, reason),
    onSuccess: () => {
      setCancelOpen(false);
      setCancelReason('');
      qc.invalidateQueries({ queryKey: ['orders'] });
      qc.invalidateQueries({ queryKey: ['order', id] });
      Alert.alert('Cancelled', 'Your order was cancelled.');
    },
    onError: (e: Error) => Alert.alert('Could not cancel', e.message),
  });

  const requestReturn = useMutation({
    mutationFn: () => accountApi.requestReturn(id!, { reason: returnReason.trim() }),
    onSuccess: () => {
      setReturnOpen(false);
      setReturnReason('');
      qc.invalidateQueries({ queryKey: ['order', id] });
      Alert.alert('Return requested', 'We will review your return shortly.');
    },
    onError: (e: Error) => Alert.alert('Return failed', e.message),
  });

  const downloadInvoice = async () => {
    try {
      const res = await accountApi.invoice(id!);
      await WebBrowser.openBrowserAsync(res.data.invoice_url);
    } catch (e) {
      Alert.alert('Invoice unavailable', e instanceof Error ? e.message : 'Try again later.');
    }
  };

  const reviewableItems = (data?.items ?? []).filter((item) => item.product_id);

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Order" title={data?.order_number ?? 'Details'} />

      {isLoading || !data ? (
        <ActivityIndicator color={colors.jade} style={{ marginTop: 40 }} />
      ) : (
        <ScrollView
          contentContainerStyle={{ padding: spacing.lg, gap: 14, paddingBottom: 40 }}
          refreshControl={
            <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
          }
        >
          <View style={[themeCard.panel, styles.heroCard]}>
            <View style={styles.heroTop}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <View style={styles.statusPill}>
                  <AppText style={styles.status}>
                    {data.order_status.replace(/_/g, ' ')}
                  </AppText>
                </View>
                <AppText variant="caption" style={{ marginTop: 8 }}>
                  Payment · {data.payment_status} · {data.payment?.method ?? 'cod'}
                </AppText>
              </View>
              <AppText style={styles.heroTotal}>{formatInr(data.total_amount)}</AppText>
            </View>
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Items</AppText>
            {(data.items ?? []).map((item) => (
              <View key={item.id} style={styles.itemBlock}>
                <OrderProductRow item={item} showPrice />
                {data.can_review && item.product_id ? (
                  <PressableScale
                    onPress={() => router.push(`/products/${item.product_id}`)}
                    style={styles.reviewLink}
                  >
                    <AppText style={styles.reviewText}>Write a review</AppText>
                  </PressableScale>
                ) : null}
              </View>
            ))}
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Delivery</AppText>
            <AppText variant="caption">Delivery person</AppText>
            <AppText style={styles.itemName}>
              {data.delivery_person?.trim() || 'Not assigned yet'}
            </AppText>
            <AppText variant="body" style={{ marginTop: 8 }}>
              {data.shipping_address}
            </AppText>
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Totals</AppText>
            <View style={styles.sumRow}>
              <AppText style={styles.sumLabel}>Subtotal</AppText>
              <AppText style={styles.sumValue}>{formatInr(Number(data.subtotal ?? 0))}</AppText>
            </View>
            {Number(data.discount ?? 0) > 0 ? (
              <View style={styles.sumRow}>
                <AppText style={styles.sumLabel}>Discount</AppText>
                <AppText style={[styles.sumValue, { color: colors.jade }]}>
                  −{formatInr(Number(data.discount))}
                </AppText>
              </View>
            ) : null}
            {Number(data.tax ?? 0) > 0 ? (
              <View style={styles.sumRow}>
                <AppText style={styles.sumLabel}>Tax</AppText>
                <AppText style={styles.sumValue}>{formatInr(Number(data.tax))}</AppText>
              </View>
            ) : null}
            <View style={styles.sumRow}>
              <AppText style={styles.sumLabel}>Shipping</AppText>
              <AppText style={styles.sumValue}>
                {Number(data.shipping_charge ?? 0) > 0 ? formatInr(Number(data.shipping_charge)) : 'Free'}
              </AppText>
            </View>
            <AppText style={styles.gstNote}>Prices include GST</AppText>
            <View style={styles.totalBand}>
              <AppText style={styles.totalLabel}>Total</AppText>
              <AppText style={styles.totalAmount}>{formatInr(data.total_amount)}</AppText>
            </View>
          </View>

          {(data.refunds ?? []).length ? (
            <View style={[themeCard.panel, styles.cardPad]}>
              <AppText style={styles.heading}>Returns / refunds</AppText>
              {data.refunds!.map((r) => (
                <View key={r.id} style={{ marginTop: 10 }}>
                  <AppText style={styles.itemName}>{r.status.toUpperCase()}</AppText>
                  <AppText variant="caption">
                    {formatInr(r.refund_amount)}
                    {r.reason ? ` · ${r.reason}` : ''}
                  </AppText>
                </View>
              ))}
            </View>
          ) : null}

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Tracking</AppText>
            <OrderTracker status={data.order_status} timeline={data.timeline} />
          </View>

          <View style={{ gap: 10 }}>
            {data.payment?.needs_payment && data.payment.payment_url ? (
              <AppButton
                label="Complete payment"
                onPress={async () => {
                  await WebBrowser.openBrowserAsync(data.payment!.payment_url!);
                  void refetch();
                }}
              />
            ) : null}
            {data.can_invoice ? (
              <AppButton label="Download invoice" variant="ghost" onPress={downloadInvoice} />
            ) : null}
            {data.can_return ? (
              <AppButton label="Request return" variant="ghost" onPress={() => setReturnOpen(true)} />
            ) : null}
            {data.can_review && reviewableItems.length > 0 ? (
              <AppButton
                label="Review products"
                variant="brass"
                onPress={() => {
                  const first = reviewableItems[0];
                  if (first?.product_id) router.push(`/products/${first.product_id}`);
                }}
              />
            ) : null}
            {data.can_cancel ? (
              <AppButton label="Cancel order" variant="ghost" onPress={() => setCancelOpen(true)} />
            ) : null}
          </View>
        </ScrollView>
      )}

      <Modal visible={cancelOpen} transparent animationType="fade" onRequestClose={() => setCancelOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <AppText style={styles.heading}>Cancel order</AppText>
            <AppText variant="caption" style={{ marginBottom: 10 }}>
              Tell us why you want to cancel. This cannot be undone.
            </AppText>
            <TextInput
              value={cancelReason}
              onChangeText={setCancelReason}
              placeholder="Cancel reason"
              placeholderTextColor={colors.inkSoft}
              multiline
              style={styles.returnInput}
            />
            <View style={{ gap: 8, marginTop: 12 }}>
              <AppButton
                label={cancel.isPending ? 'Cancelling…' : 'Cancel order'}
                onPress={() => {
                  if (cancelReason.trim().length < 3) {
                    Alert.alert('Reason needed', 'Please enter a short reason.');
                    return;
                  }
                  cancel.mutate(cancelReason.trim());
                }}
              />
              <AppButton label="Keep order" variant="ghost" onPress={() => setCancelOpen(false)} />
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={returnOpen} transparent animationType="fade" onRequestClose={() => setReturnOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <AppText style={styles.heading}>Request return</AppText>
            <AppText variant="caption" style={{ marginBottom: 10 }}>
              Tell us why you want to return this order.
            </AppText>
            <TextInput
              value={returnReason}
              onChangeText={setReturnReason}
              placeholder="Reason"
              placeholderTextColor={colors.inkSoft}
              multiline
              style={styles.returnInput}
            />
            <View style={{ gap: 8, marginTop: 12 }}>
              <AppButton
                label={requestReturn.isPending ? 'Submitting…' : 'Submit return'}
                onPress={() => {
                  if (returnReason.trim().length < 3) {
                    Alert.alert('Reason needed', 'Please enter a short reason.');
                    return;
                  }
                  requestReturn.mutate();
                }}
              />
              <AppButton label="Cancel" variant="ghost" onPress={() => setReturnOpen(false)} />
            </View>
          </View>
        </View>
      </Modal>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  cardPad: { padding: spacing.md },
  heroCard: { padding: spacing.md },
  heroTop: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 },
  statusPill: {
    alignSelf: 'flex-start',
    backgroundColor: colors.jadeSoft,
    borderRadius: radii.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  status: {
    fontFamily: typography.bodyBold,
    color: colors.jadeDeep,
    letterSpacing: 0.6,
    fontSize: 12,
    textTransform: 'uppercase',
  },
  heroTotal: { fontFamily: typography.displayBold, fontSize: 22, color: colors.ink },
  heading: { fontFamily: typography.bodySemi, marginBottom: 8, color: colors.ink, fontSize: 16 },
  sumRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 10,
    gap: 12,
  },
  sumLabel: { fontFamily: typography.body, color: colors.inkMuted, fontSize: 14 },
  sumValue: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 14 },
  gstNote: { marginTop: 8, fontFamily: typography.body, color: colors.inkSoft, fontSize: 12 },
  totalBand: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.jadeSoft,
    borderRadius: radii.md,
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  totalLabel: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 15 },
  totalAmount: { fontFamily: typography.displayBold, fontSize: 20, color: colors.jadeDeep },
  itemBlock: { marginTop: 12, gap: 6 },
  reviewLink: { marginLeft: 66 },
  reviewText: { color: colors.jade, fontFamily: typography.bodySemi, fontSize: 13 },
  itemName: { fontFamily: typography.bodySemi, color: colors.ink },
  price: { fontFamily: typography.bodyBold, color: colors.ink },
  modalBackdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalCard: {
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.lg,
    ...elevation.lift,
  },
  returnInput: {
    minHeight: 100,
    textAlignVertical: 'top',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    padding: 12,
    fontFamily: typography.body,
    color: colors.ink,
    backgroundColor: colors.canvas,
  },
});

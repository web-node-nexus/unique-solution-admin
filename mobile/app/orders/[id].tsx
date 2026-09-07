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
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useCartStore } from '@/store/cart';
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
  const hydrateCart = useCartStore((s) => s.hydrateFromServer);
  const [returnOpen, setReturnOpen] = useState(false);
  const [returnReason, setReturnReason] = useState('');

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
    mutationFn: () => accountApi.cancelOrder(id!, 'Cancelled by customer'),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['orders'] });
      qc.invalidateQueries({ queryKey: ['order', id] });
      Alert.alert('Cancelled', 'Your order was cancelled.');
    },
    onError: (e: Error) => Alert.alert('Could not cancel', e.message),
  });

  const reorder = useMutation({
    mutationFn: () => accountApi.reorder(id!),
    onSuccess: async () => {
      await hydrateCart();
      Alert.alert('Added to bag', 'Items from this order were added to your cart.', [
        { text: 'View cart', onPress: () => router.push('/(main)/(tabs)/cart') },
        { text: 'OK' },
      ]);
    },
    onError: (e: Error) => Alert.alert('Reorder failed', e.message),
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
          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.status}>{data.order_status.toUpperCase()}</AppText>
            <AppText variant="caption">
              Payment: {data.payment_status} · {data.payment?.method ?? 'cod'}
            </AppText>
            <AppText style={styles.total}>{formatInr(data.total_amount)}</AppText>
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Items</AppText>
            {(data.items ?? []).map((item) => (
              <View key={item.id} style={styles.line}>
                <View style={{ flex: 1, minWidth: 0 }}>
                  <AppText style={styles.itemName} numberOfLines={2}>
                    {item.product_name}
                  </AppText>
                  <AppText variant="caption">Qty {item.quantity}</AppText>
                  {data.can_review && item.product_id ? (
                    <PressableScale
                      onPress={() => router.push(`/products/${item.product_id}`)}
                      style={{ marginTop: 4 }}
                    >
                      <AppText style={{ color: colors.jade, fontFamily: typography.bodySemi, fontSize: 13 }}>
                        Write a review
                      </AppText>
                    </PressableScale>
                  ) : null}
                </View>
                <AppText style={styles.price} numberOfLines={1}>
                  {formatInr(item.subtotal)}
                </AppText>
              </View>
            ))}
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Delivery</AppText>
            <AppText variant="body">{data.shipping_address}</AppText>
          </View>

          <View style={[themeCard.panel, styles.cardPad]}>
            <AppText style={styles.heading}>Totals</AppText>
            {(
              [
                ['Subtotal', data.subtotal],
                ['Discount', data.discount],
                ['Tax', data.tax],
                ['Shipping', data.shipping_charge],
                ['Total', data.total_amount],
              ] as const
            ).map(([label, value]) => (
              <View key={String(label)} style={styles.line}>
                <AppText variant="caption">{String(label)}</AppText>
                <AppText style={styles.price}>{formatInr(Number(value ?? 0))}</AppText>
              </View>
            ))}
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

          {(data.timeline ?? []).length ? (
            <View style={[themeCard.panel, styles.cardPad]}>
              <AppText style={styles.heading}>Tracking</AppText>
              {data.timeline!.map((t, i) => (
                <View key={`${t.status}-${i}`} style={{ marginTop: 10 }}>
                  <AppText style={styles.itemName}>{t.status}</AppText>
                  <AppText variant="caption">{t.remarks || t.at}</AppText>
                </View>
              ))}
            </View>
          ) : null}

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
              <AppButton
                label="Cancel order"
                variant="ghost"
                onPress={() =>
                  Alert.alert('Cancel order?', 'This cannot be undone.', [
                    { text: 'Keep', style: 'cancel' },
                    { text: 'Cancel order', style: 'destructive', onPress: () => cancel.mutate() },
                  ])
                }
              />
            ) : null}
            {data.can_reorder ? (
              <AppButton label="Reorder" variant="brass" onPress={() => reorder.mutate()} />
            ) : null}
          </View>
        </ScrollView>
      )}

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
  status: {
    fontFamily: typography.bodyBold,
    color: colors.jade,
    letterSpacing: 1,
    marginBottom: 4,
  },
  total: { fontFamily: typography.display, fontSize: 28, marginTop: 8, color: colors.jade },
  heading: { fontFamily: typography.bodySemi, marginBottom: 6, color: colors.ink },
  line: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 8 },
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

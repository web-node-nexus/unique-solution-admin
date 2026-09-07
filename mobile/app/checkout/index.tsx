import { useQuery, useQueryClient } from '@tanstack/react-query';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import {
  Banknote,
  Check,
  CreditCard,
  MapPin,
  Plus,
  Smartphone,
  Ticket,
} from 'lucide-react-native';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { accountApi } from '@/api/account';
import { catalogApi, type CouponPreview } from '@/api/catalog';
import { KeyboardForm } from '@/components/layout/KeyboardForm';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { useCartStore } from '@/store/cart';
import { useShopStore } from '@/store/shop';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';
import { formatInr, sellingPrice } from '@/utils/price';
import { track } from '@/utils/analytics';

type PayMethod = 'cod' | 'razorpay' | 'upi';

function parseQuery(url: string): Record<string, string> {
  try {
    const parsed = Linking.parse(url);
    const q = (parsed.queryParams ?? {}) as Record<string, string | string[] | undefined>;
    const out: Record<string, string> = {};
    Object.entries(q).forEach(([k, v]) => {
      if (Array.isArray(v)) out[k] = String(v[0] ?? '');
      else if (v != null) out[k] = String(v);
    });
    return out;
  } catch {
    return {};
  }
}

export default function CheckoutScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ coupon?: string }>();
  const user = useAuthStore((s) => s.user);
  const { lines, clear, subtotal } = useCartStore();
  const shop = useShopStore((s) => s.shop);
  const loadShop = useShopStore((s) => s.load);
  const qc = useQueryClient();
  const [addressId, setAddressId] = useState<number | null>(null);
  const [coupon, setCoupon] = useState('');
  const [notes, setNotes] = useState('');
  const [method, setMethod] = useState<PayMethod>('cod');
  const [busy, setBusy] = useState(false);
  const [couponBusy, setCouponBusy] = useState(false);
  const [couponPreview, setCouponPreview] = useState<CouponPreview | null>(null);
  const [couponError, setCouponError] = useState<string | null>(null);

  useEffect(() => {
    void loadShop();
  }, [loadShop]);

  useEffect(() => {
    const code =
      typeof params.coupon === 'string'
        ? params.coupon
        : Array.isArray(params.coupon)
          ? params.coupon[0]
          : '';
    if (code) setCoupon(String(code).toUpperCase());
  }, [params.coupon]);

  const { data: addresses, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await accountApi.addresses()).data,
    enabled: !!user,
  });

  useFocusEffect(
    useCallback(() => {
      if (!user) return;
      void qc.invalidateQueries({ queryKey: ['addresses'] });
      void refetch();
    }, [user, qc, refetch]),
  );

  const selected = useMemo(() => {
    if (!addresses?.length) return null;
    return (
      addresses.find((a) => a.id === addressId) ??
      addresses.find((a) => a.is_default) ??
      addresses[0]
    );
  }, [addresses, addressId]);

  useEffect(() => {
    if (selected && addressId == null) setAddressId(selected.id);
  }, [selected, addressId]);

  const cartSubtotal = subtotal();
  const taxPct = Number(shop?.tax_percentage ?? 0);
  const shipping = Number(
    couponPreview?.shipping_charge ?? shop?.default_shipping_charge ?? 0,
  );
  const discountPreview = couponPreview?.discount_amount ?? 0;
  const taxPreview = couponPreview
    ? couponPreview.tax
    : Math.round(((cartSubtotal * taxPct) / 100) * 100) / 100;
  const totalPreview = couponPreview
    ? couponPreview.total
    : Math.round((cartSubtotal + taxPreview + shipping) * 100) / 100;

  const applyCoupon = async () => {
    const code = coupon.trim();
    if (!code) {
      Alert.alert('Coupon', 'Enter a coupon code first.');
      return;
    }
    setCouponBusy(true);
    setCouponError(null);
    try {
      const res = await catalogApi.previewCoupon(code, cartSubtotal);
      setCouponPreview(res.data);
      setCoupon(res.data.code);
    } catch (e) {
      setCouponPreview(null);
      setCouponError(e instanceof Error ? e.message : 'Invalid coupon');
    } finally {
      setCouponBusy(false);
    }
  };

  if (!user) {
    return (
      <ScreenShell>
        <ScreenHeader showBack eyebrow="Checkout" title="Sign in required" />
        <View style={styles.gate}>
          <AppText style={styles.gateTitle}>Sign in to checkout</AppText>
          <AppText variant="caption" style={{ textAlign: 'center' }}>
            Your bag stays saved on this phone. Sign in to choose an address and place the order.
          </AppText>
          <AppButton label="Sign in" onPress={() => router.push('/auth/login')} />
          <AppButton
            label="Create account"
            variant="ghost"
            onPress={() => router.push('/auth/register')}
          />
        </View>
      </ScreenShell>
    );
  }

  if (!lines.length) {
    return (
      <ScreenShell>
        <ScreenHeader showBack eyebrow="Checkout" title="Empty bag" />
        <View style={styles.gate}>
          <AppText style={styles.gateTitle}>Nothing to checkout</AppText>
          <AppButton label="Browse products" onPress={() => router.replace('/products')} />
        </View>
      </ScreenShell>
    );
  }

  const placeOrder = async () => {
    if (!selected) {
      Alert.alert('Address needed', 'Add a delivery address first.');
      router.push('/addresses');
      return;
    }
    setBusy(true);
    try {
      const res = await accountApi.checkout({
        address_id: selected.id,
        coupon_code: coupon.trim() || undefined,
        notes: notes.trim() || undefined,
        payment_method: method,
        items: lines.map((l) => ({
          product_id: l.productId,
          product_variant_id: l.variantId ?? null,
          quantity: l.qty,
        })),
      });
      clear();
      void track('purchase', {
        order_id: res.data.id,
        payment_method: method,
        total: res.data.total_amount,
      });

      const session = res.data.payment_session;
      const payUrl = session?.payment_url ?? res.data.payment?.payment_url;
      if (method !== 'cod' && payUrl) {
        const result = await WebBrowser.openAuthSessionAsync(payUrl, 'uniquesolution://');
        if (result.type === 'success' && result.url) {
          const q = parseQuery(result.url);
          const orderId = res.data.id;
          const rzOrder = q.razorpay_order_id || session?.razorpay_order_id;
          const rzPay = q.razorpay_payment_id;
          const rzSig = q.razorpay_signature;
          if (rzOrder && rzPay && rzSig && accountApi.verifyPayment) {
            try {
              await accountApi.verifyPayment(orderId, {
                razorpay_order_id: rzOrder,
                razorpay_payment_id: rzPay,
                razorpay_signature: rzSig,
              });
            } catch {
              // continue
            }
          }
          router.replace({
            pathname: '/orders/[id]',
            params: {
              id: String(orderId),
              paid: q.paid ?? (rzPay ? '1' : '0'),
            },
          });
          return;
        }
      }
      router.replace(`/orders/${res.data.id}`);
    } catch (e) {
      Alert.alert('Checkout failed', e instanceof Error ? e.message : 'Try again');
    } finally {
      setBusy(false);
    }
  };

  const payLabel =
    method === 'cod' ? 'Place COD order' : method === 'upi' ? 'Pay with UPI' : 'Pay online';

  return (
    <ScreenShell>
      <ScreenHeader showBack eyebrow="Secure checkout" title="Checkout" />

      <KeyboardForm
        contentContainerStyle={{ padding: spacing.md, gap: 12, paddingBottom: 150 }}
        refreshControl={
          <AppRefreshControl
            refreshing={isRefetching}
            onRefresh={() => {
              void refetch();
              void loadShop();
            }}
          />
        }
      >
        {/* 1. Addresses */}
        <View style={[themeCard.panel, styles.cardPad]}>
          <View style={styles.cardHead}>
            <View style={styles.stepBadge}>
              <AppText style={styles.stepNum}>1</AppText>
            </View>
            <AppText style={styles.cardTitle}>Delivery address</AppText>
          </View>

          {isLoading ? <ActivityIndicator color={colors.jade} style={{ marginTop: 8 }} /> : null}

          {(addresses ?? []).map((a) => {
            const on = (selected?.id ?? null) === a.id;
            return (
              <PressableScale
                key={a.id}
                style={[styles.addrCard, on && styles.addrCardOn]}
                onPress={() => setAddressId(a.id)}
              >
                <View style={[styles.radio, on && styles.radioOn]}>
                  {on ? <Check size={12} color={colors.paper} strokeWidth={3} /> : null}
                </View>
                <View style={{ flex: 1, minWidth: 0 }}>
                  <View style={styles.addrTop}>
                    <AppText style={styles.addrLabel} numberOfLines={1}>
                      {a.label || 'Address'}
                    </AppText>
                    {a.is_default ? (
                      <View style={styles.defaultPill}>
                        <AppText style={styles.defaultText}>Default</AppText>
                      </View>
                    ) : null}
                  </View>
                  <AppText style={styles.addrBody} numberOfLines={3}>
                    {a.address}
                    {a.city ? `, ${a.city}` : ''}
                    {a.state ? `, ${a.state}` : ''}
                    {a.pincode ? ` · ${a.pincode}` : ''}
                  </AppText>
                </View>
              </PressableScale>
            );
          })}

          <PressableScale
            style={styles.addAddr}
            onPress={() => router.push('/addresses')}
          >
            <Plus size={16} color={colors.jade} strokeWidth={2.4} />
            <AppText style={styles.addAddrText}>
              {(addresses?.length ?? 0) > 0 ? 'Add another address' : 'Add delivery address'}
            </AppText>
          </PressableScale>

          {(addresses?.length ?? 0) > 0 ? (
            <PressableScale onPress={() => router.push('/addresses')} style={{ marginTop: 4 }}>
              <AppText style={styles.manageLink}>Manage saved addresses</AppText>
            </PressableScale>
          ) : null}
        </View>

        {/* 2. Payment */}
        <View style={[themeCard.panel, styles.cardPad]}>
          <View style={styles.cardHead}>
            <View style={styles.stepBadge}>
              <AppText style={styles.stepNum}>2</AppText>
            </View>
            <AppText style={styles.cardTitle}>Payment method</AppText>
          </View>
          <View style={styles.payRow}>
            {(
              [
                { id: 'cod' as const, label: 'Cash on delivery', hint: 'Pay when delivered', Icon: Banknote },
                { id: 'upi' as const, label: 'UPI', hint: 'GPay / PhonePe / BHIM', Icon: Smartphone },
                { id: 'razorpay' as const, label: 'Card / Netbanking', hint: 'Secure Razorpay', Icon: CreditCard },
              ] as const
            ).map((opt) => {
              const on = method === opt.id;
              return (
                <PressableScale
                  key={opt.id}
                  style={[styles.payOpt, on && styles.payOptOn]}
                  onPress={() => setMethod(opt.id)}
                >
                  <View style={[styles.payIcon, on && styles.payIconOn]}>
                    <opt.Icon size={18} color={on ? colors.paper : colors.ink} />
                  </View>
                  <View style={{ flex: 1, minWidth: 0 }}>
                    <AppText style={[styles.payLabel, on && { color: colors.jade }]}>
                      {opt.label}
                    </AppText>
                    <AppText style={styles.payHint}>{opt.hint}</AppText>
                  </View>
                  <View style={[styles.radio, on && styles.radioOn]}>
                    {on ? <Check size={12} color={colors.paper} strokeWidth={3} /> : null}
                  </View>
                </PressableScale>
              );
            })}
          </View>
        </View>

        {/* 3. Coupon + notes */}
        <View style={[themeCard.panel, styles.cardPad]}>
          <View style={styles.cardHead}>
            <View style={styles.stepBadge}>
              <AppText style={styles.stepNum}>3</AppText>
            </View>
            <Ticket size={16} color={colors.brass} />
            <AppText style={styles.cardTitle}>Coupon & notes</AppText>
          </View>
          <View style={styles.couponRow}>
            <TextInput
              value={coupon}
              onChangeText={(text) => {
                setCoupon(text.toUpperCase());
                setCouponPreview(null);
                setCouponError(null);
              }}
              placeholder="Coupon code"
              placeholderTextColor={colors.inkSoft}
              autoCapitalize="characters"
              style={[styles.input, { flex: 1, marginTop: 0 }]}
            />
            <PressableScale style={styles.applyBtn} onPress={applyCoupon}>
              {couponBusy ? (
                <ActivityIndicator color={colors.paper} size="small" />
              ) : (
                <AppText style={styles.applyText}>Apply</AppText>
              )}
            </PressableScale>
          </View>
          {couponError ? (
            <AppText style={{ color: colors.danger, marginTop: 8 }}>{couponError}</AppText>
          ) : null}
          {couponPreview ? (
            <AppText style={styles.couponOk}>
              {couponPreview.title || couponPreview.code} · −
              {formatInr(couponPreview.discount_amount)}
            </AppText>
          ) : null}
          <TextInput
            value={notes}
            onChangeText={setNotes}
            placeholder="Delivery notes (optional)"
            placeholderTextColor={colors.inkSoft}
            style={[styles.input, { marginTop: 10, minHeight: 72, textAlignVertical: 'top' }]}
            multiline
          />
        </View>

        {/* 4. Summary */}
        <View style={[themeCard.panel, styles.cardPad]}>
          <View style={styles.cardHead}>
            <View style={styles.stepBadge}>
              <AppText style={styles.stepNum}>4</AppText>
            </View>
            <AppText style={styles.cardTitle}>Order summary</AppText>
          </View>
          {lines.map((l) => (
            <View key={l.key} style={styles.line}>
              <AppText style={{ flex: 1, minWidth: 0, fontSize: 13 }} numberOfLines={2}>
                {l.name} × {l.qty}
              </AppText>
              <AppText style={styles.price}>
                {formatInr(sellingPrice(l.mrp, l.sale_price) * l.qty)}
              </AppText>
            </View>
          ))}
          <View style={styles.divider} />
          <View style={styles.line}>
            <AppText variant="caption">Subtotal</AppText>
            <AppText style={styles.price}>{formatInr(cartSubtotal)}</AppText>
          </View>
          {discountPreview > 0 ? (
            <View style={styles.line}>
              <AppText variant="caption">Discount</AppText>
              <AppText style={[styles.price, { color: colors.jade }]}>
                −{formatInr(discountPreview)}
              </AppText>
            </View>
          ) : null}
          {taxPreview > 0 ? (
            <View style={styles.line}>
              <AppText variant="caption">
                Tax{!couponPreview && taxPct > 0 ? ` (${taxPct}%)` : ''}
              </AppText>
              <AppText style={styles.price}>{formatInr(taxPreview)}</AppText>
            </View>
          ) : null}
          <View style={styles.line}>
            <AppText variant="caption">Shipping</AppText>
            <AppText style={styles.price}>
              {shipping > 0 ? formatInr(shipping) : 'Free'}
            </AppText>
          </View>
          <View style={[styles.line, { marginTop: 10 }]}>
            <AppText style={{ fontFamily: typography.bodySemi }}>Total payable</AppText>
            <AppText style={styles.total}>{formatInr(totalPreview)}</AppText>
          </View>
          {selected ? (
            <View style={styles.deliverHint}>
              <MapPin size={13} color={colors.jade} />
              <AppText style={styles.deliverHintText} numberOfLines={2}>
                Delivering to {selected.label || 'address'} · {selected.city || selected.pincode || 'Kurud'}
              </AppText>
            </View>
          ) : null}
        </View>
      </KeyboardForm>

      <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 14) }]}>
        <View style={styles.footerLeft}>
          <AppText variant="caption">Total</AppText>
          <AppText style={styles.footerTotal}>{formatInr(totalPreview)}</AppText>
        </View>
        <View style={{ flex: 1 }}>
          {busy ? (
            <ActivityIndicator color={colors.jade} />
          ) : (
            <AppButton label={payLabel} onPress={placeOrder} disabled={!selected} />
          )}
        </View>
      </View>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  gate: {
    paddingHorizontal: spacing.lg,
    gap: 12,
    alignItems: 'center',
    marginTop: 24,
  },
  gateTitle: {
    fontFamily: typography.displayBold,
    fontSize: 24,
    color: colors.ink,
    textAlign: 'center',
  },
  cardPad: { padding: spacing.md },
  cardHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 10,
  },
  stepBadge: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: colors.jadeSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepNum: {
    fontFamily: typography.bodyBold,
    fontSize: 11,
    color: colors.jade,
  },
  cardTitle: { fontFamily: typography.bodySemi, color: colors.ink, fontSize: 16 },
  addrCard: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'flex-start',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    padding: 12,
    backgroundColor: colors.canvas,
    marginBottom: 8,
  },
  addrCardOn: {
    borderColor: colors.jade,
    backgroundColor: colors.jadeSoft,
  },
  radio: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: colors.borderStrong,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  radioOn: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  addrTop: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 4 },
  addrLabel: { fontFamily: typography.bodySemi, fontSize: 14, color: colors.ink, flexShrink: 1 },
  defaultPill: {
    backgroundColor: colors.brassSoft,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: radii.pill,
  },
  defaultText: {
    fontFamily: typography.bodySemi,
    fontSize: 10,
    color: colors.brassDeep,
  },
  addrBody: {
    fontFamily: typography.body,
    fontSize: 12,
    lineHeight: 17,
    color: colors.inkMuted,
  },
  addAddr: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginTop: 4,
    paddingVertical: 12,
    borderRadius: radii.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.jade,
    backgroundColor: colors.jadeSoft,
  },
  addAddrText: {
    fontFamily: typography.bodySemi,
    fontSize: 13,
    color: colors.jade,
  },
  manageLink: {
    textAlign: 'center',
    fontFamily: typography.bodyMedium,
    fontSize: 12,
    color: colors.inkMuted,
    textDecorationLine: 'underline',
  },
  payRow: { gap: 8 },
  payOpt: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    padding: 12,
    backgroundColor: colors.canvas,
  },
  payOptOn: {
    borderColor: colors.jade,
    backgroundColor: colors.jadeSoft,
  },
  payIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  payIconOn: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  payLabel: { fontFamily: typography.bodySemi, fontSize: 14, color: colors.ink },
  payHint: { fontFamily: typography.body, fontSize: 11, color: colors.inkMuted, marginTop: 2 },
  input: {
    marginTop: 8,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    paddingHorizontal: 12,
    paddingVertical: 12,
    fontFamily: typography.bodyMedium,
    color: colors.ink,
    backgroundColor: colors.canvas,
  },
  couponRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  applyBtn: {
    backgroundColor: colors.jade,
    borderRadius: radii.pill,
    paddingHorizontal: 16,
    paddingVertical: 14,
    minWidth: 78,
    alignItems: 'center',
    ...elevation.soft,
  },
  applyText: { color: colors.paper, fontFamily: typography.bodySemi, fontSize: 13 },
  couponOk: {
    color: colors.jade,
    marginTop: 8,
    fontFamily: typography.bodySemi,
    fontSize: 13,
  },
  line: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 8 },
  divider: {
    height: StyleSheet.hairlineWidth,
    backgroundColor: colors.border,
    marginTop: 10,
    marginBottom: 2,
  },
  price: { fontFamily: typography.bodyBold, color: colors.ink, flexShrink: 0 },
  total: { fontFamily: typography.displayBold, fontSize: 20, color: colors.ink },
  deliverHint: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 12,
    backgroundColor: colors.jadeSoft,
    borderRadius: radii.sm,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  deliverHintText: {
    flex: 1,
    fontFamily: typography.body,
    fontSize: 12,
    color: colors.jade,
  },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.md,
    paddingTop: 12,
    backgroundColor: colors.paper,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.borderStrong,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    ...elevation.soft,
  },
  footerLeft: { minWidth: 88 },
  footerTotal: {
    fontFamily: typography.displayBold,
    fontSize: 20,
    color: colors.ink,
    marginTop: 2,
  },
});

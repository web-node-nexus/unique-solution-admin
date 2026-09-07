import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { MapPin, Pencil, Star, Trash2 } from 'lucide-react-native';
import { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { accountApi, type Address } from '@/api/account';
import { ScreenHeader } from '@/components/layout/ScreenHeader';
import { ScreenShell, themeCard } from '@/components/layout/ScreenShell';
import { AppRefreshControl } from '@/components/ui/AppRefreshControl';
import { AppButton, AppText, PressableScale } from '@/components/ui/primitives';
import { useAuthStore } from '@/store/auth';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

const emptyForm = {
  label: 'Home',
  address: '',
  city: '',
  state: '',
  pincode: '',
  is_default: false,
};

export default function AddressesScreen() {
  const user = useAuthStore((s) => s.user);
  const qc = useQueryClient();
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await accountApi.addresses()).data,
    enabled: !!user,
  });

  const save = useMutation({
    mutationFn: async () => {
      const payload = {
        label: form.label,
        address: form.address,
        city: form.city,
        state: form.state.trim() || null,
        pincode: form.pincode,
        is_default: form.is_default,
      };
      if (editingId) return accountApi.updateAddress(editingId, payload);
      return accountApi.createAddress({ ...payload, address: form.address });
    },
    onSuccess: () => {
      setForm(emptyForm);
      setEditingId(null);
      qc.invalidateQueries({ queryKey: ['addresses'] });
    },
    onError: (e: Error) => Alert.alert('Could not save', e.message),
  });

  const remove = useMutation({
    mutationFn: (id: number) => accountApi.deleteAddress(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['addresses'] }),
  });

  const startEdit = (item: Address) => {
    setEditingId(item.id);
    setForm({
      label: item.label || 'Home',
      address: item.address,
      city: item.city || '',
      state: item.state || '',
      pincode: item.pincode || '',
      is_default: item.is_default,
    });
  };

  if (!user) {
    return (
      <ScreenShell>
        <ScreenHeader showBack eyebrow="Delivery" title="Addresses" />
        <View style={styles.empty}>
          <View style={themeCard.emptyIcon}>
            <MapPin size={28} color={colors.inkSoft} strokeWidth={1.6} />
          </View>
          <AppText variant="caption" style={{ textAlign: 'center' }}>
            Sign in to manage delivery addresses.
          </AppText>
          <AppButton label="Sign in" onPress={() => router.push('/auth/login')} />
        </View>
      </ScreenShell>
    );
  }

  return (
    <ScreenShell>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={8}
      >
        <ScreenHeader showBack eyebrow="Delivery" title="Addresses" />

        <FlatList
          data={data ?? []}
          keyExtractor={(item) => String(item.id)}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="interactive"
          refreshControl={
            <AppRefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />
          }
          ListHeaderComponent={
            <View style={[themeCard.panel, styles.form]}>
              <AppText style={styles.formTitle}>
                {editingId
                  ? 'Edit address'
                  : (data?.length ?? 0) > 0
                    ? 'Add another address'
                    : 'Add new address'}
              </AppText>
              <AppText variant="caption" style={{ marginBottom: 2 }}>
                Save Home, Office, or any label — you can pick one at checkout.
              </AppText>
              <TextInput
                style={themeCard.input}
                value={form.label}
                onChangeText={(label) => setForm((f) => ({ ...f, label }))}
                placeholder="Label (Home / Office)"
                placeholderTextColor={colors.inkSoft}
              />
              <TextInput
                style={themeCard.input}
                value={form.address}
                onChangeText={(address) => setForm((f) => ({ ...f, address }))}
                placeholder="Full address"
                placeholderTextColor={colors.inkSoft}
              />
              <TextInput
                style={themeCard.input}
                value={form.state}
                onChangeText={(state) => setForm((f) => ({ ...f, state }))}
                placeholder="State"
                placeholderTextColor={colors.inkSoft}
              />
              <View style={{ flexDirection: 'row', gap: 10 }}>
                <TextInput
                  style={[themeCard.input, { flex: 1, minWidth: 0 }]}
                  value={form.city}
                  onChangeText={(city) => setForm((f) => ({ ...f, city }))}
                  placeholder="City"
                  placeholderTextColor={colors.inkSoft}
                />
                <TextInput
                  style={[themeCard.input, { flex: 1, minWidth: 0 }]}
                  value={form.pincode}
                  onChangeText={(pincode) => setForm((f) => ({ ...f, pincode }))}
                  placeholder="PIN"
                  keyboardType="number-pad"
                  placeholderTextColor={colors.inkSoft}
                />
              </View>
              <PressableScale
                style={styles.defaultRow}
                onPress={() => setForm((f) => ({ ...f, is_default: !f.is_default }))}
              >
                <Star
                  size={16}
                  color={form.is_default ? colors.brass : colors.inkSoft}
                  fill={form.is_default ? colors.brass : 'transparent'}
                />
                <AppText variant="caption">Set as default</AppText>
              </PressableScale>
              {save.isPending ? (
                <ActivityIndicator color={colors.jade} />
              ) : (
                <View style={{ gap: 8 }}>
                  <AppButton
                    label={editingId ? 'Update address' : 'Save address'}
                    onPress={() => {
                      if (!form.address.trim()) {
                        Alert.alert('Address required');
                        return;
                      }
                      save.mutate();
                    }}
                  />
                  {editingId ? (
                    <AppButton
                      label="Cancel edit"
                      variant="ghost"
                      onPress={() => {
                        setEditingId(null);
                        setForm(emptyForm);
                      }}
                    />
                  ) : null}
                </View>
              )}
            </View>
          }
          contentContainerStyle={{ padding: spacing.lg, gap: 10, paddingBottom: 40 }}
          ListEmptyComponent={
            !isLoading ? (
              <View style={styles.emptyInline}>
                <MapPin size={22} color={colors.inkSoft} strokeWidth={1.6} />
                <AppText variant="caption">No saved addresses yet.</AppText>
              </View>
            ) : null
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <AppText style={styles.name} numberOfLines={1}>
                  {item.label || 'Address'}
                  {item.is_default ? ' · Default' : ''}
                </AppText>
                <AppText variant="caption" numberOfLines={4}>
                  {item.address}
                  {item.city ? `, ${item.city}` : ''}
                  {item.state ? `, ${item.state}` : ''}
                  {item.pincode ? ` · ${item.pincode}` : ''}
                </AppText>
              </View>
              <PressableScale onPress={() => startEdit(item)} style={styles.iconBtn}>
                <Pencil size={16} color={colors.ink} strokeWidth={2} />
              </PressableScale>
              <PressableScale onPress={() => remove.mutate(item.id)} style={styles.iconBtn}>
                <Trash2 size={16} color={colors.danger} strokeWidth={2} />
              </PressableScale>
            </View>
          )}
        />
      </KeyboardAvoidingView>
    </ScreenShell>
  );
}

const styles = StyleSheet.create({
  empty: { alignItems: 'center', gap: 12, marginTop: 48, paddingHorizontal: spacing.lg },
  emptyInline: { alignItems: 'center', gap: 8, marginTop: 12 },
  form: {
    padding: spacing.md,
    gap: 10,
    marginBottom: 8,
  },
  formTitle: { fontFamily: typography.bodySemi, color: colors.ink },
  defaultRow: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 4 },
  card: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  name: { fontFamily: typography.bodySemi, color: colors.ink, marginBottom: 4 },
  iconBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.canvas,
  },
});

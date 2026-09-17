import { Check, ChevronDown } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

export type DropdownOption = {
  key: string;
  label: string;
  hex?: string | null;
  disabled?: boolean;
};

export function OptionDropdown({
  label,
  valueLabel,
  valueKey,
  valueHex,
  options,
  onSelect,
  placeholder = 'Select',
}: {
  label: string;
  valueLabel: string | null;
  valueKey?: string | null;
  valueHex?: string | null;
  options: DropdownOption[];
  onSelect: (key: string) => void;
  placeholder?: string;
}) {
  const [open, setOpen] = useState(false);
  const selectedKey = useMemo(() => {
    if (valueKey) return valueKey;
    return options.find((o) => o.label === valueLabel)?.key ?? null;
  }, [options, valueKey, valueLabel]);

  return (
    <View style={styles.wrap}>
      <AppText variant="label">{label}</AppText>
      <PressableScale style={styles.trigger} onPress={() => setOpen(true)}>
        <View style={styles.triggerLeft}>
          {valueHex ? (
            <View
              style={[
                styles.swatch,
                {
                  backgroundColor: String(valueHex).startsWith('#')
                    ? String(valueHex)
                    : `#${valueHex}`,
                },
              ]}
            />
          ) : null}
          <AppText style={styles.triggerText} numberOfLines={1}>
            {valueLabel || placeholder}
          </AppText>
        </View>
        <ChevronDown size={18} color={colors.inkMuted} strokeWidth={2.2} />
      </PressableScale>

      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable style={styles.backdrop} onPress={() => setOpen(false)}>
          <Pressable style={styles.sheet} onPress={(e) => e.stopPropagation()}>
            <View style={styles.sheetHead}>
              <AppText style={styles.sheetTitle}>{label}</AppText>
              <PressableScale onPress={() => setOpen(false)}>
                <AppText style={styles.close}>Close</AppText>
              </PressableScale>
            </View>
            <ScrollView style={{ maxHeight: 360 }} showsVerticalScrollIndicator={false}>
              {options.map((opt) => {
                const active = opt.key === selectedKey || opt.label === valueLabel;
                return (
                  <PressableScale
                    key={opt.key}
                    disabled={opt.disabled}
                    style={[styles.option, active && styles.optionOn, opt.disabled && styles.optionOff]}
                    onPress={() => {
                      if (opt.disabled) return;
                      onSelect(opt.key);
                      setOpen(false);
                    }}
                  >
                    <View style={styles.triggerLeft}>
                      {opt.hex ? (
                        <View
                          style={[
                            styles.swatch,
                            {
                              backgroundColor: String(opt.hex).startsWith('#')
                                ? String(opt.hex)
                                : `#${opt.hex}`,
                            },
                          ]}
                        />
                      ) : null}
                      <AppText
                        style={[styles.optionText, active && styles.optionTextOn]}
                        numberOfLines={2}
                      >
                        {opt.label}
                      </AppText>
                    </View>
                    {active ? <Check size={18} color={colors.jade} strokeWidth={2.4} /> : null}
                  </PressableScale>
                );
              })}
            </ScrollView>
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 8 },
  trigger: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.borderStrong,
    borderRadius: radii.lg,
    paddingHorizontal: 14,
    paddingVertical: 14,
    minHeight: 52,
    ...elevation.soft,
  },
  triggerLeft: { flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1, minWidth: 0 },
  triggerText: {
    fontFamily: typography.bodySemi,
    fontSize: 15,
    color: colors.ink,
    flexShrink: 1,
  },
  swatch: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: colors.borderStrong,
  },
  backdrop: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.paper,
    borderTopLeftRadius: radii.xl,
    borderTopRightRadius: radii.xl,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.xl,
    maxHeight: '70%',
  },
  sheetHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  sheetTitle: {
    fontFamily: typography.bodyBold,
    fontSize: 17,
    color: colors.ink,
  },
  close: {
    fontFamily: typography.bodySemi,
    color: colors.jade,
    fontSize: 14,
  },
  option: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: radii.md,
    marginBottom: 4,
  },
  optionOn: {
    backgroundColor: colors.jadeSoft,
  },
  optionOff: {
    opacity: 0.45,
  },
  optionText: {
    fontFamily: typography.bodyMedium,
    fontSize: 15,
    color: colors.ink,
    flexShrink: 1,
  },
  optionTextOn: {
    fontFamily: typography.bodySemi,
    color: colors.jadeDeep,
  },
});

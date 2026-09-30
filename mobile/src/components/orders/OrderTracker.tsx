import { StyleSheet, View } from 'react-native';
import { AppText } from '@/components/ui/primitives';
import { colors, typography } from '@/theme/tokens';

const FLOW = [
  { key: 'pending', label: 'Order placed' },
  { key: 'confirmed', label: 'Confirmed' },
  { key: 'shipped', label: 'Shipped' },
  { key: 'delivered', label: 'Delivered' },
] as const;

const GRAY = '#D0D5DD';
const GREEN = colors.success;

type TimelineEvent = { status: string; remarks?: string | null; at?: string | null };

function reachedIndex(status: string, timeline: TimelineEvent[]): number {
  if (status === 'cancelled') {
    const ranks = timeline
      .map((event) => FLOW.findIndex((step) => step.key === event.status))
      .filter((index) => index >= 0);
    return ranks.length ? Math.max(...ranks) : 0;
  }
  if (status === 'returned' || status === 'delivered') return FLOW.length - 1;
  if (status === 'processing') return 1;
  const index = FLOW.findIndex((step) => step.key === status);
  return index >= 0 ? index : 0;
}

function formatWhen(iso?: string | null): string | null {
  if (!iso) return null;
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return null;
  return date.toLocaleString('en-IN', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function OrderTracker({
  status,
  timeline,
}: {
  status: string;
  timeline?: TimelineEvent[];
}) {
  const events = timeline ?? [];
  const active = reachedIndex(status, events);
  const cancel = events.find((event) => event.status === 'cancelled');

  return (
    <View>
      {FLOW.map((step, index) => {
        const filled = index <= active;
        const lineOn = index < active;
        const when = filled ? formatWhen(events.find((event) => event.status === step.key)?.at) : null;
        const connect = index < FLOW.length - 1 || status === 'cancelled';

        return (
          <View key={step.key} style={styles.row}>
            <View style={styles.rail}>
              <View style={[styles.dot, filled ? styles.dotOn : styles.dotOff]} />
              {connect ? <View style={[styles.line, lineOn ? styles.lineOn : styles.lineOff]} /> : null}
            </View>
            <View style={styles.copy}>
              <AppText style={[styles.label, filled ? styles.labelOn : styles.labelOff]}>{step.label}</AppText>
              {when ? <AppText variant="caption">{when}</AppText> : null}
            </View>
          </View>
        );
      })}

      {status === 'cancelled' ? (
        <View style={styles.row}>
          <View style={styles.rail}>
            <View style={[styles.dot, styles.dotStop]} />
          </View>
          <View style={styles.copy}>
            <AppText style={[styles.label, styles.labelStop]}>Cancelled</AppText>
            {cancel?.remarks ? <AppText variant="caption">{cancel.remarks}</AppText> : null}
          </View>
        </View>
      ) : null}

      {status === 'returned' ? (
        <View style={styles.row}>
          <View style={styles.rail}>
            <View style={[styles.dot, styles.dotOn]} />
          </View>
          <View style={styles.copy}>
            <AppText style={[styles.label, styles.labelOn]}>Returned</AppText>
          </View>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'stretch' },
  rail: { width: 28, alignItems: 'center' },
  dot: {
    width: 14,
    height: 14,
    borderRadius: 7,
    marginTop: 2,
    borderWidth: 2,
  },
  dotOn: { backgroundColor: GREEN, borderColor: GREEN },
  dotOff: { backgroundColor: colors.paper, borderColor: GRAY },
  dotStop: { backgroundColor: colors.danger, borderColor: colors.danger },
  line: { width: 3, flex: 1, minHeight: 28, marginVertical: 4, borderRadius: 2 },
  lineOn: { backgroundColor: GREEN },
  lineOff: { backgroundColor: GRAY },
  copy: { flex: 1, paddingBottom: 18, paddingLeft: 8 },
  label: { fontFamily: typography.bodySemi, fontSize: 15 },
  labelOn: { color: colors.ink },
  labelOff: { color: colors.inkSoft, fontFamily: typography.body },
  labelStop: { color: colors.danger },
});

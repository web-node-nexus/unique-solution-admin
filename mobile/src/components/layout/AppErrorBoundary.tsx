import React, { Component, type ErrorInfo, type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import { AppButton, AppText } from '@/components/ui/primitives';
import { colors, spacing, typography } from '@/theme/tokens';
import { reportCrash } from '@/utils/crash';

type Props = { children: ReactNode };
type State = { hasError: boolean; message: string };

export class AppErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, message: '' };

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, message: error.message || 'Something went wrong' };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    void reportCrash({
      message: error.message || 'React render error',
      stack: `${error.stack || ''}\n${info.componentStack || ''}`,
      is_fatal: false,
      context: { source: 'error_boundary' },
    });
  }

  render() {
    if (!this.state.hasError) return this.props.children;

    return (
      <View style={styles.wrap}>
        <AppText variant="display" style={styles.title}>
          Something broke
        </AppText>
        <AppText variant="caption" style={styles.copy}>
          The error was reported. You can try continuing.
        </AppText>
        <AppText variant="caption" numberOfLines={4} style={styles.msg}>
          {this.state.message}
        </AppText>
        <AppButton
          label="Try again"
          onPress={() => this.setState({ hasError: false, message: '' })}
        />
      </View>
    );
  }
}

const styles = StyleSheet.create({
  wrap: {
    flex: 1,
    backgroundColor: colors.stone,
    justifyContent: 'center',
    padding: spacing.xl,
    gap: 12,
  },
  title: { fontSize: 28 },
  copy: { marginBottom: 4 },
  msg: { color: colors.inkSoft, fontFamily: typography.body, marginBottom: 8 },
});

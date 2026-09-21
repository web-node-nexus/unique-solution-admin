import { LinearGradient } from 'expo-linear-gradient';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  InteractionManager,
  Platform,
  StyleSheet,
  UIManager,
  View,
} from 'react-native';
import Animated, {
  Easing,
  runOnJS,
  useAnimatedStyle,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import { WebView } from 'react-native-webview';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, typography } from '@/theme/tokens';

if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
  try {
    UIManager.setLayoutAnimationEnabledExperimental(true);
  } catch {
    // New Architecture no-op
  }
}

const PREVIEW_CSS = `
  html, body { margin: 0; padding: 0; background: transparent; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 15px;
    line-height: 1.65;
    color: #0F172A;
    padding: 2px 0 8px;
    word-wrap: break-word;
    overflow-wrap: anywhere;
  }
  img, video, iframe { max-width: 100%; height: auto; }
  table { width: 100%; border-collapse: collapse; margin: 0.7rem 0; }
  th, td { border: 1px solid #E2E8F0; padding: 8px 10px; text-align: left; vertical-align: top; }
  th { background: #F1F5F9; font-weight: 600; }
  h1, h2, h3, h4 { color: #0F172A; margin: 0.9em 0 0.35em; line-height: 1.3; }
  h1 { font-size: 1.3rem; }
  h2 { font-size: 1.12rem; }
  p { margin: 0.5em 0; }
  ul, ol { padding-left: 1.2rem; margin: 0.5em 0; }
  a { color: #2563EB; }
`;

const HEIGHT_SCRIPT = `
  (function () {
    function send() {
      var h = Math.max(
        document.body ? document.body.scrollHeight : 0,
        document.documentElement ? document.documentElement.scrollHeight : 0
      );
      window.ReactNativeWebView.postMessage(String(h || 80));
    }
    send();
    setTimeout(send, 120);
    setTimeout(send, 400);
    setTimeout(send, 900);
    if (window.ResizeObserver && document.body) {
      new ResizeObserver(send).observe(document.body);
    }
  })();
  true;
`;

/** ~3 physical inches in dp (160dp ≈ 1"). */
const DEFAULT_COLLAPSED_INCHES = 3;

function stripScripts(html: string): string {
  return html
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
    .replace(/\son\w+="[^"]*"/gi, '')
    .replace(/\son\w+='[^']*'/gi, '');
}

function wrapHtml(raw: string): string {
  try {
    const html = stripScripts(String(raw ?? ''));
    const trimmed = html.trim();
    if (!trimmed) {
      return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${PREVIEW_CSS}</style></head><body></body></html>`;
    }

    let styles = '';
    const styleMatches = trimmed.match(/<style\b[^>]*>[\s\S]*?<\/style>/gi);
    if (styleMatches?.length) {
      styles = styleMatches.join('\n');
    }

    let body = trimmed;
    const bodyMatch = trimmed.match(/<body\b[^>]*>([\s\S]*?)<\/body>/i);
    if (bodyMatch) {
      body = bodyMatch[1];
    } else if (/<html[\s>]/i.test(trimmed)) {
      body = trimmed
        .replace(/<!DOCTYPE[\s\S]*?>/i, '')
        .replace(/<\/?(html|head|body)\b[^>]*>/gi, '')
        .replace(/<meta\b[^>]*>/gi, '')
        .replace(/<title\b[^>]*>[\s\S]*?<\/title>/gi, '')
        .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
    }

    return `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"><style>${PREVIEW_CSS}</style>${styles}</head><body>${body}</body></html>`;
  } catch {
    return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>${PREVIEW_CSS}</style></head><body><p>Could not render description.</p></body></html>`;
  }
}

function toPlainText(html: string): string {
  return String(html ?? '')
    .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

export function HtmlContent({
  html,
  framed = true,
  collapsible = false,
  collapsedInches = DEFAULT_COLLAPSED_INCHES,
  onCollapse,
}: {
  html: string;
  framed?: boolean;
  /** When true, content taller than ~collapsedInches shows Show more. */
  collapsible?: boolean;
  /** Visible height before Show more (About = 3 inches ≈ 480dp). */
  collapsedInches?: number;
  /** Called after Show less finishes animating closed. */
  onCollapse?: () => void;
}) {
  const collapsedCap = useMemo(
    () => Math.round(collapsedInches * 160),
    [collapsedInches]
  );
  const [fullHeight, setFullHeight] = useState(0);
  const [measured, setMeasured] = useState(false);
  const [expanded, setExpanded] = useState(false);
  const [ready, setReady] = useState(false);
  const [failed, setFailed] = useState(false);
  const source = useMemo(() => ({ html: wrapHtml(html) }), [html]);
  const plainText = useMemo(() => toPlainText(html), [html]);
  const onCollapseRef = useRef(onCollapse);
  onCollapseRef.current = onCollapse;

  const needsToggle = collapsible && measured && fullHeight > collapsedCap + 36;
  const animH = useSharedValue(collapsedCap);
  const animStyle = useAnimatedStyle(() => ({
    height: animH.value,
    overflow: 'hidden' as const,
  }));

  const notifyCollapsed = () => {
    onCollapseRef.current?.();
  };

  useEffect(() => {
    setExpanded(false);
    setFullHeight(0);
    setMeasured(false);
    setFailed(false);
    setReady(false);
    animH.value = collapsedCap;
    const task = InteractionManager.runAfterInteractions(() => {
      setReady(true);
    });
    return () => task.cancel();
    // eslint-disable-next-line react-hooks/exhaustive-deps -- reset only when html/cap changes
  }, [html, collapsedCap]);

  useEffect(() => {
    if (!measured) return;

    const target =
      !collapsible || !needsToggle || expanded
        ? Math.max(fullHeight, 48)
        : collapsedCap;

    const collapsing = !expanded && needsToggle;
    animH.value = withTiming(
      target,
      {
        duration: collapsing ? 520 : 360,
        easing: collapsing
          ? Easing.bezier(0.22, 1, 0.36, 1)
          : Easing.out(Easing.cubic),
      },
      (finished) => {
        if (finished && collapsing) {
          runOnJS(notifyCollapsed)();
        }
      }
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [expanded, measured, fullHeight, needsToggle, collapsible, collapsedCap]);

  if (!html?.trim()) {
    return null;
  }

  if (failed) {
    return (
      <View style={framed ? styles.wrap : styles.bare}>
        <AppText style={styles.fallbackText}>{plainText || 'Description unavailable.'}</AppText>
      </View>
    );
  }

  const webHeight = measured ? Math.max(fullHeight, 48) : Math.max(collapsedCap, 160);

  return (
    <View style={framed ? styles.wrap : styles.bare} collapsable={false}>
      <Animated.View style={animStyle}>
        {ready ? (
          <WebView
            originWhitelist={['*']}
            source={source}
            scrollEnabled={false}
            showsVerticalScrollIndicator={false}
            showsHorizontalScrollIndicator={false}
            javaScriptEnabled
            mixedContentMode="always"
            automaticallyAdjustContentInsets={false}
            injectedJavaScript={HEIGHT_SCRIPT}
            onMessage={(e) => {
              const next = Number(e.nativeEvent.data);
              if (Number.isFinite(next) && next > 0) {
                setFullHeight(Math.min(Math.max(next, 48), 6000));
                setMeasured(true);
              }
            }}
            onError={() => setFailed(true)}
            onHttpError={() => setFailed(true)}
            renderError={() => (
              <AppText style={styles.fallbackText}>{plainText || 'Description unavailable.'}</AppText>
            )}
            style={[styles.web, { height: webHeight }]}
          />
        ) : (
          <View style={{ height: collapsedCap }} />
        )}
        {needsToggle && !expanded ? (
          <LinearGradient
            colors={['rgba(255,255,255,0)', colors.paper]}
            style={styles.fade}
            pointerEvents="none"
          />
        ) : null}
      </Animated.View>

      {needsToggle ? (
        <PressableScale onPress={() => setExpanded((v) => !v)} style={styles.toggle}>
          <AppText style={styles.toggleText}>{expanded ? 'Show less' : 'Show more'}</AppText>
        </PressableScale>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginTop: 8,
    backgroundColor: colors.paper,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
    paddingHorizontal: 12,
    paddingVertical: 8,
    ...elevation.soft,
  },
  bare: {
    marginTop: 6,
    overflow: 'hidden',
    backgroundColor: 'transparent',
  },
  web: {
    width: '100%',
    backgroundColor: 'transparent',
  },
  fade: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 56,
  },
  toggle: {
    alignSelf: 'flex-end',
    paddingVertical: 10,
    paddingHorizontal: 4,
  },
  toggleText: {
    fontFamily: typography.bodySemi,
    fontSize: 14,
    color: '#2563EB',
    textDecorationLine: 'underline',
  },
  fallbackText: {
    fontFamily: typography.body,
    fontSize: 14,
    lineHeight: 22,
    color: colors.inkMuted,
    paddingVertical: 4,
  },
});

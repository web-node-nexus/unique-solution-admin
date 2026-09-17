import { LinearGradient } from 'expo-linear-gradient';
import { useEffect, useMemo, useState } from 'react';
import {
  LayoutAnimation,
  Platform,
  StyleSheet,
  UIManager,
  View,
} from 'react-native';
import { WebView } from 'react-native-webview';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, typography } from '@/theme/tokens';

if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
  UIManager.setLayoutAnimationEnabledExperimental(true);
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

/** Default: keep ~1000 words visible before See more. */
const DEFAULT_MAX_WORDS = 1000;

/**
 * Approximate on-screen height for N words (15px / 1.65 leading, ~9 words/line on phones).
 * Kept generous so rich HTML cards still show plenty before collapse.
 */
function heightForWordBudget(words: number): number {
  const lines = Math.ceil(words / 9);
  return Math.max(720, Math.round(lines * 28));
}

function stripScripts(html: string): string {
  return html
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
    .replace(/\son\w+="[^"]*"/gi, '')
    .replace(/\son\w+='[^']*'/gi, '');
}

function wrapHtml(raw: string): string {
  const html = stripScripts(raw);
  const trimmed = html.trim();
  if (/<html[\s>]/i.test(trimmed)) {
    if (/<head[\s>]/i.test(trimmed)) {
      return trimmed.replace(
        /<head([^>]*)>/i,
        `<head$1><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"><style>${PREVIEW_CSS}</style>`
      );
    }
    return trimmed.replace(
      /<html([^>]*)>/i,
      `<html$1><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"><style>${PREVIEW_CSS}</style></head>`
    );
  }
  return `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"><style>${PREVIEW_CSS}</style></head><body>${html}</body></html>`;
}

function countWords(html: string): number {
  const text = html
    .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/\s+/g, ' ')
    .trim();
  if (!text) return 0;
  return text.split(/\s+/).filter(Boolean).length;
}

export function HtmlContent({
  html,
  framed = true,
  collapsible = false,
  maxWords = DEFAULT_MAX_WORDS,
  onCollapse,
}: {
  html: string;
  framed?: boolean;
  /** When true, long content shows See more after ~maxWords. */
  collapsible?: boolean;
  maxWords?: number;
  /** Called after collapsing (See less) so the parent can scroll the section into view. */
  onCollapse?: () => void;
}) {
  const [height, setHeight] = useState(0);
  const [measured, setMeasured] = useState(false);
  const [expanded, setExpanded] = useState(false);
  const source = useMemo(() => ({ html: wrapHtml(html) }), [html]);
  const words = useMemo(() => countWords(html), [html]);
  const collapsedCap = useMemo(() => heightForWordBudget(maxWords), [maxWords]);

  useEffect(() => {
    setExpanded(false);
    setHeight(0);
    setMeasured(false);
  }, [html]);

  if (!html?.trim()) {
    return null;
  }

  const contentTallerThanPreview = measured && height > collapsedCap + 48;
  const needsToggle = collapsible && words > maxWords && contentTallerThanPreview;
  const showCollapsed = needsToggle && !expanded;
  // Never clip to a tiny box before WebView reports real height (that caused empty + only "See more")
  const webHeight = !measured
    ? collapsible && words > maxWords
      ? collapsedCap
      : 180
    : showCollapsed
      ? Math.min(height, collapsedCap)
      : Math.max(height, 48);

  const handleToggle = () => {
    LayoutAnimation.configureNext({
      duration: 280,
      update: { type: LayoutAnimation.Types.easeInEaseOut },
      create: {
        type: LayoutAnimation.Types.easeInEaseOut,
        property: LayoutAnimation.Properties.opacity,
      },
      delete: {
        type: LayoutAnimation.Types.easeInEaseOut,
        property: LayoutAnimation.Properties.opacity,
      },
    });

    if (expanded) {
      setExpanded(false);
      requestAnimationFrame(() => {
        setTimeout(() => onCollapse?.(), 40);
      });
    } else {
      setExpanded(true);
    }
  };

  return (
    <View style={framed ? styles.wrap : styles.bare} collapsable={false}>
      <View style={showCollapsed ? [styles.collapsedClip, { maxHeight: collapsedCap }] : undefined}>
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
              setHeight(Math.min(Math.max(next, 48), 12000));
              setMeasured(true);
            }
          }}
          style={[styles.web, { height: webHeight }]}
        />
        {showCollapsed ? (
          <LinearGradient
            colors={['rgba(255,255,255,0)', colors.paper]}
            style={styles.fade}
            pointerEvents="none"
          />
        ) : null}
      </View>

      {needsToggle ? (
        <PressableScale onPress={handleToggle} style={styles.toggle}>
          <AppText style={styles.toggleText}>{expanded ? 'See less' : 'See more'}</AppText>
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
  collapsedClip: {
    overflow: 'hidden',
  },
  fade: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 72,
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
});

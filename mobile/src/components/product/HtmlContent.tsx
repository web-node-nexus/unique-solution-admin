import { useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { WebView } from 'react-native-webview';
import { colors, elevation, radii } from '@/theme/tokens';

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
  a { color: #0F8F8A; }
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
    if (window.ResizeObserver && document.body) {
      new ResizeObserver(send).observe(document.body);
    }
  })();
  true;
`;

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

export function HtmlContent({ html, framed = true }: { html: string; framed?: boolean }) {
  const [height, setHeight] = useState(80);
  const source = useMemo(() => ({ html: wrapHtml(html) }), [html]);

  if (!html?.trim()) {
    return null;
  }

  return (
    <View style={framed ? styles.wrap : styles.bare}>
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
            setHeight(Math.min(Math.max(next, 48), 8000));
          }
        }}
        style={[styles.web, { height }]}
      />
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
});

import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useRef, useState } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import { API_URL } from '@/api/client';

const bundled = require('../../../assets/default-splash.jpg');
const HOLD_MS = 2000;

type SplashJson = {
  data?: { image_url?: string };
};

function isHttpUrl(value: unknown): value is string {
  return typeof value === 'string' && /^https?:\/\//i.test(value);
}

export function RemoteSplash({ onDone }: { onDone: () => void }) {
  const [remote, setRemote] = useState<string | null>(null);
  const onDoneRef = useRef(onDone);
  onDoneRef.current = onDone;

  useEffect(() => {
    SplashScreen.hideAsync().catch(() => undefined);
    const timer = setTimeout(() => onDoneRef.current(), HOLD_MS);
    let cancelled = false;

    const run = async () => {
      try {
        const res = await fetch(`${API_URL}/splash`);
        if (!res.ok) return;
        const json = (await res.json()) as SplashJson;
        const url = json?.data?.image_url;
        if (!cancelled && isHttpUrl(url)) setRemote(url);
      } catch {
        // Keep the poster shipped with the app.
      }
    };

    void run();
    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, []);

  return (
    <View style={styles.cover}>
      <Image source={bundled} style={StyleSheet.absoluteFill} resizeMode="cover" fadeDuration={0} />
      {remote ? (
        <Image source={{ uri: remote }} style={StyleSheet.absoluteFill} resizeMode="cover" fadeDuration={0} />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  cover: {
    ...StyleSheet.absoluteFillObject,
    zIndex: 20000,
    backgroundColor: '#012CB9',
  },
});

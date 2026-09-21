import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { X } from 'lucide-react-native';
import { useCallback, useRef, useState } from 'react';
import {
  Modal,
  NativeScrollEvent,
  NativeSyntheticEvent,
  Pressable,
  ScrollView,
  StyleSheet,
  useWindowDimensions,
  View,
} from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import Animated, {
  FadeIn,
  useAnimatedStyle,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, IconButton, PressableScale } from '@/components/ui/primitives';
import { colors, typography } from '@/theme/tokens';

const ACCENT = '#2C64E3';

type GalleryImage = { id: number | string; url: string };

function ZoomableImage({ uri, width, height }: { uri: string; width: number; height: number }) {
  const scale = useSharedValue(1);
  const savedScale = useSharedValue(1);
  const translateX = useSharedValue(0);
  const translateY = useSharedValue(0);
  const savedX = useSharedValue(0);
  const savedY = useSharedValue(0);

  const pinch = Gesture.Pinch()
    .onUpdate((e) => {
      scale.value = Math.min(4, Math.max(1, savedScale.value * e.scale));
    })
    .onEnd(() => {
      savedScale.value = scale.value;
      if (scale.value < 1.05) {
        scale.value = withTiming(1);
        translateX.value = withTiming(0);
        translateY.value = withTiming(0);
        savedScale.value = 1;
        savedX.value = 0;
        savedY.value = 0;
      }
    });

  const pan = Gesture.Pan()
    .onUpdate((e) => {
      if (scale.value <= 1) return;
      translateX.value = savedX.value + e.translationX;
      translateY.value = savedY.value + e.translationY;
    })
    .onEnd(() => {
      savedX.value = translateX.value;
      savedY.value = translateY.value;
    });

  const doubleTap = Gesture.Tap()
    .numberOfTaps(2)
    .onEnd(() => {
      if (scale.value > 1.2) {
        scale.value = withTiming(1);
        translateX.value = withTiming(0);
        translateY.value = withTiming(0);
        savedScale.value = 1;
        savedX.value = 0;
        savedY.value = 0;
      } else {
        scale.value = withTiming(2.4);
        savedScale.value = 2.4;
      }
    });

  const composed = Gesture.Simultaneous(pinch, pan, doubleTap);
  const animatedStyle = useAnimatedStyle(() => ({
    transform: [
      { translateX: translateX.value },
      { translateY: translateY.value },
      { scale: scale.value },
    ],
  }));

  return (
    <GestureDetector gesture={composed}>
      <Animated.View style={[{ width, height, justifyContent: 'center' }, animatedStyle]}>
        <Image source={{ uri }} style={{ width, height }} contentFit="contain" />
      </Animated.View>
    </GestureDetector>
  );
}

export function ProductImageGallery({
  images,
  width,
  height,
  fallback,
}: {
  images: GalleryImage[];
  width: number;
  height: number;
  fallback?: React.ReactNode;
}) {
  const insets = useSafeAreaInsets();
  const { width: winW, height: winH } = useWindowDimensions();
  const pagerRef = useRef<ScrollView>(null);
  const [open, setOpen] = useState(false);
  const [index, setIndex] = useState(0);
  const usable = images.filter((i) => !!i.url);

  const openAt = useCallback((i: number) => {
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    setIndex(i);
    setOpen(true);
  }, []);

  const goTo = (i: number) => {
    void Haptics.selectionAsync();
    setIndex(i);
    pagerRef.current?.scrollTo({ x: i * width, animated: true });
  };

  const onScroll = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const next = Math.round(e.nativeEvent.contentOffset.x / Math.max(width, 1));
    if (next !== index) {
      setIndex(next);
      void Haptics.selectionAsync();
    }
  };

  if (!usable.length) {
    return <View style={{ width, height, backgroundColor: '#F8FAFC' }}>{fallback}</View>;
  }

  return (
    <>
      <View style={{ width, height, backgroundColor: '#FFFFFF' }}>
        <ScrollView
          ref={pagerRef}
          horizontal
          pagingEnabled
          showsHorizontalScrollIndicator={false}
          onMomentumScrollEnd={onScroll}
          decelerationRate="fast"
        >
          {usable.map((img, i) => (
            <Pressable key={img.id} onPress={() => openAt(i)}>
              <Animated.View
                entering={FadeIn.duration(280)}
                style={{
                  width,
                  height,
                  paddingHorizontal: 20,
                  paddingVertical: 12,
                  alignItems: 'center',
                  justifyContent: 'center',
                  backgroundColor: '#FFFFFF',
                }}
              >
                <Image
                  source={{ uri: img.url }}
                  style={{ width: width - 40, height: height - 24 }}
                  contentFit="contain"
                  transition={350}
                />
              </Animated.View>
            </Pressable>
          ))}
        </ScrollView>

        <View style={styles.countPill} pointerEvents="none">
          <AppText style={styles.countText}>
            {index + 1} / {usable.length}
          </AppText>
        </View>
      </View>

      {usable.length > 1 ? (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.thumbs}
        >
          {usable.map((img, i) => (
            <PressableScale key={img.id} onPress={() => goTo(i)} style={styles.thumbWrap}>
              <Image
                source={{ uri: img.url }}
                style={[styles.thumb, i === index && styles.thumbOn]}
                contentFit="contain"
              />
            </PressableScale>
          ))}
        </ScrollView>
      ) : null}

      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <View style={[styles.modal, { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 8 }]}>
          <View style={styles.modalTop}>
            <AppText style={styles.counter}>
              {index + 1} / {usable.length}
            </AppText>
            <IconButton size={40} onPress={() => setOpen(false)} style={styles.close}>
              <X size={20} color={colors.ink} strokeWidth={2.2} />
            </IconButton>
          </View>
          <ScrollView
            key={open ? `zoom-${index}` : 'closed'}
            horizontal
            pagingEnabled
            showsHorizontalScrollIndicator={false}
            onMomentumScrollEnd={(e) => {
              const next = Math.round(e.nativeEvent.contentOffset.x / winW);
              setIndex(next);
            }}
            contentOffset={{ x: index * winW, y: 0 }}
          >
            {usable.map((img) => (
              <View key={img.id} style={{ width: winW, height: winH * 0.78, backgroundColor: '#0F172A' }}>
                <ZoomableImage uri={img.url} width={winW} height={winH * 0.78} />
              </View>
            ))}
          </ScrollView>
          <AppText style={styles.help}>Pinch · double-tap · swipe</AppText>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  countPill: {
    position: 'absolute',
    right: 14,
    bottom: 14,
    backgroundColor: 'rgba(15, 23, 42, 0.72)',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
  },
  countText: {
    color: '#FFFFFF',
    fontFamily: typography.bodyMedium,
    fontSize: 12,
  },
  thumbs: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 2,
    gap: 10,
  },
  thumbWrap: {},
  thumb: {
    width: 58,
    height: 58,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: '#E5E7EB',
    backgroundColor: '#F8FAFC',
    padding: 4,
  },
  thumbOn: {
    borderColor: ACCENT,
  },
  modal: { flex: 1, backgroundColor: '#0F172A' },
  modalTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  counter: {
    color: '#E2E8F0',
    fontFamily: typography.bodyMedium,
    fontSize: 14,
  },
  close: {
    backgroundColor: colors.paper,
    borderColor: colors.border,
  },
  help: {
    textAlign: 'center',
    color: '#94A3B8',
    fontFamily: typography.body,
    fontSize: 12,
    marginTop: 8,
  },
});

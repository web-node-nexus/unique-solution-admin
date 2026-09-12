import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { Images, X, ZoomIn } from 'lucide-react-native';
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
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, IconButton, PressableScale } from '@/components/ui/primitives';
import { colors, elevation, radii, typography } from '@/theme/tokens';

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
  const progress = useSharedValue(0);

  const openAt = useCallback((i: number) => {
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    setIndex(i);
    setOpen(true);
  }, []);

  const goTo = (i: number) => {
    void Haptics.selectionAsync();
    setIndex(i);
    pagerRef.current?.scrollTo({ x: i * width, animated: true });
    progress.value = withSpring(i);
  };

  const onScroll = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const next = Math.round(e.nativeEvent.contentOffset.x / Math.max(width, 1));
    if (next !== index) {
      setIndex(next);
      void Haptics.selectionAsync();
    }
  };

  if (!usable.length) {
    return <View style={{ width, height, backgroundColor: colors.canvasDeep }}>{fallback}</View>;
  }

  return (
    <>
      <View style={{ width, height, backgroundColor: colors.canvasDeep }}>
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
                  padding: 22,
                  alignItems: 'center',
                  justifyContent: 'center',
                  backgroundColor: colors.canvasDeep,
                }}
              >
                <Image
                  source={{ uri: img.url }}
                  style={{ width: width - 44, height: height - 44 }}
                  contentFit="contain"
                  transition={350}
                />
              </Animated.View>
            </Pressable>
          ))}
        </ScrollView>

        <View style={styles.topMeta} pointerEvents="none">
          <View style={styles.countPill}>
            <Images size={12} color={colors.ink} strokeWidth={2} />
            <AppText style={styles.countText}>
              {index + 1} / {usable.length}
            </AppText>
          </View>
        </View>

        <View style={styles.zoomHint} pointerEvents="none">
          <ZoomIn size={13} color={colors.inkMuted} strokeWidth={2.2} />
          <AppText style={styles.zoomHintText}>Tap to enlarge</AppText>
        </View>

        {usable.length > 1 ? (
          <View style={styles.dots}>
            {usable.map((img, i) => (
              <View key={img.id} style={[styles.dot, i === index && styles.dotOn]} />
            ))}
          </View>
        ) : null}
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
              <View key={img.id} style={{ width: winW, height: winH * 0.78, backgroundColor: colors.canvasDeep }}>
                <ZoomableImage uri={img.url} width={winW} height={winH * 0.78} />
              </View>
            ))}
          </ScrollView>
          <AppText style={styles.help}>Pinch · double-tap · swipe for next angle</AppText>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  topMeta: {
    position: 'absolute',
    left: 16,
    bottom: 18,
  },
  countPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.paper,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  countText: {
    color: colors.ink,
    fontFamily: typography.bodyMedium,
    fontSize: 12,
  },
  zoomHint: {
    position: 'absolute',
    right: 14,
    bottom: 18,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.paper,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  zoomHintText: {
    color: colors.inkMuted,
    fontFamily: typography.body,
    fontSize: 11,
  },
  dots: {
    position: 'absolute',
    top: 14,
    alignSelf: 'center',
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 5,
  },
  dot: {
    width: 5,
    height: 5,
    borderRadius: 3,
    backgroundColor: colors.borderStrong,
  },
  dotOn: {
    width: 16,
    backgroundColor: colors.jade,
  },
  thumbs: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 4,
    gap: 8,
  },
  thumbWrap: {},
  thumb: {
    width: 56,
    height: 56,
    borderRadius: radii.sm,
    borderWidth: 1.5,
    borderColor: 'transparent',
    backgroundColor: colors.canvasDeep,
    padding: 4,
  },
  thumbOn: {
    borderColor: colors.jade,
  },
  modal: { flex: 1, backgroundColor: colors.canvas },
  modalTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  counter: {
    color: colors.inkMuted,
    fontFamily: typography.bodyMedium,
    fontSize: 14,
  },
  close: {
    backgroundColor: colors.paper,
    borderColor: colors.border,
  },
  help: {
    textAlign: 'center',
    color: colors.inkSoft,
    fontFamily: typography.body,
    fontSize: 12,
    marginTop: 8,
  },
});

import { Image } from 'expo-image';
import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { ChevronRight, MapPin, ShieldCheck, Truck } from 'lucide-react-native';
import React, { useCallback, useRef, useState } from 'react';
import {
  Dimensions,
  FlatList,
  NativeScrollEvent,
  NativeSyntheticEvent,
  Pressable,
  StyleSheet,
  View,
} from 'react-native';
import Animated, {
  Extrapolation,
  FadeIn,
  interpolate,
  SharedValue,
  useAnimatedScrollHandler,
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AppText, PressableScale } from '@/components/ui/primitives';
import { useOnboardingStore } from '@/store/onboarding';
import { colors, elevation, radii, spacing, typography } from '@/theme/tokens';

const { width, height } = Dimensions.get('window');
const AnimatedFlatList = Animated.createAnimatedComponent(FlatList<Slide>);
const IMAGE_H = Math.min(height * 0.42, 360);

type Slide = {
  key: string;
  image: number;
  eyebrow: string;
  title: string;
  body: string;
  accent: string;
  chips: { icon: typeof ShieldCheck; label: string }[];
};

const slides: Slide[] = [
  {
    key: '1',
    image: require('../../assets/onboarding/catalog.png'),
    eyebrow: 'Kurud · Chhattisgarh',
    title: 'Unique\nSolution',
    body: 'Electronics chosen with a retailer\'s eye — phones, appliances, and accessories worth bringing home.',
    accent: colors.jadeMid,
    chips: [
      { icon: ShieldCheck, label: 'Curated brands' },
      { icon: ShieldCheck, label: 'Genuine stock' },
    ],
  },
  {
    key: '2',
    image: require('../../assets/onboarding/filters.png'),
    eyebrow: 'Shop smarter',
    title: 'Find the\nright piece',
    body: 'Filter by brand, colour, storage, and budget. Pinch details that matter — skip the noise.',
    accent: colors.brass,
    chips: [
      { icon: ShieldCheck, label: 'Smart filters' },
      { icon: ShieldCheck, label: 'Clear pricing' },
    ],
  },
  {
    key: '3',
    image: require('../../assets/onboarding/delivery.png'),
    eyebrow: 'Kargil Chowk',
    title: 'Local trust,\ndoorstep care',
    body: 'From our counter to your door — tracking, support, and the same people you already know in Kurud.',
    accent: colors.brass,
    chips: [
      { icon: Truck, label: 'Home delivery' },
      { icon: MapPin, label: 'Visit the shop' },
    ],
  },
];

function ParallaxImage({
  source,
  index,
  scrollX,
}: {
  source: number;
  index: number;
  scrollX: SharedValue<number>;
}) {
  const style = useAnimatedStyle(() => {
    const input = [(index - 1) * width, index * width, (index + 1) * width];
    const scale = interpolate(scrollX.value, input, [1.08, 1, 1.08], Extrapolation.CLAMP);
    const translateX = interpolate(scrollX.value, input, [-width * 0.08, 0, width * 0.08], Extrapolation.CLAMP);
    const opacity = interpolate(scrollX.value, input, [0.7, 1, 0.7], Extrapolation.CLAMP);
    return { transform: [{ scale }, { translateX }], opacity };
  });

  return (
    <Animated.View style={[styles.imageStage, style]}>
      <Image source={source} style={StyleSheet.absoluteFillObject} contentFit="cover" transition={400} />
    </Animated.View>
  );
}

function ProgressDot({
  index,
  scrollX,
  onPress,
}: {
  index: number;
  scrollX: SharedValue<number>;
  onPress: () => void;
}) {
  const style = useAnimatedStyle(() => {
    const input = [(index - 1) * width, index * width, (index + 1) * width];
    const w = interpolate(scrollX.value, input, [8, 28, 8], Extrapolation.CLAMP);
    const opacity = interpolate(scrollX.value, input, [0.35, 1, 0.35], Extrapolation.CLAMP);
    return { width: w, opacity };
  });

  return (
    <Pressable onPress={onPress} hitSlop={10}>
      <Animated.View style={[styles.dot, style]} />
    </Pressable>
  );
}

export default function OnboardingScreen() {
  const insets = useSafeAreaInsets();
  const complete = useOnboardingStore((s) => s.complete);
  const [index, setIndex] = useState(0);
  const [pressedChip, setPressedChip] = useState<string | null>(null);
  const listRef = useRef<FlatList<Slide>>(null);
  const scrollX = useSharedValue(0);
  const ctaScale = useSharedValue(1);

  const onScroll = useAnimatedScrollHandler({
    onScroll: (e) => {
      scrollX.value = e.contentOffset.x;
    },
  });

  const onMomentumEnd = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const i = Math.round(e.nativeEvent.contentOffset.x / width);
    if (i !== index) {
      setIndex(i);
      void Haptics.selectionAsync();
    }
  };

  const finish = useCallback(() => {
    void Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    complete();
    router.replace('/(main)/(tabs)');
  }, [complete]);

  const goTo = (i: number) => {
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    listRef.current?.scrollToIndex({ index: i, animated: true });
    setIndex(i);
  };

  const next = () => {
    ctaScale.value = withSpring(0.96, {}, () => {
      ctaScale.value = withSpring(1);
    });
    if (index >= slides.length - 1) {
      finish();
      return;
    }
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    goTo(index + 1);
  };

  const ctaStyle = useAnimatedStyle(() => ({
    transform: [{ scale: ctaScale.value }],
  }));

  return (
    <View style={styles.root}>
      <AnimatedFlatList
        ref={listRef}
        data={slides}
        keyExtractor={(item) => item.key}
        horizontal
        pagingEnabled
        bounces
        decelerationRate="fast"
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        onMomentumScrollEnd={onMomentumEnd}
        scrollEventThrottle={16}
        renderItem={({ item, index: i }) => (
          <View style={{ width, height }}>
            <View style={[styles.slide, { paddingTop: insets.top + 18, paddingBottom: insets.bottom + 140 }]}>
              <View style={styles.topRow}>
                <Animated.View entering={FadeIn.duration(500)}>
                  <AppText style={styles.eyebrow}>{item.eyebrow}</AppText>
                </Animated.View>
                <PressableScale onPress={finish} style={styles.skipBtn}>
                  <AppText style={styles.skip}>Skip</AppText>
                </PressableScale>
              </View>

              <ParallaxImage source={item.image} index={i} scrollX={scrollX} />

              <View style={styles.bottomCopy}>
                <AppText style={styles.title}>{item.title}</AppText>
                <AppText style={styles.body}>{item.body}</AppText>

                <View style={styles.interactiveChips}>
                  {item.chips.map((chip) => {
                    const Icon = chip.icon;
                    const active = pressedChip === `${item.key}-${chip.label}`;
                    return (
                      <PressableScale
                        key={chip.label}
                        onPress={() => {
                          void Haptics.selectionAsync();
                          setPressedChip(`${item.key}-${chip.label}`);
                          setTimeout(() => setPressedChip(null), 600);
                        }}
                        style={[styles.liveChip, active && styles.liveChipActive]}
                      >
                        <Icon size={15} color={active ? colors.paper : colors.jade} strokeWidth={2.2} />
                        <AppText style={[styles.liveChipText, active && styles.liveChipTextActive]}>
                          {chip.label}
                        </AppText>
                      </PressableScale>
                    );
                  })}
                </View>

                {i === 0 ? (
                  <AppText style={styles.brandHint}>Swipe to explore · tap chips</AppText>
                ) : null}
              </View>
            </View>
          </View>
        )}
      />

      <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        <View style={styles.dots}>
          {slides.map((s, i) => (
            <ProgressDot key={s.key} index={i} scrollX={scrollX} onPress={() => goTo(i)} />
          ))}
        </View>

        <Animated.View style={ctaStyle}>
          <PressableScale onPress={next} style={styles.cta}>
            <AppText style={styles.ctaText}>
              {index === slides.length - 1 ? 'Enter the shop' : 'Continue'}
            </AppText>
            <View style={styles.ctaIcon}>
              <ChevronRight size={18} color={colors.paper} strokeWidth={2.4} />
            </View>
          </PressableScale>
        </Animated.View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.canvas },
  slide: {
    flex: 1,
    paddingHorizontal: spacing.lg,
    gap: 18,
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  eyebrow: {
    fontFamily: typography.bodyMedium,
    color: colors.jade,
    letterSpacing: 0.8,
    textTransform: 'uppercase',
    fontSize: 11,
  },
  skipBtn: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: radii.pill,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  skip: {
    fontFamily: typography.bodyMedium,
    color: colors.inkMuted,
    fontSize: 13,
  },
  imageStage: {
    height: IMAGE_H,
    borderRadius: radii.xl,
    overflow: 'hidden',
    backgroundColor: colors.canvasDeep,
    ...elevation.soft,
  },
  bottomCopy: { gap: 12, maxWidth: 380, flex: 1 },
  title: {
    fontFamily: typography.displayBold,
    fontSize: 36,
    lineHeight: 42,
    color: colors.ink,
    letterSpacing: -0.6,
  },
  body: {
    fontFamily: typography.body,
    fontSize: 15,
    lineHeight: 23,
    color: colors.inkMuted,
    maxWidth: 340,
  },
  interactiveChips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 4,
  },
  liveChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: radii.pill,
    backgroundColor: colors.paper,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.soft,
  },
  liveChipActive: {
    backgroundColor: colors.jade,
    borderColor: colors.jade,
  },
  liveChipText: {
    fontFamily: typography.bodyMedium,
    color: colors.ink,
    fontSize: 13,
  },
  liveChipTextActive: {
    color: colors.paper,
  },
  brandHint: {
    marginTop: 6,
    fontFamily: typography.body,
    fontSize: 12,
    color: colors.inkSoft,
    letterSpacing: 0.2,
  },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: spacing.lg,
    gap: spacing.md,
  },
  dots: { flexDirection: 'row', gap: 8, justifyContent: 'center', alignItems: 'center' },
  dot: {
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.jade,
  },
  cta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.paper,
    borderRadius: radii.pill,
    paddingLeft: 20,
    paddingRight: 8,
    paddingVertical: 8,
    minHeight: 52,
    borderWidth: 1,
    borderColor: colors.border,
    ...elevation.lift,
  },
  ctaText: {
    fontFamily: typography.bodySemi,
    fontSize: 15,
    color: colors.ink,
  },
  ctaIcon: {
    width: 36,
    height: 36,
    borderRadius: radii.pill,
    backgroundColor: colors.jade,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

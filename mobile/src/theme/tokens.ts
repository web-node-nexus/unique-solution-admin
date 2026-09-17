export const colors = {
  // Soft candy retail canvas — light but clearly tinted
  canvas: '#EEF4FF',
  canvasDeep: '#D9E6FF',
  mist: '#FFF8FB',
  stone: '#F5F0FF',
  stoneDeep: '#E9DEFF',
  ink: '#0F172A',
  inkMuted: '#5B6478',
  inkSoft: '#8B95A8',
  paper: '#FFFFFF',
  // Electric teal primary
  jade: '#00C2B2',
  jadeDeep: '#009B8E',
  jadeMid: '#2EE6D6',
  jadeSoft: '#D8FFFB',
  jadeGlow: 'rgba(0, 194, 178, 0.22)',
  // Warm amber / coral pops
  brass: '#FFB020',
  brassDeep: '#F79009',
  brassSoft: '#FFF4D6',
  accent: '#FF4D8D',
  accentSoft: '#FFE0EC',
  // Supporting accents
  sky: '#3B82F6',
  skySoft: '#DCE8FF',
  violet: '#A855F7',
  violetSoft: '#F0E4FF',
  mango: '#FF8A3D',
  mangoSoft: '#FFE8D6',
  danger: '#F04438',
  success: '#12B76A',
  offer: '#FF3B6B',
  border: 'rgba(15, 23, 42, 0.07)',
  borderStrong: 'rgba(15, 23, 42, 0.12)',
  overlay: 'rgba(15, 23, 42, 0.45)',
  shadow: 'rgba(15, 23, 42, 0.14)',
  heroFrom: '#1B3A8C',
  heroVia: '#0E7C9B',
  heroTo: '#00C2B2',
  flashFrom: '#8B1048',
  flashTo: '#FF4D8D',
} as const;

/** Rotating soft tints for category tiles / chips */
export const accentPalette = [
  { bg: colors.jadeSoft, border: 'rgba(0, 194, 178, 0.28)', ink: colors.jadeDeep },
  { bg: colors.accentSoft, border: 'rgba(255, 77, 141, 0.28)', ink: colors.accent },
  { bg: colors.violetSoft, border: 'rgba(168, 85, 247, 0.28)', ink: colors.violet },
  { bg: colors.skySoft, border: 'rgba(59, 130, 246, 0.28)', ink: colors.sky },
  { bg: colors.brassSoft, border: 'rgba(255, 176, 32, 0.35)', ink: colors.brassDeep },
  { bg: colors.mangoSoft, border: 'rgba(255, 138, 61, 0.28)', ink: colors.mango },
] as const;

export const spacing = {
  xxs: 4,
  xs: 8,
  sm: 12,
  md: 16,
  lg: 20,
  xl: 28,
  xxl: 40,
  huge: 56,
} as const;

export const radii = {
  sm: 12,
  md: 16,
  lg: 22,
  xl: 28,
  xxl: 36,
  pill: 999,
} as const;

export const typography = {
  display: 'SourceSerif4_600SemiBold',
  displayBold: 'SourceSerif4_700Bold',
  body: 'SourceSans3_400Regular',
  bodyMedium: 'SourceSans3_500Medium',
  bodySemi: 'SourceSans3_600SemiBold',
  bodyBold: 'SourceSans3_700Bold',
} as const;

export const elevation = {
  soft: {
    shadowColor: colors.shadow,
    shadowOpacity: 1,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 3,
  },
  lift: {
    shadowColor: colors.shadow,
    shadowOpacity: 1,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 10 },
    elevation: 8,
  },
} as const;

export const gradients = {
  hero: [colors.heroFrom, colors.heroVia, colors.heroTo] as const,
  deal: [colors.flashFrom, colors.flashTo] as const,
  brass: [colors.brassDeep, colors.brass] as const,
  mist: ['#F3F7FF', '#FFF0F7', '#E8FFFB'] as const,
  accentWash: ['#E8F1FF', '#FFE8F3', '#E0FFFB', '#FFF4E0'] as const,
  tab: ['#FFFFFF', '#F0FFFC', '#FFF5FA'] as const,
  cardWash: ['#F7FBFF', '#FFF8FC'] as const,
  filter: [colors.jade, colors.jadeMid] as const,
  stripe: [colors.jade, colors.violet, colors.accent] as const,
} as const;

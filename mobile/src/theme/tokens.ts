export const colors = {
  // Soft retail daylight — slightly warmer + fresher
  canvas: '#F3F7FC',
  canvasDeep: '#E4EDF8',
  mist: '#FFFFFF',
  stone: '#F3F7FC',
  stoneDeep: '#DCE7F4',
  ink: '#0B1B2B',
  inkMuted: '#5B6B7C',
  inkSoft: '#8B9AAB',
  paper: '#FFFFFF',
  // Professional teal with more presence
  jade: '#0D9B94',
  jadeDeep: '#0A6F6A',
  jadeMid: '#18B5AD',
  jadeSoft: '#E4F8F6',
  jadeGlow: 'rgba(13, 155, 148, 0.16)',
  // Warm amber / coral highlights
  brass: '#F4A261',
  brassDeep: '#E07A2F',
  brassSoft: '#FFF3E6',
  accent: '#FF6B35',
  accentSoft: '#FFE4D6',
  // Supporting accents
  sky: '#3B82F6',
  skySoft: '#E8F1FF',
  violet: '#7C5CFC',
  violetSoft: '#F0EBFF',
  danger: '#E5484D',
  success: '#0D9B94',
  offer: '#E5484D',
  border: 'rgba(11, 27, 43, 0.08)',
  borderStrong: 'rgba(11, 27, 43, 0.14)',
  overlay: 'rgba(11, 27, 43, 0.45)',
  shadow: 'rgba(11, 27, 43, 0.12)',
  heroFrom: '#0B3D5C',
  heroVia: '#0D6B7A',
  heroTo: '#0D9B94',
  flashFrom: '#4A1020',
  flashTo: '#C43B4E',
} as const;

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
  mist: ['#F5F9FD', '#EEF6FF', '#E8F7F4'] as const,
  accentWash: ['#FFF5EE', '#F3F7FC', '#E8F7F4'] as const,
} as const;

export const colors = {
  // Soft retail daylight
  canvas: '#F4F7FB',
  canvasDeep: '#E8EEF5',
  mist: '#FFFFFF',
  stone: '#F4F7FB',
  stoneDeep: '#E5EBF3',
  ink: '#0F172A',
  inkMuted: '#64748B',
  inkSoft: '#94A3B8',
  paper: '#FFFFFF',
  // Teal accent (light-theme cousin of the mock cyan)
  jade: '#0F8F8A',
  jadeDeep: '#0B6F6B',
  jadeMid: '#14A8A2',
  jadeSoft: '#E7F7F6',
  jadeGlow: 'rgba(15, 143, 138, 0.14)',
  // Warm amber for highlights / FAB
  brass: '#F5A524',
  brassDeep: '#D4890F',
  brassSoft: '#FFF6E5',
  accent: '#FF7A1A',
  accentSoft: '#FFE8D6',
  danger: '#E5484D',
  success: '#0F8F8A',
  offer: '#E5484D',
  border: 'rgba(15, 23, 42, 0.08)',
  borderStrong: 'rgba(15, 23, 42, 0.14)',
  overlay: 'rgba(15, 23, 42, 0.45)',
  shadow: 'rgba(15, 23, 42, 0.1)',
  heroFrom: '#0E3B55',
  heroVia: '#0F5C6E',
  heroTo: '#0F8F8A',
  flashFrom: '#3B0D1A',
  flashTo: '#6B1528',
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
  mist: ['#F4F7FB', '#FFFFFF', '#EAF1F8'] as const,
} as const;

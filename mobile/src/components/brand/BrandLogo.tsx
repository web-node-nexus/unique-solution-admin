import { Image } from 'expo-image';

const source = require('../../../assets/brand-logo.png');

export function BrandLogo({ size = 72 }: { size?: number }) {
  return (
    <Image
      source={source}
      style={{ width: size, height: size, borderRadius: size / 2 }}
      contentFit="cover"
      accessibilityLabel="Unique Solution"
    />
  );
}

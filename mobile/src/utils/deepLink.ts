import { Linking } from 'react-native';
import { router } from 'expo-router';

export type DeepLink = {
  type?: string | null;
  value?: string | null;
  label?: string | null;
} | null | undefined;

/**
 * Navigate from admin-configured deep links (banners, sales, notifications).
 */
export async function openDeepLink(link: DeepLink): Promise<void> {
  const type = (link?.type || 'none').toLowerCase();
  const value = link?.value ? String(link.value) : '';
  const title = link?.label || undefined;

  switch (type) {
    case 'none':
    case '':
      return;
    case 'category':
      if (!value) return;
      router.push({
        pathname: '/products',
        params: { category_id: value, title: title || 'Collection' },
      });
      return;
    case 'brand':
      if (!value) return;
      router.push({
        pathname: '/products',
        params: { brand_id: value, title: title || 'Brand' },
      });
      return;
    case 'product':
      if (!value) return;
      router.push(`/products/${value}`);
      return;
    case 'coupon':
      router.push({
        pathname: '/checkout',
        params: value ? { coupon: value } : {},
      });
      return;
    case 'url':
    case 'external':
      if (!value) return;
      if (value.startsWith('http://') || value.startsWith('https://')) {
        await Linking.openURL(value);
        return;
      }
      if (value.startsWith('/')) {
        router.push(value as never);
      }
      return;
    case 'deals':
    case 'sales':
      router.push('/deals');
      return;
    default:
      if (value.startsWith('http')) {
        await Linking.openURL(value);
      }
  }
}

export function saleToDeepLink(sale: {
  link_type?: string | null;
  link_value?: string | null;
  title?: string | null;
}): DeepLink {
  return {
    type: sale.link_type || 'deals',
    value: sale.link_value,
    label: sale.title,
  };
}

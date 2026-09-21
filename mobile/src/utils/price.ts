export function formatInr(amount: number | null | undefined): string {
  if (amount == null || Number.isNaN(Number(amount))) return '—';
  try {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0,
    }).format(Number(amount));
  } catch {
    return `₹${Math.round(Number(amount)).toLocaleString('en-IN')}`;
  }
}

export function sellingPrice(mrp: number, sale?: number | null): number {
  if (sale != null && sale > 0 && sale < mrp) return sale;
  return mrp;
}

export function discountPercent(mrp: number, sale?: number | null): number | null {
  if (sale == null || sale <= 0 || sale >= mrp) return null;
  return Math.round(((mrp - sale) / mrp) * 100);
}

/**
 * Catalog prices are GST-inclusive (India retail).
 * Never add tax% on top of the listed price — only shipping.
 */
export function orderTotals(subtotal: number, shipping = 0, discount = 0) {
  const afterDiscount = Math.max(0, Number(subtotal) - Number(discount || 0));
  const ship = Math.max(0, Number(shipping) || 0);
  const total = Math.round((afterDiscount + ship) * 100) / 100;
  return {
    afterDiscount: Math.round(afterDiscount * 100) / 100,
    tax: 0,
    shipping: ship,
    total,
  };
}

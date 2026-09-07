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

<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Stock Valuation</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}h1{font-size:18px;color:#0d9488}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f3f4f6}.muted{color:#666}</style></head><body>
<h1>Unique Solution — Stock Valuation</h1>
<div class="muted">SKUs: {{ $summary['sku_count'] }} · Units: {{ $summary['total_units'] }} · Value: {{ format_money($summary['total_value']) }}</div>
<table><thead><tr><th>SKU</th><th>Product</th><th>Stock</th><th>Unit</th><th>Value</th></tr></thead><tbody>
@foreach($variants as $variant)
@php $unit = (float) ($variant->discount_price ?? $variant->price); @endphp
<tr>
<td>{{ $variant->sku }}</td>
<td>{{ $variant->product?->name }}</td>
<td>{{ $variant->stock_quantity }}</td>
<td>{{ format_money($unit) }}</td>
<td>{{ format_money($unit * (int)$variant->stock_quantity) }}</td>
</tr>
@endforeach
</tbody></table></body></html>

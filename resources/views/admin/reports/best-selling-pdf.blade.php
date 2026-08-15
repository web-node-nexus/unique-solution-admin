<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Best Selling</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}h1{font-size:18px;color:#0d9488}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f3f4f6}.muted{color:#666}</style></head><body>
<h1>Unique Solution — Best Selling</h1>
<div class="muted">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
<table><thead><tr><th>#</th><th>Product</th><th>Units</th><th>Revenue</th></tr></thead><tbody>
@foreach($products as $i => $product)
<tr><td>{{ $i+1 }}</td><td>{{ $product->product_name }}</td><td>{{ $product->units_sold }}</td><td>{{ format_money($product->revenue) }}</td></tr>
@endforeach
</tbody></table></body></html>

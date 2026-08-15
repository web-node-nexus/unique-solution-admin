<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Sales Report</title>
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#222}
h1{font-size:18px;margin:0 0 4px;color:#0d9488}
.muted{color:#666;margin-bottom:16px}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ddd;padding:6px;text-align:left}
th{background:#f3f4f6}
</style></head><body>
<h1>Unique Solution — Sales Report</h1>
<div class="muted">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }} · Orders: {{ $summary['orders_count'] }} · Revenue: {{ format_money($summary['revenue']) }}</div>
<table>
<thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
<tbody>
@foreach($orders as $order)
<tr>
<td>{{ $order->order_number }}</td>
<td>{{ $order->user?->name ?? 'Guest' }}</td>
<td>{{ $order->order_status }}</td>
<td>{{ format_money($order->total_amount) }}</td>
<td>{{ $order->created_at?->format('d M Y') }}</td>
</tr>
@endforeach
</tbody></table>
</body></html>

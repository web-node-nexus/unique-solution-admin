<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Refunds Report</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}h1{font-size:18px;color:#0d9488}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f3f4f6}.muted{color:#666}</style></head><body>
<h1>Unique Solution — Refunds Report</h1>
<div class="muted">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }} · Count: {{ $summary['count'] }} · Approved: {{ format_money($summary['approved_amount']) }}</div>
<table><thead><tr><th>ID</th><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
@foreach($refunds as $refund)
<tr>
<td>#{{ $refund->id }}</td>
<td>{{ $refund->order?->order_number }}</td>
<td>{{ $refund->requestedBy?->name }}</td>
<td>{{ format_money($refund->refund_amount) }}</td>
<td>{{ $refund->status }}</td>
<td>{{ $refund->created_at?->format('d M Y') }}</td>
</tr>
@endforeach
</tbody></table></body></html>

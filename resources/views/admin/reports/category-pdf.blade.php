<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Category Report</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}h1{font-size:18px;color:#0d9488}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f3f4f6}.muted{color:#666}</style></head><body>
<h1>Unique Solution — Category Sales</h1>
<div class="muted">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
<table><thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead><tbody>
@foreach($rows as $row)
<tr><td>{{ $row->name }}</td><td>{{ $row->units }}</td><td>{{ format_money($row->revenue) }}</td></tr>
@endforeach
</tbody></table></body></html>

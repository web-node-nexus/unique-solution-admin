<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Packing Slip — {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #666; }
        .meta { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .actions { margin-bottom: 16px; }
        @media print {
            .actions { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Print</button>
        <a href="{{ route('admin.orders.packing-slip', $order) }}?pdf=1">Download PDF</a>
        <a href="{{ route('admin.orders.show', $order) }}">Back to order</a>
    </div>

    <h1>{{ shop_name() }}</h1>
    <div class="muted">{{ shop_address() ?: setting('shop_address', 'Kargil Chowk') }}</div>
    <div class="muted">{{ setting('contact_number') }} @if(setting('contact_email')) · {{ setting('contact_email') }} @endif</div>

    <h2>Packing Slip</h2>
    <div class="meta">
        <div><strong>Order:</strong> {{ $order->order_number }}</div>
        <div><strong>Date:</strong> {{ $order->created_at?->format('d M Y') }}</div>
        <div><strong>Customer:</strong> {{ $order->user?->name ?? 'Guest' }}</div>
        <div><strong>Phone:</strong> {{ $order->user?->phone ?? '—' }}</div>
    </div>

    <h2>Ship to</h2>
    <pre style="white-space:pre-wrap;font-family:inherit;margin:0;">{{ $order->shipping_address }}</pre>

    <h2>Items</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variant</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product_name_snapshot }}</td>
                    <td>
                        @if (is_array($item->variant_details_snapshot))
                            @foreach ($item->variant_details_snapshot as $k => $v)
                                {{ is_string($k) ? $k.': ' : '' }}{{ is_array($v) ? implode(', ', $v) : $v }}@if(!$loop->last); @endif
                            @endforeach
                        @else
                            {{ $item->variant_details_snapshot }}
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No items</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($order->notes)
        <h2>Notes</h2>
        <p>{{ $order->notes }}</p>
    @endif
</body>
</html>

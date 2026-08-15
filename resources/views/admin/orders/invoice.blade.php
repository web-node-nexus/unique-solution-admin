<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 22px; margin: 0; color: #0d9488; }
        h2 { font-size: 14px; margin: 20px 0 8px; }
        .muted { color: #666; }
        .header { margin-bottom: 20px; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; width: 48%; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { border: none; padding: 4px 0; }
        .totals .grand { font-weight: bold; font-size: 14px; border-top: 1px solid #ccc; padding-top: 8px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $shopName ?? shop_name() }}</h1>
        <div class="muted">{{ $shopAddress ?? (shop_address() ?: 'Kargil Chowk') }}</div>
        <div class="muted">{{ setting('contact_email') }} {{ setting('contact_number') }}</div>
    </div>

    <h2>Tax Invoice</h2>
    <div class="row">
        <div class="col">
            <strong>Invoice #:</strong> {{ $order->order_number }}<br>
            <strong>Date:</strong> {{ $order->created_at?->format('d M Y') }}<br>
            <strong>Payment:</strong> {{ ucfirst($order->payment_status) }}
        </div>
        <div class="col">
            <strong>Bill to</strong><br>
            {{ $order->user?->name ?? 'Guest' }}<br>
            {{ $order->user?->email }}<br>
            <pre style="white-space:pre-wrap;font-family:inherit;margin:4px 0 0;">{{ $order->billing_address }}</pre>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Details</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product_name_snapshot }}</td>
                    <td>
                        @if (is_array($item->variant_details_snapshot))
                            @foreach ($item->variant_details_snapshot as $k => $v)
                                {{ is_string($k) ? $k.': ' : '' }}{{ is_array($v) ? implode(', ', $v) : $v }}@if(!$loop->last); @endif
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ format_money($item->price) }}</td>
                    <td>{{ format_money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">{{ format_money($order->subtotal) }}</td></tr>
        <tr><td>Discount</td><td class="text-right">{{ format_money($order->discount) }}</td></tr>
        <tr><td>Tax</td><td class="text-right">{{ format_money($order->tax) }}</td></tr>
        <tr><td>Shipping</td><td class="text-right">{{ format_money($order->shipping_charge) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="text-right">{{ format_money($order->total_amount) }}</td></tr>
    </table>

    <p class="muted" style="margin-top:30px;">Thank you for shopping with Unique Solution.</p>
</body>
</html>

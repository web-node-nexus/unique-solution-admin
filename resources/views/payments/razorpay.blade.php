<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pay · {{ $shopName }}</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #F3EEE6; color: #1A1F1C; margin: 0; padding: 24px; }
    .card { max-width: 420px; margin: 40px auto; background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 8px 30px rgba(0,0,0,.08); }
    h1 { font-size: 22px; margin: 0 0 8px; }
    p { color: #5C675F; line-height: 1.5; }
    .amount { font-size: 28px; font-weight: 700; margin: 16px 0; color: #0E6B5C; }
    button { width: 100%; border: 0; background: #0E6B5C; color: #fff; font-size: 16px; font-weight: 600; padding: 14px; border-radius: 12px; cursor: pointer; }
  </style>
</head>
<body>
  <div class="card">
    <h1>{{ $shopName }}</h1>
    <p>Order <strong>{{ $order->order_number }}</strong></p>
    <div class="amount">₹{{ number_format((float) $order->total_amount, 2) }}</div>
    <p>Pay securely with UPI, card, or netbanking via Razorpay.</p>
    <button id="payBtn" type="button">Pay now</button>
  </div>

  <form id="confirmForm" method="POST" action="{{ url('/pay/'.$order->id.'/confirm') }}" style="display:none">
    @csrf
    <input type="hidden" name="t" value="{{ $token }}">
    <input type="hidden" name="razorpay_order_id" id="rz_order">
    <input type="hidden" name="razorpay_payment_id" id="rz_payment">
    <input type="hidden" name="razorpay_signature" id="rz_signature">
  </form>

  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
    const options = {
      key: @json($key),
      amount: {{ (int) $amount }},
      currency: @json($currency),
      name: @json($shopName),
      description: @json('Order '.$order->order_number),
      order_id: @json($razorpayOrderId),
      prefill: {
        name: @json($customer?->name),
        email: @json($customer?->email),
        contact: @json($customer?->phone),
      },
      theme: { color: '#0E6B5C' },
      handler: function (response) {
        document.getElementById('rz_order').value = response.razorpay_order_id;
        document.getElementById('rz_payment').value = response.razorpay_payment_id;
        document.getElementById('rz_signature').value = response.razorpay_signature;
        document.getElementById('confirmForm').submit();
      },
      modal: {
        ondismiss: function () {
          window.location.href = 'uniquesolution://orders/{{ $order->id }}?paid=0';
        }
      }
    };

    document.getElementById('payBtn').addEventListener('click', function () {
      const rzp = new Razorpay(options);
      rzp.open();
    });

    // Auto-open checkout
    setTimeout(function () {
      const rzp = new Razorpay(options);
      rzp.open();
    }, 400);
  </script>
</body>
</html>

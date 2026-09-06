<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentCheckoutController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpay,
        private readonly OrderService $orders,
    ) {}

    public function show(Request $request, Order $order): View|\Illuminate\Http\RedirectResponse
    {
        $token = (string) $request->query('t', '');
        $payment = $order->payments()->latest('id')->first();
        $gateway = is_array($payment?->gateway_response) ? $payment->gateway_response : [];

        if (! $payment || ($gateway['pay_token'] ?? null) !== $token) {
            abort(404);
        }

        if ($payment->status === 'paid') {
            return redirect()->away('uniquesolution://orders/'.$order->id.'?paid=1');
        }

        return view('payments.razorpay', [
            'order' => $order,
            'payment' => $payment,
            'key' => $this->razorpay->key(),
            'razorpayOrderId' => $gateway['razorpay_order_id'] ?? '',
            'amount' => (int) ($gateway['razorpay_amount'] ?? round($order->total_amount * 100)),
            'currency' => $gateway['razorpay_currency'] ?? 'INR',
            'shopName' => (string) Setting::get('shop_name', 'Unique Solution'),
            'token' => $token,
            'customer' => $order->user,
        ]);
    }

    public function confirm(Request $request, Order $order)
    {
        $data = $request->validate([
            't' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = $order->payments()->latest('id')->firstOrFail();
        $gateway = is_array($payment->gateway_response) ? $payment->gateway_response : [];

        if (($gateway['pay_token'] ?? null) !== $data['t']) {
            abort(403);
        }

        if ($payment->status !== 'paid') {
            if (! $this->razorpay->verifySignature(
                $data['razorpay_order_id'],
                $data['razorpay_payment_id'],
                $data['razorpay_signature']
            )) {
                return redirect()->away('uniquesolution://orders/'.$order->id.'?paid=0');
            }

            $gateway['razorpay_payment_id'] = $data['razorpay_payment_id'];
            $gateway['razorpay_signature'] = $data['razorpay_signature'];

            $payment->update([
                'status' => 'paid',
                'transaction_id' => $data['razorpay_payment_id'],
                'paid_at' => now(),
                'gateway_response' => $gateway,
            ]);
            $order->update(['payment_status' => 'paid']);

            if ($order->order_status === 'pending' && $order->user) {
                $this->orders->updateStatus($order, 'confirmed', 'Payment received online', $order->user);
            }
        }

        return redirect()->away(
            'uniquesolution://orders/'.$order->id
            .'?paid=1'
            .'&razorpay_order_id='.urlencode($data['razorpay_order_id'])
            .'&razorpay_payment_id='.urlencode($data['razorpay_payment_id'])
            .'&razorpay_signature='.urlencode($data['razorpay_signature'])
        );
    }
}

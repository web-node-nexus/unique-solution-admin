<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RazorpayService
{
    public function isConfigured(): bool
    {
        return $this->key() !== '' && $this->secret() !== '';
    }

    public function key(): string
    {
        return trim((string) Setting::get('razorpay_key', ''));
    }

    public function secret(): string
    {
        return trim((string) Setting::get('razorpay_secret', ''));
    }

    /**
     * @return array{id: string, amount: int, currency: string, receipt: string}
     */
    public function createOrder(float $amountInr, string $receipt): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured. Add keys in Admin → Settings.');
        }

        $amountPaise = (int) round($amountInr * 100);
        if ($amountPaise < 100) {
            throw new RuntimeException('Order amount is too low for online payment.');
        }

        $response = Http::withBasicAuth($this->key(), $this->secret())
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => 'INR',
                'receipt' => $receipt,
                'payment_capture' => 1,
            ]);

        if (! $response->successful()) {
            Log::error('Razorpay order create failed', ['body' => $response->json()]);
            throw new RuntimeException($response->json('error.description') ?? 'Could not start Razorpay order.');
        }

        /** @var array{id: string, amount: int, currency: string, receipt?: string} $data */
        $data = $response->json();

        return [
            'id' => $data['id'],
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'] ?? 'INR',
            'receipt' => $data['receipt'] ?? $receipt,
        ];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret());

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPayment(string $paymentId): ?array
    {
        $response = Http::withBasicAuth($this->key(), $this->secret())
            ->acceptJson()
            ->get("https://api.razorpay.com/v1/payments/{$paymentId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwoFactorSmsService
{
    public function sendOtp(string $phone10): string
    {
        $apiKey = (string) config('services.twofactor.api_key');
        $template = (string) config('services.twofactor.template', 'OTP1');
        $base = rtrim((string) config('services.twofactor.base_url'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('SMS gateway is not configured.');
        }

        // AUTOGEN accepts the 10-digit Indian mobile number.
        $url = "{$base}/{$apiKey}/SMS/{$phone10}/AUTOGEN/{$template}";
        $response = Http::timeout(20)->acceptJson()->get($url);
        $json = $response->json() ?? [];

        $status = strtolower((string) ($json['Status'] ?? $json['status'] ?? ''));
        $details = (string) ($json['Details'] ?? $json['details'] ?? '');

        if (! $response->successful() || $status !== 'success' || $details === '') {
            $message = $details !== '' ? $details : 'Could not send OTP right now.';
            throw new RuntimeException($message);
        }

        return $details;
    }

    /**
     * Verify with 2factor VERIFY3: phone (with country code) + OTP value.
     * GET /API/V1/{api_key}/SMS/VERIFY3/91XXXXXXXXXX/{otp}
     */
    public function verifyOtp(string $phone10, string $otp): bool
    {
        $apiKey = (string) config('services.twofactor.api_key');
        $base = rtrim((string) config('services.twofactor.base_url'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('SMS gateway is not configured.');
        }

        $phoneWithCountry = '91'.$phone10;
        $otp = trim($otp);
        $url = "{$base}/{$apiKey}/SMS/VERIFY3/{$phoneWithCountry}/{$otp}";
        $response = Http::timeout(20)->acceptJson()->get($url);
        $json = $response->json() ?? [];

        $status = strtolower((string) ($json['Status'] ?? $json['status'] ?? ''));
        $details = strtolower((string) ($json['Details'] ?? $json['details'] ?? ''));

        // Docs sometimes show Status=Error with Details="OTP Matched".
        if (str_contains($details, 'otp matched') || str_contains($details, 'matched')) {
            return true;
        }

        return $response->successful() && $status === 'success';
    }

    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10) {
            return null;
        }

        return $digits;
    }
}

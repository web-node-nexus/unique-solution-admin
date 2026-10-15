<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DeliveryAuthController extends Controller
{
    public function sendOtp(Request $request, TwoFactorSmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = TwoFactorSmsService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid 10-digit mobile number.'],
            ]);
        }

        $staff = $this->findStaffByPhone($phone);
        if (! $staff) {
            throw ValidationException::withMessages([
                'phone' => ['No delivery partner account found for this number.'],
            ]);
        }

        $throttleKey = "delivery-otp-send:{$phone}";
        if (Cache::has($throttleKey)) {
            throw ValidationException::withMessages([
                'phone' => ['Please wait a minute before requesting another OTP.'],
            ]);
        }

        try {
            $sessionId = $sms->sendOtp($phone);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        Cache::put("delivery-otp-session:{$phone}", $sessionId, now()->addMinutes(10));
        Cache::put($throttleKey, 1, now()->addMinute());

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone.',
            'data' => [
                'phone' => $phone,
                'expires_in' => 600,
            ],
        ]);
    }

    public function verifyOtp(Request $request, TwoFactorSmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $phone = TwoFactorSmsService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid 10-digit mobile number.'],
            ]);
        }

        $staff = $this->findStaffByPhone($phone);
        if (! $staff) {
            throw ValidationException::withMessages([
                'phone' => ['No delivery partner account found for this number.'],
            ]);
        }

        if (! Cache::has("delivery-otp-session:{$phone}")) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired. Request a new one.'],
            ]);
        }

        try {
            // 2factor VERIFY3: phone with country code + OTP value.
            $ok = $sms->verifyOtp($phone, trim($data['otp']));
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'otp' => [$e->getMessage()],
            ]);
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP. Try again.'],
            ]);
        }

        Cache::forget("delivery-otp-session:{$phone}");
        Cache::forget("delivery-otp-send:{$phone}");

        $staff->tokens()->where('name', 'delivery-app')->delete();
        $token = $staff->createToken('delivery-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in.',
            'data' => [
                'token' => $token,
                'user' => $this->transformStaff($staff),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->isDeliveryStaff($user), 403);

        return response()->json([
            'success' => true,
            'data' => $this->transformStaff($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }

    private function findStaffByPhone(string $phone10): ?User
    {
        return User::query()
            ->role('Staff')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get(['id', 'name', 'email', 'phone', 'is_active'])
            ->first(function (User $user) use ($phone10) {
                return TwoFactorSmsService::normalizePhone((string) $user->phone) === $phone10;
            });
    }

    private function isDeliveryStaff(?User $user): bool
    {
        return $user !== null && $user->is_active && $user->hasRole('Staff', 'web');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformStaff(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => TwoFactorSmsService::normalizePhone((string) $user->phone) ?? $user->phone,
            'email' => $user->email,
        ];
    }
}

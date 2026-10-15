<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'customer',
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->transformUser($user),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        throw ValidationException::withMessages([
            'phone' => ['Password login is disabled. Sign in with mobile OTP.'],
        ]);
    }

    /**
     * Send login OTP to a customer mobile number (creates account on first verify).
     */
    public function sendOtp(Request $request, TwoFactorSmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = TwoFactorSmsService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid 10-digit Indian mobile number.'],
            ]);
        }

        $existing = $this->findCustomerByPhone($phone);
        if ($existing && $existing->is_active === false) {
            throw ValidationException::withMessages([
                'phone' => ['This account is blocked. Contact the shop.'],
            ]);
        }

        if ($this->isStaffPhone($phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Staff numbers must use the delivery partner app.'],
            ]);
        }

        $throttleKey = "customer-otp-send:{$phone}";
        if (Cache::has($throttleKey)) {
            throw ValidationException::withMessages([
                'phone' => ['Please wait a minute before requesting another OTP.'],
            ]);
        }

        try {
            $sms->sendOtp($phone);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        Cache::put("customer-otp-session:{$phone}", 1, now()->addMinutes(10));
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

    /**
     * Verify customer OTP and issue a long-lived Sanctum token.
     */
    public function verifyOtp(Request $request, TwoFactorSmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $phone = TwoFactorSmsService::normalizePhone($data['phone']);
        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => ['Enter a valid 10-digit Indian mobile number.'],
            ]);
        }

        if (! Cache::has("customer-otp-session:{$phone}")) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired. Request a new one.'],
            ]);
        }

        try {
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

        Cache::forget("customer-otp-session:{$phone}");
        Cache::forget("customer-otp-send:{$phone}");

        $user = $this->findCustomerByPhone($phone);
        if (! $user) {
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $name = 'Customer '.substr($phone, -4);
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $phone.'@phone.customer.local',
                'phone' => $phone,
                'password' => Str::random(40),
                'role' => 'customer',
                'is_active' => true,
            ]);
        }

        if ($user->is_active === false) {
            throw ValidationException::withMessages([
                'phone' => ['This account is blocked. Contact the shop.'],
            ]);
        }

        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'phone' => ['Please use the admin panel for staff accounts.'],
            ]);
        }

        if (TwoFactorSmsService::normalizePhone((string) $user->phone) !== $phone) {
            $user->update(['phone' => $phone]);
        }

        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in.',
            'data' => [
                'token' => $token,
                'user' => $this->transformUser($user->fresh()),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->transformUser($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->transformUser($user->fresh()),
            'message' => 'Profile updated.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $data['password']]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Password updated.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        // Always return success to avoid email enumeration.
        if ($user && ! $user->isAdmin()) {
            $code = (string) random_int(100000, 999999);
            Cache::put($this->resetCacheKey($user->email), Hash::make($code), now()->addMinutes(15));

            try {
                Mail::raw(
                    "Your Unique Solution password reset code is: {$code}\n\nThis code expires in 15 minutes.",
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Password reset code');
                    }
                );
            } catch (\Throwable $e) {
                if (config('app.debug')) {
                    \Log::info('Password reset code (mail failed)', [
                        'email' => $user->email,
                        'code' => $code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (config('app.debug')) {
                \Log::info('Password reset code', ['email' => $user->email, 'code' => $code]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'If that email exists, a reset code has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        $cached = $user ? Cache::get($this->resetCacheKey($user->email)) : null;

        if (! $user || ! $cached || ! Hash::check($data['code'], $cached)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code.'],
            ]);
        }

        $user->update(['password' => $data['password']]);
        Cache::forget($this->resetCacheKey($user->email));

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Password reset successfully. You can sign in now.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'data' => null]);
    }

    private function resetCacheKey(string $email): string
    {
        return 'pwd_reset:'.strtolower($email);
    }

    private function findCustomerByPhone(string $phone10): ?User
    {
        return User::query()
            ->customers()
            ->whereNotNull('phone')
            ->get(['id', 'name', 'email', 'phone', 'address', 'is_active', 'role'])
            ->first(function (User $user) use ($phone10) {
                return TwoFactorSmsService::normalizePhone((string) $user->phone) === $phone10;
            });
    }

    private function isStaffPhone(string $phone10): bool
    {
        return User::query()
            ->role(['Super Admin', 'Manager', 'Staff', 'Support'])
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->contains(fn (User $user) => TwoFactorSmsService::normalizePhone((string) $user->phone) === $phone10);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => TwoFactorSmsService::normalizePhone((string) ($user->phone ?? '')) ?? $user->phone,
            'address' => $user->address,
        ];
    }
}

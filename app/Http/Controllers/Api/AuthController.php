<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if ($user->is_active === false) {
            throw ValidationException::withMessages([
                'email' => ['This account is blocked. Contact the shop.'],
            ]);
        }

        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Please use the admin panel for staff accounts.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->transformUser($user),
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

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
        ];
    }
}

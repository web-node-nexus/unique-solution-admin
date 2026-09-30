<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Signed tokens for images uploaded one-by-one before the product form is saved.
 * Only paths issued by the server (inside allowed folders) can be attached, so the
 * form can never point a product at an arbitrary file.
 */
class StagedUpload
{
    public const FOLDERS = ['products', 'variants'];

    public static function issue(string $path, int $userId): string
    {
        return Crypt::encryptString(json_encode([
            'p' => $path,
            'u' => $userId,
            't' => time(),
        ]));
    }

    public static function resolve(mixed $token, ?int $userId = null): ?string
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        $path = is_array($payload) ? ($payload['p'] ?? null) : null;
        if (! is_string($path) || str_contains($path, '..')) {
            return null;
        }

        if ($userId !== null && (int) ($payload['u'] ?? 0) !== $userId) {
            return null;
        }

        $folder = strtok($path, '/');
        if (! in_array($folder, self::FOLDERS, true)) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /**
     * @return list<string>
     */
    public static function resolveMany(mixed $tokens, ?int $userId = null): array
    {
        $paths = [];
        foreach ((array) $tokens as $token) {
            $path = self::resolve($token, $userId);
            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}

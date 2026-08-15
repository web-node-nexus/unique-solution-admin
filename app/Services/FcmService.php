<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmService
{
    /**
     * Send a push to every registered device token.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: int, failure: int, skipped: bool, message?: string}
     */
    public function sendToAll(string $title, string $body, ?string $imageUrl = null, array $data = []): array
    {
        $tokens = DeviceToken::query()->pluck('token')->filter()->unique()->values()->all();

        if ($tokens === []) {
            return [
                'success' => 0,
                'failure' => 0,
                'skipped' => true,
                'message' => 'No device tokens registered yet.',
            ];
        }

        return $this->sendToTokens($tokens, $title, $body, $imageUrl, $data);
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     * @return array{success: int, failure: int, skipped: bool, message?: string}
     */
    public function sendToTokens(array $tokens, string $title, string $body, ?string $imageUrl = null, array $data = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return ['success' => 0, 'failure' => 0, 'skipped' => true, 'message' => 'No tokens provided.'];
        }

        $projectId = $this->projectId();
        $credentialsPath = $this->credentialsPath();
        $serverKey = $this->serverKey();

        try {
            if ($projectId && $credentialsPath && is_readable($credentialsPath)) {
                return $this->sendHttpV1($tokens, $title, $body, $imageUrl, $data, $projectId, $credentialsPath);
            }

            if ($serverKey !== '') {
                return $this->sendLegacy($tokens, $title, $body, $imageUrl, $data, $serverKey);
            }
        } catch (Throwable $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);

            return [
                'success' => 0,
                'failure' => count($tokens),
                'skipped' => false,
                'message' => $e->getMessage(),
            ];
        }

        Log::warning('FCM not configured — in-app notification saved, push skipped.');

        return [
            'success' => 0,
            'failure' => 0,
            'skipped' => true,
            'message' => 'FCM not configured. Add FCM credentials in Settings or .env.',
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     * @return array{success: int, failure: int, skipped: bool}
     */
    private function sendHttpV1(
        array $tokens,
        string $title,
        string $body,
        ?string $imageUrl,
        array $data,
        string $projectId,
        string $credentialsPath
    ): array {
        $accessToken = $this->googleAccessToken($credentialsPath);
        $success = 0;
        $failure = 0;
        $invalid = [];

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        foreach (array_chunk($tokens, 100) as $chunk) {
            foreach ($chunk as $token) {
                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => array_filter([
                            'title' => $title,
                            'body' => $body,
                            'image' => $imageUrl,
                        ]),
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'high',
                            'notification' => array_filter([
                                'image' => $imageUrl,
                                'sound' => 'default',
                            ]),
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'mutable-content' => 1,
                                ],
                            ],
                            'fcm_options' => array_filter([
                                'image' => $imageUrl,
                            ]),
                        ],
                    ],
                ];

                $response = Http::timeout((int) config('fcm.timeout', 15))
                    ->withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

                if ($response->successful()) {
                    $success++;
                    DeviceToken::query()->where('token', $token)->update(['last_used_at' => now()]);
                } else {
                    $failure++;
                    $errorCode = data_get($response->json(), 'error.details.0.errorCode')
                        ?? data_get($response->json(), 'error.status');

                    if (in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'], true)
                        || str_contains((string) $response->body(), 'UNREGISTERED')
                        || str_contains((string) $response->body(), 'Requested entity was not found')) {
                        $invalid[] = $token;
                    }

                    Log::warning('FCM v1 send failed', [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ]);
                }
            }
        }

        if ($invalid !== []) {
            DeviceToken::query()->whereIn('token', $invalid)->delete();
        }

        return ['success' => $success, 'failure' => $failure, 'skipped' => false];
    }

    /**
     * Legacy FCM HTTP API (server key).
     *
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     * @return array{success: int, failure: int, skipped: bool}
     */
    private function sendLegacy(
        array $tokens,
        string $title,
        string $body,
        ?string $imageUrl,
        array $data,
        string $serverKey
    ): array {
        $success = 0;
        $failure = 0;
        $invalid = [];

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        foreach (array_chunk($tokens, 500) as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'priority' => 'high',
                'notification' => array_filter([
                    'title' => $title,
                    'body' => $body,
                    'image' => $imageUrl,
                    'sound' => 'default',
                ]),
                'data' => $stringData,
            ];

            $response = Http::timeout((int) config('fcm.timeout', 15))
                ->withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (! $response->successful()) {
                $failure += count($chunk);
                Log::warning('FCM legacy send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                continue;
            }

            $results = $response->json('results') ?? [];
            foreach ($results as $index => $result) {
                if (! empty($result['message_id'])) {
                    $success++;
                    DeviceToken::query()->where('token', $chunk[$index])->update(['last_used_at' => now()]);
                } else {
                    $failure++;
                    if (! empty($result['error']) && in_array($result['error'], [
                        'NotRegistered',
                        'InvalidRegistration',
                    ], true)) {
                        $invalid[] = $chunk[$index];
                    }
                }
            }
        }

        if ($invalid !== []) {
            DeviceToken::query()->whereIn('token', $invalid)->delete();
        }

        return ['success' => $success, 'failure' => $failure, 'skipped' => false];
    }

    private function googleAccessToken(string $credentialsPath): string
    {
        $json = json_decode((string) file_get_contents($credentialsPath), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            throw new \RuntimeException('Invalid Firebase service account JSON.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claim;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $json['private_key'], OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new \RuntimeException('Unable to sign Firebase JWT.');
        }

        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new \RuntimeException('Unable to fetch Google OAuth access token for FCM.');
        }

        return (string) $response->json('access_token');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function projectId(): string
    {
        return (string) (Setting::get('fcm_project_id') ?: config('fcm.project_id') ?: '');
    }

    private function serverKey(): string
    {
        return (string) (Setting::get('fcm_server_key') ?: config('fcm.server_key') ?: '');
    }

    private function credentialsPath(): ?string
    {
        $fromSettings = Setting::get('fcm_credentials_path');
        $path = $fromSettings ?: config('fcm.credentials');

        if (! $path) {
            return null;
        }

        if (! str_starts_with((string) $path, '/')) {
            $path = storage_path('app/'.ltrim((string) $path, '/'));
        }

        return (string) $path;
    }
}

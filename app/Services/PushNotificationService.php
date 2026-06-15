<?php

namespace App\Services;

use App\Models\Device;
use App\Models\MobileAppSetting;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected ?array $credentials = null;

    public function isEnabled(): bool
    {
        if (!$this->getProjectId() || !$this->getCredentialsPath()) {
            return false;
        }

        try {
            $mobile = MobileAppSetting::current();

            return (bool) ($mobile->enable_push_notifications ?? true);
        } catch (\Throwable) {
            return true;
        }
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        if (!$this->isEnabled()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $type = (string) ($data['type'] ?? '');
        $priority = isset($data['priority']) ? (string) $data['priority'] : null;

        $user = User::find($userId);
        if ($user) {
            $preferences = $user->getOrCreateNotificationPreference();
            if (!$preferences->shouldNotifyForPush($type, $priority)) {
                return ['sent' => 0, 'failed' => 0, 'skipped' => true];
            }
        }

        $tokens = Device::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if (empty($userIds)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $totals = ['sent' => 0, 'failed' => 0, 'skipped' => false];

        foreach ($userIds as $userId) {
            $result = $this->sendToUser($userId, $title, $body, $data);
            $totals['sent'] += $result['sent'] ?? 0;
            $totals['failed'] += $result['failed'] ?? 0;
        }

        return $totals;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $result = $this->sendToTokens([$token], $title, $body, $data);

        return ($result['sent'] ?? 0) > 0;
    }

    protected function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (!$this->isEnabled()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('FCM push skipped: unable to obtain access token');

            return ['sent' => 0, 'failed' => count($tokens), 'skipped' => false];
        }

        $projectId = $this->getProjectId();
        $payloadData = $this->normalizeDataPayload($data, $title, $body);
        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $payloadData,
                            'android' => [
                                'priority' => 'HIGH',
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $sent++;
                    continue;
                }

                $failed++;
                $this->handleFailedDelivery($token, $response->json(), $response->status());
            } catch (\Throwable $e) {
                $failed++;
                Log::error('FCM push delivery failed', [
                    'token' => substr($token, 0, 12) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => false];
    }

    protected function handleFailedDelivery(string $token, ?array $responseBody, int $statusCode): void
    {
        Log::warning('FCM push rejected', [
            'status' => $statusCode,
            'token' => substr($token, 0, 12) . '...',
            'response' => $responseBody,
        ]);

        if (!$this->shouldClearToken($responseBody, $statusCode)) {
            return;
        }

        Device::query()
            ->where('fcm_token', $token)
            ->update([
                'fcm_token' => null,
                'is_active' => false,
            ]);
    }

    protected function shouldClearToken(?array $responseBody, int $statusCode): bool
    {
        if (in_array($statusCode, [404, 410], true)) {
            return true;
        }

        $details = $responseBody['error']['details'] ?? [];
        foreach ($details as $detail) {
            $errorCode = strtoupper((string) ($detail['errorCode'] ?? ''));
            if ($errorCode === 'UNREGISTERED') {
                return true;
            }
        }

        $message = strtolower((string) ($responseBody['error']['message'] ?? ''));
        if (str_contains($message, 'not found') || str_contains($message, 'unregistered')) {
            return true;
        }

        return false;
    }

    protected function normalizeDataPayload(array $data, string $title, string $body): array
    {
        $payload = array_merge([
            'title' => $title,
            'body' => $body,
        ], $data);

        $normalized = [];
        foreach ($payload as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $normalized[(string) $key] = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $normalized[(string) $key] = (string) $value;
            } else {
                $normalized[(string) $key] = json_encode($value);
            }
        }

        return $normalized;
    }

    protected function getAccessToken(): ?string
    {
        $cacheKey = 'firebase_fcm_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $credentials = $this->loadCredentials();
            if (!$credentials) {
                return null;
            }

            $now = time();
            $jwt = JWT::encode([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $credentials['private_key'], 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to obtain Firebase access token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    protected function loadCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials ?: null;
        }

        $path = $this->getCredentialsPath();
        if (!$path || !is_readable($path)) {
            $this->credentials = [];

            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            Log::error('Invalid Firebase credentials file', ['path' => $path]);
            $this->credentials = [];

            return null;
        }

        $this->credentials = $json;

        return $this->credentials;
    }

    protected function getProjectId(): ?string
    {
        $configured = config('services.firebase.project_id');
        if ($configured) {
            return $configured;
        }

        $credentials = $this->loadCredentials();

        return $credentials['project_id'] ?? null;
    }

    protected function getCredentialsPath(): ?string
    {
        $path = config('services.firebase.credentials');
        if (!$path) {
            return null;
        }

        if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }
}

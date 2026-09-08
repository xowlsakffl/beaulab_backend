<?php

namespace App\Domains\Common\Notification\Actions;

use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Common\Notification\Models\NotificationDevice;
use App\Domains\Common\Notification\Models\NotificationInbox;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PUSH delivery를 실제 FCM/APNs provider로 발송한다.
 * 세대별 스냅샷과 기기별 결과를 유지하고, Job 하나가 기기 하나를 처리한다.
 */
final class SendPushNotificationDeliveryAction
{
    public function execute(int $deliveryId): array
    {
        $delivery = \Illuminate\Support\Facades\DB::transaction(function () use ($deliveryId): ?NotificationDelivery {
            $delivery = NotificationDelivery::query()->lockForUpdate()->find($deliveryId);
            if (! $delivery || $delivery->channel !== NotificationDelivery::CHANNEL_PUSH
                || $delivery->status !== NotificationDelivery::STATUS_PENDING
                || $delivery->processing_until?->isFuture()
                || $delivery->next_attempt_at?->isFuture()) {
                return null;
            }

            $delivery->forceFill([
                'lease_token' => (string) \Illuminate\Support\Str::uuid(),
                'processing_until' => now()->addMinutes(5),
                'attempted_at' => now(),
            ])->save();

            return $delivery;
        });

        if (! $delivery) {
            return ['skipped' => true];
        }

        $snapshot = $delivery->message_snapshot;
        $inbox = is_array($snapshot)
            ? (new NotificationInbox)->forceFill($snapshot)
            : $delivery->inbox;
        $results = $delivery->device_results ?? [];
        $devices = $inbox ? NotificationDevice::query()
            ->where('owner_type', $inbox->recipient_type)
            ->where('owner_id', $inbox->recipient_id)
            ->whereNull('revoked_at')
            ->orderBy('id')->get() : collect();
        $pending = [];
        $error = null;

        if (! (bool) config('notification_push.enabled', false)) {
            $error = 'PUSH_ENABLED 설정이 꺼져 있습니다.';
        } elseif (! $inbox || $devices->isEmpty()) {
            $error = '활성 푸시 디바이스가 없습니다.';
        } else {
            foreach ($devices as $device) {
                $result = $results[(string) $device->id] ?? null;
                if ($result === null || $result['status'] === NotificationDelivery::STATUS_PENDING) {
                    $pending[] = $device;
                }
            }

            // One device per job bounds network time and preserves successful devices.
            $device = collect($pending)->first(fn ($device): bool => (int) ($results[(string) $device->id]['next_attempt_at'] ?? 0) <= now()->timestamp);
            if ($device) {
                $key = (string) $device->id;
                $attempts = (int) ($results[$key]['attempts'] ?? 0) + 1;
                try {
                    $result = $this->sendToDevice($inbox, $device);
                } catch (Throwable $exception) {
                    Log::warning('Push transport failure.', ['delivery_id' => $deliveryId, 'device_id' => $device->id, 'exception' => $exception::class]);
                    $result = $this->failedResult('UNKNOWN', 'Push transport failed.', retryable: true);
                }

                $retry = ! $result['success'] && ($result['retryable'] ?? false) && $attempts < 3;
                $results[$key] = [
                    'status' => $result['success'] ? NotificationDelivery::STATUS_SENT
                        : ($retry ? NotificationDelivery::STATUS_PENDING : NotificationDelivery::STATUS_FAILED),
                    'attempts' => $attempts,
                    'next_attempt_at' => $retry ? now()->addSeconds($attempts * 30)->timestamp : null,
                    'provider' => $result['provider'],
                    'provider_message_id' => $result['provider_message_id'],
                    'error' => $result['success'] ? null : mb_substr((string) ($result['error'] ?? 'Push failed.'), 0, 1000),
                ];
                if ($result['invalid_token']) {
                    NotificationDevice::query()->whereKey($device->id)
                        ->where('push_token', $device->push_token)->update(['revoked_at' => now()]);
                }
            }
        }

        $nextAttempts = [];
        foreach ($devices as $device) {
            $result = $results[(string) $device->id] ?? null;
            if ($error === null && ($result === null || $result['status'] === NotificationDelivery::STATUS_PENDING)) {
                $nextAttempts[] = (int) ($result['next_attempt_at'] ?? now()->timestamp);
            }
        }
        $waiting = $nextAttempts !== [];
        $failed = $error !== null || collect($results)->contains(fn ($result): bool => $result['status'] === NotificationDelivery::STATUS_FAILED);
        $status = $waiting ? NotificationDelivery::STATUS_PENDING
            : ($failed ? NotificationDelivery::STATUS_FAILED : NotificationDelivery::STATUS_SENT);
        $delay = $waiting ? max(1, min($nextAttempts) - now()->timestamp) : null;

        $saved = NotificationDelivery::query()->whereKey($delivery->id)
            ->where('generation', $delivery->generation)->where('lease_token', $delivery->lease_token)
            ->update([
                'status' => $status,
                'provider' => $this->summarizeProvider(array_column($results, 'provider')),
                'provider_message_id' => collect($results)->first(fn ($result): bool => $result['status'] === NotificationDelivery::STATUS_SENT)['provider_message_id'] ?? null,
                'device_results' => json_encode($results, JSON_THROW_ON_ERROR),
                'message_snapshot' => json_encode($snapshot ?? $inbox?->only(['id', 'recipient_type', 'recipient_id', 'event_type', 'title', 'body', 'target_type', 'target_id', 'payload']), JSON_THROW_ON_ERROR),
                'lease_token' => null,
                'processing_until' => null,
                'next_attempt_at' => $waiting ? now()->addSeconds($delay) : null,
                'delivered_at' => $status === NotificationDelivery::STATUS_SENT ? now() : null,
                'failed_at' => $status === NotificationDelivery::STATUS_FAILED ? now() : null,
                'error_message' => $error ?? ($failed ? 'One or more push devices failed; see device_results.' : null),
                'updated_at' => now(),
            ]);

        return $saved && $waiting ? ['retry_after' => $delay] : ['skipped' => false];
    }

    /**
     * @return array{provider:string, success:bool, provider_message_id:?string, error:?string, invalid_token:bool}
     */
    private function sendToDevice(NotificationInbox $inbox, NotificationDevice $device): array
    {
        $provider = mb_strtolower((string) config(
            "notification_push.provider_by_platform.{$device->platform}",
            'fcm'
        ));

        return match ($provider) {
            'apns' => $this->sendViaApns($inbox, $device),
            default => $this->sendViaFcm($inbox, $device),
        };
    }

    /**
     * @return array{provider:string, success:bool, provider_message_id:?string, error:?string, invalid_token:bool}
     */
    private function sendViaFcm(NotificationInbox $inbox, NotificationDevice $device): array
    {
        if (! (bool) config('notification_push.fcm.enabled', false)) {
            return $this->failedResult(NotificationDelivery::PROVIDER_FCM, 'FCM 설정이 꺼져 있습니다.');
        }

        $account = $this->fcmServiceAccount();
        if ($account === null) {
            return $this->failedResult(NotificationDelivery::PROVIDER_FCM, 'FCM service account 설정이 없습니다.');
        }

        $accessToken = $this->fcmAccessToken($account);
        if ($accessToken === null) {
            return $this->failedResult(NotificationDelivery::PROVIDER_FCM, 'FCM access token 발급에 실패했습니다.', retryable: true);
        }

        $projectId = (string) $account['project_id'];
        $response = Http::connectTimeout(5)->timeout(min(10, max(1, (int) config('notification_push.timeout', 10))))
            ->withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => (string) $device->push_token,
                    'notification' => [
                        'title' => $this->title($inbox),
                        'body' => $this->body($inbox),
                    ],
                    'data' => $this->stringData($inbox),
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
            return [
                'provider' => NotificationDelivery::PROVIDER_FCM,
                'success' => true,
                'provider_message_id' => $response->json('name'),
                'error' => null,
                'invalid_token' => false,
            ];
        }

        $error = $response->json('error') ?? ['status' => $response->status(), 'body' => $response->body()];
        $error = is_array($error) ? $error : ['body' => (string) $error];
        $invalidToken = $this->isFcmInvalidToken($error);

        Log::warning('FCM 푸시 발송 실패', [
            'inbox_id' => $inbox->id,
            'device_id' => $device->id,
            'status' => $response->status(),
            'error_status' => $error['status'] ?? null,
            'error_code' => $error['code'] ?? null,
            'invalid_token' => $invalidToken,
        ]);

        return $this->failedResult(
            NotificationDelivery::PROVIDER_FCM,
            json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'FCM 발송 실패',
            $invalidToken,
            $response->status() === 429 || $response->serverError(),
        );
    }

    /**
     * @return array{provider:string, success:bool, provider_message_id:?string, error:?string, invalid_token:bool}
     */
    private function sendViaApns(NotificationInbox $inbox, NotificationDevice $device): array
    {
        if (! (bool) config('notification_push.apns.enabled', false)) {
            return $this->failedResult(NotificationDelivery::PROVIDER_APNS, 'APNs 설정이 꺼져 있습니다.');
        }

        $token = $this->apnsProviderToken();
        $bundleId = (string) config('notification_push.apns.bundle_id', '');

        if ($token === null || $bundleId === '') {
            return $this->failedResult(NotificationDelivery::PROVIDER_APNS, 'APNs 인증 설정이 없습니다.');
        }

        $host = mb_strtolower((string) config('notification_push.apns.environment')) === 'sandbox'
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';

        $response = Http::connectTimeout(5)->timeout(min(10, max(1, (int) config('notification_push.timeout', 10))))
            ->withOptions(['version' => 2.0])
            ->withToken($token)
            ->withHeaders([
                'apns-topic' => $bundleId,
                'apns-push-type' => 'alert',
                'apns-priority' => '10',
            ])
            ->post("{$host}/3/device/{$device->push_token}", [
                'aps' => [
                    'alert' => [
                        'title' => $this->title($inbox),
                        'body' => $this->body($inbox),
                    ],
                    'sound' => 'default',
                ],
                'data' => $this->stringData($inbox),
            ]);

        if ($response->successful()) {
            return [
                'provider' => NotificationDelivery::PROVIDER_APNS,
                'success' => true,
                'provider_message_id' => $response->header('apns-id'),
                'error' => null,
                'invalid_token' => false,
            ];
        }

        $reason = (string) ($response->json('reason') ?? $response->body());
        $invalidToken = in_array($reason, ['BadDeviceToken', 'Unregistered'], true);

        Log::warning('APNs 푸시 발송 실패', [
            'inbox_id' => $inbox->id,
            'device_id' => $device->id,
            'status' => $response->status(),
            'reason' => $reason !== '' ? mb_strimwidth($reason, 0, 500, '...') : null,
            'invalid_token' => $invalidToken,
        ]);

        return $this->failedResult(
            NotificationDelivery::PROVIDER_APNS,
            $reason !== '' ? $reason : 'APNs 발송 실패',
            $invalidToken,
            $response->status() === 429 || $response->serverError(),
        );
    }

    /**
     * @return array{provider:string, success:bool, provider_message_id:?string, error:?string, invalid_token:bool}
     */
    private function failedResult(string $provider, string $error, bool $invalidToken = false, bool $retryable = false): array
    {
        return [
            'provider' => $provider,
            'success' => false,
            'provider_message_id' => null,
            'error' => $error,
            'invalid_token' => $invalidToken,
            'retryable' => $retryable,
        ];
    }

    /**
     * @return array{project_id:string, client_email:string, private_key:string, token_uri:string, scope:string}|null
     */
    private function fcmServiceAccount(): ?array
    {
        $data = null;
        $json = trim((string) config('notification_push.fcm.service_account_json', ''));
        $path = trim((string) config('notification_push.fcm.service_account_path', ''));

        if ($json !== '') {
            $data = json_decode($json, true);
        } elseif ($path !== '' && is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true);
        }

        $projectId = (string) ($data['project_id'] ?? config('notification_push.fcm.project_id', ''));
        $clientEmail = (string) ($data['client_email'] ?? config('notification_push.fcm.client_email', ''));
        $privateKey = (string) ($data['private_key'] ?? config('notification_push.fcm.private_key', ''));
        $tokenUri = (string) ($data['token_uri'] ?? config('notification_push.fcm.token_uri'));
        $scope = (string) config('notification_push.fcm.scope');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '' || $tokenUri === '' || $scope === '') {
            return null;
        }

        return [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
            'token_uri' => $tokenUri,
            'scope' => $scope,
        ];
    }

    /**
     * @param  array{project_id:string, client_email:string, private_key:string, token_uri:string, scope:string}  $account
     */
    private function fcmAccessToken(array $account): ?string
    {
        $cacheKey = 'notification_push:fcm_access_token:'.sha1($account['client_email'].$account['project_id']);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($account): ?string {
            $now = time();
            $jwt = JWT::encode([
                'iss' => $account['client_email'],
                'scope' => $account['scope'],
                'aud' => $account['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ], $account['private_key'], 'RS256');

            $response = Http::asForm()
                ->connectTimeout(5)->timeout(min(10, max(1, (int) config('notification_push.timeout', 10))))
                ->post($account['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('FCM access token 발급 실패', [
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function apnsProviderToken(): ?string
    {
        $teamId = (string) config('notification_push.apns.team_id', '');
        $keyId = (string) config('notification_push.apns.key_id', '');
        $privateKey = (string) config('notification_push.apns.private_key', '');
        $privateKeyPath = (string) config('notification_push.apns.private_key_path', '');

        if ($privateKey === '' && $privateKeyPath !== '' && is_file($privateKeyPath)) {
            $privateKey = (string) file_get_contents($privateKeyPath);
        }

        if ($teamId === '' || $keyId === '' || $privateKey === '') {
            return null;
        }

        $cacheKey = 'notification_push:apns_provider_token:'.sha1($teamId.$keyId);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($teamId, $keyId, $privateKey): string {
            return JWT::encode([
                'iss' => $teamId,
                'iat' => time(),
            ], $privateKey, 'ES256', $keyId);
        });
    }

    private function isFcmInvalidToken(array $error): bool
    {
        $details = $error['details'] ?? [];
        $details = is_array($details) ? $details : [];

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            if (($detail['errorCode'] ?? null) === 'UNREGISTERED') {
                return true;
            }
        }

        return false;
    }

    private function summarizeProvider(array $providers): ?string
    {
        $providers = array_values(array_unique(array_filter($providers)));

        return match (count($providers)) {
            0 => null,
            1 => $providers[0],
            default => NotificationDelivery::PROVIDER_MIXED,
        };
    }

    private function title(NotificationInbox $inbox): string
    {
        $title = trim((string) $inbox->title);

        return $title !== '' ? $title : '새 알림이 도착했습니다.';
    }

    private function body(NotificationInbox $inbox): string
    {
        return trim((string) $inbox->body);
    }

    /**
     * FCM data payload는 문자열 값만 허용하므로 payload를 안전하게 문자열화한다.
     *
     * @return array<string, string>
     */
    private function stringData(NotificationInbox $inbox): array
    {
        $data = [
            'notification_id' => (string) $inbox->id,
            'event_type' => (string) $inbox->event_type,
            'target_type' => (string) $inbox->target_type,
            'target_id' => $inbox->target_id !== null ? (string) $inbox->target_id : '',
        ];

        foreach (($inbox->payload ?? []) as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $data[$key] = is_scalar($value) || $value === null
                ? (string) $value
                : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        }

        return $data;
    }
}

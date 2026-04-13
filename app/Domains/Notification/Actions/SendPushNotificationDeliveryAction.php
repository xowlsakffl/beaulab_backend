<?php

namespace App\Domains\Notification\Actions;

use App\Domains\Notification\Models\NotificationDelivery;
use App\Domains\Notification\Models\NotificationDevice;
use App\Domains\Notification\Models\NotificationInbox;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PUSH delivery를 실제 FCM/APNs provider로 발송한다.
 * delivery row는 채널 단위라서 여러 디바이스 결과를 한 행에 요약해서 남긴다.
 */
final class SendPushNotificationDeliveryAction
{
    public function execute(int $deliveryId): array
    {
        $delivery = NotificationDelivery::query()
            ->with('inbox')
            ->find($deliveryId);

        if (! $delivery instanceof NotificationDelivery || ! $delivery->inbox instanceof NotificationInbox) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        if ($delivery->channel !== NotificationDelivery::CHANNEL_PUSH) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $devices = NotificationDevice::query()
            ->where('owner_type', $delivery->inbox->recipient_type)
            ->where('owner_id', $delivery->inbox->recipient_id)
            ->whereNull('revoked_at')
            ->get();

        if ($devices->isEmpty()) {
            $this->markFailed($delivery, '활성 푸시 디바이스가 없습니다.');

            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        if (! (bool) config('notification_push.enabled', false)) {
            $this->markFailed($delivery, 'PUSH_ENABLED 설정이 꺼져 있습니다.');

            return ['sent' => 0, 'failed' => $devices->count(), 'skipped' => true];
        }

        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempted_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        $sent = 0;
        $failed = 0;
        $providers = [];
        $firstProviderMessageId = null;
        $errors = [];

        foreach ($devices as $device) {
            try {
                $result = $this->sendToDevice($delivery->inbox, $device);
            } catch (Throwable $exception) {
                Log::warning('푸시 발송 중 예외 발생', [
                    'delivery_id' => $delivery->id,
                    'device_id' => $device->id,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);

                $result = $this->failedResult('UNKNOWN', $exception->getMessage());
            }

            $providers[] = $result['provider'];

            if ($result['success']) {
                $sent++;
                $firstProviderMessageId ??= $result['provider_message_id'];

                continue;
            }

            $failed++;
            $errors[] = sprintf(
                'device:%d provider:%s error:%s',
                (int) $device->id,
                $result['provider'],
                $result['error'] ?? 'unknown'
            );

            if ($result['invalid_token']) {
                $device->forceFill(['revoked_at' => now()])->save();
            }
        }

        $provider = $this->summarizeProvider($providers);

        if ($sent > 0) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'provider' => $provider,
                'provider_message_id' => $firstProviderMessageId,
                'delivered_at' => now(),
                'failed_at' => null,
                'error_message' => $errors === [] ? null : mb_strimwidth(implode(' | ', $errors), 0, 2000, '...'),
            ])->save();
        } else {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_FAILED,
                'provider' => $provider,
                'provider_message_id' => null,
                'delivered_at' => null,
                'failed_at' => now(),
                'error_message' => mb_strimwidth(implode(' | ', $errors), 0, 2000, '...'),
            ])->save();
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => false,
        ];
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
            return $this->failedResult(NotificationDelivery::PROVIDER_FCM, 'FCM access token 발급에 실패했습니다.');
        }

        $projectId = (string) $account['project_id'];
        $response = Http::timeout((int) config('notification_push.timeout', 10))
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

        return $this->failedResult(
            NotificationDelivery::PROVIDER_FCM,
            json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'FCM 발송 실패',
            $this->isFcmInvalidToken($error)
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

        $response = Http::timeout((int) config('notification_push.timeout', 10))
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

        return $this->failedResult(
            NotificationDelivery::PROVIDER_APNS,
            $reason !== '' ? $reason : 'APNs 발송 실패',
            in_array($reason, ['BadDeviceToken', 'Unregistered'], true)
        );
    }

    private function markFailed(NotificationDelivery $delivery, string $message): void
    {
        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_FAILED,
            'attempted_at' => now(),
            'delivered_at' => null,
            'failed_at' => now(),
            'error_message' => $message,
        ])->save();
    }

    /**
     * @return array{provider:string, success:bool, provider_message_id:?string, error:?string, invalid_token:bool}
     */
    private function failedResult(string $provider, string $error, bool $invalidToken = false): array
    {
        return [
            'provider' => $provider,
            'success' => false,
            'provider_message_id' => null,
            'error' => $error,
            'invalid_token' => $invalidToken,
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
                ->timeout((int) config('notification_push.timeout', 10))
                ->post($account['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('FCM access token 발급 실패', [
                    'status' => $response->status(),
                    'body' => $response->body(),
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
        if (($error['status'] ?? null) === 'NOT_FOUND') {
            return true;
        }

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

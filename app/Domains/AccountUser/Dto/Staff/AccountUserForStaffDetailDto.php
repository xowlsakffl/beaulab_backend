<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserForStaffDetailDto DTO.
 */
final readonly class AccountUserForStaffDetailDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public ?string $phone,
        public string $signupChannel,
        public string $signupChannelLabel,
        public string $status,
        public string $statusLabel,
        public int $warningCount,
        public ?string $blockedAt,
        public ?string $withdrawalReason,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public ?string $lastAccessedAt,
        public ?string $lastAccessIp,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
        public array $notificationSettings,
        public array $consultationInfo,
        public array $activityInfo,
        public array $reportedInfo,
        public array $accessLogs,
    ) {}

    public static function fromModel(
        AccountUser $user,
        array $consultationInfo = [],
        array $activityInfo = [],
        array $reportedInfo = [],
        array $accessLogs = [],
    ): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            nickname: $user->nickname,
            email: $user->email,
            phone: $user->phone,
            signupChannel: (string) $user->signup_channel,
            signupChannelLabel: AccountUser::signupChannelLabels()[(string) $user->signup_channel] ?? (string) $user->signup_channel,
            status: $user->status,
            statusLabel: AccountUser::statusLabels()[(string) $user->status] ?? (string) $user->status,
            warningCount: (int) $user->warning_count,
            blockedAt: $user->blocked_at?->toISOString(),
            withdrawalReason: $user->withdrawal_reason,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            lastLoginAt: $user->last_login_at?->toISOString(),
            lastAccessedAt: $user->last_accessed_at?->toISOString(),
            lastAccessIp: $user->last_access_ip,
            createdAt: $user->created_at?->toISOString() ?? '',
            updatedAt: $user->updated_at?->toISOString() ?? '',
            deletedAt: $user->deleted_at?->toISOString(),
            notificationSettings: self::notificationSettings($user),
            consultationInfo: self::consultationInfo($consultationInfo),
            activityInfo: $activityInfo,
            reportedInfo: $reportedInfo,
            accessLogs: $accessLogs,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'signup_channel' => $this->signupChannel,
            'signup_channel_label' => $this->signupChannelLabel,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'warning_count' => $this->warningCount,
            'blocked_at' => $this->blockedAt,
            'withdrawal_reason' => $this->withdrawalReason,
            'email_verified_at' => $this->emailVerifiedAt,
            'last_login_at' => $this->lastLoginAt,
            'last_accessed_at' => $this->lastAccessedAt,
            'last_access_ip' => $this->lastAccessIp,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
            'notification_settings' => $this->notificationSettings,
            'consultation_info' => $this->consultationInfo,
            'activity_info' => $this->activityInfo,
            'reported_info' => $this->reportedInfo,
            'access_logs' => $this->accessLogs,
        ];

        return $data;
    }

    private static function notificationSettings(AccountUser $user): array
    {
        return [
            'comment_notification_enabled' => (bool) $user->comment_notification_enabled,
            'note_notification_enabled' => (bool) $user->note_notification_enabled,
            'marketing_sms_agreed' => (bool) $user->marketing_sms_agreed,
            'marketing_email_agreed' => (bool) $user->marketing_email_agreed,
            'marketing_push_agreed' => (bool) $user->marketing_push_agreed,
            'marketing_night_push_agreed' => (bool) $user->marketing_night_push_agreed,
        ];
    }

    private static function consultationInfo(array $counts = []): array
    {
        return [
            'event_dbs' => (int) ($counts['event_dbs'] ?? 0),
            'remote_consultations' => (int) ($counts['remote_consultations'] ?? 0),
            'real_model_dbs' => (int) ($counts['real_model_dbs'] ?? 0),
        ];
    }
}

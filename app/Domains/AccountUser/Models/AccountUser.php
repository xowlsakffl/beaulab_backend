<?php

namespace App\Domains\AccountUser\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use Database\Factories\AccountUserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * AccountUser 역할 정의.
 * 일반 회원 계정 도메인
 */
final class AccountUser extends Authenticatable
{
    use HasAdminNotes, HasApiTokens, HasAuditLogs, HasFactory, HasOperationHistories, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'user';

    protected $table = 'account_users';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_BLOCKED = 'BLOCKED';

    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    public const int WARNING_BLOCK_THRESHOLD = 10;

    public const SIGNUP_CHANNEL_EMAIL = 'EMAIL';

    public const SIGNUP_CHANNEL_KAKAO = 'KAKAO';

    public const SIGNUP_CHANNEL_NAVER = 'NAVER';

    public const SIGNUP_CHANNEL_APPLE = 'APPLE';

    public const SIGNUP_CHANNEL_FACEBOOK = 'FACEBOOK';

    public const SIGNUP_CHANNEL_EMAIL_NO_CONTACT = 'EMAIL_NO_CONTACT';

    public const SIGNUP_CHANNEL_UNKNOWN = 'UNKNOWN';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'warning_count' => 0,
        'signup_channel' => self::SIGNUP_CHANNEL_EMAIL,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'phone',
        'signup_channel',
        'password',
        'status',
        'warning_count',
        'blocked_at',
        'withdrawal_reason',
        'comment_notification_enabled',
        'note_notification_enabled',
        'marketing_sms_agreed',
        'marketing_email_agreed',
        'marketing_push_agreed',
        'marketing_night_push_agreed',
        'email_verified_at',
        'last_login_at',
        'last_accessed_at',
        'last_access_ip',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'warning_count' => 'integer',
            'blocked_at' => 'datetime',
            'comment_notification_enabled' => 'boolean',
            'note_notification_enabled' => 'boolean',
            'marketing_sms_agreed' => 'boolean',
            'marketing_email_agreed' => 'boolean',
            'marketing_push_agreed' => 'boolean',
            'marketing_night_push_agreed' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_BLOCKED,
            self::STATUS_WITHDRAWN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => '정상',
            self::STATUS_SUSPENDED => '정지',
            self::STATUS_BLOCKED => '차단',
            self::STATUS_WITHDRAWN => '탈퇴',
        ];
    }

    /**
     * @return list<string>
     */
    public static function signupChannels(): array
    {
        return array_keys(self::signupChannelLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function signupChannelLabels(): array
    {
        return [
            self::SIGNUP_CHANNEL_KAKAO => '카카오톡',
            self::SIGNUP_CHANNEL_NAVER => '네이버',
            self::SIGNUP_CHANNEL_EMAIL => '이메일',
            self::SIGNUP_CHANNEL_APPLE => '애플',
            self::SIGNUP_CHANNEL_FACEBOOK => '페이스북',
            self::SIGNUP_CHANNEL_EMAIL_NO_CONTACT => '이메일(연락처 x)',
            self::SIGNUP_CHANNEL_UNKNOWN => '미확인',
        ];
    }

    protected static function newFactory(): Factory
    {
        return AccountUserFactory::new();
    }

    public function blockedUserRelations(): HasMany
    {
        return $this->hasMany(AccountUserBlock::class, 'blocker_user_id');
    }

    public function blockerRelations(): HasMany
    {
        return $this->hasMany(AccountUserBlock::class, 'blocked_user_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccountUserAccessLog::class, 'account_user_id');
    }

    public function hospitalEventConsultations(): HasMany
    {
        return $this->hasMany(HospitalEventConsultation::class, 'account_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }
}

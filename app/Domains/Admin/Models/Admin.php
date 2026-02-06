<?php

namespace App\Domains\Admin\Models;

use App\Common\Notifications\QueuedResetPasswordNotification;
use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

final class Admin extends Authenticatable
{
    use HasRoles, HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, LogsActivity;

    protected string $guard_name = 'admin';

    /**
     * 계정 상태 상수 (migration comment: active, suspended, blocked)
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BLOCKED = 'BLOCKED';

    /**
     * 기본값 (DB default가 있어도 도메인 기본값은 명시 권장)
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * Mass assignable
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'status',
        'email_verified_at',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'active_membership_id',
    ];

    /**
     * Hidden for serialization
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Casts
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'active_membership_id' => 'int',
        ];
    }

    protected static function newFactory(): Factory
    {
        return AdminFactory::new();
    }

    /**
     * Force password reset email to be queued (Horizon/Redis).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPasswordNotification($token));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * 한 아이디 = 한 소속(멤버십 1개) 고정
     * 현재 보고 있는 병원
     * 접근 가능한 데이터 범위
     * 메뉴 노출
     * 사용 예
     * $membership = $admin->activeMembership;
     * $hospital   = $membership?->hospital;
     */
    public function activeMembership(): BelongsTo
    {
        return $this->belongsTo(AdminMembership::class, 'active_membership_id');
    }

    public function membership(): ?AdminMembership
    {
        return $this->activeMembership;
    }

    // 소속 아이디 가져오기
    public function membershipTargetId(): ?int
    {
        return $this->activeMembership?->target_id;
    }

    // 소속 권한 가져오기
    public function membershipRole(): ?string
    {
        return $this->activeMembership?->role;
    }

    /**
     * 상태 헬퍼
     */
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
}

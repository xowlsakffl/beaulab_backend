<?php

namespace App\Domains\Partner\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Common\Notifications\QueuedResetPasswordNotification;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\AccountPartnerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class AccountPartner extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, HasAuditLogs;

    protected string $guard_name = 'partner';

    protected $table = 'account_partners';
    /**
     * 계정 상태 상수 (migration comment: active, suspended, blocked)
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BLOCKED = 'BLOCKED';

    /**
     * 계정 타입 (HOSTPITAL | BEAUTY)
     **/
    public const PARTNER_HOSPITAL = 'HOSPITAL';
    public const PARTNER_BEAUTY = 'BEAUTY';

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
        'partner_type',
        'hospital_id',
        'beauty_id'
    ];

    /**
     * Hidden for serialization
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'last_login_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return AccountPartnerFactory::new();
    }

    /**
     * Force password reset email to be queued (Horizon/Redis).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPasswordNotification($token));
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function beauty(): BelongsTo
    {
        return $this->belongsTo(Beauty::class, 'beauty_id');
    }

    public function isHospital(): bool {
        return $this->partner_type === self::PARTNER_HOSPITAL;
    }

    public function isBeauty(): bool {
        return $this->partner_type === self::PARTNER_BEAUTY;
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

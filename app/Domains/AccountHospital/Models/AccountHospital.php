<?php

namespace App\Domains\AccountHospital\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\AccountHospitalFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * AccountHospital 역할 정의.
 * 병원 계정 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
class AccountHospital extends Authenticatable
{
    use HasApiTokens, HasAuditLogs, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected string $guard_name = 'hospital';

    protected $table = 'account_hospitals';

    /**
     * 계정 상태 상수 (migration comment: active, suspended, blocked, withdrawn)
     */
    public const STATUS_ACTIVE = 'ACTIVE'; // 활성

    public const STATUS_SUSPENDED = 'SUSPENDED'; // 정지

    public const STATUS_BLOCKED = 'BLOCKED'; // 차단

    public const STATUS_WITHDRAWN = 'WITHDRAWN'; // 탈퇴

    /**
     * 기본값 (DB default가 있어도 도메인 기본값은 명시 권장)
     */
    protected $attributes = [
        'status' => self::STATUS_SUSPENDED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'phone',
        'password',
        'status',
        'hospital_id',
        'phone_verified_at',
        'last_login_at',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return AccountHospitalFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function completedInvitations(): HasMany
    {
        return $this->hasMany(HospitalAccountInvitation::class, 'completed_account_hospital_id');
    }

    public function verifiedPhone(): ?string
    {
        if ($this->phone_verified_at === null) {
            return null;
        }

        $phone = trim((string) $this->phone);

        return $phone !== '' ? $phone : null;
    }

    /**
     * 운영 상태 헬퍼
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

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
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
}

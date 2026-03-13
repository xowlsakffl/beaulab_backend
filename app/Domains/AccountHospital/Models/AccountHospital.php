<?php

namespace App\Domains\AccountHospital\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\AccountHospitalFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class AccountHospital extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, HasAuditLogs;

    protected string $guard_name = 'hospital';

    protected $table = 'account_hospitals';
    /**
     * 계정 상태 상수 (migration comment: active, suspended, blocked)
     */
    public const STATUS_ACTIVE = 'ACTIVE'; // 활성
    public const STATUS_SUSPENDED = 'SUSPENDED'; // 정지
    public const STATUS_BLOCKED = 'BLOCKED'; // 차단

    /**
     * 기본값 (DB default가 있어도 도메인 기본값은 명시 권장)
     */
    protected $attributes = [
        'status' => self::STATUS_SUSPENDED,
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
        'hospital_id',
        'email_verified_at',
        'last_login_at',
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
        return AccountHospitalFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
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

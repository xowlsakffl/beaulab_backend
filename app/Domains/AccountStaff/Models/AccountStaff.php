<?php

namespace App\Domains\AccountStaff\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Database\Factories\AccountStaffFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * AccountStaff 역할 정의.
 * 스태프 계정 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class AccountStaff extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, HasAuditLogs;

    protected string $guard_name = 'staff';

    protected $table = 'account_staffs';

    public const string STATUS_ACTIVE = 'ACTIVE';
    public const string STATUS_SUSPENDED = 'SUSPENDED';
    public const string STATUS_BLOCKED = 'BLOCKED';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'status',
        'email_verified_at',
        'department',
        'job_title',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

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
        return AccountStaffFactory::new();
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
}

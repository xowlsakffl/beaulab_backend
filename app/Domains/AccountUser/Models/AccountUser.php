<?php

namespace App\Domains\AccountUser\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Database\Factories\AccountUserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * AccountUser 역할 정의.
 * 일반 회원 계정 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class AccountUser extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, HasAuditLogs;

    protected string $guard_name = 'user';
    protected $table = 'account_users';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BLOCKED = 'BLOCKED';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
        'last_login_at',
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
        ];
    }

    protected static function newFactory(): Factory
    {
        return AccountUserFactory::new();
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

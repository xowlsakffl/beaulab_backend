<?php

namespace App\Domains\Staff\Models;

use App\Common\Notifications\QueuedResetPasswordNotification;
use Database\Factories\AccountStaffFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

final class AccountStaff extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, SoftDeletes, LogsActivity;

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

<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HospitalAccountInvitation extends Model
{
    public const SOURCE_HOSPITAL = 'HOSPITAL';

    public const SOURCE_HOSPITAL_ENTRY = 'HOSPITAL_ENTRY';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_USED = 'USED';

    public const STATUS_REVOKED = 'REVOKED';

    public const STATUS_EXPIRED = 'EXPIRED';

    protected $table = 'hospital_account_invitations';

    protected $fillable = [
        'source_type',
        'hospital_id',
        'hospital_entry_id',
        'recipient_email',
        'token_hash',
        'expires_at',
        'sent_at',
        'used_at',
        'revoked_at',
        'created_by_staff_id',
        'completed_account_hospital_id',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public static function sourceTypes(): array
    {
        return [
            self::SOURCE_HOSPITAL,
            self::SOURCE_HOSPITAL_ENTRY,
        ];
    }

    public function isActive(): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && $this->expires_at?->isFuture() === true;
    }

    public function status(): string
    {
        return match (true) {
            $this->used_at !== null => self::STATUS_USED,
            $this->revoked_at !== null => self::STATUS_REVOKED,
            $this->expires_at?->isPast() === true => self::STATUS_EXPIRED,
            default => self::STATUS_ACTIVE,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => '사용 가능',
            self::STATUS_USED => '사용 완료',
            self::STATUS_REVOKED => '폐기',
            self::STATUS_EXPIRED => '만료',
            default => '-',
        };
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function hospitalEntry(): BelongsTo
    {
        return $this->belongsTo(HospitalEntry::class, 'hospital_entry_id');
    }

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }

    public function completedAccountHospital(): BelongsTo
    {
        return $this->belongsTo(AccountHospital::class, 'completed_account_hospital_id');
    }

    public function phoneVerifications(): HasMany
    {
        return $this->hasMany(HospitalAccountPhoneVerification::class, 'hospital_account_invitation_id');
    }
}

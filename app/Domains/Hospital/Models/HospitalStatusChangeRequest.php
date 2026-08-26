<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use Database\Factories\HospitalStatusChangeRequestFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalStatusChangeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'hospital_status_change_requests';

    protected $fillable = [
        'hospital_id',
        'previous_status',
        'target_status',
        'status',
        'reason',
        'decision_reason',
        'requested_by_staff_id',
        'processed_by_staff_id',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalStatusChangeRequestFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'requested_by_staff_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'processed_by_staff_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** @return list<string> */
    public static function decisions(): array
    {
        return [self::STATUS_APPROVED, self::STATUS_REJECTED];
    }
}

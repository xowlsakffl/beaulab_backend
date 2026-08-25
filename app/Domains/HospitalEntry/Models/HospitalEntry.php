<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Models;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\HospitalEntryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEntry extends Model
{
    use HasFactory, HasOperationHistories, SoftDeletes;

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_REVIEWING = 'REVIEWING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    protected $table = 'hospital_entries';

    protected $fillable = [
        'hospital_name',
        'hospital_phone',
        'address',
        'address_detail',
        'business_number',
        'ceo_name',
        'license_number',
        'applicant_name',
        'applicant_position',
        'applicant_phone',
        'applicant_email',
        'allow_status',
        'hospital_id',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return HospitalEntryFactory::new();
    }

    /**
     * @return array<int, string>
     */
    public static function allowStatuses(): array
    {
        return [
            self::ALLOW_PENDING,
            self::ALLOW_REVIEWING,
            self::ALLOW_APPROVED,
            self::ALLOW_REJECTED,
        ];
    }

    public static function allowStatusLabel(?string $value): string
    {
        return match ($value) {
            self::ALLOW_PENDING => '신청',
            self::ALLOW_REVIEWING => '검수',
            self::ALLOW_APPROVED => '승인',
            self::ALLOW_REJECTED => '반려',
            default => '-',
        };
    }

    public function businessRegistrationFile(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'hospital_entry_business_registration_file');
    }

    public function licenseFile(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'hospital_entry_license_file');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function accountInvitations(): HasMany
    {
        return $this->hasMany(HospitalAccountInvitation::class, 'hospital_entry_id');
    }
}

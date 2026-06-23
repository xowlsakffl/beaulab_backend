<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Models;

use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Database\Factories\HospitalEntryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEntry extends Model
{
    use HasFactory, HasOperationHistories, SoftDeletes;

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    public const ALLOW_STATUS_LABELS = [
        self::ALLOW_PENDING => '입점신청',
        self::ALLOW_APPROVED => '입점승인',
        self::ALLOW_REJECTED => '입점반려',
    ];

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
    ];

    protected static function newFactory(): Factory
    {
        return HospitalEntryFactory::new();
    }

    /**
     * @return array<int, string>
     */
    public static function allowStatuses(): array
    {
        return array_keys(self::ALLOW_STATUS_LABELS);
    }

    public static function allowStatusLabel(?string $value): string
    {
        return self::ALLOW_STATUS_LABELS[$value ?? ''] ?? '-';
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
}

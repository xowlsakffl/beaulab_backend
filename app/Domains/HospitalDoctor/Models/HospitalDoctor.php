<?php

declare(strict_types=1);

namespace App\Domains\HospitalDoctor\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalReview\Models\HospitalReview;
use Database\Factories\HospitalDoctorFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HospitalDoctor 역할 정의.
 * 병원 의사 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class HospitalDoctor extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const GENDER_MALE = '남';

    public const GENDER_FEMALE = '여';

    public const POSITION_HEAD_DIRECTOR = '대표원장';

    public const POSITION_DIRECTOR = '원장';

    public const SPECIALIST_FIELD_NONE = 'NONE';

    public const SPECIALIST_FIELD_PLASTIC_SURGERY = 'PLASTIC_SURGERY';

    public const SPECIALIST_FIELD_SURGERY = 'SURGERY';

    public const SPECIALIST_FIELD_OTOLARYNGOLOGY = 'OTOLARYNGOLOGY';

    public const SPECIALIST_FIELD_FAMILY_MEDICINE = 'FAMILY_MEDICINE';

    public const SPECIALIST_FIELD_OBSTETRICS_GYNECOLOGY = 'OBSTETRICS_GYNECOLOGY';

    public const SPECIALIST_FIELD_ORAL_MAXILLOFACIAL_SURGERY = 'ORAL_MAXILLOFACIAL_SURGERY';

    public const SPECIALIST_FIELD_ANESTHESIOLOGY_PAIN_MEDICINE = 'ANESTHESIOLOGY_PAIN_MEDICINE';

    public const SPECIALIST_FIELD_KOREAN_MEDICINE = 'KOREAN_MEDICINE';

    public const SPECIALIST_FIELD_DENTISTRY = 'DENTISTRY';

    public const SPECIALIST_FIELD_ORTHODONTICS = 'ORTHODONTICS';

    public const SPECIALIST_FIELD_DERMATOLOGY = 'DERMATOLOGY';

    public const SPECIALIST_FIELD_OPHTHALMOLOGY = 'OPHTHALMOLOGY';

    public const SPECIALIST_FIELD_INTERNAL_MEDICINE = 'INTERNAL_MEDICINE';

    public const SPECIALIST_FIELD_NEUROLOGY = 'NEUROLOGY';

    public const SPECIALIST_FIELD_ORTHOPEDICS = 'ORTHOPEDICS';

    public const SPECIALIST_FIELD_NEUROSURGERY = 'NEUROSURGERY';

    public const SPECIALIST_FIELD_THORACIC_SURGERY = 'THORACIC_SURGERY';

    public const SPECIALIST_FIELD_PEDIATRICS = 'PEDIATRICS';

    public const SPECIALIST_FIELD_UROLOGY = 'UROLOGY';

    public const SPECIALIST_FIELD_RADIOLOGY = 'RADIOLOGY';

    public const SPECIALIST_FIELD_EMERGENCY_MEDICINE = 'EMERGENCY_MEDICINE';

    public const SPECIALIST_FIELD_REHABILITATION_MEDICINE = 'REHABILITATION_MEDICINE';

    public const SPECIALIST_FIELD_PROSTHODONTICS = 'PROSTHODONTICS';

    public const SPECIALIST_FIELD_PERIODONTICS = 'PERIODONTICS';

    public const SPECIALIST_FIELD_INTEGRATED_DENTISTRY = 'INTEGRATED_DENTISTRY';

    public const SPECIALIST_FIELD_PATHOLOGY = 'PATHOLOGY';

    public const SPECIALIST_FIELD_OCCUPATIONAL_ENVIRONMENTAL_MEDICINE = 'OCCUPATIONAL_ENVIRONMENTAL_MEDICINE';

    public const SPECIALIST_FIELD_CONSERVATIVE_DENTISTRY = 'CONSERVATIVE_DENTISTRY';

    public const SPECIALIST_FIELD_OTHER = 'OTHER';

    public const SPECIALIST_FIELD_LABELS = [
        self::SPECIALIST_FIELD_NONE => '선택안함',
        self::SPECIALIST_FIELD_PLASTIC_SURGERY => '성형외과',
        self::SPECIALIST_FIELD_SURGERY => '외과',
        self::SPECIALIST_FIELD_OTOLARYNGOLOGY => '이비인후과',
        self::SPECIALIST_FIELD_FAMILY_MEDICINE => '가정의학과',
        self::SPECIALIST_FIELD_OBSTETRICS_GYNECOLOGY => '산부인과',
        self::SPECIALIST_FIELD_ORAL_MAXILLOFACIAL_SURGERY => '구강악안면외과',
        self::SPECIALIST_FIELD_ANESTHESIOLOGY_PAIN_MEDICINE => '마취통증의학과',
        self::SPECIALIST_FIELD_KOREAN_MEDICINE => '한의학과',
        self::SPECIALIST_FIELD_DENTISTRY => '치과',
        self::SPECIALIST_FIELD_ORTHODONTICS => '치과교정과',
        self::SPECIALIST_FIELD_DERMATOLOGY => '피부과',
        self::SPECIALIST_FIELD_OPHTHALMOLOGY => '안과',
        self::SPECIALIST_FIELD_INTERNAL_MEDICINE => '내과',
        self::SPECIALIST_FIELD_NEUROLOGY => '신경과',
        self::SPECIALIST_FIELD_ORTHOPEDICS => '정형외과',
        self::SPECIALIST_FIELD_NEUROSURGERY => '신경외과',
        self::SPECIALIST_FIELD_THORACIC_SURGERY => '흉부외과',
        self::SPECIALIST_FIELD_PEDIATRICS => '소아청소년과',
        self::SPECIALIST_FIELD_UROLOGY => '비뇨의학과',
        self::SPECIALIST_FIELD_RADIOLOGY => '영상의학과',
        self::SPECIALIST_FIELD_EMERGENCY_MEDICINE => '응급의학과',
        self::SPECIALIST_FIELD_REHABILITATION_MEDICINE => '재활의학과',
        self::SPECIALIST_FIELD_PROSTHODONTICS => '치과보철과',
        self::SPECIALIST_FIELD_PERIODONTICS => '치주과',
        self::SPECIALIST_FIELD_INTEGRATED_DENTISTRY => '통합치의학과',
        self::SPECIALIST_FIELD_PATHOLOGY => '병리과',
        self::SPECIALIST_FIELD_OCCUPATIONAL_ENVIRONMENTAL_MEDICINE => '직업환경의학과',
        self::SPECIALIST_FIELD_CONSERVATIVE_DENTISTRY => '치과보존과',
        self::SPECIALIST_FIELD_OTHER => '기타',
    ];

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    // status
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_doctors';

    protected $fillable = [
        'hospital_id',
        'sort_order',
        'name',
        'gender',
        'position',
        'career_started_at',
        'license_number',
        'specialist_field',
        'educations',
        'careers',
        'etc_contents',
        'status',
        'allow_status',
        'view_count',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'educations' => 'array',
        'careers' => 'array',
        'etc_contents' => 'array',
        'career_started_at' => 'date',
    ];

    protected $attributes = [
        'view_count' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalDoctorFactory::new();
    }

    public static function normalizeGender(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = mb_strtoupper(trim($value), 'UTF-8');

        return match ($normalized) {
            'M', 'MALE', 'MAN', self::GENDER_MALE => self::GENDER_MALE,
            'F', 'FEMALE', 'WOMAN', self::GENDER_FEMALE => self::GENDER_FEMALE,
            default => trim($value),
        };
    }

    public static function normalizePosition(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return trim($value);
    }

    public static function positions(): array
    {
        return [
            self::POSITION_HEAD_DIRECTOR,
            self::POSITION_DIRECTOR,
        ];
    }

    public static function specialistFields(): array
    {
        return array_keys(self::SPECIALIST_FIELD_LABELS);
    }

    public static function specialistFieldLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return self::SPECIALIST_FIELD_LABELS[self::SPECIALIST_FIELD_NONE];
        }

        return self::SPECIALIST_FIELD_LABELS[$value] ?? $value;
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(HospitalReview::class, 'doctor_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(HospitalEvent::class, 'hospital_event_doctor_assignments', 'hospital_doctor_id', 'hospital_event_id')
            ->withPivot(['sort_order', 'is_career_visible', 'is_activity_visible'])
            ->withTimestamps();
    }

    public function eventDBs(): HasMany
    {
        return $this->hasMany(HospitalEventDB::class, 'hospital_doctor_id');
    }

    public function profileImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'profile_image');
    }

    public function licenseImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'license_image');
    }

    public function specialistCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'specialist_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function isApproved(): bool
    {
        return $this->allow_status === self::ALLOW_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->allow_status === self::ALLOW_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->allow_status === self::ALLOW_REJECTED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}

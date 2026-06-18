<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use App\Domains\HospitalReview\Models\HospitalReview;
use Database\Factories\HospitalFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hospital 역할 정의.
 * 병원 도메인의 Eloquent 모델
 */
final class Hospital extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    // allow_status
    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    // status
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    // department
    public const DEPARTMENT_PLASTIC_SURGERY = 'PLASTIC_SURGERY';

    public const DEPARTMENT_DERMATOLOGY = 'DERMATOLOGY';

    public const DEPARTMENT_CLINIC = 'CLINIC';

    public const DEPARTMENT_DENTISTRY = 'DENTISTRY';

    public const DEPARTMENT_OPHTHALMOLOGY = 'OPHTHALMOLOGY';

    public const DEPARTMENT_KOREAN_MEDICINE = 'KOREAN_MEDICINE';

    public const DEPARTMENT_OTHER = 'OTHER';

    protected $table = 'hospitals';

    protected $fillable = [
        'name',
        'department',
        'description',
        'address',
        'address_detail',
        'latitude',
        'longitude',
        'tel',
        'ad_reception_phone_1',
        'ad_reception_phone_2',
        'ad_reception_phone_3',
        'email',
        'consulting_hours',
        'operation_hours',
        'direction',
        'view_count',
        'evaluation_count',
        'evaluation_average_rating',
        'allow_status',
        'status',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'evaluation_count' => 'integer',
        'evaluation_average_rating' => 'float',
        'operation_hours' => 'array',
    ];

    protected $attributes = [
        'department' => self::DEPARTMENT_OTHER,
        'view_count' => 0,
        'evaluation_count' => 0,
        'evaluation_average_rating' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalFactory::new();
    }

    public function accountHospital(): HasOne
    {
        return $this->hasOne(AccountHospital::class, 'hospital_id');
    }

    public function hospitalReviews(): HasMany
    {
        return $this->hasMany(HospitalReview::class, 'hospital_id');
    }

    public function hospitalEvaluations(): HasMany
    {
        return $this->hasMany(HospitalEvaluation::class, 'hospital_id');
    }

    public function hospitalEvents(): HasMany
    {
        return $this->hasMany(HospitalEvent::class, 'hospital_id');
    }

    public function hospitalEventDBs(): HasMany
    {
        return $this->hasMany(HospitalEventDB::class, 'hospital_id');
    }

    public function hospitalEventRealModelDBs(): HasMany
    {
        return $this->hasMany(HospitalEventRealModelDB::class, 'hospital_id');
    }

    public function logoMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'logo');
    }

    public function galleryMedia(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'gallery')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(HospitalDoctor::class, 'hospital_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function businessRegistration(): HasOne
    {
        return $this->hasOne(HospitalBusinessRegistration::class, 'hospital_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(HospitalFeature::class, 'hospital_feature_assignments', 'hospital_id', 'hospital_feature_id')
            ->withTimestamps()
            ->orderBy('hospital_features.sort_order')
            ->orderBy('hospital_features.id');
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

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    /**
     * @return list<string>
     */
    public static function departments(): array
    {
        return [
            self::DEPARTMENT_PLASTIC_SURGERY,
            self::DEPARTMENT_DERMATOLOGY,
            self::DEPARTMENT_CLINIC,
            self::DEPARTMENT_DENTISTRY,
            self::DEPARTMENT_OPHTHALMOLOGY,
            self::DEPARTMENT_KOREAN_MEDICINE,
            self::DEPARTMENT_OTHER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function departmentLabels(): array
    {
        return [
            self::DEPARTMENT_PLASTIC_SURGERY => '성형외과',
            self::DEPARTMENT_DERMATOLOGY => '피부과',
            self::DEPARTMENT_CLINIC => '의원',
            self::DEPARTMENT_DENTISTRY => '치과',
            self::DEPARTMENT_OPHTHALMOLOGY => '안과',
            self::DEPARTMENT_KOREAN_MEDICINE => '한의원',
            self::DEPARTMENT_OTHER => '기타',
        ];
    }

    public function departmentLabel(): string
    {
        return self::departmentLabels()[(string) $this->department]
            ?? (string) $this->department;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Models;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Concerns\HasAdminNotes;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Database\Factories\HospitalFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hospital 역할 정의.
 * 병원 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Hospital extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogs, HasAdminNotes;

    // allow_status
    public const ALLOW_PENDING  = 'PENDING';
    public const ALLOW_APPROVED = 'APPROVED';
    public const ALLOW_REJECTED = 'REJECTED';

    // status
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    protected $table = 'hospitals';

    protected $fillable = [
        'name',
        'description',
        'address',
        'address_detail',
        'latitude',
        'longitude',
        'tel',
        'email',
        'consulting_hours',
        'direction',
        'view_count',
        'allow_status',
        'status'
    ];

    protected $casts = [
        'view_count' => 'integer',
    ];

    protected $attributes = [
        'view_count' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalFactory::new();
    }

    public function accountHospitals(): HasMany
    {
        return $this->hasMany(AccountHospital::class, 'hospital_id');
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
}

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Concerns\HasAdminNotes;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Database\Factories\BeautyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Beauty 역할 정의.
 * 뷰티 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Beauty extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogs, HasAdminNotes;

    // allow_status
    public const string ALLOW_PENDING  = 'PENDING';
    public const string ALLOW_APPROVED = 'APPROVED';
    public const string ALLOW_REJECTED = 'REJECTED';

    // status
    public const string STATUS_ACTIVE    = 'ACTIVE';
    public const string STATUS_SUSPENDED = 'SUSPENDED';
    public const string STATUS_WITHDRAWN = 'WITHDRAWN';

    protected $table = 'beauties';

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
        return BeautyFactory::new();
    }

    public function accountBeauties(): HasMany
    {
        return $this->hasMany(AccountBeauty::class, 'beauty_id');
    }

    public function experts(): HasMany
    {
        return $this->hasMany(BeautyExpert::class, 'beauty_id')
            ->orderBy('sort_order')
            ->orderBy('id');
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

    public function businessRegistration(): HasOne
    {
        return $this->hasOne(BeautyBusinessRegistration::class, 'beauty_id');
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

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }
}

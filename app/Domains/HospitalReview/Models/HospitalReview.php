<?php

namespace App\Domains\HospitalReview\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\HospitalReviewFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HospitalReview 역할 정의.
 * 병의원 후기 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class HospitalReview extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const CATEGORY_DOMAIN_SURGERY = Category::DOMAIN_HOSPITAL_REVIEW_SURGERY;

    public const CATEGORY_DOMAIN_TREATMENT = Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const MAX_BEFORE_IMAGE_COUNT = 4;

    public const MAX_AFTER_IMAGE_COUNT = 4;

    public const MAX_CATEGORY_COUNT = 10;

    protected $table = 'hospital_reviews';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'hospital_id',
        'doctor_id',
        'category_domain',
        'title',
        'content',
        'author_ip',
        'cost',
        'rating',
        'status',
        'is_main_featured',
        'is_sub_featured',
        'view_count',
        'comment_count',
        'like_count',
        'save_count',
    ];

    protected $casts = [
        'author_id' => 'integer',
        'hospital_id' => 'integer',
        'doctor_id' => 'integer',
        'cost' => 'integer',
        'rating' => 'integer',
        'is_main_featured' => 'boolean',
        'is_sub_featured' => 'boolean',
        'view_count' => 'integer',
        'comment_count' => 'integer',
        'like_count' => 'integer',
        'save_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'is_main_featured' => false,
        'is_sub_featured' => false,
        'view_count' => 0,
        'comment_count' => 0,
        'like_count' => 0,
        'save_count' => 0,
    ];

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function categoryDomains(): array
    {
        return [
            self::CATEGORY_DOMAIN_SURGERY,
            self::CATEGORY_DOMAIN_TREATMENT,
        ];
    }

    public function isStatusChangeLocked(): bool
    {
        return false;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'author_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'doctor_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HospitalReviewComment::class, 'hospital_review_id')
            ->orderBy('id');
    }

    public function beforeImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'before_images')
            ->ordered();
    }

    public function afterImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'after_images')
            ->ordered();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return HospitalReviewFactory::new();
    }
}

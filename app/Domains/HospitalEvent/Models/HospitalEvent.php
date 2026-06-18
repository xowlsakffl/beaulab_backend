<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\HospitalEventFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEvent extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const TYPE_TEXT = 'TEXT';

    public const TYPE_IMAGE = 'IMAGE';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_REVIEWING = 'REVIEWING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    public const ALLOW_PARTNER_CANCELED = 'PARTNER_CANCELED';

    public const COLLECTION_THUMBNAIL_IMAGE = 'thumbnail_image';

    public const COLLECTION_EVENT_PAGE_IMAGE = 'event_page_image';

    public const MAX_CATEGORY_COUNT = 3;

    public const MAX_DOCTOR_COUNT = 3;

    public const MAX_PROCEDURE_TARGET_ITEMS = 5;

    public const MAX_PROCEDURE_BENEFIT_ITEMS = 6;

    public const MIN_TEXT_ITEMS = 1;

    public const MAX_DISCOUNT_RATE = 49;

    protected $table = 'hospital_events';

    protected $fillable = [
        'hospital_id',
        'event_type',
        'is_male_targeted',
        'name',
        'description',
        'is_event_period_unlimited',
        'event_start_at',
        'event_end_at',
        'normal_price',
        'event_price',
        'is_vat_included',
        'discount_rate',
        'base_consultation_price',
        'consultation_price',
        'has_options',
        'procedure_targets',
        'procedure_benefits',
        'side_effect_notice',
        'allow_status',
        'status',
        'view_count',
    ];

    protected $casts = [
        'is_event_period_unlimited' => 'boolean',
        'is_male_targeted' => 'boolean',
        'event_start_at' => 'datetime',
        'event_end_at' => 'datetime',
        'normal_price' => 'integer',
        'event_price' => 'integer',
        'is_vat_included' => 'boolean',
        'discount_rate' => 'integer',
        'base_consultation_price' => 'integer',
        'consultation_price' => 'integer',
        'has_options' => 'boolean',
        'procedure_targets' => 'array',
        'procedure_benefits' => 'array',
        'view_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'event_type' => self::TYPE_IMAGE,
        'is_male_targeted' => false,
        'is_event_period_unlimited' => true,
        'normal_price' => 0,
        'event_price' => 0,
        'is_vat_included' => true,
        'discount_rate' => 0,
        'base_consultation_price' => 0,
        'consultation_price' => 0,
        'has_options' => false,
        'allow_status' => self::ALLOW_PENDING,
        'status' => self::STATUS_INACTIVE,
        'view_count' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalEventFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctorAssignments(): HasMany
    {
        return $this->hasMany(HospitalEventDoctorAssignment::class, 'hospital_event_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(HospitalDoctor::class, 'hospital_event_doctor_assignments', 'hospital_event_id', 'hospital_doctor_id')
            ->withPivot(['sort_order', 'is_career_visible', 'is_activity_visible'])
            ->withTimestamps()
            ->orderBy('hospital_event_doctor_assignments.sort_order')
            ->orderBy('hospital_doctors.id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(HospitalEventOption::class, 'hospital_event_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function eventDBs(): HasMany
    {
        return $this->hasMany(HospitalEventDB::class, 'hospital_event_id');
    }

    public function realModelDBs(): HasMany
    {
        return $this->hasMany(HospitalEventRealModelDB::class, 'hospital_event_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function thumbnailImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', self::COLLECTION_THUMBNAIL_IMAGE);
    }

    public function eventPageImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', self::COLLECTION_EVENT_PAGE_IMAGE);
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::TYPE_TEXT, self::TYPE_IMAGE];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
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
            self::ALLOW_PARTNER_CANCELED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function staffManageableAllowStatuses(): array
    {
        return [
            self::ALLOW_PENDING,
            self::ALLOW_REVIEWING,
            self::ALLOW_APPROVED,
            self::ALLOW_REJECTED,
        ];
    }

    public static function calculateDiscountRate(int $normalPrice, int $eventPrice): int
    {
        if ($normalPrice <= 0) {
            return 0;
        }

        return (int) round((1 - ($eventPrice / $normalPrice)) * 100);
    }

    public static function exceedsMaxDiscountRate(int $normalPrice, int $eventPrice): bool
    {
        if ($normalPrice <= 0) {
            return false;
        }

        return $eventPrice * 100 < $normalPrice * (100 - self::MAX_DISCOUNT_RATE);
    }

    public static function consultationBasePrice(int $eventPrice): int
    {
        return match (true) {
            $eventPrice <= 50000 => 10000,
            $eventPrice <= 100000 => 12500,
            $eventPrice <= 200000 => 15000,
            $eventPrice <= 300000 => 17500,
            $eventPrice <= 500000 => 20000,
            $eventPrice <= 800000 => 22500,
            $eventPrice <= 1000000 => 25000,
            $eventPrice <= 1500000 => 27500,
            $eventPrice <= 2500000 => 30000,
            $eventPrice <= 3000000 => 32500,
            $eventPrice <= 4000000 => 37500,
            default => 40000,
        };
    }
}

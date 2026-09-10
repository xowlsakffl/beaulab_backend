<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Carbon\CarbonInterface;
use Database\Factories\HospitalEventFactory;
use Illuminate\Database\Eloquent\Builder;
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

final class HospitalEvent extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const TYPE_TEXT = 'TEXT';

    public const TYPE_IMAGE = 'IMAGE';

    public const HOSPITAL_STATUS_PUBLIC = 'PUBLIC';

    public const HOSPITAL_STATUS_PRIVATE = 'PRIVATE';

    public const ADMIN_STATUS_NORMAL = 'NORMAL';

    public const ADMIN_STATUS_FORCED_STOPPED = 'FORCED_STOPPED';

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_REVIEWING = 'REVIEWING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    public const COLLECTION_THUMBNAIL_IMAGE = 'thumbnail_image';

    public const COLLECTION_EVENT_PAGE_IMAGE = 'event_page_image';

    public const COLLECTION_BEFORE_PHOTO = 'before_photo';

    public const COLLECTION_AFTER_PHOTO = 'after_photo';

    public const MAX_BEFORE_AFTER_PHOTOS = 4;

    public const MAX_CATEGORY_COUNT = 3;

    public const MAX_DOCTOR_COUNT = 3;

    public const MAX_PROCEDURE_TARGET_ITEMS = 5;

    public const MAX_PROCEDURE_BENEFIT_ITEMS = 6;

    public const MIN_TEXT_ITEMS = 1;

    public const MAX_DISCOUNT_RATE = 49;

    protected $table = 'hospital_events';

    protected $fillable = [
        'hospital_id',
        'manager_staff_id',
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
        'hospital_status',
        'admin_status',
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
        'hospital_status' => self::HOSPITAL_STATUS_PUBLIC,
        'admin_status' => self::ADMIN_STATUS_NORMAL,
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

    public function managerStaff(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'manager_staff_id');
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

    public function beforeAfterPhotos(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->whereIn('collection', [self::COLLECTION_BEFORE_PHOTO, self::COLLECTION_AFTER_PHOTO])
            ->orderBy('sort_order')->orderBy('id');
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
    public static function hospitalStatuses(): array
    {
        return [self::HOSPITAL_STATUS_PUBLIC, self::HOSPITAL_STATUS_PRIVATE];
    }

    public static function hospitalStatusLabel(?string $status): string
    {
        return match ($status) {
            self::HOSPITAL_STATUS_PUBLIC => '공개',
            self::HOSPITAL_STATUS_PRIVATE => '비공개',
            default => $status ?: '-',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function adminStatuses(): array
    {
        return [self::ADMIN_STATUS_NORMAL, self::ADMIN_STATUS_FORCED_STOPPED];
    }

    public static function adminStatusLabel(?string $status): string
    {
        return match ($status) {
            self::ADMIN_STATUS_NORMAL => '정상',
            self::ADMIN_STATUS_FORCED_STOPPED => '강제중지',
            default => $status ?: '-',
        };
    }

    public function isApplicationOpen(): bool
    {
        $now = now();

        return $this->hospital_status === self::HOSPITAL_STATUS_PUBLIC
            && $this->admin_status === self::ADMIN_STATUS_NORMAL
            && $this->allow_status === self::ALLOW_APPROVED
            && $this->deleted_at === null
            && ($this->event_start_at === null || ! $this->event_start_at->greaterThan($now))
            && (
                (bool) $this->is_event_period_unlimited
                || $this->event_end_at === null
                || ! $this->event_end_at->copy()->endOfDay()->lessThan($now)
            );
    }

    public function scopeApplicationOpen(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('hospital_status', self::HOSPITAL_STATUS_PUBLIC)
            ->where('admin_status', self::ADMIN_STATUS_NORMAL)
            ->where('allow_status', self::ALLOW_APPROVED)
            ->where(function (Builder $startQuery) use ($at): void {
                $startQuery->whereNull('event_start_at')
                    ->orWhere('event_start_at', '<=', $at);
            })
            ->where(function (Builder $endQuery) use ($at): void {
                $endQuery->where('is_event_period_unlimited', true)
                    ->orWhereNull('event_end_at')
                    ->orWhere('event_end_at', '>=', $at->copy()->startOfDay());
            });
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

    public static function allowStatusLabel(?string $status): string
    {
        return match ($status) {
            self::ALLOW_PENDING => '신청',
            self::ALLOW_REVIEWING => '검수',
            self::ALLOW_APPROVED => '승인',
            self::ALLOW_REJECTED => '반려',
            default => $status ?: '-',
        };
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

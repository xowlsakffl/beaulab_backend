<?php

namespace App\Domains\HospitalEventAd\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Database\Factories\HospitalEventAdFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEventAd extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const PLACEMENT_MAIN_POPUP = 'MAIN_POPUP';

    public const PLACEMENT_MAIN_VERTICAL_BANNER = 'MAIN_VERTICAL_BANNER';

    public const PLACEMENT_MAIN_HORIZONTAL_BANNER = 'MAIN_HORIZONTAL_BANNER';

    public const PLACEMENT_SURGERY_TOP_BANNER = 'SURGERY_TOP_BANNER';

    public const PLACEMENT_SURGERY_HOT_EVENT = 'SURGERY_HOT_EVENT';

    public const PLACEMENT_SURGERY_CATEGORY_BANNER = 'SURGERY_CATEGORY_BANNER';

    public const PLACEMENT_PETIT_TOP_BANNER = 'PETIT_TOP_BANNER';

    public const PLACEMENT_PETIT_HOT_EVENT = 'PETIT_HOT_EVENT';

    public const PLACEMENT_PETIT_CATEGORY_BANNER = 'PETIT_CATEGORY_BANNER';

    public const PLACEMENT_CONSULT_MEMO = 'CONSULT_MEMO';

    public const PLACEMENT_SEARCH = 'SEARCH';

    public const ALLOW_PENDING = 'PENDING';

    public const ALLOW_REVIEWING = 'REVIEWING';

    public const ALLOW_APPROVED = 'APPROVED';

    public const ALLOW_REJECTED = 'REJECTED';

    public const AD_STATUS_SCHEDULED = 'SCHEDULED';

    public const AD_STATUS_RUNNING = 'RUNNING';

    public const AD_STATUS_ENDED = 'ENDED';

    public const COLLECTION_AD_IMAGE = 'ad_image';

    public const WEEKLY_SLOT_LIMIT = 3;

    protected $table = 'hospital_event_ads';

    protected $fillable = [
        'hospital_id',
        'hospital_event_id',
        'manager_staff_id',
        'placement',
        'cost',
        'start_at',
        'end_at',
        'allow_status',
    ];

    protected $casts = [
        'cost' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'allow_status' => self::ALLOW_PENDING,
        'cost' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalEventAdFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function hospitalEvent(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }

    public function managerStaff(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'manager_staff_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function adImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', self::COLLECTION_AD_IMAGE);
    }

    /**
     * @return array<int, string>
     */
    public static function placements(): array
    {
        return [
            self::PLACEMENT_MAIN_POPUP,
            self::PLACEMENT_MAIN_VERTICAL_BANNER,
            self::PLACEMENT_MAIN_HORIZONTAL_BANNER,
            self::PLACEMENT_SURGERY_TOP_BANNER,
            self::PLACEMENT_SURGERY_HOT_EVENT,
            self::PLACEMENT_SURGERY_CATEGORY_BANNER,
            self::PLACEMENT_PETIT_TOP_BANNER,
            self::PLACEMENT_PETIT_HOT_EVENT,
            self::PLACEMENT_PETIT_CATEGORY_BANNER,
            self::PLACEMENT_CONSULT_MEMO,
            self::PLACEMENT_SEARCH,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function categoryRequiredPlacements(): array
    {
        return [
            self::PLACEMENT_SURGERY_CATEGORY_BANNER,
            self::PLACEMENT_PETIT_CATEGORY_BANNER,
        ];
    }

    public static function requiresCategory(?string $placement): bool
    {
        return in_array($placement, self::categoryRequiredPlacements(), true);
    }

    public static function categoryUsageForPlacement(?string $placement): ?string
    {
        return match ($placement) {
            self::PLACEMENT_SURGERY_CATEGORY_BANNER => CategoryUsage::USAGE_HOSPITAL_EVENT_AD_SURGERY,
            self::PLACEMENT_PETIT_CATEGORY_BANNER => CategoryUsage::USAGE_HOSPITAL_EVENT_AD_TREATMENT,
            default => null,
        };
    }

    public static function placementLabel(?string $placement): string
    {
        return match ($placement) {
            self::PLACEMENT_MAIN_POPUP => '메인 팝업',
            self::PLACEMENT_MAIN_VERTICAL_BANNER => '메인 세로배너',
            self::PLACEMENT_MAIN_HORIZONTAL_BANNER => '메인 가로배너',
            self::PLACEMENT_SURGERY_TOP_BANNER => '성형 상단배너',
            self::PLACEMENT_SURGERY_HOT_EVENT => '성형 HOT이벤트',
            self::PLACEMENT_SURGERY_CATEGORY_BANNER => '성형 카테고리별 배너',
            self::PLACEMENT_PETIT_TOP_BANNER => '쁘띠 상단배너',
            self::PLACEMENT_PETIT_HOT_EVENT => '쁘띠 HOT이벤트',
            self::PLACEMENT_PETIT_CATEGORY_BANNER => '쁘띠 카테고리별 배너',
            self::PLACEMENT_CONSULT_MEMO => '상담메모장',
            self::PLACEMENT_SEARCH => '검색창',
            default => $placement ?: '-',
        };
    }

    public static function placementGroupLabel(?string $placement): string
    {
        return match ($placement) {
            self::PLACEMENT_MAIN_POPUP,
            self::PLACEMENT_MAIN_VERTICAL_BANNER,
            self::PLACEMENT_MAIN_HORIZONTAL_BANNER => '메인',
            self::PLACEMENT_SURGERY_TOP_BANNER,
            self::PLACEMENT_SURGERY_HOT_EVENT,
            self::PLACEMENT_SURGERY_CATEGORY_BANNER => '성형이벤트',
            self::PLACEMENT_PETIT_TOP_BANNER,
            self::PLACEMENT_PETIT_HOT_EVENT,
            self::PLACEMENT_PETIT_CATEGORY_BANNER => '쁘띠이벤트',
            self::PLACEMENT_CONSULT_MEMO,
            self::PLACEMENT_SEARCH => '기타',
            default => '-',
        };
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

    /**
     * @return array<int, string>
     */
    public static function adStatuses(): array
    {
        return [
            self::AD_STATUS_SCHEDULED,
            self::AD_STATUS_RUNNING,
            self::AD_STATUS_ENDED,
        ];
    }

    public function adStatus(?\DateTimeInterface $now = null): ?string
    {
        if ($this->allow_status !== self::ALLOW_APPROVED) {
            return null;
        }

        $now ??= now();

        if ($this->start_at !== null && $this->start_at->greaterThan($now)) {
            return self::AD_STATUS_SCHEDULED;
        }

        if ($this->end_at !== null && $this->end_at->lessThan($now)) {
            return self::AD_STATUS_ENDED;
        }

        return self::AD_STATUS_RUNNING;
    }

    public static function adStatusLabel(?string $status): string
    {
        return match ($status) {
            self::AD_STATUS_SCHEDULED => '광고예정',
            self::AD_STATUS_RUNNING => '광고중',
            self::AD_STATUS_ENDED => '광고종료',
            default => '-',
        };
    }
}

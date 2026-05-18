<?php

namespace App\Domains\HospitalEvaluation\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\HospitalEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEvaluation extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const CATEGORY_DOMAIN_SURGERY = Category::DOMAIN_HOSPITAL_EVALUATION_SURGERY;

    public const CATEGORY_DOMAIN_TREATMENT = Category::DOMAIN_HOSPITAL_EVALUATION_TREATMENT;

    public const CATEGORY_DOMAIN_CONSULTATION = Category::DOMAIN_HOSPITAL_EVALUATION_CONSULTATION;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const POST_STATUS_NORMAL = 'POST_NORMAL';

    public const POST_STATUS_AUTO_BLIND = 'POST_AUTO_BLIND';

    public const POST_STATUS_USER_DELETE = 'POST_USER_DELETE';

    public const POST_STATUS_ADMIN_STOP = 'POST_ADMIN_STOP';

    public const RECEIPT_STATUS_NONE = 'NONE';

    public const RECEIPT_STATUS_UPLOADED = 'UPLOADED';

    public const RECEIPT_STATUS_VERIFIED = 'VERIFIED';

    public const RECEIPT_STATUS_REJECTED = 'REJECTED';

    public const RECEIPT_REJECTION_REASON_IMAGE_MISMATCH = 'IMAGE_MISMATCH';

    public const RECEIPT_REJECTION_REASON_BUSINESS_NAME_MISMATCH = 'BUSINESS_NAME_MISMATCH';

    public const RECEIPT_REJECTION_REASON_BUSINESS_NUMBER_MISMATCH = 'BUSINESS_NUMBER_MISMATCH';

    public const RECEIPT_REJECTION_REASON_TRANSACTION_DATE_MISMATCH = 'TRANSACTION_DATE_MISMATCH';

    public const RECEIPT_REJECTION_REASON_SURGERY_COST_MISMATCH = 'SURGERY_COST_MISMATCH';

    public const RECEIPT_REJECTION_REASON_OTHER = 'OTHER';

    public const MAX_CATEGORY_COUNT = 10;

    public const STATUS_CHANGE_LOCKED_POST_STATUSES = [
        self::POST_STATUS_AUTO_BLIND,
        self::POST_STATUS_USER_DELETE,
        self::POST_STATUS_ADMIN_STOP,
    ];

    protected $table = 'hospital_evaluations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'hospital_id',
        'doctor_id',
        'category_domain',
        'content',
        'phone',
        'author_ip',
        'cost',
        'rating_staff_kindness',
        'rating_surgery_satisfaction',
        'rating_facility',
        'rating_aftercare',
        'rating_cost',
        'has_overtreatment',
        'is_waiting_time_long',
        'has_doctor_consultation',
        'is_recommended',
        'status',
        'post_status',
        'view_count',
        'receipt_status',
        'receipt_rejection_reason',
        'receipt_rejection_reason_text',
    ];

    protected $casts = [
        'author_id' => 'integer',
        'hospital_id' => 'integer',
        'doctor_id' => 'integer',
        'cost' => 'integer',
        'rating_staff_kindness' => 'integer',
        'rating_surgery_satisfaction' => 'integer',
        'rating_facility' => 'integer',
        'rating_aftercare' => 'integer',
        'rating_cost' => 'integer',
        'has_overtreatment' => 'boolean',
        'is_waiting_time_long' => 'boolean',
        'has_doctor_consultation' => 'boolean',
        'is_recommended' => 'boolean',
        'view_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'cost' => 0,
        'status' => self::STATUS_ACTIVE,
        'post_status' => self::POST_STATUS_NORMAL,
        'view_count' => 0,
        'receipt_status' => self::RECEIPT_STATUS_NONE,
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
    public static function postStatuses(): array
    {
        return [
            self::POST_STATUS_NORMAL,
            self::POST_STATUS_AUTO_BLIND,
            self::POST_STATUS_USER_DELETE,
            self::POST_STATUS_ADMIN_STOP,
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
            self::CATEGORY_DOMAIN_CONSULTATION,
        ];
    }

    /**
     * @return list<string>
     */
    public static function receiptStatuses(): array
    {
        return [
            self::RECEIPT_STATUS_NONE,
            self::RECEIPT_STATUS_UPLOADED,
            self::RECEIPT_STATUS_VERIFIED,
            self::RECEIPT_STATUS_REJECTED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function receiptStatusLabels(): array
    {
        return [
            self::RECEIPT_STATUS_NONE => '없음',
            self::RECEIPT_STATUS_UPLOADED => '영수증',
            self::RECEIPT_STATUS_VERIFIED => '영수증 인증',
            self::RECEIPT_STATUS_REJECTED => '영수증 부적합',
        ];
    }

    /**
     * @return list<string>
     */
    public static function receiptRejectionReasons(): array
    {
        return [
            self::RECEIPT_REJECTION_REASON_IMAGE_MISMATCH,
            self::RECEIPT_REJECTION_REASON_BUSINESS_NAME_MISMATCH,
            self::RECEIPT_REJECTION_REASON_BUSINESS_NUMBER_MISMATCH,
            self::RECEIPT_REJECTION_REASON_TRANSACTION_DATE_MISMATCH,
            self::RECEIPT_REJECTION_REASON_SURGERY_COST_MISMATCH,
            self::RECEIPT_REJECTION_REASON_OTHER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function receiptRejectionReasonLabels(): array
    {
        return [
            self::RECEIPT_REJECTION_REASON_IMAGE_MISMATCH => '영수증 이미지 불일치',
            self::RECEIPT_REJECTION_REASON_BUSINESS_NAME_MISMATCH => '상호 불일치',
            self::RECEIPT_REJECTION_REASON_BUSINESS_NUMBER_MISMATCH => '사업자번호 불일치',
            self::RECEIPT_REJECTION_REASON_TRANSACTION_DATE_MISMATCH => '거래일시 불일치',
            self::RECEIPT_REJECTION_REASON_SURGERY_COST_MISMATCH => '수술금액 불일치',
            self::RECEIPT_REJECTION_REASON_OTHER => '기타',
        ];
    }

    public static function averageRatingExpression(): string
    {
        return '(rating_staff_kindness + rating_surgery_satisfaction + rating_facility + rating_aftercare + rating_cost) / 5';
    }

    public function averageRating(): float
    {
        return round((
            (int) $this->rating_staff_kindness
            + (int) $this->rating_surgery_satisfaction
            + (int) $this->rating_facility
            + (int) $this->rating_aftercare
            + (int) $this->rating_cost
        ) / 5, 1);
    }

    public function receiptStatusLabel(): string
    {
        return self::receiptStatusLabels()[(string) $this->receipt_status]
            ?? (string) $this->receipt_status;
    }

    public function receiptRejectionReasonLabel(): ?string
    {
        if ($this->receipt_rejection_reason === null) {
            return null;
        }

        return self::receiptRejectionReasonLabels()[(string) $this->receipt_rejection_reason]
            ?? (string) $this->receipt_rejection_reason;
    }

    public function isStatusChangeLocked(): bool
    {
        $state = $this->relationLoaded('contentReportState')
            ? $this->contentReportState
            : $this->contentReportState()->first(['id', 'target_type', 'target_id', 'report_status']);

        return $state instanceof ContentReportState
            && in_array((string) $state->report_status, [
                ContentReportState::STATUS_AUTO_BLOCKED,
                ContentReportState::STATUS_ADMIN_HIDDEN,
            ], true);
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

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'images')
            ->ordered();
    }

    public function receiptImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'receipt_images')
            ->ordered();
    }

    public function contentReportState(): MorphOne
    {
        return $this->morphOne(ContentReportState::class, 'target', 'target_type', 'target_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return HospitalEvaluationFactory::new();
    }
}

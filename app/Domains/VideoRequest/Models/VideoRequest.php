<?php

declare(strict_types=1);

namespace App\Domains\VideoRequest\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VideoRequest extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const REVIEW_STATUS_PENDING = 'PENDING';
    public const REVIEW_STATUS_IN_REVIEW = 'IN_REVIEW';
    public const REVIEW_STATUS_APPROVED = 'APPROVED';
    public const REVIEW_STATUS_REJECTED = 'REJECTED';
    public const REVIEW_STATUS_PARTNER_CANCELED = 'PARTNER_CANCELED';

    protected $table = 'video_requests';

    protected $fillable = [
        'hospital_id',
        'beauty_id',
        'doctor_id',
        'expert_id',
        'submitted_by_partner_id',
        'title',
        'description',
        'is_usage_consented',
        'duration_seconds',
        'requested_publish_start_at',
        'requested_publish_end_at',
        'is_publish_period_unlimited',
        'review_status',
        'reviewed_by_staff_id',
        'reviewed_at',
        'reject_reason',
        'reject_reason_detail',
    ];

    protected $casts = [
        'is_usage_consented' => 'boolean',
        'is_publish_period_unlimited' => 'boolean',
        'duration_seconds' => 'integer',
        'requested_publish_start_at' => 'datetime',
        'requested_publish_end_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];


    public function sourceVideo(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'source_video_file');
    }

    public function sourceThumbnail(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'source_thumbnail_file');
    }

    public function isPending(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_PENDING;
    }

    public function isInReview(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_IN_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_REJECTED;
    }

    public function isPartnerCanceled(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_PARTNER_CANCELED;
    }

    public function isAccessibleByPartner(AccountPartner $partner): bool
    {
        if ($partner->isHospital()) {
            return (int) $this->hospital_id > 0
                && (int) $this->hospital_id === (int) $partner->hospital_id;
        }

        if ($partner->isBeauty()) {
            return (int) $this->beauty_id > 0
                && (int) $this->beauty_id === (int) $partner->beauty_id;
        }

        return false;
    }
}

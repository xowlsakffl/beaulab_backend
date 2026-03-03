<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideoRequest\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalVideoRequest extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const REVIEW_STATUS_APPLYING = 'APPLYING'; // 신청중
    public const REVIEW_STATUS_IN_REVIEW = 'IN_REVIEW'; // 검토중
    public const REVIEW_STATUS_APPROVED = 'APPROVED'; // 승인
    public const REVIEW_STATUS_REJECTED = 'REJECTED'; // 거절
    public const REVIEW_STATUS_PARTNER_CANCELED = 'PARTNER_CANCELED'; // 파트너 취소

    protected $table = 'hospital_video_requests';

    protected $fillable = [
        'hospital_id',
        'doctor_id',
        'submitted_by_account_id',
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

    public function isApplying(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_APPLYING;
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
}

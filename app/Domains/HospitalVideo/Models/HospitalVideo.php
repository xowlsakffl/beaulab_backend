<?php

namespace App\Domains\HospitalVideo\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalVideo extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const DISTRIBUTION_CHANNEL_YOUTUBE = 'YOUTUBE';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_PRIVATE = 'PRIVATE';

    public const REVIEW_STATUS_APPLYING = 'APPLYING';
    public const REVIEW_STATUS_IN_REVIEW = 'IN_REVIEW';
    public const REVIEW_STATUS_APPROVED = 'APPROVED';
    public const REVIEW_STATUS_REJECTED = 'REJECTED';
    public const REVIEW_STATUS_PARTNER_CANCELED = 'PARTNER_CANCELED';

    protected $table = 'hospital_videos';

    protected $fillable = [
        'hospital_id',
        'doctor_id',
        'submitted_by_account_id',
        'title',
        'description',
        'is_usage_consented',
        'distribution_channel',
        'external_video_id',
        'external_video_url',
        'thumbnail_media_id',
        'duration_seconds',
        'status',
        'published_at',
        'view_count',
        'like_count',
        'publish_start_at',
        'publish_end_at',
        'is_publish_period_unlimited',
        'review_status',
        'reviewed_by_staff_id',
        'reviewed_at',
        'reject_reason',
        'reject_reason_detail',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'is_usage_consented' => 'boolean',
        'is_publish_period_unlimited' => 'boolean',
        'published_at' => 'datetime',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'distribution_channel' => self::DISTRIBUTION_CHANNEL_YOUTUBE,
        'duration_seconds' => 0,
        'status' => self::STATUS_ACTIVE,
        'view_count' => 0,
        'like_count' => 0,
        'is_usage_consented' => false,
        'is_publish_period_unlimited' => false,
        'review_status' => self::REVIEW_STATUS_APPROVED,
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'doctor_id');
    }

    public function thumbnailMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\VideoRequest\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
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
        'source_video_media_id',
        'source_thumbnail_media_id',
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
}

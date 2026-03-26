<?php

namespace App\Domains\HospitalVideo\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalVideo extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const DISTRIBUTION_CHANNEL_YOUTUBE_APP = 'YOUTUBE_APP';
    public const DISTRIBUTION_CHANNEL_APP = 'APP';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public const ALLOW_SUBMITTED = 'SUBMITTED';
    public const ALLOW_IN_REVIEW = 'IN_REVIEW';
    public const ALLOW_APPROVED = 'APPROVED';
    public const ALLOW_REJECTED = 'REJECTED';
    public const ALLOW_EXCLUDED = 'EXCLUDED';
    public const ALLOW_PARTNER_CANCELED = 'PARTNER_CANCELED';

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
        'duration_seconds',
        'status',
        'view_count',
        'like_count',
        'publish_start_at',
        'publish_end_at',
        'is_publish_period_unlimited',
        'allow_status',
        'allowed_by_staff_id',
        'allowed_at',
        'reject_reason',
        'reject_reason_detail',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'is_usage_consented' => 'boolean',
        'is_publish_period_unlimited' => 'boolean',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'allowed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'distribution_channel' => self::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
        'duration_seconds' => 0,
        'status' => self::STATUS_INACTIVE,
        'view_count' => 0,
        'like_count' => 0,
        'is_usage_consented' => false,
        'is_publish_period_unlimited' => false,
        'allow_status' => self::ALLOW_SUBMITTED,
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'doctor_id');
    }

    public function thumbnailMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'thumbnail_file');
    }

    public function videoFileMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'video_file');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}

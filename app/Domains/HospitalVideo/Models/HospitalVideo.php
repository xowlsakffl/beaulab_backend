<?php

namespace App\Domains\HospitalVideo\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\AdminNote\Concerns\HasAdminNotes;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\HospitalVideoFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalVideo extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const HOSPITAL_STATUS_PUBLIC = 'PUBLIC';

    public const HOSPITAL_STATUS_PRIVATE = 'PRIVATE';

    public const ADMIN_STATUS_NORMAL = 'NORMAL';

    public const ADMIN_STATUS_FORCED_STOPPED = 'FORCED_STOPPED';

    protected $table = 'hospital_videos';

    protected $fillable = [
        'hospital_id',
        'doctor_id',
        'manager_staff_id',
        'title',
        'description',
        'external_video_url',
        'hospital_status',
        'admin_status',
        'view_count',
        'like_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'like_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'hospital_status' => self::HOSPITAL_STATUS_PUBLIC,
        'admin_status' => self::ADMIN_STATUS_NORMAL,
        'view_count' => 0,
        'like_count' => 0,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalVideoFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'doctor_id');
    }

    public function managerStaff(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'manager_staff_id');
    }

    public function thumbnailMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'thumbnail_file');
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

    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'hashtaggable', 'hashtaggables', 'hashtaggable_id', 'hashtag_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('hashtaggables.sort_order')
            ->orderBy('hashtags.id');
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
            self::HOSPITAL_STATUS_PRIVATE => '미공개',
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

    public function isVisible(): bool
    {
        return $this->hospital_status === self::HOSPITAL_STATUS_PUBLIC
            && $this->admin_status === self::ADMIN_STATUS_NORMAL
            && $this->deleted_at === null;
    }
}

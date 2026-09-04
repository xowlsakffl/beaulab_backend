<?php

namespace App\Domains\Notice\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notice 역할 정의.
 * 공지사항 도메인의 Eloquent 모델로, 게시 대상과 공개여부, 상단공지를 관리한다.
 */
final class Notice extends Model
{
    use HasAuditLogs, HasOperationHistories, SoftDeletes;

    public const CHANNEL_ALL = 'ALL';

    public const CHANNEL_APP_WEB = 'APP_WEB';

    public const CHANNEL_HOSPITAL = 'HOSPITAL';

    public const CHANNEL_BEAUTY = 'BEAUTY';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'notices';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'channel',
        'title',
        'content',
        'status',
        'is_pinned',
        'view_count',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'view_count' => 'integer',
        'created_by_staff_id' => 'integer',
        'updated_by_staff_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'channel' => self::CHANNEL_ALL,
        'status' => self::STATUS_INACTIVE,
        'is_pinned' => false,
        'view_count' => 0,
    ];

    public function attachments(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'attachments')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function editorImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'editor_images')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'updated_by_staff_id');
    }

    public function scopeForAudience(Builder $query, string $channel): Builder
    {
        return $query->whereIn('channel', [self::CHANNEL_ALL, $channel]);
    }

    /**
     * @return array<int, string>
     */
    public static function channels(): array
    {
        return [
            self::CHANNEL_ALL,
            self::CHANNEL_APP_WEB,
            self::CHANNEL_HOSPITAL,
            self::CHANNEL_BEAUTY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }
}

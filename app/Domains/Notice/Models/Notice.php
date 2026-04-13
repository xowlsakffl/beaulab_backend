<?php

namespace App\Domains\Notice\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notice 역할 정의.
 * 공지사항 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Notice extends Model
{
    use SoftDeletes, HasAuditLogs;

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
        'is_publish_period_unlimited',
        'publish_start_at',
        'publish_end_at',
        'is_important',
        'view_count',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_publish_period_unlimited' => 'boolean',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'is_important' => 'boolean',
        'view_count' => 'integer',
        'created_by_staff_id' => 'integer',
        'updated_by_staff_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'channel' => self::CHANNEL_ALL,
        'status' => self::STATUS_ACTIVE,
        'is_pinned' => false,
        'is_publish_period_unlimited' => true,
        'is_important' => false,
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

    public function scopePublishedAt(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q) use ($at): void {
                $q->whereNull('publish_start_at')
                    ->orWhere('publish_start_at', '<=', $at);
            })
            ->where(function (Builder $q) use ($at): void {
                $q->where('is_publish_period_unlimited', true)
                    ->orWhereNull('publish_end_at')
                    ->orWhere('publish_end_at', '>=', $at);
            });
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

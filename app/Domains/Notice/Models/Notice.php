<?php

namespace App\Domains\Notice\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

final class Notice extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const CHANNEL_ALL = 'ALL';
    public const CHANNEL_APP_WEB = 'APP_WEB';
    public const CHANNEL_HOSPITAL = 'HOSPITAL';
    public const CHANNEL_BEAUTY = 'BEAUTY';

    public const EXPOSURE_HIDDEN = 'HIDDEN';
    public const EXPOSURE_SCHEDULED = 'SCHEDULED';
    public const EXPOSURE_PUBLISHED = 'PUBLISHED';
    public const EXPOSURE_EXPIRED = 'EXPIRED';

    protected $table = 'notices';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'channel',
        'title',
        'content',
        'is_visible',
        'is_pinned',
        'pinned_order',
        'is_publish_period_unlimited',
        'publish_start_at',
        'publish_end_at',
        'is_push_enabled',
        'push_sent_at',
        'is_important',
        'view_count',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_pinned' => 'boolean',
        'pinned_order' => 'integer',
        'is_publish_period_unlimited' => 'boolean',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'is_push_enabled' => 'boolean',
        'push_sent_at' => 'datetime',
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
        'is_visible' => true,
        'is_pinned' => false,
        'pinned_order' => 0,
        'is_publish_period_unlimited' => true,
        'is_push_enabled' => false,
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

    public function popupImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'popup_image');
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
            ->where('is_visible', true)
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

    public function exposureStatus(?CarbonInterface $at = null): string
    {
        $at ??= now();

        if (! (bool) $this->is_visible) {
            return self::EXPOSURE_HIDDEN;
        }

        $publishStartAt = $this->publish_start_at;
        if ($publishStartAt instanceof Carbon && $publishStartAt->gt($at)) {
            return self::EXPOSURE_SCHEDULED;
        }

        if (
            ! (bool) $this->is_publish_period_unlimited
            && $this->publish_end_at instanceof Carbon
            && $this->publish_end_at->lt($at)
        ) {
            return self::EXPOSURE_EXPIRED;
        }

        return self::EXPOSURE_PUBLISHED;
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
    public static function exposureStatuses(): array
    {
        return [
            self::EXPOSURE_HIDDEN,
            self::EXPOSURE_SCHEDULED,
            self::EXPOSURE_PUBLISHED,
            self::EXPOSURE_EXPIRED,
        ];
    }
}

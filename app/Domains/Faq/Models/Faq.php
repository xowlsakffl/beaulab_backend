<?php

namespace App\Domains\Faq\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Faq 역할 정의.
 * FAQ 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Faq extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const CHANNEL_ALL = 'ALL';
    public const CHANNEL_APP_WEB = 'APP_WEB';
    public const CHANNEL_HOSPITAL = 'HOSPITAL';
    public const CHANNEL_BEAUTY = 'BEAUTY';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'faqs';

    protected $fillable = [
        'channel',
        'question',
        'content',
        'status',
        'sort_order',
        'view_count',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
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
        'sort_order' => 0,
        'view_count' => 0,
    ];

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
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

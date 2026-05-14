<?php

namespace App\Domains\Talk\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Database\Factories\TalkFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Talk 역할 정의.
 * 토크 도메인의 Eloquent 모델
 */
final class Talk extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const CATEGORY_DOMAIN = Category::DOMAIN_TALK;

    public const CATEGORY_CODE_PLASTIC_PETIT = 'TALK_PLASTIC_PETIT';

    public const CATEGORY_CODE_BEAUTY = 'TALK_BEAUTY';

    public const CATEGORY_CODE_DAILY = 'TALK_DAILY';

    public const CATEGORY_CODE_SECRET = 'TALK_SECRET';

    public const array CATEGORY_CODES = [
        self::CATEGORY_CODE_PLASTIC_PETIT,
        self::CATEGORY_CODE_BEAUTY,
        self::CATEGORY_CODE_DAILY,
        self::CATEGORY_CODE_SECRET,
    ];

    public const int MAX_IMAGE_COUNT = 4;

    protected $table = 'talks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'title',
        'content',
        'status',
        'author_ip',
        'is_pinned',
        'pinned_order',
        'view_count',
        'comment_count',
        'like_count',
        'save_count',
    ];

    protected $casts = [
        'author_id' => 'integer',
        'is_pinned' => 'boolean',
        'pinned_order' => 'integer',
        'view_count' => 'integer',
        'comment_count' => 'integer',
        'like_count' => 'integer',
        'save_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'is_pinned' => false,
        'pinned_order' => 0,
        'view_count' => 0,
        'comment_count' => 0,
        'like_count' => 0,
        'save_count' => 0,
    ];

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function categoryCodes(): array
    {
        return self::CATEGORY_CODES;
    }

    public function isStatusChangeLocked(): bool
    {
        return false;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TalkComment::class, 'talk_id')
            ->orderBy('id');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(TalkPoll::class, 'talk_id');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(TalkSave::class, 'talk_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'images')
            ->ordered();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return TalkFactory::new();
    }
}

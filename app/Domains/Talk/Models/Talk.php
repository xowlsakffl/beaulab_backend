<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Concerns\HasAdminNotes;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Database\Factories\TalkFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Talk 역할 정의.
 * 토크 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Talk extends Model
{
    use HasAdminNotes, HasAuditLogs, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'talks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'title',
        'content',
        'status',
        'is_visible',
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
        'is_visible' => 'boolean',
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
        'is_visible' => true,
        'is_pinned' => false,
        'pinned_order' => 0,
        'view_count' => 0,
        'comment_count' => 0,
        'like_count' => 0,
        'save_count' => 0,
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TalkComment::class, 'talk_id')
            ->orderBy('id');
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

<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Concerns\HasOperationHistories;
use Database\Factories\TalkCommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * TalkComment 역할 정의.
 * 토크 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class TalkComment extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const POST_STATUS_NORMAL = 'POST_NORMAL';

    public const POST_STATUS_AUTO_BLIND = 'POST_AUTO_BLIND';

    public const POST_STATUS_USER_DELETE = 'POST_USER_DELETE';

    public const POST_STATUS_ADMIN_STOP = 'POST_ADMIN_STOP';

    public const array STATUS_CHANGE_LOCKED_POST_STATUSES = [
        self::POST_STATUS_AUTO_BLIND,
        self::POST_STATUS_USER_DELETE,
        self::POST_STATUS_ADMIN_STOP,
    ];

    protected $table = 'talk_comments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_id',
        'parent_id',
        'author_id',
        'content',
        'status',
        'post_status',
        'author_ip',
        'like_count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'talk_id' => 'integer',
        'parent_id' => 'integer',
        'author_id' => 'integer',
        'like_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'post_status' => self::POST_STATUS_NORMAL,
        'like_count' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'is_reply',
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
    public static function postStatuses(): array
    {
        return [
            self::POST_STATUS_NORMAL,
            self::POST_STATUS_AUTO_BLIND,
            self::POST_STATUS_USER_DELETE,
            self::POST_STATUS_ADMIN_STOP,
        ];
    }

    /**
     * @return list<string>
     */
    public static function categoryCodes(): array
    {
        return Talk::categoryCodes();
    }

    public function isStatusChangeLocked(): bool
    {
        return in_array((string) $this->post_status, self::STATUS_CHANGE_LOCKED_POST_STATUSES, true);
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class, 'talk_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'author_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(TalkCommentMention::class, 'talk_comment_id')
            ->orderBy('id');
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            AccountUser::class,
            'talk_comment_mentions',
            'talk_comment_id',
            'mentioned_user_id'
        )->withPivot(['mentioned_by_user_id', 'mention_text', 'start_offset', 'end_offset'])
            ->withTimestamps();
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function isRootComment(): bool
    {
        return $this->parent_id === null;
    }

    public function getIsReplyAttribute(): bool
    {
        return $this->isReply();
    }

    protected static function booted(): void
    {
        static::saving(static function (self $comment): void {
            if ($comment->parent_id === null) {
                return;
            }

            $parent = self::query()
                ->select(['id', 'talk_id', 'parent_id'])
                ->find((int) $comment->parent_id);

            if (! $parent instanceof self || ! $parent->isRootComment()) {
                throw new InvalidArgumentException('대댓글은 최상위 댓글에만 작성할 수 있습니다.');
            }

            if ((int) $parent->talk_id !== (int) $comment->talk_id) {
                throw new InvalidArgumentException('대댓글은 부모 댓글과 같은 토크에만 작성할 수 있습니다.');
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return TalkCommentFactory::new();
    }
}

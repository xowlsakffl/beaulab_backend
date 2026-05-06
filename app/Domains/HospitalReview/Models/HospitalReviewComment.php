<?php

namespace App\Domains\HospitalReview\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

final class HospitalReviewComment extends Model
{
    use HasAuditLogs, HasOperationHistories, SoftDeletes;

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

    protected $table = 'hospital_review_comments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hospital_review_id',
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
        'hospital_review_id' => 'integer',
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

    public function isStatusChangeLocked(): bool
    {
        return in_array((string) $this->post_status, self::STATUS_CHANGE_LOCKED_POST_STATUSES, true);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(HospitalReview::class, 'hospital_review_id');
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
        return $this->hasMany(HospitalReviewCommentMention::class, 'hospital_review_comment_id')
            ->orderBy('id');
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            AccountUser::class,
            'hospital_review_comment_mentions',
            'hospital_review_comment_id',
            'mentioned_user_id'
        )->withPivot(['mentioned_by_user_id', 'mention_text'])
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
                ->select(['id', 'hospital_review_id', 'parent_id'])
                ->find((int) $comment->parent_id);

            if (! $parent instanceof self || ! $parent->isRootComment()) {
                throw new InvalidArgumentException('대댓글은 최상위 댓글에만 작성할 수 있습니다.');
            }

            if ((int) $parent->hospital_review_id !== (int) $comment->hospital_review_id) {
                throw new InvalidArgumentException('대댓글은 부모 댓글과 같은 병원후기에만 작성할 수 있습니다.');
            }
        });
    }
}

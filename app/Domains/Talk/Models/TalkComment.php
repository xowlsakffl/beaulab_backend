<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Concerns\HasAdminNotes;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Database\Factories\TalkCommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TalkComment extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogs, HasAdminNotes;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_talk_comments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hospital_talk_id',
        'parent_id',
        'author_id',
        'content',
        'status',
        'is_visible',
        'author_ip',
        'like_count',
    ];

    protected $casts = [
        'hospital_talk_id' => 'integer',
        'parent_id' => 'integer',
        'author_id' => 'integer',
        'is_visible' => 'boolean',
        'like_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'is_visible' => true,
        'like_count' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'is_reply',
    ];

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class, 'hospital_talk_id');
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
        return $this->hasMany(TalkCommentMention::class, 'hospital_talk_comment_id')
            ->orderBy('id');
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            AccountUser::class,
            'hospital_talk_comment_mentions',
            'hospital_talk_comment_id',
            'mentioned_user_id'
        )->withPivot(['mentioned_by_user_id', 'mention_text', 'start_offset', 'end_offset'])
            ->withTimestamps();
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function getIsReplyAttribute(): bool
    {
        return $this->isReply();
    }

    protected static function newFactory(): Factory
    {
        return TalkCommentFactory::new();
    }
}

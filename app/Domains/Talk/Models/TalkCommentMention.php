<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TalkCommentMention extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_talk_comment_mentions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hospital_talk_comment_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'mention_text',
        'start_offset',
        'end_offset',
    ];

    protected $casts = [
        'hospital_talk_comment_id' => 'integer',
        'mentioned_user_id' => 'integer',
        'mentioned_by_user_id' => 'integer',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TalkComment::class, 'hospital_talk_comment_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'mentioned_user_id');
    }

    public function mentionedByUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'mentioned_by_user_id');
    }
}

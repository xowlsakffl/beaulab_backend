<?php

namespace App\Domains\Talk\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TalkCommentMention 역할 정의.
 * 토크 도메인의 Eloquent 모델로, �
�븨, 愿怨? ?�
�퐫?? ?곹깭 ?곸닔瑜??쒓납??紐⑥븘 ?꾨찓???곗씠???묎렐 湲곗????쒓났?쒕떎.
 */
final class TalkCommentMention extends Model
{
    use HasAuditLogs;

    protected $table = 'talk_comment_mentions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_comment_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'mention_text',
        'start_offset',
        'end_offset',
    ];

    protected $casts = [
        'talk_comment_id' => 'integer',
        'mentioned_user_id' => 'integer',
        'mentioned_by_user_id' => 'integer',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TalkComment::class, 'talk_comment_id');
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

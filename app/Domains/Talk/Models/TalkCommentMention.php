<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TalkCommentMention 역할 정의.
 * 토크 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
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

<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TalkPollVote 역할 정의.
 * 사용자가 토크 투표에서 선택한 항목을 관리한다.
 */
final class TalkPollVote extends Model
{
    protected $table = 'talk_poll_votes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_poll_id',
        'talk_poll_option_id',
        'user_id',
    ];

    protected $casts = [
        'talk_poll_id' => 'integer',
        'talk_poll_option_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(TalkPoll::class, 'talk_poll_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(TalkPollOption::class, 'talk_poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'user_id');
    }
}

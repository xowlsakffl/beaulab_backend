<?php

namespace App\Domains\Talk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TalkPollOption 역할 정의.
 * 토크 투표의 선택 항목과 투표 수를 관리한다.
 */
final class TalkPollOption extends Model
{
    protected $table = 'talk_poll_options';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_poll_id',
        'content',
        'sort_order',
        'vote_count',
    ];

    protected $casts = [
        'talk_poll_id' => 'integer',
        'sort_order' => 'integer',
        'vote_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(TalkPoll::class, 'talk_poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(TalkPollVote::class, 'talk_poll_option_id');
    }
}

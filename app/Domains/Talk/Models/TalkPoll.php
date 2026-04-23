<?php

namespace App\Domains\Talk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TalkPoll 역할 정의.
 * 토크 게시글에 연결되는 투표 기본 정보를 관리한다.
 */
final class TalkPoll extends Model
{
    protected $table = 'talk_polls';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_id',
        'allow_multiple',
    ];

    protected $casts = [
        'talk_id' => 'integer',
        'allow_multiple' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class, 'talk_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TalkPollOption::class, 'talk_poll_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(TalkPollVote::class, 'talk_poll_id');
    }
}

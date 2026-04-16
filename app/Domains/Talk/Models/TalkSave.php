<?php

namespace App\Domains\Talk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 토크 게시글 저장 모델.
 * 사용자별 저장 여부를 유니크하게 보관하고 talks.save_count의 기준 데이터로 사용한다.
 */
final class TalkSave extends Model
{
    protected $table = 'talk_saves';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'talk_id',
        'account_user_id',
    ];

    protected $casts = [
        'talk_id' => 'integer',
        'account_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class, 'talk_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'account_user_id');
    }
}

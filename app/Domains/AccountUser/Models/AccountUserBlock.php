<?php

namespace App\Domains\AccountUser\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 앱 사용자 간 방향성 차단 관계 모델.
 * A가 B를 차단한 상태만 저장하며, B가 A를 차단한 상태와는 별도 행으로 다룬다.
 */
final class AccountUserBlock extends Model
{
    protected $table = 'account_user_blocks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'blocker_user_id',
        'blocked_user_id',
        'blocked_at',
    ];

    protected $casts = [
        'blocker_user_id' => 'integer',
        'blocked_user_id' => 'integer',
        'blocked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'blocker_user_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'blocked_user_id');
    }
}

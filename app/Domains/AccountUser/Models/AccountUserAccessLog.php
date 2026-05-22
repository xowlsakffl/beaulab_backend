<?php

namespace App\Domains\AccountUser\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountUserAccessLog 역할 정의.
 * 앱/웹이 foreground 상태로 열린 시점의 사용자 접속 기록을 저장한다.
 */
final class AccountUserAccessLog extends Model
{
    protected $table = 'account_user_access_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_user_id',
        'ip',
        'user_agent',
        'platform',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'account_user_id');
    }
}

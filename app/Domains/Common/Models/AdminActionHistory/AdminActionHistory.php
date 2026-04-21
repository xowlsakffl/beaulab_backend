<?php

namespace App\Domains\Common\Models\AdminActionHistory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AdminActionHistory 역할 정의.
 * 운영 화면에 노출할 관리자 액션 이력을 공통 폴리모픽 모델로 관리한다.
 */
final class AdminActionHistory extends Model
{
    public const string ACTION_VISIBILITY_UPDATED = 'VISIBILITY_UPDATED';

    protected $table = 'admin_action_histories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'target_type',
        'target_id',
        'actor_type',
        'actor_id',
        'action',
        'field',
        'before_value',
        'after_value',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'actor_id' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id');
    }
}

<?php

namespace App\Domains\Common\OperationHistory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * OperationHistory 역할 정의.
 * 운영 화면에 노출할 운영/시스템 액션 이력을 공통 폴리모픽 모델로 관리한다.
 */
final class OperationHistory extends Model
{
    public const string ACTION_CREATED = 'CREATED';

    public const string ACTION_UPDATED = 'UPDATED';

    public const string ACTION_STATUS_UPDATED = 'STATUS_UPDATED';

    public const string ACTION_DELETED = 'DELETED';

    public const string ACTOR_KIND_STAFF = 'STAFF';
    public const string ACTOR_KIND_HOSPITAL = 'HOSPITAL';
    public const string ACTOR_KIND_BEAUTY = 'BEAUTY';
    public const string ACTOR_KIND_USER = 'USER';
    public const string ACTOR_KIND_SYSTEM = 'SYSTEM';
    public const string ACTOR_KIND_UNKNOWN = 'UNKNOWN';

    protected $table = 'operation_histories';

    protected $with = ['changes'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'target_type',
        'target_id',
        'actor_type',
        'actor_id',
        'actor_kind',
        'action',
        'batch_uuid',
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

    public function changes(): HasMany
    {
        return $this->hasMany(OperationHistoryChange::class, 'operation_history_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getFieldAttribute(): ?string
    {
        return $this->changes->first()?->field_key;
    }

    public function getBeforeValueAttribute(): mixed
    {
        return $this->changes->first()?->before_value;
    }

    public function getAfterValueAttribute(): mixed
    {
        return $this->changes->first()?->after_value;
    }
}

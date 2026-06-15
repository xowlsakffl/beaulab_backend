<?php

namespace App\Domains\Common\OperationHistory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OperationHistoryChange 역할 정의.
 * 운영 히스토리 1건에 포함되는 필드별 변경 전/후 값을 관리한다.
 */
final class OperationHistoryChange extends Model
{
    protected $table = 'operation_history_changes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'operation_history_id',
        'field_key',
        'field_label',
        'before_value',
        'after_value',
        'before_display',
        'after_display',
        'sort_order',
    ];

    protected $casts = [
        'operation_history_id' => 'integer',
        'before_value' => 'array',
        'after_value' => 'array',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(OperationHistory::class, 'operation_history_id');
    }
}

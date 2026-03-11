<?php

namespace App\Domains\Common\Models\AdminNote;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AdminNote extends Model
{
    use SoftDeletes, HasAuditLogs;

    protected $table = 'admin_notes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'target_type',
        'target_id',
        'note',
        'is_internal',
        'created_by_staff_id',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'is_internal' => 'boolean',
        'created_by_staff_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }

    public function scopeForTarget(Builder $query, Model $target): Builder
    {
        return $query
            ->where('target_type', $target::class)
            ->where('target_id', $target->getKey());
    }
}

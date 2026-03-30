<?php

namespace App\Domains\Common\Models\AdminNote;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Support\AdminNote\AdminNoteActorRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'creator_type',
        'creator_id',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'is_internal' => 'boolean',
        'creator_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function creator(): MorphTo
    {
        return $this->morphTo('creator', 'creator_type', 'creator_id');
    }

    public function scopeForTarget(Builder $query, Model $target): Builder
    {
        return $query
            ->where('target_type', $target::class)
            ->where('target_id', $target->getKey());
    }

    public function scopeVisibleTo(Builder $query, mixed $actor): Builder
    {
        if (AdminNoteActorRegistry::isStaffActor($actor)) {
            return $query;
        }

        return $query->where('is_internal', false);
    }
}

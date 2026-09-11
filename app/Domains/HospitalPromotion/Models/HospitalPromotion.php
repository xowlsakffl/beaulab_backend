<?php

namespace App\Domains\HospitalPromotion\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

final class HospitalPromotion extends Model
{
    use HasAuditLogs, HasOperationHistories;

    public const SIDES = ['LEFT', 'RIGHT'];

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    public const SCHEDULE_FIELDS = ['side', 'slot', 'start_date', 'end_date'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'title', 'content', 'side', 'slot', 'start_date', 'end_date', 'status',
        'created_by_staff_id', 'updated_by_staff_id',
    ];

    protected $attributes = ['status' => 'INACTIVE', 'click_count' => 0];

    protected $casts = [
        'slot' => 'integer', 'click_count' => 'integer',
        'start_date' => 'immutable_date', 'end_date' => 'immutable_date',
        'created_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime',
    ];

    public static function today(): string
    {
        return CarbonImmutable::now('Asia/Seoul')->toDateString();
    }

    public function progress(?string $today = null): string
    {
        $today ??= self::today();

        return $this->start_date->toDateString() > $today ? 'UPCOMING'
            : ($this->end_date->toDateString() < $today ? 'ENDED' : 'CURRENT');
    }

    public function scopeCurrent(Builder $query, string $today): Builder
    {
        return $query->where('start_date', '<=', $today)->where('end_date', '>=', $today);
    }

    public function scopePublicCurrent(Builder $query, string $today): Builder
    {
        return $query->current($today)->where('status', 'ACTIVE');
    }

    public function banner(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'banner');
    }

    public function editorImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'editor_images')->orderBy('sort_order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }
}

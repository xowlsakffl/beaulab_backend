<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class HospitalChatBotQna extends Model
{
    use HasAuditLogs, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_chatbot_qnas';

    protected $fillable = [
        'folder_id',
        'category',
        'question',
        'question_key',
        'answer',
        'status',
        'sort_order',
        'created_by_staff_id',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'folder_id' => 'integer',
        'sort_order' => 'integer',
        'created_by_staff_id' => 'integer',
        'updated_by_staff_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'sort_order' => 0,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'created_by_staff_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'updated_by_staff_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    public static function normalizeQuestion(string $question): string
    {
        return Str::lower(Str::squish($question));
    }

    public static function questionKey(string $question): string
    {
        return hash('sha256', self::normalizeQuestion($question));
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(HospitalChatBotQnaFolder::class, 'folder_id');
    }
}

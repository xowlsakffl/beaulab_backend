<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 병의원 챗봇 공통 설정.
 * 전체 병의원에 하나만 존재하며 상담원 연결을 즉시 중단할 때 사용한다.
 */
final class HospitalChatBotSetting extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_chatbot_settings';

    protected $fillable = [
        'id',
        'is_force_closed',
        'public_holiday_codes',
        'break_starts_at',
        'break_ends_at',
        'force_closed_reason',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'is_force_closed' => 'boolean',
        'public_holiday_codes' => 'array',
        'updated_by_staff_id' => 'integer',
    ];

    protected $attributes = [
        'is_force_closed' => false,
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'updated_by_staff_id');
    }

    /** 설정 행이 없으면 기본값(중단 아님)으로 본다. */
    public static function current(): self
    {
        return self::query()->orderBy('id')->first() ?? new self;
    }
}

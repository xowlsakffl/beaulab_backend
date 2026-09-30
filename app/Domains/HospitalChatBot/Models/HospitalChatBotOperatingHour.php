<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalChatBotOperatingHour extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_chatbot_operating_hours';

    protected $fillable = [
        'day_of_week',
        'is_open',
        'opens_at',
        'closes_at',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_open' => 'boolean',
        'updated_by_staff_id' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'updated_by_staff_id');
    }
}

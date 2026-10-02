<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalChatBotClosureDate extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_chatbot_closure_dates';

    protected $fillable = [
        'closure_date',
        'end_date',
        'reason',
        'updated_by_staff_id',
    ];

    protected $casts = [
        'closure_date' => 'immutable_date:Y-m-d',
        'end_date' => 'immutable_date:Y-m-d',
        'updated_by_staff_id' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'updated_by_staff_id');
    }
}

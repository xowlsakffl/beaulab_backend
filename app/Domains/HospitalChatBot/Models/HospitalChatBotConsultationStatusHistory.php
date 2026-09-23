<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalChatBotConsultationStatusHistory extends Model
{
    protected $table = 'hospital_chatbot_consultation_status_histories';

    public $timestamps = false;

    protected $fillable = [
        'consultation_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'reason',
        'occurred_at',
        'request_id',
    ];

    protected $casts = [
        'consultation_id' => 'integer',
        'actor_id' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(HospitalChatBotConsultation::class, 'consultation_id');
    }
}

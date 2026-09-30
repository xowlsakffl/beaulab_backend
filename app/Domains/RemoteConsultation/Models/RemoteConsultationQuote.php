<?php

namespace App\Domains\RemoteConsultation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RemoteConsultationQuote extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['matched_categories' => 'array', 'quoted_categories' => 'array', 'recommended_events' => 'array', 'total_amount' => 'integer', 'answered_at' => 'datetime'];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(RemoteConsultation::class, 'remote_consultation_id');
    }
}
